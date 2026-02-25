<?php
// get_available_cars_ajax.php - Fetch available cars for a car order (waybill)

require 'open_db.php';

header('Content-Type: application/json');

if (!isset($_GET['waybill_number'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing waybill_number']);
    exit;
}

$waybill_number = $_GET['waybill_number'];
$dbc = open_db();

// First, get the shipment and car code details
$sql = 'SELECT car_orders.shipment as shipment_id,
               shipments.code as shipment,
               shipments.description as description,
               shipments.consignment as consignment_id,
               shipments.car_code as car_code_id,
               shipments.loading_location as loading_location_id,
               shipments.unloading_location as unloading_location_id,
               shipments.remarks as remarks,
               commodities.code as consignment,
               car_codes.code as car_code,
               sta01.station as loading_station,
               loc01.code as loading_location,
               sta02.station as unloading_station,
               loc02.code as unloading_location
        FROM car_orders
        LEFT JOIN shipments ON shipments.id = car_orders.shipment
        LEFT JOIN commodities ON commodities.id = shipments.consignment
        LEFT JOIN car_codes ON car_codes.id = shipments.car_code
        LEFT JOIN locations loc01 ON loc01.id = shipments.loading_location
        LEFT JOIN locations loc02 ON loc02.id = shipments.unloading_location
        LEFT JOIN routing sta01 ON sta01.id = loc01.station
        LEFT JOIN routing sta02 ON sta02.id = loc02.station
        WHERE car_orders.waybill_number = "' . mysqli_real_escape_string($dbc, $waybill_number) . '"';

$rs = mysqli_query($dbc, $sql);
if (mysqli_num_rows($rs) <= 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Waybill not found']);
    exit;
}

$order_row = mysqli_fetch_array($rs);
$shipment_id = $order_row['shipment_id'];
$car_code = $order_row['car_code'];
$loading_station = $order_row['loading_station'];
$shipment = $order_row['shipment'];

// Now get available cars using the four-tier system

// 1. Cars in the special shipment pool
$sql_pool = 'SELECT cars.reporting_marks as reporting_marks,
                    car_codes.code as car_code,
                    cars.id as car_id,
                    routing.station as current_station,
                    locations.code as current_location,
                    0 as priority,
                    cars.load_count as load_count,
                    cars.remarks as remarks
             FROM cars
             LEFT JOIN pool ON cars.id = pool.car_id
             LEFT JOIN locations ON locations.id = cars.current_location_id
             LEFT JOIN routing ON routing.id = locations.station
             LEFT JOIN car_codes ON car_codes.id = cars.car_code_id
             WHERE cars.status = "Empty"
               AND cars.id NOT IN (SELECT car FROM car_orders)
               AND car_codes.code LIKE REPLACE("' . mysqli_real_escape_string($dbc, $car_code) . '", "*", "%")
               AND pool.car_id = cars.id
               AND pool.shipment_id = "' . $shipment_id . '"
             ORDER BY cars.load_count';

$rs_pool = mysqli_query($dbc, $sql_pool);
$pool_cars = [];
while ($row = mysqli_fetch_array($rs_pool)) {
    $pool_cars[] = array_merge($row, ['category' => 'pool']);
}

// 2. Cars at the loading station
$sql_station = 'SELECT cars.reporting_marks as reporting_marks,
                       car_codes.code as car_code,
                       cars.id as car_id,
                       routing.station as current_station,
                       locations.code as current_location,
                       0 as priority,
                       cars.load_count as load_count,
                       cars.remarks as remarks
                FROM cars
                LEFT JOIN locations ON locations.id = cars.current_location_id
                LEFT JOIN routing ON routing.id = locations.station
                LEFT JOIN car_codes ON car_codes.id = cars.car_code_id
                WHERE cars.status = "Empty"
                  AND cars.id NOT IN (SELECT car FROM car_orders)
                  AND cars.id NOT IN (SELECT car_id FROM pool)
                  AND car_codes.code LIKE REPLACE("' . mysqli_real_escape_string($dbc, $car_code) . '", "*", "%")
                  AND cars.current_location_id IN (SELECT locations.id
                                                   FROM locations, routing
                                                   WHERE locations.station = routing.id AND routing.station = "' . mysqli_real_escape_string($dbc, $loading_station) . '")
                ORDER BY priority, cars.load_count';

$rs_station = mysqli_query($dbc, $sql_station);
$station_cars = [];
while ($row = mysqli_fetch_array($rs_station)) {
    $station_cars[] = array_merge($row, ['category' => 'station']);
}

// 3. Cars at prioritized locations
$sql_priority = 'SELECT cars.reporting_marks as reporting_marks,
                        cars.id as car_id,
                        car_codes.code as car_code,
                        routing.station as current_station,
                        locations.code as current_location,
                        empty_locations.priority as priority,
                        cars.load_count as load_count,
                        cars.remarks as remarks
                 FROM (cars, empty_locations, shipments)
                 LEFT JOIN locations ON locations.id = cars.current_location_id
                 LEFT JOIN routing ON routing.id = locations.station
                 LEFT JOIN car_codes ON car_codes.id = cars.car_code_id
                 WHERE cars.status = "Empty"
                   AND cars.id NOT IN (SELECT car FROM car_orders)
                   AND cars.id NOT IN (SELECT car_id FROM pool)
                   AND car_codes.code LIKE REPLACE("' . mysqli_real_escape_string($dbc, $car_code) . '", "*", "%")
                   AND cars.current_location_id = empty_locations.location
                   AND empty_locations.shipment = shipments.id
                   AND shipments.code = "' . mysqli_real_escape_string($dbc, $shipment) . '"
                   AND cars.current_location_id NOT IN (SELECT locations.id
                                                    FROM locations, routing
                                                    WHERE locations.station = routing.id AND routing.station = "' . mysqli_real_escape_string($dbc, $loading_station) . '")
                 ORDER BY priority, cars.load_count';

$rs_priority = mysqli_query($dbc, $sql_priority);
$priority_cars = [];
while ($row = mysqli_fetch_array($rs_priority)) {
    $priority_cars[] = array_merge($row, ['category' => 'priority']);
}

// 4. All remaining eligible cars
$sql_system = 'SELECT DISTINCT cars.reporting_marks as reporting_marks,
                              cars.id as car_id,
                              car_codes.code as car_code,
                              routing.station as current_station,
                              locations.code as current_location,
                              0 as priority,
                              cars.load_count as load_count,
                              cars.remarks as remarks
               FROM cars
               LEFT JOIN locations ON locations.id = cars.current_location_id
               LEFT JOIN routing ON routing.id = locations.station
               LEFT JOIN car_codes ON car_codes.id = cars.car_code_id
               WHERE cars.status = "Empty"
                 AND cars.id NOT IN (SELECT car FROM car_orders)
                 AND cars.id NOT IN (SELECT car_id FROM pool)
                 AND car_codes.code LIKE REPLACE("' . mysqli_real_escape_string($dbc, $car_code) . '", "*", "%")
                 AND cars.reporting_marks NOT IN
                 (SELECT cars.reporting_marks
                  FROM cars
                  WHERE cars.status = "Empty"
                    AND car_codes.code LIKE REPLACE("' . mysqli_real_escape_string($dbc, $car_code) . '", "*", "%")
                    AND cars.current_location_id IN (SELECT locations.id
                                                     FROM locations, routing
                                                     WHERE locations.station = routing.id AND routing.station = "' . mysqli_real_escape_string($dbc, $loading_station) . '")
                                                     UNION
                                                     SELECT cars.reporting_marks
                                                     FROM (cars, empty_locations)
                                                     WHERE cars.status = "Empty"
                                                       AND car_codes.code LIKE REPLACE("' . mysqli_real_escape_string($dbc, $car_code) . '", "*", "%")
                                                       AND cars.current_location_id = empty_locations.location
                                                       AND empty_locations.shipment = "' . $shipment . '"
                                                       AND cars.current_location_id NOT IN (SELECT locations.id
                                                                                      FROM locations, routing
                                                                                      WHERE locations.station = routing.id AND routing.station = "' . mysqli_real_escape_string($dbc, $loading_station) . '"))
               ORDER BY priority, cars.load_count';

$rs_system = mysqli_query($dbc, $sql_system);
$system_cars = [];
while ($row = mysqli_fetch_array($rs_system)) {
    $system_cars[] = array_merge($row, ['category' => 'system']);
}

mysqli_close($dbc);

// Combine all cars
$all_cars = array_merge($pool_cars, $station_cars, $priority_cars, $system_cars);

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
    'pool_count' => count($pool_cars),
    'station_count' => count($station_cars),
    'priority_count' => count($priority_cars),
    'system_count' => count($system_cars),
    'cars' => $all_cars
]);
?>
