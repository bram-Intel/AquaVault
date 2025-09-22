# AquaVault Capital - Deployment Guide

This guide will walk you through deploying AquaVault Capital on a cPanel hosting environment.

## 📋 Pre-Deployment Checklist

- [ ] cPanel hosting account with PHP 7.4+
- [ ] MySQL database access
- [ ] Paystack account (test and live keys)
- [ ] Domain name with SSL certificate capability
- [ ] File manager access in cPanel

## 🗄️ Database Setup

### Step 1: Create Database
1. Login to cPanel
2. Go to "MySQL Databases"
3. Create a new database: `aquavault_db`
4. Create a database user: `aquavault_user`
5. Set a strong password for the user
6. Add the user to the database with "ALL PRIVILEGES"

### Step 2: Import Schema
1. Go to "phpMyAdmin" in cPanel
2. Select your database
3. Click "Import" tab
4. Upload the `db/schema.sql` file
5. Click "Go" to import

### Step 3: Verify Import
Check that these tables were created:
- `users`
- `admin_users`
- `investment_plans`
- `user_investments`
- `transactions`
- `system_settings`

## 📁 File Upload

### Step 1: Upload Files
1. Go to "File Manager" in cPanel
2. Navigate to `public_html` directory
3. Upload all project files maintaining the directory structure
4. Ensure all files are uploaded successfully

### Step 2: Set Permissions
Set the following directory permissions:
```bash
/assets/uploads/kyc/ → 755 (or 777 if upload fails)
/assets/uploads/avatars/ → 755 (or 777 if upload fails)
```

### Step 3: Verify Structure
Ensure your directory structure looks like this:
```
public_html/
├── admin/
├── api/
├── assets/
├── config/
├── db/
├── includes/
├── user/
├── index.php
└── README.md
```

## ⚙️ Configuration

### Step 1: Database Configuration
Edit `db/connect.php`:
```php
$db_host = 'localhost';
$db_name = 'your_database_name';
$db_user = 'your_database_user';
$db_pass = 'your_database_password';
```

### Step 2: Paystack Configuration
Edit `config/paystack.php`:

**For Testing:**
```php
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_test_public_key');
define('PAYSTACK_SECRET_KEY', 'sk_test_your_test_secret_key');
```

**For Production:**
```php
define('PAYSTACK_PUBLIC_KEY', 'pk_live_your_live_public_key');
define('PAYSTACK_SECRET_KEY', 'sk_live_your_live_secret_key');
```

**Update URLs:**
```php
define('CALLBACK_URL', 'https://yourdomain.com/user/success.php');
define('WEBHOOK_URL', 'https://yourdomain.com/api/webhook.php');
```

## 🔐 Security Setup

### Step 1: SSL Certificate
1. In cPanel, go to "SSL/TLS"
2. Enable "Let's Encrypt" SSL
3. Force HTTPS redirect
4. Verify SSL is working

### Step 2: File Permissions
Ensure sensitive files have proper permissions:
```bash
config/paystack.php → 644
db/connect.php → 644
```

### Step 3: Admin Account
The default admin account is created automatically:
- Username: `admin`
- Password: `admin123`
- Email: `admin@aquavault.com`

**⚠️ IMPORTANT: Change the admin password immediately after first login!**

## 💳 Paystack Integration

### Step 1: Webhook Setup
1. Login to your Paystack dashboard
2. Go to "Settings" → "Webhooks"
3. Add webhook URL: `https://yourdomain.com/api/webhook.php`
4. Enable these events:
   - `charge.success`
   - `charge.failed`
5. Save webhook configuration

### Step 2: Test Payments
1. Use test keys for development
2. Test the complete payment flow
3. Verify webhook is receiving events
4. Switch to live keys for production

## 🧪 Testing

### Step 1: Basic Functionality
1. Visit your domain
2. Test user registration
3. Test admin login
4. Verify database connections

### Step 2: User Flow Testing
1. Register a new user
2. Upload KYC document
3. Login as admin and approve KYC
4. Test investment flow
5. Test payment with Paystack test keys

### Step 3: Admin Panel Testing
1. Login as admin
2. Create investment plans
3. Manage users
4. Review KYC documents

## 🚀 Go Live Checklist

- [ ] SSL certificate active
- [ ] Database properly configured
- [ ] Paystack live keys configured
- [ ] Webhook URL set in Paystack
- [ ] File permissions set correctly
- [ ] Admin password changed
- [ ] Test payment flow completed
- [ ] Error logging enabled
- [ ] Backup system in place

## 📊 Post-Deployment

### Monitoring
1. Check error logs regularly
2. Monitor user registrations
3. Track payment success rates
4. Monitor server performance

### Maintenance
1. Regular database backups
2. Update SSL certificates
3. Monitor security updates
4. Review user feedback

## 🆘 Troubleshooting

### Common Issues

**Database Connection Error:**
- Verify database credentials
- Check database server status
- Ensure user has proper privileges

**File Upload Issues:**
- Check directory permissions
- Verify PHP upload limits
- Check available disk space

**Payment Issues:**
- Verify Paystack keys
- Check webhook configuration
- Ensure SSL is active

**KYC Upload Problems:**
- Check file size limits
- Verify allowed file types
- Check upload directory permissions

### Error Logs
Check these locations for error logs:
- cPanel Error Logs
- PHP Error Logs
- Application logs in `/assets/logs/` (if created)

## 📞 Support

If you encounter issues:
1. Check this deployment guide
2. Review error logs
3. Contact your hosting provider
4. Contact Paystack support for payment issues

## 🔄 Updates

To update the application:
1. Backup current files and database
2. Upload new files
3. Run any database migrations
4. Test functionality
5. Update configuration if needed

---

**Deployment Complete!** 🎉

Your AquaVault Capital platform should now be live and ready for users.
