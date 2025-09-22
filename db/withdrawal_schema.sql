-- AquaVault Capital - Withdrawal System Schema
-- Add these tables to support withdrawal functionality

-- User bank accounts table
CREATE TABLE `user_bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `bank_code` varchar(10) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_primary` (`is_primary`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Withdrawal requests table
CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `bank_account_id` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL UNIQUE,
  `amount` decimal(15,2) NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL COMMENT 'Original investment amount',
  `returns_amount` decimal(15,2) NOT NULL COMMENT 'Interest/profit amount',
  `tax_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Tax deducted',
  `net_amount` decimal(15,2) NOT NULL COMMENT 'Final amount after tax',
  `status` enum('pending','approved','processing','completed','rejected','failed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `paystack_transfer_code` varchar(100) DEFAULT NULL,
  `paystack_reference` varchar(100) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL COMMENT 'Admin who processed the request',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `investment_id` (`investment_id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `status` (`status`),
  KEY `processed_by` (`processed_by`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`investment_id`) REFERENCES `user_investments` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bank_account_id`) REFERENCES `user_bank_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add withdrawal settings to system_settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('withdrawal_processing_fee', '0', 'Processing fee for withdrawals (percentage)'),
('withdrawal_auto_approve', '0', 'Auto-approve withdrawals below this amount'),
('withdrawal_processing_time', '24', 'Processing time in hours'),
('paystack_transfer_enabled', '1', 'Enable Paystack transfers for withdrawals');

-- Add withdrawal type to transactions table if not exists
ALTER TABLE `transactions` 
ADD COLUMN IF NOT EXISTS `withdrawal_id` int(11) DEFAULT NULL AFTER `investment_id`,
ADD KEY IF NOT EXISTS `withdrawal_id` (`withdrawal_id`),
ADD CONSTRAINT IF NOT EXISTS `transactions_withdrawal_fk` 
FOREIGN KEY (`withdrawal_id`) REFERENCES `withdrawal_requests` (`id`) ON DELETE SET NULL;
