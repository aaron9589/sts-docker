import { allJobs, allLocations, carsInTrain, carsAtLocation } from '$lib/server/queries';
import { saveCarPositions } from '$lib/server/operations';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const jobId = parseInt(url.searchParams.get('job') ?? '', 10);
	const locationId = parseInt(url.searchParams.get('location') ?? '', 10);

	let cars: ReturnType<typeof carsInTrain> = [];
	if (!Number.isNaN(jobId)) cars = carsInTrain(jobId);
	else if (!Number.isNaN(locationId)) cars = carsAtLocation(locationId);

	return {
		jobs: allJobs(),
		locations: allLocations(),
		selectedJob: Number.isNaN(jobId) ? null : jobId,
		selectedLocation: Number.isNaN(locationId) ? null : locationId,
		cars
	};
};

export const actions: Actions = {
	save: async ({ request }) => {
		const form = await request.formData();
		const carIds = form.getAll('car').map((v) => parseInt(String(v), 10));
		saveCarPositions(carIds.filter((id) => !Number.isNaN(id)));
		return { saved: carIds.length };
	}
};
