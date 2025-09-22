<?php
require_once 'db/connect.php';

echo "=== DEBUGGING WITHDRAWAL ISSUE ===\n\n";

// Check server timezone and date
echo "Server Timezone: " . date_default_timezone_get() . "\n";
echo "Server Current Date: " . date('Y-m-d H:i:s') . "\n";
echo "Server Current Date (UTC): " . gmdate('Y-m-d H:i:s') . "\n\n";

// Check user investments
echo "=== ALL USER INVESTMENTS ===\n";
try {
    $stmt = $pdo->prepare('SELECT id, user_id, reference, amount, status, category_id, start_date, maturity_date, created_at FROM user_investments ORDER BY id DESC');
    $stmt->execute();
    $investments = $stmt->fetchAll();
    
    if (empty($investments)) {
        echo "No investments found.\n";
    } else {
        foreach ($investments as $inv) {
            echo "ID: {$inv['id']}, User: {$inv['user_id']}, Ref: {$inv['reference']}, Status: {$inv['status']}, Category: {$inv['category_id']}\n";
            echo "  Start: {$inv['start_date']}, Maturity: {$inv['maturity_date']}, Created: {$inv['created_at']}\n";
            
            // Check if maturity date is in the past
            $maturity_timestamp = strtotime($inv['maturity_date']);
            $current_timestamp = time();
            $is_past = $maturity_timestamp < $current_timestamp;
            echo "  Maturity timestamp: $maturity_timestamp, Current: $current_timestamp, Is past: " . ($is_past ? 'YES' : 'NO') . "\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check matured investments specifically
echo "=== MATURED INVESTMENTS ===\n";
try {
    $stmt = $pdo->prepare('SELECT id, user_id, reference, amount, status, category_id, start_date, maturity_date FROM user_investments WHERE status = "matured"');
    $stmt->execute();
    $matured = $stmt->fetchAll();
    
    if (empty($matured)) {
        echo "No matured investments found.\n";
    } else {
        foreach ($matured as $inv) {
            echo "ID: {$inv['id']}, User: {$inv['user_id']}, Ref: {$inv['reference']}, Category: {$inv['category_id']}\n";
            echo "  Start: {$inv['start_date']}, Maturity: {$inv['maturity_date']}\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check withdrawal requests
echo "=== WITHDRAWAL REQUESTS ===\n";
try {
    $stmt = $pdo->prepare('SELECT id, user_id, investment_id, reference, status FROM withdrawal_requests');
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    if (empty($requests)) {
        echo "No withdrawal requests found.\n";
    } else {
        foreach ($requests as $req) {
            echo "ID: {$req['id']}, User: {$req['user_id']}, Investment: {$req['investment_id']}, Ref: {$req['reference']}, Status: {$req['status']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test the exact query from withdraw.php
echo "\n=== TESTING WITHDRAWAL QUERY ===\n";
try {
    // Let's test with user ID 1 (you can change this to your actual user ID)
    $test_user_id = 1;
    
    $stmt = $pdo->prepare("
        SELECT ui.*, ic.name as category_name, ic.icon as category_icon, id.name as duration_name
        FROM user_investments ui
        LEFT JOIN investment_categories ic ON ui.category_id = ic.id
        LEFT JOIN investment_durations id ON ui.duration_id = id.id
        WHERE ui.user_id = ? 
        AND ui.status = 'matured' 
        AND ui.id NOT IN (
            SELECT DISTINCT investment_id 
            FROM withdrawal_requests 
            WHERE investment_id IS NOT NULL 
            AND status IN ('pending', 'approved', 'processing', 'completed')
        )
        ORDER BY ui.maturity_date ASC
    ");
    $stmt->execute([$test_user_id]);
    $results = $stmt->fetchAll();
    
    echo "Query results for user ID $test_user_id:\n";
    if (empty($results)) {
        echo "No results found.\n";
    } else {
        foreach ($results as $result) {
            echo "ID: {$result['id']}, Ref: {$result['reference']}, Amount: {$result['amount']}, Category: {$result['category_name']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check what user IDs exist
echo "\n=== AVAILABLE USERS ===\n";
try {
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email FROM users');
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, Email: {$user['email']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
