<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/MentorPromotion.php';
require_login();

$userId = current_user_id();
$mentorPromotion = new MentorPromotion(db());

// Get user info
$userStmt = db()->prepare('SELECT role, email FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get profile info
$profileStmt = db()->prepare('SELECT full_name, avatar_url FROM user_profiles WHERE user_id = ?');
$profileStmt->execute([$userId]);
$profile = $profileStmt->fetch();

// Check eligibility
$eligibility = $mentorPromotion->checkEligibility($userId);

$pageTitle = 'Mentor Progress';
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
    <style>
        .mentor-hero {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 1rem;
            text-align: center;
        }
        
        .mentor-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .mentor-hero p {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        .progress-overview {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .progress-circle {
            width: 150px;
            height: 150px;
            margin: 0 auto 1rem;
            position: relative;
        }
        
        .progress-circle svg {
            transform: rotate(-90deg);
        }
        
        .progress-circle-bg {
            fill: none;
            stroke: var(--color-border);
            stroke-width: 10;
        }
        
        .progress-circle-fill {
            fill: none;
            stroke: url(#gradient);
            stroke-width: 10;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .criterion-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .criterion-card.met {
            border-color: var(--color-accent);
            background: rgba(16, 185, 129, 0.05);
        }
        
        .criterion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .criterion-icon {
            font-size: 2rem;
        }
        
        .criterion-status {
            font-size: 1.5rem;
        }
        
        .criterion-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .criterion-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: var(--color-text-muted);
            font-size: 0.875rem;
        }
        
        .criterion-bar {
            height: 8px;
            background: var(--color-background);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .criterion-bar-fill {
            height: 100%;
            background: var(--gradient-primary);
            transition: width 0.5s ease;
        }
        
        .criterion-bar-fill.complete {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .eligible-banner {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .eligible-banner h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .not-eligible-message {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
      $unread = 0; $role = $user['role'] ?? null;
      $n = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
      $n->execute([$userId]);
      $unread = (int)$n->fetchColumn();
    ?>
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
                      <li><a href="mentor-progress.php" class="active" style="color: #10b981;">🎓 Become a Mentor</a></li>
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
                        $nameParts = explode(' ', $profile['full_name'] ?? 'User');
                        foreach ($nameParts as $part) {
                            if (!empty($part)) {
                                $initials .= strtoupper($part[0]);
                            }
                        }
                        $initials = substr($initials, 0, 2);
                        
                        if (!empty($profile['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($profile['avatar_url']); ?>" alt="Profile">
                        <?php else: ?>
                            <div class="avatar-initials"><?php echo $initials; ?></div>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn-login">Logout</a>
                </div>
            </div>
        </div>
    </nav>

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

    <div class="container" style="padding: 2rem 0;">
        <div class="mentor-hero">
            <h1>🎓 Become a Mentor</h1>
            <p>Help your peers and earn mentor status through achievements!</p>
        </div>

        <?php if ($user['role'] === 'mentor'): ?>
            <div class="eligible-banner">
                <h2>🎉 You're Already a Mentor!</h2>
                <p style="margin-bottom: 1rem;">Congratulations on achieving mentor status. You can now create courses and help other students on their learning journey.</p>
                <a href="courses.php" class="btn btn-large" style="background: white; color: #10b981;">View Courses →</a>
            </div>
        <?php elseif ($eligibility['eligible']): ?>
            <div class="eligible-banner">
                <h2>🎉 You're Eligible to Become a Mentor!</h2>
                <p style="margin-bottom: 1rem;">Congratulations! You've met all the requirements. An admin will review your progress and promote you soon.</p>
                <p style="font-size: 0.875rem; opacity: 0.9;">The promotion process is automatic and usually happens within 24 hours.</p>
            </div>
        <?php endif; ?>

        <div class="progress-overview">
            <h2 style="text-align: center; margin-bottom: 2rem;">Overall Progress</h2>
            <div class="progress-circle">
                <svg width="150" height="150">
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <circle class="progress-circle-bg" cx="75" cy="75" r="65"></circle>
                    <circle 
                        class="progress-circle-fill" 
                        cx="75" 
                        cy="75" 
                        r="65"
                        stroke-dasharray="<?php echo 2 * 3.14159 * 65; ?>"
                        stroke-dashoffset="<?php echo 2 * 3.14159 * 65 * (1 - $eligibility['progress'] / 100); ?>"
                    ></circle>
                </svg>
                <div class="progress-text"><?php echo $eligibility['progress']; ?>%</div>
            </div>
            <p style="text-align: center; color: var(--color-text-muted);">
                <?php echo count(array_filter($eligibility['criteria'], fn($c) => $c['met'])); ?> of <?php echo count($eligibility['criteria']); ?> criteria met
            </p>
        </div>

        <h2 style="margin-bottom: 1rem;">Requirements</h2>
        <div class="criteria-grid">
            <?php foreach ($eligibility['criteria'] as $key => $criterion): ?>
                <?php 
                    $percentage = $criterion['threshold'] > 0 
                        ? min(100, ($criterion['current'] / $criterion['threshold']) * 100) 
                        : 0;
                    $icons = [
                        'min_completed_quests' => '📝',
                        'min_game_score' => '🎮',
                        'min_courses_completed' => '📚',
                        'min_badges_earned' => '🏅',
                        'min_perfect_submissions' => '⭐'
                    ];
                ?>
                <div class="criterion-card <?php echo $criterion['met'] ? 'met' : ''; ?>">
                    <div class="criterion-header">
                        <div class="criterion-icon"><?php echo $icons[$key] ?? '✨'; ?></div>
                        <div class="criterion-status"><?php echo $criterion['met'] ? '✅' : '⏳'; ?></div>
                    </div>
                    <div class="criterion-name"><?php echo htmlspecialchars($criterion['name']); ?></div>
                    <div class="criterion-progress">
                        <span><?php echo $criterion['current']; ?> / <?php echo $criterion['threshold']; ?></span>
                        <span><?php echo round($percentage); ?>%</span>
                    </div>
                    <div class="criterion-bar">
                        <div class="criterion-bar-fill <?php echo $criterion['met'] ? 'complete' : ''; ?>" 
                             style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$eligibility['eligible'] && $user['role'] !== 'mentor'): ?>
            <div class="not-eligible-message">
                <h3>Keep Learning!</h3>
                <p style="color: var(--color-text-muted); margin-top: 0.5rem;">
                    Complete the requirements above to become a mentor and help other students on their learning journey.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
      const notifBtn = document.getElementById('notifBtn');
      const notifPopup = document.getElementById('notificationPopup');
      const notifList = document.getElementById('notificationList');
      const closeNotifBtn = document.getElementById('closeNotifBtn');
      const notifCountEl = document.getElementById('notifCount');
      
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
            notifList.innerHTML = unreadNotifs.map(n=>`<div class="notification-item unread" data-notif-id="${n.id}" style="cursor: pointer;"><div class="notif-content"><div class="notif-message">${n.message}</div><div class="notif-time">${new Date(n.created_at).toLocaleString()}</div></div></div>`).join('');
            
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
