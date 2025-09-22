<?php
/**
 * AquaVault Capital - Withdrawal Request
 * Request withdrawal from matured investments
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';
require_once '../config/paystack.php';

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

// Get matured investments
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, ic.name as category_name, ic.icon as category_icon, id.name as duration_name
        FROM user_investments ui
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.user_id = ? 
        AND ui.status = 'matured' 
        AND ui.category_id IS NOT NULL
        AND ui.id NOT IN (
            SELECT DISTINCT investment_id 
            FROM withdrawal_requests 
            WHERE investment_id IS NOT NULL 
            AND status IN ('pending', 'approved', 'processing', 'completed')
        )
        ORDER BY ui.maturity_date ASC
    ");
    $stmt->execute([$user_id]);
    $matured_investments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Matured investments fetch error: " . $e->getMessage());
    $matured_investments = [];
}

// Get user's bank accounts
try {
    $stmt = $pdo->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? AND is_verified = 1 ORDER BY is_primary DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $bank_accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Bank accounts fetch error: " . $e->getMessage());
    $bank_accounts = [];
}

// Get system settings
try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('min_withdrawal', 'max_withdrawal', 'withdrawal_processing_fee')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $settings = ['min_withdrawal' => 1000, 'max_withdrawal' => 5000000, 'withdrawal_processing_fee' => 0];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdrawal') {
    $investment_id = $_POST['investment_id'];
    $bank_account_id = $_POST['bank_account_id'];
    
    // Validate inputs
    if (empty($investment_id) || empty($bank_account_id)) {
        $error = "Please select an investment and bank account.";
    } else {
        // Get investment details
        $stmt = $pdo->prepare("SELECT * FROM user_investments WHERE id = ? AND user_id = ? AND status = 'matured'");
        $stmt->execute([$investment_id, $user_id]);
        $investment = $stmt->fetch();
        
        if (!$investment) {
            $error = "Invalid investment selected.";
        } else {
            // Get bank account details
            $stmt = $pdo->prepare("SELECT * FROM user_bank_accounts WHERE id = ? AND user_id = ? AND is_verified = 1");
            $stmt->execute([$bank_account_id, $user_id]);
            $bank_account = $stmt->fetch();
            
            if (!$bank_account) {
                $error = "Invalid bank account selected.";
            } else {
                // Calculate amounts
                $principal_amount = $investment['amount'];
                $returns_amount = $investment['net_return'];
                $total_amount = $principal_amount + $returns_amount;
                
                // Apply processing fee if any
                $processing_fee = 0;
                if (isset($settings['withdrawal_processing_fee']) && $settings['withdrawal_processing_fee'] > 0) {
                    $processing_fee = ($total_amount * $settings['withdrawal_processing_fee']) / 100;
                }
                
                $net_amount = $total_amount - $processing_fee;
                
                // Check minimum withdrawal amount
                if ($net_amount < $settings['min_withdrawal']) {
                    $error = "Withdrawal amount must be at least ₦" . number_format($settings['min_withdrawal']);
                } else {
                    // Generate withdrawal reference
                    $reference = generate_payment_reference('WD');
                    
                    try {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Create withdrawal request
                        $stmt = $pdo->prepare("
                            INSERT INTO withdrawal_requests (
                                user_id, investment_id, bank_account_id, reference, amount, 
                                principal_amount, returns_amount, tax_amount, net_amount, status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                        ");
                        
                        if ($stmt->execute([
                            $user_id, $investment_id, $bank_account_id, $reference, $net_amount,
                            $principal_amount, $returns_amount, $processing_fee, $net_amount
                        ])) {
                            // Create transaction record
                            $stmt = $pdo->prepare("
                                INSERT INTO transactions (
                                    user_id, investment_id, reference, type, amount, 
                                    description, status, payment_method
                                ) VALUES (?, ?, ?, 'withdrawal', ?, ?, 'pending', 'paystack')
                            ");
                            
                            $description = "Withdrawal request for investment: " . $investment['reference'];
                            $stmt->execute([$user_id, $investment_id, $reference, $net_amount, $description]);
                            
                            $pdo->commit();
                            
                            $success = "Withdrawal request submitted successfully! Reference: " . $reference;
                            
                            // Refresh matured investments
                            $stmt = $pdo->prepare("
                                SELECT ui.*, ic.name as category_name, ic.icon as category_icon, id.name as duration_name
                                FROM user_investments ui
                                LEFT JOIN investment_categories ic ON ui.category_id = ic.id
                                LEFT JOIN investment_durations id ON ui.duration_id = id.id
                                WHERE ui.user_id = ? 
                                AND ui.status = 'matured' 
                                AND ui.category_id IS NOT NULL
                                AND ui.id NOT IN (
                                    SELECT DISTINCT investment_id 
                                    FROM withdrawal_requests 
                                    WHERE investment_id IS NOT NULL 
                                    AND status IN ('pending', 'approved', 'processing', 'completed')
                                )
                                ORDER BY ui.maturity_date ASC
                            ");
                            $stmt->execute([$user_id]);
                            $matured_investments = $stmt->fetchAll();
                        } else {
                            $pdo->rollback();
                            $error = "Failed to create withdrawal request. Please try again.";
                        }
                    } catch (PDOException $e) {
                        $pdo->rollback();
                        error_log("Withdrawal request error: " . $e->getMessage());
                        $error = "An error occurred. Please try again.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Withdraw Funds</h1>
                        <p class="text-gray-600 mt-2">Request withdrawal from your matured investments</p>
                    </div>
                    <a href="bank_accounts.php" class="btn-secondary">
                        <i class="fas fa-university mr-2"></i>
                        Manage Bank Accounts
                    </a>
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

            <!-- Withdrawal Form -->
            <?php if (empty($matured_investments)): ?>
                <div class="card text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-calendar-check text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Matured Investments</h3>
                    <p class="text-gray-600 mb-6">You don't have any matured investments available for withdrawal.</p>
                    <a href="dashboard.php" class="btn-primary">
                        <i class="fas fa-chart-line mr-2"></i>
                        View Dashboard
                    </a>
                </div>
            <?php elseif (empty($bank_accounts)): ?>
                <div class="card text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-university text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Bank Accounts</h3>
                    <p class="text-gray-600 mb-6">You need to add a verified bank account before you can request withdrawals.</p>
                    <a href="bank_accounts.php" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add Bank Account
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="request_withdrawal">
                    
                    <!-- Investment Selection -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-semibold">Select Investment</h3>
                            <p class="text-gray-600">Choose a matured investment to withdraw from</p>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                <?php foreach ($matured_investments as $investment): ?>
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                        <input type="radio" name="investment_id" value="<?php echo $investment['id']; ?>" 
                                               class="form-radio text-blue-600" required>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">
                                                        <?php echo htmlspecialchars($investment['category_name']); ?>
                                                        - <?php echo htmlspecialchars($investment['duration_name']); ?>
                                                    </h4>
                                                    <p class="text-sm text-gray-600">
                                                        Reference: <?php echo htmlspecialchars($investment['reference']); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        Matured: <?php echo date('M j, Y', strtotime($investment['maturity_date'])); ?>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="font-semibold text-gray-900">
                                                        ₦<?php echo number_format($investment['amount'] + $investment['net_return']); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        Principal: ₦<?php echo number_format($investment['amount']); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        Returns: ₦<?php echo number_format($investment['net_return']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank Account Selection -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-semibold">Select Bank Account</h3>
                            <p class="text-gray-600">Choose where to receive your withdrawal</p>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                <?php foreach ($bank_accounts as $account): ?>
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                        <input type="radio" name="bank_account_id" value="<?php echo $account['id']; ?>" 
                                               class="form-radio text-blue-600" required>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">
                                                        <?php echo htmlspecialchars($account['account_name']); ?>
                                                    </h4>
                                                    <p class="text-sm text-gray-600">
                                                        <?php echo htmlspecialchars($account['bank_name']); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <?php echo htmlspecialchars($account['account_number']); ?>
                                                    </p>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <?php if ($account['is_primary']): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-star mr-1"></i>
                                                            Primary
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Verified
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Withdrawal Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-lg font-semibold">Withdrawal Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Principal Amount:</span>
                                        <span class="font-medium" id="principal-amount">₦0</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Returns:</span>
                                        <span class="font-medium" id="returns-amount">₦0</span>
                                    </div>
                                    <?php if (isset($settings['withdrawal_processing_fee']) && $settings['withdrawal_processing_fee'] > 0): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Processing Fee (<?php echo $settings['withdrawal_processing_fee']; ?>%):</span>
                                            <span class="font-medium" id="processing-fee">₦0</span>
                                        </div>
                                    <?php endif; ?>
                                    <hr class="my-2">
                                    <div class="flex justify-between font-semibold text-lg">
                                        <span>Total Withdrawal:</span>
                                        <span id="total-amount">₦0</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                                    <div class="text-sm text-blue-800">
                                        <p class="font-medium mb-1">Withdrawal Processing</p>
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Withdrawals are processed within <?php echo $settings['withdrawal_processing_time'] ?? 24; ?> hours</li>
                                            <li>Minimum withdrawal: ₦<?php echo number_format($settings['min_withdrawal']); ?></li>
                                            <li>Maximum withdrawal: ₦<?php echo number_format($settings['max_withdrawal']); ?></li>
                                            <li>You will receive an email notification when your withdrawal is processed</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary btn-lg">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Request Withdrawal
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Update withdrawal summary when investment is selected
        document.querySelectorAll('input[name="investment_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const investmentData = <?php echo json_encode($matured_investments); ?>;
                    const selectedInvestment = investmentData.find(inv => inv.id == this.value);
                    
                    if (selectedInvestment) {
                        const principal = parseFloat(selectedInvestment.amount);
                        const returns = parseFloat(selectedInvestment.net_return);
                        const total = principal + returns;
                        const processingFeeRate = <?php echo $settings['withdrawal_processing_fee'] ?? 0; ?>;
                        const processingFee = (total * processingFeeRate) / 100;
                        const netTotal = total - processingFee;
                        
                        document.getElementById('principal-amount').textContent = '₦' + principal.toLocaleString();
                        document.getElementById('returns-amount').textContent = '₦' + returns.toLocaleString();
                        document.getElementById('processing-fee').textContent = '₦' + processingFee.toLocaleString();
                        document.getElementById('total-amount').textContent = '₦' + netTotal.toLocaleString();
                    }
                }
            });
        });
    </script>
</body>
</html>
