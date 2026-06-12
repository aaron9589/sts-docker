import {
	allStations,
	allJobs,
	carsAtStationForSwitchlist,
	jobsPickingUpAt
} from '$lib/server/queries';
import { assignCarToJob } from '$lib/server/operations';
import { db } from '$lib/server/db';
import { fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const stationId = parseInt(url.searchParams.get('station') ?? '', 10);
	const station = Number.isNaN(stationId) ? null : stationId;

	return {
		stations: allStations(),
		jobs: allJobs(),
		selectedStation: station,
		cars: station ? carsAtStationForSwitchlist(station) : [],
		stationJobs: station ? jobsPickingUpAt(station) : [],
		instructions: station
			? ((db().prepare('SELECT instructions FROM stations WHERE id = ?').get(station) as
					| { instructions: string | null }
					| undefined)?.instructions ?? null)
			: null
	};
};

export const actions: Actions = {
	assign: async ({ request }) => {
		const form = await request.formData();
		let assigned = 0;
		for (const [key, value] of form.entries()) {
			if (key.startsWith('job_for_') && String(value).length > 0) {
				const carId = parseInt(key.slice('job_for_'.length), 10);
				const jobId = parseInt(String(value), 10);
				if (!Number.isNaN(carId) && !Number.isNaN(jobId)) {
					assignCarToJob(carId, jobId);
					assigned++;
				}
			}
		}
		return { assigned };
	},
	assignOne: async ({ request }) => {
		const form = await request.formData();
		const carId = parseInt(String(form.get('car_id') ?? ''), 10);
		const jobId = parseInt(String(form.get('job_id') ?? ''), 10);
		if (Number.isNaN(carId) || Number.isNaN(jobId)) return fail(400, { error: 'Missing parameters' });
		assignCarToJob(carId, jobId);
		return { assigned: carId };
	}
};
