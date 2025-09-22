-- Remove old investment plans and update existing investments
-- Run this after the new category/duration system is working

-- First, let's see what old investments exist
-- SELECT * FROM user_investments WHERE plan_id IS NOT NULL;

-- Update existing investments to use the new system
-- For now, we'll set plan_id to NULL and let the new system handle them
UPDATE user_investments SET plan_id = NULL WHERE plan_id IS NOT NULL;

-- Remove the old investment plans (optional - you might want to keep them for reference)
-- DELETE FROM investment_plans;

-- Note: The old investment_plans table structure will remain but won't be used
-- The new system uses investment_categories and investment_durations instead
