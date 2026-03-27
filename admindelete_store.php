<?php
header('Content-Type: application/json');
require_once './config.php';
$user = $_SESSION['user'];
$userId = $user['id'];

$payload = json_decode(file_get_contents('php://input'), true);

if (!isset($payload['storeId'])) {
    echo json_encode(['success' => false, 'error' => 'No ID']);
    exit;
}

$sql = "SELECT 360g_stores.id AS store_id, 360g_stores.storename, google_users.name AS user_name, google_users.email AS user_email,google_users.refresh_token AS user_refresh FROM 360g_stores 
INNER JOIN google_users ON 360g_stores.userId = google_users.id WHERE 360g_stores.id= $payload[storeId]";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();


$storeName = $data['storename'];
$userName = $data['user_name'];
$userEmail = $data['user_email'];
$userRefresh = $data['user_refresh'];

$storeId = (int) $payload['storeId'];


function rrmdir(string $dir): bool
{
    if (!is_dir($dir)) {
        return true;
    }
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    return rmdir($dir);
}

//---------------------------Access Token----------------------------------------------
function getAccessToken($userRefresh): string
{


    $tokenResponse = http_build_query([
        'client_id' => '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com',
        'client_secret' => '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',
        'refresh_token' => $userRefresh,
        'grant_type' => 'refresh_token',
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $tokenResponse,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($raw, true);
    if (!isset($data['access_token'])) {
        //  throw new RuntimeException('Could not obtain access-token: ' . $raw);
        $_SESSION['admindelstoreerror'] = 'Could not obtain access-token';
        header("Location: store.php");
        exit();
    }
    return $data['access_token'];
}


///--------------------------- 3. Utility: batch-delete Street View photos-----------------------------------------

function batchDeletePhotos(array $photoIds, string $accessToken): array
{
    if (empty($photoIds)) {
        $_SESSION['admindelstoreerror'] = 'Nothing to delete — photoIds array empty.';
        header("Location: adminpannel.php");
        exit();
    }

    $payload = json_encode(['photoIds' => $photoIds]);
    $ch = curl_init('https://streetviewpublish.googleapis.com/v1/photos:batchDelete');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        // throw new RuntimeException('Curl error: ' . curl_error($ch));
        $_SESSION['admindelstoreerror'] = 'Curl error:';
        header("Location: adminpannel.php");
        exit();
    }
    curl_close($ch);

    return json_decode($raw, true);
}


//---------------------------------- *  4. Main logic: delete store + photos------------------------------------

if (!isset($payload['storeId'])) {
    $storeId = $payload['storeId'];
    // 4-a.  Fetch store-name (for filesystem cleanup later)
    $stmt = $conn->prepare('SELECT storeName FROM 360g_stores WHERE id = ?');
    $stmt->bind_param('i', $storeId);
    $stmt->execute();
    $stmt->bind_result($storeName);
    $stmt->fetch();
    $stmt->close();

    // 4-b.  Collect every photo-ID for this store
    $photoStmt = $conn->prepare('SELECT 360photoId FROM 360g_storeimages WHERE storeID = ?');
    $photoStmt->bind_param('i', $storeId);
    $photoStmt->execute();
    $result = $photoStmt->get_result();
    $storeImageArray = [];
    while ($row = $result->fetch_assoc()) {
        $storeImageArray[] = $row['360photoId'];
    }
    $photoStmt->close();


    $accessToken = getAccessToken($userRefresh);

    // 4-d.  Delete Street View photos
    try {
        $deleteResp = batchDeletePhotos($storeImageArray, $accessToken);
        // Optional: inspect $deleteResp for per-photo error details
    } catch (RuntimeException $e) {
        $_SESSION['admindelstoreerror'] = 'Google deletion failed: ' . $e->getMessage();
        header('Location: ./adminpannel.php');
        exit;
    }

    // 4-e.  Remove DB rows + local folder
    if ($conn->query("DELETE FROM 360g_stores WHERE id = $storeId") === true) {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $storeName);
        $uploadDir = __DIR__ . '/upload/' . $userId . '/' . $safeName;
        rrmdir($uploadDir);
        $_SESSION['admindelstoresuccess'] = 'Store, images and folder deleted successfully.';
    } else {
        $_SESSION['admindelstoreerror'] = 'Error deleting store: ' . $conn->error;
    }
    $conn->close();
}

header('Location: ./adminpannel.php');
exit;
?>