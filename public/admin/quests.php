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
$questId = (int)($_GET['id'] ?? 0);

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_quest'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $courseId = (int)$_POST['course_id'];
        $title = Security::sanitize($_POST['title'] ?? '');
        $description = Security::sanitize($_POST['description'] ?? '');
        $difficulty = $_POST['difficulty'] ?? 'easy';
        $maxPoints = (int)$_POST['max_points'];
        
        if (empty($title) || empty($description) || $courseId <= 0) {
            $error = 'All fields are required';
        } else {
            try {
                $stmt = db()->prepare('
                    INSERT INTO quests (course_id, title, description, difficulty, max_points) 
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$courseId, $title, $description, $difficulty, $maxPoints]);
                $success = 'Quest created successfully!';
                $action = 'list';
            } catch (Exception $e) {
                error_log('Quest creation error: ' . $e->getMessage());
                $error = 'Failed to create quest';
            }
        }
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quest'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $id = (int)$_POST['quest_id'];
        $courseId = (int)$_POST['course_id'];
        $title = Security::sanitize($_POST['title'] ?? '');
        $description = Security::sanitize($_POST['description'] ?? '');
        $difficulty = $_POST['difficulty'] ?? 'easy';
        $maxPoints = (int)$_POST['max_points'];
        
        if (empty($title) || empty($description)) {
            $error = 'All fields are required';
        } else {
            try {
                $stmt = db()->prepare('
                    UPDATE quests 
                    SET course_id = ?, title = ?, description = ?, difficulty = ?, max_points = ? 
                    WHERE id = ?
                ');
                $stmt->execute([$courseId, $title, $description, $difficulty, $maxPoints, $id]);
                $success = 'Quest updated successfully!';
                $action = 'list';
            } catch (Exception $e) {
                error_log('Quest update error: ' . $e->getMessage());
                $error = 'Failed to update quest';
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = db()->prepare('DELETE FROM quests WHERE id = ?');
        $stmt->execute([$deleteId]);
        $success = 'Quest deleted successfully!';
    } catch (Exception $e) {
        error_log('Quest deletion error: ' . $e->getMessage());
        $error = 'Failed to delete quest';
    }
}

// Get quest for editing
$quest = null;
if ($action === 'edit' && $questId > 0) {
    $stmt = db()->prepare('SELECT * FROM quests WHERE id = ?');
    $stmt->execute([$questId]);
    $quest = $stmt->fetch();
    if (!$quest) {
        $error = 'Quest not found';
        $action = 'list';
    }
}

// Get all courses for dropdown
$coursesStmt = db()->query('SELECT id, title FROM courses ORDER BY title');
$courses = $coursesStmt->fetchAll();

// Get all quests
$questsStmt = db()->query('
    SELECT q.*, c.title as course_title,
           COUNT(DISTINCT s.id) as submission_count
    FROM quests q
    INNER JOIN courses c ON c.id = q.course_id
    LEFT JOIN submissions s ON s.quest_id = q.id
    GROUP BY q.id
    ORDER BY q.created_at DESC
');
$quests = $questsStmt->fetchAll();

$pageTitle = 'Manage Quests';
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
                    <li><a href="quests.php" class="active">Quests</a></li>
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
                    <h1><img src="../../images/Task.png" alt="Quests" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Manage Quests</h1>
                    <a href="?action=create" class="btn btn-primary">➕ Create New Quest</a>
                </div>

                <?php if (!empty($quests)): ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Difficulty</th>
                                    <th>Points</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quests as $q): ?>
                                    <tr>
                                        <td><?php echo $q['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($q['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($q['course_title']); ?></td>
                                        <td>
                                            <span class="difficulty-badge difficulty-<?php echo $q['difficulty']; ?>">
                                                <?php echo ucfirst($q['difficulty']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $q['max_points']; ?></td>
                                        <td><?php echo (int)$q['submission_count']; ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $q['id']; ?>" class="btn-small btn-edit">Edit</a>
                                            <a href="?delete=<?php echo $q['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><img src="../../images/Task.png" alt="Quests" style="width: 64px; height: 64px; object-fit: contain;"></div>
                        <h3>No quests yet</h3>
                        <p>Create your first quest to get started!</p>
                        <a href="?action=create" class="btn btn-primary">Create Quest</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'create' || $action === 'edit'): ?>
                <!-- Create/Edit Form -->
                <div class="admin-header">
                    <h1><?php echo $action === 'create' ? '➕ Create New Quest' : '<img src="../../images/Edit.png" alt="Edit" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Edit Quest'; ?></h1>
                    <a href="quests.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="admin-form-container">
                    <form method="post" class="admin-form">
                        <?php echo Security::csrfField(); ?>
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="quest_id" value="<?php echo $quest['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="course_id" class="form-label">Course *</label>
                            <select id="course_id" name="course_id" class="form-input" required>
                                <option value="">Select a course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($quest && $quest['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="title" class="form-label">Quest Title *</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($quest['title'] ?? ''); ?>"
                                required
                                placeholder="e.g., Print Hello World"
                            >
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description *</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                class="form-input" 
                                rows="8"
                                required
                                placeholder="Describe the challenge. What should students implement?"
                            ><?php echo htmlspecialchars($quest['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="difficulty" class="form-label">Difficulty *</label>
                                <select id="difficulty" name="difficulty" class="form-input" required>
                                    <option value="easy" <?php echo ($quest && $quest['difficulty'] === 'easy') ? 'selected' : ''; ?>>Easy</option>
                                    <option value="medium" <?php echo ($quest && $quest['difficulty'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="hard" <?php echo ($quest && $quest['difficulty'] === 'hard') ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="max_points" class="form-label">Max Points *</label>
                                <input 
                                    type="number" 
                                    id="max_points" 
                                    name="max_points" 
                                    class="form-input" 
                                    value="<?php echo $quest['max_points'] ?? 10; ?>"
                                    min="1"
                                    max="1000"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="<?php echo $action === 'create' ? 'create_quest' : 'update_quest'; ?>" class="btn btn-primary btn-large">
                                <?php echo $action === 'create' ? '➕ Create Quest' : '💾 Update Quest'; ?>
                            </button>
                            <a href="quests.php" class="btn btn-secondary btn-large">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
