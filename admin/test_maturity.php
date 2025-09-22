<?php
/**
 * AquaVault Capital - Investment Maturity Test Tool
 * Manually trigger maturity for testing withdrawal functionality
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if admin is logged in
require_admin();

$admin_id = $_SESSION['admin_id'];

// Get admin details
try {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Admin fetch error: " . $e->getMessage());
    header('Location: login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'mature_investment':
                $investment_id = $_POST['investment_id'];
                $confirm = $_POST['confirm'] ?? false;
                
                if (!$confirm) {
                    $error = "Please confirm that you want to mature this investment.";
                } else {
                    // Get investment details
                    $stmt = $pdo->prepare("
                        SELECT ui.*, u.first_name, u.last_name, u.email,
                               ic.name as category_name, id.name as duration_name
                        FROM user_investments ui
                        JOIN users u ON ui.user_id = u.id
                        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
                        LEFT JOIN investment_durations id ON ui.duration_id = id.id
                        WHERE ui.id = ? AND ui.status = 'active'
                    ");
                    $stmt->execute([$investment_id]);
                    $investment = $stmt->fetch();
                    
                    if (!$investment) {
                        $error = "Investment not found or not active.";
                    } else {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Update investment status to matured
                        $stmt = $pdo->prepare("
                            UPDATE user_investments 
                            SET status = 'matured', matured_at = NOW(), updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$investment_id]);
                        
                        // Create return transaction
                        $stmt = $pdo->prepare("
                            INSERT INTO transactions (
                                user_id, investment_id, reference, type, amount, 
                                description, status, payment_method
                            ) VALUES (?, ?, ?, 'return', ?, ?, 'completed', 'system')
                        ");
                        
                        $return_reference = 'RET_' . time() . '_' . $investment_id;
                        $description = "Investment returns for " . $investment['reference'] . " - Matured on " . date('Y-m-d');
                        
                        $stmt->execute([
                            $investment['user_id'],
                            $investment_id,
                            $return_reference,
                            $investment['net_return'],
                            $description
                        ]);
                        
                        // Update user's total returns
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET total_returns = total_returns + ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$investment['net_return'], $investment['user_id']]);
                        
                        $pdo->commit();
                        
                        $success = "Investment matured successfully! User can now request withdrawal.";
                        
                        // Log the manual maturity
                        error_log("Investment manually matured by admin {$admin_id}: ID {$investment_id}, User {$investment['user_id']}, Amount {$investment['net_return']}");
                    }
                }
                break;
                
            case 'create_test_investment':
                $user_id = $_POST['user_id'];
                $amount = $_POST['amount'];
                $duration_days = $_POST['duration_days'];
                $interest_rate = $_POST['interest_rate'];
                $tax_rate = $_POST['tax_rate'];
                $category_id = $_POST['category_id'] ?? null;
                
                // Validate inputs
                if (empty($user_id) || empty($amount) || empty($duration_days) || empty($interest_rate) || empty($category_id)) {
                    $error = "Please fill in all required fields including category.";
                } else {
                    // Get user details
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        $error = "User not found.";
                    } else {
                        // Calculate returns
                        $expected_return = ($amount * $interest_rate) / 100;
                        $tax_amount = ($expected_return * $tax_rate) / 100;
                        $net_return = $expected_return - $tax_amount;
                        
                        // Calculate maturity date (set to yesterday so it can be matured immediately)
                        $maturity_date = date('Y-m-d', strtotime('-1 day'));
                        $start_date = date('Y-m-d', strtotime($maturity_date . ' -' . $duration_days . ' days'));
                        
                        // Generate reference
                        $reference = 'TEST_' . time() . '_' . $user_id;
                        
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Create test investment
                        $stmt = $pdo->prepare("
                            INSERT INTO user_investments (
                                user_id, category_id, reference, amount, interest_rate, tax_rate,
                                expected_return, net_return, start_date, maturity_date,
                                status, payment_status, payment_reference, payment_method
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'paid', ?, 'test')
                        ");
                        
                        $payment_ref = 'PAY_TEST_' . time();
                        $stmt->execute([
                            $user_id, $category_id, $reference, $amount, $interest_rate, $tax_rate,
                            $expected_return, $net_return, $start_date, $maturity_date,
                            $payment_ref
                        ]);
                        
                        $new_investment_id = $pdo->lastInsertId();
                        
                        // Create transaction record
                        $stmt = $pdo->prepare("
                            INSERT INTO transactions (
                                user_id, investment_id, reference, type, amount, 
                                description, status, payment_method, payment_reference
                            ) VALUES (?, ?, ?, 'investment', ?, ?, 'completed', 'test', ?)
                        ");
                        
                        $description = "Test investment created for maturity testing";
                        $stmt->execute([
                            $user_id, $new_investment_id, $reference, $amount, $description, $payment_ref
                        ]);
                        
                        // Update user total invested
                        $stmt = $pdo->prepare("UPDATE users SET total_invested = total_invested + ? WHERE id = ?");
                        $stmt->execute([$amount, $user_id]);
                        
                        $pdo->commit();
                        
                        $success = "Test investment created successfully! Investment ID: " . $new_investment_id;
                    }
                }
                break;
        }
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log("Maturity test error: " . $e->getMessage());
        $error = "An error occurred: " . $e->getMessage();
    }
}

// Get active investments
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, u.first_name, u.last_name, u.email,
               ic.name as category_name, id.name as duration_name
        FROM user_investments ui
        JOIN users u ON ui.user_id = u.id
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.status = 'active'
        ORDER BY ui.maturity_date ASC
    ");
    $stmt->execute();
    $active_investments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Active investments fetch error: " . $e->getMessage());
    $active_investments = [];
}

// Get users for test investment creation
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users ORDER BY first_name, last_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Users fetch error: " . $e->getMessage());
    $users = [];
}

// Get investment categories
try {
    $stmt = $pdo->prepare("SELECT id, name FROM investment_categories WHERE is_active = 1 ORDER BY name");
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
    <title>Test Investment Maturity - Admin - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Test Investment Maturity</h1>
                        <p class="text-gray-600 mt-2">Manually mature investments to test withdrawal functionality</p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($error)): ?>
                <div class="alert alert-error mb-6">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Create Test Investment -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Create Test Investment</h3>
                    <p class="text-gray-600">Create a test investment that can be matured immediately for testing</p>
                </div>
                <div class="card-body">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="create_test_investment">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="user_id" class="form-label">User</label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">Select User</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id" class="form-label">Investment Category</label>
                                <select name="category_id" id="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount" class="form-label">Investment Amount (₦)</label>
                                <input type="number" name="amount" id="amount" class="form-control" 
                                       placeholder="100000" min="1000" step="1000" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="duration_days" class="form-label">Duration (Days)</label>
                                <input type="number" name="duration_days" id="duration_days" class="form-control" 
                                       placeholder="30" min="1" max="365" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="interest_rate" class="form-label">Interest Rate (%)</label>
                                <input type="number" name="interest_rate" id="interest_rate" class="form-control" 
                                       placeholder="15" min="0" max="100" step="0.1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" name="tax_rate" id="tax_rate" class="form-control" 
                                       placeholder="5" min="0" max="50" step="0.1" value="5">
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium mb-1">Test Investment Details</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Investment will be created with a start date in the past</li>
                                        <li>Maturity date will be set to yesterday</li>
                                        <li>Status will be set to 'active' and can be matured immediately</li>
                                        <li>Payment status will be set to 'paid' for testing</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                Create Test Investment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Active Investments -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Active Investments</h3>
                    <p class="text-gray-600">Select an investment to manually mature for testing</p>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($active_investments)): ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <i class="fas fa-chart-line text-6xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Active Investments</h3>
                            <p class="text-gray-600">Create a test investment above to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>User</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Returns</th>
                                        <th>Start Date</th>
                                        <th>Maturity Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_investments as $investment): ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($investment['reference']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: <?php echo $investment['id']; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($investment['first_name'] . ' ' . $investment['last_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($investment['email']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($investment['category_name'] ?? 'N/A'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($investment['duration_name'] ?? 'N/A'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-medium text-gray-900">
                                                    ₦<?php echo number_format($investment['amount']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo $investment['interest_rate']; ?>% p.a.
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-medium text-gray-900">
                                                    ₦<?php echo number_format($investment['net_return']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Net return
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M j, Y', strtotime($investment['start_date'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M j, Y', strtotime($investment['maturity_date'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php 
                                                    $days_left = (strtotime($investment['maturity_date']) - time()) / (60 * 60 * 24);
                                                    if ($days_left > 0) {
                                                        echo ceil($days_left) . ' days left';
                                                    } else {
                                                        echo '<span class="text-red-600 font-medium">Overdue</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-play mr-1"></i>
                                                    Active
                                                </span>
                                            </td>
                                            <td>
                                                <button onclick="openMatureModal(<?php echo $investment['id']; ?>, '<?php echo htmlspecialchars($investment['reference']); ?>', '<?php echo htmlspecialchars($investment['first_name'] . ' ' . $investment['last_name']); ?>', <?php echo $investment['amount']; ?>, <?php echo $investment['net_return']; ?>)" 
                                                        class="btn-warning btn-sm" title="Mature Investment">
                                                    <i class="fas fa-calendar-check"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mature Investment Modal -->
    <div id="matureModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-xl font-semibold">Mature Investment</h2>
                <button onclick="closeMatureModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="mature_investment">
                <input type="hidden" name="investment_id" id="mature_investment_id">
                
                <div class="bg-yellow-50 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                        <div class="text-sm text-yellow-800">
                            <p class="font-medium mb-1">Warning: Manual Maturity</p>
                            <p>This will immediately mature the investment and make it available for withdrawal. This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Investment Details</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-600">Reference:</span>
                                    <span id="mature_reference" class="text-gray-900"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">User:</span>
                                    <span id="mature_user" class="text-gray-900"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Amount:</span>
                                    <span id="mature_amount" class="text-gray-900"></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600">Returns:</span>
                                    <span id="mature_returns" class="text-gray-900"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="flex items-center">
                            <input type="checkbox" name="confirm" value="1" class="form-checkbox text-red-600" required>
                            <span class="ml-2 text-sm text-gray-700">
                                I confirm that I want to mature this investment for testing purposes
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeMatureModal()" class="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn-warning">
                        <i class="fas fa-calendar-check mr-2"></i>
                        Mature Investment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMatureModal(investmentId, reference, user, amount, returns) {
            document.getElementById('mature_investment_id').value = investmentId;
            document.getElementById('mature_reference').textContent = reference;
            document.getElementById('mature_user').textContent = user;
            document.getElementById('mature_amount').textContent = '₦' + amount.toLocaleString();
            document.getElementById('mature_returns').textContent = '₦' + returns.toLocaleString();
            document.getElementById('matureModal').classList.add('active');
        }
        
        function closeMatureModal() {
            document.getElementById('matureModal').classList.remove('active');
            document.querySelector('input[name="confirm"]').checked = false;
        }
    </script>
</body>
</html>
