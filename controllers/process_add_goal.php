
<?php
// controllers/process_add_goal.php - Process add goal form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../controllers/GoalController.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Validate form data
$errors = [];

// Title validation
if(!isset($_POST['title']) || empty($_POST['title'])) {
    $errors[] = 'Goal title is required';
}

// Target value validation
if(!isset($_POST['target_value']) || empty($_POST['target_value']) || !is_numeric($_POST['target_value']) || $_POST['target_value'] <= 0) {
    $errors[] = 'Valid target value is required';
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

// If there are errors, redirect back with error messages
if(!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Prepare goal data
$goal_data = [
    'user_id' => $_SESSION['user_id'],
    'title' => $_POST['title'],
    'description' => $_POST['description'] ?? '',
    'target_value' => $_POST['target_value'],
    'start_date' => $_POST['start_date'],
    'end_date' => $_POST['end_date'],
    'xp_reward' => $_POST['xp_reward'] ?? 50
];

// Create goal controller
$goalController = new GoalController();

// Try to add the goal
$result = $goalController->addGoal($goal_data);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to goals page or dashboard
$referer = isset($_POST['redirect']) ? $_POST['redirect'] : '../views/goals.php';
header('Location: ' . $referer);
exit;