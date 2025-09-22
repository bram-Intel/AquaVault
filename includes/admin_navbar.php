<?php
/**
 * AquaVault Capital - Admin Navigation Bar Component
 * Shared navigation for admin pages
 */
if (!isset($_SESSION)) {
    session_start();
}
?>

<nav class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="dashboard.php" class="flex items-center">
                    <div class="gradient-bg w-8 h-8 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">AV</span>
                    </div>
                    <span class="ml-2 text-xl font-bold text-gray-900">AquaVault Admin</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Dashboard</a>
                <a href="manage_investments.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Investments</a>
                <a href="manage_categories.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Categories</a>
                <a href="manage_durations.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Durations</a>
                <a href="withdrawal_requests.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Withdrawals</a>
                <a href="users.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Users</a>
                <a href="kyc_approvals.php" class="text-gray-700 hover:text-blue-600 transition duration-200">KYC</a>
            </div>

            <!-- Admin Menu -->
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <div class="flex items-center space-x-3">
                        <!-- Admin Avatar -->
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <span class="text-red-600 text-sm font-medium">A</span>
                        </div>
                        
                        <!-- Admin Name -->
                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        
                        <!-- Logout -->
                        <a href="logout.php" class="text-red-600 hover:text-red-800 transition duration-200">
                            Logout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="flex items-center space-x-3">
                        <a href="login.php" class="text-gray-700 hover:text-blue-600 transition duration-200">Login</a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="md:hidden p-2 rounded-md text-gray-700 hover:text-blue-600 hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden border-t border-gray-200 py-4">
            <div class="flex flex-col space-y-3">
                <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">Dashboard</a>
                <a href="manage_investments.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">Investments</a>
                <a href="manage_categories.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">Categories</a>
                <a href="manage_durations.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">Durations</a>
                <a href="withdrawal_requests.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">Withdrawals</a>
                <a href="users.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">Users</a>
                <a href="kyc_approvals.php" class="text-gray-700 hover:text-blue-600 transition duration-200 px-3 py-2">KYC</a>
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <a href="logout.php" class="text-red-600 hover:text-red-800 transition duration-200 px-3 py-2">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-button').addEventListener('click', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
});
</script>
