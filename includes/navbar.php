<?php
/**
 * AquaVault Capital - MOBILE-ONLY Sidebar Navigation Component
 * Forces mobile drawer behavior on ALL devices.
 * Sidebar starts CLOSED by default.
 */
if (!isset($_SESSION)) {
    session_start();
}
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
/* FORCE MOBILE BEHAVIOR ON ALL SCREEN SIZES */
/* Completely hide sidebar by default */
.sidebar {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    left: -100vw !important;
    transform: translateX(-100vw) !important;
    z-index: -1 !important;
    width: 0 !important;
    overflow: hidden !important;
    pointer-events: none !important;
    position: fixed !important;
    top: 0 !important;
    height: 100vh !important;
    background: white !important;
    box-shadow: 5px 0 20px rgba(0, 0, 0, 0.3) !important;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
}

/* Only show sidebar when explicitly opened */
.sidebar.open {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    left: 0 !important;
    transform: translateX(0) !important;
    z-index: 9999 !important;
    width: 85vw !important;
    max-width: 300px !important;
    pointer-events: auto !important;
}

/* Overlay for ALL screen sizes */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

/* Hide any potentially conflicting navigation elements */
nav:not(.sidebar-nav),
.navbar:not(.sidebar),
.navigation:not(.sidebar-nav),
.nav-bar:not(.sidebar),
.menu:not(.sidebar),
.top-nav:not(.top-header),
.header-nav:not(.top-header),
.main-nav:not(.sidebar-nav),
.fixed-top:not(.top-header),
.sticky-top:not(.top-header),
aside:not(.sidebar),
.drawer:not(.sidebar),
.offcanvas:not(.sidebar),
[class*="nav-"]:not([class*="sidebar"]):not(.nav-link):not(.nav-text):not(.nav-icon):not(.nav-item):not(.nav-list),
.navbar-nav,
.nav-tabs,
.nav-pills,
.breadcrumb,
.dropdown-menu,
.collapse:not(.sidebar *),
.navbar-collapse {
    display: none !important;
    visibility: hidden !important;
    z-index: -1 !important;
    left: -9999px !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    pointer-events: none !important;
}

/* Ensure main content takes full width always */
.main-content-wrapper {
    margin-left: 0 !important;
    width: 100vw !important;
    padding-left: 0 !important;
}

/* Ensure top header takes full width always */
.top-header {
    left: 0 !important;
    width: 100vw !important;
    z-index: 9997 !important;
    position: fixed !important;
}

/* Prevent body scroll when sidebar is open */
body.sidebar-open {
    overflow: hidden !important;
    position: fixed !important;
    width: 100% !important;
    top: 0 !important;
    left: 0 !important;
}
</style>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<!-- Modern Sidebar (Mobile Drawer Style) -->
<aside id="sidebar" class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="brand-icon">
                <span>AV</span>
            </div>
            <span class="brand-text">AquaVault</span>
        </a>
        <button id="sidebar-close" class="sidebar-close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="invest.php" class="nav-link <?php echo $current_page === 'invest.php' ? 'active' : ''; ?>" data-tooltip="Invest">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span class="nav-text">Invest</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="withdraw.php" class="nav-link <?php echo $current_page === 'withdraw.php' ? 'active' : ''; ?>" data-tooltip="Withdraw">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    <span class="nav-text">Withdraw</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="withdrawal_requests.php" class="nav-link <?php echo $current_page === 'withdrawal_requests.php' ? 'active' : ''; ?>" data-tooltip="Requests">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10,9 9,9 8,9"></polyline>
                    </svg>
                    <span class="nav-text">Requests</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="bank_accounts.php" class="nav-link <?php echo $current_page === 'bank_accounts.php' ? 'active' : ''; ?>" data-tooltip="Bank Accounts">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span class="nav-text">Bank Accounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" data-tooltip="Profile">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="nav-text">Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="kyc.php" class="nav-link <?php echo $current_page === 'kyc.php' ? 'active' : ''; ?>" data-tooltip="KYC">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"></path>
                        <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                        <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                        <path d="M12 3c0 1-1 3-3 3s-3-2-3-3 1-3 3-3 3 2 3 3"></path>
                        <path d="M12 21c0-1 1-3 3-3s3 2 3 3-1 3-3 3-3-2-3-3"></path>
                    </svg>
                    <span class="nav-text">KYC</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- User Section -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (isset($_SESSION['user_avatar']) && !empty($_SESSION['user_avatar'])): ?>
                        <img src="../assets/uploads/avatars/<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" 
                             alt="Avatar" class="avatar-img">
                    <?php else: ?>
                        <span class="avatar-text">
                            <?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U'; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?></div>
                    <div class="user-email"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16,17 21,12 16,7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    <?php else: ?>
        <div class="sidebar-auth">
            <a href="login.php" class="auth-link">Login</a>
            <a href="register.php" class="auth-btn">Register</a>
        </div>
    <?php endif; ?>
</aside>

<!-- Top Header Bar -->
<header class="top-header">
    <div class="header-content">
        <button id="sidebar-toggle" class="sidebar-toggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="header-title">
            <h1><?php echo ucfirst(str_replace('.php', '', $current_page)); ?></h1>
        </div>
        <div class="header-actions">
            <!-- Optional: Add notification or other actions here -->
        </div>
    </div>
</header>

<!-- Main Content Wrapper -->
<div class="main-content-wrapper">
    <main class="main-content">
<script>
// MOBILE-ONLY Sidebar Navigation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    // Check if elements exist
    if (!sidebar || !sidebarToggle || !sidebarOverlay) {
        console.error('Required sidebar elements not found');
        return;
    }

    // Initialize sidebar to be CLOSED
    function initializeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
        document.body.classList.remove('sidebar-open');
        // Clear any inline styles
        sidebar.style.cssText = '';
    }

    // Open sidebar
    function openSidebar() {
        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        document.body.classList.add('sidebar-open');
    }

    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
        document.body.classList.remove('sidebar-open');
    }

    // Toggle sidebar (always toggles open/close)
    function toggleSidebar(e) {
        if (e) e.preventDefault();
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });

    // Navigation link handling - close sidebar after click
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            setTimeout(closeSidebar, 100);
        });
    });

    // Touch/swipe support
    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartY = 0;
    let touchEndY = 0;

    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        const swipeThreshold = 50;
        const swipeDistance = touchEndX - touchStartX;
        const verticalDistance = Math.abs(touchEndY - touchStartY);

        // Only handle horizontal swipes
        if (verticalDistance > swipeThreshold) {
            return;
        }

        // Swipe right to open sidebar (from left edge only)
        if (swipeDistance > swipeThreshold && touchStartX < 50 && !sidebar.classList.contains('open')) {
            openSidebar();
        }

        // Swipe left to close sidebar
        if (swipeDistance < -swipeThreshold && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    }

    // Initialize
    initializeSidebar();

    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        document.body.style.overflow = '';
        document.body.classList.remove('sidebar-open');
    });
});
</script>