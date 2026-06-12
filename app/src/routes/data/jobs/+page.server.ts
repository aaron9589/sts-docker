import { db } from '$lib/server/db';
import { fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = () => ({
	jobs: db()
		.prepare(
			`SELECT j.id, j.name, j.description,
			        (SELECT COUNT(*) FROM job_steps WHERE job_id = j.id) AS steps,
			        (SELECT COUNT(*) FROM cars WHERE handled_by_job_id = j.id) AS cars
			 FROM jobs j ORDER BY j.name`
		)
		.all() as { id: number; name: string; description: string | null; steps: number; cars: number }[]
});

export const actions: Actions = {
	create: async ({ request }) => {
		const form = await request.formData();
		const name = String(form.get('name') ?? '').trim();
		if (!name) return fail(400, { error: 'Job name is required' });
		try {
			db()
				.prepare('INSERT INTO jobs (name, description) VALUES (?, ?)')
				.run(name, String(form.get('description') ?? ''));
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		return { created: true };
	},
	delete: async ({ request }) => {
		const form = await request.formData();
		const id = parseInt(String(form.get('id') ?? ''), 10);
		if (Number.isNaN(id)) return fail(400, { error: 'Missing id' });
		const d = db();
		d.transaction(() => {
			d.prepare('UPDATE cars SET handled_by_job_id = NULL WHERE handled_by_job_id = ?').run(id);
			d.prepare('DELETE FROM jobs WHERE id = ?').run(id); // steps + criteria cascade
		})();
		return { deleted: true };
	}
};
