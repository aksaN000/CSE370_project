<?php
// views/notifications.php - Notifications view page
// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

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

// Initialize notification controller
$notificationController = new NotificationController();

// Get all notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$notifications = $notificationController->getAllNotifications($user->id, $limit, $offset);

// Get total notification count
$total_count = $notificationController->getNotificationCount($user->id);
$total_pages = ceil($total_count / $limit);

// Get unread count
$unread_count = $notificationController->getNotificationCount($user->id, true);

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
                <h1 class="h2">Notifications</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if($unread_count > 0): ?>
                        <form action="../controllers/mark_all_notifications_read.php" method="POST" class="me-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check-all"></i> Mark All as Read
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
            
            <!-- Notifications List -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Notifications</h5>
                        <span class="badge bg-primary rounded-pill"><?php echo $total_count; ?> Total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($notifications)): ?>
                        <div class="alert alert-info m-3">
                            <i class="bi bi-info-circle"></i> You don't have any notifications yet.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($notifications as $notification): ?>
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
                                <div class="list-group-item list-group-item-action <?php echo !$notification['is_read'] ? 'list-group-item-light' : ''; ?>">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="bi bi-<?php echo $icon; ?>-fill text-<?php echo $color; ?> fs-3"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                                                <small class="text-muted"><?php echo date('M d, g:i a', strtotime($notification['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo $notification['message']; ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if(!$notification['is_read']): ?>
                                                    <form action="../controllers/mark_notification_read.php" method="POST" class="me-2">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-check"></i> Mark as Read
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Read</span>
                                                <?php endif; ?>
                                                <form action="../controllers/delete_notification.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" class="btn btn-sm text-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <div class="d-flex justify-content-center my-3">
                                <nav aria-label="Notifications pagination">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/partials/footer.php';

?>