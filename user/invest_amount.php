<?php
/**
 * AquaVault Capital - Investment Amount Selection
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];

// Get category_id from POST (when coming from invest.php or invest_details.php) or from session (when form is resubmitted)
$category_id = (int)($_POST['category_id'] ?? $_SESSION['selected_category_id'] ?? 0);

if (!$category_id) {
    header('Location: invest.php');
    exit();
}

// Store category_id in session for form resubmissions
$_SESSION['selected_category_id'] = $category_id;

// Check KYC status
require_kyc_approved($user_id, $pdo);

// Get category details
try {
    $stmt = $pdo->prepare("
        SELECT name as category_name, min_amount, max_amount, description as category_description
        FROM investment_categories 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        header('Location: invest.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Category fetch error: " . $e->getMessage());
    header('Location: invest.php');
    exit();
}

// Get available durations for this category
try {
    $stmt = $pdo->prepare("
        SELECT id, name, days, interest_rate, tax_rate
        FROM investment_durations 
        WHERE category_id = ? AND is_active = 1
        ORDER BY days ASC
    ");
    $stmt->execute([$category_id]);
    $durations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Durations fetch error: " . $e->getMessage());
    $durations = [];
}

$error = '';

// Process amount and duration selection
if ($_POST && isset($_POST['amount']) && isset($_POST['duration_id'])) {
    $amount = (float)($_POST['amount'] ?? 0);
    $duration_id = (int)($_POST['duration_id'] ?? 0);

    // Find the selected duration
    $selected_duration = null;
    foreach ($durations as $duration) {
        if ($duration['id'] == $duration_id) {
            $selected_duration = $duration;
            break;
        }
    }

    if (!$selected_duration) {
        $error = 'Please select a valid investment duration.';
    } elseif ($amount < $category['min_amount']) {
        $error = 'Amount must be at least ₦' . number_format($category['min_amount']);
    } elseif ($category['max_amount'] && $amount > $category['max_amount']) {
        $error = 'Amount cannot exceed ₦' . number_format($category['max_amount']);
    } else {
        // Store investment details in session and redirect to review
        $_SESSION['investment_data'] = [
            'category_id' => $category_id,
            'duration_id' => $duration_id,
            'amount' => $amount,
            'category_name' => $category['category_name'],
            'duration_name' => $selected_duration['name'],
            'duration_days' => $selected_duration['days'],
            'interest_rate' => $selected_duration['interest_rate'],
            'tax_rate' => $selected_duration['tax_rate']
        ];
        
        header('Location: invest_review.php');
        exit();
    }
}
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
        .duration-card {
            transition: all 0.2s ease;
        }
        .duration-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .duration-card.selected {
            border-color: #007BFF;
            background-color: #f0f8ff;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
   <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">1</div>
                    <span class="ml-2 text-sm font-medium text-blue-600">Amount</span>
                </div>
                <div class="w-16 h-1 bg-gray-200 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm font-medium">2</div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Review</span>
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
            <h1 class="text-3xl font-bold text-gray-900">Investment Amount</h1>
            <p class="mt-2 text-gray-600">How much would you like to invest in <?php echo htmlspecialchars($category['category_name']); ?>?</p>
            <p class="text-sm text-gray-500">Enter your amount and select your investment duration</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Amount Input -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Enter Investment Amount</h2>

                <?php if ($error): ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Investment Amount (₦)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₦</span>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   required
                                   min="<?php echo $category['min_amount']; ?>"
                                   <?php echo $category['max_amount'] ? 'max="' . $category['max_amount'] . '"' : ''; ?>
                                   step="1000"
                                   value="<?php echo htmlspecialchars($_POST['amount'] ?? $category['min_amount']); ?>"
                                   class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                                   oninput="calculateReturns()">
                        </div>
                        <div class="mt-2 flex justify-between text-sm text-gray-500">
                            <span>Min: ₦<?php echo number_format($category['min_amount']); ?></span>
                            <?php if ($category['max_amount']): ?>
                                <span>Max: ₦<?php echo number_format($category['max_amount']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Duration Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Investment Duration</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($durations as $duration): ?>
                                <div class="relative">
                                    <input type="radio" 
                                           id="duration_<?php echo $duration['id']; ?>" 
                                           name="duration_id" 
                                           value="<?php echo $duration['id']; ?>"
                                           data-days="<?php echo $duration['days']; ?>"
                                           data-rate="<?php echo $duration['interest_rate']; ?>"
                                           data-tax="<?php echo $duration['tax_rate']; ?>"
                                           data-name="<?php echo htmlspecialchars($duration['name']); ?>"
                                           class="hidden duration-input"
                                           onchange="updateDurationDetails()">
                                    <label for="duration_<?php echo $duration['id']; ?>" 
                                           class="duration-card block bg-white rounded-lg p-4 border-2 border-gray-300 cursor-pointer text-center hover:border-blue-500 transition-all duration-200">
                                        <div class="text-lg font-semibold text-gray-900">
                                            <?php echo $duration['days']; ?> days
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <?php echo number_format($duration['interest_rate'], 1); ?>% p.a.
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Quick Select</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            $quick_amounts = [
                                $category['min_amount'],
                                $category['min_amount'] * 2,
                                $category['min_amount'] * 5,
                                $category['min_amount'] * 10
                            ];
                            
                            foreach ($quick_amounts as $quick_amount):
                                if (!$category['max_amount'] || $quick_amount <= $category['max_amount']):
                            ?>
                                <button type="button" 
                                        onclick="setAmount(<?php echo $quick_amount; ?>)"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                                    ₦<?php echo number_format($quick_amount); ?>
                                </button>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full gradient-bg text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition duration-200">
                        Continue to Review
                    </button>
                </form>
            </div>

            <!-- Plan Details & Calculator -->
            <div class="space-y-6">
                <!-- Investment Summary -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Investment Details</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Category</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($category['category_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-semibold" id="selected-duration">Select duration</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Interest Rate</span>
                            <span class="font-semibold text-green-600" id="selected-rate">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax Rate</span>
                            <span class="font-semibold" id="selected-tax">-</span>
                        </div>
                    </div>
                </div>

                <!-- Live Calculator -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Investment Calculator</h3>
                    <div id="calculator-results" class="space-y-3">
                        <!-- Results will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Maturity Date -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">📅 Maturity Information</h4>
                    <p class="text-sm text-blue-800">
                        Your investment will mature on <span id="maturity-date" class="font-semibold"></span>
                        (<span id="duration-days">0</span> days from today)
                    </p>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="mt-8 text-center">
            <div class="flex items-center justify-center gap-2 text-sm text-gray-600 mb-4">
                <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a>
                <span>›</span>
                <a href="invest.php" class="text-blue-600 hover:underline">Investment Plans</a>
                <span>›</span>
                <a href="invest_details.php" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($category['category_name']); ?></a>
                <span>›</span>
                <span class="text-gray-900">Investment Amount</span>
            </div>
            <a href="invest_details.php" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200">
                ← Back to Plan Details
            </a>
        </div>
    </div>

    <script>
        // Investment data for calculations
        let investmentData = {
            interestRate: 0,
            taxRate: 0,
            durationDays: 0
        };

        function setAmount(amount) {
            document.getElementById('amount').value = amount;
            calculateReturns();
        }

        function updateDurationDetails() {
            const selectedDuration = document.querySelector('input[name="duration_id"]:checked');
            
            if (selectedDuration) {
                investmentData.interestRate = parseFloat(selectedDuration.dataset.rate);
                investmentData.taxRate = parseFloat(selectedDuration.dataset.tax);
                investmentData.durationDays = parseInt(selectedDuration.dataset.days);
                
                // Update visual selection
                document.querySelectorAll('.duration-card').forEach(card => {
                    card.classList.remove('selected');
                });
                selectedDuration.nextElementSibling.classList.add('selected');
                
                // Update display
                document.getElementById('selected-duration').textContent = selectedDuration.dataset.name;
                document.getElementById('selected-rate').textContent = investmentData.interestRate.toFixed(1) + '% p.a.';
                document.getElementById('selected-tax').textContent = investmentData.taxRate.toFixed(1) + '%';
                document.getElementById('duration-days').textContent = investmentData.durationDays;
                
                // Recalculate if amount is entered
                calculateReturns();
                updateMaturityDate();
            } else {
                // Reset display
                document.getElementById('selected-duration').textContent = 'Select duration';
                document.getElementById('selected-rate').textContent = '-';
                document.getElementById('selected-tax').textContent = '-';
                document.getElementById('calculator-results').innerHTML = `
                    <p class="text-gray-500 text-center">Select duration and enter amount to see calculations</p>
                `;
            }
        }

        function calculateReturns() {
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const selectedDuration = document.querySelector('input[name="duration_id"]:checked');
            
            // Get current duration data if available
            let currentInterestRate = investmentData.interestRate;
            let currentTaxRate = investmentData.taxRate;
            let currentDurationDays = investmentData.durationDays;
            
            if (selectedDuration) {
                currentInterestRate = parseFloat(selectedDuration.dataset.rate);
                currentTaxRate = parseFloat(selectedDuration.dataset.tax);
                currentDurationDays = parseInt(selectedDuration.dataset.days);
            }
            
            if (amount > 0 && selectedDuration && currentInterestRate > 0) {
                // Calculate returns
                const grossReturn = amount * (currentInterestRate / 100) * (currentDurationDays / 365);
                const tax = grossReturn * (currentTaxRate / 100);
                const netReturn = grossReturn - tax;
                const totalPayout = amount + netReturn;

                // Update calculator display
                document.getElementById('calculator-results').innerHTML = `
                    <div class="flex justify-between">
                        <span class="text-gray-600">Principal Amount</span>
                        <span class="font-semibold">₦${amount.toLocaleString()}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Gross Returns</span>
                        <span class="font-semibold text-green-600">₦${grossReturn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (${currentTaxRate}%)</span>
                        <span class="font-semibold text-red-600">-₦${tax.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Net Returns</span>
                        <span class="font-semibold text-green-600">₦${netReturn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-3 mt-3">
                        <span class="text-gray-900 font-semibold">Total Payout</span>
                        <span class="font-bold text-blue-600 text-lg">₦${totalPayout.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                `;
            } else if (amount > 0 && !selectedDuration) {
                document.getElementById('calculator-results').innerHTML = `
                    <div class="text-center p-4">
                        <p class="text-gray-500 mb-2">Amount: ₦${amount.toLocaleString()}</p>
                        <p class="text-blue-600 font-medium">Now select a duration to see your returns</p>
                    </div>
                `;
            } else if (selectedDuration && amount === 0) {
                document.getElementById('calculator-results').innerHTML = `
                    <div class="text-center p-4">
                        <p class="text-gray-500 mb-2">Duration: ${selectedDuration.dataset.name}</p>
                        <p class="text-blue-600 font-medium">Now enter an amount to see your returns</p>
                    </div>
                `;
            } else {
                document.getElementById('calculator-results').innerHTML = `
                    <p class="text-gray-500 text-center">Enter an amount and select duration to see calculations</p>
                `;
            }
        }

        function updateMaturityDate() {
            if (investmentData.durationDays > 0) {
                const today = new Date();
                const maturityDate = new Date(today.getTime() + (investmentData.durationDays * 24 * 60 * 60 * 1000));
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('maturity-date').textContent = maturityDate.toLocaleDateString('en-US', options);
            }
        }

        // Initialize calculator on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateReturns();
        });

        // Format number input
        document.getElementById('amount').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            if (value) {
                e.target.value = parseInt(value);
            }
        });
    </script>
</body>
</html>