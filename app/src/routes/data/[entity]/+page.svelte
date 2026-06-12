<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();

	let showAdd = $state(false);
	let saveErrors: Record<string, string> = $state({});
	let savePending: Record<string, boolean> = $state({});

	// ── popup editor ──────────────────────────────────────────────────
	type PopupEdit = {
		rowId: unknown;
		fieldName: string;
		fieldType: string;
		value: string;
		anchorRect: DOMRect;
	};
	let popup = $state<PopupEdit | null>(null);

	function cellKey(rowId: unknown, fieldName: string) {
		return String(rowId) + ':' + fieldName;
	}

	function openPopup(rowId: unknown, col: typeof data.columns[0], anchorEl: HTMLElement) {
		const field = fieldForKey(col.key);
		if (!field) return;
		const row = data.rows.find((r) => r.id === rowId);
		if (!row) return;
		popup = {
			rowId,
			fieldName: field.name,
			fieldType: field.type,
			value: String(row[field.name] ?? ''),
			anchorRect: anchorEl.getBoundingClientRect()
		};
	}

	function closePopup() { popup = null; }

	function focusEl(el: HTMLElement) {
		el.focus();
		if (el instanceof HTMLInputElement) el.select();
	}

	async function commitPopup() {
		if (!popup) return;
		const { rowId, fieldName, value } = popup;
		const row = data.rows.find((r) => r.id === rowId);
		closePopup();
		if (!row || String(row[fieldName] ?? '') === value) return;
		await saveField(rowId, fieldName, value === '' ? null : value);
	}

	function onPopupKeydown(e: KeyboardEvent) {
		if (e.key === 'Escape') { closePopup(); return; }
		if (e.key === 'Enter' && !(e.target instanceof HTMLTextAreaElement)) commitPopup();
	}

	// ── pool popup ────────────────────────────────────────────────────
	let poolPopup = $state<{ rowId: unknown; selected: Set<string>; anchorRect: DOMRect } | null>(null);

	function openPoolPopup(rowId: unknown, anchorEl: HTMLElement) {
		const row = data.rows.find((r) => r.id === rowId);
		if (!row) return;
		const codes = String(row['pool_shipments'] ?? '').split(', ').filter(Boolean);
		const opts = data.options['pool_shipments'] ?? [];
		const selected = new Set(
			opts.filter((o) => codes.includes(String(o.label))).map((o) => String(o.value))
		);
		poolPopup = { rowId, selected, anchorRect: anchorEl.getBoundingClientRect() };
	}

	function closePoolPopup() { poolPopup = null; }

	async function savePool(rowId: unknown, selected: Set<string>) {
		const key = String(rowId) + ':pool_shipments';
		savePending = { ...savePending, [key]: true };
		const body = new FormData();
		body.append('car_id', String(rowId));
		for (const sid of selected) body.append('shipment_ids', sid);
		const res = await fetch('?/updatePool', { method: 'POST', body });
		const text = await res.text();
		savePending = { ...savePending, [key]: false };
		if (text.includes('"updated"')) {
			const row = data.rows.find((r) => r.id === rowId);
			if (row) {
				const opts = data.options['pool_shipments'] ?? [];
				row['pool_shipments'] = opts
					.filter((o) => selected.has(String(o.value)))
					.map((o) => o.label)
					.join(', ');
				row['in_pool'] = selected.size > 0 ? 1 : 0;
			}
		} else {
			const match = text.match(/"error"\s*:\s*"([^"]+)"/);
			saveErrors = { ...saveErrors, [key]: match ? match[1] : 'Save failed' };
		}
	}

	// ── sort & filter ─────────────────────────────────────────────────
	let sortKey = $state<string | null>(null);
	let sortDir = $state<1 | -1>(1);
	let inputFilters: Record<string, string> = $state({});
	let appliedFilters: Record<string, string> = $state({});

	let debounceTimer: ReturnType<typeof setTimeout> | null = null;
	function onFilterInput(key: string, value: string) {
		inputFilters[key] = value;
		if (debounceTimer) clearTimeout(debounceTimer);
		debounceTimer = setTimeout(() => { appliedFilters = { ...inputFilters }; }, 200);
	}

	function toggleSort(key: string) {
		if (sortKey === key) { sortDir = sortDir === 1 ? -1 : 1; }
		else { sortKey = key; sortDir = 1; }
	}

	function sortKeyForItem(item: typeof visibleColumns[0]): string {
		if (item.type === 'group') return item.group.displayKeys?.[0] ?? item.group.columns[0];
		return item.col.displayKey ?? item.col.key;
	}

	const filteredRows = $derived((() => {
		let rows = data.rows as Record<string, unknown>[];
		for (const [key, val] of Object.entries(appliedFilters)) {
			if (!val) continue;
			const q = val.toLowerCase();
			rows = rows.filter(r => String(r[key] ?? '').toLowerCase().includes(q));
		}
		if (sortKey) {
			const k = sortKey, d = sortDir;
			rows = [...rows].sort((a, b) => {
				const av = String(a[k] ?? ''), bv = String(b[k] ?? '');
				const an = Number(av), bn = Number(bv);
				const cmp = (!isNaN(an) && !isNaN(bn)) ? an - bn
					: av.localeCompare(bv, undefined, { sensitivity: 'base' });
				return cmp * d;
			});
		}
		return rows;
	})());

	// ── helpers ───────────────────────────────────────────────────────
	// reset add form after successful create
	$effect(() => {
		if (form && !('error' in form && form.error)) showAdd = false;
	});

	function fieldForKey(key: string) {
		return data.fields.find((f) => f.name === key) ?? null;
	}

	function groupForKey(key: string) {
		return data.columnGroups.find((g) => g.columns.includes(key)) ?? null;
	}

	const visibleColumns = $derived((() => {
		const seen = new Set<string>();
		const out: ({ type: 'col'; col: typeof data.columns[0] } | { type: 'group'; group: typeof data.columnGroups[0] })[] = [];
		for (const col of data.columns) {
			const g = groupForKey(col.key);
			if (g) {
				if (!seen.has(g.key)) { seen.add(g.key); out.push({ type: 'group', group: g }); }
			} else {
				out.push({ type: 'col', col });
			}
		}
		return out;
	})());

	async function saveField(rowId: unknown, fieldName: string, value: unknown) {
		const key = String(rowId) + ':' + fieldName;
		savePending = { ...savePending, [key]: true };
		saveErrors = { ...saveErrors, [key]: '' };
		const row = data.rows.find((r) => r.id === rowId);
		if (!row) return;
		const body = new FormData();
		body.append('id', String(rowId));
		for (const f of data.fields) {
			if (f.name === fieldName) {
				body.append(f.name, value === null || value === undefined ? '' : String(value));
			} else {
				const v = row[f.name];
				body.append(f.name, v === null || v === undefined ? '' : String(v));
			}
		}
		const res = await fetch('?/update', { method: 'POST', body });
		const text = await res.text();
		savePending = { ...savePending, [key]: false };
		if (text.includes('"updated"')) {
			// patch the row locally so the display reflects the new value immediately
			row[fieldName] = value;
			// if this field has a display-key (label column), update that too
			const col = data.columns.find((c) => c.key === fieldName);
			if (col?.displayKey) {
				const opts = data.options[fieldName] ?? [];
				const opt = opts.find((o) => String(o.value) === String(value));
				row[col.displayKey] = opt ? opt.label : value;
			}
			// update group displayKeys (e.g. loading_location_label)
			const grp = data.columnGroups.find((g) => g.columns.includes(fieldName));
			if (grp?.displayKeys) {
				const idx = grp.columns.indexOf(fieldName);
				if (idx !== -1) {
					const opts = data.options[fieldName] ?? [];
					const opt = opts.find((o) => String(o.value) === String(value));
					row[grp.displayKeys[idx]] = opt ? opt.label : value;
				}
			}
		} else {
			const match = text.match(/"error"\s*:\s*"([^"]+)"/);
			saveErrors = { ...saveErrors, [key]: match ? match[1] : 'Save failed' };
		}
	}

	// group popover inline saves (number/text fields inside the schedule/route popover)
	function onGroupBlur(rowId: unknown, fieldName: string, currentVal: unknown, e: Event) {
		const val = (e.target as HTMLInputElement).value;
		if (String(currentVal ?? '') !== val) saveField(rowId, fieldName, val);
	}
</script>

<svelte:head><title>STS - {data.label}</title></svelte:head>

<Navbar title={data.label} variant="grey" back={{ href: '/data', label: 'Database' }} />

<div class="page">
	{#if form && 'error' in form && form.error}
		<div class="alert alert-danger">{form.error}</div>
	{/if}

	{#if data.entity === 'jobs'}
		<div class="alert alert-info">Jobs are managed on the <a href="/data/jobs">jobs page</a>.</div>
	{/if}

	{#if !data.readonly && data.fields.length > 0}
		<p>
			<button type="button" class="btn btn-primary" onclick={() => { showAdd = !showAdd; }}>
				{showAdd ? 'Cancel' : `Add ${data.labelSingular}`}
			</button>
		</p>

		{#if showAdd}
			<div class="card">
				<h3>New {data.labelSingular}</h3>
				<form method="POST" action="?/create" use:enhance>
					{#each data.fields as field (field.name)}
						<div class="form-row">
							<label for={field.name} style="min-width: 240px;">{field.label}{field.required ? ' *' : ''}</label>
							{#if field.type === 'select'}
								<select id={field.name} name={field.name}>
									{#if !field.required}<option value=""></option>{/if}
									{#each data.options[field.name] ?? [] as opt}
										<option value={opt.value}>{opt.label}</option>
									{/each}
								</select>
							{:else if field.type === 'textarea'}
								<textarea id={field.name} name={field.name} cols={60}></textarea>
							{:else if field.type === 'number'}
								<input type="number" id={field.name} name={field.name} />
							{:else}
								<input type="text" id={field.name} name={field.name} />
							{/if}
						</div>
					{/each}
					<button class="btn btn-success">Add</button>
				</form>
			</div>
		{/if}
	{/if}

	<div class="table-wrap">
		<table class="ops">
			<thead>
				<tr>
					{#each visibleColumns as item (item.type === 'col' ? item.col.key : item.group.key)}
						{@const sk = sortKeyForItem(item)}
						{@const label = item.type === 'group' ? item.group.label : item.col.label}
						{@const hint = item.type === 'col' ? item.col.hint : undefined}
						{@const w = item.type === 'col' ? item.col.width : undefined}
						<th
							class="sortable"
							class:sorted={sortKey === sk}
							title={hint}
							style={w ? `width: ${w}; max-width: ${w};` : undefined}
							onclick={() => toggleSort(sk)}
						>
							{label}
							{#if sortKey === sk}
								<span class="sort-arrow">{sortDir === 1 ? '↑' : '↓'}</span>
							{:else}
								<span class="sort-arrow muted">⇅</span>
							{/if}
						</th>
					{/each}
					<th></th>
				</tr>
				<tr class="filter-row">
					{#each visibleColumns as item (item.type === 'col' ? item.col.key : item.group.key)}
						{@const fk = sortKeyForItem(item)}
						<th>
							<input
								type="search"
								placeholder="Filter…"
								value={inputFilters[fk] ?? ''}
								oninput={(e) => onFilterInput(fk, (e.target as HTMLInputElement).value)}
							/>
						</th>
					{/each}
					<th></th>
				</tr>
			</thead>
			<tbody>
				{#each filteredRows as row (row.id)}
					<tr class:row-pooled={data.rowHighlightKey && row[data.rowHighlightKey]}>
						{#each visibleColumns as item (item.type === 'col' ? item.col.key : item.group.key)}
							{#if item.type === 'group'}
								{@const grpCols = data.columns.filter(c => item.group.columns.includes(c.key))}
								{@const anyPending = grpCols.some(c => savePending[String(row.id) + ':' + c.key])}
								{@const displayKeys = item.group.displayKeys}
								<td class="group-cell" class:saving={anyPending} class:route-cell={item.group.key === 'route'}>
									<details>
										<summary>
											{#if displayKeys}
												<span class="route-summary">
													{#each grpCols as c, i}
														<span class="route-line" title={c.label}>
															<span class="route-arrow">{i === 0 ? '↑' : '↓'}</span>{row[displayKeys[i]] ?? '–'}
														</span>
													{/each}
												</span>
											{:else}
												{#each grpCols as c}
													<span class="grp-val" title={c.label}>{row[c.key] ?? '–'}</span>
												{/each}
											{/if}
										</summary>
										<div class="grp-popover">
											{#each grpCols as c}
												{@const field = fieldForKey(c.key)}
												{@const errKey = String(row.id) + ':' + c.key}
												{@const inputId = `grp-${row.id}-${c.key}`}
												<div class="grp-row">
													<label for={inputId} title={c.hint}>{c.label}</label>
													{#if field && field.type === 'number'}
														<input
															id={inputId}
															type="number"
															value={row[field.name] ?? ''}
															onblur={(e) => onGroupBlur(row.id, field.name, row[field.name], e)}
															class:error={!!saveErrors[errKey]}
														/>
													{:else if field && field.type === 'text'}
														<input
															id={inputId}
															type="text"
															value={String(row[field.name] ?? '')}
															onblur={(e) => onGroupBlur(row.id, field.name, row[field.name], e)}
															class:error={!!saveErrors[errKey]}
														/>
													{:else if field && field.type === 'select'}
														<select
															id={inputId}
															value={String(row[field.name] ?? '')}
															onchange={(e) => saveField(row.id, field.name, (e.target as HTMLSelectElement).value || null)}
															class:error={!!saveErrors[errKey]}
														>
															{#if !field.required}<option value=""></option>{/if}
															{#each data.options[field.name] ?? [] as opt}
																<option value={String(opt.value)}>{opt.label}</option>
															{/each}
														</select>
													{:else}
														<span>{row[c.key] ?? ''}</span>
													{/if}
													{#if saveErrors[errKey]}
														<span class="field-error">{saveErrors[errKey]}</span>
													{/if}
												</div>
											{/each}
										</div>
									</details>
								</td>
							{:else}
								{@const col = item.col}
								{@const field = fieldForKey(col.key)}
								{@const errKey = String(row.id) + ':' + col.key}
								{@const pending = savePending[errKey]}
								{@const displayVal = col.displayKey ? (row[col.displayKey] ?? '') : (row[col.key] ?? '')}
								{@const isPoolCol = col.badge === 'pool' && !data.readonly && (data.options['pool_shipments']?.length ?? 0) > 0}
								{@const isEditable = !data.readonly && !!field}
								<td
									class:saving={pending}
									class:cell-editable={isEditable || isPoolCol}
									class:cell-error={!!saveErrors[errKey]}
									onclick={isPoolCol ? (e) => openPoolPopup(row.id, e.currentTarget as HTMLElement) : isEditable ? (e) => openPopup(row.id, col, e.currentTarget as HTMLElement) : undefined}
									title={isPoolCol ? 'Click to edit pool' : isEditable ? 'Click to edit' : undefined}
								>
									{#if col.badge === 'status' && row[col.key]}
										<span class="status-badge status-{String(row[col.key]).toLowerCase()}">{row[col.key]}</span>
									{:else if col.badge === 'pool'}
										{#if row[col.key]}
											<span class="pool-list">
												{#each String(row[col.key]).split(', ') as s}
													<span class="pool-badge">{s}</span>
												{/each}
											</span>
										{:else if isPoolCol}
											<span class="cell-text muted">–</span>
										{/if}
									{:else}
										<span class="cell-text">{displayVal}</span>
									{/if}
									{#if saveErrors[errKey]}
										<div class="field-error">{saveErrors[errKey]}</div>
									{/if}
								</td>
							{/if}
						{/each}
						<td style="white-space: nowrap;">
							<form
								method="POST"
								action="?/delete"
								use:enhance={({ cancel }) => {
									if (!confirm('Delete this row?')) cancel();
								}}
								style="display:inline;"
							>
								<input type="hidden" name="id" value={row.id} />
								<button class="btn btn-sm btn-danger">Delete</button>
							</form>
						</td>
					</tr>
				{/each}
			</tbody>
		</table>
	</div>
	<p class="muted small">
		{#if filteredRows.length !== data.rows.length}
			{filteredRows.length} of {data.rows.length} rows
		{:else}
			{data.rows.length} rows
		{/if}
	</p>
</div>

{#if poolPopup}
	<!-- svelte-ignore a11y_no_static_element_interactions -->
	<div class="popup-backdrop" onclick={closePoolPopup} onkeydown={(e) => e.key === 'Escape' && closePoolPopup()}></div>
	<div
		class="popup-editor pool-popup"
		style="top:{poolPopup.anchorRect.bottom + window.scrollY + 4}px;left:{Math.min(poolPopup.anchorRect.left + window.scrollX, window.innerWidth - 240)}px"
		onkeydown={(e) => e.key === 'Escape' && closePoolPopup()}
	>
		<div class="popup-label">Pool Shipments</div>
		<div class="pool-check-list">
			{#each data.options['pool_shipments'] ?? [] as opt}
				{@const checked = poolPopup.selected.has(String(opt.value))}
				<label class="pool-check-row">
					<input
						type="checkbox"
						{checked}
						onchange={() => {
							const sid = String(opt.value);
							const next = new Set(poolPopup!.selected);
							if (next.has(sid)) next.delete(sid); else next.add(sid);
							poolPopup!.selected = next;
						}}
					/>
					{opt.label}
				</label>
			{/each}
		</div>
		<div class="popup-actions">
			<button class="btn btn-sm btn-primary" onclick={() => { const pp = poolPopup!; closePoolPopup(); savePool(pp.rowId, pp.selected); }}>Save</button>
			<button class="btn btn-sm btn-secondary" onclick={closePoolPopup}>Cancel</button>
		</div>
	</div>
{/if}

{#if popup}
	{@const _field = fieldForKey(popup.fieldName)}
	{@const _col = data.columns.find(c => fieldForKey(c.key)?.name === popup!.fieldName)}
	<!-- svelte-ignore a11y_no_static_element_interactions -->
	<div class="popup-backdrop" onclick={closePopup} onkeydown={(e) => e.key === 'Escape' && closePopup()}></div>
	{#if _field}
		<div
			class="popup-editor"
			style="top:{popup.anchorRect.bottom + window.scrollY + 4}px;left:{Math.min(popup.anchorRect.left + window.scrollX, window.innerWidth - 260)}px"
			onkeydown={onPopupKeydown}
		>
			<div class="popup-label">{_field.label}</div>
			{#if _field.type === 'select'}
				<select
					use:focusEl
					value={popup.value}
					onchange={(e) => { popup!.value = (e.target as HTMLSelectElement).value; commitPopup(); }}
					class={_col?.badge === 'status' && popup.value ? `status-select status-${popup.value.toLowerCase()}` : undefined}
				>
					{#if !_field.required}<option value=""></option>{/if}
					{#each data.options[_field.name] ?? [] as opt}
						<option value={String(opt.value)}>{opt.label}</option>
					{/each}
				</select>
			{:else if _field.type === 'textarea'}
				<textarea use:focusEl rows={4} onblur={commitPopup}>{popup.value}</textarea>
			{:else if _field.type === 'number'}
				<input type="number" use:focusEl value={popup.value}
					oninput={(e) => { popup!.value = (e.target as HTMLInputElement).value; }}
					onblur={commitPopup} />
			{:else}
				<input type="text" use:focusEl value={popup.value}
					oninput={(e) => { popup!.value = (e.target as HTMLInputElement).value; }}
					onblur={commitPopup} />
			{/if}
			<div class="popup-hint">Enter to save · Esc to cancel</div>
		</div>
	{/if}
{/if}

<style>
	/* ── sort & filter header ── */
	thead th.sortable {
		cursor: pointer;
		user-select: none;
		white-space: nowrap;
	}
	thead th.sortable:hover { background: #e8e8e8; }
	thead th.sorted { background: #dce8f8; }
	thead th[title] {
		text-decoration: underline dotted #aaa;
		text-underline-offset: 3px;
	}
	.sort-arrow {
		font-size: 0.7rem;
		margin-left: 3px;
		opacity: 0.5;
	}
	thead th.sorted .sort-arrow { opacity: 1; color: #2563eb; }

	tr.filter-row th {
		padding: 3px 4px;
		background: #f0f0f0;
		position: sticky;
		top: 32px; /* sits just below the label row */
		z-index: 9;
	}
	tr.filter-row input[type="search"] {
		width: 100%;
		min-height: unset;
		height: 26px;
		padding: 2px 6px;
		font-size: 0.78rem;
		border: 1px solid var(--border);
		border-radius: 3px;
		background: #fff;
		font-family: inherit;
		box-sizing: border-box;
	}
	tr.filter-row input[type="search"]:focus {
		outline: none;
		border-color: #80bdff;
	}

	/* ── grouped column cell ── */
	td.group-cell {
		white-space: nowrap;
		padding: 0;
	}

	td.group-cell details {
		position: relative;
	}

	td.group-cell summary {
		list-style: none;
		cursor: pointer;
		padding: 4px 8px;
		display: flex;
		gap: 6px;
		align-items: center;
		user-select: none;
	}
	td.group-cell summary::-webkit-details-marker { display: none; }

	td.group-cell summary::after {
		content: '✎';
		font-size: 0.7rem;
		color: #aaa;
		margin-left: 2px;
		flex-shrink: 0;
	}
	td.group-cell details[open] summary::after {
		content: '▲';
	}
	td.group-cell summary:hover { background: var(--grey-bg); }

	.grp-val {
		font-size: 0.8rem;
		color: var(--text-2);
	}
	.grp-val::after {
		content: '/';
		margin-left: 4px;
		color: #ccc;
	}
	.grp-val:last-child::after { content: ''; }

	/* route group: two-line compact display */
	td.route-cell summary {
		display: block;
		padding: 4px 22px 4px 8px; /* room for the ✎ arrow */
	}
	td.route-cell summary::after {
		position: absolute;
		right: 6px;
		top: 50%;
		transform: translateY(-50%);
	}
	.route-summary {
		display: flex;
		flex-direction: column;
		gap: 1px;
	}
	.route-line {
		display: flex;
		align-items: baseline;
		gap: 3px;
		font-size: 0.78rem;
		color: var(--text);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 18ch;
	}
	.route-arrow {
		font-size: 0.7rem;
		color: #888;
		flex-shrink: 0;
	}

	.grp-popover {
		position: absolute;
		z-index: 100;
		background: #fff;
		border: 1px solid var(--border);
		border-radius: 6px;
		box-shadow: 0 4px 16px rgba(0,0,0,0.15);
		padding: 10px 14px;
		min-width: 360px;
		right: 0;
	}

	.grp-row {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 6px;
	}
	.grp-row:last-child { margin-bottom: 0; }

	.grp-row label {
		min-width: 100px;
		font-size: 0.8rem;
		color: var(--text-2);
		flex-shrink: 0;
	}

	.grp-row input[type="number"],
	.grp-row input[type="text"] {
		width: 5rem;
		min-height: unset;
		font-size: 0.85rem;
		padding: 3px 6px;
		border: 1px solid var(--border);
		border-radius: 3px;
		text-align: right;
		appearance: textfield;
		-moz-appearance: textfield;
	}
	.grp-row select {
		min-height: unset;
		font-size: 0.85rem;
		padding: 3px 6px;
		border: 1px solid var(--border);
		border-radius: 3px;
		max-width: 220px;
	}
	.grp-row input::-webkit-outer-spin-button,
	.grp-row input::-webkit-inner-spin-button {
		-webkit-appearance: none;
	}
	.grp-row input:focus {
		outline: none;
		border-color: #80bdff;
	}

	/* ── saving state ── */
	td.saving { opacity: 0.55; pointer-events: none; }

	/* ── pool row highlight & badge ── */
	:global(tr.row-pooled td) {
		background: #f0e6ff;
	}
	:global(table.ops tbody tr.row-pooled:hover td) {
		background: #e6d9f7;
	}
	.pool-list {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}
	.pool-badge {
		display: inline-block;
		padding: 2px 6px;
		border-radius: 3px;
		font-size: 0.78rem;
		font-weight: 600;
		background: #9b59b6;
		color: #fff;
		white-space: nowrap;
	}

	/* ── editable cell ── */
	td.cell-editable {
		cursor: pointer;
	}
	td.cell-editable:hover { background: #f0f4ff !important; }
	td.cell-editable:hover .cell-text { outline: 1px dashed #aaa; border-radius: 2px; }
	td.cell-error .cell-text { outline: 1px solid var(--danger); border-radius: 2px; }

	.cell-text {
		display: block;
		word-break: break-word;
	}

	.field-error {
		font-size: 0.75rem;
		color: var(--danger, #c62828);
		margin-top: 2px;
	}

	/* ── popup editor ── */
	:global(.popup-backdrop) {
		position: fixed;
		inset: 0;
		z-index: 199;
	}
	:global(.popup-editor) {
		position: absolute;
		z-index: 200;
		background: #fff;
		border: 1px solid var(--border);
		border-radius: 6px;
		box-shadow: 0 4px 20px rgba(0,0,0,0.18);
		padding: 10px 12px;
		min-width: 220px;
		max-width: 340px;
	}
	:global(.popup-label) {
		font-size: 0.75rem;
		font-weight: 600;
		color: var(--text-2);
		margin-bottom: 6px;
		text-transform: uppercase;
		letter-spacing: 0.03em;
	}
	:global(.popup-hint) {
		font-size: 0.7rem;
		color: var(--text-3);
		margin-top: 6px;
	}
	:global(.popup-editor input[type="text"]),
	:global(.popup-editor input[type="number"]),
	:global(.popup-editor select),
	:global(.popup-editor textarea) {
		width: 100%;
		min-height: unset;
		font-size: 0.9rem;
		font-family: inherit;
		padding: 5px 8px;
		border: 1.5px solid var(--border);
		border-radius: 4px;
		background: #fff;
		box-sizing: border-box;
	}
	:global(.popup-editor input:focus),
	:global(.popup-editor select:focus),
	:global(.popup-editor textarea:focus) {
		outline: none;
		border-color: #80bdff;
		box-shadow: 0 0 0 2px rgba(0,123,255,0.15);
	}
	:global(.popup-editor select.status-select) { font-weight: 600; }
	:global(.popup-editor select.status-empty)       { background: #ffeaa7; color: #333; }
	:global(.popup-editor select.status-loaded)      { background: #a8e6cf; color: #333; }
	:global(.popup-editor select.status-loading)     { background: #74b9ff; color: #fff; }
	:global(.popup-editor select.status-unloading)   { background: #fab1a0; color: #fff; }
	:global(.popup-editor select.status-ordered)     { background: #dfe6e9; color: #333; }
	:global(.popup-editor select.status-unavailable) { background: #d63031; color: #fff; }

	:global(.pool-popup) { min-width: 200px; }
	:global(.pool-check-list) {
		display: flex;
		flex-direction: column;
		gap: 4px;
		max-height: 260px;
		overflow-y: auto;
		margin-bottom: 10px;
	}
	:global(.pool-check-row) {
		display: flex;
		align-items: center;
		gap: 7px;
		font-size: 0.85rem;
		cursor: pointer;
		padding: 2px 0;
	}
	:global(.pool-check-row input[type="checkbox"]) {
		width: auto;
		min-height: unset;
	}
	:global(.popup-actions) {
		display: flex;
		gap: 6px;
	}
</style>
