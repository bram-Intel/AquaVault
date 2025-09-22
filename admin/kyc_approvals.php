<?php
/**
 * AquaVault Capital - Admin KYC Approvals
 */
session_start();
require_once '../db/connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Process KYC approval/rejection
if ($_POST && isset($_POST['action']) && isset($_POST['user_id'])) {
    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];
    $admin_id = $_SESSION['admin_id'];

    if (in_array($action, ['approve', 'reject'])) {
        try {
            $new_status = ($action === 'approve') ? 'approved' : 'rejected';
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET kyc_status = ?, kyc_reviewed_at = NOW(), kyc_reviewed_by = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$new_status, $admin_id, $user_id])) {
                $success = "KYC document has been " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
            } else {
                $error = "Failed to update KYC status.";
            }
        } catch (PDOException $e) {
            error_log("KYC approval error: " . $e->getMessage());
            $error = "Failed to update KYC status.";
        }
    }
}

// Get pending KYC documents
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone, 
               u.kyc_document, u.kyc_document_type, u.kyc_submitted_at, u.kyc_status,
               a.full_name as reviewed_by_name, u.kyc_reviewed_at
        FROM users u
        LEFT JOIN admin_users a ON u.kyc_reviewed_by = a.id
        WHERE u.kyc_document IS NOT NULL
        ORDER BY 
            CASE WHEN u.kyc_status = 'pending' THEN 1 ELSE 2 END,
            u.kyc_submitted_at DESC
    ");
    $stmt->execute();
    $kyc_documents = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("KYC fetch error: " . $e->getMessage());
    $kyc_documents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Approvals - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="gradient-bg w-8 h-8 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">AV</span>
                    </div>
                    <span class="ml-2 text-xl font-bold text-gray-900">Admin Panel</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">KYC Document Approvals</h1>
            <p class="mt-2 text-gray-600">Review and approve user identification documents</p>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <?php
        $pending_count = count(array_filter($kyc_documents, fn($doc) => $doc['kyc_status'] === 'pending'));
        $approved_count = count(array_filter($kyc_documents, fn($doc) => $doc['kyc_status'] === 'approved'));
        $rejected_count = count(array_filter($kyc_documents, fn($doc) => $doc['kyc_status'] === 'rejected'));
        ?>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <span class="text-yellow-600 text-xl">⏳</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Review</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $pending_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <span class="text-green-600 text-xl">✅</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Approved</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $approved_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <span class="text-red-600 text-xl">❌</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Rejected</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $rejected_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <span class="text-blue-600 text-xl">📄</span>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Documents</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($kyc_documents); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- KYC Documents Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">KYC Documents</h2>
            </div>

            <?php if (empty($kyc_documents)): ?>
                <div class="p-8 text-center">
                    <p class="text-gray-500">No KYC documents found.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($kyc_documents as $doc): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($doc['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($doc['phone']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doc['kyc_document_type']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($doc['kyc_document']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$doc['kyc_status']]; ?>">
                                            <?php echo ucfirst($doc['kyc_status']); ?>
                                        </span>
                                        <?php if ($doc['kyc_status'] !== 'pending' && $doc['reviewed_by_name']): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                By: <?php echo htmlspecialchars($doc['reviewed_by_name']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo date('M j, Y g:i A', strtotime($doc['kyc_reviewed_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y g:i A', strtotime($doc['kyc_submitted_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="view_kyc.php?id=<?php echo $doc['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                        
                                        <?php if ($doc['kyc_status'] === 'pending'): ?>
                                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to approve this document?')">
                                                <input type="hidden" name="user_id" value="<?php echo $doc['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                            </form>
                                            
                                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to reject this document?')">
                                                <input type="hidden" name="user_id" value="<?php echo $doc['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Reject</button>
                                            </form>
                                        <?php endif; ?>
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