<?php
/**
 * Wagon Endpoint Handler
 * Routes wagon-related requests to specific operations
 */

require_once dirname(__DIR__) . '/open_db.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/validation.php';

function handleWagonEndpoint($method, $pathParts) {
    global $connection;
    
    if (empty($pathParts)) {
        return jsonError('Invalid wagon endpoint', 400);
    }

    $operation = array_shift($pathParts);

    switch ($operation) {
        case 'cargo':
            if ($method !== 'GET') {
                return jsonError('Method not allowed', 405);
            }
            handleCargoLookup($pathParts);
            break;

        case 'location':
            if ($method !== 'GET') {
                return jsonError('Method not allowed', 405);
            }
            handleLocationLookup($pathParts);
            break;

        case 'unload':
            if ($method !== 'POST') {
                return jsonError('Method not allowed', 405);
            }
            handleWagonUnload();
            break;

        case 'load':
            if ($method !== 'POST') {
                return jsonError('Method not allowed', 405);
            }
            handleWagonLoad();
            break;

        case 'reposition':
            if ($method !== 'POST') {
                return jsonError('Method not allowed', 405);
            }
            handleWagonReposition();
            break;

        default:
            return jsonError('Unknown wagon operation', 400);
    }
}

/**
 * GET /wagon/cargo/id/:tag_name
 * Returns detailed cargo information for a car by tag/ID
 */
function handleCargoLookup($pathParts) {
    global $connection;
    
    if (count($pathParts) < 2 || $pathParts[0] !== 'id') {
        return jsonError('Invalid cargo lookup format', 400);
    }

    $tagName = $pathParts[1];
    
    // Query car by reporting marks (tag_name)
    $query = "SELECT c.id, c.reporting_marks, c.car_code, c.status, 
                     co.waybill_number as shipment_code, l.name as loading_station, l.description as loading_location,
                     ul.name as unloading_station, ul.description as unloading_location
              FROM cars c
              LEFT JOIN car_orders co ON c.id = co.car
              LEFT JOIN locations l ON c.current_location = l.id
              LEFT JOIN locations ul ON co.destination = ul.id
              WHERE c.reporting_marks = ? OR c.car_code = ?
              LIMIT 1";
    
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $stmt->bind_param('ss', $tagName, $tagName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return jsonError('Car not found', 404);
    }

    $car = $result->fetch_assoc();
    
    return jsonResponse([
        'id' => $car['id'],
        'reportingMarks' => $car['reporting_marks'],
        'carCode' => $car['car_code'],
        'status' => $car['status'],
        'shipmentCode' => $car['shipment_code'],
        'loadingStation' => $car['loading_station'],
        'loadingLocation' => $car['loading_location'],
        'unloadingStation' => $car['unloading_station'],
        'unloadingLocation' => $car['unloading_location']
    ], 200);
}

/**
 * GET /wagon/location/:location_name
 * Returns all cars currently at a specific location
 */
function handleLocationLookup($pathParts) {
    global $connection;
    
    $locationName = !empty($pathParts) ? $pathParts[0] : '';
    
    if (empty($locationName)) {
        return jsonError('Location name is required', 400);
    }

    // Query all cars at the location
    $query = "SELECT c.id, c.reporting_marks, c.car_code, c.status
              FROM cars c
              LEFT JOIN locations l ON c.current_location = l.id
              WHERE LOWER(l.name) = LOWER(?) OR LOWER(l.description) = LOWER(?)
              ORDER BY c.reporting_marks";
    
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $stmt->bind_param('ss', $locationName, $locationName);
    $stmt->execute();
    $result = $stmt->get_result();

    $cars = [];
    while ($row = $result->fetch_assoc()) {
        $cars[] = [
            'id' => $row['id'],
            'reportingMarks' => $row['reporting_marks'],
            'carType' => $row['car_code'],
            'status' => $row['status']
        ];
    }

    return jsonResponse($cars, 200);
}

/**
 * POST /wagon/unload
 * Mark a wagon/car as unloading at current location
 * Required fields: wagonId, reportingMarks
 */
function handleWagonUnload() {
    global $connection;
    
    $payload = getJsonPayload();
    
    // Validate required fields
    $validation = validateRequired($payload, ['wagonId', 'reportingMarks']);
    if (!$validation['valid']) {
        return jsonError($validation['message'], 400);
    }

    $wagonId = $payload['wagonId'];
    $reportingMarks = $payload['reportingMarks'];

    // Verify car exists with correct reporting marks
    $query = "SELECT id FROM cars WHERE id = ? AND reporting_marks = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $stmt->bind_param('is', $wagonId, $reportingMarks);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return jsonError('Car not found with provided ID and reporting marks', 404);
    }

    // Update car status to Unloading
    $updateQuery = "UPDATE cars SET status = 'Unloading' WHERE id = ?";
    $updateStmt = $connection->prepare($updateQuery);
    if (!$updateStmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $updateStmt->bind_param('i', $wagonId);
    if (!$updateStmt->execute()) {
        return jsonError('Failed to update car status', 500);
    }

    return jsonResponse(['message' => 'Wagon unload request processed successfully'], 200);
}

/**
 * POST /wagon/load
 * Mark a wagon/car as loading
 * Required fields: wagonId, reportingMarks
 */
function handleWagonLoad() {
    global $connection;
    
    $payload = getJsonPayload();
    
    // Validate required fields
    $validation = validateRequired($payload, ['wagonId', 'reportingMarks']);
    if (!$validation['valid']) {
        return jsonError($validation['message'], 400);
    }

    $wagonId = $payload['wagonId'];
    $reportingMarks = $payload['reportingMarks'];

    // Verify car exists with correct reporting marks
    $query = "SELECT id FROM cars WHERE id = ? AND reporting_marks = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $stmt->bind_param('is', $wagonId, $reportingMarks);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return jsonError('Car not found with provided ID and reporting marks', 404);
    }

    // Update car status to Loading
    $updateQuery = "UPDATE cars SET status = 'Loading' WHERE id = ?";
    $updateStmt = $connection->prepare($updateQuery);
    if (!$updateStmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $updateStmt->bind_param('i', $wagonId);
    if (!$updateStmt->execute()) {
        return jsonError('Failed to update car status', 500);
    }

    return jsonResponse(['message' => 'Wagon load request processed successfully'], 200);
}

/**
 * POST /wagon/reposition
 * Reposition a wagon to a new location
 * Required fields: wagonId, reportingMarks, locationId
 */
function handleWagonReposition() {
    global $connection;
    
    $payload = getJsonPayload();
    
    // Validate required fields
    $validation = validateRequired($payload, ['wagonId', 'reportingMarks', 'locationId']);
    if (!$validation['valid']) {
        return jsonError($validation['message'], 400);
    }

    $wagonId = $payload['wagonId'];
    $reportingMarks = $payload['reportingMarks'];
    $locationId = $payload['locationId'];

    // Verify car exists with correct reporting marks
    $query = "SELECT id FROM cars WHERE id = ? AND reporting_marks = ?";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $stmt->bind_param('is', $wagonId, $reportingMarks);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return jsonError('Car not found with provided ID and reporting marks', 404);
    }

    // Verify location exists
    $locQuery = "SELECT id FROM locations WHERE id = ?";
    $locStmt = $connection->prepare($locQuery);
    if (!$locStmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $locStmt->bind_param('i', $locationId);
    $locStmt->execute();
    $locResult = $locStmt->get_result();

    if ($locResult->num_rows === 0) {
        return jsonError('Location not found', 404);
    }

    // Update car location
    $updateQuery = "UPDATE cars SET current_location = ? WHERE id = ?";
    $updateStmt = $connection->prepare($updateQuery);
    if (!$updateStmt) {
        return jsonError('Database error: ' . $connection->error, 500);
    }

    $updateStmt->bind_param('ii', $locationId, $wagonId);
    if (!$updateStmt->execute()) {
        return jsonError('Failed to update car location', 500);
    }

    return jsonResponse(['message' => 'Wagon reposition request processed successfully'], 200);
}

/**
 * Get JSON payload from request body
 */
function getJsonPayload() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? $_POST ?? [];
}
?>
