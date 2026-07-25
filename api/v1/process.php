<?php
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Use POST.'
    ]);
    exit;
}

// Check if file is uploaded
if (!isset($_FILES['bom_file'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'No file uploaded under key "bom_file".'
    ]);
    exit;
}

$file = $_FILES['bom_file'];

// Check upload error
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'File upload error code: ' . $file['error']
    ]);
    exit;
}

// Get extension
$filename = $file['name'];
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Require database connection, DbOperations, and ParserFactory
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/DbOperations.php';
require_once __DIR__ . '/ParserFactory.php';

try {
    $importer = ParserFactory::create($extension);
    $className = get_class($importer);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}

try {
    // Call the parser on the temporary uploaded file path
    $parsedData = $importer->parse($file['tmp_name']);

    // Detect product name from filename (excluding extension)
    $productName = pathinfo($filename, PATHINFO_FILENAME);

    // Perform database operations using separate class
    $dbOps = new DbOperations($pdo);
    $dbOps->upsertBom($parsedData);

    echo json_encode([
        'status' => 'success',
        'message' => 'BOM file parsed and saved/updated in database successfully.',
        'product_name' => $productName,
        'file_name' => $filename,
        'file_size' => $file['size'],
        'detected_format' => strtoupper($extension),
        'importer_class' => $className,
        'parsed_data' => $parsedData
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during file parsing or DB ingestion: ' . $e->getMessage()
    ]);
}
