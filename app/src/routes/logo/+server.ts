import { getSetting } from '$lib/server/db';
import { error } from '@sveltejs/kit';
import type { RequestHandler } from './$types';

export const GET: RequestHandler = ({ url }) => {
	const key = url.searchParams.get('key') ?? 'logo_data';
	const dataUrl = getSetting(key) || getSetting('logo_data');

	if (!dataUrl) error(404, 'No logo configured');

	const match = dataUrl.match(/^data:([^;]+);base64,(.+)$/s);
	if (!match) error(400, 'Invalid logo data');

	const [, mime, b64] = match;
	const bytes = Buffer.from(b64, 'base64');

	return new Response(bytes, {
		headers: {
			'Content-Type': mime,
			'Cache-Control': 'public, max-age=3600'
		}
	});
};
