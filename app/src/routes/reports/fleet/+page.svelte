<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
</script>

<svelte:head><title>STS - Fleet Report</title></svelte:head>

<Navbar title="Fleet Report" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<div class="print-header">
		<h2>{data.railroadName}</h2>
		<h3>Fleet Report</h3>
	</div>
	<p class="muted noprint">{data.cars.length} cars on the roster.</p>
	<div class="table-wrap">
		<table class="report">
			<thead>
				<tr>
					<th>Reporting Marks</th>
					<th>Car Code</th>
					<th>Status</th>
					<th>Current Station / Location</th>
					<th>Home Station / Location</th>
					<th>Handled by</th>
					<th>Loads</th>
					<th>Owner</th>
					<th>Remarks</th>
				</tr>
			</thead>
			<tbody>
				{#each data.cars as car (car.id)}
					<tr>
						<td><a href="/reports/history?car={car.id}">{car.reporting_marks}</a></td>
						<td>{car.car_code}</td>
						<td><StatusBadge status={car.status} /></td>
						<td>
							{#if car.current_location}
								{car.current_station} / {car.current_location}
							{:else}
								In Train
							{/if}
						</td>
						<td>{car.home_station ? `${car.home_station} / ${car.home_location}` : ''}</td>
						<td>{car.job_name}</td>
						<td style="text-align:center;">{car.load_count}</td>
						<td>{car.owner}</td>
						<td>{car.remarks}</td>
					</tr>
				{/each}
			</tbody>
		</table>
	</div>
</div>
