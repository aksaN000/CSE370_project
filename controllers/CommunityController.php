<?php
// controllers/CommunityController.php - Community controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Community.php';

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