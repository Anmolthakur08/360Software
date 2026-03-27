<?php
include_once './config.php';
// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

// Read raw POST input and decode JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!isset($data['sortedItems']) || !is_array($data['sortedItems'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Process sorted items
$sortedItems = $data['sortedItems'];

// Example: Loop through and echo each storeId and imageId (you can save to DB instead)
foreach ($sortedItems as $index => $item) {
    $storeId = $item['storeId'];
    $imageId = $item['imageId'];
    $udateQuery = "UPDATE 360g_storeimages SET orders = ? WHERE storeID = ? AND id = ?";

     $stmt = $conn->prepare($udateQuery);
    $stmt->bind_param("iii", $index,$storeId,$imageId);
    
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'DB error';
    }
    // Example debug output
    // In production, you would update the database here
    //error_log("Position: $index, Store ID: $storeId, Image ID: $imageId");
}

// Return success response
echo json_encode(['status' => 'success', 'message' => 'Items reordered successfully']);
