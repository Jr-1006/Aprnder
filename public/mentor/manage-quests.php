<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Security.php';
require_login();

// Check if user is mentor
$userId = current_user_id();
$userStmt = db()->prepare('SELECT role FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user['role'] !== 'mentor') {
    header('Location: ../dashboard.php?error=mentor_only');
    exit;
}

$courseId = (int)($_GET['course_id'] ?? 0);

// Get course details and verify ownership
$courseStmt = db()->prepare('SELECT * FROM courses WHERE id = ? AND created_by = ?');
$courseStmt->execute([$courseId, $userId]);
$course = $courseStmt->fetch();

if (!$course) {
    header('Location: dashboard.php?error=not_found');
    exit;
}

$success = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$questId = (int)($_GET['id'] ?? 0);

// Handle quest creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quest'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $difficulty = $_POST['difficulty'] ?? 'easy';
    $maxPoints = (int)($_POST['max_points'] ?? 0);
    
    if (empty($title)) {
        $error = 'Quest title is required.';
    } elseif (empty($description)) {
        $error = 'Quest description is required.';
    } elseif ($maxPoints < 1 || $maxPoints > 500) {
        $error = 'Points must be between 1 and 500.';
    } else {
        try {
            if ($action === 'create') {
                $stmt = db()->prepare('INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$courseId, $title, $description, $difficulty, $maxPoints]);
                $success = 'Quest created successfully!';
            } else {
                // Verify quest ownership
                $checkStmt = db()->prepare('SELECT q.id FROM quests q INNER JOIN courses c ON c.id = q.course_id WHERE q.id = ? AND c.created_by = ?');
                $checkStmt->execute([$questId, $userId]);
                if (!$checkStmt->fetch()) {
                    $error = 'Quest not found or access denied.';
                } else {
                    $stmt = db()->prepare('UPDATE quests SET title = ?, description = ?, difficulty = ?, max_points = ? WHERE id = ?');
                    $stmt->execute([$title, $description, $difficulty, $maxPoints, $questId]);
                    $success = 'Quest updated successfully!';
                }
            }
            $action = 'list';
        } catch (Exception $e) {
            $error = 'Error saving quest: ' . $e->getMessage();
        }
    }
}

// Get quests for this course
$questsStmt = db()->prepare('
    SELECT 
        q.*,
        COUNT(DISTINCT s.id) as submission_count
    FROM quests q
    LEFT JOIN submissions s ON s.quest_id = q.id
    WHERE q.course_id = ?
    GROUP BY q.id
    ORDER BY q.created_at ASC
');
$questsStmt->execute([$courseId]);
$quests = $questsStmt->fetchAll();

// Get quest for editing
$editQuest = null;
if ($action === 'edit' && $questId > 0) {
    $editStmt = db()->prepare('SELECT q.* FROM quests q INNER JOIN courses c ON c.id = q.course_id WHERE q.id = ? AND c.created_by = ?');
    $editStmt->execute([$questId, $userId]);
    $editQuest = $editStmt->fetch();
    if (!$editQuest) {
        $error = 'Quest not found.';
        $action = 'list';
    }
}

$pageTitle = 'Manage Quests';
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
                    <li><a href="dashboard.php">My Courses</a></li>
                    <li><a href="create-course.php">Create Course</a></li>
                    <li><a href="../courses.php">All Courses</a></li>
                </ul>
                <a href="../logout.php" class="btn-login">Logout</a>
            </div>
        </div>
    </nav>

    <section class="admin-section">
        <div class="container">
            <div class="admin-header">
                <div>
                    <a href="dashboard.php" style="color: var(--color-primary); text-decoration: none; display: inline-block; margin-bottom: 0.5rem;">← Back to My Courses</a>
                    <h1><img src="../../images/Task.png" alt="Manage" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Manage Quests</h1>
                    <p><?php echo htmlspecialchars($course['title']); ?></p>
                </div>
                <?php if ($action === 'list'): ?>
                    <a href="?course_id=<?php echo $courseId; ?>&action=create" class="btn btn-primary">➕ Add New Quest</a>
                <?php endif; ?>
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

            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><img src="../../images/Updated.png" alt="Success" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content">Course created successfully! Now add some quests to get started.</div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- Quest List -->
                <?php if (count($quests) > 0): ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Quest Title</th>
                                    <th>Difficulty</th>
                                    <th>Points</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quests as $quest): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($quest['title']); ?></strong>
                                            <div style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars(substr($quest['description'], 0, 60)) . '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $quest['difficulty']; ?>">
                                                <?php echo ucfirst($quest['difficulty']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo $quest['max_points']; ?></strong> pts</td>
                                        <td><?php echo $quest['submission_count']; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="?course_id=<?php echo $courseId; ?>&action=edit&id=<?php echo $quest['id']; ?>" class="btn btn-small btn-primary">
                                                    <img src="../../images/Edit.png" alt="Edit" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px;">Edit
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
                        <div class="empty-icon"><img src="../../images/Task.png" alt="Quests" style="width: 64px; height: 64px; object-fit: contain;"></div>
                        <h3>No quests yet</h3>
                        <p>Add your first quest to this course!</p>
                        <a href="?course_id=<?php echo $courseId; ?>&action=create" class="btn btn-primary btn-large">➕ Create First Quest</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Create/Edit Quest Form -->
                <div class="form-container">
                    <form method="POST" class="admin-form">
                        <div class="form-section">
                            <h3><?php echo $action === 'create' ? '➕ Create New Quest' : '<img src="../../images/Edit.png" alt="Edit" style="width: 20px; height: 20px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Edit Quest'; ?></h3>
                            
                            <div class="form-group">
                                <label for="title">Quest Title *</label>
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    class="form-control" 
                                    required
                                    value="<?php echo htmlspecialchars($editQuest['title'] ?? ''); ?>"
                                    placeholder="e.g., Introduction to Variables"
                                >
                            </div>

                            <div class="form-group">
                                <label for="description">Quest Description *</label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="form-control" 
                                    rows="8" 
                                    required
                                    placeholder="Describe the quest objectives, requirements, and what students need to do..."
                                ><?php echo htmlspecialchars($editQuest['description'] ?? ''); ?></textarea>
                                <small class="form-text">Supports Markdown formatting for better formatting.</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="difficulty">Difficulty Level *</label>
                                    <select id="difficulty" name="difficulty" class="form-control" required>
                                        <option value="easy" <?php echo ($editQuest['difficulty'] ?? '') === 'easy' ? 'selected' : ''; ?>>Easy</option>
                                        <option value="medium" <?php echo ($editQuest['difficulty'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="hard" <?php echo ($editQuest['difficulty'] ?? '') === 'hard' ? 'selected' : ''; ?>>Hard</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="max_points">Points Reward * (1-500)</label>
                                    <input 
                                        type="number" 
                                        id="max_points" 
                                        name="max_points" 
                                        class="form-control" 
                                        required
                                        min="1"
                                        max="500"
                                        value="<?php echo htmlspecialchars($editQuest['max_points'] ?? '50'); ?>"
                                    >
                                    <small class="form-text">Students earn these points when they complete this quest.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="save_quest" class="btn btn-primary btn-large">
                                <?php echo $action === 'create' ? '➕ Create Quest' : '💾 Update Quest'; ?>
                            </button>
                            <a href="?course_id=<?php echo $courseId; ?>" class="btn btn-secondary btn-large">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
