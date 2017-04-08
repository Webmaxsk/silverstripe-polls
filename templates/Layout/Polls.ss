<div class="content">
	<% if PollControllers %>
		<% loop PollControllers %>
			$PollDetail
		<% end_loop %>
	<% else_if Poll %>
		$PollDetail
	<% else %>
		<p><%t Poll_Controller.NOPOLLS 'There are no polls' %></p>
	<% end_if %>
</div>