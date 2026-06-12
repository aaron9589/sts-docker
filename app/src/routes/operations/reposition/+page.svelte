<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();

	const groups = $derived.by(() => {
		const out: { key: string; label: string; cars: typeof data.cars }[] = [];
		for (const car of data.cars) {
			const key = `${car.home_station} | ${car.home_location}`;
			const last = out.at(-1);
			if (!last || last.key !== key) {
				out.push({ key, label: `${car.home_station ?? '(no home)'} — ${car.home_location ?? ''}`, cars: [car] });
			} else {
				last.cars.push(car);
			}
		}
		return out;
	});
</script>

<svelte:head><title>STS - Reposition Empty Cars</title></svelte:head>

<Navbar title="Reposition Empty Cars" variant="green" back={{ href: '/operations', label: 'Operations' }} />

<div class="page">
	<p class="muted">
		Select a destination for each empty car to be repositioned, then click UPDATE. Cars not at
		their home location are highlighted grey; cars at home are green. REPOSITION TO HOME sends
		every away-from-home empty back to its home location in one step.
	</p>

	{#if form && 'repositioned' in form}
		<div class="alert alert-success">{form.repositioned} non-revenue car order(s) generated.</div>
	{/if}
	{#if form && 'homeCount' in form}
		<div class="alert alert-success">{form.homeCount} non-revenue car order(s) generated to send cars home.</div>
	{/if}

	{#if data.cars.length === 0}
		<div class="alert alert-info">No cars are currently available for repositioning.</div>
	{:else}
		<form
			method="POST"
			action="?/home"
			use:enhance={({ cancel }) => {
				if (!confirm('Reposition all cars not at their home location to that destination?')) cancel();
			}}
			style="display:inline-block; margin-bottom: 0.75rem;"
		>
			<button class="btn btn-warning">REPOSITION TO HOME</button>
		</form>

		<form method="POST" action="?/update" use:enhance>
			<p><button class="btn btn-success">UPDATE</button></p>
			<div class="table-wrap">
				<table class="ops">
					<thead>
						<tr>
							<th>Destination</th>
							<th>Reporting Marks</th>
							<th>Car Code</th>
							<th>Current Station / Location</th>
							<th>Position</th>
							<th>Home Station / Location</th>
							<th>Remarks</th>
						</tr>
					</thead>
					<tbody>
						{#each groups as group (group.key)}
							<tr class="group-header"><td colspan="7">{group.label}</td></tr>
							{#each group.cars as car (car.id)}
								<tr class={car.at_home ? 'row-home' : 'row-away'}>
									<td>
										<select name="dest_for_{car.id}">
											<option value=""></option>
											{#each data.locations as l (l.id)}
												<option value={l.id}>{l.station} - {l.code}</option>
											{/each}
										</select>
									</td>
									<td>{car.reporting_marks}</td>
									<td>{car.car_code}</td>
									<td>{car.current_station}<br />{car.current_location}</td>
									<td style="text-align:center;">{car.position}</td>
									<td>{car.home_station}<br />{car.home_location}</td>
									<td>{car.remarks}</td>
								</tr>
							{/each}
						{/each}
					</tbody>
				</table>
			</div>
		</form>
	{/if}
</div>
