
<?php
// controllers/process_add_task.php - Process add task form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Challenge.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if required parameters are provided
if(!isset($_POST['challenge_id']) || empty($_POST['challenge_id']) || !isset($_POST['title']) || empty($_POST['title'])) {
    $_SESSION['error'] = 'Invalid form submission';
    header('Location: ../views/challenges.php');
    exit;
}

// Get form data
$challenge_id = $_POST['challenge_id'];
$title = $_POST['title'];
$description = $_POST['description'] ?? '';
$user_id = $_SESSION['user_id'];

// Create challenge object
$conn = $GLOBALS['conn'];
$challenge = new Challenge($conn);
$challenge->id = $challenge_id;

// Check if challenge exists and user is the creator
if($challenge->getChallengeById($challenge_id)) {
    if($challenge->creator_id != $user_id) {
        $_SESSION['error'] = 'You are not authorized to add tasks to this challenge';
        header('Location: ../views/challenges.php?id=' . $challenge_id);
        exit;
    }
    
    // Add the task
    if($challenge->addTask($title, $description)) {
        $_SESSION['success'] = 'Task added successfully';
    } else {
        $_SESSION['error'] = 'Failed to add task';
    }
} else {
    $_SESSION['error'] = 'Challenge not found';
}

// Redirect back to challenge page
header('Location: ../views/challenges.php?id=' . $challenge_id);
exit;