<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

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

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    exit;
}

try {
    // Check if email already exists
    $checkStmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists']);
        exit;
    }
    
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
        require_once __DIR__ . '/../../src/MailService.php';
        $mailService = new MailService();
        $mailService->sendRegistrationConfirmation($email, $fullName);
    } catch (Exception $e) {
        error_log('Email sending error: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please sign in.'
    ]);
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while creating your account. Please try again.']);
}
