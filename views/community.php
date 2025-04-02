<?php
// views/community.php - Community page

// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CommunityController.php';
require_once __DIR__ . '/../utils/helpers.php';

// This detects if a notification was clicked and marks it as read

// Check if a notification needs to be marked as read
require_once __DIR__ . '/../utils/notification_handler.php';

// Handle notification read marking - if it returns true, a redirect was performed and we should exit
if(handleNotificationReadMarking()) {
    exit;
}

$authController = new AuthController();

// Redirect if not logged in
if (!$authController->isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Get logged in user
$user = $authController->getLoggedInUser();

// Initialize community controller
$communityController = new CommunityController();

// Get the view type (friends, requests, search, profile, leaderboard)
$view = isset($_GET['view']) ? $_GET['view'] : 'friends';

// Process search if submitted
$search_results = [];
$search_term = '';
if ($view === 'search' && isset($_GET['search'])) {
    $search_term = $_GET['search'];
    if (!empty($search_term)) {
        $search_results = $communityController->searchUsers($search_term, $user->id);
    }
}

// Get friend requests
$friend_requests = $communityController->getFriendRequests($user->id);

// Get friends
$friends = $communityController->getFriends($user->id);

// Get user profile if viewing a profile
$profile = null;
if ($view === 'profile' && isset($_GET['id'])) {
    $profile_id = $_GET['id'];
    $profile_result = $communityController->getUserProfile($profile_id, $user->id);
    if ($profile_result['success']) {
        $profile = $profile_result['profile'];
    }
}

// Get leaderboard
$leaderboard_category = isset($_GET['category']) ? $_GET['category'] : 'xp';
$leaderboard = $communityController->getLeaderboard($leaderboard_category);

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
                <h1 class="h2">Community</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form action="community.php" method="GET" class="me-2">
                        <input type="hidden" name="view" value="search">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search users..." name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Alert messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Navigation tabs -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'friends' ? 'active' : ''; ?>" href="community.php?view=friends">
                        <i class="bi bi-people-fill"></i> Friends
                        <?php if (count($friends) > 0): ?>
                            <span class="badge bg-secondary ms-1"><?php echo count($friends); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'requests' ? 'active' : ''; ?>" href="community.php?view=requests">
                        <i class="bi bi-person-plus-fill"></i> Friend Requests
                        <?php if (count($friend_requests['incoming']) > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo count($friend_requests['incoming']); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'search' ? 'active' : ''; ?>" href="community.php?view=search">
                        <i class="bi bi-search"></i> Find Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'leaderboard' ? 'active' : ''; ?>" href="community.php?view=leaderboard">
                        <i class="bi bi-trophy-fill"></i> Leaderboard
                    </a>
                </li>
            </ul>
            
            <!-- Content based on view -->
            <?php if ($view === 'friends'): ?>
                <!-- Friends View -->
                <div class="row">
                    <?php if (empty($friends)): ?>
                        <div class="col">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> You don't have any friends yet. 
                                <a href="community.php?view=search">Search for users</a> to add friends.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($friends as $friend): ?>
                            <div class="col-md-4 col-lg-3 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($friend['username']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($friend['username']); ?>&background=random&size=100" alt="<?php echo htmlspecialchars($friend['username']); ?>" class="rounded-circle">
                                            <div class="mt-2">
                                                <span class="badge bg-primary">Level <?php echo $friend['level']; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col">
                                                <h6><?php echo $friend['total_habits']; ?></h6>
                                                <small class="text-muted">Habits</small>
                                            </div>
                                            <div class="col">
                                                <h6><?php echo $friend['total_goals']; ?></h6>
                                                <small class="text-muted">Goals</small>
                                            </div>
                                            <div class="col">
                                                <h6><?php echo number_format($friend['current_xp']); ?></h6>
                                                <small class="text-muted">XP</small>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <a href="community.php?view=profile&id=<?php echo $friend['friend_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-person"></i> View Profile
                                            </a>
                                            <form action="../controllers/process_friend_request.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="friend_id" value="<?php echo $friend['friend_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Are you sure you want to remove this friend?')">
                                                    <i class="bi bi-person-dash"></i> Remove Friend
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-footer text-muted">
                                        <small>Friends since <?php echo formatDate($friend['created_at'], 'M j, Y'); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>


            <?php elseif ($view === 'requests'): ?>
                <!-- Friend Requests View -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    Incoming Friend Requests
                                    <?php if (count($friend_requests['incoming']) > 0): ?>
                                        <span class="badge bg-danger float-end"><?php echo count($friend_requests['incoming']); ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($friend_requests['incoming'])): ?>
                                    <p class="text-muted">No pending friend requests.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($friend_requests['incoming'] as $request): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($request['sender_name']); ?>&background=random&size=50" alt="<?php echo htmlspecialchars($request['sender_name']); ?>" class="rounded-circle me-3" width="50">
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($request['sender_name']); ?></h6>
                                                            <small class="text-muted"><?php echo timeAgo($request['created_at']); ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex">
                                                        <form action="../controllers/process_friend_request.php" method="POST" class="me-1">
                                                            <input type="hidden" name="action" value="accept">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="bi bi-check-lg"></i> Accept
                                                            </button>
                                                        </form>
                                                        <form action="../controllers/process_friend_request.php" method="POST">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-x-lg"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    Sent Friend Requests
                                    <?php if (count($friend_requests['outgoing']) > 0): ?>
                                        <span class="badge bg-light text-dark float-end"><?php echo count($friend_requests['outgoing']); ?></span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($friend_requests['outgoing'])): ?>
                                    <p class="text-muted">No pending outgoing requests.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($friend_requests['outgoing'] as $request): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($request['recipient_name']); ?>&background=random&size=50" alt="<?php echo htmlspecialchars($request['recipient_name']); ?>" class="rounded-circle me-3" width="50">
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($request['recipient_name']); ?></h6>
                                                            <small class="text-muted">Sent <?php echo timeAgo($request['created_at']); ?></small>
                                                        </div>
                                                    </div>
                                                    <form action="../controllers/process_friend_request.php" method="POST">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-x-lg"></i> Cancel
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($view === 'search'): ?>
                <!-- Search Users View -->
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Find Users</h5>
                            </div>
                            <div class="card-body">
                                <form action="community.php" method="GET" class="mb-4">
                                    <input type="hidden" name="view" value="search">
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-lg" placeholder="Search by username..." name="search" value="<?php echo htmlspecialchars($search_term); ?>" required>
                                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                                    </div>
                                </form>
                                
                                <?php if (!empty($search_term) && empty($search_results)): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> No users found matching "<?php echo htmlspecialchars($search_term); ?>".
                                    </div>
                                <?php elseif (!empty($search_results)): ?>
                                    <h6 class="mb-3"><?php echo count($search_results); ?> users found for "<?php echo htmlspecialchars($search_term); ?>"</h6>
                                    <div class="list-group">
                                        <?php foreach ($search_results as $result): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($result['username']); ?>&background=random&size=50" alt="<?php echo htmlspecialchars($result['username']); ?>" class="rounded-circle me-3" width="50">
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($result['username']); ?></h6>
                                                            <span class="badge bg-primary">Level <?php echo $result['level']; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex">
                                                        <a href="community.php?view=profile&id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                                                            <i class="bi bi-person"></i> View Profile
                                                        </a>
                                                        
                                                        <?php if ($result['is_friend']): ?>
                                                            <span class="btn btn-sm btn-success disabled">
                                                                <i class="bi bi-check-lg"></i> Friends
                                                            </span>
                                                        <?php else: ?>
                                                            <form action="../controllers/process_friend_request.php" method="POST">
                                                                <input type="hidden" name="action" value="send">
                                                                <input type="hidden" name="recipient_id" value="<?php echo $result['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="bi bi-person-plus"></i> Add Friend
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (empty($search_term)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-search display-1 text-muted"></i>
                                        <p class="mt-3 text-muted">Enter a username to search for users.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <?php elseif ($view === 'profile' && $profile): ?>
                <!-- User Profile View -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?php echo htmlspecialchars($profile['username']); ?></h5>
                            </div>
                            <div class="card-body text-center">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($profile['username']); ?>&background=random&size=150" alt="<?php echo htmlspecialchars($profile['username']); ?>" class="rounded-circle mb-3" width="150">
                                
                                <h5>Level <?php echo $profile['level']; ?></h5>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                
                                <p><strong>XP: </strong><?php echo number_format($profile['current_xp']); ?></p>
                                <p><strong>Member since: </strong><?php echo formatDate($profile['created_at'], 'F j, Y'); ?></p>
                                
                                <?php if ($profile['id'] !== $user->id): ?>
                                    <div class="d-grid gap-2 mt-3">
                                        <?php if (isset($profile['is_friend']) && $profile['is_friend']): ?>
                                            <div class="btn btn-success mb-2">
                                                <i class="bi bi-check-lg"></i> Friends
                                            </div>
                                            <form action="../controllers/process_friend_request.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="friend_id" value="<?php echo $profile['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Are you sure you want to remove this friend?')">
                                                    <i class="bi bi-person-dash"></i> Remove Friend
                                                </button>
                                            </form>
                                        <?php elseif (isset($profile['has_sent_request']) && $profile['has_sent_request']): ?>
                                            <div class="btn btn-secondary mb-2 disabled">
                                                <i class="bi bi-hourglass"></i> Friend Request Sent
                                            </div>
                                        <?php elseif (isset($profile['has_received_request']) && $profile['has_received_request']): ?>
                                            <div class="d-flex">
                                                <form action="../controllers/process_friend_request.php" method="POST" class="me-1 w-50">
                                                    <input type="hidden" name="action" value="accept">
                                                    <input type="hidden" name="request_id" value="<?php echo $profile['request_id']; ?>">
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="bi bi-check-lg"></i> Accept
                                                    </button>
                                                </form>
                                                <form action="../controllers/process_friend_request.php" method="POST" class="w-50">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="request_id" value="<?php echo $profile['request_id']; ?>">
                                                    <button type="submit" class="btn btn-danger w-100">
                                                        <i class="bi bi-x-lg"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <form action="../controllers/process_friend_request.php" method="POST">
                                                <input type="hidden" name="action" value="send">
                                                <input type="hidden" name="recipient_id" value="<?php echo $profile['id']; ?>">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="bi bi-person-plus"></i> Add Friend
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8 mb-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Statistics</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($profile['show_stats'] || $profile['id'] === $user->id): ?>
                                    <div class="row">
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <h2 class="display-5"><?php echo $profile['total_completions'] ?? 0; ?></h2>
                                            <p class="text-muted">Habit Completions</p>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <h2 class="display-5"><?php echo $profile['total_challenges'] ?? 0; ?></h2>
                                            <p class="text-muted">Challenges</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> This user has chosen to keep their statistics private.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Additional profile sections would continue here -->
                    </div>
                </div>
                
            <?php elseif ($view === 'leaderboard'): ?>
                <!-- Leaderboard View -->
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Community Leaderboard</h5>
                            </div>
                            <div class="card-body">
                                <!-- Leaderboard category tabs -->
                                <ul class="nav nav-pills mb-4">
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $leaderboard_category === 'xp' ? 'active' : ''; ?>" href="community.php?view=leaderboard&category=xp">
                                            <i class="bi bi-lightning-fill"></i> XP
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $leaderboard_category === 'habits' ? 'active' : ''; ?>" href="community.php?view=leaderboard&category=habits">
                                            <i class="bi bi-check-circle-fill"></i> Most Habits
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $leaderboard_category === 'completions' ? 'active' : ''; ?>" href="community.php?view=leaderboard&category=completions">
                                            <i class="bi bi-calendar-check-fill"></i> Most Completions
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $leaderboard_category === 'goals' ? 'active' : ''; ?>" href="community.php?view=leaderboard&category=goals">
                                            <i class="bi bi-trophy-fill"></i> Goals Completed
                                        </a>
                                    </li>
                                </ul>
                                
                                <?php if (empty($leaderboard['leaderboard'])): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> No data available for this leaderboard.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Rank</th>
                                                    <th scope="col">User</th>
                                                    <th scope="col">Level</th>
                                                    <th scope="col">
                                                        <?php
                                                        switch ($leaderboard_category) {
                                                            case 'xp':
                                                                echo 'Experience Points';
                                                                break;
                                                            case 'habits':
                                                                echo 'Total Habits';
                                                                break;
                                                            case 'completions':
                                                                echo 'Total Completions';
                                                                break;
                                                            case 'goals':
                                                                echo 'Goals Completed';
                                                                break;
                                                        }
                                                        ?>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($leaderboard['leaderboard'] as $index => $entry): ?>
                                                    <tr <?php echo $entry['id'] == $user->id ? 'class="table-primary"' : ''; ?>>
                                                        <th scope="row">
                                                            <?php if ($index < 3): ?>
                                                                <span class="badge bg-<?php echo $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'danger'); ?> rounded-pill">
                                                                    <?php echo $index + 1; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <?php echo $index + 1; ?>
                                                            <?php endif; ?>
                                                        </th>
                                                        <td>
                                                            <a href="community.php?view=profile&id=<?php echo $entry['id']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($entry['username']); ?>
                                                                <?php echo $entry['id'] == $user->id ? ' (You)' : ''; ?>
                                                            </a>
                                                        </td>
                                                        <td>Level <?php echo $entry['level']; ?></td>
                                                        <td>
                                                            <strong>
                                                                <?php 
                                                                if ($leaderboard_category === 'xp') {
                                                                    echo number_format($entry['score']);
                                                                } else {
                                                                    echo $entry['score'];
                                                                }
                                                                ?>
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($view === 'profile' && !$profile): ?>
                <!-- Profile Not Found -->
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> User profile not found or you don't have permission to view it.
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/partials/footer.php';
?>