<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { form }: PageProps = $props();
	let confirmed = $state(false);
	let importing = $state(false);
</script>

<svelte:head><title>STS - Restore / Import</title></svelte:head>

<Navbar title="Restore / Import" variant="grey" back={{ href: '/maint', label: 'Maintenance' }} />

<div class="page">
	{#if form && 'error' in form && form.error}
		<div class="alert alert-danger">{form.error}</div>
	{/if}

	{#if form && form.counts}
		{@const counts = form.counts}
		{@const warnings = form.warnings ?? []}
		{@const orphans = form.orphans ?? []}
		<div class="alert alert-success">
			<strong>Import of {form.fileName} complete.</strong><br />
			{counts.stations} stations, {counts.locations} locations, {counts.cars} cars,
			{counts.shipments} shipments, {counts.orders} car orders,
			{counts.jobs} jobs ({counts.steps} steps), {counts.history} history entries.
		</div>
		{#if warnings.length > 0}
			<div class="alert alert-warning">
				<strong>{warnings.length} warning(s):</strong>
				<ul style="margin: 0.5rem 0 0;">
					{#each warnings as w}
						<li>{w}</li>
					{/each}
				</ul>
			</div>
		{/if}
		{#if orphans.length > 0}
			<div class="alert alert-warning">
				Some imported rows reference records that no longer exist (usually history entries for
				deleted cars): {orphans.map((o) => `${o.table} (${o.count})`).join(', ')}.
				Review them on the <a href="/maint/validate">Validate DB</a> page.
			</div>
		{/if}
		<p><a class="btn btn-primary" href="/">Go to Home</a></p>
	{/if}

	<div class="card">
		<h3>Import a legacy backup (.sql)</h3>
		<p class="muted small">
			Upload the file downloaded from the old STS app's <strong>Backup DB</strong> page. The
			legacy data is converted to the new format (job step tables, reposition waybills, pickup
			criteria) and <strong>replaces everything currently in this database</strong>.
		</p>
		<form
			method="POST"
			action="?/import"
			enctype="multipart/form-data"
			use:enhance={() => {
				importing = true;
				return async ({ update }) => {
					importing = false;
					confirmed = false;
					await update();
				};
			}}
		>
			<div class="form-row">
				<input type="file" name="backup" accept=".sql,text/plain" required />
			</div>
			<label>
				<input type="checkbox" bind:checked={confirmed} />
				Yes, replace all current data with this backup
			</label>
			<br /><br />
			<button class="btn btn-danger" disabled={!confirmed || importing}>
				{importing ? 'Importing…' : 'IMPORT BACKUP'}
			</button>
		</form>
	</div>

	<div class="card">
		<h3>Restore a SQLite backup (.db)</h3>
		<p class="muted small">
			Backups downloaded from this app's <a href="/maint/backup">Backup DB</a> page are complete
			SQLite database files. To restore one, stop the container and replace
			<code>sts.db</code> in the data volume with the backup file, then start the container again.
		</p>
	</div>
</div>

<style>
	input[type='file'] {
		border: 1.5px solid var(--border);
		border-radius: 0.375rem;
		padding: 0.4rem 0.6rem;
		background: #fff;
		min-height: 44px;
	}
</style>
