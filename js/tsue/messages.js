//messages
var $last_reply_id=0, $isPostingReply = false, replyEditorLoaded = false, checkedCheckBoxes = 0, $allChecked=false;

function _activeTinymceEditor(aEditor)
{
	if(!replyEditorLoaded)
	{
		replyEditorLoaded = aEditor;
		replyEditorLoaded.val('');
		tinyMCE.execCommand('mceAddControl', false, 'postAComment');
		tinymce.execCommand('mceFocus', false, 'postAComment');
		$('.postACommentButtons').fadeIn('slow');
		tinyMCE.activeEditor.setContent('');
	}
}

$(document).on('click', '#messages_select_all', function(e)
{
	e.preventDefault();

	$allChecked = $allChecked ? false : true;

	if($allChecked)
	{
		$.TSUE.enableInputButton('input[name="messages_delete_messages"]');
	}
	else
	{
		$.TSUE.disableInputButton('input[name="messages_delete_messages"]');
	}
	
	$('input[name="deleteMessages[]"]').attr('checked', $allChecked);
	
	return false;
});

$(document).on('click', '#replyMessage', function(e)
{
	e.preventDefault();

	var $reply_id = parseInt($(this).attr('reply_id')), $message_id = parseInt($(this).attr('message_id'));

	if(!$reply_id || !$message_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	_activeTinymceEditor($(this));//If editor doesn't loaded yet, lets load it first!
	
	buildQuery = 'action=messages_get_reply&message_id='+$message_id+'&reply_id='+$reply_id+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse) && serverResponse)
			{
				if(!replyEditorLoaded)
				{
					alert('Fatal Error: Editor does not loaded yet.. Please try again later..');
					return false;
				}
				else
				{
					var $activeEditorContent = tinyMCE.activeEditor.getContent();
					tinyMCE.activeEditor.setContent($activeEditorContent+serverResponse);
					$.TSUE.autoScroller($('#postAComment'), 'postAComment');
				}
			}
			else if(serverResponse)
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	})
});

$(document).on('click', '#messages_view_all', function(e)
{
	e.preventDefault();
	$.TSUE.jumpInternal('?p=messages&pid=20');
});

$(document).on('click', '#messages_new_message', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	buildQuery = 'action=messages_new_message&securitytoken='+TSUESettings['stKey'];
	
	var receiver_membername = $(this).attr('receiver_membername');
	if(receiver_membername)
	{
		buildQuery += '&receiver_membername='+$.TSUE.urlEncode(receiver_membername);
	}
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);

			$('#messages_post_new_message #receiver_membername').on('keyup', function()//TODO: MAKE GLOBAL
			{
				$.TSUE.autoComplete(this, 'search_membername');
			});
		}
	});
});
$(document).on('submit', '#messages_post_message', function(e)
{
	$.TSUE.insertLoaderAfter('#message_cancel');
	$('#serverResponse').remove();
	e.preventDefault();

	var receiver_membername = $('#receiver_membername', this).val(), subject = $('#subject', this).val(), message = tinyMCE.activeEditor.getContent();
	TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

	buildQuery = 'action=messages_new_message&do=save&receiver_membername='+$.TSUE.urlEncode(receiver_membername)+'&subject='+$.TSUE.urlEncode(subject)+'&message='+$.TSUE.urlEncode(message)+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$(serverResponse).prependTo('#show_member_messages');
				$('#messages_no_message').remove();
				$.TSUE.closedialog();
				$.TSUE.alert(TSUEPhrases['message_posted']);
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

$('#messages_post_reply').submit(function(e)
{
	e.preventDefault();

	$isPostingReply = true;
	$.TSUE.insertLoaderAfter('#post-reply-buttons');

	var message_id = $('input[name="message_id"]', this).val(), spanID = '#message_id_'+message_id, reply = tinyMCE.activeEditor.getContent();
	buildQuery = 'action=messages_post_reply&message_id='+parseInt(message_id)+'&reply='+$.TSUE.urlEncode(reply)+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse) && serverResponse != '')
			{
				var $search_last_reply_id = serverResponse.match(/~last_reply_id:([0-9]+)~/);
				serverResponse = serverResponse.replace($search_last_reply_id['0'], '');

				$last_reply_id = parseInt($search_last_reply_id['1']);

				$(serverResponse).appendTo('#fetchNewMessages');
				
				$.TSUE.alert(TSUEPhrases['message_saved']);
				tinyMCE.activeEditor.setContent('');
				$isPostingReply = false;
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}		
	});

	return false;
});

$(document).on('click', 'input[name="deleteMessages[]"]', function(e)
{
	if($(this).is(':checked'))
	{
		checkedCheckBoxes++;
	}
	else if(checkedCheckBoxes > 0)
	{
		checkedCheckBoxes--;
	}

	if(checkedCheckBoxes)
	{
		$.TSUE.enableInputButton('input[name="messages_delete_messages"]');
	}
	else
	{
		$.TSUE.disableInputButton('input[name="messages_delete_messages"]');
	}
});
$(document).on('click', 'input[name="messages_delete_messages"]', function(e)
{
	e.preventDefault();
	
	SelectedMessages = [];

	$('input[name="deleteMessages[]"]').each(function(i, e)
	{
		if($(e).is(':checked'))
		{
			var message_id = $(e).val();
			if(message_id)
			{
				SelectedMessages[i] = message_id;
			}
		}
	});

	if(SelectedMessages.length)
	{
		$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message'], function(yes)
		{
			if(yes)
			{
				buildQuery = 'action=messages_delete_messages&message_ids='+SelectedMessages+'&securitytoken='+TSUESettings['stKey'];
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/messages.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if($.TSUE.findresponsecode(serverResponse) == 'E')
						{
							$.TSUE.alert(serverResponse);
						}
						else
						{
							TSUESettings['messagesChecksCache'] = false;//Let system re-check messages via Ajax.

							if(TSUESettings['currentActiveURL'].match(/p=messages/ig))
							{
								$(SelectedMessages).each(function(i, message_id)
								{
									$('#show_message_'+message_id).remove();
								});
							}
							$.TSUE.disableInputButton('input[name="messages_delete_messages"]');
							$.TSUE.alert(serverResponse);
						}
					}		
				});
			}
		});
	}
});

$(document).ready(function()
{
	$.TSUE.disableInputButton('input[name="messages_delete_messages"]');
	
	var $message_id = TSUESettings['currentActiveURL'].match(/message_id=([0-9]+)/);

	if(TSUESettings['irtm_enabled'] == 1 && $message_id && $message_id['1'])
	{
		$last_reply_id = parseInt($('#last_reply_id').val());

		function fetchNewMessages()
		{
			if($isPostingReply)
			{
				return false;
			}

			TSUESettings['disableButtonsWhileAjax']= false, TSUESettings['closeOverlayWhileAjax']= false, TSUESettings['showLoaderWhileAjax']= false;

			buildQuery = 'action=fetch_new_replies&message_id='+parseInt($message_id['1'])+'&last_reply_id='+$last_reply_id+'&securitytoken='+TSUESettings['stKey'];
			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/messages.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse) && serverResponse != '')
					{
						var $search_last_reply_id = serverResponse.match(/~last_reply_id:([0-9]+)~/);
						serverResponse = serverResponse.replace($search_last_reply_id['0'], '');

						$last_reply_id = $search_last_reply_id['1'];

						$(serverResponse).appendTo('#fetchNewMessages');
					}
				}
			});

			TSUESettings['disableButtonsWhileAjax']= true, TSUESettings['closeOverlayWhileAjax']= true, TSUESettings['showLoaderWhileAjax']= true;
		}

		$(window).load(function()
		{
			var $fetchNewMessages = setInterval(fetchNewMessages, TSUESettings['fetchNewRepliesTimeout']);
		});
	}
});

$('#messages_show_more').on('click', function(e)
{
	e.preventDefault();

	$this = $(this), $read_messages = $this.attr('rel');

	if($read_messages)
	{
		$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);
		
		buildQuery = 'action=show_more_messages&read_messages='+$read_messages+'&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			url:TSUESettings['website_url']+'/ajax/messages.php',
			data:buildQuery,
			success: function(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					$(serverResponse).insertBefore($this);
					$this.remove();
				}
				else
				{
					$this.html($.TSUE.clearresponse(serverResponse));
				}
			}
		});
	}

	return false;
});

$(document).on('click', '#postAComment', function(e)
{
	_activeTinymceEditor($(this));
});

$('#pm_markAsUnread').on('click', function(e)
{
	e.preventDefault();
	
	var $this = $(this), $message_id = parseInt($this.attr('rel'));

	if(!$message_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=mark_as_unread&message_id='+$message_id+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				$.TSUE.dialog(serverResponse);
			}
			else
			{
				$.TSUE.jumpInternal('?p=messages&pid=20');
			}
		}
	});
	
	return false;
});

$('#pm_DeleteMessage').on('click', function(e)
{
	e.preventDefault();
	
	var $this = $(this), $message_id = parseInt($this.attr('rel'));

	if(!$message_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=messages_delete_messages&message_ids='+$message_id+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if($.TSUE.findresponsecode(serverResponse) == 'E')
			{
				$.TSUE.dialog(serverResponse);
			}
			else
			{
				$.TSUE.jumpInternal('?p=messages&pid=20');
			}						
		}		
	});
});

$('#pm_forwardMessage').on('click', function(e)
{
	e.preventDefault();
	
	var $this = $(this), $message_id = parseInt($this.attr('rel'));

	if(!$message_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}
	
	buildQuery = 'action=messages_new_message&message_id='+$message_id+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/messages.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);

			$('#messages_post_new_message #receiver_membername').on('keyup', function()//TODO: MAKE GLOBAL
			{
				$.TSUE.autoComplete(this, 'search_membername');
			});
		}
	});
});

$(window).load(function()
{
	//messages
	if(TSUESettings['currentActiveURL'].match(/dialog=pm/ig))
	{
		var $this = $('li[rel="checkMessages"]');
		
		$.TSUE.checkMessages($this);

		$(document).ajaxStop(function ()
		{
			$.TSUE.dialog($('.atext', $this).html());
		});
	}
});