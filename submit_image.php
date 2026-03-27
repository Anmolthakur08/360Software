<?php
/**
 * g_storeimages_add.php
 * Upload a 360-degree image for a store and record it in the DB.
 */

// ---------------------------------------------------------------------------
// CONFIG
// ---------------------------------------------------------------------------
include_once './config.php';   // provides $conn (mysqli)

// ---------------------------------------------------------------------------
// MAIN
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['newimagemessage'] = 'Invalid request method.';
    header('Location: g_storeimages.php');
    exit();
}
$user = $_SESSION['user'];
$userID = $user['id'];

/* ---------- Basic request values ---------- */
$storeID = $conn->real_escape_string($_POST['storeID'] ?? '');
$imageName = $conn->real_escape_string($_FILES['newImage']['name'] ?? '');

/* ---------- Early duplicate-name check ---------- */
// $dup = $conn->query("SELECT 1 FROM 360g_storeimages WHERE imageName = '$imageName' LIMIT 1");
// if ($dup && $dup->num_rows > 0) {
//     $_SESSION['newimagemessage'] = 'Image already exists.';
//     header("Location: g_storeimages.php?id=$storeID");
//     exit();
// }

/* ---------- Look up store lat/lon ---------- */
$stmtsid = $conn->prepare(
    'SELECT placeLat, placeLon FROM 360g_stores WHERE id = ? LIMIT 1'
);
$stmtsid->bind_param('i', $storeID);
$stmtsid->execute();
$stmtsid->bind_result($placeLat, $placeLon);
$found = $stmtsid->fetch();
$stmtsid->close();

if (!$found) {
    $_SESSION['newimagemessage'] = "Store ID $storeID not found.";
    header('Location: g_storeimages.php');
    exit();
}

/* ---------- Check uploaded file exists ---------- */
if (!isset($_FILES['newImage']) || $_FILES['newImage']['error'] !== 0) {
    $_SESSION['newimagemessage'] = 'No image uploaded or upload error.';
    header("Location: g_storeimages.php?id=$storeID");
    exit();
}

/* ---------- NOTE: 360-image validation removed ---------- */
$tmpPath = $_FILES['newImage']['tmp_name'];

/* ---------- Optional: extension whitelist ---------- */
$fileExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($fileExt, $allowed, true)) {
    $_SESSION['newimagemessage'] = 'Invalid file type. Only JPG, JPEG, PNG, GIF allowed.';
    header("Location: g_storeimages.php?id=$storeID");
    exit();
}

/* ---------- Form fields (fall back to store lat/lon if 0) ---------- */
$imageLat = ($_POST['newimageLat'] ?? 0) == 0 ? $placeLat : $conn->real_escape_string($_POST['newimageLat']);
$imageLong = ($_POST['newimageLong'] ?? 0) == 0 ? $placeLon : $conn->real_escape_string($_POST['newimageLong']);
$imageHeading = empty($_POST['newimageHeading']) ? 1 : (int) $_POST['newimageHeading'];
$imageAlt = $conn->real_escape_string($_POST['newimageAlt'] ?? '');

/* ---------- Determine next `orders` value ---------- */
$row = $conn->query("SELECT MAX(orders) AS max_order FROM 360g_storeimages WHERE storeID = '$storeID'")
    ->fetch_assoc();
$newOrder = isset($row['max_order']) ? $row['max_order'] + 1 : 0;

/* ---------- Insert DB record ---------- */
$sql = "INSERT INTO 360g_storeimages
        (storeID, imageName, imageLat, imageLong, imageHeading, imageAlt, orders)
        VALUES
        ('$storeID', '$imageName', '$imageLat', '$imageLong', '$imageHeading', '$imageAlt', '$newOrder')";
if (!$conn->query($sql)) {
    $_SESSION['newimagemessage'] = 'DB error: ' . $conn->error;
    header("Location: g_storeimages.php?id=$storeID");
    exit();
}

/* ---------- Move the file into its final folder ---------- */
$stmt = $conn->prepare('SELECT storeName FROM 360g_stores WHERE id = ?');
$stmt->bind_param('i', $storeID);
$stmt->execute();
$stmt->bind_result($storeName);
$stmt->fetch();
$stmt->close();

$safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $storeName);
$uploadDir = __DIR__ . "/upload/$userID/$safeName/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$destPath = $uploadDir . basename($imageName);

if (move_uploaded_file($tmpPath, $destPath)) {
    $upd = $conn->prepare("UPDATE 360g_stores SET savecon = 0 WHERE id = ?");
    $upd->bind_param('i', $storeID);
    $upd->execute();
    $upd->close();
    $_SESSION['success_message'] = 'New Image added successfully.';
} else {
    $_SESSION['newimagemessage'] = 'Image added but file-move failed.';
}

$conn->close();
header("Location: g_storeimages.php?id=$storeID");
exit();
?>