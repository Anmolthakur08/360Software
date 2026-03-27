<?php
include_once './config.php';
$storeID = $_GET['id'] ?? 0; // Make sure $storeID is defined
$user = $_SESSION['user'];
$userID = $user['id'];


// 1. Get place coordinates
$stmtsid = $conn->prepare('SELECT placeLat, placeLon FROM 360g_stores WHERE id = ? LIMIT 1');
$stmtsid->bind_param('i', $storeID);
$stmtsid->execute();
$stmtsid->bind_result($placeLat, $placeLon);
$found = $stmtsid->fetch();
$stmtsid->close();

if (!$found) {
    $_SESSION['importimgerror'] = "Store ID $storeID not found.";
    header('Location: g_storeimages.php');
    exit();
}

// 2. Get store name
$stmt = $conn->prepare('SELECT storeName FROM 360g_stores WHERE id = ?');
$stmt->bind_param('i', $storeID);
$stmt->execute();
$stmt->bind_result($storeName);
$stmt->fetch();
$stmt->close();

$safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $storeName);
$uploadDir = __DIR__ . "/upload/$userID/$safeName/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// $textAPI = $_SESSION['textsearchAPIdata'];

// $flagUris = [];

// if (!empty($textAPI['places'])) {
//     foreach ($textAPI['places'] as $place) {
//         if (!empty($place['photos'])) {
//             foreach ($place['photos'] as $photo) {
//                 if (isset($photo['flagContentUri'])) {
//                     $flagUris[] = $photo['flagContentUri'];
//                 }
//             }
//         }
//     }
// }

// // Output the array for debugging
// echo '<pre>';
// print_r($flagUris);
// echo '</pre>';

//----------- 3. Static array of 360° image URLs
$imageUrls = [
    "https://images.unsplash.com/photo-1557971370-e7298ee473fb?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NHx8MzYwfGVufDB8fDB8fHww",
    "https://images.unsplash.com/photo-1707405997487-557b33c87a91?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8MzYwJTIwcGFub3JhbWF8ZW58MHx8MHx8fDA%3D"
];

// 4. Process each image
foreach ($imageUrls as $index => $url) {
    $imageData = file_get_contents($url);
    if ($imageData === false) {
        $_SESSION['importimgerror'] = "❌ Failed to download image from URL: $url<br>";
        header('Location: g_storeimages.php');
        exit();
    }

    $base64 = base64_encode($imageData);
    $timestamp = time();
    $jpgFileName = "img_" . $storeID . "_" . $index . "_" . $timestamp . ".jpg";
    $jpgPath = $uploadDir . $jpgFileName;

    if (file_put_contents($jpgPath, base64_decode($base64))) {
        //echo "✅ Image saved successfully: $jpgPath<br>";

        // Image order
        $result = $conn->query("SELECT MAX(orders) AS max_order FROM 360g_storeimages WHERE storeID = $storeID");
        $row = $result->fetch_assoc();
        $newOrder = isset($row['max_order']) ? ($row['max_order'] + 1) : 0;

        $imageAlt = "Imported 360 image #$index";
        $relativePath = "$jpgFileName";

        $insert = $conn->prepare("INSERT INTO 360g_storeimages 
        (storeID, imageName, imageLat, imageLong, imageHeading, imageAlt, orders) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
        $heading = 0;
        $insert->bind_param('issddsi', $storeID, $relativePath, $placeLat, $placeLon, $heading, $imageAlt, $newOrder);
        $insert->execute();
        $insert->close();
        header("Location: g_storeimages.php?id=$storeID");
    } else {
        $_SESSION['importimgerror'] = "❌ Failed to save image: $jpgPath<br>";
        header('Location: g_storeimages.php');
        exit();
    }
}
?>