<?php
/**
 * AquaVault Capital - KYC Status
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];

// Get user KYC information
try {
    $stmt = $pdo->prepare("
        SELECT kyc_status, kyc_document, kyc_document_type, kyc_submitted_at, 
               kyc_reviewed_at, kyc_reviewed_by, au.full_name as reviewed_by_name
        FROM users u
        LEFT JOIN admin_users au ON u.kyc_reviewed_by = au.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $kyc_info = $stmt->fetch();
    
    if (!$kyc_info) {
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("KYC info fetch error: " . $e->getMessage());
    header('Location: kyc.php');
    exit();
}

$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'uploaded':
            $message = 'KYC document uploaded successfully! It will be reviewed within 24-48 hours.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Status - AquaVault Capital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">KYC Verification Status</h1>
            <p class="mt-2 text-gray-600">Track your identity verification progress</p>
        </div>

        <!-- Success Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- KYC Status Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Verification Status</h2>
                <div class="flex items-center">
                    <?php
                    $status_config = [
                        'pending' => ['color' => 'yellow', 'icon' => '⏳', 'text' => 'Under Review'],
                        'approved' => ['color' => 'green', 'icon' => '✅', 'text' => 'Verified'],
                        'rejected' => ['color' => 'red', 'icon' => '❌', 'text' => 'Rejected']
                    ];
                    $config = $status_config[$kyc_info['kyc_status']] ?? $status_config['pending'];
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?php echo $config['color']; ?>-100 text-<?php echo $config['color']; ?>-800">
                        <?php echo $config['icon']; ?> <?php echo $config['text']; ?>
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                <?php if ($kyc_info['kyc_document_type']): ?>
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Document Type</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($kyc_info['kyc_document_type']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($kyc_info['kyc_submitted_at']): ?>
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Submitted On</span>
                        <span class="font-semibold"><?php echo date('M d, Y H:i', strtotime($kyc_info['kyc_submitted_at'])); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($kyc_info['kyc_reviewed_at']): ?>
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Reviewed On</span>
                        <span class="font-semibold"><?php echo date('M d, Y H:i', strtotime($kyc_info['kyc_reviewed_at'])); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($kyc_info['reviewed_by_name']): ?>
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Reviewed By</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($kyc_info['reviewed_by_name']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($kyc_info['kyc_document']): ?>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-600">Document</span>
                        <a href="../assets/uploads/kyc/<?php echo htmlspecialchars($kyc_info['kyc_document']); ?>" 
                           target="_blank"
                           class="text-blue-600 hover:text-blue-800 font-medium">
                            View Document
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status-specific content -->
        <?php if ($kyc_info['kyc_status'] === 'pending'): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-yellow-900 mb-4">⏳ Under Review</h3>
                <div class="space-y-3 text-sm text-yellow-800">
                    <p>• Your KYC document has been submitted and is currently under review</p>
                    <p>• Our team will verify your identity within 24-48 hours</p>
                    <p>• You'll receive an email notification once the review is complete</p>
                    <p>• You can upload a new document if needed</p>
                </div>
            </div>
        <?php elseif ($kyc_info['kyc_status'] === 'approved'): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-green-900 mb-4">✅ Verification Complete</h3>
                <div class="space-y-3 text-sm text-green-800">
                    <p>• Your identity has been successfully verified</p>
                    <p>• You can now make investments on our platform</p>
                    <p>• Your account is fully activated</p>
                </div>
            </div>
        <?php elseif ($kyc_info['kyc_status'] === 'rejected'): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-red-900 mb-4">❌ Verification Rejected</h3>
                <div class="space-y-3 text-sm text-red-800">
                    <p>• Your KYC document was rejected during review</p>
                    <p>• Please ensure your document is clear and valid</p>
                    <p>• You can upload a new document to try again</p>
                    <p>• Contact support if you need assistance</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <?php if ($kyc_info['kyc_status'] !== 'approved'): ?>
                <a href="kyc.php" 
                   class="gradient-bg text-white px-8 py-3 rounded-lg font-medium hover:opacity-90 transition duration-200 text-center">
                    <?php echo $kyc_info['kyc_document'] ? 'Upload New Document' : 'Upload Document'; ?>
                </a>
            <?php endif; ?>
            
            <a href="dashboard.php" 
               class="px-8 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200 text-center">
                Back to Dashboard
            </a>
        </div>

        <!-- Support Information -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 text-sm">
                Need help with KYC verification? Contact us at 
                <a href="mailto:support@aquavault.com" class="text-blue-600 hover:text-blue-800">support@aquavault.com</a>
            </p>
        </div>
    </div>
</body>
</html>
