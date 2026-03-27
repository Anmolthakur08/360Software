<?php
$cookie_lifetime = 60 * 60 * 24 * 7; // 7 days

session_set_cookie_params([
  'lifetime' => $cookie_lifetime,
  'path' => '/',
  'secure' => true,              // only send cookie over HTTPS
  'httponly' => true,              // not accessible via JS
  'samesite' => 'Lax'              // or 'Strict' based on your needs
]);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'On');
$DB_SERVER = "db5018092804.hosting-data.io";
$DB_USER = "dbu2900651";
$DB_PASS = "XpF%dl4#W1*,VMy";
$DB_NAME = "dbs14366958";
// Database connection
$conn = new mysqli($DB_SERVER, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>