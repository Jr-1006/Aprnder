<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprnder - Master Programming Through Gaming</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">
                    <img src="../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto;">
                </a>
                <ul class="nav-links">
                    <?php if (!is_logged_in()): ?>
                        <li><a href="index.php" class="active">Home</a></li>
                    <?php else: ?>
                        <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <?php endif; ?>
                    <?php if (is_logged_in()): ?>
                        <li><a href="courses.php">Courses</a></li>
                    <?php endif; ?>
                    <li><a href="game.php">Game</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="discord.php">Discord</a></li>
                        <li><a href="leaderboard.php">Leaderboard</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if (!is_logged_in()): ?>
                        <li><a href="about.php">About</a></li>
                        <li><a href="help.php">Help</a></li>
                    <?php endif; ?>
                </ul>
                <?php if (!is_logged_in()): ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php else: ?>
                    <a href="logout.php" class="btn-login">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">
                        Interactive and Gamified Problem-Based Learning for 
                        <span class="gradient-text">ICT Students</span>
                    </h1>
                    <p class="hero-description">
                        Transform coding education into an engaging experience! Aprnder combines gamified problem-based learning with interactive gameplay, designed specifically for ICT senior high school students.
                    </p>
                    <div class="hero-buttons">
                        <a href="courses.php" class="btn btn-primary">Start Learning</a>
                        <a href="game.php" class="btn btn-secondary">Play Game</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="game-preview">
                        <div class="game-screen">
                            <div class="game-dot green" style="top: 30%; left: 25%;"></div>
                            <div class="game-dot green" style="top: 60%; left: 70%;"></div>
                            <div class="game-dot red" style="top: 25%; right: 20%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2 class="section-title">Solving Traditional ICT Learning Challenges</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><img src="../images/Game.png" alt="Game" style="width: 48px; height: 48px; object-fit: contain;"></div>
                    <h3>Gamified Engagement</h3>
                    <p>Move beyond static lectures and exercises. Points, challenges, and rewards keep you motivated and make coding enjoyable instead of intimidating.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><img src="../images/Think.png" alt="Think" style="width: 48px; height: 48px; object-fit: contain;"></div>
                    <h3>Problem-Based Learning</h3>
                    <p>Apply coding concepts to real-world problems through interactive quests. Strengthen critical thinking and problem-solving skills actively.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><img src="../images/Target.png" alt="Target" style="width: 48px; height: 48px; object-fit: contain;"></div>
                    <h3>Built for ICT Students</h3>
                    <p>Specifically designed for senior high school ICT students. Curriculum-aligned content that addresses the unique challenges of learning programming.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><img src="../images/Boost.png" alt="Boost" style="width: 48px; height: 48px; object-fit: contain;"></div>
                    <h3>Boost Performance</h3>
                    <p>Increase participation, confidence, and academic performance through an interactive system that transforms frustration into achievement.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">Problem-Based Learning in Action</h2>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">01</div>
                    <h3>Explore Interactive Quests</h3>
                    <p>Engage with problem-based coding challenges that replace static exercises with hands-on, meaningful learning activities.</p>
                </div>
                <div class="step">
                    <div class="step-number">02</div>
                    <h3>Learn Through Doing</h3>
                    <p>Write real code to solve problems. Move beyond abstract concepts to practical application in an interactive environment.</p>
                </div>
                <div class="step">
                    <div class="step-number">03</div>
                    <h3>Apply Skills in Game</h3>
                    <p>Reinforce learning through tower defense gameplay. See your code in action as you defend against waves using programming logic.</p>
                </div>
                <div class="step">
                    <div class="step-number">04</div>
                    <h3>Build Confidence</h3>
                    <p>Track your progress, earn rewards, and gain confidence. Transform coding from intimidating to achievable through gamification.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Transform Your ICT Learning Experience?</h2>
                <p>Join ICT students who are conquering coding challenges through interactive, gamified problem-based learning.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-large" style="background: white; color: #6366f1; font-weight: 600; border: 2px solid white;">Get Started Free</a>
                    <a href="game.php" class="btn btn-large" style="background: transparent; color: white; border: 2px solid white;">Try Demo Game</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto; margin-bottom: 1rem;">
                    <p>Learn coding through gaming.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Contact Us</h4>
                        <ul>
                            <li><strong>Email:</strong> mjraquino2@tip.edu.ph</li>
                            <li><strong>Address:</strong><br>363 Casal St, Quiapo, Manila,<br>1001 Metro Manila</li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#" onclick="openTermsModal(); return false;">Terms of Service</a></li>
                            <li><a href="#" onclick="openPrivacyModal(); return false;">Privacy Policy</a></li>
                            <li><strong>DPO:</strong> Cheska Eunice Diaz</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Aprnder. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php include('../includes/legal_modals.php'); ?>
</body>
</html>


