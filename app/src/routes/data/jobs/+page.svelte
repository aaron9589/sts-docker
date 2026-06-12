<script lang="ts">
	import { enhance } from '$app/forms';
	import Navbar from '$lib/components/Navbar.svelte';
	import type { PageProps } from './$types';

	let { data, form }: PageProps = $props();
</script>

<svelte:head><title>STS - Jobs</title></svelte:head>

<Navbar title="Jobs / Trains" variant="grey" back={{ href: '/data', label: 'Database' }} />

<div class="page">
	{#if form && 'error' in form && form.error}
		<div class="alert alert-danger">{form.error}</div>
	{/if}

	<div class="card">
		<h3>Add New Job/Train</h3>
		<form method="POST" action="?/create" use:enhance>
			<div class="form-row">
				<label for="name" style="min-width: 120px;">Job Name *</label>
				<input type="text" id="name" name="name" required />
			</div>
			<div class="form-row">
				<label for="description" style="min-width: 120px;">Description</label>
				<textarea id="description" name="description" cols={60}></textarea>
			</div>
			<button class="btn btn-success">Add Job</button>
		</form>
	</div>

	<div class="table-wrap">
		<table class="ops">
			<thead>
				<tr>
					<th>Job Name</th>
					<th>Description</th>
					<th>Steps</th>
					<th>Cars Assigned</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{#each data.jobs as job (job.id)}
					<tr>
						<td><a href="/data/jobs/{job.id}">{job.name}</a></td>
						<td style="white-space: pre-line;">{job.description}</td>
						<td style="text-align:center;">{job.steps}</td>
						<td style="text-align:center;">{job.cars}</td>
						<td>
							<form
								method="POST"
								action="?/delete"
								use:enhance={({ cancel }) => {
									if (!confirm(`Delete job ${job.name}, its steps and pickup criteria?`)) cancel();
								}}
								style="display:inline;"
							>
								<input type="hidden" name="id" value={job.id} />
								<button class="btn btn-sm btn-danger">Delete</button>
							</form>
						</td>
					</tr>
				{/each}
			</tbody>
		</table>
	</div>
</div>
