<?php
/**
 * AquaVault Capital - Admin Withdrawal Requests
 * Manage and process withdrawal requests
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';
require_once '../config/paystack.php';

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
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    try {
        // Get withdrawal request details
        $stmt = $pdo->prepare("
            SELECT wr.*, uba.account_name, uba.account_number, uba.bank_code, uba.bank_name,
                   u.first_name, u.last_name, u.email
            FROM withdrawal_requests wr
            LEFT JOIN user_bank_accounts uba ON wr.bank_account_id = uba.id
            LEFT JOIN users u ON wr.user_id = u.id
            WHERE wr.id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            $error = "Withdrawal request not found.";
        } else {
            switch ($action) {
                case 'approve':
                    // Update status to approved
                    $stmt = $pdo->prepare("
                        UPDATE withdrawal_requests 
                        SET status = 'approved', processed_by = ?, processed_at = NOW()
                        WHERE id = ?
                    ");
                    if ($stmt->execute([$admin_id, $request_id])) {
                        $success = "Withdrawal request approved successfully!";
                    } else {
                        $error = "Failed to approve withdrawal request.";
                    }
                    break;
                    
                case 'reject':
                    $rejection_reason = trim($_POST['rejection_reason']);
                    if (empty($rejection_reason)) {
                        $error = "Rejection reason is required.";
                    } else {
                        // Update status to rejected
                        $stmt = $pdo->prepare("
                            UPDATE withdrawal_requests 
                            SET status = 'rejected', rejection_reason = ?, processed_by = ?, processed_at = NOW()
                            WHERE id = ?
                        ");
                        if ($stmt->execute([$rejection_reason, $admin_id, $request_id])) {
                            $success = "Withdrawal request rejected successfully!";
                        } else {
                            $error = "Failed to reject withdrawal request.";
                        }
                    }
                    break;
                    
                case 'process':
                    // Create transfer recipient
                    $recipient_response = create_transfer_recipient(
                        'nuban',
                        $request['account_name'],
                        $request['account_number'],
                        $request['bank_code']
                    );
                    
                    if ($recipient_response['status']) {
                        $recipient_code = $recipient_response['data']['recipient_code'];
                        
                        // Initiate transfer
                        $transfer_response = initiate_transfer(
                            'balance',
                            $request['net_amount'],
                            $recipient_code,
                            'Withdrawal from AquaVault Capital - ' . $request['reference']
                        );
                        
                        if ($transfer_response['status']) {
                            $transfer_code = $transfer_response['data']['transfer_code'];
                            $transfer_reference = $transfer_response['data']['reference'];
                            
                            // Update withdrawal request with transfer details
                            $stmt = $pdo->prepare("
                                UPDATE withdrawal_requests 
                                SET status = 'processing', 
                                    paystack_transfer_code = ?, 
                                    paystack_reference = ?,
                                    processed_by = ?, 
                                    processed_at = NOW()
                                WHERE id = ?
                            ");
                            
                            if ($stmt->execute([$transfer_code, $transfer_reference, $admin_id, $request_id])) {
                                // Update transaction status
                                $stmt = $pdo->prepare("
                                    UPDATE transactions 
                                    SET status = 'completed', payment_reference = ?
                                    WHERE reference = ?
                                ");
                                $stmt->execute([$transfer_reference, $request['reference']]);
                                
                                $success = "Withdrawal processed successfully! Transfer Code: " . $transfer_code;
                            } else {
                                $error = "Failed to update withdrawal request with transfer details.";
                            }
                        } else {
                            $error = "Failed to initiate transfer: " . ($transfer_response['message'] ?? 'Unknown error');
                        }
                    } else {
                        $error = "Failed to create transfer recipient: " . ($recipient_response['message'] ?? 'Unknown error');
                    }
                    break;
            }
        }
    } catch (PDOException $e) {
        error_log("Withdrawal processing error: " . $e->getMessage());
        $error = "An error occurred while processing the withdrawal request.";
    }
}

// Get withdrawal requests with filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "wr.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(wr.reference LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $stmt = $pdo->prepare("
        SELECT wr.*, 
               u.first_name, u.last_name, u.email,
               uba.account_name, uba.account_number, uba.bank_name,
               ui.reference as investment_reference,
               ic.name as category_name,
               id.name as duration_name,
               au.full_name as processed_by_name
        FROM withdrawal_requests wr
        LEFT JOIN users u ON wr.user_id = u.id
        LEFT JOIN user_bank_accounts uba ON wr.bank_account_id = uba.id
        LEFT JOIN user_investments ui ON wr.investment_id = ui.id
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        LEFT JOIN admin_users au ON wr.processed_by = au.id
        $where_clause
        ORDER BY wr.created_at DESC
    ");
    $stmt->execute($params);
    $withdrawal_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Withdrawal requests fetch error: " . $e->getMessage());
    $withdrawal_requests = [];
}

// Get statistics
try {
    $stats = [];
    
    // Total pending
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(net_amount) as total FROM withdrawal_requests WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending'] = $stmt->fetch();
    
    // Total approved
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(net_amount) as total FROM withdrawal_requests WHERE status = 'approved'");
    $stmt->execute();
    $stats['approved'] = $stmt->fetch();
    
    // Total processing
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(net_amount) as total FROM withdrawal_requests WHERE status = 'processing'");
    $stmt->execute();
    $stats['processing'] = $stmt->fetch();
    
    // Total completed
    $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(net_amount) as total FROM withdrawal_requests WHERE status = 'completed'");
    $stmt->execute();
    $stats['completed'] = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Statistics fetch error: " . $e->getMessage());
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Requests - Admin - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Withdrawal Requests</h1>
                        <p class="text-gray-600 mt-2">Manage and process user withdrawal requests</p>
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

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo $stats['pending']['count'] ?? 0; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                ₦<?php echo number_format($stats['pending']['total'] ?? 0); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Approved</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo $stats['approved']['count'] ?? 0; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                ₦<?php echo number_format($stats['approved']['total'] ?? 0); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-spinner text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Processing</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo $stats['processing']['count'] ?? 0; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                ₦<?php echo number_format($stats['processing']['total'] ?? 0); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Completed</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php echo $stats['completed']['count'] ?? 0; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                ₦<?php echo number_format($stats['completed']['total'] ?? 0); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-6">
                <div class="card-body">
                    <form method="GET" class="flex flex-wrap items-center gap-4">
                        <div class="flex-1 min-w-64">
                            <input type="text" name="search" placeholder="Search by reference, name, or email..." 
                                   class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search mr-2"></i>
                            Filter
                        </button>
                        <a href="withdrawal_requests.php" class="btn-secondary">
                            <i class="fas fa-times mr-2"></i>
                            Clear
                        </a>
                    </form>
                </div>
            </div>

            <!-- Withdrawal Requests Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Withdrawal Requests</h3>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($withdrawal_requests)): ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <i class="fas fa-receipt text-6xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Withdrawal Requests</h3>
                            <p class="text-gray-600">No withdrawal requests found matching your criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>User</th>
                                        <th>Investment</th>
                                        <th>Bank Details</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($withdrawal_requests as $request): ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($request['reference']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($request['email']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($request['category_name'] ?? 'N/A'); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($request['investment_reference'] ?? 'N/A'); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($request['account_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($request['bank_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($request['account_number']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-right">
                                                    <div class="font-medium text-gray-900">
                                                        ₦<?php echo number_format($request['net_amount']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Principal: ₦<?php echo number_format($request['principal_amount']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Returns: ₦<?php echo number_format($request['returns_amount']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
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
                                            </td>
                                            <td>
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('g:i A', strtotime($request['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex space-x-2">
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <button type="submit" class="btn-success btn-sm" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <button onclick="openRejectModal(<?php echo $request['id']; ?>)" 
                                                                class="btn-danger btn-sm" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($request['status'] === 'approved'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="action" value="process">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                            <button type="submit" class="btn-primary btn-sm" title="Process Payment">
                                                                <i class="fas fa-money-bill-wave"></i>
                                                            </button>
                                                        </form>
                                                    <?php elseif ($request['status'] === 'processing'): ?>
                                                        <span class="text-sm text-gray-500" title="Status will be updated automatically via webhook">
                                                            <i class="fas fa-sync-alt mr-1"></i>
                                                            Processing
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <button onclick="viewRequestDetails(<?php echo $request['id']; ?>)" 
                                                            class="btn-secondary btn-sm" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
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

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-xl font-semibold">Reject Withdrawal Request</h2>
                <button onclick="closeRejectModal()" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject_request_id">
                
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">Rejection Reason</label>
                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" 
                              rows="4" placeholder="Please provide a reason for rejecting this withdrawal request..." required></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeRejectModal()" class="btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn-danger">
                        <i class="fas fa-times mr-2"></i>
                        Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(requestId) {
            document.getElementById('reject_request_id').value = requestId;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
            document.getElementById('rejection_reason').value = '';
        }
        
        function viewRequestDetails(requestId) {
            // You can implement a detailed view modal here
            alert('View details for request ID: ' + requestId);
        }
    </script>
</body>
</html>
