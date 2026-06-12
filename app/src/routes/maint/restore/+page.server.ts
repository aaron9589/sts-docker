import { db } from '$lib/server/db';
import { parseBackup, importTables } from '$lib/server/import-backup';
import { fail } from '@sveltejs/kit';
import type { Actions } from './$types';

export const actions: Actions = {
	import: async ({ request }) => {
		const form = await request.formData();
		const file = form.get('backup');
		if (!(file instanceof File) || file.size === 0) {
			return fail(400, { error: 'Choose a backup .sql file first.' });
		}

		let text: string;
		try {
			text = await file.text();
		} catch {
			return fail(400, { error: 'Could not read the uploaded file.' });
		}

		const tables = parseBackup(text);
		if (tables.size === 0 || !tables.has('cars')) {
			return fail(400, {
				error:
					'That does not look like a legacy STS backup (no tables found). Use the file downloaded from the old app\'s "Backup DB" page.'
			});
		}

		try {
			const result = importTables(db(), tables);
			return { ...result, fileName: file.name };
		} catch (e) {
			return fail(500, { error: `Import failed: ${(e as Error).message}` });
		}
	}
};
