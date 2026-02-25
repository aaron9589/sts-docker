<?php
// assign_car_ajax.php - Assign a car to a waybill via AJAX

require 'open_db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['waybill_number']) || !isset($input['car_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$waybill_number = $input['waybill_number'];
$car_id = $input['car_id'];

$dbc = open_db();

// Assign the car to the waybill
$sql = 'UPDATE car_orders SET car = "' . mysqli_real_escape_string($dbc, $car_id) . '"
        WHERE waybill_number = "' . mysqli_real_escape_string($dbc, $waybill_number) . '"';

if (!mysqli_query($dbc, $sql)) {
    http_response_code(500);
    echo json_encode(['error' => 'Update error: ' . mysqli_error($dbc)]);
    exit;
}

// Get session number for history record
$sql = 'SELECT setting_value FROM settings WHERE setting_name = "session_nbr"';
$rs = mysqli_query($dbc, $sql);
$row = mysqli_fetch_array($rs);
$session_nbr = $row['setting_value'];

// Get car's current location for history record
$sql = 'SELECT current_location_id FROM cars WHERE id = "' . mysqli_real_escape_string($dbc, $car_id) . '"';
$rs = mysqli_query($dbc, $sql);
$row = mysqli_fetch_array($rs);
$location = $row['current_location_id'];

// Insert history record
$sql = 'INSERT INTO history(car_id, session_nbr, event_date, event, location)
        VALUES ("' . mysqli_real_escape_string($dbc, $car_id) . '",
                "' . $session_nbr . '",
                "' . date("Y-m-d H:i:s") . '",
                "Filled car order ' . mysqli_real_escape_string($dbc, $waybill_number) . '",
                "' . $location . '")';

if (!mysqli_query($dbc, $sql)) {
    http_response_code(500);
    echo json_encode(['error' => 'History insert error: ' . mysqli_error($dbc)]);
    exit;
}

// Check if car is at loading location
$sql = 'SELECT COUNT(*) as count_at_loading
        FROM cars, shipments, car_orders
        WHERE car_orders.waybill_number = "' . mysqli_real_escape_string($dbc, $waybill_number) . '"
          AND shipments.id = car_orders.shipment
          AND cars.id = "' . mysqli_real_escape_string($dbc, $car_id) . '"
          AND cars.current_location_id = shipments.loading_location
          AND cars.status = "Empty"';

$rs = mysqli_query($dbc, $sql);
$row = mysqli_fetch_array($rs);

if ($row['count_at_loading'] > 0) {
    // Car is at loading location - mark as Loaded
    $sql = 'UPDATE cars
            SET status = "Loaded",
                load_count = load_count + 1
            WHERE id = "' . mysqli_real_escape_string($dbc, $car_id) . '"';
} else {
    // Car is not at loading location - mark as Ordered
    $sql = 'UPDATE cars
            SET status = "Ordered",
                load_count = load_count + 1
            WHERE id = "' . mysqli_real_escape_string($dbc, $car_id) . '"';
}

if (!mysqli_query($dbc, $sql)) {
    http_response_code(500);
    echo json_encode(['error' => 'Car status update error: ' . mysqli_error($dbc)]);
    exit;
}

// Get the car details to return
$sql = 'SELECT cars.reporting_marks, car_codes.code as car_code
        FROM cars
        LEFT JOIN car_codes ON car_codes.id = cars.car_code_id
        WHERE cars.id = "' . mysqli_real_escape_string($dbc, $car_id) . '"';

$rs = mysqli_query($dbc, $sql);
$car_row = mysqli_fetch_array($rs);

mysqli_close($dbc);

echo json_encode([
    'success' => true,
    'message' => 'Car assigned successfully',
    'car_reporting_marks' => $car_row['reporting_marks'],
    'car_code' => $car_row['car_code']
]);
?>
