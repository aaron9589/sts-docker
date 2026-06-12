import { db, getSetting } from '$lib/server/db';
import type { PageServerLoad } from './$types';

export interface FleetRow {
	id: number;
	reporting_marks: string;
	car_code: string;
	status: string;
	load_count: number;
	remarks: string | null;
	current_station: string | null;
	current_location: string | null;
	home_station: string | null;
	home_location: string | null;
	job_name: string | null;
	owner: string | null;
}

export const load: PageServerLoad = () => {
	const cars = db()
		.prepare(
			`SELECT c.id, c.reporting_marks, cc.code AS car_code, c.status, c.load_count, c.remarks,
			        cs.name AS current_station, cl.code AS current_location,
			        hs.name AS home_station, hl.code AS home_location,
			        j.name AS job_name, o.name AS owner
			 FROM cars c
			 JOIN car_codes cc ON cc.id = c.car_code_id
			 LEFT JOIN locations cl ON cl.id = c.current_location_id
			 LEFT JOIN stations cs ON cs.id = cl.station_id
			 LEFT JOIN locations hl ON hl.id = c.home_location_id
			 LEFT JOIN stations hs ON hs.id = hl.station_id
			 LEFT JOIN jobs j ON j.id = c.handled_by_job_id
			 LEFT JOIN ownership ow ON ow.car_id = c.id
			 LEFT JOIN owners o ON o.id = ow.owner_id
			 ORDER BY c.reporting_marks`
		)
		.all() as FleetRow[];
	return { cars, railroadName: getSetting('railroad_name') };
};
