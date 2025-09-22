<?php
/**
 * AquaVault Capital - Modern Enterprise Admin Dashboard
 * Sophisticated FinTech admin interface
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if admin is logged in
require_admin();

$admin_id = $_SESSION['admin_id'];

// Get admin details
try {
    $stmt = $pdo->prepare("SELECT username, full_name, role FROM admin_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Admin fetch error: " . $e->getMessage());
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stmt->execute();
    $total_users = $stmt->fetch()['total'];
    
    // Pending KYC
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE kyc_status = 'pending'");
    $stmt->execute();
    $pending_kyc = $stmt->fetch()['total'];
    
    // Pending investments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_investments WHERE status = 'pending'");
    $stmt->execute();
    $pending_investments = $stmt->fetch()['total'];
    
    // Active investments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_investments WHERE status = 'active'");
    $stmt->execute();
    $active_investments = $stmt->fetch()['total'];
    
    // Total invested amount
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM user_investments WHERE status = 'active'");
    $stmt->execute();
    $total_invested = $stmt->fetch()['total'] ?? 0;
    
    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT t.*, u.first_name, u.last_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll();
    
    // Pending KYC documents
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, kyc_submitted_at 
        FROM users 
        WHERE kyc_status = 'pending' 
        ORDER BY kyc_submitted_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $pending_kyc_users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $total_users = $pending_kyc = $pending_investments = $active_investments = 0;
    $total_invested = 0;
    $recent_transactions = $pending_kyc_users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-900) 100%);
            color: white;
            padding: var(--space-8) 0;
            margin-bottom: var(--space-8);
        }
        
        .metric-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-500);
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-300);
        }
        
        .metric-card.warning::before {
            background: var(--warning-500);
        }
        
        .metric-card.success::before {
            background: var(--success-500);
        }
        
        .metric-card.error::before {
            background: var(--error-500);
        }
        
        .metric-value {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: var(--space-2);
        }
        
        .metric-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .metric-change {
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-medium);
            margin-top: var(--space-1);
        }
        
        .metric-change.positive {
            color: var(--success-600);
        }
        
        .metric-change.negative {
            color: var(--error-600);
        }
        
        .action-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            text-align: center;
            text-decoration: none;
            color: var(--gray-700);
            transition: all var(--transition-normal);
            display: block;
        }
        
        .action-card:hover {
            border-color: var(--primary-300);
            box-shadow: var(--shadow-md);
            color: var(--primary-600);
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        .action-card.primary {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            color: white;
            border-color: var(--primary-600);
        }
        
        .action-card.primary:hover {
            background: linear-gradient(135deg, var(--primary-700), var(--primary-800));
            color: white;
        }
        
        .table-container {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--gray-50);
            padding: var(--space-6);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            padding: var(--space-4) var(--space-6);
            text-align: left;
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-semibold);
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .table td {
            padding: var(--space-4) var(--space-6);
            border-bottom: 1px solid var(--gray-100);
            font-size: var(--font-size-sm);
        }
        
        .table tbody tr:hover {
            background-color: var(--gray-50);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            padding: var(--space-1) var(--space-3);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-medium);
            border-radius: var(--radius-full);
        }
        
        .status-badge.success {
            background-color: var(--success-50);
            color: var(--success-600);
        }
        
        .status-badge.warning {
            background-color: var(--warning-50);
            color: var(--warning-600);
        }
        
        .status-badge.error {
            background-color: var(--error-50);
            color: var(--error-600);
        }
        
        .status-badge.info {
            background-color: var(--primary-50);
            color: var(--primary-600);
        }
        
        .alert-banner {
            background: linear-gradient(135deg, var(--warning-50), var(--warning-100));
            border: 1px solid var(--warning-200);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            margin-bottom: var(--space-8);
        }
        
        .alert-banner.error {
            background: linear-gradient(135deg, var(--error-50), var(--error-100));
            border-color: var(--error-200);
        }
        
        .sidebar {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
        }
        
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3);
            border-radius: var(--radius-lg);
            text-decoration: none;
            color: var(--gray-700);
            transition: all var(--transition-fast);
            margin-bottom: var(--space-2);
        }
        
        .sidebar-item:hover {
            background-color: var(--gray-50);
            color: var(--primary-600);
            text-decoration: none;
        }
        
        .sidebar-item.active {
            background-color: var(--primary-50);
            color: var(--primary-600);
        }
    </style>
</head>
<body>
    <!-- Modern Admin Navigation -->
    <nav class="navbar">
        <div class="container-wide">
            <div class="flex items-center justify-between" style="height: 64px;">
                <!-- Brand -->
                <a href="dashboard.php" class="navbar-brand">
                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--gray-800), var(--gray-900)); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-weight: var(--font-weight-bold); font-size: 14px;">AV</span>
                    </div>
                    <span>AquaVault Admin</span>
                </a>

                <!-- Navigation -->
                <div class="navbar-nav">
                    <a href="dashboard.php" class="nav-link active">Dashboard</a>
                    <a href="manage_investments.php" class="nav-link">Investments</a>
                    <a href="manage_categories.php" class="nav-link">Categories</a>
                    <a href="users.php" class="nav-link">Users</a>
                    <a href="kyc_approvals.php" class="nav-link">KYC</a>
                </div>

                <!-- Admin Menu -->
                <div class="flex items-center" style="gap: var(--space-4);">
                    <div style="width: 32px; height: 32px; border-radius: var(--radius-full); background: var(--error-100); display: flex; align-items: center; justify-content: center; font-weight: var(--font-weight-semibold); color: var(--error-600);">
                        A
                    </div>
                    <div>
                        <div style="font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); color: var(--gray-900);">
                            <?php echo htmlspecialchars($admin['full_name']); ?>
                        </div>
                        <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                            <?php echo ucfirst($admin['role']); ?>
                        </div>
                    </div>
                    <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-wide">
            <div class="flex items-center justify-between">
                <div>
                    <h1 style="font-size: var(--font-size-4xl); font-weight: var(--font-weight-bold); margin: 0 0 var(--space-2) 0;">
                        Admin Dashboard
                    </h1>
                    <p style="font-size: var(--font-size-lg); color: rgba(255, 255, 255, 0.8); margin: 0;">
                        Platform overview and management center
                    </p>
                </div>
                <div class="text-right">
                    <div style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); margin-bottom: var(--space-1);">
                        ₦<?php echo number_format($total_invested); ?>
                    </div>
                    <div style="font-size: var(--font-size-sm); color: rgba(255, 255, 255, 0.8);">
                        Total Assets Under Management
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-wide">
        <!-- Alert Banners -->
        <?php if ($pending_investments > 0): ?>
            <div class="alert-banner">
                <div class="flex items-center">
                    <div style="font-size: 24px; margin-right: var(--space-3);">⚠️</div>
                    <div>
                        <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-1) 0;">
                            Pending Investments Require Approval
                        </h3>
                        <p style="margin: 0; color: var(--warning-800);">
                            You have <?php echo $pending_investments; ?> pending investment<?php echo $pending_investments > 1 ? 's' : ''; ?> that need<?php echo $pending_investments == 1 ? 's' : ''; ?> approval.
                            <a href="manage_investments.php" style="color: var(--warning-900); font-weight: var(--font-weight-semibold); text-decoration: underline;">Review and approve now →</a>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pending_kyc > 0): ?>
            <div class="alert-banner">
                <div class="flex items-center">
                    <div style="font-size: 24px; margin-right: var(--space-3);">📄</div>
                    <div>
                        <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-1) 0;">
                            Pending KYC Reviews
                        </h3>
                        <p style="margin: 0; color: var(--warning-800);">
                            You have <?php echo $pending_kyc; ?> KYC document<?php echo $pending_kyc > 1 ? 's' : ''; ?> awaiting review.
                            <a href="kyc_approvals.php" style="color: var(--warning-900); font-weight: var(--font-weight-semibold); text-decoration: underline;">Review KYC documents →</a>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="metric-card">
                <div class="metric-value"><?php echo number_format($total_users); ?></div>
                <div class="metric-label">Total Users</div>
                <div class="metric-change positive">+12% this month</div>
            </div>
            <div class="metric-card warning">
                <div class="metric-value"><?php echo number_format($pending_kyc); ?></div>
                <div class="metric-label">Pending KYC</div>
                <div class="metric-change"><?php echo $pending_kyc > 0 ? 'Requires attention' : 'All clear'; ?></div>
            </div>
            <div class="metric-card warning">
                <div class="metric-value"><?php echo number_format($pending_investments); ?></div>
                <div class="metric-label">Pending Investments</div>
                <div class="metric-change"><?php echo $pending_investments > 0 ? 'Awaiting approval' : 'All processed'; ?></div>
            </div>
            <div class="metric-card success">
                <div class="metric-value"><?php echo number_format($active_investments); ?></div>
                <div class="metric-label">Active Investments</div>
                <div class="metric-change positive">+8% this week</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <a href="manage_investments.php" class="action-card primary">
                        <div style="font-size: 32px; margin-bottom: var(--space-3);">💰</div>
                        <div style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin-bottom: var(--space-2);">
                            Manage Investments
                        </div>
                        <div style="font-size: var(--font-size-sm); opacity: 0.9;">
                            Review and approve pending investments
                        </div>
                    </a>
                    <a href="kyc_approvals.php" class="action-card">
                        <div style="font-size: 32px; margin-bottom: var(--space-3);">📄</div>
                        <div style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin-bottom: var(--space-2);">
                            KYC Reviews
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                            Process KYC document submissions
                        </div>
                    </a>
                    <a href="users.php" class="action-card">
                        <div style="font-size: 32px; margin-bottom: var(--space-3);">👥</div>
                        <div style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin-bottom: var(--space-2);">
                            User Management
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                            View and manage user accounts
                        </div>
                    </a>
                </div>

                <!-- Recent Transactions -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold); margin: 0;">
                            Recent Transactions
                        </h2>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: var(--space-8); color: var(--gray-500);">
                                        No recent transactions
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: var(--font-weight-medium);">
                                                <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="text-transform: capitalize;">
                                                <?php echo htmlspecialchars($transaction['type']); ?>
                                            </span>
                                        </td>
                                        <td style="font-weight: var(--font-weight-semibold);">
                                            ₦<?php echo number_format($transaction['amount']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'error'); ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--gray-600);">
                                            <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <div class="sidebar">
                    <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-4) 0;">
                        Quick Actions
                    </h3>
                    <a href="manage_investments.php" class="sidebar-item">
                        <span style="font-size: 20px;">💰</span>
                        <span>Manage Investments</span>
                    </a>
                    <a href="manage_categories.php" class="sidebar-item">
                        <span style="font-size: 20px;">📊</span>
                        <span>Investment Categories</span>
                    </a>
                    <a href="manage_durations.php" class="sidebar-item">
                        <span style="font-size: 20px;">⏰</span>
                        <span>Duration Settings</span>
                    </a>
                    <a href="users.php" class="sidebar-item">
                        <span style="font-size: 20px;">👥</span>
                        <span>User Management</span>
                    </a>
                    <a href="kyc_approvals.php" class="sidebar-item">
                        <span style="font-size: 20px;">📄</span>
                        <span>KYC Approvals</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.metric-card, .action-card, .table-container');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
