<?php
/**
 * AquaVault Capital - Payment Success
 */
session_start();
require_once '../db/connect.php';
require_once '../config/paystack.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];
$success_message = '';
$investment_details = null;

// Check if payment reference exists
if (isset($_GET['reference'])) {
    $reference = sanitize_input($_GET['reference']);
    
    try {
        // Verify payment with Paystack
        $payment_verification = verify_payment($reference);
        
        if ($payment_verification['status'] && $payment_verification['data']['status'] === 'success') {
            // Payment successful - check if investment exists and is active
            $stmt = $pdo->prepare("
                SELECT ui.*, ic.name as category_name, id.name as duration_name
                FROM user_investments ui
                LEFT JOIN investment_categories ic ON ui.category_id = ic.id
                LEFT JOIN investment_durations id ON ui.duration_id = id.id
                WHERE ui.payment_reference = ? AND ui.user_id = ?
            ");
            $stmt->execute([$reference, $user_id]);
            $investment = $stmt->fetch();
            
            if ($investment) {
                if ($investment['status'] === 'active' && $investment['payment_status'] === 'paid') {
                    // Investment is already active (webhook processed it)
                    $investment_details = [
                        'reference' => $investment['reference'],
                        'category_name' => $investment['category_name'],
                        'duration_name' => $investment['duration_name'],
                        'amount' => $investment['amount'],
                        'maturity_date' => $investment['maturity_date'],
                        'expected_return' => $investment['net_return'],
                        'total_payout' => $investment['amount'] + $investment['net_return']
                    ];
                    
                    $success_message = 'Investment activated successfully!';
                    
                    // Clean up session data after successful payment
                    unset($_SESSION['investment_data']);
                    unset($_SESSION['selected_category_id']);
                    unset($_SESSION['payment_reference']);
                } else {
                    // Investment exists but not yet activated (webhook might be delayed)
                    $success_message = 'Payment successful! Your investment is being processed and will be activated shortly.';
                }
            } else {
                // Investment not found - this shouldn't happen
                $success_message = 'Payment successful, but investment record not found. Please contact support.';
            }
        } else {
            $success_message = 'Payment verification failed.';
        }
    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        $success_message = 'Payment verification error. Please contact support.';
    }
} else {
    $success_message = 'No payment reference found.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - AquaVault Capital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1000;
        }
        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #f39c12;
            animation: confetti-fall 3s linear infinite;
        }
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Success Animation -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🎉 Payment Successful!</h1>
            <p class="text-gray-600">Your investment has been activated successfully</p>
        </div>

        <?php if ($investment_details): ?>
            <!-- Investment Details -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Investment Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-600">Investment Reference</span>
                            <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($investment_details['reference']); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-600">Investment Category</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($investment_details['category_name']); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($investment_details['duration_name']); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-600">Investment Amount</span>
                            <span class="font-semibold text-blue-600"><?php echo format_currency($investment_details['amount']); ?></span>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-600">Maturity Date</span>
                            <span class="font-semibold text-green-600"><?php echo date('M d, Y', strtotime($investment_details['maturity_date'])); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center py-3 border-b border-gray-200">
                            <span class="text-gray-600">Expected Returns</span>
                            <span class="font-semibold text-green-600"><?php echo format_currency($investment_details['expected_return']); ?></span>
                        </div>
                        
                        <div class="flex justify-between items-center py-4 bg-blue-50 rounded-lg px-4">
                            <span class="text-gray-900 font-semibold">Total Payout</span>
                            <span class="font-bold text-blue-600 text-lg"><?php echo format_currency($investment_details['total_payout']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">📋 What's Next?</h3>
                <div class="space-y-3 text-sm text-blue-800">
                    <p>• Your investment is now active and earning returns</p>
                    <p>• You can track your investment progress in your dashboard</p>
                    <p>• Funds will be automatically released on the maturity date</p>
                    <p>• You'll receive email notifications about your investment status</p>
                    <p>• Contact support if you have any questions</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-red-900 mb-2">⚠️ Payment Issue</h3>
                <p class="text-red-800"><?php echo htmlspecialchars($success_message); ?></p>
                <p class="text-red-700 text-sm mt-2">Please contact our support team if you need assistance.</p>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="dashboard.php" 
               class="gradient-bg text-white px-8 py-3 rounded-lg font-medium hover:opacity-90 transition duration-200 text-center">
                📊 View Dashboard
            </a>
            <a href="invest.php" 
               class="px-8 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200 text-center">
                💰 Make Another Investment
            </a>
        </div>

        <!-- Support Information -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 text-sm">
                Need help? Contact us at 
                <a href="mailto:support@aquavault.com" class="text-blue-600 hover:text-blue-800">support@aquavault.com</a>
                or call +234-XXX-XXXX
            </p>
        </div>
    </div>

    <!-- Confetti Animation -->
    <div id="confetti-container" class="confetti"></div>

    <script>
        // Create confetti animation
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            const colors = ['#f39c12', '#e74c3c', '#3498db', '#2ecc71', '#9b59b6'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti-piece';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                container.appendChild(confetti);
            }
            
            // Remove confetti after animation
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        // Start confetti animation
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
        });
    </script>
</body>
</html>
