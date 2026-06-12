<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
</script>

<svelte:head><title>STS - Wheel Report</title></svelte:head>

<Navbar title="Wheel Report" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<div class="print-header">
		<h2>{data.railroadName}</h2>
		<h3>Wheel Report</h3>
	</div>
	{#if data.jobs.length === 0}
		<div class="alert alert-info">No jobs are currently handling any cars.</div>
	{/if}
	{#each data.jobs as job (job.id)}
		<h3 style="background: var(--blue); color: #fff; padding: 0.4rem 0.6rem; border-radius: 0.25rem;">
			{job.name} — {job.cars.length} cars
		</h3>
		<div class="table-wrap" style="margin-bottom: 1rem;">
			<table class="report">
				<thead>
					<tr>
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
					{#each job.cars as car (car.id)}
						<tr>
							<td>
								{#if car.current_location}
									{car.current_station} / {car.current_location}
								{:else}
									In Train
								{/if}
							</td>
							<td>{car.reporting_marks}</td>
							<td>{car.car_code}</td>
							<td><StatusBadge status={car.status} /></td>
							<td>{car.waybill_number}</td>
							<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
							<td><Destination {car} kind="next" /></td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	{/each}
</div>
