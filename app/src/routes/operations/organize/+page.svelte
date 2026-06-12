<script lang="ts">
	import { goto, invalidateAll } from '$app/navigation';
	import { deserialize } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { CarRow } from '$lib/types';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();

	// local working order, refreshed whenever the loaded data changes
	let order: CarRow[] = $state([]);
	$effect(() => {
		order = [...data.cars];
	});

	let draggingId: number | null = $state(null);
	let saveState: 'idle' | 'saving' | 'success' | 'error' = $state('idle');

	function onDragStart(carId: number) {
		draggingId = carId;
	}

	function onDragOver(e: DragEvent, overId: number) {
		e.preventDefault();
		if (draggingId === null || draggingId === overId) return;
		const from = order.findIndex((c) => c.id === draggingId);
		const to = order.findIndex((c) => c.id === overId);
		if (from < 0 || to < 0) return;
		const next = [...order];
		const [moved] = next.splice(from, 1);
		next.splice(to, 0, moved);
		order = next;
	}

	async function save() {
		saveState = 'saving';
		const body = new FormData();
		for (const car of order) body.append('car', String(car.id));
		const res = await fetch('?/save', { method: 'POST', body });
		const result = deserialize(await res.text());
		saveState = result.type === 'success' ? 'success' : 'error';
		if (result.type === 'success') await invalidateAll();
		setTimeout(() => (saveState = 'idle'), 2000);
	}

	function selectJob(e: Event) {
		const v = (e.target as HTMLSelectElement).value;
		goto(v ? `?job=${v}` : '?', { keepFocus: true });
	}
	function selectLocation(e: Event) {
		const v = (e.target as HTMLSelectElement).value;
		goto(v ? `?location=${v}` : '?', { keepFocus: true });
	}
</script>

<svelte:head><title>STS - Organize Cars</title></svelte:head>

<Navbar title="Organize Cars" variant="green" back={{ href: '/operations', label: 'Operations' }} />

<div class="page">
	<div class="menu-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1rem;">
		<div class="card">
			<h3>Cars in a Job/Train</h3>
			<select onchange={selectJob} value={data.selectedJob ?? ''}>
				<option value=""></option>
				{#each data.jobs as j (j.id)}
					<option value={j.id}>{j.name}</option>
				{/each}
			</select>
		</div>
		<div class="card">
			<h3>Cars at a Location</h3>
			<select onchange={selectLocation} value={data.selectedLocation ?? ''}>
				<option value=""></option>
				{#each data.locations as l (l.id)}
					<option value={l.id}>{l.station} - {l.code}</option>
				{/each}
			</select>
		</div>
	</div>

	{#if data.selectedJob || data.selectedLocation}
		{#if order.length === 0}
			<div class="alert alert-warning">No cars found.</div>
		{:else}
			<div class="alert alert-info">
				Drag rows to re-sequence the cars, then click SAVE ORDER. Position numbers update as you drag.
			</div>
			<p>
				<button
					class="btn"
					class:btn-primary={saveState === 'idle' || saveState === 'saving'}
					class:btn-success={saveState === 'success'}
					class:btn-danger={saveState === 'error'}
					disabled={saveState === 'saving'}
					onclick={save}
				>
					{saveState === 'saving' ? 'Saving…' : saveState === 'success' ? 'Saved ✓' : saveState === 'error' ? 'Error ✗' : 'SAVE ORDER'}
				</button>
			</p>
			<div class="table-wrap">
				<table class="ops">
					<thead>
						<tr>
							<th>☰</th>
							<th>Position</th>
							<th>Reporting Marks</th>
							<th>Car Code</th>
							<th>Status</th>
							<th>Consignment</th>
							<th>Current Station / Location</th>
							<th>Loading Station / Location</th>
							<th>Unloading Station / Location</th>
						</tr>
					</thead>
					<tbody>
						{#each order as car, i (car.id)}
							<tr
								draggable="true"
								class:dragging={draggingId === car.id}
								ondragstart={() => onDragStart(car.id)}
								ondragover={(e) => onDragOver(e, car.id)}
								ondragend={() => (draggingId = null)}
							>
								<td style="text-align:center; cursor:grab;">☰</td>
								<td style="text-align:center;">{i + 1}</td>
								<td>{car.reporting_marks}</td>
								<td>{car.car_code}</td>
								<td><StatusBadge status={car.status} /></td>
								<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
								<td>
									{#if car.current_location}
										{car.current_station}<br />{car.current_location}
									{:else}
										In Train
									{/if}
								</td>
								<td><Destination {car} kind="loading" /></td>
								<td><Destination {car} kind="unloading" /></td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		{/if}
	{/if}
</div>

<style>
	tr.dragging td {
		background: #fd7e14 !important;
		color: #fff;
		opacity: 0.85;
	}
</style>
