<?php
/**
 * AquaVault Capital - Investment Review & Payment
 */
session_start();
require_once '../db/connect.php';
require_once '../config/paystack.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];

// Check if investment data exists in session
if (!isset($_SESSION['investment_data'])) {
    error_log("No investment data in session for user $user_id");
    header('Location: invest.php');
    exit();
}

$investment_data = $_SESSION['investment_data'];

// Validate required investment data fields
$required_fields = ['category_id', 'duration_id', 'amount', 'category_name', 'duration_name', 'duration_days', 'interest_rate', 'tax_rate'];
foreach ($required_fields as $field) {
    if (!isset($investment_data[$field]) || empty($investment_data[$field])) {
        error_log("Missing required investment data field: $field for user $user_id");
        header('Location: invest.php');
        exit();
    }
}

// Debug log investment data
error_log("Investment data for user $user_id: " . json_encode($investment_data));

// Get user details for payment
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("User fetch error: " . $e->getMessage());
    header('Location: invest.php');
    exit();
}

// Calculate returns
try {
    $returns = calculate_returns(
        $investment_data['amount'],
        $investment_data['interest_rate'],
        $investment_data['duration_days'],
        $investment_data['tax_rate']
    );
} catch (Exception $e) {
    error_log("Returns calculation error: " . $e->getMessage());
    header('Location: invest.php');
    exit();
}

$error = '';
$success = '';

// Process payment
if ($_POST && isset($_POST['proceed_payment'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            // Generate payment reference
            $payment_reference = generate_payment_reference();
            
            // Calculate dates
            $start_date = date('Y-m-d');
            $maturity_date = date('Y-m-d', strtotime('+' . $investment_data['duration_days'] . ' days'));
            
            // Create pending investment record
            $stmt = $pdo->prepare("
                INSERT INTO user_investments 
                (user_id, plan_id, category_id, duration_id, reference, amount, interest_rate, tax_rate, 
                 expected_return, net_return, start_date, maturity_date, 
                 status, payment_status, payment_reference, payment_method) 
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, 'paystack')
            ");
            
            $stmt->execute([
                $user_id,
                $investment_data['category_id'],
                $investment_data['duration_id'],
                $payment_reference,
                $investment_data['amount'],
                $investment_data['interest_rate'],
                $investment_data['tax_rate'],
                $returns['gross_return'],
                $returns['net_return'],
                $start_date,
                $maturity_date,
                $payment_reference
            ]);
            
            // Create pending transaction record
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (user_id, reference, type, amount, description, status, payment_method, payment_reference) 
                VALUES (?, ?, 'investment', ?, ?, 'pending', 'paystack', ?)
            ");
            
            $stmt->execute([
                $user_id,
                $payment_reference,
                $investment_data['amount'],
                'Investment in ' . $investment_data['category_name'] . ' - ' . $investment_data['duration_name'],
                $payment_reference
            ]);
            
            // Initialize Paystack payment
            $payment_response = initialize_payment(
                $user['email'],
                $investment_data['amount'],
                $payment_reference,
                'https://' . $_SERVER['HTTP_HOST'] . '/user/success.php'
            );
            
            if ($payment_response['status']) {
                // Store payment reference in session
                $_SESSION['payment_reference'] = $payment_reference;
                
                // Redirect to Paystack
                header('Location: ' . $payment_response['data']['authorization_url']);
                exit();
            } else {
                $error_message = isset($payment_response['message']) ? $payment_response['message'] : 'Unknown error';
                $error = 'Payment initialization failed: ' . $error_message . '. Please try again.';
                error_log("Payment initialization failed: " . json_encode($payment_response));
            }
        } catch (Exception $e) {
            error_log("Payment error: " . $e->getMessage());
            $error = 'Payment processing error: ' . $e->getMessage() . '. Please try again.';
        }
    }
}

// Calculate maturity date
$maturity_date = date('Y-m-d', strtotime('+' . $investment_data['duration_days'] . ' days'));
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
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-medium">✓</div>
                    <span class="ml-2 text-sm font-medium text-green-600">Amount</span>
                </div>
                <div class="w-16 h-1 bg-green-500 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <span class="ml-2 text-sm font-medium text-blue-600">Review</span>
                </div>
                <div class="w-16 h-1 bg-gray-200 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm font-medium">3</div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Payment</span>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Review Your Investment</h1>
            <p class="mt-2 text-gray-600">Please review your investment details before proceeding to payment</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Investment Summary -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Investment Summary</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Investment Category</span>
                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($investment_data['category_name']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Duration</span>
                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($investment_data['duration_name']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Investment Amount</span>
                        <span class="font-semibold text-blue-600"><?php echo format_currency($investment_data['amount']); ?></span>
                    </div>
                    
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Interest Rate</span>
                        <span class="font-semibold text-green-600"><?php echo number_format($investment_data['interest_rate'], 1); ?>% p.a.</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Start Date</span>
                        <span class="font-semibold"><?php echo date('M d, Y'); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Maturity Date</span>
                        <span class="font-semibold text-blue-600"><?php echo date('M d, Y', strtotime($maturity_date)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Returns Calculation -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Returns Calculation</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Principal Amount</span>
                        <span class="font-semibold"><?php echo format_currency($investment_data['amount']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Gross Returns</span>
                        <span class="font-semibold text-green-600"><?php echo format_currency($returns['gross_return']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Tax (<?php echo number_format($investment_data['tax_rate'], 1); ?>%)</span>
                        <span class="font-semibold text-red-600">-<?php echo format_currency($returns['tax']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Net Returns</span>
                        <span class="font-semibold text-green-600"><?php echo format_currency($returns['net_return']); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center py-4 bg-blue-50 rounded-lg px-4">
                        <span class="text-gray-900 font-semibold text-lg">Total Payout</span>
                        <span class="font-bold text-blue-600 text-xl"><?php echo format_currency($returns['total_payout']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Terms and Conditions</h3>
            <div class="text-sm text-gray-600 space-y-2">
                <p>• Your investment will be locked for <?php echo $investment_data['duration_days']; ?> days from the date of payment.</p>
                <p>• Early withdrawal is not permitted. Funds will be automatically released on the maturity date.</p>
                <p>• Returns are calculated based on the annual interest rate of <?php echo number_format($investment_data['interest_rate'], 1); ?>%.</p>
                <p>• Tax of <?php echo number_format($investment_data['tax_rate'], 1); ?>% will be deducted from your returns.</p>
                <p>• Payment is processed securely via Paystack.</p>
                <p>• By proceeding, you agree to our Terms of Service and Privacy Policy.</p>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Ready to Invest?</h3>
                        <p class="text-gray-600">Click below to proceed with secure payment</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600"><?php echo format_currency($investment_data['amount']); ?></div>
                        <div class="text-sm text-gray-500">Total Investment</div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="button" 
                            onclick="window.history.back()"
                            class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                        ← Back to Amount
                    </button>
                    <button type="submit" 
                            name="proceed_payment"
                            class="flex-1 gradient-bg text-white py-3 px-6 rounded-lg font-medium hover:opacity-90 transition duration-200">
                        🔒 Proceed to Payment
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">
                        <strong>Secure Payment:</strong> Your payment is processed securely via Paystack with bank-level encryption.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
