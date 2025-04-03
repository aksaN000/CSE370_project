<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SettingsController.php';

function migrateUserSettings() {
    global $conn;
    
    // Select users without settings
    $query = "SELECT id FROM users u 
              WHERE NOT EXISTS (
                  SELECT 1 FROM user_settings us 
                  WHERE us.user_id = u.id
              )";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $users_without_settings = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $settingsController = new SettingsController();
    
    foreach ($users_without_settings as $user_id) {
        $settingsController->createDefaultSettings($user_id);
    }
    
    echo "Migrated settings for " . count($users_without_settings) . " users.";
}

// Run the migration
migrateUserSettings();