<?php
/**
 * STS REST API - Test/Example Client
 * This file demonstrates how to use the REST API endpoints
 */

// Base API URL
$baseUrl = 'http://localhost/sts/api';

echo "STS REST API Test Examples\n";
echo str_repeat('=', 50) . "\n\n";

// Example 1: Get car details
echo "1. GET Car Details\n";
echo "URL: /wagon/cargo/id/104-F\n";
$example1 = [
    'method' => 'GET',
    'url' => $baseUrl . '/wagon/cargo/id/104-F',
    'response' => [
        'id' => 1,
        'reportingMarks' => '104-F',
        'carCode' => 'BOX',
        'status' => 'Empty',
        'shipmentCode' => '007-001',
        'loadingStation' => 'Port',
        'loadingLocation' => 'Loading Dock A',
        'unloadingStation' => 'Factory',
        'unloadingLocation' => 'Unloading Dock B'
    ]
];
echo "Response:\n" . json_encode($example1['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Example 2: Get cars at location
echo "2. GET Cars at Location\n";
echo "URL: /wagon/location/Port\n";
$example2 = [
    'method' => 'GET',
    'url' => $baseUrl . '/wagon/location/Port',
    'response' => [
        [
            'id' => 1,
            'reportingMarks' => '104-F',
            'carType' => 'BOX',
            'status' => 'Empty'
        ],
        [
            'id' => 2,
            'reportingMarks' => '205-A',
            'carType' => 'TANK',
            'status' => 'Loaded'
        ]
    ]
];
echo "Response:\n" . json_encode($example2['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Example 3: Unload wagon
echo "3. POST Unload Wagon\n";
echo "URL: /wagon/unload\n";
echo "Request Body:\n";
$example3 = [
    'method' => 'POST',
    'url' => $baseUrl . '/wagon/unload',
    'request' => [
        'wagonId' => 1,
        'reportingMarks' => '104-F'
    ],
    'response' => [
        'message' => 'Wagon unload request processed successfully'
    ]
];
echo json_encode($example3['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "Response:\n" . json_encode($example3['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Example 4: Load wagon
echo "4. POST Load Wagon\n";
echo "URL: /wagon/load\n";
echo "Request Body:\n";
$example4 = [
    'method' => 'POST',
    'url' => $baseUrl . '/wagon/load',
    'request' => [
        'wagonId' => 1,
        'reportingMarks' => '104-F'
    ],
    'response' => [
        'message' => 'Wagon load request processed successfully'
    ]
];
echo json_encode($example4['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "Response:\n" . json_encode($example4['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Example 5: Reposition wagon
echo "5. POST Reposition Wagon\n";
echo "URL: /wagon/reposition\n";
echo "Request Body:\n";
$example5 = [
    'method' => 'POST',
    'url' => $baseUrl . '/wagon/reposition',
    'request' => [
        'wagonId' => 1,
        'reportingMarks' => '104-F',
        'locationId' => 5
    ],
    'response' => [
        'message' => 'Wagon reposition request processed successfully'
    ]
];
echo json_encode($example5['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "Response:\n" . json_encode($example5['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo str_repeat('=', 50) . "\n";
echo "For more details, see README.md\n";
?>
