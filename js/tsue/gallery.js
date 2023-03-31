$('#igUpload').click(function(e)
{
	e.preventDefault();

	buildQuery = 'action=upload&securitytoken='+TSUESettings['stKey'];

	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/gallery.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('.overlay_text ul.tabs').tabs('div.panelItems > div');
			$('#ig_upload_form').submit(function(e)
			{
				$('#igUploadForm').fadeOut('fast', function(){$('#igUploading').fadeIn('fast');});
			});
		}
	});

	return false;
});

$('.igDelete').click(function(e)
{
	e.preventDefault();

	var $attachment_id = parseInt($(this).attr('rel'));
	if(!$attachment_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			buildQuery = 'action=delete&attachment_id='+$attachment_id+'&securitytoken='+TSUESettings['stKey'];

			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/gallery.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if($.TSUE.findresponsecode(serverResponse))
					{
						$.TSUE.dialog(serverResponse);
					}
					else
					{
						$.TSUE.jumpInternal('?p=gallery&pid=400');
					}
				}
			});
		}
	});

	return false;
});

$('.igImageDetails textarea').mouseup(function(e)
{
        e.preventDefault();
});

$('.igImageDetails textarea').on('focus', function()
{
	$(this).select();
});