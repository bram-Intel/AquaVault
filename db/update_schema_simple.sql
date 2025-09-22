-- AquaVault Capital - Dynamic Investment System Update (SIMPLE VERSION)
-- Run this after the main schema to add dynamic categories and durations

-- Create investment_categories table (will fail silently if exists)
CREATE TABLE IF NOT EXISTS `investment_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL UNIQUE,
  `description` text,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007BFF',
  `min_amount` decimal(15,2) NOT NULL DEFAULT 10000.00,
  `max_amount` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create investment_durations table (will fail silently if exists)
CREATE TABLE IF NOT EXISTS `investment_durations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `days` int(11) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL COMMENT 'Annual percentage rate',
  `tax_rate` decimal(5,2) DEFAULT 5.00 COMMENT 'Tax percentage on returns',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `days` (`days`),
  KEY `is_active` (`is_active`),
  FOREIGN KEY (`category_id`) REFERENCES `investment_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns to existing tables (will fail silently if columns exist)
ALTER TABLE `investment_plans` ADD COLUMN `category_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `user_investments` ADD COLUMN `category_id` int(11) DEFAULT NULL AFTER `plan_id`;
ALTER TABLE `user_investments` ADD COLUMN `duration_id` int(11) DEFAULT NULL AFTER `category_id`;

-- Add foreign keys (will fail silently if they exist)
ALTER TABLE `investment_plans` ADD KEY `category_id` (`category_id`);
ALTER TABLE `investment_plans` ADD FOREIGN KEY (`category_id`) REFERENCES `investment_categories` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_investments` ADD KEY `category_id` (`category_id`);
ALTER TABLE `user_investments` ADD KEY `duration_id` (`duration_id`);
ALTER TABLE `user_investments` ADD FOREIGN KEY (`category_id`) REFERENCES `investment_categories` (`id`) ON DELETE SET NULL;
ALTER TABLE `user_investments` ADD FOREIGN KEY (`duration_id`) REFERENCES `investment_durations` (`id`) ON DELETE SET NULL;

-- Insert default investment categories (will ignore duplicates)
INSERT IGNORE INTO `investment_categories` (`name`, `slug`, `description`, `icon`, `color`, `min_amount`, `max_amount`, `is_active`, `sort_order`) VALUES
('AquaVault Stock', 'aquavault-stock', 'Invest in AquaVault Capital stocks with competitive returns', '📈', '#007BFF', 10000.00, 5000000.00, 1, 1),
('Agriculture', 'agriculture', 'Agricultural investment opportunities with steady growth', '🌾', '#28A745', 25000.00, 10000000.00, 1, 2),
('Transportation', 'transportation', 'Transportation and logistics investment plans', '🚛', '#FFC107', 50000.00, 20000000.00, 1, 3),
('Real Estate', 'real-estate', 'Real estate investment opportunities', '🏠', '#DC3545', 100000.00, 50000000.00, 1, 4);

-- Insert default investment durations for each category (will ignore duplicates)
-- AquaVault Stock
INSERT IGNORE INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`) VALUES
(1, '7 Days', 7, 8.00, 5.00, 1, 1),
(1, '30 Days', 30, 10.00, 5.00, 1, 2),
(1, '60 Days', 60, 12.00, 5.00, 1, 3),
(1, '90 Days', 90, 15.00, 5.00, 1, 4),
(1, '180 Days', 180, 18.00, 5.00, 1, 5),
(1, '365 Days', 365, 22.00, 5.00, 1, 6);

-- Agriculture
INSERT IGNORE INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`) VALUES
(2, '7 Days', 7, 6.00, 5.00, 1, 1),
(2, '30 Days', 30, 8.00, 5.00, 1, 2),
(2, '60 Days', 60, 10.00, 5.00, 1, 3),
(2, '90 Days', 90, 12.00, 5.00, 1, 4),
(2, '180 Days', 180, 15.00, 5.00, 1, 5),
(2, '365 Days', 365, 18.00, 5.00, 1, 6);

-- Transportation
INSERT IGNORE INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`) VALUES
(3, '7 Days', 7, 7.00, 5.00, 1, 1),
(3, '30 Days', 30, 9.00, 5.00, 1, 2),
(3, '60 Days', 60, 11.00, 5.00, 1, 3),
(3, '90 Days', 90, 13.00, 5.00, 1, 4),
(3, '180 Days', 180, 16.00, 5.00, 1, 5),
(3, '365 Days', 365, 20.00, 5.00, 1, 6);

-- Real Estate
INSERT IGNORE INTO `investment_durations` (`category_id`, `name`, `days`, `interest_rate`, `tax_rate`, `is_active`, `sort_order`) VALUES
(4, '7 Days', 7, 5.00, 5.00, 1, 1),
(4, '30 Days', 30, 7.00, 5.00, 1, 2),
(4, '60 Days', 60, 9.00, 5.00, 1, 3),
(4, '90 Days', 90, 11.00, 5.00, 1, 4),
(4, '180 Days', 180, 14.00, 5.00, 1, 5),
(4, '365 Days', 365, 17.00, 5.00, 1, 6);
