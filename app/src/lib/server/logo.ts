import { createHash } from 'node:crypto';
import { getSetting } from './db';

export interface LogoPattern {
	pattern: string;
	name: string; // human label
}

/** Count literal (non-wildcard) characters in a glob pattern. Used for specificity ranking. */
function specificity(pattern: string): number {
	let n = 0;
	for (const ch of pattern) {
		if (ch !== '?' && ch !== '*') n++;
	}
	return n;
}

/** Test a glob pattern against a string. Supports ? (one char) and * (any substring). */
export function globMatch(pattern: string, str: string): boolean {
	const p = pattern.toLowerCase();
	const s = str.toLowerCase();

	// dp[i][j] = pattern[0..i) matches str[0..j)
	const dp: boolean[][] = Array.from({ length: p.length + 1 }, () =>
		new Array(s.length + 1).fill(false)
	);
	dp[0][0] = true;
	for (let i = 1; i <= p.length; i++) {
		if (p[i - 1] === '*') dp[i][0] = dp[i - 1][0];
	}

	for (let i = 1; i <= p.length; i++) {
		for (let j = 1; j <= s.length; j++) {
			if (p[i - 1] === '*') {
				dp[i][j] = dp[i - 1][j] || dp[i][j - 1];
			} else if (p[i - 1] === '?' || p[i - 1] === s[j - 1]) {
				dp[i][j] = dp[i - 1][j - 1];
			}
		}
	}

	return dp[p.length][s.length];
}

/** Short content hash of a stored logo, for cache-busting image URLs. */
export function logoVersion(settingKey: string): string {
	const value = getSetting(settingKey);
	if (!value) return '';
	return createHash('md5').update(value).digest('hex').slice(0, 8);
}

/** Load patterns from the logo_patterns setting (JSON array). */
export function getLogoPatterns(): LogoPattern[] {
	const raw = getSetting('logo_patterns');
	if (!raw) return [];
	try {
		return JSON.parse(raw) as LogoPattern[];
	} catch {
		return [];
	}
}

/**
 * Find the logo setting key for a given train name.
 * Returns 'logo_data_<n>' for the best-matching pattern, or 'logo_data' (default).
 * "Best" = most literal characters in the pattern; ties go to first in list.
 */
export function resolveLogoKey(trainName: string): string {
	const patterns = getLogoPatterns();
	if (!patterns.length) return 'logo_data';

	const matches = patterns
		.map((p, i) => ({ p, i, spec: specificity(p.pattern) }))
		.filter(({ p }) => globMatch(p.pattern, trainName));

	if (!matches.length) return 'logo_data';

	matches.sort((a, b) => b.spec - a.spec || a.i - b.i);
	const winner = matches[0].p;
	return `logo_data_${patterns.indexOf(winner)}`;
}
