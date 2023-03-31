$('#addRelease').click(function(e)
{
	e.preventDefault();

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
		data: 'action=upcomingrelease&do=new&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			
			$('#addUpcomingReleaseForm').submit(function(e)
			{
				e.preventDefault();

				$('#serverResponse').remove();
				$.TSUE.inputLoader('#title');

				var $title = $('#title', this).val(), $description = $('#description', this).val();
				if($title && $description)
				{
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
						data: 'action=upcomingrelease&do=saveNew&title='+$.TSUE.urlEncode($title)+'&description='+$.TSUE.urlEncode($description)+'&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							$.TSUE.removeinputLoader('#title');
							if(serverResponse && !$.TSUE.findresponsecode(serverResponse))
							{
								$.TSUE.closedialog();
								$('#show_error').remove();
								$(serverResponse).prependTo('#newUCRHolder');
							}
							else
							{
								$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#addUpcomingReleaseForm');
							}
						}
					});
				}
				else
				{
					$.TSUE.removeinputLoader('#title');
					$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				}

				return false;
			});
		}
	});

	return false;
});

$(document).on('click', '#deleteRelease', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/deleteRelease_/, ''));

	if(!$rid)
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
				url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
				data: 'action=upcomingrelease&do=delete&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
				success: function(serverResponse)
				{
					if($.TSUE.findresponsecode(serverResponse))
					{
						$.TSUE.dialog(serverResponse);
					}
					else
					{
						$('[rel="release_'+$rid+'"]').fadeOut('slow');
						$.TSUE.alert(serverResponse);
					}
				}
			});
		}
	});

	return false;
});

$(document).on('click', '#editRelease', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/editRelease_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
		data: 'action=upcomingrelease&do=edit&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#editReleaseForm').submit(function(e)
			{
				e.preventDefault();

				$('#serverResponse').remove();
				$.TSUE.inputLoader('#title');

				var $this = $(this), $title = $('input[name="title"]', $this).val(), $description = $('textarea[name="description"]', $this).val();
				
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
					data: 'action=upcomingrelease&do=saveEdit&rid='+$rid+'&title='+$.TSUE.urlEncode($title)+'&description='+$.TSUE.urlEncode($description)+'&securitytoken='+TSUESettings['stKey'],
					dataType: 'json',
					success: function(serverResponse)
					{
						$('#serverResponse').remove();
						$.TSUE.removeinputLoader('#title');
						
						if(serverResponse['error'])
						{
							$('<div id="serverResponse" class="error">'+$.TSUE.clearresponse(serverResponse['error'])+'</div>').insertBefore($this);
						}
						else
						{
							$.TSUE.closedialog();
							$.TSUE.alert(TSUEPhrases['message_saved']);
							$('[rel="release_'+$rid+'"] h1').html(serverResponse['title']);
							$('[rel="release_'+$rid+'"] .releaseDescription').html(serverResponse['description']);
						}
					}
				});

				return false;
			});
		}
	});

	return false;
});

$(document).on('click', '#fillRelease', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/fillRelease_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
		data: 'action=upcomingrelease&do=fill&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#completeReleaseForm').submit(function(e)
			{
				e.preventDefault();

				var $tid = parseInt($('#tid', this).val());
				if($tid)
				{
					$.TSUE.inputLoader('#tid');
					$('#serverResponse').remove();
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/upcomingreleases.php',
						data: 'action=upcomingrelease&do=saveFill&rid='+$rid+'&tid='+$tid+'&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							$.TSUE.removeinputLoader('#tid');
							if($.TSUE.findresponsecode(serverResponse) == 'D')
							{
								$($.TSUE.clearresponse(serverResponse)).insertBefore('#completeReleaseForm');
								$('#completeReleaseForm').remove();
								$('[rel="fillRelease_'+$rid+'"]').remove();
							}
							else
							{
								$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#completeReleaseForm');
							}
						}
					});
				}
				else
				{
					$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				}

				return false;
			});
		}
	});

	return false;
});