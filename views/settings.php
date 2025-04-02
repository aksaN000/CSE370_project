<?php
// views/settings.php - Application settings page

// Include auth controller
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../controllers/SettingsController.php';

$authController = new AuthController();

// Redirect if not logged in
if(!$authController->isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

// Get logged in user
$user = $authController->getLoggedInUser();

// Initialize settings controller
$settingsController = new SettingsController();

// Process theme settings update
$theme_updated = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theme'])) {
    $theme = $_POST['theme'] ?? 'light';
    $color_scheme = $_POST['color_scheme'] ?? 'default';
    $enable_animations = isset($_POST['enable_animations']) ? 1 : 0;
    $compact_mode = isset($_POST['compact_mode']) ? 1 : 0;
    
    // Save theme settings
    $result = $settingsController->updateThemeSettings($user->id, $theme, $color_scheme, $enable_animations, $compact_mode);
    
    // Set the theme in session and cookies
    $_SESSION['user_theme'] = $theme;
    $_SESSION['color_scheme'] = $color_scheme;
    $_SESSION['enable_animations'] = $enable_animations;
    $_SESSION['compact_mode'] = $compact_mode;
    
    setcookie('user_theme', $theme, time() + (86400 * 365), "/"); // 1-year cookie
    setcookie('color_scheme', $color_scheme, time() + (86400 * 365), "/");
    setcookie('enable_animations', $enable_animations, time() + (86400 * 365), "/");
    setcookie('compact_mode', $compact_mode, time() + (86400 * 365), "/");
    
    $theme_updated = $result['success'];
    $theme_message = $result['message'];
}

// Process notification settings update
$notification_updated = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $habit_reminders = isset($_POST['habit_reminders']) ? 1 : 0;
    $goal_updates = isset($_POST['goal_updates']) ? 1 : 0;
    $challenge_notifications = isset($_POST['challenge_notifications']) ? 1 : 0;
    $level_up_notifications = isset($_POST['level_up_notifications']) ? 1 : 0;
    
    $email_daily = isset($_POST['email_daily']) ? 1 : 0;
    $email_weekly = isset($_POST['email_weekly']) ? 1 : 0;
    $email_reminders = isset($_POST['email_reminders']) ? 1 : 0;
    
    // Save notification preferences
    $result = $settingsController->updateNotificationSettings(
        $user->id, 
        $email_notifications, 
        $habit_reminders, 
        $goal_updates, 
        $challenge_notifications, 
        $level_up_notifications,
        $email_daily,
        $email_weekly,
        $email_reminders
    );
    
    $notification_updated = $result['success'];
    $notification_message = $result['message'];
}

// Process privacy settings update
$privacy_updated = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_privacy'])) {
    $public_profile = isset($_POST['public_profile']) ? 1 : 0;
    $show_stats = isset($_POST['show_stats']) ? 1 : 0;
    $show_achievements = isset($_POST['show_achievements']) ? 1 : 0;
    $analytics_consent = isset($_POST['analytics_consent']) ? 1 : 0;
    
    // Save privacy preferences
    $result = $settingsController->updatePrivacySettings(
        $user->id, 
        $public_profile, 
        $show_stats, 
        $show_achievements, 
        $analytics_consent
    );
    
    $privacy_updated = $result['success'];
    $privacy_message = $result['message'];
}

// Process data export
$data_exported = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_data'])) {
    $format = $_POST['export_format'] ?? 'json';
    
    // Export data
    $result = $settingsController->exportUserData($user->id, $format);
    
    $data_exported = $result['success'];
    $data_export_message = $result['message'];
    
    if($data_exported) {
        // Set headers to download file
        header('Content-Type: application/' . $format);
        header('Content-Disposition: attachment; filename="habit_tracker_data_' . date('Y-m-d') . '.' . $format . '"');
        echo $result['data'];
        exit;
    }
}

// Process data import
$data_imported = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_data']) && isset($_FILES['import_file'])) {
    // Import data
    $result = $settingsController->importUserData($user->id, $_FILES['import_file']);
    
    $data_imported = $result['success'];
    $data_import_message = $result['message'];
}

// Process data reset
$data_reset = false;
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_data'])) {
    $reset_type = $_POST['reset_type'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    
    if($confirmation === 'CONFIRM') {
        // Reset data
        $result = $settingsController->resetUserData($user->id, $reset_type);
        
        $data_reset = $result['success'];
        $data_reset_message = $result['message'];
    } else {
        $data_reset = false;
        $data_reset_message = 'Please type CONFIRM to reset your data.';
    }
}

// Get current theme settings
$userSettings = $settingsController->getUserSettings($user->id);
$current_theme = $userSettings['theme'] ?? 'light';
$current_color_scheme = $userSettings['color_scheme'] ?? 'default';
$enable_animations = $userSettings['enable_animations'] ?? 1;
$compact_mode = $userSettings['compact_mode'] ?? 0;

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
                <h1 class="h2">Settings</h1>
            </div>
            
            <!-- Settings Tabs -->
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="true">Appearance</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab" aria-controls="notifications" aria-selected="false">Notifications</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab" aria-controls="privacy" aria-selected="false">Privacy</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab" aria-controls="data" aria-selected="false">Data & Backup</button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="settingsTabsContent">
                <!-- Appearance Tab -->
                <div class="tab-pane fade show active" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                    <?php if($theme_updated): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $theme_message ?? 'Theme settings updated successfully!'; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Theme Settings</h5>
                        </div>
                        <div class="card-body">
                            <form action="settings.php" method="POST">
                                <input type="hidden" name="update_theme" value="1">
                                
                                <div class="mb-4">
                                    <label class="form-label">Select Theme</label>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" id="themeLight" value="light" <?php echo ($current_theme === 'light') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="themeLight">
                                                    <div class="card">
                                                        <div class="card-body text-center">
                                                            <i class="bi bi-brightness-high fs-3 text-warning mb-2"></i>
                                                            <h6>Light Theme</h6>
                                                            <small class="text-muted">Default bright theme</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" id="themeDark" value="dark" <?php echo ($current_theme === 'dark') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="themeDark">
                                                    <div class="card bg-dark text-white">
                                                        <div class="card-body text-center">
                                                            <i class="bi bi-moon-stars fs-3 text-info mb-2"></i>
                                                            <h6>Dark Theme</h6>
                                                            <small class="text-muted">Easier on the eyes</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="theme" id="themeSystem" value="system" <?php echo ($current_theme === 'system') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="themeSystem">
                                                    <div class="card">
                                                        <div class="card-body text-center">
                                                            <i class="bi bi-display fs-3 text-primary mb-2"></i>
                                                            <h6>System Theme</h6>
                                                            <small class="text-muted">Follow system settings</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Color Scheme</label>
                                    <div class="row">
                                        <div class="col-md-2 col-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="color_scheme" id="colorDefault" value="default" <?php echo ($current_color_scheme === 'default') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="colorDefault">
                                                    <div class="p-3 rounded-circle bg-primary"></div>
                                                    <small class="d-block mt-1 text-center">Default</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="color_scheme" id="colorTeal" value="teal" <?php echo ($current_color_scheme === 'teal') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="colorTeal">
                                                    <div class="p-3 rounded-circle" style="background-color: #20c997;"></div>
                                                    <small class="d-block mt-1 text-center">Teal</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="color_scheme" id="colorIndigo" value="indigo" <?php echo ($current_color_scheme === 'indigo') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="colorIndigo">
                                                    <div class="p-3 rounded-circle" style="background-color: #6610f2;"></div>
                                                    <small class="d-block mt-1 text-center">Indigo</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="color_scheme" id="colorRose" value="rose" <?php echo ($current_color_scheme === 'rose') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="colorRose">
                                                    <div class="p-3 rounded-circle" style="background-color: #e83e8c;"></div>
                                                    <small class="d-block mt-1 text-center">Rose</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="color_scheme" id="colorAmber" value="amber" <?php echo ($current_color_scheme === 'amber') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="colorAmber">
                                                    <div class="p-3 rounded-circle" style="background-color: #fd7e14;"></div>
                                                    <small class="d-block mt-1 text-center">Amber</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="color_scheme" id="colorEmerald" value="emerald" <?php echo ($current_color_scheme === 'emerald') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="colorEmerald">
                                                    <div class="p-3 rounded-circle" style="background-color: #28a745;"></div>
                                                    <small class="d-block mt-1 text-center">Emerald</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enableAnimations" name="enable_animations" <?php echo $enable_animations ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enableAnimations">Enable Animations</label>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="compactMode" name="compact_mode" <?php echo $compact_mode ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="compactMode">Compact Mode</label>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Save Appearance Settings</button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">Theme changes will be applied immediately and remembered for your next visit.</small>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Tab -->
                <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                        <?php if(isset($notification_updated)): ?>
                            <div class="alert alert-<?php echo $notification_updated ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo $notification_message ?? 'Notification settings updated successfully!'; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">In-App Notification Preferences</h5>
                            </div>
                            <div class="card-body">
                                <form action="settings.php" method="POST" id="notificationForm">
                                    <input type="hidden" name="update_notifications" value="1">
                                    
                                    <h6 class="mb-3">Notification Types</h6>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="habitReminders" name="habit_reminders" <?php echo $userSettings['habit_reminders'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="habitReminders">Daily Habit Reminders</label>
                                        <div class="form-text">Receive reminders for incomplete habits</div>
                                    </div>
                                    
                                    <div class="collapse mb-3" id="habitReminderOptions">
                                        <div class="card card-body bg-light border-0">
                                            <div class="mb-3">
                                                <label class="form-label">Reminder Time</label>
                                                <select class="form-select" name="habit_reminder_time">
                                                    <option value="morning" <?php echo ($userSettings['habit_reminder_time'] ?? 'morning') == 'morning' ? 'selected' : ''; ?>>Morning (8:00 AM)</option>
                                                    <option value="afternoon" <?php echo ($userSettings['habit_reminder_time'] ?? 'morning') == 'afternoon' ? 'selected' : ''; ?>>Afternoon (2:00 PM)</option>
                                                    <option value="evening" <?php echo ($userSettings['habit_reminder_time'] ?? 'morning') == 'evening' ? 'selected' : ''; ?>>Evening (7:00 PM)</option>
                                                    <option value="custom" <?php echo ($userSettings['habit_reminder_time'] ?? 'morning') == 'custom' ? 'selected' : ''; ?>>Custom Time</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3" id="customTimeGroup" style="display: <?php echo ($userSettings['habit_reminder_time'] ?? 'morning') == 'custom' ? 'block' : 'none'; ?>;">
                                                <label class="form-label">Custom Reminder Time</label>
                                                <input type="time" class="form-control" name="habit_reminder_custom_time" value="<?php echo $userSettings['habit_reminder_custom_time'] ?? '08:00'; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="goalUpdates" name="goal_updates" <?php echo $userSettings['goal_updates'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="goalUpdates">Goal Updates</label>
                                        <div class="form-text">Get updates about upcoming goal deadlines</div>
                                    </div>
                                    
                                    <div class="collapse mb-3" id="goalUpdateOptions">
                                        <div class="card card-body bg-light border-0">
                                            <div class="mb-3">
                                                <label class="form-label">Notify me when:</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="goalUpdateDeadline" name="goal_update_deadline" <?php echo ($userSettings['goal_update_deadline'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="goalUpdateDeadline">
                                                        Goal deadline is approaching
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="goalUpdateMilestone" name="goal_update_milestone" <?php echo ($userSettings['goal_update_milestone'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="goalUpdateMilestone">
                                                        Goal reaches a milestone (25%, 50%, 75%)
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="goalUpdateExpired" name="goal_update_expired" <?php echo ($userSettings['goal_update_expired'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="goalUpdateExpired">
                                                        Goal has expired without completion
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="challengeNotifications" name="challenge_notifications" <?php echo $userSettings['challenge_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="challengeNotifications">Challenge Notifications</label>
                                        <div class="form-text">Receive updates about challenges you've joined</div>
                                    </div>
                                    
                                    <div class="collapse mb-3" id="challengeNotificationOptions">
                                        <div class="card card-body bg-light border-0">
                                            <div class="mb-3">
                                                <label class="form-label">Notify me about:</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="challengeTaskReminders" name="challenge_task_reminders" <?php echo ($userSettings['challenge_task_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="challengeTaskReminders">
                                                        Incomplete challenge tasks
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="challengeNewParticipants" name="challenge_new_participants" <?php echo ($userSettings['challenge_new_participants'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="challengeNewParticipants">
                                                        New challenge participants
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="challengeCompletion" name="challenge_completion" <?php echo ($userSettings['challenge_completion'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="challengeCompletion">
                                                        Challenge completion status
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="levelUpNotifications" name="level_up_notifications" <?php echo $userSettings['level_up_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="levelUpNotifications">Level Up Notifications</label>
                                        <div class="form-text">Get notified when you level up</div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h6 class="mb-3">Notification Display</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notification Sound</label>
                                        <select class="form-select" name="notification_sound">
                                            <option value="default" <?php echo ($userSettings['notification_sound'] ?? 'default') == 'default' ? 'selected' : ''; ?>>Default</option>
                                            <option value="chime" <?php echo ($userSettings['notification_sound'] ?? 'default') == 'chime' ? 'selected' : ''; ?>>Chime</option>
                                            <option value="bell" <?php echo ($userSettings['notification_sound'] ?? 'default') == 'bell' ? 'selected' : ''; ?>>Bell</option>
                                            <option value="success" <?php echo ($userSettings['notification_sound'] ?? 'default') == 'success' ? 'selected' : ''; ?>>Success</option>
                                            <option value="none" <?php echo ($userSettings['notification_sound'] ?? 'default') == 'none' ? 'selected' : ''; ?>>None (Silent)</option>
                                        </select>
                                        <div class="form-text">Choose a sound for in-app notifications</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Notification Display Duration</label>
                                        <select class="form-select" name="notification_duration">
                                            <option value="short" <?php echo ($userSettings['notification_duration'] ?? 'medium') == 'short' ? 'selected' : ''; ?>>Short (3 seconds)</option>
                                            <option value="medium" <?php echo ($userSettings['notification_duration'] ?? 'medium') == 'medium' ? 'selected' : ''; ?>>Medium (5 seconds)</option>
                                            <option value="long" <?php echo ($userSettings['notification_duration'] ?? 'medium') == 'long' ? 'selected' : ''; ?>>Long (8 seconds)</option>
                                        </select>
                                        <div class="form-text">How long notifications stay on screen</div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h6 class="mb-3">Email Notifications</h6>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="emailNotifications" name="email_notifications" <?php echo $userSettings['email_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                        <div class="form-text">Receive notifications by email</div>
                                    </div>
                                    
                                    <div class="mb-3 ps-4" id="emailNotificationOptions">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="emailDaily" name="email_daily" <?php echo $userSettings['email_daily'] ? 'checked' : ''; ?> <?php echo !$userSettings['email_notifications'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="emailDaily">
                                                Daily Digest
                                            </label>
                                            <div class="form-text">Receive a summary of your day's activity</div>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="emailWeekly" name="email_weekly" <?php echo $userSettings['email_weekly'] ? 'checked' : ''; ?> <?php echo !$userSettings['email_notifications'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="emailWeekly">
                                                Weekly Summary
                                            </label>
                                            <div class="form-text">Receive a weekly progress report</div>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="emailReminders" name="email_reminders" <?php echo $userSettings['email_reminders'] ? 'checked' : ''; ?> <?php echo !$userSettings['email_notifications'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="emailReminders">
                                                Missed Habit Reminders
                                            </label>
                                            <div class="form-text">Get emailed about habits you haven't completed</div>
                                        </div>
                                        
                                        <div class="mt-3" id="emailTimeOptions" <?php echo !$userSettings['email_notifications'] ? 'style="display:none;"' : ''; ?>>
                                            <label class="form-label">Preferred email time</label>
                                            <select class="form-select" name="email_time" <?php echo !$userSettings['email_notifications'] ? 'disabled' : ''; ?>>
                                                <option value="morning" <?php echo ($userSettings['email_time'] ?? 'morning') == 'morning' ? 'selected' : ''; ?>>Morning (8:00 AM)</option>
                                                <option value="afternoon" <?php echo ($userSettings['email_time'] ?? 'morning') == 'afternoon' ? 'selected' : ''; ?>>Afternoon (2:00 PM)</option>
                                                <option value="evening" <?php echo ($userSettings['email_time'] ?? 'morning') == 'evening' ? 'selected' : ''; ?>>Evening (7:00 PM)</option>
                                            </select>
                                            <div class="form-text">When to send email notifications</div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">Email notifications will be sent to: <?php echo $user->email; ?></small>
                            </div>
                        </div>
                    </div>

                    <script>
                    // JavaScript for handling notification options
                    document.addEventListener('DOMContentLoaded', function() {
                        // Handle habit reminder options
                        const habitReminders = document.getElementById('habitReminders');
                        const habitReminderOptions = document.getElementById('habitReminderOptions');
                        
                        if(habitReminders && habitReminderOptions) {
                            habitReminders.addEventListener('change', function() {
                                if(this.checked) {
                                    new bootstrap.Collapse(habitReminderOptions).show();
                                } else {
                                    new bootstrap.Collapse(habitReminderOptions).hide();
                                }
                            });
                            
                            // Initialize on page load
                            if(habitReminders.checked) {
                                new bootstrap.Collapse(habitReminderOptions).show();
                            }
                        }
                        
                        // Handle goal update options
                        const goalUpdates = document.getElementById('goalUpdates');
                        const goalUpdateOptions = document.getElementById('goalUpdateOptions');
                        
                        if(goalUpdates && goalUpdateOptions) {
                            goalUpdates.addEventListener('change', function() {
                                if(this.checked) {
                                    new bootstrap.Collapse(goalUpdateOptions).show();
                                } else {
                                    new bootstrap.Collapse(goalUpdateOptions).hide();
                                }
                            });
                            
                            // Initialize on page load
                            if(goalUpdates.checked) {
                                new bootstrap.Collapse(goalUpdateOptions).show();
                            }
                        }
                        
                        // Handle challenge notification options
                        const challengeNotifications = document.getElementById('challengeNotifications');
                        const challengeNotificationOptions = document.getElementById('challengeNotificationOptions');
                        
                        if(challengeNotifications && challengeNotificationOptions) {
                            challengeNotifications.addEventListener('change', function() {
                                if(this.checked) {
                                    new bootstrap.Collapse(challengeNotificationOptions).show();
                                } else {
                                    new bootstrap.Collapse(challengeNotificationOptions).hide();
                                }
                            });
                            
                            // Initialize on page load
                            if(challengeNotifications.checked) {
                                new bootstrap.Collapse(challengeNotificationOptions).show();
                            }
                        }
                        
                        // Handle email notification options
                        const emailNotifications = document.getElementById('emailNotifications');
                        const emailOptions = document.querySelectorAll('#emailDaily, #emailWeekly, #emailReminders, select[name="email_time"]');
                        const emailTimeOptions = document.getElementById('emailTimeOptions');
                        
                        if(emailNotifications && emailOptions.length > 0) {
                            emailNotifications.addEventListener('change', function() {
                                emailOptions.forEach(option => {
                                    option.disabled = !this.checked;
                                });
                                
                                if(emailTimeOptions) {
                                    emailTimeOptions.style.display = this.checked ? 'block' : 'none';
                                }
                            });
                        }
                        
                        // Handle custom time option for habit reminders
                        const habitReminderTime = document.querySelector('select[name="habit_reminder_time"]');
                        const customTimeGroup = document.getElementById('customTimeGroup');
                        
                        if(habitReminderTime && customTimeGroup) {
                            habitReminderTime.addEventListener('change', function() {
                                customTimeGroup.style.display = this.value === 'custom' ? 'block' : 'none';
                            });
                        }
                    });
                    </script>
                
                <!-- Privacy Tab -->
                <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                        <?php if(isset($privacy_updated)): ?>
                            <div class="alert alert-<?php echo $privacy_updated ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo $privacy_message ?? 'Privacy settings updated successfully!'; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Privacy Settings</h5>
                            </div>
                            <div class="card-body">
                            <form action="../controllers/process_update_privacy.php" method="POST" id="privacyForm">
                                <input type="hidden" name="update_privacy" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label">Profile Visibility</label>
                                    <select class="form-select" name="profile_visibility" id="profileVisibility">
                                        <option value="private" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'selected' : ''; ?>>Private (Only you)</option>
                                        <option value="friends" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'friends' ? 'selected' : ''; ?>>Friends Only</option>
                                        <option value="members" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'members' ? 'selected' : ''; ?>>Community Members</option>
                                        <option value="public" <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'public' ? 'selected' : ''; ?>>Public</option>
                                    </select>
                                    <div class="form-text">Control who can view your profile</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="publicProfile" name="public_profile" <?php echo $userSettings['public_profile'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="publicProfile">Legacy Public Profile Setting</label>
                                    <div class="form-text">This setting is maintained for compatibility. Use the Profile Visibility setting above for more control.</div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">Profile Information Visibility</h6>
                                <p class="text-muted mb-3">Choose what information is visible to others when your profile is viewable:</p>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input profile-dependent" type="checkbox" role="switch" id="showStats" name="show_stats" <?php echo $userSettings['show_stats'] ? 'checked' : ''; ?> <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="showStats">Show Statistics</label>
                                    <div class="form-text">Display your activity statistics on your profile</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input profile-dependent" type="checkbox" role="switch" id="showAchievements" name="show_achievements" <?php echo $userSettings['show_achievements'] ? 'checked' : ''; ?> <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="showAchievements">Show Achievements</label>
                                    <div class="form-text">Display your badges and achievements on your profile</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input profile-dependent" type="checkbox" role="switch" id="showHabits" name="show_habits" <?php echo ($userSettings['show_habits'] ?? 0) ? 'checked' : ''; ?> <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="showHabits">Show Habits</label>
                                    <div class="form-text">Display your habits on your profile</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input profile-dependent" type="checkbox" role="switch" id="showGoals" name="show_goals" <?php echo ($userSettings['show_goals'] ?? 0) ? 'checked' : ''; ?> <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="showGoals">Show Goals</label>
                                    <div class="form-text">Display your goals on your profile</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input profile-dependent" type="checkbox" role="switch" id="showChallenges" name="show_challenges" <?php echo ($userSettings['show_challenges'] ?? 1) ? 'checked' : ''; ?> <?php echo ($userSettings['profile_visibility'] ?? 'private') == 'private' ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="showChallenges">Show Challenges</label>
                                    <div class="form-text">Display the challenges you've participated in</div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">Community Interaction</h6>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="allowChallengeInvites" name="allow_challenge_invites" <?php echo ($userSettings['allow_challenge_invites'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allowChallengeInvites">Allow Challenge Invitations</label>
                                    <div class="form-text">Allow other users to invite you to challenges</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="showInLeaderboards" name="show_in_leaderboards" <?php echo ($userSettings['show_in_leaderboards'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="showInLeaderboards">Show in Leaderboards</label>
                                    <div class="form-text">Display your name in public leaderboards</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="allowFriendRequests" name="allow_friend_requests" <?php echo ($userSettings['allow_friend_requests'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allowFriendRequests">Allow Friend Requests</label>
                                    <div class="form-text">Allow other users to send you friend requests</div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">Data & Privacy</h6>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="analyticsConsent" name="analytics_consent" <?php echo $userSettings['analytics_consent'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="analyticsConsent">Usage Analytics</label>
                                    <div class="form-text">Allow collection of anonymous usage data to improve the app</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="featureImprovementConsent" name="feature_improvement_consent" <?php echo ($userSettings['feature_improvement_consent'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="featureImprovementConsent">Feature Improvement Participation</label>
                                    <div class="form-text">Participate in new feature testing and provide feedback</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="dataSharing" name="data_sharing" <?php echo ($userSettings['data_sharing'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="dataSharing">Data Sharing for Research</label>
                                    <div class="form-text">Allow anonymized data to be used for habit formation research</div>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="alert alert-info">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <i class="bi bi-info-circle-fill fs-4"></i>
                                            </div>
                                            <div>
                                                <h6>Privacy Notice</h6>
                                                <p class="mb-0">Your data is always kept secure and we never share your personal information with third parties without your explicit consent. Read our <a href="#" class="alert-link">Privacy Policy</a> for more information.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                                </div>
                            </form>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">Last updated: <?php echo formatDate($userSettings['updated_at'] ?? date('Y-m-d H:i:s'), 'F j, Y, g:i a'); ?></small>
                            </div>
                        </div>
                    </div>

                    <script>
                    // JavaScript for handling privacy settings
                    document.addEventListener('DOMContentLoaded', function() {
                        // Handle profile visibility dependencies
                        const profileVisibility = document.getElementById('profileVisibility');
                        const profileDependentInputs = document.querySelectorAll('.profile-dependent');
                        
                        if(profileVisibility && profileDependentInputs.length > 0) {
                            profileVisibility.addEventListener('change', function() {
                                const isPrivate = this.value === 'private';
                                profileDependentInputs.forEach(input => {
                                    input.disabled = isPrivate;
                                    if(isPrivate) {
                                        input.checked = false;
                                    }
                                });
                            });
                        }
                        
                        // Sync the legacy public profile setting with the new visibility dropdown
                        const publicProfile = document.getElementById('publicProfile');
                        if(profileVisibility && publicProfile) {
                            // When profile visibility changes, update the public profile checkbox
                            profileVisibility.addEventListener('change', function() {
                                publicProfile.checked = this.value !== 'private';
                            });
                            
                            // When public profile checkbox changes, update the profile visibility dropdown
                            publicProfile.addEventListener('change', function() {
                                if(this.checked) {
                                    if(profileVisibility.value === 'private') {
                                        profileVisibility.value = 'public';
                                        // Trigger the change event to update dependent inputs
                                        profileVisibility.dispatchEvent(new Event('change'));
                                    }
                                } else {
                                    profileVisibility.value = 'private';
                                    // Trigger the change event to update dependent inputs
                                    profileVisibility.dispatchEvent(new Event('change'));
                                }
                            });
                        }
                    });
                    </script>
                
                <!-- Data & Backup Tab -->
                <div class="tab-pane fade" id="data" role="tabpanel" aria-labelledby="data-tab">
                    <?php if($data_exported): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $data_export_message ?? 'Data exported successfully!'; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($data_imported): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $data_import_message ?? 'Data imported successfully!'; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($data_reset_message)): ?>
                        <div class="alert alert-<?php echo $data_reset ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo $data_reset_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Data Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6>Export Your Data</h6>
                                <p class="text-muted">Download all your data in CSV or JSON format.</p>
                                <form action="settings.php" method="POST">
                                    <input type="hidden" name="export_data" value="1">
                                    <div class="d-flex">
                                        <select name="export_format" class="form-select me-2" style="width: auto;">
                                            <option value="json">JSON Format</option>
                                            <option value="csv">CSV Format</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="bi bi-download"></i> Export Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="mb-4">
                                <h6>Import Data</h6>
                                <p class="text-muted">Import your data from a JSON or CSV file.</p>
                                <form action="settings.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="import_data" value="1">
                                    <div class="input-group mb-3">
                                        <input type="file" class="form-control" id="importFile" name="import_file" accept=".json,.csv" required>
                                        <button class="btn btn-outline-primary" type="submit">Import</button>
                                    </div>
                                    <small class="form-text text-muted">The imported data will be merged with your existing data.</small>
                                </form>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="mb-4">
                                <h6>Reset Data</h6>
                                <p class="text-muted">Reset specific data or your entire account. <strong>This action cannot be undone.</strong></p>
                                
                                <form action="settings.php" method="POST" class="mb-3" onsubmit="return confirm('Are you sure you want to reset this data? This action cannot be undone.');">
                                    <input type="hidden" name="reset_data" value="1">
                                    <div class="mb-3">
                                        <select name="reset_type" class="form-select">
                                            <option value="habits">Reset All Habits</option>
                                            <option value="goals">Reset All Goals</option>
                                            <option value="challenges">Reset All Challenges</option>
                                            <option value="journal">Reset All Journal Entries</option>
                                            <option value="all">Reset All Data</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirmation" class="form-label">Type CONFIRM to proceed:</label>
                                        <input type="text" class="form-control" id="confirmation" name="confirmation" required placeholder="CONFIRM">
                                    </div>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-exclamation-triangle"></i> Reset Selected Data
                                    </button>
                                </form>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div>
                                <h6 class="text-danger">Danger Zone</h6>
                                <p class="text-muted">This action cannot be undone and will permanently delete your account and all associated data.</p>
                                
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="bi bi-trash"></i> Delete Account
                                </button>
                                
                                <!-- Delete Account Modal -->
                                <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Warning: This action cannot be undone.
                                                </div>
                                                <p>Deleting your account will permanently remove:</p>
                                                <ul>
                                                    <li>All your habits and tracking history</li>
                                                    <li>All your goals and progress</li>
                                                    <li>All your challenges</li>
                                                    <li>All your journal entries</li>
                                                    <li>All your achievements and badges</li>
                                                    <li>Your account information</li>
                                                </ul>
                                                <p>Are you absolutely sure you want to delete your account?</p>
                                                
                                                <form action="../controllers/process_delete_account.php" method="POST">
                                                    <div class="mb-3">
                                                        <label for="deleteConfirmation" class="form-label">Type <strong>DELETE MY ACCOUNT</strong> to confirm:</label>
                                                        <input type="text" class="form-control" id="deleteConfirmation" name="delete_confirmation" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="deletePassword" class="form-label">Enter your password:</label>
                                                        <input type="password" class="form-control" id="deletePassword" name="password" required>
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger" form="deleteAccountForm">Delete Account</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">We recommend regularly exporting your data for backup purposes.</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Theme switching logic
    document.addEventListener('DOMContentLoaded', function() {
        // Apply theme based on selection
        const themeRadios = document.querySelectorAll('input[name="theme"]');
        const htmlElement = document.documentElement;
        
        function applyTheme(theme) {
            if(theme === 'dark') {
                htmlElement.setAttribute('data-bs-theme', 'dark');
            } else if(theme === 'light') {
                htmlElement.setAttribute('data-bs-theme', 'light');
            } else if(theme === 'system') {
                // Check system preference
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                htmlElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
            }
        }
        
        // Apply theme on page load
        const currentTheme = '<?php echo $current_theme; ?>';
        applyTheme(currentTheme);
        
        // Listen for theme changes
        themeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                applyTheme(this.value);
            });
        });
        
        // Listen for system theme changes if using system theme
        if(currentTheme === 'system') {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                applyTheme('system');
            });
        }
        
        // Email notifications toggle logic
        const emailToggle = document.getElementById('emailNotifications');
        const emailOptions = document.querySelectorAll('input[name^="email_"]');
        
        if(emailToggle) {
            emailToggle.addEventListener('change', function() {
                emailOptions.forEach(option => {
                    if(option.id !== 'emailNotifications') {
                        option.disabled = !this.checked;
                    }
                });
            });
        }
        
        // Public profile toggle logic
        const publicProfileToggle = document.getElementById('publicProfile');
        const profileOptions = document.querySelectorAll('#showStats, #showAchievements');
        
        if(publicProfileToggle) {
            publicProfileToggle.addEventListener('change', function() {
                profileOptions.forEach(option => {
                    option.disabled = !this.checked;
                });
            });
        }
        
        // Apply color scheme
        const colorSchemeRadios = document.querySelectorAll('input[name="color_scheme"]');
        function applyColorScheme(scheme) {
            // Remove any existing color scheme classes
            const colorClasses = ['color-default', 'color-teal', 'color-indigo', 'color-rose', 'color-amber', 'color-emerald'];
            colorClasses.forEach(cls => {
                document.body.classList.remove(cls);
            });
            
            // Add the new color scheme class
            document.body.classList.add('color-' + scheme);
        }
        
        // Apply color scheme on load
        const currentColorScheme = '<?php echo $current_color_scheme; ?>';
        applyColorScheme(currentColorScheme);
        
        // Listen for color scheme changes
        colorSchemeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                applyColorScheme(this.value);
            });
        });
    });
</script>

<?php
// Include footer
include __DIR__ . '/partials/footer.php';
?>