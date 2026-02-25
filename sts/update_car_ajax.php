<?php
// update_car_ajax.php - Handles AJAX updates for car attributes

require 'open_db.php';

header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get the JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['car_id']) || !isset($input['field']) || !isset($input['value'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$car_id = $input['car_id'];
$field = $input['field'];
$value = $input['value'];

// Whitelist of allowed fields to update
$allowed_fields = [
    'reporting_marks',
    'car_code_id',
    'current_location_id',
    'position',
    'status',
    'handled_by_job_id',
    'remarks',
    'home_location',
    'RFID_code'
];

// Validate field name
if (!in_array($field, $allowed_fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid field']);
    exit;
}

$dbc = open_db();

// Build and execute update query
$field_escaped = mysqli_real_escape_string($dbc, $field);
$value_escaped = mysqli_real_escape_string($dbc, $value);
$car_id_escaped = mysqli_real_escape_string($dbc, $car_id);

$sql = "UPDATE cars SET $field_escaped = '$value_escaped' WHERE id = '$car_id_escaped'";

if (mysqli_query($dbc, $sql)) {
    // If status changed to Empty or Unavailable, apply db_edit logic
    if ($field === 'status' && ($value === 'Empty' || $value === 'Unavailable')) {
        // Remove from any job/train
        $sql_job = "UPDATE cars SET handled_by_job_id = 0 WHERE id = '$car_id_escaped'";
        mysqli_query($dbc, $sql_job);

        // Delete any car orders linked to this car
        $sql_orders = "DELETE FROM car_orders WHERE car = '$car_id_escaped'";
        mysqli_query($dbc, $sql_orders);
    }

    // Get the display value for the field
    $displayValue = $value;
    $formatAsHtml = false;
    if ($field === 'car_code_id') {
        $sql_display = "SELECT code FROM car_codes WHERE id = '$value'";
        $rs_display = mysqli_query($dbc, $sql_display);
        if ($row_display = mysqli_fetch_array($rs_display)) {
            $displayValue = $row_display['code'];
        }
    } elseif ($field === 'current_location_id') {
        if ($value > 0) {
            $sql_display = "SELECT locations.code, routing.station FROM locations LEFT JOIN routing ON locations.station = routing.id WHERE locations.id = '$value'";
            $rs_display = mysqli_query($dbc, $sql_display);
            if ($row_display = mysqli_fetch_array($rs_display)) {
                $station = $row_display['station'] ?: 'Unknown';
                $displayValue = "<u>" . $station . "</u><br />" . $row_display['code'];
                $formatAsHtml = true;
            }
        }
    } elseif ($field === 'home_location') {
        $sql_display = "SELECT locations.code, routing.station FROM locations LEFT JOIN routing ON locations.station = routing.id WHERE locations.id = '$value'";
        $rs_display = mysqli_query($dbc, $sql_display);
        if ($row_display = mysqli_fetch_array($rs_display)) {
            $station = $row_display['station'] ?: 'Unknown';
            $displayValue = "<u>" . $station . "</u><br />" . $row_display['code'];
            $formatAsHtml = true;
        }
    } elseif ($field === 'handled_by_job_id') {
        if ($value > 0) {
            $sql_display = "SELECT name FROM jobs WHERE id = '$value'";
            $rs_display = mysqli_query($dbc, $sql_display);
            if ($row_display = mysqli_fetch_array($rs_display)) {
                $displayValue = $row_display['name'];
            }
        } else {
            $displayValue = '';
        }
    }

    // Return the updated value with display text
    echo json_encode([
        'success' => true,
        'field' => $field,
        'value' => $value,
        'displayValue' => $displayValue,
        'formatAsHtml' => $formatAsHtml,
        'message' => 'Field updated successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . mysqli_error($dbc)
    ]);
}

mysqli_close($dbc);
?>
