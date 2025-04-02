// Modified version of process_update_privacy.php - Create this file in the controllers directory

<?php
// controllers/process_update_privacy.php - Process privacy settings update

// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SettingsController.php';

// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_privacy'])) {
    header('Location: ../views/settings.php');
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get form data - basic settings
$public_profile = isset($_POST['public_profile']) ? 1 : 0;
$show_stats = isset($_POST['show_stats']) ? 1 : 0;
$show_achievements = isset($_POST['show_achievements']) ? 1 : 0;
$analytics_consent = isset($_POST['analytics_consent']) ? 1 : 0;

// Get additional privacy settings
$additional_settings = [
    'profile_visibility' => $_POST['profile_visibility'] ?? 'private',
    'show_habits' => isset($_POST['show_habits']) ? 1 : 0,
    'show_goals' => isset($_POST['show_goals']) ? 1 : 0,
    'show_challenges' => isset($_POST['show_challenges']) ? 1 : 0,
    'allow_challenge_invites' => isset($_POST['allow_challenge_invites']) ? 1 : 0,
    'show_in_leaderboards' => isset($_POST['show_in_leaderboards']) ? 1 : 0,
    'allow_friend_requests' => isset($_POST['allow_friend_requests']) ? 1 : 0,
    'feature_improvement_consent' => isset($_POST['feature_improvement_consent']) ? 1 : 0,
    'data_sharing' => isset($_POST['data_sharing']) ? 1 : 0
];

// Create settings controller
$settingsController = new SettingsController();

// Update privacy settings
$result = $settingsController->updatePrivacySettings(
    $user_id, 
    $public_profile, 
    $show_stats, 
    $show_achievements, 
    $analytics_consent,
    $additional_settings
);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to settings page with privacy tab active
header('Location: ../views/settings.php#privacy');
exit;