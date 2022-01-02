<div class="content">
	<% if Content %>
		<div class="typography">$Content</div>
	<% end_if %>
	<% if PollControllers %>
		<% loop PollControllers %>
			$PollDetail
		<% end_loop %>
	<% else %>
		<p><%t PollsPage.NOPOLLS 'There are no polls' %></p>
	<% end_if %>
</div>