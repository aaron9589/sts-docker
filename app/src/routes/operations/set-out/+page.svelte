<script lang="ts">
	import { enhance } from '$app/forms';
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();
	let bulk = $state('');
	let selections: Record<number, string> = $state({});

	function navigate(job: number | null, defaults: boolean) {
		const params = new URLSearchParams();
		if (job) params.set('job', String(job));
		if (defaults) params.set('defaults', '1');
		goto(`?${params}`, { keepFocus: true });
	}

	function applyBulk(value: string) {
		if (!value) return;
		for (const car of data.cars) selections[car.id] = value;
	}
</script>

<svelte:head><title>STS - Set Out Cars</title></svelte:head>

<Navbar title="Set Out Cars" variant="green" back={{ href: '/operations', label: 'Operations' }} print />

<div class="page">
	<div class="noprint">
		<label class="form-row">
			<input
				type="checkbox"
				checked={data.defaultsOnly}
				onchange={(e) => navigate(null, (e.target as HTMLInputElement).checked)}
			/>
			Show default set-out locations only
		</label>
		<div class="form-row">
			<label for="job_list">Select a job to do the setouts:</label>
			<select
				id="job_list"
				value={data.selectedJob ?? ''}
				onchange={(e) => navigate(parseInt((e.target as HTMLSelectElement).value, 10) || null, data.defaultsOnly)}
			>
				<option value=""></option>
				{#each data.jobs as j (j.id)}
					<option value={j.id}>{j.name}</option>
				{/each}
			</select>
		</div>
	</div>

	{#if form && 'setOut' in form}
		<div class="alert alert-success">
			{form.setOut} car(s) set out.
			Next: <a href="/operations/organize">organize car positions</a> at the set-out locations.
		</div>
	{/if}

	{#if data.selectedJob}
		{#if data.cars.length === 0}
			<div class="alert alert-warning">The switchlist for this job/train doesn't contain any cars.</div>
		{:else}
			<div class="alert alert-info noprint">
				Mark where each car was left by selecting its set-out location, then click
				<strong>SET OUT</strong>. Leave a row blank (KEEP IN TRAIN) to leave the car in the train.
				<div class="form-row" style="margin-top: 0.75rem;">
					<label for="bulk">Set all locations to:</label>
					<select id="bulk" bind:value={bulk} onchange={() => applyBulk(bulk)}>
						<option value="">Select location</option>
						{#each data.locations as l (l.id)}
							<option value={l.id}>{l.station} - {l.code}</option>
						{/each}
					</select>
				</div>
			</div>

			<form method="POST" action="?/setout" use:enhance>
				<p><button class="btn btn-success">SET OUT</button></p>
				<div class="table-wrap">
					<table class="ops">
						<thead>
							<tr>
								<th>Set-out Location</th>
								<th>Position</th>
								<th>Reporting Marks</th>
								<th>Car Code</th>
								<th>Loading Station / Location</th>
								<th>Status</th>
								<th>Unloading Station / Location</th>
								<th>Consignment</th>
							</tr>
						</thead>
						<tbody>
							{#each data.cars as car (car.id)}
								<tr>
									<td>
										<select name="loc_for_{car.id}" bind:value={selections[car.id]}>
											<option value="">KEEP IN TRAIN</option>
											{#each data.locations as l (l.id)}
												<option value={l.id}>{l.station} - {l.code}</option>
											{/each}
										</select>
									</td>
									<td style="text-align:center;">{car.position}</td>
									<td>{car.reporting_marks}</td>
									<td>{car.car_code}</td>
									<td><Destination {car} kind="loading" /></td>
									<td><StatusBadge status={car.status} /></td>
									<td><Destination {car} kind="unloading" /></td>
									<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
								</tr>
							{/each}
						</tbody>
					</table>
				</div>
			</form>
		{/if}
	{/if}
</div>
