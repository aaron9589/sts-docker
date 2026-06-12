import { db } from '$lib/server/db';
import { allStations } from '$lib/server/queries';
import { error, fail, redirect } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = ({ params }) => {
	const id = parseInt(params.id, 10);
	const job = db().prepare('SELECT id, name, description FROM jobs WHERE id = ?').get(id) as
		| { id: number; name: string; description: string | null }
		| undefined;
	if (!job) throw error(404, 'Job not found');

	const steps = db()
		.prepare(
			`SELECT js.step_number, js.station_id, js.pickup, js.setout, js.remarks, st.name AS station,
			        EXISTS (SELECT 1 FROM pu_criteria pc
			                WHERE pc.job_id = js.job_id AND pc.step_nbr = js.step_number) AS has_criteria
			 FROM job_steps js
			 JOIN stations st ON st.id = js.station_id
			 WHERE js.job_id = ?
			 ORDER BY js.step_number`
		)
		.all(id) as {
		step_number: number;
		station_id: number;
		pickup: 0 | 1;
		setout: 0 | 1;
		remarks: string | null;
		station: string;
		has_criteria: 0 | 1;
	}[];

	return { job, steps, stations: allStations() };
};

export const actions: Actions = {
	update: async ({ params, request }) => {
		const id = parseInt(params.id, 10);
		const form = await request.formData();
		const name = String(form.get('name') ?? '').trim();
		if (!name) return fail(400, { error: 'Job name is required' });
		try {
			db()
				.prepare('UPDATE jobs SET name = ?, description = ? WHERE id = ?')
				.run(name, String(form.get('description') ?? ''), id);
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		return { saved: true };
	},
	/**
	 * Legacy semantics (db_edit_jobs.php): the submitted set of steps replaces
	 * the job's step list; a step with sequence 0 is dropped.
	 */
	steps: async ({ params, request }) => {
		const id = parseInt(params.id, 10);
		const form = await request.formData();
		const count = parseInt(String(form.get('row_count') ?? '0'), 10);

		type Step = { step: number; station: number; pickup: 0 | 1; setout: 0 | 1; remarks: string };
		const steps: Step[] = [];
		for (let i = 0; i < count; i++) {
			const step = parseInt(String(form.get(`step${i}`) ?? '0'), 10);
			const station = parseInt(String(form.get(`station${i}`) ?? ''), 10);
			if (!step || step <= 0 || Number.isNaN(station)) continue;
			steps.push({
				step,
				station,
				pickup: form.get(`pickup${i}`) ? 1 : 0,
				setout: form.get(`setout${i}`) ? 1 : 0,
				remarks: String(form.get(`remarks${i}`) ?? '')
			});
		}
		// optional "new step" row
		const newStep = parseInt(String(form.get('new_step') ?? ''), 10);
		const newStation = parseInt(String(form.get('new_station') ?? ''), 10);
		if (newStep > 0 && !Number.isNaN(newStation)) {
			steps.push({
				step: newStep,
				station: newStation,
				pickup: form.get('new_pickup') ? 1 : 0,
				setout: form.get('new_setout') ? 1 : 0,
				remarks: String(form.get('new_remarks') ?? '')
			});
		}

		const d = db();
		try {
			d.transaction(() => {
				d.prepare('DELETE FROM job_steps WHERE job_id = ?').run(id);
				const ins = d.prepare(
					`INSERT INTO job_steps (job_id, step_number, station_id, pickup, setout, remarks)
					 VALUES (?, ?, ?, ?, ?, ?)`
				);
				for (const s of steps) ins.run(id, s.step, s.station, s.pickup, s.setout, s.remarks);
			})();
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		throw redirect(303, `/data/jobs/${id}`);
	}
};
