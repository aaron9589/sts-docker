/**
 * Config-driven CRUD definitions for the data editor pages
 * (replaces the legacy db_list_* / db_edit_* page pairs).
 */
import { db } from './db';

export interface FieldDef {
	name: string;
	label: string;
	type: 'text' | 'number' | 'textarea' | 'select' | 'checkbox';
	/** For selects: SQL returning value/label pairs. */
	optionsSql?: string;
	required?: boolean;
	nullable?: boolean;
}

export interface EntityDef {
	table: string;
	label: string;
	labelSingular: string;
	/** SELECT producing the list view; must include id as first column. */
	listSql: string;
	listColumns: { key: string; label: string; hint?: string; badge?: 'status' | 'pool'; group?: string; width?: string; displayKey?: string }[];
	/** Column groups: collapsed into a single cell with a popover editor. */
	columnGroups?: { key: string; label: string; columns: string[]; displayKeys?: string[] }[];
	fields: FieldDef[];
	/** Tables that are view/delete-only in the editor. */
	readonly?: boolean;
	/** SQL column name whose truthy value adds a CSS class to the row (e.g. 'in_pool'). */
	rowHighlightKey?: string;
	rowHighlightClass?: string;
}

const STATION_OPTIONS = 'SELECT id AS value, name AS label FROM stations ORDER BY sort_seq, name';
const LOCATION_OPTIONS = `SELECT l.id AS value, st.name || ' - ' || l.code AS label
	FROM locations l JOIN stations st ON st.id = l.station_id ORDER BY st.sort_seq, st.name, l.code`;
const CAR_CODE_OPTIONS = 'SELECT id AS value, code AS label FROM car_codes ORDER BY code';
const COMMODITY_OPTIONS = 'SELECT id AS value, code AS label FROM commodities ORDER BY code';
const CAR_OPTIONS = 'SELECT id AS value, reporting_marks AS label FROM cars ORDER BY reporting_marks';
const SHIPMENT_OPTIONS = 'SELECT id AS value, code AS label FROM shipments ORDER BY code';
const OWNER_OPTIONS = 'SELECT id AS value, name AS label FROM owners ORDER BY name';
const JOB_OPTIONS = 'SELECT id AS value, name AS label FROM jobs ORDER BY name';

export const entities: Record<string, EntityDef> = {
	'car-codes': {
		table: 'car_codes',
		label: 'Car Codes',
		labelSingular: 'Car Code',
		listSql: 'SELECT id, code, description, remarks FROM car_codes ORDER BY code',
		listColumns: [
			{ key: 'code', label: 'Code', hint: 'Short identifier used in reports and on waybills' },
			{ key: 'description', label: 'Description', hint: 'Human-readable name for this car type' },
			{ key: 'remarks', label: 'Remarks', hint: 'Free-form notes, not shown on waybills' }
		],
		fields: [
			{ name: 'code', label: 'Code', type: 'text', required: true },
			{ name: 'description', label: 'Description', type: 'text' },
			{ name: 'remarks', label: 'Remarks', type: 'textarea' }
		]
	},
	commodities: {
		table: 'commodities',
		label: 'Commodities',
		labelSingular: 'Commodity',
		listSql: 'SELECT id, code, description, remarks FROM commodities ORDER BY code',
		listColumns: [
			{ key: 'code', label: 'Code', hint: 'Short identifier printed on waybills' },
			{ key: 'description', label: 'Description', hint: 'Human-readable name for this commodity' },
			{ key: 'remarks', label: 'Remarks', hint: 'Free-form notes, not shown on waybills' }
		],
		fields: [
			{ name: 'code', label: 'Code', type: 'text', required: true },
			{ name: 'description', label: 'Description', type: 'text' },
			{ name: 'remarks', label: 'Remarks', type: 'textarea' }
		]
	},
	stations: {
		table: 'stations',
		label: 'Stations',
		labelSingular: 'Station',
		listSql: `SELECT s.id, s.name, s.sort_seq, s.default_setout_location_id, l.code AS default_setout, s.instructions, s.color1, s.color2
			FROM stations s LEFT JOIN locations l ON l.id = s.default_setout_location_id
			ORDER BY s.sort_seq, s.name`,
		listColumns: [
			{ key: 'name', label: 'Station', hint: 'Name shown on reports and switch lists' },
			{ key: 'sort_seq', label: 'Sort Seq', hint: 'Controls the order stations appear in dropdowns and reports — lower numbers appear first' },
			{ key: 'default_setout_location_id', label: 'Default Set-out Location', hint: 'When a car is set out at this station with no specific spot, it lands here', displayKey: 'default_setout' },
			{ key: 'instructions', label: 'Routing Instructions', hint: 'Operator notes printed in the station header on switch lists' }
		],
		fields: [
			{ name: 'name', label: 'Station Name', type: 'text', required: true },
			{ name: 'sort_seq', label: 'Sort Sequence', type: 'number' },
			{
				name: 'default_setout_location_id',
				label: 'Default Set-out Location',
				type: 'select',
				optionsSql: LOCATION_OPTIONS,
				nullable: true
			},
			{ name: 'instructions', label: 'Routing Instructions', type: 'textarea' },
			{ name: 'color1', label: 'Color 1 (palette index)', type: 'number', nullable: true },
			{ name: 'color2', label: 'Color 2 (palette index)', type: 'number', nullable: true }
		]
	},
	locations: {
		table: 'locations',
		label: 'Locations',
		labelSingular: 'Location',
		listSql: `SELECT l.id, l.code, l.station_id, st.name AS station_label, l.track, l.spot, l.rpt_station, l.color, l.remarks
			FROM locations l LEFT JOIN stations st ON st.id = l.station_id
			ORDER BY st.sort_seq, st.name, l.code`,
		listColumns: [
			{ key: 'code', label: 'Code', hint: 'Short identifier for this spot, shown on switch lists and waybills' },
			{ key: 'station_id', label: 'Station', hint: 'The station this location belongs to', displayKey: 'station_label' },
			{ key: 'track', label: 'Track', hint: 'Track name or number at this location (informational)' },
			{ key: 'spot', label: 'Spot', hint: 'Specific spot identifier on the track (informational)' },
			{ key: 'rpt_station', label: 'Report Station', hint: 'Alternate station name printed on waybills — overrides the station name for this location only' },
			{ key: 'remarks', label: 'Routing Instructions', hint: 'Operator notes; a ⚑ flag is shown on X2010 waybills for reposition cars when this is set' }
		],
		fields: [
			{ name: 'code', label: 'Location Code', type: 'text', required: true },
			{ name: 'station_id', label: 'Station', type: 'select', optionsSql: STATION_OPTIONS, required: true },
			{ name: 'track', label: 'Track', type: 'text' },
			{ name: 'spot', label: 'Spot', type: 'text' },
			{ name: 'rpt_station', label: 'Report Station (waybill substitution)', type: 'text' },
			{ name: 'color', label: 'Color (CSS value)', type: 'text' },
			{ name: 'remarks', label: 'Routing Instructions (⚑ flag on X2010 for reposition cars)', type: 'textarea' }
		]
	},
	cars: {
		table: 'cars',
		label: 'Cars',
		labelSingular: 'Car',
		listSql: `SELECT c.id, c.reporting_marks, c.car_code_id, cc.code AS car_code_label,
			c.status,
			c.current_location_id, lst.name || ' - ' || ll.code AS current_location_label,
			c.home_location_id, hst.name || ' - ' || hl.code AS home_location_label,
			c.load_count, c.rfid_code, c.position, c.remarks,
			COALESCE(pool_agg.shipment_codes, '') AS pool_shipments,
			CASE WHEN pool_agg.car_id IS NOT NULL THEN 1 ELSE 0 END AS in_pool
			FROM cars c
			LEFT JOIN car_codes cc ON cc.id = c.car_code_id
			LEFT JOIN locations ll ON ll.id = c.current_location_id
			LEFT JOIN stations lst ON lst.id = ll.station_id
			LEFT JOIN locations hl ON hl.id = c.home_location_id
			LEFT JOIN stations hst ON hst.id = hl.station_id
			LEFT JOIN (
				SELECT p.car_id, GROUP_CONCAT(s.code, ', ') AS shipment_codes
				FROM pool p JOIN shipments s ON s.id = p.shipment_id
				GROUP BY p.car_id
			) pool_agg ON pool_agg.car_id = c.id
			ORDER BY c.reporting_marks`,
		listColumns: [
			{ key: 'reporting_marks', label: 'Reporting Marks', hint: 'Road number printed on the car, e.g. BNSF 12345' },
			{ key: 'car_code_id', label: 'Car Code', hint: 'Car type code — determines which shipments this car can fill', displayKey: 'car_code_label' },
			{ key: 'status', label: 'Status', hint: 'Current operational status in the car lifecycle (Empty → Ordered → Loading → Loaded → Unloading → Empty)', badge: 'status' },
			{ key: 'pool_shipments', label: 'Pool', hint: 'Shipments this car is dedicated to via the Special Pool', badge: 'pool' },
			{ key: 'current_location_id', label: 'Current Location', hint: 'Where the car is sitting; blank means the car is currently in a train', displayKey: 'current_location_label' },
			{ key: 'home_location_id', label: 'Home Location', hint: 'Where the car returns to when repositioned as an empty', displayKey: 'home_location_label' },
			{ key: 'load_count', label: 'Loads', hint: 'Cumulative number of revenue loads this car has carried across all sessions' },
			{ key: 'rfid_code', label: 'RFID', hint: 'Optional RFID tag code used by the mobile scanner API to identify this car' }
		],
		rowHighlightKey: 'in_pool',
		rowHighlightClass: 'row-pooled',
		fields: [
			{ name: 'reporting_marks', label: 'Reporting Marks', type: 'text', required: true },
			{ name: 'car_code_id', label: 'Car Code', type: 'select', optionsSql: CAR_CODE_OPTIONS, required: true },
			{
				name: 'status',
				label: 'Status',
				type: 'select',
				optionsSql: `SELECT 'Empty' AS value, 'Empty' AS label UNION ALL SELECT 'Ordered','Ordered'
					UNION ALL SELECT 'Loading','Loading' UNION ALL SELECT 'Loaded','Loaded'
					UNION ALL SELECT 'Unloading','Unloading' UNION ALL SELECT 'Unavailable','Unavailable'`
			},
			{ name: 'current_location_id', label: 'Current Location (blank = in train)', type: 'select', optionsSql: LOCATION_OPTIONS, nullable: true },
			{ name: 'home_location_id', label: 'Home Location', type: 'select', optionsSql: LOCATION_OPTIONS, nullable: true },
			{ name: 'position', label: 'Position', type: 'number' },
			{ name: 'load_count', label: 'Load Count', type: 'number' },
			{ name: 'rfid_code', label: 'RFID Code', type: 'text' },
			{ name: 'remarks', label: 'Remarks', type: 'textarea' }
		]
	},
	shipments: {
		table: 'shipments',
		label: 'Shipments',
		labelSingular: 'Shipment',
		listSql: `SELECT s.id, s.code, s.description, s.consignment_id, cm.code AS consignment_label,
			s.car_code_id, cc.code AS car_code_label,
			s.loading_location_id, lst.name || ' - ' || ll.code AS loading_location_label,
			s.unloading_location_id, ust.name || ' - ' || ul.code AS unloading_location_label,
			s.last_ship_date, s.min_interval, s.max_interval, s.min_amount, s.max_amount,
			s.min_load_time, s.max_load_time, s.min_unload_time, s.max_unload_time,
			s.special_instructions, s.remarks
			FROM shipments s
			LEFT JOIN commodities cm ON cm.id = s.consignment_id
			LEFT JOIN car_codes cc ON cc.id = s.car_code_id
			LEFT JOIN locations ll ON ll.id = s.loading_location_id
			LEFT JOIN stations lst ON lst.id = ll.station_id
			LEFT JOIN locations ul ON ul.id = s.unloading_location_id
			LEFT JOIN stations ust ON ust.id = ul.station_id
			ORDER BY s.code`,
		listColumns: [
			{ key: 'code', label: 'Code', hint: 'Short identifier for this shipment, shown on waybills' },
			{ key: 'description', label: 'Description', hint: 'Human-readable name for this traffic lane', width: '25ch' },
			{ key: 'consignment_id', label: 'Commodity', hint: 'The commodity being shipped — printed on waybills', displayKey: 'consignment_label' },
			{ key: 'car_code_id', label: 'Car Code', hint: 'Car type required for this shipment; supports * as a wildcard', displayKey: 'car_code_label' },
			{ key: 'loading_location_id', label: 'Loading', hint: 'Where empty cars are spotted and loaded', group: 'route' },
			{ key: 'unloading_location_id', label: 'Unloading', hint: 'Where loaded cars are spotted and unloaded', group: 'route' },
			{ key: 'last_ship_date', label: 'Last Ship', hint: 'Session number when this shipment last generated a car order — used to enforce min/max interval', group: 'schedule' },
			{ key: 'min_interval', label: 'Min Interval', hint: 'Minimum number of sessions that must pass before this shipment can generate another order', group: 'schedule' },
			{ key: 'max_interval', label: 'Max Interval', hint: 'Maximum number of sessions between orders — a new order is forced if this is exceeded', group: 'schedule' },
			{ key: 'min_amount', label: 'Min Amount', hint: 'Minimum cars ordered per session when this shipment fires', group: 'schedule' },
			{ key: 'max_amount', label: 'Max Amount', hint: 'Maximum cars ordered per session when this shipment fires', group: 'schedule' }
		],
		columnGroups: [
			{ key: 'route', label: 'Route', columns: ['loading_location_id', 'unloading_location_id'], displayKeys: ['loading_location_label', 'unloading_location_label'] },
			{ key: 'schedule', label: 'Schedule', columns: ['last_ship_date', 'min_interval', 'max_interval', 'min_amount', 'max_amount'] }
		],
		fields: [
			{ name: 'code', label: 'Shipment Code', type: 'text', required: true },
			{ name: 'description', label: 'Description', type: 'text' },
			{ name: 'consignment_id', label: 'Commodity', type: 'select', optionsSql: COMMODITY_OPTIONS, nullable: true },
			{ name: 'car_code_id', label: 'Car Code', type: 'select', optionsSql: CAR_CODE_OPTIONS, nullable: true },
			{ name: 'loading_location_id', label: 'Loading Location', type: 'select', optionsSql: LOCATION_OPTIONS, nullable: true },
			{ name: 'unloading_location_id', label: 'Unloading Location', type: 'select', optionsSql: LOCATION_OPTIONS, nullable: true },
			{ name: 'last_ship_date', label: 'Last Ship Date (session)', type: 'number' },
			{ name: 'min_interval', label: 'Min Interval', type: 'number' },
			{ name: 'max_interval', label: 'Max Interval', type: 'number' },
			{ name: 'min_amount', label: 'Min Amount', type: 'number' },
			{ name: 'max_amount', label: 'Max Amount', type: 'number' },
			{ name: 'min_load_time', label: 'Min Load Time (negative = instant)', type: 'number' },
			{ name: 'max_load_time', label: 'Max Load Time', type: 'number' },
			{ name: 'min_unload_time', label: 'Min Unload Time (negative = instant)', type: 'number' },
			{ name: 'max_unload_time', label: 'Max Unload Time', type: 'number' },
			{ name: 'special_instructions', label: 'Special Instructions (⚑ flag on X2010 for revenue cars)', type: 'textarea' },
			{ name: 'remarks', label: 'Remarks', type: 'textarea' }
		]
	},
	'car-orders': {
		table: 'car_orders',
		label: 'Car Orders',
		labelSingular: 'Car Order',
		listSql: `SELECT co.waybill_number AS id, co.waybill_number, s.code AS shipment,
			dst.name || ' - ' || dl.code AS destination, c.reporting_marks AS car
			FROM car_orders co
			LEFT JOIN shipments s ON s.id = co.shipment_id
			LEFT JOIN locations dl ON dl.id = co.destination_location_id
			LEFT JOIN stations dst ON dst.id = dl.station_id
			LEFT JOIN cars c ON c.id = co.car_id
			ORDER BY co.waybill_number`,
		listColumns: [
			{ key: 'waybill_number', label: 'Waybill', hint: 'Unique waybill number for this car order' },
			{ key: 'shipment', label: 'Shipment', hint: 'The shipment this order was generated for' },
			{ key: 'destination', label: 'Reposition Destination', hint: 'For E-waybills (non-revenue moves), the location the empty car must be repositioned to' },
			{ key: 'car', label: 'Car', hint: 'The car assigned to fill this order — blank means the order is unfilled' }
		],
		fields: [],
		readonly: true
	},
	pool: {
		table: 'pool',
		label: 'Special Pool',
		labelSingular: 'Pool Entry',
		listSql: `SELECT p.rowid AS id, p.car_id, c.reporting_marks AS car_label, p.shipment_id, s.code AS shipment_label
			FROM pool p
			JOIN cars c ON c.id = p.car_id
			JOIN shipments s ON s.id = p.shipment_id
			ORDER BY s.code, c.reporting_marks`,
		listColumns: [
			{ key: 'car_id', label: 'Car', hint: 'Car dedicated to this shipment — offered first when filling orders, before any other available cars', displayKey: 'car_label' },
			{ key: 'shipment_id', label: 'Shipment', hint: 'The shipment this car is reserved for', displayKey: 'shipment_label' }
		],
		fields: [
			{ name: 'car_id', label: 'Car', type: 'select', optionsSql: CAR_OPTIONS, required: true },
			{ name: 'shipment_id', label: 'Shipment', type: 'select', optionsSql: SHIPMENT_OPTIONS, required: true }
		]
	},
	'empty-locations': {
		table: 'empty_locations',
		label: 'Priority Empty Locations',
		labelSingular: 'Priority Location',
		listSql: `SELECT el.rowid AS id, el.shipment_id, s.code AS shipment_label, el.priority,
			el.location_id, lst.name || ' - ' || l.code AS location_label
			FROM empty_locations el
			JOIN shipments s ON s.id = el.shipment_id
			LEFT JOIN locations l ON l.id = el.location_id
			LEFT JOIN stations lst ON lst.id = l.station_id
			ORDER BY s.code, el.priority`,
		listColumns: [
			{ key: 'shipment_id', label: 'Shipment', hint: 'The shipment that prefers to draw empty cars from this location', displayKey: 'shipment_label' },
			{ key: 'priority', label: 'Priority', hint: '1 = highest priority — when filling this shipment, empties at lower-numbered priority locations are used first' },
			{ key: 'location_id', label: 'Location', hint: 'A location to look for available empty cars before falling back to the general pool', displayKey: 'location_label' }
		],
		fields: [
			{ name: 'shipment_id', label: 'Shipment', type: 'select', optionsSql: SHIPMENT_OPTIONS, required: true },
			{ name: 'priority', label: 'Priority (1 = highest)', type: 'number', required: true },
			{ name: 'location_id', label: 'Location', type: 'select', optionsSql: LOCATION_OPTIONS, required: true }
		]
	},
	owners: {
		table: 'owners',
		label: 'Owners',
		labelSingular: 'Owner',
		listSql: 'SELECT id, name, remarks FROM owners ORDER BY name',
		listColumns: [
			{ key: 'name', label: 'Name', hint: 'Owner name, e.g. a club member or group' },
			{ key: 'remarks', label: 'Remarks', hint: 'Free-form notes about this owner' }
		],
		fields: [
			{ name: 'name', label: 'Name', type: 'text', required: true },
			{ name: 'remarks', label: 'Remarks', type: 'textarea' }
		]
	},
	ownership: {
		table: 'ownership',
		label: 'Ownership',
		labelSingular: 'Ownership Link',
		listSql: `SELECT ow.rowid AS id, ow.car_id, c.reporting_marks AS car_label, ow.owner_id, o.name AS owner_label, ow.on_off_rr
			FROM ownership ow
			JOIN cars c ON c.id = ow.car_id
			JOIN owners o ON o.id = ow.owner_id
			ORDER BY o.name, c.reporting_marks`,
		listColumns: [
			{ key: 'car_id', label: 'Car', hint: 'The car being tracked for ownership', displayKey: 'car_label' },
			{ key: 'owner_id', label: 'Owner', hint: 'The owner this car belongs to', displayKey: 'owner_label' },
			{ key: 'on_off_rr', label: 'On/Off Railroad', hint: '"on" = car is on the railroad; any other value is the saved car status from before it was removed (restored when the car is put back on)' }
		],
		fields: [
			{ name: 'car_id', label: 'Car', type: 'select', optionsSql: CAR_OPTIONS, required: true },
			{ name: 'owner_id', label: 'Owner', type: 'select', optionsSql: OWNER_OPTIONS, required: true }
		]
	},
	'pu-criteria': {
		table: 'pu_criteria',
		label: 'Auto-Assign Criteria',
		labelSingular: 'Pickup Criteria',
		listSql: `SELECT pc.id, pc.job_id, j.name AS job_label, pc.step_nbr, pc.car_status,
			pc.commodity_id, cm.code AS commodity_label,
			pc.car_code_id, cc.code AS car_code_label,
			pc.dest_station_id, st.name AS dest_station_label
			FROM pu_criteria pc
			JOIN jobs j ON j.id = pc.job_id
			LEFT JOIN commodities cm ON cm.id = pc.commodity_id
			LEFT JOIN car_codes cc ON cc.id = pc.car_code_id
			LEFT JOIN stations st ON st.id = pc.dest_station_id
			ORDER BY j.name, pc.step_nbr`,
		listColumns: [
			{ key: 'job_id', label: 'Job', hint: 'The job (train/switch run) this auto-assign rule applies to', displayKey: 'job_label' },
			{ key: 'step_nbr', label: 'Step', hint: 'Evaluation order — lower step numbers are checked first when auto-assigning cars to this job' },
			{ key: 'car_status', label: 'Car Status', hint: 'Restricts this rule to cars in this status (Loaded or Empty); blank means any status matches', badge: 'status' },
			{ key: 'commodity_id', label: 'Commodity', hint: 'Restricts this rule to cars carrying this commodity; blank means any commodity matches', displayKey: 'commodity_label' },
			{ key: 'car_code_id', label: 'Car Code', hint: 'Restricts this rule to cars of this type; blank means any car code matches', displayKey: 'car_code_label' },
			{ key: 'dest_station_id', label: 'Destination Station', hint: 'Restricts this rule to cars destined for this station; blank means any destination matches', displayKey: 'dest_station_label' }
		],
		fields: [
			{ name: 'job_id', label: 'Job', type: 'select', optionsSql: JOB_OPTIONS, required: true },
			{ name: 'step_nbr', label: 'Step Number', type: 'number', required: true },
			{
				name: 'car_status',
				label: 'Car Status (blank = any)',
				type: 'select',
				optionsSql: `SELECT 'Loaded' AS value, 'Loaded' AS label UNION ALL SELECT 'Empty','Empty'`,
				nullable: true
			},
			{ name: 'commodity_id', label: 'Commodity (blank = any)', type: 'select', optionsSql: COMMODITY_OPTIONS, nullable: true },
			{ name: 'car_code_id', label: 'Car Code (blank = any)', type: 'select', optionsSql: CAR_CODE_OPTIONS, nullable: true },
			{ name: 'dest_station_id', label: 'Destination Station (blank = any)', type: 'select', optionsSql: STATION_OPTIONS, nullable: true }
		]
	}
};

export function fieldOptions(def: EntityDef): Record<string, { value: unknown; label: string }[]> {
	const out: Record<string, { value: unknown; label: string }[]> = {};
	for (const f of def.fields) {
		if (f.type === 'select' && f.optionsSql) {
			out[f.name] = db().prepare(f.optionsSql).all() as { value: unknown; label: string }[];
		}
	}
	return out;
}
