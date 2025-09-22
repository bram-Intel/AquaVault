<?php
/**
 * AquaVault Capital - Withdrawal Tables Installation Script
 * Run this script to add withdrawal functionality to your database
 */

require_once 'db/connect.php';

echo "<h2>AquaVault Capital - Installing Withdrawal Tables</h2>\n";

try {
    // Read the withdrawal schema
    $schema = file_get_contents('db/withdrawal_schema.sql');
    
    if (!$schema) {
        throw new Exception("Could not read withdrawal_schema.sql file");
    }
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>\n";
        } catch (PDOException $e) {
            $error_count++;
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
            echo "<p style='color: gray;'>Statement: " . substr($statement, 0, 100) . "...</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h3>Installation Summary</h3>\n";
    echo "<p><strong>Successful statements:</strong> $success_count</p>\n";
    echo "<p><strong>Failed statements:</strong> $error_count</p>\n";
    
    if ($error_count === 0) {
        echo "<p style='color: green; font-weight: bold;'>🎉 Withdrawal tables installed successfully!</p>\n";
        echo "<h3>Next Steps:</h3>\n";
        echo "<ul>\n";
        echo "<li>Set up a cron job to run <code>/home/jennifer/aqua/api/check_maturity.php</code> daily</li>\n";
        echo "<li>Test the withdrawal functionality with a test user</li>\n";
        echo "<li>Configure Paystack transfer settings in your admin panel</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p style='color: red; font-weight: bold;'>⚠️ Some statements failed. Please check the errors above.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Fatal Error: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='user/dashboard.php'>Go to User Dashboard</a> | <a href='admin/dashboard.php'>Go to Admin Dashboard</a></p>\n";
?>
