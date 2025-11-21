<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/MailService.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!$email) {
        $error = 'Please enter your email address';
    } elseif (!Security::validateEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if user exists
        $stmt = db()->prepare('SELECT id, email FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token (valid for 24 hours)
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $hashedToken = hash('sha256', $resetToken);
            
            try {
                // Delete any existing reset tokens
                $deleteStmt = db()->prepare('DELETE FROM user_tokens WHERE user_id = ? AND type = \'reset\'');
                $deleteStmt->execute([$user['id']]);
                
                // Insert new reset token
                $insertStmt = db()->prepare('
                    INSERT INTO user_tokens (user_id, token, type, expires_at, created_at)
                    VALUES (?, ?, \'reset\', ?, NOW())
                ');
                $insertStmt->execute([$user['id'], $hashedToken, $expiresAt]);
                
                error_log("Password reset token created for user: {$email}");
                error_log("Token expires at: {$expiresAt}, Current time: " . date('Y-m-d H:i:s'));
                
                // Try to send email, but also provide direct link if email fails
                $mailSent = false;
                try {
                    $mailService = new MailService();
                    $profileStmt = db()->prepare('SELECT full_name FROM user_profiles WHERE user_id = ?');
                    $profileStmt->execute([$user['id']]);
                    $profile = $profileStmt->fetch();
                    $name = $profile['full_name'] ?? 'User';
                    
                    $mailSent = $mailService->sendPasswordReset($email, $name, $resetToken);
                } catch (Exception $mailError) {
                    error_log('Email sending error: ' . $mailError->getMessage());
                }
                
                if ($mailSent) {
                    $success = 'Password reset link has been sent to your email. Please check your inbox (and spam folder). The link expires in 24 hours.';
                } else {
                    // Email sending failed, but token was created - provide direct link
                    $resetUrl = "http://localhost/Websys/public/reset-password.php?token=" . urlencode($resetToken);
                    $success = 'Password reset token has been generated. Click here to reset: <a href="' . $resetUrl . '" style="color: #6366f1; text-decoration: underline;">Reset Password</a><br><br><small>(Email sending failed, but you can use the link above.)</small>';
                }
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $error = 'An error occurred: ' . $e->getMessage();
            }
        } else {
            // Don't reveal if email exists (security best practice)
            $success = 'If that email exists in our system, a password reset link has been sent to it.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
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
                    <span class="brand-icon">🔐</span>
                    <h1>Forgot Password</h1>
                </div>
                <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon">⚠️</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <span class="alert-icon">✅</span>
                    <?php echo $success; ?>
                </div>
                <div class="text-center" style="margin-top: 1rem;">
                    <a href="login.php" class="btn btn-primary">Back to Login</a>
                </div>
            <?php else: ?>
                <form method="post" class="auth-form" novalidate>
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your email address"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required 
                            autofocus
                            autocomplete="email"
                        >
                        <div class="form-error" id="email-error"></div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large auth-submit">
                        <span class="btn-text">Send Reset Link</span>
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
                <h2>Need Help?</h2>
                <p>If you're having trouble accessing your account, we're here to help.</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">📧</span>
                        <span>Email Recovery</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🔒</span>
                        <span>Secure Process</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">⏰</span>
                        <span>Link Expires in 24 Hours</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="static/auth.js"></script>
</body>
</html>

