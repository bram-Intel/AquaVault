<?php
/**
 * AquaVault Capital - Investment Calculator API
 * Real-time calculation endpoint
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['amount', 'interest_rate', 'duration_days', 'tax_rate'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || !is_numeric($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing or invalid field: $field"]);
        exit();
    }
}

$amount = (float)$input['amount'];
$interest_rate = (float)$input['interest_rate'];
$duration_days = (int)$input['duration_days'];
$tax_rate = (float)$input['tax_rate'];

// Validate ranges
if ($amount <= 0 || $amount > 100000000) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount must be between 1 and 100,000,000']);
    exit();
}

if ($interest_rate < 0 || $interest_rate > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Interest rate must be between 0 and 100']);
    exit();
}

if ($duration_days < 1 || $duration_days > 3650) {
    http_response_code(400);
    echo json_encode(['error' => 'Duration must be between 1 and 3650 days']);
    exit();
}

if ($tax_rate < 0 || $tax_rate > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Tax rate must be between 0 and 100']);
    exit();
}

// Calculate returns
$gross_return = $amount * ($interest_rate / 100) * ($duration_days / 365);
$tax = $gross_return * ($tax_rate / 100);
$net_return = $gross_return - $tax;
$total_payout = $amount + $net_return;

// Calculate dates
$start_date = date('Y-m-d');
$maturity_date = date('Y-m-d', strtotime("+$duration_days days"));

// Return calculation results
echo json_encode([
    'success' => true,
    'data' => [
        'principal_amount' => $amount,
        'gross_return' => round($gross_return, 2),
        'tax' => round($tax, 2),
        'net_return' => round($net_return, 2),
        'total_payout' => round($total_payout, 2),
        'start_date' => $start_date,
        'maturity_date' => $maturity_date,
        'duration_days' => $duration_days,
        'interest_rate' => $interest_rate,
        'tax_rate' => $tax_rate
    ]
]);
?>
