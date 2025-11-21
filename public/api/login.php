<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']);

// Validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    $stmt = db()->prepare('SELECT id, email, password_hash, role, active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    if (!$user['active']) {
        echo json_encode(['success' => false, 'message' => 'Your account has been deactivated']);
        exit;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Update last login
    $updateStmt = db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $updateStmt->execute([$user['id']]);
    
    // Handle remember me
    if ($rememberMe) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $tokenStmt = db()->prepare('
            INSERT INTO user_tokens (user_id, token, type, expires_at) 
            VALUES (?, ?, ?, ?)
        ');
        $tokenStmt->execute([$user['id'], $token, 'remember', $expiresAt]);
        
        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
    }
    
    $redirect = 'dashboard.php';
    if ($user['role'] === 'admin') {
        $redirect = 'admin/dashboard.php';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect
    ]);
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
