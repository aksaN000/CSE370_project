<?php
// views/challenges.php - Challenges view page
// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ChallengeController.php';

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

// Initialize challenge controller
$challengeController = new ChallengeController();

// Check if we're viewing a specific challenge
$is_viewing_challenge = isset($_GET['id']);
$challenge = null;
$tasks = [];
$completed_tasks = [];
$user_progress = 0;
$is_joined = false;

if($is_viewing_challenge) {
    $challenge_id = $_GET['id'];
    $challenge_result = $challengeController->getChallengeDetails($challenge_id);
    
    if($challenge_result['success']) {
        $challenge = $challenge_result['challenge'];
        $tasks = $challenge['tasks'];
        
        // Check if user is joined
        $this_challenge = new Challenge($GLOBALS['conn']);
        $this_challenge->id = $challenge_id;
        $is_joined = $this_challenge->isUserJoined($user->id);
        
        if($is_joined) {
            $completed_tasks = $challengeController->getCompletedTasks($challenge_id, $user->id);
            $user_progress = $challengeController->getUserProgressPercentage($challenge_id, $user->id);
        }
    } else {
        // Redirect to challenges page if challenge not found
        $_SESSION['error'] = $challenge_result['message'];
        header('Location: challenges.php');
        exit;
    }
}

// Check if we're creating a new challenge
$is_creating = isset($_GET['action']) && $_GET['action'] === 'create';

// Get all challenges if not viewing a specific one and not creating
$active_challenges = [];
$completed_challenges = [];
$created_challenges = [];

if(!$is_viewing_challenge && !$is_creating) {
    $active_challenges = $challengeController->getActiveChallenges($user->id);
    $completed_challenges = $challengeController->getCompletedChallenges($user->id);
    $created_challenges = $challengeController->getUserCreatedChallenges($user->id);
    
    // Get all challenges that the user hasn't joined yet
    $all_challenges = $challengeController->getAllChallenges();
    $available_challenges = [];
    
    foreach($all_challenges as $chal) {
        $is_in_active = false;
        $is_in_completed = false;
        
        foreach($active_challenges as $active) {
            if($active['id'] == $chal['id']) {
                $is_in_active = true;
                break;
            }
        }
        
        if(!$is_in_active) {
            foreach($completed_challenges as $complete) {
                if($complete['id'] == $chal['id']) {
                    $is_in_completed = true;
                    break;
                }
            }
        }
        
        if(!$is_in_active && !$is_in_completed) {
            $available_challenges[] = $chal;
        }
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
            <?php if($is_viewing_challenge): ?>
                <!-- Challenge Details View -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Challenge: <?php echo $challenge['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="challenges.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Challenges
                        </a>
                        <?php if(!$is_joined && $challenge['creator_id'] != $user->id): ?>
                            <form action="../controllers/process_join_challenge.php" method="POST" class="ms-2">
                                <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-person-plus"></i> Join Challenge
                                </button>
                            </form>
                        <?php elseif($is_joined && $challenge['creator_id'] != $user->id): ?>
                            <form action="../controllers/process_leave_challenge.php" method="POST" class="ms-2">
                                <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-person-dash"></i> Leave Challenge
                                </button>
                            </form>
                        <?php endif; ?>
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
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Challenge Details</h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br($challenge['description']); ?></p>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Start Date</h6>
                                        <p><?php echo date('F j, Y', strtotime($challenge['start_date'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>End Date</h6>
                                        <p><?php echo date('F j, Y', strtotime($challenge['end_date'])); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>XP Reward</h6>
                                        <p><span class="badge bg-success"><?php echo $challenge['xp_reward']; ?> XP</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Created By</h6>
                                        <p><?php echo $challenge['creator_id'] == $user->id ? 'You' : 'User #' . $challenge['creator_id']; ?></p>
                                    </div>
                                </div>
                                
                                <?php if($is_joined || $challenge['creator_id'] == $user->id): ?>
                                    <div class="mt-3">
                                        <h6>Progress</h6>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $user_progress; ?>%;" aria-valuenow="<?php echo $user_progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $user_progress; ?>%</div>
                                        </div>
                                        <small class="text-muted">Complete all tasks to earn the XP reward!</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if(!empty($tasks) && ($is_joined || $challenge['creator_id'] == $user->id)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Challenge Tasks</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <?php foreach($tasks as $task): ?>
                                            <?php
                                            $is_completed = false;
                                            foreach($completed_tasks as $completed) {
                                                if($completed['id'] == $task['id']) {
                                                    $is_completed = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <div class="list-group-item list-group-item-action <?php echo $is_completed ? 'list-group-item-success' : ''; ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h5 class="mb-1"><?php echo $task['title']; ?></h5>
                                                    <?php if($is_joined && !$is_completed): ?>
                                                        <form action="../controllers/process_complete_task.php" method="POST">
                                                            <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary">
                                                                <i class="bi bi-check-lg"></i> Complete
                                                            </button>
                                                        </form>
                                                    <?php elseif($is_completed): ?>
                                                        <span class="badge bg-success">Completed</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if(!empty($task['description'])): ?>
                                                    <p class="mb-1"><?php echo $task['description']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif(empty($tasks) && ($is_joined || $challenge['creator_id'] == $user->id)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Challenge Tasks</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> There are no specific tasks for this challenge. The goal is to complete the challenge by the end date.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <?php if($challenge['creator_id'] == $user->id): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Creator Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                            <i class="bi bi-plus"></i> Add Task
                                        </button>
                                        <a href="challenges.php?action=edit&id=<?php echo $challenge['id']; ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Edit Challenge
                                        </a>
                                        <form action="../controllers/process_delete_challenge.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this challenge?');">
                                            <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="bi bi-trash"></i> Delete Challenge
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Challenge Timeline</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $start_date = new DateTime($challenge['start_date']);
                                $end_date = new DateTime($challenge['end_date']);
                                $today = new DateTime();
                                
                                $total_days = $start_date->diff($end_date)->days + 1;
                                $days_passed = $today < $start_date ? 0 : min($total_days, $start_date->diff($today)->days + 1);
                                $days_remaining = max(0, $end_date->diff($today)->days);
                                
                                if($today > $end_date) {
                                    $timeline_percent = 100;
                                } elseif($today < $start_date) {
                                    $timeline_percent = 0;
                                } else {
                                    $timeline_percent = min(100, max(0, round(($days_passed / $total_days) * 100)));
                                }
                                ?>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small><?php echo date('M j', strtotime($challenge['start_date'])); ?></small>
                                        <small><?php echo date('M j', strtotime($challenge['end_date'])); ?></small>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $timeline_percent; ?>%;" aria-valuenow="<?php echo $timeline_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between text-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $total_days; ?></h5>
                                        <small class="text-muted">Total Days</small>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo $days_passed; ?></h5>
                                        <small class="text-muted">Days Passed</small>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo $days_remaining; ?></h5>
                                        <small class="text-muted">Days Left</small>
                                    </div>
                                </div>
                                
                                <?php if($today < $start_date): ?>
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="bi bi-hourglass"></i> This challenge hasn't started yet.
                                    </div>
                                <?php elseif($today > $end_date): ?>
                                    <div class="alert alert-danger mt-3 mb-0">
                                        <i class="bi bi-exclamation-triangle"></i> This challenge has ended.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Invite Friends to Challenge Section -->
                        <?php if($challenge['creator_id'] == $user->id || $is_joined): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Invite Friends</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get user's friends
                                    $query = "SELECT f.friend_id, u.username 
                                            FROM user_friends f
                                            JOIN users u ON f.friend_id = u.id
                                            WHERE f.user_id = :user_id
                                            ORDER BY u.username ASC";
                                    
                                    $stmt = $GLOBALS['conn']->prepare($query);
                                    $stmt->bindParam(':user_id', $user->id);
                                    $stmt->execute();
                                    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Get challenge participants
                                    $query = "SELECT user_id FROM challenge_participants WHERE challenge_id = :challenge_id";
                                    $stmt = $GLOBALS['conn']->prepare($query);
                                    $stmt->bindParam(':challenge_id', $challenge['id']);
                                    $stmt->execute();
                                    $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    if(empty($friends)):
                                    ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle"></i> You don't have any friends to invite. 
                                            <a href="../views/community.php?view=search">Find some friends</a> and invite them to join!
                                        </div>
                                    <?php elseif(empty(array_filter($friends, function($friend) use ($participants) { 
                                        return !in_array($friend['friend_id'], $participants); 
                                    }))): ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle"></i> All your friends are already participating in this challenge.
                                        </div>
                                    <?php else: ?>
                                        <form action="../controllers/process_challenge_invite.php" method="POST">
                                            <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                            <div class="mb-3">
                                                <label for="recipientId" class="form-label">Select a friend to invite</label>
                                                <select class="form-select" id="recipientId" name="recipient_id" required>
                                                    <option value="">Select a friend...</option>
                                                    <?php foreach($friends as $friend): ?>
                                                        <?php if(!in_array($friend['friend_id'], $participants)): ?>
                                                            <option value="<?php echo $friend['friend_id']; ?>"><?php echo htmlspecialchars($friend['username']); ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-envelope"></i> Send Invitation
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>




                    </div>
                </div>
                
                <!-- Add Task Modal -->
                <?php if($challenge['creator_id'] == $user->id): ?>
                    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="../controllers/process_add_task.php" method="POST">
                                    <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="taskTitle" class="form-label">Task Title</label>
                                            <input type="text" class="form-control" id="taskTitle" name="title" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="taskDescription" class="form-label">Description (Optional)</label>
                                            <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Add Task</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php elseif($is_creating): ?>
                <!-- Create Challenge View -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Create New Challenge</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="challenges.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </div>
                
                <!-- Alert messages -->
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Challenge Details</h5>
                            </div>
                            <div class="card-body">
                                <form action="../controllers/process_add_challenge.php" method="POST">
                                    <div class="mb-3">
                                        <label for="challengeTitle" class="form-label">Challenge Title</label>
                                        <input type="text" class="form-control" id="challengeTitle" name="title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="challengeDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="challengeDescription" name="description" rows="4" required></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="challengeStartDate" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="challengeStartDate" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="challengeEndDate" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="challengeEndDate" name="end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="challengeReward" class="form-label">XP Reward</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="challengeReward" name="xp_reward" min="10" value="100" required>
                                            <span class="input-group-text">XP</span>
                                        </div>
                                        <div class="form-text">Participants will earn this amount of XP upon completing the challenge.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>Tasks</h5>
                                        <p class="text-muted">Add tasks that participants need to complete for this challenge. You can also add tasks later.</p>
                                        
                                        <div id="taskContainer">
                                            <div class="task-item mb-3 p-3 border rounded">
                                                <div class="mb-2">
                                                    <label class="form-label">Task 1 Title</label>
                                                    <input type="text" class="form-control" name="tasks[0][title]" placeholder="Task title">
                                                </div>
                                                <div>
                                                    <label class="form-label">Description (Optional)</label>
                                                    <textarea class="form-control" name="tasks[0][description]" rows="2" placeholder="Task description"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="button" class="btn btn-outline-primary" id="addTaskButton">
                                            <i class="bi bi-plus"></i> Add Another Task
                                        </button>
                                    </div>
                                    
                                    <div class="d-grid gap-2 mt-4">
                                        <button type="submit" class="btn btn-primary">Create Challenge</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const addTaskButton = document.getElementById('addTaskButton');
                        const taskContainer = document.getElementById('taskContainer');
                        let taskCount = 1;
                        
                        addTaskButton.addEventListener('click', function() {
                            const taskItem = document.createElement('div');
                            taskItem.className = 'task-item mb-3 p-3 border rounded';
                            taskItem.innerHTML = `
                                <div class="d-flex justify-content-between mb-2">
                                    <h6>Task ${taskCount + 1}</h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-task">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Task Title</label>
                                    <input type="text" class="form-control" name="tasks[${taskCount}][title]" placeholder="Task title">
                                </div>
                                <div>
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea class="form-control" name="tasks[${taskCount}][description]" rows="2" placeholder="Task description"></textarea>
                                </div>
                            `;
                            
                            taskContainer.appendChild(taskItem);
                            taskCount++;
                            
                            // Add event listener to remove button
                            const removeButton = taskItem.querySelector('.remove-task');
                            removeButton.addEventListener('click', function() {
                                taskItem.remove();
                                updateTaskNumbers();
                            });
                        });
                        
                        function updateTaskNumbers() {
                            const taskItems = document.querySelectorAll('.task-item');
                            taskItems.forEach((item, index) => {
                                const heading = item.querySelector('h6');
                                if (heading) {
                                    heading.textContent = `Task ${index + 1}`;
                                }
                                
                                const titleInput = item.querySelector('input[name^="tasks"]');
                                if (titleInput) {
                                    titleInput.name = `tasks[${index}][title]`;
                                }
                                
                                const descTextarea = item.querySelector('textarea[name^="tasks"]');
                                if (descTextarea) {
                                    descTextarea.name = `tasks[${index}][description]`;
                                }
                            });
                            
                            taskCount = taskItems.length;
                        }
                    });
                </script>
            <?php else: ?>
                <!-- Challenge List View -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Challenges</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="challenges.php?action=create" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Create Challenge
                        </a>
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
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- Active Challenges Section -->
                        <h3 class="mb-3">Your Active Challenges</h3>
                        <?php if(empty($active_challenges)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> You're not participating in any active challenges.
                                Explore available challenges below or <a href="challenges.php?action=create">create your own</a>!
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach($active_challenges as $challenge): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 border-danger">
                                            <div class="card-header bg-danger text-white">
                                                <h5 class="mb-0"><?php echo $challenge['title']; ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo substr($challenge['description'], 0, 100) . (strlen($challenge['description']) > 100 ? '...' : ''); ?></p>
                                                <div class="progress mb-3">
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $challenge['progress_percentage']; ?>%;" aria-valuenow="<?php echo $challenge['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $challenge['progress_percentage']; ?>%</div>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-people"></i> <?php echo $challenge['participant_count']; ?> participants
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-lightning"></i> <?php echo $challenge['xp_reward']; ?> XP
                                                    </small>
                                                </div>
                                                <div class="d-grid">
                                                    <a href="challenges.php?id=<?php echo $challenge['id']; ?>" class="btn btn-sm btn-outline-danger">View Details</a>
                                                </div>
                                            </div>
                                            <div class="card-footer text-muted">
                                                <small>Ends: <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Available Challenges Section -->
                        <?php if(!empty($available_challenges)): ?>
                            <h3 class="mt-4 mb-3">Available Challenges</h3>
                            <div class="row">
                                <?php foreach($available_challenges as $challenge): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0"><?php echo $challenge['title']; ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo substr($challenge['description'], 0, 100) . (strlen($challenge['description']) > 100 ? '...' : ''); ?></p>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <small class="text-muted">
                                                        <i class="bi bi-person"></i> Created by: <?php echo $challenge['creator_name']; ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-lightning"></i> <?php echo $challenge['xp_reward']; ?> XP
                                                    </small>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <a href="challenges.php?id=<?php echo $challenge['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                    <form action="../controllers/process_join_challenge.php" method="POST">
                                                        <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-person-plus"></i> Join
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="card-footer d-flex justify-content-between text-muted">
                                                <small>Starts: <?php echo date('M d, Y', strtotime($challenge['start_date'])); ?></small>
                                                <small>Ends: <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Completed Challenges Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Completed Challenges</h5>
                            </div>
                            <div class="card-body">
                                <?php if(empty($completed_challenges)): ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> You haven't completed any challenges yet.
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach($completed_challenges as $challenge): ?>
                                            <a href="challenges.php?id=<?php echo $challenge['id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo $challenge['title']; ?></h6>
                                                    <small class="text-success">+<?php echo $challenge['xp_reward']; ?> XP</small>
                                                </div>
                                                <small class="text-muted">Completed: <?php echo date('M d, Y', strtotime($challenge['completion_date'])); ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Challenges You Created Section -->
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Challenges You Created</h5>
                            </div>
                            <div class="card-body">
                                <?php if(empty($created_challenges)): ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> You haven't created any challenges yet.
                                        <a href="challenges.php?action=create">Create a challenge</a> to motivate others!
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach($created_challenges as $challenge): ?>
                                            <a href="challenges.php?id=<?php echo $challenge['id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo $challenge['title']; ?></h6>
                                                    <small><?php echo $challenge['participant_count']; ?> participants</small>
                                                </div>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $challenge['progress_percentage']; ?>%;" aria-valuenow="<?php echo $challenge['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted">Ends: <?php echo date('M d, Y', strtotime($challenge['end_date'])); ?></small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="challenges.php?action=create" class="btn btn-primary w-100">
                                    <i class="bi bi-plus"></i> Create New Challenge
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../views/partials/footer.php';

?>