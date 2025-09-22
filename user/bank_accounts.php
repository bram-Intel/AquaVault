<?php
/**
 * AquaVault Capital - Bank Account Management
 * Manage user bank accounts for withdrawals
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

// Get user's bank accounts
try {
    $stmt = $pdo->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $bank_accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Bank accounts fetch error: " . $e->getMessage());
    $bank_accounts = [];
}

// Get Nigerian banks from Paystack
$banks_response = get_nigerian_banks();
$banks = $banks_response['status'] ? $banks_response['data'] : [];
// Add Test Bank for development
$banks[] = [
    'code' => '001',
    'name' => 'Test Bank (Use for Testing)'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
           case 'add_account':
    $account_name = trim($_POST['account_name']);
    $account_number = preg_replace('/\D/', '', trim($_POST['account_number'])); // Keep only digits
    $bank_code = trim($_POST['bank_code']);
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    // Validate inputs
    if (empty($account_name) || empty($account_number) || empty($bank_code)) {
        $error = "All fields are required.";
    } elseif (strlen($account_number) !== 10) {
        $error = "Account number must be exactly 10 digits.";
    } else {
        
        // ✅ SPECIAL TEST MODE: Skip Paystack for bank code "001"
        if ($bank_code === '001') {
            $verified_name = 'Test Account'; // Override with fake verified name
            error_log("Test mode: Skipping Paystack verification for bank code 001");
        } else {
            // Normal Paystack verification
            $verify_response = resolve_bank_account($account_number, $bank_code);
            error_log("Paystack verify response: " . print_r($verify_response, true));
            
            if ($verify_response['status']) {
                $verified_name = $verify_response['data']['account_name'] ?? $account_name;
            } else {
                $message = $verify_response['message'] ?? 'Invalid account details';
                $error = "❌ " . htmlspecialchars($message) . ". Please verify your account number and bank.";
                break; // Exit case
            }
        }

        try {
            // If primary is selected, unset other primary accounts
            if ($is_primary) {
                $stmt = $pdo->prepare("UPDATE user_bank_accounts SET is_primary = 0 WHERE user_id = ?");
                $stmt->execute([$user_id]);
            }
            
            // Insert new bank account
            $stmt = $pdo->prepare("
                INSERT INTO user_bank_accounts (user_id, account_name, account_number, bank_code, bank_name, is_primary, is_verified)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $bank_name = 'Unknown Bank'; // Fallback
            foreach ($banks as $bank) {
                if ($bank['code'] === $bank_code) {
                    $bank_name = $bank['name'];
                    break;
                }
            }
            
            if ($stmt->execute([$user_id, $verified_name, $account_number, $bank_code, $bank_name, $is_primary])) {
                $success = "✅ Bank account added and verified successfully!";
                // Refresh bank accounts
                $stmt = $pdo->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
                $stmt->execute([$user_id]);
                $bank_accounts = $stmt->fetchAll();
            } else {
                $error = "❌ Failed to save bank account. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Database insert error: " . $e->getMessage());
            $error = "❌ System error. Please contact support.";
        }
    }
    break;
                
            case 'set_primary':
                $account_id = $_POST['account_id'];
                
                try {
                    // Unset all primary accounts
                    $stmt = $pdo->prepare("UPDATE user_bank_accounts SET is_primary = 0 WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Set selected account as primary
                    $stmt = $pdo->prepare("UPDATE user_bank_accounts SET is_primary = 1 WHERE id = ? AND user_id = ?");
                    if ($stmt->execute([$account_id, $user_id])) {
                        $success = "✅ Primary account updated successfully!";
                        // Refresh bank accounts
                        $stmt = $pdo->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
                        $stmt->execute([$user_id]);
                        $bank_accounts = $stmt->fetchAll();
                    } else {
                        $error = "❌ Failed to update primary account.";
                    }
                } catch (Exception $e) {
                    error_log("Set primary error: " . $e->getMessage());
                    $error = "❌ System error. Please try again.";
                }
                break;
                
            case 'delete_account':
                $account_id = $_POST['account_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM user_bank_accounts WHERE id = ? AND user_id = ?");
                    if ($stmt->execute([$account_id, $user_id])) {
                        $success = "✅ Bank account deleted successfully!";
                        // Refresh bank accounts
                        $stmt = $pdo->prepare("SELECT * FROM user_bank_accounts WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
                        $stmt->execute([$user_id]);
                        $bank_accounts = $stmt->fetchAll();
                    } else {
                        $error = "❌ Failed to delete bank account.";
                    }
                } catch (Exception $e) {
                    error_log("Delete account error: " . $e->getMessage());
                    $error = "❌ System error. Please try again.";
                }
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
    <title>Bank Accounts - AquaVault Capital</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Bank Accounts</h1>
                        <p class="text-gray-600 mt-2">Manage your bank accounts for withdrawals</p>
                    </div>
                    <button onclick="openAddAccountModal()" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add Bank Account
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($error)): ?>
                <div class="alert alert-error mb-6">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Bank Accounts List -->
            <div class="grid gap-6">
                <?php if (empty($bank_accounts)): ?>
                    <div class="card text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-university text-6xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Bank Accounts</h3>
                        <p class="text-gray-600 mb-6">Add a bank account to enable withdrawals from your investments.</p>
                        <button onclick="openAddAccountModal()" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Add Your First Bank Account
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($bank_accounts as $account): ?>
                        <div class="card">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-university text-blue-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($account['account_name']); ?></h3>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($account['bank_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($account['account_number']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <?php if ($account['is_primary']): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-star mr-1"></i>
                                            Primary
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($account['is_verified']): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Verified
                                        </span>
                                    <?php endif; ?>
                                    
                                    <div class="flex space-x-2">
                                        <?php if (!$account['is_primary']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="set_primary">
                                                <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                                <button type="submit" class="btn-secondary btn-sm" title="Set as Primary">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this bank account?')">
                                            <input type="hidden" name="action" value="delete_account">
                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" class="btn-danger btn-sm" title="Delete Account">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Bank Account Modal -->
    <div id="addAccountModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-xl font-semibold">Add Bank Account</h2>
                <button onclick="closeAddAccountModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="add_account">
                
                <div class="form-group">
                    <label for="bank_code" class="form-label">Bank</label>
                    <select name="bank_code" id="bank_code" class="form-control" required>
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo htmlspecialchars($bank['code']); ?>">
                                <?php echo htmlspecialchars($bank['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="account_number" class="form-label">Account Number</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" 
                           placeholder="Enter 10-digit account number" required maxlength="10" pattern="\d{10}">
                    <small class="form-text">Enter your 10-digit account number (numbers only)</small>
                </div>
                
                <div class="form-group">
                    <label for="account_name" class="form-label">Account Name</label>
                    <input type="text" name="account_name" id="account_name" class="form-control" 
                           placeholder="Enter account name as registered with bank" required>
                    <small class="form-text">This will be verified automatically by Paystack</small>
                </div>
                
                <div class="form-group">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_primary" class="form-checkbox">
                        <span class="ml-2">Set as primary account</span>
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeAddAccountModal()" class="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddAccountModal() {
            document.getElementById('addAccountModal').classList.add('active');
        }
        
        function closeAddAccountModal() {
            document.getElementById('addAccountModal').classList.remove('active');
        }
        
        // Auto-format account number to digits only
        document.getElementById('account_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>