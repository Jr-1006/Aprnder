<?php
require_once __DIR__ . '/../src/bootstrap.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle logout message
if (isset($_GET['logged_out'])) {
    $success = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($email && $password) {
        $stmt = db()->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                setcookie('remember_token', $token, $expires, '/', '', false, true);
                
                // Store token in database
                $tokenStmt = db()->prepare('INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
                $tokenStmt->execute([$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', $expires)]);
            }
            
            // Update last login
            $updateStmt = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $updateStmt->execute([$user['id']]);
            
            $success = 'Login successful! Redirecting...';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}

// Handle remember me token
if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = db()->prepare('
        SELECT u.id, u.email, u.role 
        FROM users u 
        INNER JOIN user_tokens t ON u.id = t.user_id 
        WHERE t.token = ? AND t.expires_at > NOW()
    ');
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <span class="brand-icon"><img src="../images/Game.png" alt="Game" style="width: 32px; height: 32px; object-fit: contain;"></span>
                    <h1><?php echo APP_NAME; ?></h1>
                </div>
                <p class="auth-subtitle">Welcome back! Sign in to continue your learning journey.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon"><img src="../images/Pending.png" alt="Warning" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <?php echo htmlspecialchars($error); ?>
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
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required 
                        autofocus
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
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span class="toggle-icon">👁️</span>
                        </button>
                    </div>
                    <div class="form-error" id="password-error"></div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkmark"></span>
                        Remember me for 30 days
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-large auth-submit">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-loader" style="display: none;">🔄</span>
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php" class="auth-link">Create one here</a></p>
            </div>
        </div>

        <div class="auth-sidebar">
            <div class="sidebar-content">
                <h2>Master Programming Through Gaming</h2>
                <p>Join thousands of learners who are mastering coding concepts through our interactive tower defense game.</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Target.png" alt="Target" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Interactive Learning</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Award.png" alt="Award" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Achievement System</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Progress.png" alt="Progress" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Progress Tracking</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon"><img src="../images/Community.png" alt="Community" style="width: 24px; height: 24px; object-fit: contain;"></span>
                        <span>Community Features</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="static/auth.js"></script>
</body>
</html>


