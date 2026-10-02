<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Members_list} <span class="text-body-secondary fw-normal">({Ammount}: {memberzahl})</span></div>
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th scope="col">{Number}</th>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=members&sort1=1&sort2={s}">{Name}</a></th>
					<th scope="col"><span class="visually-hidden">{Write_a_message}</span></th>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=members&sort1=2&sort2={s}">{Position}</a></th>
					<th scope="col" class="text-end"><a href="/game/alliance?mode=admin&edit=members&sort1=3&sort2={s}">{Points}</a></th>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=members&sort1=0&sort2={s}">{Coordinated}</a></th>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=members&sort1=4&sort2={s}">{Member_from}</a></th>
					<th scope="col"><a href="/game/alliance?mode=admin&edit=members&sort1=5&sort2={s}">Duree d inactivite</a></th>
					<th scope="col" class="text-center">Fonction</th>
				</tr>
			</thead>
			<tbody>
				{memberslist}
			</tbody>
		</table>
	</div>
	<div class="card-footer text-center">
		<a href="/game/alliance?mode=admin&edit=ally"><i class="bi bi-arrow-left" aria-hidden="true"></i> {Return_to_overview}</a>
	</div>
</div>
