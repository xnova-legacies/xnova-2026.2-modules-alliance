<tr class="table-active">
	<td colspan="9">
		<form action="/game/alliance?mode=admin&edit=members&rank={id}" method="POST" class="d-flex flex-wrap align-items-center justify-content-center gap-2">
			<span class="fw-semibold">{Rank_for}</span>
			<select class="form-select form-select-sm xnova-rank-select" name="newrang" aria-label="{Rank_for}">{options}</select>
			<button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {Save}</button>
		</form>
	</td>
</tr>
