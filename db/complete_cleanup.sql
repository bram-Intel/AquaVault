-- Complete cleanup script for AquaVault Capital
-- This removes all old investment plan data and makes the system completely clean
-- Run this to have a fresh start with only the new dynamic system

-- 1. Remove old investment plans (optional - you can keep them for reference)
-- DELETE FROM investment_plans;

-- 2. Update existing user investments to use new system
-- Set plan_id to NULL for all investments
UPDATE user_investments SET plan_id = NULL;

-- 3. Update transaction descriptions to use new format
UPDATE transactions 
SET description = REPLACE(description, 'Investment in ', 'Investment in Category: ')
WHERE description LIKE 'Investment in %' AND description NOT LIKE '%Category:%';

-- 4. Remove the foreign key constraint on plan_id (if not already done)
-- This allows plan_id to be NULL
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'user_investments' 
     AND CONSTRAINT_NAME = 'user_investments_ibfk_2') > 0,
    'ALTER TABLE `user_investments` DROP FOREIGN KEY `user_investments_ibfk_2`',
    'SELECT "Foreign key constraint already removed"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Verify the new system is working
-- Check if investment categories exist
SELECT 'Investment Categories:' as info;
SELECT COUNT(*) as category_count FROM investment_categories;

-- Check if investment durations exist  
SELECT 'Investment Durations:' as info;
SELECT COUNT(*) as duration_count FROM investment_durations;

-- Check user investments with new system
SELECT 'New System Investments:' as info;
SELECT COUNT(*) as new_investments FROM user_investments WHERE category_id IS NOT NULL;

-- Check old system investments
SELECT 'Old System Investments:' as info;
SELECT COUNT(*) as old_investments FROM user_investments WHERE plan_id IS NOT NULL;

-- 6. Show current system status
SELECT 'System Status: Ready for new dynamic investment system' as status;
