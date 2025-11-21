-- Gamified PBL System Schema
-- Engine: InnoDB, Charset: utf8mb4

CREATE DATABASE IF NOT EXISTS pbl_gamified CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pbl_gamified;

-- Users: login accounts for students and mentors
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','mentor','admin','user') NOT NULL DEFAULT 'student',
  active TINYINT(1) NOT NULL DEFAULT 1,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  verification_token VARCHAR(64) NULL,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email),
  INDEX idx_users_active (active)
) ENGINE=InnoDB;

-- User Profiles: 1:1 with users
CREATE TABLE IF NOT EXISTS user_profiles (
  user_id INT UNSIGNED PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  avatar_url VARCHAR(255) NULL,
  bio TEXT NULL,
  preferences JSON NULL,
  CONSTRAINT fk_user_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Courses: ICT strands/courses
CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_courses_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Enrollments: Many students to many courses
CREATE TABLE IF NOT EXISTS enrollments (
  user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, course_id),
  CONSTRAINT fk_enrollments_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_enrollments_course
    FOREIGN KEY (course_id) REFERENCES courses(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Quests (problems/activities) under a course
CREATE TABLE IF NOT EXISTS quests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'easy',
  max_points INT UNSIGNED NOT NULL DEFAULT 10,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_quests_course
    FOREIGN KEY (course_id) REFERENCES courses(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Submissions for quests by students
CREATE TABLE IF NOT EXISTS submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quest_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  code TEXT NOT NULL,
  status ENUM('pending','passed','failed','resubmitted') NOT NULL DEFAULT 'pending',
  points_awarded INT SIGNED NULL,
  feedback TEXT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_submissions_quest
    FOREIGN KEY (quest_id) REFERENCES quests(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_submissions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_submissions_user (user_id),
  INDEX idx_submissions_quest (quest_id)
) ENGINE=InnoDB;

-- Badges (achievements)
CREATE TABLE IF NOT EXISTS badges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  icon_url VARCHAR(255) NULL,
  points_threshold INT UNSIGNED NULL
) ENGINE=InnoDB;

-- User-Badges: Many-to-many
CREATE TABLE IF NOT EXISTS user_badges (
  user_id INT UNSIGNED NOT NULL,
  badge_id INT UNSIGNED NOT NULL,
  awarded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, badge_id),
  CONSTRAINT fk_user_badges_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_badges_badge
    FOREIGN KEY (badge_id) REFERENCES badges(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Notifications: simple messages per user (for AJAX polling)
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(255) NOT NULL,
  type ENUM('info','success','warning','error','welcome') NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB;

-- User Tokens: for remember me and password reset functionality
CREATE TABLE IF NOT EXISTS user_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL,
  type ENUM('remember','reset','verify') NOT NULL DEFAULT 'remember',
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_tokens_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_user_tokens_token (token),
  INDEX idx_user_tokens_user (user_id),
  INDEX idx_user_tokens_expires (expires_at)
) ENGINE=InnoDB;

-- Game Scores: for tower defense game
CREATE TABLE IF NOT EXISTS game_scores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  score INT NOT NULL,
  wave_reached INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_game_scores_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_user_score (user_id, score DESC),
  INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB;

-- Seed minimal data (admin + demo course/quest)
INSERT INTO users (email, password_hash, role) VALUES
  ('admin@example.com', '$2y$10$Q0O2HcU9y2r7i7E7jQdCquqH3QmQ3uW3kQx4cJt9a9ZQ5yC1WJrOG', 'admin')
  ON DUPLICATE KEY UPDATE email = email;
-- Password is: Admin123!

INSERT INTO user_profiles (user_id, full_name) 
  SELECT id, 'Administrator' FROM users WHERE email='admin@example.com'
  ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO courses (title, description, created_by)
  SELECT 'Intro to Coding (ICT)', 'Foundations of programming for SHS ICT strand', id FROM users WHERE email='admin@example.com'
  ON DUPLICATE KEY UPDATE title = title;

INSERT INTO quests (course_id, title, description, difficulty, max_points)
  SELECT c.id, 'Print Hello World', 'Write a program that prints Hello World', 'easy', 10 FROM courses c WHERE c.title='Intro to Coding (ICT)'
  ON DUPLICATE KEY UPDATE title = title;


