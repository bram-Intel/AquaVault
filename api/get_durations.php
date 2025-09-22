<?php
/**
 * AquaVault Capital - Get Investment Durations API
 * Returns durations for a specific investment category
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get category ID
$category_id = (int)($_GET['category_id'] ?? 0);

if (!$category_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID is required']);
    exit();
}

try {
    require_once '../db/connect.php';
    
    // Get durations for the category
    $stmt = $pdo->prepare("
        SELECT id, name, days, interest_rate, tax_rate, is_active
        FROM investment_durations 
        WHERE category_id = ? AND is_active = 1
        ORDER BY days ASC
    ");
    $stmt->execute([$category_id]);
    $durations = $stmt->fetchAll();
    
    if (empty($durations)) {
        echo json_encode([
            'success' => false,
            'error' => 'No durations found for this category'
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'durations' => $durations
    ]);
    
} catch (PDOException $e) {
    error_log("Get durations error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>
