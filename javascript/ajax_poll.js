$(document).on('submit', '.Form_PollForm', function(e) {
	var form = $(this);

	e.preventDefault();

	var doPoll = $("#"+form.attr('id')+"_action_doPoll");

	$.ajax(form.attr('action'), {
		type: "POST",
		data: form.serialize(),
		beforeSend: function() {
			doPoll.attr('value',ss.i18n._t('Poll.PROCESSING', 'Processing...'));
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