<?php
/**
 * Validation Helper Functions
 */

/**
 * Validate that required fields are present in payload
 */
function validateRequired($payload, $requiredFields) {
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($payload[$field]) || $payload[$field] === '' || $payload[$field] === null) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        return [
            'valid' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ];
    }

    return ['valid' => true];
}
?>
