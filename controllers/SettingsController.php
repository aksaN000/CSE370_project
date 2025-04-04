<?php
// controllers/SettingsController.php - Settings management controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/helpers.php';










class SettingsController {
    private $conn;
    private $user;
    private $settings_table = 'user_settings';
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->user = new User($conn);
    }
    
    // Get user settings
    public function getUserSettings($user_id) {
        // Check if settings exist for this user
        $query = "SELECT * FROM " . $this->settings_table . " WHERE user_id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // Return existing settings
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Create default settings
            $this->createDefaultSettings($user_id);
            
            // Return default settings
            $default_settings = [
                'user_id' => $user_id,
                'theme' => 'light',
                'color_scheme' => 'default',
                'enable_animations' => 1,
                'compact_mode' => 0,
                'email_notifications' => 1,
                'habit_reminders' => 1,
                'goal_updates' => 1,
                'challenge_notifications' => 1,
                'level_up_notifications' => 1,
                'email_daily' => 1,
                'email_weekly' => 1,
                'email_reminders' => 0,
                'public_profile' => 0,
                'show_stats' => 0,
                'show_achievements' => 1,
                'analytics_consent' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            return $default_settings;
        }
    }
    
    // Create default settings for a user
    public function updateThemeSettings($user_id, $theme, $color_scheme, $enable_animations, $compact_mode) {
        try {
            // Ensure type conversion
            $enable_animations = filter_var($enable_animations, FILTER_VALIDATE_BOOLEAN);
            
            // Existing settings update query
            $query = "UPDATE " . $this->settings_table . " SET 
                theme = :theme,
                color_scheme = :color_scheme,
                enable_animations = :enable_animations,
                compact_mode = :compact_mode,
                updated_at = NOW()
            WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':theme', $theme);
            $stmt->bindParam(':color_scheme', $color_scheme);
            $stmt->bindParam(':enable_animations', $enable_animations, PDO::PARAM_BOOL);
            $stmt->bindParam(':compact_mode', $compact_mode, PDO::PARAM_BOOL);
            
            if($stmt->execute()) {
                // Update session variables
                $_SESSION['enable_animations'] = $enable_animations;
                
                return [
                    'success' => true,
                    'message' => 'Theme settings updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update theme settings'
                ];
            }
        } catch (Exception $e) {
            error_log('Theme settings update error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error updating theme settings: ' . $e->getMessage()
            ];
        }
    }
    // Add this method to the SettingsController class in controllers/SettingsController.php

/**
 * 
 * @param int $user_id The user ID
 * @param array $settings Default settings array 
 * @return bool Success status
 */
public function createDefaultSettings($user_id, $settings = []) {
    // Set default values if not provided
    $defaults = [
        'theme' => 'light',
        'color_scheme' => 'default',
        'enable_animations' => 1,
        'compact_mode' => 0,
        'email_notifications' => 1,
        'habit_reminders' => 1,
        'goal_updates' => 1,
        'challenge_notifications' => 1,
        'level_up_notifications' => 1,
        'email_daily' => 1,
        'email_weekly' => 1,
        'email_reminders' => 0,
        'public_profile' => 0,
        'profile_visibility' => 'private',
        'show_stats' => 0,
        'show_achievements' => 1,
        'show_habits' => 0,
        'show_goals' => 0,
        'show_challenges' => 1,
        'allow_challenge_invites' => 1,
        'show_in_leaderboards' => 1,
        'allow_friend_requests' => 1,
        'analytics_consent' => 1,
        'feature_improvement_consent' => 0,
        'data_sharing' => 0
    ];
    
    // Merge provided settings with defaults
    $settings = array_merge($defaults, $settings);
    
    try {
        // Check if settings already exist for this user
        $query = "SELECT * FROM " . $this->settings_table . " WHERE user_id = :user_id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // Settings already exist, no need to create
            return true;
        }
        
        // Create settings query with all fields
        $query = "INSERT INTO " . $this->settings_table . " (
            user_id, theme, color_scheme, enable_animations, compact_mode,
            email_notifications, habit_reminders, goal_updates, challenge_notifications, level_up_notifications,
            email_daily, email_weekly, email_reminders, public_profile, profile_visibility,
            show_stats, show_achievements, show_habits, show_goals, show_challenges,
            allow_challenge_invites, show_in_leaderboards, allow_friend_requests,
            analytics_consent, feature_improvement_consent, data_sharing,
            created_at, updated_at
        ) VALUES (
            :user_id, :theme, :color_scheme, :enable_animations, :compact_mode,
            :email_notifications, :habit_reminders, :goal_updates, :challenge_notifications, :level_up_notifications,
            :email_daily, :email_weekly, :email_reminders, :public_profile, :profile_visibility,
            :show_stats, :show_achievements, :show_habits, :show_goals, :show_challenges,
            :allow_challenge_invites, :show_in_leaderboards, :allow_friend_requests,
            :analytics_consent, :feature_improvement_consent, :data_sharing,
            NOW(), NOW()
        )";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':theme', $settings['theme']);
        $stmt->bindParam(':color_scheme', $settings['color_scheme']);
        $stmt->bindParam(':enable_animations', $settings['enable_animations']);
        $stmt->bindParam(':compact_mode', $settings['compact_mode']);
        $stmt->bindParam(':email_notifications', $settings['email_notifications']);
        $stmt->bindParam(':habit_reminders', $settings['habit_reminders']);
        $stmt->bindParam(':goal_updates', $settings['goal_updates']);
        $stmt->bindParam(':challenge_notifications', $settings['challenge_notifications']);
        $stmt->bindParam(':level_up_notifications', $settings['level_up_notifications']);
        $stmt->bindParam(':email_daily', $settings['email_daily']);
        $stmt->bindParam(':email_weekly', $settings['email_weekly']);
        $stmt->bindParam(':email_reminders', $settings['email_reminders']);
        $stmt->bindParam(':public_profile', $settings['public_profile']);
        $stmt->bindParam(':profile_visibility', $settings['profile_visibility']);
        $stmt->bindParam(':show_stats', $settings['show_stats']);
        $stmt->bindParam(':show_achievements', $settings['show_achievements']);
        $stmt->bindParam(':show_habits', $settings['show_habits']);
        $stmt->bindParam(':show_goals', $settings['show_goals']);
        $stmt->bindParam(':show_challenges', $settings['show_challenges']);
        $stmt->bindParam(':allow_challenge_invites', $settings['allow_challenge_invites']);
        $stmt->bindParam(':show_in_leaderboards', $settings['show_in_leaderboards']);
        $stmt->bindParam(':allow_friend_requests', $settings['allow_friend_requests']);
        $stmt->bindParam(':analytics_consent', $settings['analytics_consent']);
        $stmt->bindParam(':feature_improvement_consent', $settings['feature_improvement_consent']);
        $stmt->bindParam(':data_sharing', $settings['data_sharing']);
        
        // Execute query
        $stmt->execute();
        return true;
    } catch(Exception $e) {
        error_log('Error creating default settings: ' . $e->getMessage());
        return false;
    }
}
    // Update notification settings
    public function updateNotificationSettings(
        $user_id, 
        $email_notifications, 
        $habit_reminders, 
        $goal_updates, 
        $challenge_notifications, 
        $level_up_notifications,
        $email_daily,
        $email_weekly,
        $email_reminders,
        $additional_settings = []
    ) {
        // Check if settings exist
        $settings = $this->getUserSettings($user_id);
        
        // Prepare additional fields from the form
        $habit_reminder_time = $additional_settings['habit_reminder_time'] ?? 'morning';
        $habit_reminder_custom_time = $additional_settings['habit_reminder_custom_time'] ?? '08:00';
        $goal_update_deadline = isset($additional_settings['goal_update_deadline']) ? 1 : 0;
        $goal_update_milestone = isset($additional_settings['goal_update_milestone']) ? 1 : 0;
        $goal_update_expired = isset($additional_settings['goal_update_expired']) ? 1 : 0;
        $challenge_task_reminders = isset($additional_settings['challenge_task_reminders']) ? 1 : 0;
        $challenge_new_participants = isset($additional_settings['challenge_new_participants']) ? 1 : 0;
        $challenge_completion = isset($additional_settings['challenge_completion']) ? 1 : 0;
        $notification_sound = $additional_settings['notification_sound'] ?? 'default';
        $notification_duration = $additional_settings['notification_duration'] ?? 'medium';
        $email_time = $additional_settings['email_time'] ?? 'morning';
        
        if(isset($settings['user_id'])) {
            // Update existing settings
            $query = "UPDATE " . $this->settings_table . " SET 
                email_notifications = :email_notifications,
                habit_reminders = :habit_reminders,
                goal_updates = :goal_updates,
                challenge_notifications = :challenge_notifications,
                level_up_notifications = :level_up_notifications,
                email_daily = :email_daily,
                email_weekly = :email_weekly,
                email_reminders = :email_reminders,
                habit_reminder_time = :habit_reminder_time,
                habit_reminder_custom_time = :habit_reminder_custom_time,
                goal_update_deadline = :goal_update_deadline,
                goal_update_milestone = :goal_update_milestone,
                goal_update_expired = :goal_update_expired,
                challenge_task_reminders = :challenge_task_reminders,
                challenge_new_participants = :challenge_new_participants,
                challenge_completion = :challenge_completion,
                notification_sound = :notification_sound,
                notification_duration = :notification_duration,
                email_time = :email_time,
                updated_at = NOW()
            WHERE user_id = :user_id";
        } else {
            // Create new settings
            $query = "INSERT INTO " . $this->settings_table . " (
                user_id, email_notifications, habit_reminders, goal_updates, 
                challenge_notifications, level_up_notifications,
                email_daily, email_weekly, email_reminders,
                habit_reminder_time, habit_reminder_custom_time,
                goal_update_deadline, goal_update_milestone, goal_update_expired,
                challenge_task_reminders, challenge_new_participants, challenge_completion,
                notification_sound, notification_duration, email_time,
                updated_at
            ) VALUES (
                :user_id, :email_notifications, :habit_reminders, :goal_updates, 
                :challenge_notifications, :level_up_notifications,
                :email_daily, :email_weekly, :email_reminders,
                :habit_reminder_time, :habit_reminder_custom_time,
                :goal_update_deadline, :goal_update_milestone, :goal_update_expired,
                :challenge_task_reminders, :challenge_new_participants, :challenge_completion,
                :notification_sound, :notification_duration, :email_time,
                NOW()
            )";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':email_notifications', $email_notifications);
        $stmt->bindParam(':habit_reminders', $habit_reminders);
        $stmt->bindParam(':goal_updates', $goal_updates);
        $stmt->bindParam(':challenge_notifications', $challenge_notifications);
        $stmt->bindParam(':level_up_notifications', $level_up_notifications);
        $stmt->bindParam(':email_daily', $email_daily);
        $stmt->bindParam(':email_weekly', $email_weekly);
        $stmt->bindParam(':email_reminders', $email_reminders);
        $stmt->bindParam(':habit_reminder_time', $habit_reminder_time);
        $stmt->bindParam(':habit_reminder_custom_time', $habit_reminder_custom_time);
        $stmt->bindParam(':goal_update_deadline', $goal_update_deadline);
        $stmt->bindParam(':goal_update_milestone', $goal_update_milestone);
        $stmt->bindParam(':goal_update_expired', $goal_update_expired);
        $stmt->bindParam(':challenge_task_reminders', $challenge_task_reminders);
        $stmt->bindParam(':challenge_new_participants', $challenge_new_participants);
        $stmt->bindParam(':challenge_completion', $challenge_completion);
        $stmt->bindParam(':notification_sound', $notification_sound);
        $stmt->bindParam(':notification_duration', $notification_duration);
        $stmt->bindParam(':email_time', $email_time);
        
        if($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update notification settings'
            ];
        }
    }
    
    // Update privacy settings
    public function updatePrivacySettings(
        $user_id, 
        $public_profile, 
        $show_stats, 
        $show_achievements, 
        $analytics_consent,
        $additional_settings = []
    ) {
        // Check if settings exist for this user
        $settings = $this->getUserSettings($user_id);
        
        // Get additional privacy settings with defaults
        $profile_visibility = $additional_settings['profile_visibility'] ?? 'private';
        $show_habits = isset($additional_settings['show_habits']) ? (int)$additional_settings['show_habits'] : 0;
        $show_goals = isset($additional_settings['show_goals']) ? (int)$additional_settings['show_goals'] : 0;
        $show_challenges = isset($additional_settings['show_challenges']) ? (int)$additional_settings['show_challenges'] : 1;
        $allow_challenge_invites = isset($additional_settings['allow_challenge_invites']) ? (int)$additional_settings['allow_challenge_invites'] : 1;
        $show_in_leaderboards = isset($additional_settings['show_in_leaderboards']) ? (int)$additional_settings['show_in_leaderboards'] : 1;
        $allow_friend_requests = isset($additional_settings['allow_friend_requests']) ? (int)$additional_settings['allow_friend_requests'] : 1;
        $feature_improvement_consent = isset($additional_settings['feature_improvement_consent']) ? (int)$additional_settings['feature_improvement_consent'] : 0;
        $data_sharing = isset($additional_settings['data_sharing']) ? (int)$additional_settings['data_sharing'] : 0;
        
        try {
            if(isset($settings['user_id'])) {
                // Update existing settings
                $query = "UPDATE " . $this->settings_table . " SET 
                    public_profile = :public_profile,
                    profile_visibility = :profile_visibility,
                    show_stats = :show_stats,
                    show_achievements = :show_achievements,
                    show_habits = :show_habits,
                    show_goals = :show_goals,
                    show_challenges = :show_challenges,
                    allow_challenge_invites = :allow_challenge_invites,
                    show_in_leaderboards = :show_in_leaderboards,
                    allow_friend_requests = :allow_friend_requests,
                    analytics_consent = :analytics_consent,
                    feature_improvement_consent = :feature_improvement_consent,
                    data_sharing = :data_sharing,
                    updated_at = NOW()
                WHERE user_id = :user_id";
            } else {
                // Create new settings
                $query = "INSERT INTO " . $this->settings_table . " (
                    user_id, public_profile, profile_visibility, 
                    show_stats, show_achievements, show_habits, show_goals, show_challenges,
                    allow_challenge_invites, show_in_leaderboards, allow_friend_requests, 
                    analytics_consent, feature_improvement_consent, data_sharing, created_at, updated_at
                ) VALUES (
                    :user_id, :public_profile, :profile_visibility, 
                    :show_stats, :show_achievements, :show_habits, :show_goals, :show_challenges,
                    :allow_challenge_invites, :show_in_leaderboards, :allow_friend_requests,
                    :analytics_consent, :feature_improvement_consent, :data_sharing, NOW(), NOW()
                )";
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind all parameters
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':public_profile', $public_profile, PDO::PARAM_INT);
            $stmt->bindParam(':profile_visibility', $profile_visibility);
            $stmt->bindParam(':show_stats', $show_stats, PDO::PARAM_INT);
            $stmt->bindParam(':show_achievements', $show_achievements, PDO::PARAM_INT);
            $stmt->bindParam(':show_habits', $show_habits, PDO::PARAM_INT);
            $stmt->bindParam(':show_goals', $show_goals, PDO::PARAM_INT);
            $stmt->bindParam(':show_challenges', $show_challenges, PDO::PARAM_INT);
            $stmt->bindParam(':allow_challenge_invites', $allow_challenge_invites, PDO::PARAM_INT);
            $stmt->bindParam(':show_in_leaderboards', $show_in_leaderboards, PDO::PARAM_INT);
            $stmt->bindParam(':allow_friend_requests', $allow_friend_requests, PDO::PARAM_INT);
            $stmt->bindParam(':analytics_consent', $analytics_consent, PDO::PARAM_INT);
            $stmt->bindParam(':feature_improvement_consent', $feature_improvement_consent, PDO::PARAM_INT);
            $stmt->bindParam(':data_sharing', $data_sharing, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Privacy settings updated successfully'
                ];
            } else {
                $errorInfo = $stmt->errorInfo();
                return [
                    'success' => false,
                    'message' => 'Failed to update privacy settings: ' . $errorInfo[2]
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating privacy settings: ' . $e->getMessage()
            ];
        }
    }
    
    // Export user data
    public function exportUserData($user_id, $format = 'json') {
        $data = [
            'user' => $this->getUserData($user_id),
            'habits' => $this->getHabitData($user_id),
            'goals' => $this->getGoalData($user_id),
            'challenges' => $this->getChallengeData($user_id),
            'journal_entries' => $this->getJournalData($user_id),
            'achievements' => $this->getAchievementData($user_id),
            'settings' => $this->getUserSettings($user_id),
            'export_date' => date('Y-m-d H:i:s')
        ];
        
        if($format === 'json') {
            return [
                'success' => true,
                'message' => 'Data exported successfully',
                'data' => json_encode($data, JSON_PRETTY_PRINT)
            ];
        } else if($format === 'csv') {
            // Convert to CSV
            $csv_data = $this->convertToCSV($data);
            
            return [
                'success' => true,
                'message' => 'Data exported successfully',
                'data' => $csv_data
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid export format'
            ];
        }
    }
    
    // Convert data to CSV
    private function convertToCSV($data) {
        $csv_data = "";
        
        // Convert user data
        $csv_data .= "=== USER DATA ===\n";
        $csv_data .= $this->arrayToCSV([$data['user']]);
        $csv_data .= "\n\n";
        
        // Convert habits
        $csv_data .= "=== HABITS ===\n";
        if(!empty($data['habits'])) {
            $csv_data .= $this->arrayToCSV($data['habits']);
        } else {
            $csv_data .= "No habits found\n";
        }
        $csv_data .= "\n\n";
        
        // Convert goals
        $csv_data .= "=== GOALS ===\n";
        if(!empty($data['goals'])) {
            $csv_data .= $this->arrayToCSV($data['goals']);
        } else {
            $csv_data .= "No goals found\n";
        }
        $csv_data .= "\n\n";
        
        // Convert challenges
        $csv_data .= "=== CHALLENGES ===\n";
        if(!empty($data['challenges'])) {
            $csv_data .= $this->arrayToCSV($data['challenges']);
        } else {
            $csv_data .= "No challenges found\n";
        }
        $csv_data .= "\n\n";
        
        // Convert journal entries
        $csv_data .= "=== JOURNAL ENTRIES ===\n";
        if(!empty($data['journal_entries'])) {
            $csv_data .= $this->arrayToCSV($data['journal_entries']);
        } else {
            $csv_data .= "No journal entries found\n";
        }
        $csv_data .= "\n\n";
        
        // Convert achievements
        $csv_data .= "=== ACHIEVEMENTS ===\n";
        if(!empty($data['achievements'])) {
            $csv_data .= $this->arrayToCSV($data['achievements']);
        } else {
            $csv_data .= "No achievements found\n";
        }
        $csv_data .= "\n\n";
        
        // Convert settings
        $csv_data .= "=== SETTINGS ===\n";
        $csv_data .= $this->arrayToCSV([$data['settings']]);
        $csv_data .= "\n\n";
        
        return $csv_data;
    }
    
    // Convert array to CSV
    private function arrayToCSV($array) {
        if(empty($array)) {
            return "";
        }
        
        // Get headers from first row
        $headers = array_keys($array[0]);
        
        // Start with headers
        $csv = implode(",", $headers) . "\n";
        
        // Add data rows
        foreach($array as $row) {
            $csv_row = [];
            foreach($headers as $header) {
                $value = isset($row[$header]) ? $row[$header] : '';
                // Handle array or object values
                if(is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                // Escape quotes and wrap in quotes
                $csv_row[] = '"' . str_replace('"', '""', $value) . '"';
            }
            $csv .= implode(",", $csv_row) . "\n";
        }
        
        return $csv;
    }
    
    // Import user data
    public function importUserData($user_id, $file) {
        // Check file type
        $file_type = $file['type'];
        $allowed_types = ['application/json', 'text/csv', 'text/plain'];
        
        if(!in_array($file_type, $allowed_types)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Please upload a JSON or CSV file.'
            ];
        }
        
        // Read file content
        $file_content = file_get_contents($file['tmp_name']);
        if(!$file_content) {
            return [
                'success' => false,
                'message' => 'Failed to read file content'
            ];
        }
        
        // Parse file content
        $data = [];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if($file_extension === 'json' || $file_type === 'application/json') {
            $data = json_decode($file_content, true);
            if(json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON file: ' . json_last_error_msg()
                ];
            }
        } else if($file_extension === 'csv' || $file_type === 'text/csv' || $file_type === 'text/plain') {
            return [
                'success' => false,
                'message' => 'CSV import is not yet supported. Please use JSON format.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Unsupported file format'
            ];
        }
        
        // Validate data structure
        if(!isset($data['user']) || !isset($data['habits']) || !isset($data['goals'])) {
            return [
                'success' => false,
                'message' => 'Invalid data structure in the imported file'
            ];
        }
        
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Import data - this is a simplified version
            // In a real application, you would implement a more robust import process
            // that handles merging data with existing records
            
            // Import habits
            $result = $this->importHabits($user_id, $data['habits']);
            if(!$result['success']) {
                throw new Exception($result['message']);
            }
            
            // Import goals
            $result = $this->importGoals($user_id, $data['goals']);
            if(!$result['success']) {
                throw new Exception($result['message']);
            }
            
            // Import journal entries
            if(isset($data['journal_entries'])) {
                $result = $this->importJournalEntries($user_id, $data['journal_entries']);
                if(!$result['success']) {
                    throw new Exception($result['message']);
                }
            }
            
            // Import settings
            if(isset($data['settings'])) {
                $result = $this->importSettings($user_id, $data['settings']);
                if(!$result['success']) {
                    throw new Exception($result['message']);
                }
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Data imported successfully'
            ];
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }
    
    // Reset user data
    public function resetUserData($user_id, $reset_type) {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            switch($reset_type) {
                case 'habits':
                    // Delete all habits
                    $query = "DELETE FROM habits WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete all habit completions
                    $query = "DELETE FROM habit_completions WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $message = 'All habits and habit completions have been reset';
                    break;
                    
                case 'goals':
                    // Delete all goals
                    $query = "DELETE FROM goals WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $message = 'All goals have been reset';
                    break;
                    
                case 'challenges':
                    // Delete all challenge participations
                    $query = "DELETE FROM challenge_participants WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete all task completions
                    $query = "DELETE FROM challenge_task_completions WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete all challenges created by the user
                    // First get all challenge IDs created by the user
                    $query = "SELECT id FROM challenges WHERE creator_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $challenge_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Delete all related tasks
                    if(!empty($challenge_ids)) {
                        $placeholders = implode(',', array_fill(0, count($challenge_ids), '?'));
                        $query = "DELETE FROM challenge_tasks WHERE challenge_id IN ($placeholders)";
                        $stmt = $this->conn->prepare($query);
                        foreach($challenge_ids as $index => $id) {
                            $stmt->bindValue($index + 1, $id);
                        }
                        $stmt->execute();
                    }
                    
                    // Delete all challenges
                    $query = "DELETE FROM challenges WHERE creator_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $message = 'All challenges have been reset';
                    break;
                    
                case 'journal':
                    // Delete all journal references
                    $query = "DELETE jr FROM journal_references jr
                              INNER JOIN journal_entries je ON jr.journal_id = je.id
                              WHERE je.user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete all journal entries
                    $query = "DELETE FROM journal_entries WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $message = 'All journal entries have been reset';
                    break;
                    
                case 'all':
                    // Reset all data
                    // Delete habits
                    $query = "DELETE FROM habits WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete habit completions
                    $query = "DELETE FROM habit_completions WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete goals
                    $query = "DELETE FROM goals WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete challenge participations
                    $query = "DELETE FROM challenge_participants WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete task completions
                    $query = "DELETE FROM challenge_task_completions WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Get all challenge IDs created by the user
                    $query = "SELECT id FROM challenges WHERE creator_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $challenge_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Delete all related tasks
                    if(!empty($challenge_ids)) {
                        $placeholders = implode(',', array_fill(0, count($challenge_ids), '?'));
                        $query = "DELETE FROM challenge_tasks WHERE challenge_id IN ($placeholders)";
                        $stmt = $this->conn->prepare($query);
                        foreach($challenge_ids as $index => $id) {
                            $stmt->bindValue($index + 1, $id);
                        }
                        $stmt->execute();
                    }
                    
                    // Delete challenges
                    $query = "DELETE FROM challenges WHERE creator_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete journal references
                    $query = "DELETE jr FROM journal_references jr
                              INNER JOIN journal_entries je ON jr.journal_id = je.id
                              WHERE je.user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete journal entries
                    $query = "DELETE FROM journal_entries WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Reset user XP and level
                    $query = "UPDATE users SET current_xp = 0, level = 1 WHERE id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete achievements
                    $query = "DELETE FROM user_achievements WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    // Delete notifications
                    $query = "DELETE FROM notifications WHERE user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $message = 'All data has been reset';
                    break;
                    
                default:
                    throw new Exception('Invalid reset type');
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => $message
            ];
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            
            return [
                'success' => false,
                'message' => 'Reset failed: ' . $e->getMessage()
            ];
        }
    }
    
    // Helper method to get user data
    private function getUserData($user_id) {
        try {
            $query = "SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.current_xp, 
                u.level, 
                u.created_at, 
                u.updated_at,
                us.profile_visibility,
                us.public_profile,
                us.show_stats,
                us.show_achievements
            FROM users u
            LEFT JOIN user_settings us ON u.id = us.user_id
            WHERE u.id = :user_id 
            LIMIT 0,1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userData) {
                return null;
            }
            
            // Ensure default values if not set
            $userData['profile_visibility'] = $userData['profile_visibility'] ?? 'private';
            $userData['public_profile'] = $userData['public_profile'] ?? 0;
            $userData['show_stats'] = $userData['show_stats'] ?? 0;
            $userData['show_achievements'] = $userData['show_achievements'] ?? 1;
            
            return $userData;
        } catch (PDOException $e) {
            error_log('Error fetching user data: ' . $e->getMessage());
            return null;
        }
    }
    
    // Helper method to get habit data
    private function getHabitData($user_id) {
        try {
            // Fetch habits with category information in one query
            $query = "SELECT 
                h.id, 
                h.user_id, 
                h.category_id, 
                h.title, 
                h.description, 
                h.frequency_type, 
                h.frequency_value, 
                h.start_date, 
                h.end_date, 
                h.xp_reward,
                h.created_at,
                c.name as category_name,
                c.color as category_color,
                (SELECT COUNT(*) FROM habit_completions hc WHERE hc.habit_id = h.id) as total_completions,
                (SELECT MAX(completion_date) FROM habit_completions hc WHERE hc.habit_id = h.id) as last_completion_date
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            WHERE h.user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch completions for habits using a single query
            if (!empty($habits)) {
                $habit_ids = array_column($habits, 'id');
                $placeholders = implode(',', array_fill(0, count($habit_ids), '?'));
                
                $completion_query = "SELECT 
                    habit_id, 
                    completion_date, 
                    created_at
                FROM habit_completions 
                WHERE habit_id IN ($placeholders)
                ORDER BY completion_date ASC";
                
                $completion_stmt = $this->conn->prepare($completion_query);
                
                // Bind habit IDs
                foreach ($habit_ids as $index => $id) {
                    $completion_stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
                }
                
                $completion_stmt->execute();
                $completions = $completion_stmt->fetchAll(PDO::FETCH_GROUP);
                
                // Attach completions to habits
                foreach ($habits as &$habit) {
                    $habit['completions'] = $completions[$habit['id']] ?? [];
                    
                    // Calculate streak
                    $habit['streak'] = $this->calculateHabitStreak($habit['completions']);
                }
            }
            
            return $habits;
        } catch (PDOException $e) {
            error_log('Error fetching habit data: ' . $e->getMessage());
            return [];
        }
    }
    
    // Helper method to calculate habit streak
    private function calculateHabitStreak($completions) {
        if (empty($completions)) {
            return 0;
        }
        
        // Sort completions by date
        usort($completions, function($a, $b) {
            return strtotime($a['completion_date']) - strtotime($b['completion_date']);
        });
        
        $streak = 1;
        $max_streak = 1;
        $last_date = new DateTime($completions[0]['completion_date']);
        
        for ($i = 1; $i < count($completions); $i++) {
            $current_date = new DateTime($completions[$i]['completion_date']);
            $diff = $last_date->diff($current_date);
            
            if ($diff->days === 1) {
                $streak++;
                $max_streak = max($max_streak, $streak);
            } elseif ($diff->days > 1) {
                $streak = 1;
            }
            
            $last_date = $current_date;
        }
        
        return $max_streak;
    }
    
    // Helper method to get goal data
    private function getGoalData($user_id) {
        $query = "SELECT * FROM goals WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Helper method to get challenge data
    private function getChallengeData($user_id) {
        // Get created challenges
        $query = "SELECT c.*, 'creator' as role 
                  FROM challenges c
                  WHERE c.creator_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $created_challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get joined challenges
        $query = "SELECT c.*, 'participant' as role, cp.is_completed, cp.completion_date
                  FROM challenges c
                  JOIN challenge_participants cp ON c.id = cp.challenge_id
                  WHERE cp.user_id = :user_id AND c.creator_id != :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $joined_challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine challenges
        $challenges = array_merge($created_challenges, $joined_challenges);
        
        // Get tasks and completions for each challenge
        foreach($challenges as &$challenge) {
            // Get tasks
            $query = "SELECT * FROM challenge_tasks WHERE challenge_id = :challenge_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':challenge_id', $challenge['id']);
            $stmt->execute();
            
            $challenge['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get completions
            if($challenge['role'] === 'participant') {
                $completions = [];
                foreach($challenge['tasks'] as $task) {
                    $query = "SELECT * FROM challenge_task_completions 
                              WHERE task_id = :task_id AND user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':task_id', $task['id']);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    
                    $completion = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($completion) {
                        $completions[] = $completion;
                    }
                }
                
                $challenge['completions'] = $completions;
            }
        }
        
        return $challenges;
    }
    
    // Helper method to get journal data
    private function getJournalData($user_id) {
        $query = "SELECT * FROM journal_entries WHERE user_id = :user_id ORDER BY entry_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get references for each journal entry
        foreach($journals as &$journal) {
            $query = "SELECT r.reference_type, r.reference_id,
                      CASE 
                          WHEN r.reference_type = 'habit' THEN (SELECT title FROM habits WHERE id = r.reference_id)
                          WHEN r.reference_type = 'goal' THEN (SELECT title FROM goals WHERE id = r.reference_id)
                          WHEN r.reference_type = 'challenge' THEN (SELECT title FROM challenges WHERE id = r.reference_id)
                          ELSE NULL
                      END as reference_title
                      FROM journal_references r 
                      WHERE r.journal_id = :journal_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':journal_id', $journal['id']);
            $stmt->execute();
            
            $journal['references'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $journals;
    }
    
    // Helper method to get achievement data
    private function getAchievementData($user_id) {
        $query = "SELECT ua.*, l.level_number, l.title, l.badge_name, l.badge_description, l.badge_image 
                  FROM user_achievements ua
                  JOIN levels l ON ua.level_id = l.id
                  WHERE ua.user_id = :user_id
                  ORDER BY l.level_number ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Helper method to import habits
    private function importHabits($user_id, $habits) {
        if(empty($habits)) {
            return [
                'success' => true,
                'message' => 'No habits to import'
            ];
        }
        
        try {
            foreach($habits as $habit) {
                // Check if habit already exists
                $query = "SELECT id FROM habits 
                          WHERE user_id = :user_id AND title = :title 
                          LIMIT 0,1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $habit['title']);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    // Habit already exists, skip it
                    continue;
                }
                
                // Get category ID
                $category_id = 1; // Default category
                if(isset($habit['category_name']) && !empty($habit['category_name'])) {
                    $query = "SELECT id FROM categories WHERE name = :name LIMIT 0,1";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':name', $habit['category_name']);
                    $stmt->execute();
                    
                    if($stmt->rowCount() > 0) {
                        $category = $stmt->fetch(PDO::FETCH_ASSOC);
                        $category_id = $category['id'];
                    }
                }
                
                // Create new habit
                $query = "INSERT INTO habits (
                    user_id, category_id, title, description, frequency_type, frequency_value, 
                    start_date, end_date, xp_reward
                ) VALUES (
                    :user_id, :category_id, :title, :description, :frequency_type, :frequency_value, 
                    :start_date, :end_date, :xp_reward
                )";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':title', $habit['title']);
                $stmt->bindParam(':description', $habit['description']);
                $stmt->bindParam(':frequency_type', $habit['frequency_type']);
                $stmt->bindParam(':frequency_value', $habit['frequency_value']);
                $stmt->bindParam(':start_date', $habit['start_date']);
                
                if(empty($habit['end_date'])) {
                    $stmt->bindValue(':end_date', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':end_date', $habit['end_date']);
                }
                
                $stmt->bindParam(':xp_reward', $habit['xp_reward']);
                $stmt->execute();
                
                $habit_id = $this->conn->lastInsertId();
                
                // Import completions if available
                if(isset($habit['completions']) && !empty($habit['completions'])) {
                    foreach($habit['completions'] as $completion) {
                        $query = "INSERT INTO habit_completions (
                            habit_id, user_id, completion_date
                        ) VALUES (
                            :habit_id, :user_id, :completion_date
                        )";
                        
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':habit_id', $habit_id);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':completion_date', $completion['completion_date']);
                        $stmt->execute();
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => count($habits) . ' habits imported successfully'
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import habits: ' . $e->getMessage()
            ];
        }
    }
    
    // Helper method to import goals
    private function importGoals($user_id, $goals) {
        if(empty($goals)) {
            return [
                'success' => true,
                'message' => 'No goals to import'
            ];
        }
        
        try {
            foreach($goals as $goal) {
                // Check if goal already exists
                $query = "SELECT id FROM goals 
                          WHERE user_id = :user_id AND title = :title 
                          LIMIT 0,1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $goal['title']);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    // Goal already exists, skip it
                    continue;
                }
                
                // Create new goal
                $query = "INSERT INTO goals (
                    user_id, title, description, target_value, current_value, 
                    start_date, end_date, is_completed, xp_reward
                ) VALUES (
                    :user_id, :title, :description, :target_value, :current_value, 
                    :start_date, :end_date, :is_completed, :xp_reward
                )";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $goal['title']);
                $stmt->bindParam(':description', $goal['description']);
                $stmt->bindParam(':target_value', $goal['target_value']);
                $stmt->bindParam(':current_value', $goal['current_value']);
                $stmt->bindParam(':start_date', $goal['start_date']);
                $stmt->bindParam(':end_date', $goal['end_date']);
                $stmt->bindParam(':is_completed', $goal['is_completed']);
                $stmt->bindParam(':xp_reward', $goal['xp_reward']);
                $stmt->execute();
            }
            
            return [
                'success' => true,
                'message' => count($goals) . ' goals imported successfully'
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import goals: ' . $e->getMessage()
            ];
        }
    }
    
    // Helper method to import journal entries
    private function importJournalEntries($user_id, $journals) {
        if(empty($journals)) {
            return [
                'success' => true,
                'message' => 'No journal entries to import'
            ];
        }
        
        try {
            foreach($journals as $journal) {
                // Check if journal entry already exists
                $query = "SELECT id FROM journal_entries 
                          WHERE user_id = :user_id AND title = :title AND entry_date = :entry_date 
                          LIMIT 0,1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $journal['title']);
                $stmt->bindParam(':entry_date', $journal['entry_date']);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    // Journal entry already exists, skip it
                    continue;
                }
                
                // Create new journal entry
                $query = "INSERT INTO journal_entries (
                    user_id, title, content, mood, entry_date
                ) VALUES (
                    :user_id, :title, :content, :mood, :entry_date
                )";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':title', $journal['title']);
                $stmt->bindParam(':content', $journal['content']);
                $stmt->bindParam(':mood', $journal['mood']);
                $stmt->bindParam(':entry_date', $journal['entry_date']);
                $stmt->execute();
                
                $journal_id = $this->conn->lastInsertId();
                
                // Import references if available
                if(isset($journal['references']) && !empty($journal['references'])) {
                    foreach($journal['references'] as $reference) {
                        $query = "INSERT INTO journal_references (
                            journal_id, reference_type, reference_id
                        ) VALUES (
                            :journal_id, :reference_type, :reference_id
                        )";
                        
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':journal_id', $journal_id);
                        $stmt->bindParam(':reference_type', $reference['reference_type']);
                        $stmt->bindParam(':reference_id', $reference['reference_id']);
                        $stmt->execute();
                    }
                }
            }
            
            return [
                'success' => true,
                'message' => count($journals) . ' journal entries imported successfully'
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import journal entries: ' . $e->getMessage()
            ];
        }
    }
    
    // Helper method to import settings
    private function importSettings($user_id, $settings) {
        try {
            // Check if settings already exist
            $query = "SELECT * FROM " . $this->settings_table . " WHERE user_id = :user_id LIMIT 0,1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                // Settings already exist, update them
                $query = "UPDATE " . $this->settings_table . " SET 
                    theme = :theme,
                    color_scheme = :color_scheme,
                    enable_animations = :enable_animations,
                    compact_mode = :compact_mode,
                    email_notifications = :email_notifications,
                    habit_reminders = :habit_reminders,
                    goal_updates = :goal_updates,
                    challenge_notifications = :challenge_notifications,
                    level_up_notifications = :level_up_notifications,
                    email_daily = :email_daily,
                    email_weekly = :email_weekly,
                    email_reminders = :email_reminders,
                    public_profile = :public_profile,
                    show_stats = :show_stats,
                    show_achievements = :show_achievements,
                    analytics_consent = :analytics_consent,
                    updated_at = NOW()
                WHERE user_id = :user_id";
            } else {
                // Settings don't exist, create them
                $query = "INSERT INTO " . $this->settings_table . " (
                    user_id, theme, color_scheme, enable_animations, compact_mode, 
                    email_notifications, habit_reminders, goal_updates, challenge_notifications, level_up_notifications,
                    email_daily, email_weekly, email_reminders, public_profile, show_stats, show_achievements, analytics_consent,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :theme, :color_scheme, :enable_animations, :compact_mode,
                    :email_notifications, :habit_reminders, :goal_updates, :challenge_notifications, :level_up_notifications,
                    :email_daily, :email_weekly, :email_reminders, :public_profile, :show_stats, :show_achievements, :analytics_consent,
                    NOW(), NOW()
                )";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':theme', $settings['theme']);
            $stmt->bindParam(':color_scheme', $settings['color_scheme']);
            $stmt->bindParam(':enable_animations', $settings['enable_animations']);
            $stmt->bindParam(':compact_mode', $settings['compact_mode']);
            $stmt->bindParam(':email_notifications', $settings['email_notifications']);
            $stmt->bindParam(':habit_reminders', $settings['habit_reminders']);
            $stmt->bindParam(':goal_updates', $settings['goal_updates']);
            $stmt->bindParam(':challenge_notifications', $settings['challenge_notifications']);
            $stmt->bindParam(':level_up_notifications', $settings['level_up_notifications']);
            $stmt->bindParam(':email_daily', $settings['email_daily']);
            $stmt->bindParam(':email_weekly', $settings['email_weekly']);
            $stmt->bindParam(':email_reminders', $settings['email_reminders']);
            $stmt->bindParam(':public_profile', $settings['public_profile']);
            $stmt->bindParam(':show_stats', $settings['show_stats']);
            $stmt->bindParam(':show_achievements', $settings['show_achievements']);
            $stmt->bindParam(':analytics_consent', $settings['analytics_consent']);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Settings imported successfully'
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to import settings: ' . $e->getMessage()
            ];
        }
    }
    
    // Delete user account
    public function deleteAccount($user_id, $password) {
        // Verify password first
        $this->user->id = $user_id;
        if(!$this->user->getUserById($user_id)) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Verify password
        if(!password_verify($password, $this->user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid password'
            ];
        }
        
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Delete all user data
            
            // Delete habits and completions
            $query = "DELETE FROM habit_completions WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $query = "DELETE FROM habits WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Delete goals
            $query = "DELETE FROM goals WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Delete challenge participations and task completions
            $query = "DELETE FROM challenge_task_completions WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $query = "DELETE FROM challenge_participants WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Get challenges created by user
            $query = "SELECT id FROM challenges WHERE creator_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $challenge_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete challenge tasks
            if(!empty($challenge_ids)) {
                $placeholders = implode(',', array_fill(0, count($challenge_ids), '?'));
                
                // Delete task completions for these challenges
                $query = "DELETE ctc FROM challenge_task_completions ctc
                          INNER JOIN challenge_tasks ct ON ctc.task_id = ct.id
                          WHERE ct.challenge_id IN ($placeholders)";
                $stmt = $this->conn->prepare($query);
                foreach($challenge_ids as $index => $id) {
                    $stmt->bindValue($index + 1, $id);
                }
                $stmt->execute();
                
                // Delete tasks
                $query = "DELETE FROM challenge_tasks WHERE challenge_id IN ($placeholders)";
                $stmt = $this->conn->prepare($query);
                foreach($challenge_ids as $index => $id) {
                    $stmt->bindValue($index + 1, $id);
                }
                $stmt->execute();
                
                // Delete participants
                $query = "DELETE FROM challenge_participants WHERE challenge_id IN ($placeholders)";
                $stmt = $this->conn->prepare($query);
                foreach($challenge_ids as $index => $id) {
                    $stmt->bindValue($index + 1, $id);
                }
                $stmt->execute();
                
                // Delete challenges
                $query = "DELETE FROM challenges WHERE id IN ($placeholders)";
                $stmt = $this->conn->prepare($query);
                foreach($challenge_ids as $index => $id) {
                    $stmt->bindValue($index + 1, $id);
                }
                $stmt->execute();
            }
            
            // Delete journal references and entries
            $query = "DELETE jr FROM journal_references jr
                      INNER JOIN journal_entries je ON jr.journal_id = je.id
                      WHERE je.user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $query = "DELETE FROM journal_entries WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Delete achievements
            $query = "DELETE FROM user_achievements WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Delete notifications
            $query = "DELETE FROM notifications WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Delete settings
            $query = "DELETE FROM " . $this->settings_table . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Finally, delete the user
            $query = "DELETE FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Account deleted successfully'
            ];
        } catch(Exception $e) {
            // Rollback transaction on error
            $this->conn->rollBack();
            
            return [
                'success' => false,
                'message' => 'Failed to delete account: ' . $e->getMessage()
            ];
        }
    }
}