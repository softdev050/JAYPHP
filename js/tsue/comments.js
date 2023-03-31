/* 
	Post a New Comment
*/
$('#comments_post_form').submit(function(e)
{
	e.preventDefault();

	var message = tinyMCE.activeEditor.getContent(), content_type = $(this).find('input#content_type').val(), content_id = $(this).find('input#content_id').val();

	if(message === '')
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter('input[type="reset"]');

	buildQuery = 'action=post_comment&message='+$.TSUE.urlEncode(message)+'&content_type='+$.TSUE.urlEncode(content_type)+'&content_id='+parseInt(content_id)+'&securitytoken='+TSUESettings['stKey'];
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/comments.php',
		data: buildQuery,
		success: function(serverResponse)
		{
			if($.TSUE.findresponsecode(serverResponse) == 'D')
			{
				$.TSUE.alert(TSUEPhrases['message_posted']);
				serverResponse = $.TSUE.clearresponse(serverResponse);
				
				if($('#comments_show_more').length)
				{
					$(serverResponse).insertBefore('#comments_show_more');
				}
				else
				{
					$(serverResponse).insertBefore('#comments_post_form');
				}
				
				$('#no_comments').remove();
				tinyMCE.activeEditor.setContent('');
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});
});

/* 
	Delete Comment
*/
$(document).on('click', '#delete_comment', function(e)
{
	e.preventDefault();
	var $this = $(this), $comment_id = parseInt($this.attr('rel')), $content_type = $this.attr('content_type'), $commentDiv = $('#Commentsbox_'+$comment_id), $thisHtml = $this.html();

	if(!$comment_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message'], function(yes)
	{
		if(yes)
		{
			$this.html(TSUESettings['ajaxLoaderImage']);
			buildQuery = 'action=delete_comment&comment_id='+parseInt($comment_id)+'&content_type='+$.TSUE.urlEncode($content_type)+'&securitytoken='+TSUESettings['stKey'];
	
			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/comments.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if(serverResponse)
					{
						$this.html($thisHtml);
						$('<div id="sServerResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#comments_post_form');
						$.TSUE.autoRemoveBodyDIV('#sServerResponse');
					}
					else
					{
						$.TSUE.alert(TSUEPhrases['message_deleted']);
						$commentDiv.fadeOut('slow');
					}
				}
			});
		}
	});
});

/* 
	Delete Comment Reply
*/
$(document).on('click', '#delete_comment_reply', function(e)
{
	e.preventDefault();
	var $this = $(this), $reply_id = parseInt($this.attr('rel')), $content_type = $this.attr('content_type'), $replyDiv = $('#comment_reply_'+$reply_id), $thisHtml = $this.html();

	if(!$reply_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message'], function(yes)
	{
		if(yes)
		{
			$this.html(TSUESettings['ajaxLoaderImage']);
			buildQuery = 'action=delete_comment&reply_id='+parseInt($reply_id)+'&content_type='+$.TSUE.urlEncode($content_type)+'&securitytoken='+TSUESettings['stKey'];
	
			$.ajax(
			{
				url:TSUESettings['website_url']+'/ajax/comments.php',
				data: buildQuery,
				success: function(serverResponse)
				{
					if(serverResponse)
					{
						$this.html($thisHtml);
						$('<div id="sServerResponse">'+$.TSUE.clearresponse(serverResponse)+'</div>').insertBefore('#comments_post_form');
						$.TSUE.autoRemoveBodyDIV('#sServerResponse');
					}
					else
					{
						$.TSUE.alert(TSUEPhrases['message_deleted']);
						$replyDiv.fadeOut('slow');
					}
				}
			});
		}
	});
});

/* 
	Reply to Profile Comment
*/
$(document).on('click', '#reply_comment', function(e)
{
	e.preventDefault();
	var $this = $(this), $comment_id = parseInt($this.attr('rel')), $content_id = parseInt($this.attr('content_id')), $content_type = $this.attr('content_type');

	if(!$comment_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=get_editor_for_comment_reply&content_type='+$.TSUE.urlEncode($content_type)+'&comment_id='+parseInt($comment_id)+'&securitytoken='+TSUESettings['stKey'];

	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/comments.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.dialog($.TSUE.removeSpaces(serverResponse));

				$('#cancel_editor_message_'+$comment_id).click(function(e)
				{
					$.TSUE.closedialog();
					e.preventDefault();
				});

				$('#save_editor_message_'+$comment_id).click(function(e)
				{
					e.preventDefault();
					var currentMessage = tinyMCE.activeEditor.getContent();
					if(!currentMessage)
					{
						$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
						return false;
					}

					$.TSUE.insertLoaderAfter($.TSUE.getOverlayDiv().find('input[type="button"]').last());
					TSUESettings['closeOverlayWhileAjax'] = false;

					buildQuery = 'action=post_comment&do=reply&message='+$.TSUE.urlEncode(currentMessage)+'&content_type='+$.TSUE.urlEncode($content_type)+'&comment_id='+parseInt($comment_id)+'&content_id='+parseInt($content_id)+'&securitytoken='+TSUESettings['stKey'];
					
					$.ajax(
					{
						url:TSUESettings['website_url']+'/ajax/comments.php',
						data: buildQuery,
						success: function(serverResponse)
						{
							if(!$.TSUE.findresponsecode(serverResponse))
							{
								$.TSUE.closedialog();
								$.TSUE.alert(TSUEPhrases['message_posted']);
								serverResponse = $.TSUE.clearresponse(serverResponse);
								$(serverResponse).appendTo($('#posted_reply_message_holder_'+$comment_id)).fadeIn('slow', function()
								{
									$.TSUE.autoScroller($('#posted_reply_message_holder_'+$comment_id), '#posted_reply_message_holder_'+$comment_id);
								});
							}
							else
							{
								$(serverResponse).prependTo($.TSUE.findOverlayTextDiv());
							}
						}
					});
				});
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}		
	});
});

/* 
	Edit Comment
*/
$(document).on('click', '#edit_comment', function(e)
{
	e.preventDefault();
	var $this = $(this), $comment_id = parseInt($this.attr('rel')), $content_type = $this.attr('content_type');

	if(!$comment_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=get_comment_for_edit&comment_id='+parseInt($comment_id)+'&content_type='+$.TSUE.urlEncode($content_type)+'&securitytoken='+TSUESettings['stKey'];

	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/comments.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$.TSUE.dialog($.TSUE.removeSpaces(serverResponse));

				$('#cancel_editor_message_'+$comment_id).click(function(e)
				{
					$.TSUE.closedialog();
					e.preventDefault();
				});

				$('#save_editor_message_'+$comment_id).click(function(e)
				{
					handleEditedComment($comment_id, $content_type);
					e.preventDefault();
				});
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}		
	});
});
//Handle Edited Post.
function handleEditedComment($comment_id, $content_type)
{
	var currentMessage = tinyMCE.activeEditor.getContent(), $commentDiv = $('#posted_message_'+$comment_id)
	
	//remove last and first..<p>&nbsp;</p>
	currentMessage = $.trim(currentMessage);
	var SearchIN = currentMessage.substr(-13);
	if(SearchIN == '<p>&nbsp;</p>')
	{
		strLenCM = currentMessage.length;
		currentMessage = currentMessage.slice(0, strLenCM-13);
	}

	SearchIN = currentMessage.substr(13);
	if(SearchIN == '<p>&nbsp;</p>')
	{
		currentMessage = currentMessage.slice(0, 13);
	}

	if(!currentMessage)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}
	else
	{
		$.TSUE.insertLoaderAfter($.TSUE.getOverlayDiv().find('input[type="button"]').last());

		TSUESettings['closeOverlayWhileAjax'] = false;
		buildQuery = 'action=update_comment&comment_id='+parseInt($comment_id)+'&content_type='+$.TSUE.urlEncode($content_type)+'&message='+$.TSUE.urlEncode(currentMessage)+'&securitytoken='+TSUESettings['stKey'];
		$.ajax(
		{
			url:TSUESettings['website_url']+'/ajax/comments.php',
			data:buildQuery,
			success: function(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					$.TSUE.closedialog();
					$.TSUE.alert(TSUEPhrases['message_saved']);
					$commentDiv.hide().html(serverResponse).fadeIn('slow', function()
					{
						$.TSUE.autoScroller($commentDiv, $commentDiv);
					});
				}
				else
				{
					$(serverResponse).prependTo($.TSUE.findOverlayTextDiv());
				}
			}		
		});
	}
}

//Show more comments
$(document).on('click', '#comments_show_more', function(e)
{
	$this = $(this);
	var $commentDetails = $this.attr('rel').split('|');//0: last comment id, 1: content_type, 2: content_id
	
	if($commentDetails.length != 3)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);
	
	buildQuery = 'action=comments_show_more&last_comment_id='+parseInt($commentDetails['0'])+'&content_type='+$.TSUE.urlEncode($commentDetails['1'])+'&content_id='+parseInt($commentDetails['2'])+'&securitytoken='+TSUESettings['stKey'];

	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/comments.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$(serverResponse).insertAfter($this);
				$this.remove();
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});
});

var commentsEditorLoaded = false;

$(document).on('click', '#postAComment', function(e)
{
	if(!commentsEditorLoaded)
	{
		commentsEditorLoaded = $(this);
		commentsEditorLoaded.val('');
		tinyMCE.execCommand('mceAddControl', false, 'postAComment');
		tinymce.execCommand('mceFocus', false, 'postAComment');
		$('.postACommentButtons').fadeIn('slow');
		tinyMCE.activeEditor.setContent('');
	}
});

$('ul.tabs').tabs('div.tabItems');