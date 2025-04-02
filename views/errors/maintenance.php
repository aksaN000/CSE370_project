// views/errors/maintenance.php - Maintenance mode page
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance | Habit Tracker</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-container {
            text-align: center;
            max-width: 600px;
        }
        .maintenance-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            color: #17a2b8;
        }
    </style>
</head>
<body class="bg-light">
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="bi bi-gear-fill"></i>
        </div>
        <h1 class="mb-4">We're Under Maintenance</h1>
        <p class="lead mb-4">We're working on making the Habit Tracker even better. Please check back soon!</p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Expected downtime: Approximately 30 minutes
        </div>
        <p class="mt-4">
            <button type="button" class="btn btn-outline-primary btn-lg px-4" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh Page
            </button>
        </p>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>