<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_login();

// Check if user is mentor
$userId = current_user_id();
$userStmt = db()->prepare('SELECT u.*, p.full_name FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user['role'] !== 'mentor') {
    header('Location: ../dashboard.php?error=mentor_only');
    exit;
}

// Get mentor's courses
$coursesStmt = db()->prepare('
    SELECT 
        c.id,
        c.title,
        c.description,
        c.created_at,
        COUNT(DISTINCT e.user_id) as student_count,
        COUNT(DISTINCT q.id) as quest_count
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    LEFT JOIN quests q ON q.course_id = c.id
    WHERE c.created_by = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
');
$coursesStmt->execute([$userId]);
$courses = $coursesStmt->fetchAll();

// Get total stats
$statsStmt = db()->prepare('
    SELECT 
        COUNT(DISTINCT c.id) as total_courses,
        COUNT(DISTINCT e.user_id) as total_students,
        COUNT(DISTINCT q.id) as total_quests,
        COALESCE(SUM(q.max_points), 0) as total_points_available
    FROM courses c
    LEFT JOIN enrollments e ON e.course_id = c.id
    LEFT JOIN quests q ON q.course_id = c.id
    WHERE c.created_by = ?
');
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

$pageTitle = 'Mentor Dashboard';
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
                    <li><a href="dashboard.php" class="active">My Courses</a></li>
                    <?php if (count($courses) > 0): ?>
                        <li><a href="submissions.php?course_id=<?php echo $courses[0]['id']; ?>">Submissions</a></li>
                    <?php endif; ?>
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
                <h1 style="margin-bottom: 0.5rem;">🎓 Mentor Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($user['full_name'] ?? 'Mentor'); ?>! Manage your courses and help students learn.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid" style="justify-content: center;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_courses']; ?></div>
                        <div class="stat-label">My Courses</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-label">Students Enrolled</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_quests']; ?></div>
                        <div class="stat-label">Total Quests</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15 8 22 9 17 14 18 21 12 18 6 21 7 14 2 9 9 8 12 2"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_points_available']; ?></div>
                        <div class="stat-label">Points Available</div>
                    </div>
                </div>
            </div>

            <!-- My Courses -->
            <div class="content-section" style="max-width: 1200px; margin: 0 auto;">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="margin: 0; text-align: left;"><img src="../../images/Grad Cap.png" alt="Courses" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">My Courses</h2>
                    <a href="create-course.php" class="btn btn-primary">➕ Create New Course</a>
                </div>

                <?php if (count($courses) > 0): ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Course Title</th>
                                    <th>Students / Quests</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <strong style="display: block; margin-bottom: 0.5rem; font-size: 1.1rem;"><?php echo htmlspecialchars($course['title']); ?></strong>
                                            <div style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                                                <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <span><strong style="color: #10b981;"><?php echo $course['student_count']; ?></strong> Students</span>
                                                <span><strong style="color: #6366f1;"><?php echo $course['quest_count']; ?></strong> Quests</span>
                                            </div>
                                        </td>
                                        <td style="white-space: nowrap; color: var(--text-secondary);"><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                <a href="submissions.php?course_id=<?php echo $course['id']; ?>" class="btn btn-small" style="padding: 0.5rem 0.75rem; background: #f59e0b; color: white; font-size: 0.85rem;" title="View Submissions & Grade">
                                                    <img src="../../images/Progress.png" alt="Submissions" style="width: 16px; height: 16px; object-fit: contain;">
                                                </a>
                                                <a href="../course.php?id=<?php echo $course['id']; ?>" class="btn btn-small" style="padding: 0.5rem 0.75rem; background: #6b7280; color: white; font-size: 0.85rem;" title="View Course">
                                                    <img src="../../images/See.png" alt="View" style="width: 16px; height: 16px; object-fit: contain;">
                                                </a>
                                                <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-small btn-primary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" title="Edit Course">
                                                    <img src="../../images/Edit.png" alt="Edit" style="width: 16px; height: 16px; object-fit: contain;">
                                                </a>
                                                <a href="manage-quests.php?course_id=<?php echo $course['id']; ?>" class="btn btn-small" style="padding: 0.5rem 0.75rem; background: #10b981; color: white; font-size: 0.85rem;" title="Manage Quests">
                                                    <img src="../../images/Task.png" alt="Quests" style="width: 16px; height: 16px; object-fit: contain;">
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><img src="../../images/Grad Cap.png" alt="Courses" style="width: 64px; height: 64px; object-fit: contain;"></div>
                        <h3>No courses yet</h3>
                        <p>Create your first course and start helping students learn!</p>
                        <a href="create-course.php" class="btn btn-primary btn-large">➕ Create Your First Course</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

</body>
</html>
