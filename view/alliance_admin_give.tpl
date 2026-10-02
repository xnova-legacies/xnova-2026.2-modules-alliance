<form method="post" action="/game/alliance?mode=admin&edit=give" class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">A qui voulez vous donner l alliance ?</div>
	<div class="card-body">
		<label class="form-label" for="give-user">Choisissez le joueur a qui vous souhaitez donner l alliance :</label>
		<select class="form-select" id="give-user" name="id">{ally_give_options}</select>
	</div>
	<div class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="bi bi-arrow-right-circle" aria-hidden="true"></i> Donner</button></div>
</form>
