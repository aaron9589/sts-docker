/** Report data builders. Rules per docs/SPEC.md §6. */
import { db, randInt, sessionNumber } from './db';

/**
 * car_forecast.php / shipment_forecast.php: simulate the next 10 sessions.
 * Note the legacy forecast uses plain rand(min, max) (no x100 rounding trick).
 */
export interface ForecastRow {
	shipment_code: string;
	description: string | null;
	car_code: string | null;
	loading_station: string | null;
	loading_location: string | null;
	perSession: number[];
	total: number;
}

export function forecast(sortBy: 'car_code' | 'loading_location'): {
	startSession: number;
	rows: ForecastRow[];
} {
	const startSession = sessionNumber();
	const shipments = db()
		.prepare(
			`SELECT s.code AS shipment_code, s.description, s.last_ship_date,
			        s.min_interval, s.max_interval, s.min_amount, s.max_amount,
			        cc.code AS car_code, ll.code AS loading_location, ls.name AS loading_station
			 FROM shipments s
			 LEFT JOIN car_codes cc ON cc.id = s.car_code_id
			 LEFT JOIN locations ll ON ll.id = s.loading_location_id
			 LEFT JOIN stations ls ON ls.id = ll.station_id
			 ORDER BY ${sortBy === 'car_code' ? 'cc.code, s.code' : 'll.code, s.code'}`
		)
		.all() as {
		shipment_code: string;
		description: string | null;
		last_ship_date: number;
		min_interval: number;
		max_interval: number;
		min_amount: number;
		max_amount: number;
		car_code: string | null;
		loading_location: string | null;
		loading_station: string | null;
	}[];

	const rows = shipments.map((s) => {
		const perSession: number[] = [];
		let prevShipDate = s.last_ship_date;
		let total = 0;
		for (let i = 0; i < 10; i++) {
			const session = startSession + i;
			const interval = randInt(s.min_interval, s.max_interval);
			if (prevShipDate + interval <= session) {
				const numCars = randInt(s.min_amount, s.max_amount);
				perSession.push(numCars);
				prevShipDate = session;
				total += numCars;
			} else {
				perSession.push(0);
			}
		}
		return {
			shipment_code: s.shipment_code,
			description: s.description,
			car_code: s.car_code,
			loading_station: s.loading_station,
			loading_location: s.loading_location,
			perSession,
			total
		};
	});

	return { startSession, rows };
}

export interface HistoryEntry {
	session_nbr: number;
	event_date: string;
	event: string;
	location_code: string | null;
	station_name: string | null;
}

export function carHistory(carId: number): HistoryEntry[] {
	return db()
		.prepare(
			`SELECT h.session_nbr, h.event_date, h.event,
			        l.code AS location_code, st.name AS station_name
			 FROM history h
			 LEFT JOIN locations l ON l.id = h.location_id
			 LEFT JOIN stations st ON st.id = l.station_id
			 WHERE h.car_id = ?
			 ORDER BY h.event_date DESC`
		)
		.all(carId) as HistoryEntry[];
}

export interface WaybillData {
	waybill_number: string;
	is_reposition: boolean;
	reporting_marks: string;
	car_code: string | null;
	consignment: string | null;
	special_instructions: string | null;
	shipment_remarks: string | null;
	current_station: string | null;
	current_location: string | null;
	/** Printed names honour locations.rpt_station substitution. */
	from_station: string | null;
	from_location: string | null;
	to_station: string | null;
	to_location: string | null;
	/** True when the car must first travel empty to the loading station. */
	needs_empty_leg: boolean;
}

/** Waybills are printable only when the car is not enroute (has a location). */
export function printableWaybills(): { waybill_number: string }[] {
	return db()
		.prepare(
			`SELECT co.waybill_number FROM car_orders co
			 JOIN cars c ON c.id = co.car_id
			 WHERE c.current_location_id IS NOT NULL
			 ORDER BY co.waybill_number`
		)
		.all() as { waybill_number: string }[];
}

export function waybillData(waybillNumber: string): WaybillData | null {
	const row = db()
		.prepare(
			`SELECT co.waybill_number,
			        (co.destination_location_id IS NOT NULL) AS is_reposition,
			        c.reporting_marks, cc.code AS car_code,
			        cm.code AS consignment, s.special_instructions, s.remarks AS shipment_remarks,
			        curs.name AS current_station_name, curl.code AS current_location,
			        curl.rpt_station AS current_rpt,
			        ls.name AS loading_station_name, ll.code AS loading_location, ll.rpt_station AS loading_rpt,
			        us.name AS unloading_station_name, ul.code AS unloading_location, ul.rpt_station AS unloading_rpt,
			        ds.name AS dest_station_name, dl.code AS dest_location, dl.rpt_station AS dest_rpt
			 FROM car_orders co
			 JOIN cars c ON c.id = co.car_id
			 LEFT JOIN car_codes cc ON cc.id = c.car_code_id
			 LEFT JOIN shipments s ON s.id = co.shipment_id
			 LEFT JOIN commodities cm ON cm.id = s.consignment_id
			 LEFT JOIN locations curl ON curl.id = c.current_location_id
			 LEFT JOIN stations curs ON curs.id = curl.station_id
			 LEFT JOIN locations ll ON ll.id = s.loading_location_id
			 LEFT JOIN stations ls ON ls.id = ll.station_id
			 LEFT JOIN locations ul ON ul.id = s.unloading_location_id
			 LEFT JOIN stations us ON us.id = ul.station_id
			 LEFT JOIN locations dl ON dl.id = co.destination_location_id
			 LEFT JOIN stations ds ON ds.id = dl.station_id
			 WHERE co.waybill_number = ?`
		)
		.get(waybillNumber) as Record<string, unknown> | undefined;
	if (!row) return null;

	const isRepo = !!row.is_reposition;
	const sub = (name: unknown, rpt: unknown) =>
		(typeof rpt === 'string' && rpt.length > 0 ? rpt : (name as string | null)) ?? null;

	const currentStation = sub(row.current_station_name, row.current_rpt);
	const fromStation = isRepo
		? currentStation
		: sub(row.loading_station_name, row.loading_rpt);
	const toStation = isRepo
		? sub(row.dest_station_name, row.dest_rpt)
		: sub(row.unloading_station_name, row.unloading_rpt);

	return {
		waybill_number: String(row.waybill_number),
		is_reposition: isRepo,
		reporting_marks: String(row.reporting_marks),
		car_code: row.car_code as string | null,
		consignment: row.consignment as string | null,
		special_instructions: row.special_instructions as string | null,
		shipment_remarks: row.shipment_remarks as string | null,
		current_station: currentStation,
		current_location: row.current_location as string | null,
		from_station: fromStation,
		from_location: (isRepo ? row.current_location : row.loading_location) as string | null,
		to_station: toStation,
		to_location: (isRepo ? row.dest_location : row.unloading_location) as string | null,
		needs_empty_leg: !isRepo && currentStation !== sub(row.loading_station_name, row.loading_rpt)
	};
}
