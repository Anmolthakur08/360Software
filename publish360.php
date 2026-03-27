<?php
include_once 'config.php';
$user = $_SESSION['user'];
$userID = $user['id'];

if (isset($_GET['storeId']) && isset($_GET['imageId'])) {
    $storeId = intval($_GET['storeId']);
    $imageId = intval($_GET['imageId']);  // Ensure it's an integer

    // 1. Fetch store name
    $stmtsid = $conn->prepare(" SELECT storeName FROM 360g_stores WHERE id = ?");
    $stmtsid->bind_param("i", $storeId);
    $stmtsid->execute();
    $stmtsid->bind_result($storeName);
    $stmtsid->fetch();
    $stmtsid->close();
    if (!$storeName) {
        $_SESSION['360publisherror'] = 'No matching image files found.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }

    $uploadDir = "./upload/$userID/$storeName/";
    $fetchedPlaceId = "";
    $fetchedPlaceName = "";


    // Query only the specific image for this store
    $sql = "SELECT imageName FROM 360g_storeimages WHERE storeID = $storeId AND id = $imageId";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $imageNames = [];
        while ($row = $result->fetch_assoc()) {
            $imageNames[] = $row['imageName'];
        }

        $allFiles = scandir($uploadDir);
        $matchedFiles = [];

        foreach ($imageNames as $imgName) {
            if (in_array($imgName, $allFiles)) {
                $matchedFiles[] = $uploadDir . $imgName;
            }
        }

        if (empty($matchedFiles)) {
            $_SESSION['360publisherror'] = 'No matching image files found.';
            header("Location: g_storeimages.php?id=$storeID");
            exit();
        }

        $user = $_SESSION['user'];
        $userId = $user['id'];

        $stmt = $conn->prepare("SELECT refresh_token FROM google_users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($userRefreshToken);
        $stmt->fetch();
        $stmt->close();



        // Google API credentials
        $refreshToken = $userRefreshToken;
        $clientId = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';
        $clientSecret = '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';
        $apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU';

        // Get access token
        $tokenData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
        $tokenResponse = curl_exec($ch);
        curl_close($ch);

        $token = json_decode($tokenResponse, true);
        if (!isset($token['access_token'])) {
            $_SESSION['360publisherror'] = 'Failed to get access token';
            header("Location: g_storeimages.php?id=$storeID");
            exit();
        }
        $accessToken = $token['access_token'];

        // Process only the first image
        $filePath = $matchedFiles[0];
        $exifData = @exif_read_data($filePath);
        $captureTime = null;

        if ($exifData && isset($exifData['DateTimeOriginal'])) {
            $dt = DateTime::createFromFormat('Y:m:d H:i:s', $exifData['DateTimeOriginal']);
            if ($dt !== false) {
                $captureTime = $dt->getTimestamp();
            }
        }
        if ($captureTime === null) {
            $captureTime = time();
        }

        // Start upload
        $uploadStartUrl = "https://streetviewpublish.googleapis.com/v1/photo:startUpload?key=$apiKey";
        $ch = curl_init($uploadStartUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Length: 0"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        $uploadStartResponse = curl_exec($ch);
        curl_close($ch);

        $uploadStartData = json_decode($uploadStartResponse, true);
        if (!isset($uploadStartData['uploadUrl'])) {
            $_SESSION['360publisherror'] = 'Failed to get upload URL.';
            header("Location: g_storeimages.php?id=$storeId");
        }
        $uploadUrl = $uploadStartData['uploadUrl'];

        // Upload image
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: image/jpeg"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
        $uploadResponse = curl_exec($ch);
        curl_close($ch);

        // Submit metadata
        $photoData = [
            'uploadReference' => ['uploadUrl' => $uploadUrl],
            'captureTime' => ['seconds' => (int) $captureTime]
        ];
        $ch = curl_init("https://streetviewpublish.googleapis.com/v1/photo?key=$apiKey");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($photoData));
        $submitResponse = curl_exec($ch);
        curl_close($ch);

        $getplaceId = "SELECT 360placeId, placeName FROM 360g_storeimages WHERE storeID = ? LIMIT 1";
        $stmt = $conn->prepare($getplaceId);
        if ($stmt) {
            $stmt->bind_param("i", $storeId);
            $stmt->execute();
            $stmt->bind_result($placeId, $placeName);

            if ($stmt->fetch()) {
                $fetchedPlaceId = $placeId;
                $fetchedPlaceName = $placeName;
            } else {
                $_SESSION['360publisherror'] = 'No record found.';
                header("Location: g_storeimages.php?id=$storeId");
                exit();
            }
            $stmt->close();
        } else {

            $_SESSION['360publisherror'] = 'Prepare failed: ';
            header("Location: g_storeimages.php?id=$storeId");
            exit();
        }

        $submitData = json_decode($submitResponse, true);
        if (isset($submitData['photoId']['id'])) {
            $photoId = $submitData['photoId']['id'];
            $fileName = basename($filePath);

            $updateSql = "UPDATE 360g_storeimages SET `360photoId` = ?, `360status` = 'published', `360placeId` = ?, `placeName` = ? WHERE imageName = ?";
            $stmt = $conn->prepare($updateSql);
            if ($stmt === false) {
                $_SESSION['360publisherror'] = 'Failed to prepare statement';
                header("Location: g_storeimages.php?id=$storeId");
                exit();
            }
            $stmt->bind_param('ssss', $photoId, $fetchedPlaceId, $fetchedPlaceName, $fileName);
            if (!$stmt->execute()) {
                $_SESSION['360publisherror'] = 'Failed to update photo ID for image';
                header("Location: g_storeimages.php?id=$storeId");
                exit();
            }
            $stmt->close();
        } else {
            $_SESSION['360publisherror'] = 'Please Publish all image than Publish single Image';
            header("Location: g_storeimages.php?id=$storeId");
            exit();
        }
        header("Location: singlephotoplaceupd.php?id=$storeId");
    } else {
        $_SESSION['360publisherror'] = 'No images found in database for this store.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }
} else {
    $_SESSION['360publisherror'] = 'Store ID not provided.';
    header("Location: g_storeimages.php?id=$storeId");
    exit();
}
?>