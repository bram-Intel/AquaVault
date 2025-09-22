<?php
require_once 'db/connect.php';

// Set timezone
date_default_timezone_set('Africa/Lagos');

echo "=== MANUAL INVESTMENT MATURITY TOOL ===\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mature') {
    $investment_id = $_POST['investment_id'];
    
    try {
        // Get investment details
        $stmt = $pdo->prepare("SELECT * FROM user_investments WHERE id = ? AND status = 'active'");
        $stmt->execute([$investment_id]);
        $investment = $stmt->fetch();
        
        if (!$investment) {
            echo "Investment not found or not active.\n";
        } else {
            echo "Maturity investment ID: {$investment['id']}\n";
            echo "User ID: {$investment['user_id']}\n";
            echo "Reference: {$investment['reference']}\n";
            echo "Amount: {$investment['amount']}\n";
            echo "Net Return: {$investment['net_return']}\n\n";
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Update investment status to matured
            $stmt = $pdo->prepare("
                UPDATE user_investments 
                SET status = 'matured', matured_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$investment_id]);
            
            // Create return transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (
                    user_id, investment_id, reference, type, amount, 
                    description, status, payment_method
                ) VALUES (?, ?, ?, 'return', ?, ?, 'completed', 'system')
            ");
            
            $return_reference = 'RET_' . time() . '_' . $investment_id;
            $description = "Investment returns for " . $investment['reference'] . " - Matured on " . date('Y-m-d');
            
            $stmt->execute([
                $investment['user_id'],
                $investment_id,
                $return_reference,
                $investment['net_return'],
                $description
            ]);
            
            // Update user's total returns
            $stmt = $pdo->prepare("
                UPDATE users 
                SET total_returns = total_returns + ?
                WHERE id = ?
            ");
            $stmt->execute([$investment['net_return'], $investment['user_id']]);
            
            $pdo->commit();
            
            echo "✅ Investment matured successfully!\n";
            echo "Return reference: $return_reference\n";
            echo "User can now request withdrawal.\n\n";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Show current server date
echo "Current server date: " . date('Y-m-d H:i:s') . "\n";
echo "Current server timezone: " . date_default_timezone_get() . "\n\n";

// Show active investments
try {
    $stmt = $pdo->prepare('SELECT id, user_id, reference, amount, net_return, status FROM user_investments WHERE status = "active" ORDER BY id DESC');
    $stmt->execute();
    $investments = $stmt->fetchAll();
    
    if (empty($investments)) {
        echo "No active investments found.\n";
    } else {
        echo "Active investments:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-5s %-8s %-20s %-12s %-12s\n", "ID", "User", "Reference", "Amount", "Returns");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($investments as $inv) {
            printf("%-5s %-8s %-20s %-12s %-12s\n", 
                $inv['id'], 
                $inv['user_id'], 
                substr($inv['reference'], 0, 20), 
                '₦' . number_format($inv['amount']), 
                '₦' . number_format($inv['net_return'])
            );
        }
        echo str_repeat("-", 80) . "\n\n";
        
        echo "To mature an investment, use the form below:\n\n";
        
        echo "<form method='POST'>\n";
        echo "<input type='hidden' name='action' value='mature'>\n";
        echo "Investment ID: <input type='number' name='investment_id' required>\n";
        echo "<input type='submit' value='Mature Investment'>\n";
        echo "</form>\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Show matured investments
echo "\n" . str_repeat("=", 50) . "\n";
echo "MATURED INVESTMENTS:\n";
echo str_repeat("=", 50) . "\n";

try {
    $stmt = $pdo->prepare('SELECT id, user_id, reference, amount, net_return, status, matured_at FROM user_investments WHERE status = "matured" ORDER BY id DESC');
    $stmt->execute();
    $matured = $stmt->fetchAll();
    
    if (empty($matured)) {
        echo "No matured investments found.\n";
    } else {
        echo str_repeat("-", 100) . "\n";
        printf("%-5s %-8s %-20s %-12s %-12s %-20s\n", "ID", "User", "Reference", "Amount", "Returns", "Matured At");
        echo str_repeat("-", 100) . "\n";
        
        foreach ($matured as $inv) {
            printf("%-5s %-8s %-20s %-12s %-12s %-20s\n", 
                $inv['id'], 
                $inv['user_id'], 
                substr($inv['reference'], 0, 20), 
                '₦' . number_format($inv['amount']), 
                '₦' . number_format($inv['net_return']),
                $inv['matured_at']
            );
        }
        echo str_repeat("-", 100) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
