<?php
// get_available_cars_ajax.php - Fetch available cars for a car order (waybill)

require 'open_db.php';
require 'fill_order_helpers.php';

header('Content-Type: application/json');

if (!isset($_GET['waybill_number'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing waybill_number']);
    exit;
}

$waybill_number = $_GET['waybill_number'];
$dbc = open_db();

$order_row = fill_order_get_details($dbc, $waybill_number);
if ($order_row === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Waybill not found']);
    exit;
}

$all_cars = fill_order_get_available_cars($dbc, $order_row);

$pool_count = 0;
$station_count = 0;
$priority_count = 0;
$system_count = 0;
foreach ($all_cars as $car) {
    switch ($car['category']) {
        case 'pool':
            $pool_count++;
            break;
        case 'station':
            $station_count++;
            break;
        case 'priority':
            $priority_count++;
            break;
        default:
            $system_count++;
            break;
    }
}

mysqli_close($dbc);

echo json_encode([
    'shipment' => $order_row['shipment'],
    'description' => $order_row['description'],
    'consignment' => $order_row['consignment'],
    'car_code' => $order_row['car_code'],
    'loading_station' => $order_row['loading_station'],
    'loading_location' => $order_row['loading_location'],
    'unloading_station' => $order_row['unloading_station'],
    'unloading_location' => $order_row['unloading_location'],
    'remarks' => $order_row['remarks'],
    'total_cars_found' => count($all_cars),
    'pool_count' => $pool_count,
    'station_count' => $station_count,
    'priority_count' => $priority_count,
    'system_count' => $system_count,
    'cars' => $all_cars
]);

?>
