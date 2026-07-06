import { entities, fieldOptions } from '$lib/server/entities';
import { db } from '$lib/server/db';
import { error, fail } from '@sveltejs/kit';
import type { Actions, PageServerLoad } from './$types';

function def(entity: string) {
	const d = entities[entity];
	if (!d) throw error(404, 'Unknown table');
	return d;
}

export const load: PageServerLoad = ({ params }) => {
	const d = def(params.entity);
	const options = fieldOptions(d);
	if (params.entity === 'cars') {
		options['pool_shipments'] = db()
			.prepare('SELECT id AS value, code AS label FROM shipments ORDER BY code')
			.all() as { value: unknown; label: string }[];
	}
	return {
		entity: params.entity,
		label: d.label,
		labelSingular: d.labelSingular,
		readonly: d.readonly ?? false,
		columns: d.listColumns,
		columnGroups: d.columnGroups ?? [],
		rowHighlightKey: d.rowHighlightKey ?? null,
		rowHighlightClass: d.rowHighlightClass ?? null,
		rows: db().prepare(d.listSql).all() as Record<string, unknown>[],
		fields: d.fields.map(({ optionsSql: _o, ...f }) => f),
		options
	};
};

class FieldError extends Error {}

function valueFor(
	field: { type: string; label: string; nullable?: boolean },
	raw: FormDataEntryValue | null
): unknown {
	const s = raw === null ? '' : String(raw);
	if (field.type === 'number' || field.type === 'select') {
		if (s === '') return field.nullable ? null : field.type === 'number' ? 0 : null;
		const n = Number(s);
		// Never bind a non-numeric string into a numeric/FK column.
		if (Number.isNaN(n)) throw new FieldError(`${field.label} must be a number`);
		return n;
	}
	return s === '' && field.nullable ? null : s;
}

export const actions: Actions = {
	create: async ({ params, request }) => {
		const d = def(params.entity);
		if (d.readonly || d.fields.length === 0) return fail(400, { error: 'Read-only table' });
		const form = await request.formData();
		const cols = d.fields.map((f) => f.name);
		for (const f of d.fields) {
			if (f.required && !String(form.get(f.name) ?? '')) {
				return fail(400, { error: `${f.label} is required` });
			}
		}
		let values;
		try {
			values = d.fields.map((f) => valueFor(f, form.get(f.name)));
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		try {
			db()
				.prepare(
					`INSERT INTO ${d.table} (${cols.join(',')}) VALUES (${cols.map(() => '?').join(',')})`
				)
				.run(...values);
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		return { created: true };
	},
	delete: async ({ params, request }) => {
		const d = def(params.entity);
		const form = await request.formData();
		const id = String(form.get('id') ?? '');
		if (!id) return fail(400, { error: 'Missing id' });
		// pool/empty_locations/ownership are WITHOUT-rowid-style composites listed by rowid
		const keyCol = ['pool', 'empty_locations', 'ownership'].includes(d.table)
			? 'rowid'
			: d.table === 'car_orders'
				? 'waybill_number'
				: 'id';
		try {
			db().prepare(`DELETE FROM ${d.table} WHERE ${keyCol} = ?`).run(id);
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		return { deleted: true };
	},
	updatePool: async ({ params, request }) => {
		if (params.entity !== 'cars') return fail(400, { error: 'Not supported' });
		const form = await request.formData();
		const carId = Number(form.get('car_id'));
		if (!carId) return fail(400, { error: 'Missing car_id' });
		const shipmentIds = form.getAll('shipment_ids').map(Number).filter(Boolean);
		const database = db();
		database.prepare('DELETE FROM pool WHERE car_id = ?').run(carId);
		for (const sid of shipmentIds) {
			database.prepare('INSERT OR IGNORE INTO pool (car_id, shipment_id) VALUES (?, ?)').run(carId, sid);
		}
		return { updated: true };
	},
	update: async ({ params, request }) => {
		const d = def(params.entity);
		if (d.readonly) return fail(400, { error: 'Read-only table' });
		const form = await request.formData();
		const id = String(form.get('id') ?? '');
		if (!id) return fail(400, { error: 'Missing id' });
		const keyCol = ['pool', 'empty_locations', 'ownership'].includes(d.table) ? 'rowid' : 'id';
		const sets = d.fields.map((f) => `${f.name} = ?`).join(', ');
		let values;
		try {
			values = d.fields.map((f) => valueFor(f, form.get(f.name)));
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		try {
			db().prepare(`UPDATE ${d.table} SET ${sets} WHERE ${keyCol} = ?`).run(...values, id);
		} catch (e) {
			return fail(400, { error: (e as Error).message });
		}
		return { updated: true };
	}
};
