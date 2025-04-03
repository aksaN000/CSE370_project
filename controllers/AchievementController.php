<?php
// controllers/AchievementController.php - Achievement controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Achievement.php';

class AchievementController {
    private $conn;
    private $achievement;
    private $special_achievements;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->achievement = new Achievement($conn);
        
        // Define special achievements
        $this->special_achievements = [
            [
                'name' => 'Early Bird',
                'description' => 'Complete 5 habits before 9 AM',
                'icon' => 'sunrise',
                'color' => 'warning',
                'check' => function($user_id) {
                    $query = "SELECT COUNT(DISTINCT DATE(hc.completion_date)) as early_habit_days
                              FROM habit_completions hc
                              JOIN habits h ON hc.habit_id = h.id
                              WHERE h.user_id = :user_id 
                              AND TIME(hc.completion_date) < '09:00:00'
                              GROUP BY DATE(hc.completion_date)
                              HAVING early_habit_days >= 5
                              LIMIT 1";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    return $stmt->rowCount() > 0;
                }
            ],
            [
                'name' => 'Perfectionist',
                'description' => 'Complete all habits for 7 consecutive days',
                'icon' => 'calendar-check',
                'color' => 'success',
                'check' => function($user_id) {
                    // First, get all unique completion dates for the user's habits
                    $query = "SELECT DISTINCT DATE(hc.completion_date) as completion_date
                              FROM habit_completions hc
                              JOIN habits h ON hc.habit_id = h.id
                              WHERE h.user_id = :user_id
                              ORDER BY completion_date";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Check for 7 consecutive days of habit completions
                    $max_consecutive_days = 0;
                    $current_streak = 1;
                    $last_date = null;
                    
                    foreach ($dates as $date) {
                        $current_date = new DateTime($date);
                        
                        if ($last_date !== null) {
                            $interval = $last_date->diff($current_date);
                            
                            if ($interval->days === 1) {
                                $current_streak++;
                                
                                // Update max streak if current streak is longer
                                $max_consecutive_days = max($max_consecutive_days, $current_streak);
                            } elseif ($interval->days > 1) {
                                // Reset streak if there's a gap
                                $current_streak = 1;
                            }
                        }
                        
                        $last_date = $current_date;
                    }
                    
                    return $max_consecutive_days >= 7;
                }
            ],
            [
                'name' => 'Goal Crusher',
                'description' => 'Complete 10 goals',
                'icon' => 'bullseye',
                'color' => 'danger',
                'check' => function($user_id) {
                    $query = "SELECT COUNT(*) as completed_goals 
                              FROM goals 
                              WHERE user_id = :user_id AND is_completed = 1";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $result['completed_goals'] >= 10;
                }
            ],
            [
                'name' => 'Social Butterfly',
                'description' => 'Join and complete 5 challenges',
                'icon' => 'people',
                'color' => 'primary',
                'check' => function($user_id) {
                    $query = "SELECT COUNT(*) as completed_challenges 
                              FROM challenge_participants 
                              WHERE user_id = :user_id AND is_completed = 1";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $result['completed_challenges'] >= 5;
                }
            ],
            [
                'name' => 'Deep Thinker',
                'description' => 'Write 20 journal entries',
                'icon' => 'journal-text',
                'color' => 'info',
                'check' => function($user_id) {
                    $query = "SELECT COUNT(*) as journal_entries 
                              FROM journal_entries 
                              WHERE user_id = :user_id";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $result['journal_entries'] >= 20;
                }
            ]
        ];
    }
    
    public function getUserAchievements($user_id) {
        // Fetch level-based achievements
        $query = "SELECT ua.*, l.level_number, l.title, l.badge_name, l.badge_description, l.badge_image 
                  FROM user_achievements ua
                  JOIN levels l ON ua.level_id = l.id
                  WHERE ua.user_id = :user_id
                  ORDER BY l.level_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get special achievements
        $special_achievements = $this->getSpecialAchievements($user_id);
        
        return [
            'level_achievements' => $achievements,
            'special_achievements' => $special_achievements
        ];
    }
    
    private function getSpecialAchievements($user_id) {
        $unlocked_special_achievements = [];
        foreach ($this->special_achievements as $achievement) {
            // Check if the achievement condition is met
            $check = $achievement['check'];
            if ($check($user_id)) {
                // Create a copy without the check function to avoid serialization issues
                $unlocked = [
                    'name' => $achievement['name'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'color' => $achievement['color']
                ];
                $unlocked_special_achievements[] = $unlocked;
            }
        }
        return $unlocked_special_achievements;
    }
}