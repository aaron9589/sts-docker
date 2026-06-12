import { findLocation, json, apiError } from '$lib/server/api';
import type { RequestHandler } from './$types';

export const GET: RequestHandler = ({ params }) => {
	const location = findLocation(decodeURIComponent(params.name));
	if (!location) return apiError('Location not found', 404);
	return json(location);
};
