<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Security.php';
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
$action = $_GET['action'] ?? 'list';
$courseId = (int)($_GET['id'] ?? 0);

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $title = Security::sanitize($_POST['title'] ?? '');
        $description = Security::sanitize($_POST['description'] ?? '');
        
        if (empty($title)) {
            $error = 'Course title is required';
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO courses (title, description, created_by) VALUES (?, ?, ?)');
                $stmt->execute([$title, $description, $userId]);
                $success = 'Course created successfully!';
                $action = 'list';
            } catch (Exception $e) {
                error_log('Course creation error: ' . $e->getMessage());
                $error = 'Failed to create course';
            }
        }
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $id = (int)$_POST['course_id'];
        $title = Security::sanitize($_POST['title'] ?? '');
        $description = Security::sanitize($_POST['description'] ?? '');
        
        if (empty($title)) {
            $error = 'Course title is required';
        } else {
            try {
                $stmt = db()->prepare('UPDATE courses SET title = ?, description = ? WHERE id = ?');
                $stmt->execute([$title, $description, $id]);
                $success = 'Course updated successfully!';
                $action = 'list';
            } catch (Exception $e) {
                error_log('Course update error: ' . $e->getMessage());
                $error = 'Failed to update course';
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = db()->prepare('DELETE FROM courses WHERE id = ?');
        $stmt->execute([$deleteId]);
        $success = 'Course deleted successfully!';
    } catch (Exception $e) {
        error_log('Course deletion error: ' . $e->getMessage());
        $error = 'Failed to delete course. It may have associated quests.';
    }
}

// Get course for editing
$course = null;
if ($action === 'edit' && $courseId > 0) {
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();
    if (!$course) {
        $error = 'Course not found';
        $action = 'list';
    }
}

// Get all courses
$coursesStmt = db()->query('
    SELECT c.*, u.email as creator_email, p.full_name as creator_name,
           COUNT(DISTINCT q.id) as quest_count,
           COUNT(DISTINCT e.user_id) as enrollment_count
    FROM courses c
    LEFT JOIN users u ON u.id = c.created_by
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN quests q ON q.course_id = c.id
    LEFT JOIN enrollments e ON e.course_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
');
$courses = $coursesStmt->fetchAll();

$pageTitle = 'Manage Courses';
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
                    <li><a href="courses.php" class="active">Courses</a></li>
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

            <?php if ($action === 'list'): ?>
                <!-- List View -->
                <div class="admin-header">
                    <h1><img src="../../images/Grad Cap.png" alt="Courses" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Manage Courses</h1>
                    <a href="?action=create" class="btn btn-primary">➕ Create New Course</a>
                </div>

                <?php if (!empty($courses)): ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Creator</th>
                                    <th>Quests</th>
                                    <th>Enrollments</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td><?php echo $c['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($c['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($c['creator_name'] ?? $c['creator_email']); ?></td>
                                        <td><?php echo (int)$c['quest_count']; ?></td>
                                        <td><?php echo (int)$c['enrollment_count']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn-small btn-edit">Edit</a>
                                            <a href="?delete=<?php echo $c['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Are you sure? This will delete all associated quests!')">Delete</a>
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
                        <p>Create your first course to get started!</p>
                        <a href="?action=create" class="btn btn-primary">Create Course</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'create' || $action === 'edit'): ?>
                <!-- Create/Edit Form -->
                <div class="admin-header">
                    <h1><?php echo $action === 'create' ? '➕ Create New Course' : '<img src="../../images/Edit.png" alt="Edit" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Edit Course'; ?></h1>
                    <a href="courses.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="admin-form-container">
                    <form method="post" class="admin-form">
                        <?php echo Security::csrfField(); ?>
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="title" class="form-label">Course Title *</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>"
                                required
                                placeholder="e.g., Introduction to Python"
                            >
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description *</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                class="form-input" 
                                rows="6"
                                required
                                placeholder="Describe what students will learn in this course..."
                            ><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="<?php echo $action === 'create' ? 'create_course' : 'update_course'; ?>" class="btn btn-primary btn-large">
                                <?php echo $action === 'create' ? '➕ Create Course' : '💾 Update Course'; ?>
                            </button>
                            <a href="courses.php" class="btn btn-secondary btn-large">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
