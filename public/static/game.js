'use strict';

// Game Configuration
const GAME_CONFIG = {
    CANVAS_WIDTH: 900,
    CANVAS_HEIGHT: 600,
    GRID_SIZE: 40,
    TOWER_RADIUS: 15,
    ENEMY_RADIUS: 8,
    BULLET_RADIUS: 3,
    TOWER_SPEED: 3,
    ENEMY_SPEED: 1,
    BULLET_SPEED: 8,
    WAVE_DELAY: 3000,
    SPAWN_DELAY: 1000,
    MAX_WAVES: 13,
    INITIAL_LIVES: 20
};

// Game State
class GameState {
    constructor() {
        this.score = 0;
        this.lives = GAME_CONFIG.INITIAL_LIVES;
        this.wave = 1;
        this.enemiesLeft = 0;
        this.gameRunning = false;
        this.gamePaused = false;
        this.selectedTower = null;
        this.selectedTowerType = 'basic';
        this.waveInProgress = false;
        this.spawningComplete = false;
        this.gameOver = false;
        this.gameWon = false;
        
        // Game objects
        this.towers = [];
        this.enemies = [];
        this.bullets = [];
        this.particles = [];
        
        // Tower management
        this.towerLimits = {
            basic: 0,
            rapid: 0,
            heavy: 0,
            sniper: 0,
            laser: 0,
            missile: 0
        };
        this.maxTowers = 2; // Starting limit
        
        // Path for enemies (changes per wave)
        this.path = this.generatePath(1);
        
        // Tower types with unlock waves
        this.towerTypes = {
            basic: { 
                damage: 25, range: 80, fireRate: 1000, cost: 50, color: '#10b981',
                unlockWave: 1, description: 'Balanced tower with good damage and range'
            },
            rapid: { 
                damage: 15, range: 60, fireRate: 400, cost: 75, color: '#f59e0b',
                unlockWave: 3, description: 'Fast firing tower with lower damage'
            },
            heavy: { 
                damage: 60, range: 100, fireRate: 2500, cost: 120, color: '#ef4444',
                unlockWave: 5, description: 'High damage tower with slow firing rate'
            },
            sniper: {
                damage: 120, range: 150, fireRate: 3000, cost: 200, color: '#8b5cf6',
                unlockWave: 7, description: 'Long range tower with high damage'
            },
            laser: {
                damage: 35, range: 90, fireRate: 800, cost: 150, color: '#06b6d4',
                unlockWave: 9, description: 'Energy weapon that pierces through enemies'
            },
            missile: {
                damage: 80, range: 120, fireRate: 2000, cost: 250, color: '#f97316',
                unlockWave: 11, description: 'Explosive weapon with area damage'
            }
        };
        
        // Enhanced enemy types
        this.enemyTypes = {
            basic: { health: 50, speed: 1, reward: 10, color: '#ef4444', size: 1 },
            fast: { health: 30, speed: 2, reward: 15, color: '#f59e0b', size: 0.8 },
            tank: { health: 200, speed: 0.6, reward: 40, color: '#6b7280', size: 1.3 },
            flyer: { health: 25, speed: 2.5, reward: 20, color: '#8b5cf6', size: 0.7 },
            boss: { health: 500, speed: 0.4, reward: 100, color: '#dc2626', size: 2 }
        };
        
        // Wave configurations
        this.waveConfigs = this.generateWaveConfigs();
    }
    
    generateWaveConfigs() {
        return {
            1: { name: 'Training Ground', enemies: ['basic'], count: 6, spawnDelay: 1500 },
            2: { name: 'First Challenge', enemies: ['basic', 'fast'], count: 10, spawnDelay: 1200 },
            3: { name: 'Mixed Forces', enemies: ['basic', 'fast'], count: 14, spawnDelay: 1000 },
            4: { name: 'Heavy Assault', enemies: ['basic', 'fast', 'tank'], count: 16, spawnDelay: 900 },
            5: { name: 'Armored Division', enemies: ['basic', 'fast', 'tank'], count: 18, spawnDelay: 800 },
            6: { name: 'Aerial Threat', enemies: ['basic', 'fast', 'tank', 'flyer'], count: 20, spawnDelay: 700 },
            7: { name: 'Elite Forces', enemies: ['fast', 'tank', 'flyer'], count: 22, spawnDelay: 600 },
            8: { name: 'Shadow Legion', enemies: ['tank', 'flyer', 'basic'], count: 25, spawnDelay: 550 },
            9: { name: 'Storm Troopers', enemies: ['fast', 'flyer', 'tank'], count: 28, spawnDelay: 500 },
            10: { name: 'Inferno Army', enemies: ['tank', 'flyer', 'fast'], count: 32, spawnDelay: 450 },
            11: { name: 'Cosmic Warriors', enemies: ['flyer', 'tank', 'basic'], count: 35, spawnDelay: 400 },
            12: { name: 'Final Assault', enemies: ['tank', 'flyer', 'basic', 'fast'], count: 40, spawnDelay: 350 },
            13: { name: 'Boss Battle', enemies: ['boss', 'tank', 'flyer'], count: 5, spawnDelay: 1500 }
        };
    }
    
    generatePath(wave) {
        const path = [];
        const startY = GAME_CONFIG.CANVAS_HEIGHT / 2;
        const segments = 12;
        const segmentWidth = GAME_CONFIG.CANVAS_WIDTH / segments;
        
        // Different path patterns based on wave - more dramatic and varied
        for (let i = 0; i <= segments; i++) {
            const x = i * segmentWidth;
            let y = startY;
            
            switch (wave) {
                case 1: // Straight path - easy start
                    break;
                case 2: // Gentle wave
                    y += Math.sin(i * 0.3) * 30;
                    break;
                case 3: // S-curve
                    y += Math.sin(i * 0.5) * 50;
                    break;
                case 4: // Zigzag pattern
                    y += Math.sin(i * 0.8) * 60;
                    break;
                case 5: // Double wave
                    y += Math.sin(i * 0.4) * 40 + Math.sin(i * 1.2) * 20;
                    break;
                case 6: // Sharp turns
                    y += Math.sin(i * 0.7) * 80;
                    break;
                case 7: // Complex curve
                    y += Math.sin(i * 0.6) * 70 + Math.cos(i * 0.4) * 30;
                    break;
                case 8: // Extreme S-curve
                    y += Math.sin(i * 0.5) * 100;
                    break;
                case 9: // Double spiral
                    y += Math.sin(i * 0.8) * 60 + Math.cos(i * 0.6) * 40;
                    break;
                case 10: // Chaotic pattern
                    y += Math.sin(i * 1.0) * 90 + Math.cos(i * 0.8) * 50;
                    break;
                case 11: // Extreme curves
                    y += Math.sin(i * 0.9) * 120;
                    break;
                case 12: // Final challenge - complex
                    y += Math.sin(i * 1.2) * 100 + Math.cos(i * 0.7) * 60;
                    break;
                case 13: // Boss battle - challenging but fair
                    y += Math.sin(i * 0.8) * 80;
                    break;
            }
            
            // Keep path within bounds
            y = Math.max(40, Math.min(GAME_CONFIG.CANVAS_HEIGHT - 40, y));
            path.push({ x, y });
        }
        
        return path;
    }
    
    updateTowerLimits() {
        // Update tower limits based on current wave
        this.maxTowers = Math.min(15, 2 + Math.floor(this.wave / 2));
        
        // Reset tower limits
        Object.keys(this.towerLimits).forEach(type => {
            this.towerLimits[type] = 0;
        });
        
        // Set limits based on wave
        const limits = {
            1: { basic: 2 },
            2: { basic: 3 },
            3: { basic: 3, rapid: 1 },
            4: { basic: 4, rapid: 2 },
            5: { basic: 4, rapid: 2, heavy: 1 },
            6: { basic: 5, rapid: 3, heavy: 2 },
            7: { basic: 5, rapid: 3, heavy: 2, sniper: 1 },
            8: { basic: 6, rapid: 4, heavy: 3, sniper: 2 },
            9: { basic: 6, rapid: 4, heavy: 3, sniper: 2, laser: 1 },
            10: { basic: 7, rapid: 5, heavy: 4, sniper: 3, laser: 2 },
            11: { basic: 7, rapid: 5, heavy: 4, sniper: 3, laser: 2, missile: 1 },
            12: { basic: 8, rapid: 6, heavy: 5, sniper: 4, laser: 3, missile: 2 },
            13: { basic: 10, rapid: 8, heavy: 6, sniper: 5, laser: 4, missile: 3 }
        };
        
        const waveLimits = limits[this.wave] || limits[13];
        Object.assign(this.towerLimits, waveLimits);
    }
}

// Tower Class
class Tower {
    constructor(x, y, type) {
        this.x = x;
        this.y = y;
        this.type = type;
        const towerType = gameState.towerTypes[type];
        this.damage = towerType.damage;
        this.range = towerType.range;
        this.fireRate = towerType.fireRate;
        this.color = towerType.color;
        this.cost = towerType.cost;
        this.lastFireTime = 0;
        this.angle = 0;
        this.selected = false;
        this.isMoving = false;
        this.targetX = x;
        this.targetY = y;
        this.level = 1;
    }
    
    update() {
        // Handle tower movement
        if (this.isMoving) {
            const dx = this.targetX - this.x;
            const dy = this.targetY - this.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance > 2) {
                this.x += (dx / distance) * GAME_CONFIG.TOWER_SPEED;
                this.y += (dy / distance) * GAME_CONFIG.TOWER_SPEED;
            } else {
                this.x = this.targetX;
                this.y = this.targetY;
                this.isMoving = false;
            }
        }
        
        // Find target
        const target = this.findTarget();
        if (target) {
            this.angle = Math.atan2(target.y - this.y, target.x - this.x);
            
            // Fire at target
            const now = Date.now();
            if (now - this.lastFireTime > this.fireRate) {
                this.fire(target);
                this.lastFireTime = now;
            }
        }
    }
    
    findTarget() {
        let closestEnemy = null;
        let closestDistance = this.range;
        
        for (const enemy of gameState.enemies) {
            const dx = enemy.x - this.x;
            const dy = enemy.y - this.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance <= this.range && distance < closestDistance) {
                closestEnemy = enemy;
                closestDistance = distance;
            }
        }
        
        return closestEnemy;
    }
    
    fire(target) {
        // Special fire mechanics for different tower types
        switch (this.type) {
            case 'laser':
                // Laser pierces through enemies
                this.fireLaser(target);
                break;
            case 'missile':
                // Missile with area damage
                this.fireMissile(target);
                break;
            case 'sniper':
                // High damage single shot
                this.fireSniper(target);
                break;
            default:
                // Standard bullet
                const bullet = new Bullet(this.x, this.y, target, this.damage, this.type);
                gameState.bullets.push(bullet);
                this.createMuzzleFlash();
                break;
        }
    }
    
    fireLaser(target) {
        // Create laser beam effect
        const laser = new Laser(this.x, this.y, target, this.damage);
        gameState.bullets.push(laser);
        this.createLaserEffect();
    }
    
    fireMissile(target) {
        // Create missile with explosion
        const missile = new Missile(this.x, this.y, target, this.damage);
        gameState.bullets.push(missile);
        this.createMuzzleFlash();
    }
    
    fireSniper(target) {
        // High damage bullet with special effect
        const bullet = new Bullet(this.x, this.y, target, this.damage, this.type);
        gameState.bullets.push(bullet);
        this.createSniperEffect();
    }
    
    createMuzzleFlash() {
        for (let i = 0; i < 5; i++) {
            const particle = new Particle(
                this.x + (Math.random() - 0.5) * 10,
                this.y + (Math.random() - 0.5) * 10,
                Math.random() * Math.PI * 2,
                2 + Math.random() * 3,
                this.color,
                20
            );
            gameState.particles.push(particle);
        }
    }
    
    createLaserEffect() {
        for (let i = 0; i < 8; i++) {
            const particle = new Particle(
                this.x + (Math.random() - 0.5) * 15,
                this.y + (Math.random() - 0.5) * 15,
                Math.random() * Math.PI * 2,
                3 + Math.random() * 4,
                '#06b6d4',
                30
            );
            gameState.particles.push(particle);
        }
    }
    
    createSniperEffect() {
        for (let i = 0; i < 10; i++) {
            const particle = new Particle(
                this.x + (Math.random() - 0.5) * 12,
                this.y + (Math.random() - 0.5) * 12,
                Math.random() * Math.PI * 2,
                2 + Math.random() * 5,
                '#8b5cf6',
                25
            );
            gameState.particles.push(particle);
        }
    }
    
    move(dx, dy) {
        const newX = Math.max(GAME_CONFIG.TOWER_RADIUS, 
                    Math.min(GAME_CONFIG.CANVAS_WIDTH - GAME_CONFIG.TOWER_RADIUS, this.x + dx));
        const newY = Math.max(GAME_CONFIG.TOWER_RADIUS, 
                    Math.min(GAME_CONFIG.CANVAS_HEIGHT - GAME_CONFIG.TOWER_RADIUS, this.y + dy));
        
        this.targetX = newX;
        this.targetY = newY;
        this.isMoving = true;
    }
    
    rotate() {
        this.angle += Math.PI / 4; // 45 degree rotation
    }
    
    draw(ctx) {
        // Draw tower
        ctx.save();
        ctx.translate(this.x, this.y);
        ctx.rotate(this.angle);
        
        // Tower body
        ctx.fillStyle = this.color;
        ctx.beginPath();
        ctx.arc(0, 0, GAME_CONFIG.TOWER_RADIUS, 0, Math.PI * 2);
        ctx.fill();
        
        // Tower barrel
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(GAME_CONFIG.TOWER_RADIUS - 5, -3, 15, 6);
        
        // Selection indicator
        if (this.selected) {
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(0, 0, GAME_CONFIG.TOWER_RADIUS + 5, 0, Math.PI * 2);
            ctx.stroke();
        }
        
        // Range indicator
        if (this.selected) {
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.arc(0, 0, this.range, 0, Math.PI * 2);
            ctx.stroke();
        }
        
        ctx.restore();
    }
}

// Enemy Class
class Enemy {
    constructor(type = 'basic') {
        this.type = type;
        const enemyType = gameState.enemyTypes[type];
        this.maxHealth = enemyType.health;
        this.health = this.maxHealth;
        this.speed = enemyType.speed;
        this.reward = enemyType.reward;
        this.color = enemyType.color;
        this.size = enemyType.size || 1;
        
        // Apply progressive wave difficulty scaling
        const waveMultiplier = 1 + (gameState.wave - 1) * 0.3;
        const speedMultiplier = 1 + (gameState.wave - 1) * 0.15;
        
        this.maxHealth = Math.floor(this.maxHealth * waveMultiplier);
        this.health = this.maxHealth;
        this.speed = this.speed * speedMultiplier;
        this.reward = Math.floor(this.reward * waveMultiplier);
        
        // Position on path
        this.pathIndex = 0;
        this.x = gameState.path[0].x;
        this.y = gameState.path[0].y;
        this.progress = 0;
        
        this.alive = true;
        this.slowEffect = 0; // For slow effects
        this.lastSlowTime = 0;
    }
    
    update() {
        if (!this.alive) return;
        
        // Apply slow effect
        let currentSpeed = this.speed;
        if (this.slowEffect > 0 && Date.now() - this.lastSlowTime < 2000) {
            currentSpeed *= 0.5; // 50% speed reduction
        } else {
            this.slowEffect = 0;
        }
        
        // Move along path
        this.progress += currentSpeed * 0.5;
        
        if (this.pathIndex < gameState.path.length - 1) {
            const current = gameState.path[this.pathIndex];
            const next = gameState.path[this.pathIndex + 1];
            const segmentLength = Math.sqrt(
                Math.pow(next.x - current.x, 2) + Math.pow(next.y - current.y, 2)
            );
            
            if (this.progress >= segmentLength) {
                this.progress = 0;
                this.pathIndex++;
            } else {
                const ratio = this.progress / segmentLength;
                this.x = current.x + (next.x - current.x) * ratio;
                this.y = current.y + (next.y - current.y) * ratio;
            }
        } else {
            // Reached the end
            this.alive = false;
            gameState.lives--;
            updateUI();
            
            // Check for game over
            if (gameState.lives <= 0 && !gameState.gameOver) {
                console.log('Lives reached 0, triggering game over');
                gameState.lives = 0; // Ensure it doesn't go negative
                updateUI();
                gameOver();
            }
        }
    }
    
    takeDamage(damage, damageType = 'normal') {
        this.health -= damage;
        
        // Apply special effects based on damage type
        if (damageType === 'laser') {
            // Laser pierces and applies slow effect
            this.slowEffect = 1;
            this.lastSlowTime = Date.now();
        } else if (damageType === 'missile') {
            // Missile has area damage effect (handled in explosion)
        }
        
        if (this.health <= 0) {
            this.alive = false;
            gameState.score += this.reward;
            updateUI();
            
            // Create death particles based on enemy type
            const particleCount = this.type === 'boss' ? 20 : 8;
            for (let i = 0; i < particleCount; i++) {
                const particle = new Particle(
                    this.x + (Math.random() - 0.5) * 20,
                    this.y + (Math.random() - 0.5) * 20,
                    Math.random() * Math.PI * 2,
                    1 + Math.random() * 2,
                    this.color,
                    30
                );
                gameState.particles.push(particle);
            }
            
            // Special boss death effect
            if (this.type === 'boss') {
                this.createBossDeathEffect();
            }
        }
    }
    
    createBossDeathEffect() {
        // Create large explosion effect for boss
        for (let i = 0; i < 30; i++) {
            const particle = new Particle(
                this.x + (Math.random() - 0.5) * 40,
                this.y + (Math.random() - 0.5) * 40,
                Math.random() * Math.PI * 2,
                2 + Math.random() * 4,
                '#ffd700',
                60
            );
            gameState.particles.push(particle);
        }
    }
    
    draw(ctx) {
        const radius = GAME_CONFIG.ENEMY_RADIUS * this.size;
        
        // Enemy body with size scaling
        ctx.fillStyle = this.color;
        ctx.beginPath();
        ctx.arc(this.x, this.y, radius, 0, Math.PI * 2);
        ctx.fill();
        
        // Special effects for different enemy types
        if (this.type === 'boss') {
            // Boss aura effect
            ctx.strokeStyle = '#ffd700';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(this.x, this.y, radius + 5, 0, Math.PI * 2);
            ctx.stroke();
        }
        
        if (this.slowEffect > 0) {
            // Slow effect indicator
            ctx.fillStyle = 'rgba(0, 191, 255, 0.5)';
            ctx.beginPath();
            ctx.arc(this.x, this.y, radius + 3, 0, Math.PI * 2);
            ctx.fill();
        }
        
        // Health bar (larger for bosses)
        const barWidth = radius * 2;
        const barHeight = this.type === 'boss' ? 6 : 4;
        const healthRatio = this.health / this.maxHealth;
        
        ctx.fillStyle = '#333333';
        ctx.fillRect(this.x - barWidth/2, this.y - radius - 12, barWidth, barHeight);
        
        ctx.fillStyle = healthRatio > 0.5 ? '#22c55e' : healthRatio > 0.25 ? '#f59e0b' : '#ef4444';
        ctx.fillRect(this.x - barWidth/2, this.y - radius - 12, barWidth * healthRatio, barHeight);
    }
}

// Bullet Class
class Bullet {
    constructor(x, y, target, damage, type = 'normal') {
        this.x = x;
        this.y = y;
        this.target = target;
        this.damage = damage;
        this.type = type;
        this.speed = GAME_CONFIG.BULLET_SPEED;
        
        const dx = target.x - x;
        const dy = target.y - y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        this.vx = (dx / distance) * this.speed;
        this.vy = (dy / distance) * this.speed;
        
        this.alive = true;
    }
    
    update() {
        if (!this.alive) return;
        
        this.x += this.vx;
        this.y += this.vy;
        
        // Check collision with target
        if (this.target && this.target.alive) {
            const dx = this.target.x - this.x;
            const dy = this.target.y - this.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            const targetRadius = GAME_CONFIG.ENEMY_RADIUS * this.target.size;
            
            if (distance < targetRadius) {
                this.target.takeDamage(this.damage, this.type);
                this.alive = false;
                
                // Create hit effect
                for (let i = 0; i < 3; i++) {
                    const particle = new Particle(
                        this.x + (Math.random() - 0.5) * 10,
                        this.y + (Math.random() - 0.5) * 10,
                        Math.random() * Math.PI * 2,
                        1 + Math.random() * 2,
                        '#ffffff',
                        15
                    );
                    gameState.particles.push(particle);
                }
            }
        }
        
        // Remove if out of bounds
        if (this.x < 0 || this.x > GAME_CONFIG.CANVAS_WIDTH || 
            this.y < 0 || this.y > GAME_CONFIG.CANVAS_HEIGHT) {
            this.alive = false;
        }
    }
    
    draw(ctx) {
        // Different bullet styles based on type
        switch (this.type) {
            case 'sniper':
                ctx.fillStyle = '#8b5cf6';
                ctx.beginPath();
                ctx.arc(this.x, this.y, GAME_CONFIG.BULLET_RADIUS * 1.5, 0, Math.PI * 2);
                ctx.fill();
                break;
            default:
                ctx.fillStyle = '#ffffff';
                ctx.beginPath();
                ctx.arc(this.x, this.y, GAME_CONFIG.BULLET_RADIUS, 0, Math.PI * 2);
                ctx.fill();
                break;
        }
    }
}

// Laser Class (pierces through enemies)
class Laser extends Bullet {
    constructor(x, y, target, damage) {
        super(x, y, target, damage, 'laser');
        this.piercedEnemies = [];
        this.maxPierce = 3;
    }
    
    update() {
        if (!this.alive) return;
        
        this.x += this.vx;
        this.y += this.vy;
        
        // Check collision with all enemies (piercing)
        for (const enemy of gameState.enemies) {
            if (enemy.alive && !this.piercedEnemies.includes(enemy)) {
                const dx = enemy.x - this.x;
                const dy = enemy.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                const targetRadius = GAME_CONFIG.ENEMY_RADIUS * enemy.size;
                
                if (distance < targetRadius) {
                    enemy.takeDamage(this.damage, 'laser');
                    this.piercedEnemies.push(enemy);
                    
                    if (this.piercedEnemies.length >= this.maxPierce) {
                        this.alive = false;
                    }
                    
                    // Create laser hit effect
                    for (let i = 0; i < 5; i++) {
                        const particle = new Particle(
                            this.x + (Math.random() - 0.5) * 8,
                            this.y + (Math.random() - 0.5) * 8,
                            Math.random() * Math.PI * 2,
                            2 + Math.random() * 3,
                            '#06b6d4',
                            20
                        );
                        gameState.particles.push(particle);
                    }
                }
            }
        }
        
        // Remove if out of bounds
        if (this.x < 0 || this.x > GAME_CONFIG.CANVAS_WIDTH || 
            this.y < 0 || this.y > GAME_CONFIG.CANVAS_HEIGHT) {
            this.alive = false;
        }
    }
    
    draw(ctx) {
        // Laser beam effect
        ctx.strokeStyle = '#06b6d4';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(this.x - this.vx * 2, this.y - this.vy * 2);
        ctx.lineTo(this.x, this.y);
        ctx.stroke();
        
        // Laser core
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.arc(this.x, this.y, GAME_CONFIG.BULLET_RADIUS, 0, Math.PI * 2);
        ctx.fill();
    }
}

// Missile Class (area damage)
class Missile extends Bullet {
    constructor(x, y, target, damage) {
        super(x, y, target, damage, 'missile');
        this.explosionRadius = 60;
        this.hasExploded = false;
    }
    
    update() {
        if (!this.alive) return;
        
        this.x += this.vx;
        this.y += this.vy;
        
        // Check collision with target or proximity
        if (this.target && this.target.alive) {
            const dx = this.target.x - this.x;
            const dy = this.target.y - this.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < GAME_CONFIG.ENEMY_RADIUS * this.target.size + 20) {
                this.explode();
                return;
            }
        }
        
        // Remove if out of bounds
        if (this.x < 0 || this.x > GAME_CONFIG.CANVAS_WIDTH || 
            this.y < 0 || this.y > GAME_CONFIG.CANVAS_HEIGHT) {
            this.explode();
        }
    }
    
    explode() {
        if (this.hasExploded) return;
        this.hasExploded = true;
        this.alive = false;
        
        // Damage all enemies in explosion radius
        for (const enemy of gameState.enemies) {
            if (enemy.alive) {
                const dx = enemy.x - this.x;
                const dy = enemy.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < this.explosionRadius) {
                    const damageMultiplier = 1 - (distance / this.explosionRadius) * 0.5; // 50% damage at edge
                    enemy.takeDamage(Math.floor(this.damage * damageMultiplier), 'missile');
                }
            }
        }
        
        // Create explosion effect
        for (let i = 0; i < 20; i++) {
            const particle = new Particle(
                this.x + (Math.random() - 0.5) * this.explosionRadius,
                this.y + (Math.random() - 0.5) * this.explosionRadius,
                Math.random() * Math.PI * 2,
                2 + Math.random() * 4,
                '#f97316',
                40
            );
            gameState.particles.push(particle);
        }
    }
    
    draw(ctx) {
        // Missile with trail
        ctx.fillStyle = '#f97316';
        ctx.beginPath();
        ctx.arc(this.x, this.y, GAME_CONFIG.BULLET_RADIUS * 1.2, 0, Math.PI * 2);
        ctx.fill();
        
        // Missile trail
        ctx.strokeStyle = '#ff6b35';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(this.x - this.vx * 3, this.y - this.vy * 3);
        ctx.lineTo(this.x, this.y);
        ctx.stroke();
    }
}

// Particle Class
class Particle {
    constructor(x, y, angle, speed, color, life) {
        this.x = x;
        this.y = y;
        this.vx = Math.cos(angle) * speed;
        this.vy = Math.sin(angle) * speed;
        this.color = color;
        this.life = life;
        this.maxLife = life;
        this.alive = true;
    }
    
    update() {
        this.x += this.vx;
        this.y += this.vy;
        this.life--;
        
        if (this.life <= 0) {
            this.alive = false;
        }
        
        // Apply gravity
        this.vy += 0.1;
    }
    
    draw(ctx) {
        const alpha = this.life / this.maxLife;
        ctx.fillStyle = this.color + Math.floor(alpha * 255).toString(16).padStart(2, '0');
        ctx.beginPath();
        ctx.arc(this.x, this.y, 2, 0, Math.PI * 2);
        ctx.fill();
    }
}

// Global game state
let gameState;
let canvas;
let ctx;
let animationId;

// UI Helper Functions
function showGameMessage(message, type = 'info') {
    const overlay = document.querySelector('.game-overlay');
    const messageEl = document.getElementById('game-message');
    
    console.log('showGameMessage called:', message, type);
    console.log('Overlay element:', overlay);
    console.log('Message element:', messageEl);
    
    if (overlay && messageEl) {
        messageEl.textContent = message;
        messageEl.className = 'game-message ' + type;
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        console.log('Message displayed');
    } else {
        console.error('Could not find overlay or message element');
    }
}

function hideGameMessage() {
    const overlay = document.querySelector('.game-overlay');
    console.log('hideGameMessage called');
    if (overlay) {
        overlay.style.display = 'none';
        console.log('Message hidden');
    }
}

// Initialize game
function initGame() {
    console.log('Initializing game...');
    
    // Show initialization message
    showGameMessage('🎮 Initializing Game...', 'info');
    
    gameState = new GameState();
    canvas = document.getElementById('gameCanvas');
    
    if (!canvas) {
        console.error('Game canvas not found!');
        showGameMessage('❌ Error: Game canvas not found!', 'error');
        return;
    }
    
    ctx = canvas.getContext('2d');
    
    // Set canvas size
    canvas.width = GAME_CONFIG.CANVAS_WIDTH;
    canvas.height = GAME_CONFIG.CANVAS_HEIGHT;
    
    // Show canvas and hide placeholder
    canvas.style.display = 'block';
    const placeholder = document.querySelector('.canvas-placeholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    console.log('Canvas initialized:', canvas.width, 'x', canvas.height);
    
    // Set up event listeners
    setupEventListeners();
    
    // Set game as running
    gameState.gameRunning = true;
    console.log('Game state set to running');
    
    // Initialize UI
    updateUI();
    updateTowerUI();
    
    // Test canvas drawing
    ctx.fillStyle = '#1a1a2e';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#374151';
    ctx.lineWidth = 30;
    ctx.beginPath();
    ctx.moveTo(0, canvas.height / 2);
    ctx.lineTo(canvas.width, canvas.height / 2);
    ctx.stroke();
    console.log('Canvas test drawing completed');
    
    // Force initial draw to show terrain and path
    draw();
    
    // Start game loop
    gameLoop();
    
    console.log('Game initialized successfully');
    
    // Show success message
    setTimeout(() => {
        showGameMessage('✅ Game Ready! Click "Start Wave" to begin', 'success');
        setTimeout(() => hideGameMessage(), 3000);
    }, 500);
}

// Setup event listeners
function setupEventListeners() {
    // Keyboard controls
    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('keyup', handleKeyUp);
    
    // Mouse controls
    canvas.addEventListener('click', handleCanvasClick);
    
    // UI controls
    document.getElementById('start-wave').addEventListener('click', startWave);
    document.getElementById('pause-game').addEventListener('click', togglePause);
    document.getElementById('reset-game').addEventListener('click', resetGame);
    
    // Tower type selection
    document.querySelectorAll('.tower-item').forEach(option => {
        option.addEventListener('click', () => {
            if (option.classList.contains('disabled')) return;
            
            document.querySelectorAll('.tower-item').forEach(opt => opt.classList.remove('active'));
            option.classList.add('active');
            gameState.selectedTowerType = option.dataset.tower;
            console.log('Selected tower type:', gameState.selectedTowerType);
        });
    });
    
    // Instructions modal (if exists)
    const closeInstructionsBtn = document.getElementById('close-instructions');
    const startGameBtn = document.getElementById('start-game');
    
    if (closeInstructionsBtn) {
        closeInstructionsBtn.addEventListener('click', hideInstructionsModal);
    }
    
    if (startGameBtn) {
        startGameBtn.addEventListener('click', () => {
            hideInstructionsModal();
            gameState.gameRunning = true;
            console.log('Game started!');
        });
    }
}

// Handle keyboard input
function handleKeyDown(event) {
    if (!gameState.gameRunning || gameState.gamePaused) return;
    
    const selectedTower = gameState.selectedTower;
    if (!selectedTower) return;
    
    const moveDistance = 20;
    
    switch (event.code) {
        case 'KeyW':
        case 'ArrowUp':
            selectedTower.move(0, -moveDistance);
            event.preventDefault();
            break;
        case 'KeyS':
        case 'ArrowDown':
            selectedTower.move(0, moveDistance);
            event.preventDefault();
            break;
        case 'KeyA':
        case 'ArrowLeft':
            selectedTower.move(-moveDistance, 0);
            event.preventDefault();
            break;
        case 'KeyD':
        case 'ArrowRight':
            selectedTower.move(moveDistance, 0);
            event.preventDefault();
            break;
        case 'KeyR':
            selectedTower.rotate();
            event.preventDefault();
            break;
        case 'Space':
            // Manual fire
            const target = selectedTower.findTarget();
            if (target) {
                selectedTower.fire(target);
            }
            event.preventDefault();
            break;
    }
}

function handleKeyUp(event) {
    // Handle key up events if needed
}

// Handle canvas clicks
function handleCanvasClick(event) {
    if (!gameState.gameRunning || gameState.gamePaused) return;
    
    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    // Check if clicking on existing tower
    let clickedTower = null;
    for (const tower of gameState.towers) {
        const dx = x - tower.x;
        const dy = y - tower.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance <= GAME_CONFIG.TOWER_RADIUS) {
            clickedTower = tower;
            break;
        }
    }
    
    if (clickedTower) {
        // Select existing tower
        gameState.selectedTower = clickedTower;
        gameState.towers.forEach(t => t.selected = false);
        clickedTower.selected = true;
    } else {
        // Place new tower
        placeTower(x, y);
    }
}

// Place new tower
function placeTower(x, y) {
    // Check if position is valid (not on path, not too close to other towers)
    if (isValidTowerPosition(x, y)) {
        const towerType = gameState.selectedTowerType;
        
        // Check if tower type is unlocked for current wave
        if (gameState.towerTypes[towerType].unlockWave > gameState.wave) {
            showMessage(`This tower unlocks at wave ${gameState.towerTypes[towerType].unlockWave}`, 'warning');
            return;
        }
        
        // Check tower limits
        const currentCount = gameState.towers.filter(t => t.type === towerType).length;
        if (currentCount >= gameState.towerLimits[towerType]) {
            showMessage(`You can only place ${gameState.towerLimits[towerType]} ${towerType} towers`, 'warning');
            return;
        }
        
        // Check total tower limit
        if (gameState.towers.length >= gameState.maxTowers) {
            showMessage(`Maximum ${gameState.maxTowers} towers allowed`, 'warning');
            return;
        }
        
        const tower = new Tower(x, y, towerType);
        gameState.towers.push(tower);
        gameState.selectedTower = tower;
        gameState.towers.forEach(t => t.selected = false);
        tower.selected = true;
        
        updateTowerUI();
    }
}

// Check if tower position is valid
function isValidTowerPosition(x, y) {
    // Check distance from path
    for (let i = 0; i < gameState.path.length - 1; i++) {
        const current = gameState.path[i];
        const next = gameState.path[i + 1];
        
        const distance = distanceToLineSegment(x, y, current.x, current.y, next.x, next.y);
        if (distance < GAME_CONFIG.TOWER_RADIUS + 10) {
            return false;
        }
    }
    
    // Check distance from other towers
    for (const tower of gameState.towers) {
        const dx = x - tower.x;
        const dy = y - tower.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < GAME_CONFIG.TOWER_RADIUS * 2 + 10) {
            return false;
        }
    }
    
    return true;
}

// Distance from point to line segment
function distanceToLineSegment(px, py, x1, y1, x2, y2) {
    const A = px - x1;
    const B = py - y1;
    const C = x2 - x1;
    const D = y2 - y1;
    
    const dot = A * C + B * D;
    const lenSq = C * C + D * D;
    let param = -1;
    
    if (lenSq !== 0) {
        param = dot / lenSq;
    }
    
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

// Start wave
function startWave() {
    console.log('startWave called');
    console.log('gameState.waveInProgress:', gameState.waveInProgress);
    console.log('gameState.gameRunning:', gameState.gameRunning);
    
    if (gameState.waveInProgress) {
        console.log('Wave already in progress, returning');
        showGameMessage('⚠️ Wave already in progress!', 'info');
        setTimeout(() => hideGameMessage(), 2000);
        return;
    }
    
    if (!gameState.gameRunning) {
        console.log('Game not running, returning');
        showGameMessage('❌ Game not initialized! Click "Initialize Game" first', 'error');
        setTimeout(() => hideGameMessage(), 3000);
        return;
    }
    
    // Check if all waves completed
    if (gameState.wave > GAME_CONFIG.MAX_WAVES) {
        gameWon();
        return;
    }
    
    gameState.waveInProgress = true;
    gameState.spawningComplete = false;
    const waveConfig = gameState.waveConfigs[gameState.wave];
    
    console.log('Starting wave:', gameState.wave, waveConfig);
    
    if (!waveConfig) {
        gameWon();
        return;
    }
    
    gameState.enemiesLeft = waveConfig.count;
    
    // Update path for new wave
    gameState.path = gameState.generatePath(gameState.wave);
    
    // Update tower limits for new wave
    gameState.updateTowerLimits();
    
    updateUI();
    updateTowerUI();
    
    showGameMessage(`🌊 Wave ${gameState.wave}: ${waveConfig.name}`, 'info');
    setTimeout(() => hideGameMessage(), 2000);
    
    spawnEnemies(waveConfig);
}

// Spawn enemies
function spawnEnemies(waveConfig) {
    let spawned = 0;
    const { enemies, count, spawnDelay } = waveConfig;
    
    const spawnInterval = setInterval(() => {
        if (spawned >= count || !gameState.gameRunning) {
            clearInterval(spawnInterval);
            // Mark that spawning is complete
            gameState.spawningComplete = true;
            return;
        }
        
        // Choose enemy type from wave configuration
        const enemyType = enemies[Math.floor(Math.random() * enemies.length)];
        
        const enemy = new Enemy(enemyType);
        gameState.enemies.push(enemy);
        spawned++;
        
        gameState.enemiesLeft--;
        updateUI();
    }, spawnDelay);
}

// Check if wave is complete
function checkWaveCompletion() {
    // Check if all enemies are defeated and spawning is complete
    if (gameState.enemies.length === 0 && gameState.spawningComplete && gameState.waveInProgress) {
        completeWave();
    }
}

// Complete wave
function completeWave() {
    gameState.waveInProgress = false;
    gameState.enemiesLeft = 0;
    
    // Check if all waves completed
    if (gameState.wave >= GAME_CONFIG.MAX_WAVES) {
        gameWon();
        return;
    }
    
    gameState.wave++;
    updateUI();
    updateTowerUI();
    
    showMessage(`Wave ${gameState.wave - 1} Complete!`, 'success');
    
    // Show next wave info
    const nextWaveConfig = gameState.waveConfigs[gameState.wave];
    if (nextWaveConfig) {
        setTimeout(() => {
            showMessage(`Next: Wave ${gameState.wave} - ${nextWaveConfig.name}`, 'info');
        }, 2000);
    }
    
    // Auto-start next wave after delay
    setTimeout(() => {
        if (gameState.gameRunning && !gameState.gameOver && !gameState.gameWon) {
            startWave();
        }
    }, GAME_CONFIG.WAVE_DELAY);
}

// Toggle pause
function togglePause() {
    gameState.gamePaused = !gameState.gamePaused;
    const button = document.getElementById('pause-game');
    button.textContent = gameState.gamePaused ? 'Resume' : 'Pause';
}

// Reset game
function resetGame() {
    // Close any modals
    closeGameOverModal();
    closeVictoryModal();
    
    // Reset game state
    gameState = new GameState();
    gameState.gameRunning = true;
    
    // Update UI
    updateUI();
    updateTowerUI();
    
    // Redraw canvas
    draw();
    
    showMessage('Game Reset! Click "Start Wave" to begin', 'info');
}

// Game over
function gameOver() {
    gameState.gameOver = true;
    gameState.gameRunning = false;
    gameState.waveInProgress = false;
    
    // Show game over modal
    showGameOverModal();
    
    // Save score if user is logged in
    if (window.GameConfig && window.GameConfig.userId) {
        saveScore(gameState.score, gameState.wave);
    }
}

// Show game over modal
function showGameOverModal() {
    console.log('Showing game over modal');
    
    const modalHTML = `
        <div class="game-modal" id="game-over-modal" style="display: flex;">
            <div class="game-modal-content">
                <div class="game-modal-header defeat">
                    <h2>💀 GAME OVER!</h2>
                </div>
                <div class="game-modal-body">
                    <div class="game-over-stats">
                        <div class="stat-row">
                            <span class="stat-label">Final Score:</span>
                            <span class="stat-value">${gameState.score}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Wave Reached:</span>
                            <span class="stat-value">${gameState.wave}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Towers Placed:</span>
                            <span class="stat-value">${gameState.towers.length}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Enemies Defeated:</span>
                            <span class="stat-value">${Math.floor(gameState.score / 10)}</span>
                        </div>
                    </div>
                    <p class="game-over-message">💔 Your base was destroyed! All lives lost.</p>
                    <p class="game-over-message" style="font-size: 0.9rem; margin-top: 10px;">Don't give up! Try different tower strategies and placements.</p>
                </div>
                <div class="game-modal-footer">
                    <button class="btn btn-primary" onclick="closeGameOverModal(); resetGame();">🔄 Play Again</button>
                    <button class="btn btn-secondary" onclick="window.location.href='leaderboard.php'">🏆 Leaderboard</button>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('game-over-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Play sound effect if available
    playGameOverSound();
    
    console.log('Game over modal displayed');
}

// Play game over sound (optional)
function playGameOverSound() {
    try {
        // You can add a sound effect here if you have audio files
        // const audio = new Audio('sounds/game-over.mp3');
        // audio.play();
    } catch (e) {
        console.log('Sound not available');
    }
}

// Close game over modal
function closeGameOverModal() {
    const modal = document.getElementById('game-over-modal');
    if (modal) {
        modal.remove();
    }
}

// Game won
function gameWon() {
    gameState.gameWon = true;
    gameState.gameRunning = false;
    gameState.waveInProgress = false;
    
    // Show victory modal
    showVictoryModal();
    
    // Save final score if user is logged in
    if (window.GameConfig && window.GameConfig.userId) {
        saveScore(gameState.score, gameState.wave);
    }
}

// Show victory modal
function showVictoryModal() {
    const modalHTML = `
        <div class="game-modal" id="victory-modal" style="display: flex;">
            <div class="game-modal-content">
                <div class="game-modal-header victory">
                    <h2>🎉 Victory!</h2>
                </div>
                <div class="game-modal-body">
                    <div class="game-over-stats">
                        <div class="stat-row">
                            <span class="stat-label">Final Score:</span>
                            <span class="stat-value">${gameState.score}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Waves Completed:</span>
                            <span class="stat-value">${GAME_CONFIG.MAX_WAVES}</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Lives Remaining:</span>
                            <span class="stat-value">${gameState.lives}</span>
                        </div>
                    </div>
                    <p class="game-over-message">Congratulations! You've completed all waves and defended your base!</p>
                </div>
                <div class="game-modal-footer">
                    <button class="btn btn-primary" onclick="closeVictoryModal(); resetGame();">Play Again</button>
                    <button class="btn btn-secondary" onclick="window.location.href='leaderboard.php'">View Leaderboard</button>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('victory-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Close victory modal
function closeVictoryModal() {
    const modal = document.getElementById('victory-modal');
    if (modal) {
        modal.remove();
    }
}

// Save score
async function saveScore(score, wave) {
    try {
        const response = await fetch('/api/game-scores.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                score: score,
                wave: wave,
                userId: window.GameConfig.userId
            })
        });
        
        if (response.ok) {
            console.log('Score saved successfully');
        }
    } catch (error) {
        console.error('Failed to save score:', error);
    }
}

// Update UI
function updateUI() {
    document.getElementById('score').textContent = gameState.score;
    
    const livesElement = document.getElementById('lives');
    livesElement.textContent = gameState.lives;
    
    // Add visual warning when lives are low
    if (gameState.lives <= 0) {
        livesElement.className = 'stat-value red blink';
    } else if (gameState.lives <= 5) {
        livesElement.className = 'stat-value red pulse';
    } else if (gameState.lives <= 10) {
        livesElement.className = 'stat-value orange';
    } else {
        livesElement.className = 'stat-value green';
    }
    
    document.getElementById('wave').textContent = gameState.wave;
    document.getElementById('enemies-left').textContent = gameState.enemiesLeft;
}

// Update tower UI
function updateTowerUI() {
    const towerItems = document.querySelectorAll('.tower-item');
    towerItems.forEach(item => {
        const towerType = item.dataset.tower;
        const towerInfo = gameState.towerTypes[towerType];
        const currentCount = gameState.towers.filter(t => t.type === towerType).length;
        const limit = gameState.towerLimits[towerType] || 0;
        const nameSpan = item.querySelector('.tower-name');
        
        // Update unlock status
        if (towerInfo.unlockWave > gameState.wave) {
            // Tower is locked
            item.classList.add('disabled');
            item.classList.remove('active');
            if (nameSpan) {
                nameSpan.textContent = `${towerType} (Wave ${towerInfo.unlockWave})`;
            }
        } else {
            // Tower is unlocked
            item.classList.remove('disabled');
            if (nameSpan) {
                nameSpan.textContent = `${towerType} (${currentCount}/${limit})`;
            }
            
            // Check if limit reached
            if (limit > 0 && currentCount >= limit) {
                item.style.opacity = '0.6';
            } else {
                item.style.opacity = '1';
            }
        }
    });
}

// Show message
function showMessage(text, type = 'info') {
    const messageEl = document.getElementById('game-message');
    messageEl.textContent = text;
    messageEl.className = `game-message show ${type}`;
    
    setTimeout(() => {
        messageEl.classList.remove('show');
    }, 3000);
}

// Show instructions modal
function showInstructionsModal() {
    const modal = document.getElementById('instructions-modal');
    if (modal) {
        modal.classList.add('show');
    }
}

// Hide instructions modal
function hideInstructionsModal() {
    const modal = document.getElementById('instructions-modal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Main game loop
function gameLoop() {
    // Always draw the game, even when paused or not running
    draw();
    
    // Only update game logic when running and not paused
    if (!gameState.gamePaused && gameState.gameRunning) {
        update();
    }
    
    animationId = requestAnimationFrame(gameLoop);
}

// Update game objects
function update() {
    // Update towers
    for (const tower of gameState.towers) {
        tower.update();
    }
    
    // Update enemies
    for (let i = gameState.enemies.length - 1; i >= 0; i--) {
        const enemy = gameState.enemies[i];
        enemy.update();
        
        if (!enemy.alive) {
            gameState.enemies.splice(i, 1);
        }
    }
    
    // Check wave completion after enemy updates
    if (gameState.waveInProgress && gameState.enemies.length === 0 && gameState.spawningComplete) {
        checkWaveCompletion();
    }
    
    // Update bullets
    for (let i = gameState.bullets.length - 1; i >= 0; i--) {
        const bullet = gameState.bullets[i];
        bullet.update();
        
        if (!bullet.alive) {
            gameState.bullets.splice(i, 1);
        }
    }
    
    // Update particles
    for (let i = gameState.particles.length - 1; i >= 0; i--) {
        const particle = gameState.particles[i];
        particle.update();
        
        if (!particle.alive) {
            gameState.particles.splice(i, 1);
        }
    }
}

// Draw game
function draw() {
    if (!gameState || !ctx) {
        console.warn('Game state or context not ready for drawing');
        return;
    }
    
    // Clear canvas with simple background
    ctx.fillStyle = '#1a1a2e';
    ctx.fillRect(0, 0, GAME_CONFIG.CANVAS_WIDTH, GAME_CONFIG.CANVAS_HEIGHT);
    
    // Draw path
    drawPath();
    
    // Draw towers
    if (gameState.towers) {
        for (const tower of gameState.towers) {
            tower.draw(ctx);
        }
    }
    
    // Draw enemies
    if (gameState.enemies) {
        for (const enemy of gameState.enemies) {
            enemy.draw(ctx);
        }
    }
    
    // Draw bullets
    if (gameState.bullets) {
        for (const bullet of gameState.bullets) {
            bullet.draw(ctx);
        }
    }
    
    // Draw particles
    if (gameState.particles) {
        for (const particle of gameState.particles) {
            particle.draw(ctx);
        }
    }
    
    // Draw grid (optional, for debugging)
    if (gameState.selectedTower) {
        drawGrid();
    }
}

// Simple background drawing (no terrain complexity)
function drawBackground() {
    ctx.fillStyle = '#1a1a2e';
    ctx.fillRect(0, 0, GAME_CONFIG.CANVAS_WIDTH, GAME_CONFIG.CANVAS_HEIGHT);
}


// Draw path
function drawPath() {
    if (!gameState || !gameState.path || gameState.path.length === 0) {
        return;
    }
    
    ctx.strokeStyle = '#374151';
    ctx.lineWidth = 30;
    ctx.lineCap = 'round';
    
    ctx.beginPath();
    ctx.moveTo(gameState.path[0].x, gameState.path[0].y);
    
    for (let i = 1; i < gameState.path.length; i++) {
        ctx.lineTo(gameState.path[i].x, gameState.path[i].y);
    }
    
    ctx.stroke();
    
    // Draw path border
    ctx.strokeStyle = '#1f2937';
    ctx.lineWidth = 34;
    ctx.stroke();
}

// Draw grid
function drawGrid() {
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.1)';
    ctx.lineWidth = 1;
    
    for (let x = 0; x <= GAME_CONFIG.CANVAS_WIDTH; x += GAME_CONFIG.GRID_SIZE) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, GAME_CONFIG.CANVAS_HEIGHT);
        ctx.stroke();
    }
    
    for (let y = 0; y <= GAME_CONFIG.CANVAS_HEIGHT; y += GAME_CONFIG.GRID_SIZE) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(GAME_CONFIG.CANVAS_WIDTH, y);
        ctx.stroke();
    }
}

// Debug function to check game state
function debugGameState() {
    console.log('Game State Debug:');
    console.log('- GameState exists:', !!gameState);
    console.log('- Canvas exists:', !!canvas);
    console.log('- Context exists:', !!ctx);
    console.log('- Path exists:', !!gameState?.path);
    console.log('- Path length:', gameState?.path?.length || 0);
    console.log('- Wave:', gameState?.wave || 'N/A');
    console.log('- Wave configs:', !!gameState?.waveConfigs);
}

// Initialize game when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing game...');
    
    // Wait for all elements to be available
    setTimeout(() => {
        // Check if all required elements exist
        const canvas = document.getElementById('gameCanvas');
        const startWaveBtn = document.getElementById('start-wave');
        const pauseBtn = document.getElementById('pause-game');
        const resetBtn = document.getElementById('reset-game');
        
        if (!canvas) {
            console.error('Canvas element not found!');
            return;
        }
        
        if (!startWaveBtn || !pauseBtn || !resetBtn) {
            console.error('Game buttons not found!');
            return;
        }
        
        console.log('All elements found, initializing game...');
        initGame();
        
        // Debug after initialization
        setTimeout(debugGameState, 200);
    }, 500); // Increased delay to ensure everything is loaded
});
