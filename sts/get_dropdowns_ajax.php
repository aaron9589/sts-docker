<?php
// get_dropdowns_ajax.php - Fetch dropdown options for inline editing

require 'open_db.php';

header('Content-Type: application/json');

$dbc = open_db();

// Get car codes
$sql_codes = 'SELECT id, code FROM car_codes ORDER BY code';
$rs_codes = mysqli_query($dbc, $sql_codes);
$carCodes = [];
while ($row = mysqli_fetch_array($rs_codes)) {
  $carCodes[] = [
    'id' => $row['id'],
    'code' => $row['code']
  ];
}

// Get locations with their stations
$sql_locs = 'SELECT locations.id, locations.code, routing.station
             FROM locations
             LEFT JOIN routing ON locations.station = routing.id
             ORDER BY routing.station, locations.code';
$rs_locs = mysqli_query($dbc, $sql_locs);
$locations = [];
while ($row = mysqli_fetch_array($rs_locs)) {
  $locations[] = [
    'id' => $row['id'],
    'location' => $row['code'],
    'station' => $row['station'] ?: 'Unknown'
  ];
}

// Get jobs
$sql_jobs = 'SELECT id, name FROM jobs ORDER BY name';
$rs_jobs = mysqli_query($dbc, $sql_jobs);
$jobs = [];
while ($row = mysqli_fetch_array($rs_jobs)) {
  $jobs[] = [
    'id' => $row['id'],
    'name' => $row['name']
  ];
}

mysqli_close($dbc);

echo json_encode([
  'carCodes' => $carCodes,
  'locations' => $locations,
  'jobs' => $jobs
]);
?>
