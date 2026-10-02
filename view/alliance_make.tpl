<form action="?mode=make&yes=1" method="POST" class="card xnova-panel border-0 shadow-sm mb-3" data-ajax="/game/api/alliance/make" data-ajax-reload="1">
	<div class="card-header fw-semibold">{make_alliance}</div>
	<div class="card-body">
		<div class="mb-3">
			<label class="form-label" for="atag">{alliance_tag} <span class="text-body-secondary">(3-8 {characters})</span></label>
			<input class="form-control" type="text" id="atag" name="atag" size="8" maxlength="8" value="">
		</div>
		<div class="mb-3">
			<label class="form-label" for="aname">{allyance_name} <span class="text-body-secondary">(max. 35 {characters})</span></label>
			<input class="form-control" type="text" id="aname" name="aname" size="20" maxlength="30" value="">
		</div>
		<button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {Make}</button>
	</div>
</form>
