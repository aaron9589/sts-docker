import { allJobs, carsInJobRouteOrder } from '$lib/server/queries';
import { getSetting } from '$lib/server/db';
import { logoVersion, resolveLogoKey } from '$lib/server/logo';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const jobId = parseInt(url.searchParams.get('job') ?? '', 10);
	const job = Number.isNaN(jobId) ? null : jobId;
	const jobs = allJobs();
	const cars = job ? carsInJobRouteOrder(job) : [];
	const serial = String(Math.floor(Math.random() * 999999)).padStart(6, '0');

	const jobInfo = jobs.find((j) => j.id === job) ?? null;
	const logoKey = jobInfo ? resolveLogoKey(jobInfo.name) : 'logo_data';
	const hasLogo = !!getSetting(logoKey) || !!getSetting('logo_data');

	return {
		jobs,
		selectedJob: job,
		jobInfo,
		cars,
		loads: cars.filter((c) => ['Loaded', 'Loading', 'Unloading'].includes(c.status)).length,
		empties: cars.filter((c) => !['Loaded', 'Loading', 'Unloading'].includes(c.status)).length,
		railroadName: getSetting('railroad_name'),
		railroadInitials: getSetting('railroad_initials'),
		serialNumber: serial,
		logoKey,
		logoVersion: logoVersion(logoKey) || logoVersion('logo_data'),
		hasLogo
	};
};
