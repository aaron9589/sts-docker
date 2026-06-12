/**
 * Core STS workflows. Each function implements the rules in docs/SPEC.md §3–§5,
 * preserving the legacy PHP behaviour exactly (file references in comments).
 */
import {
	db,
	addHistory,
	sessionNumber,
	setSetting,
	randAmount,
	randInt,
	pad2,
	pad3,
	nextLetterWaybillCounter,
	isRepositionWaybill
} from './db';

export interface GenerateResult {
	session: number;
	waybills: string[];
}

/** generate.php automatic mode: bump session, order cars for due shipments. */
export function generateAutomatic(): GenerateResult {
	const d = db();
	const session = sessionNumber() + 1;
	const waybills: string[] = [];

	d.transaction(() => {
		setSetting('session_nbr', String(session));
		const shipments = d
			.prepare(
				`SELECT id, last_ship_date, min_interval, max_interval, min_amount, max_amount
				 FROM shipments`
			)
			.all() as {
			id: number;
			last_ship_date: number;
			min_interval: number;
			max_interval: number;
			min_amount: number;
			max_amount: number;
		}[];

		let counter = 0;
		const markShipped = d.prepare('UPDATE shipments SET last_ship_date = ? WHERE id = ?');
		const insertOrder = d.prepare(
			'INSERT INTO car_orders (waybill_number, shipment_id, car_id) VALUES (?, ?, NULL)'
		);

		for (const s of shipments) {
			const interval = randAmount(s.min_interval, s.max_interval);
			if (s.last_ship_date + interval <= session) {
				markShipped.run(session, s.id);
				const numCars = randAmount(s.min_amount, s.max_amount);
				for (let i = 0; i < numCars; i++) {
					counter++;
					const wb = `${pad3(session)}-${pad3(counter)}`;
					insertOrder.run(wb, s.id);
					waybills.push(wb);
				}
			}
		}
	})();

	return { session, waybills };
}

/** generate.php manual mode: order cars for the selected shipments (SSS-MNN). */
export function generateManual(shipmentIds: number[]): GenerateResult {
	const d = db();
	const session = sessionNumber();
	const waybills: string[] = [];

	d.transaction(() => {
		let counter = nextLetterWaybillCounter('M', session);
		const markShipped = d.prepare('UPDATE shipments SET last_ship_date = ? WHERE id = ?');
		const insertOrder = d.prepare(
			'INSERT INTO car_orders (waybill_number, shipment_id, car_id) VALUES (?, ?, NULL)'
		);
		const getAmounts = d.prepare('SELECT min_amount, max_amount FROM shipments WHERE id = ?');

		for (const id of shipmentIds) {
			const s = getAmounts.get(id) as { min_amount: number; max_amount: number } | undefined;
			if (!s) continue;
			markShipped.run(session, id);
			const numCars = randAmount(s.min_amount, s.max_amount);
			for (let i = 0; i < numCars; i++) {
				const wb = `${pad3(session)}-M${pad2(counter++)}`;
				insertOrder.run(wb, id);
				waybills.push(wb);
			}
		}
	})();

	return { session, waybills };
}

/**
 * assign_car_ajax.php: fill a car order. If the car is already Empty at the
 * shipment's loading location it goes straight to Loaded, otherwise Ordered.
 * load_count increments either way.
 */
export function assignCarToOrder(waybillNumber: string, carId: number): void {
	const d = db();
	d.transaction(() => {
		d.prepare('UPDATE car_orders SET car_id = ? WHERE waybill_number = ?').run(
			carId,
			waybillNumber
		);

		const car = d
			.prepare('SELECT current_location_id, status FROM cars WHERE id = ?')
			.get(carId) as { current_location_id: number | null; status: string };
		addHistory(carId, `Filled car order ${waybillNumber}`, car.current_location_id);

		const atLoading = d
			.prepare(
				`SELECT COUNT(*) AS n FROM car_orders co
				 JOIN shipments s ON s.id = co.shipment_id
				 JOIN cars c ON c.id = co.car_id
				 WHERE co.waybill_number = ? AND c.status = 'Empty'
				   AND c.current_location_id = s.loading_location_id`
			)
			.get(waybillNumber) as { n: number };

		const newStatus = atLoading.n > 0 ? 'Loaded' : 'Ordered';
		d.prepare('UPDATE cars SET status = ?, load_count = load_count + 1 WHERE id = ?').run(
			newStatus,
			carId
		);
	})();
}

/** build_switchlists.php / auto_assign.php: put a car on a job's switchlist. */
export function assignCarToJob(carId: number, jobId: number): void {
	const d = db();
	d.transaction(() => {
		d.prepare('UPDATE cars SET handled_by_job_id = ? WHERE id = ?').run(jobId, carId);
		const job = d.prepare('SELECT name FROM jobs WHERE id = ?').get(jobId) as { name: string };
		const car = d.prepare('SELECT current_location_id FROM cars WHERE id = ?').get(carId) as {
			current_location_id: number | null;
		};
		addHistory(carId, `Assigned to Job ${job.name}`, car.current_location_id);
	})();
}

/** pick_up.php: car goes into the train (location NULL). Position is preserved. */
export function pickUpCar(carId: number): void {
	const d = db();
	d.transaction(() => {
		const car = d
			.prepare(
				`SELECT c.current_location_id, j.name AS job_name
				 FROM cars c LEFT JOIN jobs j ON j.id = c.handled_by_job_id
				 WHERE c.id = ?`
			)
			.get(carId) as { current_location_id: number | null; job_name: string | null };
		d.prepare('UPDATE cars SET current_location_id = NULL WHERE id = ?').run(carId);
		addHistory(carId, `Picked up by Job ${car.job_name ?? ''}`, car.current_location_id);
	})();
}

/** Reverse a recent pickup: restore the car's location from its last pickup history entry. */
export function undoPickUpCar(carId: number, jobId: number): void {
	const d = db();
	d.transaction(() => {
		const hist = d
			.prepare(
				`SELECT location_id FROM history
				 WHERE car_id = ? AND event LIKE 'Picked up by Job%'
				 ORDER BY rowid DESC LIMIT 1`
			)
			.get(carId) as { location_id: number | null } | undefined;
		const locationId = hist?.location_id ?? null;
		d.prepare('UPDATE cars SET current_location_id = ?, handled_by_job_id = ? WHERE id = ?').run(
			locationId,
			jobId,
			carId
		);
		d.prepare(`DELETE FROM history WHERE car_id = ? AND event LIKE 'Picked up by Job%' AND rowid = (
			SELECT rowid FROM history WHERE car_id = ? AND event LIKE 'Picked up by Job%' ORDER BY rowid DESC LIMIT 1
		)`).run(carId, carId);
	})();
}

/**
 * set_out.php: place a car at a location and run the status state machine,
 * including instant load/unload when the shipment's min/max times are negative.
 */
export function setOutCar(carId: number, locationId: number): void {
	const d = db();
	const session = sessionNumber();

	d.transaction(() => {
		const before = d
			.prepare(
				`SELECT c.status, j.name AS job_name FROM cars c
				 LEFT JOIN jobs j ON j.id = c.handled_by_job_id WHERE c.id = ?`
			)
			.get(carId) as { status: string; job_name: string | null };

		d.prepare(
			'UPDATE cars SET current_location_id = ?, handled_by_job_id = NULL, position = 0 WHERE id = ?'
		).run(locationId, carId);
		addHistory(carId, `Set out by Job ${before.job_name ?? ''}`, locationId);

		// Ordered at loading location -> Loading
		d.prepare(
			`UPDATE cars SET status = 'Loading', last_spotted = ?
			 WHERE id = ? AND status = 'Ordered'
			   AND EXISTS (SELECT 1 FROM car_orders co JOIN shipments s ON s.id = co.shipment_id
			               WHERE co.car_id = cars.id AND cars.current_location_id = s.loading_location_id)`
		).run(session, carId);

		// Loaded at unloading location -> Unloading
		d.prepare(
			`UPDATE cars SET status = 'Unloading', last_spotted = ?
			 WHERE id = ? AND status = 'Loaded'
			   AND EXISTS (SELECT 1 FROM car_orders co JOIN shipments s ON s.id = co.shipment_id
			               WHERE co.car_id = cars.id AND cars.current_location_id = s.unloading_location_id)`
		).run(session, carId);

		// Reposition order arrived at its destination -> Empty + order deleted
		const repoDone = d
			.prepare(
				`UPDATE cars SET status = 'Empty'
				 WHERE id = ? AND status = 'Ordered'
				   AND EXISTS (SELECT 1 FROM car_orders co
				               WHERE co.car_id = cars.id AND co.destination_location_id IS NOT NULL
				                 AND co.destination_location_id = cars.current_location_id)`
			)
			.run(carId);
		if (repoDone.changes > 0) {
			d.prepare('DELETE FROM car_orders WHERE car_id = ?').run(carId);
		}

		// Instant load/unload: negative min or max time skips the in-progress state.
		const cur = d
			.prepare(
				`SELECT c.status, s.min_load_time, s.max_load_time, s.min_unload_time, s.max_unload_time
				 FROM cars c
				 JOIN car_orders co ON co.car_id = c.id
				 JOIN shipments s ON s.id = co.shipment_id
				 WHERE c.id = ?`
			)
			.get(carId) as
			| {
					status: string;
					min_load_time: number;
					max_load_time: number;
					min_unload_time: number;
					max_unload_time: number;
			  }
			| undefined;

		if (cur) {
			if (cur.status === 'Loading' && (cur.min_load_time < 0 || cur.max_load_time < 0)) {
				d.prepare("UPDATE cars SET status = 'Loaded', last_spotted = 0 WHERE id = ?").run(carId);
			}
			if (cur.status === 'Unloading' && (cur.min_unload_time < 0 || cur.max_unload_time < 0)) {
				d.prepare("UPDATE cars SET status = 'Empty', last_spotted = 0 WHERE id = ?").run(carId);
				d.prepare('DELETE FROM car_orders WHERE car_id = ?').run(carId);
			}
		}
	})();
}

/**
 * load_unload.php UPDATE: complete a load/unload cycle for one car.
 * Loading -> Loaded; Unloading -> Empty (order deleted);
 * Empty (reposition car at destination) -> order deleted.
 */
export function completeLoadUnload(carId: number, currentStatus: string): void {
	const d = db();
	d.transaction(() => {
		let newStatus = currentStatus;
		if (currentStatus === 'Loading') {
			newStatus = 'Loaded';
		} else if (currentStatus === 'Unloading' || currentStatus === 'Empty') {
			newStatus = 'Empty';
			d.prepare('DELETE FROM car_orders WHERE car_id = ?').run(carId);
		}
		d.prepare('UPDATE cars SET status = ?, last_spotted = 0 WHERE id = ?').run(newStatus, carId);
	})();
}

/**
 * load_unload.php pre-check suggestion: a car is "ready" when
 * last_spotted + rand(min_time, max_time) <= current session.
 */
export function suggestReady(
	status: string,
	lastSpotted: number,
	minTime: number,
	maxTime: number,
	session = sessionNumber()
): boolean {
	if (status !== 'Loading' && status !== 'Unloading') return false;
	return lastSpotted + randInt(minTime, maxTime) <= session;
}

/** reposition.php: create an E-waybill sending an empty car to a destination. */
export function repositionCar(carId: number, destinationLocationId: number): string {
	const d = db();
	const session = sessionNumber();
	let waybill = '';

	d.transaction(() => {
		const counter = nextLetterWaybillCounter('E', session);
		waybill = `${pad3(session)}-E${pad2(counter)}`;
		d.prepare(
			'INSERT INTO car_orders (waybill_number, destination_location_id, car_id) VALUES (?, ?, ?)'
		).run(waybill, destinationLocationId, carId);
		d.prepare("UPDATE cars SET status = 'Ordered' WHERE id = ?").run(carId);

		const car = d.prepare('SELECT current_location_id FROM cars WHERE id = ?').get(carId) as {
			current_location_id: number | null;
		};
		const dest = d.prepare('SELECT code FROM locations WHERE id = ?').get(destinationLocationId) as
			| { code: string }
			| undefined;
		addHistory(carId, `Repositioned to ${dest?.code ?? ''}`, car.current_location_id);
	})();

	return waybill;
}

/** repo_to_home.php: reposition every empty, unordered, away-from-home car to home. */
export function repositionAllToHome(): number {
	const d = db();
	const cars = d
		.prepare(
			`SELECT id, home_location_id FROM cars
			 WHERE status = 'Empty' AND home_location_id IS NOT NULL
			   AND current_location_id IS NOT NULL
			   AND current_location_id != home_location_id
			   AND id NOT IN (SELECT car_id FROM car_orders WHERE car_id IS NOT NULL)`
		)
		.all() as { id: number; home_location_id: number }[];

	for (const car of cars) {
		repositionCar(car.id, car.home_location_id);
	}
	return cars.length;
}

/** organize_cars.php: persist a new car ordering (1-based positions). */
export function saveCarPositions(carIdsInOrder: number[]): void {
	const d = db();
	const update = d.prepare('UPDATE cars SET position = ? WHERE id = ?');
	d.transaction(() => {
		carIdsInOrder.forEach((carId, i) => update.run(i + 1, carId));
	})();
}

/** restart.php / reset.php. Reset additionally zeroes load counts. */
export function restartSimulation(opts: { resetLoadCounts: boolean }): void {
	const d = db();
	d.transaction(() => {
		d.prepare('UPDATE shipments SET last_ship_date = 0').run();
		d.prepare('DELETE FROM car_orders').run();
		d.prepare(
			`UPDATE cars SET status = 'Empty', handled_by_job_id = NULL, last_spotted = 0
			 WHERE status != 'Unavailable'`
		).run();
		d.prepare(
			'UPDATE cars SET current_location_id = home_location_id WHERE home_location_id IS NOT NULL'
		).run();
		if (opts.resetLoadCounts) {
			d.prepare('UPDATE cars SET load_count = 0').run();
		}
		setSetting('session_nbr', '0');
	})();
}

/** wipe.php: clear all data, keep the schema, reset settings to defaults. */
export function wipeDatabase(): void {
	const d = db();
	d.transaction(() => {
		for (const table of [
			'history',
			'pool',
			'empty_locations',
			'pu_criteria',
			'job_steps',
			'car_orders',
			'ownership',
			'owners',
			'cars',
			'shipments',
			'jobs',
			'locations',
			'stations',
			'car_codes',
			'commodities'
		]) {
			d.prepare(`DELETE FROM ${table}`).run();
		}
		setSetting('session_nbr', '0');
		setSetting('print_width', '7.5in');
		setSetting('railroad_name', '');
		setSetting('railroad_initials', '');
	})();
}

export { isRepositionWaybill };
