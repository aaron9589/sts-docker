<script lang="ts">
	import { page } from '$app/state';
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();

	let pickUpJob = $state(page.url.searchParams.get('pickUpJob') ?? '');
	let setOutJob = $state(page.url.searchParams.get('setOutJob') ?? '');
	let organizeJob = $state(page.url.searchParams.get('organizeJob') ?? '');
	let switchlistStation = $state(page.url.searchParams.get('switchlistStation') ?? '');

	function updateParam(key: string, value: string) {
		const params = new URLSearchParams(page.url.searchParams);
		if (value) params.set(key, value); else params.delete(key);
		goto(`?${params}`, { replaceState: true, keepFocus: true, noScroll: true });
	}
</script>

<svelte:head><title>STS - Operations</title></svelte:head>

<Navbar title="Operations" subtitle={data.railroadName ?? undefined} variant="green" />

<div class="page">
	<p class="muted noprint" style="margin-bottom:1rem;">Session <strong>{data.session}</strong></p>

	<div class="ops-phases">

		<!-- ── Before Operations ── -->
		<div class="ops-phase">
			<div class="phase-header">
				<i class="bi bi-clock-history"></i> Before Operations
			</div>
			<div class="phase-cards">

				<a class="op-card" href="/operations/generate">
					<i class="bi bi-gear op-icon"></i>
					<div class="op-body">
						<h3>Generate Car Orders</h3>
						<p>Auto or manually generate car orders for the session</p>
					</div>
				</a>

				<a class="op-card" href="/operations/fill-orders">
					<i class="bi bi-box op-icon"></i>
					<div class="op-body">
						<h3>Fill Car Orders</h3>
						<p>Assign available cars to open orders</p>
					</div>
				</a>

				<a class="op-card" href="/operations/reposition">
					<i class="bi bi-arrows-move op-icon"></i>
					<div class="op-body">
						<h3>Reposition Empty Cars</h3>
						<p>Move empty cars to their home location</p>
					</div>
				</a>

			</div>
		</div>

		<!-- ── During Operations ── -->
		<div class="ops-phase">
			<div class="phase-header">
				<i class="bi bi-play-circle"></i> During Operations
			</div>
			<div class="phase-cards">

				<div class="op-card op-card-launcher">
					<i class="bi bi-list-check op-icon"></i>
					<div class="op-body">
						<h3>Build Switch Lists</h3>
						<p>Assign cars to jobs/trains for switching</p>
						<div class="quick-launch">
							<select value={switchlistStation} onchange={(e) => { switchlistStation = (e.target as HTMLSelectElement).value; updateParam('switchlistStation', switchlistStation); }}>
								<option value="">— select station —</option>
								{#each data.stations as s (s.id)}
									<option value={s.id}>{s.name}</option>
								{/each}
							</select>
							<a
								href={switchlistStation ? `/operations/switchlists?station=${switchlistStation}` : '/operations/switchlists'}
								class="btn btn-sm btn-success"
							>Go</a>
						</div>
					</div>
				</div>

				<div class="op-card op-card-launcher">
					<i class="bi bi-arrow-up-circle op-icon"></i>
					<div class="op-body">
						<h3>Pick Up Cars</h3>
						<p>Mark cars as picked up by a job</p>
						<div class="quick-launch">
							<select value={pickUpJob} onchange={(e) => { pickUpJob = (e.target as HTMLSelectElement).value; updateParam('pickUpJob', pickUpJob); }}>
								<option value="">— select job —</option>
								{#each data.jobs as j (j.id)}
									<option value={j.id}>{j.name}</option>
								{/each}
							</select>
							<a
								href={pickUpJob ? `/operations/pick-up?job=${pickUpJob}` : '/operations/pick-up'}
								class="btn btn-sm btn-success"
							>Go</a>
						</div>
					</div>
				</div>

				<div class="op-card op-card-launcher">
					<i class="bi bi-sort-numeric-up op-icon"></i>
					<div class="op-body">
						<h3>Organize Cars</h3>
						<p>View and adjust car order in a consist</p>
						<div class="quick-launch">
							<select value={organizeJob} onchange={(e) => { organizeJob = (e.target as HTMLSelectElement).value; updateParam('organizeJob', organizeJob); }}>
								<option value="">— select job —</option>
								{#each data.jobs as j (j.id)}
									<option value={j.id}>{j.name}</option>
								{/each}
							</select>
							<a
								href={organizeJob ? `/operations/organize?job=${organizeJob}` : '/operations/organize'}
								class="btn btn-sm btn-success"
							>Go</a>
						</div>
					</div>
				</div>

				<div class="op-card op-card-launcher">
					<i class="bi bi-arrow-down-circle op-icon"></i>
					<div class="op-body">
						<h3>Set Out Cars</h3>
						<p>Set out cars at their destination</p>
						<div class="quick-launch">
							<select value={setOutJob} onchange={(e) => { setOutJob = (e.target as HTMLSelectElement).value; updateParam('setOutJob', setOutJob); }}>
								<option value="">— select job —</option>
								{#each data.jobs as j (j.id)}
									<option value={j.id}>{j.name}</option>
								{/each}
							</select>
							<a
								href={setOutJob ? `/operations/set-out?job=${setOutJob}` : '/operations/set-out'}
								class="btn btn-sm btn-success"
							>Go</a>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- ── After Operations ── -->
		<div class="ops-phase">
			<div class="phase-header">
				<i class="bi bi-check-circle"></i> After Operations
			</div>
			<div class="phase-cards">

				<a class="op-card" href="/operations/load-unload">
					<i class="bi bi-truck op-icon"></i>
					<div class="op-body">
						<h3>Load / Unload Cars</h3>
						<p>Complete car loading and unloading</p>
					</div>
				</a>

			</div>
		</div>

	</div>
</div>

<style>
	.ops-phases {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 1.25rem;
		margin-top: 0.5rem;
		align-items: start;
	}

	@media (max-width: 900px) {
		.ops-phases { grid-template-columns: 1fr; }
	}

	.phase-header {
		background: var(--green);
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
		border: 1px solid #d4e6d4;
		border-top: none;
		border-radius: 0 0 0.4rem 0.4rem;
		overflow: hidden;
	}

	.op-card {
		display: flex;
		align-items: flex-start;
		gap: 0.85rem;
		padding: 0.85rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #eee;
		transition: background 0.12s;
	}
	.op-card:last-child { border-bottom: none; }
	.op-card:hover { background: #f0f7f0; }

	.op-icon {
		font-size: 1.35rem;
		color: var(--green);
		flex-shrink: 0;
		margin-top: 2px;
	}

	.op-body {
		flex: 1;
		min-width: 0;
	}
	.op-body h3 {
		font-size: 0.95rem;
		margin: 0 0 0.15rem;
		font-weight: 600;
	}
	.op-body p {
		color: var(--text-2);
		font-size: 0.8rem;
		margin: 0 0 0.4rem;
	}

	.op-card-launcher {
		cursor: default;
	}
	.op-card-launcher:hover { background: #f0f7f0; }

	.quick-launch {
		display: flex;
		gap: 0.4rem;
		align-items: center;
	}
	.quick-launch select {
		flex: 1;
		min-height: 34px;
		font-size: 0.82rem;
		padding: 0.15rem 0.4rem;
	}
</style>
