<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Alliance_admin}</div>
	<div class="list-group list-group-flush">
		<a class="list-group-item list-group-item-action" href="?mode=admin&edit=rights"><i class="bi bi-shield-check" aria-hidden="true"></i> {Law_settings}</a>
		<a class="list-group-item list-group-item-action" href="?mode=admin&edit=members"><i class="bi bi-people" aria-hidden="true"></i> {Members_administrate}</a>
		<a class="list-group-item list-group-item-action" href="?mode=admin&edit=tag"><i class="bi bi-tag" aria-hidden="true"></i> {Change_the_ally_tag}</a>
		<a class="list-group-item list-group-item-action" href="?mode=admin&edit=name"><i class="bi bi-pencil" aria-hidden="true"></i> {Change_the_ally_name}</a>
	</div>
</div>

<form action="" method="POST" class="card xnova-panel border-0 shadow-sm mb-3">
	<input type="hidden" name="t" value="{t}">
	<div class="card-header fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
		<span>{Texts}</span>
		<span class="d-flex flex-wrap gap-2">
			<a class="btn btn-sm btn-outline-secondary" href="?mode=admin&edit=ally&t=1">{External_text}</a>
			<a class="btn btn-sm btn-outline-secondary" href="?mode=admin&edit=ally&t=2">{Internal_text}</a>
		</span>
	</div>
	<div class="card-body">
		<label class="form-label" for="ally-admin-text">
			{Show_of_request_text} <span class="text-body-secondary">(<span id="cntChars">0</span> / 5000 {characters})</span>
		</label>
		<textarea class="form-control" id="ally-admin-text" name="text" rows="15" onkeyup="javascript:cntchar(5000)">{text}</textarea>
		<div class="form-text">{request_type}</div>
	</div>
	<div class="card-footer d-flex flex-wrap justify-content-end gap-2">
		<button class="btn btn-outline-secondary" type="reset">{Reset}</button>
		<button class="btn btn-primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {Save}</button>
	</div>
</form>

<form action="" method="POST" class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Options}</div>
	<div class="card-body">
		<div class="mb-3">
			<label class="form-label" for="ally-web">{Main_Page}</label>
			<input class="form-control" type="text" id="ally-web" name="web" value="{ally_web}">
		</div>
		<div class="mb-3">
			<label class="form-label" for="ally-image">{Alliance_logo}</label>
			<input class="form-control" type="text" id="ally-image" name="image" value="{ally_image}">
		</div>
		<div class="mb-3">
			<label class="form-label" for="ally-request-notallow">{Requests}</label>
			<select class="form-select" id="ally-request-notallow" name="request_notallow">
				<option value="1"{ally_request_notallow_0}>{No_allow_request}</option>
				<option value="0"{ally_request_notallow_1}>{Allow_request}</option>
			</select>
		</div>
		<div class="mb-3">
			<label class="form-label" for="ally-owner-range">{Founder_name}</label>
			<input class="form-control" type="text" id="ally-owner-range" name="owner_range" value="{ally_owner_range}">
		</div>
	</div>
	<div class="card-footer text-end">
		<button class="btn btn-primary" type="submit" name="options" value="{Save}"><i class="bi bi-check-lg" aria-hidden="true"></i> {Save}</button>
	</div>
</form>

{Disolve_alliance}
{Transfer_alliance}
<script src="/scripts/cntchar.js"></script>

