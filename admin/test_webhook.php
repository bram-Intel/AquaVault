<?php
/**
 * AquaVault Capital - Webhook Test Script
 * Test webhook integration for transfer events
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

// Handle test webhook submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_webhook') {
    $event_type = $_POST['event_type'];
    $transfer_code = $_POST['transfer_code'];
    $reference = $_POST['reference'];
    $reason = $_POST['reason'] ?? '';
    
    // Create test webhook payload
    $test_payload = [
        'event' => $event_type,
        'data' => [
            'transfer_code' => $transfer_code,
            'reference' => $reference,
            'amount' => 50000,
            'currency' => 'NGN',
            'status' => $event_type === 'transfer.success' ? 'success' : 'failed',
            'failure_reason' => $event_type === 'transfer.failed' ? $reason : null,
            'reason' => $event_type === 'transfer.reversed' ? $reason : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Send test webhook to our webhook handler
    $webhook_url = 'https://aqua.jenniferfan.us/api/webhook.php';
    $payload = json_encode($test_payload);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Paystack-Signature: ' . hash_hmac('sha512', $payload, PAYSTACK_SECRET_KEY)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $test_result = "Error: " . $error;
        $test_status = "error";
    } else {
        $test_result = "HTTP $http_code: " . $result;
        $test_status = $http_code === 200 ? "success" : "error";
    }
}

// Get recent withdrawal requests for testing
try {
    $stmt = $pdo->prepare("
        SELECT wr.*, u.first_name, u.last_name, u.email
        FROM withdrawal_requests wr
        JOIN users u ON wr.user_id = u.id
        WHERE wr.paystack_transfer_code IS NOT NULL
        ORDER BY wr.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_withdrawals = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Recent withdrawals fetch error: " . $e->getMessage());
    $recent_withdrawals = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Webhook - Admin - AquaVault Capital</title>
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Test Webhook Integration</h1>
                        <p class="text-gray-600 mt-2">Test Paystack webhook events for withdrawal processing</p>
                    </div>
                </div>
            </div>

            <!-- Test Result -->
            <?php if (isset($test_result)): ?>
                <div class="alert alert-<?php echo $test_status === 'success' ? 'success' : 'error'; ?> mb-6">
                    <i class="fas fa-<?php echo $test_status === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <strong>Test Result:</strong> <?php echo htmlspecialchars($test_result); ?>
                </div>
            <?php endif; ?>

            <!-- Webhook Test Form -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Send Test Webhook</h3>
                    <p class="text-gray-600">Simulate Paystack webhook events to test withdrawal processing</p>
                </div>
                <div class="card-body">
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="test_webhook">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="event_type" class="form-label">Event Type</label>
                                <select name="event_type" id="event_type" class="form-control" required>
                                    <option value="">Select Event Type</option>
                                    <option value="transfer.success">Transfer Success</option>
                                    <option value="transfer.failed">Transfer Failed</option>
                                    <option value="transfer.reversed">Transfer Reversed</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="transfer_code" class="form-label">Transfer Code</label>
                                <input type="text" name="transfer_code" id="transfer_code" class="form-control" 
                                       placeholder="TRF_xxxxxxxxxx" required>
                                <small class="text-gray-500">Use a real transfer code from recent withdrawals</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reference" class="form-label">Reference</label>
                            <input type="text" name="reference" id="reference" class="form-control" 
                                   placeholder="TST_<?php echo time(); ?>" required>
                        </div>
                        
                        <div class="form-group" id="reason_group" style="display: none;">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" 
                                      placeholder="Enter failure or reversal reason..."></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Send Test Webhook
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Withdrawals -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-lg font-semibold">Recent Withdrawals with Transfer Codes</h3>
                    <p class="text-gray-600">Use these transfer codes for testing</p>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_withdrawals)): ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <i class="fas fa-receipt text-6xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Recent Withdrawals</h3>
                            <p class="text-gray-600">No withdrawals with transfer codes found.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Transfer Code</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($withdrawal['reference']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($withdrawal['first_name'] . ' ' . $withdrawal['last_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($withdrawal['email']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-medium text-gray-900">
                                                    ₦<?php echo number_format($withdrawal['net_amount']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_icon = '';
                                                switch ($withdrawal['status']) {
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
                                                    <?php echo ucfirst($withdrawal['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="font-mono text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($withdrawal['paystack_transfer_code']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M j, Y', strtotime($withdrawal['created_at'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('g:i A', strtotime($withdrawal['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button onclick="useTransferCode('<?php echo htmlspecialchars($withdrawal['paystack_transfer_code']); ?>')" 
                                                        class="btn-secondary btn-sm" title="Use for Testing">
                                                    <i class="fas fa-copy"></i>
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

    <script>
        // Show/hide reason field based on event type
        document.getElementById('event_type').addEventListener('change', function() {
            const reasonGroup = document.getElementById('reason_group');
            const reasonField = document.getElementById('reason');
            
            if (this.value === 'transfer.failed' || this.value === 'transfer.reversed') {
                reasonGroup.style.display = 'block';
                reasonField.required = true;
            } else {
                reasonGroup.style.display = 'none';
                reasonField.required = false;
                reasonField.value = '';
            }
        });
        
        // Use transfer code from table
        function useTransferCode(transferCode) {
            document.getElementById('transfer_code').value = transferCode;
            document.getElementById('transfer_code').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>