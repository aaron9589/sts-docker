import { allStations, carsAtStationReport } from '$lib/server/queries';
import { getSetting } from '$lib/server/db';
import type { CarRow } from '$lib/types';
import type { PageServerLoad } from './$types';

export interface StationSection {
	stationId: number;
	stationName: string;
	cars: CarRow[];
}

export const load: PageServerLoad = ({ url }) => {
	const stationParam = url.searchParams.get('station');
	const hideUnavail = url.searchParams.get('hide_unavail') === '1';
	const stations = allStations();

	let sections: StationSection[] = [];
	if (stationParam === 'all') {
		sections = stations
			.map((s) => ({
				stationId: s.id,
				stationName: s.name,
				cars: carsAtStationReport(s.id, hideUnavail)
			}))
			.filter((s) => s.cars.length > 0);
	} else if (stationParam) {
		const id = parseInt(stationParam, 10);
		const station = stations.find((s) => s.id === id);
		if (station) {
			sections = [
				{ stationId: id, stationName: station.name, cars: carsAtStationReport(id, hideUnavail) }
			];
		}
	}

	return {
		stations,
		selected: stationParam,
		hideUnavail,
		sections,
		railroadName: getSetting('railroad_name')
	};
};
