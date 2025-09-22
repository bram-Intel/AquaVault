<?php
/**
 * AquaVault Capital - Withdrawal Requests
 * View user's withdrawal requests
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

// Get user's withdrawal requests
try {
    $stmt = $pdo->prepare("
        SELECT wr.*, 
               uba.account_name, uba.account_number, uba.bank_name,
               ui.reference as investment_reference,
               ic.name as category_name, ic.icon as category_icon,
               id.name as duration_name,
               au.full_name as processed_by_name
        FROM withdrawal_requests wr
        LEFT JOIN user_bank_accounts uba ON wr.bank_account_id = uba.id
        LEFT JOIN user_investments ui ON wr.investment_id = ui.id
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        LEFT JOIN admin_users au ON wr.processed_by = au.id
        WHERE wr.user_id = ?
        ORDER BY wr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $withdrawal_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Withdrawal requests fetch error: " . $e->getMessage());
    $withdrawal_requests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Requests - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Withdrawal Requests</h1>
                        <p class="text-gray-600 mt-2">Track your withdrawal requests and their status</p>
                    </div>
                    <a href="withdraw.php" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        New Withdrawal
                    </a>
                </div>
            </div>

            <!-- Withdrawal Requests List -->
            <?php if (empty($withdrawal_requests)): ?>
                <div class="card text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-receipt text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Withdrawal Requests</h3>
                    <p class="text-gray-600 mb-6">You haven't made any withdrawal requests yet.</p>
                    <a href="withdraw.php" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Request Withdrawal
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($withdrawal_requests as $request): ?>
                        <div class="card">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900">
                                                Withdrawal Request #<?php echo htmlspecialchars($request['reference']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($request['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <?php
                                        $status_class = '';
                                        $status_icon = '';
                                        switch ($request['status']) {
                                            case 'pending':
                                                $status_class = 'badge-warning';
                                                $status_icon = 'fas fa-clock';
                                                break;
                                            case 'approved':
                                                $status_class = 'badge-info';
                                                $status_icon = 'fas fa-check';
                                                break;
                                            case 'processing':
                                                $status_class = 'badge-primary';
                                                $status_icon = 'fas fa-spinner';
                                                break;
                                            case 'completed':
                                                $status_class = 'badge-success';
                                                $status_icon = 'fas fa-check-circle';
                                                break;
                                            case 'rejected':
                                                $status_class = 'badge-danger';
                                                $status_icon = 'fas fa-times-circle';
                                                break;
                                            case 'failed':
                                                $status_class = 'badge-danger';
                                                $status_icon = 'fas fa-exclamation-triangle';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <i class="<?php echo $status_icon; ?> mr-1"></i>
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- Investment Details -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Investment Details</h4>
                                        <div class="space-y-1 text-sm">
                                            <p class="text-gray-600">
                                                <span class="font-medium">Category:</span>
                                                <?php echo htmlspecialchars($request['category_name'] ?? 'N/A'); ?>
                                            </p>
                                            <p class="text-gray-600">
                                                <span class="font-medium">Duration:</span>
                                                <?php echo htmlspecialchars($request['duration_name'] ?? 'N/A'); ?>
                                            </p>
                                            <p class="text-gray-600">
                                                <span class="font-medium">Reference:</span>
                                                <?php echo htmlspecialchars($request['investment_reference'] ?? 'N/A'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Bank Details -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Bank Details</h4>
                                        <div class="space-y-1 text-sm">
                                            <p class="text-gray-600">
                                                <span class="font-medium">Account Name:</span>
                                                <?php echo htmlspecialchars($request['account_name']); ?>
                                            </p>
                                            <p class="text-gray-600">
                                                <span class="font-medium">Bank:</span>
                                                <?php echo htmlspecialchars($request['bank_name']); ?>
                                            </p>
                                            <p class="text-gray-600">
                                                <span class="font-medium">Account Number:</span>
                                                <?php echo htmlspecialchars($request['account_number']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Amount Details -->
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">Amount Details</h4>
                                        <div class="space-y-1 text-sm">
                                            <p class="text-gray-600">
                                                <span class="font-medium">Principal:</span>
                                                ₦<?php echo number_format($request['principal_amount']); ?>
                                            </p>
                                            <p class="text-gray-600">
                                                <span class="font-medium">Returns:</span>
                                                ₦<?php echo number_format($request['returns_amount']); ?>
                                            </p>
                                            <?php if ($request['tax_amount'] > 0): ?>
                                                <p class="text-gray-600">
                                                    <span class="font-medium">Processing Fee:</span>
                                                    ₦<?php echo number_format($request['tax_amount']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <hr class="my-2">
                                            <p class="text-gray-900 font-semibold">
                                                <span class="font-medium">Total:</span>
                                                ₦<?php echo number_format($request['net_amount']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Admin Notes or Rejection Reason -->
                                <?php if ($request['admin_notes'] || $request['rejection_reason']): ?>
                                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <?php echo $request['status'] === 'rejected' ? 'Rejection Reason' : 'Admin Notes'; ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($request['rejection_reason'] ?: $request['admin_notes']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Processing Information -->
                                <?php if ($request['processed_by'] && $request['processed_at']): ?>
                                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-user-shield text-blue-600 mr-3"></i>
                                            <div>
                                                <p class="text-sm font-medium text-blue-900">
                                                    Processed by: <?php echo htmlspecialchars($request['processed_by_name']); ?>
                                                </p>
                                                <p class="text-sm text-blue-700">
                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($request['processed_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Paystack Transfer Information -->
                                <?php if ($request['paystack_transfer_code']): ?>
                                    <div class="mt-4 p-4 bg-green-50 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-exchange-alt text-green-600 mr-3"></i>
                                            <div>
                                                <p class="text-sm font-medium text-green-900">
                                                    Transfer Code: <?php echo htmlspecialchars($request['paystack_transfer_code']); ?>
                                                </p>
                                                <?php if ($request['paystack_reference']): ?>
                                                    <p class="text-sm text-green-700">
                                                        Reference: <?php echo htmlspecialchars($request['paystack_reference']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
