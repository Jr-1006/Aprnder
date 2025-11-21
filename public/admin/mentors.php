<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Security.php';
require_once __DIR__ . '/../../src/MentorPromotion.php';
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

// Check if mentor system tables exist
try {
    $checkTable = db()->query("SHOW TABLES LIKE 'mentor_criteria'");
    $tableExists = $checkTable->fetch();
    
    if (!$tableExists) {
        // Mentor system not set up yet
        $needsSetup = true;
    } else {
        $needsSetup = false;
        $mentorPromotion = new MentorPromotion(db());
    }
} catch (Exception $e) {
    $needsSetup = true;
}

$success = '';
$error = '';
$action = $_GET['action'] ?? 'overview';

// Only process if mentor system is set up
if (!$needsSetup) {
    // Handle Update Criteria
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_criteria'])) {
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
        } else {
            try {
                foreach ($_POST['criteria'] as $id => $value) {
                    $stmt = db()->prepare('UPDATE mentor_criteria SET threshold_value = ? WHERE id = ?');
                    $stmt->execute([$value, $id]);
                }
                $success = 'Criteria updated successfully!';
            } catch (Exception $e) {
                error_log('Criteria update error: ' . $e->getMessage());
                $error = 'Failed to update criteria';
            }
        }
    }

    // Handle Manual Promotion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_user'])) {
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token';
        } else {
            $promoteUserId = (int)$_POST['user_id'];
            $notes = $_POST['notes'] ?? '';
            
            if ($mentorPromotion->promoteToMentor($promoteUserId, $userId, $notes)) {
                $success = 'User promoted to mentor successfully!';
            } else {
                $error = 'Failed to promote user';
            }
        }
    }

    // Handle Auto-Promote Eligible
    if (isset($_GET['auto_promote']) && Security::validateCSRFToken($_GET['token'] ?? '')) {
        $promoted = $mentorPromotion->autoPromoteEligibleStudents();
        $success = count($promoted) . ' student(s) promoted to mentor!';
    }

    // Get mentor stats
    $stats = $mentorPromotion->getMentorStats();

    // Get criteria
    $criteriaStmt = db()->query('SELECT * FROM mentor_criteria ORDER BY id');
    $criteria = $criteriaStmt->fetchAll();
}

// Get recent promotions and eligible students only if setup is complete
if (!$needsSetup) {
    $promotionsStmt = db()->query('
        SELECT mp.*, u.email, p.full_name, a.email as promoted_by_email
        FROM mentor_promotions mp
        INNER JOIN users u ON u.id = mp.user_id
        LEFT JOIN user_profiles p ON p.user_id = u.id
        LEFT JOIN users a ON a.id = mp.promoted_by
        ORDER BY mp.promoted_at DESC
        LIMIT 20
    ');
    $promotions = $promotionsStmt->fetchAll();

    // Get eligible students
    $eligibleStmt = db()->query('
        SELECT u.id, u.email, p.full_name,
               COUNT(DISTINCT s.id) as total_submissions
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        LEFT JOIN submissions s ON s.user_id = u.id
        WHERE u.role IN ("student", "user")
        AND NOT EXISTS (
            SELECT 1 FROM mentor_promotions mp WHERE mp.user_id = u.id
        )
        GROUP BY u.id
        ORDER BY total_submissions DESC
        LIMIT 50
    ');
    $eligibleStudents = $eligibleStmt->fetchAll();

    // Check actual eligibility for each
    $checkedStudents = [];
    foreach ($eligibleStudents as $student) {
        $eligibility = $mentorPromotion->checkEligibility($student['id']);
        if ($eligibility['eligible']) {
            $student['eligibility'] = $eligibility;
            $checkedStudents[] = $student;
        }
    }
} else {
    $promotions = [];
    $eligibleStudents = [];
    $checkedStudents = [];
    $criteria = [];
    $stats = [];
}

$pageTitle = 'Mentor Management';
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
                    <li><a href="users.php">Users</a></li>
                    <li><a href="mentors.php" class="active">Mentors</a></li>
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

            <div class="admin-header">
                <h1>🎓 Mentor Management</h1>
                <?php if (!empty($checkedStudents)): ?>
                    <a href="?auto_promote=1&token=<?php echo Security::generateCSRFToken(); ?>" 
                       class="btn btn-primary"
                       onclick="return confirm('Auto-promote <?php echo count($checkedStudents); ?> eligible student(s)?')">
                        ⚡ Auto-Promote Eligible
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($needsSetup): ?>
                <!-- Setup Required Message -->
                <div class="admin-panel" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 2px solid #ef4444; padding: 2rem; margin: 2rem 0;">
                    <h2 style="color: white; margin-bottom: 1rem; font-size: 1.5rem;"><img src="../../images/Pending.png" alt="Warning" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Mentor System Not Set Up</h2>
                    <p style="color: white; margin-bottom: 1.5rem; font-size: 1.1rem;">
                        The mentor system database tables haven't been created yet. Please run the migration to enable this feature.
                    </p>
                    
                    <div style="background: rgba(0,0,0,0.2); border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                        <h3 style="color: white; margin-bottom: 1rem;">📋 Setup Instructions:</h3>
                        <ol style="color: white; line-height: 1.8; margin-left: 1.5rem;">
                            <li><strong>Option 1 - Command Line (Recommended):</strong>
                                <pre style="background: rgba(0,0,0,0.3); padding: 0.75rem; border-radius: 0.25rem; margin: 0.5rem 0; color: #fbbf24; font-size: 0.9rem; overflow-x: auto;">cd C:\xampp\htdocs\Websys
php run_migration.php</pre>
                            </li>
                            <li style="margin-top: 1rem;"><strong>Option 2 - phpMyAdmin:</strong>
                                <ul style="margin-left: 1rem; margin-top: 0.5rem;">
                                    <li>Open <a href="http://localhost/phpmyadmin" target="_blank" style="color: #fbbf24; text-decoration: underline;">phpMyAdmin</a></li>
                                    <li>Select database: <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.5rem; border-radius: 0.25rem;">pbl_gamified</code></li>
                                    <li>Go to "Import" tab</li>
                                    <li>Choose file: <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.5rem; border-radius: 0.25rem;">db/migrations/add_mentor_system.sql</code></li>
                                    <li>Click "Go"</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                    
                    <p style="color: white; font-size: 0.9rem; opacity: 0.9;">
                        📚 For more details, see <code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.5rem; border-radius: 0.25rem;">MENTOR_SETUP.md</code> in the root directory.
                    </p>
                </div>
            <?php else: ?>

            <!-- Stats Overview -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-content">
                        <h3>Total Mentors</h3>
                        <p class="stat-number"><?php echo $stats['total_mentors'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🤖</div>
                    <div class="stat-content">
                        <h3>Auto Promotions</h3>
                        <p class="stat-number"><?php echo $stats['auto_promotions'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-content">
                        <h3>Manual Promotions</h3>
                        <p class="stat-number"><?php echo $stats['manual_promotions'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><img src="../../images/Target.png" alt="Target" style="width: 40px; height: 40px; object-fit: contain;"></div>
                    <div class="stat-content">
                        <h3>Eligible Now</h3>
                        <p class="stat-number"><?php echo count($checkedStudents); ?></p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="admin-tabs" style="margin-bottom: 2rem;">
                <a href="?action=overview" class="admin-tab <?php echo $action === 'overview' ? 'active' : ''; ?>">Overview</a>
                <a href="?action=criteria" class="admin-tab <?php echo $action === 'criteria' ? 'active' : ''; ?>">Criteria Settings</a>
                <a href="?action=eligible" class="admin-tab <?php echo $action === 'eligible' ? 'active' : ''; ?>">Eligible Students</a>
                <a href="?action=history" class="admin-tab <?php echo $action === 'history' ? 'active' : ''; ?>">Promotion History</a>
            </div>

            <?php if ($action === 'overview'): ?>
                <!-- Overview -->
                <div class="admin-panel">
                    <h2>Recent Promotions</h2>
                    <?php if (!empty($promotions)): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
                                        <th>Promoted By</th>
                                        <th>Date</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($promotions as $promo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($promo['full_name'] ?? $promo['email']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $promo['promotion_type'] === 'auto' ? 'passed' : 'pending'; ?>">
                                                    <?php echo ucfirst($promo['promotion_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $promo['promoted_by'] ? htmlspecialchars($promo['promoted_by_email']) : 'System'; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($promo['promoted_at'])); ?></td>
                                            <td><?php echo htmlspecialchars(substr($promo['notes'] ?? '', 0, 50)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">🎓</div>
                            <h3>No promotions yet</h3>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($action === 'criteria'): ?>
                <!-- Criteria Settings -->
                <div class="admin-form-container">
                    <h2>Mentor Promotion Criteria</h2>
                    <p style="color: var(--color-text-muted); margin-bottom: 2rem;">
                        Students must meet ALL criteria below to be eligible for auto-promotion to mentor status.
                    </p>
                    <form method="post" class="admin-form">
                        <?php echo Security::csrfField(); ?>
                        <?php foreach ($criteria as $criterion): ?>
                            <div class="form-group">
                                <label for="criteria_<?php echo $criterion['id']; ?>" class="form-label">
                                    <?php echo htmlspecialchars($criterion['name']); ?>
                                    <?php if (!$criterion['is_active']): ?>
                                        <span class="status-badge status-failed">Inactive</span>
                                    <?php endif; ?>
                                </label>
                                <input 
                                    type="number" 
                                    id="criteria_<?php echo $criterion['id']; ?>"
                                    name="criteria[<?php echo $criterion['id']; ?>]" 
                                    class="form-input" 
                                    value="<?php echo $criterion['threshold_value']; ?>"
                                    min="0"
                                    <?php echo !$criterion['is_active'] ? 'disabled' : ''; ?>
                                >
                                <small class="form-help"><?php echo htmlspecialchars($criterion['description']); ?></small>
                            </div>
                        <?php endforeach; ?>
                        <div class="form-actions">
                            <button type="submit" name="update_criteria" class="btn btn-primary btn-large">
                                💾 Update Criteria
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif ($action === 'eligible'): ?>
                <!-- Eligible Students -->
                <div class="admin-panel">
                    <h2>Eligible Students (<?php echo count($checkedStudents); ?>)</h2>
                    <?php if (!empty($checkedStudents)): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Progress</th>
                                        <th>Submissions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($checkedStudents as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <span class="status-badge status-passed">
                                                    <?php echo $student['eligibility']['progress']; ?>% Complete
                                                </span>
                                            </td>
                                            <td><?php echo $student['total_submissions']; ?></td>
                                            <td class="action-buttons">
                                                <form method="post" style="display: inline;">
                                                    <?php echo Security::csrfField(); ?>
                                                    <input type="hidden" name="user_id" value="<?php echo $student['id']; ?>">
                                                    <input type="hidden" name="notes" value="Manually promoted - met all criteria">
                                                    <button type="submit" name="promote_user" class="btn-small btn-primary">
                                                        Promote
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><img src="../../images/Target.png" alt="Mentors" style="width: 64px; height: 64px; object-fit: contain;"></div>
                            <h3>No eligible students</h3>
                            <p>No students have met all the mentor criteria yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($action === 'history'): ?>
                <!-- Full History -->
                <div class="admin-panel">
                    <h2>All Promotions</h2>
                    <?php if (!empty($promotions)): ?>
                        <div class="admin-table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Promoted By</th>
                                        <th>Date</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($promotions as $promo): ?>
                                        <tr>
                                            <td><?php echo $promo['id']; ?></td>
                                            <td><?php echo htmlspecialchars($promo['full_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($promo['email']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $promo['promotion_type'] === 'auto' ? 'passed' : 'pending'; ?>">
                                                    <?php echo ucfirst($promo['promotion_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $promo['promoted_by'] ? htmlspecialchars($promo['promoted_by_email']) : 'System'; ?></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($promo['promoted_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($promo['notes'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📜</div>
                            <h3>No promotion history</h3>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php endif; // End of needsSetup check ?>
        </div>
    </section>

</body>
</html>
