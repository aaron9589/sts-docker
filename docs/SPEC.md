# STS Rebuild Specification

Business logic captured from the legacy PHP application (`sts/`), to be reimplemented in
SvelteKit + TypeScript + SQLite. This document is the source of truth for the rebuild —
every rule below was extracted from the working PHP code, with file references for audit.

## 1. Domain overview

STS (Shipper-Driven Traffic Simulator) generates freight car movements for a model
railroad. Shippers (shipments) periodically order cars; the operator fills orders by
assigning empty cars, builds switchlists assigning cars to jobs (trains), and moves cars
through a pickup → set-out → load/unload cycle across operating **sessions**.

### Glossary

| Term | Meaning |
|---|---|
| Session | Operating session number, a monotonically increasing integer (`settings.session_nbr`) |
| Station | A named point on the railroad (`routing` table), with sort order and optional routing instructions |
| Location | A spot/track at a station (`locations`), where cars sit. `cars.current_location_id = 0` means **in a train** |
| Shipment | A recurring traffic pattern: commodity, car type, loading location → unloading location, with random interval/amount |
| Car order / waybill | A demand for one car for a shipment (`car_orders`), keyed by waybill number; `car = 0` means unfilled |
| Job | A train/switch job with an ordered list of station steps (pickup/setout flags) |
| Reposition (E-waybill) | A non-revenue move sending an empty car to a destination location. **`car_orders.shipment` holds a location id, not a shipment id, for E-waybills** |

## 2. Data model

Legacy schema (MariaDB) → new schema (SQLite). Modernizations are listed in §10.

### stations (legacy `routing`)
- `id` PK, `name` (legacy `station`), `default_setout_location_id` (legacy `station_nbr` — see §5.6), `instructions` text, `sort_seq` int, `color1` int, `color2` int (palette indexes used by `set_colors.php`).

### locations
- `id` PK, `code`, `station_id` (legacy column `station` → `routing.id`), `track`, `spot`, `rpt_station` (alternate station name printed on waybills), `remarks`, `color` (css color name/hex; overrides station palette).

### car_codes
- `id` PK, `code`, `description`, `remarks`. Shipment car codes may contain `*` as wildcard (matched as SQL `LIKE` with `%`).

### commodities
- `id` PK, `code`, `description`, `remarks`.

### cars
- `id` PK, `reporting_marks`, `car_code_id`, `current_location_id` (0 = in train), `position` int (sequence at a location or within a train), `status` (see §3), `handled_by_job_id` (0 = none), `remarks`, `load_count` int, `home_location_id`, `rfid_code`, `block_id` (unused), `last_spotted` int (session number when spotted for loading/unloading).

### shipments
- `id` PK, `code`, `description`, `consignment` (commodity id), `car_code` (car_code id; code may be wildcard), `loading_location` (location id), `unloading_location` (location id), `last_ship_date` (session number), `min_interval`, `max_interval`, `min_amount`, `max_amount`, `min_load_time`, `max_load_time`, `min_unload_time`, `max_unload_time`, `special_instructions`, `remarks`.
- Load/unload time semantics: blank → 0; **negative → instantaneous** (skip Loading/Unloading state, §5.5).

### car_orders
- `waybill_number` PK (text), `shipment` int, `car` int (0/empty = unfilled).
- For E-waybills `shipment` is a **location id** (the reposition destination).

### jobs + job_steps
- Legacy: `jobs (id, name, description)` plus **one dynamically created table per job** named after the job: `(step_number int PK, station int, pickup char(1) 'T'/'F', setout char(1), remarks)` (created in `db_list_jobs.php`, renamed on job rename, dropped on delete).
- New: single `job_steps (job_id, step_number, station_id, pickup bool, setout bool, remarks)` table. This removes the dynamic-DDL design entirely.

### pu_criteria (auto-assign pickup criteria)
- `id` PK, `job_id` — **legacy quirk: stores the job NAME string, not the id** (normalize to a real FK in the new schema), `step_nbr`, `car_status` ('' = any; 'Loaded' or 'Empty'), `commodity_id`, `car_code_id`, `dest_station_id` ('' / non-numeric = any).

### pool
- `(car_id, shipment_id)` — cars dedicated to a shipment ("special pool"). Pool cars are offered first when filling that shipment's orders and excluded from other tiers.

### empty_locations
- `(shipment, priority, location)` — prioritized locations a shipment prefers to draw empties from (tier 3 in §5.2).

### owners / ownership
- `owners (id, name, remarks)`; `ownership (car_id, owner_id, on_off_rr)`.
- `on_off_rr` = `'on'` when the car is on the railroad; otherwise it stores the car's **saved status** while the car is off the railroad (status set to `Unavailable`). Restoring puts the saved status back. (`add_remove.php`)

### history
- `(car_id, session_nbr, event_date datetime, event text, location int)` — location is the location **id where the event happened** (before movement). Capped per car by setting `max_history` (default 24); oldest (lowest session) rows pruned on DB open in legacy — in the rebuild, prune after insert.
- Event strings used: `Filled car order {wb}`, `Assigned to Job {name}`, `Picked up by Job {name}`, `Set out by Job {name}`, `Repositioned to {location code}`.

### settings
- Key/value: `session_nbr` (int as text), `railroad_name`, `railroad_initials`, `print_width` (default `7.5in`), `max_history` (default 24).

## 3. Car status lifecycle

Statuses: `Empty`, `Ordered`, `Loading`, `Loaded`, `Unloading`, `Unavailable`.

```
                 assign to revenue order, car NOT at loading loc
   Empty ───────────────────────────────────────────────► Ordered
     │  assign to revenue order, car already at loading loc │
     │  (status → Loaded directly, load_count+1)            │ set out at loading loc
     │                                                      ▼
     │                                                   Loading ──(complete load)──► Loaded
     │                                                                                  │ set out at unloading loc
     ▼                                                                                  ▼
   (reposition: E-waybill created, status → Ordered;                                Unloading
    set out at destination → Empty, order deleted)              (complete unload, order deleted) → Empty
```

Rules (sources: `assign_car_ajax.php`, `set_out.php`, `load_unload.php`, `reposition.php`):
- **Assign car to revenue order**: set `car_orders.car`; `load_count += 1`; if car is `Empty` **and already at the shipment's loading location** → status `Loaded`, else status `Ordered`. History `Filled car order {wb}`.
- **Set out** (per car, after location update): if `Ordered` and now at loading location → `Loading`, `last_spotted = session`. If `Loaded` and now at unloading location → `Unloading`, `last_spotted = session`. If `Ordered` on an E-waybill and now at the destination location (`car_orders.shipment = current_location_id`) → `Empty` and delete the car order.
- **Instant load/unload**: after the above, if status became `Loading` and the shipment's `min_load_time < 0 || max_load_time < 0` → immediately `Loaded`, `last_spotted = 0`. If `Unloading` and `min_unload_time < 0 || max_unload_time < 0` → immediately `Empty`, `last_spotted = 0`, delete car order.
- **Complete load** (`load_unload.php` UPDATE): `Loading` → `Loaded`. **Complete unload**: `Unloading` → `Empty` + delete car order. A row shown with status `Empty` (E-waybill car at destination) → stays `Empty` + delete car order. All set `last_spotted = 0`.
- **Unavailable**: set when a car is taken off the railroad; excluded from restart/reset status resets; previous status is preserved in `ownership.on_off_rr`.

## 4. Waybill numbering

All formats use the session number left-padded to 3 with zeros:
- **Automatic generation**: `SSS-NNN` — NNN is a per-generation-run counter starting at 1, padded to 3 (`generate.php`).
- **Manual generation**: `SSS-MNN` — NN continues from `max(substr(waybill_number,6,2))` among existing `SSS-M__` orders for this session (`generate.php`).
- **Reposition (non-revenue)**: `SSS-ENN` — NN continues from the highest existing `SSS-E__` for the session, else 1 (`reposition.php`, `repo_to_home.php`).
- **E-waybill detection** in legacy is `substr(waybill_number, 4, 1) == 'E'` (5th char) or `instr(waybill_number, 'E')`. New code should test position 5 / pattern `^\d{3}-E\d{2}$`.

## 5. Operations workflows

### 5.1 Generate car orders (`generate.php`)
- **Automatic**: increment `session_nbr` and save. For each shipment: `interval = round(rand(min_interval*100, max_interval*100)/100)`; if `last_ship_date + interval <= session` → set `last_ship_date = session`, order `n = round(rand(min_amount*100, max_amount*100)/100)` cars, each an unfilled order (`car = 0`) with an auto waybill number.
- **Manual**: user checks shipments from a filterable list; same `n` computation per checked shipment; M-waybills; `last_ship_date = session`.

### 5.2 Fill car orders (`fill_orders.php`, `get_available_cars_ajax.php`, `assign_car_ajax.php`)
- Open orders: `car_orders` where `car = '' or car is null` (treat as `car = 0`), one expandable card per order; orders whose shipment has pool cars are flagged "Pool".
- Eligible cars, in **four ranked tiers** (each tier sorted by ascending `load_count`; a car appears in only its highest tier):
  1. **Pool** — `pool` rows for this shipment.
  2. **Station** — empty cars already at any location of the loading station.
  3. **Priority** — empty cars at `empty_locations` rows for this shipment (ordered by `priority` then load_count), excluding tier 2 cars.
  4. **System** — all remaining eligible empties.
- Eligibility for all tiers: `status = 'Empty'`, not already on any order, car code matches shipment car code (`*` → `%` LIKE), and (tiers 2–4) not in any pool.
- Assignment: §3 rules.

### 5.3 Build switchlists (`build_switchlists.php`, `get_cars_at_station.php`, `auto_assign.php`)
- Station-by-station: list cars at the chosen station with `status IN ('Ordered','Loaded')` and `handled_by_job_id = 0`, grouped by location. Per car, offer a dropdown of jobs that have a step at this station with `pickup = 'T'`. Assigning sets `handled_by_job_id` and writes history `Assigned to Job {name}`.
- Display rules per row: destination column shows loading location (bold/colored) when `Ordered`; unloading location (bold/colored) when `Loaded`; for E-waybills the destination is looked up from the order's location id, consignment shows `Non-Revenue`, loading shows `N/A`.
- **Auto-assign** (per job): for each `pu_criteria` step of the job, find cars at the step's station matching the criteria, where the car's *next destination* lies in the criteria's destination station (any if unset):
  - `Ordered` + non-E waybill → next destination = shipment loading location.
  - `Loaded` → next destination = shipment unloading location.
  - `Ordered` + E waybill → next destination = order's destination location.
  - User reviews the pickup list (all checked by default) and confirms; each checked car gets `handled_by_job_id = job`.

### 5.4 Pick up (`pick_up.php`, `get_cars_position_in_job.php`)
- Lists cars with `handled_by_job_id = job` (at stations on the job's route). Checked cars get `current_location_id = 0` (in train). Position is **not** reset (preserves user organization). History `Picked up by Job {name}` with the pre-pickup location.

### 5.5 Set out (`set_out.php`, `get_cars_in_job.php`)
- Lists cars with `handled_by_job_id = job and current_location_id = 0`. Per car, a dropdown of set-out locations = all locations at stations on the job's route with `setout = 'T'`, ordered by station `sort_seq`; plus "KEEP IN TRAIN" (no action).
- "Default locations only" mode: restrict each station's options to the location where `locations.id = stations.default_setout_location_id`.
- A bulk "set all locations to X" helper applies a value to every row whose dropdown contains it.
- On SET OUT, per car: update `current_location_id`, `handled_by_job_id = 0`, `position = 0`; history `Set out by Job {name}` (with the **new** location id); then run the status transitions in §3 (including instant load/unload).

### 5.6 Load / unload (`load_unload.php`)
- Page lists: `status IN ('Loading','Unloading')` plus `Empty` cars sitting at their E-waybill destination. Order: Loading, Unloading, Empty; then station `sort_seq`, location, position, marks.
- **Pre-check suggestion**: a car's checkbox is pre-checked when `last_spotted + rand(min_time, max_time) <= session` (load times for Loading, unload times for Unloading; blank times → 0).
- UPDATE applies §3 completion rules to checked cars.

### 5.7 Reposition empties (`reposition.php`, `repo_to_home.php`)
- Lists `Empty` cars with no car order, grouped by home station/location; rows where current ≠ home highlighted. Selecting a destination creates an E-waybill (`shipment` = destination **location id**), sets status `Ordered`, history `Repositioned to {code}`.
- **Reposition to home**: same, in bulk, for every empty unordered car with `current_location_id != home_location`, destination = home location. Returns the count.

### 5.8 Organize cars (`organize_cars.php`, `get_job_cars.php`, `get_location_cars.php`, `update_car_positions.php`)
- Two views: cars in a job (in-train, `current_location_id = 0`) or cars at a location. Drag-and-drop reorder; saving writes `cars.position` = 1..n by car **id**.

## 6. Reports

All reports honor settings `railroad_name`, `railroad_initials`, `print_width`, and print in a compact monospace style (landscape, Courier New ≤7pt in the legacy print CSS — the rebuild keeps print-first styling).

- **Station car report** (`display_station_report.php`): for one station or all stations (ordered by `sort_seq`). Optional "hide Unavailable". Two sections: (a) *Cars to be Picked Up by Service* — cars with a `handled_by` job, grouped by job, one print page per job, destination = loading loc (Ordered) / unloading loc (Loading/Loaded/Unloading) / E-destination; (b) *All Cars On Hand* — every car at the station grouped by location with full detail (screen only).
- **Switchlist** (`printable_switchlist.php`, formats: mobile/half/full/dot-matrix/work-order/X2010): cars with `handled_by_job_id = job`; revenue rows show consignment + loading/unloading; E-rows show destination from the order's location id; ordered by `position, step_number, current_station, current_location, unloading_location, reporting_marks`; counts loads vs empties.
- **Waybill** (`display_waybill.php` → `printable_waybill.php`): printable only for orders whose car has `current_location_id > 0` (not enroute). If the car's current station ≠ loading station, an **empty-car waybill section is auto-generated** directing the car to the loading station first; `rpt_station` substitutes the printed station name when set. E-waybills print as empty-car waybills only.
- **Car-card waybills** (`build_cc_waybill.php`, `build_cc_mtybill.php`, `printable_ccwaybill*.php`): card-format variants of the same data.
- **Wheel report** (`wheel_report.php`): per job, all cars it handles with pickup location and destination.
- **Car forecast** (`car_forecast.php`): simulates the next 10 sessions per shipment using `interval = rand(min_interval, max_interval)`, `amount = rand(min_amount, max_amount)` (note: legacy forecast uses plain rand, not the ×100 trick), grouped/subtotaled by car code with column totals.
- **Shipment forecast** (`shipment_forecast.php`): same simulation, sorted by loading location + shipment code.
- **Car history** (`car_history.php`): a car's history rows, newest first.
- **Fleet report** (`display_fleet_report.php`): full car roster. **QR reports**: per-car and per-location QR code sheets (use the `phpqrcode`-equivalent; the rebuild can use a JS QR library).
- **Scan** (`scan_car.php`, `scan_location.php`): look up a car by marks/RFID/`-id-` token, or a location by code/`%id%` token, showing current order context. Mirrored by the REST API.

## 7. Data editors

Generic list + edit pages for: cars, car codes, commodities, locations, stations (routing), shipments, jobs (incl. steps + per-step auto-assign criteria), car orders, special pool, empty (priority) locations, owners/ownership. Plus `manage_cargo.php` (cargo_list.txt lines used for waybill LADING), rolling stock photo album with image upload (`ImageStore/DB_Images/RollingStock/{car_id}.jpg` — clicking reporting marks anywhere pops the photo).

## 8. Maintenance

- **Restart** (`restart.php`): `shipments.last_ship_date = 0`; delete all car orders; non-Unavailable cars → `Empty`, `handled_by_job_id = 0`, `last_spotted = 0`; cars with a home location → moved there (others stay; in-train cars without home need manual placement); `session_nbr = 0`.
- **Reset** (`reset.php`): Restart **plus** `load_count = 0` for all cars.
- **Wipe** (`wipe.php`): drop job step tables, truncate all data tables, reset settings (`session_nbr=0`, `print_width=7.5in`, blank railroad name/initials).
- **Validate** (`validate_db.php`): report ghost/orphan records — locations with missing stations, shipments with bad refs, cars with bad refs, orders pointing at missing cars, job rows without step tables and vice versa, `&` in reporting marks.
- **Backup/restore** (`backup_db.php`, `restore_db.php`, `backup_tables.php`): SQL dump download / upload-restore; with SQLite this becomes a file copy/download of the database (plus optional SQL export).
- **Import tables** (`import_tables.php`): CSV import per table.
- **Fleet add/remove** (`add_remove.php`): §2 owners/ownership semantics.
- Legacy `fix_*.php` one-off repair scripts: superseded by FK constraints + validate; not ported.

## 9. REST API (`sts/api/`)

JSON, no auth (home-network use). Keep paths compatible so existing RFID/phone clients work:
- `GET /wagon/cargo/id/:tag` — car by reporting marks (uppercased), RFID code, or `-id-` token; returns car + order + station/location detail.
- `GET /wagon/location/:name` — location by code or `%id%` token + cars present.
- `POST /wagon/load`, `POST /wagon/unload` — complete loading/unloading (waybill_number, reportingMarks, status as in §3); unload deletes the order.
- `POST /wagon/reposition` — create E-waybill (wagonId, reportingMarks, locationId), status → Ordered, history record.
- `GET /wagon/details` — full car/order detail.

## 10. Modernization decisions

1. **SQLite** single-file DB (`data/sts.db`), WAL mode; Docker volume holds the data dir. Backup = copy the file.
2. **Normalized schema**: `job_steps` table replaces per-job dynamic tables; `pu_criteria.job_id` becomes a real FK; FK constraints ON; `cars.current_location_id` nullable (NULL = in train) — importer maps 0 → NULL; same for `handled_by_job_id`.
3. **E-waybill destination** gets its own column: `car_orders.destination_location_id`, replacing the overloaded `shipment` field (importer splits on waybill pattern). `shipment_id` nullable.
4. **Transactions** around every multi-statement workflow (assign, set out, generate…), which the legacy app lacked.
5. **History pruning** after insert, not on every connection.
6. Keep: waybill number formats, status names, four-tier fill ranking, random interval/amount math (×100 rounding in generate), pre-check suggestion logic, instant load/unload on negative times, `rpt_station` substitution, `*` wildcards in shipment car codes.
7. **Importer**: one-time script reading a `mysqldump` of `sts_db3` (or live MariaDB connection) → SQLite, including converting each job's step table into `job_steps` rows and resolving `pu_criteria.job_id` names to ids.
8. UI: keep the established visual language (status badge colors, green operations navbar, print-first reports) from the existing steering doc, reimplemented as Svelte components.
