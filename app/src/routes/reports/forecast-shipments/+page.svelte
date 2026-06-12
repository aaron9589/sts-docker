<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
	import ForecastTable from '$lib/components/ForecastTable.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
</script>

<svelte:head><title>STS - Shipment Forecast</title></svelte:head>

<Navbar title="Shipment Forecast" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<p class="muted">
		Approximate carloads at each loading location over the next 10 operating sessions. Reload the
		page for a fresh simulation.
	</p>
	<ForecastTable rows={data.rows} startSession={data.startSession} groupBy="loading_location" />
</div>
