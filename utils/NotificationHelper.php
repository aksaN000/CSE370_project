<?php
class NotificationHelper {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Check if notifications are enabled for a specific user and notification type
     * 
     * @param int $user_id The user ID
     * @param string $notification_type The notification type (habit, goal, challenge, level_up, etc.)
     * @return bool True if notifications are enabled, false otherwise
     */
    public function areNotificationsEnabled($user_id, $notification_type) {
        $query = "SELECT * FROM user_settings WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no settings found, use defaults
        if (!$settings) {
            return true; // Default to enabled
        }
        
        // Map notification type to setting field
        switch ($notification_type) {
            case 'habit':
                return (bool)$settings['habit_reminders'];
            case 'goal':
                return (bool)$settings['goal_updates'];
            case 'challenge':
                return (bool)$settings['challenge_notifications'];
            case 'level':
            case 'xp':
                return (bool)$settings['level_up_notifications'];
            case 'friend':
                return true; // Friend notifications are always enabled
            case 'system':
                return true; // System notifications are always enabled
            default:
                return true; // Default to enabled for unknown types
        }
    }
    
    /**
     * Create a notification only if it's enabled in user settings
     * 
     * @param array $notification_data Notification data
     * @return bool True if notification was created, false otherwise
     */
    public function createNotificationIfEnabled($notification_data) {
        // Extract necessary data
        $user_id = $notification_data['user_id'];
        $type = $notification_data['type'];
        
        // Check if this notification type is enabled
        if (!$this->areNotificationsEnabled($user_id, $type)) {
            return false; // Don't create notification if disabled
        }
        
        // Create notification
        $query = "INSERT INTO notifications (user_id, type, title, message, link_data) 
                  VALUES (:user_id, :type, :title, :message, :link_data)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':title', $notification_data['title']);
        $stmt->bindParam(':message', $notification_data['message']);
        
        // Handle link_data (may be null)
        if (isset($notification_data['link_data'])) {
            $link_data = is_array($notification_data['link_data']) ? 
                         json_encode($notification_data['link_data']) : 
                         $notification_data['link_data'];
            $stmt->bindParam(':link_data', $link_data);
        } else {
            $stmt->bindValue(':link_data', null, PDO::PARAM_NULL);
        }
        
        return $stmt->execute();
    }
}