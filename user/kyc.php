<?php
/**
 * AquaVault Capital - KYC Document Upload
 */
session_start();
require_once '../db/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user's current KYC status
try {
    $stmt = $pdo->prepare("SELECT kyc_status, kyc_document, kyc_document_type, kyc_submitted_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("KYC fetch error: " . $e->getMessage());
    $error = 'Unable to load KYC information.';
}

// Process KYC upload
if ($_POST && isset($_FILES['kyc_document'])) {
    $document_type = sanitize_input($_POST['document_type'] ?? '');
    $file = $_FILES['kyc_document'];

    // Validation
    if (empty($document_type)) {
        $error = 'Please select a document type.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        $error = 'File size must be less than 5MB.';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Only JPEG, PNG, and PDF files are allowed.';
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../assets/uploads/kyc/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'kyc_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    // Update user's KYC information
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET kyc_document = ?, kyc_document_type = ?, kyc_status = 'pending', kyc_submitted_at = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$filename, $document_type, $user_id])) {
                        $_SESSION['kyc_status'] = 'pending';
                        $success = 'KYC document uploaded successfully! Your document is under review.';
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT kyc_status, kyc_document, kyc_document_type, kyc_submitted_at FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user = $stmt->fetch();
                    } else {
                        unlink($file_path); // Delete uploaded file
                        $error = 'Failed to save KYC information.';
                    }
                } catch (PDOException $e) {
                    error_log("KYC update error: " . $e->getMessage());
                    unlink($file_path); // Delete uploaded file
                    $error = 'Failed to save KYC information.';
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
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
    <title>Profile - AquaVault Capital</title>
    <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
   <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">KYC Verification</h1>
            <p class="mt-2 text-gray-600">Upload your identification document for account verification</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- KYC Status Card -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Verification Status</h2>
                
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
                
                <div class="mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium border <?php echo $status_colors[$user['kyc_status']]; ?>">
                        <?php echo $status_text[$user['kyc_status']]; ?>
                    </span>
                </div>

                <?php if ($user['kyc_document']): ?>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Document Type:</span>
                            <span class="text-sm text-gray-900 ml-2"><?php echo htmlspecialchars($user['kyc_document_type']); ?></span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Submitted:</span>
                            <span class="text-sm text-gray-900 ml-2"><?php echo date('M j, Y g:i A', strtotime($user['kyc_submitted_at'])); ?></span>
                        </div>
                        <?php if ($user['kyc_status'] === 'approved'): ?>
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-green-700 text-sm">✅ Your account is verified! You can now make investments.</p>
                            </div>
                        <?php elseif ($user['kyc_status'] === 'rejected'): ?>
                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-700 text-sm">❌ Your document was rejected. Please upload a new document.</p>
                            </div>
                        <?php else: ?>
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-yellow-700 text-sm">⏳ Your document is being reviewed. This usually takes 24-48 hours.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-blue-700 text-sm">📄 No document uploaded yet. Please upload your ID to start verification.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload Form -->
            <?php if ($user['kyc_status'] !== 'approved'): ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <?php echo $user['kyc_document'] ? 'Upload New Document' : 'Upload Document'; ?>
                </h2>

                <?php if ($error): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                        <select id="document_type" name="document_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select document type</option>
                            <option value="NIN">National Identity Number (NIN)</option>
                            <option value="Driver's License">Driver's License</option>
                            <option value="International Passport">International Passport</option>
                            <option value="Voter's Card">Voter's Card</option>
                        </select>
                    </div>

                    <div>
                        <label for="kyc_document" class="block text-sm font-medium text-gray-700 mb-2">Upload Document</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                            <input type="file" id="kyc_document" name="kyc_document" accept=".jpg,.jpeg,.png,.pdf" required
                                   class="hidden" onchange="updateFileName(this)">
                            <label for="kyc_document" class="cursor-pointer">
                                <div class="text-gray-400 mb-2">
                                    <svg class="mx-auto h-12 w-12" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-600">Click to upload or drag and drop</p>
                                <p class="text-xs text-gray-500 mt-1">JPEG, PNG, PDF (Max 5MB)</p>
                            </label>
                        </div>
                        <p id="file-name" class="mt-2 text-sm text-gray-600"></p>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">Document Requirements:</h3>
                        <ul class="text-xs text-blue-800 space-y-1">
                            <li>• Document must be clear and readable</li>
                            <li>• All corners of the document must be visible</li>
                            <li>• Document must be valid and not expired</li>
                            <li>• File size should not exceed 5MB</li>
                        </ul>
                    </div>

                    <button type="submit" 
                            class="w-full gradient-bg text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition duration-200">
                        Upload Document
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Navigation Links -->
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = document.getElementById('file-name');
            if (input.files.length > 0) {
                const file = input.files[0];
                const size = (file.size / 1024 / 1024).toFixed(2);
                fileName.textContent = `Selected: ${file.name} (${size} MB)`;
            } else {
                fileName.textContent = '';
            }
        }

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        const fileInput = document.getElementById('kyc_document');

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileName(fileInput);
            }
        });
    </script>
</body>
</html>