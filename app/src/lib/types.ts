/** Shared row shapes used by both server queries and Svelte components. */

export interface CarRow {
	id: number;
	reporting_marks: string;
	car_code: string;
	status: string;
	position: number;
	waybill_number: string | null;
	current_station: string | null;
	current_location: string | null;
	loading_station: string | null;
	loading_location: string | null;
	unloading_station: string | null;
	unloading_location: string | null;
	consignment: string | null;
	job_name: string | null;
	last_spotted: number;
	shipment_remarks: string | null;
	special_instructions: string | null;
	/** The car's own remarks (x2010 form encodes "gross mass|length" here). */
	car_remarks: string | null;
	/** For reposition (E) waybills: the destination looked up from the order. */
	dest_station: string | null;
	dest_location: string | null;
	/** Routing remarks of the reposition destination location. */
	dest_location_remarks: string | null;
	is_reposition: boolean;
}

export type CarTier = 'pool' | 'station' | 'priority' | 'system';

export interface EligibleCar {
	car_id: number;
	reporting_marks: string;
	car_code: string;
	current_station: string | null;
	current_location: string | null;
	load_count: number;
	remarks: string | null;
	tier: CarTier;
}

export interface OpenOrder {
	waybill_number: string;
	shipment_id: number;
	shipment_code: string;
	description: string | null;
	consignment: string | null;
	car_code: string;
	loading_station: string;
	loading_location: string;
	unloading_station: string;
	unloading_location: string;
	remarks: string | null;
	pool_count: number;
}

export interface SetoutLocation {
	id: number;
	code: string;
	station: string;
	is_default: boolean;
}

export interface RepositionRow {
	id: number;
	reporting_marks: string;
	car_code: string;
	position: number;
	remarks: string | null;
	current_station: string | null;
	current_location: string | null;
	home_station: string | null;
	home_location: string | null;
	at_home: boolean;
}
