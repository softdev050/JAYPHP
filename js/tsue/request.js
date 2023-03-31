$('#requestTorrent').click(function(e)
{
	e.preventDefault();

	function _saveMemberRequest(requestTitle, requestDescription, requestCategory)
	{
		$.ajax
		({
			url:TSUESettings['website_url']+'/ajax/request.php',
			data: 'action=request&do=saveNew&title='+$.TSUE.urlEncode(requestTitle)+'&description='+$.TSUE.urlEncode(requestDescription)+'&category='+requestCategory+'&securitytoken='+TSUESettings['stKey'],
			success: function(serverResponse)
			{
				$.TSUE.removeinputLoader('#title');
				if(serverResponse && !$.TSUE.findresponsecode(serverResponse))
				{
					$.TSUE.closedialog();
					$('#show_error').remove();
					$(serverResponse).prependTo('#newRequestHolder').fadeOut('fast').fadeIn('slow');
				}
				else
				{
					$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#requestTorrentForm');
				}
			}
		});
	};

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/request.php',
		data: 'action=request&do=new&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			
			$('#requestTorrentForm').submit(function(e)
			{
				e.preventDefault();

				$('#serverResponse').remove();
				$.TSUE.inputLoader('#title');

				var $title = $('#title', this).val(), $description = $('#description', this).val(), $category = parseInt($('#category', this).val());
				if($title && $description && $category)
				{
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/request.php',
						data: 'action=request&do=search&title='+$.TSUE.urlEncode($title)+'&description='+$.TSUE.urlEncode($description)+'&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							$.TSUE.removeinputLoader('#title');
							if(serverResponse)
							{
								$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#requestTorrentForm');
								$('#requestConfirmPostSave').click(function(e)
								{
									e.preventDefault();
									$('#serverResponse').remove();
									$.TSUE.inputLoader('#title');
									return _saveMemberRequest($title, $description, $category);
								});
							}
							else
							{
								return _saveMemberRequest($title, $description, $category);
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

$(document).on('click', '#showRequest', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel'));
	if($rid)
	{
		$('#showRequest_'+$rid).toggle();
	}
	return false;
});

$(document).on('click', '.voteButton', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/voteButton_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/request.php',
		data: 'action=request&do=vote&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			if($.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.dialog(serverResponse);
			}
			else
			{
				serverResponse = serverResponse.split('|');
				$('[rel="voteCount_'+$rid+'"]').html(parseInt(serverResponse[1]));
				$('[rel="voteButton_'+$rid+'"]').html(serverResponse[0]).attr('disabled', true);
			}
		}
	});

	return false;
});

$(document).on('click', '#fillRequest', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/fillRequest_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/request.php',
		data: 'action=request&do=fill&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#fillRequestForm').submit(function(e)
			{
				e.preventDefault();

				var $tid = parseInt($('#tid', this).val());
				if($tid)
				{
					$.TSUE.inputLoader('#tid');
					$('#serverResponse').remove();
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/request.php',
						data: 'action=request&do=saveFill&rid='+$rid+'&tid='+$tid+'&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							$.TSUE.removeinputLoader('#tid');
							if($.TSUE.findresponsecode(serverResponse) == 'D')
							{
								$($.TSUE.clearresponse(serverResponse)).insertBefore('#fillRequestForm');
								$('#fillRequestForm').remove();
								$('[rel="fillRequest_'+$rid+'"]').remove();
							}
							else
							{
								$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#fillRequestForm');
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

$(document).on('click', '#resetRequest', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/resetRequest_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/request.php',
		data: 'action=request&do=reset&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			if($.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.dialog(serverResponse);
			}
			else
			{
				$('[rel="resetRequest_'+$rid+'"]').remove();
				$.TSUE.alert(serverResponse);
			}
		}
	});

	return false;
});

$(document).on('click', '#deleteRequest', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/deleteRequest_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/request.php',
		data: 'action=request&do=delete&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#deleteRequestForm').submit(function(e)
			{
				e.preventDefault();
				
				var $this=$(this), $reason=$('textarea[name="reason"]', $this).val();
				if(!$reason)
				{
					$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
					return false;
				}

				buildQuery = 'action=request&do=delete&reason='+$.TSUE.urlEncode($reason)+'&rid='+$rid+'&securitytoken='+TSUESettings['stKey'];
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/request.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$('[rel="request_'+$rid+'"]').fadeOut('slow');
						}

						$.TSUE.closedialog();
						$.TSUE.alert($.TSUE.strip_tags(serverResponse));
					}
				});
				
				return false;
			});
		}
	});

	return false;
});

$(document).on('click', '#editRequest', function(e)
{
	e.preventDefault();

	var $rid = parseInt($(this).attr('rel').replace(/editRequest_/, ''));

	if(!$rid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/request.php',
		data: 'action=request&do=edit&rid='+$rid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#editRequestForm').submit(function(e)
			{
				e.preventDefault();

				$('#serverResponse').remove();
				$.TSUE.inputLoader('#title');

				var $this = $(this), $title = $('input[name="title"]', $this).val(), $description = $('textarea[name="description"]', $this).val(), $category = parseInt($('#category', this).val());
				
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/request.php',
					data: 'action=request&do=saveEdit&rid='+$rid+'&title='+$.TSUE.urlEncode($title)+'&category='+$category+'&description='+$.TSUE.urlEncode($description)+'&securitytoken='+TSUESettings['stKey'],
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
							$('#req_title_'+$rid).fadeOut('slow', function()
							{
								$(this).html(serverResponse['title']).fadeIn('slow');
							});
							$('#showRequest_'+$rid).fadeOut('slow', function()
							{
								$(this).html(serverResponse['description']).fadeIn('slow');
							});
							$('#requestCategory_'+$rid).find('img').fadeOut('slow', function()
							{
								$(this).attr('title', serverResponse['cname']).attr('src', TSUESettings['website_url']+'/data/torrents/category_images/'+serverResponse['cid']+'.png').fadeIn('slow');
							});
						}
					}
				});

				return false;
			});
		}
	});

	return false;
});

$('#hide_filled_requests').click(function(e)
{
	e.preventDefault();

	$(this).remove();

	$('div[id="request"]').each(function()
	{
		if($(this).html().match(/class="filled"/))
		{
			$(this).remove();
		}
	});

	$('.tipsy').remove();

	return false;
});