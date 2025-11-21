<?php
require_once __DIR__ . '/../src/bootstrap.php';
$pageTitle = 'About Us';
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
    <style>
        .legal-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            overflow-y: auto;
        }
        .legal-modal-content {
            background: white;
            margin: 5% auto;
            max-width: 800px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideDown 0.3s ease-out;
        }
        @keyframes modalSlideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .legal-modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px 12px 0 0;
        }
        .legal-modal-header h2 {
            margin: 0;
            color: white;
            font-size: 1.5rem;
        }
        .legal-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .legal-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .legal-modal-body {
            padding: 2rem;
            max-height: 70vh;
            overflow-y: auto;
            line-height: 1.6;
        }
        .legal-modal-body h3 {
            color: #667eea;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .legal-modal-body p {
            margin-bottom: 1rem;
            color: #374151;
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
                    <?php if (is_logged_in()): ?>
                        <li><a href="courses.php">Courses</a></li>
                    <?php endif; ?>
                    <li><a href="game.php">Game</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="discord.php">Discord</a></li>
                        <li><a href="leaderboard.php">Leaderboard</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if (!is_logged_in()): ?>
                        <li><a href="about.php" class="active">About</a></li>
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
                    <?php if (is_logged_in()): ?>
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

    <section class="static-page-header">
        <div class="container">
            <h1>About Aprnder</h1>
            <p class="lead">An Interactive and Gamified Problem-Based Learning System for ICT Students</p>
        </div>
    </section>

    <section class="static-page-content">
        <div class="container">
            <div class="content-section">
                <h2>The Problem We're Solving</h2>
                <p>Traditional teaching methods in Information and Communications Technology (ICT) often rely on lectures and static exercises, which can make learning programming dull and difficult to grasp. Many senior high school students struggle with coding because of its abstract concepts and the lack of interactive, hands-on experiences. This leads to frustration, low motivation, and weak problem-solving skills—qualities that are crucial in ICT education.</p>
                <p>Aprnder addresses these challenges by providing an interactive and gamified problem-based learning (PBL) system that transforms coding education into an engaging and less intimidating experience. Our platform helps students stay motivated, think creatively, and perform better academically by making learning active, enjoyable, and meaningful.</p>
            </div>

            <div class="content-section">
                <h2>Our Approach</h2>
                <div class="features-list-static">
                    <div class="feature-item-static">
                        <div class="feature-icon-static"><img src="../images/Game.png" alt="Game" style="width: 40px; height: 40px; object-fit: contain;"></div>
                        <div>
                            <h3>Gamification Elements</h3>
                            <p>Points, challenges, and rewards keep students motivated and engaged throughout their learning journey, making coding feel less like work and more like play.</p>
                        </div>
                    </div>
                    <div class="feature-item-static">
                        <div class="feature-icon-static"><img src="../images/Think.png" alt="Think" style="width: 40px; height: 40px; object-fit: contain;"></div>
                        <div>
                            <h3>Problem-Based Learning</h3>
                            <p>Students apply coding concepts to real-world problems through interactive quests, strengthening critical thinking and problem-solving skills.</p>
                        </div>
                    </div>
                    <div class="feature-item-static">
                        <div class="feature-icon-static"><img src="../images/Target.png" alt="Target" style="width: 40px; height: 40px; object-fit: contain;"></div>
                        <div>
                            <h3>Interactive Challenges</h3>
                            <p>Hands-on coding exercises and a tower defense game provide practical application of programming concepts in an engaging format.</p>
                        </div>
                    </div>
                    <div class="feature-item-static">
                        <div class="feature-icon-static"><img src="../images/Progress.png" alt="Progress" style="width: 40px; height: 40px; object-fit: contain;"></div>
                        <div>
                            <h3>Progress Tracking</h3>
                            <p>Students and instructors can monitor learning progress through achievements, badges, and leaderboards that encourage continuous improvement.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>Meet Our Team</h2>
                <div class="team-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                    <div class="team-member" style="text-align: center; padding: 2rem; background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                        <div class="team-avatar" style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 1rem; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                            <img src="../images/james.jpg" alt="James Romel Aquino" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h3 style="margin-bottom: 0.5rem;">James Romel Aquino</h3>
                        <p style="color: var(--color-text-muted); margin-bottom: 1rem;">Back-End Developer</p>
                        <p style="font-size: 0.9rem; color: var(--color-text-muted);">Developed the server-side architecture, database systems, and API endpoints that power the learning platform, including user authentication, quest management, and gamification features.</p>
                    </div>
                    
                    <div class="team-member" style="text-align: center; padding: 2rem; background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                        <div class="team-avatar" style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 1rem; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                            <img src="../images/kyle.jpg" alt="Kyle" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h3 style="margin-bottom: 0.5rem;">Kyle Alldrine Cataga</h3>
                        <p style="color: var(--color-text-muted); margin-bottom: 1rem;">Documentation</p>
                        <p style="font-size: 0.9rem; color: var(--color-text-muted);">Handled the documentation process by preparing, organizing, and maintaining all project-related files.</p>
                    </div>
                    
                    <div class="team-member" style="text-align: center; padding: 2rem; background: var(--color-surface); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                        <div class="team-avatar" style="width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 1rem; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                            <img src="../images/ska.jpg" alt="Ska" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <h3 style="margin-bottom: 0.5rem;">Cheska Eunice Diaz</h3>
                        <p style="color: var(--color-text-muted); margin-bottom: 1rem;">Front-End Developer</p>
                        <p style="font-size: 0.9rem; color: var(--color-text-muted);">Developed and designed the user interface of the website, ensuring a responsive layout and visually consistent design across all sections.</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2>Our Objectives</h2>
                <p>Aprnder was developed by college students who recognized the need for more engaging and interactive ICT education. Our platform is designed with three core objectives:</p>
                <ul class="content-list">
                    <li><strong>Design and Development:</strong> To create an interactive and gamified problem-based learning (PBL) system tailored specifically for ICT senior high school students.</li>
                    <li><strong>Student Engagement:</strong> To increase student engagement, motivation, and understanding of coding through interactive, game-based problem-solving activities.</li>
                    <li><strong>Measurable Impact:</strong> To assess the effectiveness of the system in improving students' participation, confidence, and performance in coding lessons.</li>
                </ul>
                <p>By combining problem-based learning with gamification mechanics, we've created a platform where students can learn, practice, and master programming concepts in an enjoyable and meaningful way.</p>
            </div>

            <div class="content-section">
                <h2>Technology Stack</h2>
                <p>Aprnder was built using industry-standard tools and technologies to ensure a robust and scalable learning platform:</p>
                <ul class="content-list">
                    <li><strong>Development Environment:</strong> Visual Studio Code for front-end and back-end development</li>
                    <li><strong>Server:</strong> XAMPP (Apache, PHP, MySQL) for local development and testing</li>
                    <li><strong>Design:</strong> Figma for creating interactive and visually appealing UI/UX designs</li>
                    <li><strong>Hardware:</strong> Developed on systems with AMD Ryzen 5 5600 processors, 16GB RAM, and 500GB storage</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>For Instructors & Mentors</h2>
                <p>Our platform empowers educators with tools to support ICT students effectively:</p>
                <ul class="content-list">
                    <li>Create and manage custom courses with problem-based quests</li>
                    <li>Review code submissions and provide personalized feedback</li>
                    <li>Track student progress and identify areas needing support</li>
                    <li>Monitor engagement through leaderboards and achievement systems</li>
                    <li>Foster a competitive yet collaborative learning environment</li>
                </ul>
            </div>

            <div class="content-section">
                <h2>For Students</h2>
                <p>Designed with ICT senior high school students in mind, Aprnder offers:</p>
                <ul class="content-list">
                    <li>Interactive quests that make abstract coding concepts concrete and understandable</li>
                    <li>Gamified learning experience with points, badges, and rewards</li>
                    <li>Tower defense game that reinforces programming logic and problem-solving</li>
                    <li>Self-paced progression that accommodates different learning speeds</li>
                    <li>Community features including leaderboards to stay motivated</li>
                </ul>
            </div>

            <div class="cta-box">
                <h2>Ready to Start Your Coding Journey?</h2>
                <p>Experience interactive and engaging programming education designed for ICT students</p>
                <div class="cta-buttons">
                    <?php if (is_logged_in()): ?>
                        <a href="courses.php" class="btn btn-primary btn-large">Browse Courses</a>
                        <a href="game.php" class="btn btn-secondary btn-large">Play Game</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">Get Started Free</a>
                        <a href="game.php" class="btn btn-secondary btn-large">Try Demo</a>
                    <?php endif; ?>
                </div>
            </div>
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

    <!-- Terms of Service Modal -->
    <div id="termsModal" class="legal-modal" style="display: none;">
        <div class="legal-modal-content">
            <div class="legal-modal-header">
                <h2>📄 Terms of Service</h2>
                <button class="legal-modal-close" onclick="closeTermsModal()">&times;</button>
            </div>
            <div class="legal-modal-body">
                <p><strong>Effective Date:</strong> <?php echo date('F j, Y'); ?></p>
                
                <h3>1. Acceptance of Terms</h3>
                <p>By accessing and using Aprnder ("the Platform"), you accept and agree to be bound by the terms and provision of this agreement.</p>
                
                <h3>2. Description of Service</h3>
                <p>Aprnder provides a gamified problem-based learning platform for programming education.</p>
                
                <h3>3. User Accounts</h3>
                <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>
                
                <h3>4. Acceptable Use</h3>
                <p>You agree not to misuse the Platform, including attempting to access restricted areas or interfering with other users' experience.</p>
                
                <h3>5. Intellectual Property</h3>
                <p>All Platform content is owned by Aprnder or its licensors and protected by copyright laws.</p>
                
                <h3>6. Privacy</h3>
                <p>Your use is governed by our Privacy Policy.</p>
                
                <h3>7. Limitation of Liability</h3>
                <p>Aprnder shall not be liable for indirect, incidental, or consequential damages.</p>
                
                <h3>8. Contact Information</h3>
                <p><strong>Email:</strong> mjraquino2@tip.edu.ph<br>
                <strong>Address:</strong> 363 Casal St, Quiapo, Manila, 1001 Metro Manila<br>
                <strong>Data Protection Officer:</strong> Cheska Eunice Diaz</p>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div id="privacyModal" class="legal-modal" style="display: none;">
        <div class="legal-modal-content">
            <div class="legal-modal-header">
                <h2><img src="../images/Secure.png" alt="Privacy" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Privacy Policy</h2>
                <button class="legal-modal-close" onclick="closePrivacyModal()">&times;</button>
            </div>
            <div class="legal-modal-body">
                <p><strong>Effective Date:</strong> <?php echo date('F j, Y'); ?></p>
                
                <h3>1. Introduction</h3>
                <p>Aprnder is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information.</p>
                
                <h3>2. Information We Collect</h3>
                <p><strong>Personal Information:</strong> Name, email address, profile information<br>
                <strong>Usage Data:</strong> Course progress, quiz results, game scores<br>
                <strong>Technical Data:</strong> IP address, browser type, device information</p>
                
                <h3>3. How We Use Your Information</h3>
                <p>• Provide and improve educational services<br>
                • Track learning progress and performance<br>
                • Communicate updates and notifications<br>
                • Ensure platform security</p>
                
                <h3>4. Data Sharing</h3>
                <p>We do not sell your personal information. We may share data with service providers necessary for platform operation.</p>
                
                <h3>5. Data Security</h3>
                <p>We implement industry-standard security measures to protect your data.</p>
                
                <h3>6. Your Rights</h3>
                <p>You have the right to access, correct, or delete your personal information.</p>
                
                <h3>7. Cookies</h3>
                <p>We use cookies to enhance user experience and track platform usage.</p>
                
                <h3>8. Children's Privacy</h3>
                <p>Our platform is designed for students 13 and older. Parental consent required for younger users.</p>
                
                <h3>9. Contact Information</h3>
                <p><strong>Email:</strong> mjraquino2@tip.edu.ph<br>
                <strong>Address:</strong> 363 Casal St, Quiapo, Manila, 1001 Metro Manila<br>
                <strong>Data Protection Officer:</strong> Cheska Eunice Diaz</p>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="../images/APRNDR (4).png" alt="Aprnder Logo" style="height: 48px; width: auto; margin-bottom: 1rem;">
                    <p>Learn coding through gaming.</p>
                </div>
                <div class="footer-links">
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
                            <li><a href="#" onclick="openTermsModal(); return false;">Terms of Service</a></li>
                            <li><a href="#" onclick="openPrivacyModal(); return false;">Privacy Policy</a></li>
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
          }else{
            notifList.innerHTML = '<div class="empty-notifications"><div class="empty-icon"><img src="../images/Alarm.png" alt="Notifications" style="width: 64px; height: 64px; object-fit: contain;"></div><h3>No notifications yet</h3></div>';
          }
        }catch(e){ notifList.innerHTML = '<div class="empty-notifications">Error loading notifications</div>'; }
      }
      if (notifBtn) notifBtn.addEventListener('click', (e)=>{ e.preventDefault(); notifPopup.style.display='flex'; loadNotifications(); });
      if (closeNotifBtn) closeNotifBtn.addEventListener('click', ()=> notifPopup.style.display='none');
      if (notifPopup) notifPopup.addEventListener('click', (e)=>{ if (e.target===notifPopup) notifPopup.style.display='none'; });
      
      // Legal Modal Functions
      function openTermsModal() {
        document.getElementById('termsModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
      }
      
      function closeTermsModal() {
        document.getElementById('termsModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }
      
      function openPrivacyModal() {
        document.getElementById('privacyModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
      }
      
      function closePrivacyModal() {
        document.getElementById('privacyModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }
      
      // Close modals when clicking outside
      window.onclick = function(event) {
        const termsModal = document.getElementById('termsModal');
        const privacyModal = document.getElementById('privacyModal');
        if (event.target == termsModal) {
          closeTermsModal();
        }
        if (event.target == privacyModal) {
          closePrivacyModal();
        }
      }
    </script>
</body>
</html>
