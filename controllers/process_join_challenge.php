
<?php
// controllers/process_join_challenge.php - Process join challenge form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Challenge.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if challenge_id is provided
if(!isset($_POST['challenge_id']) || empty($_POST['challenge_id'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../views/challenges.php');
    exit;
}

// Get challenge ID
$challenge_id = $_POST['challenge_id'];
$user_id = $_SESSION['user_id'];

// Create challenge controller
$challengeController = new ChallengeController();

// Try to join the challenge
$result = $challengeController->joinChallenge($challenge_id, $user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to challenge page
header('Location: ../views/challenges.php?id=' . $challenge_id);
exit;
