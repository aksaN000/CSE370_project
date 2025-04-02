<?php
// controllers/process_logout.php - Process user logout
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../views/auth/login.php');
exit;