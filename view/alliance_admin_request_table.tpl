{request}

<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Apply_ally_overview} [{ally_tag}]</div>
	<div class="card-body py-2">
		<span class="text-body-secondary">{There_is_hanging_request}</span>
	</div>
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=requests&show=0&sort=1">{Candidate}</a></th>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=requests&show=0&sort=0">{Date_of_the_request}</a></th>
				</tr>
			</thead>
			<tbody>
				{list}
			</tbody>
		</table>
	</div>
	<div class="card-footer text-center">
		<a href="/game/alliance"><i class="bi bi-arrow-left" aria-hidden="true"></i> {Back}</a>
	</div>
</div>
