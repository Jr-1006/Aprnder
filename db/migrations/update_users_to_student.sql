-- Migration: Update existing users from 'user' role to 'student' role
-- Created: 2025-10-21
-- This updates all existing users with role='user' to role='student'

USE pbl_gamified;

-- Update existing users with 'user' role to 'student' role
-- Excludes admin and mentor roles
UPDATE users 
SET role = 'student' 
WHERE role = 'user';

-- Verify the changes
SELECT role, COUNT(*) as count 
FROM users 
GROUP BY role 
ORDER BY role;
