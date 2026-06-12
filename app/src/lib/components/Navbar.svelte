<script lang="ts">
	interface Props {
		title: string;
		subtitle?: string;
		variant?: 'blue' | 'green' | 'grey';
		back?: { href: string; label: string };
		print?: boolean;
	}
	let { title, subtitle, variant = 'blue', back, print = false }: Props = $props();
</script>

<nav class="navbar navbar-{variant} noprint">
	<span class="brand">
		{title}
		{#if subtitle}<span class="navbar-subtitle">{subtitle}</span>{/if}
	</span>
	<div class="nav-actions">
		{#if back}
			<a href={back.href} class="btn btn-sm btn-outline-light">&larr; {back.label}</a>
		{/if}
		<a href="/" class="btn btn-sm btn-outline-light">Home</a>
		{#if print}
			<button type="button" class="btn btn-sm btn-outline-light" onclick={() => window.print()}>Print</button>
		{/if}
	</div>
</nav>
