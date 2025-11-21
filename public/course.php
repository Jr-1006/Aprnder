<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_login();

$userId = current_user_id();

// Get user data for navigation
$userStmt = db()->prepare('SELECT u.*, p.full_name, p.avatar_url FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
$role = $user['role'] ?? null;

$courseId = (int)($_GET['id'] ?? 0);

if ($courseId <= 0) {
    header('Location: courses.php');
    exit;
}

// Get course details
$courseStmt = db()->prepare('
    SELECT 
        c.id,
        c.title,
        c.description,
        c.created_at,
        u.email as creator_email,
        p.full_name as creator_name,
        CASE WHEN e.user_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
    FROM courses c
    LEFT JOIN users u ON u.id = c.created_by
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN enrollments e ON e.course_id = c.id AND e.user_id = ?
    WHERE c.id = ?
');
$courseStmt->execute([$userId, $courseId]);
$course = $courseStmt->fetch();

if (!$course) {
    header('Location: courses.php?error=course_not_found');
    exit;
}

// Get quests for this course with submission status
$questsStmt = db()->prepare('
    SELECT 
        q.id,
        q.title,
        q.description,
        q.difficulty,
        q.max_points,
        s.id as submission_id,
        s.status as submission_status,
        s.points_awarded
    FROM quests q
    LEFT JOIN submissions s ON s.quest_id = q.id AND s.user_id = ?
    WHERE q.course_id = ?
    ORDER BY q.id ASC
');
$questsStmt->execute([$userId, $courseId]);
$quests = $questsStmt->fetchAll();

// Calculate progress
$totalQuests = count($quests);
$completedQuests = 0;
$totalPoints = 0;

foreach ($quests as $quest) {
    if ($quest['submission_status'] === 'passed') {
        $completedQuests++;
        $totalPoints += (int)$quest['points_awarded'];
    }
}

$progress = $totalQuests > 0 ? round(($completedQuests / $totalQuests) * 100) : 0;

$pageTitle = $course['title'];
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

    <?php if (isset($_GET['enrolled'])): ?>
        <div class="container" style="margin-top: 1rem;">
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <div class="alert-content">Successfully enrolled in this course!</div>
            </div>
        </div>
    <?php endif; ?>

    <section class="course-detail-header">
        <div class="container">
            <a href="courses.php" class="back-link">← Back to Courses</a>
            
            <div class="course-detail-content">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="course-detail-description"><?php echo htmlspecialchars($course['description']); ?></p>
                
                <div class="course-detail-meta">
                    <span>By <?php echo htmlspecialchars($course['creator_name'] ?? $course['creator_email']); ?></span>
                    <span>•</span>
                    <span><?php echo $totalQuests; ?> Quests</span>
                </div>
                
                <?php if ($course['is_enrolled']): ?>
                    <div class="progress-section">
                        <div class="progress-header">
                            <span>Your Progress</span>
                            <span><?php echo $completedQuests; ?> / <?php echo $totalQuests; ?> completed</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="points-earned">
                            <span class="points-icon">⭐</span>
                            <span><?php echo $totalPoints; ?> points earned</span>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="post" action="enroll.php">
                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                        <button type="submit" class="btn btn-primary btn-large">Enroll in This Course</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="quests-list-section">
        <div class="container">
            <h2>Course Quests</h2>
            
            <?php if (!empty($quests)): ?>
                <div class="quests-list">
                    <?php foreach ($quests as $index => $quest): 
                        $statusClass = '';
                        $statusText = 'Not Started';
                        $statusIcon = '⚪';
                        
                        if ($quest['submission_status']) {
                            switch ($quest['submission_status']) {
                                case 'passed':
                                    $statusClass = 'status-passed';
                                    $statusText = 'Completed';
                                    $statusIcon = '✅';
                                    break;
                                case 'failed':
                                    $statusClass = 'status-failed';
                                    $statusText = 'Failed';
                                    $statusIcon = '❌';
                                    break;
                                case 'pending':
                                    $statusClass = 'status-pending';
                                    $statusText = 'Under Review';
                                    $statusIcon = '⏳';
                                    break;
                                case 'resubmitted':
                                    $statusClass = 'status-pending';
                                    $statusText = 'Resubmitted';
                                    $statusIcon = '🔄';
                                    break;
                            }
                        }
                        
                        $difficultyClass = 'difficulty-' . $quest['difficulty'];
                    ?>
                        <div class="quest-item <?php echo $statusClass; ?>">
                            <div class="quest-number"><?php echo $index + 1; ?></div>
                            
                            <div class="quest-content">
                                <h3><?php echo htmlspecialchars($quest['title']); ?></h3>
                                <p><?php echo htmlspecialchars($quest['description']); ?></p>
                                
                                <div class="quest-meta">
                                    <span class="quest-difficulty <?php echo $difficultyClass; ?>">
                                        <?php echo ucfirst($quest['difficulty']); ?>
                                    </span>
                                    <span class="quest-points">
                                        ⭐ <?php echo (int)$quest['max_points']; ?> points
                                    </span>
                                </div>
                            </div>
                            
                            <div class="quest-actions">
                                <div class="quest-status <?php echo $statusClass; ?>">
                                    <span class="status-icon"><?php echo $statusIcon; ?></span>
                                    <span><?php echo $statusText; ?></span>
                                </div>
                                
                                <?php if ($course['is_enrolled']): ?>
                                    <a href="quest.php?id=<?php echo (int)$quest['id']; ?>" class="btn btn-primary">
                                        <?php echo $quest['submission_status'] === 'passed' ? 'Review' : 'Start Quest'; ?>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Enroll to Access</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No quests available yet</h3>
                    <p>The instructor is preparing content for this course.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
              notifList.innerHTML = '<div class="empty-notifications"><div class="empty-icon">🔔</div><h3>No notifications yet</h3></div>';
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
