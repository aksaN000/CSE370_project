<?php
// views/achievements.php - Achievements page

// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../controllers/AchievementController.php';

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

// Define special achievements (for display, whether unlocked or not)
$special_achievements = [
    [
        'name' => 'Early Bird',
        'description' => 'Complete 5 habits before 9 AM',
        'icon' => 'sunrise',
        'color' => 'warning'
    ],
    [
        'name' => 'Perfectionist',
        'description' => 'Complete all habits for 7 consecutive days',
        'icon' => 'calendar-check',
        'color' => 'success'
    ],
    [
        'name' => 'Goal Crusher',
        'description' => 'Complete 10 goals',
        'icon' => 'bullseye',
        'color' => 'danger'
    ],
    [
        'name' => 'Social Butterfly',
        'description' => 'Join and complete 5 challenges',
        'icon' => 'people',
        'color' => 'primary'
    ],
    [
        'name' => 'Deep Thinker',
        'description' => 'Write 20 journal entries',
        'icon' => 'journal-text',
        'color' => 'info'
    ]
];

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
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <div class="level-badge">
                                <?php echo $user->level; ?>
                            </div>
                        </div>
                        <div class="col-md-7 mb-3 mb-md-0">
                            <h4>Current Level: <?php echo $user->level; ?> - <?php echo getLevelInfo($user->level, $GLOBALS['conn'])['title']; ?></h4>
                            <p class="text-muted mb-2">
                                <?php echo getLevelInfo($user->level, $GLOBALS['conn'])['badge_description']; ?>
                            </p>
                            <?php
                            $current_xp = $user->current_xp;
                            $level_info = getLevelInfo($user->level, $GLOBALS['conn']);
                            $next_level_xp = getNextLevelXP($user->level, $GLOBALS['conn']);
                            
                            if($next_level_xp) {
                                $xp_for_current_level = $current_xp - $level_info['xp_required'];
                                $xp_needed_for_next_level = $next_level_xp - $level_info['xp_required'];
                                $progress = ($xp_for_current_level / $xp_needed_for_next_level) * 100;
                            } else {
                                $progress = 100; // Max level
                            }
                            ?>
                            <div class="progress mb-1">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <?php if($next_level_xp): ?>
                                <small class="text-muted">
                                    <?php echo $xp_for_current_level; ?> / <?php echo $xp_needed_for_next_level; ?> XP to Level <?php echo $user->level + 1; ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">Maximum level reached!</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <h5>Total XP</h5>
                            <span class="display-6"><?php echo $user->current_xp; ?></span>
                        </div>
                    </div>
                </div>
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
                        <div class="card h-100 achievement-card <?php echo $is_unlocked ? '' : 'locked'; ?>" 
                             data-bs-toggle="modal" data-bs-target="#achievementModal<?php echo $level['level_number']; ?>">
                            <div class="card-body text-center">
                                <?php if($level['badge_image']): ?>
                                    <img src="../assets/images/badges/<?php echo $level['badge_image']; ?>" alt="<?php echo $level['badge_name']; ?>" class="img-fluid mb-3" style="max-height: 100px;">
                                <?php else: ?>
                                    <div class="achievement-icon">
                                        <?php
                                        $icon = 'trophy';
                                        switch($level['level_number']) {
                                            case 1:
                                                $icon = 'gem';
                                                $color = 'primary';
                                                break;
                                            case 2:
                                                $icon = 'star';
                                                $color = 'info';
                                                break;
                                            case 3:
                                                $icon = 'award';
                                                $color = 'warning';
                                                break;
                                            case 4:
                                                $icon = 'trophy';
                                                $color = 'danger';
                                                break;
                                            case 5:
                                                $icon = 'lightning';
                                                $color = 'success';
                                                break;
                                            default:
                                                $icon = 'award';
                                                $color = 'warning';
                                        }
                                        ?>
                                        <i class="bi bi-<?php echo $icon; ?>-fill text-<?php echo $color; ?>"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h5 class="card-title"><?php echo $level['badge_name']; ?></h5>
                                <h6 class="text-muted">Level <?php echo $level['level_number']; ?></h6>
                                
                                <?php if($is_unlocked): ?>
                                    <span class="badge bg-success">Unlocked</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Locked</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($is_unlocked): ?>
                                <div class="card-footer text-muted text-center">
                                    Earned on <?php echo formatDate($unlocked_achievements[$level['level_number']]['unlocked_at']); ?>
                                </div>
                            <?php else: ?>
                                <div class="card-footer text-muted text-center">
                                    Reach Level <?php echo $level['level_number']; ?> to unlock
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Achievement Modal -->
                    <div class="modal fade" id="achievementModal<?php echo $level['level_number']; ?>" tabindex="-1" aria-labelledby="achievementModalLabel<?php echo $level['level_number']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header <?php echo $is_unlocked ? 'bg-success' : 'bg-secondary'; ?> text-white">
                                    <h5 class="modal-title" id="achievementModalLabel<?php echo $level['level_number']; ?>">
                                        <?php echo $level['badge_name']; ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <?php if($level['badge_image']): ?>
                                        <img src="../assets/images/badges//<?php echo $level['badge_image']; ?>" alt="<?php echo $level['badge_name']; ?>" class="img-fluid mb-3" style="max-height: 150px;">
                                    <?php else: ?>
                                        <div class="achievement-icon" style="font-size: 5rem; margin-bottom: 1.5rem;">
                                            <i class="bi bi-<?php echo $icon; ?>-fill text-<?php echo $color; ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h4><?php echo $level['title']; ?> (Level <?php echo $level['level_number']; ?>)</h4>
                                    <p class="lead"><?php echo $level['badge_name']; ?></p>
                                    <p><?php echo $level['badge_description']; ?></p>
                                    
                                    <?php if($is_unlocked): ?>
                                        <div class="alert alert-success">
                                            <i class="bi bi-check-circle-fill"></i> Unlocked on <?php echo formatDate($unlocked_achievements[$level['level_number']]['unlocked_at'], 'F j, Y'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-secondary">
                                            <i class="bi bi-lock-fill"></i> Reach Level <?php echo $level['level_number']; ?> to unlock this badge
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <span class="badge bg-primary">XP Required: <?php echo $level['xp_required']; ?></span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Special Achievements -->
            <h3 class="mt-5 mb-4">Special Achievements</h3>
            
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle"></i> Complete habits, reach goals, and join challenges to earn special badges!
            </div>

            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                <?php foreach($special_achievements as $achievement): ?>
                    <?php 
                    // Check if this achievement is unlocked
                    $is_unlocked = false;
                    foreach ($unlocked_special_achievements as $unlocked) {
                        if ($unlocked['name'] === $achievement['name']) {
                            $is_unlocked = true;
                            break;
                        }
                    }
                    ?>
                    <div class="col">
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
                                <?php echo $is_unlocked ? 'Achievement Completed!' : 'Keep trying!'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<style>
.achievement-card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.achievement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.achievement-card.locked {
    opacity: 0.7;
}

.achievement-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.level-badge {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    border-radius: 50%;
    background: linear-gradient(135deg, #3a7bd5, #00d2ff);
    color: white;
    font-size: 2.5rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
</style>

<?php
// Include footer
include __DIR__ . '/../views/partials/footer.php';
?>