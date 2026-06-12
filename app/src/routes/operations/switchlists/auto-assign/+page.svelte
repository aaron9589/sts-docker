<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
	let allChecked = $state(true);
</script>

<svelte:head><title>STS - Auto-Assign Cars</title></svelte:head>

<Navbar
	title="Auto-Assign: {data.job.name}"
	variant="green"
	back={{ href: '/operations/switchlists', label: 'Switch Lists' }}
/>

<div class="page">
	{#if data.criteria.length === 0}
		<div class="alert alert-warning">
			Job {data.job.name} doesn't have any pickup criteria. Add criteria from the job's step
			editor on the Database page.
		</div>
	{:else}
		<div class="card">
			<h3>Pickup Criteria</h3>
			<div class="table-wrap" style="box-shadow:none;">
				<table class="ops">
					<thead>
						<tr>
							<th>Step</th>
							<th>Pickup Station</th>
							<th>Car Status</th>
							<th>Commodity</th>
							<th>Car Code</th>
							<th>Destination Station</th>
						</tr>
					</thead>
					<tbody>
						{#each data.criteria as c}
							<tr>
								<td style="text-align:center;">{c.step_nbr}</td>
								<td>{c.pickup_station}</td>
								<td>{c.car_status}</td>
								<td>{c.commodity}</td>
								<td>{c.car_code}</td>
								<td>{c.dest_station}</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</div>

		<h3>Pickup List ({data.candidates.length} cars)</h3>
		{#if data.candidates.length === 0}
			<div class="alert alert-info">No cars currently match the pickup criteria.</div>
		{:else}
			<form method="POST" action="?/assign">
				<input type="hidden" name="job" value={data.job.id} />
				<div class="form-row">
					<button class="btn btn-success">ASSIGN CARS</button>
					<label>
						<input type="checkbox" bind:checked={allChecked} />
						Check/uncheck all cars in the list
					</label>
				</div>
				<div class="table-wrap">
					<table class="ops">
						<thead>
							<tr>
								<th>Pick Up?</th>
								<th>Pickup Location</th>
								<th>Reporting Marks</th>
								<th>Car Code</th>
								<th>Status</th>
								<th>Waybill</th>
								<th>Contents</th>
								<th>Destination</th>
							</tr>
						</thead>
						<tbody>
							{#each data.candidates as car (car.id)}
								<tr>
									<td style="text-align:center;">
										{#key allChecked}
											<input type="checkbox" name="car" value={car.id} checked={allChecked} />
										{/key}
									</td>
									<td>{car.pickup_station}</td>
									<td>{car.reporting_marks}</td>
									<td style="text-align:center;">{car.car_code}</td>
									<td><StatusBadge status={car.status} /></td>
									<td>{car.waybill_number}</td>
									<td>{car.is_reposition ? 'REPOSITIONING EMPTY' : car.consignment}</td>
									<td><Destination {car} kind="next" /></td>
								</tr>
							{/each}
						</tbody>
					</table>
				</div>
			</form>
		{/if}
	{/if}
</div>
