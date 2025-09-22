<?php
/**
 * AquaVault Capital - Investment Maturity Checker
 * This script checks for matured investments and updates their status
 * Should be run as a cron job daily
 */
require_once '../db/connect.php';

// Set timezone
date_default_timezone_set('Africa/Lagos');

try {
    // Get all active investments that have reached maturity
    $stmt = $pdo->prepare("
        SELECT * FROM user_investments 
        WHERE status = 'active' 
        AND maturity_date <= CURDATE()
        AND category_id IS NOT NULL
    ");
    $stmt->execute();
    $matured_investments = $stmt->fetchAll();
    
    $updated_count = 0;
    $error_count = 0;
    
    foreach ($matured_investments as $investment) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update investment status to matured
            $stmt = $pdo->prepare("
                UPDATE user_investments 
                SET status = 'matured', matured_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$investment['id']]);
            
            // Create return transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (
                    user_id, investment_id, reference, type, amount, 
                    description, status, payment_method
                ) VALUES (?, ?, ?, 'return', ?, ?, 'completed', 'system')
            ");
            
            $return_reference = 'RET_' . time() . '_' . $investment['id'];
            $description = "Investment returns for " . $investment['reference'] . " - Matured on " . date('Y-m-d');
            
            $stmt->execute([
                $investment['user_id'],
                $investment['id'],
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
            $updated_count++;
            
            // Log the maturity
            error_log("Investment matured: ID {$investment['id']}, User {$investment['user_id']}, Amount {$investment['net_return']}");
            
        } catch (PDOException $e) {
            $pdo->rollback();
            $error_count++;
            error_log("Error updating matured investment ID {$investment['id']}: " . $e->getMessage());
        }
    }
    
    // Log summary
    $log_message = "Maturity check completed: {$updated_count} investments matured, {$error_count} errors";
    error_log($log_message);
    
    // If running from command line, output the results
    if (php_sapi_name() === 'cli') {
        echo $log_message . "\n";
    } else {
        // If running via web, return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $log_message,
            'matured_count' => $updated_count,
            'error_count' => $error_count,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (PDOException $e) {
    $error_message = "Database error during maturity check: " . $e->getMessage();
    error_log($error_message);
    
    if (php_sapi_name() === 'cli') {
        echo $error_message . "\n";
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $error_message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
?>
