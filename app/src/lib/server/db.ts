import Database from 'better-sqlite3';
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import schemaSql from './schema.sql?raw';

const DATA_DIR = process.env.STS_DATA_DIR ?? 'data';
const DB_PATH = process.env.STS_DB_PATH ?? join(DATA_DIR, 'sts.db');

let _db: Database.Database | undefined;

export function db(): Database.Database {
	if (!_db) {
		mkdirSync(dirname(DB_PATH), { recursive: true });
		_db = new Database(DB_PATH);
		_db.pragma('journal_mode = WAL');
		_db.pragma('foreign_keys = ON');
		_db.exec(schemaSql);
	}
	return _db;
}

export function getSetting(name: string): string {
	const row = db()
		.prepare('SELECT setting_value FROM settings WHERE setting_name = ?')
		.get(name) as { setting_value: string } | undefined;
	return row?.setting_value ?? '';
}

export function setSetting(name: string, value: string): void {
	db().prepare('UPDATE settings SET setting_value = ? WHERE setting_name = ?').run(value, name);
}

export function sessionNumber(): number {
	return parseInt(getSetting('session_nbr') || '0', 10);
}

/** Random integer in [min, max] inclusive (matches PHP rand/mt_rand). */
export function randInt(min: number, max: number): number {
	if (max < min) [min, max] = [max, min];
	return Math.floor(Math.random() * (max - min + 1)) + min;
}

/**
 * Legacy generate.php math: round(rand(min*100, max*100)/100).
 * Gives fractional bounds a proportional chance of rounding either way.
 */
export function randAmount(min: number, max: number): number {
	return Math.round(randInt(min * 100, max * 100) / 100);
}

export function addHistory(
	carId: number,
	event: string,
	locationId: number | null,
	session = sessionNumber()
): void {
	const d = db();
	d.prepare(
		`INSERT INTO history (car_id, session_nbr, event_date, event, location_id)
		 VALUES (?, ?, datetime('now', 'localtime'), ?, ?)`
	).run(carId, session, event, locationId);

	const max = parseInt(getSetting('max_history') || '24', 10);
	d.prepare(
		`DELETE FROM history WHERE id IN (
		   SELECT id FROM history WHERE car_id = ?
		   ORDER BY session_nbr DESC, id DESC LIMIT -1 OFFSET ?
		 )`
	).run(carId, max);
}

/** Waybill numbers: SSS-NNN (auto), SSS-MNN (manual), SSS-ENN (reposition). */
export function isRepositionWaybill(waybill: string | null | undefined): boolean {
	return !!waybill && waybill.charAt(4) === 'E';
}

export function pad3(n: number): string {
	return String(n).padStart(3, '0');
}

export function pad2(n: number): string {
	return String(n).padStart(2, '0');
}

/** Next counter for SSS-{M|E}NN waybills in the given session. */
export function nextLetterWaybillCounter(letter: 'M' | 'E', session: number): number {
	const prefix = `${pad3(session)}-${letter}`;
	// Suffix can grow past two digits, so take the numeric max of the suffix
	// rather than a lexical ORDER BY (which sorts 'M9' after 'M10').
	const row = db()
		.prepare(
			`SELECT MAX(CAST(SUBSTR(waybill_number, ?) AS INTEGER)) AS n FROM car_orders
			 WHERE waybill_number LIKE ?`
		)
		.get(prefix.length + 1, `${prefix}%`) as { n: number | null };
	return (row.n ?? 0) + 1;
}
