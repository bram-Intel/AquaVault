# AquaVault Capital - Withdrawal System

## Overview

The withdrawal system allows users to request withdrawals from their matured investments and receive payments directly to their bank accounts via Paystack transfers.

## Features

### User Features
- **Bank Account Management**: Add, verify, and manage multiple bank accounts
- **Withdrawal Requests**: Request withdrawals from matured investments
- **Request Tracking**: View status of all withdrawal requests
- **Real-time Updates**: Get notifications when withdrawal status changes

### Admin Features
- **Withdrawal Approval**: Approve or reject withdrawal requests
- **Paystack Integration**: Process payments directly through Paystack transfers
- **Request Management**: View and manage all withdrawal requests
- **Statistics Dashboard**: Monitor withdrawal metrics and amounts

## Database Schema

### New Tables Added

#### `user_bank_accounts`
Stores user bank account information for withdrawals.

```sql
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
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);
```

#### `withdrawal_requests`
Tracks all withdrawal requests and their processing status.

```sql
CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `bank_account_id` int(11) NOT NULL,
  `reference` varchar(100) NOT NULL UNIQUE,
  `amount` decimal(15,2) NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `returns_amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','processing','completed','rejected','failed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `paystack_transfer_code` varchar(100) DEFAULT NULL,
  `paystack_reference` varchar(100) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`investment_id`) REFERENCES `user_investments` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bank_account_id`) REFERENCES `user_bank_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
);
```

## Installation

### 1. Run the Installation Script

```bash
# Navigate to your AquaVault directory
cd /path/to/AquaVault

# Run the installation script
php install_withdrawal_tables.php
```

### 2. Set Up Cron Job

Add a daily cron job to check for matured investments:

```bash
# Edit crontab
crontab -e

# Add this line to run daily at 2 AM (adjust path to your installation)
0 2 * * * /usr/bin/php /home/jennifer/aqua/api/check_maturity.php

# With logging (recommended for debugging)
0 2 * * * /usr/bin/php /home/jennifer/aqua/api/check_maturity.php >> /home/jennifer/aqua/maturity_check.log 2>&1
```

### 3. Configure Paystack

Ensure your Paystack configuration in `config/paystack.php` includes:
- Valid secret key for transfers
- Proper webhook URL
- Transfer functionality enabled

## File Structure

### New Files Added

```
user/
├── bank_accounts.php          # Bank account management
├── withdraw.php               # Withdrawal request form
└── withdrawal_requests.php    # View withdrawal requests

admin/
└── withdrawal_requests.php    # Admin withdrawal management

api/
└── check_maturity.php         # Investment maturity checker

db/
└── withdrawal_schema.sql      # Database schema for withdrawals

config/
└── paystack.php               # Extended with transfer functions
```

## User Workflow

### 1. Add Bank Account
1. Navigate to "Bank Accounts" from dashboard
2. Click "Add Bank Account"
3. Select bank and enter account details
4. Account is automatically verified via Paystack

### 2. Request Withdrawal
1. Go to "Withdraw" from dashboard
2. Select a matured investment
3. Choose bank account for payment
4. Review withdrawal summary
5. Submit request

### 3. Track Requests
1. Visit "Requests" page
2. View all withdrawal requests
3. Check status and processing details

## Admin Workflow

### 1. Review Requests
1. Access admin panel
2. Go to "Withdrawals" section
3. View pending requests with user and investment details

### 2. Process Requests
1. **Approve**: Mark request as approved
2. **Process Payment**: Initiate Paystack transfer
3. **Complete**: Mark as completed after successful transfer
4. **Reject**: Reject with reason if needed

### 3. Monitor Statistics
- View pending, approved, processing, and completed counts
- Monitor total amounts for each status
- Track processing times

## Paystack Integration

### Transfer Functions Added

- `create_transfer_recipient()`: Create recipient for transfers
- `initiate_transfer()`: Initiate transfer to bank account
- `verify_transfer()`: Verify transfer status
- `get_nigerian_banks()`: Get list of Nigerian banks
- `resolve_bank_account()`: Verify bank account details

### Transfer Process

1. Create transfer recipient using bank details
2. Initiate transfer from Paystack balance
3. Store transfer code and reference
4. Update withdrawal request status
5. Monitor transfer completion

## Security Features

- **Account Verification**: All bank accounts verified via Paystack
- **Admin Approval**: All withdrawals require admin approval
- **Audit Trail**: Complete transaction history
- **Status Tracking**: Real-time status updates
- **Secure Transfers**: Paystack handles all financial transactions

## System Settings

New settings added to `system_settings` table:

- `withdrawal_processing_fee`: Processing fee percentage
- `withdrawal_auto_approve`: Auto-approve threshold amount
- `withdrawal_processing_time`: Expected processing time in hours
- `paystack_transfer_enabled`: Enable/disable Paystack transfers

## Error Handling

- Database transaction rollback on errors
- Comprehensive error logging
- User-friendly error messages
- Paystack API error handling
- Graceful failure recovery

## Testing

### Test Scenarios

1. **Bank Account Management**
   - Add valid bank account
   - Add invalid bank account
   - Set primary account
   - Delete account

2. **Withdrawal Requests**
   - Request from matured investment
   - Request from non-matured investment
   - Request with insufficient funds
   - Multiple requests from same investment

3. **Admin Processing**
   - Approve valid request
   - Reject request with reason
   - Process payment successfully
   - Handle payment failures

## Monitoring

### Key Metrics to Monitor

- Daily withdrawal request volume
- Average processing time
- Success/failure rates
- Total amounts processed
- User satisfaction scores

### Log Files

- Check `error_log` for system errors
- Monitor Paystack webhook logs
- Review admin action logs
- Track transfer success rates

## Troubleshooting

### Common Issues

1. **Bank Account Verification Fails**
   - Check Paystack API credentials
   - Verify bank code format
   - Ensure account number is correct

2. **Transfer Failures**
   - Check Paystack balance
   - Verify recipient details
   - Review transfer limits

3. **Maturity Check Not Running**
   - Verify cron job is set up
   - Check file permissions
   - Review error logs

### Support

For technical support or issues:
1. Check error logs first
2. Verify Paystack configuration
3. Test with small amounts initially
4. Contact Paystack support for transfer issues

## Future Enhancements

- **Automated Approvals**: Auto-approve small amounts
- **Scheduled Withdrawals**: Recurring withdrawal requests
- **Multiple Currencies**: Support for other currencies
- **Mobile App**: Native mobile application
- **Advanced Analytics**: Detailed reporting dashboard
