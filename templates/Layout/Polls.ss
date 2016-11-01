<div class="content">
	<% if PollControllers %>
		<% loop PollControllers %>
			$PollDetail
		<% end_loop %>
	<% else %>
		$PollDetail
	<% end_if %>
</div>