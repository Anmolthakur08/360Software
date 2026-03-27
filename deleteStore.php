<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once './config.php';
$user = $_SESSION['user'];
$userId = $user['id'];
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

//--------------------------- Access Token ----------------------------------------------
function getAccessToken(mysqli $conn, int $userId): string
{
    $stmt = $conn->prepare('SELECT refresh_token FROM google_users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($refreshToken);
    $stmt->fetch();
    $stmt->close();

    if (!$refreshToken) {
        $_SESSION['deletestoreerror'] = 'No refresh-token found for this user.';
        header("Location: store.php");
        exit();
    }

    $tokenResponse = http_build_query([
        'client_id' => '310344686192-560imlrkhos1eu4e5c11ttj6aaoo6rb8.apps.googleusercontent.com',
        'client_secret' => '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',
        'refresh_token' => $refreshToken,
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
        $_SESSION['deletestoreerror'] = 'Could not obtain access-token';
        header("Location: store.php");
        exit();
    }
    return $data['access_token'];
}

//--------------------------- Utility: batch-delete Street View photos -------------------------------
function batchDeletePhotos(array $photoIds, string $accessToken): array
{
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
        $_SESSION['deletestoreerror'] = 'Curl error: ';
        header("Location: store.php");
        exit();
    }
    curl_close($ch);

    return json_decode($raw, true);
}

//--------------------------- Main logic: delete store + photos + folder -------------------------------

if (isset($_GET['id'], $_SESSION['user'])) {
    $storeId = (int) $_GET['id'];

    // Fetch store name
    $stmt = $conn->prepare('SELECT storeName FROM 360g_stores WHERE id = ?');
    $stmt->bind_param('i', $storeId);
    $stmt->execute();
    $stmt->bind_result($storeName);
    $stmt->fetch();
    $stmt->close();

    // Collect all photo IDs
    $photoStmt = $conn->prepare('SELECT `360photoId` FROM 360g_storeimages WHERE storeID = ? AND `360photoId` IS NOT NULL AND `360photoId` != ""');
    $photoStmt->bind_param('i', $storeId);
    $photoStmt->execute();
    $result = $photoStmt->get_result();
    $storeImageArray = [];
    while ($row = $result->fetch_assoc()) {
        $storeImageArray[] = $row['360photoId'];
    }
    $photoStmt->close();

    // If there are images, delete from Street View
    if (!empty($storeImageArray)) {
        $userId = $_SESSION['user']['id'];
        $accessToken = getAccessToken($conn, $userId);
        try {
            $deleteResp = batchDeletePhotos($storeImageArray, $accessToken);
            // Optional: log $deleteResp if needed
        } catch (RuntimeException $e) {
            $_SESSION['deletestoreerror'] = 'Google deletion failed: ';
            header('Location: ./store.php');
            exit;
        }
    }

    // Delete store from DB and remove folder
    if ($conn->query("DELETE FROM 360g_stores WHERE id = $storeId") === true) {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $storeName);
        $uploadDir = __DIR__ . '/upload/' . $userId . '/' . $safeName;
        rrmdir($uploadDir);
        $_SESSION['message'] = 'Store, images and folder deleted successfully.';
    } else {
        $_SESSION['deletestoreerror'] = 'Error deleting store: ' . $conn->error;
    }

    $conn->close();
}

header('Location: ./store.php');
exit;
?>