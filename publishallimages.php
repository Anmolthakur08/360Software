<?php
include_once 'config.php';
$user = $_SESSION['user'];
$userId = $user['id'];
if (isset($_GET['id'])) {
    $storeId = intval($_GET['id']);
    if (!isset($_GET['id'])) {
        $_SESSION['publishallimageserror'] = 'Store ID not provided.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }

    // 1. Fetch store name
    $stmtsid = $conn->prepare("
    SELECT storeName
    FROM 360g_stores
    WHERE id = ?
    LIMIT 1
  ");
    $stmtsid->bind_param("i", $storeId);
    $stmtsid->execute();
    $stmtsid->bind_result($storeName);
    $stmtsid->fetch();
    $stmtsid->close();
    if (!$storeName) {
        $_SESSION['publishallimageserror'] = 'Store ID not found.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }

    $uploadDir = "./upload/$userId/$storeName/";
    if (!is_dir($uploadDir)) {
        $_SESSION['publishallimageserror'] = 'Upload directory does not exist.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }

    $sql = "SELECT imageName FROM 360g_storeimages WHERE storeID = $storeId";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $imageNames = [];
        while ($row = $result->fetch_assoc()) {
            $imageNames[] = $row['imageName'];
        }

        // // Step 2: Get 360placeId for the store
        // $placeIdSql = "SELECT `360placeId` FROM 360g_storeimages WHERE storeID = $storeId LIMIT 1";
        // $placeIdResult = $conn->query($placeIdSql);
        // $placeId = null;

        // if ($placeIdResult && $placeIdResult->num_rows > 0) {
        //     $row = $placeIdResult->fetch_assoc();
        //     $placeId = $row['360placeId'];
        //     echo "Place ID: $placeId<br>";
        // } else {
        //     echo "No Place ID found for this store.<br>";
        // }

        $allFiles = scandir($uploadDir);
        $matchedFiles = [];

        foreach ($imageNames as $imgName) {
            if (in_array($imgName, $allFiles)) {
                $matchedFiles[] = $uploadDir . $imgName;
            }
        }
        //print_r($matchedFiles);
        //die();
        if (empty($matchedFiles)) {
            $_SESSION['publishallimageserror'] = 'No matching image files found.';
            header("Location: g_storeimages.php?id=$storeId");
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
        $clientId = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';  // Replace with your OAuth client ID
        $clientSecret = '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';  // Replace with your OAuth client secret
        $apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU';  // Replace with your API key

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
        //echo $tokenResponse;
        //die();
        $token = json_decode($tokenResponse, true);

        if (!isset($token['access_token'])) {
            $_SESSION['publishallimageserror'] = 'Failed to get access token';
            header("Location: g_storeimages.php?id=$storeId");
            exit();
        }
        $accessToken = $token['access_token'];
        //echo $accessToken;
        //die();
        // Define helper function
        $allUpdated = true;

        // Loop through each image and upload
        foreach ($matchedFiles as $filePath) {
            //echo "<hr>Processing: $filePath<br>";

            $exifData = @exif_read_data($filePath);

            // Extract capture time from EXIF or fallback to current time
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
                $_SESSION['publishallimageserror'] = 'Failed to get upload URL';
                header("Location: g_storeimages.php?id=$storeId");
                exit();
            }
            $uploadUrl = $uploadStartData['uploadUrl'];

            // Upload file to Google
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

            // Submit photo metadata
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

            //echo "Submitted photo: $submitResponse<br>";

            // Parse response and update DB
            $submitData = json_decode($submitResponse, true);
            if (isset($submitData['photoId']['id'])) {
                $photoId = $submitData['photoId']['id'];
                $fileName = basename($filePath);

                // Update photoId in DB
                $updateSql = "UPDATE 360g_storeimages SET `360photoId` = ?  WHERE imageName = ?";
                $stmt = $conn->prepare($updateSql);
                if ($stmt === false) {
                    $_SESSION['publishallimageserror'] = 'Failed to prepare statement: ';
                    header("Location: g_storeimages.php?id=$storeId");
                    exit();
                }
                $stmt->bind_param('ss', $photoId, $fileName);
                if ($stmt->execute()) {

                    // echo "Photo ID stored in database for image $fileName<br>";
                    //   exit();
                } else {
                    $_SESSION['publishallimageserror'] = 'Failed to update photo ID for image ';
                    header("Location: g_storeimages.php?id=$storeId");
                    exit();
                }
                $stmt->close();
            } else {
                //echo "No photo ID returned for $filePath<br>";
                $_SESSION['publishallimageserror'] = 'Please Update the Image Lat Long then publish';
                header("Location: g_storeimages.php?id=$storeId");
                exit();

            }
        }
        header("Location: updimgplace.php?id=$storeId");
    } else {
        $_SESSION['publishallimageserror'] = 'No images found in database for this store.';
        header("Location: g_storeimages.php?id=$storeId");
        exit();
    }
} else {

    $_SESSION['publishallimageserror'] = 'Store ID not provided.';
    header("Location: g_storeimages.php?id=$storeId");
    exit();
}
?>