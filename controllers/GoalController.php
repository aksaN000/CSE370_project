<?php
// controllers/GoalController.php - Goal controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../utils/XPSystem.php';
require_once __DIR__ . '/../utils/NotificationHelper.php';

class GoalController {
    private $conn;
    private $goal;
    private $xpSystem;
    private $notificationHelper;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->goal = new Goal($conn);
        $this->xpSystem = new XPSystem($conn);
        $this->notificationHelper = new NotificationHelper($conn);
    }
    
    // Get all goals for a user
    public function getAllGoals($user_id) {
        return $this->goal->getAllGoals($user_id);
    }
    
    // Get upcoming goals for a user
    public function getUpcomingGoals($user_id, $limit = 5) {
        return $this->goal->getUpcomingGoals($user_id, $limit);
    }
    
    // Add a new goal
    public function addGoal($goal_data) {
        // Set the goal properties
        $this->goal->user_id = $goal_data['user_id'];
        $this->goal->title = $goal_data['title'];
        $this->goal->description = $goal_data['description'];
        $this->goal->target_value = $goal_data['target_value'];
        $this->goal->start_date = $goal_data['start_date'];
        $this->goal->end_date = $goal_data['end_date'];
        $this->goal->xp_reward = $goal_data['xp_reward'] ?? 50;
        
        // Create the goal
        if($this->goal->create()) {
            // Optional: Create a notification for goal creation if needed
            $notification_data = [
                'user_id' => $this->goal->user_id,
                'type' => 'goal',
                'title' => 'New Goal Created',
                'message' => "You've created a new goal: {$this->goal->title}"
            ];
            
            $this->notificationHelper->createNotificationIfEnabled($notification_data);
            
            return [
                'success' => true,
                'message' => 'Goal created successfully',
                'goal_id' => $this->goal->id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create goal'
            ];
        }
    }

    // Get total number of goals for a user
    public function getTotalGoals($user_id) {
        $goals = $this->goal->getAllGoals($user_id);
        return count($goals);
    }

    // Get total number of completed goals for a user
    public function getCompletedGoals($user_id) {
        $goals = $this->goal->getAllGoals($user_id);
        $completed = 0;
        foreach($goals as $goal) {
            if($goal['is_completed']) {
                $completed++;
            }
        }
        return $completed;
    }
    
    // Update goal progress
    public function updateGoalProgress($goal_id, $user_id, $progress_value) {
        // First check if the goal exists and belongs to the user
        $this->goal->id = $goal_id;
        if(!$this->goal->getGoalById($goal_id) || $this->goal->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid goal or unauthorized access'
            ];
        }
        
        // Check if the goal is already completed
        if($this->goal->is_completed) {
            return [
                'success' => false,
                'message' => 'Goal is already completed'
            ];
        }
        
        // Store the previous value to check if goal is newly completed
        $was_completed = $this->goal->current_value >= $this->goal->target_value;
        
        // Update the progress
        if($this->goal->updateProgress($progress_value)) {
            $result = [
                'success' => true,
                'message' => 'Progress updated successfully',
                'current_value' => $this->goal->current_value,
                'target_value' => $this->goal->target_value,
                'is_completed' => $this->goal->is_completed
            ];
            
            // Check if goal is newly completed
            $is_newly_completed = !$was_completed && $this->goal->current_value >= $this->goal->target_value;
            
            if($is_newly_completed) {
                // Award XP to the user
                $xp_result = $this->xpSystem->awardXP($user_id, $this->goal->xp_reward, 'goal', 'Completed goal: ' . $this->goal->title);
                
                // Create goal-specific notification only if enabled
                $notification_data = [
                    'user_id' => $user_id,
                    'type' => 'goal',
                    'title' => 'Goal Completed',
                    'message' => "Congratulations! You completed the goal: {$this->goal->title}"
                ];
                
                $this->notificationHelper->createNotificationIfEnabled($notification_data);
                
                $result['xp_awarded'] = $this->goal->xp_reward;
                $result['level_up'] = $xp_result['level_up'] ?? false;
                $result['new_level'] = $xp_result['new_level'] ?? null;
                $result['message'] = 'Goal completed! XP awarded: ' . $this->goal->xp_reward;
            }
            
            return $result;
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update progress'
            ];
        }
    }
    
    // Delete a goal
    public function deleteGoal($goal_id, $user_id) {
        // First check if the goal exists and belongs to the user
        $this->goal->id = $goal_id;
        if(!$this->goal->getGoalById($goal_id) || $this->goal->user_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid goal or unauthorized access'
            ];
        }
        
        // Delete the goal
        if($this->goal->delete()) {
            // Optional: Create a notification for goal deletion if needed
            $notification_data = [
                'user_id' => $user_id,
                'type' => 'goal',
                'title' => 'Goal Deleted',
                'message' => "You've deleted the goal: {$this->goal->title}"
            ];
            
            $this->notificationHelper->createNotificationIfEnabled($notification_data);
            
            return [
                'success' => true,
                'message' => 'Goal deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete goal'
            ];
        }
    }
}