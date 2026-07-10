# STS v2 Hardening Plan

Follow-up work from a full code review of the v2 app (`app/`). Execute tasks in
order — Phase 2 (tests) intentionally lands before Phase 3 (behaviour changes)
so the tests protect them.

**Before starting any task, read `llm-wiki/v2-app-conventions.md`** (module map,
schema semantics, footguns). `docs/SPEC.md` is the source of truth for business
rules — never change behaviour it specifies without updating it in the same
commit.

**Verification for every task**: `cd app && npm run check && npm run build` must
pass. Work on a branch off `milestone/v2`, one task per commit.

Each task names the model it's sized for: **haiku** = mechanical, contained,
follow the instructions literally; **sonnet** = requires judgment about edge
cases and data migration.

---

## Phase 1 — cleanups (haiku)

### 1.1 Make `setSetting()` an upsert — haiku

`app/src/lib/server/db.ts`: `setSetting()` runs a bare `UPDATE`, which silently
does nothing when the key isn't already in the `settings` table.

- Change it to:
  `INSERT INTO settings (setting_name, setting_desc, setting_value) VALUES (?, ?, ?) ON CONFLICT(setting_name) DO UPDATE SET setting_value = excluded.setting_value`
- Add an optional third parameter `desc` defaulting to the setting name
  (`setting_desc` is NOT NULL). On conflict, do NOT overwrite the existing
  description.
- In `app/src/routes/maint/+page.server.ts`, delete the now-redundant local
  `upsertSetting` helper and call `setSetting(name, value, desc)` at its call
  sites.
- Acceptance: writing a brand-new key (e.g. `setSetting('x', '1')`) creates the
  row; the maint page's logo upload / pattern flows still typecheck.

### 1.2 Delete dead `suggestReady()` — haiku

`app/src/lib/server/operations.ts` exports `suggestReady()`, but nothing imports
it — `loadUnloadList()` in `queries.ts` implements the same rule inline. Delete
the function. Acceptance: `grep -r suggestReady app/src` returns nothing;
`npm run check` passes.

### 1.3 Wrap multi-statement writes in transactions — haiku

Two write paths run multiple statements without a transaction:

- `app/src/routes/data/[entity]/+page.server.ts` → `updatePool` action (DELETE +
  N INSERTs): wrap the delete+insert loop in
  `db().transaction(() => { ... })()`, matching the style used in
  `operations.ts`.
- `app/src/lib/server/operations.ts` → `repositionAllToHome()` calls
  `repositionCar()` once per car, each its own transaction. Wrap the loop in one
  outer transaction (better-sqlite3 transactions nest safely as savepoints).

Acceptance: `npm run check` passes; no behaviour change.

### 1.4 Pin GitHub Actions in app-ci.yml — haiku

`.github/workflows/app-ci.yml` uses floating tags (`actions/checkout@v4`,
`actions/setup-node@v4`); every other workflow in this repo pins actions to a
full commit SHA with a `# vX.Y.Z` comment (see
`.github/workflows/publish-docker.yaml` for the convention and the current
checkout pin). Pin both actions the same way, using the same checkout SHA as
publish-docker. Also bump `node-version` from 20 to 24 to match `app/Dockerfile`
(`node:24-bookworm-slim`).

---

## Phase 2 — test suite (sonnet)

### 2.1 Add a vitest suite for the operations state machine — sonnet

There are currently no tests; typecheck and build are the only CI gates, and the
state machine in `operations.ts` is exactly the kind of logic that regresses
silently.

- Add `vitest` as a devDependency and a `"test": "vitest run"` script.
- Make the tests hermetic: `db.ts` reads `STS_DB_PATH` at module load — set
  `STS_DB_PATH=:memory:` (or a temp file) in test setup **before** importing any
  server module. If import-order makes that fragile, refactor `db.ts` minimally
  (e.g. read the env var inside `db()`), nothing more.
- Seed a tiny fixture directly through the schema: 2 stations, 2 locations, 1
  car code, 1 commodity, 2 cars, 1 shipment, 1 job with steps.
- Cover, per SPEC.md §3–§5 (cite the section in each test name):
  - `assignCarToOrder`: Empty car already at loading location → `Loaded`;
    elsewhere → `Ordered`; `load_count` increments both ways.
  - `setOutCar`: Ordered at loading loc → `Loading` + `last_spotted`; Loaded at
    unloading loc → `Unloading`; E-waybill car at its destination → `Empty` and
    only the reposition order deleted.
  - Instant load/unload: negative `min_load_time` skips `Loading` → straight to
    `Loaded`; negative unload time → `Empty` + order deleted.
  - `completeLoadUnload`: all three status branches.
  - Waybill counters: `generateManual` / `repositionCar` produce `SSS-MNN` /
    `SSS-ENN`; counter after `M9` is `M10` (numeric, not lexical — SPEC §4).
  - `generateAutomatic`: bumps session, respects
    `last_ship_date + interval <= session`.
  - `restartSimulation`: statuses reset except `Unavailable`; home relocation;
    `resetLoadCounts` variant.
- Add `npm test` as a step in `.github/workflows/app-ci.yml` after
  `npm run check`.
- Keep it to one or two test files; no mocking frameworks — real in-memory
  SQLite is the point.

---

## Phase 3 — behaviour changes (sonnet)

### 3.1 Enforce one-order-per-car in the schema — sonnet

The invariant "a car holds at most one car order" is currently enforced only by
query filters; every `LEFT JOIN car_orders` (see `CAR_ROW_SELECT` in
`queries.ts`, `api.ts`, `reports.ts`) produces duplicate rows if it breaks.

- Add to `app/src/lib/server/schema.sql`:
  `CREATE UNIQUE INDEX IF NOT EXISTS idx_car_orders_one_per_car ON car_orders(car_id) WHERE car_id IS NOT NULL;`
- **Migration care** — `schema.sql` reruns on every boot, so this statement will
  throw at startup on an existing database that already contains duplicates,
  bricking the app. In `db()` (db.ts), before `exec(schemaSql)`, dedupe
  defensively: keep the lowest `waybill_number` per car, set `car_id = NULL` on
  the rest (the order returns to "unfilled" rather than being deleted — SPEC
  §5.2 treats unfilled orders as re-fillable).
- **Importer** — legacy MariaDB has no such constraint either. `importTables()`
  in `import-backup.ts` runs with FKs off and could insert duplicates; apply the
  same dedupe there and push a warning per affected car into `warnings` so the
  restore page surfaces it.
- Add a test (extends Phase 2 suite): importing/creating two orders for one car
  leaves exactly one filled order and one unfilled order, plus a warning.

### 3.2 Restore page accepts v2 `.db` backups — sonnet

`/maint/backup/download` produces a serialized SQLite `.db`, but
`/maint/restore` only parses legacy `.sql` backups — the app's own backups
cannot be restored through the UI.

- In `app/src/routes/maint/restore/+page.server.ts`, detect the upload type: a
  v2 backup starts with the 16-byte SQLite magic `SQLite format 3\0`; otherwise
  fall through to the existing legacy-.sql path unchanged.
- For a `.db` upload: open the uploaded bytes as a read-only better-sqlite3
  database (write to a temp file first, e.g. under `STS_DATA_DIR`), run
  `PRAGMA integrity_check` and verify the `cars` and `settings` tables exist;
  reject with a clear `fail(400, ...)` otherwise.
- Replace the live database atomically: `db.ts` needs a new exported
  `replaceDatabase(tempPath)` that closes the singleton connection, moves the
  file over `DB_PATH` (fs.renameSync — same directory, so atomic), clears the
  `_db` singleton so the next `db()` call reopens, then reruns the schema (which
  also applies any newer migrations to an older backup).
- Delete WAL/SHM sidecar files (`DB_PATH + '-wal'`, `'-shm'`) when swapping, or
  the old WAL will corrupt the restored file.
- Update `/maint/restore/+page.svelte` copy to say both formats are accepted,
  and report which path ran.
- Update the "Backup and restore are asymmetric" footgun bullet in
  `llm-wiki/v2-app-conventions.md` — it becomes false once this lands.
- Acceptance: download a backup from a running dev instance, wipe, restore it
  through the page, data returns; uploading a random binary file is rejected
  with a message, not a crash.

---

## Out of scope (reviewed, deliberately skipped)

- Auth on the REST API — no-auth is a documented design decision
  (private-network use, `sts/api/README.md`).
- `handleLoadUnload` rejecting `Empty` completions — matches legacy API
  behaviour exactly; not a bug.
- The forecast's plain `randInt` vs generate's `randAmount` — deliberate legacy
  parity, documented in the wiki page. Do not "fix".
