/**
 * Legacy backup import: parses the .sql file produced by the legacy app's
 * "Backup DB" page and loads it into the new schema, applying the
 * transformations from docs/SPEC.md §10. Shared by the import-legacy CLI
 * script and the Restore page in the web UI.
 *
 * Backup format: SQL statements separated by lines containing only '#';
 * inserts are single statements with every value double-quoted and
 * backslash-escaped. Column names are recovered from CREATE TABLE statements.
 */
import type { Database } from 'better-sqlite3';

export type TableData = Map<string, Record<string, unknown>[]>;

export function parseBackup(text: string): TableData {
	const columns = new Map<string, string[]>();
	const tables: TableData = new Map();

	const statements = text
		.split(/\r?\n#(?:\r?\n|$)/)
		.map((s) => s.trim())
		.filter((s) => s.length > 0);

	for (const stmt of statements) {
		const create = /^CREATE TABLE `((?:[^`]|``)+)`\s*\(([\s\S]*)\)[^)]*$/i.exec(stmt);
		if (create) {
			const name = create[1].replaceAll('``', '`');
			const cols: string[] = [];
			for (const line of create[2].split('\n')) {
				const col = /^\s*`([^`]+)`\s/.exec(line);
				if (col) cols.push(col[1]);
			}
			columns.set(name, cols);
			tables.set(name, []);
			continue;
		}

		const insert = /^insert into `((?:[^`]|``)+)`\s+values\s*\(([\s\S]*)\);?$/i.exec(stmt);
		if (insert) {
			const name = insert[1].replaceAll('``', '`');
			const cols = columns.get(name);
			if (!cols) continue;
			const values = parseValueList(insert[2]);
			tables.get(name)!.push(Object.fromEntries(cols.map((c, i) => [c, values[i] ?? ''])));
		}
		// drop table / SET / comments: ignored
	}
	return tables;
}

/** Parse `"a","b\'c",NULL,...` into an array of strings (NULL -> ''). */
function parseValueList(src: string): string[] {
	const values: string[] = [];
	let i = 0;
	while (i < src.length) {
		while (i < src.length && (src[i] === ',' || src[i] === ' ' || src[i] === '\n' || src[i] === '\r' || src[i] === '\t')) i++;
		if (i >= src.length) break;

		if (src[i] === '"' || src[i] === "'") {
			const quote = src[i++];
			let value = '';
			while (i < src.length) {
				const ch = src[i];
				if (ch === '\\' && i + 1 < src.length) {
					const next = src[i + 1];
					// Recognised C-style escapes; for anything else (including a
					// literal backslash, e.g. a Windows path) keep the backslash
					// so data isn't silently dropped.
					value +=
						next === 'n'
							? '\n'
							: next === 'r'
								? '\r'
								: next === 't'
									? '\t'
									: next === '0'
										? '\0'
										: next === '\\' || next === '"' || next === "'"
											? next
											: '\\' + next;
					i += 2;
				} else if (ch === quote) {
					i++;
					break;
				} else {
					value += ch;
					i++;
				}
			}
			values.push(value);
		} else {
			let token = '';
			while (i < src.length && src[i] !== ',') token += src[i++];
			token = token.trim();
			values.push(token.toUpperCase() === 'NULL' ? '' : token);
		}
	}
	return values;
}

export interface ImportResult {
	warnings: string[];
	counts: Record<string, number>;
	orphans: { table: string; count: number }[];
}

/**
 * Replace all data in the target database with the legacy tables.
 * The caller provides an open connection with the new schema applied.
 */
export function importTables(db: Database, tables: TableData): ImportResult {
	const warnings: string[] = [];
	const rows = (table: string): Record<string, unknown>[] => {
		const r = tables.get(table);
		if (r === undefined) {
			warnings.push(`Table "${table}" not found in the backup - skipped.`);
			return [];
		}
		return r;
	};
	const n = (v: unknown): number | null => {
		if (v === null || v === undefined || v === '') return null;
		const num = Number(v);
		return Number.isNaN(num) ? null : num;
	};
	const nz = (v: unknown): number | null => {
		// 0 sentinel -> NULL
		const num = n(v);
		return num === 0 ? null : num;
	};

	const ins = {
		station: db.prepare(
			'INSERT INTO stations (id, name, default_setout_location_id, instructions, sort_seq, color1, color2) VALUES (?,?,?,?,?,?,?)'
		),
		location: db.prepare(
			'INSERT INTO locations (id, code, station_id, track, spot, rpt_station, remarks, color) VALUES (?,?,?,?,?,?,?,?)'
		),
		carCode: db.prepare('INSERT INTO car_codes (id, code, description, remarks) VALUES (?,?,?,?)'),
		commodity: db.prepare(
			'INSERT INTO commodities (id, code, description, remarks) VALUES (?,?,?,?)'
		),
		car: db.prepare(
			`INSERT INTO cars (id, reporting_marks, car_code_id, current_location_id, position, status,
			 handled_by_job_id, remarks, load_count, home_location_id, rfid_code, last_spotted)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`
		),
		shipment: db.prepare(
			`INSERT INTO shipments (id, code, description, consignment_id, car_code_id, loading_location_id,
			 unloading_location_id, last_ship_date, min_interval, max_interval, min_amount, max_amount,
			 min_load_time, max_load_time, min_unload_time, max_unload_time, special_instructions, remarks)
			 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`
		),
		order: db.prepare(
			'INSERT INTO car_orders (waybill_number, shipment_id, destination_location_id, car_id) VALUES (?,?,?,?)'
		),
		job: db.prepare('INSERT INTO jobs (id, name, description) VALUES (?,?,?)'),
		jobStep: db.prepare(
			'INSERT OR REPLACE INTO job_steps (job_id, step_number, station_id, pickup, setout, remarks) VALUES (?,?,?,?,?,?)'
		),
		puCriteria: db.prepare(
			'INSERT INTO pu_criteria (id, job_id, step_nbr, car_status, commodity_id, car_code_id, dest_station_id) VALUES (?,?,?,?,?,?,?)'
		),
		pool: db.prepare('INSERT OR IGNORE INTO pool (car_id, shipment_id) VALUES (?,?)'),
		emptyLoc: db.prepare(
			'INSERT OR IGNORE INTO empty_locations (shipment_id, priority, location_id) VALUES (?,?,?)'
		),
		owner: db.prepare('INSERT INTO owners (id, name, remarks) VALUES (?,?,?)'),
		ownership: db.prepare(
			'INSERT OR IGNORE INTO ownership (car_id, owner_id, on_off_rr) VALUES (?,?,?)'
		),
		history: db.prepare(
			'INSERT INTO history (car_id, session_nbr, event_date, event, location_id) VALUES (?,?,?,?,?)'
		),
		setting: db.prepare(
			'INSERT OR REPLACE INTO settings (setting_name, setting_desc, setting_value) VALUES (?,?,?)'
		)
	};

	// FK enforcement must change outside the transaction; legacy data has orphans.
	db.pragma('foreign_keys = OFF');
	try {
		db.transaction(() => {
			for (const table of [
				'history', 'pool', 'empty_locations', 'pu_criteria', 'job_steps', 'car_orders',
				'ownership', 'owners', 'cars', 'shipments', 'jobs', 'locations', 'stations',
				'car_codes', 'commodities'
			]) {
				db.prepare(`DELETE FROM ${table}`).run();
			}

			for (const r of rows('routing')) {
				ins.station.run(r.id, r.station, nz(r.station_nbr), r.instructions, n(r.sort_seq), n(r.color1), n(r.color2));
			}
			for (const r of rows('locations')) {
				ins.location.run(r.Id, r.code, n(r.station), r.track, r.spot, r.rpt_station, r.remarks, r.color);
			}
			for (const r of rows('car_codes')) {
				ins.carCode.run(r.Id, r.code, r.description, r.remarks);
			}
			for (const r of rows('commodities')) {
				ins.commodity.run(r.Id, r.Code, r.Description, r.Remarks);
			}

			const jobs = rows('jobs');
			const jobIdByName = new Map<string, number>();
			for (const r of jobs) {
				ins.job.run(r.Id, r.name, r.description);
				jobIdByName.set(String(r.name), Number(r.Id));
			}
			for (const r of jobs) {
				const steps = tables.get(String(r.name));
				if (steps === undefined) {
					warnings.push(`Step table missing for job "${r.name}" - job imported without steps.`);
					continue;
				}
				for (const s of steps) {
					ins.jobStep.run(
						r.Id, n(s.step_number), n(s.station),
						s.pickup === 'T' ? 1 : 0, s.setout === 'T' ? 1 : 0, s.remarks
					);
				}
			}

			for (const r of rows('pu_criteria')) {
				// legacy job_id column stores the job NAME
				const jobId = jobIdByName.get(String(r.job_id));
				if (jobId === undefined) {
					warnings.push(
						`Pickup criteria ${r.id}: no job named "${r.job_id}" - skipped (stale row; re-create it from the jobs page).`
					);
					continue;
				}
				ins.puCriteria.run(
					r.id, jobId, n(r.step_nbr), r.car_status || null,
					nz(r.commodity_id), nz(r.car_code_id), nz(r.dest_station_id)
				);
			}

			for (const r of rows('cars')) {
				ins.car.run(
					r.Id, r.reporting_marks, n(r.car_code_id), nz(r.current_location_id),
					n(r.position) ?? 0, r.status || 'Empty', nz(r.handled_by_job_id), r.remarks,
					n(r.load_count) ?? 0, nz(r.home_location), r.RFID_code, n(r.last_spotted) ?? 0
				);
			}

			for (const r of rows('shipments')) {
				ins.shipment.run(
					r.Id, r.code, r.description, nz(r.consignment), nz(r.car_code),
					nz(r.loading_location), nz(r.unloading_location),
					n(r.last_ship_date) ?? 0,
					n(r.min_interval) ?? 1, n(r.max_interval) ?? 1,
					n(r.min_amount) ?? 1, n(r.max_amount) ?? 1,
					n(r.min_load_time) ?? 0, n(r.max_load_time) ?? 0,
					n(r.min_unload_time) ?? 0, n(r.max_unload_time) ?? 0,
					r.special_instructions, r.remarks
				);
			}

			for (const r of rows('car_orders')) {
				const wb = String(r.waybill_number);
				const isRepo = wb.charAt(4) === 'E';
				ins.order.run(wb, isRepo ? null : n(r.shipment), isRepo ? n(r.shipment) : null, nz(r.car));
			}

			for (const r of rows('pool')) {
				ins.pool.run(r.car_id, r.shipment_id);
			}
			for (const r of rows('empty_locations')) {
				ins.emptyLoc.run(r.shipment, n(r.priority) ?? 0, r.location);
			}
			for (const r of rows('owners')) {
				ins.owner.run(r.id, r.name, r.remarks);
			}
			for (const r of rows('ownership')) {
				ins.ownership.run(r.car_id, r.owner_id, r.on_off_rr || 'on');
			}
			for (const r of rows('history')) {
				const date =
					r.event_date instanceof Date
						? r.event_date.toISOString().slice(0, 19).replace('T', ' ')
						: String(r.event_date ?? '');
				ins.history.run(r.car_id, n(r.session_nbr) ?? 0, date, r.event, nz(r.location));
			}
			for (const r of rows('settings')) {
				ins.setting.run(r.setting_name, r.setting_desc, r.setting_value);
			}
		})();
	} finally {
		db.pragma('foreign_keys = ON');
	}

	const orphanRows = db.prepare('PRAGMA foreign_key_check').all() as { table: string }[];
	const byTable = new Map<string, number>();
	for (const o of orphanRows) byTable.set(o.table, (byTable.get(o.table) ?? 0) + 1);
	const orphans = [...byTable.entries()].map(([table, count]) => ({ table, count }));

	const counts = db
		.prepare(
			`SELECT (SELECT COUNT(*) FROM stations) AS stations, (SELECT COUNT(*) FROM locations) AS locations,
			        (SELECT COUNT(*) FROM cars) AS cars, (SELECT COUNT(*) FROM shipments) AS shipments,
			        (SELECT COUNT(*) FROM car_orders) AS orders, (SELECT COUNT(*) FROM jobs) AS jobs,
			        (SELECT COUNT(*) FROM job_steps) AS steps, (SELECT COUNT(*) FROM history) AS history`
		)
		.get() as Record<string, number>;

	return { warnings, counts, orphans };
}
