<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';

	const groups = [
		{
			title: 'Railroad',
			icon: 'bi-map',
			items: [
				{ href: '/data/stations', label: 'Stations', icon: 'bi-geo-alt', desc: 'Stations, sort order and routing instructions.' },
				{ href: '/data/locations', label: 'Locations', icon: 'bi-pin-map', desc: 'Spots and tracks at each station.' },
				{ href: '/data/jobs', label: 'Jobs / Trains', icon: 'bi-signpost-2', desc: 'Jobs with their station steps and auto-assign criteria.' }
			]
		},
		{
			title: 'Traffic',
			icon: 'bi-arrow-left-right',
			items: [
				{ href: '/data/shipments', label: 'Shipments', icon: 'bi-send', desc: 'Recurring traffic patterns and schedules.' },
				{ href: '/data/commodities', label: 'Commodities', icon: 'bi-basket', desc: 'What gets shipped.' },
				{ href: '/data/car-orders', label: 'Car Orders', icon: 'bi-receipt', desc: 'Open and filled waybills.' },
				{ href: '/data/pool', label: 'Special Pool', icon: 'bi-collection', desc: 'Cars dedicated to specific shipments.' },
				{ href: '/data/empty-locations', label: 'Priority Empty Locations', icon: 'bi-sort-down', desc: 'Preferred sources of empties per shipment.' },
				{ href: '/data/pu-criteria', label: 'Auto-Assign Criteria', icon: 'bi-sliders', desc: 'Pickup rules for the auto-assigner.' }
			]
		},
		{
			title: 'Fleet',
			icon: 'bi-train-front',
			items: [
				{ href: '/data/cars', label: 'Cars', icon: 'bi-train-lightrail', desc: 'The car roster.' },
				{ href: '/data/car-codes', label: 'Car Codes', icon: 'bi-tag', desc: 'Car type codes.' },
				{ href: '/data/owners', label: 'Owners', icon: 'bi-person', desc: 'Car owners.' },
				{ href: '/data/ownership', label: 'Ownership', icon: 'bi-people', desc: 'Car-owner links and on/off-railroad state.' }
			]
		}
	];
</script>

<svelte:head><title>STS - Database</title></svelte:head>

<Navbar title="Database" variant="grey" back={{ href: '/', label: 'Home' }} />

<div class="page">
	<div class="db-phases">
		{#each groups as group (group.title)}
			<div class="db-phase">
				<div class="phase-header">
					<i class="bi {group.icon}"></i> {group.title}
				</div>
				<div class="phase-cards">
					{#each group.items as item (item.href)}
						<a class="db-card" href={item.href}>
							<i class="bi {item.icon} db-icon"></i>
							<div class="db-body">
								<h3>{item.label}</h3>
								<p>{item.desc}</p>
							</div>
						</a>
					{/each}
				</div>
			</div>
		{/each}
	</div>
</div>

<style>
	.db-phases {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 1.25rem;
		margin-top: 0.5rem;
		align-items: start;
	}
	@media (max-width: 900px) {
		.db-phases { grid-template-columns: 1fr; }
	}

	.phase-header {
		background: #6c757d;
		color: #fff;
		font-size: 0.85rem;
		font-weight: 700;
		padding: 0.55rem 0.9rem;
		border-radius: 0.4rem 0.4rem 0 0;
		display: flex;
		align-items: center;
		gap: 0.4rem;
	}

	.phase-cards {
		background: #fff;
		border: 1px solid #d0d5db;
		border-top: none;
		border-radius: 0 0 0.4rem 0.4rem;
		overflow: hidden;
	}

	.db-card {
		display: flex;
		align-items: flex-start;
		gap: 0.85rem;
		padding: 0.85rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #eee;
		transition: background 0.12s;
	}
	.db-card:last-child { border-bottom: none; }
	.db-card:hover { background: var(--grey-bg); }

	.db-icon {
		font-size: 1.25rem;
		color: #6c757d;
		flex-shrink: 0;
		margin-top: 2px;
	}

	.db-body { flex: 1; min-width: 0; }
	.db-body h3 { font-size: 0.95rem; margin: 0 0 0.15rem; font-weight: 600; }
	.db-body p { color: var(--text-2); font-size: 0.8rem; margin: 0; }
</style>
