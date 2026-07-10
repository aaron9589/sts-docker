---
title: STS v2 App Conventions & Pitfalls
purpose:
  Server-code layout, schema semantics, and known footguns for the SvelteKit
  rebuild in app/.
load_when:
  Writing or reviewing any code under app/ — routes, server modules, schema, or
  the legacy importer.
owner: shared
last_updated: 10/07/2026
---

# STS v2 App Conventions & Pitfalls

`docs/SPEC.md` is the source of truth for all business rules — every server
function cites its SPEC section and the legacy PHP file it reimplements. When
changing behaviour, cite the SPEC section; when the SPEC is wrong, fix the SPEC
in the same PR.

## Server module map (`app/src/lib/server/`)

| Module             | Role                                                                                                      |
| ------------------ | --------------------------------------------------------------------------------------------------------- |
| `operations.ts`    | **All state-changing workflows**, one function per legacy page, each wrapped in a `db.transaction()`      |
| `queries.ts`       | Read-side queries; `CAR_ROW_SELECT` is the shared car-detail SELECT reused by most operations pages       |
| `entities.ts`      | Config-driven CRUD definitions driving the generic `/data/[entity]` editor (table + columns + field defs) |
| `api.ts`           | REST helpers mirroring `sts/api/endpoints/wagon.php`                                                      |
| `import-backup.ts` | Legacy `.sql` backup parser + importer, shared by `scripts/import-legacy.ts` and `/maint/restore`         |
| `reports.ts`       | Forecast/history/waybill data builders                                                                    |
| `db.ts`            | Singleton connection, settings helpers, waybill counters, `randInt`/`randAmount`                          |

New writes belong in `operations.ts` inside a transaction; new reads in
`queries.ts`. Route `+page.server.ts` files stay thin (parse form → call server
module).

## Schema semantics that differ from legacy

- **NULL replaces legacy `0` sentinels**: `cars.current_location_id` NULL = in a
  train, `handled_by_job_id` NULL = unassigned, `car_orders.car_id` NULL =
  unfilled order. The importer maps `0 → NULL` (`nz()`). Never write `0` into
  these columns.
- **E-waybills have their own column**: `car_orders.destination_location_id`
  replaces the legacy overloaded `shipment` field. A row is revenue
  (`shipment_id` set) or reposition (`destination_location_id` set); a CHECK
  requires at least one. Test reposition-ness with
  `destination_location_id IS NOT NULL`, not the waybill string —
  `isRepositionWaybill()` (`charAt(4) === 'E'`) exists only for the importer and
  legacy-format parsing.
- **`job_steps` is one table** (no per-job dynamic tables); `pu_criteria.job_id`
  is a real FK (legacy stored the job _name_ — importer resolves it).
- **Schema migration = rerunning `schema.sql` on boot.** `db()` executes it on
  every startup, so everything in it must be idempotent
  (`CREATE TABLE IF NOT EXISTS`, `INSERT OR IGNORE`). Adding a column to an
  existing table needs an explicit migration step — `CREATE IF NOT EXISTS` won't
  alter existing installs.

## Known footguns

- **`setSetting()` is UPDATE-only and silently no-ops on unknown keys.** Any new
  setting key must be seeded in `schema.sql`'s `INSERT OR IGNORE` block, or
  written with an upsert (`/maint/+page.server.ts` has a local `upsertSetting`
  for the dynamic `logo_data_<n>` keys).
- **One-order-per-car is an invariant enforced only by query filters** (fill and
  reposition both exclude cars that already hold an order) — there is no UNIQUE
  constraint on `car_orders.car_id`. Every `LEFT JOIN car_orders` (in
  `CAR_ROW_SELECT`, `api.ts`, reports) fans out into duplicate rows if a car
  ever ends up on two orders. Any new write path that assigns a car to an order
  must preserve this invariant.
- **Backup and restore are asymmetric**: `/maint/backup/download` streams a
  serialized SQLite `.db` file, but `/maint/restore` only accepts a _legacy_
  `.sql` backup. Restoring a v2 `.db` backup means replacing `sts.db` in the
  data volume by hand.
- **Random-math parity is deliberate and inconsistent by design**: order
  generation uses `randAmount()` (the legacy ×100 rounding trick); the forecast
  reports use plain `randInt()` because the legacy forecast did. The load/unload
  "suggested" checkbox is a deliberate _divergence_ — legacy re-rolled `rand()`
  per render (flickery); v2 uses the deterministic
  `last_spotted + min_time <= session`.
- **Waybill letter counters take the numeric MAX of the suffix**, not a lexical
  ORDER BY ('M9' sorts after 'M10' lexically) — see
  `nextLetterWaybillCounter()`. Formats: `SSS-NNN` auto, `SSS-MNN` manual,
  `SSS-ENN` reposition.
- **Legacy API paths are rerouted in `app/src/hooks.ts`**:
  `/sts/api(/index.php)?/wagon/... → /api/wagon/...` so existing RFID/phone
  clients keep working. Don't rename `/api/wagon/*` routes without updating the
  reroute.
- **`ORIGIN` env var is required in production** for SvelteKit form actions (set
  in `docker-compose.yml` to the browsed-to URL); `BODY_SIZE_LIMIT=64M` exists
  so legacy backup uploads fit through the restore page.

## Importer notes (`import-backup.ts`)

Imports run with `PRAGMA foreign_keys = OFF` (legacy data has orphans), then
report `PRAGMA foreign_key_check` results as orphan counts for the Validate
page. Legacy quirks handled: per-job step tables → `job_steps`,
`pu_criteria.job_id` names → ids (stale names skipped with a warning), E-waybill
detection via `waybill.charAt(4) === 'E'` splitting `shipment` into
`shipment_id`/`destination_location_id`, `0` sentinels → NULL, MariaDB
column-name case (`Id`/`Code` vs `id`/`code`) per table.

## CI

`.github/workflows/app-ci.yml` runs `npm run check` (svelte-check/typecheck) and
`npm run build` on `app/**` changes. There is no test suite — typecheck and
build are the only gates, so behaviour changes need manual verification against
SPEC.md.
