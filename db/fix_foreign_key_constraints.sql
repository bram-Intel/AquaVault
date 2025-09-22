-- Fix foreign key constraints for the new dynamic investment system
-- Run this to make the system compatible with the new category/duration structure

-- First, drop the existing foreign key constraint on plan_id
ALTER TABLE `user_investments` DROP FOREIGN KEY `user_investments_ibfk_2`;

-- Now we can insert investments with plan_id = NULL
-- The new system uses category_id and duration_id instead of plan_id

-- Optional: If you want to keep the constraint but allow NULL values,
-- you can recreate it with ON DELETE SET NULL
-- ALTER TABLE `user_investments` ADD CONSTRAINT `user_investments_ibfk_2` 
-- FOREIGN KEY (`plan_id`) REFERENCES `investment_plans` (`id`) ON DELETE SET NULL;

-- Note: The new system doesn't require plan_id anymore
-- Investments are now identified by category_id and duration_id
