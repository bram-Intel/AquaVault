-- Add 730 Days (2 Years) duration to all investment categories
-- 30% interest rate with 5% tax rate

-- AquaVault Stock (category_id = 1)
INSERT INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`, `created_at`, `updated_at`) 
VALUES (1, '730 Days', 730, 30.00, 5.00, 1, 7, NOW(), NOW());

-- Agriculture (category_id = 2)
INSERT INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`, `created_at`, `updated_at`) 
VALUES (2, '730 Days', 730, 30.00, 5.00, 1, 7, NOW(), NOW());

-- Transportation (category_id = 3)
INSERT INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`, `created_at`, `updated_at`) 
VALUES (3, '730 Days', 730, 30.00, 5.00, 1, 7, NOW(), NOW());

-- Real Estate (category_id = 4)
INSERT INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`, `created_at`, `updated_at`) 
VALUES (4, '730 Days', 730, 30.00, 5.00, 1, 7, NOW(), NOW());
