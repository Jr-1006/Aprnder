# Gamified PBL - Tower Defense Learning Platform

A modern web application that combines programming education with an interactive tower defense game, built with PHP, JavaScript, and CSS using flexbox and grid layouts.

## Features

### 🎮 Tower Defense Game
- **Interactive Tower Control**: Move towers using WASD or Arrow keys
- **Real-time Strategy**: Place towers strategically to defend against waves of enemies
- **Multiple Tower Types**: Basic, Rapid, and Heavy towers with different stats
- **Enemy Variety**: Different enemy types with varying health and speed
- **Visual Effects**: Particle systems, animations, and smooth transitions

### 🎨 Modern UI/UX
- **Boot.dev-inspired Design**: Clean, modern interface with dark theme
- **Responsive Layout**: CSS Grid and Flexbox for optimal display on all devices
- **Smooth Animations**: CSS transitions and keyframe animations
- **Interactive Elements**: Hover effects, modal dialogs, and real-time updates

### 📊 Gamification Features
- **Score Tracking**: Persistent high scores and wave progression
- **Leaderboard**: Top players ranking system
- **User Statistics**: Games played, best scores, and achievements
- **Progress Tracking**: Detailed analytics for learning progress

## Technology Stack

- **Backend**: PHP 8+ with PDO for database operations
- **Frontend**: HTML5, CSS3 (Grid & Flexbox), JavaScript ES6+
- **Database**: MySQL with proper indexing and foreign keys
- **Canvas API**: HTML5 Canvas for game rendering
- **Responsive Design**: Mobile-first approach with CSS Grid and Flexbox

## File Structure

```
Websys/
├── public/
│   ├── index.php          # Homepage with hero section
│   ├── game.php           # Tower defense game page
│   ├── dashboard.php      # User dashboard with stats
│   ├── static/
│   │   ├── styles.css     # Main stylesheet with modern design
│   │   ├── game-styles.css # Game-specific styles
│   │   ├── app.js         # General app functionality
│   │   └── game.js        # Game engine and logic
│   └── api/
│       ├── notifications.php # Notification system
│       └── game-scores.php   # Score saving API
├── src/
│   ├── bootstrap.php      # Application bootstrap
│   ├── config.php         # Configuration settings
│   ├── db.php            # Database connection
│   └── session.php       # Session management
└── db/
    └── schema.sql        # Database schema
```

## Game Controls

### Tower Movement
- **WASD Keys** or **Arrow Keys**: Move selected tower
- **Click**: Select a tower or place new tower
- **R Key**: Rotate selected tower
- **Space**: Manual fire (if target in range)

### Game Management
- **Start Wave**: Begin enemy spawning
- **Pause/Resume**: Toggle game state
- **Reset**: Restart the game

## CSS Architecture

### Flexbox Usage
- Navigation menu alignment
- Hero section content layout
- Button groups and action bars
- Stat cards and feature layouts

### CSS Grid Usage
- Main page layouts (hero, features, game showcase)
- Dashboard statistics grid
- Leaderboard item layout
- Game container (sidebar + canvas)
- Responsive breakpoints for mobile

### Modern CSS Features
- CSS Custom Properties (variables)
- CSS Grid with auto-fit and minmax
- Flexbox with gap property
- CSS transforms and animations
- Backdrop filters and shadows

## Installation

1. **Setup XAMPP**: Ensure XAMPP is running with Apache and MySQL
2. **Database Setup**: Import the schema.sql file to create required tables
3. **Configuration**: Update database credentials in `src/config.php`
4. **Access**: Navigate to `http://localhost/Websys/` in your browser

## Game Features

### Tower Types
- **Basic Tower**: Balanced damage and range (50 cost)
- **Rapid Tower**: Fast firing, lower damage (75 cost)
- **Heavy Tower**: High damage, slow firing (100 cost)

### Enemy Types
- **Basic Enemy**: Standard health and speed
- **Fast Enemy**: Low health, high speed
- **Tank Enemy**: High health, low speed

### Game Mechanics
- **Pathfinding**: Enemies follow a predefined winding path
- **Collision Detection**: Precise bullet-enemy collision system
- **Wave Progression**: Increasing difficulty with each wave
- **Score System**: Points awarded for defeating enemies
- **Lives System**: Lose lives when enemies reach the end

## API Endpoints

### Game Scores API (`/api/game-scores.php`)
- **POST**: Save game score and wave reached
- **Response**: Success status, best scores, leaderboard data

### Notifications API (`/api/notifications.php`)
- **GET**: Fetch user notifications
- **POST**: Mark notifications as read

## Responsive Design

The application uses a mobile-first approach with breakpoints:
- **Mobile**: < 480px - Single column layouts
- **Tablet**: 480px - 768px - Adjusted grid columns
- **Desktop**: > 768px - Full grid layouts

## Browser Support

- Modern browsers with CSS Grid and Flexbox support
- Canvas API for game rendering
- ES6+ JavaScript features
- CSS Custom Properties

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test on multiple devices and browsers
5. Submit a pull request

## License

This project is for educational purposes. Feel free to use and modify for learning.
