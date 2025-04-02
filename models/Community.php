<?php
// models/Community.php - Community model for friends and social interactions

class Community {
    private $conn;
    private $friends_table = 'user_friends';
    private $friend_requests_table = 'friend_requests';
    private $leaderboard_table = 'leaderboard_entries';
    
    // Community properties
    public $id;
    public $user_id;
    public $friend_id;
    public $status;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Send friend request
    public function sendFriendRequest($sender_id, $recipient_id) {
        // Check if request already exists
        if($this->requestExists($sender_id, $recipient_id)) {
            return false;
        }
        
        // Check if they are already friends
        if($this->areFriends($sender_id, $recipient_id)) {
            return false;
        }
        
        // Create query
        $query = "INSERT INTO " . $this->friend_requests_table . " 
                  SET sender_id = :sender_id, 
                      recipient_id = :recipient_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        
        // Execute query
        if($stmt->execute()) {
            // Create notification for recipient
            $this->createFriendRequestNotification($sender_id, $recipient_id);
            return true;
        }
        
        return false;
    }
    
    // Check if request already exists
    private function requestExists($sender_id, $recipient_id) {
        // Query
        $query = "SELECT id FROM " . $this->friend_requests_table . " 
                  WHERE (sender_id = :sender_id AND recipient_id = :recipient_id)
                  OR (sender_id = :recipient_id AND recipient_id = :sender_id)
                  LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        
        // Execute query
        $stmt->execute();
        
        // Return result
        return $stmt->rowCount() > 0;
    }
    
    // Create friend request notification
    private function createFriendRequestNotification($sender_id, $recipient_id) {
        // Get sender username
        $query = "SELECT username FROM users WHERE id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $sender_id);
        $stmt->execute();
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($sender) {
            // Create notification
            $query = "INSERT INTO notifications (user_id, type, title, message, link_data) 
                      VALUES (:user_id, 'friend', 'Friend Request', :message, :link_data)";
            
            $message = $sender['username'] . " sent you a friend request";
            $link_data = json_encode(['type' => 'friend_request', 'id' => $sender_id]);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $recipient_id);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':link_data', $link_data);
            $stmt->execute();
        }
    }
    
    // Accept friend request
    public function acceptFriendRequest($request_id, $recipient_id) {
        // Verify the request exists and belongs to recipient
        $query = "SELECT * FROM " . $this->friend_requests_table . "
                  WHERE id = :request_id AND recipient_id = :recipient_id
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            return false;
        }
        
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        $sender_id = $request['sender_id'];
        
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Add as friends
            $query = "INSERT INTO " . $this->friends_table . " 
                      (user_id, friend_id) VALUES 
                      (:user_id, :friend_id),
                      (:friend_id, :user_id)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $recipient_id);
            $stmt->bindParam(':friend_id', $sender_id);
            $stmt->execute();
            
            // Delete the request
            $query = "DELETE FROM " . $this->friend_requests_table . " WHERE id = :request_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();
            
            // Create notification for sender
            $this->createFriendAcceptedNotification($sender_id, $recipient_id);
            
            // Commit transaction
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            // Rollback changes
            $this->conn->rollBack();
            return false;
        }
    }
    
    // Create friend accepted notification
    private function createFriendAcceptedNotification($sender_id, $recipient_id) {
        // Get recipient username
        $query = "SELECT username FROM users WHERE id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $recipient_id);
        $stmt->execute();
        $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($recipient) {
            // Create notification
            $query = "INSERT INTO notifications (user_id, type, title, message, link_data) 
                      VALUES (:user_id, 'friend', 'Friend Request Accepted', :message, :link_data)";
            
            $message = $recipient['username'] . " accepted your friend request";
            $link_data = json_encode(['type' => 'profile', 'id' => $recipient_id]);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $sender_id);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':link_data', $link_data);
            $stmt->execute();
        }
    }
    
    // Reject friend request
    public function rejectFriendRequest($request_id, $recipient_id) {
        // Verify the request exists and belongs to recipient
        $query = "SELECT * FROM " . $this->friend_requests_table . "
                  WHERE id = :request_id AND recipient_id = :recipient_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            return false;
        }
        
        // Delete the request
        $query = "DELETE FROM " . $this->friend_requests_table . " WHERE id = :request_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        return $stmt->execute();
    }
    
    // Get incoming friend requests
    public function getIncomingFriendRequests($user_id) {
        $query = "SELECT fr.*, u.username as sender_name 
                  FROM " . $this->friend_requests_table . " fr
                  JOIN users u ON fr.sender_id = u.id
                  WHERE fr.recipient_id = :user_id
                  ORDER BY fr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get outgoing friend requests
    public function getOutgoingFriendRequests($user_id) {
        $query = "SELECT fr.*, u.username as recipient_name 
                  FROM " . $this->friend_requests_table . " fr
                  JOIN users u ON fr.recipient_id = u.id
                  WHERE fr.sender_id = :user_id
                  ORDER BY fr.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user's friends
    public function getFriends($user_id) {
        $query = "SELECT f.*, u.username, u.level, u.current_xp, 
                         (SELECT COUNT(*) FROM habits WHERE user_id = u.id) as total_habits,
                         (SELECT COUNT(*) FROM goals WHERE user_id = u.id) as total_goals
                  FROM " . $this->friends_table . " f
                  JOIN users u ON f.friend_id = u.id
                  WHERE f.user_id = :user_id
                  ORDER BY u.username ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check if users are friends
    public function areFriends($user_id, $friend_id) {
        $query = "SELECT id FROM " . $this->friends_table . "
                  WHERE user_id = :user_id AND friend_id = :friend_id
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Remove friend
    public function removeFriend($user_id, $friend_id) {
        $query = "DELETE FROM " . $this->friends_table . "
                  WHERE (user_id = :user_id AND friend_id = :friend_id)
                  OR (user_id = :friend_id AND friend_id = :user_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        
        return $stmt->execute();
    }
    
    // Search for users
    public function searchUsers($search_term, $current_user_id) {
        $query = "SELECT id, username, level, 
                    (SELECT COUNT(*) FROM " . $this->friends_table . " 
                    WHERE user_id = :current_user_id AND friend_id = users.id) as is_friend
                  FROM users 
                  WHERE id != :current_user_id 
                  AND username LIKE :search_term
                  ORDER BY username ASC
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $search_term = '%' . $search_term . '%';
        $stmt->bindParam(':search_term', $search_term);
        $stmt->bindParam(':current_user_id', $current_user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user's profile
    public function getUserProfile($user_id) {
        $query = "SELECT u.id, u.username, u.level, u.current_xp, u.created_at,
                  (SELECT COUNT(*) FROM habits WHERE user_id = u.id) as total_habits,
                  (SELECT COUNT(*) FROM goals WHERE user_id = u.id) as total_goals,
                  (SELECT COUNT(*) FROM challenges WHERE creator_id = u.id) as total_challenges,
                  (SELECT COUNT(*) FROM habit_completions WHERE user_id = u.id) as total_completions,
                  (SELECT COUNT(*) FROM " . $this->friends_table . " WHERE user_id = u.id) as friend_count,
                  us.profile_visibility, us.public_profile, us.show_stats, us.show_habits, us.show_goals,
                  us.show_challenges, us.show_achievements
                  FROM users u
                  LEFT JOIN user_settings us ON u.id = us.user_id
                  WHERE u.id = :user_id
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get leaderboard
    public function getLeaderboard($category = 'xp', $limit = 10) {
        $query = "";
        
        switch($category) {
            case 'xp':
                $query = "SELECT u.id, u.username, u.level, u.current_xp as score,
                          (SELECT level_number FROM levels WHERE xp_required <= u.current_xp ORDER BY level_number DESC LIMIT 1) as level_number,
                          (SELECT COUNT(*) FROM habits WHERE user_id = u.id) as total_habits
                          FROM users u
                          JOIN user_settings us ON u.id = us.user_id
                          WHERE us.show_in_leaderboards = 1
                          ORDER BY u.current_xp DESC
                          LIMIT :limit";
                break;
                
            case 'habits':
                $query = "SELECT u.id, u.username, u.level, COUNT(h.id) as score
                          FROM users u
                          JOIN habits h ON u.id = h.user_id
                          JOIN user_settings us ON u.id = us.user_id
                          WHERE us.show_in_leaderboards = 1
                          GROUP BY u.id
                          ORDER BY score DESC
                          LIMIT :limit";
                break;
                
            case 'completions':
                $query = "SELECT u.id, u.username, u.level, COUNT(hc.id) as score
                          FROM users u
                          JOIN habit_completions hc ON u.id = hc.user_id
                          JOIN user_settings us ON u.id = us.user_id
                          WHERE us.show_in_leaderboards = 1
                          GROUP BY u.id
                          ORDER BY score DESC
                          LIMIT :limit";
                break;
                
            case 'goals':
                $query = "SELECT u.id, u.username, u.level, COUNT(g.id) as score
                          FROM users u
                          JOIN goals g ON u.id = g.user_id
                          JOIN user_settings us ON u.id = us.user_id
                          WHERE us.show_in_leaderboards = 1 AND g.is_completed = 1
                          GROUP BY u.id
                          ORDER BY score DESC
                          LIMIT :limit";
                break;
        }
        
        if(empty($query)) {
            return [];
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Invite friend to challenge
    public function inviteToChallenge($challenge_id, $sender_id, $recipient_id) {
        // First check if challenge exists
        $query = "SELECT * FROM challenges WHERE id = :challenge_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->execute();
        
        if($stmt->rowCount() == 0) {
            return false;
        }
        
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if recipient is already participating
        $query = "SELECT * FROM challenge_participants 
                  WHERE challenge_id = :challenge_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':challenge_id', $challenge_id);
        $stmt->bindParam(':user_id', $recipient_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return false;
        }
        
        // Create challenge invitation notification
        $query = "SELECT username FROM users WHERE id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $sender_id);
        $stmt->execute();
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($sender) {
            // Create notification
            $query = "INSERT INTO notifications (user_id, type, title, message, link_data) 
                      VALUES (:user_id, 'challenge', 'Challenge Invitation', :message, :link_data)";
            
            $message = $sender['username'] . " invited you to join the challenge: " . $challenge['title'];
            $link_data = json_encode(['type' => 'challenge', 'id' => $challenge_id]);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $recipient_id);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':link_data', $link_data);
            
            return $stmt->execute();
        }
        
        return false;
    }
}
?>