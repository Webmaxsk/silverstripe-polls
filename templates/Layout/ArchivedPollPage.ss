<div class="content">
	<% if Content %>
		<div class="typography">$Content</div>
	<% end_if %>
	<% if Widgets %>
		<% loop Widgets %>
			$WidgetHolder
		<% end_loop %>
	<% else %>
		<p><%t ArchivedPollPage.NOPOLLS 'There are no polls' %></p>
	<% end_if %>
</div>