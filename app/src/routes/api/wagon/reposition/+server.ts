import { db } from '$lib/server/db';
import { repositionCar } from '$lib/server/operations';
import { json, apiError } from '$lib/server/api';
import type { RequestHandler } from './$types';

/**
 * POST /api/wagon/reposition (legacy reposition.php semantics).
 * Body: { wagonId, reportingMarks, locationId }
 */
export const POST: RequestHandler = async ({ request }) => {
	let body: Record<string, unknown>;
	try {
		body = await request.json();
	} catch {
		return apiError('Invalid JSON body', 400);
	}
	const wagonId = parseInt(String(body.wagonId ?? ''), 10);
	const marks = String(body.reportingMarks ?? '');
	const locationId = parseInt(String(body.locationId ?? ''), 10);
	if (Number.isNaN(wagonId) || !marks || Number.isNaN(locationId)) {
		return apiError('wagonId, reportingMarks and locationId are required', 400);
	}

	const car = db()
		.prepare('SELECT id, status FROM cars WHERE id = ? AND UPPER(reporting_marks) = UPPER(?)')
		.get(wagonId, marks) as { id: number; status: string } | undefined;
	if (!car) return apiError('Car not found', 404);
	if (car.status !== 'Empty') return apiError(`Car is ${car.status}, not Empty`, 409);
	const onOrder = db()
		.prepare('SELECT COUNT(*) AS n FROM car_orders WHERE car_id = ?')
		.get(car.id) as { n: number };
	if (onOrder.n > 0) return apiError('Car already has an order', 409);
	const location = db().prepare('SELECT id FROM locations WHERE id = ?').get(locationId);
	if (!location) return apiError('Location not found', 404);

	const waybill = repositionCar(car.id, locationId);
	return json({ message: `Car ${marks} ordered to reposition`, waybillNumber: waybill });
};
