<?php
include_once './config.php';

unset($_SESSION['user']);

// Redirect to index.php
$_SESSION['logoutsuccess'] = 'You have logged out successfully';
header("Location: index.php");
exit;
?>