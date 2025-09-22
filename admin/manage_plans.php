<?php
/**
 * AquaVault Capital - Manage Investment Plans
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if admin is logged in
require_admin();

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO investment_plans 
                        (name, category, description, min_amount, max_amount, duration_days, interest_rate, tax_rate, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        sanitize_input($_POST['name']),
                        sanitize_input($_POST['category']),
                        sanitize_input($_POST['description']),
                        (float)$_POST['min_amount'],
                        !empty($_POST['max_amount']) ? (float)$_POST['max_amount'] : null,
                        (int)$_POST['duration_days'],
                        (float)$_POST['interest_rate'],
                        (float)$_POST['tax_rate'],
                        isset($_POST['is_active']) ? 1 : 0
                    ]);
                    $message = 'Investment plan created successfully!';
                } catch (PDOException $e) {
                    error_log("Plan creation error: " . $e->getMessage());
                    $error = 'Failed to create investment plan.';
                }
                break;
                
            case 'update':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE investment_plans 
                        SET name = ?, category = ?, description = ?, min_amount = ?, max_amount = ?, 
                            duration_days = ?, interest_rate = ?, tax_rate = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        sanitize_input($_POST['name']),
                        sanitize_input($_POST['category']),
                        sanitize_input($_POST['description']),
                        (float)$_POST['min_amount'],
                        !empty($_POST['max_amount']) ? (float)$_POST['max_amount'] : null,
                        (int)$_POST['duration_days'],
                        (float)$_POST['interest_rate'],
                        (float)$_POST['tax_rate'],
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['plan_id']
                    ]);
                    $message = 'Investment plan updated successfully!';
                } catch (PDOException $e) {
                    error_log("Plan update error: " . $e->getMessage());
                    $error = 'Failed to update investment plan.';
                }
                break;
                
            case 'toggle_status':
                try {
                    $stmt = $pdo->prepare("UPDATE investment_plans SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([(int)$_POST['plan_id']]);
                    $message = 'Plan status updated successfully!';
                } catch (PDOException $e) {
                    error_log("Plan status toggle error: " . $e->getMessage());
                    $error = 'Failed to update plan status.';
                }
                break;
        }
    }
}

// Get all investment plans
try {
    $stmt = $pdo->prepare("SELECT * FROM investment_plans ORDER BY created_at DESC");
    $stmt->execute();
    $plans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Plans fetch error: " . $e->getMessage());
    $plans = [];
}

// Get plan for editing
$edit_plan = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($plans as $plan) {
        if ($plan['id'] == $edit_id) {
            $edit_plan = $plan;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Investment Plans - AquaVault Capital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Admin Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="gradient-bg w-8 h-8 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">AV</span>
                    </div>
                    <span class="ml-2 text-xl font-bold text-gray-900">AquaVault Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600">Dashboard</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Investment Management</h1>
            <p class="mt-2 text-gray-600">Manage investment categories and durations for the new dynamic system</p>
        </div>

        <!-- New System Notice -->
        <div class="mb-8 p-6 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-blue-900">New Dynamic Investment System</h3>
                    <p class="text-blue-800 mt-1">
                        AquaVault now uses a dynamic category and duration system. 
                        <a href="manage_categories.php" class="font-medium underline hover:no-underline">Manage Categories</a> and 
                        <a href="manage_durations.php" class="font-medium underline hover:no-underline">Manage Durations</a> instead.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="manage_categories.php" class="gradient-bg text-white p-6 rounded-lg text-center hover:opacity-90 transition duration-200">
                    <div class="text-3xl mb-3">📊</div>
                    <div class="font-medium text-lg">Manage Categories</div>
                    <div class="text-sm opacity-90 mt-1">Create and manage investment categories</div>
                </a>
                <a href="manage_durations.php" class="bg-white border border-gray-300 text-gray-700 p-6 rounded-lg text-center hover:bg-gray-50 transition duration-200">
                    <div class="text-3xl mb-3">⏰</div>
                    <div class="font-medium text-lg">Manage Durations</div>
                    <div class="text-sm text-gray-500 mt-1">Set interest rates for each duration</div>
                </a>
                <a href="users.php" class="bg-white border border-gray-300 text-gray-700 p-6 rounded-lg text-center hover:bg-gray-50 transition duration-200">
                    <div class="text-3xl mb-3">👥</div>
                    <div class="font-medium text-lg">View Users</div>
                    <div class="text-sm text-gray-500 mt-1">Manage user accounts and investments</div>
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600 text-sm"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- System Status -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">System Status</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-3xl mb-2">✅</div>
                    <h3 class="font-semibold text-green-800">New System Active</h3>
                    <p class="text-sm text-green-600">Dynamic categories and durations are working</p>
                </div>
                
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-3xl mb-2">📊</div>
                    <h3 class="font-semibold text-blue-800">Categories</h3>
                    <p class="text-sm text-blue-600">4 investment categories available</p>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-3xl mb-2">⏰</div>
                    <h3 class="font-semibold text-purple-800">Durations</h3>
                    <p class="text-sm text-purple-600">24 duration options with dynamic rates</p>
                </div>
            </div>
        </div>

        <!-- Old System Notice -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-yellow-900">Legacy Investment Plans</h3>
                    <p class="text-yellow-800 mt-1">
                        The old investment plans system is deprecated. All new investments use the dynamic category and duration system. 
                        Old plans are kept for reference only and cannot be modified.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
