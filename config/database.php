<?php
// config/database.php - Database connection

$host = 'localhost';
$db_name = 'habit_tracker';
$username = 'root'; // Change if different
$password = ''; // Change if different

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection Error: " . $e->getMessage();
    die();
}