<script lang="ts">
	import { deserialize } from '$app/forms';
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();

	// Set of car ids picked up this session (optimistic — greyed out until refresh)
	let pickedUp = $state(new Set<number>());
	let pending = $state(new Set<number>());

	function selectJob(e: Event) {
		const v = (e.target as HTMLSelectElement).value;
		pickedUp = new Set();
		goto(v ? `?job=${v}` : '?', { keepFocus: true });
	}

	const jobName = $derived(data.jobs.find((j) => j.id === data.selectedJob)?.name ?? '');

	async function post(action: string, fields: Record<string, string>) {
		const body = new FormData();
		for (const [k, v] of Object.entries(fields)) body.set(k, v);
		const res = await fetch(`?/${action}`, { method: 'POST', body });
		return deserialize(await res.text());
	}

	async function pickUp(carId: number) {
		pending = new Set([...pending, carId]);
		const result = await post('pickupOne', { car_id: String(carId) });
		pending = new Set([...pending].filter((id) => id !== carId));
		if (result.type === 'success') {
			pickedUp = new Set([...pickedUp, carId]);
		} else {
			alert('Error picking up car. Please try again.');
		}
	}

	async function undo(carId: number) {
		if (!data.selectedJob) return;
		pending = new Set([...pending, carId]);
		const result = await post('undoOne', {
			car_id: String(carId),
			job_id: String(data.selectedJob)
		});
		pending = new Set([...pending].filter((id) => id !== carId));
		if (result.type === 'success') {
			pickedUp = new Set([...pickedUp].filter((id) => id !== carId));
		} else {
			alert('Error undoing pickup. Please try again.');
		}
	}
</script>

<svelte:head><title>STS - Pick Up Cars</title></svelte:head>

<Navbar title="Pick Up Cars" variant="green" back={{ href: '/operations', label: 'Operations' }} print />

<div class="page">
	<div class="form-row noprint">
		<label for="job_list">Select a job to do the pickups:</label>
		<select id="job_list" onchange={selectJob} value={data.selectedJob ?? ''}>
			<option value=""></option>
			{#each data.jobs as j (j.id)}
				<option value={j.id}>{j.name}</option>
			{/each}
		</select>
	</div>

	{#if data.selectedJob}
		{#if data.cars.length === 0}
			<div class="alert alert-warning">
				The switchlist for {jobName} doesn't contain any cars awaiting pickup.
			</div>
		{:else}
			<div class="alert alert-info noprint">
				Click <strong>Pick Up</strong> on each car as you collect it.
			</div>

			<div class="table-wrap">
				<table class="ops">
					<thead>
						<tr>
							<th class="noprint"></th>
							<th>Reporting Marks</th>
							<th>Car Code</th>
							<th>Current Location</th>
							<th>Loading Station / Location</th>
							<th>Status</th>
							<th>Unloading Station / Location</th>
							<th>Consignment</th>
						</tr>
					</thead>
					<tbody>
						{#each data.cars as car (car.id)}
							{@const done = pickedUp.has(car.id)}
							{@const busy = pending.has(car.id)}
							<tr class:picked-up={done} class:pending={busy}>
								<td class="action-cell noprint">
									{#if done}
										<span class="picked-label">Picked up</span>
										<button
											type="button"
											class="btn btn-sm btn-outline-secondary undo-btn"
											onclick={() => undo(car.id)}
											disabled={busy}
										>Undo</button>
									{:else}
										<button
											type="button"
											class="btn btn-sm btn-success"
											onclick={() => pickUp(car.id)}
											disabled={busy}
										>Pick Up</button>
									{/if}
								</td>
								<td>{car.reporting_marks}</td>
								<td>{car.car_code}</td>
								<td>{car.current_station}<br />{car.current_location}</td>
								<td><Destination {car} kind="loading" /></td>
								<td><StatusBadge status={car.status} /></td>
								<td><Destination {car} kind="unloading" /></td>
								<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		{/if}
	{/if}
</div>

<style>
	tr.picked-up td {
		opacity: 0.35;
		text-decoration: line-through;
	}
	tr.picked-up .action-cell {
		opacity: 1;
		text-decoration: none;
	}
	tr.pending td {
		opacity: 0.6;
		pointer-events: none;
	}

	.action-cell {
		white-space: nowrap;
		display: flex;
		align-items: center;
		gap: 0.4rem;
	}

	.picked-label {
		font-size: 0.78rem;
		color: var(--text-2);
		font-style: italic;
	}

	.undo-btn {
		font-size: 0.75rem;
		padding: 0.15rem 0.5rem;
	}
</style>
