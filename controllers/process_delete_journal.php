
<?php
// controllers/process_delete_journal.php - Process delete journal form
// Start session
session_start();

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Journal.php';
require_once __DIR__ . '/../controllers/JournalController.php';


// Check if user is logged in
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page
    header('Location: ../views/auth/login.php');
    exit;
}

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/journal.php');
    exit;
}

// Check if journal_id is provided
if(!isset($_POST['journal_id']) || empty($_POST['journal_id'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../views/journal.php');
    exit;
}

// Get journal ID
$journal_id = $_POST['journal_id'];
$user_id = $_SESSION['user_id'];

// Create journal controller
$journalController = new JournalController();

// Try to delete the journal
$result = $journalController->deleteJournal($journal_id, $user_id);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to journal page
header('Location: ../views/journal.php');
exit;