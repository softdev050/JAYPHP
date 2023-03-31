//POLL
$(document).on('submit', '#poll_form', function(e)
{
	e.preventDefault();
	
	$.TSUE.insertLoaderAfter('#vote-button');
	buildQuery = 'action=vote&'+$(this).serialize()+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/poll.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$('#show_poll').fadeOut('slow', function()
				{
					$(this).remove();
					$(serverResponse).insertAfter('#poll_form');
				});
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});

	return false;
});

$(document).on('click', '#list_voters', function(e)
{
	e.preventDefault();

	var pid = parseInt($(this).attr('pid')), buildQuery = 'action=list_voters&pid='+pid+'&securitytoken='+TSUESettings['stKey'];
	if(!pid)
	{
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/poll.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});

	return false;
});

$(document).on('click', '#edit_poll', function(e)
{
	e.preventDefault();

	var $this = $(this), pid = parseInt($this.attr('pid')), threadid = parseInt($this.attr('threadid')), buildQuery = 'action=edit_poll&pid='+pid+'&threadid='+threadid+'&securitytoken='+TSUESettings['stKey'];
			
	if(!pid || !threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/poll.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#edit_poll_form').submit(function(e)
			{
				e.preventDefault();

				$('#serverResponse').remove();
				$.TSUE.insertLoaderAfter('input[type="reset"]');
				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

				var $poll_question = $.TSUE.urlEncode($('input[name="poll_question"]').val()),
				$closeDaysAfter = parseInt($('input[name="closeDaysAfter"]').val()) || 0,
				$multiple = parseInt($('input:checkbox[name="multiple"]:checked').val()) || 0,
				$pollOptions = [];

				$('input[name="pollOptions[]"]').each(function(i, e)
				{
					var $option = $(e).val().trim();
					if($option)
					{
						$pollOptions[i] = $.TSUE.urlEncode($option);
					}
				});

				buildQuery = 'action=edit_poll&do=save&pid='+pid+'&threadid='+threadid+'&poll_question='+$poll_question+'&pollOptions='+$pollOptions+'&closeDaysAfter='+$closeDaysAfter+'&multiple='+$multiple+'&securitytoken='+TSUESettings['stKey'];
				$.ajax(
				{
					url:TSUESettings['website_url']+'/ajax/poll.php',
					data: buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$.TSUE.closedialog();
							$.TSUE.alert(TSUEPhrases['message_saved']);
							window.location.reload();
						}
						else
						{
							$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').prependTo($.TSUE.findOverlayTextDiv());
						}
					}
				});
				TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;

				return false;
			});
		}
	});

	return false;
});

$(document).on('click', '#delete_poll', function(e)
{
	e.preventDefault();

	var $this = $(this), pid = parseInt($this.attr('pid')), threadid = parseInt($this.attr('threadid')), buildQuery = 'action=delete_poll&pid='+pid+'&threadid='+threadid+'&securitytoken='+TSUESettings['stKey'];
			
	if(!pid || !threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/poll.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.alert($.TSUE.strip_tags(serverResponse));
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$this.remove();
					}
				}
			});
		}
	});

	return false;
});