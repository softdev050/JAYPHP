//Market
//purchase
$(document).on('click', '[rel="market_purchase"]', function(e)
{
	e.preventDefault();

	var $itemid = parseInt($(this).attr('name'));
	if(!$itemid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=market&do=purchase&itemid='+$itemid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/market.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					serverResponse = $.TSUE.clearresponse(serverResponse);
					serverResponse = serverResponse.split('|');
					
					$('#market_item_x_has_been_purchased_'+$itemid).html('<div class="done">'+serverResponse[0]+'</div>');
					
					if(serverResponse[1])
					{
						$('#market_you_have_x_points').html(serverResponse[1]);
						//Refresh Stats..
						$.TSUE.refreshMemberStats();
					}
				}
				else
				{
					$.TSUE.dialog(serverResponse);
				}
			}
		}
	});
});

//custom_title
$(document).on('submit', 'form[name="custom_title"]', function(e)
{
	e.preventDefault();

	var $itemid = parseInt($('input[name="itemid"]', this).val());
	var $custom_title = $('input[name="custom_title"]', this).val();

	if(!$itemid || !$custom_title)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=market&do=purchase&itemid='+$itemid+'&custom_title='+$.TSUE.urlEncode($custom_title)+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/market.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					serverResponse = $.TSUE.clearresponse(serverResponse);
					serverResponse = serverResponse.split('|');
					
					$('#market_item_x_has_been_purchased_'+$itemid).html('<div class="done">'+serverResponse[0]+'</div>');
					
					if(serverResponse[1])
					{
						$('#market_you_have_x_points').html(serverResponse[1]);

						//Refresh Stats..
						$.TSUE.refreshMemberStats();
					}
				}
				else
				{
					$.TSUE.dialog(serverResponse);
				}
			}
		}
	});

	return false;
});

//gift
$(document).on('submit', 'form[name="gift"]', function(e)
{
	e.preventDefault();

	var $itemid = parseInt($('input[name="itemid"]', this).val());
	var $membername = $('input[name="membername"]', this).val();

	if(!$itemid || !$membername)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=market&do=purchase&itemid='+$itemid+'&membername='+$.TSUE.urlEncode($membername)+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/market.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					serverResponse = $.TSUE.clearresponse(serverResponse);
					serverResponse = serverResponse.split('|');
					
					$('#market_item_x_has_been_purchased_'+$itemid).html('<div class="done">'+serverResponse[0]+'</div>');
					
					if(serverResponse[1])
					{
						$('#market_you_have_x_points').html(serverResponse[1]);
						
						//Refresh Stats..
						$.TSUE.refreshMemberStats();
					}
				}
				else
				{
					$.TSUE.dialog(serverResponse);
				}
			}
		}
	});

	return false;
});

//hitrun
$(document).on('submit', 'form[name="hitrun"]', function(e)
{
	e.preventDefault();

	var $itemid = parseInt($('input[name="itemid"]', this).val());
	var $tid = $('select[name="tid"]', this).val();

	if(!$itemid || !$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=market&do=purchase&itemid='+$itemid+'&tid='+$.TSUE.urlEncode($tid)+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/market.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					serverResponse = $.TSUE.clearresponse(serverResponse);
					serverResponse = serverResponse.split('|');
					
					$('#market_item_x_has_been_purchased_'+$itemid).html('<div class="done">'+serverResponse[0]+'</div>');
					
					if(serverResponse[1])
					{
						$('#market_you_have_x_points').html(serverResponse[1]);
						
						//Refresh Stats..
						$.TSUE.refreshMemberStats();
					}
				}
				else
				{
					$.TSUE.dialog(serverResponse);
				}
			}
		}
	});

	return false;
});

//change membername
$(document).on('submit', 'form[name="change_membername"]', function(e)
{
	e.preventDefault();

	var $itemid = parseInt($('input[name="itemid"]', this).val());
	var $new_membername = $('input[name="new_membername"]', this).val();

	if(!$itemid || !$new_membername)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=market&do=purchase&itemid='+$itemid+'&new_membername='+$.TSUE.urlEncode($new_membername)+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/market.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					serverResponse = $.TSUE.clearresponse(serverResponse);
					serverResponse = serverResponse.split('|');
					
					$('#market_item_x_has_been_purchased_'+$itemid).html('<div class="done">'+serverResponse[0]+'</div>');
					
					if(serverResponse[1])
					{
						$('#market_you_have_x_points').html(serverResponse[1]);

						//Refresh Stats..
						$.TSUE.refreshMemberStats();
					}
				}
				else
				{
					$.TSUE.dialog(serverResponse);
				}
			}
		}
	});

	return false;
});