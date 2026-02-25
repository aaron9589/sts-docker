<?php
/**
 * Response Helper Functions
 * Standardizes API responses
 */

/**
 * Send a JSON success response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Send a JSON error response
 */
function jsonError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
}
?>
