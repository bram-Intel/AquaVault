-- Fix plan_id column to allow NULL values for new dynamic system
-- This allows the new category/duration system to work without plan_id

-- First, check if the column exists and its current definition
SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'user_investments' 
AND COLUMN_NAME = 'plan_id';

-- Modify the plan_id column to allow NULL values
ALTER TABLE `user_investments` MODIFY COLUMN `plan_id` INT(11) DEFAULT NULL;

-- Verify the change
SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'user_investments' 
AND COLUMN_NAME = 'plan_id';

-- Show current table structure
DESCRIBE `user_investments`;
