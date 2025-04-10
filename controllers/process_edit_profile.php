<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Check login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../views/auth/login.php');
    exit;
}

$authController = new AuthController();
$result = $authController->editProfile($_SESSION['user_id'], $_POST);

if ($result['success']) {
    $_SESSION['success'] = $result['message'];
    if (isset($result['profile_picture'])) {
        $_SESSION['profile_picture'] = $result['profile_picture'];
    }
} else {
    $_SESSION['error'] = is_array($result['errors']) ? implode(', ', $result['errors']) : $result['errors'];
}

header('Location: ../views/profile.php');
exit;