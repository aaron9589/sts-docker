import { getSetting, sessionNumber } from '$lib/server/db';
import { allJobs, allStations } from '$lib/server/queries';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = () => ({
	railroadName: getSetting('railroad_name'),
	session: sessionNumber(),
	jobs: allJobs(),
	stations: allStations()
});
