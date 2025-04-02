<!-- views/partials/sidebar.php -->
<?php
// Determine the root path dynamically
$root_path = realpath(dirname(__FILE__) . '/../../');

// Include necessary files with absolute paths
require_once $root_path . '/utils/helpers.php';
require_once $root_path . '/controllers/AuthController.php';

// Get theme settings
$current_theme = $_SESSION['user_theme'] ?? 'light';
$color_scheme = $_SESSION['color_scheme'] ?? 'default';
$compact_mode = $_SESSION['compact_mode'] ?? 0;

// Determine sidebar classes based on theme
$sidebar_classes = 'sidebar';
$sidebar_bg_class = 'bg-light';
$sidebar_text_class = '';

if($current_theme === 'dark' || ($current_theme === 'system' && isset($_COOKIE['system_dark']) && $_COOKIE['system_dark'] === 'true')) {
    $sidebar_bg_class = 'bg-dark';
    $sidebar_text_class = 'text-white';
}

// Add compact mode class if enabled
if($compact_mode) {
    $sidebar_classes .= ' sidebar-compact';
}

// Get current user's XP information
$authController = new AuthController();
$user = $authController->getLoggedInUser();

// Calculate XP progress
$xp_progress_percentage = 0;
$xp_for_current_level = 0;
$xp_needed_for_next_level = 0;
$current_level = 0;

if($user) {
    $current_level = $user->level;
    $current_xp = $user->current_xp;
    
    // Get current and next level information
    $level_info = getLevelInfo($current_level, $GLOBALS['conn']);
    $next_level_xp = getNextLevelXP($current_level, $GLOBALS['conn']);
    
    // Calculate XP progress percentage
    if($next_level_xp) {
        $current_level_xp = $level_info['xp_required'];
        $xp_for_current_level = $current_xp - $current_level_xp;
        $xp_needed_for_next_level = $next_level_xp - $current_level_xp;
        $xp_progress_percentage = min(100, max(0, ($xp_for_current_level / $xp_needed_for_next_level) * 100));
    } else {
        $xp_progress_percentage = 100; // Max level
        $xp_for_current_level = $current_xp;
        $xp_needed_for_next_level = $current_xp;
    }
}
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block <?php echo $sidebar_bg_class; ?> sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? '../index.php' : 'index.php'; ?>">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'habits.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'habits.php' : 'views/habits.php'; ?>">
                    <i class="bi bi-check-circle"></i>
                    My Habits
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'goals.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'goals.php' : 'views/goals.php'; ?>">
                    <i class="bi bi-trophy"></i>
                    Goals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'challenges.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'challenges.php' : 'views/challenges.php'; ?>">
                    <i class="bi bi-people"></i>
                    Challenges
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'community.php' ? 'active' : ''; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'community.php' : 'views/community.php'; ?>">
                    <i class="bi bi-people"></i>
                    Community
                    <?php
                    // Get friend request count
                    if(isset($_SESSION['user_id'])) {
                        $user_id = $_SESSION['user_id'];
                        $query = "SELECT COUNT(*) as count FROM friend_requests WHERE recipient_id = :user_id";
                        $stmt = $GLOBALS['conn']->prepare($query);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if($result && $result['count'] > 0):
                    ?>
                        <span class="badge rounded-pill bg-danger float-end"><?php echo $result['count']; ?></span>
                    <?php
                        endif;
                    }
                    ?>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'journal.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'journal.php' : 'views/journal.php'; ?>">
                    <i class="bi bi-journal-text"></i>
                    Journal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'analytics.php' : 'views/analytics.php'; ?>">
                    <i class="bi bi-graph-up"></i>
                    Analytics
                </a>
            </li>
        </ul>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 <?php echo $sidebar_text_class; ?> text-muted">
            <span>Quick Actions</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $sidebar_text_class; ?>" href="#" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                    <i class="bi bi-plus-circle"></i>
                    Add New Habit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $sidebar_text_class; ?>" href="#" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                    <i class="bi bi-plus-circle"></i>
                    Add New Goal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'challenges.php?action=join' : 'views/challenges.php?action=join'; ?>">
                    <i class="bi bi-plus-circle"></i>
                    Join Challenge
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'journal.php?action=new' : 'views/journal.php?action=new'; ?>">
                    <i class="bi bi-plus-circle"></i>
                    New Journal Entry
                </a>
            </li>
        </ul>
        
        <?php if($user): ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 <?php echo $sidebar_text_class; ?> text-muted">
                <span>Your Progress</span>
            </h6>
            <div class="px-3 py-2">
                <div class="mb-2">
                    <small class="<?php echo $sidebar_text_class; ?> text-muted">Level <?php echo $current_level; ?></small>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $xp_progress_percentage; ?>%" aria-valuenow="<?php echo $xp_progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <small class="<?php echo $sidebar_text_class; ?> text-muted">
                    <?php echo $xp_for_current_level; ?> / <?php echo $xp_needed_for_next_level; ?> XP to Level <?php echo $current_level + 1; ?>
                </small>
            </div>
        <?php endif; ?>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 <?php echo $sidebar_text_class; ?> text-muted">
            <span>Account</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'profile.php' : 'views/profile.php'; ?>">
                    <i class="bi bi-person"></i>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'achievements.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'achievements.php' : 'views/achievements.php'; ?>">
                    <i class="bi bi-award"></i>
                    Achievements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?> <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? 'settings.php' : 'views/settings.php'; ?>">
                    <i class="bi bi-gear"></i>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $sidebar_text_class; ?>" href="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) === 'views') ? '../controllers/process_logout.php' : 'controllers/process_logout.php'; ?>">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>