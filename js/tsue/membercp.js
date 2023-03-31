//Member CP
$('#scanPort').submit(function(e)
{
	e.preventDefault();

	var $this = $(this), $inputField = $('input[name="port"]', $this), $port = parseInt($inputField.val());
	
	if($port)
	{
		$.TSUE.inputLoader($inputField);

		$('#portStatus').remove();

		buildQuery = 'action=membercp&do=open_port_check&port='+$port+'&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			url:TSUESettings['website_url']+'/ajax/membercp.php',
			data: buildQuery,
			success: function(serverResponse)
			{
				$.TSUE.removeinputLoader($inputField);
				$('<div id="portStatus"><br />'+$.TSUE.clearresponse(serverResponse)+'</div>').insertAfter($this);
			}
		});
	}
	else
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
	}

	return false;
});

$('#membercp_avatar_form').submit(function(e)
{
	$.TSUE.insertLoaderAfter('input[type="submit"]');
});

$('#membercp_form').submit(function(e)
{
	wDo = $('input[name="action"]').val();
	buildQuery = 'action=membercp&do='+wDo+'&securitytoken='+TSUESettings['stKey'];

	e.preventDefault();
	$.TSUE.insertLoaderAfter('input[type="reset"]');

	switch(wDo)
	{
		case 'personal_details':
			var gender = $('#memberinfo_gender_female').is(':checked') ? 'f' : ($('#memberinfo_gender_male').is(':checked') ? 'm' : ''), $date_of_birth = $('input[name="date_of_birth"]');
			buildQuery += '&gender='+gender;
			
			if($date_of_birth.length && $date_of_birth.val() != '')
			{
				buildQuery += '&date_of_birth='+$date_of_birth.val();
			}
		break;

		case 'contact_details':
			buildQuery = $.TSUE.buildLinkQuery(buildQuery,  ['your_email', 'membercp_your_existing_password']);
			useAlert=false;
		break;

		case 'preferences':
			var themeid = $('select[name="themeid"]').val(), languageid = $('select[name="languageid"]').val(), timezone = $('select[name="timezone"]').val(), torrentStyle = $('select[name="torrentStyle"]').val(), cids = [];

			$('input[name="cid[]"]').each(function()
			{
				var $this = $(this);
				if($this.is(':checked'))
				{
					cids.push(parseInt($this.val()));
				}
			});

			buildQuery += '&themeid='+parseInt(themeid)+'&languageid='+parseInt(languageid)+'&timezone='+$.TSUE.urlEncode(timezone)+'&torrentStyle='+parseInt(torrentStyle)+'&cids='+cids+'&accountParked='+($('#accountParked').is(':checked') ? '1' : '0');
		break;

		case 'privacy':
			var visible = $('input[name="visible"]').is(':checked'),
            receive_admin_email = $('input[name="receive_admin_email"]').is(':checked'),
			receive_pm_email = $('input[name="receive_pm_email"]').is(':checked'),
			show_your_age = $('input[name="show_your_age"]').is(':checked'),
            allow_view_profile = $('select[name="allow_view_profile"]').val();
			buildQuery += '&visible='+(visible ? 1 : 0)+'&receive_admin_email='+(receive_admin_email ? 1 : 0)+'&receive_pm_email='+(receive_pm_email ? 1 : 0)+'&show_your_age='+(show_your_age ? 1 : 0)+'&allow_view_profile='+$.TSUE.urlEncode(allow_view_profile);
		break;

		case 'password':
			buildQuery = $.TSUE.buildLinkQuery(buildQuery,  ['membercp_your_existing_password', 'membercp_new_password', 'membercp_confirm_new_password']);
		break;

		case 'signature':
			var signature = tinyMCE.activeEditor.getContent();
			buildQuery += '&signature='+$.TSUE.urlEncode(signature);
		break;

		case 'invite':
			var invite_friend_name = $('input[name="invite_friend_name"]').val(), invite_friend_email = $('input[name="invite_friend_email"]').val(), invite_friend_message = $('textarea[name="invite_friend_message"]').val();
			buildQuery += '&invite_friend_name='+$.TSUE.urlEncode(invite_friend_name)+'&invite_friend_email='+$.TSUE.urlEncode(invite_friend_email)+'&invite_friend_message='+$.TSUE.urlEncode(invite_friend_message);
		break;

		case 'performance':
			var shoutbox_enabled = $('input[name="shoutbox_enabled"]').is(':checked'),
            irtm_enabled = $('input[name="irtm_enabled"]').is(':checked'),
            alerts_enabled = $('input[name="alerts_enabled"]').is(':checked');
			buildQuery += '&shoutbox_enabled='+(shoutbox_enabled ? 1 : 0)+'&irtm_enabled='+(irtm_enabled ? 1 : 0)+'&alerts_enabled='+(alerts_enabled ? 1 : 0);
		break;

		default:
			buildQuery = '';
		break;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/membercp.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			$('#membercp_your_existing_password,#membercp_new_password,#membercp_confirm_new_password').val('');
			
			if(wDo == 'invite')
			{
				$('.error,.done').remove();
				
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					$('#invite_friend_name,#invite_friend_email,#invite_friend_message').val('');
					$('#inviteListHeader,.inviteListRows').remove();
					//Refresh Stats..
					$.TSUE.refreshMemberStats();
					$(serverResponse).insertAfter('#membercp_form');
				}
				else
				{
					$(serverResponse).insertBefore('#membercp_form');
				}
			}
			else
			{
				if($.TSUE.findresponsecode(serverResponse) == 'D')
				{
					if(wDo == 'contact_details')
					{
						$.TSUE.dialog(serverResponse);
					}
					else
					{
						$.TSUE.alert(serverResponse);
						if(wDo == 'preferences')
						{
							setTimeout(function()
							{
								window.location.reload();
							}, 1000);
						}
						if(wDo == 'personal_details' && $date_of_birth.length && $date_of_birth.val() != '')
						{
							$('span[rel="date_of_birth"]').fadeOut().html($date_of_birth.val()).fadeIn();
						}
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

$(document).on('click', '#delete_invite', function(e)
{
	var $this = $(this), $hash = $this.attr('rel');
	
	if(!$hash)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			$('.error,.done').remove();
			buildQuery = 'action=membercp&do=delete_invite&hash='+$.TSUE.urlEncode($hash)+'&securitytoken='+TSUESettings['stKey'];
			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/membercp.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#inviteListHeader,.inviteListRows').remove();
						$($.TSUE.clearresponse(serverResponse)).insertAfter('#membercp_form');
					}
					else
					{
						$($.TSUE.clearresponse(serverResponse)).insertAfter('#membercp_form');
					}
				}
			});
		}
	});
});

$(document).on('click', '#delete_subscribed_thread', function(e)
{
	e.preventDefault();

	var $threadid = parseInt($(this).attr('rel'));
	if(!$threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			buildQuery = 'action=membercp&do=delete_subscribed_thread&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];
			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/membercp.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						window.location.reload();
					}
					else
					{
						$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
					}
				}
			});
		}
	});

	return false;
});

showPasswordStrength('#membercp_new_password');