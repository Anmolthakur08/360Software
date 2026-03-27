<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once './config.php';
$user = $_SESSION['user'];
$userId = $user['id'];




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placeNameWithCountry = trim($_POST['placeId']);
    if ($placeNameWithCountry === '') {
        $_SESSION['savestoreError'] = 'Error: Please select a place.';
        header("Location: store.php");
        exit();
    }

    if (isset($_POST['placeId'])) {
        $placeId = $_POST['placeId'];
        $originalPlaceName = $_POST[$placeId];
        $placeLat = $_POST[$placeId . '_lat'];
        $placeLong = $_POST[$placeId . '_long'];
        $sanitizedStoreName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalPlaceName);

        if ($conn->connect_error) {
            //   die("" . $conn->connect_error);
            $_SESSION['savestoreError'] = 'Database connection failed ';
            header("Location: store.php");
            exit();
        }


        // ✅ Update placeId

        $updateSql = "INSERT INTO 360g_stores (storeName, placeId,userId,placeLat,placeLon) values (?,?,?,?,?) ";
        $stmt = $conn->prepare($updateSql);

        $stmt->bind_param("ssiss", $sanitizedStoreName, $placeId, $userId, $placeLat, $placeLong);

        if ($stmt->execute()) {
            $uploadDir = __DIR__ . '/upload/' . $userId . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sanitizedStoreName);
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $_SESSION['savestoreError'] .= ' However, failed to create store folder.';
                    header("Location: store.php");
                    exit();
                }
            }
            header("Location: store.php");
            $_SESSION['savestoresuccess'] .= 'Store Added Successfully';
            exit();
        } else {
            $_SESSION['savestoreError'] .= ' ❌ Error updating database: ';
            header("Location: store.php");
            exit();
        }

        $stmt->close();
        $conn->close();
    } else {
        $_SESSION['savestoreError'] .= ' ❌ Please select a place';
        header("Location: store.php");
        exit();
    }
} else {
    $_SESSION['savestoreError'] .= ' ❗ Invalid request. Please submit a place name and ensure store ID is provided in the URL.';
    header("Location: store.php");
    exit();
}
?>