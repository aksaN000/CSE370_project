-- Database creation
CREATE DATABASE habit_tracker;
USE habit_tracker;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    current_xp INT DEFAULT 0,
    level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table for habits
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    icon VARCHAR(50) DEFAULT 'list',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Habits table
CREATE TABLE habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    frequency_type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    frequency_value JSON, -- Stores details about frequency (e.g., days of week)
    start_date DATE NOT NULL,
    end_date DATE,
    xp_reward INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Habit completions
CREATE TABLE habit_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    user_id INT NOT NULL,
    completion_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (habit_id, completion_date)
);

-- Goals table
CREATE TABLE goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    target_value INT DEFAULT 1,
    current_value INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE,
    is_completed BOOLEAN DEFAULT FALSE,
    xp_reward INT DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Challenges table
CREATE TABLE challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    xp_reward INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Challenge participation
CREATE TABLE challenge_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_completed BOOLEAN DEFAULT FALSE,
    completion_date TIMESTAMP NULL,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (challenge_id, user_id)
);

-- Challenge tasks
CREATE TABLE challenge_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
);

-- Challenge task completions
CREATE TABLE challenge_task_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES challenge_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (task_id, user_id)
);

-- Journal entries
CREATE TABLE journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100),
    content TEXT NOT NULL,
    mood ENUM('happy', 'motivated', 'neutral', 'tired', 'frustrated', 'sad') NOT NULL,
    entry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Journal entry references (links to habits, goals, challenges)
CREATE TABLE journal_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    journal_id INT NOT NULL,
    reference_type ENUM('habit', 'goal', 'challenge') NOT NULL,
    reference_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE
);

-- Levels configuration
CREATE TABLE levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_number INT NOT NULL,
    title VARCHAR(50) NOT NULL,
    xp_required INT NOT NULL,
    badge_name VARCHAR(50),
    badge_description TEXT,
    badge_image VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User achievements/badges
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    level_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, level_id)
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('habit', 'goal', 'challenge', 'xp', 'level', 'system') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, color, icon) VALUES 
('Exercise', '#e74c3c', 'dumbbell'),
('Reading', '#3498db', 'book'),
('Productivity', '#2ecc71', 'briefcase'),
('Nutrition', '#f39c12', 'utensils'),
('Mindfulness', '#9b59b6', 'peace'),
('Health', '#1abc9c', 'heart');

-- Insert default levels
INSERT INTO levels (level_number, title, xp_required, badge_name, badge_description, badge_image) VALUES
(1, 'Newbie', 0, 'First Step!', 'You have begun your journey.', 'badge-level-1.png'),
(2, 'Achiever', 100, 'Keep Going!', 'You are making progress.', 'badge-level-2.png'),
(3, 'Champion', 300, 'Habit Pro!', 'You are becoming a habit master.', 'badge-level-3.png'),
(4, 'Master', 600, 'Consistency King', 'Your consistency is impressive.', 'badge-level-4.png'),
(5, 'Legend', 1000, 'Legendary Status', 'You have achieved legendary status.', 'badge-level-5.png');

-- Create user_settings table
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `theme` enum('light','dark','system') NOT NULL DEFAULT 'light',
  `color_scheme` varchar(20) NOT NULL DEFAULT 'default',
  `enable_animations` tinyint(1) NOT NULL DEFAULT 1,
  `compact_mode` tinyint(1) NOT NULL DEFAULT 0,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `habit_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `goal_updates` tinyint(1) NOT NULL DEFAULT 1,
  `challenge_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `level_up_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `email_daily` tinyint(1) NOT NULL DEFAULT 1,
  `email_weekly` tinyint(1) NOT NULL DEFAULT 1,
  `email_reminders` tinyint(1) NOT NULL DEFAULT 0,
  `public_profile` tinyint(1) NOT NULL DEFAULT 0,
  `show_stats` tinyint(1) NOT NULL DEFAULT 0,
  `show_achievements` tinyint(1) NOT NULL DEFAULT 1,
  `analytics_consent` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_settings_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add some default settings for testing
INSERT INTO `user_settings` (`user_id`, `theme`, `color_scheme`) 
SELECT id, 'light', 'default' FROM users
WHERE NOT EXISTS (SELECT 1 FROM user_settings WHERE user_settings.user_id = users.id);




-- Notification settings columns
ALTER TABLE `user_settings` 
ADD COLUMN IF NOT EXISTS `habit_reminder_time` ENUM('morning', 'afternoon', 'evening', 'custom') NOT NULL DEFAULT 'morning' AFTER `level_up_notifications`,
ADD COLUMN IF NOT EXISTS `habit_reminder_custom_time` TIME DEFAULT '08:00:00' AFTER `habit_reminder_time`,
ADD COLUMN IF NOT EXISTS `goal_update_deadline` TINYINT(1) NOT NULL DEFAULT 1 AFTER `habit_reminder_custom_time`,
ADD COLUMN IF NOT EXISTS `goal_update_milestone` TINYINT(1) NOT NULL DEFAULT 1 AFTER `goal_update_deadline`,
ADD COLUMN IF NOT EXISTS `goal_update_expired` TINYINT(1) NOT NULL DEFAULT 1 AFTER `goal_update_milestone`,
ADD COLUMN IF NOT EXISTS `challenge_task_reminders` TINYINT(1) NOT NULL DEFAULT 1 AFTER `goal_update_expired`,
ADD COLUMN IF NOT EXISTS `challenge_new_participants` TINYINT(1) NOT NULL DEFAULT 1 AFTER `challenge_task_reminders`,
ADD COLUMN IF NOT EXISTS `challenge_completion` TINYINT(1) NOT NULL DEFAULT 1 AFTER `challenge_new_participants`,
ADD COLUMN IF NOT EXISTS `notification_sound` VARCHAR(20) NOT NULL DEFAULT 'default' AFTER `challenge_completion`,
ADD COLUMN IF NOT EXISTS `notification_duration` ENUM('short', 'medium', 'long') NOT NULL DEFAULT 'medium' AFTER `notification_sound`,
ADD COLUMN IF NOT EXISTS `email_time` ENUM('morning', 'afternoon', 'evening') NOT NULL DEFAULT 'morning' AFTER `email_reminders`;

-- Privacy settings columns
ALTER TABLE `user_settings` 
ADD COLUMN IF NOT EXISTS `profile_visibility` ENUM('private', 'friends', 'members', 'public') NOT NULL DEFAULT 'private' AFTER `public_profile`,
ADD COLUMN IF NOT EXISTS `show_habits` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_achievements`,
ADD COLUMN IF NOT EXISTS `show_goals` TINYINT(1) NOT NULL DEFAULT 0 AFTER `show_habits`,
ADD COLUMN IF NOT EXISTS `show_challenges` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_goals`,
ADD COLUMN IF NOT EXISTS `allow_challenge_invites` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_challenges`,
ADD COLUMN IF NOT EXISTS `show_in_leaderboards` TINYINT(1) NOT NULL DEFAULT 1 AFTER `allow_challenge_invites`,
ADD COLUMN IF NOT EXISTS `allow_friend_requests` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_in_leaderboards`,
ADD COLUMN IF NOT EXISTS `feature_improvement_consent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `analytics_consent`,
ADD COLUMN IF NOT EXISTS `data_sharing` TINYINT(1) NOT NULL DEFAULT 0 AFTER `feature_improvement_consent`;


-- Add link_data column to notifications table for storing related links
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS link_data JSON NULL AFTER message;

-- Update notification type enum to include friend type
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('habit', 'goal', 'challenge', 'xp', 'level', 'system', 'friend') NOT NULL;

-- Create user_friends table
CREATE TABLE IF NOT EXISTS user_friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, friend_id)
);

-- Create friend_requests table
CREATE TABLE IF NOT EXISTS friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (sender_id, recipient_id)
);

-- Create leaderboard_entries table for cached leaderboard data
CREATE TABLE IF NOT EXISTS leaderboard_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(20) NOT NULL,
    score INT NOT NULL,
    rank INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, category)
);

-- Create indices for performance
CREATE INDEX IF NOT EXISTS idx_friend_requests_recipient ON friend_requests(recipient_id);
CREATE INDEX IF NOT EXISTS idx_friend_requests_sender ON friend_requests(sender_id);
CREATE INDEX IF NOT EXISTS idx_user_friends_user ON user_friends(user_id);
CREATE INDEX IF NOT EXISTS idx_user_friends_friend ON user_friends(friend_id);
CREATE INDEX IF NOT EXISTS idx_leaderboard_category ON leaderboard_entries(category, rank);