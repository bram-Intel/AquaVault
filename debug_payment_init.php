<?php
/**
 * Debug script to test payment initialization
 */
session_start();
require_once 'db/connect.php';
require_once 'config/paystack.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login instead of dying
    header('Location: user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die('User not found');
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

echo "<h1>Payment Initialization Debug</h1>";
echo "<p><strong>User:</strong> " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</p>";
echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";

// Test payment initialization
$test_amount = 10000; // ₦100
$test_reference = generate_payment_reference();
$callback_url = 'https://' . $_SERVER['HTTP_HOST'] . '/user/success.php';

echo "<h2>Payment Parameters</h2>";
echo "<p><strong>Amount:</strong> ₦" . number_format($test_amount) . "</p>";
echo "<p><strong>Reference:</strong> $test_reference</p>";
echo "<p><strong>Callback URL:</strong> $callback_url</p>";
echo "<p><strong>Paystack Public Key:</strong> " . PAYSTACK_PUBLIC_KEY . "</p>";
echo "<p><strong>Paystack Secret Key:</strong> " . substr(PAYSTACK_SECRET_KEY, 0, 10) . "...</p>";

echo "<h2>Testing Payment Initialization</h2>";

try {
    $payment_response = initialize_payment(
        $user['email'],
        $test_amount,
        $test_reference,
        $callback_url
    );
    
    echo "<h3>Response:</h3>";
    echo "<pre>" . print_r($payment_response, true) . "</pre>";
    
    if ($payment_response['status']) {
        echo "<p style='color: green;'><strong>✅ Payment initialization successful!</strong></p>";
        echo "<p><a href='" . $payment_response['data']['authorization_url'] . "' target='_blank'>Test Payment Link</a></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Payment initialization failed!</strong></p>";
        if (isset($payment_response['message'])) {
            echo "<p><strong>Error:</strong> " . htmlspecialchars($payment_response['message']) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Exception occurred:</strong></p>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<h2>cURL Test</h2>";
echo "<p>Testing direct cURL to Paystack API...</p>";

$url = PAYSTACK_BASE_URL . "/transaction/initialize";
$fields = [
    'email' => $user['email'],
    'amount' => $test_amount * 100,
    'reference' => $test_reference,
    'currency' => CURRENCY,
    'callback_url' => $callback_url
];

$fields_string = http_build_query($fields);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    "Cache-Control: no-cache",
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $http_code</p>";
if ($curl_error) {
    echo "<p style='color: red;'><strong>cURL Error:</strong> $curl_error</p>";
}
echo "<p><strong>Raw Response:</strong></p>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

echo "<p><a href='user/dashboard.php'>Back to Dashboard</a></p>";
?>
