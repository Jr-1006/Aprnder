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

$courseId = (int)($_GET['id'] ?? 0);

// Get course and verify ownership
$courseStmt = db()->prepare('SELECT * FROM courses WHERE id = ? AND created_by = ?');
$courseStmt->execute([$courseId, $userId]);
$course = $courseStmt->fetch();

if (!$course) {
    header('Location: dashboard.php?error=not_found');
    exit;
}

$success = '';
$error = '';

// Handle course update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Course title is required.';
    } elseif (empty($description)) {
        $error = 'Course description is required.';
    } else {
        try {
            $stmt = db()->prepare('UPDATE courses SET title = ?, description = ? WHERE id = ? AND created_by = ?');
            $stmt->execute([$title, $description, $courseId, $userId]);
            $success = 'Course updated successfully!';
            
            // Refresh course data
            $courseStmt->execute([$courseId, $userId]);
            $course = $courseStmt->fetch();
        } catch (Exception $e) {
            $error = 'Error updating course: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Course';
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
                    <h1><img src="../../images/Edit.png" alt="Edit" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Edit Course</h1>
                    <p>Update your course information</p>
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

            <div class="form-container">
                <form method="POST" class="admin-form">
                    <div class="form-section">
                        <h3><img src="../../images/Grad Cap.png" alt="Course" style="width: 20px; height: 20px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Course Information</h3>
                        
                        <div class="form-group">
                            <label for="title">Course Title *</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="form-control" 
                                required
                                value="<?php echo htmlspecialchars($course['title']); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="description">Course Description *</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                class="form-control" 
                                rows="6" 
                                required
                            ><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_course" class="btn btn-primary btn-large">
                            💾 Update Course
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-large">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

</body>
</html>
