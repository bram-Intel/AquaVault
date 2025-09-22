<?php
/**
 * AquaVault Capital - Modern Enterprise Dashboard
 * Sophisticated FinTech UI inspired by Cowrywise, Notion, Slack
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];

// Get user details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("User fetch error: " . $e->getMessage());
    header('Location: login.php');
    exit();
}

// Get active investments (only new system)
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, ic.name as category_name, ic.icon as category_icon, id.name as duration_name
        FROM user_investments ui
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.user_id = ? AND ui.status = 'active' AND ui.category_id IS NOT NULL
        ORDER BY ui.maturity_date ASC
    ");
    $stmt->execute([$user_id]);
    $active_investments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Active investments fetch error: " . $e->getMessage());
    $active_investments = [];
}

// Get recent transactions (only new system)
try {
    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE user_id = ? AND description LIKE '%Investment in%'
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Transactions fetch error: " . $e->getMessage());
    $recent_transactions = [];
}

// Calculate total active investment value
$total_active_investment = 0;
$total_expected_returns = 0;
foreach ($active_investments as $investment) {
    $total_active_investment += $investment['amount'];
    $total_expected_returns += $investment['net_return'];
}

$total_portfolio_value = $total_active_investment + $total_expected_returns;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93c5fd;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --secondary-50: #f0f9ff;
            --secondary-600: #0284c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
            --success-100: #dcfce7;
            --success-500: #22c55e;
            --success-600: #16a34a;
            --warning-500: #eab308;
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-12: 3rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
            --radius-full: 9999px;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --transition-normal: all 0.3s ease;
            --transition-fast: all 0.15s ease;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-4xl: 2.25rem;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--gray-50);
            line-height: 1.5;
        }

        .container-wide {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-4);
        }

        @media (max-width: 768px) {
            .container-wide {
                padding: 0 var(--space-3);
            }
        }

        /* Responsive Grid System */
        .grid {
            display: grid;
            gap: var(--space-6);
        }

        .grid-cols-1 { grid-template-columns: repeat(1, 1fr); }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

        @media (min-width: 768px) {
            .md\:grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
            .md\:grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
        }

        @media (min-width: 1024px) {
            .lg\:grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
            .lg\:col-span-2 { grid-column: span 2; }
        }

        @media (max-width: 640px) {
            .grid {
                gap: var(--space-4);
            }
        }

        /* Flexbox utilities */
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Spacing utilities */
        .mb-6 { margin-bottom: var(--space-6); }
        .mb-8 { margin-bottom: var(--space-8); }

     

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            color: white;
            padding: var(--space-8) 0;
            margin-bottom: var(--space-8);
        }

        .welcome-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-6);
        }

        .welcome-title {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            margin: 0 0 var(--space-2) 0;
        }

        .welcome-subtitle {
            font-size: var(--font-size-lg);
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        .portfolio-summary {
            text-align: right;
        }

        .portfolio-value {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            margin-bottom: var(--space-1);
        }

        .portfolio-label {
            font-size: var(--font-size-sm);
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .welcome-section {
                padding: var(--space-6) 0;
            }

            .welcome-content {
                flex-direction: column;
                gap: var(--space-4);
                text-align: center;
                padding: 0 var(--space-4);
            }

            .welcome-title {
                font-size: var(--font-size-2xl);
            }

            .portfolio-summary {
                text-align: center;
            }
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .card-header {
            padding: var(--space-6);
            border-bottom: 1px solid var(--gray-200);
        }

        .card-body {
            padding: var(--space-6);
        }

        @media (max-width: 640px) {
            .card-header,
            .card-body {
                padding: var(--space-4);
            }
        }

        /* Metric Cards */
        .metric-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            text-align: center;
            transition: all var(--transition-normal);
        }

        .metric-card:hover {
            border-color: var(--primary-300);
            box-shadow: var(--shadow-md);
        }

        .metric-value {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--gray-900);
            margin-bottom: var(--space-2);
        }

        .metric-label {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
            font-weight: var(--font-weight-medium);
        }

        @media (max-width: 640px) {
            .metric-card {
                padding: var(--space-4);
            }

            .metric-value {
                font-size: var(--font-size-xl);
            }
        }

        /* Investment Cards */
        .investment-card {
            transition: all var(--transition-normal);
            cursor: pointer;
        }

        .investment-card:hover {
            background-color: var(--gray-50);
        }

        @media (max-width: 640px) {
            .investment-card .flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: var(--space-3);
            }

            .investment-card .text-right {
                text-align: left;
                width: 100%;
            }
        }

        /* Quick Actions */
        .quick-action {
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

        .quick-action:hover {
            border-color: var(--primary-300);
            box-shadow: var(--shadow-md);
            color: var(--primary-600);
            text-decoration: none;
            transform: translateY(-2px);
        }

        @media (max-width: 640px) {
            .quick-action {
                padding: var(--space-4);
            }
        }

        /* Transaction Items */
        .transaction-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-4);
            border-bottom: 1px solid var(--gray-100);
            transition: background-color var(--transition-fast);
        }

        .transaction-item:hover {
            background-color: var(--gray-50);
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 640px) {
            .transaction-item {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-2);
            }

            .transaction-item .text-right {
                text-align: left;
                width: 100%;
            }
        }

        /* Avatars */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--primary-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: var(--font-weight-semibold);
            color: var(--primary-600);
            flex-shrink: 0;
        }

        /* Status indicators */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: var(--radius-full);
            background-color: var(--success-500);
            margin-right: var(--space-2);
            flex-shrink: 0;
        }

        .status-dot.pending {
            background-color: var(--warning-500);
        }

        .status-dot.matured {
            background-color: var(--primary-500);
        }

        /* Buttons */
        .btn {
            padding: var(--space-2) var(--space-4);
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-medium);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
            cursor: pointer;
            border: 1px solid transparent;
            transition: all var(--transition-fast);
        }

        .btn-primary {
            background: var(--primary-600);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-700);
            color: white;
        }

        .btn-ghost {
            background: transparent;
            color: var(--gray-500);
        }

        .btn-ghost:hover {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn-sm {
            padding: var(--space-2) var(--space-3);
            font-size: var(--font-size-sm);
        }

        /* Status badges */
        .status {
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-medium);
        }

        .status-success {
            background: var(--success-100);
            color: var(--success-600);
        }

        /* User menu responsive */
        .user-menu {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        @media (max-width: 768px) {
            .user-menu {
                gap: var(--space-2);
            }

            .user-info {
                display: none;
            }
        }

        /* Responsive utilities */
        @media (max-width: 640px) {
            .sm\:hidden {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .md\:hidden {
                display: none;
            }
        }

        /* Empty state improvements */
        .empty-state {
            text-align: center;
            padding: var(--space-12);
        }

        @media (max-width: 640px) {
            .empty-state {
                padding: var(--space-8);
            }
        }

        /* Scrollable containers */
        .scrollable {
            max-height: 400px;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .scrollable {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Sidebar Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <div class="welcome-text">
                <h1 class="welcome-title">
                    Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>
                </h1>
                <p class="welcome-subtitle">
                    Here's your investment portfolio overview
                </p>
            </div>
            <div class="portfolio-summary">
                <div class="portfolio-value">
                    ₦<?php echo number_format($total_portfolio_value); ?>
                </div>
                <div class="portfolio-label">
                    Total Portfolio Value
                </div>
            </div>
        </div>
    </div>

        <!-- Portfolio Overview -->
        <div class="grid grid-cols-2 md:grid-cols-4 mb-8">
            <div class="metric-card">
                <div class="metric-value">₦<?php echo number_format($total_active_investment); ?></div>
                <div class="metric-label">Active Investments</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">₦<?php echo number_format($total_expected_returns); ?></div>
                <div class="metric-label">Expected Returns</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo count($active_investments); ?></div>
                <div class="metric-label">Active Plans</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">₦<?php echo number_format($user['wallet_balance']); ?></div>
                <div class="metric-label">Wallet Balance</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="invest.php" class="quick-action">
                <div style="font-size: 24px; margin-bottom: var(--space-2);">📈</div>
                <div style="font-weight: var(--font-weight-semibold);">Invest</div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-500);">Start Investing</div>
            </a>
            <a href="withdraw.php" class="quick-action">
                <div style="font-size: 24px; margin-bottom: var(--space-2);">💰</div>
                <div style="font-weight: var(--font-weight-semibold);">Withdraw</div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-500);">Request Withdrawal</div>
            </a>
            <a href="bank_accounts.php" class="quick-action">
                <div style="font-size: 24px; margin-bottom: var(--space-2);">🏦</div>
                <div style="font-weight: var(--font-weight-semibold);">Bank Accounts</div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-500);">Manage Accounts</div>
            </a>
            <a href="withdrawal_requests.php" class="quick-action">
                <div style="font-size: 24px; margin-bottom: var(--space-2);">📋</div>
                <div style="font-weight: var(--font-weight-semibold);">Requests</div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-500);">View Requests</div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3">
            <!-- Active Investments -->
            <div class="lg:col-span-2 mb-8">
                <div class="card">
                    <div class="card-header">
                        <div class="flex items-center justify-between">
                            <h2 style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold); margin: 0;">
                                Active Investments
                            </h2>
                            <a href="invest.php" class="btn btn-primary btn-sm">
                                <span class="sm:hidden">+</span>
                                <span class="md:hidden">New</span>
                                <span style="display: none;" class="md:inline">New Investment</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($active_investments)): ?>
                            <div class="empty-state">
                                <div style="font-size: 48px; margin-bottom: var(--space-4);">📊</div>
                                <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--gray-700); margin-bottom: var(--space-2);">
                                    No Active Investments
                                </h3>
                                <p style="color: var(--gray-500); margin-bottom: var(--space-6);">
                                    Start building your portfolio with our investment plans
                                </p>
                                <a href="invest.php" class="btn btn-primary">Start Investing</a>
                            </div>
                        <?php else: ?>
                            <div class="scrollable">
                                <?php foreach ($active_investments as $investment): ?>
                                    <div class="investment-card" style="padding: var(--space-6); border-bottom: 1px solid var(--gray-100);">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center" style="gap: var(--space-4);">
                                                <div style="font-size: 24px;">
                                                    <?php echo $investment['category_icon'] ?? '📊'; ?>
                                                </div>
                                                <div>
                                                    <h3 style="font-size: var(--font-size-base); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-1) 0;">
                                                        <?php echo htmlspecialchars($investment['category_name'] ?? 'Investment'); ?>
                                                    </h3>
                                                    <p style="font-size: var(--font-size-sm); color: var(--gray-500); margin: 0;">
                                                        <?php echo htmlspecialchars($investment['duration_name'] ?? 'Duration'); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin-bottom: var(--space-1);">
                                                    ₦<?php echo number_format($investment['amount']); ?>
                                                </div>
                                                <div class="flex items-center">
                                                    <div class="status-dot"></div>
                                                    <span style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                                        Matures <?php echo date('M d, Y', strtotime($investment['maturity_date'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                

                <!-- Recent Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0;">
                            Recent Activity
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($recent_transactions)): ?>
                            <div class="empty-state">
                                <div style="font-size: 32px; margin-bottom: var(--space-3);">📝</div>
                                <p style="color: var(--gray-500); font-size: var(--font-size-sm);">No recent activity</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <div class="transaction-item">
                                    <div class="flex items-center" style="gap: var(--space-3);">
                                        <div style="width: 32px; height: 32px; background: var(--success-100); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 14px;">💰</span>
                                        </div>
                                        <div>
                                            <div style="font-size: var(--font-size-sm); font-weight: var(--font-weight-medium);">
                                                Investment
                                            </div>
                                            <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                                <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div style="font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--success-600);">
                                            +₦<?php echo number_format($transaction['amount']); ?>
                                        </div>
                                        <div class="status status-success">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card, .metric-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Show/hide elements based on screen size
            function handleResize() {
                const width = window.innerWidth;
                const mdElements = document.querySelectorAll('.md\\:inline');
                
                mdElements.forEach(el => {
                    if (width >= 768) {
                        el.style.display = 'inline';
                    } else {
                        el.style.display = 'none';
                    }
                });
            }

            // Initial call and resize listener
            handleResize();
            window.addEventListener('resize', handleResize);

            // Enhanced mobile interactions
            const investmentCards = document.querySelectorAll('.investment-card');
            investmentCards.forEach(card => {
                card.addEventListener('touchstart', function() {
                    this.style.backgroundColor = 'var(--gray-50)';
                });
                
                card.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 150);
                });
            });

            // Smooth scroll for better mobile experience
            if ('scrollBehavior' in document.documentElement.style) {
                document.documentElement.style.scrollBehavior = 'smooth';
            }
        });

        // Handle touch interactions for better mobile UX
        function addTouchSupport() {
            const interactiveElements = document.querySelectorAll('.quick-action, .btn, .transaction-item');
            
            interactiveElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }

        // Initialize touch support
        addTouchSupport();

        // Performance optimization for scroll
        let ticking = false;
        function updateOnScroll() {
            if (!ticking) {
                requestAnimationFrame(() => {
                    // Add scroll-based animations or updates here
                    ticking = false;
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', updateOnScroll, { passive: true });
    </script>
    </main>
</div>
</body>
</html>