<?php
/**
 * AquaVault Capital - Paystack Webhook Handler
 * Handles payment notifications from Paystack
 */
require_once '../db/connect.php';
require_once '../config/paystack.php';

// Set content type
header('Content-Type: application/json');

// Get the raw POST data
$input = file_get_contents('php://input');
$event = json_decode($input, true);

// Log webhook for debugging
error_log("Paystack Webhook: " . $input);

// Verify webhook signature for security
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$expected_signature = hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY);

// Verify signature to prevent unauthorized webhook calls
if (!hash_equals($expected_signature, $signature)) {
    error_log("Webhook signature mismatch - Expected: $expected_signature, Received: $signature");
    error_log("Webhook payload: " . $input);
    
    // In production, always verify signatures
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit();
}

// Handle different event types
switch ($event['event']) {
    case 'charge.success':
        handle_successful_payment($event['data'], $pdo);
        break;
    
    case 'charge.failed':
        handle_failed_payment($event['data'], $pdo);
        break;
    
    case 'transfer.success':
        handle_successful_transfer($event['data'], $pdo);
        break;
    
    case 'transfer.failed':
        handle_failed_transfer($event['data'], $pdo);
        break;
    
    case 'transfer.reversed':
        handle_reversed_transfer($event['data'], $pdo);
        break;
    
    default:
        // Log unhandled events
        error_log("Unhandled webhook event: " . $event['event']);
        break;
}

echo json_encode(['status' => 'success']);

/**
 * Handle successful payment
 */
function handle_successful_payment($data, $pdo) {
    $reference = $data['reference'];
    
    try {
        // Check if payment is already processed
        $stmt = $pdo->prepare("SELECT id FROM user_investments WHERE payment_reference = ? AND payment_status = 'paid'");
        $stmt->execute([$reference]);
        
        if ($stmt->fetch()) {
            // Payment already processed
            return;
        }
        
        // Find pending investment with this reference
        $stmt = $pdo->prepare("
            SELECT ui.*, u.email, u.first_name, u.last_name 
            FROM user_investments ui 
            JOIN users u ON ui.user_id = u.id 
            WHERE ui.payment_reference = ? AND ui.payment_status = 'pending'
        ");
        $stmt->execute([$reference]);
        $investment = $stmt->fetch();
        
        if (!$investment) {
            error_log("No pending investment found for reference: $reference");
            return;
        }
        
        // Update investment status
        $stmt = $pdo->prepare("
            UPDATE user_investments 
            SET payment_status = 'paid', status = 'active', updated_at = NOW() 
            WHERE payment_reference = ?
        ");
        $stmt->execute([$reference]);
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'completed', updated_at = NOW() 
            WHERE payment_reference = ?
        ");
        $stmt->execute([$reference]);
        
        // Update user total invested
        $stmt = $pdo->prepare("UPDATE users SET total_invested = total_invested + ? WHERE id = ?");
        $stmt->execute([$investment['amount'], $investment['user_id']]);
        
        // Log successful activation
        error_log("Investment activated successfully for user {$investment['user_id']}, reference: $reference");
        
        // TODO: Send email notification to user
        
    } catch (PDOException $e) {
        error_log("Webhook payment processing error: " . $e->getMessage());
    }
}

/**
 * Handle failed payment
 */
function handle_failed_payment($data, $pdo) {
    $reference = $data['reference'];
    
    try {
        // Update investment status
        $stmt = $pdo->prepare("
            UPDATE user_investments 
            SET payment_status = 'failed', status = 'cancelled', updated_at = NOW() 
            WHERE payment_reference = ?
        ");
        $stmt->execute([$reference]);
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'failed', updated_at = NOW() 
            WHERE payment_reference = ?
        ");
        $stmt->execute([$reference]);
        
        // Log failed payment
        error_log("Payment failed for reference: $reference");
        
        // TODO: Send email notification to user about failed payment
        
    } catch (PDOException $e) {
        error_log("Webhook payment failure processing error: " . $e->getMessage());
    }
}

/**
 * Handle successful transfer
 */
function handle_successful_transfer($data, $pdo) {
    $transfer_code = $data['transfer_code'];
    $reference = $data['reference'];
    
    try {
        // Find withdrawal request with this transfer code
        $stmt = $pdo->prepare("
            SELECT wr.*, u.email, u.first_name, u.last_name,
                   uba.account_name, uba.account_number, uba.bank_name
            FROM withdrawal_requests wr
            JOIN users u ON wr.user_id = u.id
            JOIN user_bank_accounts uba ON wr.bank_account_id = uba.id
            WHERE wr.paystack_transfer_code = ? AND wr.status = 'processing'
        ");
        $stmt->execute([$transfer_code]);
        $withdrawal = $stmt->fetch();
        
        if (!$withdrawal) {
            error_log("No processing withdrawal found for transfer code: $transfer_code");
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update withdrawal request status to completed
        $stmt = $pdo->prepare("
            UPDATE withdrawal_requests 
            SET status = 'completed', 
                paystack_reference = ?,
                processed_at = NOW(),
                updated_at = NOW()
            WHERE paystack_transfer_code = ?
        ");
        $stmt->execute([$reference, $transfer_code]);
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'completed', 
                payment_reference = ?,
                updated_at = NOW()
            WHERE reference = ?
        ");
        $stmt->execute([$reference, $withdrawal['reference']]);
        
        // Update user wallet balance (deduct the withdrawal amount)
        $stmt = $pdo->prepare("
            UPDATE users 
            SET wallet_balance = wallet_balance - ?
            WHERE id = ?
        ");
        $stmt->execute([$withdrawal['net_amount'], $withdrawal['user_id']]);
        
        $pdo->commit();
        
        // Log successful transfer
        error_log("Withdrawal completed successfully for user {$withdrawal['user_id']}, transfer code: $transfer_code, amount: {$withdrawal['net_amount']}");
        
        // TODO: Send email notification to user about successful withdrawal
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Webhook transfer success processing error: " . $e->getMessage());
    }
}

/**
 * Handle failed transfer
 */
function handle_failed_transfer($data, $pdo) {
    $transfer_code = $data['transfer_code'];
    $reference = $data['reference'];
    $failure_reason = $data['failure_reason'] ?? 'Transfer failed';
    
    try {
        // Find withdrawal request with this transfer code
        $stmt = $pdo->prepare("
            SELECT wr.*, u.email, u.first_name, u.last_name
            FROM withdrawal_requests wr
            JOIN users u ON wr.user_id = u.id
            WHERE wr.paystack_transfer_code = ? AND wr.status = 'processing'
        ");
        $stmt->execute([$transfer_code]);
        $withdrawal = $stmt->fetch();
        
        if (!$withdrawal) {
            error_log("No processing withdrawal found for transfer code: $transfer_code");
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update withdrawal request status to failed
        $stmt = $pdo->prepare("
            UPDATE withdrawal_requests 
            SET status = 'failed', 
                rejection_reason = ?,
                paystack_reference = ?,
                processed_at = NOW(),
                updated_at = NOW()
            WHERE paystack_transfer_code = ?
        ");
        $stmt->execute([$failure_reason, $reference, $transfer_code]);
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'failed', 
                payment_reference = ?,
                updated_at = NOW()
            WHERE reference = ?
        ");
        $stmt->execute([$reference, $withdrawal['reference']]);
        
        $pdo->commit();
        
        // Log failed transfer
        error_log("Withdrawal failed for user {$withdrawal['user_id']}, transfer code: $transfer_code, reason: $failure_reason");
        
        // TODO: Send email notification to user about failed withdrawal
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Webhook transfer failure processing error: " . $e->getMessage());
    }
}

/**
 * Handle reversed transfer
 */
function handle_reversed_transfer($data, $pdo) {
    $transfer_code = $data['transfer_code'];
    $reference = $data['reference'];
    $reversal_reason = $data['reason'] ?? 'Transfer reversed';
    
    try {
        // Find withdrawal request with this transfer code
        $stmt = $pdo->prepare("
            SELECT wr.*, u.email, u.first_name, u.last_name
            FROM withdrawal_requests wr
            JOIN users u ON wr.user_id = u.id
            WHERE wr.paystack_transfer_code = ? AND wr.status IN ('processing', 'completed')
        ");
        $stmt->execute([$transfer_code]);
        $withdrawal = $stmt->fetch();
        
        if (!$withdrawal) {
            error_log("No withdrawal found for transfer code: $transfer_code");
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update withdrawal request status to failed
        $stmt = $pdo->prepare("
            UPDATE withdrawal_requests 
            SET status = 'failed', 
                rejection_reason = ?,
                paystack_reference = ?,
                processed_at = NOW(),
                updated_at = NOW()
            WHERE paystack_transfer_code = ?
        ");
        $stmt->execute([$reversal_reason, $reference, $transfer_code]);
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'failed', 
                payment_reference = ?,
                updated_at = NOW()
            WHERE reference = ?
        ");
        $stmt->execute([$reference, $withdrawal['reference']]);
        
        // If withdrawal was completed, refund the amount to user's wallet
        if ($withdrawal['status'] === 'completed') {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET wallet_balance = wallet_balance + ?
                WHERE id = ?
            ");
            $stmt->execute([$withdrawal['net_amount'], $withdrawal['user_id']]);
        }
        
        $pdo->commit();
        
        // Log reversed transfer
        error_log("Withdrawal reversed for user {$withdrawal['user_id']}, transfer code: $transfer_code, reason: $reversal_reason");
        
        // TODO: Send email notification to user about reversed withdrawal
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Webhook transfer reversal processing error: " . $e->getMessage());
    }
}
?>
