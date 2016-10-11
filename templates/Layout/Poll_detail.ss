<div class="poll_detail">
	<% if PollForm %>
		$PollForm
	<% else %>
		<% with Poll %>
			<strong class="poll-title">$Title</strong>
			<ul>
				<% if AllowResults %>
					<% with Results %>
						<% loop Results %>
							<li>
								<div class="option">$Option: $Percentage%</div>
								<div class="bar" style="width:<% if Percentage=0 %>1px<% else %>$Percentage%<% end_if %>">&nbsp;</div>
							</li>
						<% end_loop %>
						<li>Počet hlasujúcich: <strong>$Total</strong></li>
					<% end_with %>
				<% else %>
					<li>$MySubmission</li>
				<% end_if %>
			</ul>
		<% end_with %>
	<% end_if %>
</div>