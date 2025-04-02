
<?php
// controllers/process_add_habit.php - Process add habit form
// Start session
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
    header('Location: ../index.php');
    exit;
}

// Validate form data
$errors = [];

// Title validation
if(!isset($_POST['title']) || empty($_POST['title'])) {
    $errors[] = 'Habit title is required';
}

// Frequency validation
if(!isset($_POST['frequency_type']) || empty($_POST['frequency_type'])) {
    $errors[] = 'Frequency type is required';
}

// Check frequency values based on type
$frequency_value = null;
if($_POST['frequency_type'] === 'weekly' && (!isset($_POST['frequency_value']) || empty($_POST['frequency_value']))) {
    $errors[] = 'Please select at least one day of the week';
} elseif($_POST['frequency_type'] === 'weekly') {
    $frequency_value = json_encode($_POST['frequency_value']);
} elseif($_POST['frequency_type'] === 'monthly' && isset($_POST['monthly_day'])) {
    $frequency_value = json_encode(['day' => intval($_POST['monthly_day'])]);
} elseif($_POST['frequency_type'] === 'custom' && isset($_POST['custom_days'])) {
    $frequency_value = json_encode(['days' => intval($_POST['custom_days'])]);
} elseif($_POST['frequency_type'] === 'daily') {
    $frequency_value = json_encode(['daily' => true]);
}

// Date validation
if(!isset($_POST['start_date']) || empty($_POST['start_date'])) {
    $errors[] = 'Start date is required';
}

// If there are errors, redirect back with error messages
if(!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Prepare habit data
$habit_data = [
    'user_id' => $_SESSION['user_id'],
    'category_id' => $_POST['category_id'] ?? 1,
    'title' => $_POST['title'],
    'description' => $_POST['description'] ?? '',
    'frequency_type' => $_POST['frequency_type'],
    'frequency_value' => $frequency_value,
    'start_date' => $_POST['start_date'],
    'end_date' => empty($_POST['end_date']) ? null : $_POST['end_date'],
    'xp_reward' => $_POST['xp_reward'] ?? 10
];

// Create habit controller
$habitController = new HabitController();

// Try to add the habit
$result = $habitController->addHabit($habit_data);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to referring page or dashboard
$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header('Location: ' . $referer);
exit;