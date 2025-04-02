// views/errors/403.php - 403 Forbidden page
<?php
session_start();
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Forbidden | Habit Tracker</title>
    
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
        .error-container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: #fd7e14;
            margin-bottom: 1rem;
        }
        .error-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            color: #fd7e14;
        }
    </style>
</head>
<body class="bg-light">
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-lock-fill"></i>
        </div>
        <div class="error-code">403</div>
        <h1 class="mb-4">Access Forbidden</h1>
        <p class="lead mb-4">You don't have permission to access this resource.</p>
        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <a href="<?php echo $is_logged_in ? '/' : '/views/auth/login.php'; ?>" class="btn btn-primary btn-lg px-4 me-md-2">
                <i class="bi bi-house-fill"></i> <?php echo $is_logged_in ? 'Go to Dashboard' : 'Go to Login'; ?>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="window.history.back()">
                <i class="bi bi-arrow-left"></i> Go Back
            </button>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
