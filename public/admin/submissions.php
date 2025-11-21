<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Security.php';
require_once __DIR__ . '/../../src/BadgeService.php';
require_login();

// Check if user is admin (mentors NOT allowed)
$userId = current_user_id();
$userStmt = db()->prepare('SELECT role FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard.php?error=access_denied');
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
    }
}

// Get single submission for review
$submission = null;
if ($submissionId > 0) {
    $stmt = db()->prepare('
        SELECT s.*, q.title as quest_title, q.max_points, q.description as quest_description,
               c.title as course_title, u.email, p.full_name
        FROM submissions s
        INNER JOIN quests q ON q.id = s.quest_id
        INNER JOIN courses c ON c.id = q.course_id
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE s.id = ?
    ');
    $stmt->execute([$submissionId]);
    $submission = $stmt->fetch();
}

// Get all submissions with filter
$query = '
    SELECT s.id, s.status, s.points_awarded, s.submitted_at,
           q.title as quest_title, q.max_points,
           c.title as course_title,
           u.email, p.full_name
    FROM submissions s
    INNER JOIN quests q ON q.id = s.quest_id
    INNER JOIN courses c ON c.id = q.course_id
    INNER JOIN users u ON u.id = s.user_id
    LEFT JOIN user_profiles p ON p.user_id = u.id
';

if ($statusFilter !== 'all') {
    $query .= ' WHERE s.status = :status';
}

$query .= ' ORDER BY s.submitted_at DESC LIMIT 100';

$stmt = db()->prepare($query);
if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter);
}
$stmt->execute();
$submissions = $stmt->fetchAll();

// Get counts for filters
$countsStmt = db()->query('
    SELECT status, COUNT(*) as count
    FROM submissions
    GROUP BY status
');
$counts = [];
foreach ($countsStmt->fetchAll() as $row) {
    $counts[$row['status']] = $row['count'];
}
$counts['all'] = array_sum($counts);

$pageTitle = 'Review Submissions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="../index.php" class="logo">
                    <img src="../../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto;">
                </a>
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="quests.php">Quests</a></li>
                    <li><a href="submissions.php" class="active">Submissions</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="mentors.php">Mentors</a></li>
                </ul>
                <a href="../logout.php" class="btn-login">Logout</a>
            </div>
        </div>
    </nav>

    <section class="admin-section">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><img src="../../images/Pending.png" alt="Warning" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><img src="../../images/Updated.png" alt="Success" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!$submission): ?>
                <!-- List View -->
                <div class="admin-header">
                    <h1>📋 Review Submissions</h1>
                </div>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?status=all" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                        All (<?php echo $counts['all'] ?? 0; ?>)
                    </a>
                    <a href="?status=pending" class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                        Pending (<?php echo $counts['pending'] ?? 0; ?>)
                    </a>
                    <a href="?status=passed" class="filter-tab <?php echo $statusFilter === 'passed' ? 'active' : ''; ?>">
                        Passed (<?php echo $counts['passed'] ?? 0; ?>)
                    </a>
                    <a href="?status=failed" class="filter-tab <?php echo $statusFilter === 'failed' ? 'active' : ''; ?>">
                        Failed (<?php echo $counts['failed'] ?? 0; ?>)
                    </a>
                </div>

                <?php if (!empty($submissions)): ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Quest</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Points</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $s): ?>
                                    <tr>
                                        <td><?php echo $s['id']; ?></td>
                                        <td><?php echo htmlspecialchars($s['full_name'] ?? $s['email']); ?></td>
                                        <td><?php echo htmlspecialchars($s['quest_title']); ?></td>
                                        <td><?php echo htmlspecialchars($s['course_title']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $s['status']; ?>">
                                                <?php echo ucfirst($s['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $s['points_awarded'] ? $s['points_awarded'] . '/' . $s['max_points'] : '-'; ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($s['submitted_at'])); ?></td>
                                        <td>
                                            <a href="?id=<?php echo $s['id']; ?>" class="btn-small btn-edit">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>No submissions found</h3>
                        <p>No submissions match the selected filter.</p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Review View -->
                <div class="admin-header">
                    <h1><img src="../../images/Task.png" alt="Review" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Review Submission</h1>
                    <a href="submissions.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="submission-review-container">
                    <!-- Submission Info -->
                    <div class="submission-info-panel">
                        <h2>Submission Details</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Student:</span>
                                <span class="info-value"><?php echo htmlspecialchars($submission['full_name'] ?? $submission['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Course:</span>
                                <span class="info-value"><?php echo htmlspecialchars($submission['course_title']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Quest:</span>
                                <span class="info-value"><?php echo htmlspecialchars($submission['quest_title']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Max Points:</span>
                                <span class="info-value"><?php echo $submission['max_points']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Submitted:</span>
                                <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Current Status:</span>
                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quest Description -->
                    <div class="quest-description-panel">
                        <h3>Quest Requirements</h3>
                        <p><?php echo nl2br(htmlspecialchars($submission['quest_description'])); ?></p>
                    </div>

                    <!-- Submitted Code -->
                    <div class="code-panel">
                        <h3>Submitted Code</h3>
                        <div class="code-display"><?php echo htmlspecialchars($submission['code']); ?></div>
                    </div>

                    <!-- Review Form -->
                    <div class="review-form">
                        <h3>Provide Feedback</h3>
                        <form method="post">
                            <?php echo Security::csrfField(); ?>
                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">

                            <div class="form-group">
                                <label for="status" class="form-label">Status *</label>
                                <select id="status" name="status" class="form-input" required onchange="togglePoints(this.value)">
                                    <option value="pending" <?php echo $submission['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="passed" <?php echo $submission['status'] === 'passed' ? 'selected' : ''; ?>>Passed</option>
                                    <option value="failed" <?php echo $submission['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>

                            <div class="form-group" id="points-group">
                                <label for="points_awarded" class="form-label">Points Awarded *</label>
                                <input 
                                    type="number" 
                                    id="points_awarded" 
                                    name="points_awarded" 
                                    class="form-input" 
                                    min="0" 
                                    max="<?php echo $submission['max_points']; ?>"
                                    value="<?php echo $submission['points_awarded'] ?? $submission['max_points']; ?>"
                                >
                                <small class="form-help">Max: <?php echo $submission['max_points']; ?> points</small>
                            </div>

                            <div class="form-group">
                                <label for="feedback" class="form-label">Feedback *</label>
                                <textarea 
                                    id="feedback" 
                                    name="feedback" 
                                    class="form-input" 
                                    rows="6"
                                    required
                                    placeholder="Provide constructive feedback to the student..."
                                ><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                            </div>

                            <div class="review-actions">
                                <button type="submit" name="review_submission" class="btn btn-primary btn-large">
                                    💾 Submit Review
                                </button>
                                <a href="submissions.php" class="btn btn-secondary btn-large">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function togglePoints(status) {
                        const pointsGroup = document.getElementById('points-group');
                        const pointsInput = document.getElementById('points_awarded');
                        
                        if (status === 'passed') {
                            pointsGroup.style.display = 'block';
                            pointsInput.required = true;
                        } else {
                            pointsGroup.style.display = 'none';
                            pointsInput.required = false;
                            pointsInput.value = 0;
                        }
                    }
                    
                    // Initialize on page load
                    togglePoints(document.getElementById('status').value);
                </script>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
