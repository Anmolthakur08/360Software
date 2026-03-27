<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once './config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = trim($_POST['storeName']);
    $storeId = isset($_POST['storeId']) ? intval($_POST['storeId']) : 0;

    if ($storeName === '') {
        $_SESSION['message'] = 'Store name cannot be empty!';
        header("Location: ./store.php");
        exit();
    }

    if ($storeId > 0) {
        // Update existing store
        $sql = "SELECT * FROM 360g_stores WHERE storeName = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $storeName, $storeId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['message'] = 'Store name already exists!';
            $stmt->close();
            $conn->close();
            header("Location: ./store.php");
            exit();
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE 360g_stores SET storeName = ? WHERE id = ?");
        $stmt->bind_param("si", $storeName, $storeId);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Store updated successfully!';
        } else {
            $_SESSION['message'] = 'Error updating store: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        // Insert new store
        $sql = "SELECT * FROM 360g_stores WHERE storeName = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $storeName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['message'] = 'Store already exists!';
            $stmt->close();
            $conn->close();
            header("Location: ./store.php");
            exit();
        }
        $stmt->close();

        $user = $_SESSION['user'];
        $userId = $user['id'];

        $stmt = $conn->prepare("INSERT INTO 360g_stores (storeName, userId, dateCreated) VALUES (?, ?, NOW())");
        if ($stmt === false) {
            die("Error preparing the statement: " . $conn->error);
        }
        $stmt->bind_param("si", $storeName, $userId);

        if ($stmt->execute()) {
            $_SESSION['message'] = 'Store added successfully!';
            // Create directory with storeName under 'upload'
            $uploadDir = __DIR__ . '/upload/' . $userId . '' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $storeName);
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $_SESSION['message'] .= ' However, failed to create store folder.';
                }
            }
        } else {
            $_SESSION['message'] = 'Error adding store: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();
    header("Location: ./store.php");
    exit();
}
?>