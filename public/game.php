<?php
require_once __DIR__ . '/../src/bootstrap.php';

// If user is logged in, get their info for score tracking
$user_id = null;
$user_name = 'Guest';
if (is_logged_in()) {
    $user_id = current_user_id();
    $userStmt = db()->prepare('SELECT u.id, u.email, p.full_name, p.avatar_url FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = ?');
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch();
    $user_name = $user['full_name'] ?? $user['email'] ?? 'Player';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Tower Defense - Aprnder</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .game-wrapper {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 1rem;
            height: calc(100vh - 80px);
            padding: 1rem;
        }
        
        .control-panel {
            background: var(--color-surface);
            border: 2px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .control-panel h2 {
            margin-bottom: 1rem;
            color: var(--color-primary);
        }
        
        .css-input-section {
            margin-bottom: 2rem;
        }
        
        .css-input-section label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .css-editor {
            width: 100%;
            height: 200px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 1rem;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            background: #1e1e1e;
            color: #d4d4d4;
            resize: vertical;
            tab-size: 2;
        }
        
        .btn-control {
            padding: 0.75rem 1.5rem;
            margin-top: 0.5rem;
            margin-right: 0.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #5855eb;
        }
        
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .stats-panel {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid #6366f1;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-weight: 600;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--color-primary);
        }
        
        .game-area {
            display: flex;
            flex-direction: column;
            background: var(--color-surface);
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--color-border);
        }
        
        .game-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 1rem 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .game-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .game-canvas-container {
            flex: 1;
            padding: 1rem;
            background: #1a1a2e;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #gameCanvas {
            display: block;
            background: #0f172a;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            max-width: 100%;
            height: auto;
        }
        
        /* Custom Alert Modal */
        .aprender-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        
        .aprender-modal-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 10% auto;
            padding: 0;
            border-radius: 16px;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .aprender-modal-header {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px 16px 0 0;
            border-bottom: 3px solid #667eea;
        }
        
        .aprender-modal-header h3 {
            margin: 0;
            color: #667eea;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .aprender-modal-body {
            padding: 2rem;
            background: white;
            color: #1f2937;
            line-height: 1.8;
            white-space: pre-line;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .aprender-modal-footer {
            padding: 1.5rem;
            background: white;
            border-radius: 0 0 16px 16px;
            text-align: right;
        }
        
        .aprender-modal-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .aprender-modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .aprender-modal-btn:active {
            transform: translateY(0);
        }
        
        canvas {
            display: block;
            background: #1a1a2e;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .instructions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .instructions h3 {
            margin-bottom: 0.5rem;
            color: #856404;
        }
        
        .instructions ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #856404;
            font-size: 0.9rem;
        }
        
        .instructions li {
            margin-bottom: 0.25rem;
        }
        
        .instructions code {
            background: white;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: monospace;
        }
        
        @media (max-width: 1024px) {
            .game-wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .control-panel {
                max-height: 400px;
            }
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
                    <li><a href="game.php" class="active">Game</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="discord.php">Discord</a></li>
                        <li><a href="leaderboard.php">Leaderboard</a></li>
                    <?php endif; ?>
                    <li><a href="contact.php">Contact</a></li>
                    <?php if (!is_logged_in()): ?>
                        <li><a href="about.php">About</a></li>
                        <li><a href="help.php">Help</a></li>
                    <?php endif; ?>
                    <?php if (is_logged_in()): 
                        $roleStmt = db()->prepare('SELECT role FROM users WHERE id = ?');
                        $roleStmt->execute([current_user_id()]);
                        $roleVal = $roleStmt->fetchColumn();
                        if ($roleVal === 'student' || $roleVal === 'user'): ?>
                          <li><a href="mentor-progress.php" style="color: #10b981;">🎓 Become a Mentor</a></li>
                        <?php endif; 
                        if ($roleVal === 'mentor'): ?>
                          <li><a href="mentor/dashboard.php" style="color: #10b981;">👨‍🏫 Mentor Panel</a></li>
                        <?php endif; 
                        if ($roleVal === 'admin'): ?>
                          <li><a href="admin/dashboard.php" style="color: #fbbf24;">⚡ Admin</a></li>
                        <?php endif; endif; ?>
                </ul>
                <div class="nav-actions">
                    <?php if (is_logged_in()): ?>
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
                    <?php else: ?>
                        <a href="login.php" class="btn-login">Login</a>
                    <?php endif; ?>
            </div>
        </div>
        </div>
    </nav>

    <div class="game-wrapper">
        <div class="control-panel">
            <h2><img src="../images/Game.png" alt="Game" style="width: 24px; height: 24px; object-fit: contain; vertical-align: middle; margin-right: 8px;">CSS Tower Defense</h2>
            
            <div class="instructions">
                <h3><img src="../images/Grad Cap.png" alt="Learn" style="width: 20px; height: 20px; object-fit: contain; vertical-align: middle; margin-right: 8px;">How to Play:</h3>
                <ul>
                    <li>Write <strong>CSS code</strong> to position turrets (Flexbox or Grid)</li>
                    <li>Support: <code>display: flex</code>, <code>display: grid</code>, <code>margin</code>, and more</li>
                    <li>Click <strong>"Apply CSS"</strong> to see placement</li>
                    <li>Click <strong>"Start Wave"</strong> when ready</li>
                    <li>Terrain changes each wave - adapt your CSS!</li>
                </ul>
                
                <details style="margin-top: 1rem; padding: 0.75rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; border-left: 3px solid #10b981;">
                    <summary style="cursor: pointer; font-weight: 600; color: #10b981;">📖 CSS Property Guide</summary>
                    <div style="margin-top: 0.75rem; font-size: 0.85rem; line-height: 1.6;">
                        <p><strong>justify-content:</strong> (horizontal)</p>
                        <ul style="margin: 0.25rem 0 0.75rem 1rem; list-style: circle;">
                            <li><code>flex-start</code> - Left side</li>
                            <li><code>center</code> - Center</li>
                            <li><code>flex-end</code> - Right side</li>
                            <li><code>space-between</code> - Spread with edges</li>
                            <li><code>space-around</code> - Spread evenly</li>
                        </ul>
                        
                        <p><strong>align-items:</strong> (vertical)</p>
                        <ul style="margin: 0.25rem 0 0.75rem 1rem; list-style: circle;">
                            <li><code>flex-start</code> - Top</li>
                            <li><code>center</code> - Middle</li>
                            <li><code>flex-end</code> - Bottom</li>
                        </ul>
                        
                        <p><strong>gap:</strong> Space between items</p>
                        <ul style="margin: 0.25rem 0 0.75rem 1rem; list-style: circle;">
                            <li><code>gap: 50px;</code> - Small spacing</li>
                            <li><code>gap: 100px;</code> - Large spacing</li>
                        </ul>
                        
                        <p><strong>display: grid</strong> - CSS Grid Layout</p>
                        <ul style="margin: 0.25rem 0 0.75rem 1rem; list-style: circle;">
                            <li><code>grid-template-columns: repeat(3, 1fr)</code> - 3 equal columns</li>
                            <li><code>grid-template-rows: repeat(2, 1fr)</code> - 2 equal rows</li>
                            <li><code>gap: 20px;</code> - Grid gap</li>
                        </ul>
                        
                        <p><strong>margin:</strong> - Offset positioning</p>
                        <ul style="margin: 0.25rem 0 0 1rem; list-style: circle;">
                            <li><code>margin: 50px;</code> - Adds space from edges</li>
                            <li><code>margin-left: 100px;</code> - Horizontal offset</li>
                            <li><code>margin-top: 80px;</code> - Vertical offset</li>
                        </ul>
                    </div>
                </details>
            </div>
            
            <div class="css-input-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label for="cssInput"><strong>Your CSS Code:</strong></label>
                    <button id="showExampleBtn" class="btn-control" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #3b82f6; color: white; border: 2px solid #3b82f6; font-weight: 500;"><img src="../images/Task.png" alt="Example" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px;">Show Example</button>
                </div>
                <textarea id="cssInput" class="css-editor" placeholder="display: flex;
justify-content: space-around;
align-items: center;
gap: 80px;"></textarea>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                    <button id="applyBtn" class="btn-control btn-primary" style="flex: 1;">Apply CSS</button>
                    <button id="startWaveBtn" class="btn-control btn-primary" style="flex: 1;" disabled>Start Wave</button>
                </div>
                <button id="resetBtn" class="btn-control btn-secondary" style="width: 100%; margin-top: 0.5rem;">Reset Game</button>
                <div id="waveHint" class="tip-box" style="margin-top: 1rem; padding: 0.75rem; background: rgba(99, 102, 241, 0.1); border-radius: 8px; font-size: 0.9rem; border-left: 3px solid #6366f1; line-height: 1.6;">
                    <img src="../images/Target.png" alt="Target" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px;"><strong>Wave 1: Horizontal Distribution</strong><br>
                    The path runs horizontally at y=300, then moves vertically.<br>
                    <code>justify-content: space-around</code> spreads 6 turrets evenly across the width.<br>
                    <img src="../images/Think.png" alt="Tip" style="width: 16px; height: 16px; object-fit: contain; vertical-align: middle; margin-right: 4px;">Alternative: Try <code>space-between</code> or <code>center</code>
                </div>
            </div>
            
            <div class="stats-panel">
                <div class="stat-row">
                    <span class="stat-label">Score:</span>
                    <span id="scoreValue" class="stat-value">0</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Lives:</span>
                    <span id="livesValue" class="stat-value">20</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Wave:</span>
                    <span id="waveValue" class="stat-value">1</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Enemies:</span>
                    <span id="enemiesValue" class="stat-value">0</span>
                </div>
                </div>
            </div>

        <div class="game-area">
            <div class="game-header">
                <h1>CSS Tower Defense</h1>
                <div>Player: <?php echo htmlspecialchars($user_name); ?></div>
            </div>
            <div class="game-canvas-container">
                <canvas id="gameCanvas" width="800" height="600"></canvas>
            </div>
                </div>
            </div>

    <!-- Custom Alert Modal -->
    <div id="aprenderModal" class="aprender-modal">
        <div class="aprender-modal-content">
            <div class="aprender-modal-header">
                <h3>🎓 Aprnder says</h3>
            </div>
            <div class="aprender-modal-body" id="aprenderModalBody">
                <!-- Message content will be inserted here -->
            </div>
            <div class="aprender-modal-footer">
                <button class="aprender-modal-btn" id="aprenderModalBtn">OK</button>
            </div>
        </div>
    </div>

    <script>
        const CANVAS_WIDTH = 800;
        const CANVAS_HEIGHT = 600;
        const TOWER_RANGE = 160; // Slightly larger for better coverage
        const TOWER_DAMAGE = 35;
        const TOWER_FIRERATE = 750; // ms between shots - optimized for smooth gameplay
        const NUM_TURRETS = 6; // Fixed number of turrets - encourages strategic placement

        // Wave-based path patterns - terrain changes each wave!
        const WAVE_PATHS = {
            1: [ // Simple path - Learn justify-content
                {x: -20, y: 300}, {x: 200, y: 300}, {x: 200, y: 150}, 
                {x: 500, y: 150}, {x: 500, y: 450}, {x: 820, y: 450}
            ],
            2: [ // Zigzag - Learn align-items
                {x: -20, y: 100}, {x: 250, y: 100}, {x: 250, y: 400},
                {x: 550, y: 400}, {x: 550, y: 200}, {x: 820, y: 200}
            ],
            3: [ // Wide curves - Combine properties
                {x: -20, y: 200}, {x: 150, y: 200}, {x: 150, y: 450},
                {x: 400, y: 450}, {x: 400, y: 100}, {x: 650, y: 100},
                {x: 650, y: 500}, {x: 820, y: 500}
            ],
            4: [ // Narrow passages - Use gap
                {x: -20, y: 150}, {x: 300, y: 150}, {x: 300, y: 450},
                {x: 500, y: 450}, {x: 500, y: 250}, {x: 820, y: 250}
            ],
            5: [ // Complex - Master all properties
                {x: -20, y: 300}, {x: 150, y: 300}, {x: 150, y: 100},
                {x: 400, y: 100}, {x: 400, y: 500}, {x: 650, y: 500},
                {x: 650, y: 200}, {x: 820, y: 200}
            ]
        };

        // Wave hints for progressive CSS learning
        const WAVE_HINTS = {
            1: "🎯 <strong>Wave 1: Horizontal Distribution</strong><br>" +
               "The path runs horizontally at y=300, then moves vertically.<br>" +
               "<code>justify-content: space-around</code> spreads 6 turrets evenly across the width.<br>" +
               "💡 Alternative: Try <code>space-between</code> or <code>center</code>",
            
            2: "📐 <strong>Wave 2: Vertical Positioning</strong><br>" +
               "Zigzag path alternates between top (y=100) and bottom (y=400)!<br>" +
               "<code>align-items: flex-start</code> places turrets at TOP to cover the upper path.<br>" +
               "💡 You can also use <code>flex-end</code> for bottom or <code>center</code> for middle",
            
            3: "🎨 <strong>Wave 3: Strategic Combinations</strong><br>" +
               "Wide curves require turrets in specific zones!<br>" +
               "Combine <code>justify-content</code> + <code>align-items</code> + <code>gap</code>.<br>" +
               "💡 Example: Center turrets at bottom with spacing for better coverage",
            
            4: "📏 <strong>Wave 4: Spacing Control</strong><br>" +
               "Narrow passages need carefully spaced turrets to avoid path overlap!<br>" +
               "<code>gap: 100px</code> ensures turrets don't cluster together.<br>" +
               "💡 Larger gap = more spread, smaller gap = tighter formation",
            
            5: "🏆 <strong>Wave 5: Master All Properties!</strong><br>" +
               "Complex serpentine path - think about overlapping turret ranges!<br>" +
               "Use ALL properties: <code>justify-content</code>, <code>align-items</code>, <code>gap</code><br>" +
               "💡 Strategic tip: Cover corners where enemies slow down turning"
        };
        
        // CSS examples for each wave (for reference)
        const WAVE_EXAMPLES = {
            1: "display: flex;\njustify-content: space-around;\nalign-items: center;",
            2: "display: flex;\njustify-content: space-between;\nalign-items: flex-start;",
            3: "display: flex;\njustify-content: center;\nalign-items: flex-end;\ngap: 60px;",
            4: "display: flex;\njustify-content: flex-start;\nalign-items: center;\ngap: 100px;",
            5: "display: flex;\njustify-content: space-around;\nalign-items: flex-start;\ngap: 80px;"
        };

        // Game State
        let gameState = {
            score: 0,
            lives: 20,
            wave: 1,
            enemiesLeft: 0,
            gameRunning: false,
            isSpawning: false,
            turretCSS: '',
            turrets: [],
            enemies: [],
            bullets: [],
            particles: [],
            lastFrameTime: 0,
            currentPath: [ // Initialize with wave 1 path
                {x: -20, y: 300}, {x: 200, y: 300}, {x: 200, y: 150}, 
                {x: 500, y: 150}, {x: 500, y: 450}, {x: 820, y: 450}
            ]
        };

        // Canvas setup - will be initialized in DOMContentLoaded
        let canvas = null;
        let ctx = null;
        
        // Custom Alert Function - Shows "Aprnder says" instead of browser default
        function aprenderAlert(message) {
            const modal = document.getElementById('aprenderModal');
            const modalBody = document.getElementById('aprenderModalBody');
            const modalBtn = document.getElementById('aprenderModalBtn');
            
            modalBody.textContent = message;
            modal.style.display = 'block';
            
            // Close modal on button click
            const closeModal = () => {
                modal.style.display = 'none';
                modalBtn.removeEventListener('click', closeModal);
            };
            
            modalBtn.addEventListener('click', closeModal);
            
            // Close on background click
            modal.onclick = (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            };
        }
        
        // Custom Confirm Function - Returns a promise
        function aprenderConfirm(message) {
            return new Promise((resolve) => {
                const modal = document.getElementById('aprenderModal');
                const modalBody = document.getElementById('aprenderModalBody');
                const modalFooter = modal.querySelector('.aprender-modal-footer');
                
                modalBody.textContent = message;
                modal.style.display = 'block';
                
                // Replace footer with Yes/No buttons
                modalFooter.innerHTML = `
                    <button class="aprender-modal-btn" id="confirmYes" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); margin-right: 0.5rem;">Yes</button>
                    <button class="aprender-modal-btn" id="confirmNo" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">No</button>
                `;
                
                const yesBtn = document.getElementById('confirmYes');
                const noBtn = document.getElementById('confirmNo');
                
                const closeModal = (result) => {
                    modal.style.display = 'none';
                    // Restore original footer
                    modalFooter.innerHTML = '<button class="aprender-modal-btn" id="aprenderModalBtn">OK</button>';
                    resolve(result);
                };
                
                yesBtn.onclick = () => closeModal(true);
                noBtn.onclick = () => closeModal(false);
                
                // Close on background click = No
                modal.onclick = (e) => {
                    if (e.target === modal) {
                        closeModal(false);
                    }
                };
            });
        }
        
        // Enemy path (snaking through canvas with safe zones for turrets)
        const PATH_POINTS = [
            {x: 0, y: 150},
            {x: 120, y: 150},
            {x: 120, y: 280},
            {x: 350, y: 280},
            {x: 350, y: 80},
            {x: 580, y: 80},
            {x: 580, y: 400},
            {x: 800, y: 400}
        ];

        // Define safe zones where turrets can be placed (around the path)
        const SAFE_ZONES = [
            {x: 60, y: 60, id: 1},
            {x: 220, y: 60, id: 2},
            {x: 220, y: 320, id: 3},
            {x: 500, y: 60, id: 4},
            {x: 500, y: 340, id: 5},
            {x: 680, y: 340, id: 6},
            {x: 680, y: 60, id: 7},
            {x: 400, y: 200, id: 8}
        ];

        // Check if point is on path
        function isPointOnPath(x, y, pathPoints, radius = 35) {
            for (let i = 0; i < pathPoints.length - 1; i++) {
                const p1 = pathPoints[i];
                const p2 = pathPoints[i + 1];
                const distance = pointToLineDistance(x, y, p1.x, p1.y, p2.x, p2.y);
                if (distance < radius) return true;
            }
            return false;
        }

        function pointToLineDistance(px, py, x1, y1, x2, y2) {
            const A = px - x1;
            const B = py - y1;
            const C = x2 - x1;
            const D = y2 - y1;
            
            const dot = A * C + B * D;
            const lenSq = C * C + D * D;
            let param = -1;
            
            if (lenSq !== 0) param = dot / lenSq;
            
            let xx, yy;
            
            if (param < 0) {
                xx = x1;
                yy = y1;
            } else if (param > 1) {
                xx = x2;
                yy = y2;
            } else {
                xx = x1 + param * C;
                yy = y1 + param * D;
            }
            
            const dx = px - xx;
            const dy = py - yy;
            return Math.sqrt(dx * dx + dy * dy);
        }

        // Draw enemy path
        function drawPath() {
            if (!ctx) return;
            const path = gameState.currentPath;
            if (!path || path.length < 2) return;
            
            // Draw path glow
            ctx.strokeStyle = 'rgba(59, 130, 246, 0.15)';
            ctx.lineWidth = 60;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.beginPath();
            ctx.moveTo(path[0].x, path[0].y);
            for (let i = 1; i < path.length; i++) {
                ctx.lineTo(path[i].x, path[i].y);
            }
            ctx.stroke();
            
            // Draw path background
            ctx.strokeStyle = '#374151';
            ctx.lineWidth = 50;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.beginPath();
            ctx.moveTo(path[0].x, path[0].y);
            for (let i = 1; i < path.length; i++) {
                ctx.lineTo(path[i].x, path[i].y);
            }
            ctx.stroke();
            
            // Draw path center line (dashed)
            ctx.strokeStyle = '#6b7280';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.setLineDash([10, 5]);
            ctx.beginPath();
            ctx.moveTo(path[0].x, path[0].y);
            for (let i = 1; i < path.length; i++) {
                ctx.lineTo(path[i].x, path[i].y);
            }
            ctx.stroke();
            ctx.setLineDash([]);
        }

        // Apply CSS to position turrets
        function applyCSS() {
            const cssCode = document.getElementById('cssInput').value;
            if (!cssCode.trim()) {
                aprenderAlert('Please enter CSS code!');
                return;
            }
            
            try {
                // Parse CSS to understand positioning intent
                const cssProps = parseCSS(cssCode);
                
                // Calculate turret positions based on CSS
                const positions = calculateTowerPositions(cssProps);
                
                // Create turrets only in valid positions (not on the path)
                gameState.turrets = [];
                positions.forEach(pos => {
                    if (!isPointOnPath(pos.x, pos.y, gameState.currentPath, 40)) {
                        if (pos.x > 30 && pos.x < CANVAS_WIDTH - 30 && 
                            pos.y > 30 && pos.y < CANVAS_HEIGHT - 30) {
                            gameState.turrets.push({
                                x: pos.x,
                                y: pos.y,
                                angle: 0,
                                lastShot: 0,
                                range: TOWER_RANGE,
                                damage: TOWER_DAMAGE + (gameState.wave - 1) * 5,
                                id: pos.id
                            });
                        }
                    }
                });
                
                if (gameState.turrets.length === 0) {
                    aprenderAlert('⚠️ Could not place turrets! They are on the enemy path.\n\nTry different CSS like:\n• justify-content: space-around\n• align-items: flex-start');
                    updateUI();
                    draw();
                    return;
                }
                
                gameState.turretCSS = cssCode;
                updateUI();
                draw();
                
                // Enable start wave button
                document.getElementById('startWaveBtn').disabled = false;
                
                // Show CSS properties used
                const propsUsed = [];
                if (cssProps.justifycontent) propsUsed.push(`justify-content: ${cssProps.justifycontent}`);
                if (cssProps.alignitems) propsUsed.push(`align-items: ${cssProps.alignitems}`);
                if (cssProps.gap) propsUsed.push(`gap: ${cssProps.gap}`);
                
                const propsText = propsUsed.length > 0 ? '\n\n📝 CSS Applied:\n• ' + propsUsed.join('\n• ') : '';
                
                aprenderAlert(`✅ ${gameState.turrets.length} Turrets Placed!${propsText}\n\nClick "Start Wave" when ready!`);
                
            } catch (error) {
                aprenderAlert('Error parsing CSS: ' + error.message);
            }
        }

        function parseCSS(cssText) {
            const props = {};
            const lines = cssText.split('\n').map(l => l.trim()).filter(l => l);
            
            lines.forEach(line => {
                if (line.includes(':')) {
                    const parts = line.split(':').map(p => p.trim());
                    const prop = parts[0].replace(/-/g, '').toLowerCase();
                    // Handle multiple values (like margin: 10px 20px)
                    let value = parts[1]?.replace(';', '').trim();
                    if (value) {
                        // Parse multiple values
                        const values = value.split(/\s+/);
                        if (values.length > 1) {
                            props[prop] = values;
                        } else {
                            props[prop] = value;
                        }
                    }
                }
            });
            
            return props;
        }
        
        function parseValue(value) {
            if (Array.isArray(value)) {
                // Return first value for simplicity
                return parseInt(value[0]?.replace('px', '')) || 0;
            }
            return parseInt(value?.replace('px', '')) || 0;
        }

        function calculateTowerPositions(cssProps) {
            // Base positions in safe zones
            let positions = [...SAFE_ZONES];
            
            // Apply CSS transformations
            const justify = cssProps.justifycontent || 'center';
            const align = cssProps.alignitems || 'center';
            const direction = cssProps.flexdirection || 'row';
            const gap = parseValue(cssProps.gap);
            const gridgap = parseValue(cssProps.gap) || parseValue(cssProps.gridgap);
            
            // Parse Grid template
            const gridCols = cssProps.gridtemplatecolumns || '';
            const gridRows = cssProps.gridtemplaterows || '';
            
            // Parse margin
            const margin = cssProps.margin;
            const marginTop = parseValue(cssProps.margintop) || parseValue(margin);
            const marginLeft = parseValue(cssProps.marginleft) || parseValue(margin);
            
            // Handle CSS Grid
            if ((cssProps.display === 'grid' || gridCols || gridRows)) {
                // Parse grid-template-columns (e.g., "repeat(2, 1fr)" or "200px 200px 200px")
                let cols = 3;
                if (gridCols) {
                    const repeatMatch = gridCols.match(/repeat\((\d+)/);
                    if (repeatMatch) {
                        cols = parseInt(repeatMatch[1]);
                    } else {
                        cols = gridCols.split(' ').length;
                    }
                }
                
                // Parse grid-template-rows
                let rows = 2;
                if (gridRows) {
                    const repeatMatch = gridRows.match(/repeat\((\d+)/);
                    if (repeatMatch) {
                        rows = parseInt(repeatMatch[1]);
                    } else {
                        rows = gridRows.split(' ').length;
                    }
                }
                
                // Calculate grid positions
                const cellWidth = (CANVAS_WIDTH - 100) / cols;
                const cellHeight = (CANVAS_HEIGHT - 100) / rows;
                
                positions.forEach((pos, i) => {
                    const row = Math.floor(i / cols);
                    const col = i % cols;
                    pos.x = 50 + (col * cellWidth) + (cellWidth / 2);
                    pos.y = 50 + (row * cellHeight) + (cellHeight / 2);
                });
                
                // Apply grid gap
                if (gridgap > 0 && positions.length > 1) {
                    positions.forEach((pos, i) => {
                        const row = Math.floor(i / cols);
                        const col = i % cols;
                        if (col > 0) pos.x += gridgap * col;
                        if (row > 0) pos.y += gridgap * row;
                    });
                }
            } else {
                // Original flexbox logic
            
            // Apply justify-content
            if (justify === 'space-between' && positions.length > 1) {
                const step = CANVAS_WIDTH / (positions.length - 1);
                positions.forEach((pos, i) => {
                    pos.x = i * step;
                });
            } else if (justify === 'space-around') {
                const spacing = CANVAS_WIDTH / positions.length;
                positions.forEach((pos, i) => {
                    pos.x = spacing * (i + 0.5);
                });
            } else if (justify === 'flex-start') {
                positions.forEach((pos, i) => {
                    pos.x = 60 + (i * 100);
                });
            } else if (justify === 'flex-end') {
                positions.forEach((pos, i) => {
                    pos.x = CANVAS_WIDTH - 60 - (i * 100);
                });
            } else if (justify === 'center') {
                const centerX = CANVAS_WIDTH / 2;
                positions.forEach((pos, i) => {
                    pos.x = centerX - (positions.length / 2 * 100) + (i * 100) + 50;
                });
            }
            
            // Apply align-items
            if (align === 'flex-start') {
                positions.forEach(pos => pos.y = 80);
            } else if (align === 'flex-end') {
                positions.forEach(pos => pos.y = CANVAS_HEIGHT - 80);
            } else if (align === 'center') {
                positions.forEach(pos => pos.y = CANVAS_HEIGHT / 2);
            }
            
            // Apply gap if specified
            if (gap > 0 && positions.length > 1) {
                positions.sort((a, b) => a.x - b.x);
                const startX = positions[0].x;
                positions.forEach((pos, i) => {
                    if (i > 0) {
                        pos.x = startX + (i * gap);
                    }
                });
            }
            }
            
            // Apply margin to all positions (works with both Grid and Flexbox)
            if (marginTop > 0) {
                positions.forEach(pos => pos.y += marginTop);
            }
            if (marginLeft > 0) {
                positions.forEach(pos => pos.x += marginLeft);
            }
            
            // Limit to NUM_TURRETS
            return positions.slice(0, NUM_TURRETS);
        }

        // Start wave
        function startWave() {
            if (gameState.turrets.length === 0) {
                aprenderAlert('Please apply CSS first!');
                return;
            }
            
            if (gameState.gameRunning) {
                aprenderAlert('Wave already in progress!');
                return;
            }
            
            gameState.gameRunning = true;
            gameState.isSpawning = true;
            
            // Calculate wave stats with balanced difficulty progression
            const enemiesCount = 5 + (gameState.wave * 2); // More enemies per wave
            const enemyHealth = 50 + (gameState.wave - 1) * 20; // Gradual health increase
            const enemySpeed = 1.0 + (gameState.wave - 1) * 0.15; // Faster enemies per wave
            
            gameState.enemiesLeft = enemiesCount;
            
            // Spawn enemies
            spawnEnemies(enemiesCount, enemyHealth, enemySpeed);
            
            updateUI();
        }

        function spawnEnemies(count, health, speed) {
            let spawned = 0;
            
            const spawnInterval = setInterval(() => {
                if (spawned >= count || !gameState.gameRunning) {
                    clearInterval(spawnInterval);
                    gameState.isSpawning = false;
                    return;
                }
                
                const startPos = gameState.currentPath[0];
                gameState.enemies.push({
                    x: startPos.x,
                    y: startPos.y,
                    health: health,
                    maxHealth: health,
                    speed: speed,
                    reward: 10 * gameState.wave,
                    pathIndex: 0,
                    lastDamage: 0
                });
                
                spawned++;
                
                updateUI();
                
                if (spawned >= count) {
                    clearInterval(spawnInterval);
                    gameState.isSpawning = false;
                }
            }, 900); // Faster spawn
        }

        // Update enemies
        function updateEnemies() {
            const path = gameState.currentPath;
            if (!path || path.length < 2) return;
            
            for (let i = gameState.enemies.length - 1; i >= 0; i--) {
                const enemy = gameState.enemies[i];
                
                if (enemy.pathIndex < path.length - 1) {
                    const current = path[enemy.pathIndex];
                    const next = path[enemy.pathIndex + 1];
                    
                    const dx = next.x - current.x;
                    const dy = next.y - current.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    
                    const moveX = (dx / distance) * enemy.speed;
                    const moveY = (dy / distance) * enemy.speed;
                    
                    enemy.x += moveX;
                    enemy.y += moveY;
                    
                    // Check if reached next point
                    const newDx = next.x - enemy.x;
                    const newDy = next.y - enemy.y;
                    
                    if ((newDx * dx < 0) || (newDy * dy < 0)) {
                        enemy.pathIndex++;
                        enemy.x = next.x;
                        enemy.y = next.y;
                    }
                } else {
                    // Reached end - lose life
                    gameState.lives--;
                    gameState.enemies.splice(i, 1);
                    
                    if (gameState.lives <= 0) {
                        gameOver();
                    }
                }
                
                // Remove dead enemies
                if (enemy.health <= 0) {
                    gameState.score += enemy.reward;
                    gameState.enemies.splice(i, 1);
                    
                    // Add particles
                    for (let j = 0; j < 8; j++) {
                        gameState.particles.push({
                            x: enemy.x,
                            y: enemy.y,
                            vx: (Math.random() - 0.5) * 4,
                            vy: (Math.random() - 0.5) * 4,
                            life: 30,
                            color: '#ff6b6b'
                        });
                    }
                }
            }
            
            // Update particles
            gameState.particles = gameState.particles.filter(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.2; // gravity
                p.life--;
                return p.life > 0;
            });
        }

        // Update turrets and shooting
        function updateTurrets() {
            const now = Date.now();
            
            gameState.turrets.forEach(tower => {
                // Find nearest enemy in range
                let target = null;
                let minDist = tower.range;
                
                gameState.enemies.forEach(enemy => {
                    const dx = enemy.x - tower.x;
                    const dy = enemy.y - tower.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    
                    if (dist < minDist && dist < tower.range) {
                        minDist = dist;
                        target = enemy;
                    }
                });
                
                // Aim at target
                if (target) {
                    tower.angle = Math.atan2(target.y - tower.y, target.x - tower.x);
                    
                    // Shoot
                    if (now - tower.lastShot > TOWER_FIRERATE) {
                        tower.lastShot = now;
                        
                        gameState.bullets.push({
                            x: tower.x,
                            y: tower.y,
                            targetX: target.x,
                            targetY: target.y,
                            damage: tower.damage,
                            speed: 8
                        });
                    }
                }
            });
        }

        // Update bullets
        function updateBullets() {
            gameState.bullets.forEach((bullet, i) => {
                const dx = bullet.targetX - bullet.x;
                const dy = bullet.targetY - bullet.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 10) {
                    // Check collision with enemies
                    gameState.enemies.forEach(enemy => {
                        const edx = enemy.x - bullet.targetX;
                        const edy = enemy.y - bullet.targetY;
                        const edist = Math.sqrt(edx * edx + edy * edy);
                        
                        if (edist < 15) {
                            enemy.health -= bullet.damage;
                            
                            // Add hit effect
                            for (let j = 0; j < 3; j++) {
                                gameState.particles.push({
                                    x: enemy.x,
                                    y: enemy.y,
                                    vx: (Math.random() - 0.5) * 2,
                                    vy: (Math.random() - 0.5) * 2,
                                    life: 10,
                                    color: '#fff'
                                });
                            }
                        }
                    });
                    
                    gameState.bullets.splice(i, 1);
                } else {
                    bullet.x += (dx / distance) * bullet.speed;
                    bullet.y += (dy / distance) * bullet.speed;
                }
            });
        }

        async function gameOver() {
            gameState.gameRunning = false;
            gameState.isSpawning = false;
            
            setTimeout(async () => {
                const playAgain = await aprenderConfirm(`Game Over!\nFinal Score: ${gameState.score}\nWave Reached: ${gameState.wave}\n\nPlay Again?`);
                
                if (playAgain) {
                    resetGame();
                }
            }, 100);
        }

        // Check wave completion
        function checkWaveComplete() {
            if (gameState.gameRunning && !gameState.isSpawning && 
                gameState.enemies.length === 0) {
                
                gameState.gameRunning = false;
                gameState.wave++;
                
                // Change terrain for next wave
                const nextPathIndex = ((gameState.wave - 1) % 5) + 1;
                gameState.currentPath = WAVE_PATHS[nextPathIndex];
                
                // Clear turrets - require new CSS for new terrain
                gameState.turrets = [];
                document.getElementById('startWaveBtn').disabled = true;
                
                // Update hint with enhanced content
                const hintBox = document.getElementById('waveHint');
                if (hintBox) {
                    hintBox.innerHTML = WAVE_HINTS[nextPathIndex];
                }
                
                setTimeout(() => {
                    const prevWave = gameState.wave - 1;
                    const nextPathIndex = ((gameState.wave - 1) % 5) + 1;
                    
                    // Get text-only version of hint (remove HTML tags)
                    const hintText = WAVE_HINTS[nextPathIndex]
                        .replace(/<br>/g, '\n')
                        .replace(/<[^>]*>/g, '')
                        .replace(/&nbsp;/g, ' ');
                    
                    aprenderAlert(`🎉 Wave ${prevWave} Complete! Great job!\n\n` +
                          `📊 Score: ${gameState.score}\n` +
                          `❤️ Lives: ${gameState.lives}/20\n\n` +
                          `⚠️ TERRAIN CHANGED FOR WAVE ${gameState.wave}!\n` +
                          `The enemy path is now different.\n\n` +
                          `${hintText}\n\n` +
                          `📝 Click "Show Example" for a working CSS solution!`);
                    updateUI();
                    draw();
                }, 300);
            }
        }

        // Draw function
        function draw() {
            if (!ctx || !canvas) return; // Safety check
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw path
            drawPath();
            
            // Draw turret range indicators
            if (gameState.gameRunning) {
                gameState.turrets.forEach(tower => {
                    ctx.strokeStyle = 'rgba(99, 102, 241, 0.2)';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.arc(tower.x, tower.y, tower.range, 0, Math.PI * 2);
                    ctx.stroke();
                });
            }
            
            // Draw turrets with labels
            gameState.turrets.forEach((tower, idx) => {
                ctx.save();
                ctx.translate(tower.x, tower.y);
                
                // Draw range indicator circle (subtle)
                if (!gameState.gameRunning) {
                    ctx.strokeStyle = 'rgba(99, 102, 241, 0.15)';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([5, 5]);
                    ctx.beginPath();
                    ctx.arc(0, 0, tower.range, 0, Math.PI * 2);
                    ctx.stroke();
                    ctx.setLineDash([]);
                }
                
                ctx.rotate(tower.angle);
                
                // Tower base with gradient
                const gradient = ctx.createRadialGradient(0, 0, 0, 0, 0, 18);
                gradient.addColorStop(0, '#818cf8');
                gradient.addColorStop(1, '#6366f1');
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(0, 0, 18, 0, Math.PI * 2);
                ctx.fill();
                
                // Tower border
                ctx.strokeStyle = '#4f46e5';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // Tower barrel
                ctx.fillStyle = '#fff';
                ctx.fillRect(15, -4, 20, 8);
                ctx.strokeStyle = '#e5e7eb';
                ctx.lineWidth = 1;
                ctx.strokeRect(15, -4, 20, 8);
                
                ctx.restore();
                
                // Draw turret number label
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 13px Inter, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.strokeStyle = '#1e293b';
                ctx.lineWidth = 3;
                ctx.strokeText((idx + 1).toString(), tower.x, tower.y);
                ctx.fillText((idx + 1).toString(), tower.x, tower.y);
            });
            
            // Draw enemies
            gameState.enemies.forEach(enemy => {
                // Enemy body
                ctx.fillStyle = '#ef4444';
                ctx.beginPath();
                ctx.arc(enemy.x, enemy.y, 12, 0, Math.PI * 2);
                ctx.fill();
                
                // Health bar
                const barWidth = 30;
                const barHeight = 3;
                const healthRatio = enemy.health / enemy.maxHealth;
                
                ctx.fillStyle = '#333';
                ctx.fillRect(enemy.x - barWidth/2, enemy.y - 25, barWidth, barHeight);
                
                ctx.fillStyle = healthRatio > 0.5 ? '#22c55e' : '#ef4444';
                ctx.fillRect(enemy.x - barWidth/2, enemy.y - 25, barWidth * healthRatio, barHeight);
            });
            
            // Draw bullets
            gameState.bullets.forEach(bullet => {
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(bullet.x, bullet.y, 3, 0, Math.PI * 2);
                ctx.fill();
                
                // Trail
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(bullet.x - 5, bullet.y);
                ctx.lineTo(bullet.x, bullet.y);
                ctx.stroke();
            });
            
            // Draw particles
            gameState.particles.forEach(particle => {
                const alpha = particle.life / 30;
                ctx.fillStyle = particle.color + Math.floor(alpha * 255).toString(16).padStart(2, '0');
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, 2, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        // Update UI
        function updateUI() {
            document.getElementById('scoreValue').textContent = gameState.score;
            document.getElementById('livesValue').textContent = gameState.lives;
            document.getElementById('waveValue').textContent = gameState.wave;
            document.getElementById('enemiesValue').textContent = gameState.enemies.length;
        }

        // Game loop with delta time for smooth performance
        let lastTime = 0;
        function gameLoop(currentTime) {
            if (!ctx || !canvas) {
                // Wait for initialization
                requestAnimationFrame(gameLoop);
                return;
            }
            
            const deltaTime = currentTime - lastTime;
            lastTime = currentTime;
            
            // Limit updates to 60 FPS for consistent performance
            if (deltaTime < 16.67) {
                requestAnimationFrame(gameLoop);
                return;
            }
            
            if (gameState.gameRunning) {
                updateEnemies();
                updateTurrets();
                updateBullets();
                checkWaveComplete();
            }
            
            draw();
            
            // Update UI less frequently (every 100ms) for better performance
            if (currentTime % 100 < 16.67) {
                updateUI();
            }
            
            requestAnimationFrame(gameLoop);
        }

        function resetGame() {
            gameState = {
                score: 0,
                lives: 20,
                wave: 1,
                enemiesLeft: 0,
                gameRunning: false,
                isSpawning: false,
                turretCSS: '',
                turrets: [],
                enemies: [],
                bullets: [],
                particles: [],
                lastFrameTime: 0,
                currentPath: WAVE_PATHS[1] // Start with wave 1 path
            };
            document.getElementById('cssInput').value = '';
            document.getElementById('startWaveBtn').disabled = true;
            
            // Reset hint to wave 1
            const hintBox = document.getElementById('waveHint');
            if (hintBox) {
                hintBox.innerHTML = WAVE_HINTS[1];
            }
            
            updateUI();
            draw();
        }

        // Initialize game when DOM is ready
        function initializeGame() {
            // Initialize canvas
            canvas = document.getElementById('gameCanvas');
            if (!canvas) {
                console.error('Canvas element not found!');
                return;
            }
            
            ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Could not get canvas context!');
                return;
            }
            
            // Show Example Button - helps students learn
            const showExampleBtn = document.getElementById('showExampleBtn');
            if (showExampleBtn) {
                showExampleBtn.addEventListener('click', () => {
                    const waveNum = ((gameState.wave - 1) % 5) + 1;
                    const example = WAVE_EXAMPLES[waveNum];
                    
                    // Get wave-specific explanation
                    let explanation = '';
                    switch(waveNum) {
                        case 1:
                            explanation = `📚 Wave 1 - Learn justify-content (Horizontal Spacing)\n\n` +
                                        `This example uses:\n` +
                                        `• display: flex - Enables flexbox layout\n` +
                                        `• justify-content: space-around - Spreads turrets evenly with space around each\n` +
                                        `• align-items: center - Positions turrets in the middle vertically\n\n` +
                                        `💡 Try these alternatives:\n` +
                                        `- space-between (edges, no outer space)\n` +
                                        `- space-evenly (equal space everywhere)\n` +
                                        `- flex-start (left side)\n` +
                                        `- flex-end (right side)`;
                            break;
                        case 2:
                            explanation = `📚 Wave 2 - Learn align-items (Vertical Positioning)\n\n` +
                                        `This example uses:\n` +
                                        `• justify-content: space-between - Spreads turrets from edge to edge\n` +
                                        `• align-items: flex-start - Places turrets at the TOP of the canvas\n\n` +
                                        `💡 The zigzag path requires vertical positioning!\n` +
                                        `Try these:\n` +
                                        `- flex-start (top)\n` +
                                        `- center (middle)\n` +
                                        `- flex-end (bottom)\n\n` +
                                        `🎯 Tip: Enemies travel horizontally at the top and bottom!`;
                            break;
                        case 3:
                            explanation = `📚 Wave 3 - Combine Properties (Strategic Placement)\n\n` +
                                        `This example combines:\n` +
                                        `• justify-content: center - Groups turrets in the CENTER horizontally\n` +
                                        `• align-items: flex-end - Places them at the BOTTOM\n` +
                                        `• gap: 60px - Adds 60px spacing between each turret\n\n` +
                                        `💡 This creates a cluster at the bottom-center!\n` +
                                        `Experiment with different combinations to cover the wide curves.`;
                            break;
                        case 4:
                            explanation = `📚 Wave 4 - Master gap (Precise Spacing)\n\n` +
                                        `This example focuses on spacing:\n` +
                                        `• gap: 100px - Creates LARGE spacing between turrets\n` +
                                        `• justify-content: flex-start - Starts from the left\n` +
                                        `• align-items: center - Middle vertical position\n\n` +
                                        `💡 The gap property prevents turrets from clustering!\n` +
                                        `Try different gap values:\n` +
                                        `- 50px (tight spacing)\n` +
                                        `- 100px (medium spacing)\n` +
                                        `- 150px (wide spacing)`;
                            break;
                        case 5:
                            explanation = `📚 Wave 5 - Master Challenge (All Properties)\n\n` +
                                        `This example uses ALL properties:\n` +
                                        `• justify-content: space-around\n` +
                                        `• align-items: flex-start\n` +
                                        `• gap: 80px\n\n` +
                                        `💡 The complex path requires strategic thinking!\n` +
                                        `Consider:\n` +
                                        `- Where are the LONGEST path segments?\n` +
                                        `- Where do enemies move SLOWLY (corners)?\n` +
                                        `- How can turrets OVERLAP ranges for maximum damage?\n\n` +
                                        `🏆 This is your final test - think strategically!`;
                            break;
                    }
                    
                    // Fill in the CSS
                    document.getElementById('cssInput').value = example;
                    
                    // Show detailed explanation
                    aprenderAlert(explanation + '\n\n✏️ CSS has been filled in! Click "Apply CSS" to see the placement.');
                });
            }

            // Event listeners
            document.getElementById('applyBtn').addEventListener('click', applyCSS);
            document.getElementById('startWaveBtn').addEventListener('click', startWave);
            document.getElementById('resetBtn').addEventListener('click', resetGame);

            // Initialize game - path already set in gameState
            updateUI();
            draw();
            gameLoop();
        }
        
        // Run initialization with a small delay to ensure everything is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(initializeGame, 100);
            });
        } else {
            // DOM already loaded, run with small delay
            setTimeout(initializeGame, 100);
        }

        // Save score on page unload if logged in
        const userId = <?php echo json_encode($user_id); ?>;
        window.addEventListener('beforeunload', () => {
            if (gameState.score > 0 && userId) {
                fetch('api/game-scores.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        score: gameState.score,
                        wave: gameState.wave,
                        userId: userId
                    })
                }).catch(() => {});
            }
        });
    </script>

    <?php if (is_logged_in()): ?>
    <!-- Notification Popup (only for logged-in users) -->
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
    <?php endif; ?>
            
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

    <?php include('../includes/legal_modals.php'); ?>

    <?php if (is_logged_in()): ?>
    <script>
  // Notification system (only for logged-in users)
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
      if(!res.ok) return; const data = await res.json();
      
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
</html>
