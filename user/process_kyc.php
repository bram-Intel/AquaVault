<?php
/**
 * AquaVault Capital - Process KYC Document Upload
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if KYC is already approved
try {
    $stmt = $pdo->prepare("SELECT kyc_status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['kyc_status'] === 'approved') {
        header('Location: kyc_status.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("KYC status check error: " . $e->getMessage());
}

// Process file upload
if ($_POST && isset($_FILES['kyc_document'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $document_type = sanitize_input($_POST['document_type']);
        $file = $_FILES['kyc_document'];
        
        // Validate document type
        $allowed_types = ['NIN', 'Driver License', 'International Passport', 'Voter ID'];
        if (!in_array($document_type, $allowed_types)) {
            $error = 'Invalid document type selected.';
        }
        // Validate file
        elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed. Please try again.';
        }
        // Check file size (max 5MB)
        elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File size must be less than 5MB.';
        }
        // Check file type
        else {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $error = 'Only JPG, PNG, and PDF files are allowed.';
            } else {
                // Generate unique filename
                $filename = 'kyc_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = '../assets/uploads/kyc/' . $filename;
                
                // Create directory if it doesn't exist
                if (!is_dir('../assets/uploads/kyc/')) {
                    mkdir('../assets/uploads/kyc/', 0755, true);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    try {
                        // Update user record
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET kyc_document = ?, kyc_document_type = ?, kyc_status = 'pending', 
                                kyc_submitted_at = NOW(), kyc_reviewed_at = NULL, kyc_reviewed_by = NULL
                            WHERE id = ?
                        ");
                        $stmt->execute([$filename, $document_type, $user_id]);
                        
                        $success = 'KYC document uploaded successfully! It will be reviewed within 24-48 hours.';
                        
                        // Log activity
                        log_activity($user_id, 'kyc_uploaded', "Document type: $document_type", $pdo);
                        
                    } catch (PDOException $e) {
                        error_log("KYC update error: " . $e->getMessage());
                        $error = 'Failed to save KYC information. Please try again.';
                        
                        // Remove uploaded file on database error
                        if (file_exists($upload_path)) {
                            unlink($upload_path);
                        }
                    }
                } else {
                    $error = 'Failed to upload file. Please try again.';
                }
            }
        }
    }
}

// Redirect to KYC status page on success
if ($success) {
    header('Location: kyc_status.php?message=uploaded');
    exit();
}

// Redirect back to KYC page with error
if ($error) {
    header('Location: kyc.php?error=' . urlencode($error));
    exit();
}
?>
