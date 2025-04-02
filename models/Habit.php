
<?php
// models/Habit.php - Habit model
class Habit {
    private $conn;
    private $table = 'habits';
    private $completion_table = 'habit_completions';
    private $categories_table = 'categories';
    
    // Habit properties
    public $id;
    public $user_id;
    public $category_id;
    public $title;
    public $description;
    public $frequency_type;
    public $frequency_value;
    public $start_date;
    public $end_date;
    public $xp_reward;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    

    // Get the streak for a habit
public function getStreak($habit_id) {
    return $this->calculateStreak($habit_id);
}
    // Create new habit
    public function create() {
        // Sanitize input
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        // Encode frequency_value as JSON if it's an array
        if(is_array($this->frequency_value)) {
            $this->frequency_value = json_encode($this->frequency_value);
        }
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET user_id = :user_id, 
                      category_id = :category_id,
                      title = :title, 
                      description = :description,
                      frequency_type = :frequency_type,
                      frequency_value = :frequency_value,
                      start_date = :start_date,
                      end_date = :end_date,
                      xp_reward = :xp_reward";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':frequency_type', $this->frequency_type);
        $stmt->bindParam(':frequency_value', $this->frequency_value);
        $stmt->bindParam(':start_date', $this->start_date);
        
        // Bind end_date or set it to NULL
        if(empty($this->end_date)) {
            $stmt->bindValue(':end_date', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':end_date', $this->end_date);
        }
        
        // Set default XP reward if not specified
        $this->xp_reward = $this->xp_reward ?? 10;
        $stmt->bindParam(':xp_reward', $this->xp_reward);
        
        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly created habit
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    // Update habit
    public function update() {
        // Create base query
        $query = "UPDATE " . $this->table . " 
                SET category_id = :category_id,
                    title = :title, 
                    description = :description,
                    xp_reward = :xp_reward";
        
        // Add frequency fields if they're set
        if (isset($this->frequency_type)) {
            $query .= ", frequency_type = :frequency_type";
        }
        
        if (isset($this->frequency_value)) {
            $query .= ", frequency_value = :frequency_value";
        }
        
        // Complete the query
        $query .= " WHERE id = :id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':xp_reward', $this->xp_reward);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Bind frequency parameters if set
        if (isset($this->frequency_type)) {
            $stmt->bindParam(':frequency_type', $this->frequency_type);
        }
        
        if (isset($this->frequency_value)) {
            $stmt->bindParam(':frequency_value', $this->frequency_value);
        }
        
        // Execute query
        return $stmt->execute();
    }
    
    // Get habit by ID
    public function getHabitById($id) {
        // Query
        $query = "SELECT h.*, c.name as category_name, c.color as category_color 
                  FROM " . $this->table . " h
                  LEFT JOIN " . $this->categories_table . " c ON h.category_id = c.id 
                  WHERE h.id = :id 
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
            $this->category_id = $row['category_id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->frequency_type = $row['frequency_type'];
            $this->frequency_value = $row['frequency_value'];
            $this->start_date = $row['start_date'];
            $this->end_date = $row['end_date'];
            $this->xp_reward = $row['xp_reward'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Get all habits for a user
    public function getAllHabits($user_id) {
        // Query
        $query = "SELECT h.*, c.name as category_name, c.color as category_color, 
                  (SELECT COUNT(*) FROM " . $this->completion_table . " 
                   WHERE habit_id = h.id) as completion_count,
                  (SELECT MAX(completion_date) FROM " . $this->completion_table . " 
                   WHERE habit_id = h.id) as last_completion_date,
                  (SELECT COUNT(*) FROM " . $this->completion_table . " 
                   WHERE habit_id = h.id AND completion_date = CURDATE()) as is_completed_today
                  FROM " . $this->table . " h
                  LEFT JOIN " . $this->categories_table . " c ON h.category_id = c.id 
                  WHERE h.user_id = :user_id 
                  ORDER BY h.title ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':user_id', $user_id);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $habits = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate streak
            $streak = $this->calculateStreak($row['id']);
            
            // Format the habit data
            $habits[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'frequency_type' => $row['frequency_type'],
                'frequency_value' => $row['frequency_value'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'xp_reward' => $row['xp_reward'],
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'category_color' => $row['category_color'],
                'completion_count' => $row['completion_count'],
                'last_completion_date' => $row['last_completion_date'],
                'is_completed_today' => (bool)$row['is_completed_today'],
                'streak' => $streak,
                'created_at' => $row['created_at']
            ];
        }
        
        return $habits;
    }
    
    // Get active habits for today
    public function getActiveHabitsForToday($user_id) {
        // Get current day of week (0 = Monday, 6 = Sunday)
        $currentDayOfWeek = date('N') - 1;
        
        // Query for active habits based on frequency
        $query = "SELECT h.*, c.name as category_name, c.color as category_color,
                  (SELECT COUNT(*) FROM " . $this->completion_table . " 
                   WHERE habit_id = h.id AND completion_date = CURDATE()) as is_completed
                  FROM " . $this->table . " h
                  LEFT JOIN " . $this->categories_table . " c ON h.category_id = c.id 
                  WHERE h.user_id = :user_id 
                  AND (h.end_date IS NULL OR h.end_date >= CURDATE())
                  AND h.start_date <= CURDATE()
                  AND (
                      (h.frequency_type = 'daily') OR
                      (h.frequency_type = 'weekly' AND JSON_CONTAINS(h.frequency_value, :day_of_week)) OR
                      (h.frequency_type = 'monthly' AND DAY(CURDATE()) = JSON_EXTRACT(h.frequency_value, '$.day'))
                  )
                  ORDER BY h.title ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $dayOfWeekStr = '"' . $currentDayOfWeek . '"';
        $stmt->bindParam(':day_of_week', $dayOfWeekStr);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $habits = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Calculate streak
            $streak = $this->calculateStreak($row['id']);
            
            // Format the habit data
            $habits[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'frequency_type' => $row['frequency_type'],
                'frequency_value' => $row['frequency_value'],
                'xp_reward' => $row['xp_reward'],
                'category_name' => $row['category_name'],
                'category_color' => $row['category_color'],
                'is_completed' => (bool)$row['is_completed'],
                'streak' => $streak
            ];
        }
        
        return $habits;
    }
    
    // Get all categories
    public function getAllCategories() {
        // Query
        $query = "SELECT * FROM " . $this->categories_table . " ORDER BY name ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        // Return results
        $categories = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'color' => $row['color'],
                'icon' => $row['icon']
            ];
        }
        
        return $categories;
    }
    
    // Check if habit is completed today
    public function isCompletedToday() {
        // Query
        $query = "SELECT COUNT(*) as count FROM " . $this->completion_table . " 
                  WHERE habit_id = :habit_id AND completion_date = CURDATE()";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':habit_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'] > 0;
    }
    
    // Mark habit as complete for today
    public function markAsComplete() {
        // Create query
        $query = "INSERT INTO " . $this->completion_table . " (habit_id, user_id, completion_date)
                  VALUES (:habit_id, :user_id, CURDATE())";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':habit_id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Calculate streak for a habit
    private function calculateStreak($habit_id) {
        // Get all completions ordered by date
        $query = "SELECT completion_date FROM " . $this->completion_table . " 
                  WHERE habit_id = :habit_id 
                  ORDER BY completion_date DESC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':habit_id', $habit_id);
        
        // Execute query
        $stmt->execute();
        
        // Get all completion dates
        $completions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If no completions, return 0
        if(empty($completions)) {
            return 0;
        }
        
        // Check if completed today
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Ensure we have a consistent way to compare dates
        $normalizedCompletions = array_map(function($date) {
            return date('Y-m-d', strtotime($date));
        }, $completions);
        
        // If not completed today or yesterday, streak is 0
        if(!in_array($today, $normalizedCompletions) && !in_array($yesterday, $normalizedCompletions)) {
            return 0;
        }
        
        // Calculate streak
        $streak = 1;
        $currentDate = new DateTime($normalizedCompletions[0]);
        
        // Loop through completions starting from the second one
        for($i = 1; $i < count($normalizedCompletions); $i++) {
            $previousDate = new DateTime($normalizedCompletions[$i]);
            $diff = $currentDate->diff($previousDate);
            
            // If the difference is 1 day, increment streak
            if($diff->days == 1) {
                $streak++;
                $currentDate = $previousDate;
            } else if($diff->days > 1) {
                // Break if there's a gap
                break;
            }
        }
        
        return $streak;
    }
    
    // Delete a habit
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
    
    // Get statistics for a habit
    public function getStatistics() {
        // Get all completions
        $query = "SELECT completion_date FROM " . $this->completion_table . " 
                  WHERE habit_id = :habit_id 
                  ORDER BY completion_date ASC";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':habit_id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Get all completion dates
        $completions = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $completions[] = $row['completion_date'];
        }
        
        // Calculate statistics
        $total_completions = count($completions);
        $streak = $this->calculateStreak($this->id);
        
        // Calculate consistency
        $start_date = new DateTime($this->start_date);
        $today = new DateTime();
        $days_since_start = $start_date->diff($today)->days + 1;
        $consistency_percentage = ($days_since_start > 0) ? ($total_completions / $days_since_start) * 100 : 0;
        
        // Calculate monthly statistics
        $monthly_stats = [];
        foreach($completions as $date) {
            $month = date('Y-m', strtotime($date));
            if(!isset($monthly_stats[$month])) {
                $monthly_stats[$month] = 0;
            }
            $monthly_stats[$month]++;
        }
        
        // Format stats for easy use
        return [
            'total_completions' => $total_completions,
            'current_streak' => $streak,
            'consistency_percentage' => round($consistency_percentage, 2),
            'monthly_stats' => $monthly_stats,
            'completions' => $completions
        ];
    }
}
