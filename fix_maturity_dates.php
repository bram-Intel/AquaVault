<?php
require_once 'db/connect.php';

echo "=== FIXING MATURITY DATES ===\n\n";

// Set timezone to match your server
date_default_timezone_set('Africa/Lagos');

echo "Current server date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Get all active investments
    $stmt = $pdo->prepare('SELECT id, user_id, reference, amount, start_date, maturity_date, status FROM user_investments WHERE status = "active"');
    $stmt->execute();
    $investments = $stmt->fetchAll();
    
    if (empty($investments)) {
        echo "No active investments found.\n";
    } else {
        echo "Found " . count($investments) . " active investments:\n\n";
        
        foreach ($investments as $investment) {
            echo "Investment ID: {$investment['id']}\n";
            echo "User ID: {$investment['user_id']}\n";
            echo "Reference: {$investment['reference']}\n";
            echo "Amount: {$investment['amount']}\n";
            echo "Start Date: {$investment['start_date']}\n";
            echo "Maturity Date: {$investment['maturity_date']}\n";
            echo "Status: {$investment['status']}\n";
            
            // Check if maturity date is in the past
            $maturity_timestamp = strtotime($investment['maturity_date']);
            $current_timestamp = time();
            $is_past = $maturity_timestamp < $current_timestamp;
            
            echo "Maturity timestamp: $maturity_timestamp\n";
            echo "Current timestamp: $current_timestamp\n";
            echo "Is maturity date in the past: " . ($is_past ? 'YES' : 'NO') . "\n";
            
            if ($is_past) {
                echo "This investment should be matured!\n";
                
                // Ask if user wants to mature this investment
                echo "Do you want to mature this investment? (This will make it available for withdrawal)\n";
                echo "Type 'yes' to mature, or press Enter to skip: ";
                
                // For now, let's just show what would happen
                echo "Would mature investment ID {$investment['id']} for user {$investment['user_id']}\n";
            }
            
            echo "\n" . str_repeat("-", 50) . "\n\n";
        }
    }
    
    // Also check if there are any investments with NULL maturity dates
    $stmt = $pdo->prepare('SELECT id, user_id, reference, maturity_date FROM user_investments WHERE maturity_date IS NULL');
    $stmt->execute();
    $null_dates = $stmt->fetchAll();
    
    if (!empty($null_dates)) {
        echo "Found investments with NULL maturity dates:\n";
        foreach ($null_dates as $inv) {
            echo "ID: {$inv['id']}, User: {$inv['user_id']}, Ref: {$inv['reference']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
