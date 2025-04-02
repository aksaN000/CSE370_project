<?php
// controllers/process_delete_habit.php - Process delete habit form
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../controllers/HabitController.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if habit_id is provided
if(!isset($_POST['habit_id']) || empty($_POST['habit_id'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../views/habits.php');
    exit;
}

// Get habit ID
$habit_id = $_POST['habit_id'];
$user_id = $_SESSION['user_id'];

// Create habit controller
$habitController = new HabitController();

// Try to delete the habit
$result = $habitController->deleteHabit($habit_id, $user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to habits page
header('Location: ../views/habits.php');
exit;
?>