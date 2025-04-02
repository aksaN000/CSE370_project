<?php
// controllers/process_delete_account.php - Process delete account
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SettingsController.php';

// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../views/settings.php');
    exit;
}

// Validate form data
if(!isset($_POST['delete_confirmation']) || $_POST['delete_confirmation'] !== 'DELETE MY ACCOUNT') {
    $_SESSION['error'] = 'Please type "DELETE MY ACCOUNT" to confirm account deletion';
    header('Location: ../views/settings.php#data');
    exit;
}

if(!isset($_POST['password']) || empty($_POST['password'])) {
    $_SESSION['error'] = 'Password is required to delete your account';
    header('Location: ../views/settings.php#data');
    exit;
}

$user_id = $_SESSION['user_id'];
$password = $_POST['password'];

// Create settings controller
$settingsController = new SettingsController();

// Try to delete the account
$result = $settingsController->deleteAccount($user_id, $password);

if($result['success']) {
    // Destroy session
    session_destroy();
    
    // Redirect to login page with success message
    session_start();
    $_SESSION['success'] = $result['message'];
    header('Location: ../views/auth/login.php');
} else {
    // Redirect back to settings with error message
    $_SESSION['error'] = $result['message'];
    header('Location: ../views/settings.php#data');
}
exit;