
<?php
// controllers/process_habit_completion.php - Process habit completion form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../controllers/HabitController.php';
require_once __DIR__ . '/../utils/XPSystem.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if habit_id is provided
if(!isset($_POST['habit_id']) || empty($_POST['habit_id'])) {
    $_SESSION['error'] = 'No habit specified';
    header('Location: ../index.php');
    exit;
}

// Get the habit ID
$habit_id = $_POST['habit_id'];
$user_id = $_SESSION['user_id'];

// Create habit controller
$habitController = new HabitController();

// Try to complete the habit
$result = $habitController->completeHabit($habit_id, $user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = 'Habit marked as complete! You earned ' . $result['xp_awarded'] . ' XP.';
    
    // If level up occurred, add to message
    if($result['level_up']) {
        $_SESSION['success'] .= ' Congratulations! You leveled up to level ' . $result['new_level'] . '!';
    }
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to referring page or dashboard
$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header('Location: ' . $referer);
exit;