<script lang="ts">
	import Navbar from '$lib/components/Navbar.svelte';
</script>

<svelte:head><title>STS - Backup</title></svelte:head>

<Navbar title="Backup Database" variant="grey" back={{ href: '/maint', label: 'Maintenance' }} />

<div class="page">
	<div class="card">
		<p>The entire database is a single SQLite file. Download a snapshot below.</p>
		<a class="btn btn-primary" href="/maint/backup/download" download>Download Backup</a>
		<p class="small muted" style="margin-top: 0.75rem;">
			To restore, stop the container and replace <code>data/sts.db</code> with the downloaded file.
		</p>
	</div>
</div>
