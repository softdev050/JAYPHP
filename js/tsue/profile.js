$('li.recent_activity').click(function(e)
{
	e.preventDefault();

	var $recentAcvivity = $('#recent_activity');
	var $memberid = $recentAcvivity.attr('rel');

	if($recentAcvivity.html())
	{
		return;
	}
	
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$recentAcvivity.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/profile.php',
		data: 'action=recent_activity&memberid='+parseInt($memberid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$recentAcvivity.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});

$('li.following').click(function(e)
{
	e.preventDefault();

	var $following = $('#following');
	var $memberid = $following.attr('rel');

	if($following.html())
	{
		return;
	}
	
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$following.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/profile.php',
		data: 'action=following&memberid='+parseInt($memberid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$following.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});

$('li.followers').click(function(e)
{
	e.preventDefault();

	var $followers = $('#followers');
	var $memberid = $followers.attr('rel');

	if($followers.html())
	{
		return;
	}
	
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$followers.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/profile.php',
		data: 'action=followers&memberid='+parseInt($memberid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$followers.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});