# STS v2 — SvelteKit rebuild

A ground-up rebuild of the Shipper-Driven Traffic Simulator in SvelteKit +
TypeScript + SQLite. All business rules were captured from the legacy PHP app
into [docs/SPEC.md](../docs/SPEC.md) — that document is the source of truth for
behaviour (waybill numbering, car status lifecycle, four-tier order filling,
auto-assign criteria, etc).

## Run with Docker (recommended)

From the repository root:

```bash
docker compose --profile v2 up --build
```

Then open http://localhost:8980/. No database container is needed — the whole
database is a single SQLite file stored in the mounted data volume
(`~/sts-data/sts.db` by default). Adjust the port, `ORIGIN` and volume path in
`docker-compose.yml`.

## Import your existing data

One-time migration from the legacy app. Two sources are supported:

**From a backup .sql file** (the file downloaded by the legacy "Backup DB" page):

```bash
cd app
npm install
npm run import-legacy -- --file ~/Downloads/sts-backup.sql ~/sts-data/sts.db
```

**From the live MariaDB database** (run while the old `db` container is up,
with its port reachable):

```bash
cd app
npm install
MYSQL_HOST=127.0.0.1 MYSQL_PORT=3306 MYSQL_USER=sts_user \
MYSQL_PASSWORD=sts_password MYSQL_DATABASE=sts_db3 \
npm run import-legacy -- ~/sts-data/sts.db
```

Restart the v2 container afterwards so it picks up the imported file.

The importer converts the legacy schema (per-job step tables, E-waybill
location-in-shipment-column quirk, zero sentinels) into the new normalized
schema and reports any orphaned legacy rows for the Validate DB page.

## Development

```bash
npm install
npm run dev        # dev server on :5173, database in ./data/sts.db
npm run check      # typecheck
npm run build      # production build (adapter-node) into ./build
```

Environment variables: `STS_DATA_DIR` (default `data`), `STS_DB_PATH`
(overrides the full path), `PORT`, `ORIGIN` (required in production for form
submissions).

## Layout

- `src/lib/server/schema.sql` — SQLite schema (applied automatically on boot)
- `src/lib/server/operations.ts` — all state-changing workflows (the legacy
  state machine, transactional)
- `src/lib/server/queries.ts` — read-side queries for pages and reports
- `src/routes/operations|reports|data|maint` — the four app sections
- `src/routes/api/wagon/*` — REST API; legacy `/sts/api/index.php/...` paths
  are rerouted here so existing RFID/phone clients keep working
- `scripts/import-legacy.ts` — MariaDB → SQLite migration
