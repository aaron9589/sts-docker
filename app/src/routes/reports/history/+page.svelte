<script lang="ts">
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
</script>

<svelte:head><title>STS - Car History</title></svelte:head>

<Navbar title="Car History" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<div class="card noprint">
		<div class="form-row">
			<label for="car">Car:</label>
			<select
				id="car"
				value={data.car?.id ?? ''}
				onchange={(e) => goto(`?car=${(e.target as HTMLSelectElement).value}`, { keepFocus: true })}
			>
				<option value=""></option>
				{#each data.cars as c (c.id)}
					<option value={c.id}>{c.reporting_marks}</option>
				{/each}
			</select>
		</div>
	</div>

	{#if data.car}
		<div class="print-header">
			<h2>{data.railroadName}</h2>
			<h3>Car History — {data.car.reporting_marks} ({data.car.car_code})</h3>
		</div>
		<h2 class="noprint">{data.car.reporting_marks} ({data.car.car_code})</h2>
		{#if data.entries.length === 0}
			<div class="alert alert-info">No history recorded for this car.</div>
		{:else}
			<div class="table-wrap">
				<table class="report">
					<thead>
						<tr>
							<th>Session</th>
							<th>Date / Time</th>
							<th>Event</th>
							<th>Location</th>
						</tr>
					</thead>
					<tbody>
						{#each data.entries as entry}
							<tr>
								<td style="text-align:center;">{entry.session_nbr}</td>
								<td>{entry.event_date}</td>
								<td>{entry.event}</td>
								<td>
									{#if entry.location_code}
										{entry.station_name} / {entry.location_code}
									{/if}
								</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		{/if}
	{/if}
</div>
