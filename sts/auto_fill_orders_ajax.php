<?php
// auto_fill_orders_ajax.php — assign the first eligible car to each open car order

require 'open_db.php';
require 'fill_order_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$dbc = open_db();
$waybills = fill_order_get_unfilled_waybills($dbc);

$filled = [];
$skipped = [];

foreach ($waybills as $waybill_number) {
    $order_row = fill_order_get_details($dbc, $waybill_number);
    if ($order_row === null) {
        $skipped[] = [
            'waybill_number' => $waybill_number,
            'reason' => 'Order not found',
        ];
        continue;
    }

    $available_cars = fill_order_get_available_cars($dbc, $order_row);
    if (count($available_cars) === 0) {
        $skipped[] = [
            'waybill_number' => $waybill_number,
            'reason' => 'No eligible cars',
        ];
        continue;
    }

    $first_car = $available_cars[0];
    $result = fill_order_assign_car($dbc, $waybill_number, $first_car['car_id']);
    if (!$result['success']) {
        $skipped[] = [
            'waybill_number' => $waybill_number,
            'reason' => $result['error'],
        ];
        continue;
    }

    $filled[] = [
        'waybill_number' => $waybill_number,
        'car_id' => $first_car['car_id'],
        'reporting_marks' => $result['car_reporting_marks'],
        'car_code' => $result['car_code'],
    ];
}

$remaining = fill_order_get_unfilled_waybills($dbc);
mysqli_close($dbc);

echo json_encode([
    'success' => true,
    'filled_count' => count($filled),
    'skipped_count' => count($skipped),
    'remaining_count' => count($remaining),
    'all_filled' => count($remaining) === 0,
    'filled' => $filled,
    'skipped' => $skipped,
]);

?>
