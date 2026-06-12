<script lang="ts">
	import { invalidateAll } from '$app/navigation';
	import { deserialize } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { EligibleCar, CarTier } from '$lib/types';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();

	let expanded: string | null = $state(null);
	let cars: EligibleCar[] = $state([]);
	let loading = $state(false);

	const tierLabel: Record<CarTier, string> = {
		pool: 'Pool',
		station: 'Station',
		priority: 'Priority',
		system: 'System'
	};

	async function post(action: string, fields: Record<string, string>) {
		const body = new FormData();
		for (const [k, v] of Object.entries(fields)) body.set(k, v);
		const res = await fetch(`?/${action}`, { method: 'POST', body });
		return deserialize(await res.text());
	}

	async function toggle(waybill: string) {
		if (expanded === waybill) {
			expanded = null;
			return;
		}
		expanded = waybill;
		cars = [];
		loading = true;
		const result = await post('cars', { waybill });
		if (result.type === 'success' && result.data) {
			cars = result.data.cars as EligibleCar[];
		}
		loading = false;
	}

	async function assign(waybill: string, carId: number) {
		const result = await post('assign', { waybill, car_id: String(carId) });
		if (result.type === 'success') {
			expanded = null;
			await invalidateAll();
		} else {
			alert('Error assigning car. Please try again.');
		}
	}

	function counts(tier: CarTier) {
		return cars.filter((c) => c.tier === tier).length;
	}
</script>

<svelte:head><title>STS - Fill Car Orders</title></svelte:head>

<Navbar title="Fill Car Orders" variant="green" back={{ href: '/operations', label: 'Operations' }} />

<div class="page">
	{#if data.orders.length === 0}
		<div class="alert alert-info">There are no car orders that need to be filled.</div>
	{:else}
		<div class="alert alert-info">
			<strong>{data.orders.length} open car orders.</strong>
			Click an order to see available cars, then click a car to assign it.
		</div>

		{#each data.orders as order (order.waybill_number)}
			<div class="card order-card" class:pool={order.pool_count > 0}>
				<button type="button" class="order-header" onclick={() => toggle(order.waybill_number)}>
					<span>
						<strong>{order.waybill_number}</strong>
						{#if order.pool_count > 0}<span class="badge-pool">Pool</span>{/if}
						<span class="muted"> &mdash; {order.shipment_code} - {order.description}</span>
					</span>
					<span>{expanded === order.waybill_number ? '▲' : '▼'}</span>
				</button>

				{#if expanded === order.waybill_number}
					<div class="order-details">
						<div class="order-info">
							<div><span class="label">Consignment</span>{order.consignment || '(none)'}</div>
							<div><span class="label">Car Code</span>{order.car_code}</div>
							<div>
								<span class="label">Loading</span>
								<u>{order.loading_station}</u><br />{order.loading_location}
							</div>
							<div>
								<span class="label">Unloading</span>
								<u>{order.unloading_station}</u><br />{order.unloading_location}
							</div>
							{#if order.remarks}
								<div><span class="label">Remarks</span>{order.remarks}</div>
							{/if}
						</div>

						{#if loading}
							<p class="muted">Loading available cars…</p>
						{:else if cars.length === 0}
							<div class="alert alert-warning">No eligible cars found on the system</div>
						{:else}
							<p class="small muted">
								<strong>{cars.length} eligible cars:</strong>
								Pool {counts('pool')} · Station {counts('station')} · Priority {counts('priority')} ·
								System {counts('system')}
							</p>
							<div class="car-grid">
								{#each cars as car (car.car_id)}
									<button
										type="button"
										class="car-row tier-{car.tier}"
										onclick={() => assign(order.waybill_number, car.car_id)}
									>
										<span class="car-row-top">
											<strong>{car.reporting_marks}</strong>
											<span class="tier-tag">{tierLabel[car.tier]}</span>
											<span class="load-count">Load: {car.load_count}</span>
										</span>
										<span class="small">
											<strong>Code:</strong> {car.car_code} &nbsp;
											<strong>Location:</strong> <u>{car.current_station}</u> / {car.current_location}
										</span>
										{#if car.remarks}<span class="small muted">{car.remarks}</span>{/if}
									</button>
								{/each}
							</div>
						{/if}
					</div>
				{/if}
			</div>
		{/each}
	{/if}
</div>

<style>
	.order-card { padding: 0; overflow: hidden; }
	.order-card.pool { background: #fffbe0; }
	.order-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 100%;
		padding: 1rem;
		border: none;
		background: transparent;
		font: inherit;
		cursor: pointer;
		text-align: left;
	}
	.order-header:hover { background: #e9ecef; }
	.badge-pool {
		background: #ffc107;
		color: #333;
		border-radius: 3px;
		padding: 2px 6px;
		font-size: 0.75rem;
		font-weight: 600;
		margin-left: 0.4rem;
	}
	.order-details { padding: 1rem; border-top: 1px solid var(--border); }
	.order-info {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 0.75rem 1rem;
		margin-bottom: 1rem;
	}
	.order-info .label {
		display: block;
		font-weight: bold;
		color: var(--text-2);
		font-size: 0.75rem;
		text-transform: uppercase;
	}
	.car-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
		gap: 0.5rem;
		max-height: 600px;
		overflow-y: auto;
	}
	.car-row {
		display: flex;
		flex-direction: column;
		gap: 0.3rem;
		padding: 0.75rem;
		border-radius: 0.25rem;
		border: 1px solid transparent;
		cursor: pointer;
		font: inherit;
		text-align: left;
	}
	.car-row:hover { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1); }
	.car-row.tier-pool { background: gray; color: #fff; }
	.car-row.tier-station { background: darkgray; color: #fff; }
	.car-row.tier-priority { background: lightgray; }
	.car-row.tier-system { background: #fff; border-color: var(--border); }
	.car-row-top { display: flex; align-items: center; gap: 0.5rem; }
	.tier-tag {
		background: #6c757d;
		color: #fff;
		border-radius: 3px;
		padding: 1px 5px;
		font-size: 0.72rem;
	}
	.load-count {
		margin-left: auto;
		background: #e9ecef;
		color: #000;
		border-radius: 0.25rem;
		padding: 2px 6px;
		font-size: 0.8rem;
		font-weight: bold;
	}
</style>
