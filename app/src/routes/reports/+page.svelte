<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
</script>

<svelte:head><title>STS - Reports</title></svelte:head>

<Navbar title="Reports" back={{ href: '/', label: 'Home' }} />

<div class="page">
	<div class="rpt-phases">

		<div class="rpt-phase">
			<div class="phase-header">
				<i class="bi bi-train-front"></i> Operations
			</div>
			<div class="phase-cards">
				<a class="rpt-card" href="/reports/switchlist">
					<i class="bi bi-list-columns-reverse rpt-icon"></i>
					<div class="rpt-body">
						<h3>Switchlist</h3>
						<p>Printable switchlist for a job/train</p>
					</div>
				</a>
				<a class="rpt-card" href="/reports/station">
					<i class="bi bi-building rpt-icon"></i>
					<div class="rpt-body">
						<h3>Station Car Report</h3>
						<p>Cars on hand at a station with per-job pickup summaries</p>
					</div>
				</a>
				<a class="rpt-card" href="/reports/wheel">
					<i class="bi bi-diagram-3 rpt-icon"></i>
					<div class="rpt-body">
						<h3>Wheel Report</h3>
						<p>All cars handled by each job with pickups and destinations</p>
					</div>
				</a>
			</div>
		</div>

		<div class="rpt-phase">
			<div class="phase-header">
				<i class="bi bi-file-earmark-text"></i> Waybills
			</div>
			<div class="phase-cards">
				<a class="rpt-card" href="/reports/waybill">
					<i class="bi bi-receipt rpt-icon"></i>
					<div class="rpt-body">
						<h3>Waybill</h3>
						<p>Print a waybill, with an empty-car leg when the car must travel to load</p>
					</div>
				</a>
			</div>
		</div>

		<div class="rpt-phase">
			<div class="phase-header">
				<i class="bi bi-bar-chart-line"></i> Management
			</div>
			<div class="phase-cards">
				<a class="rpt-card" href="/reports/fleet">
					<i class="bi bi-train-lightrail rpt-icon"></i>
					<div class="rpt-body">
						<h3>Fleet Report</h3>
						<p>The full car roster with status and locations</p>
					</div>
				</a>
				<a class="rpt-card" href="/reports/forecast-cars">
					<i class="bi bi-graph-up rpt-icon"></i>
					<div class="rpt-body">
						<h3>Car Forecast</h3>
						<p>Projected car loadings over the next 10 sessions, by car code</p>
					</div>
				</a>
				<a class="rpt-card" href="/reports/forecast-shipments">
					<i class="bi bi-graph-up-arrow rpt-icon"></i>
					<div class="rpt-body">
						<h3>Shipment Forecast</h3>
						<p>Projected loadings by loading location and shipment</p>
					</div>
				</a>
				<a class="rpt-card" href="/reports/history">
					<i class="bi bi-clock-history rpt-icon"></i>
					<div class="rpt-body">
						<h3>Car History</h3>
						<p>Movement history for an individual car</p>
					</div>
				</a>
			</div>
		</div>

	</div>
</div>

<style>
	.rpt-phases {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 1.25rem;
		margin-top: 0.5rem;
		align-items: start;
	}
	@media (max-width: 900px) {
		.rpt-phases { grid-template-columns: 1fr; }
	}

	.phase-header {
		background: var(--blue);
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
		border: 1px solid #cdd9f0;
		border-top: none;
		border-radius: 0 0 0.4rem 0.4rem;
		overflow: hidden;
	}

	.rpt-card {
		display: flex;
		align-items: flex-start;
		gap: 0.85rem;
		padding: 0.85rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #eee;
		transition: background 0.12s;
	}
	.rpt-card:last-child { border-bottom: none; }
	.rpt-card:hover { background: #f0f4ff; }

	.rpt-icon {
		font-size: 1.35rem;
		color: var(--blue);
		flex-shrink: 0;
		margin-top: 2px;
	}

	.rpt-body { flex: 1; min-width: 0; }
	.rpt-body h3 { font-size: 0.95rem; margin: 0 0 0.15rem; font-weight: 600; }
	.rpt-body p { color: var(--text-2); font-size: 0.8rem; margin: 0; }
</style>
