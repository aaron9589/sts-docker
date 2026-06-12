<script lang="ts">
	import { page } from '$app/state';
	import { goto } from '$app/navigation';
	import type { PageProps } from './$types';
	let { data }: PageProps = $props();

	let expanded = $state<string | null>(page.url.searchParams.get('tab') ?? 'ops');
	const toggle = (key: string) => {
		expanded = expanded === key ? null : key;
		const params = new URLSearchParams(page.url.searchParams);
		if (expanded) params.set('tab', expanded); else params.delete('tab');
		goto(`?${params}`, { replaceState: true, keepFocus: true, noScroll: true });
	};

	let pickUpJob = $state('');
	let setOutJob = $state('');
	let organizeJob = $state('');
	let switchlistStation = $state('');

	const dbGroups = [
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

<svelte:head><title>STS - Home</title></svelte:head>

<nav class="navbar navbar-blue noprint">
	<span class="brand">
		{data.railroadName || 'Shipper-Driven Traffic Simulator'}
		<span class="navbar-subtitle">Session {data.session}</span>
	</span>
</nav>

<div class="page">

	<!-- Tab buttons -->
	<div class="tab-bar">
		<button class="tab green" class:active={expanded === 'ops'} onclick={() => toggle('ops')}>
			<i class="bi bi-play-circle"></i> Operations
		</button>
		<button class="tab blue" class:active={expanded === 'reports'} onclick={() => toggle('reports')}>
			<i class="bi bi-file-earmark-text"></i> Reports
		</button>
		<button class="tab grey" class:active={expanded === 'data'} onclick={() => toggle('data')}>
			<i class="bi bi-database"></i> Database
		</button>
		<button class="tab red" class:active={expanded === 'maint'} onclick={() => toggle('maint')}>
			<i class="bi bi-tools"></i> DB Maintenance
		</button>
	</div>

	<!-- Accordion panel — full width below the tabs -->
	{#if expanded === 'ops'}
		<div class="panel panel-green">
			<div class="ops-phases">
				<div class="ops-phase">
					<div class="phase-header green-header">
						<i class="bi bi-clock-history"></i> Before Operations
					</div>
					<div class="phase-cards">
						<a class="op-card" href="/operations/generate"><i class="bi bi-gear op-icon"></i><div class="op-body"><h3>Generate Car Orders</h3><p>Auto or manually generate car orders for the session</p></div></a>
						<a class="op-card" href="/operations/fill-orders"><i class="bi bi-box op-icon"></i><div class="op-body"><h3>Fill Car Orders</h3><p>Assign available cars to open orders</p></div></a>
						<a class="op-card" href="/operations/reposition"><i class="bi bi-arrows-move op-icon"></i><div class="op-body"><h3>Reposition Empty Cars</h3><p>Move empty cars to their home location</p></div></a>
					</div>
				</div>
				<div class="ops-phase">
					<div class="phase-header green-header">
						<i class="bi bi-play-circle"></i> During Operations
					</div>
					<div class="phase-cards">
						<div class="op-card op-card-launcher">
							<i class="bi bi-list-check op-icon"></i>
							<div class="op-body">
								<h3>Build Switch Lists</h3><p>Assign cars to jobs/trains for switching</p>
								<div class="quick-launch">
									<select bind:value={switchlistStation} onchange={() => switchlistStation && goto(`/operations/switchlists?station=${switchlistStation}`)}>
										<option value="">— select station —</option>
										{#each data.stations as s (s.id)}<option value={s.id}>{s.name}</option>{/each}
									</select>
								</div>
							</div>
						</div>
						<div class="op-card op-card-launcher">
							<i class="bi bi-arrow-up-circle op-icon"></i>
							<div class="op-body">
								<h3>Pick Up Cars</h3><p>Mark cars as picked up by a job</p>
								<div class="quick-launch">
									<select bind:value={pickUpJob} onchange={() => pickUpJob && goto(`/operations/pick-up?job=${pickUpJob}`)}>
										<option value="">— select job —</option>
										{#each data.jobs as j (j.id)}<option value={j.id}>{j.name}</option>{/each}
									</select>
								</div>
							</div>
						</div>
						<div class="op-card op-card-launcher">
							<i class="bi bi-sort-numeric-up op-icon"></i>
							<div class="op-body">
								<h3>Organize Cars</h3><p>View and adjust car order in a consist</p>
								<div class="quick-launch">
									<select bind:value={organizeJob} onchange={() => organizeJob && goto(`/operations/organize?job=${organizeJob}`)}>
										<option value="">— select job —</option>
										{#each data.jobs as j (j.id)}<option value={j.id}>{j.name}</option>{/each}
									</select>
								</div>
							</div>
						</div>
						<div class="op-card op-card-launcher">
							<i class="bi bi-arrow-down-circle op-icon"></i>
							<div class="op-body">
								<h3>Set Out Cars</h3><p>Set out cars at their destination</p>
								<div class="quick-launch">
									<select bind:value={setOutJob} onchange={() => setOutJob && goto(`/operations/set-out?job=${setOutJob}`)}>
										<option value="">— select job —</option>
										{#each data.jobs as j (j.id)}<option value={j.id}>{j.name}</option>{/each}
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ops-phase">
					<div class="phase-header green-header">
						<i class="bi bi-check-circle"></i> After Operations
					</div>
					<div class="phase-cards">
						<a class="op-card" href="/operations/load-unload"><i class="bi bi-truck op-icon"></i><div class="op-body"><h3>Load / Unload Cars</h3><p>Complete car loading and unloading</p></div></a>
					</div>
				</div>
			</div>
		</div>
	{/if}

	{#if expanded === 'reports'}
		<div class="panel panel-blue">
			<div class="col-phases">
				<div class="col-phase">
					<div class="phase-header blue-header"><i class="bi bi-train-front"></i> Operations</div>
					<div class="phase-cards">
						<a class="rpt-card" href="/reports/switchlist"><i class="bi bi-list-columns-reverse rpt-icon"></i><div class="rpt-body"><h3>Switchlist</h3><p>Printable switchlist for a job/train</p></div></a>
						<a class="rpt-card" href="/reports/station"><i class="bi bi-building rpt-icon"></i><div class="rpt-body"><h3>Station Car Report</h3><p>Cars on hand at a station with per-job pickup summaries</p></div></a>
						<a class="rpt-card" href="/reports/wheel"><i class="bi bi-diagram-3 rpt-icon"></i><div class="rpt-body"><h3>Wheel Report</h3><p>All cars handled by each job with pickups and destinations</p></div></a>
					</div>
				</div>
				<div class="col-phase">
					<div class="phase-header blue-header"><i class="bi bi-file-earmark-text"></i> Waybills</div>
					<div class="phase-cards">
						<a class="rpt-card" href="/reports/waybill"><i class="bi bi-receipt rpt-icon"></i><div class="rpt-body"><h3>Waybill</h3><p>Print a waybill, with an empty-car leg when the car must travel to load</p></div></a>
					</div>
				</div>
				<div class="col-phase">
					<div class="phase-header blue-header"><i class="bi bi-bar-chart-line"></i> Management</div>
					<div class="phase-cards">
						<a class="rpt-card" href="/reports/fleet"><i class="bi bi-train-lightrail rpt-icon"></i><div class="rpt-body"><h3>Fleet Report</h3><p>The full car roster with status and locations</p></div></a>
						<a class="rpt-card" href="/reports/forecast-cars"><i class="bi bi-graph-up rpt-icon"></i><div class="rpt-body"><h3>Car Forecast</h3><p>Projected car loadings over the next 10 sessions, by car code</p></div></a>
						<a class="rpt-card" href="/reports/forecast-shipments"><i class="bi bi-graph-up-arrow rpt-icon"></i><div class="rpt-body"><h3>Shipment Forecast</h3><p>Projected loadings by loading location and shipment</p></div></a>
						<a class="rpt-card" href="/reports/history"><i class="bi bi-clock-history rpt-icon"></i><div class="rpt-body"><h3>Car History</h3><p>Movement history for an individual car</p></div></a>
					</div>
				</div>
			</div>
		</div>
	{/if}

	{#if expanded === 'data'}
		<div class="panel panel-grey">
			<div class="col-phases">
				{#each dbGroups as group (group.title)}
					<div class="col-phase">
						<div class="phase-header grey-header"><i class="bi {group.icon}"></i> {group.title}</div>
						<div class="phase-cards">
							{#each group.items as item (item.href)}
								<a class="db-card" href={item.href}><i class="bi {item.icon} db-icon"></i><div class="db-body"><h3>{item.label}</h3><p>{item.desc}</p></div></a>
							{/each}
						</div>
					</div>
				{/each}
			</div>
		</div>
	{/if}

	{#if expanded === 'maint'}
		<div class="panel panel-grey">
			<div class="col-phases">
				<div class="col-phase">
					<div class="phase-header grey-header"><i class="bi bi-tools"></i> Database</div>
					<div class="phase-cards">
						<a class="maint-card" href="/maint/validate"><i class="bi bi-check2-circle maint-icon"></i><div class="maint-body"><h3>Validate DB</h3><p>Check for ghost records, orphaned orders and inconsistencies</p></div></a>
						<a class="maint-card" href="/maint/backup"><i class="bi bi-download maint-icon"></i><div class="maint-body"><h3>Backup DB</h3><p>Download a copy of the SQLite database file</p></div></a>
						<a class="maint-card" href="/maint/restore"><i class="bi bi-upload maint-icon"></i><div class="maint-body"><h3>Restore / Import</h3><p>Import a legacy STS backup (.sql) or restore a SQLite backup</p></div></a>
					</div>
				</div>
				<div class="col-phase">
					<div class="phase-header grey-header"><i class="bi bi-sliders"></i> Settings &amp; Simulation</div>
					<div class="phase-cards">
						<a class="maint-card" href="/maint"><i class="bi bi-sliders maint-icon"></i><div class="maint-body"><h3>Settings &amp; Logos</h3><p>Railroad name, logos and other settings</p></div></a>
						<a class="maint-card" href="/maint"><i class="bi bi-exclamation-triangle maint-icon"></i><div class="maint-body"><h3>Restart / Reset / Wipe</h3><p>Simulation control and database wipe</p></div></a>
					</div>
				</div>
			</div>
		</div>
	{/if}

</div>

<style>
	/* ── Tab bar ── */
	.tab-bar {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 0.5rem;
		margin-bottom: 0;
	}
	@media (max-width: 600px) {
		.tab-bar { grid-template-columns: repeat(2, 1fr); }
	}

	.tab {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.45rem;
		padding: 0.75rem 0.5rem;
		border: none;
		border-radius: 0.45rem 0.45rem 0 0;
		font-size: 0.92rem;
		font-weight: 600;
		color: #fff;
		cursor: pointer;
		opacity: 0.72;
		transition: opacity 0.12s, filter 0.12s;
	}
	.tab:hover { opacity: 0.88; }
	.tab.active { opacity: 1; }

	.tab.green { background: var(--green); }
	.tab.blue  { background: var(--blue); }
	.tab.grey  { background: #6c757d; }
	.tab.red   { background: var(--danger, #c62828); }

	/* ── Full-width panel ── */
	.panel {
		border-radius: 0 0 0.5rem 0.5rem;
		border: 2px solid transparent;
		overflow: hidden;
	}
	.panel-green { border-color: var(--green); }
	.panel-blue  { border-color: var(--blue); }
	.panel-grey  { border-color: #6c757d; }

	/* Column layout inside panel */
	.ops-phases,
	.col-phases {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		align-items: start;
	}
	@media (max-width: 700px) {
		.ops-phases, .col-phases { grid-template-columns: 1fr; }
	}

	.ops-phase,
	.col-phase {
		border-right: 1px solid #e0e0e0;
	}
	.ops-phase:last-child,
	.col-phase:last-child { border-right: none; }

	/* Phase column header */
	.phase-header {
		font-size: 0.8rem;
		font-weight: 700;
		padding: 0.5rem 1rem;
		display: flex;
		align-items: center;
		gap: 0.4rem;
		color: #fff;
		border-bottom: 1px solid rgba(255,255,255,0.2);
	}
	.green-header { background: var(--green); }
	.blue-header  { background: var(--blue); }
	.grey-header  { background: #6c757d; }

	.phase-cards {
		background: #fff;
		display: flex;
		flex-direction: column;
	}

	/* Operations cards */
	.op-card {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		padding: 0.7rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #f0f0f0;
		transition: background 0.12s;
	}
	.op-card:last-child { border-bottom: none; }
	.op-card:hover { background: #f0f7f0; }
	.op-card-launcher { cursor: default; }
	.op-icon { font-size: 1.2rem; color: var(--green); flex-shrink: 0; margin-top: 2px; }
	.op-body { flex: 1; min-width: 0; }
	.op-body h3 { font-size: 0.9rem; margin: 0 0 0.1rem; font-weight: 600; }
	.op-body p { color: var(--text-2); font-size: 0.78rem; margin: 0 0 0.35rem; }
	.quick-launch { display: flex; gap: 0.4rem; align-items: center; }
	.quick-launch select { flex: 1; min-height: 30px; font-size: 0.79rem; padding: 0.1rem 0.3rem; }

	/* Reports cards */
	.rpt-card {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		padding: 0.7rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #f0f0f0;
		transition: background 0.12s;
	}
	.rpt-card:last-child { border-bottom: none; }
	.rpt-card:hover { background: #f0f4ff; }
	.rpt-icon { font-size: 1.2rem; color: var(--blue); flex-shrink: 0; margin-top: 2px; }
	.rpt-body { flex: 1; min-width: 0; }
	.rpt-body h3 { font-size: 0.9rem; margin: 0 0 0.1rem; font-weight: 600; }
	.rpt-body p { color: var(--text-2); font-size: 0.78rem; margin: 0; }

	/* Database cards */
	.db-card {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		padding: 0.7rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #f0f0f0;
		transition: background 0.12s;
	}
	.db-card:last-child { border-bottom: none; }
	.db-card:hover { background: var(--grey-bg); }
	.db-icon { font-size: 1.2rem; color: #6c757d; flex-shrink: 0; margin-top: 2px; }
	.db-body { flex: 1; min-width: 0; }
	.db-body h3 { font-size: 0.9rem; margin: 0 0 0.1rem; font-weight: 600; }
	.db-body p { color: var(--text-2); font-size: 0.78rem; margin: 0; }

	/* Maintenance cards */
	.maint-card {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		padding: 0.7rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #f0f0f0;
		transition: background 0.12s;
	}
	.maint-card:last-child { border-bottom: none; }
	.maint-card:hover { background: var(--grey-bg); }
	.maint-icon { font-size: 1.2rem; color: #6c757d; flex-shrink: 0; margin-top: 2px; }
	.maint-body { flex: 1; min-width: 0; }
	.maint-body h3 { font-size: 0.9rem; margin: 0 0 0.1rem; font-weight: 600; }
	.maint-body p { color: var(--text-2); font-size: 0.78rem; margin: 0; }
</style>
