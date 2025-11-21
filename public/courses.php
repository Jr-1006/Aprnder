<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Allow guests to browse courses, but require login to enroll
$userId = is_logged_in() ? current_user_id() : null;

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Build query with search and filters
$query = '
    SELECT 
        c.id,
        c.title,
        c.description,
        c.created_at,
        u.email as creator_email,
        p.full_name as creator_name,
        COUNT(DISTINCT q.id) as quest_count,
        COUNT(DISTINCT e.user_id) as enrolled_count,
        CASE WHEN e2.user_id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
    FROM courses c
    LEFT JOIN users u ON u.id = c.created_by
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN quests q ON q.course_id = c.id
    LEFT JOIN enrollments e ON e.course_id = c.id
    LEFT JOIN enrollments e2 ON e2.course_id = c.id AND e2.user_id = ?
    WHERE 1=1
';

$params = [$userId];

// Add search condition
if (!empty($search)) {
    $query .= ' AND (c.title LIKE ? OR c.description LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= ' GROUP BY c.id, c.title, c.description, c.created_at, u.email, p.full_name, e2.user_id';

// Add filter condition (after GROUP BY)
if ($filter === 'enrolled') {
    $query .= ' HAVING is_enrolled = 1';
} elseif ($filter === 'available') {
    $query .= ' HAVING is_enrolled = 0';
}

// Add sorting
switch ($sort) {
    case 'oldest':
        $query .= ' ORDER BY c.created_at ASC';
        break;
    case 'popular':
        $query .= ' ORDER BY enrolled_count DESC, c.created_at DESC';
        break;
    case 'quests':
        $query .= ' ORDER BY quest_count DESC, c.created_at DESC';
        break;
    default: // newest
        $query .= ' ORDER BY c.created_at DESC';
}

$stmt = db()->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$pageTitle = 'Courses';
$uid = is_logged_in() ? current_user_id() : null;
$role = null; $unread = 0; $userProfile = null;
if ($uid) {
    $stmt = db()->prepare('SELECT u.role, p.full_name, p.avatar_url FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = ?');
    $stmt->execute([$uid]);
    $userProfile = $stmt->fetch();
    $role = $userProfile['role'] ?? null;
    $n = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $n->execute([$uid]);
    $unread = (int)$n->fetchColumn();
}
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
                    <li><a href="courses.php" class="active">Courses</a></li>
                    <li><a href="game.php">Game</a></li>
                    
                    <?php if (is_logged_in()): ?>
                        <li><a href="discord.php">Discord</a></li>
                        <li><a href="leaderboard.php">Leaderboard</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if (!is_logged_in()): ?>
                        <li><a href="about.php">About</a></li>
                        <li><a href="help.php">Help</a></li>
                    <?php endif; ?>
                    <?php if ($role === 'student' || $role === 'user'): ?>
                      <li><a href="mentor-progress.php" style="color: #10b981;">🎓 Become a Mentor</a></li>
                    <?php endif; ?>
                    <?php if ($role === 'mentor'): ?>
                      <li><a href="mentor/dashboard.php" style="color: #10b981;">👨‍🏫 Mentor Panel</a></li>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                      <li><a href="admin/dashboard.php" style="color: #fbbf24;">⚡ Admin</a></li>
                    <?php endif; ?>
                </ul>
                <div class="nav-actions">
                    <a href="#" id="notifBtn" class="notif-bell" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"/>
                          <path d="M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                        <?php if ($unread > 0): ?><span class="notif-badge" id="notifCount"><?php echo $unread; ?></span><?php endif; ?>
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="profile.php" class="profile-avatar-nav" aria-label="Profile">
                            <?php 
                            $initials = '';
                            $nameParts = explode(' ', $userProfile['full_name'] ?? 'User');
                            foreach ($nameParts as $part) {
                                if (!empty($part)) {
                                    $initials .= strtoupper($part[0]);
                                }
                            }
                            $initials = substr($initials, 0, 2);
                            
                            if (!empty($userProfile['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($userProfile['avatar_url']); ?>" alt="Profile">
                            <?php else: ?>
                                <div class="avatar-initials"><?php echo $initials; ?></div>
                            <?php endif; ?>
                        </a>
                        <a href="logout.php" class="btn-login">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-login">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-icon"><img src="../images/Grad Cap.png" alt="Courses" style="width: 64px; height: 64px; object-fit: contain;"></div>
                <h1>Learning Courses</h1>
                <p>Master programming concepts through structured learning paths</p>
            </div>
        </div>
    </section>

    <section class="courses-content">
        <div class="container">
            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <form method="get" class="search-form">
                    <div class="search-input-wrapper">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search courses..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                        <button type="submit" class="search-btn">🔍 Search</button>
                    </div>
                    
                    <div class="filter-controls">
                        <select name="filter" class="filter-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Courses</option>
                            <?php if (is_logged_in()): ?>
                            <option value="enrolled" <?php echo $filter === 'enrolled' ? 'selected' : ''; ?>>My Courses</option>
                            <option value="available" <?php echo $filter === 'available' ? 'selected' : ''; ?>>Available</option>
                            <?php endif; ?>
                        </select>
                        
                        <select name="sort" class="filter-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            <option value="quests" <?php echo $sort === 'quests' ? 'selected' : ''; ?>>Most Quests</option>
                        </select>
                        
                        <?php if (!empty($search) || $filter !== 'all' || $sort !== 'newest'): ?>
                            <a href="courses.php" class="btn-clear-filters">✕ Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (!empty($courses)): ?>
                <div class="results-info">
                    <p>Found <?php echo count($courses); ?> course<?php echo count($courses) !== 1 ? 's' : ''; ?></p>
                </div>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <?php if ($course['is_enrolled']): ?>
                                    <span class="badge badge-success">Enrolled</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                            
                            <div class="course-meta">
                                <div class="meta-item">
                                    <span class="meta-icon"><img src="../images/Task.png" alt="Quests" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle;"></span>
                                    <span><?php echo (int)$course['quest_count']; ?> Quests</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-icon"><img src="../images/Students.png" alt="Students" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle;"></span>
                                    <span><?php echo (int)$course['enrolled_count']; ?> Students</span>
                                </div>
                            </div>
                            
                            <div class="course-footer">
                                <small>By <?php echo htmlspecialchars($course['creator_name'] ?? $course['creator_email']); ?></small>
                                <?php if (!is_logged_in()): ?>
                                    <a href="login.php?redirect=courses.php" class="btn btn-primary">Login to Enroll</a>
                                <?php elseif ($course['is_enrolled']): ?>
                                    <a href="course.php?id=<?php echo (int)$course['id']; ?>" class="btn btn-primary">Continue Learning</a>
                                <?php else: ?>
                                    <form method="post" action="enroll.php" style="display: inline;">
                                        <input type="hidden" name="course_id" value="<?php echo (int)$course['id']; ?>">
                                        <button type="submit" class="btn btn-primary">Enroll Now</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><img src="../images/Grad Cap.png" alt="Courses" style="width: 64px; height: 64px; object-fit: contain;"></div>
                    <h3>No courses available yet</h3>
                    <p>Check back soon for new learning content!</p>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Notification Popup replicated from dashboard -->
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
      const notifBtn = document.getElementById('notifBtn');
      const notifPopup = document.getElementById('notificationPopup');
      const notifList = document.getElementById('notificationList');
      const closeNotifBtn = document.getElementById('closeNotifBtn');
      const notifCountEl = document.getElementById('notifCount');

      function formatTime(ts){
        const d = new Date(ts); const now = new Date();
        const diff = (now - d) / 1000; // seconds
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
          if (notifCountEl) {
            notifCountEl.textContent = data.unread_count;
            if (data.unread_count === 0) {
              notifCountEl.style.display = 'none';
            }
          }
          
          // Hide the notification from the list
          element.style.transition = 'opacity 0.3s ease-out, max-height 0.3s ease-out';
          element.style.opacity = '0';
          element.style.maxHeight = '0';
          element.style.overflow = 'hidden';
          element.style.marginBottom = '0';
          element.style.paddingTop = '0';
          element.style.paddingBottom = '0';
          
          setTimeout(() => {
            element.remove();
            
            // Check if there are any notifications left
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
          
          // Filter to show only unread notifications
          const unreadNotifs = data.items ? data.items.filter(n => !n.is_read) : [];
          
          if(unreadNotifs.length){
            notifList.innerHTML = unreadNotifs.map(n => (
              `<div class="notification-item unread" data-notif-id="${n.id}" style="cursor: pointer;">`
              + `<div class="notif-content">`
              + `<div class="notif-message">${n.message}</div>`
              + `<div class="notif-time">${formatTime(n.created_at)}</div>`
              + `</div></div>`
            )).join('');
            
            // Add click event listeners to each notification
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
