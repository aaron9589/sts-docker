import type { Reroute } from '@sveltejs/kit';

/**
 * Legacy API path compatibility: the PHP app served the REST API at
 * /sts/api/index.php/wagon/... — reroute those to the new /api/wagon/... routes
 * so existing RFID readers and phone clients keep working unchanged.
 */
export const reroute: Reroute = ({ url }) => {
	const match = /^\/sts\/api(?:\/index\.php)?(\/.+)$/.exec(url.pathname);
	if (match) return `/api${match[1]}`;
};
