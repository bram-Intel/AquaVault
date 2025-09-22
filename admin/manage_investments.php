<?php
/**
 * AquaVault Capital - Admin Investment Management
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if admin is logged in
require_admin();

$success_message = '';
$error_message = '';

// Handle investment actions
if ($_POST && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        $investment_id = (int)$_POST['investment_id'];
        $action = $_POST['action'];
        
        try {
            switch ($action) {
                case 'approve':
                    // Activate investment
                    $stmt = $pdo->prepare("
                        UPDATE user_investments 
                        SET status = 'active', payment_status = 'paid', updated_at = NOW() 
                        WHERE id = ? AND status = 'pending'
                    ");
                    $stmt->execute([$investment_id]);
                    
                    // Update transaction status
                    $stmt = $pdo->prepare("
                        UPDATE transactions 
                        SET status = 'completed', updated_at = NOW() 
                        WHERE reference = (SELECT reference FROM user_investments WHERE id = ?)
                    ");
                    $stmt->execute([$investment_id]);
                    
                    // Update user total invested
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET total_invested = total_invested + (
                            SELECT amount FROM user_investments WHERE id = ?
                        ) 
                        WHERE id = (SELECT user_id FROM user_investments WHERE id = ?)
                    ");
                    $stmt->execute([$investment_id, $investment_id]);
                    
                    $success_message = 'Investment approved and activated successfully!';
                    break;
                    
                case 'reject':
                    // Cancel investment
                    $stmt = $pdo->prepare("
                        UPDATE user_investments 
                        SET status = 'cancelled', payment_status = 'failed', updated_at = NOW() 
                        WHERE id = ? AND status = 'pending'
                    ");
                    $stmt->execute([$investment_id]);
                    
                    // Update transaction status
                    $stmt = $pdo->prepare("
                        UPDATE transactions 
                        SET status = 'failed', updated_at = NOW() 
                        WHERE reference = (SELECT reference FROM user_investments WHERE id = ?)
                    ");
                    $stmt->execute([$investment_id]);
                    
                    $success_message = 'Investment rejected and cancelled.';
                    break;
            }
        } catch (PDOException $e) {
            error_log("Investment management error: " . $e->getMessage());
            $error_message = 'Error processing investment: ' . $e->getMessage();
        }
    }
}

// Get pending investments
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, u.first_name, u.last_name, u.email, u.phone,
               ic.name as category_name, ic.icon as category_icon,
               id.name as duration_name
        FROM user_investments ui
        JOIN users u ON ui.user_id = u.id
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.status = 'pending'
        ORDER BY ui.created_at DESC
    ");
    $stmt->execute();
    $pending_investments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Pending investments fetch error: " . $e->getMessage());
    $pending_investments = [];
}

// Get active investments
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, u.first_name, u.last_name, u.email, u.phone,
               ic.name as category_name, ic.icon as category_icon,
               id.name as duration_name
        FROM user_investments ui
        JOIN users u ON ui.user_id = u.id
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.status = 'active'
        ORDER BY ui.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $active_investments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Active investments fetch error: " . $e->getMessage());
    $active_investments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Investments - AquaVault Capital Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Investment Management</h1>
            <p class="mt-2 text-gray-600">Manage pending and active investments</p>
        </div>

        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Pending Investments -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Pending Investments (<?php echo count($pending_investments); ?>)</h2>
            
            <?php if (empty($pending_investments)): ?>
                <div class="text-center py-8">
                    <div class="text-4xl mb-4">✅</div>
                    <p class="text-gray-500">No pending investments</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pending_investments as $investment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($investment['first_name'] . ' ' . $investment['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($investment['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($investment['phone']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-2xl mr-3"><?php echo $investment['category_icon'] ?? '📊'; ?></span>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($investment['category_name'] ?? 'Investment'); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($investment['duration_name'] ?? 'Duration'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦<?php echo number_format($investment['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $investment['duration_days'] ?? 'N/A'; ?> days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y H:i', strtotime($investment['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="investment_id" value="<?php echo $investment['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" 
                                                    onclick="return confirm('Approve this investment?')"
                                                    class="text-green-600 hover:text-green-900 font-medium">
                                                ✅ Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="investment_id" value="<?php echo $investment['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" 
                                                    onclick="return confirm('Reject this investment?')"
                                                    class="text-red-600 hover:text-red-900 font-medium">
                                                ❌ Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Active Investments -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Active Investments</h2>
            
            <?php if (empty($active_investments)): ?>
                <div class="text-center py-8">
                    <div class="text-4xl mb-4">📊</div>
                    <p class="text-gray-500">No active investments</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maturity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($active_investments as $investment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($investment['first_name'] . ' ' . $investment['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($investment['email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-2xl mr-3"><?php echo $investment['category_icon'] ?? '📊'; ?></span>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($investment['category_name'] ?? 'Investment'); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($investment['duration_name'] ?? 'Duration'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦<?php echo number_format($investment['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($investment['maturity_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            🔒 Active
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
