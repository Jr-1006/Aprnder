<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Security.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$validToken = false;

// Get token from URL
$token = $_GET['token'] ?? '';

if (!$token) {
    $error = 'Invalid or missing reset token';
} else {
    // Decode URL-encoded token
    $token = urldecode($token);
    
    // Validate token
    $hashedToken = hash('sha256', $token);
    
    // First check all tokens for debugging
    $debugStmt = db()->prepare('SELECT COUNT(*) as count FROM user_tokens WHERE type = \'reset\'');
    $debugStmt->execute();
    $debugData = $debugStmt->fetch();
    error_log("Total reset tokens in database: " . $debugData['count']);
    
    $stmt = db()->prepare('
        SELECT u.id, u.email, t.expires_at, t.token, t.created_at
        FROM user_tokens t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.token = ? AND t.type = \'reset\' AND t.expires_at > NOW()
    ');
    $stmt->execute([$hashedToken]);
    $tokenData = $stmt->fetch();
    
    if ($tokenData) {
        $validToken = true;
        $userId = $tokenData['id'];
        error_log("Token validated successfully for user ID: " . $userId);
    } else {
        // Check if token exists but expired
        $checkStmt = db()->prepare("
            SELECT expires_at, NOW() as now_time, TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as mins_left
            FROM user_tokens t
            WHERE t.token = ? AND t.type = 'reset'
        ");
        $checkStmt->execute([$hashedToken]);
        $expiredData = $checkStmt->fetch();
        
        if ($expiredData) {
            // Token exists but expired
            $minsLeft = (int)$expiredData['mins_left'];
            if ($minsLeft <= 0) {
                $error = 'Reset token has expired. Please request a new password reset link.';
                error_log("Token expired. Expires at: {$expiredData['expires_at']}, Current time: {$expiredData['now_time']}, Minutes left: $minsLeft");
            } else {
                $error = 'Token validation issue. Please try again.';
                error_log("Token should be valid. Minutes left: $minsLeft");
            }
        } else {
            // Token doesn't exist at all
            error_log("Token not found in database. Search hash: " . substr($hashedToken, 0, 20) . "...");
            
            // Show all tokens for debugging
            $allStmt = db()->query('SELECT id, user_id, created_at, expires_at, LEFT(token, 20) as token_preview FROM user_tokens WHERE type = \'reset\' ORDER BY created_at DESC LIMIT 5');
            $allTokens = $allStmt->fetchAll();
            error_log("Recent reset tokens: " . json_encode($allTokens));
            
            $error = 'Invalid reset token. <a href="forgot-password.php">Request a new password reset link</a>.';
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!$newPassword || !$confirmPassword) {
        $error = 'Please fill in all fields';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Validate password strength
        $passwordErrors = Security::validatePassword($newPassword);
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } else {
            try {
                // Update password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $updateStmt->execute([$newHash, $userId]);
                
                // Delete used token
                $hashedToken = hash('sha256', $token);
                $deleteStmt = db()->prepare('DELETE FROM user_tokens WHERE token = ?');
                $deleteStmt->execute([$hashedToken]);
                
                $success = 'Password has been reset successfully! Redirecting to login...';
                
                // Redirect after 2 seconds
                header("Refresh: 2; url=login.php");
                
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
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
                    <span class="brand-icon">🔑</span>
                    <h1>Reset Password</h1>
                </div>
                <p class="auth-subtitle">Enter your new password below.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon">⚠️</span>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <span class="alert-icon">✅</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($validToken && !$success): ?>
                <form method="post" class="auth-form" novalidate>
                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your new password"
                                required
                                autofocus
                                autocomplete="new-password"
                                minlength="8"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <span class="toggle-icon">👁️</span>
                            </button>
                        </div>
                        <small class="form-help" style="display: block; margin-top: 0.25rem; color: var(--color-text-muted);">
                            Must be at least 8 characters with uppercase, lowercase, number, and special character
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="password-input">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                placeholder="Confirm your new password"
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <span class="toggle-icon">👁️</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large auth-submit">
                        <span class="btn-text">Reset Password</span>
                        <span class="btn-loader" style="display: none;">🔄</span>
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Remember your password? <a href="login.php" class="auth-link">Back to Login</a></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="auth-sidebar">
            <div class="sidebar-content">
                <h2>Password Requirements</h2>
                <p>Your new password must meet the following requirements:</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">🔤</span>
                        <span>At least 8 characters long</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🔠</span>
                        <span>Contains uppercase and lowercase</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🔢</span>
                        <span>Contains numbers</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🔣</span>
                        <span>Contains special characters</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="static/auth.js"></script>
</body>
</html>

