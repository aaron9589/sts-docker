import { allJobs, carsInTrain, setoutLocations } from '$lib/server/queries';
import { setOutCar } from '$lib/server/operations';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const jobId = parseInt(url.searchParams.get('job') ?? '', 10);
	const job = Number.isNaN(jobId) ? null : jobId;
	const defaultsOnly = url.searchParams.get('defaults') === '1';
	return {
		jobs: allJobs(),
		selectedJob: job,
		defaultsOnly,
		cars: job ? carsInTrain(job) : [],
		locations: job ? setoutLocations(job, defaultsOnly) : []
	};
};

export const actions: Actions = {
	setout: async ({ request }) => {
		const form = await request.formData();
		let setOut = 0;
		for (const [key, value] of form.entries()) {
			if (key.startsWith('loc_for_') && String(value).length > 0) {
				const carId = parseInt(key.slice('loc_for_'.length), 10);
				const locationId = parseInt(String(value), 10);
				if (!Number.isNaN(carId) && !Number.isNaN(locationId)) {
					setOutCar(carId, locationId);
					setOut++;
				}
			}
		}
		return { setOut };
	}
};
