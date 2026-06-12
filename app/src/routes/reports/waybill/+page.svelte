<script lang="ts">
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
</script>

<svelte:head><title>STS - Waybill</title></svelte:head>

<Navbar title="Waybill" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<div class="card noprint">
		{#if data.waybills.length === 0}
			<p>
				All billed cars are enroute. Waybills cannot be displayed or printed while a car is
				moving.
			</p>
		{:else}
			<div class="form-row">
				<label for="wb">Waybill:</label>
				<select
					id="wb"
					value={data.selected ?? ''}
					onchange={(e) => goto(`?waybill=${encodeURIComponent((e.target as HTMLSelectElement).value)}`, { keepFocus: true })}
				>
					<option value=""></option>
					{#each data.waybills as w (w.waybill_number)}
						<option value={w.waybill_number}>{w.waybill_number}</option>
					{/each}
				</select>
			</div>
			<p class="small muted">
				If the assigned car is not at the loading station, an empty-car movement section is
				included to route it there first.
			</p>
		{/if}
	</div>

	{#if data.waybill}
		{@const wb = data.waybill}

		{#if wb.needs_empty_leg}
			<div class="waybill-card">
				<div class="wb-head">
					<h2>{data.railroadName}</h2>
					<h3>EMPTY CAR WAYBILL — {wb.waybill_number}</h3>
				</div>
				<div class="wb-grid">
					<div><span class="label">Car</span>{wb.reporting_marks} ({wb.car_code})</div>
					<div><span class="label">From</span>{wb.current_station} / {wb.current_location}</div>
					<div><span class="label">To</span>{wb.from_station} / {wb.from_location}</div>
					<div><span class="label">Lading</span>EMPTY — move for loading</div>
				</div>
			</div>
		{/if}

		<div class="waybill-card">
			<div class="wb-head">
				<h2>{data.railroadName}</h2>
				<h3>{wb.is_reposition ? 'EMPTY CAR WAYBILL' : 'WAYBILL'} — {wb.waybill_number}</h3>
			</div>
			<div class="wb-grid">
				<div><span class="label">Car</span>{wb.reporting_marks} ({wb.car_code})</div>
				<div><span class="label">From</span>{wb.from_station} / {wb.from_location}</div>
				<div><span class="label">To</span>{wb.to_station} / {wb.to_location}</div>
				<div>
					<span class="label">Lading</span>
					{wb.is_reposition ? 'EMPTY — repositioning' : wb.consignment}
				</div>
				{#if wb.special_instructions}
					<div style="grid-column: 1 / -1;">
						<span class="label">Special Instructions</span>{wb.special_instructions}
					</div>
				{/if}
				{#if wb.shipment_remarks}
					<div style="grid-column: 1 / -1;"><span class="label">Remarks</span>{wb.shipment_remarks}</div>
				{/if}
			</div>
		</div>
	{/if}
</div>

<style>
	.waybill-card {
		background: #fff;
		border: 2px solid #000;
		max-width: 640px;
		margin-bottom: 1rem;
		padding: 1rem;
		font-family: 'Courier New', monospace;
	}
	.wb-head { text-align: center; border-bottom: 1px solid #000; margin-bottom: 0.75rem; }
	.wb-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 0.6rem 1.2rem;
	}
	.label {
		display: block;
		font-size: 0.7rem;
		font-weight: bold;
		text-transform: uppercase;
		color: #555;
	}
	@media print {
		.waybill-card { page-break-inside: avoid; }
	}
</style>
