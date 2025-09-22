# AquaVault Capital - Fixed-Term Investment Platform

A secure, modern, mobile-responsive investment platform built with pure PHP for cPanel hosting. Inspired by FairLock and Cowrywise, AquaVault Capital allows users to lock funds into fixed-term investment plans with guaranteed returns.

## 🚀 Features

### User Features
- **User Registration & Authentication** - Secure user registration and login system
- **KYC Verification** - Document upload and admin approval system
- **Investment Plans** - Multiple fixed-term investment options with different rates
- **Real-time Calculator** - Live calculation of returns and maturity dates
- **Paystack Integration** - Secure payment processing
- **Dashboard** - Comprehensive investment tracking and portfolio overview
- **Profile Management** - Update profile, change password, upload avatar
- **Mobile Responsive** - Optimized for all devices

### Admin Features
- **Admin Panel** - Complete administrative dashboard
- **KYC Management** - Approve/reject user KYC documents
- **Investment Plan Management** - Create, edit, and manage investment plans
- **User Management** - View and manage user accounts
- **Transaction Monitoring** - Track all platform transactions
- **Statistics Dashboard** - Platform analytics and insights

## 🛠️ Technology Stack

- **Backend**: Pure PHP 7.4+ (no frameworks)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript, Tailwind CSS
- **Payment**: Paystack API
- **Security**: PDO prepared statements, CSRF protection, input validation
- **Hosting**: cPanel compatible (no SSH/terminal required)

## 📁 Project Structure

```
AquaVault/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   ├── manage_plans.php   # Investment plan management
│   ├── users.php          # User management
│   ├── kyc_approvals.php  # KYC approval system
│   └── view_kyc.php       # View KYC documents
├── api/                   # API endpoints
│   ├── calculator.php     # Investment calculator API
│   └── webhook.php        # Paystack webhook handler
├── assets/                # Static assets
│   └── uploads/           # User uploads
│       ├── kyc/           # KYC documents
│       └── avatars/       # User avatars
├── config/                # Configuration files
│   └── paystack.php       # Paystack configuration
├── db/                    # Database files
│   ├── connect.php        # Database connection
│   └── schema.sql         # Database schema
├── includes/              # Shared components
│   ├── auth.php           # Authentication helpers
│   └── navbar.php         # Navigation component
├── user/                  # User-facing files
│   ├── dashboard.php      # User dashboard
│   ├── login.php          # User login
│   ├── register.php       # User registration
│   ├── logout.php         # User logout
│   ├── profile.php        # User profile
│   ├── kyc.php            # KYC upload
│   ├── kyc_status.php     # KYC status
│   ├── process_kyc.php    # KYC processing
│   ├── invest.php         # Investment plans
│   ├── invest_amount.php  # Amount selection
│   ├── invest_review.php  # Investment review
│   ├── success.php        # Payment success
│   ├── upload_avatar.php  # Avatar upload
│   ├── update_profile.php # Profile update
│   └── change_password.php # Password change
├── index.php              # Landing page
└── README.md              # This file
```

## 🚀 Installation & Deployment

### Prerequisites
- cPanel hosting account
- MySQL database access
- PHP 7.4 or higher
- Paystack account (for payments)

### Step 1: Database Setup
1. Create a MySQL database in cPanel
2. Create a database user and assign privileges
3. Import the database schema:
   ```sql
   -- Run the contents of db/schema.sql in phpMyAdmin
   ```

### Step 2: Configuration
1. Update `db/connect.php` with your database credentials:
   ```php
   $db_host = 'localhost';
   $db_name = 'your_database_name';
   $db_user = 'your_database_user';
   $db_pass = 'your_database_password';
   ```

2. Update `config/paystack.php` with your Paystack keys:
   ```php
   define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_public_key');
   define('PAYSTACK_SECRET_KEY', 'sk_test_your_secret_key');
   ```

3. Update callback URLs in `config/paystack.php`:
   ```php
   define('CALLBACK_URL', 'https://yourdomain.com/user/success.php');
   define('WEBHOOK_URL', 'https://yourdomain.com/api/webhook.php');
   ```

### Step 3: File Upload
1. Upload all files to your cPanel public_html directory
2. Set proper permissions:
   - `/assets/uploads/kyc/` → 755 (or 777 if upload fails)
   - `/assets/uploads/avatars/` → 755 (or 777 if upload fails)

### Step 4: Paystack Webhook Setup
1. In your Paystack dashboard, add webhook URL:
   `https://yourdomain.com/api/webhook.php`
2. Enable events: `charge.success`, `charge.failed`

### Step 5: SSL Certificate
1. Enable SSL certificate in cPanel (Let's Encrypt)
2. Force HTTPS redirect for security

## 🔐 Security Features

- **CSRF Protection** - All forms protected with CSRF tokens
- **Input Validation** - All user inputs validated and sanitized
- **SQL Injection Prevention** - PDO prepared statements
- **XSS Protection** - htmlspecialchars() on all outputs
- **Password Hashing** - bcrypt password hashing
- **Session Security** - Secure session management
- **File Upload Security** - Validated file types and sizes

## 💳 Payment Integration

### Paystack Configuration
- Test mode supported for development
- Production keys for live environment
- Webhook handling for payment verification
- Automatic investment activation on successful payment

### Payment Flow
1. User selects investment plan and amount
2. Payment initialized via Paystack
3. User completes payment
4. Webhook verifies payment
5. Investment automatically activated
6. User receives confirmation

## 📱 Mobile Responsiveness

- Built with Tailwind CSS for mobile-first design
- Responsive navigation with mobile menu
- Touch-friendly interface elements
- Optimized for all screen sizes

## 🎨 UI/UX Features

- **Clean Design** - Modern, professional interface
- **AquaVault Branding** - Blue (#007BFF) and green (#28A745) color scheme
- **Interactive Elements** - Hover effects and smooth transitions
- **Success Animations** - Confetti animation on payment success
- **Progress Indicators** - Step-by-step investment process
- **Lock Icons** - Visual indicators for active investments

## 📊 Admin Panel Features

- **Dashboard Analytics** - User statistics and platform metrics
- **KYC Management** - Review and approve user documents
- **Plan Management** - Create and manage investment plans
- **User Management** - View and manage user accounts
- **Transaction Monitoring** - Track all platform activities

## 🔧 Customization

### Adding New Investment Plans
1. Access admin panel
2. Go to "Manage Plans"
3. Click "Create New Investment Plan"
4. Fill in plan details
5. Set as active to make available to users

### Modifying UI Colors
Update the CSS variables in each file:
```css
.gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
```

### Adding New Features
The modular structure makes it easy to add new features:
- Add new PHP files in appropriate directories
- Update navigation in `includes/navbar.php`
- Add database tables as needed
- Follow existing security patterns

## 🐛 Troubleshooting

### Common Issues

1. **File Upload Errors**
   - Check directory permissions (755 or 777)
   - Verify upload_max_filesize in PHP settings
   - Ensure directory exists

2. **Database Connection Errors**
   - Verify database credentials in `db/connect.php`
   - Check database server status
   - Ensure database user has proper privileges

3. **Payment Issues**
   - Verify Paystack keys are correct
   - Check webhook URL configuration
   - Ensure SSL certificate is active

4. **KYC Upload Issues**
   - Check file size limits (max 5MB)
   - Verify allowed file types (JPG, PNG, PDF)
   - Ensure upload directory permissions

## 📞 Support

For technical support or questions:
- Email: support@aquavault.com
- Documentation: This README file
- Issues: Check error logs in cPanel

## 📄 License

This project is proprietary software. All rights reserved.

## 🔄 Updates & Maintenance

### Regular Maintenance Tasks
1. Monitor error logs
2. Update Paystack keys if needed
3. Backup database regularly
4. Update SSL certificates
5. Monitor user activity and transactions

### Security Updates
1. Keep PHP version updated
2. Monitor for security vulnerabilities
3. Update dependencies as needed
4. Regular security audits

---

**AquaVault Capital** - Secure your future with fixed-term investments! 🚀
