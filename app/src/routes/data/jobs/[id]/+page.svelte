<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();
</script>

<svelte:head><title>STS - Edit Job {data.job.name}</title></svelte:head>

<Navbar title="Job: {data.job.name}" variant="grey" back={{ href: '/data/jobs', label: 'Jobs' }} />

<div class="page">
	{#if form && 'error' in form && form.error}
		<div class="alert alert-danger">{form.error}</div>
	{/if}
	{#if form && 'saved' in form}
		<div class="alert alert-success">Job saved.</div>
	{/if}

	<div class="card">
		<h3>Job Details</h3>
		<form method="POST" action="?/update" use:enhance>
			<div class="form-row">
				<label for="name" style="min-width: 120px;">Job Name *</label>
				<input type="text" id="name" name="name" value={data.job.name} required />
			</div>
			<div class="form-row">
				<label for="description" style="min-width: 120px;">Description</label>
				<textarea id="description" name="description" cols={70} rows={5}>{data.job.description ?? ''}</textarea>
			</div>
			<button class="btn btn-primary">Save Job</button>
		</form>
	</div>

	<div class="card">
		<h3>Job Steps</h3>
		<p class="muted small">
			To remove a step, set its sequence number to 0 and save. The <strong>+</strong> marker means
			the step has <a href="/data/pu-criteria">auto-assign pickup criteria</a>.
		</p>
		<form method="POST" action="?/steps">
			<input type="hidden" name="row_count" value={data.steps.length} />
			<div class="table-wrap" style="box-shadow:none;">
				<table class="ops">
					<thead>
						<tr>
							<th>Sequence</th>
							<th>Station</th>
							<th>Set Out</th>
							<th>Pick Up</th>
							<th>Remarks</th>
						</tr>
					</thead>
					<tbody>
						<tr style="background: var(--summary);">
							<td><input type="number" name="new_step" style="width: 5rem;" placeholder="New" /></td>
							<td>
								<select name="new_station">
									<option value=""></option>
									{#each data.stations as s (s.id)}
										<option value={s.id}>{s.name}</option>
									{/each}
								</select>
							</td>
							<td style="text-align:center;"><input type="checkbox" name="new_setout" checked /></td>
							<td style="text-align:center;"><input type="checkbox" name="new_pickup" checked /></td>
							<td><input type="text" name="new_remarks" size={40} /></td>
						</tr>
						{#each data.steps as step, i (step.step_number)}
							<tr>
								<td>
									<input type="number" name="step{i}" value={step.step_number} style="width: 5rem;" />
								</td>
								<td>
									<select name="station{i}">
										{#each data.stations as s (s.id)}
											<option value={s.id} selected={s.id === step.station_id}>{s.name}</option>
										{/each}
									</select>
									{#if step.has_criteria}<strong title="Has auto-assign criteria">+</strong>{/if}
								</td>
								<td style="text-align:center;"><input type="checkbox" name="setout{i}" checked={!!step.setout} /></td>
								<td style="text-align:center;"><input type="checkbox" name="pickup{i}" checked={!!step.pickup} /></td>
								<td><input type="text" name="remarks{i}" value={step.remarks ?? ''} size={40} /></td>
							</tr>
						{/each}
					</tbody>
				</table>
			</div>
			<button class="btn btn-success" style="margin-top: 0.75rem;">Save Steps</button>
		</form>
	</div>
</div>
