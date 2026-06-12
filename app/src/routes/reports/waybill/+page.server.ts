import { printableWaybills, waybillData } from '$lib/server/reports';
import { getSetting, sessionNumber } from '$lib/server/db';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = ({ url }) => {
	const selected = url.searchParams.get('waybill');
	return {
		waybills: printableWaybills(),
		selected,
		waybill: selected ? waybillData(selected) : null,
		railroadName: getSetting('railroad_name'),
		session: sessionNumber()
	};
};
