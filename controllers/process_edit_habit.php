<?php
// controllers/process_edit_habit.php - Process edit habit form
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

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/habits.php');
    exit;
}

// Check if habit_id is provided
if(!isset($_POST['habit_id']) || empty($_POST['habit_id'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../views/habits.php');
    exit;
}

// Validate form data
$errors = [];

// Title validation
if(!isset($_POST['title']) || empty($_POST['title'])) {
    $errors[] = 'Habit title is required';
}

// If there are errors, redirect back with error messages
if(!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ../views/habits.php');
    exit;
}

// Prepare habit data
$habit_data = [
    'id' => $_POST['habit_id'],
    'user_id' => $_SESSION['user_id'],
    'category_id' => $_POST['category_id'] ?? 1,
    'title' => $_POST['title'],
    'description' => $_POST['description'] ?? '',
    'xp_reward' => $_POST['xp_reward'] ?? 10
];

// Create habit controller
$habitController = new HabitController();

// Try to update the habit
$result = $habitController->updateHabit($habit_data);

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