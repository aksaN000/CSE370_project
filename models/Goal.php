<?php
// models/Goal.php - Goal model
class Goal {
    private $conn;
    private $table = 'goals';
    
    // Goal properties
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $target_value;
    public $current_value;
    public $start_date;
    public $end_date;
    public $is_completed;
    public $xp_reward;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new goal
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      title = :title, 
                      description = :description,
                      target_value = :target_value,
                      current_value = :current_value,
                      start_date = :start_date,
                      end_date = :end_date,
                      xp_reward = :xp_reward";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':target_value', $this->target_value);
        
        // Set current value to 0 if not specified
        $this->current_value = $this->current_value ?? 0;
        $stmt->bindParam(':current_value', $this->current_value);
        
        $stmt->bindParam(':start_date', $this->start_date);
        $stmt->bindParam(':end_date', $this->end_date);
        
        // Set default XP reward if not specified
        $this->xp_reward = $this->xp_reward ?? 50;
        $stmt->bindParam(':xp_reward', $this->xp_reward);
        
        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly created goal
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Get goal by ID
    public function getGoalById($id) {
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
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->target_value = $row['target_value'];
            $this->current_value = $row['current_value'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->is_completed = $row['is_completed'];
            $this->xp_reward = $row['xp_reward'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get all goals for a user
    public function getAllGoals($user_id) {
        // Query
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY end_date ASC, title ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $goals = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate progress percentage
            $progress_percentage = 0;
            if($row['target_value'] > 0) {
                $progress_percentage = ($row['current_value'] / $row['target_value']) * 100;
            }
            
            // Calculate days remaining
            $today = new DateTime();
            $end_date = new DateTime($row['end_date']);
            $days_remaining = $today <= $end_date ? $today->diff($end_date)->days : 0;
            
            // Format goal data
            $goals[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'target_value' => $row['target_value'],
                'current_value' => $row['current_value'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'is_completed' => (bool)$row['is_completed'],
                'xp_reward' => $row['xp_reward'],
                'progress_percentage' => round($progress_percentage, 1),
                'days_remaining' => $days_remaining,
                'created_at' => $row['created_at']
            ];
        }
        
        return $goals;
    }
    
    // Get upcoming goals for a user with limit
    public function getUpcomingGoals($user_id, $limit = 5) {
        // Query
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND is_completed = 0 
                  AND end_date >= CURDATE() 
                  ORDER BY end_date ASC, title ASC 
                  LIMIT :limit";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $goals = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate progress percentage
            $progress_percentage = 0;
            if($row['target_value'] > 0) {
                $progress_percentage = ($row['current_value'] / $row['target_value']) * 100;
            }
            
            // Calculate days remaining
            $today = new DateTime();
            $end_date = new DateTime($row['end_date']);
            $days_remaining = $today <= $end_date ? $today->diff($end_date)->days : 0;
            
            // Format goal data
            $goals[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'target_value' => $row['target_value'],
                'current_value' => $row['current_value'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'is_completed' => (bool)$row['is_completed'],
                'xp_reward' => $row['xp_reward'],
                'progress_percentage' => round($progress_percentage, 1),
                'days_remaining' => $days_remaining
            ];
        }
        
        return $goals;
    }
    
    // Update goal progress
    public function updateProgress($progress_value) {
        // Validate progress
        $progress_value = max(0, min($progress_value, $this->target_value));
        
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET current_value = :progress_value";
        
        // Check if goal is completed
        $is_completed = $progress_value >= $this->target_value ? 1 : 0;
        if($is_completed) {
            $query .= ", is_completed = 1";
        }
        
        $query .= " WHERE id = :id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':progress_value', $progress_value);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        if($stmt->execute()) {
            // Update current value
            $this->current_value = $progress_value;
            
            // If goal is completed, update is_completed
            if($is_completed) {
                $this->is_completed = true;
            }
            
            return true;
        }
        
        return false;
    }
    
    // Delete a goal
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        return $stmt->execute();
    }
}
