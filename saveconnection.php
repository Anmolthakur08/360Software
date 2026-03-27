<?php
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

$photos = $data['updatePhotoRequests'];

// Validate and extract optional positions
$positions = [];
if (isset($data['positions']) && is_array($data['positions'])) {
    $positions = $data['positions'];
}

// Get store ID
$storeId = intval($_GET['storeId'] ?? 0);
if ($storeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing storeId']);
    exit;
}

/* --------------------------------------- 2. Save Metadata -------------------------------------------- */
$conn->begin_transaction();

try {
    // Save connections
    saveImageConnections($conn, $storeId, $photos);

    // Save position updates
    saveImagePositions($conn, $storeId, $positions);
    $upd = $conn->prepare("UPDATE 360g_stores SET savecon = 1 WHERE id = ?");
    $upd->bind_param('i', $storeId);
    $upd->execute();
    $upd->close();
    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
exit;


/* ---- 3. Helpers: Save Connections and Positions ------------------------- */

function saveImageConnections(mysqli $conn, int $storeId, array $photos): bool {
    $delete = $conn->prepare("DELETE FROM image_connections WHERE store_id = ?");
    $delete->bind_param('i', $storeId);
    $delete->execute();
    $delete->close();

    $insert = $conn->prepare(
        'INSERT INTO image_connections (store_id, from_photoid, to_photoid)
         VALUES (?, ?, ?)'
    );

    foreach ($photos as $p) {
        if (!isset($p['photo']['photoId']['id'], $p['photo']['connections'])) continue;

        $from = $p['photo']['photoId']['id'];
        foreach ($p['photo']['connections'] as $c) {
            if (!isset($c['target']['id'])) continue;

            $to = $c['target']['id'];
            $insert->bind_param('iss', $storeId, $from, $to);
            $insert->execute();
        }
    }
    $insert->close();
    return true;
}

function saveImagePositions(mysqli $conn, int $storeId, array $positions): bool {
    if (empty($positions)) return true;

    $update = $conn->prepare(
        'UPDATE 360g_storeimages SET posX = ?, posY = ? WHERE id = ? AND storeID = ?'
    );

    foreach ($positions as $p) {
        if (!isset($p['id'], $p['posX'], $p['posY'])) continue;

        $id = intval($p['id']);
        $posX = intval($p['posX']);
        $posY = intval($p['posY']);
        $update->bind_param('iiii', $posX, $posY, $id, $storeId);
        $update->execute();
    }

    $update->close();
    return true;
}
?>
