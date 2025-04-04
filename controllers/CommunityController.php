<?php
// controllers/CommunityController.php - Community controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Community.php';
require_once __DIR__ . '/../controllers/HabitController.php';
require_once __DIR__ . '/../controllers/GoalController.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';
require_once __DIR__ . '/../utils/NotificationHelper.php';

class CommunityController {
    private $conn;
    private $community;
    private $notificationHelper;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->community = new Community($conn);
        $this->notificationHelper = new NotificationHelper($conn);
    }
    
    // Search for users
    public function searchUsers($search_term, $user_id) {
        return $this->community->searchUsers($search_term, $user_id);
    }

    // Get public habits
    public function getPublicHabits($user_id, $viewer_id = null) {
        // First get the user's privacy settings
        $profile = $this->getUserProfile($user_id, $viewer_id);
        
        if (!$profile['success']) {
            return [];
        }
        
        $profile = $profile['profile'];
        
        // Check if viewer is allowed to see habits
        $canView = ($profile['show_habits'] && $this->canViewProfile($profile, $viewer_id)) || 
                  ($user_id == $viewer_id);
                  
        if (!$canView) {
            return [];
        }
        
        // Fetch and return habits
        $habitController = new HabitController();
        return $habitController->getAllHabits($user_id);
    }
    
    // Get public goals
    public function getPublicGoals($user_id, $viewer_id = null) {
        // First get the user's privacy settings
        $profile = $this->getUserProfile($user_id, $viewer_id);
        
        if (!$profile['success']) {
            return [];
        }
        
        $profile = $profile['profile'];
        
        // Check if viewer is allowed to see goals
        $canView = ($profile['show_goals'] && $this->canViewProfile($profile, $viewer_id)) || 
                  ($user_id == $viewer_id);
                  
        if (!$canView) {
            return [];
        }
        
        // Fetch and return goals
        $goalController = new GoalController();
        return $goalController->getAllGoals($user_id);
    }
    
    // Get public challenges
    public function getPublicChallenges($user_id, $viewer_id = null) {
        // First get the user's privacy settings
        $profile = $this->getUserProfile($user_id, $viewer_id);
        
        if (!$profile['success']) {
            return [];
        }
        
        $profile = $profile['profile'];
        
        // Check if viewer is allowed to see challenges
        $canView = ($profile['show_challenges'] && $this->canViewProfile($profile, $viewer_id)) || 
                  ($user_id == $viewer_id);
                  
        if (!$canView) {
            return [];
        }
        
        // Fetch and return challenges
        $challengeController = new ChallengeController();
        $active = $challengeController->getActiveChallenges($user_id);
        $completed = $challengeController->getCompletedChallenges($user_id);
        $created = $challengeController->getUserCreatedChallenges($user_id);
        
        return [
            'active' => $active,
            'completed' => $completed,
            'created' => $created
        ];
    }

    // Method to get user achievements for community profile
    public function getUserProfileAchievements($user_id, $viewer_id = null) {
        // First check profile visibility
        $profile = $this->getUserProfile($user_id, $viewer_id);
        
        if (!$profile['success']) {
            return [];
        }
        
        $profile = $profile['profile'];
        
        // Check if achievements can be viewed
        $canView = ($profile['show_achievements'] && $this->canViewProfile($profile, $viewer_id)) || 
                   ($user_id == $viewer_id);
        
        if (!$canView) {
            return [];
        }
        
        // Get achievements
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

    // Get special achievements for a user
    private function getSpecialAchievements($user_id) {
        return [
            'early_bird' => $this->checkEarlyBirdAchievement($user_id),
            'perfectionist' => $this->checkPerfectionistAchievement($user_id),
            'goal_crusher' => $this->checkGoalCrusherAchievement($user_id),
            'social_butterfly' => $this->checkSocialButterflyAchievement($user_id),
            'deep_thinker' => $this->checkDeepThinkerAchievement($user_id)
        ];
    }

    // Optimized Perfectionist Achievement method without window functions
private function checkPerfectionistAchievement($user_id) {
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
    
    // Check if the user has a 7-day streak and completed all habits on those days
    return $max_consecutive_days >= 7 ? [
        'name' => 'Perfectionist',
        'description' => 'Complete all habits for 7 consecutive days',
        'icon' => 'calendar-check',
        'color' => 'success'
    ] : null;
}

    // Check Early Bird Achievement (5 habits before 9 AM)
    private function checkEarlyBirdAchievement($user_id) {
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
        
        return $stmt->rowCount() > 0 ? [
            'name' => 'Early Bird',
            'description' => 'Complete 5 habits before 9 AM',
            'icon' => 'sunrise',
            'color' => 'warning'
        ] : null;
    }
    
    private function checkGoalCrusherAchievement($user_id) {
        $query = "SELECT COUNT(*) as completed_goals 
                  FROM goals 
                  WHERE user_id = :user_id AND is_completed = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['completed_goals'] >= 10 ? [
            'name' => 'Goal Crusher',
            'description' => 'Complete 10 goals',
            'icon' => 'bullseye',
            'color' => 'danger'
        ] : null;
    }
    
    private function checkSocialButterflyAchievement($user_id) {
        $query = "SELECT COUNT(*) as completed_challenges 
                  FROM challenge_participants 
                  WHERE user_id = :user_id AND is_completed = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['completed_challenges'] >= 5 ? [
            'name' => 'Social Butterfly',
            'description' => 'Join and complete 5 challenges',
            'icon' => 'people',
            'color' => 'primary'
        ] : null;
    }
    
    private function checkDeepThinkerAchievement($user_id) {
        $query = "SELECT COUNT(*) as journal_entries 
                  FROM journal_entries 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['journal_entries'] >= 20 ? [
            'name' => 'Deep Thinker',
            'description' => 'Write 20 journal entries',
            'icon' => 'journal-text',
            'color' => 'info'
        ] : null;
    }

    // Existing methods from previous implementation...
    // Get user's profile
    public function getUserProfile($user_id, $current_user_id = null) {
        $profile = $this->community->getUserProfile($user_id);
        
        if(!$profile) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Check visibility permissions
        $canView = $this->canViewProfile($profile, $current_user_id);
        
        if(!$canView) {
            return [
                'success' => false,
                'message' => 'This profile is private'
            ];
        }
        
        // Check if users are friends
        if($current_user_id) {
            $profile['is_friend'] = $this->community->areFriends($current_user_id, $user_id);
            
            // Optimize friend request checking
            $profile['has_sent_request'] = $this->checkFriendRequest($current_user_id, $user_id, 'sent');
            $profile['has_received_request'] = $this->checkFriendRequest($current_user_id, $user_id, 'received');
            
            if($profile['has_received_request']) {
                // Get the request ID
                $request = $this->getFriendRequestId($current_user_id, $user_id);
                $profile['request_id'] = $request ? $request['id'] : null;
            }
        }
        
        // Limit achievement fetching to prevent memory exhaustion
        $profile['achievements'] = $this->getLimitedAchievements($user_id, $current_user_id);
        
        return [
            'success' => true,
            'profile' => $profile
        ];
    }
    
    // Optimized friend request checking
    private function checkFriendRequest($current_user_id, $target_user_id, $type = 'sent') {
        $query = $type === 'sent' 
            ? "SELECT COUNT(*) as count FROM friend_requests WHERE sender_id = :sender_id AND recipient_id = :recipient_id"
            : "SELECT COUNT(*) as count FROM friend_requests WHERE sender_id = :recipient_id AND recipient_id = :sender_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sender_id', $current_user_id);
        $stmt->bindParam(':recipient_id', $target_user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    // Get friend request ID
    private function getFriendRequestId($current_user_id, $target_user_id) {
        $query = "SELECT id FROM friend_requests 
                  WHERE sender_id = :recipient_id AND recipient_id = :sender_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sender_id', $current_user_id);
        $stmt->bindParam(':recipient_id', $target_user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Limit achievements to prevent memory exhaustion
    private function getLimitedAchievements($user_id, $current_user_id) {
        // Limit the number of achievements fetched
        $query = "SELECT l.level_number, l.title, l.badge_name, l.badge_description, l.badge_image 
                  FROM user_achievements ua
                  JOIN levels l ON ua.level_id = l.id
                  WHERE ua.user_id = :user_id
                  ORDER BY l.level_number ASC
                  LIMIT 10";  // Limit to 10 most recent achievements
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch a few special achievements
        $special_achievements = array_slice($this->getSpecialAchievements($user_id), 0, 5);
        
        return [
            'level_achievements' => $achievements,
            'special_achievements' => $special_achievements
        ];
    }

    // Check if user can view a profile based on privacy settings
    private function canViewProfile($profile, $current_user_id) {
        // If it's the user's own profile
        if($profile['id'] == $current_user_id) {
            return true;
        }
        
        // Check profile visibility
        switch($profile['profile_visibility']) {
            case 'private':
                return false;
                
            case 'friends':
                // Check if they're friends
                return $current_user_id && $this->community->areFriends($profile['id'], $current_user_id);
                
            case 'members':
                // As long as the viewer is logged in
                return $current_user_id !== null;
                
            case 'public':
                return true;
                
            default:
                // Legacy public_profile setting
                return $profile['public_profile'] == 1;
        }
    }
    
    // Get user's friends
    public function getFriends($user_id) {
        return $this->community->getFriends($user_id);
    }
    
    // Get friend requests
    public function getFriendRequests($user_id) {
        $incoming = $this->community->getIncomingFriendRequests($user_id);
        $outgoing = $this->community->getOutgoingFriendRequests($user_id);
        
        return [
            'incoming' => $incoming,
            'outgoing' => $outgoing
        ];
    }
    
    // Send friend request
    public function sendFriendRequest($sender_id, $recipient_id) {
        // Check if the recipient allows friend requests
        $query = "SELECT allow_friend_requests FROM user_settings WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $recipient_id);
        $stmt->execute();
        
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$settings || $settings['allow_friend_requests'] != 1) {
            return [
                'success' => false,
                'message' => 'This user is not accepting friend requests'
            ];
        }
        
        // Check if they're already friends
        if($this->community->areFriends($sender_id, $recipient_id)) {
            return [
                'success' => false,
                'message' => 'You are already friends with this user'
            ];
        }
        
        if($this->community->sendFriendRequest($sender_id, $recipient_id)) {
            // Get sender username
            $query = "SELECT username FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $sender_id);
            $stmt->execute();
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            $sender_username = $sender ? $sender['username'] : 'A user';
            
            // Create notification
            $notification_data = [
                'user_id' => $recipient_id,
                'type' => 'friend_request',
                'title' => 'New Friend Request',
                'message' => "{$sender_username} sent you a friend request",
                'link_data' => json_encode(['type' => 'profile', 'id' => $sender_id])
            ];
            
            $this->notificationHelper->createNotificationIfEnabled($notification_data);
            
            return [
                'success' => true,
                'message' => 'Friend request sent'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Friend request already sent or failed'
            ];
        }
    }
    
    // Accept friend request
    public function acceptFriendRequest($request_id, $user_id) {
        // Get the sender details
        $query = "SELECT fr.sender_id, u.username 
                  FROM friend_requests fr 
                  JOIN users u ON fr.sender_id = u.id 
                  WHERE fr.id = :request_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($this->community->acceptFriendRequest($request_id, $user_id)) {
            // Get recipient username
            $query = "SELECT username FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
            $recipient_username = $recipient ? $recipient['username'] : 'A user';
            
            // Create notification for the sender
            if ($request) {
                $notification_data = [
                    'user_id' => $request['sender_id'],
                    'type' => 'friend_accepted',
                    'title' => 'Friend Request Accepted',
                    'message' => "{$recipient_username} accepted your friend request",
                    'link_data' => json_encode(['type' => 'profile', 'id' => $user_id])
                ];
                
                $this->notificationHelper->createNotificationIfEnabled($notification_data);
            }
            
            return [
                'success' => true,
                'message' => 'Friend request accepted'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to accept friend request'
            ];
        }
    }
    
    // Reject friend request
    public function rejectFriendRequest($request_id, $user_id) {
        if($this->community->rejectFriendRequest($request_id, $user_id)) {
            return [
                'success' => true,
                'message' => 'Friend request rejected'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to reject friend request'
            ];
        }
    }
    
    // Remove friend
    public function removeFriend($user_id, $friend_id) {
        if($this->community->removeFriend($user_id, $friend_id)) {
            return [
                'success' => true,
                'message' => 'Friend removed'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to remove friend'
            ];
        }
    }
    
    // Get leaderboard
    public function getLeaderboard($category = 'xp', $limit = 10) {
        $leaderboard = $this->community->getLeaderboard($category, $limit);
        
        return [
            'success' => true,
            'category' => $category,
            'leaderboard' => $leaderboard
        ];
    }
    
    // Invite friend to challenge
    public function inviteToChallenge($challenge_id, $sender_id, $recipient_id) {
        // Check if the recipient allows challenge invites
        $query = "SELECT allow_challenge_invites FROM user_settings WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $recipient_id);
        $stmt->execute();
        
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$settings || $settings['allow_challenge_invites'] != 1) {
            return [
                'success' => false,
                'message' => 'This user is not accepting challenge invitations'
            ];
        }
        
        // Check if they're friends
        if(!$this->community->areFriends($sender_id, $recipient_id)) {
            return [
                'success' => false,
                'message' => 'You can only invite friends to challenges'
            ];
        }
        
        if($this->community->inviteToChallenge($challenge_id, $sender_id, $recipient_id)) {
            // Get sender username and challenge title
            $query = "SELECT u.username, c.title
                      FROM users u
                      JOIN challenges c ON c.id = :challenge_id
                      WHERE u.id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $sender_id);
            $stmt->bindParam(':challenge_id', $challenge_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sender_username = $result ? $result['username'] : 'A user';
            $challenge_title = $result ? $result['title'] : 'a challenge';
            
            // Create notification only if enabled
            $notification_data = [
                'user_id' => $recipient_id,
                'type' => 'challenge',
                'title' => 'Challenge Invitation',
                'message' => "{$sender_username} invited you to join the challenge: {$challenge_title}",
                'link_data' => json_encode(['type' => 'challenge', 'id' => $challenge_id])
            ];
            
            $this->notificationHelper->createNotificationIfEnabled($notification_data);
            
            return [
                'success' => true,
                'message' => 'Challenge invitation sent'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send challenge invitation or user is already participating'
            ];
        }
    }
}
?>