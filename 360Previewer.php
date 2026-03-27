<?php
/* -----------------------------------------------------------
   PHP SECTION – fetch store + pano data from MySQL
   ----------------------------------------------------------- */
include_once './config.php';

if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit();
}

$user = $_SESSION['user'];
$userId = $user['id'];

$storeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* store name (for the image-path segment) */
$stmt = $conn->prepare("SELECT storeName FROM 360g_stores WHERE id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$stmt->bind_result($storeName);
$stmt->fetch();
$stmt->close();

/* all images for that store */
$stmt = $conn->prepare(
  "SELECT id, imageName, imageLat, imageLong, imageHeading, imageAlt, orders
   FROM   360g_storeimages
   WHERE  storeID = ?
   ORDER  BY orders ASC"
);
$stmt->bind_param("i", $storeId);
$stmt->execute();
$resultImages = $stmt->get_result();

/* build associative array keyed by a synthetic pano id */
$panos = [];
while ($row = $resultImages->fetch_assoc()) {
  $pid = 'pano-' . $row['id'];                            // unique pano id
  $panos[$pid] = [
    'location' => ['lat' => (float) $row['imageLat'], 'lng' => (float) $row['imageLong']],
    'description' => $row['imageAlt'] ?: 'Untitled',
    'imageName' => $row['imageName'],
    'forward' => (int) $row['imageHeading'],
    'orders' => (int) $row['orders']
  ];
}

echo '<script>const DB_PANOS = ' .
  json_encode($panos, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) .
  ';</script>';

?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>360 ° Pano Connection Preview</title>
  <style>
    html,
    body {
      height: 100%;
      margin: 0
    }

    #map {
      height: 100%;
      width: 100%
    }

    a#closeBtn {
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 9999;
      padding: 8px 14px;
      background: #fff;
      border-radius: 4px;
      font-family: sans-serif;
      text-decoration: none;
      color: #000;
      box-shadow: 0 0 6px rgb(235 5 5)
    }
  </style>
</head>

<body>
  <a id="closeBtn" href="./create-connections.php?storeId=<?= $storeId ?>">✕ Close</a>
  <input type="hidden" id="storeName" value="<?= htmlspecialchars($storeName) ?>">
  <input type="hidden" id="userId" value="<?= htmlspecialchars($userId) ?>">
  <div id="map"></div>

  <script>
    function degToRad(deg) {
      return deg * (Math.PI / 180);
    }

    function radToDeg(rad) {
      return rad * (180 / Math.PI);
    }

    function computeHeading(from, to) {
      const lat1 = degToRad(from.lat());
      const lng1 = degToRad(from.lng());
      const lat2 = degToRad(to.lat());
      const lng2 = degToRad(to.lng());

      const dLng = lng2 - lng1;
      const y = Math.sin(dLng) * Math.cos(lat2);
      const x = Math.cos(lat1) * Math.sin(lat2) -
        Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
      return (radToDeg(Math.atan2(y, x)) + 360) % 360;
    }

    function normalizeAngle(angle) {
      return (angle + 360) % 360;
    }

    function angleDiff(a, b) {
      const diff = normalizeAngle(a - b);
      return diff > 180 ? diff - 360 : diff;
    }

    function buildPanos(db) {
      const storeName = document.getElementById('storeName').value;
      const userId = document.getElementById('userId').value;
      const keys = Object.keys(db);
      const out = {};

      keys.forEach(pid => {
        const rec = db[pid];
        const currentLatLng = new google.maps.LatLng(rec.location.lat, rec.location.lng);
        const forward = rec.forward;

        const closest = {
          Forward: { pid: null, heading: 0, dist: Infinity },
          Left: { pid: null, heading: 0, dist: Infinity },
          Right: { pid: null, heading: 0, dist: Infinity },
          Back: { pid: null, heading: 0, dist: Infinity }
        };

        keys.forEach(otherPid => {
          if (otherPid === pid) return;
          const other = db[otherPid];
          const otherLatLng = new google.maps.LatLng(other.location.lat, other.location.lng);
          const headingToOther = computeHeading(currentLatLng, otherLatLng);

          const distance = google.maps.geometry.spherical.computeDistanceBetween(currentLatLng, otherLatLng);
          const delta = angleDiff(headingToOther, forward);

          if (Math.abs(delta) <= 45 && distance < closest.Forward.dist) {
            closest.Forward = { pid: otherPid, heading: headingToOther, dist: distance };
          } else if (delta > 45 && delta <= 135 && distance < closest.Right.dist) {
            closest.Right = { pid: otherPid, heading: headingToOther, dist: distance };
          } else if (Math.abs(delta) > 135 && distance < closest.Back.dist) {
            closest.Back = { pid: otherPid, heading: headingToOther, dist: distance };
          } else if (delta < -45 && delta >= -135 && distance < closest.Left.dist) {
            closest.Left = { pid: otherPid, heading: headingToOther, dist: distance };
          }
        });

        const directionOffsets = {
          Forward: 0,
          Right: 15,
          Back: -15,
          Left: -30
        };

        const links = [];
        Object.entries(closest).forEach(([label, info]) => {
          if (info.pid) {
            links.push({
              heading: (info.heading + directionOffsets[label] + 360) % 360,
              pano: info.pid,
              description: label
            });
          }
        });

        out[pid] = {
          location: {
            pano: pid,
            latLng: currentLatLng
          },
          description: rec.description,
          tiles: {
            tileSize: new google.maps.Size(8192, 4096),
            worldSize: new google.maps.Size(8192, 4096),
            centerHeading: rec.forward,
            getTileUrl: () => `/360software/upload/${userId}/${storeName}/${rec.imageName}`
          },
          links
        };
      });
      return out;
    }

    function addPanoCircles(map, PANOS, viewer) {
      Object.keys(PANOS).forEach(pid => {
        const loc = PANOS[pid].location.latLng;
        const circle = new google.maps.Circle({
          strokeColor: "#2196F3",
          strokeOpacity: 0.8,
          strokeWeight: 2,
          fillColor: "#2196F3",
          fillOpacity: 0.5,
          map: map,
          center: loc,
          radius: 1.2,
          clickable: true
        });

        circle.addListener('click', () => {
          viewer.setPano(pid);
        });
      });
    }

    function drawPanoPath(map, PANOS) {
      const keys = Object.keys(PANOS);
      const path = keys.map(pid => PANOS[pid].location.latLng);

      new google.maps.Polyline({
        path: path,
        geodesic: true,
        strokeColor: "#4CAF50",
        strokeOpacity: 0.7,
        strokeWeight: 3,
        map: map
      });
    }

    function init() {
      const PANOS = buildPanos(DB_PANOS);
      console.log(DB_PANOS);
      const firstPid = Object.keys(PANOS)[0];
      const center = PANOS[firstPid].location.latLng;

      const map = new google.maps.Map(document.getElementById('map'), {
        center: center,
        zoom: 18,
        mapTypeId: 'satellite'
      });

      const viewer = new google.maps.StreetViewPanorama(document.getElementById('map'), {
        pano: firstPid,
        panoProvider: id => PANOS[id],
        visible: true
      });

      map.setStreetView(viewer);

      function alignView(v) {
        const d = PANOS[v.getPano()];
        if (d && d.links.length) {
          v.setPov({ heading: d.links[0].heading, pitch: 0 });
        }
      }

      alignView(viewer);
      viewer.addListener('pano_changed', () => alignView(viewer));

      addPanoCircles(map, PANOS, viewer);
      drawPanoPath(map, PANOS);
    }
  </script>

  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBxFBPk3cTVxt5apqzPsUwy4v3cvnuns54&libraries=geometry&callback=init">
    </script>
</body>