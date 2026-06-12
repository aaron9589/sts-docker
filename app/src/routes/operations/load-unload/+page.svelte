<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();

	// pre-check follows the legacy random spotting-time suggestion from the server
	let checked: Record<number, boolean> = $state({});
	$effect(() => {
		checked = Object.fromEntries(data.cars.map((c) => [c.id, c.suggested]));
	});

	function checkAll(value: boolean) {
		for (const car of data.cars) checked[car.id] = value;
	}
</script>

<svelte:head><title>STS - Load / Unload Cars</title></svelte:head>

<Navbar title="Load / Unload Cars" variant="green" back={{ href: '/operations', label: 'Operations' }} />

<div class="page">
	{#if form && 'updated' in form}
		<div class="alert alert-success">{form.updated} car(s) updated.</div>
	{/if}

	{#if data.cars.length === 0}
		<div class="alert alert-info">There are no cars currently in the process of being loaded or unloaded.</div>
	{:else}
		<div class="alert alert-info">
			These cars are being loaded or unloaded. Cars whose suggested loading/unloading time has
			elapsed are pre-checked. Check or uncheck cars and click <strong>UPDATE</strong> to complete
			their loading/unloading.
		</div>

		<form method="POST" action="?/update" use:enhance>
			<div class="form-row">
				<button class="btn btn-success">UPDATE</button>
				<label><input type="checkbox" onchange={(e) => checkAll((e.target as HTMLInputElement).checked)} /> Check/uncheck all</label>
			</div>
			<div class="table-wrap">
				<table class="ops">
					<thead>
						<tr>
							<th>Done?</th>
							<th>Current Station / Location</th>
							<th>Position</th>
							<th>Reporting Marks</th>
							<th>Car Code</th>
							<th>Status</th>
							<th>Consignment</th>
							<th>Loading Station / Location</th>
							<th>Unloading Station / Location</th>
						</tr>
					</thead>
					<tbody>
						{#each data.cars as car (car.id)}
							<tr>
								<td style="text-align:center;">
									<input type="checkbox" name="car" value={car.id} bind:checked={checked[car.id]} />
								</td>
								<td>{car.current_station}<br />{car.current_location}</td>
								<td style="text-align:center;">{car.position}</td>
								<td>{car.reporting_marks}</td>
								<td>{car.car_code}</td>
								<td><StatusBadge status={car.status} /></td>
								<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
								<td><Destination {car} kind="loading" /></td>
								<td><Destination {car} kind="unloading" /></td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</form>
	{/if}
</div>
