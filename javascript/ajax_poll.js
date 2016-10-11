$(document).on('submit', '.Form_PollForm', function(e) {
	var form = $(this);

	e.preventDefault();

	var doPoll = $('#Form_PollForm_action_doPoll');

	$.ajax(form.attr('action'), {
		type: "POST",
		data: form.serialize(),
		beforeSend: function() {
			doPoll.attr('value','Prebieha odosielanie...');
			doPoll.attr("disabled", true);
		},
		success: function(data) {
			try {
				var json = jQuery.parseJSON(data);

				form.parent('.poll_detail').replaceWith(json);
			}
			catch(err) {
				form.replaceWith(data);
			}
		}
	});
});