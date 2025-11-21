<?php
require_once __DIR__ . '/../../src/bootstrap.php';
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

// Get statistics
$stats = [];

// Total users
$usersStmt = db()->query('SELECT COUNT(*) as count FROM users');
$stats['total_users'] = $usersStmt->fetch()['count'];

// Total courses
$coursesStmt = db()->query('SELECT COUNT(*) as count FROM courses');
$stats['total_courses'] = $coursesStmt->fetch()['count'];

// Total quests
$questsStmt = db()->query('SELECT COUNT(*) as count FROM quests');
$stats['total_quests'] = $questsStmt->fetch()['count'];

// Pending submissions
$pendingStmt = db()->query('SELECT COUNT(*) as count FROM submissions WHERE status = "pending"');
$stats['pending_submissions'] = $pendingStmt->fetch()['count'];

// Total enrollments
$enrollStmt = db()->query('SELECT COUNT(*) as count FROM enrollments');
$stats['total_enrollments'] = $enrollStmt->fetch()['count'];

// Total game plays
$gamesStmt = db()->query('SELECT COUNT(*) as count FROM game_scores');
$stats['total_games'] = $gamesStmt->fetch()['count'];

// Recent submissions
$recentSubmissionsStmt = db()->query('
    SELECT s.id, s.status, s.submitted_at, q.title as quest_title, u.email, p.full_name
    FROM submissions s
    INNER JOIN quests q ON q.id = s.quest_id
    INNER JOIN users u ON u.id = s.user_id
    LEFT JOIN user_profiles p ON p.user_id = u.id
    ORDER BY s.submitted_at DESC
    LIMIT 10
');
$recentSubmissions = $recentSubmissionsStmt->fetchAll();

// Recent users
$recentUsersStmt = db()->query('
    SELECT u.id, u.email, u.role, u.created_at, p.full_name
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    ORDER BY u.created_at DESC
    LIMIT 10
');
$recentUsers = $recentUsersStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="quests.php">Quests</a></li>
                    <li><a href="submissions.php">Submissions</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="mentors.php">Mentors</a></li>
                </ul>
                <a href="../logout.php" class="btn-login">Logout</a>
            </div>
        </div>
    </nav>

    <section class="admin-section">
        <div class="container">
            <div class="admin-header">
                <h1><img src="../../images/Progress.png" alt="Dashboard" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Admin Dashboard</h1>
                <p>Manage your learning platform</p>
            </div>

            <!-- Statistics Grid -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="stat-icon"><img src="../../images/Students.png" alt="Users" style="width: 40px; height: 40px; object-fit: contain;"></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon"><img src="../../images/Grad Cap.png" alt="Courses" style="width: 40px; height: 40px; object-fit: contain;"></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_courses']); ?></div>
                        <div class="stat-label">Courses</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon"><img src="../../images/Task.png" alt="Quests" style="width: 40px; height: 40px; object-fit: contain;"></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_quests']); ?></div>
                        <div class="stat-label">Quests</div>
                    </div>
                </div>
                <div class="admin-stat-card highlight">
                    <div class="stat-icon"><img src="../../images/Pending.png" alt="Pending" style="width: 40px; height: 40px; object-fit: contain;"></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['pending_submissions']); ?></div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_enrollments']); ?></div>
                        <div class="stat-label">Enrollments</div>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon"><img src="../../images/Game.png" alt="Games" style="width: 40px; height: 40px; object-fit: contain;"></div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($stats['total_games']); ?></div>
                        <div class="stat-label">Games Played</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="courses.php?action=create" class="btn btn-primary">➕ Create Course</a>
                    <a href="quests.php?action=create" class="btn btn-primary">➕ Create Quest</a>
                    <a href="submissions.php?status=pending" class="btn btn-secondary">📋 Review Submissions</a>
                    <a href="users.php" class="btn btn-secondary"><img src="../../images/Students.png" alt="Users" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px;">Manage Users</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="admin-grid">
                <!-- Recent Submissions -->
                <div class="admin-panel">
                    <h2>Recent Submissions</h2>
                    <?php if (!empty($recentSubmissions)): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Quest</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSubmissions as $sub): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sub['full_name'] ?? $sub['email']); ?></td>
                                            <td><?php echo htmlspecialchars($sub['quest_title']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $sub['status']; ?>">
                                                    <?php echo ucfirst($sub['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($sub['submitted_at'])); ?></td>
                                            <td>
                                                <a href="submissions.php?id=<?php echo $sub['id']; ?>" class="btn-small">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="submissions.php" class="view-all-link">View All Submissions →</a>
                    <?php else: ?>
                        <p class="empty-message">No submissions yet</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Users -->
                <div class="admin-panel">
                    <h2>Recent Users</h2>
                    <?php if (!empty($recentUsers)): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $u): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['full_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo $u['role']; ?>">
                                                    <?php echo ucfirst($u['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="users.php" class="view-all-link">View All Users →</a>
                    <?php else: ?>
                        <p class="empty-message">No users yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="../static/navbar.js" defer></script>
</body>
</html>
