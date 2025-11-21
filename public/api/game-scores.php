<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['score']) || !isset($input['wave'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: score, wave']);
        exit;
    }
    
    $userId = current_user_id();
    $score = (int)$input['score'];
    $wave = (int)$input['wave'];
    
    // Validate input
    if ($score < 0 || $wave < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid score or wave value']);
        exit;
    }
    
    // Create game_scores table if it doesn't exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS game_scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            score INT NOT NULL,
            wave_reached INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_score (user_id, score DESC),
            INDEX idx_created_at (created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    db()->exec($createTableSql);
    
    // Insert new score
    $stmt = db()->prepare('
        INSERT INTO game_scores (user_id, score, wave_reached) 
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$userId, $score, $wave]);
    
    // Get user's best score
    $bestScoreStmt = db()->prepare('
        SELECT MAX(score) as best_score, MAX(wave_reached) as best_wave 
        FROM game_scores 
        WHERE user_id = ?
    ');
    $bestScoreStmt->execute([$userId]);
    $bestScore = $bestScoreStmt->fetch();
    
    // Get leaderboard (top 10 scores)
    $leaderboardStmt = db()->query('
        SELECT u.email, p.full_name, gs.score, gs.wave_reached, gs.created_at
        FROM game_scores gs
        JOIN users u ON u.id = gs.user_id
        LEFT JOIN user_profiles p ON p.user_id = u.id
        ORDER BY gs.score DESC
        LIMIT 10
    ');
    $leaderboard = $leaderboardStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'message' => 'Score saved successfully',
        'score' => $score,
        'wave' => $wave,
        'best_score' => (int)($bestScore['best_score'] ?? 0),
        'best_wave' => (int)($bestScore['best_wave'] ?? 0),
        'leaderboard' => $leaderboard
    ]);
    
} catch (Exception $e) {
    error_log('Game score save error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save score']);
}
?>
