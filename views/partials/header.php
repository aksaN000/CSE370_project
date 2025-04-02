<?php
// Load user theme settings from database if not in session
if(isset($_SESSION['user_id']) && (!isset($_SESSION['user_theme']) || !isset($_SESSION['color_scheme']))) {
    require_once __DIR__ . '/../../controllers/SettingsController.php';
    $settingsController = new SettingsController();
    $userSettings = $settingsController->getUserSettings($_SESSION['user_id']);
    
    $_SESSION['user_theme'] = $userSettings['theme'] ?? 'light';
    $_SESSION['color_scheme'] = $userSettings['color_scheme'] ?? 'default';
    $_SESSION['enable_animations'] = $userSettings['enable_animations'] ?? 1;
    $_SESSION['compact_mode'] = $userSettings['compact_mode'] ?? 0;
}

// Apply theme to HTML element
$current_theme = $_SESSION['user_theme'] ?? 'light';
$color_scheme = $_SESSION['color_scheme'] ?? 'default';

$html_theme = 'light';
if($current_theme === 'dark') {
    $html_theme = 'dark';
} elseif($current_theme === 'system') {
    $system_dark = isset($_COOKIE['system_dark']) && $_COOKIE['system_dark'] === 'true';
    $html_theme = $system_dark ? 'dark' : 'light';
}
// Initialize settings controller if needed and not already initialized
if(!isset($settingsController) && isset($user) && $user) {
    require_once __DIR__ . '/../../controllers/SettingsController.php';
    $settingsController = new SettingsController();
}

// Get unread notifications if user is logged in
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] && !isset($unreadNotifications)) {
    require_once __DIR__ . '/../../controllers/NotificationController.php';
    $notificationController = new NotificationController();
    $unreadNotifications = $notificationController->getUnreadNotifications($_SESSION['user_id'], 5);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo $html_theme; ?>" class="color-<?php echo $color_scheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habit Tracker - Transform Your Life</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : ''; ?>assets/css/style.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : ''; ?>assets/css/theme.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : ''; ?>assets/images/favicon.png" type="image/png">
    
    <?php
    // Set the theme based on user preference
    $theme = 'light'; // Default theme
    if(isset($settingsController) && isset($user)) {
        $userSettings = $settingsController->getUserSettings($user->id);
        if(isset($userSettings['theme'])) {
            $theme = $userSettings['theme'];
        }
    }
    ?>
    <script>
        // Set initial theme before page load to prevent flashing
        (function() {
            let storedTheme = localStorage.getItem('habit-tracker-theme') || '<?php echo $theme; ?>';
            
            if(storedTheme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            } else if(storedTheme === 'light') {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            } else if(storedTheme === 'system') {
                // Check system preference
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
            }
        })();
    </script>
</head>
<body class="<?php 
    $theme = $_SESSION['user_theme'] ?? 'light';
    if($theme === 'system') {
        $theme = (isset($_COOKIE['system_dark']) && $_COOKIE['system_dark'] === 'true') ? 'dark' : 'light';
    }
    echo 'data-custom-theme="' . $theme . '"';
    // Apply theme classes based on user settings
    $themeClasses = [];
    
    // Add color scheme class
    if(isset($settingsController) && isset($user)) {
        $userSettings = $settingsController->getUserSettings($user->id);
        
        // Add color scheme class
        if(isset($userSettings['color_scheme'])) {
            $themeClasses[] = 'color-' . $userSettings['color_scheme'];
        } else {
            $themeClasses[] = 'color-default';
        }
        
        // Add animations class
        if(isset($userSettings['enable_animations']) && $userSettings['enable_animations']) {
            $themeClasses[] = 'enable-animations';
        }
        
        // Add compact mode class
        if(isset($userSettings['compact_mode']) && $userSettings['compact_mode']) {
            $themeClasses[] = 'compact-mode';
        }
    } else {
        $themeClasses[] = 'color-default';
    }
    
    echo implode(' ', $themeClasses);
?>">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../index.php' : 'index.php'; ?>">
                <img src="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../assets/images/logo.png' : 'assets/images/logo.png'; ?>" alt="Habit Tracker Logo" height="30">
                Habit Tracker
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../index.php' : 'index.php'; ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'habits.php' : 'views/habits.php'; ?>">
                            <i class="bi bi-check-circle"></i> Habits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'goals.php' : 'views/goals.php'; ?>">
                            <i class="bi bi-trophy"></i> Goals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'challenges.php' : 'views/challenges.php'; ?>">
                            <i class="bi bi-people"></i> Challenges
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'journal.php' : 'views/journal.php'; ?>">
                            <i class="bi bi-journal-text"></i> Journal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'analytics.php' : 'views/analytics.php'; ?>">
                            <i class="bi bi-graph-up"></i> Analytics
                        </a>
                    </li>
                </ul>
                
                <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                    <div class="d-flex align-items-center">
                        <!-- Notifications Dropdown -->
                        <div class="dropdown me-3">
                            <a class="btn btn-outline-light position-relative" href="#" role="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell-fill"></i>
                                <?php
                                // Get unread notification count
                                $unreadCount = isset($unreadNotifications) ? count($unreadNotifications) : 0;
                                if($unreadCount > 0):
                                ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unreadCount; ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="width: 300px;">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php if(isset($unreadNotifications) && !empty($unreadNotifications)): ?>
                                    <?php foreach($unreadNotifications as $notification): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../controllers/mark_notification_read.php?id=' . $notification['id'] : 'controllers/mark_notification_read.php?id=' . $notification['id']; ?>">
                                                <div class="d-flex">
                                                    <?php
                                                    $icon = 'info-circle';
                                                    $color = 'info';
                                                    switch($notification['type']) {
                                                        case 'habit':
                                                            $icon = 'check-circle';
                                                            $color = 'success';
                                                            break;
                                                        case 'goal':
                                                            $icon = 'trophy';
                                                            $color = 'warning';
                                                            break;
                                                        case 'challenge':
                                                            $icon = 'people';
                                                            $color = 'danger';
                                                            break;
                                                        case 'xp':
                                                            $icon = 'lightning';
                                                            $color = 'primary';
                                                            break;
                                                        case 'level':
                                                            $icon = 'arrow-up-circle';
                                                            $color = 'success';
                                                            break;
                                                    }
                                                    ?>
                                                    <div class="me-3">
                                                        <i class="bi bi-<?php echo $icon; ?>-fill text-<?php echo $color; ?> fs-4"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo $notification['title']; ?></strong>
                                                        <p class="mb-0 small"><?php echo $notification['message']; ?></p>
                                                        <small class="text-muted"><?php echo date('M d, g:i a', strtotime($notification['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><div class="dropdown-item text-center">No new notifications</div></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-center" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'notifications.php' : 'views/notifications.php'; ?>">See all notifications</a></li>
                            </ul>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'profile.php' : 'views/profile.php'; ?>"><i class="bi bi-person"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'achievements.php' : 'views/achievements.php'; ?>"><i class="bi bi-award"></i> Achievements</a></li>
                                <li><a class="dropdown-item" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'settings.php' : 'views/settings.php'; ?>"><i class="bi bi-gear"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../controllers/process_logout.php' : 'controllers/process_logout.php'; ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex">
                        <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'auth/login.php' : 'views/auth/login.php'; ?>" class="btn btn-outline-light me-2">Login</a>
                        <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/views/') !== false ? 'auth/register.php' : 'views/auth/register.php'; ?>" class="btn btn-light">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>