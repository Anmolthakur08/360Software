<?php
include_once './config.php';
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Get user input
$email = $_POST['email'];
$pass = $_POST['password'];

$stmt = $conn->prepare("SELECT id, FirstName, roles, password FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Check user and password
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['password'] === $pass) {
        $_SESSION['user'] = ['id'=>$row['id'],'name'=>$row['FirstName'],'role'=>$row['roles'],'email'=>$email];
        $_SESSION['loginsuccess'] = 'You have logged in successfully';
        header("Location: adminpannel.php");
        exit();
    } else {
        $_SESSION['loginmessage'] = 'Incorrect password.';
        header("Location: index.php");
        exit();
    }
} else {
    $_SESSION['loginmessage'] = 'Email not found.';
    header("Location: index.php");
    exit();
}

$stmt->close();
$conn->close();
?>