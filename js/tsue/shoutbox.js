var shoutboxEditorLoaded = false, loadNewShouts = false, idleTime = 0, idleInterval = false;

$(window).load(function()
{
	if(typeof refreshINseconds != 'undefined' && typeof maxLoadLimit != 'undefined')
	{
		autoGetNewShouts();
		hoverRows();
		setIdleInterval();
	}
});

function autoGetNewShouts()
{
	loadNewShouts = setInterval(getNewShouts, refreshINseconds*1000); 
};

function disableAutoGetNewShouts()
{
	loadNewShouts = window.clearInterval(loadNewShouts);
};

function getNewShouts()
{
	if(TSUESettings['isAjaxRunning'])
	{
		return false;
	}

	disableAutoGetNewShouts();
	shoutboxUpdating();
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/shoutbox.php',
		data:'action=getNewShouts&lastSID='+lastSID+'&securitytoken='+TSUESettings['stKey'],
		dataType: 'json',
		success: function(serverResponse)
		{
			disableShoutboxUpdating();
			autoGetNewShouts();
			
			if(serverResponse)
			{
				if(serverResponse['lastSID'])
				{
					lastSID = serverResponse['lastSID'];
					if(serverResponse['ShoutBOXRows'])
					{
						$(serverResponse['ShoutBOXRows']).prependTo('table[class="shoutbox"]');
						$('#shout_'+lastSID).hide().fadeIn('slow');
						hoverRows();
					}
				}
			}
		}
	});

	return false;
};

function forceUpdate()
{
	disableAutoGetNewShouts();
	TSUESettings['isAjaxRunning']=false;
	getNewShouts();
};

function setIdleInterval()
{
	idleInterval = self.setInterval(timerIncrement, 1000); // 1 second
};

function timerIncrement()
{
    idleTime += 1;
	if(idleTime > maxLoadLimit)
	{
		//Member is inactive.
		disableIdleInterval();
		disableShoutboxUpdating();
		disableAutoGetNewShouts();
		$('<div class="information" id="memberInactive">'+TSUEPhrases['shoutbox_inacitivityWarning']+'</div>').insertBefore('#shoutbox_list');
	}
};

function disableIdleInterval()
{
	idleTime = 0;
	idleInterval = window.clearInterval(idleInterval);
};

function shoutboxUpdating()
{
	$('#shoutboxUpdating').show();
	TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['disableButtonsWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
};

function disableShoutboxUpdating()
{
	$('#shoutboxUpdating').hide();
	TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['disableButtonsWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;
};

function hoverRows()
{
	$('table[class="shoutbox"] tr').hover(function()
	{
		$(this).addClass('box-hover');
		$('.sButtons', this).show();
	},
	function()
	{
		$(this).removeClass('box-hover');
		$('.sButtons', this).hide();
	});
};

$(document).on('click', '#imback', function(e)
{
	e.preventDefault();
	$('#memberInactive').remove();
	forceUpdate();
	setIdleInterval();
	return false;
});

$('#postShout').submit(function(e)
{
	e.preventDefault();

	idleTime = 0;

	var smessage = $.TSUE.removeSpaces(tinyMCE.activeEditor.getContent());
	if(smessage == '')
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	tinyMCE.activeEditor.setContent('');
	shoutboxUpdating();

	buildQuery = 'action=postShout&smessage='+$.TSUE.urlEncode(smessage)+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/shoutbox.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if($.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.dialog(serverResponse)
			}

			disableShoutboxUpdating();
			forceUpdate();
		}
	});

	return false;
});

$(document).on('click', '#edit_shout', function(e)
{
	e.preventDefault();

	idleTime = 0;

	var $sid = $(this).attr('rel');
	shoutboxUpdating();

	buildQuery = 'action=getShout&sid='+$sid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/shoutbox.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			disableShoutboxUpdating();

			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$('#cancel_editor_message_'+$sid).click(function(e)
				{
					e.preventDefault();
					$.TSUE.closedialog();
					return false;
				});

				$('#save_editor_message_'+$sid).click(function(e)
				{
					e.preventDefault();
					var smessage = $.TSUE.removeSpaces(tinyMCE.activeEditor.getContent());
					
					buildQuery = 'action=saveShout&sid='+$sid+'&smessage='+$.TSUE.urlEncode(smessage)+'&securitytoken='+TSUESettings['stKey'];
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/shoutbox.php',
						data:buildQuery,
						success: function(serverResponse)
						{
							if($.TSUE.findresponsecode(serverResponse))
							{
								$.TSUE.alert($.TSUE.htmlspecialchars(serverResponse));
							}
							else
							{
								$('#shout_'+$sid+' #smessage').fadeOut('slow', function()
								{
									var $updateShout = $(this);
									$updateShout.html(serverResponse).fadeIn('slow');
								});
								$.TSUE.alert(TSUEPhrases['message_saved']);
								$.TSUE.closedialog();
							}
						}
					});

					return false;
				});
			}
		}
	});

	return false;
});

$(document).on('click', '#delete_shout', function(e)
{
	e.preventDefault();

	idleTime = 0;

	var $this = $(this), $sid = $this.attr('rel');

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message'], function(yes)
	{
		if(yes)
		{
			shoutboxUpdating();
			buildQuery = 'action=deleteShout&sid='+$sid+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/shoutbox.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					disableShoutboxUpdating();
					if(serverResponse)
					{
						$('<div id="sServerResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#postShout');
						$.TSUE.autoRemoveBodyDIV('#sServerResponse');
					}
					else
					{
						$('#shout_'+$sid).fadeOut('slow', function() {$(this).remove()});
					}
				}
			});
		}
	});
});

$(document).on('click', '#tinymceShoutbox', function(e)
{
	idleTime = 0;

	if(!shoutboxEditorLoaded)
	{
		shoutboxEditorLoaded = $(this);
		shoutboxEditorLoaded.val('');
		tinyMCE.execCommand('mceAddControl', false, 'tinymceShoutbox');
		tinymce.execCommand('mceFocus', false, 'tinymceShoutbox');
		$('.shoutboxButtons').fadeIn('slow');
		tinyMCE.activeEditor.setContent('');
	}
});

$(document).on('change', 'select[name="shoutboxCID"]', function(e)
{
	var $cid = parseInt($(this).val());

	if($cid)
	{
		disableIdleInterval();
		disableShoutboxUpdating();
		disableAutoGetNewShouts();

		$('table[class="shoutbox"]').empty();

		lastSID = 0;

		$.TSUE.setCookie('shoutboxChannelID', $cid);

		forceUpdate();
		setIdleInterval();
	}
});