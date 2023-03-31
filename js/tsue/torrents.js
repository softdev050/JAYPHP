//Torrents
$(document).on('click', '#edit_torrent', function(e)
{	
	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.jumpInternal("?p=torrents&pid=10&action=edit&tid="+$tid);

	e.preventDefault();
});

$(document).on('click', '#refresh_imdb', function(e)
{
	var $this = $(this), $tid = parseInt($this.attr('content_id')), $thisSRC = $this.attr('src'), $li = $('#imdbContent');
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$('.tipsy').remove();
	$this.attr('src', TSUESettings['theme_dir']+'ajax/ajax_loading.gif');
	$li.html(TSUESettings['ajaxInsertLoaderAfter']+' '+TSUEPhrases['loading']);

	buildQuery = 'action=refresh_imdb&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$this.attr('src', $thisSRC);
			$li.html($.TSUE.clearresponse(serverResponse));
		}
	});

	e.preventDefault();
});

$(document).on('click', '#reseed_request', function(e)
{
	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=reseed_request&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});

	e.preventDefault();
});

$(document).on('click', '#torrent_trailer',  function(e)
{
	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=torrent_trailer&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});

	e.preventDefault();
});

$(document).on('click', '#torrent_nfo', function(e)
{
	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	var $w = $(window), $wW = $w.width(), $wH = $w.height();

	$('<div id="NFODivMain" style="position: fixed; top: 0; left: 0; width: '+$wW+'px; height: '+$wH+'px; border: 0; z-index: 10000000000000; background: white; text-align: center; overflow: auto; -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=80)";filter: alpha(opacity=80);-moz-opacity: 0.8;-khtml-opacity: 0.8;opacity: 0.8; cursor: pointer"><div style="position: fixed; left: 15px; top: 15px; cursor: pointer; width: 24px; height: 24px;"><img src="'+TSUESettings['theme_dir']+'overlay/close.png" alt="" title="" border="0" /></div><img src="'+TSUESettings['website_url']+'/ajax/torrent_nfo.php?action=view_nfo&tid='+$tid+'&securitytoken='+TSUESettings['stKey']+'" alt="" title="" border="0" style="max-width: '+($wW-100)+'px" /></div>').prependTo('body').click(function()
	{
		$('#NFODivMain').remove();
		$('html,body').css('overflow', 'auto');
	});

	$('html,body').css('overflow', 'hidden');

	e.preventDefault();
});

$(document).on('click', '#delete_torrent', function(e)
{
	e.preventDefault();

	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=delete_torrent&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#deleteTorrentForm').submit(function(e)
			{
				e.preventDefault();
				
				var $this=$(this), $reason=$('textarea[name="reason"]', $this).val();
				if(!$reason)
				{
					$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
					return false;
				}

				buildQuery = 'action=delete_torrent&reason='+$.TSUE.urlEncode($reason)+'&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/torrents.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							$('#torrent_'+$tid).fadeOut('slow');
							if(TSUESettings['currentActiveURL'].match(/action=details/ig))
							{
								$.TSUE.jumpInternal("?p=torrents&pid=10");
							}
						}

						$.TSUE.closedialog();
						$.TSUE.alert($.TSUE.strip_tags(serverResponse));
					}
				});
				
				return false;
			});
		}
	});

	return falase;
});

$(document).on('click', '#bump_torrent', function(e)
{
	e.preventDefault();

	var $tid = parseInt($(this).attr('content_id')), $this = $(this);
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=bump_torrent&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$this.remove();
				$.TSUE.alert(serverResponse)
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});

	return false;
});

$(document).on('click', '#nuke_torrent', function(e)
{
	e.preventDefault();

	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=nuke_torrent&do=form&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			$('#nukeTorrentForm').submit(function(e)
			{
				e.preventDefault();

				var $reason = $('#reason', this).val();
				if(!$reason)
				{
					$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
					return false;
				}

				buildQuery = 'action=nuke_torrent&reason='+$.TSUE.urlEncode($reason)+'&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/torrents.php',
					data:buildQuery,
					success: function(serverResponse)
					{
						$.TSUE.closedialog();
						serverResponse = serverResponse.split('~~~');
						$.TSUE.alert(serverResponse['0']);
						$(serverResponse['1']).insertAfter('[rel="nuke_torrent'+$tid+'"]');
						$('[rel="nuke_torrent'+$tid+'"]').remove();
					}
				});

				return false;
			});
		}
	});

	return false;
});

$(document).on('click', '#unnuke_torrent', function(e)
{
	e.preventDefault();

	var $tid = parseInt($(this).attr('content_id'));
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	buildQuery = 'action=unnuke_torrent&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			serverResponse = serverResponse.split('~~~');
			$.TSUE.alert(serverResponse['0']);
			$(serverResponse['1']).insertAfter('[rel="unnuke_torrent'+$tid+'"]');
			$('[rel="unnuke_torrent'+$tid+'"]').remove();
		}
	});

	return false;
});

$(document).on('click', '#torrent_info', function(e)
{
	var $tid = $(this).attr('content_id'),
	$html = $('#torrentDescription_'+$tid).html(),
	$header = $('#torrent_'+$tid+' .torrentName').html();

	$.TSUE.dialog($html, $header);
	
	$('#overlay .spoiler_click').click(function(e)
	{
		e.preventDefault();
		$.TSUE.spoiler(this);
		return false;
	});

	e.preventDefault();
});

$('[rel="torrent_seeders"],[rel="torrent_leechers"],[rel="torrent_size"],[rel="times_completed"]').click(function(e)
{
	var $tid = parseInt($(this).attr('id'));
	
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	var $header = $('#torrent_'+$tid+' .torrentName').html(), $action = $(this).attr('rel');

	$(document).on('click', '#sortpeerlist', function(e)
	{
		e.preventDefault();

		var $requestedSortBy = $('#requestedSortBy').val(), $requestedSortOrder = $('#requestedSortOrder').val();
		
		$.TSUE.insertLoaderAfter(this);

		buildQuery = 'action='+$.TSUE.urlEncode($action)+'&requestedSortBy='+$.TSUE.urlEncode($requestedSortBy)+'&requestedSortOrder='+$.TSUE.urlEncode($requestedSortOrder)+'&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
		$.ajax(
		{
			url:TSUESettings['website_url']+'/ajax/torrents.php',
			data:buildQuery,
			success: function(serverResponse)
			{
				$.TSUE.findOverlayTextDiv().html(serverResponse);
				if($action == 'torrent_seeders' || $action == 'torrent_leechers')
				{
					$('.overlay_text ul.tabs').tabs('div.panes > div');
				}
			}
		});

		return false;
	});

	buildQuery = 'action='+$.TSUE.urlEncode($action)+'&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse, $header);

			if($action == 'torrent_seeders' || $action == 'torrent_leechers')
			{
				$('.overlay_text ul.tabs').tabs('div.panes > div');
			}
		}
	});
	e.preventDefault();
});

$('#torrents_select_category').click(function(e)
{
	var $search = $('#torrent_categories .text').find('table');
	if($search.length)
	{
		$('#torrent_categories .text').empty();
		$('#torrent_categories').hide();
		return;
	}

	$.TSUE.appendLoaderAfter(this);

	buildQuery = 'action=categories_checkbox&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$(serverResponse).appendTo('#torrent_categories .text');
			$('#torrent_categories').fadeIn('slow');

			$('#torrent_categories input:checkbox').click(function()
			{
				var $this = $(this),
				$cid = $this.val(),
				$isChecked = $this.is(':checked'),
				$rel = $this.attr('rel');

				$('[rel="category_'+$cid+'"]').each(function()
				{
					$_cid = $(this).val();
					if($rel == 'main' && $isChecked && !this.checked)
					{
						this.checked = true;
					}
					else
					{
						this.checked = false;
					}
				});
			});
		}
	});

	e.preventDefault();
});

$('#form_search_torrent').submit(function()
{
	$.TSUE.insertLoaderAfter('input[type="reset"]');
});

$(document).on('click', '[rel=delete_torrent_image]', function(e)
{
	var $attachment_id = parseInt($(this).attr('id'));
	if(!$attachment_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$this = $(this);

	$.TSUE.confirmAction(TSUEPhrases['confirm_delete_message_global'], function(yes)
	{
		if(yes)
		{
			buildQuery = 'action=delete_torrent_image&attachment_id='+$attachment_id+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/torrents.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$this.next().remove();
						$this.remove();
					}
					else
					{
						var $pto = '#upload_torrent';
						var $autoRemoveHTML = $(serverResponse).prependTo($pto);
						$.TSUE.autoRemoveBodyDIV($autoRemoveHTML);
					}
				}
			});
		}
	});

	e.preventDefault();
});

$('li.similar_torrents').click(function(e)
{
	e.preventDefault();

	var $similarTorrents = $('#similar_torrents'), $tid = parseInt($similarTorrents.attr('rel'));

	if($similarTorrents.html())
	{
		return;
	}
	
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$similarTorrents.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=similar_torrents&tid='+parseInt($tid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$similarTorrents.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});

$('li.subtitles').click(function(e)
{
	e.preventDefault();

	var $Subtitles = $('#subtitles'), $tid = parseInt($Subtitles.attr('rel'));

	if($Subtitles.html())
	{
		return;
	}
	
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$Subtitles.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=subtitles&tid='+parseInt($tid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$Subtitles.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});

$('li.files').click(function(e)
{
	e.preventDefault();

	var $Files = $('#files'), $tid = parseInt($Files.attr('rel'));

	if($Files.html())
	{
		return;
	}
	
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$Files.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=torrent_size&tid='+parseInt($tid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$Files.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});

$('li.screenshots').click(function(e)
{
	e.preventDefault();

	var $screenshots = $('#screenshots'), $tid = parseInt($screenshots.attr('rel'));

	if($screenshots.html())
	{
		return;
	}
	
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$screenshots.html(TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['loading']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=screenshots&tid='+parseInt($tid)+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$screenshots.html($.TSUE.clearresponse(serverResponse));
		}
	});

	return false;
});

$('a[rel="tags"],input[rel="tags"]').click(function(e)
{
	e.preventDefault();
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=show_all_tags&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});

	return false;
});

$(document).ready(function()
{
	var $tabCount = 1, $liCount = 1, $removeDivs = new Array, $searchForm = $('form[name="form_search_torrent"]'), $searchFormDIV = $('#search_torrent'), $searchFormKWInsert = $('input[name="keywords"]', $searchForm), $autoSuggestionEnabled=false, $formExposed=false, $isAlreadyBeingSearched=false, $searchFormKWInsertWidth = $searchFormKWInsert.width();

	if($('#content .tabItems').length)
	{
		$('#content .tabItems').each(function()
		{
			var $tab = $(this);

			if(!$tab.html() && !$tab.attr('rel'))
			{
				$removeDivs[$tabCount] = 1;
				$tab.remove();
			}

			$tabCount++;
		});
	}

	if($('ul[class="tabs"] li').length)
	{
		$('ul[class="tabs"] li').each(function()
		{
			var $li = $(this);

			if($removeDivs[$liCount])
			{
				$li.remove();
			}

			$liCount++;
		});
	}

	if($('#content').find('#upload_a_file').length)
	{
		var $fuElement = $('#upload_a_file');

		$fuElement.fineUploader($.TSUE.buildFUOptions($fuElement, TSUEUpload['upload_files'])).on('complete', function(event, id, fileName, responseJSON)
		{
			if(((-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '') == 'torrent' && $('input[name="name"]').length)
			{
				$('input[name="name"]').val(fileName.slice(0, -8));
			}

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

	if($('#content').find('#upload_screenshot').length)
	{
		var $fuElement = $('#upload_screenshot'), $extendOptions = 
		{
			text:
			{
				uploadButton: TSUEUpload['upload_screenshots']
			}
		};

		$fuElement.fineUploader($.TSUE.buildFUOptions($fuElement, TSUEUpload['upload_files'], $extendOptions)).on('complete', function(event, id, fileName, responseJSON)
		{
			if(responseJSON.success)
			{						
				$attachment_id = parseInt(responseJSON.success);
				if($attachment_id)
				{
					$('<input type="hidden" name="ss_attachment_ids[]" value="'+$attachment_id+'">').appendTo('#ajax_attachments');
				}
			}
		});
	}

	$('#upload_torrent').submit(function(e)
	{
		e.preventDefault();
		return false;
	});

	$('#finish_upload').click(function(e)
	{
		e.preventDefault();

		var $responseDIV = $('#upload_torrent #response'), $responseText = $('#upload_torrent #response .responseText');		
		var uploadForm = $('#upload_torrent'), serializeForm = $(uploadForm).serialize(), uploadStep = 0, buildQuery = '';

		if($('#ifISeditTorrent').html() != '')//Edit?
		{
			var tid = parseInt($('#editTID').val());
		}
		else
		{
			var tid = 0;
		}

		function beginUpload()
		{
			uploadStep++;
			buildQuery = 'action=upload_torrent&'+serializeForm+'&tid='+tid+'&rDescription='+$.TSUE.urlEncode(tinyMCE.activeEditor.getContent())+'&uploadStep='+uploadStep+'&securitytoken='+TSUESettings['stKey'];
			TSUESettings['showLoaderWhileAjax']= false;
			$('#uploadError').remove();

			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/torrents.php',
				data:buildQuery,
				timeout: 3600000,//60 minutes for big torrents.
				error: function(x, t, m)
				{
					alert(t)
				},
				success: function(serverResponse)
				{
					TSUESettings['showLoaderWhileAjax']= true;

					if(serverResponse)
					{
						if($.TSUE.findresponsecode(serverResponse))//Error in Response?
						{
							$.mask.close();
							$responseDIV.hide();
							$('<div id="uploadError">'+$.TSUE.clearresponse(serverResponse)+'</div>').appendTo('#upload_torrent');
							return false;
						}
						else if(serverResponse.match(/~moderationmessage~/))//torrent upload and awaiting moderation?
						{
							$.mask.close();
							$('<div class="information">'+$.TSUE.clearresponse(serverResponse.replace(/~moderationmessage~/, ''))+'</div>').insertBefore(uploadForm);
							uploadForm.remove();
							return false;
						}
						else//Response is ok.
						{
							if(serverResponse.match(/~tid~/))//torrent was created and we have tid in response?
							{
								tid = parseInt(serverResponse.replace(/~tid~/, ''));
							}
							else//Normal response, lets show it.
							{
								$responseText.html(serverResponse);
							}
							beginUpload();
						}
					}
					else//Finished
					{
						$responseDIV.html(TSUEPhrases['loading']);
						$.TSUE.jumpInternal("?p=torrents&pid=10&action=details&tid="+tid);
						return false;
					}
				}
			});
		};

		$responseText.html(TSUEPhrases['loading']);
		$responseDIV.show().expose({closeOnClick: false, closeOnEsc: false, loadSpeed: 'fast'});
		beginUpload();
		return false;
	});

	$searchFormKWInsert.focus(function(e)
	{
		if($formExposed)
		{
			return;
		}

		$formExposed = true;

		if($autoSuggestionEnabled)
		{
			$('input[type="submit"],input[type="button"]', $searchForm).hide();
		}

		$searchFormKWInsert.val('');

		function getSuggestionLink()
		{
			return '<div id="autoSuggestionOnOff" stlye="padding-top: 9px; font-size: 11px;"><span class="clickable">'+TSUEPhrases['turn_o'+($autoSuggestionEnabled ? 'ff' : 'n')+'_suggestions']+'</span> <img src="'+TSUESettings['theme_dir']+'status/information.png" alt="'+TSUEPhrases['turn_on_suggestions_alt']+'" title="'+TSUEPhrases['turn_on_suggestions_alt']+'" class="middle" id="suggestionInfo" /></div>';
		}

		$searchFormDIV.expose({closeOnClick: false, closeOnEsc: false, loadSpeed: 'fast'});
		
		$('#Alfabe,input[rel="tags"]').hide();
		
		$('<div id="closeSearcher"><img src="'+TSUESettings['theme_dir']+'overlay/close.png" alt="" title="" /></div>').prependTo($searchFormDIV);

		$(getSuggestionLink()).appendTo($searchForm).click(function(e)
		{
			e.preventDefault();

			if($autoSuggestionEnabled)
			{
				$autoSuggestionEnabled = false;
				$('#autoSearchTorrentResult').remove();
				$('input[rel!="tags"]', $searchForm).show();
				$searchFormKWInsert.width($searchFormKWInsertWidth);
				$searchFormKWInsert.focus();
			}
			else
			{
				$autoSuggestionEnabled = true;
				$('input[type="submit"],input[type="button"]', $searchForm).hide();
				$searchFormKWInsert.val('').focus();
			}

			$(this).html(getSuggestionLink());			
			
			return false;
		});
		
		$('#closeSearcher').click(function(e)
		{
			e.preventDefault();
			
			$.mask.close();
			$('#Alfabe').show();
			$('input', $searchForm).show();
			$('#closeSearcher,#autoSuggestionOnOff,#autoSearchTorrentResult').remove();
			$searchFormKWInsert.val('');
			$formExposed=false;
			
			return false;
		});

		$('#suggestionInfo').tipsy({trigger: 'hover', gravity: 'w', html: true});
	})
	.keyup(function(e)
	{
		if($autoSuggestionEnabled && !TSUESettings['isAjaxRunning'])
		{
			var $keywords = $searchFormKWInsert.val(), $search_type = $('select[name="search_type"]').val();
			if($keywords.length >= 3 && !$isAlreadyBeingSearched && $search_type)
			{
				$isAlreadyBeingSearched=true;
				TSUESettings['showLoaderWhileAjax']=false, TSUESettings['disableButtonsWhileAjax']=false;
				
				$('#autoSearchTorrentResult').remove();
				$('<div id="autoSearchTorrentResult" class="torrent-box">'+TSUESettings['ajaxLoaderImage']+' '+TSUEPhrases['searching']+'</div>').appendTo($searchFormDIV);

				$.ajax
				({
					data: 'action=search_torrent&keywords='+$.TSUE.urlEncode($keywords)+'&search_type='+$search_type+'&securitytoken='+TSUESettings['stKey'],
					success: function(result)
					{
						if(!result)
						{
							result = '<div class="error">'+TSUEPhrases['message_nothing_found']+'</div>';
						}

						$('#autoSearchTorrentResult').html(result);

						$isAlreadyBeingSearched=false;
						TSUESettings['showLoaderWhileAjax']=true, TSUESettings['disableButtonsWhileAjax']=true;
					}
				});
			}
		}
	})
	.attr('autocomplete', 'off');
});

$(document).on('click', '#bookmark_torrent', function(e)
{	
	var $tid = parseInt($(this).attr('rel')), $this = $(this);
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=bookmarks&do=add&tid='+$tid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$this.remove();
				$.TSUE.alert(serverResponse)
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});

	e.preventDefault();
});

$(document).on('click', '#remove_bookmark', function(e)
{	
	var $tid = parseInt($(this).attr('rel')), $this = $(this);
	if(!$tid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/torrents.php',
		data: 'action=bookmarks&do=remove&tid='+$tid+'&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$this.remove();
				$.TSUE.alert(serverResponse)
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});

	e.preventDefault();
});

$(document).on('click', '#setAutoMultipliers', function(e)
{
	var $type = $(this).attr('rel');
	switch($type)
	{
		case 'free':
			if(!$('#download_multiplier').is(':disabled'))
				$('#download_multiplier').val('0');
		break;

		case 'silver':
			if(!$('#download_multiplier').is(':disabled'))
				$('#download_multiplier').val('0.5');
		break;

		case 'x2':
			if(!$('#upload_multiplier').is(':disabled'))
				$('#upload_multiplier').val('2');
		break;
	}
});

$(document).on('change', 'select[name="cid"]', function(e)
{
	var $cid = $(this).val();
	if($cid)
	{
		$.ajax
		({
			url:TSUESettings['website_url']+'/ajax/torrents.php',
			data: 'action=show_genres&cid='+$cid+'&securitytoken='+TSUESettings['stKey'],
			success: function(serverResponse)
			{
				$('#showAvailableGenres').html($.TSUE.clearresponse(serverResponse));
			}
		});
	}
	else
	{
		$('#showAvailableGenres').empty();
	}
});

$(function()
{
	if($('form[id="upload_torrent"]').length)
	{
		$('input:disabled', $('form[id="upload_torrent"]')).each(function()
		{
			var $workWidth=$('label[for="'+$(this).attr('name')+'"]');
			$workWidth.siblings('div').remove();
			$workWidth.remove();
		});
	}
});