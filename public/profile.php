<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Security.php';
require_login();

$userId = current_user_id();
$success = '';
$error = '';

// Get user data
$userStmt = db()->prepare('
    SELECT u.id, u.email, u.role, u.created_at, u.last_login,
           p.full_name, p.avatar_url, p.bio
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE u.id = ?
');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get user stats
$statsStmt = db()->prepare('
    SELECT 
        COUNT(DISTINCT e.course_id) as enrolled_courses,
        COUNT(DISTINCT s.id) as total_submissions,
        COUNT(DISTINCT CASE WHEN s.status = "passed" THEN s.quest_id END) as completed_quests,
        SUM(CASE WHEN s.status = "passed" THEN s.points_awarded ELSE 0 END) as total_points,
        MAX(gs.score) as best_game_score,
        MAX(gs.wave_reached) as best_wave,
        COUNT(gs.id) as games_played
    FROM users u
    LEFT JOIN enrollments e ON e.user_id = u.id
    LEFT JOIN submissions s ON s.user_id = u.id
    LEFT JOIN game_scores gs ON gs.user_id = u.id
    WHERE u.id = ?
    GROUP BY u.id
');
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// Get user badges
$badgesStmt = db()->prepare('
    SELECT b.id, b.name, b.description, b.icon_url, ub.awarded_at
    FROM user_badges ub
    INNER JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ?
    ORDER BY ub.awarded_at DESC
');
$badgesStmt->execute([$userId]);
$badges = $badgesStmt->fetchAll();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $fullName = Security::sanitize($_POST['full_name'] ?? '');
        $bio = Security::sanitize($_POST['bio'] ?? '');
        
        if (empty($fullName)) {
            $error = 'Full name is required.';
        } else {
            try {
                // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both cases
                $updateStmt = db()->prepare('
                    INSERT INTO user_profiles (user_id, full_name, bio) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        full_name = VALUES(full_name),
                        bio = VALUES(bio)
                ');
                $updateStmt->execute([$userId, $fullName, $bio]);
                
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch();
            } catch (Exception $e) {
                error_log('Profile update error: ' . $e->getMessage());
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Get current password hash
        $passStmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $passStmt->execute([$userId]);
        $passData = $passStmt->fetch();
        
        if (!password_verify($currentPassword, $passData['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            $passwordErrors = Security::validatePassword($newPassword);
            if (!empty($passwordErrors)) {
                $error = implode('<br>', $passwordErrors);
            } else {
                try {
                    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updatePassStmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $updatePassStmt->execute([$newHash, $userId]);
                    
                    $success = 'Password changed successfully!';
                } catch (Exception $e) {
                    error_log('Password change error: ' . $e->getMessage());
                    $error = 'Failed to change password. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'My Profile';
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
    <?php
      // Navbar context for unread and role
      $role = $user['role'] ?? null;
      $unread = 0; 
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
                      <li><a href="mentor-progress.php" style="color: #10b981;">🎓 Become a Mentor</a></li>
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

    <section class="profile-section">
        <div class="container">
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <?php 
                    $initials = '';
                    $nameParts = explode(' ', $user['full_name'] ?? '');
                    if (count($nameParts) >= 2) {
                        $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                    } else {
                        $initials = strtoupper(substr($user['full_name'] ?? $user['email'], 0, 2));
                    }
                    echo $initials;
                    ?>
                </div>
                <div class="profile-header-info">
                    <h1><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="profile-meta">
                        <span>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        <?php if ($user['last_login']): ?>
                            <span>•</span>
                            <span>Last login: <?php echo date('M j, Y', strtotime($user['last_login'])); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><img src="../images/Pending.png" alt="Warning" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><img src="../images/Updated.png" alt="Success" style="width: 20px; height: 20px; object-fit: contain;"></span>
                    <div class="alert-content"><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <div class="profile-grid">
                <!-- Stats Section -->
                <div class="profile-stats-section">
                    <h2>Your Statistics</h2>
                    <div class="stats-grid-profile">
                        <div class="stat-card-profile">
                            <div class="stat-icon" style="display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><img src="../images/Grad Cap.png" alt="Courses" style="width: 32px; height: 32px; object-fit: contain;"></div>
                            <div class="stat-value"><?php echo (int)($stats['enrolled_courses'] ?? 0); ?></div>
                            <div class="stat-label">Enrolled Courses</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-icon" style="display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><img src="../images/Updated.png" alt="Completed" style="width: 32px; height: 32px; object-fit: contain;"></div>
                            <div class="stat-value"><?php echo (int)($stats['completed_quests'] ?? 0); ?></div>
                            <div class="stat-label">Completed Quests</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-icon" style="display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><img src="../images/Points.png" alt="Points" style="width: 32px; height: 32px; object-fit: contain;"></div>
                            <div class="stat-value"><?php echo number_format((int)($stats['total_points'] ?? 0)); ?></div>
                            <div class="stat-label">Total Points</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-icon" style="display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><img src="../images/Game.png" alt="Games" style="width: 32px; height: 32px; object-fit: contain;"></div>
                            <div class="stat-value"><?php echo (int)($stats['games_played'] ?? 0); ?></div>
                            <div class="stat-label">Games Played</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-icon" style="display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><img src="../images/Award.png" alt="Award" style="width: 32px; height: 32px; object-fit: contain;"></div>
                            <div class="stat-value"><?php echo number_format((int)($stats['best_game_score'] ?? 0)); ?></div>
                            <div class="stat-label">Best Score</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-icon" style="display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;"><img src="../images/Progress.png" alt="Wave" style="width: 32px; height: 32px; object-fit: contain;"></div>
                            <div class="stat-value"><?php echo (int)($stats['best_wave'] ?? 0); ?></div>
                            <div class="stat-label">Best Wave</div>
                        </div>
                    </div>
                </div>

                <!-- Badges Section -->
                <div class="profile-badges-section">
                    <h2>Achievements & Badges</h2>
                    <?php if (!empty($badges)): ?>
                        <div class="badges-grid">
                            <?php foreach ($badges as $badge): ?>
                                <div class="badge-card">
                                    <div class="badge-icon-large"><?php echo $badge['icon_url'] ?: '🏅'; ?></div>
                                    <h4><?php echo htmlspecialchars($badge['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($badge['description']); ?></p>
                                    <small>Earned <?php echo date('M j, Y', strtotime($badge['awarded_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <div class="empty-icon">🏅</div>
                            <p>No badges earned yet. Complete quests and play games to earn achievements!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Edit Profile Section -->
                <div class="profile-edit-section">
                    <h2>Edit Profile</h2>
                    <form method="post" class="profile-form">
                        <?php echo Security::csrfField(); ?>
                        
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea 
                                id="bio" 
                                name="bio" 
                                class="form-input" 
                                rows="4"
                                placeholder="Tell us about yourself..."
                            ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="profile-password-section">
                    <h2>Change Password</h2>
                    <form method="post" class="profile-form">
                        <?php echo Security::csrfField(); ?>
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-input" 
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="form-input" 
                                required
                            >
                            <small class="form-help">Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                required
                            >
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
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
        }catch(e){ 
          notifList.innerHTML = '<div class="empty-notifications">Error loading notifications</div>'; 
        }
      }
      
      if (notifBtn) notifBtn.addEventListener('click', (e)=>{ e.preventDefault(); notifPopup.style.display='flex'; loadNotifications(); });
      if (closeNotifBtn) closeNotifBtn.addEventListener('click', ()=> notifPopup.style.display='none');
      if (notifPopup) notifPopup.addEventListener('click', (e)=>{ if (e.target===notifPopup) notifPopup.style.display='none'; });
    </script>
</body>
</html>
