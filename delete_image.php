<?php
include_once './config.php';// Include DB connection file
$user = $_SESSION['user'];
$userId = $user['id'];
$imageName = basename($_POST['imageName']); // Basic sanitization
$selectStmt = $conn->prepare("SELECT 360photoId FROM 360g_storeimages WHERE imageName = ?");
$selectStmt->bind_param("s", $imageName);
$selectStmt->execute();
$selectStmt->bind_result($photoIdExists);
$photoId = null;

if ($selectStmt->fetch()) {
    $photoId = $photoIdExists;
}
$selectStmt->close();
if (!empty($photoId)) {
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
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $tokenData);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    $accessToken = $responseData['access_token'] ?? null;

    if ($accessToken) {
        $url = "https://streetviewpublish.googleapis.com/v1/photo/{$photoId}?key=$apiKey";

        $headers = [
            "Authorization: Bearer $accessToken"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $apiResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        echo 'Failed to get access token. ';
    }
}

// Get the store ID from the image name
$stmt = $conn->prepare("SELECT storeID FROM 360g_storeimages WHERE imageName = ?");
$stmt->bind_param("s", $imageName);
$stmt->execute();
$stmt->bind_result($storeID);
$stmt->fetch();
$stmt->close();


// Get the store name from the store ID
$stmtsid = $conn->prepare("SELECT storeName FROM 360g_stores WHERE id = ?");
$stmtsid->bind_param("i", $storeID);
$stmtsid->execute();
$stmtsid->bind_result($storeName);
$stmtsid->fetch();
$stmtsid->close();




// Sanitize the store name and image name for safe file path
$safeStoreName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $storeName);
$safeImageName = basename($imageName); // removes any directory part from filename

// Build the file path
$filePath = "./upload/$userId/$safeStoreName/$safeImageName";

// Delete the file if it exists
if (file_exists($filePath)) {
    unlink($filePath);
} else {
    echo "File not found.";
}

// Delete from DB and file system
$stmtd = $conn->prepare("DELETE FROM 360g_storeimages WHERE imageName = ?");
$stmtd->bind_param("s", $imageName);
$upd = $conn->prepare("UPDATE 360g_stores SET savecon = 0 WHERE id = ?");
$upd->bind_param('i', $storeID);
$upd->execute();
$upd->close();

if (!$stmtd->execute()) {
    echo 'DB error';
    exit;
}
$stmtd->close();
echo 'success';
exit;
?>