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

$success = '';
$error = '';

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Course title is required.';
    } elseif (empty($description)) {
        $error = 'Course description is required.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO courses (title, description, created_by, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$title, $description, $userId]);
            $courseId = db()->lastInsertId();
            
            // Enroll the mentor in their own course
            $enrollStmt = db()->prepare('INSERT INTO enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())');
            $enrollStmt->execute([$userId, $courseId]);
            
            header('Location: manage-quests.php?course_id=' . $courseId . '&created=1');
            exit;
        } catch (Exception $e) {
            $error = 'Error creating course: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Course';
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
                    <li><a href="submissions.php">Submissions</a></li>
                    <li><a href="create-course.php" class="active">Create Course</a></li>
                    <li><a href="../courses.php">All Courses</a></li>
                </ul>
                <a href="../logout.php" class="btn-login">Logout</a>
            </div>
        </div>
    </nav>

    <section class="admin-section">
        <div class="container">
            <div class="admin-header">
                <h1>➕ Create New Course</h1>
                <p>Share your knowledge and create a new course for students.</p>
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

            <div class="form-container" style="max-width: 900px; margin: 0 auto;">
                <form method="POST" class="admin-form" style="background: rgba(255, 255, 255, 0.02); padding: 2rem; border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="form-section" style="margin-bottom: 2rem;">
                        <h3 style="display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; margin-bottom: 1.5rem; color: #fff;"><img src="../../images/Grad Cap.png" alt="Course" style="width: 24px; height: 24px; object-fit: contain;">Course Information</h3>
                        
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label for="title" style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #fff; font-size: 1rem;">Course Title *</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="form-control" 
                                required
                                placeholder="e.g., Advanced JavaScript Programming"
                                value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                style="width: 100%; padding: 1rem; font-size: 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; transition: all 0.3s ease;"
                                onfocus="this.style.borderColor='#667eea'; this.style.background='rgba(255, 255, 255, 0.08)'"
                                onblur="this.style.borderColor='rgba(255, 255, 255, 0.2)'; this.style.background='rgba(255, 255, 255, 0.05)'"
                            >
                            <small class="form-text" style="display: block; margin-top: 0.5rem; color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Choose a clear, descriptive title that tells students what they'll learn.</small>
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label for="description" style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #fff; font-size: 1rem;">Course Description *</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                class="form-control" 
                                rows="8" 
                                required
                                placeholder="Describe what students will learn in this course, the topics covered, and any prerequisites..."
                                style="width: 100%; padding: 1rem; font-size: 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; color: #fff; resize: vertical; min-height: 150px; transition: all 0.3s ease;"
                                onfocus="this.style.borderColor='#667eea'; this.style.background='rgba(255, 255, 255, 0.08)'"
                                onblur="this.style.borderColor='rgba(255, 255, 255, 0.2)'; this.style.background='rgba(255, 255, 255, 0.05)'"
                            ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="form-text" style="display: block; margin-top: 0.5rem; color: rgba(255, 255, 255, 0.6); font-size: 0.875rem;">Provide a detailed description that helps students understand the course content and learning outcomes.</small>
                        </div>
                    </div>

                    <div class="form-section" style="margin-bottom: 2rem;">
                        <h3 style="display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; margin-bottom: 1.5rem; color: #fff;">💡 Getting Started</h3>
                        <div class="info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);">
                            <h4 style="margin: 0 0 1rem 0; color: white; font-size: 1.25rem;">What happens after creating a course?</h4>
                            <ul style="margin: 0; padding-left: 1.5rem; line-height: 2;">
                                <li style="margin-bottom: 0.5rem;">You'll be redirected to add quests (assignments) for your course</li>
                                <li style="margin-bottom: 0.5rem;">You can set point values for each quest (rewards for students)</li>
                                <li style="margin-bottom: 0.5rem;">Students can enroll in your course and complete quests</li>
                                <li>You can edit your course and quests anytime</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-actions" style="display: flex; gap: 1rem; justify-content: center; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                        <button type="submit" name="create_course" class="btn btn-primary btn-large" style="padding: 1rem 3rem; font-size: 1.1rem; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0.5rem; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            ➕ Create Course
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-large" style="padding: 1rem 3rem; font-size: 1.1rem; background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.transform='translateY(0)'">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

</body>
</html>
