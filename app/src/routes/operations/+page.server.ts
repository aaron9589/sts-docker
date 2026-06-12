import { allJobs, allStations } from '$lib/server/queries';
import { getSetting, sessionNumber } from '$lib/server/db';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = () => ({
	jobs: allJobs(),
	stations: allStations(),
	railroadName: getSetting('railroad_name'),
	session: sessionNumber()
});
