<?php
require_once __DIR__ . '/../src/bootstrap.php';
$pageTitle = 'Help & FAQ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .faq-item {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            overflow: hidden;
        }
        .faq-question {
            padding: var(--spacing-md);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            transition: background 0.2s;
        }
        .faq-question:hover {
            background: var(--color-background);
        }
        .faq-answer {
            padding: 0 var(--spacing-md) var(--spacing-md);
            display: none;
            color: var(--color-text-muted);
            line-height: 1.6;
        }
        .faq-answer.active {
            display: block;
        }
        .faq-icon {
            transition: transform 0.3s;
        }
        .faq-icon.active {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">
                    <img src="../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto;">
                </a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
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
                        <li><a href="help.php" class="active">Help</a></li>
                    <?php endif; ?>
                </ul>
                <?php if (is_logged_in()): ?>
                    <a href="dashboard.php" class="btn-login">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="static-page-header">
        <div class="container">
            <h1>Help & FAQ</h1>
            <p class="lead">Find answers to common questions</p>
        </div>
    </section>

    <section class="static-page-content">
        <div class="container">
            <div class="content-section">
                <h2>Getting Started</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I create an account?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Click the "Get Started Free" button on the homepage or navigate to the registration page. Fill in your name, email, and create a strong password. You'll receive a confirmation email to verify your account.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>Is Aprnder free to use?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! Aprnder is completely free for students. You can access all courses, submit quests, and play the tower defense game without any cost.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>What programming languages can I learn?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Our courses cover various programming languages and concepts including JavaScript, Python, algorithms, data structures, and more. Check the Courses page for the full list.</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>Courses & Quests</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I enroll in a course?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Browse available courses on the Courses page. Click on a course to view details, then click "Enroll Now" to start learning. You'll immediately have access to all quests in that course.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I submit code for a quest?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Navigate to the quest page, read the challenge description, write your code in the editor, and click "Submit Code". Your submission will be reviewed by an instructor who will provide feedback and award points if your solution is correct.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>Can I resubmit a quest if I fail?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! You can resubmit quests as many times as needed. Review the instructor feedback, improve your code, and submit again.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How long does it take to get feedback?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Instructors typically review submissions within 24-48 hours. You'll receive a notification when your submission has been reviewed.</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>Tower Defense Game</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I play the tower defense game?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Navigate to the Game page. Select a tower type from the sidebar, then click on the grid to place it. Click "Start Wave" to begin. Defend your base by strategically placing towers to destroy incoming enemies.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>What do the different tower types do?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Each tower has unique characteristics: Basic (balanced), Rapid (fast firing), Heavy (high damage), Sniper (long range), Laser (continuous beam), and Missile (area damage). Experiment to find the best strategy!</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I get on the leaderboard?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Play the tower defense game and achieve high scores. Your best score is automatically submitted to the leaderboard. Compete with other players to reach the top!</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>Account & Profile</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I change my password?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Go to your Profile page, scroll to the "Change Password" section, enter your current password and new password, then click "Change Password".</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I view my progress?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Your Dashboard and Profile pages show comprehensive statistics including enrolled courses, completed quests, total points, game scores, and achievements.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>What are badges and how do I earn them?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Badges are achievements awarded for completing milestones like finishing courses, reaching score thresholds, or mastering specific skills. They appear on your profile page.</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>Technical Support</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>I forgot my password. What should I do?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Click "Forgot Password" on the login page. Enter your email address and we'll send you instructions to reset your password.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>The game isn't loading. What should I do?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Try refreshing the page, clearing your browser cache, or using a different browser. Make sure JavaScript is enabled. If the problem persists, contact support.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How do I report a bug or issue?</span>
                        <span class="faq-icon">▼</span>
                    </div>
                    <div class="faq-answer">
                        <p>Use the Contact page to report bugs or issues. Include details about what happened, what you expected, and steps to reproduce the problem.</p>
                    </div>
                </div>
            </div>

            <div class="cta-box">
                <h2>Still Have Questions?</h2>
                <p>Can't find what you're looking for? We're here to help!</p>
                <div class="cta-buttons">
                    <a href="contact.php" class="btn btn-primary btn-large">Contact Support</a>
                    <a href="about.php" class="btn btn-secondary btn-large">Learn More</a>
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

    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('.faq-icon');
            
            answer.classList.toggle('active');
            icon.classList.toggle('active');
        }
    </script>

    <?php include('../includes/legal_modals.php'); ?>
</body>
</html>
