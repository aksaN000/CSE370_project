
<?php
// controllers/process_complete_task.php - Process complete task form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Challenge.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';
require_once __DIR__ . '/../utils/XPSystem.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if required parameters are provided
if(!isset($_POST['challenge_id']) || empty($_POST['challenge_id']) || !isset($_POST['task_id']) || empty($_POST['task_id'])) {
    $_SESSION['error'] = 'Invalid form submission';
    header('Location: ../views/challenges.php');
    exit;
}

// Get form data
$challenge_id = $_POST['challenge_id'];
$task_id = $_POST['task_id'];
$user_id = $_SESSION['user_id'];

// Create challenge controller
$challengeController = new ChallengeController();

// Try to complete the task
$result = $challengeController->completeTask($challenge_id, $task_id, $user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
    
    // If challenge completed and XP awarded, update session
    if(isset($result['challenge_completed']) && $result['challenge_completed']) {
        // Update user level in session if level up occurred
        if(isset($result['level_up']) && $result['level_up'] && isset($result['new_level'])) {
            $_SESSION['level'] = $result['new_level'];
        }
    }
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to challenge page
header('Location: ../views/challenges.php?id=' . $challenge_id);
exit;