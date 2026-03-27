<?php
/******************************************************************
 * connect_images.php
 * ---------------------------------------------------------------
 * Shows draggable 360° thumbnails, lets the user draw connections,
 * and now REMEMBERS thumbnail X/Y positions after “Submit to Google”.
 ******************************************************************/
session_start();
include_once './config.php';
$user = $_SESSION['user'];
$userId = $user['id'];
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$storeId = isset($_GET['storeId']) ? intval($_GET['storeId']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>360° Image Connector with Viewer</title>

    <!-- CSS -->
    <link rel="stylesheet" href="./360Software/360Software.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photo-sphere-viewer@4.8.1/dist/photo-sphere-viewer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <style>
        body {
            margin: 0;
            overflow: hidden;
            font-family: sans-serif;
        }

        #container {
            position: relative;
            width: 100vw;
            height: calc(100vh - 220px);
            background: #f0f0f0;
        }

        .image-box {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 3px solid #444;
            cursor: move;
            border-radius: 4px;
            user-select: none;
            object-fit: cover;
        }

        svg#lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 99999;
        }

        #viewerModal {
            display: none;
            position: fixed;
            top: 90px;
            left: 231px;
            right: 231px;
            bottom: 90px;
            background: rgba(0, 0, 0, .9);
            z-index: 9999;
        }

        #viewer {
            width: 100%;
            height: 100%;
        }

        #viewerModal .close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 36px;
            color: #fff;
            cursor: pointer;
            z-index: 10000;
        }

        #viewerModal .close-btn:hover {
            opacity: .7;
        }

        *,
        ::after,
        ::before {
            box-sizing: border-box;
            z-index: 0;
        }
    </style>
</head>

<body>
    <?php include_once './navbar.php'; ?>

    <input type="hidden" id="storeId" value="<?= $storeId ?>">

    <h2 class="text-center">Connect Images – Please select at least one image.</h2>

    <div class="streetBtndiv">

        <div class="streetbtn text-center">



            <?php
            $query = "SELECT savecon FROM 360g_stores WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $storeId);
            $stmt->execute();
            $stmt->bind_result($saveCon);
            $hassavecon = false;

            if ($stmt->fetch()) {
                if ($saveCon == 1) {
                    $hassavecon = true;
                }
            }
            $stmt->close();
            ?>

            <?php if (!$hassavecon): ?>
                <button id="savedatabtn" class="btn btn-primary">Save Connection</button>
            <?php endif; ?>

            <?php if ($hassavecon): ?>
                <div id="subpre">
                    <button id="submitBtn" class="btn btn-primary">Submit to Google</button>
                    <div class="previewdiv btn btn-primary">
                        <a href="./360Previewer.php?id=<?= $storeId ?>" class="text-light">Preview Connection</a>
                    </div>
                </div>
            <?php endif; ?>



            <div class="gobackbtn btn btn-primary">
                <a href="./g_storeimages.php?id=<?php echo $storeId ?>" class="text-light">Go Back to Store Images</a>
            </div>
        </div>
    </div>

    <div id="container">
        <svg id="lines">
            <defs>
                <marker id="arrow-right" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6"
                    orient="auto">
                    <path d="M 0 0 L 10 5 L 0 10 z" fill="#0077cc" />
                </marker>
                <marker id="arrow-left" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6"
                    orient="auto-start-reverse">
                    <path d="M 0 0 L 10 5 L 0 10 z" fill="#0077cc" />
                </marker>
            </defs>
        </svg>

        <?php
        /* ------------------------------------------------------------------
         *  Fetch thumbnails (+ saved positions) and render them
         * ----------------------------------------------------------------*/
        $sql = "SELECT id, imageName, imageLat, imageLong, imageHeading, imageAlt,
               `360photoId`, `360placeId`, orders, posX, posY
        FROM 360g_storeimages
        WHERE storeID = ? 
          AND `360photoId` IS NOT NULL AND `360photoId` != '' 
          AND `360placeId` IS NOT NULL AND `360placeId` != ''
        ORDER BY orders ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $storeId);
        $stmt->execute();
        $resultImages = $stmt->get_result();
        //$images = $resultImages->fetch_all(MYSQLI_ASSOC);
        


        if ($resultImages->num_rows === 0) {
            echo "<p style='padding:20px; color:#666;'>No images found for this store.</p>";
        } else {
            // fetch store name once (to build file path)
            $storeName = $conn->query("SELECT storeName FROM 360g_stores WHERE id = $storeId")
                ->fetch_assoc()['storeName'] ?? '';
            $index = 0;

            while ($row = $resultImages->fetch_assoc()) {

                $left = is_null($row['posX']) ? (100 + $index * 270) : intval($row['posX']);
                $top = is_null($row['posY']) ? 33 : intval($row['posY']);
                $imagePath = "upload/$userId/$storeName/" . $row['imageName'];

                if (!file_exists($imagePath)) {
                    $index++;
                    continue;
                }

                echo "
        <div class='image-box'
             style='left:{$left}px; top:{$top}px; width:250px; text-align:center;height: auto;'
             data-id='{$row['id']}'
             data-photoid='{$row['360photoId']}'
             data-lat='{$row['imageLat']}'
             data-lon='{$row['imageLong']}'
             data-heading='{$row['imageHeading']}'
             data-altitude='{$row['imageAlt']}'
             data-orders='{$row['orders']}'>
            <div style='position:relative;'>
                <img src='$imagePath' style='width:244px; display:block;'
                     data-id='{$row['id']}'
                     data-photoid='{$row['360photoId']}'
                     data-lat='{$row['imageLat']}'
                     data-lon='{$row['imageLong']}'
                     data-heading='{$row['imageHeading']}'
                     data-altitude='{$row['imageAlt']}'
                     data-orders='{$row['orders']}' />

                <p style='position:absolute; top:0; left:50%; transform:translateX(-50%);
                          margin:0; color:#fff; background:rgba(0,0,0,.5);
                          padding:2px 6px; border-radius:4px; font-size:12px;'>
                    " . ($index + 1) . "
                </p>
            </div>
        </div>";
                $index++;
            }
        }
        $stmt->close();
        ?>
    </div><!-- /container -->

    <!-- 360-viewer modal -->
    <div id="viewerModal">
        <button id="closeViewer" class="close-btn" title="Close viewer">&#10005;</button>
        <div id="viewer"></div>
    </div>

    <!-- JS libraries -->
    <script src="https://cdn.jsdelivr.net/npm/uevent@2/browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/photo-sphere-viewer@4.8.1/dist/photo-sphere-viewer.js"></script>


    <script>
        // document.addEventListener('DOMContentLoaded',()=>{
        const loader = document.getElementById('pageLoader');
        // Show loader on page load
        window.addEventListener('load', () => {
            loader.classList.add('hidden');
        });

        // Show loader before unload (navigation)
        window.addEventListener('beforeunload', () => {
            loader.classList.remove('hidden');
        });
        // })

    </script>
    <script>
        /* ---------------------------------------------------------------
         * Globals
         * -------------------------------------------------------------*/
        const container = document.getElementById('container');
        const linesSVG = document.getElementById('lines');
        const viewerModal = document.getElementById('viewerModal');
        const viewerContainer = document.getElementById('viewer');
        const closeViewerBtn = document.getElementById('closeViewer');
        const storeId = document.getElementById('storeId').value;

        let images = [];           // node list of .image-box
        let connections = [];           // [ [fromDiv, toDiv], ... ]
        let selected = null;         // currently-selected thumbnail
        let viewerInstance;

        /* ---------------------------------------------------------------
         * Helper: build positions array for POST
         * -------------------------------------------------------------*/
        function buildPositionArray() {
            return images.map(img => ({
                id: parseInt(img.dataset.id, 10),
                posX: parseInt(img.style.left, 10) || 0,
                posY: parseInt(img.style.top, 10) || 0
            }));
        }

        /* ---------------------------------------------------------------
         * Existing helpers (makeDraggable, drawConnections, buildConnectionArray)
         * (unchanged except minor tweaks for modern JS)
         * -------------------------------------------------------------*/
        function makeDraggable(el) {
            let offsetX = 0, offsetY = 0;
            el.addEventListener('mousedown', e => {
                offsetX = e.offsetX; offsetY = e.offsetY;
                const move = e2 => {
                    const rect = container.getBoundingClientRect();
                    let newLeft = e2.clientX - rect.left - offsetX;
                    let newTop = e2.clientY - rect.top - offsetY;
                    newLeft = Math.max(0, Math.min(newLeft, rect.width - el.offsetWidth));
                    newTop = Math.max(0, Math.min(newTop, rect.height - el.offsetHeight));
                    el.style.left = newLeft + 'px';
                    el.style.top = newTop + 'px';
                    drawConnections();
                };
                const up = () => {
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', up);
                };
                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', up);
            });

            el.addEventListener('click', () => {
                if (!selected) {
                    selected = el; el.style.borderColor = 'blue';
                } else if (selected !== el) {
                    const exists = connections.some(([a, b]) =>
                        (a === selected && b === el) || (a === el && b === selected));
                    if (!exists) connections.push([selected, el]);
                    console.log('Updated Connection Array:', buildConnectionArray());
                    selected.style.borderColor = '#444';
                    selected = null; drawConnections();
                } else {
                    selected.style.borderColor = '#444'; selected = null;
                }
            });

            const img = el.querySelector('img');
            img?.addEventListener('dblclick', e => {
                e.stopPropagation();
                show360Viewer(img.src);
            });
        }
        function drawConnections() {
            // keep <defs>
            const defs = linesSVG.querySelector('defs').outerHTML;
            linesSVG.innerHTML = defs;

            connections.forEach(([from, to], idx) => {
                const x1 = from.offsetLeft + from.offsetWidth / 2,
                    y1 = from.offsetTop + from.offsetHeight / 2,
                    x2 = to.offsetLeft + to.offsetWidth / 2,
                    y2 = to.offsetTop + to.offsetHeight / 2;

                const dx = (x2 - x1) / 2;

                /* ------ put both path + label in a <g> so we can attach ONE listener --- */
                const grp = document.createElementNS('http://www.w3.org/2000/svg', 'g');

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', `M ${x1},${y1} C ${x1 + dx},${y1} ${x2 - dx},${y2} ${x2},${y2}`);
                path.setAttribute('stroke', '#0077cc');
                path.setAttribute('stroke-width', '4');
                path.setAttribute('fill', 'none');
                path.setAttribute('marker-start', 'url(#arrow-left)');
                path.setAttribute('marker-end', 'url(#arrow-right)');
                path.setAttribute('pointer-events', 'stroke');          // only stroke is clickable
                grp.appendChild(path);

                const angle = Math.atan2(y2 - y1, x2 - x1),
                    offset = 20,
                    textX = x1 - Math.cos(angle) * offset,
                    textY = y1 - Math.sin(angle) * offset;
                const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                label.setAttribute('x', textX);
                label.setAttribute('y', textY);
                label.setAttribute('fill', 'red');
                label.setAttribute('font-size', '16');
                label.setAttribute('font-weight', 'bold');
                label.setAttribute('text-anchor', 'middle');
                label.setAttribute('alignment-baseline', 'middle');
                label.setAttribute('pointer-events', 'none');           // ← **NEW**
                label.textContent = (idx + 1).toString();
                grp.appendChild(label);

                /* ---- one dbl-click handler removes the connection and redraws ---- */
                grp.addEventListener('dblclick', () => {
                    connections = connections.filter(([a, b]) =>
                        !((a === from && b === to) || (a === to && b === from)));
                    drawConnections();          // redraw without this connection
                });

                linesSVG.appendChild(grp);
            });
        }
        function buildConnectionArray() {
            const photoMap = new Map();
            images.forEach(img => {
                const id = img.dataset.photoid;
                const lat = parseFloat(img.dataset.lat), lon = parseFloat(img.dataset.lon),
                    heading = parseFloat(img.dataset.heading);
                const pose = {};
                if (!isNaN(lat) && !isNaN(lon)) pose.latLngPair = { latitude: lat, longitude: lon };
                if (!isNaN(heading)) pose.heading = heading;
                photoMap.set(id, { photoId: { id }, pose, connections: new Set() });
            });
            connections.forEach(([f, t]) => {
                const from = f.dataset.photoid, to = t.dataset.photoid;
                photoMap.get(from)?.connections.add(to);
                photoMap.get(to)?.connections.add(from); // bidirectional
            });
            const res = [];
            photoMap.forEach(({ photoId, pose, connections }) => {
                res.push({
                    photo: {
                        photoId, pose,
                        connections: [...connections].map(id => ({ target: { id } }))
                    },
                    updateMask: 'pose.lat_lng_pair,pose.heading,connections'
                });
            });
            return res;
        }


        /* ---------------------------------------------------------------
         * 360-viewer helpers
         * -------------------------------------------------------------*/
        function show360Viewer(url) {
            viewerModal.style.display = 'block';
            if (viewerInstance) viewerInstance.setPanorama(url);
            else viewerInstance = new PhotoSphereViewer.Viewer({
                container: viewerContainer, panorama: url, navbar: true
            });
        }
        closeViewerBtn?.addEventListener('click', () => {
            viewerModal.style.display = 'none';
            if (viewerInstance) { viewerInstance.destroy(); viewerInstance = null; }
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') viewerModal.style.display = 'none';
        });

        /* ---------------------------------------------------------------
         * Init: make thumbnails draggable
         * -------------------------------------------------------------*/
        window.addEventListener('DOMContentLoaded', () => {
            images = [...document.querySelectorAll('.image-box')];
            images.forEach(makeDraggable);

            // reload existing connection arrows from DB → JS
            savedConnections.forEach(([fromId, toId]) => {
                const from = images.find(img => img.dataset.photoid === fromId);
                const to = images.find(img => img.dataset.photoid === toId);
                if (from && to) connections.push([from, to]);
            });
            drawConnections();
        });

        /* ---------------------------------------------------------------
         * Submit: send photos + positions to submit.php
         * -------------------------------------------------------------*/
        document.getElementById('submitBtn')?.addEventListener('click', () => {
            const payload = {
                updatePhotoRequests: buildConnectionArray(),
                positions: buildPositionArray()
            };

            fetch(`submit.php?storeId=${storeId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(j => {
                    alert('Submitted to Google Street View API.');
                    window.location.href = `g_storeimages.php?id=${storeId}`;
                })
                .catch(err => {
                    console.error(err);
                    alert('Error submitting to Google API.');
                });
        });

        document.getElementById('savedatabtn')?.addEventListener('click', () => {
            const payload = {
                updatePhotoRequests: buildConnectionArray(),
                positions: buildPositionArray()
            };

            fetch(`saveconnection.php?storeId=${storeId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(j => {
                    alert('Save Connection');
                    window.location.href = `create-connections.php?storeId=${storeId}`;
                })
                .catch(err => {
                    console.error(err);
                    alert('Error submitting to Google API.');
                });
        });

    </script>
    </script>



    <!-- JS snippet that injects savedConnections from DB -->
    <script>
        const savedConnections = [
            <?php
            $rows = $conn->query("SELECT from_photoid, to_photoid
                      FROM image_connections WHERE store_id=$storeId");
            while ($row = $rows->fetch_assoc()) {
                echo "['" . addslashes($row['from_photoid']) . "',
           '" . addslashes($row['to_photoid']) . "'],";
            }
            ?>
        ];
    </script>
</body>

</html>