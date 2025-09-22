<?php
/**
 * Simple debug script to check database schema issues
 * Doesn't require information_schema access
 */
require_once 'db/connect.php';

echo "<h1>Database Schema Debug (Simple)</h1>";

try {
    // Check user_investments table structure
    echo "<h2>user_investments Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE user_investments");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test inserting a record with NULL plan_id
    echo "<h2>Testing NULL plan_id Insert</h2>";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_investments 
            (user_id, plan_id, category_id, duration_id, reference, amount, interest_rate, tax_rate, 
             expected_return, net_return, start_date, maturity_date, 
             status, payment_status, payment_reference, payment_method) 
            VALUES (1, NULL, 1, 1, 'TEST_REF', 10000, 10.00, 5.00, 100, 95, '2025-01-01', '2025-01-31', 'pending', 'pending', 'TEST_REF', 'paystack')
        ");
        $stmt->execute();
        echo "<p style='color: green;'>✅ NULL plan_id insert successful!</p>";
        
        // Clean up test record
        $pdo->exec("DELETE FROM user_investments WHERE reference = 'TEST_REF'");
        echo "<p>Test record cleaned up</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ NULL plan_id insert failed: " . $e->getMessage() . "</p>";
        echo "<p><strong>This is the issue causing your payment error!</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Quick Fix</h2>";
echo "<p>If the test above failed, run this SQL command in phpMyAdmin:</p>";
echo "<pre>ALTER TABLE `user_investments` MODIFY COLUMN `plan_id` INT(11) DEFAULT NULL;</pre>";

echo "<p><a href='user/dashboard.php'>Back to Dashboard</a></p>";
?>
