<?php
// views/achievements.php - Achievements page

// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AchievementController.php';
require_once __DIR__ . '/../utils/helpers.php';

$authController = new AuthController();

// Redirect if not logged in
if(!$authController->isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Get logged in user
$user = $authController->getLoggedInUser();

// Initialize achievement controller
$achievementController = new AchievementController();

// Get user achievements
$achievements = $achievementController->getUserAchievements($user->id);
$level_achievements = $achievements['level_achievements'];
$unlocked_special_achievements = $achievements['special_achievements'];

// Get all levels to show locked achievements too
$query = "SELECT * FROM levels ORDER BY level_number ASC";
$stmt = $GLOBALS['conn']->prepare($query);
$stmt->execute();
$all_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Achievements & Badges</h1>
            </div>
            
            <!-- User Level -->
            <div class="card mb-4">
                <!-- ... user level card content ... -->
            </div>
            
            <!-- Achievements Grid -->
            <h3 class="mb-4">Your Badges</h3>
            
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                <?php
                // Create a lookup of unlocked achievements for easy access
                $unlocked_achievements = [];
                foreach($level_achievements as $achievement) {
                    $unlocked_achievements[$achievement['level_number']] = $achievement;
                }
                
                // Display all levels, marking locked ones appropriately
                foreach($all_levels as $level):
                    $is_unlocked = isset($unlocked_achievements[$level['level_number']]);
                ?>
                    <div class="col">
                        <!-- ... achievement card content ... -->
                    </div>
                    
                    <!-- Achievement Modal -->
                    <div class="modal fade" id="achievementModal<?php echo $level['level_number']; ?>" tabindex="-1" aria-labelledby="achievementModalLabel<?php echo $level['level_number']; ?>" aria-hidden="true">
                        <!-- ... achievement modal content ... -->
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Special Achievements -->
            <h3 class="mt-5 mb-4">Special Achievements</h3>
            
            <?php if (empty($unlocked_special_achievements)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Complete habits, reach goals, and join challenges to earn special badges!
                </div>
            <?php endif; ?>
            
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                <?php foreach($achievementController->special_achievements as $achievement): ?>
                    <?php 
                    $is_unlocked = false;
                    foreach ($unlocked_special_achievements as $unlocked) {
                        if ($unlocked['name'] === $achievement['name']) {
                            $is_unlocked = true;
                            break;
                        }
                    }
                    ?>
                    <div class="col">
                        <!-- ... special achievement card content ... -->
                        <div class="card h-100 achievement-card <?php echo $is_unlocked ? '' : 'locked'; ?>">
                            <div class="card-body text-center">
                                <div class="achievement-icon">
                                    <i class="bi bi-<?php echo $achievement['icon']; ?>-fill text-<?php echo $achievement['color']; ?>"></i>
                                </div>
                                <h5 class="card-title"><?php echo $achievement['name']; ?></h5>
                                <p class="card-text small"><?php echo $achievement['description']; ?></p>
                                
                                <?php if($is_unlocked): ?>
                                    <span class="badge bg-success">Unlocked</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Locked</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-muted text-center">
                                <?php echo $is_unlocked ? 'Achievement Completed!' : 'Keep trying'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/../views/partials/footer.php';
?>