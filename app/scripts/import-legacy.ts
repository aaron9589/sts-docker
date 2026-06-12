/**
 * One-time importer: legacy sts_db3 -> SQLite. Thin CLI around
 * src/lib/server/import-backup.ts (also exposed in the web UI at /maint/restore).
 *
 *  1. From a backup .sql file (legacy "Backup DB" download):
 *     npx tsx scripts/import-legacy.ts --file backup.sql [output.db]
 *
 *  2. From a live MariaDB server:
 *     MYSQL_HOST=127.0.0.1 MYSQL_PORT=3306 MYSQL_USER=sts_user \
 *     MYSQL_PASSWORD=sts_password MYSQL_DATABASE=sts_db3 \
 *     npx tsx scripts/import-legacy.ts [output.db]
 */
import Database from 'better-sqlite3';
import { readFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { parseBackup, importTables, type TableData } from '../src/lib/server/import-backup';

const args = process.argv.slice(2);
let backupFile: string | null = null;
let out = 'data/sts.db';
for (let i = 0; i < args.length; i++) {
	if (args[i] === '--file') {
		backupFile = args[++i];
	} else {
		out = args[i];
	}
}

async function loadFromMysql(): Promise<TableData> {
	const mysql = await import('mysql2/promise');
	const conn = await mysql.createConnection({
		host: process.env.MYSQL_HOST ?? '127.0.0.1',
		port: parseInt(process.env.MYSQL_PORT ?? '3306', 10),
		user: process.env.MYSQL_USER ?? 'sts_user',
		password: process.env.MYSQL_PASSWORD ?? 'sts_password',
		database: process.env.MYSQL_DATABASE ?? 'sts_db3'
	});
	const tables: TableData = new Map();
	const [tableRows] = await conn.query('SHOW TABLES');
	for (const row of tableRows as Record<string, unknown>[]) {
		const name = String(Object.values(row)[0]);
		const [rows] = await conn.query(`SELECT * FROM \`${name.replaceAll('`', '``')}\``);
		tables.set(name, rows as Record<string, unknown>[]);
	}
	await conn.end();
	return tables;
}

async function main() {
	const tables = backupFile
		? parseBackup(readFileSync(backupFile, 'utf8'))
		: await loadFromMysql();
	console.log(`Loaded ${tables.size} tables from ${backupFile ?? 'MariaDB'}`);

	mkdirSync(dirname(out), { recursive: true });
	const db = new Database(out);
	db.pragma('journal_mode = WAL');
	db.exec(readFileSync(new URL('../src/lib/server/schema.sql', import.meta.url), 'utf8'));

	const result = importTables(db, tables);
	for (const w of result.warnings) console.warn(`  ! ${w}`);
	if (result.orphans.length > 0) {
		console.warn('! Foreign key violations imported (legacy orphans):');
		for (const o of result.orphans) console.warn(`    ${o.table}: ${o.count}`);
		console.warn('  These are usually history rows for deleted cars. Review on the Validate DB page.');
	}
	const c = result.counts;
	console.log(
		`Done: ${c.cars} cars, ${c.shipments} shipments, ${c.orders} orders, ` +
			`${c.jobs} jobs (${c.steps} steps), ${c.history} history rows -> ${out}`
	);
	db.close();
}

main().catch((e) => {
	console.error(e);
	process.exit(1);
});
