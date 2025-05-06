<?php
// controllers/process_habit_completion.php - Process habit completion form
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../controllers/HabitController.php';
require_once __DIR__ . '/../utils/XPSystem.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Return error JSON
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if habit_id is provided
if(!isset($_POST['habit_id']) || empty($_POST['habit_id'])) {
    // Return error JSON
    echo json_encode([
        'success' => false,
        'message' => 'No habit specified'
    ]);
    exit;
}

// Get the habit ID
$habit_id = $_POST['habit_id'];
$user_id = $_SESSION['user_id'];

// Create habit controller
$habitController = new HabitController();

// Try to complete the habit
$result = $habitController->completeHabit($habit_id, $user_id);

// Return JSON response
echo json_encode($result);
exit;