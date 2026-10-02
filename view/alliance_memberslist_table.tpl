<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{Members_list} <span class="text-body-secondary fw-normal">({Ammount}: {i})</span></div>
	<div class="table-responsive">
		<table class="table table-sm table-hover align-middle mb-0">
			<thead>
				<tr>
					<th scope="col">{Number}</th>
					<th scope="col"><a href="?mode=memberslist&sort1=1&sort2={s}">{Name}</a></th>
					<th scope="col"><span class="visually-hidden">{Write_a_message}</span></th>
					<th scope="col"><a href="?mode=memberslist&sort1=2&sort2={s}">{Position}</a></th>
					<th scope="col" class="text-end"><a href="?mode=memberslist&sort1=3&sort2={s}">{Points}</a></th>
					<th scope="col"><a href="?mode=memberslist&sort1=0&sort2={s}">{Coordinated}</a></th>
					<th scope="col"><a href="?mode=memberslist&sort1=4&sort2={s}">{Member_from}</a></th>
					<th scope="col"><a href="?mode=memberslist&sort1=5&sort2={s}">{Online}</a></th>
				</tr>
			</thead>
			<tbody>
				{list}
			</tbody>
		</table>
	</div>
	<div class="card-footer text-center">
		<a href="/game/alliance"><i class="bi bi-arrow-left" aria-hidden="true"></i> {Return_to_overview}</a>
	</div>
</div>
