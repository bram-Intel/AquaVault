<?php
/**
 * AquaVault Capital - Landing Page
 */
session_start();
require_once 'db/connect.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
    exit();
}

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}

// Get platform statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stmt->execute();
    $total_users = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM user_investments WHERE status = 'active'");
    $stmt->execute();
    $total_invested = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_investments WHERE status = 'active'");
    $stmt->execute();
    $active_investments = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log("Stats fetch error: " . $e->getMessage());
    $total_users = $total_invested = $active_investments = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaVault Capital - Secure Fixed-Term Investment Platform</title>
    <meta name="description" content="AquaVault Capital offers secure fixed-term investment plans with competitive returns. Lock your funds and earn guaranteed returns in Nigeria.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
        .hero-pattern { background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f3f4f6' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="gradient-bg w-8 h-8 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">AV</span>
                    </div>
                    <span class="ml-2 text-xl font-bold text-gray-900">AquaVault Capital</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition duration-200">Features</a>
                    <a href="#plans" class="text-gray-700 hover:text-blue-600 transition duration-200">Plans</a>
                    <a href="#about" class="text-gray-700 hover:text-blue-600 transition duration-200">About</a>
                    <a href="user/login.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Login</a>
                    <a href="user/register.php" class="gradient-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition duration-200">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-pattern py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                    Secure Your Future with
                    <span class="gradient-bg bg-clip-text text-transparent">Fixed-Term Investments</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    Lock your funds into secure investment plans and earn guaranteed returns. 
                    AquaVault Capital offers competitive rates with complete transparency and security.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="user/register.php" class="gradient-bg text-white px-8 py-4 rounded-lg text-lg font-medium hover:opacity-90 transition duration-200">
                        Start Investing Now
                    </a>
                    <a href="#plans" class="px-8 py-4 border border-gray-300 text-gray-700 rounded-lg text-lg font-medium hover:bg-gray-50 transition duration-200">
                        View Plans
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo number_format($total_users); ?>+</div>
                    <div class="text-gray-600">Active Investors</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-green-600 mb-2">₦<?php echo number_format($total_invested / 1000000, 1); ?>M+</div>
                    <div class="text-gray-600">Total Invested</div>
                </div>
                <div>
                    <div class="text-4xl font-bold text-purple-600 mb-2"><?php echo number_format($active_investments); ?>+</div>
                    <div class="text-gray-600">Active Investments</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Choose AquaVault Capital?</h2>
                <p class="text-xl text-gray-600">Secure, transparent, and profitable investment solutions</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Bank-Level Security</h3>
                    <p class="text-gray-600">Your funds are protected with enterprise-grade security and encryption.</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Guaranteed Returns</h3>
                    <p class="text-gray-600">Earn competitive returns with fixed-term investment plans.</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">KYC Verified</h3>
                    <p class="text-gray-600">All users are verified through our secure KYC process.</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Flexible Terms</h3>
                    <p class="text-gray-600">Choose from various investment durations to suit your needs.</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Secure Payments</h3>
                    <p class="text-gray-600">Pay securely with Paystack integration and bank-level encryption.</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">24/7 Support</h3>
                    <p class="text-gray-600">Get help whenever you need it with our dedicated support team.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Investment Plans Section -->
    <section id="plans" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Investment Plans</h2>
                <p class="text-xl text-gray-600">Choose the plan that best fits your investment goals</p>
            </div>
            
            <?php
            // Get active investment plans
            try {
                $stmt = $pdo->prepare("SELECT * FROM investment_plans WHERE is_active = 1 ORDER BY min_amount ASC LIMIT 4");
                $stmt->execute();
                $plans = $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("Plans fetch error: " . $e->getMessage());
                $plans = [];
            }
            ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($plans as $plan): ?>
                    <div class="bg-white border border-gray-200 rounded-lg shadow-lg p-6 hover:shadow-xl transition duration-200">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo number_format($plan['interest_rate'], 1); ?>%</div>
                            <div class="text-sm text-gray-500">Annual Return</div>
                        </div>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Duration</span>
                                <span class="font-semibold"><?php echo $plan['duration_days']; ?> days</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Min Amount</span>
                                <span class="font-semibold">₦<?php echo number_format($plan['min_amount']); ?></span>
                            </div>
                            <?php if ($plan['max_amount']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Max Amount</span>
                                    <span class="font-semibold">₦<?php echo number_format($plan['max_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax Rate</span>
                                <span class="font-semibold"><?php echo number_format($plan['tax_rate'], 1); ?>%</span>
                            </div>
                        </div>
                        
                        <a href="user/register.php" class="w-full gradient-bg text-white py-3 rounded-lg font-medium hover:opacity-90 transition duration-200 text-center block">
                            Get Started
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">About AquaVault Capital</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        AquaVault Capital is a leading fixed-term investment platform in Nigeria, 
                        providing secure and profitable investment opportunities for individuals and businesses.
                    </p>
                    <p class="text-lg text-gray-600 mb-6">
                        We believe in transparency, security, and helping our clients achieve their 
                        financial goals through carefully managed investment plans.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Licensed and regulated investment platform</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Bank-level security and encryption</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">24/7 customer support</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Ready to Start Investing?</h3>
                    <p class="text-gray-600 mb-6">
                        Join thousands of satisfied investors who trust AquaVault Capital 
                        with their financial future.
                    </p>
                    <div class="space-y-4">
                        <a href="user/register.php" class="w-full gradient-bg text-white py-3 rounded-lg font-medium hover:opacity-90 transition duration-200 text-center block">
                            Create Account
                        </a>
                        <a href="user/login.php" class="w-full border border-gray-300 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-50 transition duration-200 text-center block">
                            Login to Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="gradient-bg w-8 h-8 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-bold">AV</span>
                        </div>
                        <span class="ml-2 text-xl font-bold">AquaVault Capital</span>
                    </div>
                    <p class="text-gray-400">
                        Secure fixed-term investment platform for Nigeria.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition duration-200">Features</a></li>
                        <li><a href="#plans" class="text-gray-400 hover:text-white transition duration-200">Plans</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition duration-200">About</a></li>
                        <li><a href="user/register.php" class="text-gray-400 hover:text-white transition duration-200">Register</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="mailto:support@aquavault.com" class="text-gray-400 hover:text-white transition duration-200">Email Support</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-200">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-200">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-200">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Email: support@aquavault.com</li>
                        <li>Phone: +234-XXX-XXXX</li>
                        <li>Address: Lagos, Nigeria</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> AquaVault Capital. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
