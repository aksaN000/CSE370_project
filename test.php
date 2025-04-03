<?php
// controllers/CommunityController.php - Community controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Community.php';
require_once __DIR__ . '/../controllers/HabitController.php';
require_once __DIR__ . '/../controllers/GoalController.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';

class CommunityController {
    private $conn;
    private $community;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->community = new Community($conn);
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
        $special_achievements = [
            // Habit-related special achievements
            'early_bird' => $this->checkEarlyBirdAchievement($user_id),
            'perfectionist' => $this->checkPerfectionistAchievement($user_id),
            
            // Goal-related special achievements
            'goal_crusher' => $this->checkGoalCrusherAchievement($user_id),
            
            // Challenge-related special achievements
            'social_butterfly' => $this->checkSocialButterflyAchievement($user_id),
            
            // Journal-related special achievements
            'deep_thinker' => $this->checkDeepThinkerAchievement($user_id)
        ];
        
        return array_filter($special_achievements);
    }

    // Check Early Bird Achievement (5 habits before 9 AM)
    private function checkEarlyBirdAchievement($user_id) {
        $query = "SELECT COUNT(*) as early_habits 
                  FROM habit_completions hc
                  JOIN habits h ON hc.habit_id = h.id
                  WHERE h.user_id = :user_id 
                  AND TIME(hc.completion_date) < '09:00:00'
                  GROUP BY DATE(hc.completion_date)
                  HAVING early_habits >= 5
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

    // Check Perfectionist Achievement (7 consecutive days of all habit completions)
    private function checkPerfectionistAchievement($user_id) {
        $query = "SELECT MAX(consecutive_days) as max_streak
                  FROM (
                      SELECT 
                          DATE_SUB(completion_date, INTERVAL ROW_NUMBER() OVER (ORDER BY completion_date) DAY) as date_group,
                          COUNT(*) as consecutive_days
                      FROM (
                          SELECT DISTINCT DATE(hc.completion_date) as completion_date
                          FROM habit_completions hc
                          JOIN habits h ON hc.habit_id = h.id
                          WHERE h.user_id = :user_id
                          GROUP BY DATE(hc.completion_date)
                      ) unique_dates
                      GROUP BY date_group
                  ) streaks
                  WHERE consecutive_days >= 7";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0 ? [
            'name' => 'Perfectionist',
            'description' => 'Complete all habits for 7 consecutive days',
            'icon' => 'calendar-check',
            'color' => 'success'
        ] : null;
    }

    // Check Goal Crusher Achievement (10 goals completed)
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

    // Check Social Butterfly Achievement (5 challenges completed)
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

    // Check Deep Thinker Achievement (20 journal entries)
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
            
            // Check if there's a pending friend request
            $incoming = $this->community->getIncomingFriendRequests($current_user_id);
            $outgoing = $this->community->getOutgoingFriendRequests($current_user_id);
            
            $profile['has_sent_request'] = false;
            $profile['has_received_request'] = false;
            
            foreach($incoming as $request) {
                if($request['sender_id'] == $user_id) {
                    $profile['has_received_request'] = true;
                    $profile['request_id'] = $request['id'];
                    break;
                }
            }
            
            foreach($outgoing as $request) {
                if($request['recipient_id'] == $user_id) {
                    $profile['has_sent_request'] = true;
                    break;
                }
            }
        }
        
        // Add achievements to profile data
        $profile['achievements'] = $this->getUserProfileAchievements($user_id, $current_user_id);
        
        return [
            'success' => true,
            'profile' => $profile
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
        if($this->community->acceptFriendRequest($request_id, $user_id)) {
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