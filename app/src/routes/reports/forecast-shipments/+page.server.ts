import { forecast } from '$lib/server/reports';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = () => forecast('loading_location');
