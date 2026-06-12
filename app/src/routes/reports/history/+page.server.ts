import { db, getSetting } from '$lib/server/db';
import { carHistory } from '$lib/server/reports';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const carId = parseInt(url.searchParams.get('car') ?? '', 10);
	const car = Number.isNaN(carId)
		? null
		: ((db()
				.prepare(
					`SELECT c.id, c.reporting_marks, cc.code AS car_code FROM cars c
					 JOIN car_codes cc ON cc.id = c.car_code_id WHERE c.id = ?`
				)
				.get(carId) as { id: number; reporting_marks: string; car_code: string } | undefined) ??
			null);

	const cars = db()
		.prepare('SELECT id, reporting_marks FROM cars ORDER BY reporting_marks')
		.all() as { id: number; reporting_marks: string }[];

	return {
		cars,
		car,
		entries: car ? carHistory(car.id) : [],
		railroadName: getSetting('railroad_name')
	};
};
