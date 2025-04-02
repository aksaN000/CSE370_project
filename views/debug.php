<?php
// debug.php - Place this file in your main web directory
// This file checks key components of your application

// Enable all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Habit Tracker Diagnostic Tool</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Check session
echo "<h2>Session Status</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "Session was not started - started now<br>";
} else {
    echo "Session already started<br>";
}

// Display session info
echo "<h3>Session Variables:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "Database connection file included<br>";
    
    if (isset($conn) && $conn instanceof PDO) {
        echo "Database connection successful<br>";
        
        // Test a simple query
        $stmt = $conn->prepare("SELECT 1");
        $stmt->execute();
        echo "Query execution successful<br>";
        
        // Check if notifications table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE 'notifications'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "Notifications table exists<br>";
            
            // Check notification structure
            $stmt = $conn->prepare("DESCRIBE notifications");
            $stmt->execute();
            echo "<h3>Notifications Table Structure:</h3>";
            echo "<pre>";
            print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
            echo "</pre>";
            
            // Count notifications
            $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications");
            $stmt->execute();
            echo "Total notifications: " . $stmt->fetchColumn() . "<br>";
        } else {
            echo "Notifications table does not exist<br>";
        }
    } else {
        echo "Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Check file permissions and existence
echo "<h2>File Status</h2>";
$important_files = [
    'config/database.php',
    'controllers/HabitController.php',
    'controllers/GoalController.php',
    'controllers/ChallengeController.php',
    'controllers/AnalyticsController.php',
    'controllers/NotificationController.php',
    'views/habits.php',
    'views/goals.php',
    'views/challenges.php',
    'views/analytics.php'
];

foreach ($important_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "$file exists - ";
        if (is_readable($full_path)) {
            echo "and is readable<br>";
        } else {
            echo "but is not readable<br>";
        }
    } else {
        echo "$file does not exist<br>";
    }
}

// Check HTTP headers if redirects are happening
echo "<h2>HTTP Headers</h2>";
echo "<pre>";
print_r(apache_request_headers());
echo "</pre>";

// Check which URL parameters are present
echo "<h2>URL Parameters</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Simple fix advice
echo "<h2>Quick Fix Suggestion</h2>";
echo "If pages are loading with blank content, try these steps:<br>";
echo "1. Clear your browser cache and cookies<br>";
echo "2. Try accessing habits.php with this special parameter: <a href='views/habits.php?bypass_handlers=1'>views/habits.php?bypass_handlers=1</a><br>";
echo "3. Check PHP error logs for any specific errors<br>";
?>