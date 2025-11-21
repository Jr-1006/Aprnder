-- Create Two Mentor Accounts: Cheska Diaz and Kyle Cataga
USE pbl_gamified;

-- Delete existing mentor accounts if they exist (handle foreign key constraints)
-- Get all mentor user ids
SET @old_mentor_id = (SELECT id FROM users WHERE email = 'mentor@aprnder.com' LIMIT 1);
SET @cheska_old_id = (SELECT id FROM users WHERE email = 'cheska.diaz@aprnder.com' LIMIT 1);
SET @kyle_old_id = (SELECT id FROM users WHERE email = 'kyle.cataga@aprnder.com' LIMIT 1);

-- Delete related records for old mentor account
DELETE FROM submissions WHERE quest_id IN (SELECT id FROM quests WHERE course_id IN (SELECT id FROM courses WHERE created_by = @old_mentor_id));
DELETE FROM quests WHERE course_id IN (SELECT id FROM courses WHERE created_by = @old_mentor_id);
DELETE FROM enrollments WHERE course_id IN (SELECT id FROM courses WHERE created_by = @old_mentor_id);
DELETE FROM courses WHERE created_by = @old_mentor_id;
DELETE FROM notifications WHERE user_id = @old_mentor_id;
DELETE FROM enrollments WHERE user_id = @old_mentor_id;
DELETE FROM user_profiles WHERE user_id = @old_mentor_id;
DELETE FROM users WHERE id = @old_mentor_id;

-- Delete related records for Cheska if exists
DELETE FROM submissions WHERE quest_id IN (SELECT id FROM quests WHERE course_id IN (SELECT id FROM courses WHERE created_by = @cheska_old_id));
DELETE FROM quests WHERE course_id IN (SELECT id FROM courses WHERE created_by = @cheska_old_id);
DELETE FROM enrollments WHERE course_id IN (SELECT id FROM courses WHERE created_by = @cheska_old_id);
DELETE FROM courses WHERE created_by = @cheska_old_id;
DELETE FROM notifications WHERE user_id = @cheska_old_id;
DELETE FROM enrollments WHERE user_id = @cheska_old_id;
DELETE FROM user_profiles WHERE user_id = @cheska_old_id;
DELETE FROM users WHERE id = @cheska_old_id;

-- Delete related records for Kyle if exists
DELETE FROM submissions WHERE quest_id IN (SELECT id FROM quests WHERE course_id IN (SELECT id FROM courses WHERE created_by = @kyle_old_id));
DELETE FROM quests WHERE course_id IN (SELECT id FROM courses WHERE created_by = @kyle_old_id);
DELETE FROM enrollments WHERE course_id IN (SELECT id FROM courses WHERE created_by = @kyle_old_id);
DELETE FROM courses WHERE created_by = @kyle_old_id;
DELETE FROM notifications WHERE user_id = @kyle_old_id;
DELETE FROM enrollments WHERE user_id = @kyle_old_id;
DELETE FROM user_profiles WHERE user_id = @kyle_old_id;
DELETE FROM users WHERE id = @kyle_old_id;

-- Create Cheska Diaz mentor account (password: Mentor@123)
INSERT INTO users (email, password_hash, role, active, email_verified, created_at) 
VALUES ('cheska.diaz@aprnder.com', '$2y$10$rBV2jmWFVFwdYrCw8VZIO.VJqPkp5nBvKSYXMxKz9vCLVz5HqXrIS', 'mentor', 1, 1, NOW());

SET @cheska_id = LAST_INSERT_ID();

-- Create Cheska's profile
INSERT INTO user_profiles (user_id, full_name, bio) 
VALUES (@cheska_id, 'Cheska Diaz', 'Data Protection Officer and experienced web development mentor. Passionate about teaching modern programming techniques to students.');

-- Create Kyle Cataga mentor account (password: Mentor@123)
INSERT INTO users (email, password_hash, role, active, email_verified, created_at) 
VALUES ('kyle.cataga@aprnder.com', '$2y$10$rBV2jmWFVFwdYrCw8VZIO.VJqPkp5nBvKSYXMxKz9vCLVz5HqXrIS', 'mentor', 1, 1, NOW());

SET @kyle_id = LAST_INSERT_ID();

-- Create Kyle's profile
INSERT INTO user_profiles (user_id, full_name, bio) 
VALUES (@kyle_id, 'Kyle Cataga', 'Experienced CSS developer and educator. Specializes in modern web layout techniques and game-based learning.');

-- Welcome notifications
INSERT INTO notifications (user_id, message, type, is_read, created_at) 
VALUES 
  (@cheska_id, '🎉 Welcome, Cheska! Your mentor account has been created successfully!', 'success', 0, NOW()),
  (@kyle_id, '🎉 Welcome, Kyle! Your mentor account has been created successfully!', 'success', 0, NOW());

-- Success message
SELECT 
  'Two Mentor Accounts Created Successfully!' AS status,
  'Cheska Diaz - Email: cheska.diaz@aprnder.com, Password: Mentor@123' AS mentor_1,
  'Kyle Cataga - Email: kyle.cataga@aprnder.com, Password: Mentor@123' AS mentor_2;
