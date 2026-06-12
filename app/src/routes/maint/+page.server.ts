import { restartSimulation, wipeDatabase } from '$lib/server/operations';
import { db, getSetting, setSetting } from '$lib/server/db';
import { getLogoPatterns, logoVersion } from '$lib/server/logo';
import { fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

function upsertSetting(key: string, desc: string, value: string) {
	db()
		.prepare(
			'INSERT INTO settings (setting_name, setting_desc, setting_value) VALUES (?, ?, ?) ON CONFLICT(setting_name) DO UPDATE SET setting_value = excluded.setting_value'
		)
		.run(key, desc, value);
}

async function readImageBase64(file: File): Promise<string> {
	const bytes = await file.arrayBuffer();
	return `data:${file.type};base64,${Buffer.from(bytes).toString('base64')}`;
}

function validateImageFile(file: File | null): string | null {
	if (!file || file.size === 0) return 'No file selected.';
	if (file.size > 512 * 1024) return 'File must be under 512 KB.';
	if (!file.type.startsWith('image/')) return 'File must be an image.';
	return null;
}

export const load: PageServerLoad = () => {
	const patterns = getLogoPatterns();
	// Check which pattern indices have a logo blob stored
	const patternLogos = patterns.map((p, i) => ({
		...p,
		index: i,
		hasImage: !!getSetting(`logo_data_${i}`),
		version: logoVersion(`logo_data_${i}`)
	}));

	return {
		settings: db()
			.prepare(
				"SELECT setting_name, setting_desc, setting_value FROM settings WHERE setting_name NOT LIKE 'logo_data%' AND setting_name != 'logo_patterns' ORDER BY setting_name"
			)
			.all() as { setting_name: string; setting_desc: string; setting_value: string }[],
		hasLogo: !!getSetting('logo_data'),
		defaultLogoVersion: logoVersion('logo_data'),
		patternLogos
	};
};

export const actions: Actions = {
	settings: async ({ request }) => {
		const form = await request.formData();
		for (const [key, value] of form.entries()) {
			if (getSetting(key) !== String(value)) setSetting(key, String(value));
		}
		return { saved: true };
	},

	// Upload/replace the default (fallback) logo
	upload_logo: async ({ request }) => {
		const form = await request.formData();
		const file = form.get('logo') as File | null;
		const err = validateImageFile(file);
		if (err) return fail(400, { logoError: err });
		upsertSetting('logo_data', 'Railroad Logo (base64 data URL)', await readImageBase64(file!));
		return { logoSaved: true };
	},

	clear_logo: async () => {
		setSetting('logo_data', '');
		return { logoCleared: true };
	},

	// Add a new pattern rule (appends to logo_patterns JSON array)
	add_pattern: async ({ request }) => {
		const form = await request.formData();
		const pattern = String(form.get('pattern') ?? '').trim();
		const name = String(form.get('name') ?? '').trim();
		const file = form.get('logo') as File | null;

		if (!pattern) return fail(400, { logoError: 'Pattern is required.' });
		const err = validateImageFile(file);
		if (err) return fail(400, { logoError: err });

		const patterns = getLogoPatterns();
		const index = patterns.length;
		patterns.push({ pattern, name: name || pattern });
		setSetting('logo_patterns', JSON.stringify(patterns));
		upsertSetting(`logo_data_${index}`, `Logo for pattern "${pattern}"`, await readImageBase64(file!));
		return { logoSaved: true };
	},

	// Replace the image for an existing pattern
	update_pattern_image: async ({ request }) => {
		const form = await request.formData();
		const index = parseInt(String(form.get('index') ?? ''), 10);
		const file = form.get('logo') as File | null;
		const err = validateImageFile(file);
		if (err) return fail(400, { logoError: err });

		const patterns = getLogoPatterns();
		if (isNaN(index) || index < 0 || index >= patterns.length)
			return fail(400, { logoError: 'Invalid pattern index.' });

		upsertSetting(`logo_data_${index}`, `Logo for pattern "${patterns[index].pattern}"`, await readImageBase64(file!));
		return { logoSaved: true };
	},

	// Remove a pattern rule and its image blob
	remove_pattern: async ({ request }) => {
		const form = await request.formData();
		const index = parseInt(String(form.get('index') ?? ''), 10);
		const patterns = getLogoPatterns();
		if (isNaN(index) || index < 0 || index >= patterns.length)
			return fail(400, { logoError: 'Invalid pattern index.' });

		// Remove pattern from array; shift remaining logo blobs down
		patterns.splice(index, 1);
		setSetting('logo_patterns', JSON.stringify(patterns));

		// Re-index blobs: shift logo_data_N down for N > index, delete the last
		for (let i = index; i < patterns.length; i++) {
			const next = getSetting(`logo_data_${i + 1}`);
			upsertSetting(`logo_data_${i}`, `Logo for pattern "${patterns[i].pattern}"`, next);
		}
		setSetting(`logo_data_${patterns.length}`, '');
		return { logoCleared: true };
	},

	restart: async () => {
		restartSimulation({ resetLoadCounts: false });
		return { message: 'Simulation restarted: shippers reset, orders cancelled, cars emptied and sent home, session set to 0.' };
	},
	reset: async () => {
		restartSimulation({ resetLoadCounts: true });
		return { message: 'Simulation reset: as restart, plus all car load counts zeroed.' };
	},
	wipe: async () => {
		wipeDatabase();
		return { message: 'Database wiped. All data removed and settings restored to defaults.' };
	}
};
