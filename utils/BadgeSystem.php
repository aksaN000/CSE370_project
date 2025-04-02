<?php
// utils/BadgeSystem.php - Badge and achievement management

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Achievement.php';

class BadgeSystem {
    private $conn;
    private $achievement;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->achievement = new Achievement($conn);
    }
    
    // Award an achievement for a level
    public function awardLevelAchievement($user_id, $level_number) {
        // Check if user already has this achievement
        if($this->achievement->hasLevelAchievement($user_id, $level_number)) {
            return [
                'success' => false,
                'message' => 'User already has this achievement'
            ];
        }
        
        // Get level ID
        $level_id = $this->achievement->getLevelIdByNumber($level_number);
        
        if(!$level_id) {
            return [
                'success' => false,
                'message' => 'Invalid level number'
            ];
        }
        
        // Set achievement properties
        $this->achievement->user_id = $user_id;
        $this->achievement->level_id = $level_id;
        
        // Create achievement
        if($this->achievement->create()) {
            // Get level info
            $level_info = $this->getLevelInfo($level_number);
            
            return [
                'success' => true,
                'message' => 'Achievement awarded',
                'achievement' => [
                    'level' => $level_number,
                    'title' => $level_info['title'],
                    'badge_name' => $level_info['badge_name'],
                    'badge_description' => $level_info['badge_description']
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to award achievement'
            ];
        }
    }
    
    // Get level info
    public function getLevelInfo($level_number) {
        // Query
        $query = "SELECT * FROM levels WHERE level_number = :level_number LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':level_number', $level_number);
        
        // Execute query
        $stmt->execute();
        
        // Get result
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all user achievements
    public function getUserAchievements($user_id) {
        return $this->achievement->getUserAchievements($user_id);
    }
    
    // Get all available achievements
    public function getAllAchievements() {
        return $this->achievement->getAllAchievements();
    }

    public function ensureBasicAchievements($user_id) {
        // Ensure level 1 achievement is granted
        if (!$this->achievement->hasLevelAchievement($user_id, 1)) {
            $level_id = $this->achievement->getLevelIdByNumber(1);
            if ($level_id) {
                $this->achievement->user_id = $user_id;
                $this->achievement->level_id = $level_id;
                $this->achievement->create();
            }
        }
    }
    
    // Check if user has unlocked a level achievement
    public function hasUnlockedLevel($user_id, $level_number) {
        return $this->achievement->hasLevelAchievement($user_id, $level_number);
    }
    
    // Handle special achievements (can be expanded in the future)
    public function checkSpecialAchievements($user_id, $action_type, $data) {
        // This method can be expanded to handle special achievements
        // based on different actions (habit completions, streaks, etc.)
        
        $special_achievements = [];
        
        switch($action_type) {
            case 'habit_completion':
                // Check for habit-related achievements
                // e.g., Complete 5 habits in a day, 50 total completions, etc.
                break;
                
            case 'streak':
                // Check for streak-related achievements
                // e.g., 7-day streak, 30-day streak, etc.
                break;
                
            case 'goal':
                // Check for goal-related achievements
                // e.g., Complete 5 goals, etc.
                break;
                
            case 'challenge':
                // Check for challenge-related achievements
                // e.g., Complete 3 challenges, etc.
                break;
                
            case 'journal':
                // Check for journal-related achievements
                // e.g., Write 10 journal entries, etc.
                break;
        }
        
        return $special_achievements;
    }
}
?>