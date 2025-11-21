<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $agreeTerms = isset($_POST['agree_terms']);
    
    // Validation
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?\\|`~])/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    } elseif (strlen($fullName) < 2) {
        $errors[] = 'Full name must be at least 2 characters long';
    }
    
    if (!$agreeTerms) {
        $errors[] = 'You must agree to the Terms of Service';
    }
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $checkStmt = db()->prepare('SELECT id FROM users WHERE email = ?');
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with this email already exists';
            } else {
                // Create user account
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $verificationToken = bin2hex(random_bytes(32));
                
                $insertStmt = db()->prepare('
                    INSERT INTO users (email, password_hash, role, active, email_verified, verification_token, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ');
                $insertStmt->execute([$email, $passwordHash, 'student', 1, 0, $verificationToken]);
                
                $userId = db()->lastInsertId();
                
                // Create user profile
                $profileStmt = db()->prepare('
                    INSERT INTO user_profiles (user_id, full_name) 
                    VALUES (?, ?)
                ');
                $profileStmt->execute([$userId, $fullName]);
                
                // Send welcome notification
                $notifStmt = db()->prepare('
                    INSERT INTO notifications (user_id, message, type, created_at) 
                    VALUES (?, ?, ?, NOW())
                ');
                $notifStmt->execute([$userId, 'Welcome to ' . APP_NAME . '! Start your learning journey by playing the tower defense game.', 'welcome']);
                
                // Send registration confirmation email
                try {
                    require_once __DIR__ . '/../src/MailService.php';
                    $mailService = new MailService();
                    $emailSent = $mailService->sendRegistrationConfirmation($email, $fullName);
                } catch (Exception $e) {
                    error_log('Email sending error: ' . $e->getMessage());
                }
                
                // Redirect to login page
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'An error occurred while creating your account. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <span class="brand-icon"><img src="../images/Game.png" alt="Game" style="width: 32px; height: 32px; object-fit: contain;"></span>
                    <h1>Join <?php echo APP_NAME; ?></h1>
                </div>
                <p class="auth-subtitle">Create your account and start mastering programming through interactive gaming.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon"><img src="../images/Pending.png" alt="Warning" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <span class="alert-icon"><img src="../images/Updated.png" alt="Success" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form" novalidate>
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input" 
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                        required 
                        autofocus
                        autocomplete="name"
                    >
                    <div class="form-error" id="full_name-error"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                    >
                    <div class="form-error" id="email-error"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Create a strong password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter">
                            <div class="strength-bar" id="strength-bar"></div>
                        </div>
                        <div class="strength-text" id="strength-text">Password strength</div>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement" id="req-length">
                            <span class="req-icon">❌</span>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <span class="req-icon">❌</span>
                            <span>One uppercase letter</span>
                        </div>
                        <div class="requirement" id="req-lower">
                            <span class="req-icon">❌</span>
                            <span>One lowercase letter</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <span class="req-icon">❌</span>
                            <span>One number</span>
                        </div>
                        <div class="requirement" id="req-special">
                            <span class="req-icon">❌</span>
                            <span>One special character</span>
                        </div>
                    </div>
                    <div class="form-error" id="password-error"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Confirm your password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="form-error" id="confirm_password-error"></div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="agree_terms" required>
                        <span>I agree to the <a href="#" class="terms-link" onclick="openTermsModal(); return false;">Terms of Service</a> and <a href="#" class="terms-link" onclick="openPrivacyModal(); return false;">Privacy Policy</a></span>
                    </label>
                    <div class="form-error" id="agree_terms-error"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-large auth-submit">
                    <span class="btn-text">Create Account</span>
                    <span class="btn-loader" style="display: none;">🔄</span>
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
            </div>
        </div>

        <div class="auth-sidebar">
            <div class="sidebar-content">
                <h2>Why Join Our Platform?</h2>
                <p>Experience the future of programming education with our gamified learning approach.</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Game.png" alt="Game" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Interactive Tower Defense Game</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Grad Cap.png" alt="Learning" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Structured Learning Paths</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Award.png" alt="Award" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Achievement Badges</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Community.png" alt="Community" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Community Support</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Progress.png" alt="Progress" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Progress Analytics</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Target.png" alt="Target" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Real-world Projects</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/legal_modals.php'); ?>
    <script src="static/auth.js"></script>
</body>
</html>
