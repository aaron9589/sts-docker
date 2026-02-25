# STS REST API Documentation

## Overview
The STS REST API provides programmatic access to wagon/car management operations within the Short Lines Railway Operations System. All endpoints are accessible via `/sts/api/` prefix.

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

Returns detailed information about a car, including current status and orders.

**Parameters:**
- `tag_name` (required): Car reporting marks or car code

**Response:**
```json
{
  "id": 1,
  "reportingMarks": "104-F",
  "carCode": "BOX",
  "status": "Empty",
  "shipmentCode": "007-001",
  "loadingStation": "Port",
  "loadingLocation": "Loading Dock A",
  "unloadingStation": "Factory",
  "unloadingLocation": "Unloading Dock B"
}
```

**Example:**
```bash
curl http://localhost/sts/api/wagon/cargo/id/104-F
```

---

### 2. Get Cars at Location
**GET** `/wagon/location/:location_name`

Returns all cars currently located at a specific location.

**Parameters:**
- `location_name` (required): Location name or description

**Response:**
```json
[
  {
    "id": 1,
    "reportingMarks": "104-F",
    "carType": "BOX",
    "status": "Empty"
  },
  {
    "id": 2,
    "reportingMarks": "205-A",
    "carType": "TANK",
    "status": "Loaded"
  }
]
```

**Example:**
```bash
curl http://localhost/sts/api/wagon/location/Port
```

---

### 3. Unload Wagon
**POST** `/wagon/unload`

Marks a wagon/car as unloading at its current location.

**Request Body:**
```json
{
  "wagonId": 1,
  "reportingMarks": "104-F"
}
```

**Required Fields:**
- `wagonId` (integer): Car database ID
- `reportingMarks` (string): Car reporting marks

**Response:**
```json
{
  "message": "Wagon unload request processed successfully"
}
```

**Example:**
```bash
curl -X POST http://localhost/sts/api/wagon/unload \
  -H "Content-Type: application/json" \
  -d '{"wagonId": 1, "reportingMarks": "104-F"}'
```

---

### 4. Load Wagon
**POST** `/wagon/load`

Marks a wagon/car as loading.

**Request Body:**
```json
{
  "wagonId": 1,
  "reportingMarks": "104-F"
}
```

**Required Fields:**
- `wagonId` (integer): Car database ID
- `reportingMarks` (string): Car reporting marks

**Response:**
```json
{
  "message": "Wagon load request processed successfully"
}
```

**Example:**
```bash
curl -X POST http://localhost/sts/api/wagon/load \
  -H "Content-Type: application/json" \
  -d '{"wagonId": 1, "reportingMarks": "104-F"}'
```

---

### 5. Reposition Wagon
**POST** `/wagon/reposition`

Moves a wagon to a new location.

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
- `reportingMarks` (string): Car reporting marks
- `locationId` (integer): Target location ID

**Response:**
```json
{
  "message": "Wagon reposition request processed successfully"
}
```

**Example:**
```bash
curl -X POST http://localhost/sts/api/wagon/reposition \
  -H "Content-Type: application/json" \
  -d '{"wagonId": 1, "reportingMarks": "104-F", "locationId": 5}'
```

---

## Error Codes

| Status Code | Meaning |
|---|---|
| 200 | Success |
| 400 | Bad Request (invalid parameters or missing required fields) |
| 404 | Not Found (car, location, or endpoint not found) |
| 405 | Method Not Allowed (wrong HTTP method) |
| 500 | Internal Server Error |

## Common Errors

### Missing Required Fields
```json
{
  "error": "Missing required fields: wagonId, reportingMarks"
}
```

### Car Not Found
```json
{
  "error": "Car not found with provided ID and reporting marks"
}
```

### Location Not Found
```json
{
  "error": "Location not found"
}
```

---

## Notes
- All POST endpoints expect `Content-Type: application/json`
- Car IDs must match the reporting marks for security validation
- Location IDs reference the locations table in the database
