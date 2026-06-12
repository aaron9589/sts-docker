<script lang="ts">
	import { invalidateAll } from '$app/navigation';
	import { deserialize } from '$app/forms';
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
	let autoJob = $state('');
	let expanded: number | null = $state(null);
	let assigning = $state(false);

	function selectStation(e: Event) {
		const v = (e.target as HTMLSelectElement).value;
		expanded = null;
		goto(v ? `?station=${v}` : '?', { keepFocus: true });
	}

	function toggle(carId: number) {
		expanded = expanded === carId ? null : carId;
	}

	async function assign(carId: number, jobId: number) {
		assigning = true;
		const body = new FormData();
		body.set('car_id', String(carId));
		body.set('job_id', String(jobId));
		const res = await fetch('?/assignOne', { method: 'POST', body });
		const result = deserialize(await res.text());
		assigning = false;
		if (result.type === 'success') {
			expanded = null;
			await invalidateAll();
		} else {
			alert('Error assigning car. Please try again.');
		}
	}

	const groups = $derived.by(() => {
		const out: { key: string; label: string; cars: typeof data.cars }[] = [];
		for (const car of data.cars) {
			const key = car.current_location ?? '';
			const last = out.at(-1);
			if (!last || last.key !== key) {
				out.push({ key, label: `${car.current_station} — ${car.current_location}`, cars: [car] });
			} else {
				last.cars.push(car);
			}
		}
		return out;
	});
</script>

<svelte:head><title>STS - Build Switch Lists</title></svelte:head>

<Navbar title="Build Switch Lists" variant="green" back={{ href: '/operations', label: 'Operations' }} />

<div class="page">
	<div class="menu-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); margin-bottom: 1rem;">
		<div class="card">
			<h3>Assign Cars Station-by-Station</h3>
			<p class="muted small">Select a station to assign cars to jobs/trains for pickup.</p>
			<select onchange={selectStation} value={data.selectedStation ?? ''}>
				<option value=""></option>
				{#each data.stations as s (s.id)}
					<option value={s.id}>{s.name}</option>
				{/each}
			</select>
		</div>
		<div class="card">
			<h3>Auto-Assign Cars</h3>
			<p class="muted small">Select a job/train and click AUTO-ASSIGN to assign cars automatically.</p>
			<div class="form-row">
				<select bind:value={autoJob}>
					<option value=""></option>
					{#each data.jobs as j (j.id)}
						<option value={j.id}>{j.name}</option>
					{/each}
				</select>
				<button
					type="button"
					class="btn btn-success"
					disabled={!autoJob}
					onclick={() => goto(`/operations/switchlists/auto-assign?job=${autoJob}`)}
				>
					AUTO-ASSIGN
				</button>
			</div>
		</div>
	</div>

	{#if data.selectedStation}
		{#if data.cars.length === 0}
			<div class="alert alert-warning">There are no cars at this location that are ready to move.</div>
		{:else}
			<div class="alert alert-info">
				Click a car to see available jobs, then click a job to assign it.
				{#if data.instructions}
					<hr />
					<strong>Routing Instructions:</strong>
					<div style="white-space: pre-line;">{data.instructions}</div>
				{/if}
			</div>

			{#each groups as group (group.key)}
				<h3 class="location-header">{group.label}</h3>

				{#each group.cars as car (car.id)}
					<div class="card car-card" class:is-expanded={expanded === car.id}>
						<button
							type="button"
							class="car-header"
							onclick={() => toggle(car.id)}
							disabled={assigning}
						>
							<span class="car-header-main">
								<strong>{car.reporting_marks}</strong>
								<span class="muted">{car.car_code}</span>
								{#if car.job_name}
									<span class="job-badge">{car.job_name}</span>
								{/if}
							</span>
							<span class="car-header-right">
								<StatusBadge status={car.status} />
								<span class="chevron">{expanded === car.id ? '▲' : '▼'}</span>
							</span>
						</button>

						{#if expanded === car.id}
							<div class="car-details">
								<div class="car-info">
									<div><span class="label">Current Location</span>{car.current_station} / {car.current_location}</div>
									<div><span class="label">Loading</span><Destination {car} kind="loading" /></div>
									<div><span class="label">Unloading</span><Destination {car} kind="unloading" /></div>
									<div><span class="label">Consignment</span>{car.is_reposition ? 'Non-Revenue' : (car.consignment ?? '—')}</div>
								</div>

								{#if data.stationJobs.length === 0}
									<div class="alert alert-warning">No jobs are scheduled to pick up at this station.</div>
								{:else}
									<p class="small muted" style="margin-bottom: 0.5rem;"><strong>Assign to job:</strong></p>
									<div class="job-grid">
										{#each data.stationJobs as job (job.id)}
											<button
												type="button"
												class="job-row"
												class:active={car.job_name === job.name}
												onclick={() => assign(car.id, job.id)}
												disabled={assigning}
											>
												<strong>{job.name}</strong>
											</button>
										{/each}
									</div>
								{/if}
							</div>
						{/if}
					</div>
				{/each}
			{/each}
		{/if}
	{/if}
</div>

<style>
	.location-header {
		font-size: 0.9rem;
		font-weight: 700;
		color: var(--text-2);
		text-transform: uppercase;
		letter-spacing: 0.04em;
		margin: 1.25rem 0 0.4rem;
		padding-bottom: 0.25rem;
		border-bottom: 1px solid var(--border);
	}

	.car-card { padding: 0; overflow: hidden; }
	.car-card.is-expanded { border-color: var(--green); }

	.car-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 100%;
		padding: 0.85rem 1rem;
		border: none;
		background: transparent;
		font: inherit;
		cursor: pointer;
		text-align: left;
		gap: 0.5rem;
	}
	.car-header:hover { background: #f0f7f0; }

	.car-header-main {
		display: flex;
		align-items: center;
		gap: 0.6rem;
		flex: 1;
		min-width: 0;
	}

	.car-header-right {
		display: flex;
		align-items: center;
		gap: 0.6rem;
		flex-shrink: 0;
	}

	.chevron { color: var(--text-2); font-size: 0.75rem; }

	.job-badge {
		background: var(--green);
		color: #fff;
		border-radius: 3px;
		padding: 1px 6px;
		font-size: 0.75rem;
		font-weight: 600;
	}

	.car-details {
		padding: 1rem;
		border-top: 1px solid var(--border);
		background: #f9fdf9;
	}

	.car-info {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
		gap: 0.6rem 1rem;
		margin-bottom: 1rem;
	}

	.label {
		display: block;
		font-weight: bold;
		color: var(--text-2);
		font-size: 0.72rem;
		text-transform: uppercase;
	}

	.job-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		gap: 0.4rem;
	}

	.job-row {
		padding: 0.65rem 1rem;
		border-radius: 0.25rem;
		border: 1px solid var(--border);
		background: #fff;
		font: inherit;
		cursor: pointer;
		text-align: left;
		transition: background 0.1s, border-color 0.1s;
	}
	.job-row:hover { background: #e8f5e9; border-color: var(--green); }
	.job-row.active { background: var(--green); color: #fff; border-color: var(--green); }
	.job-row:disabled { opacity: 0.6; cursor: default; }
</style>
