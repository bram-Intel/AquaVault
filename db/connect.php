<?php
/**
 * AquaVault Capital - Database Connection
 * Secure PDO connection for cPanel hosting
 */

// Database configuration - Update these with your cPanel MySQL details
$db_host = 'localhost';
$db_name = 'jennifer_newaqua';  // Your database name
$db_user = 'jennifer_aquaman';  // Your database username
$db_pass = 'AquaSwift41##';  // Your database password

try {
    // Create PDO connection with error handling
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact support.");
}

// Function to sanitize input
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

// Function to generate secure random token
if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

// Function to validate email
if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

// Function to validate phone number (Nigerian format)
if (!function_exists('validate_phone')) {
    function validate_phone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^(070|080|081|090|091|080|081|070|090|091)[0-9]{8}$/', $phone);
    }
}

// Function to format currency (Nigerian Naira)
if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return '₦' . number_format($amount, 2);
    }
}

// Function to calculate investment returns
if (!function_exists('calculate_returns')) {
    function calculate_returns($amount, $interest_rate, $duration_days, $tax_rate = 0) {
        $gross_return = $amount * ($interest_rate / 100) * ($duration_days / 365);
        $tax = $gross_return * ($tax_rate / 100);
        $net_return = $gross_return - $tax;
        $total_payout = $amount + $net_return;
        
        return [
            'gross_return' => $gross_return,
            'tax' => $tax,
            'net_return' => $net_return,
            'total_payout' => $total_payout
        ];
    }
}

// Function to generate unique reference
if (!function_exists('generate_reference')) {
    function generate_reference($prefix = 'AV') {
        return $prefix . '_' . time() . '_' . random_int(1000, 9999);
    }
}
?>