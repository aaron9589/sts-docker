import { findCarByTag, json, apiError } from '$lib/server/api';
import type { RequestHandler } from './$types';

export const GET: RequestHandler = ({ params }) => {
	const car = findCarByTag(decodeURIComponent(params.tag));
	if (!car) return apiError('Car not found', 404);
	return json(car);
};
