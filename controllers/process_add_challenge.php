<?php
// controllers/process_add_challenge.php - Process add challenge form

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

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/challenges.php');
    exit;
}

// Validate form data
$errors = [];

// Title validation
if(!isset($_POST['title']) || empty($_POST['title'])) {
    $errors[] = 'Challenge title is required';
}

// Description validation
if(!isset($_POST['description']) || empty($_POST['description'])) {
    $errors[] = 'Challenge description is required';
}

// Date validation
if(!isset($_POST['start_date']) || empty($_POST['start_date'])) {
    $errors[] = 'Start date is required';
}

if(!isset($_POST['end_date']) || empty($_POST['end_date'])) {
    $errors[] = 'End date is required';
}

// Check start date is not after end date
if(isset($_POST['start_date']) && isset($_POST['end_date']) && strtotime($_POST['start_date']) > strtotime($_POST['end_date'])) {
    $errors[] = 'Start date cannot be after end date';
}

// XP reward validation
if(!isset($_POST['xp_reward']) || empty($_POST['xp_reward']) || !is_numeric($_POST['xp_reward']) || $_POST['xp_reward'] < 10) {
    $errors[] = 'XP reward must be at least 10';
}

// If there are errors, redirect back with error messages
if(!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ../views/challenges.php?action=create');
    exit;
}

// Process tasks
$tasks = [];
if(isset($_POST['tasks']) && is_array($_POST['tasks'])) {
    foreach($_POST['tasks'] as $task) {
        if(!empty($task['title'])) {
            $tasks[] = [
                'title' => $task['title'],
                'description' => $task['description'] ?? ''
            ];
        }
    }
}

// Prepare challenge data
$challenge_data = [
    'creator_id' => $_SESSION['user_id'],
    'title' => $_POST['title'],
    'description' => $_POST['description'],
    'start_date' => $_POST['start_date'],
    'end_date' => $_POST['end_date'],
    'xp_reward' => $_POST['xp_reward'],
    'tasks' => $tasks
];

// Create challenge controller
$challengeController = new ChallengeController();

// Try to add the challenge
$result = $challengeController->addChallenge($challenge_data);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
    header('Location: ../views/challenges.php?id=' . $result['challenge_id']);
} else {
    $_SESSION['error'] = $result['message'];
    header('Location: ../views/challenges.php?action=create');
}
exit;