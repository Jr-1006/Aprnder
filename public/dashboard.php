<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_login();

// Load current user and some sample data
$uid = current_user_id();
$userStmt = db()->prepare('SELECT u.id, u.email, u.role, p.full_name, p.avatar_url FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = ?');
$userStmt->execute([$uid]);
$me = $userStmt->fetch();

// Redirect ONLY admins to admin dashboard (mentors stay here)
if ($me['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

// Get unread notifications count
$notifStmt = db()->prepare('SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0');
$notifStmt->execute([$uid]);
$notif = $notifStmt->fetch();
$unread = (int)($notif['unread'] ?? 0);

// Get user's game stats
$gameStatsStmt = db()->prepare('
    SELECT 
        MAX(score) as best_score,
        MAX(wave_reached) as best_wave,
        COUNT(*) as games_played
    FROM game_scores 
    WHERE user_id = ?
');
$gameStatsStmt->execute([$uid]);
$gameStats = $gameStatsStmt->fetch();

// Get recent leaderboard
$leaderboardStmt = db()->query('
    SELECT 
        u.email, 
        p.full_name, 
        gs.score, 
        gs.wave_reached, 
        gs.created_at
    FROM game_scores gs
    JOIN users u ON u.id = gs.user_id
    LEFT JOIN user_profiles p ON p.user_id = u.id
    ORDER BY gs.score DESC
    LIMIT 5
');
$leaderboard = $leaderboardStmt->fetchAll();

// Quests listing (first course only for demo)
$quests = db()->query('SELECT q.id, q.title, q.difficulty, q.max_points, c.title AS course_title
  FROM quests q INNER JOIN courses c ON c.id = q.course_id ORDER BY q.id DESC LIMIT 20')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - <?php echo APP_NAME; ?></title>
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
          <li><a href="index.php" class="active">Home</a></li>
          <li><a href="courses.php">Courses</a></li>
          <li><a href="game.php">Game</a></li>
          <li><a href="discord.php">Discord</a></li>
          <li><a href="leaderboard.php">Leaderboard</a></li>
          <li><a href="contact.php">Contact</a></li>
          <?php if ($me['role'] === 'student' || $me['role'] === 'user'): ?>
            <li><a href="mentor-progress.php" style="color: #10b981;">🎓 Become a Mentor</a></li>
          <?php endif; ?>
          <?php if ($me['role'] === 'mentor'): ?>
            <li><a href="mentor/dashboard.php" style="color: #10b981;">👨‍🏫 Mentor Panel</a></li>
          <?php endif; ?>
          <?php if ($me['role'] === 'admin'): ?>
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
          <a href="profile.php" class="profile-avatar-nav" aria-label="Profile">
            <?php 
            $initials = '';
            $nameParts = explode(' ', $me['full_name'] ?? 'User');
            foreach ($nameParts as $part) {
                if (!empty($part)) {
                    $initials .= strtoupper($part[0]);
                }
            }
            $initials = substr($initials, 0, 2);
            
            if (!empty($me['avatar_url'])): ?>
                <img src="<?php echo htmlspecialchars($me['avatar_url']); ?>" alt="Profile">
            <?php else: ?>
                <div class="avatar-initials"><?php echo $initials; ?></div>
            <?php endif; ?>
          </a>
          <a href="logout.php" class="btn-login">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <main class="container" style="padding-top: 2rem;">
    <h1>Welcome, <?php echo htmlspecialchars($me['full_name'] ?? $me['email']); ?></h1>
    
    <!-- Game Stats Section -->
    <section class="dashboard-stats">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><img src="../images/Game.png" alt="Games" style="width: 40px; height: 40px; object-fit: contain;"></div>
          <div class="stat-content">
            <h3>Games Played</h3>
            <p class="stat-number"><?php echo (int)($gameStats['games_played'] ?? 0); ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><img src="../images/Award.png" alt="Award" style="width: 40px; height: 40px; object-fit: contain;"></div>
          <div class="stat-content">
            <h3>Best Score</h3>
            <p class="stat-number"><?php echo number_format((int)($gameStats['best_score'] ?? 0)); ?></p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🌊</div>
          <div class="stat-content">
            <h3>Best Wave</h3>
            <p class="stat-number"><?php echo (int)($gameStats['best_wave'] ?? 0); ?></p>
          </div>
        </div>
      </div>
      
      <div class="dashboard-actions">
        <a href="game.php" class="btn btn-primary btn-large">Play Tower Defense</a>
        <a href="index.php" class="btn btn-secondary">Back to Home</a>
      </div>
    </section>

    <!-- Leaderboard Section -->
    <section class="leaderboard-section">
      <h2>Top Players</h2>
      <div class="leaderboard">
        <?php if (!empty($leaderboard)): ?>
          <?php foreach ($leaderboard as $index => $player): ?>
            <div class="leaderboard-item">
              <div class="rank"><?php echo $index + 1; ?></div>
              <div class="player-info">
                <div class="player-name"><?php echo htmlspecialchars($player['full_name'] ?? $player['email']); ?></div>
                <div class="player-stats">Wave <?php echo (int)$player['wave_reached']; ?></div>
              </div>
              <div class="score"><?php echo number_format((int)$player['score']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-data">No scores yet. Be the first to play!</div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Learning Quests Section -->
    <section class="quests-section">
      <h2>Learning Quests</h2>
      <div class="grid">
        <?php foreach ($quests as $q): ?>
          <article class="card">
            <h3><?php echo htmlspecialchars($q['title']); ?></h3>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($q['course_title']); ?></p>
            <p><strong>Difficulty:</strong> <?php echo htmlspecialchars($q['difficulty']); ?> · <strong>Points:</strong> <?php echo (int)$q['max_points']; ?></p>
            <a href="quest.php?id=<?php echo (int)$q['id']; ?>" class="btn btn-primary">Start Quest</a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- Notification Popup -->
  <div id="notificationPopup" class="notification-popup" style="display: none;">
    <div class="notification-popup-content">
      <div class="notification-popup-header">
        <h2>Notifications</h2>
        <div class="header-actions">
          <button id="closeNotifBtn" class="icon-btn" title="Close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
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
    // Notification popup functionality
    const notifBtn = document.getElementById('notifBtn');
    const notifPopup = document.getElementById('notificationPopup');
    const notifList = document.getElementById('notificationList');
    const closeNotifBtn = document.getElementById('closeNotifBtn');
    const notifCountEl = document.getElementById('notifCount');
    
    // Mark notification as read
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
    
    // Fetch notifications
    async function loadNotifications() {
      try {
        const response = await fetch('api/notifications.php?count=20');
        if (!response.ok) return;
        const data = await response.json();
        
        // Filter to show only unread notifications
        const unreadNotifs = data.items ? data.items.filter(n => !n.is_read) : [];
        
        if (unreadNotifs.length > 0) {
          notifList.innerHTML = unreadNotifs.map(n => {
            const date = new Date(n.created_at);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            let timeStr;
            if (diffMins < 1) {
              timeStr = 'Just now';
            } else if (diffMins < 60) {
              timeStr = `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
            } else if (diffHours < 24) {
              timeStr = `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            } else if (diffDays < 7) {
              timeStr = `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            } else {
              timeStr = date.toLocaleDateString();
            }
            
            return `
              <div class="notification-item unread" data-notif-id="${n.id}" style="cursor: pointer;">
                <div class="notif-content">
                  <div class="notif-message">${n.message}</div>
                  <div class="notif-time">${timeStr}</div>
                </div>
              </div>
            `;
          }).join('');
          
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
      } catch (e) {
        console.error('Error loading notifications:', e);
        notifList.innerHTML = '<div class="empty-notifications">Error loading notifications</div>';
      }
    }
    
    // Open popup
    if (notifBtn) {
      notifBtn.addEventListener('click', (e) => {
        e.preventDefault();
        notifPopup.style.display = 'flex';
        loadNotifications();
      });
    }
    
    // Close popup
    if (closeNotifBtn) {
      closeNotifBtn.addEventListener('click', () => {
        notifPopup.style.display = 'none';
      });
    }
    
    // Close on outside click
    notifPopup.addEventListener('click', (e) => {
      if (e.target === notifPopup) {
        notifPopup.style.display = 'none';
      }
    });
  </script>
  <script>window.__USER_ID__ = <?php echo (int)$uid; ?>;</script>
</body>
</html>


