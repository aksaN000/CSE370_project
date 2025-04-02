<?php
// notification_handler.php - A centralized notification handling utility

// Function to safely mark a notification as read
function markNotificationAsRead($notification_id, $user_id, $conn) {
    // Mark notification as read
    $query = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    return $stmt->execute();
}

// Function to handle notification read marking and redirection
// Returns true if a redirect was performed, false otherwise
function handleNotificationReadMarking() {
    global $conn;
    
    // Check if a notification needs to be marked as read
    if(isset($_GET['mark_read']) && is_numeric($_GET['mark_read']) && isset($_SESSION['user_id'])) {
        $notification_id = $_GET['mark_read'];
        $user_id = $_SESSION['user_id'];
        
        // Mark notification as read
        markNotificationAsRead($notification_id, $user_id, $conn);
        
        // Prevent infinite redirect loops by checking if mark_read is the only parameter
        if(count($_GET) === 1 && isset($_GET['mark_read'])) {
            // If mark_read is the only parameter, simply remove it without redirecting
            return false;
        }
        
        // Remove the parameter from URL to prevent repeated marking
        $params = $_GET;
        unset($params['mark_read']);
        $new_query_string = http_build_query($params);
        $redirect_url = $_SERVER['PHP_SELF'];
        if(!empty($new_query_string)) {
            $redirect_url .= '?' . $new_query_string;
        }
        
        header('Location: ' . $redirect_url);
        exit;
    }
    
    return false;
}