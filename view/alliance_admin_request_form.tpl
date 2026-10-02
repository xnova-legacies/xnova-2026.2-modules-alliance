<form action="/game/alliance?mode=admin&edit=requests&show={id}&sort=0" method="POST" class="card xnova-panel border-0 shadow-sm mb-3" data-ajax="/game/api/alliance/request" data-ajax-reload="1">
	<div class="card-header fw-semibold">{Request_from}</div>
	<div class="card-body">
		<div class="xnova-ally-text mb-3">{ally_request_text}</div>

		<label class="form-label" for="req-text">
			{Motive_optional} <span class="text-body-secondary">(<span id="cntChars">0</span> / 500 {characters})</span>
		</label>
		<textarea class="form-control" id="req-text" name="text" rows="10" onkeyup="javascript:cntchar(500)"></textarea>

		<div class="form-text mb-3">{Request_responde}</div>

		<div class="d-flex flex-wrap gap-2">
			<button class="btn btn-success" type="submit" name="action" value="Accepter"><i class="bi bi-check-lg" aria-hidden="true"></i> Accepter</button>
			<button class="btn btn-outline-danger" type="submit" name="action" value="Refuser"><i class="bi bi-x-lg" aria-hidden="true"></i> Refuser</button>
		</div>
	</div>
</form>
<script src="/scripts/cntchar.js"></script>