-- Add CSS Flexbox & Grid Course and Sample Mentor Account
USE pbl_gamified;

-- Create mentor account (password: Mentor@123)
INSERT INTO users (email, password_hash, role, active, email_verified, created_at) 
VALUES ('cheska.diaz@aprnder.com', '$2y$10$rBV2jmWFVFwdYrCw8VZIO.VJqPkp5nBvKSYXMxKz9vCLVz5HqXrIS', 'mentor', 1, 1, NOW());

SET @mentor_id = LAST_INSERT_ID();

-- Create mentor profile
INSERT INTO user_profiles (user_id, full_name, bio) 
VALUES (@mentor_id, 'Cheska Diaz', 'Data Protection Officer and experienced web development mentor. Passionate about teaching modern web layout techniques to ICT students.');

-- Create CSS Flexbox & Grid Course
INSERT INTO courses (title, description, created_by, created_at) 
VALUES (
  'CSS Flexbox & Grid Mastery',
  'Master modern CSS layout techniques! Learn how to create responsive, flexible layouts using CSS Flexbox and Grid. This course is specifically designed for ICT students and directly supports the concepts in our Tower Defense game. Perfect for understanding how to position elements on a webpage.',
  @mentor_id,
  NOW()
);

SET @course_id = LAST_INSERT_ID();

-- Quest 1: Introduction to Flexbox
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Introduction to Flexbox Basics',
  '# Flexbox Fundamentals

Learn the basics of CSS Flexbox! Your task is to create a simple horizontal navigation menu using flexbox.

## Requirements:
1. Use `display: flex;` on the container
2. Use `justify-content: space-around;` to distribute items
3. Center items vertically with `align-items: center;`

## Code Template:
```css
.nav-container {
  display: flex;
  /* Add your properties here */
}
```

## Expected Result:
A horizontally distributed navigation menu with evenly spaced items.',
  'easy',
  50,
  NOW()
);

-- Quest 2: Flexbox Direction and Wrapping
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Flexbox Direction & Wrapping',
  '# Flexbox Direction & Wrapping

Master flex-direction and flex-wrap properties to control layout flow!

## Requirements:
1. Create a card grid that wraps to multiple lines
2. Use `flex-direction: row;`
3. Use `flex-wrap: wrap;` to allow wrapping
4. Add spacing between items using `gap: 20px;`

## Real-World Application:
This is exactly how the turrets are positioned in our Tower Defense game!

## Code Template:
```css
.card-container {
  display: flex;
  /* Add your direction and wrapping properties */
}
```',
  'easy',
  75,
  NOW()
);

-- Quest 3: Advanced Flexbox Alignment
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Advanced Flexbox Alignment',
  '# Master Flexbox Alignment

Learn advanced alignment techniques with justify-content and align-items!

## Requirements:
1. Create a centered card layout
2. Use `justify-content: center;` for horizontal centering
3. Use `align-items: center;` for vertical centering
4. Experiment with different values: flex-start, flex-end, space-between, space-around

## Challenge:
Position 6 turrets in a 2x3 grid formation, just like in Wave 1 of the game!

```css
.turret-container {
  display: flex;
  /* Apply optimal positioning */
}
```',
  'medium',
  100,
  NOW()
);

-- Quest 4: Introduction to CSS Grid
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Introduction to CSS Grid',
  '# CSS Grid Fundamentals

Learn how to create powerful 2D layouts with CSS Grid!

## Requirements:
1. Use `display: grid;`
2. Define columns with `grid-template-columns: repeat(3, 1fr);`
3. Define rows with `grid-template-rows: repeat(2, 200px);`
4. Add spacing with `gap: 15px;`

## Grid Basics:
- **Columns:** Vertical divisions
- **Rows:** Horizontal divisions
- **fr unit:** Flexible fraction of available space

```css
.grid-container {
  display: grid;
  /* Add your grid properties */
}
```',
  'medium',
  100,
  NOW()
);

-- Quest 5: Grid Areas and Placement
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Grid Areas & Placement',
  '# Advanced Grid Placement

Master grid-template-areas for creating complex layouts!

## Requirements:
1. Create a webpage layout with header, sidebar, main content, and footer
2. Use `grid-template-areas` to define named regions
3. Place items using `grid-area` property

## Template:
```css
.page-layout {
  display: grid;
  grid-template-areas:
    "header header header"
    "sidebar main main"
    "footer footer footer";
  grid-template-columns: 200px 1fr 1fr;
  grid-template-rows: auto 1fr auto;
  gap: 10px;
}

.header { grid-area: header; }
.sidebar { grid-area: sidebar; }
.main { grid-area: main; }
.footer { grid-area: footer; }
```',
  'medium',
  125,
  NOW()
);

-- Quest 6: Responsive Flexbox & Grid
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Responsive Layouts with Flexbox & Grid',
  '# Create Responsive Layouts

Combine Flexbox and Grid to build fully responsive web layouts!

## Requirements:
1. Create a responsive gallery that uses Grid on desktop
2. Switches to Flexbox column on mobile
3. Uses media queries: `@media (max-width: 768px)`
4. Implements `auto-fit` and `minmax()` for automatic responsiveness

## Advanced Technique:
```css
.responsive-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}
```

This automatically adjusts columns based on available space!',
  'hard',
  150,
  NOW()
);

-- Quest 7: Tower Defense Layout Challenge
INSERT INTO quests (course_id, title, description, difficulty, max_points, created_at) 
VALUES (
  @course_id,
  'Tower Defense Positioning Challenge',
  '# Final Challenge: Game Layout Optimization

Apply everything you''ve learned to optimize turret positioning in our Tower Defense game!

## Objective:
Position 6 turrets to defend against enemies using ONLY CSS Flexbox properties.

## Wave Requirements:
Each wave has different enemy paths. Position turrets to maximize coverage!

### Wave 1 - Straight Path:
```css
display: flex;
justify-content: space-around;
align-items: center;
gap: 80px;
```

### Wave 2 - L-Shaped Path:
```css
display: flex;
justify-content: space-between;
align-items: flex-start;
gap: 60px;
flex-wrap: wrap;
```

### Wave 3 - Zigzag Path:
```css
display: flex;
justify-content: center;
align-items: space-evenly;
gap: 100px;
```

## Success Criteria:
- All turrets positioned away from enemy path
- Maximum coverage area
- No turret overlap
- Strategic spacing using gap property

**This quest directly applies to the actual game - practice here, win there!**',
  'hard',
  200,
  NOW()
);

-- Enroll mentor in the course they created
INSERT INTO enrollments (user_id, course_id, enrolled_at) 
VALUES (@mentor_id, @course_id, NOW());

-- Add welcome notification for mentor
INSERT INTO notifications (user_id, message, type, is_read, created_at) 
VALUES (
  @mentor_id, 
  '🎉 Welcome, Cheska! Your CSS Flexbox & Grid Mastery course has been created successfully!', 
  'success', 
  0, 
  NOW()
);

-- Success message
SELECT 
  'Course and Mentor Created Successfully!' AS status,
  @mentor_id AS mentor_user_id,
  @course_id AS course_id,
  'Email: cheska.diaz@aprnder.com' AS login_email,
  'Password: Mentor@123' AS login_password;
