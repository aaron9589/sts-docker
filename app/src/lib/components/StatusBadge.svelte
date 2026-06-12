<script lang="ts">
	let { status }: { status: string } = $props();
</script>

<span class="status-badge status-{status.toLowerCase()}">{status}</span>
