<?php
/**
 * AquaVault Capital - Upload Avatar
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

// Process avatar upload
if ($_POST && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    // Validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        $error = 'File size must be less than 2MB.';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Only JPEG and PNG files are allowed.';
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../assets/uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Get current avatar to delete later
            try {
                $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_avatar = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $current_avatar = null;
            }

            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    // Update user's avatar
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    
                    if ($stmt->execute([$filename, $user_id])) {
                        // Delete old avatar if exists
                        if ($current_avatar && file_exists($upload_dir . $current_avatar)) {
                            unlink($upload_dir . $current_avatar);
                        }
                        
                        $success = 'Avatar updated successfully!';
                    } else {
                        unlink($file_path); // Delete uploaded file
                        $error = 'Failed to update avatar.';
                    }
                } catch (PDOException $e) {
                    error_log("Avatar update error: " . $e->getMessage());
                    unlink($file_path); // Delete uploaded file
                    $error = 'Failed to update avatar.';
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    }
}

// Redirect back to profile with message
if ($success) {
    $_SESSION['success_message'] = $success;
} elseif ($error) {
    $_SESSION['error_message'] = $error;
}

header('Location: profile.php');
exit();
?>