<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();
	let mode: 'none' | 'automatic' | 'manual' = $state('none');
</script>

<svelte:head><title>STS - Generate Car Orders</title></svelte:head>

<Navbar title="Generate Car Orders" variant="green" back={{ href: '/operations', label: 'Operations' }} />

<div class="page">
	<p class="muted">Current operating session: <strong>{data.session}</strong></p>

	{#if form}
		<div class="alert alert-success">
			{form.count} car order{form.count === 1 ? '' : 's'} generated for session {form.session}.
		</div>
	{/if}

	<div class="menu-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
		<button type="button" class="menu-card green" style="text-align:left; cursor:pointer;" onclick={() => (mode = 'automatic')}>
			<h3>Automatic Generation</h3>
			<p>Increment the operating session number and automatically generate car orders based on shipment schedules.</p>
		</button>
		<button type="button" class="menu-card green" style="text-align:left; cursor:pointer;" onclick={() => (mode = 'manual')}>
			<h3>Manual Generation</h3>
			<p>Choose specific shipments and generate car orders for those shipments only.</p>
		</button>
	</div>

	{#if mode === 'automatic'}
		<div class="card" style="margin-top:1rem;">
			<p>
				Ready to generate car orders automatically. This advances the session to
				<strong>{data.session + 1}</strong>.
			</p>
			<form method="POST" action="?/automatic" use:enhance>
				<button class="btn btn-success">AUTOMATIC</button>
			</form>
		</div>
	{/if}

	{#if mode === 'manual'}
		<form method="POST" action="?/manual" use:enhance>
			<div class="card" style="margin-top:1rem;">
				<p>Check shipments to order cars for, then click MANUAL.</p>
				<button class="btn btn-success" onclick={(e) => { if (!confirm('Order these cars?')) e.preventDefault(); }}>MANUAL</button>
			</div>
			<div class="table-wrap">
				<table class="ops">
					<thead>
						<tr>
							<th>Select</th>
							<th>Shipment Code</th>
							<th>Description</th>
							<th>Commodity</th>
							<th>Car Code</th>
							<th>Loading Location</th>
							<th>Unloading Location</th>
							<th>Last Ship Date</th>
							<th>Min Int</th>
							<th>Max Int</th>
							<th>Min Amt</th>
							<th>Max Amt</th>
						</tr>
					</thead>
					<tbody>
						{#each data.shipments as s (s.id)}
							<tr>
								<td style="text-align:center;"><input type="checkbox" name="shipment" value={s.id} /></td>
								<td>{s.code}</td>
								<td>{s.description}</td>
								<td>{s.commodity}</td>
								<td>{s.car_code}</td>
								<td>{s.loading_station}<br />{s.loading_location}</td>
								<td>{s.unloading_station}<br />{s.unloading_location}</td>
								<td style="text-align:center;">{s.last_ship_date}</td>
								<td style="text-align:center;">{s.min_interval}</td>
								<td style="text-align:center;">{s.max_interval}</td>
								<td style="text-align:center;">{s.min_amount}</td>
								<td style="text-align:center;">{s.max_amount}</td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
		</form>
	{/if}
</div>
