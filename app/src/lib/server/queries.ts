/**
 * Read-side queries backing the operations pages and reports.
 * Tier logic and display rules follow docs/SPEC.md §5–§6.
 */
import { db, sessionNumber } from './db';
import type {
	CarRow,
	CarTier,
	EligibleCar,
	OpenOrder,
	RepositionRow,
	SetoutLocation
} from '$lib/types';

export type { CarRow, CarTier, EligibleCar, OpenOrder, RepositionRow, SetoutLocation };

/** Shipment car codes may contain '*' wildcards (legacy LIKE match). */
function carCodeLike(code: string): string {
	return code.replaceAll('*', '%');
}

/** fill_orders.php: orders not yet assigned a car. */
export function openOrders(): OpenOrder[] {
	return db()
		.prepare(
			`SELECT co.waybill_number, co.shipment_id,
			        s.code AS shipment_code, s.description, s.remarks,
			        cm.code AS consignment, cc.code AS car_code,
			        ls.name AS loading_station, ll.code AS loading_location,
			        us.name AS unloading_station, ul.code AS unloading_location,
			        (SELECT COUNT(*) FROM pool WHERE shipment_id = co.shipment_id) AS pool_count
			 FROM car_orders co
			 JOIN shipments s ON s.id = co.shipment_id
			 LEFT JOIN commodities cm ON cm.id = s.consignment_id
			 LEFT JOIN car_codes cc ON cc.id = s.car_code_id
			 LEFT JOIN locations ll ON ll.id = s.loading_location_id
			 LEFT JOIN stations ls ON ls.id = ll.station_id
			 LEFT JOIN locations ul ON ul.id = s.unloading_location_id
			 LEFT JOIN stations us ON us.id = ul.station_id
			 WHERE co.car_id IS NULL
			 ORDER BY co.waybill_number`
		)
		.all() as OpenOrder[];
}

/**
 * get_available_cars_ajax.php: four ranked tiers of eligible empties.
 * Base eligibility: Empty, not on any order, car code matches (wildcards ok).
 * Pool cars only appear in tier 1; tiers 2-4 exclude all pool cars.
 */
export function eligibleCarsForOrder(waybillNumber: string): EligibleCar[] {
	const d = db();
	const order = d
		.prepare(
			`SELECT co.shipment_id, cc.code AS car_code,
			        ll.station_id AS loading_station_id
			 FROM car_orders co
			 JOIN shipments s ON s.id = co.shipment_id
			 LEFT JOIN car_codes cc ON cc.id = s.car_code_id
			 LEFT JOIN locations ll ON ll.id = s.loading_location_id
			 WHERE co.waybill_number = ?`
		)
		.get(waybillNumber) as
		| { shipment_id: number; car_code: string | null; loading_station_id: number | null }
		| undefined;
	if (!order) return [];

	const like = carCodeLike(order.car_code ?? '%');

	const base = `
		FROM cars c
		JOIN car_codes cc ON cc.id = c.car_code_id
		LEFT JOIN locations l ON l.id = c.current_location_id
		LEFT JOIN stations st ON st.id = l.station_id
		WHERE c.status = 'Empty'
		  AND c.id NOT IN (SELECT car_id FROM car_orders WHERE car_id IS NOT NULL)
		  AND cc.code LIKE @like`;

	const select = `SELECT c.id AS car_id, c.reporting_marks, cc.code AS car_code,
		st.name AS current_station, l.code AS current_location,
		c.load_count, c.remarks`;

	const pool = d
		.prepare(
			`${select} ${base}
			 AND c.id IN (SELECT car_id FROM pool WHERE shipment_id = @shipment)
			 ORDER BY c.load_count`
		)
		.all({ like, shipment: order.shipment_id }) as Omit<EligibleCar, 'tier'>[];

	const station = d
		.prepare(
			`${select} ${base}
			 AND c.id NOT IN (SELECT car_id FROM pool)
			 AND l.station_id = @station
			 ORDER BY c.load_count`
		)
		.all({ like, station: order.loading_station_id }) as Omit<EligibleCar, 'tier'>[];

	const priority = d
		.prepare(
			`SELECT c.id AS car_id, c.reporting_marks, cc.code AS car_code,
			        st.name AS current_station, l.code AS current_location,
			        c.load_count, c.remarks
			 FROM cars c
			 JOIN car_codes cc ON cc.id = c.car_code_id
			 JOIN empty_locations el
			   ON el.location_id = c.current_location_id AND el.shipment_id = @shipment
			 LEFT JOIN locations l ON l.id = c.current_location_id
			 LEFT JOIN stations st ON st.id = l.station_id
			 WHERE c.status = 'Empty'
			   AND c.id NOT IN (SELECT car_id FROM car_orders WHERE car_id IS NOT NULL)
			   AND c.id NOT IN (SELECT car_id FROM pool)
			   AND cc.code LIKE @like
			   AND l.station_id IS NOT @station
			 ORDER BY el.priority, c.load_count`
		)
		.all({ like, shipment: order.shipment_id, station: order.loading_station_id }) as Omit<
		EligibleCar,
		'tier'
	>[];

	const taken = new Set([...station, ...priority].map((c) => c.car_id));
	const system = (
		d
			.prepare(
				`${select} ${base}
				 AND c.id NOT IN (SELECT car_id FROM pool)
				 ORDER BY c.load_count`
			)
			.all({ like }) as Omit<EligibleCar, 'tier'>[]
	).filter((c) => !taken.has(c.car_id));

	return [
		...pool.map((c) => ({ ...c, tier: 'pool' as const })),
		...station.map((c) => ({ ...c, tier: 'station' as const })),
		...priority.map((c) => ({ ...c, tier: 'priority' as const })),
		...system.map((c) => ({ ...c, tier: 'system' as const }))
	];
}

const CAR_ROW_SELECT = `
	SELECT c.id, c.reporting_marks, cc.code AS car_code, c.status, c.position, c.last_spotted,
	       co.waybill_number,
	       cs.name AS current_station, cl.code AS current_location,
	       ls.name AS loading_station, ll.code AS loading_location,
	       us.name AS unloading_station, ul.code AS unloading_location,
	       cm.code AS consignment, j.name AS job_name, s.remarks AS shipment_remarks,
	       s.special_instructions, c.remarks AS car_remarks,
	       ds.name AS dest_station, dl.code AS dest_location, dl.remarks AS dest_location_remarks,
	       (co.destination_location_id IS NOT NULL) AS is_reposition
	FROM cars c
	JOIN car_codes cc ON cc.id = c.car_code_id
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
	LEFT JOIN stations ds ON ds.id = dl.station_id
	LEFT JOIN jobs j ON j.id = c.handled_by_job_id`;

type RawCarRow = Omit<CarRow, 'is_reposition'> & { is_reposition: 0 | 1 };

function asCarRows(rows: unknown[]): CarRow[] {
	return (rows as RawCarRow[]).map((r) => ({
		...r,
		is_reposition: !!r.is_reposition
	}));
}

/** get_cars_at_station.php: movable cars (Ordered/Loaded, unassigned) at a station. */
export function carsAtStationForSwitchlist(stationId: number): CarRow[] {
	return asCarRows(
		db()
			.prepare(
				`${CAR_ROW_SELECT}
				 WHERE cl.station_id = ? AND c.status IN ('Ordered','Loaded')
				   AND c.handled_by_job_id IS NULL
				 ORDER BY cl.code, c.position, c.reporting_marks`
			)
			.all(stationId)
	);
}

/** Jobs with a pickup step at the given station. */
export function jobsPickingUpAt(stationId: number): { id: number; name: string }[] {
	return db()
		.prepare(
			`SELECT DISTINCT j.id, j.name FROM jobs j
			 JOIN job_steps js ON js.job_id = j.id
			 WHERE js.station_id = ? AND js.pickup = 1
			 ORDER BY j.name`
		)
		.all(stationId) as { id: number; name: string }[];
}

/** get_cars_position_in_job.php: cars on a job's switchlist (any location). */
export function carsInJob(jobId: number): CarRow[] {
	return asCarRows(
		db()
			.prepare(
				`${CAR_ROW_SELECT}
				 WHERE c.handled_by_job_id = ?
				 ORDER BY c.position, cl.code, c.reporting_marks`
			)
			.all(jobId)
	);
}

/**
 * printable_switchlist.php: cars on a job's switchlist in route order — each
 * car sorts by the job step at which the route visits its current station.
 */
export function carsInJobRouteOrder(jobId: number): CarRow[] {
	return asCarRows(
		db()
			.prepare(
				`${CAR_ROW_SELECT.replace(
					'SELECT c.id',
					`SELECT (SELECT MIN(js.step_number) FROM job_steps js
					          WHERE js.job_id = c.handled_by_job_id
					            AND js.station_id = cl.station_id) AS step_number,
					 c.id`
				)}
				 WHERE c.handled_by_job_id = ?
				 ORDER BY c.position, step_number, cs.name, cl.code,
				          COALESCE(ul.code, dl.code), c.reporting_marks`
			)
			.all(jobId)
	);
}

/** get_cars_in_job.php: cars in the train (picked up) for set-out. */
export function carsInTrain(jobId: number): CarRow[] {
	return asCarRows(
		db()
			.prepare(
				`${CAR_ROW_SELECT}
				 WHERE c.handled_by_job_id = ? AND c.current_location_id IS NULL
				 ORDER BY c.position, c.reporting_marks`
			)
			.all(jobId)
	);
}

/** Locations at the job's setout stations; flag each station's default location. */
export function setoutLocations(jobId: number, defaultsOnly: boolean): SetoutLocation[] {
	const rows = db()
		.prepare(
			`SELECT DISTINCT l.id, l.code, st.name AS station,
			        (l.id = st.default_setout_location_id) AS is_default
			 FROM job_steps js
			 JOIN stations st ON st.id = js.station_id
			 JOIN locations l ON l.station_id = st.id
			 WHERE js.job_id = ? AND js.setout = 1
			 ORDER BY st.sort_seq, st.name, l.code`
		)
		.all(jobId) as (Omit<SetoutLocation, 'is_default'> & { is_default: 0 | 1 })[];
	return rows
		.map((r) => ({ ...r, is_default: !!r.is_default }))
		.filter((r) => !defaultsOnly || r.is_default);
}

/**
 * load_unload.php list: Loading/Unloading cars plus Empty reposition cars
 * sitting at their destination. Each row gets a "suggested" flag using the
 * legacy random spotting-time check.
 */
export function loadUnloadList(): (CarRow & { suggested: boolean })[] {
	const session = sessionNumber();
	const rows = db()
		.prepare(
			`${CAR_ROW_SELECT.replace(
				'SELECT c.id',
				`SELECT s.min_load_time, s.max_load_time, s.min_unload_time, s.max_unload_time,
				 cs.sort_seq AS station_sort, c.id`
			)}
			 WHERE c.status IN ('Loading','Unloading')
			    OR (c.status = 'Empty' AND co.destination_location_id IS NOT NULL
			        AND c.current_location_id = co.destination_location_id)
			 ORDER BY CASE c.status WHEN 'Loading' THEN 0 WHEN 'Unloading' THEN 1 ELSE 2 END,
			          station_sort, cl.code, c.position, c.reporting_marks`
		)
		.all() as (RawCarRow & {
		min_load_time: number | null;
		max_load_time: number | null;
		min_unload_time: number | null;
		max_unload_time: number | null;
	})[];

	return rows.map((r) => {
		let suggested = false;
		// Deterministic hint: ready once the minimum spotting time has elapsed.
		// (Re-rolling rand() here made the flag flicker between page loads.)
		if (r.status === 'Loading') {
			suggested = r.last_spotted + (r.min_load_time ?? 0) <= session;
		} else if (r.status === 'Unloading') {
			suggested = r.last_spotted + (r.min_unload_time ?? 0) <= session;
		}
		return { ...r, is_reposition: !!r.is_reposition, suggested };
	});
}

/** reposition.php list: empty, unordered cars grouped by home location. */
export function repositionList(): RepositionRow[] {
	const rows = db()
		.prepare(
			`SELECT c.id, c.reporting_marks, cc.code AS car_code, c.position, c.remarks,
			        cs.name AS current_station, cl.code AS current_location,
			        hs.name AS home_station, hl.code AS home_location,
			        (c.current_location_id = c.home_location_id) AS at_home
			 FROM cars c
			 JOIN car_codes cc ON cc.id = c.car_code_id
			 LEFT JOIN locations cl ON cl.id = c.current_location_id
			 LEFT JOIN stations cs ON cs.id = cl.station_id
			 LEFT JOIN locations hl ON hl.id = c.home_location_id
			 LEFT JOIN stations hs ON hs.id = hl.station_id
			 WHERE c.status = 'Empty'
			   AND c.id NOT IN (SELECT car_id FROM car_orders WHERE car_id IS NOT NULL)
			 ORDER BY hs.sort_seq, hs.name, hl.code,
			          CASE WHEN c.current_location_id IS NOT c.home_location_id THEN 0 ELSE 1 END,
			          cl.code, c.reporting_marks`
		)
		.all() as (Omit<RepositionRow, 'at_home'> & { at_home: 0 | 1 | null })[];
	return rows.map((r) => ({ ...r, at_home: !!r.at_home }));
}

/** Cars at a location, for the organize-cars location view. */
export function carsAtLocation(locationId: number): CarRow[] {
	return asCarRows(
		db()
			.prepare(`${CAR_ROW_SELECT} WHERE c.current_location_id = ? ORDER BY c.position, c.reporting_marks`)
			.all(locationId)
	);
}

/** display_station_report.php: every car at a station (optionally hiding Unavailable). */
export function carsAtStationReport(stationId: number, hideUnavailable: boolean): CarRow[] {
	return asCarRows(
		db()
			.prepare(
				`${CAR_ROW_SELECT}
				 WHERE cl.station_id = ?
				   ${hideUnavailable ? "AND c.status != 'Unavailable'" : ''}
				 ORDER BY cl.code, c.position, c.reporting_marks`
			)
			.all(stationId)
	);
}

export function allStations(): {
	id: number;
	name: string;
	sort_seq: number | null;
	instructions: string | null;
}[] {
	return db()
		.prepare('SELECT id, name, sort_seq, instructions FROM stations ORDER BY sort_seq, name')
		.all() as { id: number; name: string; sort_seq: number | null; instructions: string | null }[];
}

export function allJobs(): { id: number; name: string; description: string | null }[] {
	return db()
		.prepare('SELECT id, name, description FROM jobs ORDER BY name')
		.all() as { id: number; name: string; description: string | null }[];
}

export function allLocations(): { id: number; code: string; station: string }[] {
	return db()
		.prepare(
			`SELECT l.id, l.code, st.name AS station FROM locations l
			 JOIN stations st ON st.id = l.station_id
			 ORDER BY st.sort_seq, st.name, l.code`
		)
		.all() as { id: number; code: string; station: string }[];
}

/**
 * auto_assign.php: build the pickup list for a job from its pu_criteria.
 * A car qualifies when it sits at the criteria step's station and its next
 * destination falls in the criteria's destination station (if set).
 */
export interface AutoAssignCandidate extends CarRow {
	pickup_station: string;
	step_nbr: number;
}

export function autoAssignCandidates(jobId: number): AutoAssignCandidate[] {
	const d = db();
	const criteria = d
		.prepare(
			`SELECT pc.step_nbr, pc.car_status, pc.commodity_id, pc.car_code_id, pc.dest_station_id,
			        js.station_id AS pickup_station_id, st.name AS pickup_station
			 FROM pu_criteria pc
			 JOIN job_steps js ON js.job_id = pc.job_id AND js.step_number = pc.step_nbr
			 JOIN stations st ON st.id = js.station_id
			 WHERE pc.job_id = ?`
		)
		.all(jobId) as {
		step_nbr: number;
		car_status: string | null;
		commodity_id: number | null;
		car_code_id: number | null;
		dest_station_id: number | null;
		pickup_station_id: number;
		pickup_station: string;
	}[];

	const out: AutoAssignCandidate[] = [];
	const seen = new Set<number>();
	for (const cr of criteria) {
		const rows = asCarRows(
			d
				.prepare(
					`${CAR_ROW_SELECT}
					 WHERE cl.station_id = @pickupStation
					   AND c.handled_by_job_id IS NULL
					   AND co.waybill_number IS NOT NULL
					   AND (@carStatus IS NULL OR c.status = @carStatus)
					   AND (@commodity IS NULL OR s.consignment_id = @commodity)
					   AND (@carCode IS NULL OR c.car_code_id = @carCode)
					   AND (
					     (c.status = 'Ordered' AND co.shipment_id IS NOT NULL
					      AND (@destStation IS NULL OR ll.station_id = @destStation))
					     OR
					     (c.status = 'Loaded'
					      AND (@destStation IS NULL OR ul.station_id = @destStation))
					     OR
					     (c.status = 'Ordered' AND co.destination_location_id IS NOT NULL
					      AND (@destStation IS NULL OR dl.station_id = @destStation))
					   )`
				)
				.all({
					pickupStation: cr.pickup_station_id,
					carStatus: cr.car_status?.trim() ? cr.car_status : null,
					commodity: cr.commodity_id,
					carCode: cr.car_code_id,
					destStation: cr.dest_station_id
				})
		);
		for (const row of rows) {
			// A car can satisfy several criteria; keep the first match so the
			// keyed {#each} on the page never sees a duplicate car id.
			if (seen.has(row.id)) continue;
			seen.add(row.id);
			out.push({ ...row, pickup_station: cr.pickup_station, step_nbr: cr.step_nbr });
		}
	}
	return out;
}
