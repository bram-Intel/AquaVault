<?php
/**
 * AquaVault Capital - Change Password
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

// Process password change
if ($_POST) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        try {
            // Get current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$new_password_hash, $user_id])) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            }
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = 'Failed to change password. Please try again.';
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