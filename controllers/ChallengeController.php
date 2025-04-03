<?php
// controllers/ChallengeController.php - Challenge controller
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Challenge.php';
require_once __DIR__ . '/../utils/XPSystem.php';
require_once __DIR__ . '/../utils/NotificationHelper.php';

class ChallengeController {
    private $conn;
    private $challenge;
    private $xpSystem;
    private $notificationHelper;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->challenge = new Challenge($conn);
        $this->xpSystem = new XPSystem($conn);
        $this->notificationHelper = new NotificationHelper($conn);
    }
    
    // Get all challenges
    public function getAllChallenges($limit = null, $offset = 0) {
        return $this->challenge->getAllChallenges($limit, $offset);
    }
    
    // Get active challenges for a user
    public function getActiveChallenges($user_id) {
        return $this->challenge->getActiveChallenges($user_id);
    }
    
    // Get completed challenges for a user
    public function getCompletedChallenges($user_id) {
        return $this->challenge->getCompletedChallenges($user_id);
    }
    
    // Get challenges created by a user
    public function getUserCreatedChallenges($user_id) {
        return $this->challenge->getUserCreatedChallenges($user_id);
    }
    
    // Add a new challenge
    public function addChallenge($challenge_data) {
        // Set the challenge properties
        $this->challenge->creator_id = $challenge_data['creator_id'];
        $this->challenge->title = $challenge_data['title'];
        $this->challenge->description = $challenge_data['description'];
        $this->challenge->start_date = $challenge_data['start_date'];
        $this->challenge->end_date = $challenge_data['end_date'];
        $this->challenge->xp_reward = $challenge_data['xp_reward'] ?? 100;
        
        // Create the challenge
        if($this->challenge->create()) {
            $challenge_id = $this->challenge->id;
            
            // Add tasks if provided
            if(isset($challenge_data['tasks']) && is_array($challenge_data['tasks'])) {
                foreach($challenge_data['tasks'] as $task) {
                    $this->challenge->addTask($task['title'], $task['description'] ?? '');
                }
            }
            
            return [
                'success' => true,
                'message' => 'Challenge created successfully',
                'challenge_id' => $challenge_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to create challenge'
            ];
        }
    }
    
    // Get challenge details
    public function getChallengeDetails($challenge_id) {
        // Get the challenge
        $this->challenge->id = $challenge_id;
        
        if($this->challenge->getChallengeById($challenge_id)) {
            // Get tasks
            $tasks = $this->challenge->getTasks();
            
            // Format challenge data
            return [
                'success' => true,
                'challenge' => [
                    'id' => $this->challenge->id,
                    'creator_id' => $this->challenge->creator_id,
                    'title' => $this->challenge->title,
                    'description' => $this->challenge->description,
                    'start_date' => $this->challenge->start_date,
                    'end_date' => $this->challenge->end_date,
                    'xp_reward' => $this->challenge->xp_reward,
                    'created_at' => $this->challenge->created_at,
                    'tasks' => $tasks
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Challenge not found'
        ];
    }
    
    // Join a challenge
    public function joinChallenge($challenge_id, $user_id) {
        // First check if the challenge exists
        $this->challenge->id = $challenge_id;
        if(!$this->challenge->getChallengeById($challenge_id)) {
            return [
                'success' => false,
                'message' => 'Challenge not found'
            ];
        }
        
        // Check if already joined
        if($this->challenge->isUserJoined($user_id)) {
            return [
                'success' => false,
                'message' => 'You have already joined this challenge'
            ];
        }
        
        // Try to join
        if($this->challenge->joinChallenge($user_id)) {
            return [
                'success' => true,
                'message' => 'Successfully joined the challenge'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to join challenge'
            ];
        }
    }
    
    // Leave a challenge
    public function leaveChallenge($challenge_id, $user_id) {
        // First check if the challenge exists
        $this->challenge->id = $challenge_id;
        if(!$this->challenge->getChallengeById($challenge_id)) {
            return [
                'success' => false,
                'message' => 'Challenge not found'
            ];
        }
        
        // Check if is the creator (creator can't leave)
        if($this->challenge->creator_id == $user_id) {
            return [
                'success' => false,
                'message' => 'Challenge creator cannot leave the challenge'
            ];
        }
        
        // Check if joined
        if(!$this->challenge->isUserJoined($user_id)) {
            return [
                'success' => false,
                'message' => 'You have not joined this challenge'
            ];
        }
        
        // Try to leave
        if($this->challenge->leaveChallenge($user_id)) {
            return [
                'success' => true,
                'message' => 'Successfully left the challenge'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to leave challenge'
            ];
        }
    }
    
    // Complete a challenge task
    public function completeTask($challenge_id, $task_id, $user_id) {
        // First check if the challenge exists
        $this->challenge->id = $challenge_id;
        if(!$this->challenge->getChallengeById($challenge_id)) {
            return [
                'success' => false,
                'message' => 'Challenge not found'
            ];
        }
        
        // Check if joined
        if(!$this->challenge->isUserJoined($user_id)) {
            return [
                'success' => false,
                'message' => 'You have not joined this challenge'
            ];
        }
        
        // Try to complete the task
        if($this->challenge->completeTask($task_id, $user_id)) {
            $result = [
                'success' => true,
                'message' => 'Task completed successfully'
            ];
            
            // Check if all tasks are completed
            if($this->challenge->areAllTasksCompleted($user_id)) {
                // Award XP
                $xp_result = $this->xpSystem->awardXP($user_id, $this->challenge->xp_reward, 'challenge', 'Completed challenge: ' . $this->challenge->title);
                
                // Create challenge-specific notification only if enabled
                $notification_data = [
                    'user_id' => $user_id,
                    'type' => 'challenge',
                    'title' => 'Challenge Completed',
                    'message' => "Congratulations! You completed the challenge: {$this->challenge->title}"
                ];
                
                $this->notificationHelper->createNotificationIfEnabled($notification_data);
                
                $result['challenge_completed'] = true;
                $result['xp_awarded'] = $this->challenge->xp_reward;
                $result['level_up'] = $xp_result['level_up'] ?? false;
                $result['new_level'] = $xp_result['new_level'] ?? null;
                $result['message'] = 'Challenge completed! You earned ' . $this->challenge->xp_reward . ' XP.';
            }
            
            return $result;
        } else {
            return [
                'success' => false,
                'message' => 'Failed to complete task or task already completed'
            ];
        }
    }
    
    // Update a challenge
    public function updateChallenge($challenge_data) {
        // First check if the challenge exists and belongs to the user
        $this->challenge->id = $challenge_data['id'];
        if(!$this->challenge->getChallengeById($challenge_data['id']) || $this->challenge->creator_id != $challenge_data['creator_id']) {
            return [
                'success' => false,
                'message' => 'Invalid challenge or unauthorized access'
            ];
        }
        
        // Set the challenge properties
        $this->challenge->title = $challenge_data['title'];
        $this->challenge->description = $challenge_data['description'];
        $this->challenge->start_date = $challenge_data['start_date'];
        $this->challenge->end_date = $challenge_data['end_date'];
        $this->challenge->xp_reward = $challenge_data['xp_reward'];
        
        // Update the challenge
        if($this->challenge->update()) {
            return [
                'success' => true,
                'message' => 'Challenge updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update challenge'
            ];
        }
    }
    
    // Delete a challenge
    public function deleteChallenge($challenge_id, $user_id) {
        // First check if the challenge exists and belongs to the user
        $this->challenge->id = $challenge_id;
        if(!$this->challenge->getChallengeById($challenge_id) || $this->challenge->creator_id != $user_id) {
            return [
                'success' => false,
                'message' => 'Invalid challenge or unauthorized access'
            ];
        }
        
        // Delete the challenge
        if($this->challenge->delete()) {
            return [
                'success' => true,
                'message' => 'Challenge deleted successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete challenge'
            ];
        }
    }
    
    // Get challenge tasks
    public function getChallengeTasks($challenge_id) {
        // Get the challenge
        $this->challenge->id = $challenge_id;
        
        if($this->challenge->getChallengeById($challenge_id)) {
            return $this->challenge->getTasks();
        }
        
        return [];
    }
    
    // Get completed tasks for a user
    public function getCompletedTasks($challenge_id, $user_id) {
        // Get the challenge
        $this->challenge->id = $challenge_id;
        
        if($this->challenge->getChallengeById($challenge_id)) {
            return $this->challenge->getCompletedTasks($user_id);
        }
        
        return [];
    }
    
    // Get user progress percentage for a challenge
    public function getUserProgressPercentage($challenge_id, $user_id) {
        return $this->challenge->calculateUserProgressPercentage($challenge_id, $user_id);
    }
}