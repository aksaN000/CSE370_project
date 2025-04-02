<?php
// views/auth/login.php - Login form
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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $authController->login($email, $password);
    
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
    <title>Login - Habit Tracker</title>
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
        
        .login-container {
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

    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="brand-section">
                    <i class="bi bi-unlock-fill brand-icon"></i>
                    <h1 class="page-title">Welcome Back</h1>
                    <p class="subtitle">Sign in to continue your habit journey</p>
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
                        <form action="login.php" method="POST">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-envelope text-primary"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="#" class="small">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-lock text-primary"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Your password" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="fw-semibold">Create one</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS Bundle with Popper CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>