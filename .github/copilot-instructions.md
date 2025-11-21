# Copilot Instructions for Gamified PBL - Tower Defense Learning Platform

## Project Overview
This is a PHP-based web application that combines programming education with a tower defense game. The frontend uses HTML5, CSS3 (Grid & Flexbox), and JavaScript ES6+. The backend is PHP 8+ with PDO for MySQL database operations. Game rendering uses the Canvas API.

## Key Architecture & Components
- **public/**: Entry points for users (game, dashboard, login, register, etc.)
  - `game.php`: Main game logic and rendering
  - `dashboard.php`: User stats and progress
  - `api/`: REST-like endpoints for scores and notifications
  - `static/`: JS and CSS assets (game engine, styles, app logic)
- **src/**: Core backend logic
  - `bootstrap.php`: App initialization
  - `config.php`: Configuration (update DB credentials here)
  - `db.php`: PDO database connection
  - `session.php`: Session management
- **db/schema.sql**: MySQL schema (import to set up tables)

## Developer Workflows
- **Local Dev**: Use XAMPP (Apache + MySQL). Access app at `http://localhost/Websys/`.
- **DB Setup**: Import `db/schema.sql` into MySQL. Update credentials in `src/config.php`.
- **Debugging**: Use browser dev tools for JS/CSS. PHP errors appear in browser or XAMPP logs.
- **API Testing**: Endpoints in `public/api/` accept JSON requests (see README for details).

## Project-Specific Patterns
- **Game Logic**: Most game code is in `public/static/game.js` and `game.php`. Canvas API is used for rendering.
- **CSS**: Uses Grid and Flexbox extensively. Custom properties and modern CSS features are present.
- **Session & Auth**: Managed via `src/session.php` and PHP sessions. User state is checked in page controllers.
- **Score/Notification APIs**: Communicate via AJAX/fetch to `/api/game-scores.php` and `/api/notifications.php`.
- **Mobile-First**: Responsive breakpoints are defined in CSS. Test layouts on mobile and desktop.

## Conventions & Integration
- **File Naming**: Use lowercase, hyphen-separated for PHP/JS/CSS files.
- **Frontend/Backend Separation**: JS handles game UI and logic; PHP handles data, sessions, and API responses.
- **Database**: Use prepared statements (PDO) for all queries. Schema is in `db/schema.sql`.
- **No Frameworks**: Pure PHP and vanilla JS; no Laravel, React, etc.

## Examples
- To add a new API endpoint, create a PHP file in `public/api/` and follow the pattern in `game-scores.php`.
- To add a new game feature, update `public/static/game.js` and corresponding CSS in `game-styles.css`.
- For new pages, add a PHP file in `public/` and link to assets in `static/`.

## References
- See `docs/README.md` for full feature list and architecture.
- Key files: `public/game.php`, `public/static/game.js`, `src/db.php`, `db/schema.sql`.

---

**If any section is unclear or missing, please provide feedback so instructions can be improved.**
