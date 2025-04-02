<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// views/goals.php - Goals view page
// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/GoalController.php';
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

// Initialize goal controller
$goalController = new GoalController();

// Get all goals for the user
$goals = $goalController->getAllGoals($user->id);

// Group goals by status
$active_goals = array_filter($goals, function($goal) {
    return !$goal['is_completed'] && strtotime($goal['end_date']) >= strtotime('today');
});

$completed_goals = array_filter($goals, function($goal) {
    return $goal['is_completed'];
});

$expired_goals = array_filter($goals, function($goal) {
    return !$goal['is_completed'] && strtotime($goal['end_date']) < strtotime('today');
});

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
                <h1 class="h2">My Goals</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                        <i class="bi bi-plus"></i> Add New Goal
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
            
            <!-- Active Goals Section -->
            <h3 class="mt-4 mb-3">Active Goals</h3>
            <div class="row">
                <?php if(empty($active_goals)): ?>
                    <div class="col">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You don't have any active goals. 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addGoalModal">Add a goal</a> to get started.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($active_goals as $goal): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo $goal['title']; ?></h5>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-dark" type="button" id="goalOptions<?php echo $goal['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="goalOptions<?php echo $goal['id']; ?>">
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateGoalModal<?php echo $goal['id']; ?>">Update Progress</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="../controllers/process_delete_goal.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this goal?');">
                                                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo $goal['description']; ?></p>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $goal['progress_percentage']; ?>%;" aria-valuenow="<?php echo $goal['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $goal['progress_percentage']; ?>%</div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Progress: <?php echo $goal['current_value']; ?>/<?php echo $goal['target_value']; ?></span>
                                        <span>+<?php echo $goal['xp_reward']; ?> XP on completion</span>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateGoalModal<?php echo $goal['id']; ?>">
                                            Update Progress
                                        </button>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between text-muted">
                                    <small>Started: <?php echo date('M d, Y', strtotime($goal['start_date'])); ?></small>
                                    <small>Due: <?php echo date('M d, Y', strtotime($goal['end_date'])); ?></small>
                                </div>
                            </div>
                            
                            <!-- Update Goal Progress Modal -->
                            <div class="modal fade" id="updateGoalModal<?php echo $goal['id']; ?>" tabindex="-1" aria-labelledby="updateGoalModalLabel<?php echo $goal['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="updateGoalModalLabel<?php echo $goal['id']; ?>">Update Goal Progress</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="../controllers/process_update_goal.php" method="POST">
                                            <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                            <div class="modal-body">
                                                <h5><?php echo $goal['title']; ?></h5>
                                                <p class="text-muted"><?php echo $goal['description']; ?></p>
                                                
                                                <div class="mb-3">
                                                    <label for="progress<?php echo $goal['id']; ?>" class="form-label">Current Progress (out of <?php echo $goal['target_value']; ?>)</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="range" class="form-range me-2" id="progressRange<?php echo $goal['id']; ?>" name="progress_range" min="0" max="<?php echo $goal['target_value']; ?>" value="<?php echo $goal['current_value']; ?>" oninput="document.getElementById('progress<?php echo $goal['id']; ?>').value = this.value">
                                                        <input type="number" class="form-control w-25" id="progress<?php echo $goal['id']; ?>" name="progress_value" min="0" max="<?php echo $goal['target_value']; ?>" value="<?php echo $goal['current_value']; ?>" oninput="document.getElementById('progressRange<?php echo $goal['id']; ?>').value = this.value">
                                                    </div>
                                                </div>
                                                <div class="progress mb-3">
                                                    <div id="progressBar<?php echo $goal['id']; ?>" class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $goal['progress_percentage']; ?>%;" aria-valuenow="<?php echo $goal['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $goal['progress_percentage']; ?>%</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Progress</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <script>
                            // Update progress bar in real-time
                            document.getElementById('progress<?php echo $goal['id']; ?>').addEventListener('input', function() {
                                const value = this.value;
                                const max = <?php echo $goal['target_value']; ?>;
                                const percentage = (value / max) * 100;
                                
                                const progressBar = document.getElementById('progressBar<?php echo $goal['id']; ?>');
                                progressBar.style.width = percentage + '%';
                                progressBar.setAttribute('aria-valuenow', percentage);
                                progressBar.innerHTML = percentage.toFixed(1) + '%';
                            });
                        </script>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Completed Goals Section -->
            <h3 class="mt-4 mb-3">Completed Goals</h3>
            <div class="row">
                <?php if(empty($completed_goals)): ?>
                    <div class="col">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> You haven't completed any goals yet. Keep working on your active goals!
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($completed_goals as $goal): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><?php echo $goal['title']; ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo $goal['description']; ?></p>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">100%</div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Completed: <?php echo $goal['target_value']; ?>/<?php echo $goal['target_value']; ?></span>
                                        <span>+<?php echo $goal['xp_reward']; ?> XP earned</span>
                                    </div>
                                    <div class="alert alert-success mb-0">
                                        <i class="bi bi-trophy-fill"></i> Goal Achieved! Awesome!
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between text-muted">
                                    <small>Started: <?php echo date('M d, Y', strtotime($goal['start_date'])); ?></small>
                                    <small>Completed before: <?php echo date('M d, Y', strtotime($goal['end_date'])); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Expired Goals Section -->
            <?php if(!empty($expired_goals)): ?>
                <h3 class="mt-4 mb-3">Expired Goals</h3>
                <div class="row">
                    <?php foreach($expired_goals as $goal): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-danger">
                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo $goal['title']; ?></h5>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-light" type="button" id="goalOptions<?php echo $goal['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="goalOptions<?php echo $goal['id']; ?>">
                                            <li>
                                                <form action="../controllers/process_delete_goal.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this goal?');">
                                                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger">Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo $goal['description']; ?></p>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $goal['progress_percentage']; ?>%;" aria-valuenow="<?php echo $goal['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $goal['progress_percentage']; ?>%</div>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Progress: <?php echo $goal['current_value']; ?>/<?php echo $goal['target_value']; ?></span>
                                        <span>Expired</span>
                                    </div>
                                    <div class="alert alert-danger mb-0">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Goal expired on <?php echo date('M d, Y', strtotime($goal['end_date'])); ?>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between text-muted">
                                    <small>Started: <?php echo date('M d, Y', strtotime($goal['start_date'])); ?></small>
                                    <small>Due: <?php echo date('M d, Y', strtotime($goal['end_date'])); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-labelledby="addGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
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
                        <input type="date" class="form-control" id="goalEndDate" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/partials/footer.php';

?>