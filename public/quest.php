<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/BadgeService.php';
require_login();

$userId = current_user_id();

// Get user data for navigation
$userStmt = db()->prepare('SELECT u.*, p.full_name, p.avatar_url FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
$role = $user['role'] ?? null;

$questId = (int)($_GET['id'] ?? 0);

if ($questId <= 0) {
    header('Location: courses.php');
    exit;
}

// Get quest details with course info
$questStmt = db()->prepare('
    SELECT 
        q.id,
        q.title,
        q.description,
        q.difficulty,
        q.max_points,
        q.course_id,
        c.title as course_title
    FROM quests q
    INNER JOIN courses c ON c.id = q.course_id
    WHERE q.id = ?
');
$questStmt->execute([$questId]);
$quest = $questStmt->fetch();

if (!$quest) {
    header('Location: courses.php?error=quest_not_found');
    exit;
}

// Check if user is enrolled in the course
$enrolledStmt = db()->prepare('SELECT user_id FROM enrollments WHERE user_id = ? AND course_id = ?');
$enrolledStmt->execute([$userId, $quest['course_id']]);
if (!$enrolledStmt->fetch()) {
    header('Location: course.php?id=' . $quest['course_id'] . '&error=not_enrolled');
    exit;
}

// Get user's previous submissions for this quest
$submissionsStmt = db()->prepare('
    SELECT id, code, status, points_awarded, feedback, submitted_at
    FROM submissions
    WHERE quest_id = ? AND user_id = ?
    ORDER BY submitted_at DESC
');
$submissionsStmt->execute([$questId, $userId]);
$submissions = $submissionsStmt->fetchAll();

$latestSubmission = !empty($submissions) ? $submissions[0] : null;

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = trim($_POST['code']);
    
    if (empty($code)) {
        $error = 'Please write some code before submitting.';
    } else {
        try {
            $insertStmt = db()->prepare('
                INSERT INTO submissions (quest_id, user_id, code, status, submitted_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $insertStmt->execute([$questId, $userId, $code, 'pending']);
            
            // Add notification
            $notifStmt = db()->prepare('
                INSERT INTO notifications (user_id, message, type, created_at)
                VALUES (?, ?, ?, NOW())
            ');
            $notifStmt->execute([$userId, 'Your code submission for "' . $quest['title'] . '" is under review.', 'info']);
            
            // Check and award badges
            $badgeService = new BadgeService(db());
            $badgeService->checkAndAwardBadges($userId);
            
            $success = 'Code submitted successfully! Your submission is now under review.';
            
            // Refresh submissions
            $submissionsStmt->execute([$questId, $userId]);
            $submissions = $submissionsStmt->fetchAll();
            $latestSubmission = $submissions[0];
            
        } catch (Exception $e) {
            error_log('Submission error: ' . $e->getMessage());
            $error = 'Failed to submit code. Please try again.';
        }
    }
}

$pageTitle = $quest['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .code-editor {
            width: 100%;
            min-height: 300px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 16px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            background: var(--color-surface);
            color: var(--color-text);
            resize: vertical;
        }
        
        .submission-history {
            margin-top: 2rem;
        }
        
        .submission-item {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .submission-code {
            background: var(--color-background);
            padding: 1rem;
            border-radius: var(--radius-sm);
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .feedback-box {
            background: rgba(99, 102, 241, 0.1);
            border-left: 3px solid var(--color-primary);
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: var(--radius-sm);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <a href="index.php" class="logo">
                    <img src="../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto;">
                </a>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="game.php">Game</a></li>
                    <li><a href="discord.php">Discord</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if ($role === 'student' || $role === 'user'): ?>
                      <li><a href="mentor-progress.php" style="color: #10b981;">🎓 Become a Mentor</a></li>
                    <?php endif; ?>
                </ul>
                <div class="nav-actions">
                    <a href="#" id="notifBtn" class="notif-bell" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"/>
                          <path d="M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                        <?php 
                          $n = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
                          $n->execute([$userId]);
                          $unr = (int)$n->fetchColumn();
                          if ($unr > 0): ?>
                            <span class="notif-badge"><?php echo $unr; ?></span>
                          <?php endif; ?>
                    </a>
                    <a href="profile.php" class="profile-avatar-nav" aria-label="Profile">
                        <?php 
                        $initials = '';
                        $nameParts = explode(' ', $user['full_name'] ?? 'User');
                        foreach ($nameParts as $part) {
                            if (!empty($part)) {
                                $initials .= strtoupper($part[0]);
                            }
                        }
                        $initials = substr($initials, 0, 2);
                        
                        if (!empty($user['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile">
                        <?php else: ?>
                            <div class="avatar-initials"><?php echo $initials; ?></div>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn-login">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="quest-detail-section">
        <div class="container">
            <a href="course.php?id=<?php echo (int)$quest['course_id']; ?>" class="back-link">← Back to <?php echo htmlspecialchars($quest['course_title']); ?></a>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><img src="../images/Pending.png" alt="Warning" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><img src="../images/Updated.png" alt="Success" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="quest-detail-card">
                <div class="quest-detail-header">
                    <h1><?php echo htmlspecialchars($quest['title']); ?></h1>
                    <div class="quest-badges">
                        <span class="badge difficulty-<?php echo $quest['difficulty']; ?>">
                            <?php echo ucfirst($quest['difficulty']); ?>
                        </span>
                        <span class="badge badge-points">
                            <img src="../images/Points.png" alt="Points" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px;"> <?php echo (int)$quest['max_points']; ?> points
                        </span>
                    </div>
                </div>
                
                <div class="quest-description">
                    <h3>Challenge Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($quest['description'])); ?></p>
                </div>
                
                <?php if ($latestSubmission && $latestSubmission['status'] === 'passed'): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">🎉</span>
                        <div class="alert-content">
                            <strong>Quest Completed!</strong><br>
                            You earned <?php echo (int)$latestSubmission['points_awarded']; ?> points!
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="code-submission-form">
                    <h3>Your Solution</h3>
                    <form method="post">
                        <textarea 
                            name="code" 
                            class="code-editor" 
                            placeholder="// Write your code here...&#10;&#10;function solution() {&#10;    // Your implementation&#10;}"
                            required
                        ><?php echo htmlspecialchars($latestSubmission['code'] ?? ''); ?></textarea>
                        
                        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary btn-large">
                                <?php echo $latestSubmission ? 'Resubmit Code' : 'Submit Code'; ?>
                            </button>
                            <button type="reset" class="btn btn-secondary">Clear</button>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($submissions)): ?>
                    <div class="alert alert-info" style="background: #e0f2fe; border-color: #0284c7; color: #0c4a6e; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <span class="alert-icon">ℹ️</span>
                        <div class="alert-content">
                            <strong>No Submissions Yet</strong><br>
                            This is your first attempt at this quest. Write your code solution above and submit it for review!
                        </div>
                    </div>
                <?php else: ?>
                    <div class="submission-history">
                        <h3>Submission History</h3>
                        <?php foreach ($submissions as $submission): 
                            $statusClass = 'status-' . $submission['status'];
                            $statusText = ucfirst($submission['status']);
                            $statusIcon = '⏳';
                            
                            switch ($submission['status']) {
                                case 'passed':
                                    $statusIcon = '<img src="../images/Updated.png" alt="Passed" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle;">';
                                    break;
                                case 'failed':
                                    $statusIcon = '<img src="../images/Pending.png" alt="Failed" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle;">';
                                    break;
                                case 'pending':
                                    $statusIcon = '⏳';
                                    break;
                            }
                        ?>
                            <div class="submission-item">
                                <div class="submission-header">
                                    <div>
                                        <span class="<?php echo $statusClass; ?>">
                                            <?php echo $statusIcon; ?> <?php echo $statusText; ?>
                                        </span>
                                        <?php if ($submission['points_awarded']): ?>
                                            <span class="badge badge-points">
                                                +<?php echo (int)$submission['points_awarded']; ?> points
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <small><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></small>
                                </div>
                                
                                <details>
                                    <summary style="cursor: pointer; margin-bottom: 0.5rem;">View Code</summary>
                                    <div class="submission-code"><?php echo htmlspecialchars($submission['code']); ?></div>
                                </details>
                                
                                <?php if ($submission['feedback']): ?>
                                    <div class="feedback-box">
                                        <strong>Instructor Feedback:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto; margin-bottom: 1rem;">
                    <p>Learn coding through gaming.</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Product</h4>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="courses.php">Courses</a></li>
                            <li><a href="game.php">Game</a></li>
                            <li><a href="leaderboard.php">Leaderboard</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Contact Us</h4>
                        <ul>
                            <li><strong>Email:</strong> mjraquino2@tip.edu.ph</li>
                            <li><strong>Address:</strong><br>363 Casal St, Quiapo, Manila,<br>1001 Metro Manila</li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="terms.php">Terms of Service</a></li>
                            <li><a href="privacy.php">Privacy Policy</a></li>
                            <li><strong>DPO:</strong> Cheska Eunice Diaz</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Aprnder. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Notification Popup -->
    <div id="notificationPopup" class="notification-popup" style="display: none;">
      <div class="notification-popup-content">
        <div class="notification-popup-header">
          <h2>Notifications</h2>
          <div class="header-actions">
            <button id="closeNotifBtn" class="icon-btn" title="Close">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>
        <div id="notificationList" class="notification-list">
          <div class="loading-notifications">
            <div class="loading-spinner"></div>
            <p>Loading notifications...</p>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Notification system
      const notifBtn = document.getElementById('notifBtn');
      const notifPopup = document.getElementById('notificationPopup');
      const notifList = document.getElementById('notificationList');
      const closeNotifBtn = document.getElementById('closeNotifBtn');

      function formatTime(ts){
        const d = new Date(ts); const now = new Date();
        const diff = (now - d) / 1000;
        if (diff < 60) return 'Just now';
        if (diff < 3600) { const m = Math.floor(diff/60); return `${m} minute${m>1?'s':''} ago`; }
        if (diff < 86400) { const h = Math.floor(diff/3600); return `${h} hour${h>1?'s':''} ago`; }
        if (diff < 604800) { const days = Math.floor(diff/86400); return `${days} day${days>1?'s':''} ago`; }
        return d.toLocaleDateString();
      }

      async function markNotificationRead(notifId, element) {
        try {
          const res = await fetch('api/notifications.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notifId })
          });
          
          if (!res.ok) return;
          
          const data = await res.json();
          
          // Update unread count badge
          const badge = document.querySelector('.notif-badge');
          if (badge) {
            if (data.unread_count === 0) {
              badge.remove();
            } else {
              badge.textContent = data.unread_count;
            }
          }
          
          // Hide the notification
          element.style.transition = 'opacity 0.3s ease-out, max-height 0.3s ease-out';
          element.style.opacity = '0';
          element.style.maxHeight = '0';
          element.style.overflow = 'hidden';
          element.style.marginBottom = '0';
          element.style.paddingTop = '0';
          element.style.paddingBottom = '0';
          
          setTimeout(() => {
            element.remove();
            const remainingNotifs = notifList.querySelectorAll('.notification-item');
            if (remainingNotifs.length === 0) {
              notifList.innerHTML = '<div class="empty-notifications"><div class="empty-icon"><img src="../images/Alarm.png" alt="Notifications" style="width: 64px; height: 64px; object-fit: contain;"></div><h3>No notifications yet</h3></div>';
            }
          }, 300);
          
        } catch (e) {
          console.error('Error marking notification as read:', e);
        }
      }

      async function loadNotifications(){
        try{
          const res = await fetch('api/notifications.php?count=20');
          if(!res.ok) return; 
          const data = await res.json();
          
          const unreadNotifs = data.items ? data.items.filter(n => !n.is_read) : [];
          
          if(unreadNotifs.length){
            notifList.innerHTML = unreadNotifs.map(n => (
              `<div class="notification-item unread" data-notif-id="${n.id}" style="cursor: pointer;">`
              + `<div class="notif-content">`
              + `<div class="notif-message">${n.message}</div>`
              + `<div class="notif-time">${formatTime(n.created_at)}</div>`
              + `</div></div>`
            )).join('');
            
            notifList.querySelectorAll('.notification-item').forEach(item => {
              item.addEventListener('click', function() {
                const notifId = this.getAttribute('data-notif-id');
                markNotificationRead(notifId, this);
              });
            });
          } else {
            notifList.innerHTML = '<div class="empty-notifications"><div class="empty-icon">🔔</div><h3>No notifications yet</h3></div>';
          }
        }catch(e){ notifList.innerHTML = '<div class="empty-notifications">Error loading notifications</div>'; }
      }
      
      if (notifBtn) notifBtn.addEventListener('click', (e)=>{ e.preventDefault(); notifPopup.style.display='flex'; loadNotifications(); });
      if (closeNotifBtn) closeNotifBtn.addEventListener('click', ()=> notifPopup.style.display='none');
      if (notifPopup) notifPopup.addEventListener('click', (e)=>{ if (e.target===notifPopup) notifPopup.style.display='none'; });
    </script>
</body>
</html>
