<?php
/**
 * Habit Tracker - Main Index File
 * This is the entry point for the application
 */

// Start session
session_start();

require_once __DIR__ . '/controllers/AuthController.php';

// Initialize auth controller
$authController = new AuthController();

// Check if user is logged in
$isLoggedIn = $authController->isLoggedIn();

// If logged in, redirect to dashboard
if($isLoggedIn) {
    header('Location: views/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habit Tracker - Transform Your Life</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
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
        }
        
        /* Hero section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 120px 0 100px;
            margin-bottom: 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: white;
            clip-path: polygon(0 0, 100% 50%, 100% 100%, 0% 100%);
        }
        
        /* Feature styles */
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .feature-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .card-border-top {
            height: 6px;
            border-radius: 6px 6px 0 0;
        }
        
        /* CTA section */
        .cta-section {
            background-color: var(--primary);
            color: white;
            padding: 100px 0;
            margin: 100px 0 0;
            position: relative;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: white;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 50%);
        }
        
        /* Button styling */
        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-outline-primary {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--accent);
            color: white;
        }
        
        /* Header */
        header {
            padding: 20px 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .features-section {
            padding: 80px 0;
        }
        
        /* Section headers */
        .section-header {
            margin-bottom: 60px;
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--accent);
        }
        
        /* Footer */
        footer {
            background-color: var(--secondary);
            color: white;
            padding: 60px 0 20px;
        }
        
        footer a {
            text-decoration: none;
        }
        
        footer h5 {
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .social-icons a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background-color: var(--accent);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <i class="bi bi-check-circle-fill me-2 text-primary"></i>
                    <span class="fw-bold fs-4">Habit Tracker</span>
                </a>
                
                <div>
                    <a href="views/auth/login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="views/auth/register.php" class="btn btn-primary">Sign Up Free</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Transform Your Life With Better Habits</h1>
                    <p class="lead mb-5">Track your habits, build consistency, and achieve your goals with our powerful and intuitive habit tracking system.</p>
                    <div class="d-grid gap-3 d-md-flex">
                        <a href="views/auth/register.php" class="btn btn-light btn-lg px-4 fw-semibold">Start Free Now</a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-4">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block text-center">
                    <img src='assets/images/logo.png' alt="Habit Tracker Dashboard" class="img-fluid rounded-3 shadow-lg" style="max-width: 90%;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center section-header">
                <h2 class="display-5 fw-bold section-title">Powerful Features</h2>
                <p class="lead text-secondary">Everything you need to create, track, and maintain positive habits</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-border-top bg-primary"></div>
                        <div class="card-body p-4">
                            <div class="text-primary mb-3">
                                <i class="bi bi-check-circle feature-icon"></i>
                            </div>
                            <h3 class="h4 mb-3">Habit Tracking</h3>
                            <p class="text-secondary mb-4">Create daily, weekly, or monthly habits and track your consistency with ease.</p>
                            <ul class="list-unstyled text-secondary">
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Daily habit streaks</li>
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Visual completion tracking</li>
                                <li><i class="bi bi-check text-primary me-2"></i>Customizable categories</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-border-top bg-warning"></div>
                        <div class="card-body p-4">
                            <div class="text-warning mb-3">
                                <i class="bi bi-trophy feature-icon"></i>
                            </div>
                            <h3 class="h4 mb-3">Goal Setting</h3>
                            <p class="text-secondary mb-4">Set measurable goals with deadlines and track your progress towards achieving them.</p>
                            <ul class="list-unstyled text-secondary">
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Progress tracking</li>
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Deadline management</li>
                                <li><i class="bi bi-check text-primary me-2"></i>Milestone celebrations</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-border-top bg-danger"></div>
                        <div class="card-body p-4">
                            <div class="text-danger mb-3">
                                <i class="bi bi-people feature-icon"></i>
                            </div>
                            <h3 class="h4 mb-3">Challenges</h3>
                            <p class="text-secondary mb-4">Join or create challenges to stay motivated and build habits with others.</p>
                            <ul class="list-unstyled text-secondary">
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Community challenges</li>
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Task-based activities</li>
                                <li><i class="bi bi-check text-primary me-2"></i>XP rewards for completion</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-border-top bg-info"></div>
                        <div class="card-body p-4">
                            <div class="text-info mb-3">
                                <i class="bi bi-journal-text feature-icon"></i>
                            </div>
                            <h3 class="h4 mb-3">Journal</h3>
                            <p class="text-secondary mb-4">Record your daily thoughts, mood, and reflections on your habit journey.</p>
                            <ul class="list-unstyled text-secondary">
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Mood tracking</li>
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Reflection prompts</li>
                                <li><i class="bi bi-check text-primary me-2"></i>Searchable entries</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-border-top bg-success"></div>
                        <div class="card-body p-4">
                            <div class="text-success mb-3">
                                <i class="bi bi-graph-up-arrow feature-icon"></i>
                            </div>
                            <h3 class="h4 mb-3">Analytics</h3>
                            <p class="text-secondary mb-4">Gain insights into your progress with detailed analytics and visualizations.</p>
                            <ul class="list-unstyled text-secondary">
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Habit consistency charts</li>
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Goal progress tracking</li>
                                <li><i class="bi bi-check text-primary me-2"></i>Mood analysis over time</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="card-border-top" style="background-color: #9b59b6;"></div>
                        <div class="card-body p-4">
                            <div style="color: #9b59b6;" class="mb-3">
                                <i class="bi bi-award feature-icon"></i>
                            </div>
                            <h3 class="h4 mb-3">Achievements</h3>
                            <p class="text-secondary mb-4">Earn XP, level up, and unlock badges as you build consistent habits.</p>
                            <ul class="list-unstyled text-secondary">
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>XP reward system</li>
                                <li class="mb-2"><i class="bi bi-check text-primary me-2"></i>Level progression</li>
                                <li><i class="bi bi-check text-primary me-2"></i>Achievement badges</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Ready to Transform Your Life?</h2>
            <p class="lead mb-5">Start tracking your habits today and see the difference consistency can make.</p>
            <a href="views/auth/register.php" class="btn btn-light btn-lg px-5 fw-semibold">Create Your Free Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill me-2 text-info"></i>
                        <h5 class="fw-bold m-0">Habit Tracker</h5>
                    </div>
                    <p class="mb-4 text-light">Transform your life through better habits, one day at a time.</p>
                    <div class="social-icons d-flex gap-2">
                        <a href="#" class="text-white"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2 mb-4">
                    <h5>Features</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features" class="text-light">Habit Tracking</a></li>
                        <li class="mb-2"><a href="#features" class="text-light">Goal Setting</a></li>
                        <li class="mb-2"><a href="#features" class="text-light">Challenges</a></li>
                        <li class="mb-2"><a href="#features" class="text-light">Journal</a></li>
                        <li><a href="#features" class="text-light">Analytics</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2 mb-4">
                    <h5>About</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Our Story</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Team</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Careers</a></li>
                        <li><a href="#" class="text-light">Blog</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2 mb-4">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light">Support</a></li>
                        <li class="mb-2"><a href="#" class="text-light">FAQs</a></li>
                        <li class="mb-2"><a href="#" class="text-light">Privacy Policy</a></li>
                        <li><a href="#" class="text-light">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2 mb-4">
                    <h5>Get Started</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="views/auth/login.php" class="text-light">Login</a></li>
                        <li class="mb-2"><a href="views/auth/register.php" class="text-light">Sign Up</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-top border-secondary-subtle pt-4 mt-4 text-center">
                <p class="text-light">&copy; <?php echo date('Y'); ?> Habit Tracker. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS Bundle with Popper CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Smooth scroll for anchor links -->
    <script>
        $(document).ready(function() {
            // Add smooth scrolling to all links
            $("a").on('click', function(event) {
                if (this.hash !== "") {
                    event.preventDefault();
                    var hash = this.hash;
                    $('html, body').animate({
                        scrollTop: $(hash).offset().top - 70
                    }, 800);
                }
            });
        });
    </script>
</body>
</html>