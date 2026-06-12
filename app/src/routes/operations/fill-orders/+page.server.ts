import { openOrders, eligibleCarsForOrder } from '$lib/server/queries';
import { assignCarToOrder } from '$lib/server/operations';
import { fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = () => ({ orders: openOrders() });

export const actions: Actions = {
	cars: async ({ request }) => {
		const form = await request.formData();
		const waybill = String(form.get('waybill') ?? '');
		if (!waybill) return fail(400, { error: 'Missing waybill' });
		return { waybill, cars: eligibleCarsForOrder(waybill) };
	},
	assign: async ({ request }) => {
		const form = await request.formData();
		const waybill = String(form.get('waybill') ?? '');
		const carId = parseInt(String(form.get('car_id') ?? ''), 10);
		if (!waybill || Number.isNaN(carId)) return fail(400, { error: 'Missing parameters' });
		assignCarToOrder(waybill, carId);
		return { assigned: waybill };
	}
};
