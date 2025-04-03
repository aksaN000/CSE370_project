<?php
// controllers/HabitController.php - Habit controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../utils/XPSystem.php';
require_once __DIR__ . '/../utils/NotificationHelper.php';

class HabitController {
    private $conn;
    private $habit;
    private $xpSystem;
    private $notificationHelper;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->habit = new Habit($conn);
        $this->xpSystem = new XPSystem($conn);
        $this->notificationHelper = new NotificationHelper($conn);
    }
    
    // Get all habits for a user
    public function getAllHabits($user_id) {
        return $this->habit->getAllHabits($user_id);
    }
    
    // Get active habits for today
    public function getActiveHabitsForToday($user_id) {
        return $this->habit->getActiveHabitsForToday($user_id);
    }
    
    // Get all categories
    public function getAllCategories() {
        return $this->habit->getAllCategories();
    }
    
    public function getTotalCompletions($user_id) {
        // Query to get the total number of completions for this user
        $query = "SELECT COUNT(*) as total FROM habit_completions WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] ?? 0;
    }
    
    // Add a new habit
    public function addHabit($habit_data) {
        // Set the habit properties
        $this->habit->user_id = $habit_data['user_id'];
        $this->habit->category_id = $habit_data['category_id'];
        $this->habit->title = $habit_data['title'];
        $this->habit->description = $habit_data['description'];
        $this->habit->frequency_type = $habit_data['frequency_type'];
        $this->habit->frequency_value = $habit_data['frequency_value'];
        $this->habit->start_date = $habit_data['start_date'];
        $this->habit->end_date = $habit_data['end_date'];
        
        // Create the habit
        if($this->habit->create()) {
            return [
                'success' => true,
                'message' => 'Habit created successfully',
                'habit_id' => $this->habit->id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create habit'
            ];
        }
    }
    
    // Mark a habit as complete for today
    public function completeHabit($habit_id, $user_id) {
        // First check if the habit exists and belongs to the user
        $this->habit->id = $habit_id;
        if(!$this->habit->getHabitById($habit_id) || $this->habit->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid habit or unauthorized access'
            ];
        }
        
        // Check if the habit is already completed for today
        if($this->habit->isCompletedToday()) {
            return [
                'success' => false,
                'message' => 'Habit already completed for today'
            ];
        }
        
        // Mark the habit as complete
        if($this->habit->markAsComplete()) {
            // Award XP to the user
            $xp_result = $this->xpSystem->awardXP($user_id, $this->habit->xp_reward, 'habit', 'Completed habit: ' . $this->habit->title);
            
            // Create habit-specific notification
            $notification_data = [
                'user_id' => $user_id,
                'type' => 'habit',
                'title' => 'Habit Completed',
                'message' => "You completed the habit: {$this->habit->title}"
            ];
            
            $this->notificationHelper->createNotificationIfEnabled($notification_data);
            
            return [
                'success' => true,
                'message' => 'Habit marked as complete',
                'xp_awarded' => $this->habit->xp_reward,
                'level_up' => $xp_result['level_up'] ?? false,
                'new_level' => $xp_result['new_level'] ?? null
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to mark habit as complete'
            ];
        }
    }
    
    // Get level information
    public function getLevelInfo($level) {
        $query = "SELECT l1.*, l2.xp_required as next_level_xp 
                  FROM levels l1 
                  LEFT JOIN levels l2 ON l1.level_number + 1 = l2.level_number 
                  WHERE l1.level_number = :level 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':level', $level);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Delete a habit
    public function deleteHabit($habit_id, $user_id) {
        // First check if the habit exists and belongs to the user
        $this->habit->id = $habit_id;
        if(!$this->habit->getHabitById($habit_id) || $this->habit->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid habit or unauthorized access'
            ];
        }
        
        // Delete the habit
        if($this->habit->delete()) {
            return [
                'success' => true,
                'message' => 'Habit deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete habit'
            ];
        }
    }
    
    // Get habit statistics
    public function getHabitStatistics($habit_id, $user_id) {
        // First check if the habit exists and belongs to the user
        $this->habit->id = $habit_id;
        if(!$this->habit->getHabitById($habit_id) || $this->habit->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid habit or unauthorized access'
            ];
        }
        
        // Get statistics
        return $this->habit->getStatistics();
    }
    
    // Check if a habit is active today
    public function isActiveToday($habit_id) {
        // Get the habit details
        $this->habit->id = $habit_id;
        if (!$this->habit->getHabitById($habit_id)) {
            return false;
        }
        
        // Get current day of week (0 = Monday, 6 = Sunday)
        $currentDayOfWeek = date('N') - 1;
        
        // Get current day of month
        $currentDayOfMonth = date('j');
        
        // Check based on frequency type
        switch ($this->habit->frequency_type) {
            case 'daily':
                return true;
                
            case 'weekly':
                // Decode frequency values
                $frequency_value = json_decode($this->habit->frequency_value, true);
                // Check if current day is in selected days
                return in_array($currentDayOfWeek, $frequency_value);
                
            case 'monthly':
                // Decode frequency values
                $frequency_value = json_decode($this->habit->frequency_value, true);
                // Check if current day matches the monthly day
                return isset($frequency_value['day']) && $frequency_value['day'] == $currentDayOfMonth;
                
            case 'custom':
                // Decode frequency values
                $frequency_value = json_decode($this->habit->frequency_value, true);
                // Get habit start date
                $startDate = new DateTime($this->habit->start_date);
                $today = new DateTime();
                
                // Calculate days since start
                $daysSinceStart = $startDate->diff($today)->days;
                
                // Check if today is a multiple of the custom day frequency
                if (isset($frequency_value['days']) && $frequency_value['days'] > 0) {
                    return ($daysSinceStart % $frequency_value['days']) === 0;
                }
                return false;
                
            default:
                return false;
        }
    }

    // Check if a habit was active on a specific date
    public function wasActiveOnDate($habit_id, $date) {
        // Get the habit details
        $this->habit->id = $habit_id;
        if (!$this->habit->getHabitById($habit_id)) {
            return false;
        }
        
        // Create date object for the specified date
        $dateObj = new DateTime($date);
        
        // Check if the date is within the habit's start and end dates
        $startDate = new DateTime($this->habit->start_date);
        
        if ($dateObj < $startDate) {
            return false;
        }
        
        if (!empty($this->habit->end_date)) {
            $endDate = new DateTime($this->habit->end_date);
            if ($dateObj > $endDate) {
                return false;
            }
        }
        
        // Get day of week (0 = Monday, 6 = Sunday)
        $dayOfWeek = $dateObj->format('N') - 1;
        
        // Get day of month
        $dayOfMonth = $dateObj->format('j');
        
        // Check based on frequency type
        switch ($this->habit->frequency_type) {
            case 'daily':
                return true;
                
            case 'weekly':
                // Decode frequency values
                $frequency_value = json_decode($this->habit->frequency_value, true);
                // Check if the day is in selected days
                return in_array($dayOfWeek, $frequency_value);
                
            case 'monthly':
                // Decode frequency values
                $frequency_value = json_decode($this->habit->frequency_value, true);
                // Check if the day matches the monthly day
                return isset($frequency_value['day']) && $frequency_value['day'] == $dayOfMonth;
                
            case 'custom':
                // Decode frequency values
                $frequency_value = json_decode($this->habit->frequency_value, true);
                
                // Calculate days since start
                $daysSinceStart = $startDate->diff($dateObj)->days;
                
                // Check if the date is a multiple of the custom day frequency
                if (isset($frequency_value['days']) && $frequency_value['days'] > 0) {
                    return ($daysSinceStart % $frequency_value['days']) === 0;
                }
                return false;
                
            default:
                return false;
        }
    }

    // Check if a habit was completed on a specific date
    public function wasCompletedOnDate($habit_id, $date) {
        // Query to check if habit was completed on the given date
        $query = "SELECT COUNT(*) AS completed 
                FROM habit_completions 
                WHERE habit_id = :habit_id 
                AND DATE(completion_date) = :completion_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':habit_id', $habit_id);
        $stmt->bindParam(':completion_date', $date);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['completed'] > 0;
    }

    // Get the current streak for a user (across all habits)
    public function getCurrentStreak($user_id) {
        // Get all active habits for the user
        $habits = $this->getAllHabits($user_id);
        
        $currentStreak = [
            'habit_id' => null,
            'habit_title' => 'No active streak',
            'streak_days' => 0
        ];
        
        foreach ($habits as $habit) {
            // If this habit has a streak and it's greater than the current max
            if ($habit['streak'] > $currentStreak['streak_days']) {
                $currentStreak = [
                    'habit_id' => $habit['id'],
                    'habit_title' => $habit['title'],
                    'streak_days' => $habit['streak']
                ];
            }
        }
        
        return $currentStreak;
    }

    // Get the longest streak for a user (across all habits)
    public function getLongestStreak($user_id) {
        $query = "SELECT h.id, h.title, 
                (SELECT COUNT(*) FROM habit_completions hc WHERE hc.habit_id = h.id) as completion_count
                FROM habits h 
                WHERE h.user_id = :user_id
                ORDER BY completion_count DESC
                LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $habit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($habit) {
            $streak = $this->habit->getStreak($habit['id']);
            
            return [
                'habit_id' => $habit['id'],
                'habit_title' => $habit['title'],
                'streak_days' => $streak
            ];
        }
        
        return [
            'habit_id' => null,
            'habit_title' => 'No habits',
            'streak_days' => 0
        ];
    }

    // Check if database supports window functions
    private function checkWindowFunctionsSupport() {
        try {
            $query = "SELECT 1 FROM (SELECT 1 as col, ROW_NUMBER() OVER (ORDER BY col) as rn FROM (SELECT 1 UNION SELECT 2) tmp) t LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update an existing habit
    public function updateHabit($habit_data) {
        // First check if the habit exists and belongs to the user
        $this->habit->id = $habit_data['id'];
        if (!$this->habit->getHabitById($habit_data['id']) || $this->habit->user_id != $habit_data['user_id']) {
            return [
                'success' => false,
                'message' => 'Invalid habit or unauthorized access'
            ];
        }
        
        // Set the habit properties
        $this->habit->category_id = $habit_data['category_id'];
        $this->habit->title = $habit_data['title'];
        $this->habit->description = $habit_data['description'];
        $this->habit->xp_reward = $habit_data['xp_reward'];
        
        // Update frequency if provided
        if (isset($habit_data['frequency_type'])) {
            $this->habit->frequency_type = $habit_data['frequency_type'];
            
            // Process frequency value based on type
            $frequency_value = null;
            if ($habit_data['frequency_type'] === 'weekly' && isset($habit_data['frequency_value'])) {
                $frequency_value = json_encode($habit_data['frequency_value']);
            } elseif ($habit_data['frequency_type'] === 'monthly' && isset($habit_data['monthly_day'])) {
                $frequency_value = json_encode(['day' => intval($habit_data['monthly_day'])]);
            } elseif ($habit_data['frequency_type'] === 'custom' && isset($habit_data['custom_days'])) {
                $frequency_value = json_encode(['days' => intval($habit_data['custom_days'])]);
            } elseif ($habit_data['frequency_type'] === 'daily') {
                $frequency_value = json_encode(['daily' => true]);
            }
            
            if ($frequency_value !== null) {
                $this->habit->frequency_value = $frequency_value;
            }
        }
        
        // Update optional date ranges if provided
        if (isset($habit_data['start_date'])) {
            $this->habit->start_date = $habit_data['start_date'];
        }
        
        if (isset($habit_data['end_date'])) {
            $this->habit->end_date = $habit_data['end_date'];
        }
        
        // Update the habit
        if ($this->habit->update()) {
            return [
                'success' => true,
                'message' => 'Habit updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update habit'
            ];
        }
    }
}