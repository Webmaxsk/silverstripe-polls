<div class="poll_detail">
	<% if PollForm %>
		$PollForm
	<% else %>
		<% with Poll %>
			<strong class="poll-title">$Title</strong>
			<ul>
				<% if CurrentUser.canViewVotingResults($ID) %>
					<% if EnableSelect %>
						<% with Results %>
							<% loop Results %>
								<li>
									<div class="option">$Option: $Percentage%</div>
									<div class="bar" style="width:<% if Percentage=0 %>1px<% else %>$Percentage%<% end_if %>">&nbsp;</div>
								</li>
							<% end_loop %>
							<li><%t Poll.NUMBEROFVOTES "Number of votes" %>: <strong>$Total</strong></li>
						<% end_with %>
					<% else %>
						$Results
					<% end_if %>
				<% else_if CurrentUser.getMySubmissions($ID) %>
					<% loop CurrentUser.getMySubmissions($ID) %>
						<li>$Option</li>
					<% end_loop %>
				<% else %>
					<li><%t Poll.NOANSWER "No answer" %></li>
				<% end_if %>
			</ul>
		<% end_with %>
	<% end_if %>
</div>