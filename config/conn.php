<?php
date_default_timezone_set('Asia/Kolkata');
$errorLogFile = __DIR__ . '/errors.txt';
if (file_exists($errorLogFile)) {
    file_put_contents($errorLogFile, '');
} else {
    touch($errorLogFile);
}
ini_set('log_errors', 1);
ini_set('error_log', $errorLogFile);
error_reporting(E_ALL);
ini_set('display_errors', 0);
$conn = new mysqli('localhost', 'root', '', 'samadhan_erp_db_2026');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (!$conn->query("SET SESSION time_zone = '+05:30'")) {
    die("Failed to set MySQL time zone: " . $conn->error);
}
$authentication_email="info.skcinfotech@gmail.com";
$authentication_password="zjdgjguewnqfjrwh";