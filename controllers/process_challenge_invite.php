<?php
// controllers/process_challenge_invite.php - Process challenge invitation

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

// Check if challenge_id and recipient_id are provided
if(!isset($_POST['challenge_id']) || !isset($_POST['recipient_id'])) {
    $_SESSION['error'] = 'Invalid request parameters';
    header('Location: ../views/community.php');
    exit;
}

// Get parameters
$challenge_id = $_POST['challenge_id'];
$recipient_id = $_POST['recipient_id'];

// Create community controller
$communityController = new CommunityController();

// Send challenge invitation
$result = $communityController->inviteToChallenge($challenge_id, $user_id, $recipient_id);

// Set session messages
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to referring page or challenge page
$referer = $_SERVER['HTTP_REFERER'] ?? "../views/challenges.php?id=$challenge_id";
header('Location: ' . $referer);
exit;