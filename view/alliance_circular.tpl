<form action="?mode=circular&sendmail=1" method="post" class="card xnova-panel border-0 shadow-sm mb-3" data-ajax="/game/api/alliance/circular" data-ajax-reload="1">
	<div class="card-header fw-semibold">{Send_circular_mail}</div>
	<div class="card-body">
		<div class="mb-3">
			<label class="form-label" for="circular-destiny">{Destiny}</label>
			<select class="form-select xnova-circular-select" id="circular-destiny" name="r">
				{r_list}
			</select>
		</div>
		<label class="form-label" for="circular-text">
			{Text_mail} <span class="text-body-secondary">(<span id="cntChars">0</span> / 5000 {characters})</span>
		</label>
		<textarea class="form-control" id="circular-text" name="text" rows="10" onkeyup="javascript:cntchar(5000)"></textarea>
	</div>
	<div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
		<a href="/game/alliance"><i class="bi bi-arrow-left" aria-hidden="true"></i> {Back}</a>
		<div class="d-flex flex-wrap gap-2">
			<button class="btn btn-outline-secondary" type="reset">{Clear}</button>
			<button class="btn btn-primary" type="submit"><i class="bi bi-send" aria-hidden="true"></i> {Send}</button>
		</div>
	</div>
</form>
<script src="/scripts/cntchar.js"></script>
