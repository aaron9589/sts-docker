<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();
	let confirmRestart = $state(false);
	let confirmReset = $state(false);
	let confirmWipe = $state(false);
</script>

<svelte:head><title>STS - DB Maintenance</title></svelte:head>

<Navbar title="DB Maintenance" variant="grey" back={{ href: '/', label: 'Home' }} />

<div class="page">
	{#if form && 'message' in form}
		<div class="alert alert-success">{form.message}</div>
	{/if}

	<!-- Row 1: Database tools -->
	<div class="maint-grid">

		<div class="maint-phase">
			<div class="phase-header"><i class="bi bi-tools"></i> Database</div>
			<div class="phase-cards">
				<a class="maint-card" href="/maint/validate">
					<i class="bi bi-check2-circle maint-icon"></i>
					<div class="maint-body">
						<h3>Validate DB</h3>
						<p>Check for ghost records, orphaned orders and inconsistencies</p>
					</div>
				</a>
				<a class="maint-card" href="/maint/backup">
					<i class="bi bi-download maint-icon"></i>
					<div class="maint-body">
						<h3>Backup DB</h3>
						<p>Download a copy of the SQLite database file</p>
					</div>
				</a>
				<a class="maint-card" href="/maint/restore">
					<i class="bi bi-upload maint-icon"></i>
					<div class="maint-body">
						<h3>Restore / Import</h3>
						<p>Import a legacy STS backup (.sql) or restore a SQLite backup</p>
					</div>
				</a>
			</div>
		</div>

		<div class="maint-phase">
			<div class="phase-header"><i class="bi bi-exclamation-triangle"></i> Simulation</div>
			<div class="phase-cards">
				<div class="maint-action-card">
					<div class="maint-body">
						<h3>Restart</h3>
						<p>Resets shippers, cancels waybills, empties cars, repositions cars with home locations, sets session to 0. Load counts preserved.</p>
					</div>
					<form method="POST" action="?/restart" use:enhance class="maint-action-form">
						<label class="maint-confirm-label">
							<input type="checkbox" bind:checked={confirmRestart} /> Confirm restart
						</label>
						<button class="btn btn-warning btn-sm" disabled={!confirmRestart}>RESTART</button>
					</form>
				</div>
				<div class="maint-action-card">
					<div class="maint-body">
						<h3>Reset</h3>
						<p>As Restart, plus all car load counts are zeroed.</p>
					</div>
					<form method="POST" action="?/reset" use:enhance class="maint-action-form">
						<label class="maint-confirm-label">
							<input type="checkbox" bind:checked={confirmReset} /> Confirm reset
						</label>
						<button class="btn btn-warning btn-sm" disabled={!confirmReset}>RESET</button>
					</form>
				</div>
				<div class="maint-action-card maint-action-card--danger">
					<div class="maint-body">
						<h3>Wipe Database</h3>
						<p>Removes ALL data — cars, locations, shipments, jobs, history — and resets settings to defaults.</p>
					</div>
					<form method="POST" action="?/wipe" use:enhance class="maint-action-form">
						<label class="maint-confirm-label">
							<input type="checkbox" bind:checked={confirmWipe} /> Confirm wipe
						</label>
						<button class="btn btn-danger btn-sm" disabled={!confirmWipe}>WIPE</button>
					</form>
				</div>
			</div>
		</div>

		<div class="maint-phase">
			<div class="phase-header"><i class="bi bi-sliders"></i> Settings</div>
			<div class="phase-cards phase-cards--padded">
				{#if form && 'saved' in form}
					<div class="alert alert-success" style="margin-bottom:0.75rem;">Settings saved.</div>
				{/if}
				<form method="POST" action="?/settings" use:enhance>
					{#each data.settings as s (s.setting_name)}
						<div class="form-row">
							<label for={s.setting_name} style="min-width:200px;">{s.setting_desc}</label>
							<input type="text" id={s.setting_name} name={s.setting_name} value={s.setting_value} />
						</div>
					{/each}
					<button class="btn btn-primary btn-sm" style="margin-top:0.5rem;">Save Settings</button>
				</form>
			</div>
		</div>

		<div class="maint-phase">
			<div class="phase-header"><i class="bi bi-image"></i> Railroad Logo</div>
			<div class="phase-cards phase-cards--padded">
				<p class="muted small" style="margin-bottom:0.75rem;">
					Shown in the X2010 Train Consist Form header. Match patterns use glob syntax:
					<code>?</code> = any one character, <code>*</code> = any substring
					(e.g. <code>??5*</code> matches train number with <code>5</code> at position 3).
					Most specific pattern wins. PNG or JPG, max 512 KB.
				</p>

				{#if form && 'logoSaved' in form}
					<div class="alert alert-success" style="margin-bottom:0.75rem;">Logo saved.</div>
				{/if}
				{#if form && 'logoCleared' in form}
					<div class="alert alert-success" style="margin-bottom:0.75rem;">Logo removed.</div>
				{/if}
				{#if form && 'logoError' in form}
					<div class="alert alert-danger" style="margin-bottom:0.75rem;">{(form as unknown as Record<string, string>).logoError}</div>
				{/if}

				<div class="logo-section-label">Default (fallback)</div>
				<div class="logo-row">
					{#if data.hasLogo}
						<div class="logo-row-preview">
							<img src="/logo?key=logo_data&v={data.defaultLogoVersion}" alt="Default logo" class="logo-preview" />
						</div>
					{:else}
						<span class="muted small" style="align-self:center;">None set.</span>
					{/if}
					<div class="logo-row-actions">
						<form method="POST" action="?/upload_logo" use:enhance enctype="multipart/form-data" class="logo-upload-form">
							<input type="file" name="logo" accept="image/*" style="font-size:0.85rem;" />
							<button class="btn btn-primary btn-sm">{data.hasLogo ? 'Replace' : 'Upload'}</button>
						</form>
						{#if data.hasLogo}
							<form method="POST" action="?/clear_logo" use:enhance>
								<button class="btn btn-sm btn-outline-danger">Remove</button>
							</form>
						{/if}
					</div>
				</div>

				{#if data.patternLogos.length > 0}
					<div class="logo-section-label" style="margin-top:1rem;">Pattern rules</div>
					{#each data.patternLogos as pl (pl.index)}
						<div class="logo-row">
							<div class="logo-row-meta">
								<code class="logo-pattern-code">{pl.pattern}</code>
								{#if pl.name && pl.name !== pl.pattern}
									<span class="muted small">{pl.name}</span>
								{/if}
							</div>
							{#if pl.hasImage}
								<div class="logo-row-preview">
									<img src="/logo?key=logo_data_{pl.index}&v={pl.version}" alt="Logo for {pl.pattern}" class="logo-preview" />
								</div>
							{:else}
								<span class="muted small" style="align-self:center;">No image.</span>
							{/if}
							<div class="logo-row-actions">
								<form method="POST" action="?/update_pattern_image" use:enhance enctype="multipart/form-data" class="logo-upload-form">
									<input type="hidden" name="index" value={pl.index} />
									<input type="file" name="logo" accept="image/*" style="font-size:0.85rem;" />
									<button class="btn btn-primary btn-sm">{pl.hasImage ? 'Replace' : 'Upload'}</button>
								</form>
								<form method="POST" action="?/remove_pattern" use:enhance>
									<input type="hidden" name="index" value={pl.index} />
									<button class="btn btn-sm btn-outline-danger">Remove</button>
								</form>
							</div>
						</div>
					{/each}
				{/if}

				<div class="logo-section-label" style="margin-top:1rem;">Add pattern rule</div>
				<form method="POST" action="?/add_pattern" use:enhance enctype="multipart/form-data" class="logo-add-form">
					<input type="text" name="pattern" placeholder="Pattern (e.g. ??5*)" style="width:9rem;font-size:0.85rem;" required />
					<input type="text" name="name" placeholder="Label (optional)" style="width:10rem;font-size:0.85rem;" />
					<input type="file" name="logo" accept="image/*" style="font-size:0.85rem;" required />
					<button class="btn btn-primary btn-sm">Add</button>
				</form>
			</div>
		</div>

	</div>
</div>

<style>
	.maint-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 1.25rem;
		align-items: start;
	}

	@media (max-width: 700px) {
		.maint-grid { grid-template-columns: 1fr; }
	}

	.maint-phase { display: flex; flex-direction: column; }

	.phase-header {
		background: #6c757d;
		color: #fff;
		font-size: 0.85rem;
		font-weight: 700;
		padding: 0.55rem 0.9rem;
		border-radius: 0.4rem 0.4rem 0 0;
		display: flex;
		align-items: center;
		gap: 0.4rem;
	}

	.phase-cards {
		background: #fff;
		border: 1px solid #d0d5db;
		border-top: none;
		border-radius: 0 0 0.4rem 0.4rem;
		overflow: hidden;
	}

	.phase-cards--padded {
		padding: 0.85rem 1rem;
	}

	/* Linked nav cards (Database section) */
	.maint-card {
		display: flex;
		align-items: flex-start;
		gap: 0.85rem;
		padding: 0.85rem 1rem;
		text-decoration: none;
		color: var(--text);
		border-bottom: 1px solid #eee;
		transition: background 0.12s;
	}
	.maint-card:last-child { border-bottom: none; }
	.maint-card:hover { background: var(--grey-bg); }

	.maint-icon {
		font-size: 1.25rem;
		color: #6c757d;
		flex-shrink: 0;
		margin-top: 2px;
	}

	.maint-body { flex: 1; min-width: 0; }
	.maint-body h3 { font-size: 0.95rem; margin: 0 0 0.15rem; font-weight: 600; }
	.maint-body p { color: var(--text-2); font-size: 0.8rem; margin: 0; }

	/* Action cards (Simulation section) */
	.maint-action-card {
		padding: 0.85rem 1rem;
		border-bottom: 1px solid #eee;
	}
	.maint-action-card:last-child { border-bottom: none; }
	.maint-action-card--danger { background: #fff8f8; }
	.maint-action-card--danger h3 { color: var(--danger); }

	.maint-action-form {
		display: flex;
		align-items: center;
		gap: 0.75rem;
		margin-top: 0.6rem;
		flex-wrap: wrap;
	}

	.maint-confirm-label {
		font-size: 0.85rem;
		display: flex;
		align-items: center;
		gap: 0.35rem;
		cursor: pointer;
	}

	/* Logo section */
	.logo-section-label {
		font-size: 0.75rem;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		color: var(--text-2);
		margin-bottom: 0.4rem;
	}

	.logo-row {
		display: flex;
		align-items: flex-start;
		gap: 0.75rem;
		padding: 0.5rem 0;
		border-bottom: 1px solid var(--border);
		flex-wrap: wrap;
	}
	.logo-row:last-of-type { border-bottom: none; }

	.logo-row-meta {
		display: flex;
		flex-direction: column;
		gap: 0.15rem;
		min-width: 90px;
		padding-top: 0.2rem;
	}

	.logo-pattern-code {
		background: #e9ecef;
		border-radius: 3px;
		padding: 0.15rem 0.4rem;
		font-size: 0.82rem;
	}

	.logo-row-preview { flex: 0 0 auto; }

	.logo-row-actions {
		display: flex;
		flex-direction: column;
		gap: 0.3rem;
		flex: 1;
		min-width: 160px;
	}

	.logo-upload-form {
		display: flex;
		gap: 0.35rem;
		align-items: center;
		flex-wrap: wrap;
	}

	.logo-add-form {
		display: flex;
		gap: 0.4rem;
		align-items: center;
		flex-wrap: wrap;
		padding: 0.5rem 0;
	}

	.logo-preview {
		max-height: 56px;
		max-width: 180px;
		border: 1px solid var(--border);
		border-radius: 4px;
		padding: 3px;
		background: #fff;
		display: block;
	}
</style>
