<?php
/**
 * AquaVault Capital - Installation Script
 * Run this once after uploading files to verify installation
 */

// Prevent direct access in production
if (file_exists('db/connect.php')) {
    require_once 'db/connect.php';
    
    // Check if already installed
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $user_count = $stmt->fetch()['count'];
        
        if ($user_count > 0) {
            die("AquaVault Capital is already installed. Delete this file for security.");
        }
    } catch (PDOException $e) {
        // Database not set up yet, continue with installation
    }
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_POST) {
    $step = (int)$_POST['step'];
    
    switch ($step) {
        case 2:
            // Test database connection
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? '';
            $db_user = $_POST['db_user'] ?? '';
            $db_pass = $_POST['db_pass'] ?? '';
            
            try {
                $test_pdo = new PDO(
                    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                    $db_user,
                    $db_pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Update connect.php
                $connect_content = "<?php
/**
 * AquaVault Capital - Database Connection
 * Secure PDO connection for cPanel hosting
 */

// Database configuration - Update these with your cPanel MySQL details
\$db_host = '$db_host';
\$db_name = '$db_name';
\$db_user = '$db_user';
\$db_pass = '$db_pass';

try {
    // Create PDO connection with error handling
    \$pdo = new PDO(
        \"mysql:host=\$db_host;dbname=\$db_name;charset=utf8mb4\",
        \$db_user,
        \$db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException \$e) {
    // Log error and show user-friendly message
    error_log(\"Database connection failed: \" . \$e->getMessage());
    die(\"Database connection failed. Please contact support.\");
}

// Function to sanitize input
function sanitize_input(\$data) {
    return htmlspecialchars(strip_tags(trim(\$data)), ENT_QUOTES, 'UTF-8');
}

// Function to generate secure random token
function generate_token(\$length = 32) {
    return bin2hex(random_bytes(\$length));
}

// Function to validate email
function validate_email(\$email) {
    return filter_var(\$email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (Nigerian format)
function validate_phone(\$phone) {
    \$phone = preg_replace('/[^0-9]/', '', \$phone);
    return preg_match('/^(070|080|081|090|091|080|081|070|090|091)[0-9]{8}$/', \$phone);
}
?>";
                
                file_put_contents('db/connect.php', $connect_content);
                $success = 'Database connection successful!';
                $step = 3;
                
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // Configure Paystack
            $public_key = $_POST['public_key'] ?? '';
            $secret_key = $_POST['secret_key'] ?? '';
            $domain = $_POST['domain'] ?? '';
            
            if ($public_key && $secret_key && $domain) {
                $paystack_content = "<?php
/**
 * AquaVault Capital - Paystack Configuration
 * Secure payment gateway configuration
 */

// Paystack API Configuration
define('PAYSTACK_PUBLIC_KEY', '$public_key');
define('PAYSTACK_SECRET_KEY', '$secret_key');
define('PAYSTACK_BASE_URL', 'https://api.paystack.co');

// Payment configuration
define('CURRENCY', 'NGN');
define('CALLBACK_URL', 'https://$domain/user/success.php');
define('WEBHOOK_URL', 'https://$domain/api/webhook.php');

/**
 * Initialize Paystack payment
 */
function initialize_payment(\$email, \$amount, \$reference, \$callback_url = null) {
    \$url = PAYSTACK_BASE_URL . \"/transaction/initialize\";
    
    \$fields = [
        'email' => \$email,
        'amount' => \$amount * 100, // Convert to kobo
        'reference' => \$reference,
        'currency' => CURRENCY,
        'callback_url' => \$callback_url ?: CALLBACK_URL
    ];

    \$fields_string = http_build_query(\$fields);

    \$ch = curl_init();
    curl_setopt(\$ch, CURLOPT_URL, \$url);
    curl_setopt(\$ch, CURLOPT_POST, true);
    curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$fields_string);
    curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
        \"Authorization: Bearer \" . PAYSTACK_SECRET_KEY,
        \"Cache-Control: no-cache\",
    ]);
    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
    
    \$result = curl_exec(\$ch);
    curl_close(\$ch);

    return json_decode(\$result, true);
}

/**
 * Verify Paystack payment
 */
function verify_payment(\$reference) {
    \$url = PAYSTACK_BASE_URL . \"/transaction/verify/\" . rawurlencode(\$reference);

    \$ch = curl_init();
    curl_setopt(\$ch, CURLOPT_URL, \$url);
    curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
        \"Authorization: Bearer \" . PAYSTACK_SECRET_KEY,
        \"Cache-Control: no-cache\",
    ]);

    \$result = curl_exec(\$ch);
    curl_close(\$ch);

    return json_decode(\$result, true);
}

/**
 * Generate payment reference
 */
function generate_payment_reference(\$prefix = 'AV') {
    return \$prefix . '_' . time() . '_' . random_int(1000, 9999);
}
?>";
                
                file_put_contents('config/paystack.php', $paystack_content);
                $success = 'Paystack configuration saved!';
                $step = 4;
            } else {
                $error = 'Please fill in all Paystack fields.';
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaVault Capital - Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="gradient-bg w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-white text-2xl font-bold">AV</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">AquaVault Capital</h1>
            <p class="mt-2 text-gray-600">Installation Wizard</p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                            <?php echo $i <= $step ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                            <?php echo $i; ?>
                        </div>
                        <span class="ml-2 text-sm font-medium <?php echo $i <= $step ? 'text-blue-600' : 'text-gray-500'; ?>">
                            <?php 
                            switch($i) {
                                case 1: echo 'Welcome'; break;
                                case 2: echo 'Database'; break;
                                case 3: echo 'Paystack'; break;
                                case 4: echo 'Complete'; break;
                            }
                            ?>
                        </span>
                    </div>
                    <?php if ($i < 4): ?>
                        <div class="w-16 h-1 bg-gray-200 mx-4"></div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <!-- Step Content -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <?php if ($step == 1): ?>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Welcome to AquaVault Capital</h2>
                <div class="space-y-4 text-gray-600">
                    <p>This installation wizard will help you set up your AquaVault Capital investment platform.</p>
                    <p>You'll need:</p>
                    <ul class="list-disc list-inside space-y-2 ml-4">
                        <li>MySQL database credentials</li>
                        <li>Paystack API keys (test or live)</li>
                        <li>Your domain name</li>
                    </ul>
                    <p>Let's get started!</p>
                </div>
                <div class="mt-8">
                    <a href="?step=2" class="gradient-bg text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition duration-200">
                        Start Installation
                    </a>
                </div>

            <?php elseif ($step == 2): ?>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Database Configuration</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="step" value="2">
                    
                    <div>
                        <label for="db_host" class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="db_name" class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                        <input type="text" id="db_name" name="db_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="db_user" class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                        <input type="text" id="db_user" name="db_user" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="gradient-bg text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition duration-200">
                            Test Connection
                        </button>
                        <a href="?step=1" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                            Back
                        </a>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Paystack Configuration</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="step" value="3">
                    
                    <div>
                        <label for="public_key" class="block text-sm font-medium text-gray-700 mb-2">Paystack Public Key</label>
                        <input type="text" id="public_key" name="public_key" required
                               placeholder="pk_test_..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="secret_key" class="block text-sm font-medium text-gray-700 mb-2">Paystack Secret Key</label>
                        <input type="password" id="secret_key" name="secret_key" required
                               placeholder="sk_test_..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Your Domain</label>
                        <input type="text" id="domain" name="domain" required
                               placeholder="yourdomain.com"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="gradient-bg text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition duration-200">
                            Save Configuration
                        </button>
                        <a href="?step=2" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                            Back
                        </a>
                    </div>
                </form>

            <?php elseif ($step == 4): ?>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Installation Complete! 🎉</h2>
                <div class="space-y-4 text-gray-600">
                    <p>Congratulations! AquaVault Capital has been successfully installed.</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-900 mb-2">Next Steps:</h3>
                        <ul class="list-disc list-inside space-y-1 text-blue-800">
                            <li>Import the database schema from <code>db/schema.sql</code></li>
                            <li>Set up SSL certificate in cPanel</li>
                            <li>Configure Paystack webhook URL</li>
                            <li>Delete this installation file for security</li>
                        </ul>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-900 mb-2">Default Admin Account:</h3>
                        <p class="text-yellow-800">
                            Username: <strong>admin</strong><br>
                            Password: <strong>admin123</strong><br>
                            <em>Please change this password immediately!</em>
                        </p>
                    </div>
                </div>
                <div class="mt-8 flex gap-4">
                    <a href="index.php" class="gradient-bg text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition duration-200">
                        Visit Website
                    </a>
                    <a href="admin/login.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                        Admin Login
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>AquaVault Capital Installation Wizard</p>
        </div>
    </div>
</body>
</html>
