$('ul.tabs').tabs('div.tabItems');

$('#download_now').click(function(e)
{
	e.preventDefault();

	var $did = parseInt($(this).attr('rel'));

	if(!$did)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.jumpInternal('?p=downloads&pid=300&action=download&did='+$did);

	return false;
});

$('#delete_file').click(function(e)
{
	e.preventDefault();

	var $did = parseInt($(this).attr('rel'));
	if(!$did)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			buildQuery = 'action=delete_file&did='+$did+'&securitytoken='+TSUESettings['stKey'];

			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/downloads.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if($.TSUE.findresponsecode(serverResponse))
					{
						$.TSUE.dialog(serverResponse);
					}
					else
					{
						$.TSUE.jumpInternal('?p=downloads&pid=300');
					}
				}
			});
		}
	});

	return false;
});

$('#edit_file').click(function(e)
{
	e.preventDefault();

	var $did = parseInt($(this).attr('rel'));

	if(!$did)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.jumpInternal('?p=downloads&pid=300&action=edit&did='+$did);

	return false;
});

$('#import').click(function(e)
{
	$.TSUE.dialog(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	TSUESettings['showLoaderWhileAjax'] = false;
	TSUESettings['closeOverlayWhileAjax'] = false;

	buildQuery = 'action=import&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/downloads.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			$('.overlay_text').html($.TSUE.clearresponse(serverResponse));
			TSUESettings['showLoaderWhileAjax'] = true;
			TSUESettings['closeOverlayWhileAjax'] = true;
			$('#importFileForm').submit(function(e)
			{
				e.preventDefault();
				
				var $importFile = $('#importFile option:selected').text();
				if($importFile)
				{
					$.TSUE.closedialog();
					$('#dFileSelector').html('<input type="hidden" name="importFile" value="'+$importFile+'" /> '+$importFile);
				}

				return false;
			});
		}
	});
});