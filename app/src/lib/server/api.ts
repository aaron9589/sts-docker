/**
 * REST API data helpers, mirroring sts/api/endpoints/wagon.php.
 * Lookup tokens: reporting marks (uppercased), RFID code, '-id-' car token,
 * '%id%' location token.
 */
import { db } from './db';
import { completeLoadUnload } from './operations';

const CAR_DETAIL_SQL = `
	SELECT c.id, c.reporting_marks AS reportingMarks, cc.code AS carCode, cc.id AS carCodeId,
	       c.status, c.rfid_code AS rfidCode, c.remarks, c.load_count AS loadCount,
	       cl.code AS currentLocation, cl.id AS currentLocationId, cs.name AS currentStation,
	       co.waybill_number AS waybillNumber,
	       (co.destination_location_id IS NOT NULL) AS isReposition,
	       s.code AS shipmentCode, cm.code AS consignment,
	       ll.code AS loadingLocation, ll.id AS loadingLocationId, ls.name AS loadingStation,
	       ul.code AS unloadingLocation, ul.id AS unloadingLocationId, us.name AS unloadingStation,
	       dl.code AS destinationLocation, dl.id AS destinationLocationId, ds.name AS destinationStation
	FROM cars c
	LEFT JOIN car_codes cc ON cc.id = c.car_code_id
	LEFT JOIN car_orders co ON co.car_id = c.id
	LEFT JOIN shipments s ON s.id = co.shipment_id
	LEFT JOIN commodities cm ON cm.id = s.consignment_id
	LEFT JOIN locations cl ON cl.id = c.current_location_id
	LEFT JOIN stations cs ON cs.id = cl.station_id
	LEFT JOIN locations ll ON ll.id = s.loading_location_id
	LEFT JOIN stations ls ON ls.id = ll.station_id
	LEFT JOIN locations ul ON ul.id = s.unloading_location_id
	LEFT JOIN stations us ON us.id = ul.station_id
	LEFT JOIN locations dl ON dl.id = co.destination_location_id
	LEFT JOIN stations ds ON ds.id = dl.station_id`;

export function findCarByTag(tag: string): Record<string, unknown> | null {
	const d = db();
	// '-123-' token carries a car id
	const idMatch = tag.match(/^-(\d+)-$/);
	if (idMatch) {
		const row = d.prepare(`${CAR_DETAIL_SQL} WHERE c.id = ?`).get(parseInt(idMatch[1], 10));
		return (row as Record<string, unknown>) ?? null;
	}
	const row =
		d.prepare(`${CAR_DETAIL_SQL} WHERE UPPER(c.reporting_marks) = UPPER(?)`).get(tag) ??
		d.prepare(`${CAR_DETAIL_SQL} WHERE c.rfid_code = ?`).get(tag);
	return (row as Record<string, unknown>) ?? null;
}

export function findLocation(name: string): Record<string, unknown> | null {
	const d = db();
	const idMatch = name.match(/^%(\d+)%$/);
	const location = (
		idMatch
			? d
					.prepare(
						`SELECT l.id, l.code, l.track, l.spot, st.name AS station FROM locations l
						 JOIN stations st ON st.id = l.station_id WHERE l.id = ?`
					)
					.get(parseInt(idMatch[1], 10))
			: d
					.prepare(
						`SELECT l.id, l.code, l.track, l.spot, st.name AS station FROM locations l
						 JOIN stations st ON st.id = l.station_id WHERE UPPER(l.code) = UPPER(?)`
					)
					.get(name)
	) as Record<string, unknown> | undefined;
	if (!location) return null;

	const cars = d
		.prepare(
			`SELECT c.id, c.reporting_marks AS reportingMarks, c.position, cc.code AS carCode, c.status
			 FROM cars c JOIN car_codes cc ON cc.id = c.car_code_id
			 WHERE c.current_location_id = ?
			 ORDER BY c.position, c.reporting_marks`
		)
		.all(location.id);
	return { ...location, cars };
}

export function json(data: unknown, status = 200): Response {
	return new Response(JSON.stringify(data), {
		status,
		headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
	});
}

export function apiError(message: string, status: number): Response {
	return json({ error: message }, status);
}

/**
 * Shared handler for POST /api/wagon/load and /api/wagon/unload (legacy load_unload.php semantics).
 * Body: { waybill_number, reportingMarks, status: 'Loading' | 'Unloading' }
 */
export async function handleLoadUnload(request: Request): Promise<Response> {
	let body: Record<string, unknown>;
	try {
		body = await request.json();
	} catch {
		return apiError('Invalid JSON body', 400);
	}
	const waybill = String(body.waybill_number ?? body.waybillNumber ?? '');
	const marks = String(body.reportingMarks ?? '');
	const status = String(body.status ?? '');
	if (!waybill || !marks || !status) {
		return apiError('waybill_number, reportingMarks and status are required', 400);
	}
	if (status !== 'Loading' && status !== 'Unloading') {
		return apiError('status must be Loading or Unloading', 400);
	}

	const car = db()
		.prepare(
			`SELECT c.id, c.status FROM cars c
			 JOIN car_orders co ON co.car_id = c.id
			 WHERE co.waybill_number = ? AND UPPER(c.reporting_marks) = UPPER(?)`
		)
		.get(waybill, marks) as { id: number; status: string } | undefined;
	if (!car) return apiError('No car matches that waybill and reporting marks', 404);
	if (car.status !== status) {
		return apiError(`Car is ${car.status}, not ${status}`, 409);
	}

	completeLoadUnload(car.id, status);
	const newStatus = status === 'Loading' ? 'Loaded' : 'Empty';
	return json({
		message: `Car ${marks} is now ${newStatus}`,
		carId: car.id,
		status: newStatus
	});
}
