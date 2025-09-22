<?php
/**
 * AquaVault Capital - Admin View KYC Document
 */
session_start();
require_once '../db/connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$user_id) {
    header('Location: kyc_approvals.php');
    exit();
}

// Process KYC approval/rejection
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
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

// Get user and KYC information
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.phone, 
               u.kyc_document, u.kyc_document_type, u.kyc_submitted_at, u.kyc_status,
               u.kyc_reviewed_at, u.created_at,
               a.full_name as reviewed_by_name
        FROM users u
        LEFT JOIN admin_users a ON u.kyc_reviewed_by = a.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: kyc_approvals.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("User fetch error: " . $e->getMessage());
    $error = "Failed to load user information.";
}

// Check if document file exists
$document_path = '../assets/uploads/kyc/' . $user['kyc_document'];
$document_exists = $user['kyc_document'] && file_exists($document_path);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View KYC Document - Admin Panel</title>
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
                    <a href="kyc_approvals.php" class="text-blue-600 hover:text-blue-800">KYC Approvals</a>
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">KYC Document Review</h1>
                    <p class="mt-2 text-gray-600">Review identification document for <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                </div>
                <a href="kyc_approvals.php" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200">
                    ← Back to KYC Approvals
                </a>
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- User Information -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">User Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email Address</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Created</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- KYC Status -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">KYC Status</h2>
                
                <?php
                $status_colors = [
                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    'approved' => 'bg-green-100 text-green-800 border-green-200',
                    'rejected' => 'bg-red-100 text-red-800 border-red-200'
                ];
                $status_text = [
                    'pending' => 'Under Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected'
                ];
                ?>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Status</label>
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium border <?php echo $status_colors[$user['kyc_status']]; ?>">
                                <?php echo $status_text[$user['kyc_status']]; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Document Type</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['kyc_document_type']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Submitted Date</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($user['kyc_submitted_at'])); ?></p>
                    </div>
                    
                    <?php if ($user['kyc_status'] !== 'pending' && $user['reviewed_by_name']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reviewed By</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['reviewed_by_name']); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Review Date</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($user['kyc_reviewed_at'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <?php if ($user['kyc_status'] === 'pending'): ?>
                    <div class="mt-6 flex space-x-3">
                        <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to approve this document?')">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                                ✅ Approve
                            </button>
                        </form>
                        
                        <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to reject this document?')">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                                ❌ Reject
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Viewer -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Document Preview</h2>
            
            <?php if (!$document_exists): ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">📄</div>
                    <p class="text-gray-500">Document file not found or has been removed.</p>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <?php
                    $file_extension = strtolower(pathinfo($user['kyc_document'], PATHINFO_EXTENSION));
                    if (in_array($file_extension, ['jpg', 'jpeg', 'png'])):
                    ?>
                        <img src="<?php echo htmlspecialchars($document_path); ?>" 
                             alt="KYC Document" 
                             class="max-w-full h-auto mx-auto rounded-lg shadow-lg"
                             style="max-height: 600px;">
                    <?php elseif ($file_extension === 'pdf'): ?>
                        <div class="bg-gray-100 rounded-lg p-8">
                            <div class="text-gray-400 text-6xl mb-4">📄</div>
                            <p class="text-gray-600 mb-4">PDF Document</p>
                            <a href="<?php echo htmlspecialchars($document_path); ?>" 
                               target="_blank" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                📥 Download & View PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-100 rounded-lg p-8">
                            <div class="text-gray-400 text-6xl mb-4">📄</div>
                            <p class="text-gray-600 mb-4">Unsupported file format</p>
                            <a href="<?php echo htmlspecialchars($document_path); ?>" 
                               target="_blank" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                📥 Download File
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-sm text-gray-500">
                        <p>Filename: <?php echo htmlspecialchars($user['kyc_document']); ?></p>
                        <p>File size: <?php echo file_exists($document_path) ? number_format(filesize($document_path) / 1024, 2) . ' KB' : 'Unknown'; ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Review Guidelines -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">📋 Review Guidelines</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                <div>
                    <h4 class="font-medium mb-2">✅ Approve if:</h4>
                    <ul class="space-y-1">
                        <li>• Document is clear and readable</li>
                        <li>• All corners are visible</li>
                        <li>• Document is valid and not expired</li>
                        <li>• Information matches user profile</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-2">❌ Reject if:</h4>
                    <ul class="space-y-1">
                        <li>• Document is blurry or unclear</li>
                        <li>• Document is expired</li>
                        <li>• Information doesn't match profile</li>
                        <li>• Document appears tampered/fake</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>