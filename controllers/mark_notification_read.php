<?php

// Start session
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

// Check if notification_id is provided
if(!isset($_POST['notification_id']) && !isset($_GET['id'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../views/notifications.php');
    exit;
}

// Get notification ID
$notification_id = isset($_POST['notification_id']) ? $_POST['notification_id'] : $_GET['id'];
$user_id = $_SESSION['user_id'];

// Create notification controller
$notificationController = new NotificationController();

// Try to mark notification as read
$result = $notificationController->markAsRead($notification_id, $user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Determine redirect URL
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '../views/notifications.php';

// Redirect back to previous page
header('Location: ' . $redirect_url);
exit;
