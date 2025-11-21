<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Clear remember me token if it exists
if (isset($_COOKIE['remember_token'])) {
    // Delete token from database
    $token = $_COOKIE['remember_token'];
    $stmt = db()->prepare('DELETE FROM user_tokens WHERE token = ?');
    $stmt->execute([hash('sha256', $token)]);
    
    // Clear cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy session
session_destroy();

// Redirect to login with success message
header('Location: login.php?logged_out=1');
exit;


