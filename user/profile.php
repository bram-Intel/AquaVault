<?php
/**
 * AquaVault Capital - User Profile
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

// Get user information
try {
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, avatar, 
               kyc_status, wallet_balance, total_invested, total_returns, created_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    $error = 'Unable to load profile information.';
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
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
   <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
            <p class="mt-2 text-gray-600">Manage your account information and settings</p>
        </div>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <!-- Avatar -->
                    <div class="mb-4">
                        <?php if ($user['avatar'] && file_exists('../assets/uploads/avatars/' . $user['avatar'])): ?>
                            <img src="../assets/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="Profile Picture" 
                                 class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-blue-100">
                        <?php else: ?>
                            <div class="w-24 h-24 rounded-full mx-auto gradient-bg flex items-center justify-center text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>

                    <!-- KYC Status -->
                    <div class="mt-4">
                        <?php
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'approved' => 'bg-green-100 text-green-800 border-green-200',
                            'rejected' => 'bg-red-100 text-red-800 border-red-200'
                        ];
                        $status_text = [
                            'pending' => 'KYC Pending',
                            'approved' => 'KYC Verified',
                            'rejected' => 'KYC Rejected'
                        ];
                        ?>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium border <?php echo $status_colors[$user['kyc_status']]; ?>">
                            <?php echo $status_text[$user['kyc_status']]; ?>
                        </span>
                    </div>

                    <!-- Member Since -->
                    <div class="mt-4 text-sm text-gray-500">
                        Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </div>

                    <!-- Upload Avatar Button -->
                    <div class="mt-6">
                        <button onclick="document.getElementById('avatar-upload').click()" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            Change Avatar
                        </button>
                        <form id="avatar-form" action="upload_avatar.php" method="POST" enctype="multipart/form-data" class="hidden">
                            <input type="file" id="avatar-upload" name="avatar" accept="image/*" onchange="uploadAvatar()">
                        </form>
                    </div>
                </div>

                <!-- Account Summary -->
                <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Wallet Balance</span>
                            <span class="font-semibold text-green-600">₦<?php echo number_format($user['wallet_balance'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Invested</span>
                            <span class="font-semibold text-blue-600">₦<?php echo number_format($user['total_invested'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Returns</span>
                            <span class="font-semibold text-purple-600">₦<?php echo number_format($user['total_returns'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Personal Information</h3>

                    <form action="update_profile.php" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text" id="first_name" name="first_name" required
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?php echo htmlspecialchars($user['phone']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <button type="submit" 
                                class="w-full gradient-bg text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition duration-200">
                            Update Profile
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h3>

                    <form action="change_password.php" method="POST" class="space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <button type="submit" 
                                class="w-full bg-red-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-red-700 transition duration-200">
                            Change Password
                        </button>
                    </form>
                </div>

                <!-- KYC Section -->
                <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">KYC Verification</h3>
                    
                    <?php if ($user['kyc_status'] === 'approved'): ?>
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <span class="text-green-600 text-xl mr-3">✅</span>
                                <div>
                                    <p class="text-green-800 font-medium">Your account is verified!</p>
                                    <p class="text-green-700 text-sm">You can now make investments without restrictions.</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($user['kyc_status'] === 'pending'): ?>
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <span class="text-yellow-600 text-xl mr-3">⏳</span>
                                <div>
                                    <p class="text-yellow-800 font-medium">KYC document under review</p>
                                    <p class="text-yellow-700 text-sm">Your document is being reviewed. This usually takes 24-48 hours.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
                            <div class="flex items-center">
                                <span class="text-red-600 text-xl mr-3">❌</span>
                                <div>
                                    <p class="text-red-800 font-medium">KYC verification required</p>
                                    <p class="text-red-700 text-sm">Please upload your identification document to start investing.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="kyc.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            <?php echo $user['kyc_status'] === 'approved' ? 'View KYC Status' : 'Upload KYC Document'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="mt-8 text-center">
            <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            e.target.value = value;
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = e.target.value;
            
            if (newPassword !== confirmPassword) {
                e.target.setCustomValidity('Passwords do not match');
            } else {
                e.target.setCustomValidity('');
            }
        });

        // Avatar upload function
        function uploadAvatar() {
            const form = document.getElementById('avatar-form');
            const fileInput = document.getElementById('avatar-upload');
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file.');
                    return;
                }
                
                // Validate file size (2MB limit)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB.');
                    return;
                }
                
                // Submit form
                form.submit();
            }
        }
    </script>
</body>
</html>