<?php
/**
 * STS REST API - Endpoint Reference
 *
 * Base URL:  http://<host>/sts/api/index.php
 * All responses are JSON. All POST bodies must be JSON (Content-Type: application/json).
 * CORS: Access-Control-Allow-Origin: * is set on all responses.
 *
 * ============================================================
 * ENDPOINTS OVERVIEW
 * ============================================================
 *
 * GET  /wagon/cargo/id/:tag       - Look up a wagon by reporting marks or RFID
 * GET  /wagon/location/:name      - List all wagons at a location
 * POST /wagon/load                - Mark a Loading wagon as Loaded
 * POST /wagon/unload              - Mark an Unloading wagon as Empty
 * POST /wagon/reposition          - Reposition an Empty wagon to a new location
 *
 * ============================================================
 * STATUS TRANSITIONS
 * ============================================================
 *
 *  Loading   --[/wagon/load]-->       Loaded
 *  Unloading --[/wagon/unload]-->     Empty   (car_orders deleted)
 *  Empty     --[/wagon/reposition]--> Ordered (E-series waybill created)
 *
 * ============================================================
 * ERROR RESPONSES
 * ============================================================
 *
 * All errors return:  { "error": "<message>" }
 *
 *   400  Bad Request  - Missing required fields
 *   404  Not Found    - Car / location not found, or car in wrong status
 *   405  Not Allowed  - Wrong HTTP method for endpoint
 *   500  Server Error - Database error
 */

// Base API URL (update host/port as needed)
$baseUrl = 'http://<host>/sts/api/index.php';

echo "STS REST API - Endpoint Reference\n";
echo str_repeat('=', 60) . "\n\n";


// ============================================================
// 1. GET /wagon/cargo/id/:tag
// ============================================================
echo "1. GET /wagon/cargo/id/:tag\n";
echo str_repeat('-', 60) . "\n";
echo "Look up a single wagon by reporting marks or RFID code.\n";
echo "The :tag is matched case-insensitively against reporting_marks,\n";
echo "or case-sensitively against RFID_code.\n";
echo "Wrap a numeric car ID in dashes (e.g. -180-) to look up by internal ID.\n\n";

echo "URL: GET /wagon/cargo/id/1702M\n";
echo "URL: GET /wagon/cargo/id/-180-       (look up by internal car ID 180)\n\n";

$cargoWithOrder = [
    'reportingMarks'   => '1702M',
    'id'               => '180',
    'carCode'          => 'MGFH',
    'status'           => 'Unloading',
    'shipmentCode'     => '007-001',
    'loadingStation'   => 'Narranderra',
    'loadingLocation'  => 'Narranderra Grain',
    'unloadingStation' => 'Shoalhaven Starches',
    'unloadingLocation'=> 'Flour Dump Road',
];
echo "Response (200) - wagon with active order:\n";
echo json_encode($cargoWithOrder, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$cargoEmpty = [
    'reportingMarks'   => '131-C',
    'id'               => '5',
    'carCode'          => 'MHGX',
    'status'           => 'Empty',
    'shipmentCode'     => '',
    'loadingStation'   => '',
    'loadingLocation'  => '',
    'unloadingStation' => '',
    'unloadingLocation'=> '',
];
echo "Response (200) - wagon with no active order (Empty/Ordered):\n";
echo json_encode($cargoEmpty, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Error (404):  { \"error\": \"Car not found\" }\n\n\n";


// ============================================================
// 2. GET /wagon/location/:name
// ============================================================
echo "2. GET /wagon/location/:name\n";
echo str_repeat('-', 60) . "\n";
echo "List all wagons currently at a location, ordered by position then reporting marks.\n";
echo "URL-encode spaces in the location name (e.g. Flour%20Dump%20Road).\n";
echo "Wrap a numeric location ID in percent signs (e.g. %12%) to look up by internal ID.\n\n";

echo "URL: GET /wagon/location/Flour%20Dump%20Road\n";
echo "URL: GET /wagon/location/%12%        (look up by internal location ID 12)\n\n";

$locationResponse = [
    ['reportingMarks' => '1702M', 'carType' => 'MGFH', 'status' => 'Unloading'],
    ['reportingMarks' => '1704H', 'carType' => 'MGFH', 'status' => 'Unloading'],
    ['reportingMarks' => '1709G', 'carType' => 'MGFH', 'status' => 'Unloading'],
    ['reportingMarks' => '1712U', 'carType' => 'MGFH', 'status' => 'Empty'],
];
echo "Response (200) - flat array (empty array [] if no wagons present):\n";
echo json_encode($locationResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Error (404):  { \"error\": \"Location not found\" }\n\n\n";


// ============================================================
// 3. POST /wagon/load
// ============================================================
echo "3. POST /wagon/load\n";
echo str_repeat('-', 60) . "\n";
echo "Mark a wagon as Loaded.\n";
echo "The wagon must currently have status 'Loading'. On success status becomes 'Loaded'.\n\n";

echo "URL: POST /wagon/load\n";
echo "Headers: Content-Type: application/json\n\n";

$loadRequest = [
    'wagonId'        => '180',   // string or int accepted
    'reportingMarks' => '1702M',
];
echo "Request body (both fields required):\n";
echo json_encode($loadRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$loadResponse = [
    'message'        => 'Wagon load completed',
    'carId'          => 180,
    'reportingMarks' => '1702M',
    'newStatus'      => 'Loaded',
];
echo "Response (200):\n";
echo json_encode($loadResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Error (400):  { \"error\": \"Missing required fields: wagonId, reportingMarks\" }\n";
echo "Error (404):  { \"error\": \"Car not found or does not have Loading/Unloading status\" }\n\n\n";


// ============================================================
// 4. POST /wagon/unload
// ============================================================
echo "4. POST /wagon/unload\n";
echo str_repeat('-', 60) . "\n";
echo "Mark a wagon as Empty (unloaded).\n";
echo "The wagon must currently have status 'Unloading'.\n";
echo "On success status becomes 'Empty' and all car_orders for this wagon are deleted.\n\n";

echo "URL: POST /wagon/unload\n";
echo "Headers: Content-Type: application/json\n\n";

$unloadRequest = [
    'wagonId'        => '180',
    'reportingMarks' => '1702M',
];
echo "Request body (both fields required):\n";
echo json_encode($unloadRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$unloadResponse = [
    'message'        => 'Wagon unload completed',
    'carId'          => 180,
    'reportingMarks' => '1702M',
    'newStatus'      => 'Empty',
];
echo "Response (200):\n";
echo json_encode($unloadResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Error (400):  { \"error\": \"Missing required fields: wagonId, reportingMarks\" }\n";
echo "Error (404):  { \"error\": \"Car not found or does not have Loading/Unloading status\" }\n\n\n";


// ============================================================
// 5. POST /wagon/reposition
// ============================================================
echo "5. POST /wagon/reposition\n";
echo str_repeat('-', 60) . "\n";
echo "Reposition an empty wagon to a new location.\n";
echo "The wagon must:\n";
echo "  - have status = 'Empty'\n";
echo "  - have no existing car_orders\n";
echo "On success an E-series waybill is created (e.g. 009-E01) and status is set to 'Ordered'.\n";
echo "A history record is also inserted for the repositioning event.\n\n";

echo "URL: POST /wagon/reposition\n";
echo "Headers: Content-Type: application/json\n\n";

$repositionRequest = [
    'wagonId'        => '180',
    'reportingMarks' => '1702M',
    'locationId'     => '21',   // internal ID of destination location
];
echo "Request body (all three fields required):\n";
echo json_encode($repositionRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$repositionResponse = [
    'message'             => 'Wagon repositioned successfully',
    'carId'               => 180,
    'reportingMarks'      => '1702M',
    'waybillNumber'       => '009-E01',
    'destinationLocation' => 'Narranderra',
    'newStatus'           => 'Ordered',
];
echo "Response (200):\n";
echo json_encode($repositionResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "Error (400):  { \"error\": \"Missing required fields: wagonId, reportingMarks, locationId\" }\n";
echo "Error (404):  { \"error\": \"Car not available for repositioning (must be Empty with no orders)\" }\n";
echo "Error (404):  { \"error\": \"Destination location not found\" }\n\n\n";


// ============================================================
// KNOWN LOCATION IDs
// ============================================================
echo str_repeat('=', 60) . "\n";
echo "KNOWN LOCATION IDs (verify against your database)\n";
echo str_repeat('=', 60) . "\n";
$locations = [
    ['id' => 21, 'code' => 'Narranderra',       'note' => 'Narranderra grain loading'],
    ['id' => 22, 'code' => 'Manildra',           'note' => 'Manildra grain loading'],
    ['id' => 12, 'code' => 'Flour Dump Road',    'note' => 'Shoalhaven Starches unloader'],
    ['id' => 13, 'code' => 'Intermodal Sidings', 'note' => 'Container loading terminal'],
];
foreach ($locations as $loc) {
    printf("  ID %3d  %-25s  %s\n", $loc['id'], $loc['code'], $loc['note']);
}
echo "\n";
?>
