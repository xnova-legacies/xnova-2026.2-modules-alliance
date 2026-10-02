<div class="card xnova-panel border-0 shadow-sm mb-3">
	<div class="card-header fw-semibold">{your_alliance}</div>
	<div class="card-body">
		{ally_image}
		<table class="table table-sm align-middle">
			<tbody>
				<tr>
					<td class="xnova-label" width="30%">{Tag}</td>
					<td>{ally_tag}</td>
				</tr>
				<tr>
					<td class="xnova-label">{Name}</td>
					<td>{ally_name}</td>
				</tr>
				<tr>
					<td class="xnova-label">{Members}</td>
					<td>{ally_members}{members_list}</td>
				</tr>
				<tr>
					<td class="xnova-label">{Range}</td>
					<td>{range}{alliance_admin}</td>
				</tr>
				{requests}
				{send_circular_mail}
				{ally_web}
			</tbody>
		</table>

		<div class="xnova-ally-text mb-4">{ally_description}</div>

		<h2 class="h6 text-body-secondary text-uppercase mb-2">{Inner_section}</h2>
		<div class="xnova-ally-text">{ally_text}</div>
	</div>
</div>

{ally_owner}
