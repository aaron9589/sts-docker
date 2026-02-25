# STS REST API Documentation

## Overview
The STS REST API provides programmatic access to wagon/car management operations within the Short Lines Railway Operations System. All endpoints mirror the functionality of the existing web interface pages (scan_car.php, scan_location.php, load_unload.php, reposition.php) without the need for web scraping or intermediary pages.

## Business Logic
The API implements the exact validation and business rules from the existing application:

- **Cargo Lookup**: Searches by reporting marks (uppercase) or RFID code, returns full car and order details with station/location information
- **Location Lookup**: Returns location details and all cars at that location
- **Load/Unload**: Only cars with Loading or Unloading status can be operated on. Completing unload deletes car orders and changes status to Empty.
- **Reposition**: Only Empty cars with no existing orders appear for repositioning. Creates E-series waybills with session-based numbering.

## Authentication
Currently, the API has no authentication layer. Consider adding API key authentication in production environments.

## Base URL
```
http://localhost/sts/api
```

## Response Format
All responses are JSON formatted.

### Successful Response
```json
{
  "data": {...}
}
```

### Error Response
```json
{
  "error": "Error message describing what went wrong"
}
```

## Endpoints

### 1. Get Car Details by Tag/ID
**GET** `/wagon/cargo/id/:tag_name`

Returns comprehensive car information including current status, orders, and shipment details. Searches by reporting marks (will uppercase) or RFID code.

Can also accept car ID format delimited by "-" (e.g., "-123-") which will be converted to reporting marks.

**Parameters:**
- `tag_name` (required): Car reporting marks, RFID code, or car ID in format -ID-

**Response:**
```json
{
  "id": 1,
  "reportingMarks": "104-F",
  "carCode": "BOX",
  "carCodeId": 5,
  "status": "Empty",
  "rfidCode": "123456789",
  "remarks": "Needs inspection",
  "loadCount": 2,
  "currentLocation": "PORT1",
  "currentLocationId": 10,
  "currentStation": "Port Station",
  "waybillNumber": "007-001",
  "shipmentCode": "SHP001",
  "consignment": "General Cargo",
  "loadingLocation": "LOAD01",
  "loadingLocationId": 5,
  "loadingStation": "Factory",
  "unloadingLocation": "UNLD01",
  "unloadingLocationId": 15,
  "unloadingStation": "Distribution"
}
```

**Example:**
```bash
curl http://localhost/sts/api/wagon/cargo/id/104-F
```

---

### 2. Get Cars at Location
**GET** `/wagon/location/:location_name`

Returns location details and all cars currently at that location.

Can also accept location ID format delimited by "%" (e.g., "%123%") which will be converted to location code.

**Parameters:**
- `location_name` (required): Location code or location ID in format %ID%

**Response:**
```json
{
  "location": {
    "id": 10,
    "code": "PORT1",
    "stationId": 2,
    "station": "Port Station",
    "track": "1",
    "spot": "A",
    "remarks": "Main loading platform"
  },
  "cars": [
    {
      "position": 1,
      "reportingMarks": "104-F",
      "carCode": "BOX",
      "status": "Empty"
    },
    {
      "position": 2,
      "reportingMarks": "205-A",
      "carCode": "TANK",
      "status": "Loaded"
    }
  ]
}
```

**Example:**
```bash
curl http://localhost/sts/api/wagon/location/PORT1
```

---

### 3. Complete Wagon Unload
**POST** `/wagon/unload`

Completes the unloading process for a wagon. Only cars with Loading or Unloading status can be operated on (those that appear on the load_unload.php page).

**Behavior:**
- If status is "Loading": Changes to "Loaded", car orders remain
- If status is "Unloading": Changes to "Empty", car orders are deleted

**Request Body:**
```json
{
  "wagonId": 1,
  "reportingMarks": "104-F",
  "status": "Unloading"
}
```

**Required Fields:**
- `wagonId` (integer): Car database ID
- `reportingMarks` (string): Car reporting marks (for validation)
- `status` (string): Current status must be "Loading" or "Unloading"

**Response:**
```json
{
  "message": "Wagon unload completed",
  "carId": 1,
  "reportingMarks": "104-F",
  "newStatus": "Empty"
}
```

**Example:**
```bash
curl -X POST http://localhost/sts/api/wagon/unload \
  -H "Content-Type: application/json" \
  -d '{
    "wagonId": 1,
    "reportingMarks": "104-F",
    "status": "Unloading"
  }'
```

---

### 4. Complete Wagon Load
**POST** `/wagon/load`

Completes the loading process for a wagon. Uses identical validation and status transition logic as unload (both operate on the same set of cars).

**Behavior:**
- If status is "Loading": Changes to "Loaded", car orders remain
- If status is "Unloading": Changes to "Empty", car orders are deleted

**Request Body:**
```json
{
  "wagonId": 1,
  "reportingMarks": "104-F",
  "status": "Loading"
}
```

**Required Fields:**
- `wagonId` (integer): Car database ID
- `reportingMarks` (string): Car reporting marks (for validation)
- `status` (string): Current status must be "Loading" or "Unloading"

**Response:**
```json
{
  "message": "Wagon load completed",
  "carId": 1,
  "reportingMarks": "104-F",
  "newStatus": "Loaded"
}
```

**Example:**
```bash
curl -X POST http://localhost/sts/api/wagon/load \
  -H "Content-Type: application/json" \
  -d '{
    "wagonId": 1,
    "reportingMarks": "104-F",
    "status": "Loading"
  }'
```

---

### 5. Reposition Empty Car
**POST** `/wagon/reposition`

Repositions an empty car to a new location. Only Empty cars with no existing car_orders can be repositioned (those that appear on the reposition.php page).

Creates an E-series empty car waybill with session-based numbering (e.g., 007-E01), sets car status to "Ordered", and inserts a history record.

**Request Body:**
```json
{
  "wagonId": 1,
  "reportingMarks": "104-F",
  "locationId": 5
}
```

**Required Fields:**
- `wagonId` (integer): Car database ID
- `reportingMarks` (string): Car reporting marks (for validation)
- `locationId` (integer): Destination location ID

**Validation:**
- Car must have status = "Empty"
- Car must NOT have any existing car_orders
- Destination location must exist

**Response:**
```json
{
  "message": "Wagon repositioned successfully",
  "carId": 1,
  "reportingMarks": "104-F",
  "waybillNumber": "007-E01",
  "destinationLocation": "DEST01",
  "newStatus": "Ordered"
}
```

**Example:**
```bash
curl -X POST http://localhost/sts/api/wagon/reposition \
  -H "Content-Type: application/json" \
  -d '{
    "wagonId": 1,
    "reportingMarks": "104-F",
    "locationId": 5
  }'
```

---

## Error Codes

| Status Code | Meaning |
|---|---|
| 200 | Success |
| 400 | Bad Request (invalid parameters, missing required fields, or validation failure) |
| 404 | Not Found (car, location, or car not eligible for operation) |
| 405 | Method Not Allowed (wrong HTTP method) |
| 500 | Internal Server Error |

## Common Errors

### Missing Required Fields
```json
{
  "error": "Missing required fields: wagonId, reportingMarks"
}
```

### Car Not Eligible for Operation
```json
{
  "error": "Car not available for repositioning (must be Empty with no orders)"
}
```

### Car Not Found
```json
{
  "error": "Car not found or does not have Loading/Unloading status"
}
```

### Location Not Found
```json
{
  "error": "Destination location not found"
}
```

---

## Implementation Notes

- All car operations validate that the car ID matches the reporting marks to prevent accidental operations on wrong cars
- Status transitions follow the business rules: Loading→Loaded, Unloading→Empty (with order deletion)
- Car orders are only deleted when a car completes unloading (transitions to Empty status)
- Reposition operations create traceable history records with timestamps
- E-series waybill numbering increments per session and persists across API calls
- The API maintains complete referential integrity with the existing database schema

