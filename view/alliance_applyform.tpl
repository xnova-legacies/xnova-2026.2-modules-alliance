<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Write_to_alliance}</div>
	<form action="/game/alliance?mode=apply&allyid={allyid}" method="POST" data-ajax="/game/api/alliance/apply" data-ajax-reload="1">
		<div class="card-body">
			<label class="form-label" for="applytext">
				{Message} <span class="text-body-secondary">(<span id="cntChars">{chars_count}</span> / 6000 {characters})</span>
			</label>
			<textarea class="form-control" id="applytext" name="text" rows="10" onkeyup="javascript:cntchar(6000)">{text_apply}</textarea>
		</div>
		<div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
			<span class="text-body-secondary small">{Help}</span>
			<div class="d-flex flex-wrap gap-2">
				<button class="btn btn-outline-secondary" type="submit" name="further" value="{Reload}">{Reload}</button>
				<button class="btn btn-primary" type="submit" name="further" value="{Send}"><i class="bi bi-send" aria-hidden="true"></i> {Send}</button>
			</div>
		</div>
	</form>
</div>
<script src="/scripts/cntchar.js"></script>