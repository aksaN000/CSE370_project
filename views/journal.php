<?php
// views/journal.php - Journal view page
// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/JournalController.php';

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

// Initialize journal controller
$journalController = new JournalController();

// Check for search query
$search_term = $_GET['search'] ?? null;
$journals = [];

if($search_term) {
    // Search journals
    $journals = $journalController->searchJournals($user->id, $search_term);
} else {
    // Get all journals with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $journals = $journalController->getAllJournals($user->id, $limit, $offset);
}

// Check if we're adding a new entry
$is_new_entry = isset($_GET['action']) && $_GET['action'] === 'new';

// Check if we're editing an entry
$is_editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$journal_to_edit = null;

if($is_editing) {
    $result = $journalController->getJournalById($_GET['id'], $user->id);
    if($result['success']) {
        $journal_to_edit = $result['journal'];
    } else {
        // Handle error
        $_SESSION['error'] = $result['message'];
        header('Location: journal.php');
        exit;
    }
}

// Get available references for the journal
$available_references = $journalController->getAvailableReferences($user->id);

// Get mood statistics for the mood tracker chart
$mood_stats = $journalController->getMoodStatistics($user->id);

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
                <h1 class="h2">Journal</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if(!$is_new_entry && !$is_editing): ?>
                        <a href="journal.php?action=new" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> New Entry
                        </a>
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
            
            <?php if($is_new_entry || $is_editing): ?>
                <!-- Journal Entry Form -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $is_editing ? 'Edit Journal Entry' : 'New Journal Entry'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="../controllers/process_<?php echo $is_editing ? 'edit' : 'add'; ?>_journal.php" method="POST">
                            <?php if($is_editing): ?>
                                <input type="hidden" name="journal_id" value="<?php echo $journal_to_edit['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="journalTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="journalTitle" name="title" value="<?php echo $is_editing ? $journal_to_edit['title'] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="journalDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="journalDate" name="entry_date" value="<?php echo $is_editing ? $journal_to_edit['entry_date'] : date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mood</label>
                                <div class="d-flex justify-content-between">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mood" id="moodHappy" value="happy" <?php echo ($is_editing && $journal_to_edit['mood'] === 'happy') ? 'checked' : (!$is_editing ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="moodHappy">
                                            <i class="bi bi-emoji-smile fs-4 text-warning"></i> Happy
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mood" id="moodMotivated" value="motivated" <?php echo ($is_editing && $journal_to_edit['mood'] === 'motivated') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="moodMotivated">
                                            <i class="bi bi-emoji-laughing fs-4 text-success"></i> Motivated
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mood" id="moodNeutral" value="neutral" <?php echo ($is_editing && $journal_to_edit['mood'] === 'neutral') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="moodNeutral">
                                            <i class="bi bi-emoji-neutral fs-4 text-secondary"></i> Neutral
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mood" id="moodTired" value="tired" <?php echo ($is_editing && $journal_to_edit['mood'] === 'tired') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="moodTired">
                                            <i class="bi bi-emoji-expressionless fs-4 text-info"></i> Tired
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mood" id="moodFrustrated" value="frustrated" <?php echo ($is_editing && $journal_to_edit['mood'] === 'frustrated') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="moodFrustrated">
                                            <i class="bi bi-emoji-frown fs-4 text-danger"></i> Frustrated
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="mood" id="moodSad" value="sad" <?php echo ($is_editing && $journal_to_edit['mood'] === 'sad') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="moodSad">
                                            <i class="bi bi-emoji-tear fs-4 text-primary"></i> Sad
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="journalContent" class="form-label">Journal Entry</label>
                                <textarea class="form-control" id="journalContent" name="content" rows="8" required><?php echo $is_editing ? $journal_to_edit['content'] : ''; ?></textarea>
                                <div class="form-text">
                                    Reflection Prompts: What went well today? What did you struggle with? What are you grateful for?
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Link to Habits, Goals, or Challenges</label>
                                <div class="row">
                                    <?php if(!empty($available_references['habits'])): ?>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Habits</label>
                                            <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                                <?php foreach($available_references['habits'] as $habit): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="references[habit][]" value="<?php echo $habit['id']; ?>" id="habit<?php echo $habit['id']; ?>"
                                                            <?php 
                                                            if($is_editing) {
                                                                foreach($journal_to_edit['references'] as $ref) {
                                                                    if($ref['type'] === 'habit' && $ref['id'] == $habit['id']) {
                                                                        echo 'checked';
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        >
                                                        <label class="form-check-label" for="habit<?php echo $habit['id']; ?>">
                                                            <?php echo $habit['title']; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($available_references['goals'])): ?>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Goals</label>
                                            <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                                <?php foreach($available_references['goals'] as $goal): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="references[goal][]" value="<?php echo $goal['id']; ?>" id="goal<?php echo $goal['id']; ?>"
                                                            <?php 
                                                            if($is_editing) {
                                                                foreach($journal_to_edit['references'] as $ref) {
                                                                    if($ref['type'] === 'goal' && $ref['id'] == $goal['id']) {
                                                                        echo 'checked';
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        >
                                                        <label class="form-check-label" for="goal<?php echo $goal['id']; ?>">
                                                            <?php echo $goal['title']; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($available_references['challenges'])): ?>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Challenges</label>
                                            <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                                <?php foreach($available_references['challenges'] as $challenge): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="references[challenge][]" value="<?php echo $challenge['id']; ?>" id="challenge<?php echo $challenge['id']; ?>"
                                                            <?php 
                                                            if($is_editing) {
                                                                foreach($journal_to_edit['references'] as $ref) {
                                                                    if($ref['type'] === 'challenge' && $ref['id'] == $challenge['id']) {
                                                                        echo 'checked';
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        >
                                                        <label class="form-check-label" for="challenge<?php echo $challenge['id']; ?>">
                                                            <?php echo $challenge['title']; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="journal.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary"><?php echo $is_editing ? 'Update' : 'Save'; ?> Journal Entry</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Journal List View -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <!-- Search -->
                        <form action="journal.php" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search journal entries..." name="search" value="<?php echo $search_term ?? ''; ?>">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                                <?php if($search_term): ?>
                                    <a href="journal.php" class="btn btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php if(empty($journals)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <?php echo $search_term ? 'No journal entries match your search.' : 'You haven\'t created any journal entries yet.'; ?>
                                <a href="journal.php?action=new">Create your first entry</a> to get started.
                            </div>
                        <?php else: ?>
                            <!-- Journal Entries -->
                            <?php foreach($journals as $journal): ?>
                                <div class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"><?php echo $journal['title']; ?></h5>
                                            <small class="text-muted"><?php echo date('F j, Y', strtotime($journal['entry_date'])); ?></small>
                                        </div>
                                        <div>
                                            <?php
                                            $mood_icon = '';
                                            $mood_color = '';
                                            switch($journal['mood']) {
                                                case 'happy':
                                                    $mood_icon = 'emoji-smile';
                                                    $mood_color = 'text-warning';
                                                    break;
                                                case 'motivated':
                                                    $mood_icon = 'emoji-laughing';
                                                    $mood_color = 'text-success';
                                                    break;
                                                case 'neutral':
                                                    $mood_icon = 'emoji-neutral';
                                                    $mood_color = 'text-secondary';
                                                    break;
                                                case 'tired':
                                                    $mood_icon = 'emoji-expressionless';
                                                    $mood_color = 'text-info';
                                                    break;
                                                case 'frustrated':
                                                    $mood_icon = 'emoji-frown';
                                                    $mood_color = 'text-danger';
                                                    break;
                                                case 'sad':
                                                    $mood_icon = 'emoji-tear';
                                                    $mood_color = 'text-primary';
                                                    break;
                                            }
                                            ?>
                                            <i class="bi bi-<?php echo $mood_icon; ?> fs-4 <?php echo $mood_color; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo nl2br(substr($journal['content'], 0, 200)) . (strlen($journal['content']) > 200 ? '...' : ''); ?></p>
                                        
                                        <?php if(!empty($journal['references'])): ?>
                                            <div class="mt-2">
                                                <?php foreach($journal['references'] as $ref): ?>
                                                    <?php
                                                    $badge_color = '';
                                                    switch($ref['type']) {
                                                        case 'habit':
                                                            $badge_color = 'bg-primary';
                                                            break;
                                                        case 'goal':
                                                            $badge_color = 'bg-warning text-dark';
                                                            break;
                                                        case 'challenge':
                                                            $badge_color = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_color; ?> me-1">
                                                        <?php echo ucfirst($ref['type']); ?>: <?php echo $ref['title']; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#journalContent<?php echo $journal['id']; ?>" aria-expanded="false" aria-controls="journalContent<?php echo $journal['id']; ?>">
                                                Read More
                                            </button>
                                            <a href="journal.php?action=edit&id=<?php echo $journal['id']; ?>" class="btn btn-sm btn-outline-secondary ms-1">
                                                Edit
                                            </a>
                                            <form action="../controllers/process_delete_journal.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this journal entry?');">
                                                <input type="hidden" name="journal_id" value="<?php echo $journal['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1">Delete</button>
                                            </form>
                                        </div>
                                        
                                        <div class="collapse mt-3" id="journalContent<?php echo $journal['id']; ?>">
                                            <div class="card card-body">
                                                <?php echo nl2br($journal['content']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Mood Tracker -->
                        <div class="card mb-4 mood-chart-card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Mood Tracker</h5>
                            </div>
                            <div class="card-body">
                                <?php if(empty($mood_stats)): ?>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle"></i> Start creating journal entries to track your mood over time.
                                    </div>
                                <?php else: ?>
                                    <canvas id="moodChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                                                
                        <!-- Recent Habits & Goals -->
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Journal Reflection Prompts</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i> What went well today?
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-question-circle-fill text-primary me-2"></i> What challenged you?
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-lightbulb-fill text-warning me-2"></i> What did you learn?
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-heart-fill text-danger me-2"></i> What are you grateful for?
                                    </li>
                                    <li class="list-group-item">
                                        <i class="bi bi-arrow-right-circle-fill text-info me-2"></i> What will you focus on tomorrow?
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php if(!empty($mood_stats)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mood chart
        var ctx = document.getElementById('moodChart').getContext('2d');
        var moodChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [
                    <?php 
                    $moods = [];
                    foreach($mood_stats as $mood => $count) {
                        $moods[] = "'" . ucfirst($mood) . "'";
                    }
                    echo implode(', ', $moods);
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        $counts = [];
                        foreach($mood_stats as $mood => $count) {
                            $counts[] = $count;
                        }
                        echo implode(', ', $counts);
                        ?>
                    ],
                    backgroundColor: [
                        '#ffc107', // happy - warning
                        '#28a745', // motivated - success
                        '#6c757d', // neutral - secondary
                        '#17a2b8', // tired - info
                        '#dc3545', // frustrated - danger
                        '#007bff'  // sad - primary
                    ]
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    });
</script>
<?php endif; ?>