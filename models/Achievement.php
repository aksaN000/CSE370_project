<?php
// models/Achievement.php - Achievement model for badges and achievements

class Achievement {
    private $conn;
    private $table = 'user_achievements';
    private $levels_table = 'levels';
    
    // Achievement properties
    public $id;
    public $user_id;
    public $level_id;
    public $unlocked_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new achievement
    public function create() {
        // Check if achievement already exists
        if($this->achievementExists()) {
            return false;
        }
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      level_id = :level_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':level_id', $this->level_id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if achievement exists
    private function achievementExists() {
        // Query
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE user_id = :user_id AND level_id = :level_id 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':level_id', $this->level_id);
        
        // Execute query
        $stmt->execute();
        
        // Return result
        return $stmt->rowCount() > 0;
    }
    
    // Get all achievements for a user
    public function getUserAchievements($user_id) {
        // Query
        $query = "SELECT ua.*, l.level_number, l.title, l.badge_name, l.badge_description, l.badge_image 
                  FROM " . $this->table . " ua
                  JOIN " . $this->levels_table . " l ON ua.level_id = l.id
                  WHERE ua.user_id = :user_id
                  ORDER BY l.level_number ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if user has a specific level achievement
    public function hasLevelAchievement($user_id, $level_number) {
        // Query
        $query = "SELECT ua.id 
                  FROM " . $this->table . " ua
                  JOIN " . $this->levels_table . " l ON ua.level_id = l.id
                  WHERE ua.user_id = :user_id AND l.level_number = :level_number
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':level_number', $level_number);
        
        // Execute query
        $stmt->execute();
        
        // Return result
        return $stmt->rowCount() > 0;
    }
    
    // Get level ID by level number
    public function getLevelIdByNumber($level_number) {
        // Query
        $query = "SELECT id FROM " . $this->levels_table . " 
                  WHERE level_number = :level_number 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':level_number', $level_number);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            return $row['id'];
        }
        
        return false;
    }
    
    // Get all available achievements
    public function getAllAchievements() {
        // Query
        $query = "SELECT * FROM " . $this->levels_table . " 
                  ORDER BY level_number ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>