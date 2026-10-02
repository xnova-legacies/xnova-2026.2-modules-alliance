<form action="" method="POST" class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{question}</div>
	<div class="card-body">
		<label class="form-label" for="ally-rename">{New_name}</label>
		<input class="form-control" type="text" id="ally-rename" name="{name}">
	</div>
	<div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
		<a href="/game/alliance?mode=admin&edit=ally"><i class="bi bi-arrow-left" aria-hidden="true"></i> {Return_to_overview}</a>
		<button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {Change}</button>
	</div>
</form>