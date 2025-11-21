<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Security.php';
require_once __DIR__ . '/../../src/BadgeService.php';
require_login();

// Check if user is mentor
$userId = current_user_id();
$userStmt = db()->prepare('SELECT role FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user['role'] !== 'mentor') {
    header('Location: ../dashboard.php?error=access_denied');
    exit;
}

$courseId = (int)($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Verify mentor owns this course
$courseStmt = db()->prepare('SELECT id, title FROM courses WHERE id = ? AND created_by = ?');
$courseStmt->execute([$courseId, $userId]);
$course = $courseStmt->fetch();

if (!$course) {
    header('Location: dashboard.php?error=course_not_found');
    exit;
}

$success = '';
$error = '';
$submissionId = (int)($_GET['id'] ?? 0);
$statusFilter = $_GET['status'] ?? 'all';

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submission'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $id = (int)$_POST['submission_id'];
        $status = $_POST['status'];
        $pointsAwarded = ($status === 'passed') ? (int)$_POST['points_awarded'] : 0;
        $feedback = Security::sanitize($_POST['feedback'] ?? '');
        
        // Verify submission belongs to mentor's course
        $verifyStmt = db()->prepare('
            SELECT s.id FROM submissions s
            INNER JOIN quests q ON q.id = s.quest_id
            WHERE s.id = ? AND q.course_id = ?
        ');
        $verifyStmt->execute([$id, $courseId]);
        
        if ($verifyStmt->fetch()) {
            try {
                $stmt = db()->prepare('
                    UPDATE submissions 
                    SET status = ?, points_awarded = ?, feedback = ? 
                    WHERE id = ?
                ');
                $stmt->execute([$status, $pointsAwarded, $feedback, $id]);
                
                // Get submission details for notification
                $subStmt = db()->prepare('
                    SELECT s.user_id, q.title as quest_title
                    FROM submissions s
                    INNER JOIN quests q ON q.id = s.quest_id
                    WHERE s.id = ?
                ');
                $subStmt->execute([$id]);
                $subData = $subStmt->fetch();
                
                // Send notification to student
                $message = $status === 'passed' 
                    ? "Your submission for '{$subData['quest_title']}' was approved! You earned {$pointsAwarded} points."
                    : "Your submission for '{$subData['quest_title']}' needs revision. Check the feedback.";
                
                $notifStmt = db()->prepare('
                    INSERT INTO notifications (user_id, message, type, created_at)
                    VALUES (?, ?, ?, NOW())
                ');
                $notifType = $status === 'passed' ? 'success' : 'warning';
                $notifStmt->execute([$subData['user_id'], $message, $notifType]);
                
                // Check and award badges if passed
                if ($status === 'passed') {
                    $badgeService = new BadgeService(db());
                    $badgeService->checkAndAwardBadges($subData['user_id']);
                }
                
                $success = 'Submission reviewed successfully!';
                $submissionId = 0; // Return to list
            } catch (Exception $e) {
                error_log('Submission review error: ' . $e->getMessage());
                $error = 'Failed to review submission';
            }
        } else {
            $error = 'Invalid submission';
        }
    }
}

// Get single submission for review
$submission = null;
if ($submissionId > 0) {
    $stmt = db()->prepare('
        SELECT s.*, q.title as quest_title, q.max_points, q.description as quest_description,
               u.email, p.full_name
        FROM submissions s
        INNER JOIN quests q ON q.id = s.quest_id
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE s.id = ? AND q.course_id = ?
    ');
    $stmt->execute([$submissionId, $courseId]);
    $submission = $stmt->fetch();
}

// Get all submissions for this course
$query = '
    SELECT s.id, s.status, s.points_awarded, s.submitted_at,
           q.title as quest_title, q.max_points,
           u.email, p.full_name
    FROM submissions s
    INNER JOIN quests q ON q.id = s.quest_id
    INNER JOIN users u ON u.id = s.user_id
    LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE q.course_id = :course_id
';

if ($statusFilter !== 'all') {
    $query .= ' AND s.status = :status';
}

$query .= ' ORDER BY s.submitted_at DESC LIMIT 100';

$stmt = db()->prepare($query);
$stmt->bindValue(':course_id', $courseId);
if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter);
}
$stmt->execute();
$submissions = $stmt->fetchAll();

// Get counts for filters
$countsStmt = db()->prepare('
    SELECT s.status, COUNT(*) as count
    FROM submissions s
    INNER JOIN quests q ON q.id = s.quest_id
    WHERE q.course_id = ?
    GROUP BY s.status
');
$countsStmt->execute([$courseId]);
$counts = [];
foreach ($countsStmt->fetchAll() as $row) {
    $counts[$row['status']] = $row['count'];
}
$counts['all'] = array_sum($counts);

$pageTitle = 'Review Submissions - ' . $course['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Aprnder</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="../admin/admin-styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="../index.php" class="logo">
                    <img src="../../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto;">
                </a>
                <ul class="nav-links">
                    <li><a href="../dashboard.php">Home</a></li>
                    <li><a href="dashboard.php">My Courses</a></li>
                    <li><a href="submissions.php?course_id=<?php echo $courseId; ?>" class="active">Submissions</a></li>
                    <li><a href="create-course.php">Create Course</a></li>
                    <li><a href="../courses.php">All Courses</a></li>
                </ul>
                <a href="../logout.php" class="btn-login">Logout</a>
            </div>
        </div>
    </nav>

    <section class="admin-section">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <div class="admin-header" style="text-align: center;">
                <h1 style="margin-bottom: 0.5rem;"><img src="../../images/Progress.png" alt="Review" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Review Submissions</h1>
                <p style="margin: 0;">Course: <?php echo htmlspecialchars($course['title']); ?></p>
                <div style="margin-top: 1rem;">
                    <a href="dashboard.php" class="btn btn-secondary">← Back to My Courses</a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><img src="../../images/Pending.png" alt="Error" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><img src="../../images/Updated.png" alt="Success" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($submission): ?>
                <!-- Single Submission Review -->
                <div class="content-section" style="max-width: 900px; margin: 0 auto;">
                    <div class="data-card" style="background: rgba(255, 255, 255, 0.02); padding: 2rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1;">
                        <h2 style="margin-top: 0; display: flex; align-items: center; gap: 0.5rem;"><img src="../../images/Task.png" alt="Quest" style="width: 24px; height: 24px; object-fit: contain;"> <?php echo htmlspecialchars($submission['quest_title']); ?></h2>
                        
                        <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
                            <div>
                                <strong style="color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Student</strong>
                                <p style="margin: 0.5rem 0 0 0;"><?php echo htmlspecialchars($submission['full_name'] ?? $submission['email']); ?></p>
                            </div>
                            <div>
                                <strong style="color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Status</strong>
                                <p style="margin: 0.5rem 0 0 0;">
                                    <?php
                                    $statusColors = ['pending' => '#f59e0b', 'passed' => '#10b981', 'failed' => '#ef4444', 'resubmitted' => '#6366f1'];
                                    $statusColor = $statusColors[$submission['status']] ?? '#6b7280';
                                    ?>
                                    <span style="color: <?php echo $statusColor; ?>; font-weight: 600;">
                                        <?php echo ucfirst($submission['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <strong style="color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Max Points</strong>
                                <p style="margin: 0.5rem 0 0 0;"><?php echo $submission['max_points']; ?></p>
                            </div>
                            <div>
                                <strong style="color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Submitted</strong>
                                <p style="margin: 0.5rem 0 0 0;"><?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($submission['quest_description'])): ?>
                            <h3>Quest Description</h3>
                            <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;"><?php echo nl2br(htmlspecialchars($submission['quest_description'])); ?></p>
                        <?php endif; ?>

                        <h3>Student's Submission</h3>
                        <pre style="background: rgba(0, 0, 0, 0.3); padding: 1.5rem; border-radius: 0.5rem; overflow-x: auto; white-space: pre-wrap; color: #fff; border: 1px solid rgba(255, 255, 255, 0.1);"><?php echo htmlspecialchars($submission['code']); ?></pre>

                        <?php if (!empty($submission['feedback'])): ?>
                            <h3>Your Previous Feedback</h3>
                            <p style="background: rgba(0, 0, 0, 0.3); padding: 1rem; border-radius: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); color: rgba(255, 255, 255, 0.8);">
                                <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                            </p>
                        <?php endif; ?>

                        <form method="POST" class="admin-form" style="margin-top: 2rem;">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                            
                            <div class="form-group">
                                <label for="status">Status *</label>
                                <select id="status" name="status" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff;">
                                    <option value="pending" <?php echo $submission['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="passed" <?php echo $submission['status'] === 'passed' ? 'selected' : ''; ?>>Passed</option>
                                    <option value="failed" <?php echo $submission['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="points_awarded">Points Awarded *</label>
                                <input 
                                    type="number" 
                                    id="points_awarded" 
                                    name="points_awarded" 
                                    min="0" 
                                    max="<?php echo $submission['max_points']; ?>" 
                                    value="<?php echo $submission['points_awarded'] ?? 0; ?>" 
                                    required
                                    style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff;"
                                >
                                <small style="color: rgba(255, 255, 255, 0.6);">Maximum: <?php echo $submission['max_points']; ?> points</small>
                            </div>

                            <div class="form-group">
                                <label for="feedback">Feedback</label>
                                <textarea 
                                    id="feedback" 
                                    name="feedback" 
                                    rows="5" 
                                    placeholder="Provide helpful feedback to the student..."
                                    style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; resize: vertical; min-height: 120px;"
                                ><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-actions" style="display: flex; gap: 1rem; justify-content: center; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                                <button type="submit" name="review_submission" class="btn btn-primary btn-large" style="padding: 1rem 2rem; font-size: 1rem; font-weight: 600;">
                                    ✓ Submit Review
                                </button>
                                <a href="?course_id=<?php echo $courseId; ?>&status=<?php echo $statusFilter; ?>" class="btn btn-secondary btn-large" style="padding: 1rem 2rem;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Submissions List -->
                <div class="content-section" style="max-width: 1200px; margin: 0 auto;">
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; justify-content: center; flex-wrap: wrap;">
                        <a href="?course_id=<?php echo $courseId; ?>&status=all" class="btn <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 0.75rem 1.5rem;">
                            All (<?php echo $counts['all'] ?? 0; ?>)
                        </a>
                        <a href="?course_id=<?php echo $courseId; ?>&status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 0.75rem 1.5rem;">
                            Pending (<?php echo $counts['pending'] ?? 0; ?>)
                        </a>
                        <a href="?course_id=<?php echo $courseId; ?>&status=passed" class="btn <?php echo $statusFilter === 'passed' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 0.75rem 1.5rem;">
                            Passed (<?php echo $counts['passed'] ?? 0; ?>)
                        </a>
                        <a href="?course_id=<?php echo $courseId; ?>&status=failed" class="btn <?php echo $statusFilter === 'failed' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 0.75rem 1.5rem;">
                            Failed (<?php echo $counts['failed'] ?? 0; ?>)
                        </a>
                    </div>

                    <?php if (!empty($submissions)): ?>
                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Quest</th>
                                        <th>Status</th>
                                        <th>Points</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $sub): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sub['full_name'] ?? $sub['email']); ?></td>
                                            <td><?php echo htmlspecialchars($sub['quest_title']); ?></td>
                                            <td>
                                                <?php
                                                $statusColors = ['pending' => '#f59e0b', 'passed' => '#10b981', 'failed' => '#ef4444', 'resubmitted' => '#6366f1'];
                                                $statusColor = $statusColors[$sub['status']] ?? '#6b7280';
                                                ?>
                                                <span style="color: <?php echo $statusColor; ?>; font-weight: 600;">
                                                    <?php echo ucfirst($sub['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $sub['points_awarded'] ?? '-'; ?> / <?php echo $sub['max_points']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($sub['submitted_at'])); ?></td>
                                            <td>
                                                <a href="?course_id=<?php echo $courseId; ?>&id=<?php echo $sub['id']; ?>&status=<?php echo $statusFilter; ?>" class="btn btn-small btn-primary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                                                    Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;"><img src="../../images/Task.png" alt="No Submissions" style="width: 64px; height: 64px; object-fit: contain;"></div>
                            <h3>No submissions yet</h3>
                            <p style="color: rgba(255, 255, 255, 0.6);">Submissions from students will appear here when they submit quests.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>

