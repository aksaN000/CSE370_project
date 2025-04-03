<?php
// utils/XPSystem.php - XP and level management
class XPSystem {
    private $conn;
    private $users_table = 'users';
    private $levels_table = 'levels';
    private $achievements_table = 'user_achievements';
    private $notifications_table = 'notifications';
    private $notificationHelper;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->notificationHelper = new NotificationHelper($db);
    }
    
    // Award XP to a user
    public function awardXP($user_id, $xp_amount, $activity_type, $activity_description) {
        // First get current user XP and level
        $query = "SELECT current_xp, level FROM " . $this->users_table . " WHERE id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        $current_xp = $user['current_xp'];
        $current_level = $user['level'];
        
        // Update user XP
        $query = "UPDATE " . $this->users_table . " SET current_xp = current_xp + :xp_amount WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':xp_amount', $xp_amount);
        $stmt->bindParam(':user_id', $user_id);
        
        if(!$stmt->execute()) {
            return [
                'success' => false,
                'message' => 'Failed to update XP'
            ];
        }
        
        // Create XP notification only if enabled
        $notification_data = [
            'user_id' => $user_id,
            'type' => 'xp',
            'title' => 'XP Earned',
            'message' => "You earned {$xp_amount} XP for {$activity_description}"
        ];
        
        $this->notificationHelper->createNotificationIfEnabled($notification_data);
        
        // Check if user should level up
        $new_xp = $current_xp + $xp_amount;
        $level_up_result = $this->checkLevelUp($user_id, $new_xp, $current_level);
        
        return [
            'success' => true,
            'xp_awarded' => $xp_amount,
            'new_total_xp' => $new_xp,
            'level_up' => $level_up_result['level_up'],
            'new_level' => $level_up_result['new_level']
        ];
    }
    
    // Check if user should level up
    private function checkLevelUp($user_id, $current_xp, $current_level) {
        // Get next level requirements
        $query = "SELECT level_number, xp_required, title, badge_name 
                  FROM " . $this->levels_table . " 
                  WHERE xp_required <= :current_xp AND level_number > :current_level 
                  ORDER BY level_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':current_xp', $current_xp);
        $stmt->bindParam(':current_level', $current_level);
        $stmt->execute();
        
        $levels_to_award = [];
        $highest_level = $current_level;
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $levels_to_award[] = $row;
            $highest_level = $row['level_number'];
        }
        
        // If no new levels, return false
        if(empty($levels_to_award)) {
            return [
                'level_up' => false,
                'new_level' => $current_level
            ];
        }
        
        // Update user level
        $query = "UPDATE " . $this->users_table . " SET level = :new_level WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':new_level', $highest_level);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Award achievements and create notifications for each level
        foreach($levels_to_award as $level) {
            // Add achievement
            $this->addAchievement($user_id, $level['level_number']);
            
            // Create level-up notification only if enabled
            $notification_data = [
                'user_id' => $user_id,
                'type' => 'level',
                'title' => 'Level Up!',
                'message' => "Congratulations! You've reached Level " . $level['level_number'] . " - " . $level['title'] . 
                      " and earned the " . $level['badge_name'] . " badge!"
            ];
            
            $this->notificationHelper->createNotificationIfEnabled($notification_data);
        }
        
        return [
            'level_up' => true,
            'new_level' => $highest_level
        ];
    }
    
    // Add an achievement for a level
    private function addAchievement($user_id, $level_number) {
        // Get level ID
        $query = "SELECT id FROM " . $this->levels_table . " WHERE level_number = :level_number LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':level_number', $level_number);
        $stmt->execute();
        
        $level = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$level) {
            return false;
        }
        
        // Check if achievement already exists
        $query = "SELECT id FROM " . $this->achievements_table . " 
                  WHERE user_id = :user_id AND level_id = :level_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':level_id', $level['id']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return false;
        }
        
        // Add achievement
        $query = "INSERT INTO " . $this->achievements_table . " (user_id, level_id) VALUES (:user_id, :level_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':level_id', $level['id']);
        
        return $stmt->execute();
    }
}