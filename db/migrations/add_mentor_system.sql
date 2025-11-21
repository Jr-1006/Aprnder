-- Migration: Add Mentor Promotion System
-- Created: 2025-10-20

USE pbl_gamified;

-- Table for mentor promotion criteria
CREATE TABLE IF NOT EXISTS mentor_criteria (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  criteria_key VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  threshold_value INT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_criteria_active (is_active)
) ENGINE=InnoDB;

-- Insert default mentor criteria
INSERT INTO mentor_criteria (criteria_key, name, description, threshold_value, is_active) VALUES
('min_completed_quests', 'Minimum Completed Quests', 'Number of quests student must complete', 15, 1),
('min_game_score', 'Minimum Game Score', 'Minimum score in tower defense game', 15000, 1),
('min_courses_completed', 'Minimum Courses Completed', 'Number of courses student must complete', 2, 1),
('min_badges_earned', 'Minimum Badges Earned', 'Number of badges student must earn', 5, 1),
('min_perfect_submissions', 'Minimum Perfect Submissions', 'Number of perfect score submissions', 3, 1)
ON DUPLICATE KEY UPDATE 
  threshold_value = VALUES(threshold_value),
  is_active = VALUES(is_active);

-- Table to track mentor promotions
CREATE TABLE IF NOT EXISTS mentor_promotions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  promoted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  promoted_by INT UNSIGNED NULL, -- NULL for auto-promotion, admin user ID for manual
  promotion_type ENUM('auto', 'manual') NOT NULL DEFAULT 'auto',
  notes TEXT NULL,
  CONSTRAINT fk_mentor_promotions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mentor_promotions_admin
    FOREIGN KEY (promoted_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_mentor_promotions_user (user_id),
  INDEX idx_mentor_promotions_date (promoted_at)
) ENGINE=InnoDB;

-- Add mentor-specific badges
INSERT INTO badges (name, description, icon_url) VALUES
('Mentor Eligible', 'Met all requirements to become a mentor', '🎓'),
('Mentor', 'Promoted to mentor status', '👨‍🏫'),
('Helping Hand', 'Reviewed 10 peer submissions', '🤝'),
('Community Leader', 'Active mentor for 30 days', '⭐'),
('Top Mentor', 'Helped 50+ students successfully', '🏆')
ON DUPLICATE KEY UPDATE 
  description = VALUES(description),
  icon_url = VALUES(icon_url);

-- Update existing role enum to ensure mentor is included and default to student
ALTER TABLE users MODIFY COLUMN role ENUM('student','mentor','admin','user') NOT NULL DEFAULT 'student';
