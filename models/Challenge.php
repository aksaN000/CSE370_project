<?php
// models/Challenge.php - Challenge model
class Challenge {
    private $conn;
    private $table = 'challenges';
    private $participants_table = 'challenge_participants';
    private $tasks_table = 'challenge_tasks';
    private $task_completions_table = 'challenge_task_completions';
    
    // Challenge properties
    public $id;
    public $creator_id;
    public $title;
    public $description;
    public $start_date;
    public $end_date;
    public $xp_reward;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new challenge
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET creator_id = :creator_id, 
                      title = :title, 
                      description = :description,
                      start_date = :start_date,
                      end_date = :end_date,
                      xp_reward = :xp_reward";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':creator_id', $this->creator_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':end_date', $this->end_date);
        
        // Set default XP reward if not specified
        $this->xp_reward = $this->xp_reward ?? 100;
        $stmt->bindParam(':xp_reward', $this->xp_reward);
        
        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly created challenge
            $this->id = $this->conn->lastInsertId();
            
            // Automatically join the creator to the challenge
            $this->joinChallenge($this->creator_id);
            
            return true;
        }
        
        return false;
    }
    
    // Get challenge by ID
    public function getChallengeById($id) {
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
            $this->creator_id = $row['creator_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->xp_reward = $row['xp_reward'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get all challenges
    public function getAllChallenges($limit = null, $offset = 0) {
        // Query
        $query = "SELECT c.*, 
                  u.username as creator_name, 
                  (SELECT COUNT(*) FROM " . $this->participants_table . " WHERE challenge_id = c.id) as participant_count
                  FROM " . $this->table . " c
                  JOIN users u ON c.creator_id = u.id 
                  ORDER BY c.created_at DESC";
        
        // Add limit if specified
        if($limit !== null) {
            $query .= " LIMIT :offset, :limit";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind limit parameters if needed
        if($limit !== null) {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $challenges = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate progress percentage
            $progress_percentage = $this->calculateProgressPercentage($row['id']);
            
            // Format challenge data
            $challenges[] = [
                'id' => $row['id'],
                'creator_id' => $row['creator_id'],
                'creator_name' => $row['creator_name'],
                'title' => $row['title'],
                'description' => $row['description'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'xp_reward' => $row['xp_reward'],
                'participant_count' => $row['participant_count'],
                'progress_percentage' => $progress_percentage,
                'created_at' => $row['created_at']
            ];
        }
        
        return $challenges;
    }
    
    // Get active challenges for a user
    public function getActiveChallenges($user_id) {
        // Query
        $query = "SELECT c.*, 
                  u.username as creator_name, 
                  (SELECT COUNT(*) FROM " . $this->participants_table . " WHERE challenge_id = c.id) as participant_count,
                  cp.is_completed
                  FROM " . $this->table . " c
                  JOIN users u ON c.creator_id = u.id 
                  JOIN " . $this->participants_table . " cp ON c.id = cp.challenge_id 
                  WHERE cp.user_id = :user_id
                  AND c.end_date >= CURDATE()
                  AND cp.is_completed = 0
                  ORDER BY c.end_date ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $challenges = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate progress percentage
            $progress_percentage = $this->calculateUserProgressPercentage($row['id'], $user_id);
            
            // Format challenge data
            $challenges[] = [
                'id' => $row['id'],
                'creator_id' => $row['creator_id'],
                'creator_name' => $row['creator_name'],
                'title' => $row['title'],
                'description' => $row['description'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'xp_reward' => $row['xp_reward'],
                'participant_count' => $row['participant_count'],
                'is_completed' => (bool)$row['is_completed'],
                'progress_percentage' => $progress_percentage
            ];
        }
        
        return $challenges;
    }
    
    // Get completed challenges for a user
    public function getCompletedChallenges($user_id) {
        // Query
        $query = "SELECT c.*, 
                  u.username as creator_name, 
                  (SELECT COUNT(*) FROM " . $this->participants_table . " WHERE challenge_id = c.id) as participant_count,
                  cp.is_completed,
                  cp.completion_date
                  FROM " . $this->table . " c
                  JOIN users u ON c.creator_id = u.id 
                  JOIN " . $this->participants_table . " cp ON c.id = cp.challenge_id 
                  WHERE cp.user_id = :user_id
                  AND cp.is_completed = 1
                  ORDER BY cp.completion_date DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $challenges = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Format challenge data
            $challenges[] = [
                'id' => $row['id'],
                'creator_id' => $row['creator_id'],
                'creator_name' => $row['creator_name'],
                'title' => $row['title'],
                'description' => $row['description'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'xp_reward' => $row['xp_reward'],
                'participant_count' => $row['participant_count'],
                'is_completed' => (bool)$row['is_completed'],
                'completion_date' => $row['completion_date']
            ];
        }
        
        return $challenges;
    }
    
    // Get challenges created by a user
    public function getUserCreatedChallenges($user_id) {
        // Query
        $query = "SELECT c.*, 
                  (SELECT COUNT(*) FROM " . $this->participants_table . " WHERE challenge_id = c.id) as participant_count
                  FROM " . $this->table . " c
                  WHERE c.creator_id = :user_id
                  ORDER BY c.created_at DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $challenges = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate progress percentage
            $progress_percentage = $this->calculateProgressPercentage($row['id']);
            
            // Format challenge data
            $challenges[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'xp_reward' => $row['xp_reward'],
                'participant_count' => $row['participant_count'],
                'progress_percentage' => $progress_percentage,
                'created_at' => $row['created_at']
            ];
        }
        
        return $challenges;
    }
    
    // Join a challenge
    public function joinChallenge($user_id) {
        // Check if already joined
        if($this->isUserJoined($user_id)) {
            return false;
        }
        
        // Create query
        $query = "INSERT INTO " . $this->participants_table . " (challenge_id, user_id) 
                  VALUES (:challenge_id, :user_id)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Check if user has joined a challenge
    public function isUserJoined($user_id) {
        // Query
        $query = "SELECT * FROM " . $this->participants_table . " 
                  WHERE challenge_id = :challenge_id AND user_id = :user_id 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return result
        return $stmt->rowCount() > 0;
    }
    
    // Leave a challenge
    public function leaveChallenge($user_id) {
        // Check if is the creator (creator can't leave)
        if($this->creator_id == $user_id) {
            return false;
        }
        
        // Create query
        $query = "DELETE FROM " . $this->participants_table . " 
                  WHERE challenge_id = :challenge_id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Add a task to a challenge
    public function addTask($title, $description) {
        // Sanitize input
        $title = htmlspecialchars(strip_tags($title));
        $description = htmlspecialchars(strip_tags($description));
        
        // Create query
        $query = "INSERT INTO " . $this->tasks_table . " (challenge_id, title, description) 
                  VALUES (:challenge_id, :title, :description)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Get tasks for a challenge
    public function getTasks() {
        // Query
        $query = "SELECT * FROM " . $this->tasks_table . " 
                  WHERE challenge_id = :challenge_id 
                  ORDER BY id ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':challenge_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $tasks = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'created_at' => $row['created_at']
            ];
        }
        
        return $tasks;
    }
    
    // Complete a task for a user
    public function completeTask($task_id, $user_id) {
        // Check if task belongs to this challenge
        $query = "SELECT * FROM " . $this->tasks_table . " 
                  WHERE id = :task_id AND challenge_id = :challenge_id 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            return false;
        }
        
        // Check if already completed
        $query = "SELECT * FROM " . $this->task_completions_table . " 
                  WHERE task_id = :task_id AND user_id = :user_id 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return false;
        }
        
        // Create query
        $query = "INSERT INTO " . $this->task_completions_table . " (task_id, user_id) 
                  VALUES (:task_id, :user_id)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $result = $stmt->execute();
        
        // Check if all tasks are completed
        if($result && $this->areAllTasksCompleted($user_id)) {
            $this->markChallengeCompleted($user_id);
        }
        
        return $result;
    }
    
    // Check if all tasks are completed by a user
    public function areAllTasksCompleted($user_id) {
        // Get total tasks count
        $query = "SELECT COUNT(*) as total FROM " . $this->tasks_table . " 
                  WHERE challenge_id = :challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->execute();
        
        $total_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get completed tasks count
        $query = "SELECT COUNT(*) as completed FROM " . $this->task_completions_table . " tc
                  JOIN " . $this->tasks_table . " t ON tc.task_id = t.id
                  WHERE t.challenge_id = :challenge_id AND tc.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $completed_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        // Return true if all tasks are completed
        return $total_tasks > 0 && $completed_tasks >= $total_tasks;
    }
    
    // Mark a challenge as completed for a user
    public function markChallengeCompleted($user_id) {
        // Create query
        $query = "UPDATE " . $this->participants_table . " 
                  SET is_completed = 1, completion_date = NOW() 
                  WHERE challenge_id = :challenge_id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Get completed tasks for a user
    public function getCompletedTasks($user_id) {
        // Query
        $query = "SELECT t.id, t.title, t.description, tc.completion_date
                  FROM " . $this->tasks_table . " t
                  JOIN " . $this->task_completions_table . " tc ON t.id = tc.task_id
                  WHERE t.challenge_id = :challenge_id AND tc.user_id = :user_id
                  ORDER BY tc.completion_date ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $tasks = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'completion_date' => $row['completion_date']
            ];
        }
        
        return $tasks;
    }
    
    // Calculate progress percentage for a challenge
    private function calculateProgressPercentage($challenge_id) {
        // Get total tasks count
        $query = "SELECT COUNT(*) as total FROM " . $this->tasks_table . " 
                  WHERE challenge_id = :challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->execute();
        
        $total_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // If no tasks, percentage is either 0% or based on time passed
        if($total_tasks == 0) {
            // Calculate percentage based on time passed
            $this->id = $challenge_id;
            $this->getChallengeById($challenge_id);
            
            $start_date = new DateTime($this->start_date);
            $end_date = new DateTime($this->end_date);
            $today = new DateTime();
            
            // If challenge hasn't started yet
            if($today < $start_date) {
                return 0;
            }
            
            // If challenge has ended
            if($today > $end_date) {
                return 100;
            }
            
            // Calculate percentage based on time passed
            $total_days = $start_date->diff($end_date)->days + 1; // +1 to include both start and end days
            $days_passed = $start_date->diff($today)->days + 1;
            
            return min(100, max(0, round(($days_passed / $total_days) * 100)));
        }
        
        // Get all participants
        $query = "SELECT user_id FROM " . $this->participants_table . " 
                  WHERE challenge_id = :challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->execute();
        
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if(empty($participants)) {
            return 0;
        }
        
        // Calculate average progress across all participants
        $total_percentage = 0;
        foreach($participants as $participant) {
            $user_id = $participant['user_id'];
            $user_percentage = $this->calculateUserProgressPercentage($challenge_id, $user_id);
            $total_percentage += $user_percentage;
        }
        
        return round($total_percentage / count($participants));
    }
    
    // Calculate progress percentage for a specific user
    public function calculateUserProgressPercentage($challenge_id, $user_id) {
        // Get total tasks count
        $query = "SELECT COUNT(*) as total FROM " . $this->tasks_table . " 
                  WHERE challenge_id = :challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->execute();
        
        $total_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // If no tasks, percentage is either 0% or based on time passed
        if($total_tasks == 0) {
            // Calculate percentage based on time passed
            $this->id = $challenge_id;
            $this->getChallengeById($challenge_id);
            
            $start_date = new DateTime($this->start_date);
            $end_date = new DateTime($this->end_date);
            $today = new DateTime();
            
            // If challenge hasn't started yet
            if($today < $start_date) {
                return 0;
            }
            
            // If challenge has ended
            if($today > $end_date) {
                return 100;
            }
            
            // Calculate percentage based on time passed
            $total_days = $start_date->diff($end_date)->days + 1; // +1 to include both start and end days
            $days_passed = $start_date->diff($today)->days + 1;
            
            return min(100, max(0, round(($days_passed / $total_days) * 100)));
        }
        
        // Get completed tasks count
        $query = "SELECT COUNT(*) as completed FROM " . $this->task_completions_table . " tc
                  JOIN " . $this->tasks_table . " t ON tc.task_id = t.id
                  WHERE t.challenge_id = :challenge_id AND tc.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $completed_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];
        
        return $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
    }
    
    // Update a challenge
    public function update() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET title = :title, 
                      description = :description,
                      start_date = :start_date,
                      end_date = :end_date,
                      xp_reward = :xp_reward
                  WHERE id = :id AND creator_id = :creator_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':end_date', $this->end_date);
        $stmt->bindParam(':xp_reward', $this->xp_reward);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':creator_id', $this->creator_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Delete a challenge
    public function delete() {
        // First delete all task completions
        $query = "DELETE tc FROM " . $this->task_completions_table . " tc
                  JOIN " . $this->tasks_table . " t ON tc.task_id = t.id
                  WHERE t.challenge_id = :challenge_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->execute();
        
        // Then delete all tasks
        $query = "DELETE FROM " . $this->tasks_table . " WHERE challenge_id = :challenge_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->execute();
        
        // Then delete all participants
        $query = "DELETE FROM " . $this->participants_table . " WHERE challenge_id = :challenge_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $this->id);
        $stmt->execute();
        
        // Finally delete the challenge
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND creator_id = :creator_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':creator_id', $this->creator_id);
        
        return $stmt->execute();
    }
}
