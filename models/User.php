<?php
// models/User.php - User model
class User {
    private $conn;
    private $table = 'users';
    
    // User properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $current_xp;
    public $level;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Register user
    public function register() {
        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Check if email already exists
        if($this->emailExists()) {
            return false;
        }
        
        // Check if username already exists
        if($this->usernameExists()) {
            return false;
        }
        
        // Hash password
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET username = :username, 
                      email = :email, 
                      password = :password";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        
        // Execute query
        if($stmt->execute()) {
            // Get the ID of the newly registered user
            return $this->conn->lastInsertId();
        }
    
        return false;
    }
    
    // Login user
    public function login() {
        // Sanitize email
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Query to read user data
        $query = "SELECT id, username, email, password, current_xp, level 
                  FROM " . $this->table . " 
                  WHERE email = :email 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':email', $this->email);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If user exists
        if($num > 0) {
            // Get record details
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Assign values to object properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->current_xp = $row['current_xp'];
            $this->level = $row['level'];
            
            // Verify password
            if(password_verify($this->password, $row['password'])) {
                return true;
            }
        }
        
        return false;
    }
    
    // Check if email exists
    private function emailExists() {
        // Query
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':email', $this->email);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If email exists
        if($num > 0) {
            return true;
        }
        
        return false;
    }
    
    // Check if username exists
    private function usernameExists() {
        // Query
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':username', $this->username);
        
        // Execute query
        $stmt->execute();
        
        // Get row count
        $num = $stmt->rowCount();
        
        // If username exists
        if($num > 0) {
            return true;
        }
        
        return false;
    }
    
    // Get user by ID
    public function getUserById($id) {
        // Query
        $query = "SELECT id, username, email, password, current_xp, level, created_at 
                FROM " . $this->table . " 
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
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password']; // Added this line
            $this->current_xp = $row['current_xp'];
            $this->level = $row['level'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }
    
    // Update user XP
    public function updateXP($xp_amount) {
        // Query
        $query = "UPDATE " . $this->table . " 
                  SET current_xp = current_xp + :xp_amount 
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':xp_amount', $xp_amount);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            // Update the current_xp property
            $this->current_xp += $xp_amount;
            
            // Check if level up is needed
            $this->checkLevelUp();
            
            return true;
        }
        
        return false;
    }
    
    // Check if user should level up
    private function checkLevelUp() {
        // Query to get level requirements
        $query = "SELECT level_number, xp_required 
                  FROM levels 
                  WHERE xp_required <= :current_xp 
                  ORDER BY level_number DESC 
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':current_xp', $this->current_xp);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row && $row['level_number'] > $this->level) {
            // Update user level
            $update_query = "UPDATE " . $this->table . " 
                            SET level = :new_level 
                            WHERE id = :id";
            
            // Prepare statement
            $update_stmt = $this->conn->prepare($update_query);
            
            // Bind parameters
            $update_stmt->bindParam(':new_level', $row['level_number']);
            $update_stmt->bindParam(':id', $this->id);
            
            // Execute query
            if($update_stmt->execute()) {
                // Update the level property
                $this->level = $row['level_number'];
                
                // Add a level-up achievement
                $this->addAchievement($row['level_number']);
                
                // Create a level-up notification
                $this->createLevelUpNotification($row['level_number']);
                
                return true;
            }
        }
        
        return false;
    }
    
    // Add an achievement
    private function addAchievement($level_number) {
        // Query to get level ID
        $query = "SELECT id FROM levels WHERE level_number = :level_number LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':level_number', $level_number);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Insert achievement
            $insert_query = "INSERT INTO user_achievements(user_id, level_id) 
                            VALUES(:user_id, :level_id)";
            
            // Prepare statement
            $insert_stmt = $this->conn->prepare($insert_query);
            
            // Bind parameters
            $insert_stmt->bindParam(':user_id', $this->id);
            $insert_stmt->bindParam(':level_id', $row['id']);
            
            // Execute query
            $insert_stmt->execute();
        }
    }
    
    // Update user profile
    public function updateProfile($username, $email, $profile_picture = null) {
        // Sanitize input
        $this->username = htmlspecialchars(strip_tags($username));
        $this->email = htmlspecialchars(strip_tags($email));
        
        // Check if username or email already exists (for another user)
        if($this->usernameExistsForOthers() || $this->emailExistsForOthers()) {
            return false;
        }
        
        // Create base query
        $query = "UPDATE " . $this->table . " 
                SET username = :username, 
                    email = :email,
                    updated_at = NOW()";
        
        // Add profile picture to query if provided
        if($profile_picture !== null) {
            $query .= ", profile_picture = :profile_picture";
            $this->profile_picture = $profile_picture;
        }
        
        $query .= " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id);
        
        // Bind profile picture if present
        if($profile_picture !== null) {
            $stmt->bindParam(':profile_picture', $this->profile_picture);
        }
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update password
    public function updatePassword($current_password, $new_password) {
        // Verify current password
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!password_verify($current_password, $row['password'])) {
            return false;
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET password = :password,
                      updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if username exists for other users
    private function usernameExistsForOthers() {
        // Query
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE username = :username AND id != :id LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Return result
        return $stmt->rowCount() > 0;
    }
    
    // Check if email exists for other users
    private function emailExistsForOthers() {
        // Query
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE email = :email AND id != :id LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Return result
        return $stmt->rowCount() > 0;
    }

    // Create a level-up notification with respect to notification settings
    private function createLevelUpNotification($level_number) {
        // Check if level-up notifications are enabled
        $query = "SELECT level_up_notifications FROM user_settings WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->id);
        $stmt->execute();
        
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($settings && $settings['level_up_notifications'] != 1) {
            // Level-up notifications are disabled
            return;
        }
        
        // Query to get level details
        $query = "SELECT title, badge_name FROM levels WHERE level_number = :level_number LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':level_number', $level_number);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Create notification
            $insert_query = "INSERT INTO notifications(user_id, type, title, message) 
                            VALUES(:user_id, 'level', 'Level Up!', :message)";
            
            // Create message
            $message = "Congratulations! You've reached Level " . $level_number . " - " . $row['title'] . 
                      " and earned the " . $row['badge_name'] . " badge!";
            
            // Bind parameters
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $this->id);
            $insert_stmt->bindParam(':message', $message);
            
            // Execute query
            $insert_stmt->execute();
        }
    }
}