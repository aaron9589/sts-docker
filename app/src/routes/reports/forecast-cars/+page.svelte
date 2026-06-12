<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
	import ForecastTable from '$lib/components/ForecastTable.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
</script>

<svelte:head><title>STS - Car Forecast</title></svelte:head>

<Navbar title="Car Forecast" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<p class="muted">
		Approximate carloads over the next 10 operating sessions, simulated from each shipment's
		interval and amount ranges. Reload the page for a fresh simulation.
	</p>
	<ForecastTable rows={data.rows} startSession={data.startSession} groupBy="car_code" />
</div>
