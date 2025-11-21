<?php
declare(strict_types=1);

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}


