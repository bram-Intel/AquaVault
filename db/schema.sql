-- AquaVault Capital Database Schema
-- Import this via phpMyAdmin after creating your database

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `kyc_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `kyc_document` varchar(255) DEFAULT NULL,
  `kyc_document_type` varchar(50) DEFAULT NULL,
  `kyc_submitted_at` timestamp NULL DEFAULT NULL,
  `kyc_reviewed_at` timestamp NULL DEFAULT NULL,
  `kyc_reviewed_by` int(11) DEFAULT NULL,
  `wallet_balance` decimal(15,2) DEFAULT 0.00,
  `total_invested` decimal(15,2) DEFAULT 0.00,
  `total_returns` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kyc_status` (`kyc_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin users table
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Investment plans table
CREATE TABLE `investment_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) DEFAULT NULL,
  `duration_days` int(11) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL COMMENT 'Annual percentage rate',
  `tax_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Tax percentage on returns',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User investments table
CREATE TABLE `user_investments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL UNIQUE,
  `amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL,
  `expected_return` decimal(15,2) NOT NULL,
  `net_return` decimal(15,2) NOT NULL COMMENT 'After tax',
  `start_date` date NOT NULL,
  `maturity_date` date NOT NULL,
  `status` enum('pending','active','matured','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'paystack',
  `matured_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `status` (`status`),
  KEY `maturity_date` (`maturity_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `investment_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions table
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `reference` varchar(100) NOT NULL UNIQUE,
  `type` enum('deposit','investment','return','withdrawal','fee') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `investment_id` (`investment_id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`investment_id`) REFERENCES `user_investments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System settings table
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `description` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@aquavault.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Insert sample investment plans
INSERT INTO `investment_plans` (`name`, `category`, `description`, `min_amount`, `max_amount`, `duration_days`, `interest_rate`, `tax_rate`, `is_active`) VALUES
('Starter Plan', 'Fixed Deposit', 'Low-risk investment plan for beginners', 10000.00, 500000.00, 30, 8.00, 5.00, 1),
('Growth Plan', 'Fixed Deposit', 'Medium-term investment with competitive returns', 50000.00, 2000000.00, 90, 12.00, 5.00, 1),
('Premium Plan', 'Fixed Deposit', 'High-yield investment for serious investors', 200000.00, 10000000.00, 180, 15.00, 5.00, 1),
('Elite Plan', 'Fixed Deposit', 'Exclusive plan with maximum returns', 1000000.00, NULL, 365, 18.00, 5.00, 1);

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'AquaVault Capital', 'Website name'),
('site_email', 'support@aquavault.com', 'Support email address'),
('min_withdrawal', '1000', 'Minimum withdrawal amount'),
('max_withdrawal', '5000000', 'Maximum withdrawal amount'),
('kyc_required', '1', 'Require KYC verification for investments');

COMMIT;