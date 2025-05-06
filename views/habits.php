<?php
// views/habits.php - Habits management page

// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/HabitController.php';

// Check if a notification needs to be marked as read
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

// Initialize habit controller
$habitController = new HabitController();

// Get all habits for the user
$habits = $habitController->getAllHabits($user->id);

// Get habit categories
$categories = $habitController->getAllCategories();

// Group habits by category
$habits_by_category = [];
foreach ($habits as $habit) {
    $category_id = $habit['category_id'] ?? 0;
    $category_name = $habit['category_name'] ?? 'Uncategorized';
    
    if (!isset($habits_by_category[$category_id])) {
        $habits_by_category[$category_id] = [
            'name' => $category_name,
            'color' => $habit['category_color'] ?? '#3498db',
            'habits' => []
        ];
    }
    
    $habits_by_category[$category_id]['habits'][] = $habit;
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Habits</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                        <i class="bi bi-plus"></i> Add New Habit
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
            
            <!-- Habit Statistics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Habits</h5>
                            <h1 class="display-4"><?php echo count($habits); ?></h1>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="card-title">Today's Completion</h5>
                            <?php
                            $today_habits = 0;
                            $completed_today = 0;
                            foreach($habits as $habit) {
                                if($habitController->isActiveToday($habit['id'])) {
                                    $today_habits++;
                                    if($habit['is_completed_today']) {
                                        $completed_today++;
                                    }
                                }
                            }
                            
                            $completion_percentage = $today_habits > 0 ? round(($completed_today / $today_habits) * 100) : 0;
                            ?>
                            <h1 class="display-4"><?php echo $completion_percentage; ?>%</h1>
                            <p class="text-muted"><?php echo $completed_today; ?>/<?php echo $today_habits; ?> completed</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h5 class="card-title">Current Streak</h5>
                            <?php 
                            $current_streak = $habitController->getCurrentStreak($user->id);
                            ?>
                            <h1 class="display-4"><?php echo $current_streak['streak_days']; ?></h1>
                            <p class="text-muted">days</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h5 class="card-title">Longest Streak</h5>
                            <?php 
                            $longest_streak = $habitController->getLongestStreak($user->id);
                            ?>
                            <h1 class="display-4"><?php echo $longest_streak['streak_days']; ?></h1>
                            <p class="text-muted"><?php echo $longest_streak['habit_title']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter and Sort Options -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label for="categoryFilter" class="form-label">Filter by Category</label>
                            <select class="form-select" id="categoryFilter">
                                <option value="all">All Categories</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="statusFilter" class="form-label">Filter by Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="all">All Habits</option>
                                <option value="active">Active Today</option>
                                <option value="completed">Completed Today</option>
                                <option value="incomplete">Incomplete Today</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="sortOption" class="form-label">Sort By</label>
                            <select class="form-select" id="sortOption">
                                <option value="name">Name</option>
                                <option value="category">Category</option>
                                <option value="streak">Streak (High to Low)</option>
                                <option value="completion">Completion Rate</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Habits by Category -->
            <div id="habitsContainer">
                <?php if(empty($habits)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> You don't have any habits yet. 
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addHabitModal">Add a habit</a> to get started.
                    </div>
                <?php else: ?>
                    <?php foreach($habits_by_category as $category_id => $category): ?>
                        <div class="card mb-4 habit-category" data-category-id="<?php echo $category_id; ?>">
                            <div class="card-header" style="background-color: <?php echo $category['color']; ?>; color: white;">
                                <h5 class="mb-0"><?php echo $category['name']; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach($category['habits'] as $habit): ?>
                                        <div class="col-md-4 mb-3 habit-item" 
                                             data-category-id="<?php echo $habit['category_id']; ?>"
                                             data-is-completed="<?php echo $habit['is_completed_today'] ? 'completed' : 'incomplete'; ?>"
                                             data-is-active="<?php echo $habitController->isActiveToday($habit['id']) ? 'active' : 'inactive'; ?>"
                                             data-streak="<?php echo $habit['streak']; ?>">
                                            
                                             <div id="habit-<?php echo $habit['id']; ?>" class="card h-100 habit-card <?php echo $habit['is_completed_today'] ? 'border-success' : 'border-primary'; ?>">
                                                <div class="card-header d-flex justify-content-between align-items-center <?php echo $habit['is_completed_today'] ? 'bg-success' : 'bg-primary'; ?> text-white">
                                                    <h5 class="mb-0"><?php echo $habit['title']; ?></h5>
                                                    <?php if($habit['streak'] > 1): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill streak-badge" data-bs-toggle="tooltip" title="Current Streak">
                                                            <?php echo $habit['streak']; ?> 🔥
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text"><?php echo $habit['description']; ?></p>
                                                    
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="text-muted">
                                                            <i class="bi bi-calendar-check"></i> 
                                                            <?php
                                                            switch($habit['frequency_type']) {
                                                                case 'daily':
                                                                    echo 'Daily';
                                                                    break;
                                                                case 'weekly':
                                                                    echo 'Weekly';
                                                                    break;
                                                                case 'monthly':
                                                                    echo 'Monthly';
                                                                    break;
                                                                case 'custom':
                                                                    echo 'Custom';
                                                                    break;
                                                            }
                                                            ?>
                                                        </span>
                                                        <span class="text-muted">
                                                            <i class="bi bi-lightning"></i> +<?php echo $habit['xp_reward']; ?> XP
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if($habitController->isActiveToday($habit['id'])): ?>
                                                        <?php if($habit['is_completed_today']): ?>
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
                                                    <?php else: ?>
                                                        <div class="alert alert-secondary mb-0">
                                                            <i class="bi bi-info-circle"></i> Not scheduled for today
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between">
                                                    <a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#habitDetailsModal<?php echo $habit['id']; ?>">
                                                        <i class="bi bi-info-circle"></i> Details
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="habitMenu<?php echo $habit['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="habitMenu<?php echo $habit['id']; ?>">
                                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editHabitModal<?php echo $habit['id']; ?>"><i class="bi bi-pencil"></i> Edit</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form action="../controllers/process_delete_habit.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this habit?');">
                                                                    <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Habit Details Modal -->
                                            <div class="modal fade" id="habitDetailsModal<?php echo $habit['id']; ?>" tabindex="-1" aria-labelledby="habitDetailsModalLabel<?php echo $habit['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header <?php echo $habit['is_completed_today'] ? 'bg-success' : 'bg-primary'; ?> text-white">
                                                            <h5 class="modal-title" id="habitDetailsModalLabel<?php echo $habit['id']; ?>"><?php echo $habit['title']; ?> Details</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php 
                                                            $stats = $habitController->getHabitStatistics($habit['id'], $user->id);
                                                            ?>
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <h5>Habit Information</h5>
                                                                    <p><strong>Description:</strong> <?php echo $habit['description']; ?></p>
                                                                    <p><strong>Category:</strong> <?php echo $habit['category_name']; ?></p>
                                                                    <p><strong>Frequency:</strong> 
                                                                        <?php
                                                                        switch($habit['frequency_type']) {
                                                                            case 'daily':
                                                                                echo 'Daily';
                                                                                break;
                                                                            case 'weekly':
                                                                                echo 'Weekly';
                                                                                break;
                                                                            case 'monthly':
                                                                                echo 'Monthly';
                                                                                break;
                                                                            case 'custom':
                                                                                echo 'Custom';
                                                                                break;
                                                                        }
                                                                        ?>
                                                                    </p>
                                                                    <p><strong>Start Date:</strong> <?php echo date('F j, Y', strtotime($habit['start_date'])); ?></p>
                                                                    <?php if($habit['end_date']): ?>
                                                                        <p><strong>End Date:</strong> <?php echo date('F j, Y', strtotime($habit['end_date'])); ?></p>
                                                                    <?php endif; ?>
                                                                    <p><strong>XP Reward:</strong> <?php echo $habit['xp_reward']; ?> XP</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h5>Statistics</h5>
                                                                    <p><strong>Total Completions:</strong> <?php echo $stats['total_completions']; ?></p>
                                                                    <p><strong>Current Streak:</strong> <?php echo $stats['current_streak']; ?> days</p>
                                                                    <p><strong>Consistency Rate:</strong> <?php echo $stats['consistency_percentage']; ?>%</p>
                                                                    <p><strong>Last Completed:</strong> 
                                                                        <?php echo $habit['last_completion_date'] ? date('F j, Y', strtotime($habit['last_completion_date'])) : 'Never'; ?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            
                                                            <h5>Completion History</h5>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Date</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php
                                                                        // Get last 10 days
                                                                        $end_date = new DateTime();
                                                                        $start_date = new DateTime();
                                                                        $start_date->modify('-9 days');
                                                                        
                                                                        while($start_date <= $end_date) {
                                                                            $date = $start_date->format('Y-m-d');
                                                                            $status = 'Not Scheduled';
                                                                            
                                                                            // Check if habit was active on this date
                                                                            if($habitController->wasActiveOnDate($habit['id'], $date)) {
                                                                                $status = 'Incomplete';
                                                                                
                                                                                // Check if habit was completed on this date
                                                                                if($habitController->wasCompletedOnDate($habit['id'], $date)) {
                                                                                    $status = 'Completed';
                                                                                }
                                                                            }
                                                                            
                                                                            echo '<tr>';
                                                                            echo '<td>' . $start_date->format('M j, Y') . '</td>';
                                                                            echo '<td>';
                                                                            
                                                                            if($status === 'Completed') {
                                                                                echo '<span class="badge bg-success">Completed</span>';
                                                                            } elseif($status === 'Incomplete') {
                                                                                echo '<span class="badge bg-danger">Missed</span>';
                                                                            } else {
                                                                                echo '<span class="badge bg-secondary">Not Scheduled</span>';
                                                                            }
                                                                            
                                                                            echo '</td>';
                                                                            echo '</tr>';
                                                                            
                                                                            $start_date->modify('+1 day');
                                                                        }
                                                                        ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Edit Habit Modal -->
                                            <div class="modal fade" id="editHabitModal<?php echo $habit['id']; ?>" tabindex="-1" aria-labelledby="editHabitModalLabel<?php echo $habit['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title" id="editHabitModalLabel<?php echo $habit['id']; ?>">Edit Habit</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="../controllers/process_edit_habit.php" method="POST">
                                                            <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                                            
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="habitTitle<?php echo $habit['id']; ?>" class="form-label">Habit Title</label>
                                                                    <input type="text" class="form-control" id="habitTitle<?php echo $habit['id']; ?>" name="title" value="<?php echo $habit['title']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="habitDescription<?php echo $habit['id']; ?>" class="form-label">Description</label>
                                                                    <textarea class="form-control" id="habitDescription<?php echo $habit['id']; ?>" name="description" rows="3"><?php echo $habit['description']; ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="habitCategory<?php echo $habit['id']; ?>" class="form-label">Category</label>
                                                                    <select class="form-select" id="habitCategory<?php echo $habit['id']; ?>" name="category_id">
                                                                        <?php foreach($categories as $category): ?>
                                                                            <option value="<?php echo $category['id']; ?>" <?php echo $habit['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                                                <?php echo $category['name']; ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="habitFrequency<?php echo $habit['id']; ?>" class="form-label">Frequency</label>
                                                                    <select class="form-select" id="habitFrequency<?php echo $habit['id']; ?>" name="frequency_type">
                                                                        <option value="daily" <?php echo $habit['frequency_type'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                                        <option value="weekly" <?php echo $habit['frequency_type'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                                        <option value="monthly" <?php echo $habit['frequency_type'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                                        <option value="custom" <?php echo $habit['frequency_type'] === 'custom' ? 'selected' : ''; ?>>Custom</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="habitXP<?php echo $habit['id']; ?>" class="form-label">XP Reward</label>
                                                                    <div class="input-group">
                                                                        <input type="number" class="form-control" id="habitXP<?php echo $habit['id']; ?>" name="xp_reward" min="1" value="<?php echo $habit['xp_reward']; ?>">
                                                                        <span class="input-group-text">XP</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
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

<script>
    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Frequency options toggle
        const frequencySelect = document.getElementById('habitFrequency');
        if(frequencySelect) {
            frequencySelect.addEventListener('change', function() {
                const frequencyType = this.value;
                
                // Hide all options first
                document.querySelectorAll('.frequency-options').forEach(el => {
                    el.style.display = 'none';
                });
                
                // Show the selected option
                if(frequencyType === 'weekly') {
                    document.getElementById('weeklyOptions').style.display = 'block';
                } else if(frequencyType === 'monthly') {
                    document.getElementById('monthlyOptions').style.display = 'block';
                } else if(frequencyType === 'custom') {
                    document.getElementById('customOptions').style.display = 'block';
                }
            });
        }
        
        // Filtering and sorting logic
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');
        const sortOption = document.getElementById('sortOption');
        
        function filterHabits() {
            const categoryValue = categoryFilter.value;
            const statusValue = statusFilter.value;
            
            // Get all habit items
            const habitItems = document.querySelectorAll('.habit-item');
            
            habitItems.forEach(item => {
                let showItem = true;
                
                // Category filtering
                if(categoryValue !== 'all' && item.getAttribute('data-category-id') !== categoryValue) {
                    showItem = false;
                }
                
                // Status filtering
                if(statusValue !== 'all') {
                    if(statusValue === 'active' && item.getAttribute('data-is-active') !== 'active') {
                        showItem = false;
                    } else if(statusValue === 'completed' && item.getAttribute('data-is-completed') !== 'completed') {
                        showItem = false;
                    } else if(statusValue === 'incomplete' && (item.getAttribute('data-is-completed') !== 'incomplete' || item.getAttribute('data-is-active') !== 'active')) {
                        showItem = false;
                    }
                }
                
                // Show/hide the item
                item.style.display = showItem ? 'block' : 'none';
            });
            
            // Check if categories are empty and hide them
            document.querySelectorAll('.habit-category').forEach(category => {
                const visibleItems = category.querySelectorAll('.habit-item[style="display: block;"]');
                category.style.display = visibleItems.length > 0 ? 'block' : 'none';
            });
        }
        
        function sortHabits() {
            const sortValue = sortOption.value;
            const habitsContainer = document.getElementById('habitsContainer');
            const categories = Array.from(document.querySelectorAll('.habit-category'));
            
            // Sort categories
            if(sortValue === 'category') {
                categories.sort((a, b) => {
                    const nameA = a.querySelector('.card-header h5').textContent;
                    const nameB = b.querySelector('.card-header h5').textContent;
                    return nameA.localeCompare(nameB);
                });
            }
            
            // Sort habits within each category
            categories.forEach(category => {
                const items = Array.from(category.querySelectorAll('.habit-item'));
                
                if(sortValue === 'name') {
                    items.sort((a, b) => {
                        const nameA = a.querySelector('.card-header h5').textContent;
                        const nameB = b.querySelector('.card-header h5').textContent;
                        return nameA.localeCompare(nameB);
                    });
                } else if(sortValue === 'streak') {
                    items.sort((a, b) => {
                        const streakA = parseInt(a.getAttribute('data-streak')) || 0;
                        const streakB = parseInt(b.getAttribute('data-streak')) || 0;
                        return streakB - streakA; // High to low
                    });
                }
                
                // Reappend sorted items
                const itemsContainer = category.querySelector('.row');
                items.forEach(item => itemsContainer.appendChild(item));
            });
            
            // Reappend sorted categories
            categories.forEach(category => habitsContainer.appendChild(category));
        }
        
        // Add event listeners
        if(categoryFilter && statusFilter && sortOption) {
            categoryFilter.addEventListener('change', filterHabits);
            statusFilter.addEventListener('change', filterHabits);
            sortOption.addEventListener('change', sortHabits);
        }
    });
</script>

<?php
// Include footer
include __DIR__ . '/partials/footer.php';

?>