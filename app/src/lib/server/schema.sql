-- STS rebuild schema (SQLite). See docs/SPEC.md §2 and §10.
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS stations (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  default_setout_location_id INTEGER,
  instructions TEXT,
  sort_seq INTEGER,
  color1 INTEGER,
  color2 INTEGER
);

CREATE TABLE IF NOT EXISTS locations (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL,
  station_id INTEGER NOT NULL REFERENCES stations(id),
  track TEXT,
  spot TEXT,
  rpt_station TEXT,
  remarks TEXT,
  color TEXT
);

CREATE TABLE IF NOT EXISTS car_codes (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL,
  description TEXT,
  remarks TEXT
);

CREATE TABLE IF NOT EXISTS commodities (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL,
  description TEXT,
  remarks TEXT
);

-- current_location_id NULL = car is in a train
-- handled_by_job_id NULL = not assigned to a job
CREATE TABLE IF NOT EXISTS cars (
  id INTEGER PRIMARY KEY,
  reporting_marks TEXT NOT NULL,
  car_code_id INTEGER NOT NULL REFERENCES car_codes(id),
  current_location_id INTEGER REFERENCES locations(id),
  position INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'Empty'
    CHECK (status IN ('Empty','Ordered','Loading','Loaded','Unloading','Unavailable')),
  handled_by_job_id INTEGER REFERENCES jobs(id),
  remarks TEXT,
  load_count INTEGER NOT NULL DEFAULT 0,
  home_location_id INTEGER REFERENCES locations(id),
  rfid_code TEXT,
  last_spotted INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS shipments (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL,
  description TEXT,
  consignment_id INTEGER REFERENCES commodities(id),
  car_code_id INTEGER REFERENCES car_codes(id),
  loading_location_id INTEGER REFERENCES locations(id),
  unloading_location_id INTEGER REFERENCES locations(id),
  last_ship_date INTEGER NOT NULL DEFAULT 0,
  min_interval INTEGER NOT NULL DEFAULT 1,
  max_interval INTEGER NOT NULL DEFAULT 1,
  min_amount INTEGER NOT NULL DEFAULT 1,
  max_amount INTEGER NOT NULL DEFAULT 1,
  -- negative load/unload time = instantaneous (skip Loading/Unloading state)
  min_load_time INTEGER NOT NULL DEFAULT 0,
  max_load_time INTEGER NOT NULL DEFAULT 0,
  min_unload_time INTEGER NOT NULL DEFAULT 0,
  max_unload_time INTEGER NOT NULL DEFAULT 0,
  special_instructions TEXT,
  remarks TEXT
);

-- Revenue orders have shipment_id set; reposition (E) orders have destination_location_id set.
-- car_id NULL = unfilled order.
CREATE TABLE IF NOT EXISTS car_orders (
  waybill_number TEXT PRIMARY KEY,
  shipment_id INTEGER REFERENCES shipments(id),
  destination_location_id INTEGER REFERENCES locations(id),
  car_id INTEGER REFERENCES cars(id),
  CHECK (shipment_id IS NOT NULL OR destination_location_id IS NOT NULL)
);

CREATE TABLE IF NOT EXISTS jobs (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL UNIQUE,
  description TEXT
);

CREATE TABLE IF NOT EXISTS job_steps (
  job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
  step_number INTEGER NOT NULL,
  station_id INTEGER NOT NULL REFERENCES stations(id),
  pickup INTEGER NOT NULL DEFAULT 1,
  setout INTEGER NOT NULL DEFAULT 1,
  remarks TEXT,
  PRIMARY KEY (job_id, step_number)
);

-- empty criteria column = match anything
CREATE TABLE IF NOT EXISTS pu_criteria (
  id INTEGER PRIMARY KEY,
  job_id INTEGER NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,
  step_nbr INTEGER NOT NULL,
  car_status TEXT,
  commodity_id INTEGER REFERENCES commodities(id),
  car_code_id INTEGER REFERENCES car_codes(id),
  dest_station_id INTEGER REFERENCES stations(id)
);

CREATE TABLE IF NOT EXISTS pool (
  car_id INTEGER NOT NULL REFERENCES cars(id) ON DELETE CASCADE,
  shipment_id INTEGER NOT NULL REFERENCES shipments(id) ON DELETE CASCADE,
  PRIMARY KEY (car_id, shipment_id)
);

CREATE TABLE IF NOT EXISTS empty_locations (
  shipment_id INTEGER NOT NULL REFERENCES shipments(id) ON DELETE CASCADE,
  priority INTEGER NOT NULL,
  location_id INTEGER NOT NULL REFERENCES locations(id),
  PRIMARY KEY (shipment_id, priority, location_id)
);

CREATE TABLE IF NOT EXISTS owners (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  remarks TEXT
);

-- on_off_rr = 'on' when the car is on the railroad; otherwise it stores the
-- car's saved status while the car is off the railroad (status = Unavailable).
CREATE TABLE IF NOT EXISTS ownership (
  car_id INTEGER NOT NULL REFERENCES cars(id) ON DELETE CASCADE,
  owner_id INTEGER NOT NULL REFERENCES owners(id) ON DELETE CASCADE,
  on_off_rr TEXT NOT NULL DEFAULT 'on',
  PRIMARY KEY (car_id, owner_id)
);

-- location = location id where the event happened (before movement)
CREATE TABLE IF NOT EXISTS history (
  id INTEGER PRIMARY KEY,
  car_id INTEGER NOT NULL REFERENCES cars(id) ON DELETE CASCADE,
  session_nbr INTEGER NOT NULL,
  event_date TEXT NOT NULL,
  event TEXT NOT NULL,
  location_id INTEGER
);
CREATE INDEX IF NOT EXISTS idx_history_car ON history(car_id, session_nbr);

CREATE TABLE IF NOT EXISTS settings (
  setting_name TEXT PRIMARY KEY,
  setting_desc TEXT NOT NULL,
  setting_value TEXT NOT NULL
);

INSERT OR IGNORE INTO settings (setting_name, setting_desc, setting_value) VALUES
  ('session_nbr', 'Session Number', '0'),
  ('railroad_name', 'Name of the railroad', ''),
  ('railroad_initials', 'Initials of the railroad', ''),
  ('print_width', 'Print Width', '7.5in'),
  ('max_history', 'Max History Entries Per Car', '24'),
  ('logo_data', 'Railroad Logo (base64 data URL)', ''),
  ('logo_patterns', 'Logo pattern rules (JSON)', '[]');

CREATE INDEX IF NOT EXISTS idx_cars_location ON cars(current_location_id);
CREATE INDEX IF NOT EXISTS idx_cars_job ON cars(handled_by_job_id);
CREATE INDEX IF NOT EXISTS idx_car_orders_car ON car_orders(car_id);
CREATE INDEX IF NOT EXISTS idx_locations_station ON locations(station_id);
