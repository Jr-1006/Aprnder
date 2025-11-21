<?php

class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token input field
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        $rateLimitKey = $key . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        // Clean old entries
        if (isset($_SESSION['rate_limit'][$rateLimitKey])) {
            $_SESSION['rate_limit'][$rateLimitKey] = array_filter(
                $_SESSION['rate_limit'][$rateLimitKey],
                function($timestamp) use ($now, $timeWindow) {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            $_SESSION['rate_limit'][$rateLimitKey] = [];
        }
        
        // Check if limit exceeded
        if (count($_SESSION['rate_limit'][$rateLimitKey]) >= $maxAttempts) {
            return false;
        }
        
        // Add current attempt
        $_SESSION['rate_limit'][$rateLimitKey][] = $now;
        return true;
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?\\|`~]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    /**
     * Clean expired tokens
     */
    public static function cleanExpiredTokens() {
        try {
            $stmt = db()->prepare('DELETE FROM user_tokens WHERE expires_at < NOW()');
            $stmt->execute();
        } catch (Exception $e) {
            error_log('Token cleanup error: ' . $e->getMessage());
        }
    }
    
    /**
     * Hash sensitive data
     */
    public static function hash($data) {
        return hash('sha256', $data);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
