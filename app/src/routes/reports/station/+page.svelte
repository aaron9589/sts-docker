<script lang="ts">
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { CarRow } from '$lib/types';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();

	function navigate(station: string, hide: boolean) {
		const params = new URLSearchParams();
		if (station) params.set('station', station);
		if (hide) params.set('hide_unavail', '1');
		goto(`?${params}`, { keepFocus: true });
	}

	function byJob(cars: CarRow[]): { job: string; cars: CarRow[] }[] {
		const handled = cars.filter((c) => c.job_name);
		const map = new Map<string, CarRow[]>();
		for (const c of handled) {
			const list = map.get(c.job_name!) ?? [];
			list.push(c);
			map.set(c.job_name!, list);
		}
		return [...map.entries()].sort(([a], [b]) => a.localeCompare(b)).map(([job, cars]) => ({ job, cars }));
	}
</script>

<svelte:head><title>STS - Station Car Report</title></svelte:head>

<Navbar title="Station Car Report" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<div class="card noprint">
		<div class="form-row">
			<label for="station">Station:</label>
			<select id="station" value={data.selected ?? ''} onchange={(e) => navigate((e.target as HTMLSelectElement).value, data.hideUnavail)}>
				<option value=""></option>
				<option value="all">— All Stations —</option>
				{#each data.stations as s (s.id)}
					<option value={s.id}>{s.name}</option>
				{/each}
			</select>
			<label>
				<input
					type="checkbox"
					checked={data.hideUnavail}
					onchange={(e) => navigate(data.selected ?? '', (e.target as HTMLInputElement).checked)}
				/>
				Hide unavailable cars
			</label>
		</div>
	</div>

	{#if data.selected && data.sections.length === 0}
		<div class="alert alert-warning"><strong>No cars found</strong> at the selected location.</div>
	{/if}

	{#each data.sections as section (section.stationId)}
		<div class="print-header">
			<h2>{data.railroadName}</h2>
			<h3>Station Car Report - Cars on Hand</h3>
			<h3>{section.stationName}</h3>
		</div>
		<h2 class="noprint">{section.stationName}</h2>

		{#each byJob(section.cars) as group (group.job)}
			<div class="job-group">
				<h3 style="margin-top: 1rem;">Pick up by: {group.job}</h3>
				<div class="table-wrap">
					<table class="report">
						<thead>
							<tr>
								<th>Location</th>
								<th>Reporting Marks</th>
								<th>Car Code</th>
								<th>Status</th>
								<th>Consignment</th>
								<th>Destination</th>
								<th>Handled by</th>
							</tr>
						</thead>
						<tbody>
							{#each group.cars as car (car.id)}
								<tr>
									<td>{car.current_location}</td>
									<td>{car.reporting_marks}</td>
									<td>{car.car_code}</td>
									<td><StatusBadge status={car.status} /></td>
									<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
									<td><Destination {car} kind="next" /></td>
									<td>{car.job_name}</td>
								</tr>
							{/each}
						</tbody>
					</table>
				</div>
			</div>
		{/each}

		<div class="noprint">
			<h3 style="margin-top: 1rem;">All Cars On Hand</h3>
			<div class="table-wrap">
				<table class="report">
					<thead>
						<tr>
							<th>Location</th>
							<th>Reporting Marks</th>
							<th>Car Code</th>
							<th>Status</th>
							<th>Consignment</th>
							<th>Loading Station / Location</th>
							<th>Unloading Station / Location</th>
							<th>Remarks</th>
							<th>Handled by</th>
						</tr>
					</thead>
					<tbody>
						{#each section.cars as car (car.id)}
							<tr>
								<td>{car.current_location}</td>
								<td>{car.reporting_marks}</td>
								<td>{car.car_code}</td>
								<td><StatusBadge status={car.status} /></td>
								<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
								<td><Destination {car} kind="loading" /></td>
								<td>
									{#if !car.is_reposition && ['Loading', 'Loaded', 'Unloading'].includes(car.status)}
										<Destination {car} kind="unloading" />
									{:else if car.is_reposition}
										<Destination {car} kind="next" />
									{/if}
								</td>
								<td>{car.shipment_remarks}</td>
								<td>{car.job_name}</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
			<p class="small muted">
				<em>If a car is enroute, the next destination in the route is shown in <strong>bold</strong>.</em>
			</p>
		</div>
		<div class="page-break"></div>
	{/each}
</div>
