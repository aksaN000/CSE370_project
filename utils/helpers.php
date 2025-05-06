<?php
/**
 * // utils/BadgeSystem.phps
 * Helper Functions
 * 
 * A collection of utility functions used throughout the application
 */

/**
 * Format a date to a readable string
 * 
 * @param string $date The date string to format
 * @param string $format The format to use (default: 'M j, Y')
 * @return string The formatted date
 */
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Calculate days remaining until a specific date
 * 
 * @param string $end_date The end date
 * @return int Number of days remaining
 */
function daysRemaining($end_date) {
    $today = new DateTime();
    $end = new DateTime($end_date);
    
    if($today > $end) {
        return 0;
    }
    
    return $today->diff($end)->days;
}

/**
 * Calculate days passed since a specific date
 * 
 * @param string $start_date The start date
 * @return int Number of days passed
 */
function daysPassed($start_date) {
    $today = new DateTime();
    $start = new DateTime($start_date);
    
    if($today < $start) {
        return 0;
    }
    
    return $start->diff($today)->days;
}

/**
 * Format a number as a percentage
 * 
 * @param int|float $value The value
 * @param int|float $total The total
 * @param int $decimals Number of decimal places (default: 1)
 * @return string The formatted percentage
 */
function formatPercentage($value, $total, $decimals = 1) {
    if($total == 0) {
        return '0%';
    }
    
    return round(($value / $total) * 100, $decimals) . '%';
}

/**
 * Format a timestamp as time ago
 * 
 * @param string $timestamp The timestamp
 * @return string Time ago string
 */
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if($diff < 60) {
        return 'just now';
    }
    
    $intervals = [
        1 => ['year', 31536000],
        2 => ['month', 2592000],
        3 => ['week', 604800],
        4 => ['day', 86400],
        5 => ['hour', 3600],
        6 => ['minute', 60]
    ];
    
    foreach($intervals as $interval) {
        $division = floor($diff / $interval[1]);
        
        if($division >= 1) {
            $time_ago = $division == 1 ? '1 ' . $interval[0] : $division . ' ' . $interval[0] . 's';
            return $time_ago . ' ago';
        }
    }
}

/**
 * Truncate text to a specific length
 * 
 * @param string $text The text to truncate
 * @param int $length The maximum length
 * @param string $append String to append if truncated (default: '...')
 * @return string The truncated text
 */
function truncateText($text, $length, $append = '...') {
    if(strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Get the first sentence of a text
 * 
 * @param string $text The text
 * @return string The first sentence
 */
function getFirstSentence($text) {
    $sentences = preg_split('/(?<=[.!?])\s+/', $text, 2);
    return $sentences[0];
}

/**
 * Generate a random color
 * 
 * @return string HEX color code
 */
function randomColor() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

/**
 * Convert a date to ISO format
 * 
 * @param string $date The date string
 * @return string ISO formatted date
 */
function toISODate($date) {
    return date('Y-m-d', strtotime($date));
}

/**
 * Get the current time zone
 * 
 * @return string The current time zone
 */
function getCurrentTimezone() {
    return date_default_timezone_get();
}

/**
 * Format a number with suffix (1st, 2nd, 3rd, etc.)
 * 
 * @param int $number The number
 * @return string The formatted number
 */
function ordinalNumber($number) {
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    
    if(($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    }
    
    return $number . $ends[$number % 10];
}

/**
 * Encrypt a string
 * 
 * @param string $string The string to encrypt
 * @param string $key The encryption key
 * @return string The encrypted string
 */
function encryptString($string, $key) {
    $method = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($string, $method, $key, 0, $iv);
    
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypt a string
 * 
 * @param string $encrypted The encrypted string
 * @param string $key The encryption key
 * @return string The decrypted string
 */
function decryptString($encrypted, $key) {
    $method = 'AES-256-CBC';
    list($encrypted_data, $iv) = explode('::', base64_decode($encrypted), 2);
    
    return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
}

/**
 * Generate a random token
 * 
 * @param int $length The token length
 * @return string The random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Convert bytes to human-readable format
 * 
 * @param int $bytes The number of bytes
 * @param int $precision The precision (default: 2)
 * @return string Human-readable size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get the level info for a specific level
 * 
 * @param int $level The level number
 * @param PDO $conn Database connection
 * @return array|bool Level info or false if not found
 */
function getLevelInfo($level, $conn) {
    $query = "SELECT * FROM levels WHERE level_number = :level LIMIT 0,1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':level', $level);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Calculate the XP required for the next level
 * 
 * @param int $current_level The current level
 * @param PDO $conn Database connection
 * @return int|bool XP required or false if not found
 */
function getNextLevelXP($current_level, $conn) {
    $query = "SELECT xp_required FROM levels WHERE level_number = :level + 1 LIMIT 0,1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':level', $current_level);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['xp_required'] : false;
}

/**
 * Calculate the XP progress percentage
 * 
 * @param int $current_xp Current XP
 * @param int $current_level Current level
 * @param PDO $conn Database connection
 * @return float Progress percentage
 */
function calculateXPProgress($current_xp, $current_level, $conn) {
    $current_level_info = getLevelInfo($current_level, $conn);
    $next_level_xp = getNextLevelXP($current_level, $conn);
    
    if(!$current_level_info || !$next_level_xp) {
        return 100; // Max level or error
    }
    
    $current_level_xp = $current_level_info['xp_required'];
    $xp_for_current_level = $current_xp - $current_level_xp;
    $xp_needed_for_next_level = $next_level_xp - $current_level_xp;
    
    return min(100, max(0, ($xp_for_current_level / $xp_needed_for_next_level) * 100));
}

/**
 * Get the icon for a notification type
 * 
 * @param string $type The notification type
 * @return array Icon and color information
 */
function getNotificationIcon($type) {
    switch($type) {
        case 'habit':
            return ['icon' => 'check-circle', 'color' => 'success'];
        case 'goal':
            return ['icon' => 'trophy', 'color' => 'warning'];
        case 'challenge':
            return ['icon' => 'people', 'color' => 'danger'];
        case 'xp':
            return ['icon' => 'lightning', 'color' => 'primary'];
        case 'level':
            return ['icon' => 'arrow-up-circle', 'color' => 'success'];
        case 'system':
            return ['icon' => 'info-circle', 'color' => 'info'];
        default:
            return ['icon' => 'bell', 'color' => 'secondary'];
    }
}

/**
 * Get the icon for a mood
 * 
 * @param string $mood The mood
 * @return array Icon and color information
 */
function getMoodIcon($mood) {
    switch($mood) {
        case 'happy':
            return ['icon' => 'emoji-smile', 'color' => 'warning'];
        case 'motivated':
            return ['icon' => 'emoji-laughing', 'color' => 'success'];
        case 'neutral':
            return ['icon' => 'emoji-neutral', 'color' => 'secondary'];
        case 'tired':
            return ['icon' => 'emoji-expressionless', 'color' => 'info'];
        case 'frustrated':
            return ['icon' => 'emoji-frown', 'color' => 'danger'];
        case 'sad':
            return ['icon' => 'emoji-tear', 'color' => 'primary'];
        default:
            return ['icon' => 'emoji-neutral', 'color' => 'secondary'];
    }
}

/**
 * Check if a date is today
 * 
 * @param string $date The date to check
 * @return bool True if the date is today
 */
function isToday($date) {
    return date('Y-m-d', strtotime($date)) === date('Y-m-d');
}

/**
 * Check if a date is in the past
 * 
 * @param string $date The date to check
 * @return bool True if the date is in the past
 */
function isPast($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

/**
 * Check if a date is in the future
 * 
 * @param string $date The date to check
 * @return bool True if the date is in the future
 */
function isFuture($date) {
    return strtotime($date) > strtotime(date('Y-m-d'));
}

/**
 * Format a streak number with text
 * 
 * @param int $streak The streak number
 * @return string Formatted streak text
 */
function formatStreak($streak) {
    if($streak <= 0) {
        return "No streak";
    } elseif($streak == 1) {
        return "1 day streak";
    } else {
        return "$streak day streak";
    }
}

/**
 * Generate pagination links
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $url_pattern URL pattern with {page} placeholder
 * @return string HTML pagination links
 */
function generatePagination($current_page, $total_pages, $url_pattern) {
    if($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Pagination"><ul class="pagination">';
    
    // Previous button
    $html .= '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . ($current_page > 1 ? str_replace('{page}', $current_page - 1, $url_pattern) : '#') . '" aria-label="Previous">';
    $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
    
    // Page numbers
    $range = 2; // Show 2 pages before and after current page
    
    for($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
        $html .= '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="' . str_replace('{page}', $i, $url_pattern) . '">' . $i . '</a>';
        $html .= '</li>';
    }
    
    // Next button
    $html .= '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . ($current_page < $total_pages ? str_replace('{page}', $current_page + 1, $url_pattern) : '#') . '" aria-label="Next">';
    $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}