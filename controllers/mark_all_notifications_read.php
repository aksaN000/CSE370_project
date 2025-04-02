<?php
// Start session
// controllers/mark_all_notifications_read.php - Mark all notifications as read controller

session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../controllers/NotificationController.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Create notification controller
$notificationController = new NotificationController();

// Try to mark all notifications as read
$result = $notificationController->markAllAsRead($user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to notifications page
header('Location: ../views/notifications.php');
exit;