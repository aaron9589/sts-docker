<?php
/**
 * STS REST API Router
 * Routes API requests to appropriate handlers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/sts/api', '', $path);
$path = str_replace('index.php', '', $path);

// Parse the path to extract endpoint and parameters
$pathParts = array_filter(explode('/', $path));

// Route the request
try {
    if (empty($pathParts)) {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }

    $endpoint = array_shift($pathParts);

    switch ($endpoint) {
        case 'wagon':
            require_once 'endpoints/wagon.php';
            handleWagonEndpoint($method, $pathParts);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown endpoint']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}
?>
