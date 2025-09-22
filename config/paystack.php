<?php
/**
 * AquaVault Capital - Paystack Configuration
 * Secure payment gateway configuration
 */

// Paystack API Configuration — FIXED: Removed trailing spaces
define('PAYSTACK_PUBLIC_KEY', 'pk_test_b460df957d8d91ebbb22eb8693b963825a3ecb23');
define('PAYSTACK_SECRET_KEY', 'sk_test_d0d53b3fbc46a0c904c8df19286801ae3d60e5fc');
define('PAYSTACK_BASE_URL', 'https://api.paystack.co'); // ✅ Fixed

// Payment configuration — FIXED: Removed trailing spaces
define('CURRENCY', 'NGN');
define('CALLBACK_URL', 'https://aqua.jenniferfan.us/user/success.php'); // ✅ Fixed
define('WEBHOOK_URL', 'https://aqua.jenniferfan.us/api/webhook.php'); // ✅ Fixed

/**
 * Initialize Paystack payment
 */
if (!function_exists('initialize_payment')) {
    function initialize_payment($email, $amount, $reference, $callback_url = null) {
        $url = PAYSTACK_BASE_URL . "/transaction/initialize";
        
        $fields = [
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo
            'reference' => $reference,
            'currency' => CURRENCY,
            'callback_url' => $callback_url ?: CALLBACK_URL
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Security
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Initialize Error: " . $err);
            return ['status' => false, 'message' => 'Network error'];
        }

        return json_decode($result, true);
    }
}

/**
 * Verify Paystack payment
 */
if (!function_exists('verify_payment')) {
    function verify_payment($reference) {
        $url = PAYSTACK_BASE_URL . "/transaction/verify/" . rawurlencode($reference);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Verify Payment Error: " . $err);
            return ['status' => false, 'message' => 'Network error'];
        }

        return json_decode($result, true);
    }
}

/**
 * Generate payment reference
 */
if (!function_exists('generate_payment_reference')) {
    function generate_payment_reference($prefix = 'AV') {
        return $prefix . '_' . time() . '_' . random_int(1000, 9999);
    }
}

/**
 * Create Paystack transfer recipient
 */
if (!function_exists('create_transfer_recipient')) {
    function create_transfer_recipient($type, $name, $account_number, $bank_code) {
        $url = PAYSTACK_BASE_URL . "/transferrecipient";
        
        $fields = [
            'type' => $type, // 'nuban' for Nigerian banks
            'name' => $name,
            'account_number' => $account_number,
            'bank_code' => $bank_code,
            'currency' => CURRENCY
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Create Recipient Error: " . $err);
            return ['status' => false, 'message' => 'Network error'];
        }

        return json_decode($result, true);
    }
}

/**
 * Initiate Paystack transfer
 */
if (!function_exists('initiate_transfer')) {
    function initiate_transfer($source, $amount, $recipient, $reason = null) {
        $url = PAYSTACK_BASE_URL . "/transfer";
        
        $fields = [
            'source' => $source, // 'balance' to transfer from your balance
            'amount' => $amount * 100, // Convert to kobo
            'recipient' => $recipient,
            'reason' => $reason ?: 'Withdrawal from AquaVault Capital'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Initiate Transfer Error: " . $err);
            return ['status' => false, 'message' => 'Network error'];
        }

        return json_decode($result, true);
    }
}

/**
 * Verify Paystack transfer
 */
if (!function_exists('verify_transfer')) {
    function verify_transfer($transfer_code) {
        $url = PAYSTACK_BASE_URL . "/transfer/" . rawurlencode($transfer_code);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Verify Transfer Error: " . $err);
            return ['status' => false, 'message' => 'Network error'];
        }

        return json_decode($result, true);
    }
}

/**
 * Get list of Nigerian banks
 */
if (!function_exists('get_nigerian_banks')) {
    function get_nigerian_banks() {
        $url = PAYSTACK_BASE_URL . "/bank?country=nigeria";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Get Banks Error: " . $err);
            return ['status' => false, 'message' => 'Network error', 'data' => []];
        }

        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid response', 'data' => []];
    }
}

/**
 * Resolve bank account number — ✅ FULLY FIXED
 */
if (!function_exists('resolve_bank_account')) {
    function resolve_bank_account($account_number, $bank_code) {
        // Sanitize inputs
        $account_number = preg_replace('/\D/', '', $account_number); // Keep only digits
        $bank_code = trim($bank_code);

        // Validate
        if (strlen($account_number) !== 10) {
            error_log("Invalid account number length: " . $account_number);
            return ['status' => false, 'message' => 'Account number must be 10 digits'];
        }

        if (empty($bank_code)) {
            error_log("Empty bank code provided");
            return ['status' => false, 'message' => 'Bank code is required'];
        }

        $url = PAYSTACK_BASE_URL . "/bank/resolve?account_number=" . urlencode($account_number) . "&bank_code=" . urlencode($bank_code);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            error_log("Paystack Resolve Account cURL Error: " . $err);
            return ['status' => false, 'message' => 'Network error: ' . $err];
        }

        $decoded = json_decode($result, true);

        // Log raw response for debugging
        error_log("Paystack Resolve Response (HTTP $http_code): " . print_r($decoded, true));

        if (!is_array($decoded)) {
            error_log("Paystack returned invalid JSON: " . $result);
            return ['status' => false, 'message' => 'Invalid response from Paystack'];
        }

        if (isset($decoded['status']) && $decoded['status'] === true) {
            return [
                'status' => true,
                'data' => $decoded['data'] ?? []
            ];
        } else {
            $message = $decoded['message'] ?? 'Unknown error';
            error_log("Paystack account resolve failed: " . $message);
            return [
                'status' => false,
                'message' => $message
            ];
        }
    }
}
?>