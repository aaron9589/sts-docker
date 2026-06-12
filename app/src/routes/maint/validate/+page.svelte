<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();
	const problems = $derived(data.sections.reduce((n, s) => n + s.rows.length, 0));
</script>

<svelte:head><title>STS - Validate DB</title></svelte:head>

<Navbar title="Validate Database" variant="grey" back={{ href: '/maint', label: 'Maintenance' }} />

<div class="page">
	{#if problems === 0}
		<div class="alert alert-success">No problems found. The database is consistent.</div>
	{:else}
		<div class="alert alert-warning">{problems} problem(s) found.</div>
	{/if}

	{#each data.sections as section (section.title)}
		{#if section.rows.length > 0}
			<div class="card">
				<h3>{section.title} ({section.rows.length})</h3>
				<ul>
					{#each section.rows as row}
						<li>{row}</li>
					{/each}
				</ul>
			</div>
		{/if}
	{/each}
</div>
