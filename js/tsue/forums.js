var forumsEditorLoaded = false;

function activeTinymceEditor(aEditor)
{
	if(!forumsEditorLoaded)
	{
		forumsEditorLoaded = aEditor;
		forumsEditorLoaded.val('');
		tinyMCE.execCommand('mceAddControl', false, 'postAReply');
		tinymce.execCommand('mceFocus', false, 'postAReply');
		$('.postAReplyButtons').fadeIn('slow');
		tinyMCE.activeEditor.setContent('');
	}
}

$(document).on('click', '[rel=password_required]', function(e)
{
	e.preventDefault();

	var $forumid = parseInt($(this).attr('id'));
	if(!$forumid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=password_required&do=form&forumid='+$forumid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});
});
$(document).on('submit', '#passwordrequired_form', function(e)
{
	e.preventDefault();

	var $password = $('#passwordrequired_password').val(),
	$forumid = parseInt($('#passwordrequired_forumid').val());

	if($password && $forumid)
	{
		$.TSUE.insertLoaderAfter('#passwordrequired-buttons');
		TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
		buildQuery = 'action=password_required&do=check&forumid='+$forumid+'&password='+$.TSUE.urlEncode($password)+'&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			url:TSUESettings['website_url']+'/ajax/forums.php',
			data:buildQuery,
			success: function(serverResponse)
			{
				TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					if(TSUESettings['currentActiveURL'].match(/fid=/))
					{
						window.location.reload();
					}
					else
					{
						$.TSUE.jumpInternal('?p=forums&pid=11&fid='+$forumid);
					}
				}
				else
				{
					$('#server_response').removeClass('information').html($.TSUE.clearresponse(serverResponse));
					$('#passwordrequired_password').val('');
				}
			}
		});
	}
	else
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}
});

$('#forums_post_reply').submit(function(e)
{
	e.preventDefault();

	var $threadid = parseInt($('input[name="threadid"]').val()),
	$forumid = parseInt($('input[name="forumid"]').val()),
	$message = $.TSUE.urlEncode(tinyMCE.activeEditor.getContent()),
	$attachment_ids = [];

	if($message === '' || !$forumid || !$threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter('input[type="reset"]');

	$('input[name="attachment_ids[]"]').each(function(i, e)
	{
		$attachment_ids[i] = parseInt($(e).val());
	});

	buildQuery = 'action=forums_post_reply&threadid='+$threadid+'&forumid='+$forumid+'&message='+$message+'&attachment_ids='+$attachment_ids+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$(serverResponse).appendTo('#forums_post_new_reply');
				tinyMCE.activeEditor.setContent('');
				$('.qq-upload-list,#ajax_attachments').empty();//remove previous uploads.
				$.TSUE.alert(TSUEPhrases['message_posted']);
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	})
});

$(document).on('click', '[rel=reply_post]', function(e)
{
	e.preventDefault();

	var $postid = parseInt($(this).attr('id')),
	$threadid = parseInt($(this).attr('threadid')),
	$forumid = parseInt($(this).attr('forumid'));

	if(!$postid || !$threadid || !$forumid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	activeTinymceEditor($(this));//If editor doesn't loaded yet, lets load it first!
	
	buildQuery = 'action=forums_get_reply&postid='+$postid+'&threadid='+$threadid+'&forumid='+$forumid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				if(!forumsEditorLoaded)
				{
					alert('Fatal Error: Editor does not loaded yet.. Please try again later..');
					return false;
				}
				else
				{
					var $activeEditorContent = tinyMCE.activeEditor.getContent();
					tinyMCE.activeEditor.setContent($activeEditorContent+serverResponse);
					$.TSUE.autoScroller($('.forums_post_reply'), 'forums_post_reply');
				}
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	})
});

$('#post_new_thread').submit(function(e)
{
	e.preventDefault();

	var $forumid = parseInt($('input[name="forumid"]').val()),
	$title = $.TSUE.urlEncode($('input[name="title"]').val()),
	$message = $.TSUE.urlEncode(tinyMCE.activeEditor.getContent()),
	$attachment_ids = [],
	$email_notification = parseInt($('input:radio[name="email_notification"]:checked').val()) || 2,
	$poll_question = $.TSUE.urlEncode($('input[name="poll_question"]').val()),
	$closeDaysAfter = parseInt($('input[name="closeDaysAfter"]').val()) || 0,
	$multiple = parseInt($('input:checkbox[name="multiple"]:checked').val()) || 0,
	$pollOptions = [];

	if($message === '' || $title === '' || !$forumid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$('input[name="attachment_ids[]"]').each(function(i, e)
	{
		$attachment_ids[i] = parseInt($(e).val());
	});

	$('input[name="pollOptions[]"]').each(function(i, e)
	{
		var $option = $(e).val().trim();
		if($option)
		{
			$pollOptions[i] = $.TSUE.urlEncode($option);
		}
	});

	$.TSUE.insertLoaderAfter('input[type="button"]');

	buildQuery = 'action=new_thread&forumid='+$forumid+'&title='+$title+'&message='+$message+'&attachment_ids='+$attachment_ids+'&email_notification='+$email_notification+'&poll_question='+$poll_question+'&pollOptions='+$pollOptions+'&closeDaysAfter='+$closeDaysAfter+'&multiple='+$multiple+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.alert(TSUEPhrases['message_posted']);
				$.TSUE.disableInputButton('body input,select,textarea');
				$threadid = parseInt(serverResponse);
				$.TSUE.jumpInternal("?p=forums&pid=11&fid="+$forumid+"&tid="+$threadid);
				return false;
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	})
});

$('#search_in_forums').submit(function(e)
{
	e.preventDefault();

	var $keywords = $('#keywords').val(),
	$title_only = $('#title_only').is(':checked') ? 1 : 0,
	$membername = $('#membername').val(),
	$newer_than = $('#newer_than').val(),
	$forums = $('#multiple').val(),
	$result_type = $('#result_type_posts').is(':checked') ? 'posts' : 'threads',
	$this_member_only = $('#this_member_only').is(':checked') ? 1 : 0,
	$min_nr_of_replies = parseInt($('#min_nr_of_replies').val()),
	$order_by = $('#order_by_most_replies').is(':checked') ? 'most_replies' : ($('#order_by_most_views').is(':checked') ? 'most_views' : 'most_recent');

	if(!$keywords && !$membername)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter('input[type="reset"]');

	buildQuery = 'action=forums_search&keywords='+$.TSUE.urlEncode($keywords)+'&title_only='+parseInt($title_only)+'&membername='+$.TSUE.urlEncode($membername)+'&newer_than='+$.TSUE.urlEncode($newer_than)+'&forums='+$.TSUE.urlEncode($forums)+'&result_type='+$result_type+'&this_member_only='+$this_member_only+'&min_nr_of_replies='+$min_nr_of_replies+'&order_by='+$order_by+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse) && $.TSUE.isNumber(serverResponse))
			{
				$.TSUE.jumpInternal('?p=forums&pid=11&action=search_forums&searchid='+parseInt(serverResponse))
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});
});

$(document).on('click', '[rel=edit_post]', function(e)
{
	e.preventDefault();

	var $postid = parseInt($(this).attr('id')),
	$threadid = parseInt($(this).attr('threadid')),
	$forumid = parseInt($(this).attr('forumid'));

	if(!$postid || !$threadid || !$forumid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);
	
	buildQuery = 'action=forums_edit_post&postid='+$postid+'&threadid='+$threadid+'&forumid='+$forumid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				var originalMessage = serverResponse;
				$.TSUE.dialog(originalMessage);
				
				$('#cancel_editor_message_'+$postid).click(function(e)
				{
					$.TSUE.closedialog();
					e.preventDefault();
				});

				$('#save_editor_message_'+$postid).click(function(e)
				{
					e.preventDefault();
					
					var message = tinyMCE.activeEditor.getContent();
					if(!message)
					{
						$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
						return false;
					}

					if(message == originalMessage)
					{
						$.TSUE.closedialog();
						return false;
					}

					var edit_reason = $('#edit_reason_'+$postid).val();

					$.TSUE.insertLoaderAfter($.TSUE.getOverlayDiv().find('input[type="button"]').last());

					buildQuery = 'action=forums_edit_post&do=save&message='+$.TSUE.urlEncode(message)+'&edit_reason='+$.TSUE.urlEncode(edit_reason)+'&postid='+$postid+'&threadid='+$threadid+'&forumid='+$forumid+'&securitytoken='+TSUESettings['stKey'];
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/forums.php',
						data: buildQuery,
						success: function(serverResponse)
						{
							if(!$.TSUE.findresponsecode(serverResponse))
							{
								$.TSUE.closedialog();
								$('#post_attachments_'+$postid).empty().remove();
								$('#post_message_'+$postid).hide().html(serverResponse).fadeIn('slow');
								$.TSUE.alert(TSUEPhrases['message_saved']);
								$('.qq-upload-list,#ajax_attachments').empty();//remove previous uploads.
							}
							else
							{
								$.TSUE.dialog(serverResponse);
							}
						}
					});
				});

				if(serverResponse.match(/upload_a_file_overlay/))
				{
					setTimeout(function()
					{						
						var $fuElement = $('#upload_a_file_overlay'), $extendOptions = 
						{
							request:
							{
								endpoint: TSUESettings['website_url']+'/ajax/upload_file.php',
								params:
								{
									action: 'upload_file',
									content_type: $.TSUE.urlEncode($fuElement.attr('content_type')),
									forumid: parseInt($fuElement.attr('forumid')),
									postid: $postid,
									securitytoken: TSUESettings['stKey']
								}
							}
						};

						$fuElement.fineUploader($.TSUE.buildFUOptions($fuElement, TSUEUpload['upload_files'], $extendOptions));

					}, 2000);
				}
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	})
});

$(document).on('click', '[rel=delete_post]', function(e)
{
	e.preventDefault();
	$this = $(this);

	var $postid = parseInt($this.attr('id')),
	$threadid = parseInt($this.attr('threadid')),
	$forumid = parseInt($this.attr('forumid'));

	if(!$postid || !$threadid || !$forumid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message'], function(yes)
	{
		if(yes)
		{
			$.TSUE.insertLoaderAfter($this);

			buildQuery = 'action=forums_delete_post&postid='+$postid+'&threadid='+$threadid+'&forumid='+$forumid+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/forums.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$.TSUE.alert(serverResponse);
						if(serverResponse == 'ALL DELETED')
						{
							$.TSUE.jumpInternal('?p=forums&pid=11&fid='+$forumid);
							return false;
						}
						else
						{
							$('#show_post_'+$postid).fadeOut('slow');
						}
					}
					else
					{
						$.TSUE.alert($.TSUE.strip_tags(serverResponse));
					}
				}
			});
		}
	});
});

$(document).on('click', '[rel="editThread"]', function(e)
{
	e.preventDefault();
	handleEditThread(this, false);
	return false;
});
function handleEditThread(element, viaOnHold)
{
	var $threadid = parseInt($(element).attr((viaOnHold ? 'threadid' : 'id'))), $action = 'editThread';
	
	if(!$threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.appendLoaderAfter(element);
	buildQuery = 'action='+$.TSUE.urlEncode($action)+'&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$('#editThread').submit(function(e)//Save Edited Thread.
				{
					e.preventDefault();
					$('#serverResponse').remove();
					$.TSUE.insertLoaderAfter('input[type="reset"]');
					TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
					
					buildQuery = $(this).serialize()+'&action='+$.TSUE.urlEncode($action)+'&do=save&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/forums.php',
						data: buildQuery,
						success: function(serverResponse)
						{
							if(!$.TSUE.findresponsecode(serverResponse))
							{
								//$.TSUE.closedialog();
								//$.TSUE.alert($.TSUE.strip_tags(serverResponse));
								return window.location.reload();
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
		}
	});
}

$(document).on('click', '[rel="moveThread"]', function(e)
{
	e.preventDefault();

	var $threadid = parseInt($(this).attr('id')), $action = 'moveThread';
	if(!$threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.appendLoaderAfter(this);
	buildQuery = 'action='+$.TSUE.urlEncode($action)+'&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$('#moveThread').submit(function(e)//Save Moved Thread.
				{
					e.preventDefault();
					$('#serverResponse').remove();
					$.TSUE.insertLoaderAfter('input[type="reset"]');
					TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
					
					buildQuery = $(this).serialize()+'&action='+$.TSUE.urlEncode($action)+'&do=save&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/forums.php',
						data: buildQuery,
						success: function(serverResponse)
						{
							if(!$.TSUE.findresponsecode(serverResponse))
							{
								$.TSUE.closedialog();
								$newForumID = parseInt(serverResponse);
								if($newForumID)
								{
									//Redirect to the new forum.
									window.location = TSUESettings['website_url']+'/?p=forums&pid=11&fid='+$newForumID+'&tid='+$threadid+'&message_saved=1';
								}
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
		}
	});

	return false;
});

$(document).on('click', '[rel="deleteThread"]', function(e)
{
	e.preventDefault();
	handleDeleteThread(this);
	return false;
});
function handleDeleteThread(element)
{
	var $threadid = parseInt($(element).attr('id')), $action = 'deleteThread';
	if(!$threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message'], function(yes)
	{
		if(yes)
		{
			buildQuery = 'action='+$.TSUE.urlEncode($action)+'&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/forums.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.alert(TSUEPhrases['message_deleted']);
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$.TSUE.jumpInternal('?p=forums&pid=11&fid='+parseInt(serverResponse));
					}
				}
			});
		}
	});
}

$(document).on('click', '[rel="subscribeToThread"]', function(e)
{
	e.preventDefault();
	
	var $threadid = parseInt($(this).attr('id')), $action = 'subscribeToThread';
	if(!$threadid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.appendLoaderAfter(this);
	buildQuery = 'action='+$.TSUE.urlEncode($action)+'&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});

	return false;
});
$(document).on('submit', '#subscribeToThread', function(e)
{
	e.preventDefault();

	var $thisForm = $(this), buildQuery = $thisForm.serialize();
	$('#subsErrors').remove();
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forums.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.closedialog();
				$.TSUE.alert(serverResponse);
			}
			else
			{
				$('<div id="subsErrors">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore($thisForm);
			}
		}
	});

	return false;
});

$(document).on('click', '#deleteAttachment', function(e)
{
	e.preventDefault();
	
	var $attachment_id = parseInt($(this).attr('attachment_id'));
	if(!$attachment_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			buildQuery = 'action=delete_post_image&attachment_id='+$attachment_id+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/forums.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#attachment_'+$attachment_id).remove();
					}
					$.TSUE.alert($.TSUE.strip_tags(serverResponse));
				}
			});
		}
	});

	return false;
});

$(document).on('click', '#post_permalink', function(e)
{
	e.preventDefault();

	var $href = $(this).attr('href');
	$.TSUE.dialog('<input type="text" class="s ajaxInputText" value="'+$href+'" style="width: 99%;" />', document.title);
	return false;
});

$(document).ready(function()
{
	var $mouseOverThreadRunning, $forumid, $threadid;
	
	$('[rel="editThreadHD"]').onHold(750, function(object){$('#threadPreviewBox').remove(); clearTimeout($mouseOverThreadRunning); handleEditThread(object, true);return false;})
		.mouseover(function()
		{
			$('#threadPreviewBox').remove(); clearTimeout($mouseOverThreadRunning);
			
			var $this = $(this), $forumid = parseInt($this.attr('forumid')), $threadid = parseInt($this.attr('threadid'));
			
			$mouseOverThreadRunning = setTimeout(function()
			{
				$('<div id="threadPreviewBox" style="position: absolute; z-index: 10; max-width: 350px; padding: 3px 10px; background: #fff; border: 1px solid #ccc; -moz-box-shadow: 0 0 5px #888;-webkit-box-shadow: 0 0 5px#888;box-shadow: 0 0 5px #888;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;">'+TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']+'</div>').insertAfter($this);

				TSUESettings['showLoaderWhileAjax'] = false;
				TSUESettings['disableButtonsWhileAjax'] = false;

				buildQuery = 'action=threadPreview&forumid='+$forumid+'&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/forums.php',
					data: buildQuery,
					success: function(serverResponse)
					{
						if(serverResponse)
						{
							$('#threadPreviewBox').html(serverResponse);
						}
						else
						{
							$('#threadPreviewBox').empty().remove();
						}
						clearTimeout($mouseOverThreadRunning);

						TSUESettings['showLoaderWhileAjax'] = true;
						TSUESettings['disableButtonsWhileAjax'] = true;
					}
				});
			}, 1000)
		})
		.mouseout(function(){$('#threadPreviewBox').remove(); clearTimeout($mouseOverThreadRunning);});
	
	if($('#content').find('#upload_a_file').length)
	{
		var $fuElement = $('#upload_a_file');

		$fuElement.fineUploader($.TSUE.buildFUOptions($fuElement, TSUEUpload['upload_files'])).on('complete', function(event, id, fileName, responseJSON)
		{
			if(responseJSON.success)
			{						
				$attachment_id = parseInt(responseJSON.success);
				if($attachment_id)
				{
					$('<input type="hidden" name="attachment_ids[]" value="'+$attachment_id+'">').appendTo('#ajax_attachments');
				}
			}
		});
	}
});

var $threadids = [];
$(document).on('click', 'input[name="mass_action_threads[]"]', function(e)
{
	var $input = $(this), $val = $input.val();

	$('#massAction,#threadDeletionSR').remove();

	$.TSUE.removeItem($threadids, $val);//Remove item from the array!

	if($input.is(':checked') && $.inArray($val, $threadids) == -1)
	{
		$threadids.push($val);
	}

	if($threadids.length)
	{
		$('<div id="massAction"><span id="massDeleteThreads"><img src="'+TSUESettings['theme_dir']+'buttons/delete.png" class="middle" alt="" title="" /> '+TSUEPhrases['button_delete']+'</span> | <span id="massMoveThreads"><img src="'+TSUESettings['theme_dir']+'buttons/manage.png" class="middle" alt="" title="" /> '+TSUEPhrases['button_move']+'</span> | <span id="massLockThreads"><img src="'+TSUESettings['theme_dir']+'forums/icons/lock.png" class="middle" alt="" title="" /> '+TSUEPhrases['button_lock']+'</span> <span id="massUnLockThreads"><img src="'+TSUESettings['theme_dir']+'forums/icons/unlock.png" class="middle" alt="" title="" /> '+TSUEPhrases['button_unlock']+'</span> | <span id="massStickyThreads"><img src="'+TSUESettings['theme_dir']+'forums/icons/sticky.png" class="middle" alt="" title="" /> '+TSUEPhrases['forums_icon_sticky']+'</span> <span id="massUnStickyThreads"><img src="'+TSUESettings['theme_dir']+'forums/icons/unsticky.png" class="middle" alt="" title="" /> '+TSUEPhrases['button_unsticky']+'</span></div>').insertBefore($input).slideDown('slow');
		
		$('#massDeleteThreads').click(function(e)
		{
			e.preventDefault();
			
			var $this = $('#massAction');

			$this.html(TSUEPhrases['confirm_mass_delete_threads']).click(function(e)
			{
				e.preventDefault();

				$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);

				$.ajaxSetup({async: false});
				var j = 0;
				while (j < $threadids.length)
				{
					var $threadid = $threadids[j], $work = $('#thread_'+$threadid);
					j++;

					buildQuery = 'action=deleteThread&threadid='+$threadid+'&securitytoken='+TSUESettings['stKey'];
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/forums.php',
						data: buildQuery,
						success: function(serverResponse)
						{
							if(!$.TSUE.findresponsecode(serverResponse))
							{
								$work.fadeOut('slow');
							}
							else
							{
								$('<div id="threadDeletionSR">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#thread_input_'+$threadid);
							}
						}
					});
				}

				$this.slideUp('slow', function() {$(this).remove()});
				$.ajaxSetup({async: true});

				return false;
			});

			return false;
		});

		$('#massMoveThreads').click(function(e)
		{
			e.preventDefault();
			
			var $this = $('#massAction');

			$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);

			buildQuery = 'action=massMoveThreads&threadids='+$threadids+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/forums.php',
				data: buildQuery,
				success: function(forumSelectBOX)
				{
					if(!$.TSUE.findresponsecode(forumSelectBOX))
					{
						$this.html(forumSelectBOX);
						$('input[name="massMoveThreads"]').click(function(e)
						{
							e.preventDefault();

							var $newForumID = parseInt($('select[name="newforumid"]').val());
							if($newForumID)
							{
								$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);
								buildQuery = 'action=massMoveThreads&do=save&newforumid='+$newForumID+'&threadids='+$threadids+'&securitytoken='+TSUESettings['stKey'];
								$.ajax
								({
									url:TSUESettings['website_url']+'/ajax/forums.php',
									data: buildQuery,
									success: function(serverResponse)
									{
										if(!$.TSUE.findresponsecode(serverResponse))
										{
											var j = 0;
											while (j < $threadids.length)
											{
												var $threadid = $threadids[j], $work = $('#thread_'+$threadid);
												j++;
												$work.fadeOut('slow');
											}
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
					}
					else
					{
						$.TSUE.dialog(forumSelectBOX);
					}
				}
			});

			return false;
		});

		$('#massLockThreads,#massUnLockThreads').click(function(e)
		{
			e.preventDefault();
			
			var $action = $(this).attr('id'), $this = $('#massAction');

			$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);

			buildQuery = 'action='+$action+'&threadids='+$threadids+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/forums.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						var j = 0;
						while (j < $threadids.length)
						{
							var $threadid = $threadids[j];
							j++;

							$('#thread_'+$threadid+' td:nth-child(2)').find('#icons span:nth-child(2)').attr('class', ($action == 'massLockThreads' ? 'locked' : ''));
							$('#thread_input_'+$threadid).attr('checked', false);
						}
						$this.remove();
					}
					else
					{
						$this.html($.TSUE.clearresponse(serverResponse));
					}
				}
			});
		});

		$('#massStickyThreads,#massUnStickyThreads').click(function(e)
		{
			e.preventDefault();
			
			var $action = $(this).attr('id'), $this = $('#massAction');

			$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);

			buildQuery = 'action='+$action+'&threadids='+$threadids+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/forums.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						var j = 0;
						while (j < $threadids.length)
						{
							var $threadid = $threadids[j];
							j++;

							$('#thread_'+$threadid+' td:nth-child(2)').find('#icons span:nth-child(1)').attr('class', ($action == 'massStickyThreads' ? 'sticky' : ''));
							$('#thread_input_'+$threadid).attr('checked', false);
						}
						$this.remove();
					}
					else
					{
						$this.html($.TSUE.clearresponse(serverResponse));
					}
				}
			});
		});
	}
});

$(document).on('click', '#postAReply', function(e)
{
	activeTinymceEditor($(this));
});

$(document).on('click', '.button_add_more_fields', function(e)
{
	e.preventDefault();

	$('<div><input type="text" name="pollOptions[]" value="" class="s" /></div>').appendTo('#new_options');

	return false;
});

$(function()
{
	if(TSUESettings['currentActiveURL'].match(/message_saved=1/i))
	{
		$.TSUE.alert(TSUEPhrases['message_saved']);
	}
});