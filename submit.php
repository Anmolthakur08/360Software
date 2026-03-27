<?php
/******************************************************************
 * submit.php
 * ---------------------------------------------------------------
 * • Receives JSON from connect_images.php
 * • Refreshes Google OAuth token and calls photos:batchUpdate
 * • Saves 360status, connections, and now posX / posY per thumbnail
 ******************************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once './config.php';

/* ---- 1. Validate input -------------------------------------------------- */
$input = file_get_contents('php://input');
$data  = json_decode($input, true);


if (!$data || !isset($data['updatePhotoRequests'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input: missing updatePhotoRequests']);
    exit;
}
$photos    = $data['updatePhotoRequests'];
$positions = $data['positions'] ?? [];

$storeId = intval($_GET['storeId'] ?? 0);
if ($storeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing storeId']);
    exit;
}

/* ---- 2. Get access token ------------------------------------------------- */
$userId = $_SESSION['user']['id'];
$stmt   = $conn->prepare(
    'SELECT refresh_token FROM google_users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($refreshToken);
$stmt->fetch();  $stmt->close();

if (!$refreshToken) {
    http_response_code(500);
    echo json_encode(['error' => 'No refresh token for this user']);
    exit;
}

$clientId     = '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com';
$clientSecret = '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';

$tokenData = [
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'refresh_token' => $refreshToken,
    'grant_type'    => 'refresh_token'
];
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenData)
]);
$response = curl_exec($ch);
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Curl error: '.curl_error($ch)]);
    exit;
}
curl_close($ch);

$tokenInfo = json_decode($response, true);
if (empty($tokenInfo['access_token'])) {
    http_response_code(500);
    echo json_encode(['error'=>'Unable to obtain access token','details'=>$tokenInfo]);
    exit;
}
$accessToken = $tokenInfo['access_token'];

/* ---- 3. Call Street View Publish API ------------------------------------ */
$payload = json_encode(['updatePhotoRequests'=>$photos]);

$ch = curl_init('https://streetviewpublish.googleapis.com/v1/photos:batchUpdate');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $accessToken",
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS     => $payload
]);
$googleResp = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode([
        'error'    => "Google API returned $httpCode",
        'response' => json_decode($googleResp, true)
    ]);
    exit;
}

/* ---- 4. Persist status, connections, positions -------------------------- */
$conn->begin_transaction();

/* 4a. mark images published */
$upd = $conn->prepare(
    "UPDATE 360g_storeimages SET connectionstatus=1 WHERE storeID=?");
$upd->bind_param('i', $storeId);
$upd->execute();
$upd->close();

/* 4b. connections */
$conn->query("DELETE FROM image_connections WHERE store_id=$storeId");
$ins = $conn->prepare(
    'INSERT INTO image_connections (store_id, from_photoid, to_photoid)
     VALUES (?, ?, ?)');
foreach ($photos as $p) {
    $from = $p['photo']['photoId']['id'];
    foreach ($p['photo']['connections'] as $c) {
        $to = $c['target']['id'];
        $ins->bind_param('iss', $storeId, $from, $to);
        $ins->execute();
    }
}
$ins->close();

/* 4c. positions */
if (!empty($positions)) {
    $posStmt = $conn->prepare(
        'UPDATE 360g_storeimages SET posX=?, posY=? WHERE id=? AND storeID=?');
    foreach ($positions as $p) {
        $posX=intval($p['posX']); $posY=intval($p['posY']); $id=intval($p['id']);
        $posStmt->bind_param('iiii', $posX, $posY, $id, $storeId);
        $posStmt->execute();
    }
    $posStmt->close();
}

$now = date('Y-m-d H:i:s');
$lastup = $conn->prepare( "UPDATE 360g_stores SET lastUpdate = ? WHERE id = ?");
$lastup->bind_param('si', $now, $storeId);
$lastup->execute();
$lastup->close();
$conn->commit();
$conn->commit();

/* ---- 5. Done ------------------------------------------------------------ */
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'google' => json_decode($googleResp, true)
]);
exit;
?>
