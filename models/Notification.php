<?php
// models/Notification.php - Notification model
class Notification {
    private $conn;
    private $table = 'notifications';
    
    // Notification properties
    public $id;
    public $user_id;
    public $type;
    public $title;
    public $message;
    public $is_read;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new notification
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->message = htmlspecialchars(strip_tags($this->message));
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      type = :type, 
                      title = :title, 
                      message = :message";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':message', $this->message);
        
        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly created notification
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Get all notifications for a user
    public function getAllNotifications($user_id, $limit = null, $offset = 0) {
        // Query
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        // Add limit if specified
        if($limit !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        
        if($limit !== null) {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $notifications = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'message' => $row['message'],
                'is_read' => (bool)$row['is_read'],
                'created_at' => $row['created_at']
            ];
        }
        
        return $notifications;
    }
    
    // Get unread notifications for a user
    public function getUnreadNotifications($user_id, $limit = null) {
        // Query
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id AND is_read = 0 
                  ORDER BY created_at DESC";
        
        // Add limit if specified
        if($limit !== null) {
            $query .= " LIMIT :limit";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        
        if($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $notifications = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'message' => $row['message'],
                'is_read' => (bool)$row['is_read'],
                'created_at' => $row['created_at']
            ];
        }
        
        return $notifications;
    }
    
    // Mark notification as read
    public function markAsRead() {
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Mark all notifications as read for a user
    public function markAllAsRead($user_id) {
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1 
                  WHERE user_id = :user_id AND is_read = 0";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Delete a notification
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id = :id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Get notification by ID
    public function getNotificationById($id) {
        // Query
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id = :id 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $id);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->type = $row['type'];
            $this->title = $row['title'];
            $this->message = $row['message'];
            $this->is_read = $row['is_read'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get notification count for a user
    public function getNotificationCount($user_id, $unread_only = false) {
        // Query
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE user_id = :user_id";
        
        if($unread_only) {
            $query .= " AND is_read = 0";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'];
    }

/**
 * Get notification link
 * Processes the link_data field to generate a link URL for the notification
 * 
 * @return string|null The URL for the notification link
 */
public function getNotificationLink() {
    if(empty($this->link_data)) {
        return null;
    }
    
    $link_data = json_decode($this->link_data, true);
    
    if(!$link_data || !isset($link_data['type'])) {
        return null;
    }
    
    switch($link_data['type']) {
        case 'friend_request':
            return '../views/community.php?view=requests';
            
        case 'profile':
            return '../views/community.php?view=profile&id=' . $link_data['id'];
            
        case 'challenge':
            return '../views/challenges.php?id=' . $link_data['id'];
            
        case 'habit':
            return '../views/habits.php?id=' . $link_data['id'];
            
        case 'goal':
            return '../views/goals.php?id=' . $link_data['id'];
            
        default:
            return null;
    }
}
/**
 * Get notification icon
 * Returns the appropriate Bootstrap icon class based on notification type
 * 
 * @return string The Bootstrap icon class
 */
public function getNotificationIcon() {
    switch($this->type) {
        case 'habit':
            return 'bi-check-circle-fill text-success';
            
        case 'goal':
            return 'bi-trophy-fill text-warning';
            
        case 'challenge':
            return 'bi-people-fill text-danger';
            
        case 'xp':
            return 'bi-lightning-fill text-primary';
            
        case 'level':
            return 'bi-arrow-up-circle-fill text-success';
            
        case 'friend':
            return 'bi-person-fill text-info';
            
        case 'system':
        default:
            return 'bi-info-circle-fill text-secondary';
    }
}
    
}