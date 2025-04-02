<?php
// views/profile.php - User profile page

// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/HabitController.php';
require_once __DIR__ . '/../controllers/GoalController.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';
require_once __DIR__ . '/../utils/helpers.php';


// This detects if a notification was clicked and marks it as read

require_once __DIR__ . '/../utils/notification_handler.php';

// Handle notification read marking - if it returns true, a redirect was performed and we should exit
if(handleNotificationReadMarking()) {
    exit;
}


$authController = new AuthController();

// Redirect if not logged in
if(!$authController->isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Get logged in user
$user = $authController->getLoggedInUser();

// Initialize controllers
$habitController = new HabitController();
$goalController = new GoalController();
$challengeController = new ChallengeController();

// Get user statistics
$total_habits = count($habitController->getAllHabits($user->id));
$habit_completions = $habitController->getTotalCompletions($user->id);
$total_goals = $goalController->getTotalGoals($user->id);
$completed_goals = $goalController->getCompletedGoals($user->id);
$joined_challenges = count($challengeController->getActiveChallenges($user->id)) + count($challengeController->getCompletedChallenges($user->id));
$streak_info = $habitController->getLongestStreak($user->id);

// Get user level info
$level_info = getLevelInfo($user->level, $GLOBALS['conn']);
$next_level_xp = getNextLevelXP($user->level, $GLOBALS['conn']);
$xp_progress = calculateXPProgress($user->current_xp, $user->level, $GLOBALS['conn']);

// Process profile update
$update_message = '';
$update_success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Update profile logic
    $result = $authController->updateProfile($user->id, $username, $email, $current_password, $new_password, $confirm_password);
    
    $update_success = $result['success'];
    $update_message = $result['message'];
    
    if($update_success) {
        // Refresh user data
        $user = $authController->getLoggedInUser();
    }
}

// Include header
include '../views/partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../views/partials/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Profile Header -->
            <div class="profile-header text-center mb-4">
                <div class="container">
                    <div class="mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user->username); ?>&background=random&size=150" alt="Profile Avatar" class="profile-avatar">
                    </div>
                    <h1 class="display-5 fw-bold"><?php echo $user->username; ?></h1>
                    <p class="lead">Member since <?php echo formatDate($user->created_at, 'F j, Y'); ?></p>
                    <div class="d-inline-flex align-items-center mb-3">
                        <div class="me-3">
                            <span class="fw-bold">Level <?php echo $user->level; ?></span>
                            <span class="ms-1 badge bg-warning text-dark"><?php echo $level_info['title']; ?></span>
                        </div>
                        <div style="width: 200px;">
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $xp_progress; ?>%;" aria-valuenow="<?php echo $xp_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-light">
                                <?php echo ($user->current_xp - $level_info['xp_required']); ?> / <?php echo ($next_level_xp - $level_info['xp_required']); ?> XP to Level <?php echo $user->level + 1; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Tabs -->
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">Overview</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile" type="button" role="tab" aria-controls="edit-profile" aria-selected="false">Edit Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-settings-tab" data-bs-toggle="tab" data-bs-target="#account-settings" type="button" role="tab" aria-controls="account-settings" aria-selected="false">Account Settings</button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="profileTabsContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <div class="row">
                        <!-- Stats Cards -->
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Habit Stats</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-check-circle-fill text-primary fs-3"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Total Habits</h6>
                                                    <h4><?php echo $total_habits; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-check-all text-success fs-3"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Completions</h6>
                                                    <h4><?php echo $habit_completions; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body py-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <i class="bi bi-fire text-danger fs-3"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Longest Streak</h6>
                                                            <div class="d-flex align-items-baseline">
                                                                <h4 class="mb-0 me-2"><?php echo $streak_info['streak_days']; ?> days</h4>
                                                                <small class="text-muted"><?php echo $streak_info['habit_title']; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">Goal Stats</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-trophy-fill text-warning fs-3"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Total Goals</h6>
                                                    <h4><?php echo $total_goals; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Completed</h6>
                                                    <h4><?php echo $completed_goals; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body py-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <i class="bi bi-graph-up text-success fs-3"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Completion Rate</h6>
                                                            <div class="d-flex align-items-baseline">
                                                                <h4 class="mb-0 me-2"><?php echo $total_goals > 0 ? round(($completed_goals / $total_goals) * 100) : 0; ?>%</h4>
                                                                <small class="text-muted"><?php echo $completed_goals; ?>/<?php echo $total_goals; ?> goals completed</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">XP Stats</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-lightning-fill text-warning fs-3"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Total XP</h6>
                                                    <h4><?php echo $user->current_xp; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-people-fill text-danger fs-3"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Challenges</h6>
                                                    <h4><?php echo $joined_challenges; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body py-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2">
                                                            <i class="bi bi-award-fill text-primary fs-3"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">Current Badge</h6>
                                                            <div class="d-flex align-items-baseline">
                                                                <h4 class="mb-0 me-2"><?php echo $level_info['badge_name']; ?></h4>
                                                                <small class="text-muted">Level <?php echo $user->level; ?> achievement</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            // Get recent activity from database (habit completions, goal updates, etc.)
                            $query = "SELECT 'habit' as type, h.title, hc.completion_date as date, 'Completed habit' as action 
                                     FROM habit_completions hc 
                                     JOIN habits h ON hc.habit_id = h.id 
                                     WHERE hc.user_id = :user_id
                                     UNION
                                     SELECT 'goal' as type, g.title, g.updated_at as date, 'Updated goal progress' as action 
                                     FROM goals g
                                     WHERE g.user_id = :user_id AND g.is_completed = 0
                                     UNION
                                     SELECT 'goal' as type, g.title, g.updated_at as date, 'Completed goal' as action 
                                     FROM goals g
                                     WHERE g.user_id = :user_id AND g.is_completed = 1
                                     ORDER BY date DESC
                                     LIMIT 10";
                            
                            $stmt = $GLOBALS['conn']->prepare($query);
                            $stmt->bindParam(':user_id', $user->id);
                            $stmt->execute();
                            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php if(empty($activities)): ?>
                                <div class="p-4 text-center">
                                    <p class="mb-0 text-muted">No recent activity to show.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach($activities as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-center">
                                                <?php if($activity['type'] === 'habit'): ?>
                                                    <div class="me-3">
                                                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                                    </div>
                                                <?php elseif($activity['type'] === 'goal'): ?>
                                                    <div class="me-3">
                                                        <i class="bi bi-trophy-fill text-warning fs-4"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <p class="mb-1"><strong><?php echo $activity['action']; ?>:</strong> <?php echo $activity['title']; ?></p>
                                                    <small class="text-muted"><?php echo timeAgo($activity['date']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade" id="edit-profile" role="tabpanel" aria-labelledby="edit-profile-tab">
                    <?php if($update_message): ?>
                        <div class="alert alert-<?php echo $update_success ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $update_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Edit Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $user->username; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user->email; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter your current password to confirm changes" required>
                                </div>
                                
                                <h5 class="mt-4 mb-3">Change Password (Optional)</h5>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Account Settings Tab -->
                <div class="tab-pane fade" id="account-settings" role="tabpanel" aria-labelledby="account-settings-tab">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Notification Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form action="profile.php" method="POST">
                                        <input type="hidden" name="update_notifications" value="1">
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="emailNotifications" checked>
                                            <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="habitReminders" checked>
                                            <label class="form-check-label" for="habitReminders">Daily Habit Reminders</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="goalUpdates" checked>
                                            <label class="form-check-label" for="goalUpdates">Goal Updates</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="challengeNotifications" checked>
                                            <label class="form-check-label" for="challengeNotifications">Challenge Notifications</label>
                                        </div>
                                        
                                        <div class="mb-3 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="levelUpNotifications" checked>
                                            <label class="form-check-label" for="levelUpNotifications">Level Up Notifications</label>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary" disabled>Save Settings</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">Email notification settings will be available in a future update.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Account Management</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">Manage your account settings and data.</p>
                                    
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" type="button" disabled>
                                            <i class="bi bi-download"></i> Export My Data
                                        </button>
                                        <button class="btn btn-outline-warning" type="button" disabled>
                                            <i class="bi bi-arrow-clockwise"></i> Reset Progress
                                        </button>
                                        <button class="btn btn-outline-danger" type="button" disabled>
                                            <i class="bi bi-trash"></i> Delete Account
                                        </button>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">Account management options will be available in a future update.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/partials/footer.php';

?>