# STS REST API Documentation

## Overview
The STS REST API provides programmatic access to wagon/car management operations within the Short Lines Railway Operations System. All endpoints mirror the functionality of the existing web interface pages (scan_car.php, scan_location.php, load_unload.php, reposition.php) without the need for web scraping or intermediary pages.

**Status**: ✅ All 5 endpoints tested and working

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
http://localhost:8980/sts/api/index.php
```

## Response Format
All responses are JSON formatted.

### Successful Response
```json
{
  "data": {...}
  // or for operations
  "message": "Operation description",
  "carId": 1,
  ...
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
  "carCode": "MHGX",
  "carCodeId": 5,
  "status": "Loaded",
  "rfidCode": "041AF3987005",
  "remarks": "76t | 14.6m",
  "loadCount": 8,
  "currentLocation": "Gunnedah",
  "currentLocationId": 13,
  "currentStation": "Staging",
  "waybillNumber": "005-007",
  "shipmentCode": "Gunnedah Flour",
  "consignment": "Flour",
  "loadingLocation": "Gunnedah",
  "loadingLocationId": 13,
  "loadingStation": "Staging",
  "unloadingLocation": "Flour Dump Road",
  "unloadingLocationId": 17,
  "unloadingStation": "Shoalhaven<br>Starches"
}
```

**Example:**
```bash
curl http://localhost:8980/sts/api/index.php/wagon/cargo/id/104-F
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
    "id": 13,
    "code": "Gunnedah",
    "stationId": 0,
    "station": "Staging",
    "track": "",
    "spot": "",
    "remarks": ""
  },
  "cars": [
    {
      "position": 0,
      "reportingMarks": "104-F",
      "carCode": "MHGX",
      "status": "Loaded"
    },
    {
      "position": 0,
      "reportingMarks": "107-J",
      "carCode": "MHGX",
      "status": "Loaded"
    }
  ]
}
```

**Example:**
```bash
curl http://localhost:8980/sts/api/index.php/wagon/location/Gunnedah
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
  "wagonId": 64,
  "reportingMarks": "2230W",
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
  "carId": 64,
  "reportingMarks": "2230W",
  "newStatus": "Empty"
}
```

**Example:**
```bash
curl -X POST http://localhost:8980/sts/api/index.php/wagon/unload \
  -H "Content-Type: application/json" \
  -d '{
    "wagonId": 64,
    "reportingMarks": "2230W",
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
  "wagonId": 65,
  "reportingMarks": "2240H",
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
  "message": "Wagon load completed",
  "carId": 65,
  "reportingMarks": "2240H",
  "newStatus": "Empty"
}
```

**Example:**
```bash
curl -X POST http://localhost:8980/sts/api/index.php/wagon/load \
  -H "Content-Type: application/json" \
  -d '{
    "wagonId": 65,
    "reportingMarks": "2240H",
    "status": "Unloading"
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
  "wagonId": 64,
  "reportingMarks": "2230W",
  "locationId": 2
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
  "carId": 64,
  "reportingMarks": "2230W",
  "waybillNumber": "007-E01",
  "destinationLocation": "Chillfreeze Logistics | Intermodal Siding",
  "newStatus": "Ordered"
}
```

**Example:**
```bash
curl -X POST http://localhost:8980/sts/api/index.php/wagon/reposition \
  -H "Content-Type: application/json" \
  -d '{
    "wagonId": 64,
    "reportingMarks": "2230W",
    "locationId": 2
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

---

## Testing & Validation

### Test Coverage
All 5 endpoints have been tested and verified:

| Endpoint | Method | Test Car | Status | Result |
|----------|--------|----------|--------|--------|
| Get Car Details | GET | 104-F | Loaded | ✅ PASS - Returns full car details with order and station info |
| Get Location Cars | GET | Gunnedah | - | ✅ PASS - Returns location with 11+ cars at location |
| Unload Wagon | POST | 2230W | Unloading→Empty | ✅ PASS - Status changed, car_orders deleted |
| Load Wagon | POST | 2240H | Unloading→Empty | ✅ PASS - Status changed, car_orders deleted |
| Reposition Car | POST | 2230W (after unload) | Empty→Ordered | ✅ PASS - E-series waybill created (007-E01), location recorded |

### Database Validation
- ✅ Car status transitions verified in database
- ✅ Car orders properly deleted on unload operations
- ✅ E-series waybill numbers generated and persisted
- ✅ Destination location correctly stored in `car_orders.shipment` field
- ✅ All referential integrity constraints maintained

### Request/Response Format
- ✅ All requests use `Content-Type: application/json`
- ✅ All responses return valid JSON
- ✅ Error responses include descriptive error messages
- ✅ Success responses include operation details and new state
