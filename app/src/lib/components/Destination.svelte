<script lang="ts">
	import type { CarRow } from '$lib/server/queries';

	/**
	 * Renders a car's loading/unloading/destination cell content following the
	 * legacy display rules: reposition cars show their E-waybill destination;
	 * Ordered cars highlight the loading location; Loaded/Loading/Unloading
	 * highlight the unloading location.
	 */
	interface Props {
		car: Pick<
			CarRow,
			| 'status'
			| 'is_reposition'
			| 'loading_station'
			| 'loading_location'
			| 'unloading_station'
			| 'unloading_location'
			| 'dest_station'
			| 'dest_location'
		>;
		kind: 'loading' | 'unloading' | 'next';
	}
	let { car, kind }: Props = $props();

	const next = $derived.by(() => {
		if (car.is_reposition) {
			return { station: car.dest_station, location: car.dest_location, bold: true };
		}
		if (kind === 'loading' || (kind === 'next' && car.status === 'Ordered')) {
			return {
				station: car.loading_station,
				location: car.loading_location,
				bold: car.status === 'Ordered'
			};
		}
		return {
			station: car.unloading_station,
			location: car.unloading_location,
			bold: ['Loaded', 'Loading', 'Unloading'].includes(car.status)
		};
	});

	const show = $derived.by(() => {
		if (car.is_reposition && kind === 'loading') return false;
		return !!(next.station || next.location);
	});
</script>

{#if car.is_reposition && kind === 'loading'}
	<span class="muted">N/A</span>
{:else if show}
	{#if next.bold}
		<strong>{next.station}<br />{next.location}</strong>
	{:else}
		{next.station}<br />{next.location}
	{/if}
{/if}
