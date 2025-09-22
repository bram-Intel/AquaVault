-- Simple fix for plan_id column to allow NULL values
-- This doesn't require information_schema access

-- Modify the plan_id column to allow NULL values
ALTER TABLE `user_investments` MODIFY COLUMN `plan_id` INT(11) DEFAULT NULL;

-- Show the updated table structure
DESCRIBE `user_investments`;
