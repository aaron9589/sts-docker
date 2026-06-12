import { autoAssignCandidates } from '$lib/server/queries';
import { assignCarToJob } from '$lib/server/operations';
import { db } from '$lib/server/db';
import { error, redirect } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const jobId = parseInt(url.searchParams.get('job') ?? '', 10);
	if (Number.isNaN(jobId)) throw error(400, 'Missing job');

	const job = db().prepare('SELECT id, name FROM jobs WHERE id = ?').get(jobId) as
		| { id: number; name: string }
		| undefined;
	if (!job) throw error(404, 'Job not found');

	const criteria = db()
		.prepare(
			`SELECT pc.step_nbr, st.name AS pickup_station,
			        COALESCE(NULLIF(TRIM(pc.car_status), ''), 'ANY') AS car_status,
			        COALESCE(cm.code, 'ANY') AS commodity,
			        COALESCE(cc.code, 'ANY') AS car_code,
			        COALESCE(ds.name, 'ANY') AS dest_station
			 FROM pu_criteria pc
			 JOIN job_steps js ON js.job_id = pc.job_id AND js.step_number = pc.step_nbr
			 JOIN stations st ON st.id = js.station_id
			 LEFT JOIN commodities cm ON cm.id = pc.commodity_id
			 LEFT JOIN car_codes cc ON cc.id = pc.car_code_id
			 LEFT JOIN stations ds ON ds.id = pc.dest_station_id
			 WHERE pc.job_id = ?
			 ORDER BY pc.step_nbr`
		)
		.all(jobId) as {
		step_nbr: number;
		pickup_station: string;
		car_status: string;
		commodity: string;
		car_code: string;
		dest_station: string;
	}[];

	return { job, criteria, candidates: autoAssignCandidates(jobId) };
};

export const actions: Actions = {
	assign: async ({ request }) => {
		const form = await request.formData();
		const jobId = parseInt(String(form.get('job') ?? ''), 10);
		const carIds = form.getAll('car').map((v) => parseInt(String(v), 10));
		for (const carId of carIds) {
			if (!Number.isNaN(carId)) assignCarToJob(carId, jobId);
		}
		throw redirect(303, `/operations/switchlists?assigned=${carIds.length}`);
	}
};
