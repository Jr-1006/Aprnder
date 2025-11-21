# Gamified Problem-Based Learning (PBL) System – Database Design

## Entities (5 core tables + support)
- Users
- UserProfiles
- Courses
- Quests
- Submissions
- (Support) Enrollments, Badges, UserBadges, Notifications

## Relationships and Rationale
- One User has One UserProfile (1:1)
  - Reason: store optional profile and preferences separate from auth fields.
- One User can have Many Enrollments (1:M) and Many Courses via Enrollments (M:N)
  - Reason: students enroll in multiple courses; courses have many students.
- One Course has Many Quests (1:M)
  - Reason: each course comprises multiple problem-based quests.
- One User can have Many Submissions; One Quest can have Many Submissions (M:N via Submissions)
  - Reason: students attempt quests; the submissions table captures attempts, scores, and feedback.
- One User can have Many Badges; One Badge can belong to Many Users (M:N via UserBadges)
  - Reason: gamification achievements across the platform.
- One User has Many Notifications (1:M)
  - Reason: deliver system messages (scores, feedback, badges) for AJAX polling.

## Key Fields
- `users(id, email UNIQUE, password_hash, role)`
- `user_profiles(user_id PK, full_name, avatar_url, preferences JSON)`
- `courses(id, title, created_by → users.id)`
- `quests(id, course_id → courses.id, difficulty, max_points)`
- `submissions(id, quest_id → quests.id, user_id → users.id, status, points_awarded)`
- `enrollments(user_id → users.id, course_id → courses.id)` with PK(`user_id, course_id`)
- `badges(id, name UNIQUE)`
- `user_badges(user_id → users.id, badge_id → badges.id)` with PK(`user_id, badge_id`)
- `notifications(id, user_id → users.id, is_read)`

## Designer Instructions (phpMyAdmin)
1. Import `db/schema.sql` into MySQL and select the `pbl_gamified` database.
2. Open Designer tab, add these tables: `users`, `user_profiles`, `courses`, `quests`, `submissions`.
3. Optionally add: `enrollments`, `badges`, `user_badges`, `notifications`.
4. Create relationships by dragging PK → FK:
   - `users.id` → `user_profiles.user_id` (1:1)
   - `users.id` → `enrollments.user_id` (1:M)
   - `courses.id` → `enrollments.course_id` (1:M)
   - `courses.id` → `quests.course_id` (1:M)
   - `quests.id` → `submissions.quest_id` (1:M)
   - `users.id` → `submissions.user_id` (1:M)
   - `badges.id` → `user_badges.badge_id` (1:M)
   - `users.id` → `user_badges.user_id` (1:M)
   - `users.id` → `notifications.user_id` (1:M)
5. Ensure InnoDB and utf8mb4 are set; save the canvas layout, then export to PDF for your deliverable.

## Notes on Accessibility & Gamification
- AJAX notifications provide feedback without page reloads.
- `quests.difficulty` and `max_points` motivate progress and allow adaptive challenges.
- `user_profiles.preferences` (JSON) personalizes learning paths and UI.

## How to Export PDF
- Open phpMyAdmin Designer and use Export → PDF.
- Or open this Markdown file in any viewer and print to PDF.
