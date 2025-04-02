<?php
// controllers/AnalyticsController.php - Analytics controller

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/Challenge.php';
require_once __DIR__ . '/../models/Journal.php';
require_once __DIR__ . '/../models/User.php';

class AnalyticsController {
    private $conn;
    private $habit;
    private $goal;
    private $challenge;
    private $journal;
    private $user;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->habit = new Habit($conn);
        $this->goal = new Goal($conn);
        $this->challenge = new Challenge($conn);
        $this->journal = new Journal($conn);
        $this->user = new User($conn);
    }
    
    // Get summary statistics for a user
    public function getSummaryStats($user_id) {
        $stats = [];
        
        // Get habit stats
        $query = "SELECT 
                  (SELECT COUNT(*) FROM habits WHERE user_id = :user_id) as total_habits,
                  (SELECT COUNT(*) FROM habit_completions WHERE user_id = :user_id) as total_completions,
                  (SELECT COUNT(DISTINCT DATE(completion_date)) FROM habit_completions WHERE user_id = :user_id) as unique_days,
                  (SELECT COUNT(*) FROM habit_completions WHERE user_id = :user_id AND completion_date = CURDATE()) as today_completions";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $habit_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get goal stats
        $query = "SELECT 
                  (SELECT COUNT(*) FROM goals WHERE user_id = :user_id) as total_goals,
                  (SELECT COUNT(*) FROM goals WHERE user_id = :user_id AND is_completed = 1) as completed_goals,
                  (SELECT COUNT(*) FROM goals WHERE user_id = :user_id AND is_completed = 0 AND end_date >= CURDATE()) as active_goals,
                  (SELECT COUNT(*) FROM goals WHERE user_id = :user_id AND is_completed = 0 AND end_date < CURDATE()) as missed_goals";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $goal_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get challenge stats
        $query = "SELECT 
                  (SELECT COUNT(*) FROM challenges WHERE creator_id = :user_id) as created_challenges,
                  (SELECT COUNT(*) FROM challenge_participants WHERE user_id = :user_id) as joined_challenges,
                  (SELECT COUNT(*) FROM challenge_participants WHERE user_id = :user_id AND is_completed = 1) as completed_challenges";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $challenge_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get journal stats
        $query = "SELECT 
                  COUNT(*) as total_entries,
                  MIN(entry_date) as first_entry,
                  MAX(entry_date) as latest_entry
                  FROM journal_entries 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $journal_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate average entries per week if there are entries
        $avg_entries_per_week = 0;
        if($journal_stats['total_entries'] > 0) {
            $first_date = new DateTime($journal_stats['first_entry']);
            $latest_date = new DateTime($journal_stats['latest_entry']);
            $diff = $first_date->diff($latest_date);
            $days = $diff->days + 1; // Include both first and last day
            
            $weeks = max(1, ceil($days / 7)); // At least 1 week
            $avg_entries_per_week = round($journal_stats['total_entries'] / $weeks, 1);
        }
        
        $journal_stats['avg_entries_per_week'] = $avg_entries_per_week;
        
        // Get streak information
        $longest_streak = $this->getLongestHabitStreak($user_id);
        $current_streak = $this->getCurrentHabitStreak($user_id);
        
        // Get most completed habit
        $most_completed_habit = $this->getMostCompletedHabit($user_id);
        
        // Get XP stats
        $query = "SELECT 
                  current_xp,
                  level,
                  created_at
                  FROM users 
                  WHERE id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate average XP per day
        $avg_xp_per_day = 0;
        if($user_stats) {
            $created_date = new DateTime($user_stats['created_at']);
            $today = new DateTime();
            $days = $created_date->diff($today)->days + 1; // Include today
            
            $avg_xp_per_day = round($user_stats['current_xp'] / $days, 1);
        }
        
        $user_stats['avg_xp_per_day'] = $avg_xp_per_day;
        
        // Combine all stats
        $stats = [
            'habits' => $habit_stats,
            'goals' => $goal_stats,
            'challenges' => $challenge_stats,
            'journal' => $journal_stats,
            'streaks' => [
                'longest' => $longest_streak,
                'current' => $current_streak
            ],
            'most_completed_habit' => $most_completed_habit,
            'user' => $user_stats
        ];
        
        return $stats;
    }
    
    // Get the longest habit streak
    private function getLongestHabitStreak($user_id) {
        $query = "SELECT h.id, h.title, 
                 (SELECT COUNT(*) FROM habit_completions hc WHERE hc.habit_id = h.id) as completion_count
                 FROM habits h 
                 WHERE h.user_id = :user_id
                 ORDER BY completion_count DESC
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $habit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($habit) {
            $streak = $this->habit->getStreak($habit['id']);
            
            return [
                'habit_id' => $habit['id'],
                'habit_title' => $habit['title'],
                'streak_days' => $streak
            ];
        }
        
        return [
            'habit_id' => null,
            'habit_title' => 'No habits',
            'streak_days' => 0
        ];
    }
    
    // Get the current habit streak
    private function getCurrentHabitStreak($user_id) {
        $query = "SELECT h.id, h.title
                 FROM habits h 
                 JOIN habit_completions hc ON h.id = hc.habit_id
                 WHERE h.user_id = :user_id
                 AND hc.completion_date = CURDATE()
                 GROUP BY h.id, h.title
                 ORDER BY COUNT(hc.id) DESC
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $habit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($habit) {
            $streak = $this->habit->getStreak($habit['id']);
            
            return [
                'habit_id' => $habit['id'],
                'habit_title' => $habit['title'],
                'streak_days' => $streak
            ];
        }
        
        return [
            'habit_id' => null,
            'habit_title' => 'No active streak',
            'streak_days' => 0
        ];
    }
    
    // Get the most completed habit
    private function getMostCompletedHabit($user_id) {
        $query = "SELECT h.id, h.title, COUNT(hc.id) as completion_count
                 FROM habits h 
                 LEFT JOIN habit_completions hc ON h.id = hc.habit_id
                 WHERE h.user_id = :user_id
                 GROUP BY h.id, h.title
                 ORDER BY completion_count DESC
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $habit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($habit && $habit['completion_count'] > 0) {
            return [
                'habit_id' => $habit['id'],
                'habit_title' => $habit['title'],
                'completion_count' => $habit['completion_count']
            ];
        }
        
        return [
            'habit_id' => null,
            'habit_title' => 'No completions',
            'completion_count' => 0
        ];
    }
    
    // Get habit progress over time
    public function getHabitProgressByDate($user_id, $start_date = null, $end_date = null) {
        // Set default dates if not provided
        if(!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        if(!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $query = "SELECT DATE(hc.completion_date) as date, COUNT(hc.id) as count
                 FROM habit_completions hc
                 WHERE hc.user_id = :user_id
                 AND hc.completion_date BETWEEN :start_date AND :end_date
                 GROUP BY DATE(hc.completion_date)
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart (include all dates in range)
        $formatted_data = [];
        $current_date = new DateTime($start_date);
        $last_date = new DateTime($end_date);
        
        while($current_date <= $last_date) {
            $date_str = $current_date->format('Y-m-d');
            $count = 0;
            
            // Find if we have data for this date
            foreach($results as $result) {
                if($result['date'] === $date_str) {
                    $count = $result['count'];
                    break;
                }
            }
            
            $formatted_data[] = [
                'date' => $date_str,
                'count' => $count
            ];
            
            // Move to next day
            $current_date->modify('+1 day');
        }
        
        return $formatted_data;
    }
    
    // Get habits by category
    public function getHabitsByCategory($user_id) {
        $query = "SELECT c.name as category, COUNT(h.id) as count
                 FROM habits h
                 JOIN categories c ON h.category_id = c.id
                 WHERE h.user_id = :user_id
                 GROUP BY c.name
                 ORDER BY count DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get completion rate by category
    public function getCompletionRateByCategory($user_id) {
        $query = "SELECT c.name as category,
                 COUNT(hc.id) as completed,
                 (
                    SELECT COUNT(*)
                    FROM habits h2
                    LEFT JOIN habit_completions hc2 ON h2.id = hc2.habit_id
                    WHERE h2.category_id = c.id
                    AND h2.user_id = :user_id
                 ) as total
                 FROM categories c
                 LEFT JOIN habits h ON c.id = h.category_id
                 LEFT JOIN habit_completions hc ON h.id = hc.habit_id
                 WHERE h.user_id = :user_id
                 GROUP BY c.name
                 ORDER BY completed DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate completion rate
        foreach($results as &$result) {
            $result['completion_rate'] = $result['total'] > 0 ? 
                                       round(($result['completed'] / $result['total']) * 100, 1) : 0;
        }
        
        return $results;
    }
    
    // Get mood distribution over time
    public function getMoodDistributionOverTime($user_id, $start_date = null, $end_date = null) {
        // Set default dates if not provided
        if(!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        
        if(!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $query = "SELECT DATE(entry_date) as date, mood, COUNT(*) as count
                 FROM journal_entries
                 WHERE user_id = :user_id
                 AND entry_date BETWEEN :start_date AND :end_date
                 GROUP BY DATE(entry_date), mood
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $formatted_data = [];
        foreach($results as $result) {
            if(!isset($formatted_data[$result['date']])) {
                $formatted_data[$result['date']] = [
                    'date' => $result['date'],
                    'happy' => 0,
                    'motivated' => 0,
                    'neutral' => 0,
                    'tired' => 0,
                    'frustrated' => 0,
                    'sad' => 0
                ];
            }
            
            $formatted_data[$result['date']][$result['mood']] += $result['count'];
        }
        
        return array_values($formatted_data);
    }
    
    // Get XP progress over time
    public function getXPProgressOverTime($user_id) {
        // We don't have XP history stored, so we'll simulate it from habit completions and goals
        
        // Get habit XP - group by date
        $query = "SELECT DATE(hc.completion_date) as date, COUNT(hc.id) * 10 as xp
                 FROM habit_completions hc
                 WHERE hc.user_id = :user_id
                 GROUP BY DATE(hc.completion_date)
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $habit_xp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get goal XP - completed goals
        $query = "SELECT DATE(updated_at) as date, xp_reward as xp
                 FROM goals
                 WHERE user_id = :user_id
                 AND is_completed = 1
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $goal_xp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine the XP data
        $combined_xp = [];
        
        // Add habit XP
        foreach($habit_xp as $item) {
            if(!isset($combined_xp[$item['date']])) {
                $combined_xp[$item['date']] = 0;
            }
            
            $combined_xp[$item['date']] += $item['xp'];
        }
        
        // Add goal XP
        foreach($goal_xp as $item) {
            if(!isset($combined_xp[$item['date']])) {
                $combined_xp[$item['date']] = 0;
            }
            
            $combined_xp[$item['date']] += $item['xp'];
        }
        
        // Format data for cumulative chart
        $formatted_data = [];
        $cumulative_xp = 0;
        
        ksort($combined_xp); // Sort by date
        
        foreach($combined_xp as $date => $xp) {
            $cumulative_xp += $xp;
            
            $formatted_data[] = [
                'date' => $date,
                'daily_xp' => $xp,
                'cumulative_xp' => $cumulative_xp
            ];
        }
        
        return $formatted_data;
    }
    
    // Get level distribution among users
    public function getLevelDistribution() {
        $query = "SELECT level, COUNT(*) as count
                 FROM users
                 GROUP BY level
                 ORDER BY level ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}