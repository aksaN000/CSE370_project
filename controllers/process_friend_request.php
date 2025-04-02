<?php
// controllers/process_friend_request.php - Process friend request

// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Community.php';
require_once __DIR__ . '/../controllers/CommunityController.php';

// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Check if action and recipient_id are provided
if(!isset($_POST['action']) || 
   ($_POST['action'] === 'send' && !isset($_POST['recipient_id'])) ||
   ($_POST['action'] === 'accept' && !isset($_POST['request_id'])) ||
   ($_POST['action'] === 'reject' && !isset($_POST['request_id'])) ||
   ($_POST['action'] === 'remove' && !isset($_POST['friend_id']))) {
    
    $_SESSION['error'] = 'Invalid request parameters';
    header('Location: ../views/community.php');
    exit;
}

// Create community controller
$communityController = new CommunityController();

// Process based on action
$action = $_POST['action'];
$referer = $_SERVER['HTTP_REFERER'] ?? '../views/community.php';

switch($action) {
    case 'send':
        $recipient_id = $_POST['recipient_id'];
        $result = $communityController->sendFriendRequest($user_id, $recipient_id);
        break;
        
    case 'accept':
        $request_id = $_POST['request_id'];
        $result = $communityController->acceptFriendRequest($request_id, $user_id);
        break;
        
    case 'reject':
        $request_id = $_POST['request_id'];
        $result = $communityController->rejectFriendRequest($request_id, $user_id);
        break;
        
    case 'remove':
        $friend_id = $_POST['friend_id'];
        $result = $communityController->removeFriend($user_id, $friend_id);
        break;
        
    default:
        $result = [
            'success' => false,
            'message' => 'Invalid action'
        ];
}

// Set session messages
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to referring page or community page
header('Location: ' . $referer);
exit;