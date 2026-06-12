<script lang="ts">
	import { goto } from '$app/navigation';
	import Navbar from '$lib/components/Navbar.svelte';
	import StatusBadge from '$lib/components/StatusBadge.svelte';
	import Destination from '$lib/components/Destination.svelte';
	import type { PageProps } from './$types';

	import type { CarRow } from '$lib/types';

	let { data }: PageProps = $props();

	type Format = 'standard' | 'x2010';
	let format = $state<Format>('standard');

	/* X2010 pagination — mirrors PHP count_row_lines / X2010_LINES_PER_PAGE logic */
	const LINES_PER_PAGE = 22;

	/* ⚑ flags in the Contents column: shipment special instructions, plus the
	   destination location's routing remarks for reposition cars */
	function x2010Flags(car: CarRow): string[] {
		return [car.special_instructions, car.dest_location_remarks]
			.map((t) => (t ?? '').trim())
			.filter((t) => t !== '' && t.toLowerCase() !== 'n/a');
	}

	function rowLines(car: CarRow): number {
		const locLines = car.current_location ? 2 : 1;
		const destLines = 2;
		const contentsLines =
			1 + x2010Flags(car).reduce((n, f) => n + Math.max(1, Math.ceil(f.length / 18)), 0);
		return Math.max(locLines, destLines, contentsLines);
	}

	const pages = $derived.by((): CarRow[][] => {
		if (!data.cars.length) return [];
		const result: CarRow[][] = [];
		let page: CarRow[] = [];
		let lines = 0;
		for (const car of data.cars) {
			const rl = rowLines(car);
			if (page.length > 0 && lines + rl > LINES_PER_PAGE) {
				result.push(page);
				page = [];
				lines = 0;
			}
			page.push(car);
			lines += rl;
		}
		if (page.length) result.push(page);
		return result;
	});

	/* offsets so each page knows its global row number */
	const pageOffsets = $derived(pages.map((_, i) => pages.slice(0, i).reduce((a, p) => a + p.length, 0)));

	/* wagon number: strip trailing check letter */
	function wagonNum(marks: string): string {
		return /[a-zA-Z]$/.test(marks) ? marks.replace(/[a-zA-Z-]+$/, '') : marks;
	}
	function checkLetter(marks: string): string {
		return /[a-zA-Z]$/.test(marks) ? marks.slice(-1) : '';
	}

	/* destination for X2010 */
	function x2010Dest(car: CarRow): { station: string; location: string } {
		if (car.status === 'Loaded') {
			return { station: car.unloading_station ?? '', location: car.unloading_location ?? '' };
		}
		if (car.is_reposition) {
			return { station: car.dest_station ?? '', location: car.dest_location ?? '' };
		}
		return { station: car.loading_station ?? '', location: car.loading_location ?? '' };
	}

	/* strikethrough persistence via localStorage */
	function toggleStrike(event: Event) {
		const cb = event.target as HTMLInputElement;
		const rowId = cb.dataset.rowId!;
		if (cb.checked) {
			localStorage.setItem(rowId, 'struck');
			cb.closest('tr')!.classList.add('struck');
		} else {
			localStorage.removeItem(rowId);
			cb.closest('tr')!.classList.remove('struck');
		}
	}

	function clearStrikes() {
		document.querySelectorAll<HTMLInputElement>('input[data-row-id]').forEach((cb) => {
			cb.checked = false;
			cb.closest('tr')?.classList.remove('struck');
			localStorage.removeItem(cb.dataset.rowId!);
		});
	}

	function restoreStrikes() {
		document.querySelectorAll<HTMLInputElement>('input[data-row-id]').forEach((cb) => {
			if (localStorage.getItem(cb.dataset.rowId!) === 'struck') {
				cb.checked = true;
				cb.closest('tr')?.classList.add('struck');
			}
		});
	}

	import { onMount } from 'svelte';
	onMount(() => {
		if (format === 'x2010') restoreStrikes();
	});

	$effect(() => {
		if (format === 'x2010') {
			requestAnimationFrame(restoreStrikes);
		}
	});
</script>

<svelte:head><title>STS - Switchlist</title></svelte:head>

<Navbar title="Switchlist" back={{ href: '/reports', label: 'Reports' }} print />

<div class="page">
	<div class="card noprint">
		<div class="form-row">
			<label for="job">Job/Train:</label>
			<select
				id="job"
				value={data.selectedJob ?? ''}
				onchange={(e) => goto(`?job=${(e.target as HTMLSelectElement).value}`, { keepFocus: true })}
			>
				<option value=""></option>
				{#each data.jobs as j (j.id)}
					<option value={j.id}>{j.name}</option>
				{/each}
			</select>
		</div>

		{#if data.jobInfo}
			<div class="form-row" style="margin-top:0.5rem;">
				<span style="font-size:0.9rem;">Format:</span>
				<label class="fmt-radio">
					<input type="radio" bind:group={format} value="standard" /> Standard
				</label>
				<label class="fmt-radio">
					<input type="radio" bind:group={format} value="x2010" /> X2010 (Train Consist Form)
				</label>
			</div>
		{/if}
	</div>

	{#if data.jobInfo}
		{#if format === 'standard'}
			<!-- ── Standard format ── -->
			<div class="print-header">
				<h2>{data.railroadName} ({data.railroadInitials})</h2>
				<h3>Switchlist — {data.jobInfo.name}</h3>
			</div>
			<h2 class="noprint">Switchlist — {data.jobInfo.name}</h2>
			{#if data.jobInfo.description}
				<p class="muted" style="white-space: pre-line;">{data.jobInfo.description}</p>
			{/if}

			{#if data.cars.length === 0}
				<div class="alert alert-warning">This switchlist doesn't contain any cars.</div>
			{:else}
				<p><strong>{data.loads}</strong> loads · <strong>{data.empties}</strong> empties · {data.cars.length} cars total</p>
				<div class="table-wrap">
					<table class="report">
						<thead>
							<tr>
								<th>Position</th>
								<th>Reporting Marks</th>
								<th>Car Code</th>
								<th>Status</th>
								<th>Contents</th>
								<th>Current Station / Location</th>
								<th>Destination</th>
								<th>Special Instructions</th>
							</tr>
						</thead>
						<tbody>
							{#each data.cars as car (car.id)}
								<tr>
									<td style="text-align:center;">{car.position}</td>
									<td>{car.reporting_marks}</td>
									<td>{car.car_code}</td>
									<td><StatusBadge status={car.status} /></td>
									<td>{car.is_reposition ? 'Non-Revenue' : car.consignment}</td>
									<td>
										{#if car.current_location}
											{car.current_station} / {car.current_location}
										{:else}
											In Train
										{/if}
									</td>
									<td><Destination {car} kind="next" /></td>
									<td>{car.shipment_remarks}</td>
								</tr>
							{/each}
						</tbody>
					</table>
				</div>
			{/if}

		{:else}
			<!-- ── X2010 Train Consist Form ── -->
			{#if data.cars.length === 0}
				<div class="alert alert-warning noprint">This switchlist doesn't contain any cars.</div>
			{:else}
				<div class="noprint" style="margin-bottom:0.75rem; display:flex; gap:0.5rem; align-items:center;">
					<button class="btn btn-danger btn-sm" onclick={clearStrikes}>Clear Strikes</button>
					<span class="muted small">{data.loads} loads · {data.empties} empties · {data.cars.length} cars</span>
				</div>

				{#each pages as page, pi (pi)}
					<div class="x2010-page {pi > 0 ? 'page-break' : ''}">

						<!-- Header block -->
						<table class="x2010-hdr">
							<tbody>
								<tr>
									<td class="x2010-logo-cell">
										{#if data.hasLogo}
											<img src="/logo?key={data.logoKey}&v={data.logoVersion}" alt={data.railroadName} class="x2010-logo-img" />
										{:else}
											<span class="x2010-rr-name">{data.railroadName}</span>
											<span class="x2010-rr-initials">{data.railroadInitials}</span>
										{/if}
									</td>
									<td class="x2010-title-cell">
										<strong>Train Consist Form x 2010</strong>
									</td>
									<td class="x2010-serial-cell">
										<div class="x2010-page-info">PAGE {pi + 1} OF {pages.length}</div>
										<div class="x2010-serial">{data.serialNumber}</div>
									</td>
								</tr>
							</tbody>
						</table>

						<table class="x2010-hdr">
							<tbody>
								<tr>
									<td style="width:10%">Train No.<br /><strong>{data.jobInfo.name}</strong></td>
									<td style="width:12%">Date<br />&nbsp;</td>
									<td style="width:12%">Dept Time<br />&nbsp;</td>
									<td style="width:18%">Origin<br />&nbsp;</td>
									<td style="width:18%">Destination<br />&nbsp;</td>
									<td style="width:15%">Driver Name<br />&nbsp;</td>
									<td style="width:15%">Depot<br />&nbsp;</td>
								</tr>
							</tbody>
						</table>

						<table class="x2010-hdr">
							<tbody>
								<tr>
									<td style="width:30%">Train Radio Number</td>
									<td style="width:20%">Unit No.</td>
									<td style="width:20%">P.M. Date Due</td>
									<td style="width:30%">Brake Certificate No.</td>
								</tr>
							</tbody>
						</table>

						<!-- Data table -->
						<table class="x2010-data">
							<colgroup>
								<col style="width:5%" />
								<col style="width:7%" />
								<col style="width:10%" />
								<col style="width:3%" />
								<col style="width:3%" />
								<col style="width:7%" />
								<col style="width:6%" />
								<col style="width:6%" />
								<col style="width:19%" />
								<col style="width:19%" />
								<col style="width:15%" />
							</colgroup>
							<thead>
								<tr>
									<th>Sl.<br />No</th>
									<th>Wagon<br />Class</th>
									<th>Wagon or Locomotive<br />Number</th>
									<th>CL</th>
									<th>Sta</th>
									<th>DG</th>
									<th>Gross<br />Mass</th>
									<th>Length<br />Metres</th>
									<th>Current Location</th>
									<th>Destination</th>
									<th>Contents<br /><span style="font-weight:normal;font-size:0.8em;">&#9873; Routing</span></th>
								</tr>
							</thead>
							<tbody>
								{#each page as car, ri (car.id)}
									{@const rowNum = pageOffsets[pi] + ri + 1}
									{@const dest = x2010Dest(car)}
									{@const rowId = `x2010-${data.selectedJob}-${car.id}`}
									<tr class={ri % 2 === 1 ? 'x2010-alt' : ''}>
										<td style="text-align:center;">
											<input
												type="checkbox"
												class="strike-cb noprint"
												data-row-id={rowId}
												onchange={toggleStrike}
											/>{rowNum}
										</td>
										<td style="text-align:center;">{car.car_code.slice(0, 4)}</td>
										<td style="text-align:center;font-weight:bold;">{wagonNum(car.reporting_marks)}</td>
										<td style="text-align:center;">{checkLetter(car.reporting_marks)}</td>
										<td style="text-align:center;">{car.status === 'Loaded' ? 'L' : 'E'}</td>
										<td style="text-align:center;"></td>
										<td style="text-align:center;">{(car.car_remarks ?? '').split('|')[0]?.trim() ?? ''}</td>
										<td style="text-align:center;">{(car.car_remarks ?? '').split('|')[1]?.trim() ?? ''}</td>
										<td style="text-align:center;">
											{#if car.current_location}
												<strong>{car.current_station}</strong><br />{car.current_location}
											{:else}
												In Train
											{/if}
										</td>
										<td style="text-align:center;">
											<strong>{dest.station}</strong><br />{dest.location}
										</td>
										<td style="text-align:center;">
											{car.status === 'Loaded' ? (car.is_reposition ? '' : (car.consignment ?? '')) : ''}
											{#each x2010Flags(car) as flag (flag)}<br /><span class="x2010-flag"
													>&#9873; {flag}</span
												>{/each}
										</td>
									</tr>
								{/each}
							</tbody>
						</table>
					</div>
				{/each}
			{/if}
		{/if}
	{/if}
</div>

<style>
	.fmt-radio {
		display: flex;
		align-items: center;
		gap: 0.25rem;
		font-size: 0.9rem;
		cursor: pointer;
	}

	/* ── X2010 screen styles ── */
	.x2010-page {
		font-family: 'Arial Narrow', Arial, sans-serif;
		font-size: 13px;
		max-width: 1200px;
		margin-bottom: 2rem;
		border: 1px solid #888;
	}

	.x2010-hdr {
		width: 100%;
		border-collapse: collapse;
		border-top: none;
	}
	.x2010-hdr td {
		border: 1px solid #000;
		padding: 4px 6px;
		font-size: 12px;
		min-height: 2.4em;
		vertical-align: bottom;
	}

	.x2010-logo-cell {
		width: 30%;
		vertical-align: middle !important;
	}
	.x2010-logo-img {
		max-height: 52px;
		max-width: 100%;
		width: auto;
		display: block;
	}
	.x2010-rr-name {
		display: block;
		font-weight: bold;
		font-size: 1rem;
	}
	.x2010-rr-initials {
		font-size: 0.85rem;
		color: #555;
	}
	.x2010-title-cell {
		width: 45%;
		text-align: center;
		vertical-align: middle !important;
		font-size: 1rem;
	}
	.x2010-serial-cell {
		width: 25%;
		vertical-align: bottom;
		position: relative;
	}
	.x2010-page-info {
		font-size: 0.8rem;
	}
	.x2010-serial {
		color: #c00;
		font-size: 1.3rem;
		font-weight: bold;
		text-align: right;
	}

	.x2010-data {
		width: 100%;
		border-collapse: collapse;
		font-size: 12px;
	}
	.x2010-data th {
		background-color: #1e4d78;
		color: #fff;
		border: 1px solid #000;
		padding: 4px 5px;
		font-size: 11px;
		text-align: center;
		vertical-align: middle;
	}
	.x2010-data td {
		border: 1px solid #999;
		padding: 3px 5px;
		vertical-align: middle;
	}
	.x2010-data tr.x2010-alt td {
		background-color: #d0e8ff;
	}
	.x2010-flag {
		color: #b30000;
		font-size: 0.85em;
	}

	.strike-cb {
		margin-right: 4px;
	}
	:global(tr.struck td) {
		text-decoration: line-through;
		opacity: 0.45;
	}

	/* ── Print overrides for X2010 ── */
	@media print {
		@page { size: A5 landscape; margin: 6mm; }

		.x2010-page { max-width: 100%; border: none; margin-bottom: 0; }
		.page-break { page-break-before: always; }

		.x2010-logo-img { max-height: 32px; }
		.x2010-hdr td { font-size: 7pt; padding: 1px 3px; }
		.x2010-title-cell { font-size: 9pt; }
		.x2010-serial { font-size: 11pt; }
		.x2010-rr-name { font-size: 9pt; }

		.x2010-data th, .x2010-data td { font-size: 7pt; padding: 1px 3px; }
		.x2010-data th {
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
			background-color: #1e4d78 !important;
			color: #fff !important;
		}
		.x2010-data tr.x2010-alt td {
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
			background-color: #d0e8ff !important;
		}
	}
</style>
