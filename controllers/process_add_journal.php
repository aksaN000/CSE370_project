
<?php
// controllers/process_add_journal.php - Process add journal form
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

// Validate form data
$errors = [];

// Title validation
if(!isset($_POST['title']) || empty($_POST['title'])) {
    $errors[] = 'Title is required';
}

// Content validation
if(!isset($_POST['content']) || empty($_POST['content'])) {
    $errors[] = 'Journal content is required';
}

// Mood validation
if(!isset($_POST['mood']) || empty($_POST['mood'])) {
    $errors[] = 'Mood is required';
}

// Date validation
if(!isset($_POST['entry_date']) || empty($_POST['entry_date'])) {
    $errors[] = 'Entry date is required';
}

// If there are errors, redirect back with error messages
if(!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ../views/journal.php?action=new');
    exit;
}

// Process references
$references = [];
if(isset($_POST['references']) && is_array($_POST['references'])) {
    foreach($_POST['references'] as $type => $ids) {
        foreach($ids as $id) {
            $references[] = [
                'type' => $type,
                'id' => $id
            ];
        }
    }
}

// Prepare journal data
$journal_data = [
    'user_id' => $_SESSION['user_id'],
    'title' => $_POST['title'],
    'content' => $_POST['content'],
    'mood' => $_POST['mood'],
    'entry_date' => $_POST['entry_date'],
    'references' => $references
];

// Create journal controller
$journalController = new JournalController();

// Try to add the journal
$result = $journalController->addJournal($journal_data);

// Set session messages based on result
if($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

// Redirect back to journal page
header('Location: ../views/journal.php');
exit;