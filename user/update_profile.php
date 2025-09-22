<?php
/**
 * AquaVault Capital - Update Profile
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

// Process profile update
if ($_POST) {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = 'All fields are required.';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!validate_phone($phone)) {
        $error = 'Please enter a valid Nigerian phone number.';
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Email address is already taken by another user.';
            } else {
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$first_name, $last_name, $email, $phone, $user_id])) {
                    // Update session variables
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['user_email'] = $email;
                    
                    $success = 'Profile updated successfully!';
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = 'Failed to update profile. Please try again.';
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