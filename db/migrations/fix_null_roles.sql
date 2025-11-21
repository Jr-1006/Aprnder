-- Fix NULL roles in users table
-- This addresses the "Column 'role' cannot be null" error

USE pbl_gamified;

-- First, update any NULL roles to 'student' (shouldn't exist, but just in case)
UPDATE users 
SET role = 'student' 
WHERE role IS NULL;

-- Update 'user' role to 'student' for consistency
UPDATE users 
SET role = 'student' 
WHERE role = 'user';

-- Verify no NULL roles remain
SELECT 
    'NULL roles' as check_type,
    COUNT(*) as count 
FROM users 
WHERE role IS NULL
UNION ALL
SELECT 
    'Role distribution' as check_type,
    COUNT(*) as count
FROM users
GROUP BY role;

-- Show current role distribution
SELECT role, COUNT(*) as count 
FROM users 
GROUP BY role 
ORDER BY role;
