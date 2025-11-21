<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Get leaderboard data
$leaderboardData = [];
try {
    $stmt = db()->prepare('
        SELECT 
            u.id,
            p.full_name,
            u.email,
            u.role,
            MAX(gs.score) as best_score,
            MAX(gs.wave_reached) as best_wave,
            COUNT(gs.id) as games_played,
            MAX(gs.created_at) as last_game
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        LEFT JOIN game_scores gs ON gs.user_id = u.id
        WHERE u.role IN ("student", "user", "mentor")
        GROUP BY u.id, p.full_name, u.email, u.role
        HAVING best_score IS NOT NULL
        ORDER BY best_score DESC, best_wave DESC
        LIMIT 50
    ');
    $stmt->execute();
    $leaderboardData = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Leaderboard query error: ' . $e->getMessage());
}

// Handle PDF generation
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/../src/PDFService.php';
        
        $pdfService = new PDFService();
        $pdfService->generateLeaderboardPDF($leaderboardData);
        exit;
    } catch (Exception $e) {
        error_log('PDF generation error: ' . $e->getMessage());
        header('Location: leaderboard.php?error=pdf_failed');
        exit;
    }
}

$pageTitle = 'Leaderboard';
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
    <title>Leaderboard - Aprnder</title>
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
                    <?php if (is_logged_in()): ?>
                        <li><a href="courses.php">Courses</a></li>
                    <?php endif; ?>
                    <li><a href="game.php">Game</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="discord.php">Discord</a></li>
                        <li><a href="leaderboard.php" class="active">Leaderboard</a></li>
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
  
  <!-- Notification Popup (add missing HTML) -->
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

    <section class="leaderboard-header">
        <div class="container">
            <div class="header-content">
                <div class="header-icon">🏆</div>
                <h1>Leaderboard</h1>
            </div>
            <div class="header-actions">
                <a href="?export=pdf" class="action-btn">
                    <span>📄</span> Export as PDF
                </a>
                <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="action-btn">
                    <span>📊</span> Dashboard
                </a>
                <?php endif; ?>
                <a href="game.php" class="action-btn primary">
                    <span>🎮</span> Play Game
                </a>
            </div>
        </div>
    </section>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'pdf_failed'): ?>
        <div class="container" style="margin-top: 1rem;">
            <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--color-danger); padding: 1rem; border-radius: var(--radius-md); color: var(--color-danger);">
                Failed to generate PDF. Please try again later.
            </div>
        </div>
    <?php endif; ?>

    <section class="leaderboard-content">
        <div class="container">
            <div class="leaderboard-card">
                <div class="leaderboard-title">
                    <span class="trophy-icon">🏆</span>
                    <h2>Leaderboard</h2>
                </div>
                
                <?php if (!empty($leaderboardData)): ?>
                    <div class="leaderboard-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Player</th>
                                    <th>Score</th>
                                    <th>Waves</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaderboardData as $index => $player): 
                                    $rank = $index + 1;
                                    $rankClass = '';
                                    $badgeClass = '';
                                    if ($rank === 1) {
                                        $rankClass = 'rank-1';
                                        $badgeClass = 'gold';
                                    } elseif ($rank === 2) {
                                        $rankClass = 'rank-2';
                                        $badgeClass = 'silver';
                                    } elseif ($rank === 3) {
                                        $rankClass = 'rank-3';
                                        $badgeClass = 'bronze';
                                    }
                                    
                                    // Get initials for avatar
                                    $name = $player['full_name'] ?: $player['email'];
                                    $nameParts = explode(' ', $name);
                                    $initials = '';
                                    if (count($nameParts) >= 2) {
                                        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                                    } else {
                                        $initials = strtoupper(substr($name, 0, 2));
                                    }
                                ?>
                                    <tr class="<?php echo $rankClass; ?>">
                                        <td>
                                            <?php if ($badgeClass): ?>
                                                <span class="rank-badge <?php echo $badgeClass; ?>"><?php echo $rank; ?></span>
                                            <?php else: ?>
                                                <span><?php echo $rank; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="player-info">
                                                <div class="player-avatar"><?php echo $initials; ?></div>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <span><?php echo htmlspecialchars($name); ?></span>
                                                    <?php if ($player['role'] === 'mentor'): ?>
                                                        <span style="background: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">MENTOR</span>
                                                    <?php elseif ($player['role'] === 'student' || $player['role'] === 'user'): ?>
                                                        <span style="background: #6366f1; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">STUDENT</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo number_format($player['best_score']); ?></strong></td>
                                        <td><?php echo $player['best_wave']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($player['last_game'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <h3>No game data yet!</h3>
                        <p>Be the first to play and appear on the leaderboard!</p>
                        <a href="game.php" class="btn btn-primary">Start Playing</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

</body>
<script>
  const notifBtn = document.getElementById('notifBtn');
  const notifPopup = document.getElementById('notificationPopup');
  const notifList = document.getElementById('notificationList');
  const closeNotifBtn = document.getElementById('closeNotifBtn');
  const notifCountEl = document.getElementById('notifCount');

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
</html>
