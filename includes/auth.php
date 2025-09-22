<?php
/**
 * AquaVault Capital - Authentication Helper
 * Shared authentication functions and checks
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
if (!function_exists('require_login')) {
    function require_login() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }
    }
}

/**
 * Check if user is admin
 */
if (!function_exists('require_admin')) {
    function require_admin() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: login.php');
            exit();
        }
    }
}

/**
 * Check KYC status
 */
if (!function_exists('require_kyc_approved')) {
    function require_kyc_approved($user_id, $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT kyc_status FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || $user['kyc_status'] !== 'approved') {
                header('Location: kyc.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log("KYC check error: " . $e->getMessage());
            header('Location: kyc.php');
            exit();
        }
    }
}

/**
 * Generate CSRF token
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verify CSRF token
 */
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Note: sanitize_input, validate_email, and validate_phone functions are defined in db/connect.php

// Note: format_currency, calculate_returns, and generate_reference functions are defined in db/connect.php

/**
 * Log user activity
 */
if (!function_exists('log_activity')) {
    function log_activity($user_id, $action, $details = '', $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_activities (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user_id,
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (PDOException $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}
?>
