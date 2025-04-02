<?php
// views/auth/register.php - Registration form
// Include auth controller
require_once __DIR__ . '/../../controllers/AuthController.php';

$authController = new AuthController();

// Redirect if already logged in
if($authController->isLoggedIn()) {
    header('Location: ../../index.php');
    exit;
}

$message = '';
$errors = [];

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $result = $authController->register($username, $email, $password, $confirm_password);
    
    if($result['success']) {
        // Redirect to dashboard on success
        header('Location: ../../index.php');
        exit;
    } else {
        $message = $result['message'] ?? '';
        $errors = $result['errors'] ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Habit Tracker</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --light: #ecf0f1;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #2980b9;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: var(--light);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            border-color: var(--accent);
        }
        
        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        a {
            color: var(--accent);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .alert-danger {
            border-left-color: var(--danger);
            background-color: rgba(231, 76, 60, 0.1);
            color: #c0392b;
        }
        
        /* Header */
        header {
            padding: 20px 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo-text {
            color: var(--primary);
            font-weight: bold;
        }
        
        .back-home {
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .back-home i {
            margin-right: 5px;
        }
        
        .register-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .brand-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-icon {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .page-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #6c757d;
            font-weight: 400;
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 0.5rem;
            border-radius: 5px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
            background: var(--danger);
        }
        
        .benefits-list {
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .benefits-list h5 {
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .benefits-list ul {
            padding-left: 1.5rem;
        }
        
        .benefits-list li {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a class="navbar-brand d-flex align-items-center" href="../../index.php">
                    <i class="bi bi-check-circle-fill me-2 text-primary"></i>
                    <span class="fw-bold fs-4 logo-text">Habit Tracker</span>
                </a>
                
                <a href="../../index.php" class="back-home">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </header>

    <div class="container register-container">
        <div class="row">
            <div class="col-md-7">
                <div class="brand-section">
                    <i class="bi bi-person-plus-fill brand-icon"></i>
                    <h1 class="page-title">Create Your Account</h1>
                    <p class="subtitle">Start your journey to better habits today</p>
                </div>
                
                <div class="card">
                    <?php if(!empty($message) || !empty($errors)): ?>
                    <div class="alert alert-danger m-3">
                        <?php echo $message; ?>
                        <?php if(!empty($errors)): ?>
                            <ul class="mb-0 mt-2">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <form action="register.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-person text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-envelope text-primary"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required>
                                </div>
                                <div class="form-text">We'll never share your email with anyone else.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-lock text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Create a strong password" required>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="password-strength-bar" id="passwordStrength"></div>
                                </div>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-lock-fill text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <p class="mb-0">Already have an account? <a href="login.php" class="fw-semibold">Sign in</a></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5 d-flex align-items-center mt-4 mt-md-0">
                <div class="benefits-list w-100">
                    <h5 class="fw-bold"><i class="bi bi-stars me-2"></i>Benefits of Joining</h5>
                    <ul>
                        <li>Track your daily, weekly, and monthly habits</li>
                        <li>Set and achieve personal goals</li>
                        <li>Join challenges to stay motivated</li>
                        <li>Record your thoughts in your personal journal</li>
                        <li>Gain insights with detailed analytics</li>
                        <li>Earn achievements and level up</li>
                    </ul>
                    <div class="text-center mt-4">
                        <span class="badge bg-success rounded-pill py-2 px-3">
                            <i class="bi bi-lock-fill me-1"></i> Secure & Private
                        </span>
                        <span class="badge bg-primary rounded-pill py-2 px-3 ms-2">
                            <i class="bi bi-cloud-check-fill me-1"></i> Free Cloud Sync
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS Bundle with Popper CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <script>
        // Simple password strength indicator
        $(document).ready(function() {
            $('#password').on('input', function() {
                const password = $(this).val();
                let strength = 0;
                let width = 0;
                let color = '#e74c3c'; // Default: danger
                
                if (password.length >= 6) {
                    strength += 25;
                }
                if (password.length >= 8) {
                    strength += 25;
                }
                if (password.match(/[A-Z]/)) {
                    strength += 25;
                }
                if (password.match(/[0-9]/)) {
                    strength += 25;
                }
                
                width = strength + '%';
                
                if (strength >= 75) {
                    color = '#27ae60'; // success
                } else if (strength >= 50) {
                    color = '#f39c12'; // warning
                } else if (strength >= 25) {
                    color = '#e67e22'; // orange
                }
                
                $('#passwordStrength').css({
                    'width': width,
                    'background-color': color
                });
            });
        });
    </script>
</body>
</html>