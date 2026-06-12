import { allJobs, carsInJob } from '$lib/server/queries';
import { pickUpCar, undoPickUpCar } from '$lib/server/operations';
import { fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const jobId = parseInt(url.searchParams.get('job') ?? '', 10);
	const job = Number.isNaN(jobId) ? null : jobId;
	return {
		jobs: allJobs(),
		selectedJob: job,
		// only cars still at a location can be picked up
		cars: job ? carsInJob(job).filter((c) => c.current_location !== null) : []
	};
};

export const actions: Actions = {
	pickup: async ({ request }) => {
		const form = await request.formData();
		const carIds = form.getAll('car').map((v) => parseInt(String(v), 10));
		for (const carId of carIds) {
			if (!Number.isNaN(carId)) pickUpCar(carId);
		}
		return { pickedUp: carIds.length };
	},
	pickupOne: async ({ request }) => {
		const form = await request.formData();
		const carId = parseInt(String(form.get('car_id') ?? ''), 10);
		if (Number.isNaN(carId)) return fail(400, { error: 'Missing car_id' });
		pickUpCar(carId);
		return { pickedUp: carId };
	},
	undoOne: async ({ request }) => {
		const form = await request.formData();
		const carId = parseInt(String(form.get('car_id') ?? ''), 10);
		const jobId = parseInt(String(form.get('job_id') ?? ''), 10);
		if (Number.isNaN(carId) || Number.isNaN(jobId)) return fail(400, { error: 'Missing parameters' });
		undoPickUpCar(carId, jobId);
		return { undone: carId };
	}
};
