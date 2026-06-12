import { db } from '$lib/server/db';
import type { PageServerLoad } from './$types';

export interface ValidationSection {
	title: string;
	rows: string[];
}

export const load: PageServerLoad = () => {
	const d = db();
	const sections: ValidationSection[] = [];
	const add = (title: string, rows: { label: string }[]) =>
		sections.push({ title, rows: rows.map((r) => r.label) });

	add(
		'Locations referencing a missing station',
		d.prepare(
			`SELECT code AS label FROM locations
			 WHERE station_id NOT IN (SELECT id FROM stations) ORDER BY code`
		).all() as { label: string }[]
	);
	add(
		'Shipments with missing references',
		d.prepare(
			`SELECT s.code || ' (' ||
			   TRIM(
			     CASE WHEN cm.id IS NULL AND s.consignment_id IS NOT NULL THEN 'commodity ' ELSE '' END ||
			     CASE WHEN cc.id IS NULL AND s.car_code_id IS NOT NULL THEN 'car-code ' ELSE '' END ||
			     CASE WHEN ll.id IS NULL AND s.loading_location_id IS NOT NULL THEN 'loading-loc ' ELSE '' END ||
			     CASE WHEN ul.id IS NULL AND s.unloading_location_id IS NOT NULL THEN 'unloading-loc' ELSE '' END
			   ) || ')' AS label
			 FROM shipments s
			 LEFT JOIN commodities cm ON cm.id = s.consignment_id
			 LEFT JOIN car_codes cc ON cc.id = s.car_code_id
			 LEFT JOIN locations ll ON ll.id = s.loading_location_id
			 LEFT JOIN locations ul ON ul.id = s.unloading_location_id
			 WHERE (cm.id IS NULL AND s.consignment_id IS NOT NULL)
			    OR (cc.id IS NULL AND s.car_code_id IS NOT NULL)
			    OR (ll.id IS NULL AND s.loading_location_id IS NOT NULL)
			    OR (ul.id IS NULL AND s.unloading_location_id IS NOT NULL)
			 ORDER BY s.code`
		).all() as { label: string }[]
	);
	add(
		'Cars with missing references',
		d.prepare(
			`SELECT c.reporting_marks AS label FROM cars c
			 LEFT JOIN car_codes cc ON cc.id = c.car_code_id
			 LEFT JOIN locations cl ON cl.id = c.current_location_id
			 LEFT JOIN locations hl ON hl.id = c.home_location_id
			 LEFT JOIN jobs j ON j.id = c.handled_by_job_id
			 WHERE cc.id IS NULL
			    OR (cl.id IS NULL AND c.current_location_id IS NOT NULL)
			    OR (hl.id IS NULL AND c.home_location_id IS NOT NULL)
			    OR (j.id IS NULL AND c.handled_by_job_id IS NOT NULL)
			 ORDER BY c.reporting_marks`
		).all() as { label: string }[]
	);
	add(
		'Car orders pointing at a missing car',
		d.prepare(
			`SELECT waybill_number AS label FROM car_orders
			 WHERE car_id IS NOT NULL AND car_id NOT IN (SELECT id FROM cars)
			 ORDER BY waybill_number`
		).all() as { label: string }[]
	);
	add(
		'Car orders with missing shipment/destination',
		d.prepare(
			`SELECT waybill_number AS label FROM car_orders co
			 WHERE (co.shipment_id IS NOT NULL AND co.shipment_id NOT IN (SELECT id FROM shipments))
			    OR (co.destination_location_id IS NOT NULL
			        AND co.destination_location_id NOT IN (SELECT id FROM locations))
			 ORDER BY waybill_number`
		).all() as { label: string }[]
	);
	add(
		'Jobs without any steps',
		d.prepare(
			`SELECT name AS label FROM jobs
			 WHERE id NOT IN (SELECT DISTINCT job_id FROM job_steps) ORDER BY name`
		).all() as { label: string }[]
	);
	add(
		'Reporting marks containing "&"',
		d.prepare(
			`SELECT reporting_marks AS label FROM cars WHERE reporting_marks LIKE '%&%'`
		).all() as { label: string }[]
	);

	return { sections };
};
