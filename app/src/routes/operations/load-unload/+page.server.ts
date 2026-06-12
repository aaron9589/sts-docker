import { loadUnloadList } from '$lib/server/queries';
import { completeLoadUnload } from '$lib/server/operations';
import { db } from '$lib/server/db';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = () => ({ cars: loadUnloadList() });

export const actions: Actions = {
	update: async ({ request }) => {
		const form = await request.formData();
		const carIds = form.getAll('car').map((v) => parseInt(String(v), 10));
		let updated = 0;
		const getStatus = db().prepare('SELECT status FROM cars WHERE id = ?');
		for (const carId of carIds) {
			if (Number.isNaN(carId)) continue;
			const row = getStatus.get(carId) as { status: string } | undefined;
			if (!row) continue;
			completeLoadUnload(carId, row.status);
			updated++;
		}
		return { updated };
	}
};
