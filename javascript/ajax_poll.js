$(document).on('submit', '.Form_PollForm', function(e) {
  e.preventDefault();

  var form = $(this);
  var action = $('#'+form.attr('id')+'_action_doPoll');

  $.ajax(form.attr('action'), {
    type: form.attr('method'),
    data: form.serialize(),
    beforeSend: function() {
      action.attr('value', ss.i18n._t('Poll.PROCESSING', 'Processing...'));
      action.attr('disabled', true);
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