<?php
/**
 * Minimal Sidebar Navigation for Testing
 */
if (!isset($_SESSION)) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<!-- Minimal Sidebar -->
<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="brand-icon">
                <span>AV</span>
            </div>
            <span class="brand-text">AquaVault</span>
        </a>
        <button id="sidebar-close" class="sidebar-close">×</button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="invest.php" class="nav-link">Invest</a>
            </li>
            <li class="nav-item">
                <a href="withdraw.php" class="nav-link">Withdraw</a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link">Profile</a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Top Header -->
<header class="top-header">
    <div class="header-content">
        <button id="sidebar-toggle" class="sidebar-toggle">☰</button>
        <div class="header-title">
            <h1><?php echo ucfirst(str_replace('.php', '', $current_page)); ?></h1>
        </div>
    </div>
</header>

<!-- Main Content Wrapper -->
<div class="main-content-wrapper">
    <main class="main-content">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('active');
        
        if (sidebar.classList.contains('open')) {
            sidebar.style.left = '0px';
            sidebar.style.transform = 'none';
            sidebar.style.display = 'flex';
            sidebar.style.visibility = 'visible';
            sidebar.style.zIndex = '1001';
        } else {
            sidebar.style.left = '-280px';
            sidebar.style.transform = 'none';
        }
        
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    }
    
    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        sidebar.style.left = '-280px';
        sidebar.style.transform = 'none';
        document.body.style.overflow = '';
    }
    
    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
});
</script>
