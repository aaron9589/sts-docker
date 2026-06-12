import { db } from '$lib/server/db';

/** Backup = a consistent snapshot of the SQLite file, streamed as a download. */
export async function GET() {
	const buf = db().serialize();
	const stamp = new Date().toISOString().slice(0, 19).replaceAll(':', '-');
	return new Response(new Uint8Array(buf), {
		headers: {
			'Content-Type': 'application/vnd.sqlite3',
			'Content-Disposition': `attachment; filename="sts-backup-${stamp}.db"`
		}
	});
}
