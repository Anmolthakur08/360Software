<?php
require_once __DIR__ . '/config.php';
$user = $_SESSION['user'];
$userId = $user['id'];
$token = 'MY_SUPER_SECRET_9d714ce5e83b41b8';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ─────────────── 1.  Validate & locate the file ─────────────── */
    $fileName = $_POST['imageFileName'] ?? '';
    if ($fileName === '') {
        $_SESSION['errorupdimgmsg'] = '❌ No image filename provided.';
        header("Location: g_storeimages.php?id=$storeID");
    }


    $stmt = $conn->prepare("SELECT storeID FROM 360g_storeimages WHERE imageName = ?");
    $stmt->bind_param("s", $fileName);
    $stmt->execute();
    $stmt->bind_result($storeID);
    $stmt->fetch();
    $stmt->close();




    // Get the store name from the store ID
    $stmtsid = $conn->prepare("SELECT storeName,placeId FROM 360g_stores WHERE id = ?");
    $stmtsid->bind_param("i", $storeID);
    $stmtsid->execute();
    $stmtsid->bind_result($storeName, $placeId);
    $stmtsid->fetch();
    $stmtsid->close();




    $uploadDir = "./upload/$userId/$storeName";
    $inputPath = $uploadDir . '/' . basename($fileName);   // add the slash
    $inputFile = realpath($inputPath);

    if ($inputFile === false || !is_readable($inputFile)) {
        $_SESSION['errorupdimgmsg'] = '❌ File does not exist in upload folder.';
        header("Location: g_storeimages.php?id=$storeID");
    }

    $lat = filter_input(INPUT_POST, 'imageLat', FILTER_VALIDATE_FLOAT);
    $lon = filter_input(INPUT_POST, 'imageLong', FILTER_VALIDATE_FLOAT);
    $alt = filter_input(INPUT_POST, 'imageAlt', FILTER_VALIDATE_FLOAT);
    $heading = filter_input(INPUT_POST, 'imageHeading', FILTER_VALIDATE_FLOAT);
    $imageID = filter_input(INPUT_POST, 'imageID', FILTER_VALIDATE_INT);
    $storeID = filter_input(INPUT_POST, 'storeID', FILTER_VALIDATE_INT);
    $imageYaw = filter_input(INPUT_POST, 'imageYaw', FILTER_VALIDATE_FLOAT);

    // Normalise heading to 0-359°
    if ($heading !== false && $heading !== null) {
        // put the value in the 0-359 range but keep any decimals
        $heading = fmod($heading, 360.0);   // remainder with floats

        if ($heading < 0) {                 // fmod keeps the sign
            $heading += 360.0;
        }
    }

    /* ─────────────── 3.  Prepare cURL POST body ─────────────── */
    $postFields = [               // sample scalar field
        'file' => new CURLFile($inputFile, mime_content_type($inputFile)),
        'lat' => $lat,
        'lon' => $lon,
        'alt' => $alt,
        'heading' => $heading,
        'imageID' => $imageID,
        'storeID' => $storeID,
        'imageYaw' => $imageYaw,
    ];
    // Remove null / false values so you only send what you have
    $postFields = array_filter($postFields, static fn($v) => $v !== null && $v !== false);

    /* ─────────────── 4.  Send request ─────────────── */
    $curl = curl_init('http://3.77.8.17/change_image.php');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($curl);
    if ($response === false) {
        $_SESSION['errorupdimgmsg'] = '❌ cURL error: ';//. curl_error($curl);
        header("Location: g_storeimages.php?id=$storeID");
    }
    curl_close($curl);



    /* ─────────────── 5.  Parse JSON & save image ─────────────── */
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['errorupdimgmsg'] = '❌ Invalid JSON: ';// . json_last_error_msg();
        header("Location: g_storeimages.php?id=$storeID");
    }

    if (!($data['success'] ?? false)) {
        $_SESSION['errorupdimgmsg'] = '❌ Remote reported an error.';
        header("Location: g_storeimages.php?id=$storeID");
    }

    if (!isset($data['imageB64'])) {
        $_SESSION['errorupdimgmsg'] = '❌ No imageB64 field in response.';
        header("Location: g_storeimages.php?id=$storeID");
    }

    /* 5a.  Remove any “data:image/…;base64,” prefix */
    $rawB64 = $data['imageB64'];
    $comma = strpos($rawB64, ',');
    if ($comma !== false) {
        $rawB64 = substr($rawB64, $comma + 1);   // keep the part after the comma
    }

    /* 5b.  Decode & write */
    $binary = base64_decode($rawB64, true);
    if ($binary === false) {
        $_SESSION['errorupdimgmsg'] = '❌ base64-decode failed.';
        header("Location: g_storeimages.php?id=$storeID");
    }

    $uploadDir = "./upload/$userId/$storeName/";
    $originalName = $data['fileName'] ?? ('image_' . time() . '.jpg');
    $saveName = pathinfo($originalName, PATHINFO_FILENAME) . '_upd.' . pathinfo($originalName, PATHINFO_EXTENSION);
    $savePath = $uploadDir . $saveName;

    /* ───── 5c. Delete old file if it exists ───── */
    $oldPath = $uploadDir . basename($originalName);
    if (file_exists($oldPath)) {
        if (!unlink($oldPath)) {
            $_SESSION['errorupdimgmsg'] = '❌ Failed to delete old file: ';//. $oldPath;
            header("Location: g_storeimages.php?id=$storeID");
            exit;
        }
    }

    if (file_put_contents($savePath, $binary) === false) {
        $_SESSION['errorupdimgmsg'] = '❌ Could not write image to ';//. $savePath);
        header("Location: g_storeimages.php?id=$storeID");
        exit;
    }



    if ($conn->connect_error) {
        $_SESSION['errorupdimgmsg'] = "❌ DB connection failed: "; // . $conn->connect_error;
        header("Location: g_storeimages.php?id=$storeID");
        exit;
    }

    $stmt = $conn->prepare("UPDATE  360g_storeimages SET storeID = ?, imageName = ?, imageLat = ?, imageLong = ?, imageAlt = ?, imageHeading = ?, imageYaw=?,exifupdatedstatus='updatedexif' WHERE id = ?");
    if (!$stmt) {
        $_SESSION['errorupdimgmsg'] = "❌ Prepare failed: ";// . $conn->error;
        header("Location: g_storeimages.php?id=$storeID");
        exit;
    }

    $stmt->bind_param("isdddddi", $storeID, $saveName, $lat, $lon, $alt, $heading, $imageYaw, $imageID);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {

            // Now fetch the 360photoId
            $stmtphoto = $conn->prepare("SELECT 360photoId FROM 360g_storeimages WHERE id = ?");
            if (!$stmtphoto)
                $_SESSION['errorupdimgmsg'] = "❌ Prepare failed: ";// . $conn->error;

            $stmtphoto->bind_param("s", $imageID);
            $stmtphoto->execute();
            $stmtphoto->bind_result($photoId);

            $photoIdValue = null;
            if ($stmtphoto->fetch()) {
                $photoIdValue = $photoId;
            }
            $stmtphoto->close();
            if (!empty($photoIdValue)) {
                $user = $_SESSION['user'];
                $userId = $user['id'];

                $stmt = $conn->prepare("SELECT refresh_token FROM google_users WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->bind_result($userRefreshToken);
                $stmt->fetch();
                $stmt->close();


                // Google API credentials
                $refreshToken = $userRefreshToken; // Replace with your refresh token
                $clientId = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';  // Replace with your OAuth client ID
                $clientSecret = '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';  // Replace with your OAuth client secret
                $apiKey = 'AIzaSyAB6oe7ZQVRZZFi6iNbTIsuqZSUQXaeiqU';  // Replace with your API key


                function getAccessToken($clientId, $clientSecret, $refreshToken)
                {
                    $tokenUrl = 'https://oauth2.googleapis.com/token';
                    $tokenData = [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'refresh_token' => $refreshToken,
                        'grant_type' => 'refresh_token'
                    ];
                    $ch = curl_init($tokenUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
                    $response = curl_exec($ch);

                    if ($response === false) {
                        $err = curl_error($ch);
                        curl_close($ch);
                        throw new RuntimeException("OAuth cURL error: $err");
                    }

                    curl_close($ch);
                    $data = json_decode($response, true);

                    if (!isset($data['access_token'])) {
                        $msg = $data['error_description'] ?? $response;
                        throw new RuntimeException("No access_token in response: $msg");
                    }
                    return $data['access_token'];
                }

                function updateImagesAttribute(array $updateRequests, string $accessToken): array
                {
                    $url = "https://streetviewpublish.googleapis.com/v1/photos:batchUpdate";
                    $headers = [
                        "Authorization: Bearer $accessToken",
                        "Content-Type: application/json"
                    ];
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_POSTFIELDS => json_encode($updateRequests, JSON_UNESCAPED_SLASHES),
                        CURLOPT_TIMEOUT => 30,
                    ]);
                    $response = curl_exec($ch);

                    if ($response === false) {
                        $err = curl_error($ch);
                        curl_close($ch);
                        throw new RuntimeException("Publish cURL error: $err");
                    }
                    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                    curl_close($ch);

                    if ($code >= 400) {
                        throw new RuntimeException("Publish HTTP $code: $response");
                    }

                    $decoded = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("Invalid JSON from publish API: " . json_last_error_msg());
                    }
                    return $decoded;
                }


                $accessToken = getAccessToken($clientId, $clientSecret, $refreshToken, $storeID);

                try {
                    $accessToken = getAccessToken($clientId, $clientSecret, $refreshToken, $storeID);

                    $updateRequests = [
                        'updatePhotoRequests' => [
                            [
                                'photo' => [
                                    'photoId' => ['id' => $photoIdValue],
                                    'places' => [['placeId' => $placeId]],
                                    'pose' => [
                                        'latLngPair' => [  // camelCase in body
                                            'latitude' => $lat,
                                            'longitude' => $lon
                                        ],
                                        'heading' => $heading
                                    ]
                                ],
                                // camelCase in updateMask too:
                                'updateMask' => 'pose.latLngPair,pose.heading,places'
                            ]
                        ]
                    ];

                    $result = updateImagesAttribute($updateRequests, $accessToken);

                    // TODO: inspect $result['results'][0]['status'] etc.
                } catch (RuntimeException $e) {
                    $_SESSION['errorupdimgmsg'] = '❌ ' . $e->getMessage();
                    header("Location: g_storeimages.php?id=$storeID");
                    exit;
                }

                $result = updateImagesAttribute($updateRequests, $accessToken, $storeID);


            }
            $_SESSION['successupdimgmsg'] = 'Image Exif data Update Successfully';
            header("Location: ./g_storeimages.php?id=$storeID&photo-id=$imageID");
            exit();

        } else {
            $_SESSION['errorupdimgmsg'] = "⚠️ No rows updated.";
            header("Location: g_storeimages.php?id=$storeID");
        }
    } else {
        $_SESSION['errorupdimgmsg'] = "❌ Error updating record: ";//. $stmt->error;
        header("Location: g_storeimages.php?id=$storeID");
    }

    $stmt->close();
    $conn->close();
}


?>