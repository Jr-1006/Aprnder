<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/BadgeService.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses.php');
    exit;
}

$userId = current_user_id();
$courseId = (int)($_POST['course_id'] ?? 0);

if ($courseId <= 0) {
    header('Location: courses.php?error=invalid_course');
    exit;
}

try {
    // Check if course exists
    $checkStmt = db()->prepare('SELECT id FROM courses WHERE id = ?');
    $checkStmt->execute([$courseId]);
    if (!$checkStmt->fetch()) {
        header('Location: courses.php?error=course_not_found');
        exit;
    }
    
    // Check if already enrolled
    $enrolledStmt = db()->prepare('SELECT user_id FROM enrollments WHERE user_id = ? AND course_id = ?');
    $enrolledStmt->execute([$userId, $courseId]);
    if ($enrolledStmt->fetch()) {
        header('Location: course.php?id=' . $courseId);
        exit;
    }
    
    // Enroll user
    $insertStmt = db()->prepare('INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)');
    $insertStmt->execute([$userId, $courseId]);
    
    // Add notification
    $notifStmt = db()->prepare('
        INSERT INTO notifications (user_id, message, type, created_at) 
        VALUES (?, ?, ?, NOW())
    ');
    $notifStmt->execute([$userId, 'You have successfully enrolled in a new course!', 'success']);
    
    // Check and award badges
    $badgeService = new BadgeService(db());
    $badgeService->checkAndAwardBadges($userId);
    
    header('Location: course.php?id=' . $courseId . '&enrolled=1');
    exit;
    
} catch (Exception $e) {
    error_log('Enrollment error: ' . $e->getMessage());
    header('Location: courses.php?error=enrollment_failed');
    exit;
}
