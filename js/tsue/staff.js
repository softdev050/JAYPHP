$(document).on('click', '#newStaffMessage', function(e)
{
	e.preventDefault();
	$.TSUE.jumpInternal('?p=staffmessages&pid=500');
	return false;
});

$(document).on('click', '#add_note', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);

	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=add_a_note&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#staff_add_a_note_form').submit(function(e)
			{
				e.preventDefault();

				$.TSUE.insertLoaderAfter('#message_cancel');
				$('#serverResponse').remove();

				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

				var $note = $('textarea[name="note"]', this).val();

				buildQuery = 'action=add_a_note&do=save&memberid='+$memberid+'&note='+$.TSUE.urlEncode($note)+'&securitytoken='+TSUESettings['stKey'];
				
				$.ajax(
				{
					url:TSUESettings['website_url']+'/ajax/staff.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$.TSUE.closedialog();
							$.TSUE.alert(serverResponse);
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

$(document).on('click', '#member_ip_to_country', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);

	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=member_ip_to_country&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});

	return false;
});

$(document).on('click', '#delete_staff_note', function(e)
{
	$this = $(this);

	$('#serverResponse').remove();

	var $noteid = parseInt($(this).attr('rel'));
	if(!$noteid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	if(confirm(TSUEPhrases['confirm_delete_message']))
	{
		$.TSUE.insertLoaderAfter($this);
		buildQuery = 'action=delete_staff_note&noteid='+$noteid+'&securitytoken='+TSUESettings['stKey'];

		$.ajax(
		{
			url:TSUESettings['website_url']+'/ajax/staff.php',
			data: buildQuery,
			success: function(serverResponse)
			{
				$('<div id="serverResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').prependTo($.TSUE.findOverlayTextDiv());
				$('#staff_note_'+$noteid).fadeOut('slow');
			}
		});
	};
});

$(document).on('click', '#remove_member_avatar', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=remove_member_avatar&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.alert(serverResponse);
		}
	});
});

$(document).on('click', '#reset_member_passkey', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=reset_member_passkey&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.alert(serverResponse);
		}
	});
});

$(document).on('click', '#mute_member', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=mute_member&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('input[type="date"]').dateinput({lang: 'tsue', format: 'dd/mm/yyyy', selectors: true, min: 0, max: 600}).attr('autocomplete', 'off');

			$('#staff_mute_member_form').submit(function(e)
			{
				e.preventDefault();

				$.TSUE.insertLoaderAfter('#message_cancel');
				$('#serverResponse').remove();

				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

				var $notes = $('input[name="notes"]', this).val(), $end_date = $('input[name="end_date"]', this).val(), $areas = [];

				$('input[name="mutetype[]"]', this).each(function()
				{
					if(this.checked)
					{
						$areas.push($(this).val());
					}
				});

				buildQuery = 'action=mute_member&do=mute&areas='+$areas+'&memberid='+$memberid+'&notes='+$.TSUE.urlEncode($notes)+'&end_date='+$.TSUE.urlEncode($end_date)+'&securitytoken='+TSUESettings['stKey'];
				
				$.ajax(
				{
					url:TSUESettings['website_url']+'/ajax/staff.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$.TSUE.closedialog();
							$.TSUE.alert(serverResponse);
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
});

$(document).on('click', '#lift_mute', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=lift_mute&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.alert(serverResponse);
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});
});

$(document).on('click', '#award_member', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=award_member&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);

			$('#staff_award_member_form').submit(function(e)
			{
				e.preventDefault();

				$.TSUE.insertLoaderAfter('#message_cancel');
				$('#serverResponse').remove();

				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

				var $reason = $('input[name="reason"]', this).val();
				var $award_id = $('input[name="award_id"]:checked', this).val();

				buildQuery = 'action=award_member&do=award&memberid='+$memberid+'&reason='+$.TSUE.urlEncode($reason)+'&award_id='+$.TSUE.urlEncode($award_id)+'&securitytoken='+TSUESettings['stKey'];
				
				$.ajax(
				{
					url:TSUESettings['website_url']+'/ajax/staff.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$.TSUE.closedialog();
							$.TSUE.alert(serverResponse);
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
});

$(document).on('click', '#warn_member', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=warn_member&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('input[type="date"]').dateinput({lang: 'tsue', format: 'dd/mm/yyyy', selectors: true, min: 0, max: 600}).attr('autocomplete', 'off');

			$('#staff_warn_member_form').submit(function(e)
			{
				e.preventDefault();

				$.TSUE.insertLoaderAfter('#message_cancel');
				$('#serverResponse').remove();

				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

				var $notes = $('input[name="notes"]', this).val();
				var $end_date = $('input[name="end_date"]', this).val();

				buildQuery = 'action=warn_member&do=warn&memberid='+$memberid+'&notes='+$.TSUE.urlEncode($notes)+'&end_date='+$.TSUE.urlEncode($end_date)+'&securitytoken='+TSUESettings['stKey'];
				
				$.ajax(
				{
					url:TSUESettings['website_url']+'/ajax/staff.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$.TSUE.closedialog();
							$.TSUE.alert(serverResponse);
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
});

$(document).on('click', '#lift_warn', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=lift_warn&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.alert(serverResponse);
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});
});

$(document).on('click', '#ban_member', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=ban_member&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('input[type="date"]').dateinput({lang: 'tsue', format: 'dd/mm/yyyy', selectors: true, min: 0, max: 600}).attr('autocomplete', 'off');

			$('#staff_ban_member_form').submit(function(e)
			{
				e.preventDefault();

				$.TSUE.insertLoaderAfter('#message_cancel');
				$('#serverResponse').remove();

				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

				var $reason = $('input[name="reason"]', this).val();
				var $end_date = $('input[name="end_date"]', this).val();

				buildQuery = 'action=ban_member&do=ban&memberid='+$memberid+'&reason='+$.TSUE.urlEncode($reason)+'&end_date='+$.TSUE.urlEncode($end_date)+'&securitytoken='+TSUESettings['stKey'];
				
				$.ajax(
				{
					url:TSUESettings['website_url']+'/ajax/staff.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$.TSUE.closedialog();
							$.TSUE.alert(serverResponse);
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
});

$(document).on('click', '#lift_ban', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=lift_ban&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.alert(serverResponse);
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});
});

$(document).on('click', '#staff_member_history_link', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=member_history&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];

	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('ul.tabs').tabs('div.panes > div');
		}
	});
});

$(document).on('click', '#staff_manage_member_account', function(e)
{
	window.location = $(this).attr('href');
});

$(document).on('click', '#find_all_content', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=find_all_content&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];

	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('ul.tabs').tabs('div.tabItems');
		}
	});
});

$(document).on('click', '#spam_cleaner', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	var $memberid = parseInt($(this).attr('memberid'));
	if(!$memberid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=spam_cleaner&memberid='+$memberid+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/staff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('form[name="spam_cleaner"]').submit(function(e)
			{
				e.preventDefault();
				$('#formSRstatus').remove();				
				var $form = $(this), $formData = $form.serialize();
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/staff.php',
					data:$formData,
					success: function(serverResponse)
					{
						$('<div id="formSRstatus">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore($form);
					}
				});
				return false;
			});
		}
	});
});

$('#adminLinksShowMenu').click(function(e)
{
	e.preventDefault();
	
	var $this = $(this), $acplinksdiv = $this.next('div');

	$.TSUE.dialog('<div id="acplinksonfly" style="text-align: center !important; margin-top: 10px;">'+$acplinksdiv.html()+'</div>', $this.attr('alt'))

	return false;
});