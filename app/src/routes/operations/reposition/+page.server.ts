import { repositionList, allLocations } from '$lib/server/queries';
import { repositionCar, repositionAllToHome } from '$lib/server/operations';
import type { Actions, PageServerLoad } from './$types';

export const load: PageServerLoad = () => ({
	cars: repositionList(),
	locations: allLocations()
});

export const actions: Actions = {
	update: async ({ request }) => {
		const form = await request.formData();
		let repositioned = 0;
		for (const [key, value] of form.entries()) {
			if (key.startsWith('dest_for_') && String(value).length > 0) {
				const carId = parseInt(key.slice('dest_for_'.length), 10);
				const locationId = parseInt(String(value), 10);
				if (!Number.isNaN(carId) && !Number.isNaN(locationId)) {
					repositionCar(carId, locationId);
					repositioned++;
				}
			}
		}
		return { repositioned };
	},
	home: async () => {
		const count = repositionAllToHome();
		return { homeCount: count };
	}
};
