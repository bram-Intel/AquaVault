<?php
/**
 * Debug script to check payment status and investment records
 */
session_start();
require_once 'db/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$user_id = $_SESSION['user_id'];
$reference = $_GET['reference'] ?? '';

echo "<h1>Payment Debug Information</h1>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>Reference:</strong> $reference</p>";

if ($reference) {
    echo "<h2>Investment Records</h2>";
    try {
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
            echo "<table border='1' cellpadding='5'>";
            foreach ($investment as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No investment found with reference: $reference</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>Transaction Records</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM transactions 
            WHERE payment_reference = ? AND user_id = ?
        ");
        $stmt->execute([$reference, $user_id]);
        $transaction = $stmt->fetch();
        
        if ($transaction) {
            echo "<table border='1' cellpadding='5'>";
            foreach ($transaction as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No transaction found with reference: $reference</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Recent Investments</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, ic.name as category_name, id.name as duration_name
        FROM user_investments ui
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.user_id = ?
        ORDER BY ui.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $investments = $stmt->fetchAll();
    
    if ($investments) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Reference</th><th>Category</th><th>Duration</th><th>Amount</th><th>Status</th><th>Payment Status</th><th>Created</th></tr>";
        foreach ($investments as $inv) {
            echo "<tr>";
            echo "<td>" . $inv['reference'] . "</td>";
            echo "<td>" . $inv['category_name'] . "</td>";
            echo "<td>" . $inv['duration_name'] . "</td>";
            echo "<td>₦" . number_format($inv['amount']) . "</td>";
            echo "<td>" . $inv['status'] . "</td>";
            echo "<td>" . $inv['payment_status'] . "</td>";
            echo "<td>" . $inv['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No investments found</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='user/dashboard.php'>Back to Dashboard</a></p>";
?>
