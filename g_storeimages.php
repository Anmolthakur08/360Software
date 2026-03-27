<?php
include_once './config.php';
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
$user = $_SESSION['user'];
$userID = $user['id'];
$storeId = $_GET['id'];
$noImages = null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>360 Software</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>

    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="./360Software/360Software.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pannellum/build/pannellum.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-center",
            "timeOut": "3000"
        };
    </script>


    <style>
        .selectable-image {
            /* keeps default border off */
            border: 3px solid transparent;
            transition: border .15s ease-in-out;
        }

        /* put the highlight on :hover **or** while selected */
        .selectable-image:hover,
        .selectable-image.selected {
            border: 3px solid #0d6efd;
            /* Bootstrap “primary” blue */
        }

        #imageFileName {
            border: 1px solid #f0f0f0 !important;
            outline: none;
            /* Removes focus highlight */
            background-color: transparent;
            /* Optional */
            font-size: 16px;
            margin: 10px 0px 0px;
            font-weight: 600;
            color: #000;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php
    include_once './navbar.php';
    ?>

    <?php
    //-----------------------------submitimagepage-----------------------------
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['newimagemessage'])) {
        $message = $_SESSION['newimagemessage'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['newimagemessage']);
    }
    //---------------------------Update Image Message----------------------------
    if (isset($_SESSION['successupdimgmsg'])) {
        $message = $_SESSION['successupdimgmsg'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['successupdimgmsg']);
    }

    if (isset($_SESSION['errorupdimgmsg'])) {
        $message = $_SESSION['errorupdimgmsg'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['errorupdimgmsg']);
    }

    //---------------360publisherror--------------------------
    if (isset($_SESSION['360publisherror'])) {
        $message = $_SESSION['360publisherror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['360publisherror']);
    }

    //----------------------singlephotoplaceupderror----------------------
    if (isset($_SESSION['singlephotoplaceupderror'])) {
        $message = $_SESSION['singlephotoplaceupderror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['singlephotoplaceupderror']);
    }

    //------------------------------updimgplaceerror--------------
    if (isset($_SESSION['updimgplaceerror'])) {
        $message = $_SESSION['updimgplaceerror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['updimgplaceerror']);
    }

    if (isset($_SESSION['updimgplacesuccess'])) {
        $message = $_SESSION['updimgplacesuccess'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.success('$message');
        });
    </script>";
        unset($_SESSION['updimgplacesuccess']);
    }



    //-----------------------------publishallimageserror-------------------------
    if (isset($_SESSION['publishallimageserror'])) {
        $message = $_SESSION['publishallimageserror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['publishallimageserror']);
    }

    //--------------------------deleteimageerror----------------------------
    
    if (isset($_SESSION['deleteimageerror'])) {
        $message = $_SESSION['deleteimageerror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['deleteimageerror']);
    }
    //-----------------------importimgerror----------------------------------
    if (isset($_SESSION['importimgerror'])) {
        $message = $_SESSION['importimgerror'];
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.error('$message');
        });
    </script>";
        unset($_SESSION['importimgerror']);
    }
    ?>

    <?php
    $query = "SELECT 360placeId FROM 360g_storeimages WHERE storeID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $storeId);
    $stmt->execute();
    $stmt->bind_result($placeId);
    $has360placeId = false;
    if ($stmt->fetch()) {
        // Check if the fetched 360placeId is not null or empty
        if (!empty($placeId)) {
            $has360placeId = true;
        }
    }
    $stmt->close();

    $querysql = "SELECT * FROM 360g_storeimages WHERE storeID =$storeId order by id ASC";
    $result = $conn->query($querysql);
    $results = null;
    if ($result->num_rows > 0) {
        $results = true;
    }
    $imagesArray = [];

    while ($row = $result->fetch_assoc()) {
        $imagesArray[] = $row;
    }


    ?>



    <!-- Button trigger modal -->
    <div class="navdiv">
        <div class="top-nav-btn" style="display: flex; justify-content: center; margin: 20px 0;">
            <button type="button" class="nav-btn btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Add New 360 Image
            </button>
            <!-- <a href="import.php?id=<?php echo $storeId ?>">
            <button type="button" class="nav-btn btn btn-primary">
                Import from google
            </button>
            </a> -->

            </a>

            <?php if (!$has360placeId): ?>
                <?php if (isset($results)): ?>
                    <a href="publishallimages.php?id=<?php echo $storeId ?>">
                        <button type="button" class=" nav-btn  btn btn-primary" style="margin-left: 3px;">
                            Publish All Images to Google
                        </button>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Show "Connect Images" only if 360placeId exists -->
            <?php if ($has360placeId): ?>
                <?php if (isset($results)): ?>
                    <a class="btn btn-primary ms-2" href="./create-connections.php?storeId=<?php echo $storeId; ?>">
                        Connect Images
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($imagesArray) && isset($imagesArray[0]['connectionstatus']) && $imagesArray[0]['connectionstatus']) { ?>
            <p style="font-size:large"> Status:All images are published and connected.<br>
                If you want to add extra images firstly upload and <br>
                publish from image listing one by one than connect.
            </p>
        <?php } ?>

        <div class="gobackbtn btn btn-primary">
            <a href="./index.php">Go Back to Store</a>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <?php
                    include_once 'config.php';
                    $storeId = $_GET['id'];
                    $storename = "";
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    $sql = "SELECT * from 360g_stores Where id=$storeId ";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        // echo "<pre>";   
                        // print_r($row);
                        $storename = $row["storeName"];
                    }
                    ?>
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><?php echo $storename ?></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>


                <div class="modal-body">
                    <form action="submit_image.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" id="storeID" name="storeID" required
                            value='<?php echo $storeId ?>'><br><br>
                        <label for="newImage">Image</label><br>
                        <input type="file" id="newImage" name="newImage" required><br><br>
                        <div style="display: none;">
                            <label for="newimageLat">Image Latitude:</label><br>
                            <input type="number" id="newimageLat" name="newimageLat" step="any" required><br><br>

                            <label for="newimageLong">Image Longitude:</label><br>
                            <input type="number" id="newimageLong" name="newimageLong" step="any" required><br><br>

                            <label for="newimageAlt">Image Alt Text:</label><br>
                            <input type="number" id="newimageAlt" name="newimageAlt" step="any" required><br><br>

                            <label for="newimageHeading">Image Heading:</label><br>
                            <input type="number" id="newimageHeading" name="newimageHeading" step="any"><br><br>
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save New Image</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <?php

    ?>
    <div id="UpdateImgForm" class="maindistoreimg">
        <div class="custom-form-container">
            <h1><?php echo str_replace('_', ' ', $storename); ?></h1>
            <form id="imageForm" action="./change_image.php" method="post">
                <input type="hidden" id="storeID" name="storeID" required value='<?php echo $storeId ?>'><br><br>
                <input type="hidden" id="imageID" name="imageID">
                <input type="text" id="imageFileName" name="imageFileName" readonly><br><br>
                <div id="uploadContainer">
                    <label for="imageName">Image</label><br>
                    <input type="file" id="imageName" name="imageName" required><br><br>
                </div>

                <label for="imageLat">Image Latitude:</label><br>
                <input type="number" id="imagelLat" name="imageLat" step="any" required><br><br>

                <label for="imageLong">Image Longitude:</label><br>
                <input type="number" id="imageLong" name="imageLong" step="any" required><br><br>

                <label for="imageAlt">Image Alt Text:</label><br>
                <input type="number" id="imageAlt" name="imageAlt" step="any" required><br><br>

                <label for="imageHeading">Image Heading:</label><br>
                <div class="headingdiv">
                    <span id="leftArrow" style="cursor:pointer;"><i class="fa-solid fa-arrow-left"></i></span>
                    <input type="number" id="imageHeading" name="imageHeading" step="any" required>
                    <span id="rightArrow" style="cursor:pointer;"><i class="fa-solid fa-arrow-right"></i></span>
                </div><br><br>
                <div class="btndeg">
                    <button type="button" class="presetBtn">90</button>
                    <button type="button" class="presetBtn">180</button>
                    <button type="button" class="presetBtn">270</button>
                </div>
                <label for="imageYaw">Image Yaw</label><br>
                <input type="number" id="imageYaw" name="imageYaw" step="any"><br><br>
                <?php
                $statusquery = "SELECT `360status` FROM 360g_storeimages WHERE storeID = ?";
                $stmt = $conn->prepare($statusquery);
                $stmt->bind_param("i", $storeId);
                $stmt->execute();
                $stmt->bind_result($gstatus);
                $stmt->fetch();
                $stmt->close();

                $buttonLabel = (isset($gstatus) && $gstatus === "published") ? "Update 360Image" : "Update 360 Image";
                ?>
                <button type="submit"><?php echo $buttonLabel; ?></button>
            </form>
        </div>

        <div class="viewer-wrapper">
            <div class="view-inner">
                <div id="map"></div>
                <div id="panorama"></div>
                <label for=""><input id="connectimage" type="checkbox">&nbspCheck to Connect and Uncheck to
                    Disconnect</label>
            </div>
            <?php
            /*  Count how many images are actually “published” for this store  */
            $sql = " SELECT COUNT(*) FROM   360g_storeimages WHERE  storeID   = ? AND  360status = 'published'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $storeId);
            $stmt->execute();
            $stmt->bind_result($publishedCount);
            $stmt->fetch();
            $stmt->close();
            if ($publishedCount > 0) {
                echo '<div id="allImagesMap" style="width:100%;height:325px;margin-top:24px;"></div>';
            }
            ?>
            <!-- <div id="allImagesMap" style="width:100%;height:400px;margin-top:24px;"></div> -->
        </div>


    </div>

    <div class="map-site sortable">
        <?php
        $stmt = $conn->prepare("SELECT storeName,lastUpdate FROM 360g_stores WHERE id = ?");
        $stmt->bind_param("i", $storeId);
        $stmt->execute();
        $stmt->bind_result($storeName, $lastUpdate);
        $stmt->fetch();
        $stmt->close();

        $sqlImages = "SELECT id, imageName,imageLat, imageLong, imageHeading,imageAlt,imageYaw,360status,360placeId,connectionstatus,exifupdatedstatus FROM 360g_storeimages WHERE storeID = $storeId ORDER BY orders ASC";
        $resultImages = $conn->query($sqlImages);

        if ($resultImages && $resultImages->num_rows > 0) {
            while ($imgRow = $resultImages->fetch_assoc()) {
                $imagePath = "upload/$userID/$storeName/" . $imgRow['imageName'];
                if (file_exists($imagePath)) {
                    echo "<div style='display: inline-block; position: relative; text-align: center; margin: 12px;' data-store-id='$storeId' data-image-id='$imgRow[id]'>";

                    // Delete (X) button
                    echo "<span 
                class='delete-image' 
                data-image-name='" . htmlspecialchars($imgRow['imageName']) . "'
                data-store-id='$storeId' 
                style='position: absolute; top: -10px; right: -10px; background: red; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold;'>×</span>";
                    if (!isset($imgRow['360status']) && isset($lastUpdate)) {
                        echo "<a 
                    href='publish360.php?storeId=" . urlencode($storeId) . "&imageId=" . urlencode($imgRow['id']) . "'
                    class='publish-btn'
                    style='position: absolute; bottom: 109px; left: 50%; transform: translateX(-50%); background: green; color: white; padding: 4px 10px; font-size: 12px; border-radius: 4px; text-decoration: none; z-index: 10;'>
                    Publish
                </a>";
                    }
                    echo "<img
                class='selectable-image'
                src='" . htmlspecialchars($imagePath) . "'
                alt='Store Image'
                style='width: 220px; cursor: pointer; border: 3px solid transparent;'
                data-store-id='$storeId' data-image-id='$imgRow[id]'
                data-lat='" . htmlspecialchars($imgRow['imageLat']) . "'
                data-lon='" . htmlspecialchars($imgRow['imageLong']) . "'
                data-heading='" . htmlspecialchars($imgRow['imageHeading']) . "'
                data-altitude='" . htmlspecialchars($imgRow['imageAlt']) . "'
                data-src='" . htmlspecialchars($imagePath) . "'
                data-id='" . htmlspecialchars($imgRow['id']) . "'
                data-name='" . htmlspecialchars($imgRow['imageName']) . "' 
                data-imageYaw='" . htmlspecialchars($imgRow['imageYaw']) . "'
                data-exifstatus='" . htmlspecialchars($imgRow['exifupdatedstatus']) . "'
                />";

                    echo "<div class='imgtitle' data-store-id='$storeId' data-image-id='$imgRow[id]' style='margin-top: 5px; font-size: 14px; color: #333;'>" . htmlspecialchars($imgRow['imageName']) . "</div>";
                    echo "</div>";
                } else {
                    echo "<p>Image not found: " . htmlspecialchars($imgRow['imageName']) . "</p>";
                }
            }
        } else {

            echo "<p>No images found for this store.</p>";
            $noImages = true;
        }
        ?>

        <?php if (isset($noImages)): ?>
            <style>
                .viewer-wrapper {
                    display: none !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                }

                #UpdateImgForm {
                    display: none !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                }
            </style>
        <?php endif; ?>
    </div>

    <!-- ✨ JavaScript -->
    <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
    <script>

        function initAllImagesMap() {
            const imageEls = document.querySelectorAll('img.selectable-image[data-lat][data-lon]');
            if (imageEls.length === 0) return;

            const first = imageEls[0];
            const mapCentre = { lat: +first.dataset.lat, lng: +first.dataset.lon };

            const allMap = new google.maps.Map(document.getElementById('allImagesMap'), {
                center: mapCentre,
                zoom: 20,
                mapTypeId: 'satellite',
                gestureHandling: 'greedy', // let the mouse-wheel zoom straight away
                //scrollwheel: true
            });

            const bounds = new google.maps.LatLngBounds();

            /* ---------- NEW ---------- */
            const GREEN = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png';
            const RED = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png';

            /* @type {google.maps.Marker[]} */
            const markers = [];
            let selected = null;

            const selectMarker = (m) => {
                if (selected === m) return;          // already selected
                if (selected) selected.setIcon(GREEN);
                m.setIcon(RED);
                selected = m;
            };
            /* ------------------------- */

            imageEls.forEach(img => {
                const pos = { lat: +img.dataset.lat, lng: +img.dataset.lon };
                const imageId = img.dataset.imageId;
                const m = new google.maps.Marker({
                    position: pos,
                    map: allMap,
                    title: img.dataset.name || 'Image',
                    icon: GREEN                         // default colour
                });
                markerMap[imageId] = m;
                markers.push(m);
                bounds.extend(pos);

                // click on marker
                //m.addListener('click', () => selectMarker(m));

                // click on thumbnail
                img.addEventListener('click', () => {
                    allMap.panTo(pos);
                    selectMarker(m);
                });
            });

            if (markers.length > 1) allMap.fitBounds(bounds);

            // Optionally pre-select the first marker
            if (markers.length) selectMarker(markers[0]);

            /* MarkerClusterer needs the markers array */
            new markerClusterer.MarkerClusterer({ map: allMap, markers });
        }

        window.addEventListener('load', initAllImagesMap);

        function updateBothMarkers() {
            const lat = parseFloat(document.getElementById('imagelLat').value);
            const lon = parseFloat(document.getElementById('imageLong').value);
            const imageId = document.getElementById('imageID').value;

            if (!isNaN(lat) && !isNaN(lon)) {
                const newPos = { lat, lng: lon };

                if (marker) {
                    marker.setPosition(newPos);
                    map.panTo(newPos);
                }

                if (markerMap[imageId]) {
                    markerMap[imageId].setPosition(newPos);
                    allMap.panTo(newPos); // optional
                }
            }
        }

        document.getElementById('imagelLat').addEventListener('input', updateBothMarkers);
        document.getElementById('imageLong').addEventListener('input', updateBothMarkers);
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /* bind click listeners … (your code already does this) */

            // 👉 pick the first .selectable-image in the list
            const firstImage = document.querySelector('.selectable-image');
            if (firstImage) {
                firstImage.classList.add('selected');   // visual border
                firstImage.click();                     // run the existing logic
            }
        });

        document.querySelectorAll('.selectable-image').forEach(img => {
            img.addEventListener('click', function () {
                /* …your existing code… */

                // remove highlight from *every* image
                document
                    .querySelectorAll('.selectable-image.selected')
                    .forEach(i => i.classList.remove('selected'));

                // give it to the one the user just clicked
                this.classList.add('selected');
            });
        });
    </script>
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
        const input = document.getElementById('imageHeading');
        const leftArrow = document.getElementById('leftArrow');
        const rightArrow = document.getElementById('rightArrow');
        const presetButtons = document.querySelectorAll('.presetBtn');

        // Helper to safely parse input value as number, fallback to 0
        function getInputValue() {
            const val = parseFloat(input.value);
            return isNaN(val) ? 0 : val;
        }
        function dispatchInputEvent() {
            const event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);
        }
        // Increment or decrement input by 1
        leftArrow.addEventListener('click', () => {
            input.value = getInputValue() - 1;
            dispatchInputEvent();

        });

        rightArrow.addEventListener('click', () => {
            input.value = getInputValue() + 1;
            dispatchInputEvent();

        });

        // Set input to the button's value on click
        presetButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                input.value = btn.textContent.trim();
                dispatchInputEvent();
            });
        });
    </script>

    <script>

        document.getElementById('submitPlaceForm')?.addEventListener('submit', function (e) {
            const imagesPlaceValue = document.getElementById('imagesPlace').value.trim();

            if (!imagesPlaceValue) {
                e.preventDefault(); // Stop form submission
                alert('Please select a place before submitting.');
                location.reload(); // Reload the page after alert
            }
        });
    </script>
    <!-- <script>
        document.getElementById('submitPlaceForm').addEventListener('submit', function (e) {
            document.getElementById('pageLoader').classList.remove('hidden');
            return true;
        });
        window.addEventListener('load', function () {
            const loader = document.getElementById('pageLoader');
            if (loader) {
                loader.classList.add('none');
            }
        });
            window.addEventListener('unload', function () {
            const loader = document.getElementById('pageLoader');
            if (loader) {
                loader.classList.add('hidden');
            }
        });
    </script> -->
    <script>
        function searchPlaces(e) {
            e.preventDefault();
            const query = document.getElementById('searchImage').value.trim();
            if (!query) return alert("Please enter a place name.");

            const formData = new FormData();
            formData.append('searchImage', query);

            fetch('searchplacelisting.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(data => {
                    document.getElementById('placeResults').innerHTML = data;

                    // Add click events to list items
                    document.querySelectorAll('#placeResults li').forEach(item => {
                        item.addEventListener('click', function () {
                            // Clear other selections
                            document.querySelectorAll('#placeResults li').forEach(li => li.classList.remove('selected'));
                            this.classList.add('selected');
                            // Store selected place name in hidden field
                            document.getElementById('imagesPlace').value = this.dataset.placeName;
                        });
                    });
                });
        }
    </script>
    <script>
        let map;
        let allMap;
        let marker = null;
        let pannellumViewer = null;
        const markerMap = {};
        let cone = null;

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        function rad2deg(rad) {
            return rad * (180 / Math.PI);
        }

        function computeOffset(lat, lon, distance, bearing) {
            const R = 6378137; // Earth radius in meters
            const brng = deg2rad(bearing);
            const lat1 = deg2rad(lat);
            const lon1 = deg2rad(lon);

            const lat2 = Math.asin(
                Math.sin(lat1) * Math.cos(distance / R) +
                Math.cos(lat1) * Math.sin(distance / R) * Math.cos(brng)
            );
            const lon2 = lon1 + Math.atan2(
                Math.sin(brng) * Math.sin(distance / R) * Math.cos(lat1),
                Math.cos(distance / R) - Math.sin(lat1) * Math.sin(lat2)
            );

            return {
                lat: rad2deg(lat2),
                lng: rad2deg(lon2)
            };
        }

        function drawDirectionCone(center, heading, map) {
            const coneDistance = 30;
            const angleSpread = 30;

            const left = computeOffset(center.lat, center.lng, coneDistance, heading - angleSpread);
            const right = computeOffset(center.lat, center.lng, coneDistance, heading + angleSpread);

            const conePath = [center, left, right];

            if (cone) cone.setMap(null);

            cone = new google.maps.Polygon({
                paths: conePath,
                strokeColor: '#00ff2aff',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#00ff2aff',
                fillOpacity: 0.35,
                map: map
            });
        }

        function initMap(lat = 0, lon = 0, heading = 0) {
            const center = { lat: parseFloat(lat), lng: parseFloat(lon) };
            const headingDeg = parseFloat(heading) || 0;

            map = new google.maps.Map(document.getElementById("map"), {
                center: center,
                zoom: 19,
                mapTypeId: 'satellite'
            });

            if (marker) marker.setMap(null);

            marker = new google.maps.Marker({
                position: center,
                map: map,
                draggable: true,
                title: "Image Location",
                icon: {
                    url: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png"
                }
            });

            drawDirectionCone(center, headingDeg, map); // ✅ draw cone initially

            marker.addListener('dragend', () => {
                const pos = marker.getPosition();
                const lat = pos.lat().toFixed(6);
                const lng = pos.lng().toFixed(6);
                const heading = parseFloat(document.getElementById('imageHeading').value) || 0;

                document.getElementById('imagelLat').value = lat;
                document.getElementById('imageLong').value = lng;

                drawDirectionCone({ lat: parseFloat(lat), lng: parseFloat(lng) }, heading, map); // ✅ update cone
                updateBothMarkers();
            });
        }


        function attachYawSynchroniser(connect) {
            // remove any previous pair first
            if (pannellumViewer.__onDown) pannellumViewer.off('mousedown', pannellumViewer.__onDown);
            if (pannellumViewer.__onUp) pannellumViewer.off('mouseup', pannellumViewer.__onUp);
            if (pannellumViewer.__onTouchDown) pannellumViewer.off('touchstart', pannellumViewer.__onTouchDown);
            if (pannellumViewer.__onTouchUp) pannellumViewer.off('touchend', pannellumViewer.__onTouchUp);

            let startYaw = 0;

            const onDown = () => { startYaw = pannellumViewer.getYaw(); };

            const onUp = () => {
                const endYaw = pannellumViewer.getYaw();
                const deltaRaw = endYaw - startYaw;
                const delta = ((deltaRaw + 540) % 360) - 180;        // normalise to –180…180

                // update Yaw input
                const yawInput = document.getElementById('imageYaw');
                const currentYaw = parseFloat(yawInput.value) || 0;
                const newYaw = ((currentYaw + delta + 360) % 360).toFixed(2);
                yawInput.value = newYaw;
                yawInput.dispatchEvent(new Event('input'));

                pannellumViewer.setYaw(parseFloat(newYaw));

                if (connect) {
                    // also update heading – this will redraw the cone via the existing input listener
                    const headingInput = document.getElementById('imageHeading');
                    const currentHeading = parseFloat(headingInput.value) || 0;
                    headingInput.value = ((currentHeading + delta + 360) % 360).toFixed(2);
                    headingInput.dispatchEvent(new Event('input'));     // triggers map refresh once
                }
            };

            // save references so we can remove them next time
            pannellumViewer.__onDown = onDown;
            pannellumViewer.__onUp = onUp;
            pannellumViewer.__onTouchDown = onDown;
            pannellumViewer.__onTouchUp = onUp;

            pannellumViewer.on('mousedown', onDown);
            pannellumViewer.on('mouseup', onUp);
            pannellumViewer.on('touchstart', onDown);   // mobile
            pannellumViewer.on('touchend', onUp);     // mobile
        }

        document.querySelectorAll('img[data-lat]').forEach(img => {
            img.addEventListener('click', function () {
                const exifStatus = this.getAttribute('data-exifstatus');
                const checkbox = document.getElementById('connectimage');
                if (checkbox) {
                    checkbox.checked = (exifStatus === 'updatedexif');
                }
                const lat = this.getAttribute('data-lat') || '';
                const lon = this.getAttribute('data-lon') || '';
                const heading = this.getAttribute('data-heading') || '';
                const altitude = this.getAttribute('data-altitude') || '';
                const src = this.getAttribute('data-src') || '';
                const imgID = this.getAttribute('data-id') || '';
                const imgName = this.getAttribute('data-name') || '';
                const imageYaw = this.getAttribute('data-imageYaw') || '';

                // Populate the form inputs
                document.getElementById('imagelLat').value = lat;
                document.getElementById('imageLong').value = lon;
                document.getElementById('imageHeading').value = heading;
                document.getElementById('imageAlt').value = altitude;
                document.getElementById('imageID').value = imgID;
                document.getElementById('imageFileName').value = imgName;
                const yawInput = document.getElementById('imageYaw');
                yawInput.value = imageYaw;

                document.getElementById('imageYaw').addEventListener('input', function () {
                    const yaw = parseFloat(this.value);
                    if (!isNaN(yaw) && pannellumViewer) {
                        pannellumViewer.setYaw(yaw);
                    }
                });

                if (lat && lon) {
                    initMap(lat, lon, heading);
                }

                // Show panorama with heading
                if (src) {
                    document.getElementById('panorama').style.display = 'block';

                    // Fully destroy and recreate the viewer
                    const panoramaContainer = document.getElementById('panorama');
                    panoramaContainer.innerHTML = ''; // Clear previous viewer
                    const combinedYaw = (90 + 45) % 360;
                    console.log('heading', heading);
                    if (pannellumViewer) {
                        pannellumViewer.destroy();
                    }
                    pannellumViewer = pannellum.viewer('panorama', {
                        type: "equirectangular",
                        panorama: src,
                        autoLoad: true,
                        compass: true,
                        // pitch:0,
                        // hfov:110,
                        //northOffset:  ,
                        //northOffset: heading,
                        // northOffset: (0 - heading + 360) % 360,
                        yaw: imageYaw * 1

                    });
                    let startingYaw = 0;



                    let mouseDownHandler = null;
                    let mouseUpHandler = null;

                    function syncMapView(enable) {
                        console.log('syncMapView', enable);

                        // Clean up old handlers if any
                        if (mouseDownHandler) {
                            pannellumViewer.off('mousedown', mouseDownHandler);
                            mouseDownHandler = null;
                        }
                        if (mouseUpHandler) {
                            pannellumViewer.off('mouseup', mouseUpHandler);
                            mouseUpHandler = null;
                        }


                        mouseDownHandler = () => {
                            startingYaw = pannellumViewer.getYaw();
                        };
                        mouseUpHandler = () => {
                            const endingYaw = pannellumViewer.getYaw();
                            const yawDelta = endingYaw - startingYaw;

                            // Normalize delta to -180..180
                            const normalizedDelta = ((yawDelta + 540) % 360) - 180;


                            // Update imageYaw
                            const yawInput = document.getElementById('imageYaw');
                            const currentYaw = parseFloat(yawInput.value) || 0;
                            const newYaw = (currentYaw + normalizedDelta + 360) % 360;
                            yawInput.value = newYaw.toFixed(2);
                        };

                        if (enable) {
                            mouseUpHandler = () => {
                                const endingYaw = pannellumViewer.getYaw();
                                const yawDelta = endingYaw - startingYaw;

                                // Normalize delta to -180..180
                                const normalizedDelta = ((yawDelta + 540) % 360) - 180;

                                // Update imageHeading
                                const headingInput = document.getElementById('imageHeading');
                                const currentHeading = parseFloat(headingInput.value) || 0;
                                const newHeading = (currentHeading + normalizedDelta + 360) % 360;
                                headingInput.value = newHeading.toFixed(2);
                                headingInput.dispatchEvent(new Event('input'));

                                // Update imageYaw
                                const yawInput = document.getElementById('imageYaw');
                                const currentYaw = parseFloat(yawInput.value) || 0;
                                const newYaw = (currentYaw + normalizedDelta + 360) % 360;
                                yawInput.value = newYaw.toFixed(2);
                            };
                        }

                        pannellumViewer.on('mousedown', mouseDownHandler);
                        pannellumViewer.on('mouseup', mouseUpHandler);
                    }

                    // Initial connect state
                    const connectInput = document.getElementById('connectimage');
                    attachYawSynchroniser(connectInput.checked);

                    connectInput.addEventListener('change', e => {
                        attachYawSynchroniser(e.target.checked);
                    });



                }
            });
        });

        document.getElementById('newImage').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            EXIF.getData(file, function () {
                function convertDMSToDD(dms, ref) {
                    if (!dms) return '';
                    const degrees = dms[0].numerator / dms[0].denominator;
                    const minutes = dms[1].numerator / dms[1].denominator;
                    const seconds = dms[2].numerator / dms[2].denominator;
                    let dd = degrees + minutes / 60 + seconds / 3600;
                    if (ref === "S" || ref === "W") {
                        dd = dd * -1;
                    }
                    return dd.toFixed(6); // rounded
                }

                const lat = convertDMSToDD(EXIF.getTag(this, "GPSLatitude"), EXIF.getTag(this, "GPSLatitudeRef"));
                const lon = convertDMSToDD(EXIF.getTag(this, "GPSLongitude"), EXIF.getTag(this, "GPSLongitudeRef"));
                const heading = EXIF.getTag(this, "GPSImgDirection");

                const altitudeObj = EXIF.getTag(this, "GPSAltitude");
                let altitude = '';
                if (altitudeObj && altitudeObj.numerator !== undefined && altitudeObj.denominator !== undefined) {
                    altitude = (altitudeObj.numerator / altitudeObj.denominator).toFixed(2);
                }

                document.getElementById('newimageLat').value = lat || '0';
                document.getElementById('newimageLong').value = lon || '0';
                document.getElementById('newimageHeading').value = heading || '0';
                document.getElementById('newimageAlt').value = altitude || '0';
            });
        });



        document.getElementById('imageName').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            EXIF.getData(file, function () {
                function convertDMSToDD(dms, ref) {
                    if (!dms) return '';
                    const degrees = dms[0].numerator / dms[0].denominator;
                    const minutes = dms[1].numerator / dms[1].denominator;
                    const seconds = dms[2].numerator / dms[2].denominator;
                    let dd = degrees + minutes / 60 + seconds / 3600;
                    if (ref === "S" || ref === "W") {
                        dd = dd * -1;
                    }
                    return dd.toFixed(6); // rounded
                }

                const lat = convertDMSToDD(EXIF.getTag(this, "GPSLatitude"), EXIF.getTag(this, "GPSLatitudeRef"));
                const lon = convertDMSToDD(EXIF.getTag(this, "GPSLongitude"), EXIF.getTag(this, "GPSLongitudeRef"));
                const heading = EXIF.getTag(this, "GPSImgDirection");

                const altitudeObj = EXIF.getTag(this, "GPSAltitude");
                let altitude = '';
                if (altitudeObj && altitudeObj.numerator !== undefined && altitudeObj.denominator !== undefined) {
                    altitude = (altitudeObj.numerator / altitudeObj.denominator).toFixed(2);
                }

                document.getElementById('imagelLat').value = lat || '';
                document.getElementById('imageLong').value = lon || '';
                document.getElementById('imageHeading').value = heading || '';
                document.getElementById('imageAlt').value = altitude || '';
            });
        });

        window.onload = function () {
            const images = document.querySelectorAll('img[data-lat]');
            const lastImage = images[images.length - 1]; // Get the last one
            if (lastImage) {
                lastImage.click();

                // Auto-check the connect box if exifupdatedstatus = 'updatedexif'
                const exifStatus = lastImage.getAttribute('data-exifstatus');
                const checkbox = document.getElementById('connectimage');
                if (checkbox) {
                    checkbox.checked = (exifStatus === 'updatedexif');
                }
            }

            const upload = document.getElementById('uploadContainer');
            if (upload) {
                document.getElementById('imageName').removeAttribute('required');
                upload.style.display = 'none';
                upload.style.visibility = 'hidden';
                upload.style.opacity = '0';
            }

            // Maintain highlight on selected image
            document.querySelectorAll('.selectable-image').forEach(img => {
                img.addEventListener('click', function () {
                    document.getElementById('imageName').removeAttribute('required');
                    upload.style.display = 'none';
                    upload.style.visibility = 'hidden';
                    upload.style.opacity = '0';

                    document.querySelectorAll('.selectable-image').forEach(i => i.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
        };

    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBxFBPk3cTVxt5apqzPsUwy4v3cvnuns54&callback=initMap"
        async defer></script>

    <script>
        function updateMapFromInputs() {
            const lat = document.getElementById('imagelLat').value;
            const lon = document.getElementById('imageLong').value;
            const heading = document.getElementById('imageHeading').value;

            if (lat && lon && heading) {
                const parsedLat = parseFloat(lat);
                const parsedLon = parseFloat(lon);
                const parsedHeading = parseFloat(heading);

                if (!isNaN(parsedLat) && !isNaN(parsedLon) && !isNaN(parsedHeading)) {
                    initMap(parsedLat, parsedLon, parsedHeading);
                    drawDirectionCone(
                        { lat: parsedLat, lng: parsedLon },
                        parsedHeading,
                        map
                    );
                }
            }
        }

        document.getElementById('imagelLat').addEventListener('input', updateMapFromInputs);
        document.getElementById('imageLong').addEventListener('input', updateMapFromInputs);
        document.getElementById('imageHeading').addEventListener('input', updateMapFromInputs);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.delete-image').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const imageName = this.getAttribute('data-image-name');
                    if (confirm('Are you sure you want to delete this image?')) {
                        fetch('delete_image.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'imageName=' + encodeURIComponent(imageName)
                        })
                            .then(response => response.text())
                            .then(data => {
                                if (data.trim() === 'success') {
                                    this.parentElement.remove();
                                    window.location.reload();
                                } else {
                                    alert('Failed to delete image: ' + data);
                                }
                            });
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js"
        integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>


    <script>
        const sortableLists = document.querySelectorAll(".sortable");

        sortableLists.forEach((sortableList) => {
            Sortable.create(sortableList, {
                onEnd: function () {
                    const sortedItems = Array.from(sortableList.children).map(item => {
                        return {
                            storeId: item.getAttribute('data-store-id'),
                            imageId: item.getAttribute('data-image-id')
                        };
                    });

                    console.log("Sorted Items:", sortedItems);

                    // Send data via AJAX using fetch
                    fetch('./updateimgorder.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            // Add CSRF token here if required
                        },
                        body: JSON.stringify({ sortedItems })
                    })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            console.log('Server response:', data);
                        })
                        .catch(error => {
                            console.error('AJAX error:', error);
                        });
                }
            });
        });
    </script>

    <script>
        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const targetImageId = urlParams.get('photo-id'); // get ?photo-id=

            const images = document.querySelectorAll('img[data-image-id]');
            let imageToSelect = null;

            // Case 1: If photo-id exists in URL and matches an image
            if (targetImageId) {
                imageToSelect = Array.from(images).find(img => img.dataset.imageId === targetImageId);
            }

            // Case 2: If not found or no photo-id, select the last image
            if (!imageToSelect && images.length > 0) {
                imageToSelect = images[images.length - 1];
            }

            // Select the image
            if (imageToSelect) {
                imageToSelect.click(); // triggers existing logic (panorama, form, map)
                imageToSelect.classList.add('selected');

                // Check "Connect" box if EXIF is updated
                const exifStatus = imageToSelect.getAttribute('data-exifstatus');
                const checkbox = document.getElementById('connectimage');
                if (checkbox) {
                    checkbox.checked = (exifStatus === 'updatedexif');
                }
            }

            // Hide image upload section (since it's for editing existing)
            const upload = document.getElementById('uploadContainer');
            if (upload) {
                document.getElementById('imageName').removeAttribute('required');
                upload.style.display = 'none';
                upload.style.visibility = 'hidden';
                upload.style.opacity = '0';
            }

            // Maintain highlight on clicked image
            document.querySelectorAll('.selectable-image').forEach(img => {
                img.addEventListener('click', function () {
                    document.querySelectorAll('.selectable-image').forEach(i => i.classList.remove('selected'));
                    this.classList.add('selected');

                    if (upload) {
                        document.getElementById('imageName').removeAttribute('required');
                        upload.style.display = 'none';
                        upload.style.visibility = 'hidden';
                        upload.style.opacity = '0';
                    }
                });
            });
        };
    </script>

</body>

</html>