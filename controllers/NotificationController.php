
<?php
// controllers/NotificationController.php - Notification controller
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';


class NotificationController {
    private $conn;
    private $notification;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->notification = new Notification($conn);
    }
    
    // Get all notifications for a user
    public function getAllNotifications($user_id, $limit = null, $offset = 0) {
        return $this->notification->getAllNotifications($user_id, $limit, $offset);
    }
    
    // Get unread notifications for a user
    public function getUnreadNotifications($user_id, $limit = null) {
        return $this->notification->getUnreadNotifications($user_id, $limit);
    }
    
    // Add a new notification
    public function addNotification($notification_data) {
        // Set the notification properties
        $this->notification->user_id = $notification_data['user_id'];
        $this->notification->type = $notification_data['type'];
        $this->notification->title = $notification_data['title'];
        $this->notification->message = $notification_data['message'];
        
        // Create the notification
        if($this->notification->create()) {
            return [
                'success' => true,
                'message' => 'Notification created successfully',
                'notification_id' => $this->notification->id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create notification'
            ];
        }
    }
    
    // Mark notification as read
    public function markAsRead($notification_id, $user_id) {
        // First check if the notification exists and belongs to the user
        $this->notification->id = $notification_id;
        if(!$this->notification->getNotificationById($notification_id) || $this->notification->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid notification or unauthorized access'
            ];
        }
        
        // Mark as read
        if($this->notification->markAsRead()) {
            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ];
        }
    }
    
    // Mark all notifications as read for a user
    public function markAllAsRead($user_id) {
        if($this->notification->markAllAsRead($user_id)) {
            return [
                'success' => true,
                'message' => 'All notifications marked as read'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to mark notifications as read'
            ];
        }
    }
    
    // Delete a notification
    public function deleteNotification($notification_id, $user_id) {
        // First check if the notification exists and belongs to the user
        $this->notification->id = $notification_id;
        if(!$this->notification->getNotificationById($notification_id) || $this->notification->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid notification or unauthorized access'
            ];
        }
        
        // Delete the notification
        if($this->notification->delete()) {
            return [
                'success' => true,
                'message' => 'Notification deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete notification'
            ];
        }
    }
    
    // Get notification count for a user
    public function getNotificationCount($user_id, $unread_only = false) {
        return $this->notification->getNotificationCount($user_id, $unread_only);
    }
}