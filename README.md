
# STS Docker

A containerised fork of the Shipper-Driven Traffic Simulator (STS) model railway operations software. Rather than requiring a local XAMPP or WAMP stack to be installed on your machine, everything — PHP 8, Apache, and MariaDB — runs inside Docker containers.

## Getting Started — Using the pre-built image

1. **Install Docker.** On Mac, open Docker Desktop (find it in Applications) or install it via Homebrew:
   ```
   brew install docker
   ```
2. **Clone this repo:**
   ```
   git clone https://github.com/aaron9589/sts-docker
   cd sts-docker
   ```
3. **Edit `docker-compose.yml`.** Find the `web_prebuilt` section and update the settings marked with comments to suit your setup (port, database name, etc.).
4. **Start the containers:**
   ```
   docker compose up --profile prebuild
   ```
5. **Open your browser** and go to `http://localhost:8980/sts/` (or whichever port you mapped).
6. **Important:** Once everything is working, set `PROVISION_DATABASE` to `0` in `docker-compose.yml` so your database is not wiped on the next update.

## Building your own image

Follow steps 1–3 above, then run:
```
docker compose up --profile build --build
```

---

## Application overview

STS is organised into five main areas accessible from the home screen. Here is what each section does and what has changed in this Docker fork compared to the original XAMPP-based release.

---

### Operations

The day-to-day workflow for running a session. Accessible from the **Operations** button on the home screen.

| Page | What it does | Changes in this fork |
|---|---|---|
| **Generate Car Orders** | Start a new session — auto or manually select shipments to generate car orders for. | UI modernised to Bootstrap 5 with a clean, responsive layout. |
| **Fill Car Orders** | Assign available rolling stock to open car orders. Cars are colour-ranked by pool membership, proximity to shipper, and priority locations. | UI modernised to Bootstrap 5. Car status shown with colour-coded badges (see below). AJAX used to fetch available cars without a full page reload. |
| **Reposition Empty Cars** | Move empty wagons back to their home location between sessions. | No functional changes. |
| **Build Switch Lists** | Assign cars to specific jobs/trains, station by station or via auto-assign. | UI modernised to Bootstrap 5. AJAX used for car assignment — the page no longer reloads on each change. |
| **Pick Up Cars** | Record that a job has physically picked up its assigned cars. | No functional changes. |
| **Organize Cars** | View and adjust the order of cars within a consist. | No functional changes. |
| **Set Out Cars** | Record cars being set out at their destination. | No functional changes. |
| **Load / Unload Cars** | Mark wagons as loaded or unloaded at a location. Completing an unload removes the car order and returns the wagon to Empty status. | Available via the REST API (see below) for use with RFID readers or phone apps. |

**Car status colour codes** used throughout the Operations pages:

| Colour | Status |
|---|---|
| 🟡 Yellow | Empty |
| 🟢 Green | Loaded |
| 🔵 Blue | Loading |
| 🟠 Orange | Unloading |
| ⬜ Grey | Ordered |
| 🔴 Red | Unavailable |

---

### Reports

Printable and on-screen reports for the layout. Accessible from the **Reports** button on the home screen.

| Page | What it does | Changes in this fork |
|---|---|---|
| **Switch Lists** | Generate a printable switch list for a job. | New **X2010** format added alongside Mobile, Half Sheet, and Work Order. The default format has been changed from Mobile to **Half Sheet**. |
| **Waybills** | Generate car waybills for a session. | No functional changes. |
| **Fleet Report** | Overview of all rolling stock and their current status. | No functional changes. |
| **Station Report** | Shows all cars currently at a given station, with their status and order details. | Report now generates inline on the same page — no separate printable page required. |
| **CC Waybill** | Conductor's copy waybill report. | No functional changes. |
| **Shipment Forecast** | Projects future shipment demand. | No functional changes. |
| **Car Forecast** | Projects future car requirements by car code. | No functional changes. |
| **Wheel Report** | Summary of car movements and load counts. | No functional changes. |
| **Car QR Codes** | Printable QR code labels for each car. | No functional changes. |
| **Station QR Codes** | Printable QR code labels for stations/locations. | No functional changes. |

---

### Database Management

View and edit the underlying data for your layout. Accessible from the **DB Manage** button on the home screen.

| Page | What it does | Changes in this fork |
|---|---|---|
| **Cars** | List and edit all rolling stock. Add new cars, edit car codes, status, and remarks. | Fully modernised with Bootstrap 5. Sticky table header, inline cell editing (no page reload), and colour-coded status badges. |
| **Scan Car** | Look up a car by reporting marks or QR/barcode scan to view its details or jump to its edit page. | Fixed: the "Edit car" link now correctly navigates to the car's edit page (was broken in the original due to a missing parameter). |
| **Car Orders** | View open car orders (waybills) in the database. | No functional changes. |
| **Jobs** | Add, rename, or delete job/train routes and their station steps. | Fixed: job names with mixed-case or spaces now work correctly (the original was incorrectly forcing all job table names to lowercase). |
| **Shipments** | Manage shipment definitions — origin, destination, commodity, and car code requirements. | No functional changes. |
| **Scan Location** | Look up all cars currently at a location by scanning a location QR code. | No functional changes. |
| **Locations** | Add and edit individual spots/tracks within a station. | No functional changes. |
| **Routing** | Manage stations and the routing connections between them. | No functional changes. |
| **Car Codes** | Manage the list of car type codes (e.g. MHGX, RKWF). | No functional changes. |
| **Commodities** | Manage the list of commodities that can be shipped. | No functional changes. |
| **Backup DB** | Download a backup of the database. | No functional changes. |
| **Restore DB** | Upload and restore a previously backed-up database. | UI modernised to Bootstrap 5. |
| **Import Tables** | Import data tables from CSV files. | No functional changes. |

---

### Database Maintenance

Tools for keeping the database healthy. Accessible from the **DB Maint** button on the home screen.

| Page | What it does | Changes in this fork |
|---|---|---|
| **Validate DB** | Check the database for inconsistencies and orphaned records. | Fixed: job name lookup no longer incorrectly lowercases names, preventing false validation errors. |
| **Restart Session** | Reset the current session number to restart operations. | No functional changes. |
| **Reset DB** | Reset operational data while keeping your layout configuration. | No functional changes. |
| **Wipe DB** | Wipe the entire database back to a blank state. | Fixed: same job name case fix as Validate DB. |
| **Settings** | Configure layout-wide settings such as railroad name and print width. | No functional changes. |

---

### REST API *(new in this fork)*

A JSON REST API has been added so that external tools — RFID readers, phone apps, or layout automation software — can interact with STS programmatically without screen-scraping the web pages. All five endpoints are tested and working.

**Base URL:** `http://localhost:8980/sts/api/index.php`

| Endpoint | Method | What it does |
|---|---|---|
| `/wagon/cargo/id/:tag` | GET | Look up a wagon by reporting marks, RFID code, or car ID |
| `/wagon/location/:location` | GET | List all wagons currently at a given location |
| `/wagon/unload` | POST | Mark a wagon as loaded or empty (mirrors the Load/Unload page) |
| `/wagon/reposition` | POST | Reposition an empty wagon and create an E-series waybill |
| `/wagon/details` | GET | Return full car and order details |

See [`sts/api/README.md`](sts/api/README.md) for full request/response documentation and curl examples.

> **Security note:** The API has no authentication layer. It is designed for use on a private home network. Do not expose the port publicly without first adding authentication.

---

## Docker-specific changes

These are changes that are specific to running STS inside Docker rather than under XAMPP/WAMP.

- **No local web server needed.** PHP 8.0, Apache, and MariaDB all run inside Docker containers. Nothing extra needs to be installed on the host machine beyond Docker itself.
- **Database configured via environment variables.** The original STS hardcoded the database hostname, username, password, and database name in `credentials.php`. This fork reads those from Docker environment variables (`MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`) set in `docker-compose.yml`. Hardcoded defaults are kept as a fallback.
- **Automatic directory setup.** The Dockerfile creates the required `temp/`, `backups/`, `uploads/`, and `ImageStore/` directories and sets the correct permissions automatically on first run.
- **Database provisioning flag.** Set `PROVISION_DATABASE=1` in `docker-compose.yml` to create a fresh database on startup. Set it back to `0` after first run to prevent accidentally wiping your data on container restarts.