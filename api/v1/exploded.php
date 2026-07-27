<?php
header('Content-Type: application/json');

// Require database connection and DbOperations
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/DbOperations.php';

$dbOps = new DbOperations($pdo);

try {
    if (isset($_GET['part_id'])) {
        // Explode a specific part
        $partId = filter_input(INPUT_GET, 'part_id', FILTER_VALIDATE_INT);
        if ($partId === false || $partId === null) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or missing "part_id" parameter.'
            ]);
            exit;
        }

        // Fetch exploded BOM
        $explodedBom = $dbOps->getExplodedBom($partId);
        echo json_encode([
            'status' => 'success',
            'data' => $explodedBom
        ], JSON_PRETTY_PRINT);
    } else {
        // List all top-level products
        $products = $dbOps->getTopLevelProducts();
        echo json_encode([
            'status' => 'success',
            'data' => $products
        ], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
