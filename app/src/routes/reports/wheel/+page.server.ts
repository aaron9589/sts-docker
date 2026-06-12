import { allJobs, carsInJob } from '$lib/server/queries';
import { getSetting } from '$lib/server/db';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = () => {
	const jobs = allJobs().map((job) => ({ ...job, cars: carsInJob(job.id) }));
	return { jobs: jobs.filter((j) => j.cars.length > 0), railroadName: getSetting('railroad_name') };
};
