<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Configure_laws}</div>
	{list}
</div>

<div class="card xnova-panel border-0 shadow-sm mb-3">
	<form action="/game/alliance?mode=admin&edit=rights&add=name" method="POST">
		<div class="card-header fw-semibold">{Range_make}</div>
		<div class="card-body">
			<label class="form-label" for="newrangname">{Range_name}</label>
			<input class="form-control" type="text" id="newrangname" name="newrangname" maxlength="30">
		</div>
		<div class="card-footer text-end">
			<button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> {Make}</button>
		</div>
	</form>
</div>

<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Law_leyends}</div>
	<ul class="list-group list-group-flush xnova-ally-rights-legend">
		<li class="list-group-item"><img src="/images/r1.png" alt=""> {Alliance_dissolve}</li>
		<li class="list-group-item"><img src="/images/r2.png" alt=""> {Expel_users}</li>
		<li class="list-group-item"><img src="/images/r3.png" alt=""> {See_the_requests}</li>
		<li class="list-group-item"><img src="/images/r4.png" alt=""> {See_the_list_members}</li>
		<li class="list-group-item"><img src="/images/r5.png" alt=""> {Check_the_requests}</li>
		<li class="list-group-item"><img src="/images/r6.png" alt=""> {Alliance_admin}</li>
		<li class="list-group-item"><img src="/images/r7.png" alt=""> {See_the_online_list_member}</li>
		<li class="list-group-item"><img src="/images/r8.png" alt=""> {Make_a_circular_message}</li>
		<li class="list-group-item"><img src="/images/r9.png" alt=""> {Left_hand_text}</li>
	</ul>
	<div class="card-footer text-center">
		<a href="/game/alliance?mode=admin&edit=ally"><i class="bi bi-arrow-left" aria-hidden="true"></i> {Return_to_overview}</a>
	</div>
</div>
	