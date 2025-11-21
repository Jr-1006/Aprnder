<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Get user data if logged in
$user = null;
$role = null;
if (is_logged_in()) {
    $userId = current_user_id();
    $stmt = db()->prepare('SELECT u.*, p.full_name, p.avatar_url FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $role = $user['role'] ?? null;
}

$message = '';
$messageType = '';
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid form submission. Please try again.';
        $messageType = 'error';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message_text = trim($_POST['message'] ?? '');
        
        $errors = [];
        
        // Enhanced validation
        if (empty($name)) {
            $errors[] = 'Full name is required';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Full name must be at least 2 characters long';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Full name must be less than 100 characters';
        } elseif (!preg_match('/^[a-zA-Z\s\-\.\']+$/', $name)) {
            $errors[] = 'Full name can only contain letters, spaces, hyphens, dots, and apostrophes';
        }
        
        if (empty($email)) {
            $errors[] = 'Email address is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } elseif (strlen($email) > 255) {
            $errors[] = 'Email address is too long';
        }
        
        if (empty($subject)) {
            $errors[] = 'Please select a subject';
        } elseif (!in_array($subject, ['General Inquiry', 'Technical Support', 'Game Issues', 'Account Help', 'Feature Request', 'Other'])) {
            $errors[] = 'Please select a valid subject';
        }
        
        if (empty($message_text)) {
            $errors[] = 'Message is required';
        } elseif (strlen($message_text) < 10) {
            $errors[] = 'Message must be at least 10 characters long';
        } elseif (strlen($message_text) > 2000) {
            $errors[] = 'Message must be less than 2000 characters';
        }
        
        // Rate limiting (simple implementation)
        $ip = $_SERVER['REMOTE_ADDR'];
        $rateLimitFile = sys_get_temp_dir() . '/contact_rate_limit_' . md5($ip);
        $currentTime = time();
        
        if (file_exists($rateLimitFile)) {
            $lastSubmission = (int)file_get_contents($rateLimitFile);
            if ($currentTime - $lastSubmission < 300) { // 5 minutes
                $errors[] = 'Please wait 5 minutes before submitting another message';
            }
        }
        
        if (empty($errors)) {
            try {
                // Log contact form submission to file as backup
                $logFile = __DIR__ . '/../logs/contact_submissions.log';
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                
                $logEntry = sprintf(
                    "[%s] Name: %s | Email: %s | Subject: %s | Message: %s\n",
                    date('Y-m-d H:i:s'),
                    $name,
                    $email,
                    $subject,
                    substr($message_text, 0, 100) . '...'
                );
                file_put_contents($logFile, $logEntry, FILE_APPEND);
                
                require_once __DIR__ . '/../src/MailService.php';
                $mailService = new MailService();
                
                // Send contact form email
                $result = $mailService->sendContactForm($name, $email, $subject, $message_text);
                
                if ($result) {
                    // Set rate limit
                    file_put_contents($rateLimitFile, $currentTime);
                    
                    $message = 'Thank you for your message! We will get back to you within 24 hours.';
                    $messageType = 'success';
                    $formSubmitted = true;
                    
                    // Clear form data
                    $_POST = [];
                } else {
                    // Email failed but form is logged
                    file_put_contents($rateLimitFile, $currentTime);
                    
                    $message = 'Thank you for your message! Your submission has been recorded. We will get back to you within 24 hours.';
                    $messageType = 'success';
                    $formSubmitted = true;
                    
                    // Clear form data
                    $_POST = [];
                    
                    error_log('Email sending failed but contact form was logged for: ' . $email);
                }
            } catch (Exception $e) {
                error_log('Contact form error: ' . $e->getMessage());
                
                // Still try to log the submission
                $logFile = __DIR__ . '/../logs/contact_submissions.log';
                $logEntry = sprintf(
                    "[%s] FALLBACK - Name: %s | Email: %s | Subject: %s | Message: %s\n",
                    date('Y-m-d H:i:s'),
                    $name,
                    $email,
                    $subject,
                    substr($message_text, 0, 100) . '...'
                );
                @file_put_contents($logFile, $logEntry, FILE_APPEND);
                
                $message = 'Thank you for your message! Your submission has been recorded. We will get back to you within 24 hours.';
                $messageType = 'success';
                $formSubmitted = true;
                $_POST = [];
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo APP_NAME; ?></title>
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
                        <li><a href="leaderboard.php">Leaderboard</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php" class="active">Contact</a></li>
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
                <?php if (is_logged_in()): ?>
                <div class="nav-actions">
                    <a href="#" id="notifBtn" class="notif-bell" aria-label="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"/>
                          <path d="M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                        <?php 
                          $n = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
                          $n->execute([current_user_id()]);
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
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="contact-section">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-form-container">
                    <h1>Contact Us</h1>
                    <p class="contact-subtitle">Have a question or need help? We'd love to hear from you!</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="contact-form" id="contact-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                placeholder="Enter your full name (2-100 characters)"
                                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                required
                                maxlength="100"
                                autocomplete="name"
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="your.email@example.com"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                                maxlength="255"
                                autocomplete="email"
                            >
                            <small>We'll use this to respond to your inquiry</small>
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject <span class="required">*</span></label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry" <?php echo ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="Technical Support" <?php echo ($_POST['subject'] ?? '') === 'Technical Support' ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="Game Issues" <?php echo ($_POST['subject'] ?? '') === 'Game Issues' ? 'selected' : ''; ?>>Game Issues</option>
                                <option value="Account Help" <?php echo ($_POST['subject'] ?? '') === 'Account Help' ? 'selected' : ''; ?>>Account Help</option>
                                <option value="Feature Request" <?php echo ($_POST['subject'] ?? '') === 'Feature Request' ? 'selected' : ''; ?>>Feature Request</option>
                                <option value="Other" <?php echo ($_POST['subject'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <small>Choose the category that best describes your inquiry</small>
                        </div>

                        <div class="form-group">
                            <label for="message">Message <span class="required">*</span></label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="6"
                                placeholder="Please describe your inquiry in detail..."
                                required
                                maxlength="2000"
                            ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            <small class="char-count"><span id="char-count">0</span>/2000 characters</small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-large" id="submit-btn">
                            <span class="btn-text">Send Message</span>
                            <span class="btn-loading" style="display: none;">Sending...</span>
                        </button>
                    </form>

                    <div class="contact-info-box">
                        <h3>📞 Other Ways to Reach Us</h3>
                        <div class="info-item">
                            <strong>Response Time:</strong> We typically respond within 24 hours
                        </div>
                        <div class="info-item">
                            <strong>Game Support:</strong> For urgent game issues, please mention "URGENT" in your subject line
                        </div>
                        <div class="info-item">
                            <strong>Technical Issues:</strong> Include your browser and device information for faster resolution
                        </div>
                        <div class="info-item">
                            <strong>Feature Requests:</strong> We love hearing your ideas! Please describe how it would improve your learning experience
                        </div>
                    </div>

                    <div class="contact-footer">
                        <a href="index.php" class="link">← Back Home</a>
                        <span>or</span>
                        <a href="game.php" class="link">Try the Game →</a>
                    </div>
                </div>

                <div class="contact-sidebar">
                    <div class="sidebar-card">
                        <h3><img src="../images/Chat.png" alt="Help" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">Quick Help</h3>
                        <p>Looking for immediate answers? Check out our resources:</p>
                        <ul class="help-links">
                            <li><a href="help.php">Help & FAQs</a></li>
                            <li><a href="about.php">About Aprnder</a></li>
                            <li><a href="game.php">Try Our Tower Defense Game</a></li>
                            <?php if (is_logged_in()): ?>
                                <li><a href="courses.php">Browse Courses</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="sidebar-card">
                        <h3>🎓 Learning Support</h3>
                        <p>Need help with CSS Flexbox, coding concepts, or our gamified learning platform?</p>
                        <?php if (is_logged_in()): ?>
                            <a href="courses.php" class="btn btn-secondary">View All Courses</a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-secondary">Sign Up for Free</a>
                        <?php endif; ?>
                    </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contact-form');
            const messageTextarea = document.getElementById('message');
            const charCount = document.getElementById('char-count');
            const submitBtn = document.getElementById('submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Character counter for message
            function updateCharCount() {
                const length = messageTextarea.value.length;
                charCount.textContent = length;
                
                const counter = charCount.parentElement;
                counter.classList.remove('warning', 'danger');
                
                if (length > 1800) {
                    counter.classList.add('danger');
                } else if (length > 1500) {
                    counter.classList.add('warning');
                }
            }
            
            messageTextarea.addEventListener('input', updateCharCount);
            updateCharCount(); // Initial count
            
            // Form validation
            function validateField(field) {
                const value = field.value.trim();
                const fieldName = field.name;
                let isValid = true;
                let errorMessage = '';
                
                // Remove existing error styling
                field.classList.remove('error');
                
                switch (fieldName) {
                    case 'name':
                        if (!value) {
                            errorMessage = 'Full name is required';
                            isValid = false;
                        } else if (value.length < 2) {
                            errorMessage = 'Full name must be at least 2 characters';
                            isValid = false;
                        } else if (value.length > 100) {
                            errorMessage = 'Full name must be less than 100 characters';
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-\.\']+$/.test(value)) {
                            errorMessage = 'Full name can only contain letters, spaces, hyphens, dots, and apostrophes';
                            isValid = false;
                        }
                        break;
                        
                    case 'email':
                        if (!value) {
                            errorMessage = 'Email address is required';
                            isValid = false;
                        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                            errorMessage = 'Please enter a valid email address';
                            isValid = false;
                        } else if (value.length > 255) {
                            errorMessage = 'Email address is too long';
                            isValid = false;
                        }
                        break;
                        
                    case 'subject':
                        if (!value) {
                            errorMessage = 'Please select a subject';
                            isValid = false;
                        }
                        break;
                        
                    case 'message':
                        if (!value) {
                            errorMessage = 'Message is required';
                            isValid = false;
                        } else if (value.length < 10) {
                            errorMessage = 'Message must be at least 10 characters';
                            isValid = false;
                        } else if (value.length > 2000) {
                            errorMessage = 'Message must be less than 2000 characters';
                            isValid = false;
                        }
                        break;
                }
                
                if (!isValid) {
                    field.classList.add('error');
                    showFieldError(field, errorMessage);
                } else {
                    hideFieldError(field);
                }
                
                return isValid;
            }
            
            function showFieldError(field, message) {
                hideFieldError(field); // Remove existing error
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.style.color = '#ef4444';
                errorDiv.style.fontSize = '0.875rem';
                errorDiv.style.marginTop = '0.25rem';
                errorDiv.textContent = message;
                
                field.parentNode.appendChild(errorDiv);
            }
            
            function hideFieldError(field) {
                const existingError = field.parentNode.querySelector('.field-error');
                if (existingError) {
                    existingError.remove();
                }
            }
            
            // Real-time validation
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                field.addEventListener('blur', () => validateField(field));
                field.addEventListener('input', () => {
                    if (field.classList.contains('error')) {
                        validateField(field);
                    }
                });
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate all fields
                let isFormValid = true;
                fields.forEach(field => {
                    if (!validateField(field)) {
                        isFormValid = false;
                    }
                });
                
                if (!isFormValid) {
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
                
                // Submit form
                form.submit();
            });
            
            // Auto-resize textarea
            messageTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    </script>

    <?php if (is_logged_in()): ?>
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
    <?php endif; ?>

    <?php include('../includes/legal_modals.php'); ?>
</body>
</html>