import { db, sessionNumber } from '$lib/server/db';
import { generateAutomatic, generateManual } from '$lib/server/operations';
import type { Actions, PageServerLoad } from './$types';

export interface ShipmentRow {
	id: number;
	code: string;
	description: string | null;
	last_ship_date: number;
	min_interval: number;
	max_interval: number;
	min_amount: number;
	max_amount: number;
	commodity: string | null;
	car_code: string | null;
	loading_station: string | null;
	loading_location: string | null;
	unloading_station: string | null;
	unloading_location: string | null;
}

export const load: PageServerLoad = () => {
	const shipments = db()
		.prepare(
			`SELECT s.id, s.code, s.description, s.last_ship_date,
			        s.min_interval, s.max_interval, s.min_amount, s.max_amount,
			        cm.code AS commodity, cc.code AS car_code,
			        ls.name AS loading_station, ll.code AS loading_location,
			        us.name AS unloading_station, ul.code AS unloading_location
			 FROM shipments s
			 LEFT JOIN commodities cm ON cm.id = s.consignment_id
			 LEFT JOIN car_codes cc ON cc.id = s.car_code_id
			 LEFT JOIN locations ll ON ll.id = s.loading_location_id
			 LEFT JOIN stations ls ON ls.id = ll.station_id
			 LEFT JOIN locations ul ON ul.id = s.unloading_location_id
			 LEFT JOIN stations us ON us.id = ul.station_id
			 ORDER BY s.code`
		)
		.all() as ShipmentRow[];
	return { session: sessionNumber(), shipments };
};

export const actions: Actions = {
	automatic: async () => {
		const result = generateAutomatic();
		return { mode: 'automatic', session: result.session, count: result.waybills.length };
	},
	manual: async ({ request }) => {
		const form = await request.formData();
		const ids = form.getAll('shipment').map((v) => parseInt(String(v), 10));
		const result = generateManual(ids);
		return { mode: 'manual', session: result.session, count: result.waybills.length };
	}
};
