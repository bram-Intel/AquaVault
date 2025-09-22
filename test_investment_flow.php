<?php
/**
 * Test Investment Flow
 * This script helps test the investment flow and identify any issues
 */
session_start();
require_once 'db/connect.php';
require_once 'includes/auth.php';

// Simulate a logged-in user for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@example.com';

// Test investment data
$test_investment_data = [
    'category_id' => 1,
    'duration_id' => 1,
    'amount' => 50000,
    'category_name' => 'AquaVault Stock',
    'duration_name' => '30 Days',
    'duration_days' => 30,
    'interest_rate' => 10.0,
    'tax_rate' => 5.0
];

// Store test data in session
$_SESSION['investment_data'] = $test_investment_data;
$_SESSION['selected_category_id'] = 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Flow Test - AquaVault Capital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Investment Flow Test</h1>
            <p class="text-gray-600 mb-6">This page helps test the investment flow and identify any issues.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-900 mb-2">✅ Session Data</h3>
                    <div class="text-sm text-blue-700">
                        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                        <p><strong>Investment Data:</strong> <?php echo isset($_SESSION['investment_data']) ? 'Set' : 'Not Set'; ?></p>
                        <p><strong>Category ID:</strong> <?php echo $_SESSION['selected_category_id'] ?? 'Not Set'; ?></p>
                    </div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-900 mb-2">🧪 Test Functions</h3>
                    <div class="text-sm text-green-700">
                        <p><strong>CSRF Token:</strong> <?php echo function_exists('generate_csrf_token') ? 'Available' : 'Missing'; ?></p>
                        <p><strong>Calculate Returns:</strong> <?php echo function_exists('calculate_returns') ? 'Available' : 'Missing'; ?></p>
                        <p><strong>Format Currency:</strong> <?php echo function_exists('format_currency') ? 'Available' : 'Missing'; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <h3 class="font-semibold text-gray-900 mb-2">Test Investment Data:</h3>
                <pre class="bg-gray-100 p-4 rounded text-sm overflow-x-auto"><?php echo json_encode($test_investment_data, JSON_PRETTY_PRINT); ?></pre>
            </div>
            
            <div class="mt-6">
                <h3 class="font-semibold text-gray-900 mb-2">Test Links:</h3>
                <div class="flex flex-wrap gap-2">
                    <a href="user/invest.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">1. Select Category</a>
                    <a href="user/invest_amount.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">2. Enter Amount</a>
                    <a href="user/invest_review.php" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">3. Review Investment</a>
                    <a href="user/success.php?reference=TEST_REF_123" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">4. Success Page</a>
                </div>
            </div>
            
            <div class="mt-6">
                <h3 class="font-semibold text-gray-900 mb-2">Database Test:</h3>
                <?php
                try {
                    // Test database connection
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM investment_categories WHERE is_active = 1");
                    $result = $stmt->fetch();
                    echo "<p class='text-green-600'>✅ Database connected. Active categories: " . $result['count'] . "</p>";
                    
                    // Test categories
                    $stmt = $pdo->query("SELECT id, name FROM investment_categories WHERE is_active = 1 LIMIT 3");
                    $categories = $stmt->fetchAll();
                    echo "<p class='text-green-600'>✅ Categories available: " . count($categories) . "</p>";
                    
                    // Test durations
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM investment_durations WHERE is_active = 1");
                    $result = $stmt->fetch();
                    echo "<p class='text-green-600'>✅ Active durations: " . $result['count'] . "</p>";
                    
                } catch (Exception $e) {
                    echo "<p class='text-red-600'>❌ Database error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
