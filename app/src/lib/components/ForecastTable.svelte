<script lang="ts">
	interface Row {
		shipment_code: string;
		description: string | null;
		car_code: string | null;
		loading_station: string | null;
		loading_location: string | null;
		perSession: number[];
		total: number;
	}
	interface Props {
		rows: Row[];
		startSession: number;
		groupBy: 'car_code' | 'loading_location';
	}
	let { rows, startSession, groupBy }: Props = $props();

	const sessions = $derived(Array.from({ length: 10 }, (_, i) => startSession + i));

	const groups = $derived.by(() => {
		const out: { key: string; rows: Row[]; subtotals: number[]; total: number }[] = [];
		for (const row of rows) {
			const key =
				groupBy === 'car_code'
					? (row.car_code ?? '')
					: `${row.loading_station} — ${row.loading_location}`;
			let g = out.at(-1);
			if (!g || g.key !== key) {
				g = { key, rows: [], subtotals: Array(10).fill(0), total: 0 };
				out.push(g);
			}
			g.rows.push(row);
			row.perSession.forEach((n, i) => (g!.subtotals[i] += n));
			g.total += row.total;
		}
		return out;
	});

	const columnTotals = $derived.by(() => {
		const totals = Array(10).fill(0);
		for (const row of rows) row.perSession.forEach((n, i) => (totals[i] += n));
		return totals;
	});
</script>

<div class="table-wrap">
	<table class="report">
		<thead>
			<tr>
				<th>{groupBy === 'car_code' ? 'Car Code' : 'Loading Location'}</th>
				<th>Shipment</th>
				<th>Description</th>
				{#each sessions as s (s)}
					<th style="text-align:center;">{s}</th>
				{/each}
				<th style="text-align:center;">Total</th>
			</tr>
		</thead>
		<tbody>
			{#each groups as group (group.key)}
				{#each group.rows as row, i}
					<tr>
						<td>{i === 0 ? group.key : ''}</td>
						<td>{row.shipment_code}</td>
						<td>{row.description}</td>
						{#each row.perSession as n}
							<td style="text-align:center;">{n || ''}</td>
						{/each}
						<td style="text-align:center;"><strong>{row.total}</strong></td>
					</tr>
				{/each}
				<tr style="background: var(--summary);">
					<td colspan="3"><em>Subtotal — {group.key}</em></td>
					{#each group.subtotals as n}
						<td style="text-align:center;"><strong>{n}</strong></td>
					{/each}
					<td style="text-align:center;"><strong>{group.total}</strong></td>
				</tr>
			{/each}
			<tr style="background: var(--summary);">
				<td colspan="3"><strong>All shipments</strong></td>
				{#each columnTotals as n}
					<td style="text-align:center;"><strong>{n}</strong></td>
				{/each}
				<td style="text-align:center;">
					<strong>{columnTotals.reduce((a, b) => a + b, 0)}</strong>
				</td>
			</tr>
		</tbody>
	</table>
</div>
