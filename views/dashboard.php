<?php
// views/dashboard.php - Dashboard view page
// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/HabitController.php';
require_once __DIR__ . '/../controllers/GoalController.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../utils/helpers.php';

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
$notificationController = new NotificationController();
$totalUnreadCount = $notificationController->getNotificationCount($user->id, true);

// Get active habits for today
$todayHabits = $habitController->getActiveHabitsForToday($user->id);

// Get upcoming goals
$upcomingGoals = $goalController->getUpcomingGoals($user->id, 5);

// Get active challenges
$activeChallenges = $challengeController->getActiveChallenges($user->id);

// Get unread notifications
$unreadNotifications = $notificationController->getUnreadNotifications($user->id, 5);

// Get user level info
$levelInfo = getLevelInfo($user->level, $GLOBALS['conn']);

// Get next level info
$nextLevelXP = getNextLevelXP($user->level, $GLOBALS['conn']);

// Calculate XP progress percentage
$xpProgressPercentage = calculateXPProgress($user->current_xp, $user->level, $GLOBALS['conn']);

// Calculate XP for current level and needed for next level
$xpForCurrentLevel = $user->current_xp - $levelInfo['xp_required'];
$xpNeededForNextLevel = $nextLevelXP - $levelInfo['xp_required'];

// Include header
include '../views/partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../views/partials/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                        <i class="bi bi-plus"></i> Add Habit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                        <i class="bi bi-plus"></i> Add Goal
                    </button>
                </div>
            </div>
            
            <!-- Alert messages -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Welcome Section -->
            <div class="welcome-section mb-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="display-6 fw-bold mb-3">Welcome back, <?php echo $user->username; ?>!</h2>
                        <p class="lead mb-4">
                            <?php
                            $greeting = '';
                            $hour = date('H');
                            if($hour < 12) {
                                $greeting = 'Good morning';
                            } elseif($hour < 18) {
                                $greeting = 'Good afternoon';
                            } else {
                                $greeting = 'Good evening';
                            }
                            echo $greeting . '! ';
                            
                            if(count($todayHabits) > 0) {
                                $completed = 0;
                                foreach($todayHabits as $habit) {
                                    if($habit['is_completed']) {
                                        $completed++;
                                    }
                                }
                                
                                if($completed == 0) {
                                    echo "You have " . count($todayHabits) . " habits to complete today.";
                                } elseif($completed == count($todayHabits)) {
                                    echo "Amazing! You've completed all your habits for today.";
                                } else {
                                    echo "You've completed $completed out of " . count($todayHabits) . " habits for today.";
                                }
                            } else {
                                echo "You don't have any habits scheduled for today.";
                            }
                            ?>
                        </p>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-white">Level <?php echo $user->level; ?></span>
                                <span class="text-white">Level <?php echo $user->level + 1; ?></span>
                            </div>
                            <div class="xp-progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $xpProgressPercentage; ?>%;" aria-valuenow="<?php echo $xpProgressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="text-white-50 mt-1">
                                <small><?php echo $xpForCurrentLevel; ?> / <?php echo $xpNeededForNextLevel; ?> XP to next level</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="level-badge">
                            <?php echo $user->level; ?>
                        </div>
                        <h4 class="text-white mt-2"><?php echo $levelInfo['title']; ?></h4>
                        <p class="text-white-50"><?php echo $levelInfo['badge_name']; ?></p>
                        <p class="text-white">Total XP: <?php echo $user->current_xp; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats Row -->
            <div class="row mb-4">
                <!-- Habits Stats -->
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-primary fs-1 mb-3"></i>
                            <h5 class="card-title">Today's Habits</h5>
                            <p class="display-4 mb-1"><?php echo count($todayHabits); ?></p>
                            <p class="text-muted">
                                <?php
                                $completed_count = 0;
                                foreach($todayHabits as $habit) {
                                    if($habit['is_completed']) {
                                        $completed_count++;
                                    }
                                }
                                echo $completed_count . ' completed';
                                ?>
                            </p>
                            <a href="habits.php" class="btn btn-sm btn-outline-primary mt-2">View All Habits</a>
                        </div>
                    </div>
                </div>
                
                <!-- Goals Stats -->
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-trophy text-warning fs-1 mb-3"></i>
                            <h5 class="card-title">Active Goals</h5>
                            <p class="display-4 mb-1"><?php echo count($upcomingGoals); ?></p>
                            <p class="text-muted">
                                <?php
                                $nearest_goal = null;
                                $nearest_days = PHP_INT_MAX;
                                
                                foreach($upcomingGoals as $goal) {
                                    if($goal['days_remaining'] < $nearest_days) {
                                        $nearest_days = $goal['days_remaining'];
                                        $nearest_goal = $goal;
                                    }
                                }
                                
                                if($nearest_goal) {
                                    echo 'Next due: ' . formatDate($nearest_goal['end_date']);
                                } else {
                                    echo 'No upcoming goals';
                                }
                                ?>
                            </p>
                            <a href="goals.php" class="btn btn-sm btn-outline-warning mt-2">View All Goals</a>
                        </div>
                    </div>
                </div>
                
                <!-- Challenges Stats -->
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-danger">
                        <div class="card-body text-center">
                            <i class="bi bi-people text-danger fs-1 mb-3"></i>
                            <h5 class="card-title">Active Challenges</h5>
                            <p class="display-4 mb-1"><?php echo count($activeChallenges); ?></p>
                            <p class="text-muted">
                                <?php
                                $total_progress = 0;
                                foreach($activeChallenges as $challenge) {
                                    $total_progress += $challenge['progress_percentage'];
                                }
                                
                                $avg_progress = count($activeChallenges) > 0 ? round($total_progress / count($activeChallenges)) : 0;
                                echo 'Avg. progress: ' . $avg_progress . '%';
                                ?>
                            </p>
                            <a href="challenges.php" class="btn btn-sm btn-outline-danger mt-2">View Challenges</a>
                        </div>
                    </div>
                </div>
                
                <!-- Journal Stats -->
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <i class="bi bi-journal-text text-info fs-1 mb-3"></i>
                            <h5 class="card-title">Journal Activity</h5>
                            <?php
                            // Get journal stats from database
                            $query = "SELECT COUNT(*) as entry_count, MAX(entry_date) as last_entry 
                                     FROM journal_entries 
                                     WHERE user_id = :user_id";
                            $stmt = $GLOBALS['conn']->prepare($query);
                            $stmt->bindParam(':user_id', $user->id);
                            $stmt->execute();
                            $journal_stats = $stmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <p class="display-4 mb-1"><?php echo $journal_stats['entry_count']; ?></p>
                            <p class="text-muted">
                                <?php
                                if($journal_stats['last_entry']) {
                                    echo 'Last entry: ' . formatDate($journal_stats['last_entry']);
                                } else {
                                    echo 'No journal entries yet';
                                }
                                ?>
                            </p>
                            <a href="journal.php" class="btn btn-sm btn-outline-info mt-2">Go to Journal</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Today's Habits Section -->
            <h3 class="mt-4 mb-3">Today's Habits</h3>
            <div class="row">
                <?php if(empty($todayHabits)): ?>
                    <div class="col">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You don't have any habits scheduled for today. 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addHabitModal">Add a habit</a> to get started.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($todayHabits as $habit): ?>
                        <div class="col-md-4 mb-3">
                            <div id="habit-<?php echo $habit['id']; ?>" class="card h-100 habit-card <?php echo $habit['is_completed'] ? 'border-success' : 'border-primary'; ?>">
                                <div class="card-header d-flex justify-content-between align-items-center <?php echo $habit['is_completed'] ? 'bg-success' : 'bg-primary'; ?> text-white">
                                    <h5 class="mb-0"><?php echo $habit['title']; ?></h5>
                                    <?php if($habit['streak'] > 1): ?>
                                        <span class="badge bg-warning text-dark rounded-pill streak-badge" data-bs-toggle="tooltip" title="Current Streak">
                                            <?php echo $habit['streak']; ?> 🔥
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo $habit['description']; ?></p>
                                    <?php if($habit['is_completed']): ?>
                                        <div class="alert alert-success mb-0">
                                            <i class="bi bi-check-circle-fill"></i> Completed today!
                                        </div>
                                    <?php else: ?>
                                        <form action="../controllers/process_habit_completion.php" method="POST" class="habit-completion-form">
                                            <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-check"></i> Mark as Complete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center text-muted">
                                    <small>
                                        <i class="bi bi-tag"></i> <?php echo $habit['category_name'] ?? 'General'; ?>
                                    </small>
                                    <small>
                                        <i class="bi bi-lightning"></i> +<?php echo $habit['xp_reward']; ?> XP
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Goals Section -->
            <h3 class="mt-4 mb-3">Upcoming Goals</h3>
            <div class="row">
                <?php if(empty($upcomingGoals)): ?>
                    <div class="col">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You don't have any upcoming goals. 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addGoalModal">Add a goal</a> to get started.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($upcomingGoals as $goal): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 goal-card border-warning">
                                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo $goal['title']; ?></h5>
                                    <span class="badge bg-light text-dark">
                                        <?php echo $goal['days_remaining']; ?> days left
                                    </span>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo $goal['description']; ?></p>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $goal['progress_percentage']; ?>%;" aria-valuenow="<?php echo $goal['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $goal['progress_percentage']; ?>%</div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Progress: <?php echo $goal['current_value']; ?>/<?php echo $goal['target_value']; ?></span>
                                        <span class="text-muted">+<?php echo $goal['xp_reward']; ?> XP</span>
                                    </div>
                                </div>
                                <div class="card-footer text-muted">
                                    <div class="d-flex justify-content-between">
                                        <span>
                                            <i class="bi bi-calendar-check"></i> Due: <?php echo formatDate($goal['end_date']); ?>
                                        </span>
                                        <a href="goals.php" class="text-warning">
                                            <i class="bi bi-arrow-right"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Active Challenges Section -->
            <h3 class="mt-4 mb-3">Active Challenges</h3>
            <div class="row">
                <?php if(empty($activeChallenges)): ?>
                    <div class="col">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You're not participating in any challenges. 
                            <a href="challenges.php">Join a challenge</a> to earn more XP!
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($activeChallenges as $challenge): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 challenge-card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><?php echo $challenge['title']; ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo truncateText($challenge['description'], 100); ?></p>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $challenge['progress_percentage']; ?>%;" aria-valuenow="<?php echo $challenge['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $challenge['progress_percentage']; ?>%</div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="bi bi-people"></i> <?php echo $challenge['participant_count']; ?> participants
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-lightning"></i> +<?php echo $challenge['xp_reward']; ?> XP
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between text-muted">
                                    <span>
                                        <i class="bi bi-calendar-x"></i> Ends: <?php echo formatDate($challenge['end_date']); ?>
                                    </span>
                                    <a href="challenges.php?id=<?php echo $challenge['id']; ?>" class="text-danger">
                                        <i class="bi bi-arrow-right"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Recent Notifications -->
            <h3 class="mt-4 mb-3">Recent Notifications</h3>
            <div class="card">
                <div class="card-body">
                    <?php if(empty($unreadNotifications)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> You don't have any new notifications.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($unreadNotifications as $notification): ?>
                                <?php
                                $icon_info = getNotificationIcon($notification['type']);
                                ?>
                                <a href="../controllers/mark_notification_read.php?id=<?php echo $notification['id']; ?>&redirect=dashboard.php" class="list-group-item list-group-item-action notification-item unread">
                                    <div class="d-flex">
                                        <div class="notification-icon text-<?php echo $icon_info['color']; ?>">
                                            <i class="bi bi-<?php echo $icon_info['icon']; ?>-fill"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                                            <p class="mb-1 small"><?php echo $notification['message']; ?></p>
                                            <small class="text-muted"><?php echo timeAgo($notification['created_at']); ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All Notifications</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Habit Modal -->
<div class="modal fade" id="addHabitModal" tabindex="-1" aria-labelledby="addHabitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addHabitModalLabel">Add New Habit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../controllers/process_add_habit.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="habitTitle" class="form-label">Habit Title</label>
                        <input type="text" class="form-control" id="habitTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="habitDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="habitDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="habitCategory" class="form-label">Category</label>
                        <select class="form-select" id="habitCategory" name="category_id">
                            <?php
                            $categories = $habitController->getAllCategories();
                            foreach($categories as $category) {
                                echo '<option value="' . $category['id'] . '">' . $category['name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="habitFrequency" class="form-label">Frequency</label>
                        <select class="form-select" id="habitFrequency" name="frequency_type">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="mb-3 frequency-options" id="weeklyOptions" style="display: none;">
                        <label class="form-label">Days of Week</label>
                        <div class="btn-group d-flex flex-wrap" role="group">
                            <?php
                            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                            foreach($days as $index => $day) {
                                echo '<input type="checkbox" class="btn-check" id="day' . $index . '" name="frequency_value[]" value="' . $index . '">';
                                echo '<label class="btn btn-outline-primary m-1" for="day' . $index . '">' . $day . '</label>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="mb-3 frequency-options" id="monthlyOptions" style="display: none;">
                        <label for="monthlyDay" class="form-label">Day of Month</label>
                        <select class="form-select" id="monthlyDay" name="monthly_day">
                            <?php
                            for($i = 1; $i <= 31; $i++) {
                                echo '<option value="' . $i . '">' . $i . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3 frequency-options" id="customOptions" style="display: none;">
                        <label for="customDays" class="form-label">Repeat every X days</label>
                        <input type="number" class="form-control" id="customDays" name="custom_days" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label for="habitStartDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="habitStartDate" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="habitEndDate" class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control" id="habitEndDate" name="end_date">
                    </div>
                    <div class="mb-3">
                        <label for="habitXP" class="form-label">XP Reward</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="habitXP" name="xp_reward" min="1" value="10">
                            <span class="input-group-text">XP</span>
                        </div>
                        <div class="form-text">XP earned when completing this habit</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Habit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-labelledby="addGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="addGoalModalLabel">Add New Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../controllers/process_add_goal.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="goalTitle" class="form-label">Goal Title</label>
                        <input type="text" class="form-control" id="goalTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="goalDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="goalDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="goalTarget" class="form-label">Target Value</label>
                        <input type="number" class="form-control" id="goalTarget" name="target_value" min="1" value="1" required>
                        <div class="form-text">Set a numeric target to measure your progress.</div>
                    </div>
                    <div class="mb-3">
                        <label for="goalStartDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="goalStartDate" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="goalEndDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="goalEndDate" name="end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="goalXP" class="form-label">XP Reward</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="goalXP" name="xp_reward" min="1" value="50">
                            <span class="input-group-text">XP</span>
                        </div>
                        <div class="form-text">XP earned when completing this goal</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Add Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Handle frequency options display
        const frequencySelect = document.getElementById('habitFrequency');
        if(frequencySelect) {
            frequencySelect.addEventListener('change', function() {
                const frequencyType = this.value;
                const weeklyOptions = document.getElementById('weeklyOptions');
                const monthlyOptions = document.getElementById('monthlyOptions');
                const customOptions = document.getElementById('customOptions');
                
                // Hide all options first
                weeklyOptions.style.display = 'none';
                monthlyOptions.style.display = 'none';
                customOptions.style.display = 'none';
                
                // Show the selected option
                if(frequencyType === 'weekly') {
                    weeklyOptions.style.display = 'block';
                } else if(frequencyType === 'monthly') {
                    monthlyOptions.style.display = 'block';
                } else if(frequencyType === 'custom') {
                    customOptions.style.display = 'block';
                }
            });
        }
    });
</script>

<?php
// Include footer
include '../views/partials/footer.php';
?>