<?php
/**
 * Debug script to check database schema issues
 */
require_once 'db/connect.php';

echo "<h1>Database Schema Debug</h1>";

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
    
    // Check foreign key constraints
    echo "<h2>Foreign Key Constraints</h2>";
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'user_investments'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll();
    
    if ($constraints) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>References</th></tr>";
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "." . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No foreign key constraints found</p>";
    }
    
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
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommended Fix</h2>";
echo "<p>Run this SQL command to fix the issue:</p>";
echo "<pre>ALTER TABLE `user_investments` MODIFY COLUMN `plan_id` INT(11) DEFAULT NULL;</pre>";

echo "<p><a href='user/dashboard.php'>Back to Dashboard</a></p>";
?>
