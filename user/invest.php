<?php
/**
 * AquaVault Capital - Modern Investment Selection
 * Sophisticated FinTech UI for investment categories
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];

// Check KYC status
require_kyc_approved($user_id, $pdo);

// Get investment categories
try {
    $stmt = $pdo->prepare("
        SELECT * FROM investment_categories 
        WHERE is_active = 1 
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Plans - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            color: white;
            padding: var(--space-16) 0;
            text-align: center;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-6);
        }

        .hero-title {
            font-size: var(--font-size-5xl);
            font-weight: var(--font-weight-bold);
            margin: 0 0 var(--space-4) 0;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: var(--font-size-xl);
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: var(--space-12) 0;
            }

            .hero-title {
                font-size: var(--font-size-3xl);
            }

            .hero-subtitle {
                font-size: var(--font-size-lg);
            }

            .hero-content {
                padding: 0 var(--space-4);
            }
        }
        
        .category-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            text-align: center;
            text-decoration: none;
            color: var(--gray-900);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-500), var(--secondary-500));
            transform: scaleX(0);
            transition: transform var(--transition-normal);
        }
        
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-2xl);
            border-color: var(--primary-300);
            text-decoration: none;
            color: var(--gray-900);
        }
        
        .category-card:hover::before {
            transform: scaleX(1);
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-2xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto var(--space-4) auto;
            background: var(--gray-50);
            transition: all var(--transition-normal);
        }
        
        .category-card:hover .category-icon {
            background: var(--primary-50);
            transform: scale(1.1);
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--space-6);
            margin: var(--space-12) 0;
        }
        
        .feature-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            text-align: center;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-xl);
            background: var(--primary-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto var(--space-4) auto;
            color: var(--primary-600);
        }
        
        .stats-section {
            background: var(--gray-50);
            padding: var(--space-16) 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: var(--font-size-4xl);
            font-weight: var(--font-weight-bold);
            color: var(--primary-600);
            line-height: 1;
            margin-bottom: var(--space-2);
        }
        
        .stat-label {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .floating-action {
            position: fixed;
            bottom: var(--space-6);
            right: var(--space-6);
            z-index: 50;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            font-size: var(--font-size-sm);
            color: var(--gray-600);
            margin-bottom: var(--space-8);
        }
        
        .breadcrumb a {
            color: var(--primary-600);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Modern Sidebar Navigation -->
    <?php include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                Choose Your Investment Strategy
            </h1>
            <p class="hero-subtitle">
                Select from our carefully curated investment categories designed to maximize your returns while managing risk
            </p>
        </div>
    </div>
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span>›</span>
            <span>Investment Plans</span>
        </div>

        <!-- Investment Categories -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
            <?php foreach ($categories as $category): ?>
                <div class="category-card" onclick="selectCategory(<?php echo $category['id']; ?>)" style="cursor: pointer;">
                    <div class="category-icon" style="background-color: <?php echo $category['color']; ?>20; color: <?php echo $category['color']; ?>;">
                        <?php echo $category['icon']; ?>
                    </div>
                    <h3 style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-2) 0;">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h3>
                    <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin: 0 0 var(--space-4) 0; line-height: var(--line-height-relaxed);">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </p>
                    <div style="background: var(--gray-50); border-radius: var(--radius-lg); padding: var(--space-3);">
                        <div style="font-size: var(--font-size-xs); color: var(--gray-500); margin-bottom: var(--space-1);">
                            Minimum Investment
                        </div>
                        <div style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--primary-600);">
                            ₦<?php echo number_format($category['min_amount']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Features Section -->
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-2) 0;">
                    Secure & Protected
                </h3>
                <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin: 0; line-height: var(--line-height-relaxed);">
                    Your investments are protected with bank-level security and regulatory compliance
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-2) 0;">
                    Competitive Returns
                </h3>
                <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin: 0; line-height: var(--line-height-relaxed);">
                    Earn attractive returns with our carefully selected investment opportunities
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); margin: 0 0 var(--space-2) 0;">
                    Instant Activation
                </h3>
                <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin: 0; line-height: var(--line-height-relaxed);">
                    Your investment starts earning returns immediately after payment confirmation
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="container-wide">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="stat-item">
                    <div class="stat-number">₦2.5B+</div>
                    <div class="stat-label">Assets Under Management</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">15,000+</div>
                    <div class="stat-label">Active Investors</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime Guarantee</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.9/5</div>
                    <div class="stat-label">Customer Rating</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="floating-action">
        <a href="dashboard.php" class="btn btn-primary" style="border-radius: var(--radius-full); padding: var(--space-4); box-shadow: var(--shadow-xl);">
            <span style="font-size: 20px;">📊</span>
        </a>
    </div>

    <!-- Hidden form for category selection -->
    <form id="categoryForm" method="POST" action="invest_details.php" style="display: none;">
        <input type="hidden" name="category_id" id="selectedCategoryId">
    </form>

    <script>
        function selectCategory(categoryId) {
            // Prevent any default behavior
            event.preventDefault();
            
            // Set the category ID
            document.getElementById('selectedCategoryId').value = categoryId;
            
            // Submit the form
            document.getElementById('categoryForm').submit();
        }

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.category-card, .feature-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Add hover effects
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
    </main>
</div>
</body>
</html>
