
<?php
// controllers/process_update_goal.php - Process update goal progress form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../controllers/GoalController.php';
require_once __DIR__ . '/../utils/XPSystem.php';


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

// Check if required parameters are provided
if(!isset($_POST['goal_id']) || empty($_POST['goal_id']) || !isset($_POST['progress_value']) || !is_numeric($_POST['progress_value'])) {
    $_SESSION['error'] = 'Invalid form submission';
    header('Location: ../views/goals.php');
    exit;
}

// Get data from the form
$goal_id = $_POST['goal_id'];
$progress_value = intval($_POST['progress_value']);
$user_id = $_SESSION['user_id'];

// Create goal controller
$goalController = new GoalController();

// Try to update the goal progress
$result = $goalController->updateGoalProgress($goal_id, $user_id, $progress_value);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
    
    // If XP was awarded, add to message
    if(isset($result['xp_awarded'])) {
        $_SESSION['success'] .= ' You earned ' . $result['xp_awarded'] . ' XP!';
        
        // If level up occurred, add to message
        if(isset($result['level_up']) && $result['level_up']) {
            $_SESSION['success'] .= ' Congratulations! You leveled up to level ' . $result['new_level'] . '!';
        }
    }
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to goals page
header('Location: ../views/goals.php');
exit;