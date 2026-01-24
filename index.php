<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: pages/admin/dashboard.php");
    } else {
        header("Location: pages/member/dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Windsor Tea Factory Staff Welfare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hero-pattern" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23hero-pattern)"/></svg>');
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero .subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ee5a24 0%, #ff6b6b 100%);
        }

        /* Features Section */
        .features {
            padding: 80px 20px;
            background: #f8f9fa;
        }

        .features h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #2c3e50;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .feature-card p {
            color: #6c757d;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 60px 20px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }

        .stats h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
        }

        .stats-grid {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #667eea;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .footer h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .footer p {
            opacity: 0.8;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: background 0.3s ease;
        }

        .footer-links a:hover {
            background: rgba(255,255,255,0.1);
        }

        .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            opacity: 0.7;
            font-size: 0.9rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero .subtitle {
                font-size: 1.1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .features h2,
            .stats h2 {
                font-size: 2rem;
            }

            .features-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero {
                padding: 2rem 1rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .feature-card,
            .stat-item {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1><i class="fas fa-leaf"></i> Windsor Welfare</h1>
            <p class="subtitle">Empowering our tea factory family with comprehensive staff welfare and financial management solutions</p>

            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </a>
                <a href="register.php" class="btn">
                    <i class="fas fa-user-plus"></i> Join Our Community
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Our Welfare Services</h2>
            <div class="features-grid">
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h3>Monthly Contributions</h3>
                    <p>Secure monthly savings program with flexible contribution options and comprehensive tracking.</p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3>Loan Management</h3>
                    <p>Easy loan application and management system with transparent interest rates and flexible repayment terms.</p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Financial Tracking</h3>
                    <p>Real-time monitoring of your contributions, loan status, and financial progress with detailed reports.</p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Support</h3>
                    <p>Building stronger relationships within our tea factory community through shared welfare initiatives.</p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Platform</h3>
                    <p>Bank-level security with encrypted data protection and secure access to your personal information.</p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access your welfare account anytime, anywhere with our fully responsive mobile-optimized platform.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <h2>Our Impact</h2>
            <div class="stats-grid">
                <div class="stat-item fade-in-up">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Active Members</div>
                </div>

                <div class="stat-item fade-in-up">
                    <div class="stat-number">KES 2M+</div>
                    <div class="stat-label">Total Savings</div>
                </div>

                <div class="stat-item fade-in-up">
                    <div class="stat-number">150+</div>
                    <div class="stat-label">Loans Disbursed</div>
                </div>

                <div class="stat-item fade-in-up">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <h3>Windsor Tea Factory Staff Welfare</h3>
            <p>Committed to the well-being and financial security of our valued tea factory staff members. Together, we build a stronger, more supportive community.</p>

            <div class="footer-links">
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
                <a href="pages/member/dashboard.php">Member Portal</a>
                <a href="pages/admin/dashboard.php">Admin Portal</a>
            </div>

            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Windsor Tea Factory Staff Welfare System. All rights reserved.</p>
                <p>Powered by Windsor Welfare Management Platform</p>
            </div>
        </div>
    </footer>

    <script>
        // Add scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-up');
                    }
                });
            }, observerOptions);

            // Observe all feature cards and stat items
            document.querySelectorAll('.feature-card, .stat-item').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>