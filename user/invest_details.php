<?php
/**
 * AquaVault Capital - Investment Plan Details
 * Detailed view of investment category with all available durations
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

$user_id = $_SESSION['user_id'];

// Get category_id from POST (when coming from invest.php) or from session
$category_id = (int)($_POST['category_id'] ?? $_SESSION['selected_category_id'] ?? 0);

if (!$category_id) {
    header('Location: invest.php');
    exit();
}

// Store category_id in session
$_SESSION['selected_category_id'] = $category_id;

// Check KYC status
require_kyc_approved($user_id, $pdo);

// Get category details
try {
    $stmt = $pdo->prepare("
        SELECT * FROM investment_categories 
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
        SELECT * FROM investment_durations 
        WHERE category_id = ? AND is_active = 1
        ORDER BY days ASC
    ");
    $stmt->execute([$category_id]);
    $durations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Durations fetch error: " . $e->getMessage());
    $durations = [];
}

// Calculate average returns for display
$avg_interest_rate = 0;
if (!empty($durations)) {
    $total_rate = array_sum(array_column($durations, 'interest_rate'));
    $avg_interest_rate = $total_rate / count($durations);
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
    .fintech-card {
      background: #fff;
      border-radius: var(--radius-2xl);
      box-shadow: var(--shadow-2xl);
      padding: var(--space-10);
      margin-bottom: var(--space-12);
      font-family: 'Inter', sans-serif;
      overflow: hidden;
    }

    .hero-section {
      background: linear-gradient(135deg, <?php echo $category['color']; ?> 0%, <?php echo $category['color']; ?>dd 100%);
      color: white;
      padding: var(--space-12) var(--space-8);
      border-radius: var(--radius-xl);
      margin-bottom: var(--space-8);
    }

    .plan-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: var(--space-6);
    }

    .plan-icon {
      width: 72px;
      height: 72px;
      border-radius: var(--radius-2xl);
      background: rgba(255,255,255,0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
    }

    .plan-title h1 {
      font-size: var(--font-size-3xl);
      margin: 0 0 var(--space-2) 0;
    }

    .plan-subtitle {
      font-size: var(--font-size-md);
      opacity: 0.9;
      margin: 0;
    }

    .invest-button {
      background: white;
      color: <?php echo $category['color']; ?>;
      padding: var(--space-3) var(--space-6);
      border-radius: var(--radius-full);
      font-weight: 600;
      font-size: var(--font-size-md);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: var(--space-2);
      transition: all 0.3s;
    }

    .invest-button:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* Compact Annual Returns */
    .returns-section {
      text-align: center;
      margin: var(--space-6) 0;
      padding: var(--space-5);
      border-radius: var(--radius-xl);
      background: var(--gray-50);
      box-shadow: var(--shadow-sm);
    }

    .returns-title {
      font-size: var(--font-size-lg);
      margin: 0;
    }

    .returns-value {
      font-size: var(--font-size-3xl);
      font-weight: 700;
      color: var(--success-600);
      margin: var(--space-2) 0;
    }

    .returns-subtitle {
      color: var(--gray-600);
      font-size: var(--font-size-sm);
    }

    /* Toggle */
    .expand-toggle {
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      color: var(--primary-600);
      font-weight: 500;
      padding: var(--space-3);
      user-select: none;
    }

    .expand-toggle i {
      margin-left: var(--space-2);
      transition: transform 0.3s;
    }

    /* Collapsible */
    .details-wrapper {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.5s ease, opacity 0.3s ease;
      opacity: 0;
    }

    .details-wrapper.open {
      max-height: 2000px;
      opacity: 1;
    }

    .details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: var(--space-8);
      margin-top: var(--space-6);
    }

    .details-section {
      background: var(--gray-50);
      border-radius: var(--radius-xl);
      padding: var(--space-6);
      box-shadow: var(--shadow-xs);
    }

    .section-title {
      font-size: var(--font-size-lg);
      font-weight: 600;
      margin-bottom: var(--space-3);
      display: flex;
      align-items: center;
      gap: var(--space-2);
    }

    .section-content {
      color: var(--gray-600);
      font-size: var(--font-size-sm);
      line-height: 1.6;
    }

    @media (max-width:768px){
      .details-grid{ grid-template-columns:1fr; }
    }
  </style>
</head>
<body>


<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
   <?php include '../includes/navbar.php'; ?>

  <div class="container-wide">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span>›</span>
      <a href="invest.php">Investment Plans</a>
      <span>›</span>
      <span><?php echo htmlspecialchars($category['name']); ?></span>
    </div>

    <!-- Back Button -->
    <a href="invest.php" class="back-button">
      <i class="fas fa-arrow-left"></i>
      Back to Investment Plans
    </a>

    <!-- Fintech Card -->
    <div class="fintech-card">
      <!-- Hero -->
      <div class="hero-section">
        <div class="plan-header">
          <div class="plan-icon"><?php echo $category['icon']; ?></div>
          <div class="plan-title">
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            <p class="plan-subtitle">By AquaVault Capital</p>
          </div>
          <a href="invest_amount.php" class="invest-button">
            Invest Now <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>

      <!-- Returns -->
      <div class="returns-section">
        <h2 class="returns-title">Annual Returns</h2>
        <div class="returns-value"><?php echo number_format($avg_interest_rate,2); ?>%</div>
        <div class="returns-subtitle">(Paid at Maturity)</div>
      </div>

      <!-- Toggle -->
      <div class="expand-toggle" onclick="toggleDetails()">
        <span>View Details</span>
        <i class="fas fa-chevron-down"></i>
      </div>

      <!-- Collapsible -->
      <div class="details-wrapper" id="detailsWrapper">
        <div class="details-grid">
          <div class="details-section">
            <h3 class="section-title"><i class="fas fa-info-circle"></i> About</h3>
            <div class="section-content"><?php echo htmlspecialchars($category['description']); ?></div>
          </div>
          <div class="details-section">
            <h3 class="section-title"><i class="fas fa-chart-line"></i> How You Earn</h3>
            <div class="section-content">
              <ul>
                <li>Professional portfolio management</li>
                <li>Diversified strategies</li>
                <li>Risk-adjusted returns</li>
              </ul>
            </div>
          </div>
          <div class="details-section">
            <h3 class="section-title"><i class="fas fa-pie-chart"></i> Composition</h3>
            <div class="section-content">
              <ul>
                <li>Diversified asset allocation</li>
                <li>Liquidity optimization</li>
              </ul>
            </div>
          </div>
          <div class="details-section">
            <h3 class="section-title"><i class="fas fa-user-check"></i> Suitability</h3>
            <div class="section-content">
              <ul>
                <li>Steady returns seekers</li>
                <li>Medium to long-term goals</li>
              </ul>
            </div>
          </div>
        </div>
        <!-- CTA -->
        <div style="text-align:center;margin:var(--space-10) 0;">
          <a href="invest_amount.php" class="invest-button" style="font-size:var(--font-size-lg);padding:var(--space-5)var(--space-10);">
            <i class="fas fa-rocket"></i> Start Investing Now
          </a>
          <p style="color:var(--gray-600);margin-top:var(--space-3);font-size:var(--font-size-sm);">
            Your investment starts earning immediately after confirmation
          </p>
        </div>
      </div>
    </div>
  </div>

  <script>
    function toggleDetails(){
      const wrapper=document.getElementById('detailsWrapper');
      const icon=document.querySelector('.expand-toggle i');
      wrapper.classList.toggle('open');
      icon.style.transform=wrapper.classList.contains('open')?'rotate(180deg)':'rotate(0)';
    }
  </script>
</body>
</html>
