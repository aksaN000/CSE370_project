<?php
// views/analytics.php - Analytics view page
// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AnalyticsController.php';


$authController = new AuthController();

// Redirect if not logged in
if(!$authController->isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Get logged in user
$user = $authController->getLoggedInUser();

// Initialize analytics controller
$analyticsController = new AnalyticsController();

// Get summary statistics
$summary_stats = $analyticsController->getSummaryStats($user->id);

// Get date range for filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get habit progress data
$habit_progress = $analyticsController->getHabitProgressByDate($user->id, $start_date, $end_date);

// Get habits by category
$habits_by_category = $analyticsController->getHabitsByCategory($user->id);

// Get completion rate by category
$completion_by_category = $analyticsController->getCompletionRateByCategory($user->id);

// Get mood distribution
$mood_distribution = $analyticsController->getMoodDistributionOverTime($user->id, $start_date, $end_date);

// Get XP progress
$xp_progress = $analyticsController->getXPProgressOverTime($user->id);

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
                <h1 class="h2">Analytics & Insights</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <form action="analytics.php" method="GET" class="row g-3">
                        <div class="col-auto">
                            <label for="start_date" class="visually-hidden">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-auto">
                            <label for="end_date" class="visually-hidden">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Habit Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Total Habits</div>
                                <div class="badge bg-primary rounded-pill"><?php echo $summary_stats['habits']['total_habits']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Total Completions</div>
                                <div class="badge bg-success rounded-pill"><?php echo $summary_stats['habits']['total_completions']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Active Days</div>
                                <div class="badge bg-info rounded-pill"><?php echo $summary_stats['habits']['unique_days']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Today's Completions</div>
                                <div class="badge bg-warning text-dark rounded-pill"><?php echo $summary_stats['habits']['today_completions']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Goal Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Total Goals</div>
                                <div class="badge bg-warning text-dark rounded-pill"><?php echo $summary_stats['goals']['total_goals']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Completed Goals</div>
                                <div class="badge bg-success rounded-pill"><?php echo $summary_stats['goals']['completed_goals']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Active Goals</div>
                                <div class="badge bg-info rounded-pill"><?php echo $summary_stats['goals']['active_goals']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Missed Goals</div>
                                <div class="badge bg-danger rounded-pill"><?php echo $summary_stats['goals']['missed_goals']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Streak Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Longest Streak</h6>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-trophy-fill text-warning fs-1"></i>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold"><?php echo $summary_stats['streaks']['longest']['streak_days']; ?> days</div>
                                        <small class="text-muted"><?php echo $summary_stats['streaks']['longest']['habit_title']; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6>Current Streak</h6>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-fire text-danger fs-1"></i>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold"><?php echo $summary_stats['streaks']['current']['streak_days']; ?> days</div>
                                        <small class="text-muted"><?php echo $summary_stats['streaks']['current']['habit_title']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">XP Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Current Level</h6>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-star-fill text-warning fs-1"></i>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold">Level <?php echo $summary_stats['user']['level']; ?></div>
                                        <small class="text-muted"><?php echo $summary_stats['user']['current_xp']; ?> total XP</small>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6>XP Growth</h6>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-graph-up-arrow text-success fs-1"></i>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold"><?php echo $summary_stats['user']['avg_xp_per_day']; ?> XP/day</div>
                                        <small class="text-muted">Daily average</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Habit Completions Over Time</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="habitProgressChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Habits by Category</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($habits_by_category)): ?>
                                <div class="alert alert-info">You don't have any categorized habits yet.</div>
                            <?php else: ?>
                                <canvas id="habitsByCategoryChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">XP Progress</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($xp_progress)): ?>
                                <div class="alert alert-info">You don't have any XP data yet. Complete habits and goals to earn XP!</div>
                            <?php else: ?>
                                <canvas id="xpProgressChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Mood Tracker</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($mood_distribution)): ?>
                                <div class="alert alert-info">You don't have any journal entries yet. Start journaling to track your mood!</div>
                            <?php else: ?>
                                <canvas id="moodDistributionChart"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Completion Rate By Category -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Completion Rate by Category</h5>
                        </div>
                        <div class="card-body">
                            <?php if(empty($completion_by_category)): ?>
                                <div class="alert alert-info">You don't have enough data to show completion rates by category.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Completions</th>
                                                <th>Total Possible</th>
                                                <th>Rate</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($completion_by_category as $category): ?>
                                                <tr>
                                                    <td><?php echo $category['category']; ?></td>
                                                    <td><?php echo $category['completed']; ?></td>
                                                    <td><?php echo $category['total']; ?></td>
                                                    <td><?php echo $category['completion_rate']; ?>%</td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar <?php echo $category['completion_rate'] >= 75 ? 'bg-success' : ($category['completion_rate'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>" role="progressbar" style="width: <?php echo $category['completion_rate']; ?>%" aria-valuenow="<?php echo $category['completion_rate']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
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
            
            <!-- Most Completed Habit -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Your Top Habits</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100 border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">Most Completed Habit</h5>
                                        </div>
                                        <div class="card-body text-center">
                                            <i class="bi bi-award-fill text-warning display-1 mb-3"></i>
                                            <h3><?php echo $summary_stats['most_completed_habit']['habit_title']; ?></h3>
                                            <p class="lead"><?php echo $summary_stats['most_completed_habit']['completion_count']; ?> completions</p>
                                            <p class="text-muted">Keep up the good work!</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card h-100 border-success">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">Journal Activity</h5>
                                        </div>
                                        <div class="card-body text-center">
                                            <i class="bi bi-journal-text text-primary display-1 mb-3"></i>
                                            <h3><?php echo $summary_stats['journal']['total_entries']; ?> Journal Entries</h3>
                                            <p class="lead"><?php echo $summary_stats['journal']['avg_entries_per_week']; ?> entries per week</p>
                                            <p class="text-muted">
                                                <?php if($summary_stats['journal']['total_entries'] > 0): ?>
                                                    First entry: <?php echo date('M j, Y', strtotime($summary_stats['journal']['first_entry'])); ?>
                                                <?php else: ?>
                                                    Start journaling to track your progress!
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Habit Progress Chart
        var habitProgressCtx = document.getElementById('habitProgressChart').getContext('2d');
        var habitProgressData = {
            labels: <?php echo json_encode(array_column($habit_progress, 'date')); ?>,
            datasets: [{
                label: 'Daily Habit Completions',
                data: <?php echo json_encode(array_column($habit_progress, 'count')); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                tension: 0.4
            }]
        };
        var habitProgressChart = new Chart(habitProgressCtx, {
            type: 'line',
            data: habitProgressData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const date = new Date(context[0].label);
                                return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                            }
                        }
                    }
                }
            }
        });
        
        <?php if(!empty($habits_by_category)): ?>
        // Habits by Category Chart
        var categoryCtx = document.getElementById('habitsByCategoryChart').getContext('2d');
        var categoryData = {
            labels: <?php echo json_encode(array_column($habits_by_category, 'category')); ?>,
            datasets: [{
                label: 'Number of Habits',
                data: <?php echo json_encode(array_column($habits_by_category, 'count')); ?>,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(13, 202, 240, 0.7)',
                    'rgba(102, 16, 242, 0.7)'
                ],
                borderWidth: 1
            }]
        };
        var categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: categoryData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if(!empty($xp_progress)): ?>
        // XP Progress Chart
        var xpCtx = document.getElementById('xpProgressChart').getContext('2d');
        var xpData = {
            labels: <?php echo json_encode(array_column($xp_progress, 'date')); ?>,
            datasets: [{
                label: 'Cumulative XP',
                data: <?php echo json_encode(array_column($xp_progress, 'cumulative_xp')); ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.2)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Daily XP',
                data: <?php echo json_encode(array_column($xp_progress, 'daily_xp')); ?>,
                backgroundColor: 'rgba(13, 202, 240, 0.5)',
                borderColor: 'rgba(13, 202, 240, 1)',
                borderWidth: 1,
                type: 'bar',
                yAxisID: 'y1'
            }]
        };
        var xpChart = new Chart(xpCtx, {
            type: 'line',
            data: xpData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Cumulative XP'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Daily XP'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        
        <?php if(!empty($mood_distribution)): ?>
        // Mood Distribution Chart
        var moodCtx = document.getElementById('moodDistributionChart').getContext('2d');
        var moodData = {
            labels: <?php echo json_encode(array_column($mood_distribution, 'date')); ?>,
            datasets: [{
                label: 'Happy',
                data: <?php echo json_encode(array_column($mood_distribution, 'happy')); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.5)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Motivated',
                data: <?php echo json_encode(array_column($mood_distribution, 'motivated')); ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.5)',
                borderColor: 'rgba(25, 135, 84, 1)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Neutral',
                data: <?php echo json_encode(array_column($mood_distribution, 'neutral')); ?>,
                backgroundColor: 'rgba(108, 117, 125, 0.5)',
                borderColor: 'rgba(108, 117, 125, 1)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Tired',
                data: <?php echo json_encode(array_column($mood_distribution, 'tired')); ?>,
                backgroundColor: 'rgba(13, 202, 240, 0.5)',
                borderColor: 'rgba(13, 202, 240, 1)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Frustrated',
                data: <?php echo json_encode(array_column($mood_distribution, 'frustrated')); ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.5)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1,
                stack: 'Stack 0'
            }, {
                label: 'Sad',
                data: <?php echo json_encode(array_column($mood_distribution, 'sad')); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.5)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                stack: 'Stack 0'
            }]
        };
        var moodChart = new Chart(moodCtx, {
            type: 'bar',
            data: moodData,
            options: {
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include __DIR__ . '/../views/partials/footer.php';

?>