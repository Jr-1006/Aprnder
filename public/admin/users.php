<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Security.php';
require_login();

// Check if user is admin
$userId = current_user_id();
$userStmt = db()->prepare('SELECT role FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($user['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$success = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$editUserId = (int)($_GET['id'] ?? 0);

// Handle Update User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $id = (int)$_POST['user_id'];
        $role = $_POST['role'];
        $active = isset($_POST['active']) ? 1 : 0;
        
        try {
            $stmt = db()->prepare('UPDATE users SET role = ?, active = ? WHERE id = ?');
            $stmt->execute([$role, $active, $id]);
            $success = 'User updated successfully!';
            $action = 'list';
        } catch (Exception $e) {
            error_log('User update error: ' . $e->getMessage());
            $error = 'Failed to update user';
        }
    }
}

// Handle Delete User
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId === $userId) {
        $error = 'You cannot delete your own account!';
    } else {
        try {
            $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$deleteId]);
            $success = 'User deleted successfully!';
        } catch (Exception $e) {
            error_log('User deletion error: ' . $e->getMessage());
            $error = 'Failed to delete user';
        }
    }
}

// Get user for editing
$editUser = null;
if ($action === 'edit' && $editUserId > 0) {
    $stmt = db()->prepare('
        SELECT u.*, p.full_name, p.bio,
               COUNT(DISTINCT e.course_id) as enrolled_courses,
               COUNT(DISTINCT s.id) as submissions,
               MAX(gs.score) as best_score
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        LEFT JOIN enrollments e ON e.user_id = u.id
        LEFT JOIN submissions s ON s.user_id = u.id
        LEFT JOIN game_scores gs ON gs.user_id = u.id
        WHERE u.id = ?
        GROUP BY u.id
    ');
    $stmt->execute([$editUserId]);
    $editUser = $stmt->fetch();
    if (!$editUser) {
        $error = 'User not found';
        $action = 'list';
    }
}

// Get all users
$usersStmt = db()->query('
    SELECT u.*, p.full_name,
           COUNT(DISTINCT e.course_id) as enrolled_courses,
           COUNT(DISTINCT s.id) as submissions,
           MAX(gs.score) as best_score
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN enrollments e ON e.user_id = u.id
    LEFT JOIN submissions s ON s.user_id = u.id
    LEFT JOIN game_scores gs ON gs.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
');
$users = $usersStmt->fetchAll();

$pageTitle = 'Manage Users';
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
                    <li><a href="submissions.php">Submissions</a></li>
                    <li><a href="users.php" class="active">Users</a></li>
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
                    <h1><img src="../../images/Students.png" alt="Users" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Manage Users</h1>
                </div>

                <?php if (!empty($users)): ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Courses</th>
                                    <th>Submissions</th>
                                    <th>Best Score</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['full_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $u['role']; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['active']): ?>
                                                <span class="status-badge status-passed">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-failed">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (int)$u['enrolled_courses']; ?></td>
                                        <td><?php echo (int)$u['submissions']; ?></td>
                                        <td><?php echo number_format((int)$u['best_score']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $u['id']; ?>" class="btn-small btn-edit">Edit</a>
                                            <?php if ($u['id'] !== $userId): ?>
                                                <a href="?delete=<?php echo $u['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Are you sure? This will delete all user data!')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><img src="../../images/Students.png" alt="Users" style="width: 64px; height: 64px; object-fit: contain;"></div>
                        <h3>No users found</h3>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'edit'): ?>
                <!-- Edit Form -->
                <div class="admin-header">
                    <h1><img src="../../images/Edit.png" alt="Edit" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Edit User</h1>
                    <a href="users.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="admin-form-container">
                    <form method="post" class="admin-form">
                        <?php echo Security::csrfField(); ?>
                        <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input 
                                type="email" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($editUser['email']); ?>"
                                disabled
                            >
                            <small class="form-help">Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input 
                                type="text" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($editUser['full_name'] ?? 'N/A'); ?>"
                                disabled
                            >
                            <small class="form-help">User can update this in their profile</small>
                        </div>

                        <div class="form-group">
                            <label for="role" class="form-label">Role *</label>
                            <select id="role" name="role" class="form-input" required>
                                <option value="user" <?php echo $editUser['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="student" <?php echo $editUser['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="mentor" <?php echo $editUser['role'] === 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                                <option value="admin" <?php echo $editUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="active" <?php echo $editUser['active'] ? 'checked' : ''; ?>>
                                <span>Account Active</span>
                            </label>
                            <small class="form-help">Inactive users cannot log in</small>
                        </div>

                        <div class="user-stats-display">
                            <h3>User Statistics</h3>
                            <div class="stats-grid-small">
                                <div class="stat-item-small">
                                    <span class="stat-label-small">Enrolled Courses:</span>
                                    <span class="stat-value-small"><?php echo (int)$editUser['enrolled_courses']; ?></span>
                                </div>
                                <div class="stat-item-small">
                                    <span class="stat-label-small">Submissions:</span>
                                    <span class="stat-value-small"><?php echo (int)$editUser['submissions']; ?></span>
                                </div>
                                <div class="stat-item-small">
                                    <span class="stat-label-small">Best Score:</span>
                                    <span class="stat-value-small"><?php echo number_format((int)$editUser['best_score']); ?></span>
                                </div>
                                <div class="stat-item-small">
                                    <span class="stat-label-small">Member Since:</span>
                                    <span class="stat-value-small"><?php echo date('M j, Y', strtotime($editUser['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_user" class="btn btn-primary btn-large">
                                💾 Update User
                            </button>
                            <a href="users.php" class="btn btn-secondary btn-large">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
