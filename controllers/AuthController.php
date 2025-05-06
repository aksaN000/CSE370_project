<?php
// controllers/AuthController.php - Authentication controller
// Start session
// In controllers/AuthController.php around line 5
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include database and user model
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/NotificationController.php'; // Include NotificationController
require_once __DIR__ . '/SettingsController.php';
class AuthController {
    private $conn;
    private $user;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->user = new User($conn);
    }
     // Update user profile
    public function updateProfile($user_id, $username, $email, $current_password, $new_password = '', $confirm_password = '') {
        $errors = [];
        
        // Validate username
        if(empty($username)) {
            $errors[] = 'Username is required';
        } elseif(strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        // Validate email
        if(empty($email)) {
            $errors[] = 'Email is required';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Validate current password
        if(empty($current_password)) {
            $errors[] = 'Current password is required to confirm changes';
        }
        
        // Validate new password if provided
        if(!empty($new_password)) {
            if(strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            }
            
            if($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match';
            }
        }
        
        // Process profile picture upload if present
        $profile_picture = null;
        if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            // Validate file size (max 2MB)
            if($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile picture must be less than 2MB';
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_info = getimagesize($_FILES['profile_picture']['tmp_name']);
            
            if(!$file_info || !in_array($file_info['mime'], $allowed_types)) {
                $errors[] = 'Only JPG, PNG, and GIF files are allowed';
            }
            
            if(empty($errors)) {
                // Generate unique filename
                $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_path = __DIR__ . '/../assets/uploads/profile_pictures/' . $filename;
                
                // Move uploaded file
                if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    $profile_picture = $filename;
                } else {
                    $errors[] = 'Failed to upload profile picture';
                }
            }
        }
        
        // If no errors, update profile
        if(empty($errors)) {
            // Get user
            $this->user->id = $user_id;
            if($this->user->getUserById($user_id)) {
                // Verify current password
                if(password_verify($current_password, $this->user->password)) {
                    // Set user properties
                    $this->user->username = $username;
                    $this->user->email = $email;
                    
                    // Set profile picture if uploaded
                    if($profile_picture) {
                        $this->user->profile_picture = $profile_picture;
                    }
                    
                    // Try to update profile
                    if($this->user->updateProfile($username, $email, $profile_picture)) {
                        // Update session variables
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        if($profile_picture) {
                            $_SESSION['profile_picture'] = $profile_picture;
                        }
                        
                        // Try to update password if provided
                        if(!empty($new_password)) {
                            if($this->user->updatePassword($current_password, $new_password)) {
                                return ['success' => true, 'message' => 'Profile and password updated successfully', 'profile_picture' => $profile_picture];
                            } else {
                                return ['success' => false, 'message' => 'Profile updated but failed to update password'];
                            }
                        }
                        
                        return ['success' => true, 'message' => 'Profile updated successfully', 'profile_picture' => $profile_picture];
                    } else {
                        return ['success' => false, 'message' => 'Failed to update profile. Email or username might already exist.'];
                    }
                } else {
                    return ['success' => false, 'message' => 'Current password is incorrect'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
        } else {
            return ['success' => false, 'errors' => $errors, 'message' => implode(', ', $errors)];
        }
    }
    // Register a new user
    public function register($username, $email, $password, $confirm_password) {
        $errors = [];
        
        // Validate username
        if(empty($username)) {
            $errors[] = 'Username is required';
        } elseif(strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        }
        
        // Validate email
        if(empty($email)) {
            $errors[] = 'Email is required';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Validate password
        if(empty($password)) {
            $errors[] = 'Password is required';
        } elseif(strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        // Validate confirm password
        if($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, register user
        if(empty($errors)) {
            // Set user properties
            $this->user->username = $username;
            $this->user->email = $email;
            $this->user->password = $password;
            
            // Try to register
            if($user_id = $this->user->register()) {
                // Set the user ID
                $this->user->id = $user_id;
    
                // Set default theme settings for the new user
                $settingsController = new SettingsController();
                $defaultSettings = [
                    'theme' => 'light',
                    'color_scheme' => 'default',
                    'enable_animations' => 1,
                    'compact_mode' => 0
                ];
                $settingsController->createDefaultSettings($this->user->id, $defaultSettings);
                
                // Set the theme in session and cookies
                $_SESSION['user_theme'] = 'light';
                $_SESSION['color_scheme'] = 'default';
                $_SESSION['enable_animations'] = 1;
                $_SESSION['compact_mode'] = 0;
                
                setcookie('user_theme', 'light', time() + (86400 * 365), "/"); // 1-year cookie
                setcookie('color_scheme', 'default', time() + (86400 * 365), "/");
                setcookie('enable_animations', 1, time() + (86400 * 365), "/");
                setcookie('compact_mode', 0, time() + (86400 * 365), "/");
                
                // Login after registration
                if($this->login($email, $password)) {
                    return ['success' => true, 'message' => 'Registration successful'];
                } else {
                    return ['success' => false, 'message' => 'Registration successful but login failed. Please log in manually.'];
                }
            } else {
                return ['success' => false, 'message' => 'Registration failed. Email or username might already exist.'];
            }
        } else {
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    public function login($email, $password) {
        $errors = [];
        
        // Validate email
        if(empty($email)) {
            $errors[] = 'Email is required';
        }
        
        // Validate password
        if(empty($password)) {
            $errors[] = 'Password is required';
        }
        
        // If no errors, try to login
        if(empty($errors)) {
            // Set user properties
            $this->user->email = $email;
            $this->user->password = $password;
            
            // Try to login
            if($this->user->login()) {
                // Create session variables
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['username'] = $this->user->username;
                $_SESSION['email'] = $this->user->email;
                $_SESSION['level'] = $this->user->level;
                $_SESSION['logged_in'] = true;
                
                // Add these lines:
                require_once __DIR__ . '/../utils/BadgeSystem.php';
                $badgeSystem = new BadgeSystem();
                $badgeSystem->ensureBasicAchievements($this->user->id);
                
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
        } else {
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    // Logout a user
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Get logged in user
    public function getLoggedInUser() {
        if($this->isLoggedIn()) {
            $this->user->getUserById($_SESSION['user_id']);
            return $this->user;
        }
        return null;
    }
    
    // Get unread notifications for a user
    public function getUnreadNotifications($user_id, $limit = 100) {
        $notificationController = new NotificationController();
        return $notificationController->getUnreadNotifications($user_id, $limit);
    }
}