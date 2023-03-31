/*
	TSUE Script - Coded by Xam - Last Updated:  01-07-2013 15:38PM
*/
var buildQuery = '', uAgent = navigator.userAgent.toLowerCase(), $currentRecentTorrentListPN = 1, docTitleInterval = false;

$.TSUE =
{
	init: function()
	{
		if(TSUESettings['website_active'] == 0){$('<div id="websiteClosed">'+TSUEPhrases['website_active_admin_alert']+'</div>').prependTo('body')}
		$.TSUE.overlayCache = null, $.TSUE.memberInfoCache = [], $.TSUE.overlayCount = 0;
		var globalSettings =
		{
			isAutoCompleteRunning: false, isAjaxRunning: false, disableButtonsWhileAjax: true, closeOverlayWhileAjax: true, showLoaderWhileAjax: true, autoResizeImages: true, currentActiveURL: location.href, currentDocumentTitle: document.title,
			overlayDiv: '<div id="overlay"><div class="overlay_header"><div class="close"></div>-HEADER-</div><div class="overlay_text">-MESSAGE-</div><div class="overlay_footer"></div></div>',
			ajaxHolder: '<div id="'+TSUESettings['ajaxHolderID']+'"><img src="'+TSUESettings['theme_dir']+'ajax/ajax_loading.gif" alt="" title="" /> '+TSUEPhrases['loading']+'</div>',
			ajaxInsertLoaderAfter: '<span id="insertLoaderAfter">'+TSUESettings['ajaxLoaderImage']+'</span>',
			ajaxInsertLoaderBefore: '<span id="insertLoaderBefore">'+TSUESettings['ajaxLoaderImage']+'</span>',
			tooltipPosition: 'center right', tooltipEffect: 'fade', tooltipDelay: 0, toggleEffect: 'easeOutBounce', toggleSpeed: 1000, maskColor: '#ccc', maskSpeed: 200, maskOpacity: 0.7, checkAlertsTimeout: 60000, fetchNewRepliesTimeout: 30000, alertsCache: false, messagesChecksCache: false,
		};

		TSUESettings = $.extend(TSUESettings, globalSettings);

		if($('textarea[id="tinymce_autoload"]').length)
		{
			$.TSUE.etoggle($('textarea[id="tinymce_autoload"]'), 1, function()
			{
				tinyMCE.execCommand('mceAddControl', false, 'tinymce_autoload');
				tinymce.execCommand('mceFocus', false, 'tinymce_autoload');
			});
		}
		
		$.ajaxSetup({type:'POST', url:TSUESettings['website_url']+'/ajax.php', dataType: 'html', contentType: 'application/x-www-form-urlencoded; charset='+TSUESettings['charset'], encoding: TSUESettings['charset'], cache: false, timeout: 25000});
		$(document).ajaxStart(function(){TSUESettings['isAjaxRunning'] = true;$.TSUE.astarted()}).ajaxComplete(function(){TSUESettings['isAjaxRunning'] = false;$.TSUE.acompleted()});

		$.TSUE._timeAgo();
		$.TSUE.clickableLinks();
		$.TSUE.previewButton();
		$.TSUE.autoExpand();
		$.TSUE.initializeTooltip();
		$.TSUE.initializeFancyBOX();
		$.TSUE.scrollInfoBoxes();
		$.TSUE.initDropDownMenu();
		$.TSUE.initGlobalSearch();
		$.TSUE.initAutoDescription();
		$.TSUE.initFilterSearch();

		if(!$.TSUE.isGuest())
		{
			//Auto check for New Alerts
			if(TSUESettings['alerts_enabled'] == 1)
			{
				$(window).load(function()
				{
					$.TSUE.SetcheckAlerts = window.setInterval("$.TSUE.checkAlerts()", TSUESettings['checkAlertsTimeout']);
				});
			}

			//Auto update DST
			var tzOffset = TSUESettings['memberTimezone'] + TSUESettings['memberDST'], utcOffset = new Date().getTimezoneOffset() / 60;
			if(Math.abs(tzOffset + utcOffset) == 1)
			{
				$.ajax({data:'action=update_dst&securitytoken='+TSUESettings['stKey']});
			}
		}

		$('.torrent-box,.comment-box').hover(function(){$(this).addClass('box-hover');},function(){$(this).removeClass('box-hover');});
		$.tools.dateinput.localize('tsue', {months: TSUEPhrases['months'], shortMonths: TSUEPhrases['shortMonths'], days: TSUEPhrases['days'], shortDays: TSUEPhrases['shortDays']});
		$('input[type="date"]').dateinput({lang: 'tsue', format: 'dd/mm/yyyy', selectors: true, yearRange:[-70, 1]}).attr('autocomplete', 'off');
	},

	autoScrollTo: function()
	{
		var $scrollHash = TSUESettings['currentActiveURL'].match(/scrollTo=\[(.*)\]/);
		if($scrollHash && $scrollHash[1])
		{
			$.TSUE.autoScroller('#'+$scrollHash[1], $scrollHash[1]);
		}
	},

	buildFUOptions: function($fuElement, $uploadButtonText, $extendOptions)
	{
		var $defaultOptions = 
		{
			validation:
			{
				allowedExtensions: TSUEUpload['allowed_file_types'].split(','),
				sizeLimit: TSUEUpload['max_file_size']
			},
			text:
			{
				uploadButton: $uploadButtonText,
				cancelButton: TSUEPhrases['button_cancel'],
				retryButton: '<img src="'+TSUESettings['theme_dir']+'fileuploader/retry.png" alt="" border="0" class="middle" width="16" />',
				failUpload: '<img src="'+TSUESettings['theme_dir']+'fileuploader/error.png" alt="" border="0" class="middle" width="16" /> ',
				dragZone: '<img src="'+TSUESettings['theme_dir']+'fileuploader/add_file.png" alt="" border="0" class="middle" width="16" />',
				dropProcessing: TSUEUpload['loading'],
				formatProgress: "{percent}% {total_size}",
				waitingForResponse: TSUEUpload['loading']
			},
			failedUploadTextDisplay:
			{
				mode: 'custom',
				maxChars: 40,
				responseProperty: 'error',
				enableTooltip: true
			},
			showMessage: function(message)
			{
				$.TSUE.alert(message);
			},
			request:
			{
				endpoint: TSUESettings['website_url']+'/ajax/upload_file.php',
				params:
				{
					action: 'upload_file',
					content_type: $.TSUE.urlEncode($fuElement.attr('content_type')),
					forumid: parseInt($fuElement.attr('forumid')),
					securitytoken: TSUESettings['stKey']
				}
			}
		};

		if($extendOptions)
		{
			$defaultOptions = $.extend($defaultOptions, $extendOptions);
		}
		
		return $defaultOptions;
	},

	loadCaptcha: function()
	{
		if($('#recaptcha_widget').length)
		{
			$.getScript('http'+(document.location.protocol == 'https:' ? 's' : '')+'://www.google.com/recaptcha/api/js/recaptcha_ajax.js', function()
			{
				window.Recaptcha.create('6LdWBMgSAAAAAFwwI11YPq7c9p9SUpTJBsy9wAhA',
				'recaptcha_widget',
				{
					theme: 'custom',custom_theme_widget: 'recaptcha_widget', callback: function()
					{
						$('#recaptcha_loading').fadeOut('slow', function()
						{
							$('#recaptcha_widget').fadeIn('slow');
						});
					}
				});
			});
		}
	},

	initAutoDescription: function()
	{
		var $autoDescription = $('#autoDescription');
		if($autoDescription.length)
		{
			$('select[name="autoDescription"]', $autoDescription).on('change', function(e)
			{
				var $this = $(this), $field_id = parseInt($this.val());
				if($field_id)
				{
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax.php',
						data:'action=autoDescription&field_id='+$field_id+'&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							tinyMCE.activeEditor.setContent($.TSUE.clearresponse(serverResponse));
						}
					});
				}
			});
		}
	},

	initFacebook: function()
	{
		var $facebookDIV = $('#facebookRecommend');
		if($facebookDIV.length)
		{
			$facebookDIV.css('width', '163px');
		}
	},

	//remove item (string or number) from an array
	removeItem: function(originalArray, itemToRemove)
	{
		var j = 0;
		while (j < originalArray.length)
		{
			if (originalArray[j] == itemToRemove)
			{
				originalArray.splice(j, 1);
			}
			else
			{
				j++;
			}
		}
		return originalArray;
	},

	initGlobalSearch: function()
	{
		var $BOX = $('#boxContainer'), $searchForm = $('#globalSearchForm'), $searchType, $searchKeywords;

		function removeSearchError()//Remove any error message if exists.
		{
			$('#globalSearchError').remove();//Remove error message if exists.
			$BOX.find('.error').remove();
		}

		function closeBox()
		{
			$BOX.slideUp('slow');//Hide BOX
			$.mask.close();//Remove Mask
		}

		function createSubmitForm(query, fields)
		{
			$('<form method="post" action="'+TSUESettings['website_url']+'/'+query+'" id="newSearchForm">'+fields+'</form>').appendTo('body').submit();
		}

		//Search ICON Fired..
		$('#globalSearchButton').click(function(e)
		{
			e.preventDefault();
			removeSearchError();//Remove any error message if exists.

			if($BOX.is(':visible'))//Box is visible, lets toggle it to hide.
			{
				closeBox();
			}
			else
			{
				$BOX.slideDown('slow').expose({closeOnClick: false, closeOnEsc: false});//Show and Add Max.
			}

			return false;
		});

		//Cancel Button, Remove BOX
		$('input[name="cancel"]').click(function(e){e.preventDefault();closeBox();return false});

		//Handle Search Type
		$('select[name="searchType"]', $searchForm).change(function()
		{
			removeSearchError();//Remove any error message if exists.
			$searchType = parseInt($(this).val());
			
			$('#torrentCategoriesCheckboxes,#forumCategoriesCheckboxes').remove();//Remove Torrent Categories Checkbox Table.

			switch($searchType)
			{
				case 1://Lets Show Searchable Forums
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/forums.php',
						data:'action=forum_list_selectbox&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							$('<div id="forumCategoriesCheckboxes">'+$.TSUE.clearresponse(serverResponse)+'</div>').prependTo($searchForm);
						}
					});
				break;

				case 2://Lets show Torrent Category Checkboxes
					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/torrents.php',
						data:'action=categories_checkbox&skipSubmitButtons=1&securitytoken='+TSUESettings['stKey'],
						success: function(serverResponse)
						{
							$(serverResponse).appendTo($searchForm);
						}
					});
				break;
			}
			return false;
		});

		//Clicked on Form Submit..
		$searchForm.submit(function(e)
		{
			e.preventDefault();
			removeSearchError();//Remove any error message if exists.
			
			$searchKeywords = $('input[name="searchKeywords"]', $searchForm).val();//Search Keywords
			$searchType = parseInt($('select[name="searchType"]', $searchForm).val());//Search Type

			if(!$searchKeywords || !$searchType)//Check Required Fields..
			{
				$('<div class="error" id="globalSearchError">'+TSUEPhrases['message_required_fields_error']+'</div>').appendTo($BOX);
			}
			else
			{
				switch($searchType)
				{
					case 1://Search in Forums
						var $fids = $('select[name="forums[]"]', $searchForm).val();

						$.ajax
						({
							url:TSUESettings['website_url']+'/ajax/forums.php',
							data:'action=forums_search&keywords='+$.TSUE.urlEncode($searchKeywords)+'&title_only=0&membername=&newer_than=&forums='+$fids+'&securitytoken='+TSUESettings['stKey'],
							success: function(serverResponse)
							{
								if(!$.TSUE.findresponsecode(serverResponse))
								{
									$searchid = parseInt(serverResponse);
									$.TSUE.jumpInternal('?p=forums&pid=11&action=search_forums&searchid='+$searchid)
								}
								else
								{
									$(serverResponse).appendTo($searchForm);
								}
							}
						});
					break;

					case 2://Search in Torrents
						var $cids = [];
						$searchForm.find('input:checkbox').each(function()
						{
							if(this.checked)
							{
								$cids.push($(this).val());
							}
						});

						createSubmitForm('?p=torrents&pid=10', '<input type="hidden" name="keywords" value="'+$.TSUE.urlEncode($searchKeywords)+'"><input type="hidden" name="search_type" value="name"><input type="hidden" name="cid" value="'+$cids+'">');
					break;

					case 3://Downloads
						createSubmitForm('?p=downloads&pid=300', '<input type="hidden" name="keywords" value="'+$.TSUE.urlEncode($searchKeywords)+'">');
					break;

					case 4://FAQ
						createSubmitForm('?p=faq&pid=12', '<input type="hidden" name="action" value="search"><input type="hidden" name="keywords" value="'+$.TSUE.urlEncode($searchKeywords)+'">');
					break;

					case 5://Members
						createSubmitForm('?p=members&pid=13', '<input type="hidden" name="do" value="search"><input type="hidden" name="membername" value="'+$.TSUE.urlEncode($searchKeywords)+'">');
					break;

					case 6://Subtitles
						createSubmitForm('?p=subtitles&pid=30', '<input type="hidden" name="action" value="search"><input type="hidden" name="keywords" value="'+$.TSUE.urlEncode($searchKeywords)+'">');
					break;
				}
			}

			return false;
		});
	},

	initDropDownMenu: function()
	{
		$('.dropdown dt a').on('click', function(e)
		{
			e.preventDefault();
			$('.dropdown dd ul').hide();
			$(this).parents('.dropdown').find('ul').fadeIn('fast');
			return false;
		});
		$(document).bind('click', function(e)
		{
			if (!$(e.target).parents().hasClass('dropdown'))
			{
				$('.dropdown dd ul').hide();
			}
		});
	},

	initFilterSearch: function()
	{
		//JS Search filter.
		$('#filterText').keyup(function()
		{
			var $this = $(this), $rows = $($this.data('id')+' '+$this.data('target'));
			var val = '^(?=.*\\b' + $.trim($(this).val()).split(/\s+/).join('\\b)(?=.*\\b') + ').*$', reg = RegExp(val, 'i'), text;

			$rows.show().filter(function()
			{
				text = $(this).text().replace(/\s+/g, ' ');
				return !reg.test(text);
			}).hide();
		});
	},

	_timeAgo: function()
	{
		$('abbr.timeago').timeago();
	},

	// Check if string is a whole number(digits only).
	isNumber: function(s)
	{
		return String(s).search(/^\s*\d+\s*$/) != -1;
	},

	setCookie: function (c_name,value,exdays)
	{
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
		document.cookie=c_name + "=" + c_value;
	},
	
	spoiler: function(el)
	{
		var $this = $(el), $spoiler = $this.next('div');
		if($spoiler.is(':visible'))
		{
			$this.removeClass('spoiler_clicked').addClass('spoiler_click');
			$.TSUE.etoggle($spoiler, 0);
		}
		else
		{
			$this.removeClass('spoiler_click').addClass('spoiler_clicked');
			$.TSUE.etoggle($spoiler, 1);
		}
	},

	buildCurrentURL: function()
	{
		return TSUESettings['website_url']+'/?p='+TSUESettings['pagefile']+'&pid='+TSUESettings['pageid'];
	},

	clickableLinks: function()
	{
		$(document).on('click', '#toggle', function(e)
		{
			e.preventDefault();
			
			var $this = $(this), id = '#'+$this.attr('rel');

			if($(id).length)
			{
				if(id.match(/forumList/))
				{
					if($(id).is(':visible'))
					{
						$('tr[id='+id.replace(/#/, '')+']').hide();

						$this.attr('src', TSUESettings['theme_dir']+'forums/mix/bullet_toggle_plus.png');
						$.TSUE.setCookie('p_'+id, 'true', 15);
					}
					else
					{
						$('tr[id='+id.replace(/#/, '')+']').show();

						$this.attr('src', TSUESettings['theme_dir']+'forums/mix/bullet_toggle_minus.png');
						$.TSUE.setCookie('p_'+id, '', -1);
					}
				}
				else
				{
					if($(id).is(':visible'))
					{
						$.TSUE.etoggle(id, 0);
						$this.attr('src', TSUESettings['theme_dir']+'forums/mix/bullet_toggle_plus.png');
						$.TSUE.setCookie('p_'+id, 'true', 15);
					}
					else
					{
						$.TSUE.etoggle(id, 1);
						$this.attr('src', TSUESettings['theme_dir']+'forums/mix/bullet_toggle_minus.png');
						$.TSUE.setCookie('p_'+id, '', -1);
					}
				}
			}
			
			return false;
		});

		$(document).on('click', '#cancelRecentTorrentsSwitch', function(e)
		{
			e.preventDefault();
			$.TSUE.setCookie('tsue_rt_switch_list', '', -1);
			window.location = $.TSUE.buildCurrentURL()+'&scrollTo=[recentTorrentsHeader]';
			return false;
		});

		$(document).on('click', '#recentTorrentsSwitch', function(e)
		{
			e.preventDefault();
			$.TSUE.setCookie('tsue_rt_switch_list', 'true', 15);
			window.location = $.TSUE.buildCurrentURL()+'&scrollTo=[recentTorrentsHeader]';
			return false;
		});

		$(document).on('click', '#show_more_torrents', function(e)
		{
			e.preventDefault();
			$.TSUE.showMoreTorrents();
			return false;
		});		

		$(document).on('click', '#update_external_torrent', function(e)
		{
			e.preventDefault();

			var $this = $(this), $tid = parseInt($this.attr('alt')), $thisSRC = $this.attr('src');
			
			if(!$tid)
			{
				$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				return false;
			}

			$this.attr('src', TSUESettings['theme_dir']+'ajax/ajax_loading.gif');

			buildQuery = 'action=update_external_torrent&tid='+$tid+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				url:TSUESettings['website_url']+'/ajax/torrents.php',
				data:buildQuery,
				success: function(serverResponse)
				{
					if(serverResponse)
					{
						$.TSUE.dialog(serverResponse);
						$this.attr('src',$thisSRC);
					}
					else
					{
						$.TSUE.jumpInternal("?p=torrents&pid=10&action=details&tid="+$tid);
					}
				}
			});

			return false;
		});

		$(document).on('click', '#gotoLink', function(e)
		{
			e.preventDefault();
			
			var $gotoLink = $(this), $targetURL = $gotoLink.attr('href');
			
			if($targetURL)
			{
				$('<form id="gotoForm" action="'+$targetURL+'"><input type="text" class="s" style="width: 30px;" value="" name="gotoPageNumber" /> <input type="submit" class="submit" value="&raquo;" class="middle" /></form>').insertBefore($gotoLink);
				$gotoLink.remove();				
				$('input[name="gotoPageNumber"]').focus();
			}

			return false;
		});

		$(document).on('submit', '#gotoForm', function(e)
		{
			e.preventDefault();
			
			var $gotoForm = $(this), $pageNumber = parseInt($('input[name="gotoPageNumber"]', $gotoForm).val()), $targetURL = $gotoForm.attr('action');
			if(!$pageNumber || !$targetURL)
			{
				$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				return false;
			}
			else
			{
				window.location = $targetURL+'page='+$pageNumber;
			}

			return false;
		});

		//New Announcement
		$('#newAnnouncement').click(function(e)
		{
			e.preventDefault();
			
			$(this).animate
			(
				{height: 'toggle'},
				{
					complete: function()
					{
						$.ajax
						({
							url:TSUESettings['website_url']+'/ajax/read_announcement.php',
							data: 'action=read_announcement&securitytoken='+TSUESettings['stKey'],
							success: function(serverResponse)
							{
								$.TSUE.dialog(serverResponse);
							}
						});
					}
				}				
			);

			return false;
		});

		//Follow member
		$(document).on('click', '#follow_member', function(e)
		{
			e.preventDefault();

			$this = $(this), oldText = $this.html();
			$this.html(TSUESettings['ajaxLoaderImage']);

			var memberID = $this.attr('memberid'), inOverlay = $this.attr('inOverlay') == 'yes' ? true : false;

			if(inOverlay)
			{
				TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
			}

			if(!memberID || memberID == '0')//Don't do anything for Guests.
			{
				$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				return false;
			}

			console.log('Follow Member: '+memberID);

			$.ajax
			({
				data: 'action=follow_member&memberid='+parseInt(memberID)+'&securitytoken='+TSUESettings['stKey'],
				success: function(serverResponse)
				{
					if(inOverlay)
					{
						TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;
					}

					if($.TSUE.findresponsecode(serverResponse) == 'D')
					{
						if(inOverlay)
						{
							serverResponse = $.TSUE.htmlspecialchars(serverResponse);
						}

						$.TSUE.alert(TSUEPhrases['message_saved']);
						$this.html($.TSUE.clearresponse(serverResponse));
					}
					else
					{
						$this.html(oldText);
						$.TSUE.dialog(serverResponse);
					}
				}
			});

			return false;
		});

		//Spoiler
		$('.spoiler_click').click(function(e)
		{
			e.preventDefault();
			$.TSUE.spoiler(this);
			return false;
		});
		//Spoiler

		//Show Peer/Snatch List
		$(document).on('click', '#history_link', function(e)
		{
			e.preventDefault();
			$.TSUE.insertLoaderAfter(this);

			buildQuery = 'action=download_history&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
					$('.overlay_text ul.tabs').tabs('div.panes > div');
				}
			});
			return false;
		});
		//Show Peer/Snatch List

		//total_warns
		$(document).on('click', '#total_warns', function(e)
		{
			e.preventDefault();
			$.TSUE.insertLoaderAfter(this);

			buildQuery = 'action=total_warns&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
				}
			});
			return false;
		});
		//total_warns

		//hitrun_warns
		$(document).on('click', '#hitrun_warns', function(e)
		{
			e.preventDefault();
			$.TSUE.insertLoaderAfter(this);

			buildQuery = 'action=hitrun_warns&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
				}
			});
			return false;
		});
		//hitrun_warns

		//mutes
		$(document).on('click', '#member_mutes', function(e)
		{
			e.preventDefault();
			$.TSUE.insertLoaderAfter(this);

			buildQuery = 'action=member_mutes&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
				}
			});
			return false;
		});
		//mutes

		//Logout
		$('#logout').click(function(e)
		{
			$.TSUE.insertLoaderAfter(this);
			$(this).remove();
		});
		//Logout

		//Refresh Member Stats
		$('[rel=refreshMemberStats]').click(function(e)
		{
			e.preventDefault();
			$.TSUE.refreshMemberStats();
			return false;
		});
		//Refresh Member Stats

		//Refresh Online Members
		$('[rel=refreshOnlineMembers]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#onlineMembersList').html();
			$('#onlineMembersList').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshOnlineList&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#onlineMembersList').html(serverResponse);
					}
					else
					{
						$('#onlineMembersList').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Online Members

		//Refresh Online Members
		$(document).on('click', '#refreshRecentThreads', function(e)
		{
			e.preventDefault();
			
			var $current = $('#recentThreadsMain');

			$current.html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshRecentThreads&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$(serverResponse).insertBefore($current);
						$current.remove();
					}
					else
					{
						$current.html($.TSUE.clearresponse(serverResponse));
					}
				}
			});
			return false;
		});
		//Refresh Online Members

		//Refresh Last 24 Online Members
		$('[rel=refreshlast24OnlineMembers]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#last24onlineMembersList').html();
			$('#last24onlineMembersList').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshlast24OnlineList&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#last24onlineMembersList').html(serverResponse);
					}
					else
					{
						$('#last24onlineMembersList').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Last 24 Online Members

		//Refresh Top Uploaders
		$('[rel=refreshTopUploaders]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#top_uploaders_list').html();
			$('#top_uploaders_list').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshTopUploaders&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#top_uploaders_list').html(serverResponse);
					}
					else
					{
						$('#top_uploaders_list').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Top Uploaders

		//Refresh Top Donors
		$('[rel=refreshTopDonors]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#top_donors_list').html();
			$('#top_donors_list').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshTopDonors&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#top_donors_list').html(serverResponse);
					}
					else
					{
						$('#top_donors_list').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Top Donors

		//Refresh Newest Members
		$('[rel=refreshNewestMembers]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#newestMembers_avatars').html();
			$('#newestMembers_avatars').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshNewestMembers&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#newestMembers_avatars').html(serverResponse);
					}
					else
					{
						$('#newestMembers_avatars').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Newest Members

		//Refresh Staff Online Now
		$('[rel=refreshStaffOnlineNow]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#staffOnlineNowList').html();
			$('#staffOnlineNowList').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshStaffOnlineNow&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#staffOnlineNowList').html(serverResponse);
					}
					else
					{
						$('#staffOnlineNowList').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Staff Online Now

		//Refresh Website Stats
		$('[rel=refreshWebsiteStats]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#websiteStatsList').html();
			$('#websiteStatsList').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshWebsiteStats&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#websiteStatsList').html(serverResponse);
					}
					else
					{
						$('#websiteStatsList').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Website Stats

		//Refresh Todays Birthdays
		$('[rel=refreshTodaysBirthdays]').click(function(e)
		{
			e.preventDefault();
			$currentList = $('#todaysBirthdaysList').html();
			$('#todaysBirthdaysList').html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action=refreshtodaysBirthdays&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#todaysBirthdaysList').html(serverResponse);
					}
					else
					{
						$('#todaysBirthdaysList').html($currentList);
						$.TSUE.dialog(serverResponse);
					}
				}
			});
			return false;
		});
		//Refresh Todays Birthdays

		//Show All Likes
		$(document).on('click', '#like_x_people_like_this', function(e)
		{
			e.preventDefault();
			$.TSUE.insertLoaderAfter(this);

			var content_id = $(this).attr('content_id'), content_type = $(this).attr('content_type');
			buildQuery = 'action=like_x_people_like_this&content_type='+$.TSUE.urlEncode(content_type)+'&content_id='+$.TSUE.urlEncode(content_id)+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
				}
			});
			return false;
		});
		//Show All Likes

		//Like a Post
		$(document).on('click', '#like', function(e)
		{
			$this = $(this);
			e.preventDefault();

			var content_id = $this.attr('content_id'), content_type = $this.attr('content_type'), content_memberid = $this.attr('content_memberid'), extra = $this.attr('extra'), LikeListSpan = '#like_list_holder_'+content_id+'_'+content_type, currentText = $this.html(), action = 'like_'+$.TSUE.urlEncode(content_type);

			$LikeLinkSpan = $('#LikeLink_'+content_id+'_'+content_type);
			$DefaultLikeLink = $LikeLinkSpan.html();
			$LikeLinkSpan.html(TSUESettings['ajaxLoaderImage']);

			buildQuery = 'action='+action+'&content_type='+$.TSUE.urlEncode(content_type)+'&content_id='+parseInt(content_id)+'&content_memberid='+parseInt(content_memberid)+'&extra='+parseInt(extra)+'&securitytoken='+TSUESettings['stKey'];

			$.ajax
			({
				data: buildQuery,
				success: function(serverResponse)
				{
					if($.TSUE.findresponsecode(serverResponse) == 'D')
					{
						serverResponse = $.TSUE.clearresponse(serverResponse);
						var parseMessage = serverResponse.split('|'), LikeLink = parseMessage['0'], LikeList = parseMessage['1'], TotalLikes = parseMessage['2'];

						$LikeLinkSpan.html(LikeLink);
						$.TSUE.alert(TSUEPhrases['message_saved']);

						if(LikeList)
						{
							$(LikeListSpan).html(LikeList).fadeIn('slow');
						}
						else
						{
							$(LikeListSpan).fadeOut('slow');
						}
					}
					else
					{
						$LikeLinkSpan.html($DefaultLikeLink);
						$.TSUE.dialog(serverResponse);
					}
				}
			});

			return false;
		});
		//Like a Post

		//Run Auto Scroller
		$(document).on('click', '[rel=autoScroller]', function(e)
		{
			e.preventDefault();
			var postID = parseInt($(this).attr('name')), workWith = null, hash = null;

			if(postID == '')
			{
				$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				return false;
			}

			if($('#show_post_'+postID).length > 0)
			{
				hash = '#show_post_'+postID, workWith = $('#show_post_'+postID);
			}
			else if($('#posted_message_'+postID).length > 0)
			{
				hash = '#posted_message_'+postID, workWith = $('#posted_message_'+postID);
			}
			else if($('#profile_post_reply_'+postID).length > 0)
			{
				hash = '#profile_post_reply_'+postID, workWith = $('#profile_post_reply_'+postID);
			}
			else if(!TSUESettings['currentActiveURL'].match(/p=forums/i))
			{
				$.TSUE.jumpInternal('?p=goto&action=find&do=profilePost&postID='+postID);
			}
			else
			{
				$.TSUE.jumpInternal('?p=goto&action=find&do=forumPost&postID='+postID);
			}

			$.TSUE.autoScroller(workWith, hash);
			return false;
		});
		//Run Auto Scroller

		//Select language
		$('[rel=selectLanguage]').click(function(e)
		{
			$.ajax
			({
				data: 'action=select_language&securitytoken='+TSUESettings['stKey'],
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
					$('[rel=language]').click(function(e)
					{
						TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
						e.preventDefault();
						$languageid = parseInt($(this).attr('id'));
						if($languageid)
						{
							$.TSUE.appendLoaderAfter('#language_'+$languageid);
							$.ajax
							({
								data: 'action=update_member_language&languageid='+$languageid+'&securitytoken='+TSUESettings['stKey'],
								success: function(serverResponse)
								{
									if(!$.TSUE.findresponsecode(serverResponse))
									{
										window.location.reload();
									}
									else
									{
										$.TSUE.showMessageInOverlay($.TSUE.clearresponse(serverResponse), true);
									}
								}
							});
						}
						TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;
					});
				}
			});
			return false;
		});

		//Member name, avatar etc.. clicks.. Show Membercard via Ajax.
		$(document).on('click', '#member_info', function(e)
		{
			e.preventDefault();

			var memberID = parseInt($(this).attr('memberid'));

			if(!memberID || memberID == '0')//Don't do anything for Guests.
			{
				return false;
			}

			if($.TSUE.memberInfoCache[memberID])
			{
				$.TSUE.memberCard($.TSUE.memberInfoCache[memberID]);
				$.TSUE._timeAgo();
				$.TSUE.initDropDownMenu();
				return;
			}

			$.ajax
			({
				data: 'action=member_info&memberid='+parseInt(memberID)+'&securitytoken='+TSUESettings['stKey'],
				success: function(serverResponse)
				{
					$.TSUE.memberCard(serverResponse);
					$.TSUE.memberInfoCache[memberID] = serverResponse;
				}
			});

			return false;
		});
		//Member name, avatar etc.. clicks.. Show Membercard via Ajax.

		//report
		$(document).on('click', '#report_content', function(e)
		{
			e.preventDefault();
			var content_type = $(this).attr('content_type'), content_id = $(this).attr('content_id');

			$.TSUE.closedialog();//Remove prev.report dialogs.

			if(!content_type || !content_id)
			{
				$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				return false;
			}

			$.TSUE.insertLoaderAfter(this);

			buildQuery = 'action=report&content_type='+$.TSUE.urlEncode(content_type)+'&content_id='+$.TSUE.urlEncode(content_id)+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);

					$(document).on('submit', '#report_ajax_form_'+content_id, function(e)
					{
						handleReportSubmit(content_id, content_type);
						e.preventDefault();
					});
				}
			});

			return false;
		});
		function handleReportSubmit(content_id, content_type)
		{
			$.TSUE.inputLoader($('#report_reason_'+content_id));

			var report_reason = $('#report_reason_'+content_id).val();
			TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;

			buildQuery = 'action=report&do=save&content_type='+$.TSUE.urlEncode(content_type)+'&content_id='+$.TSUE.urlEncode(content_id)+'&report_reason='+$.TSUE.urlEncode(report_reason)+'&securitytoken='+TSUESettings['stKey'];

			$.ajax
			({
				data:buildQuery,
				success: function(serverResponse)
				{
					if(!$.TSUE.findresponsecode(serverResponse))
					{
						$('#report_ajax_div_'+content_id).remove();
						$.TSUE.closedialog();
						$.TSUE.alert(serverResponse);
					}
					else
					{
						$('#report_ajax_div_'+content_id+' #server_response').html($.TSUE.clearresponse(serverResponse));
						$.TSUE.removeinputLoader($('#report_reason_'+content_id));
					}
				}
			});

			TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;
		}
		//report

		//Country
		$(document).on('click', '.countryMember', function(e)
		{
			e.preventDefault();
			$this = $(this);
			$.TSUE.etoggle('#countrySelect', 1);
			return false;
		});
		$(document).on('click', '#countrySelect img', function(e)
		{
			e.preventDefault();
			$this = $(this);

			$.TSUE.etoggle('#countrySelect');
			$newCountryID = $this.attr('id');
			$newCountrySRC = $this.attr('src');

			$.TSUE.insertLoaderAfter('.countryMember');
			buildQuery = 'action=update_member_country&country='+$.TSUE.urlEncode($newCountryID)+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data: buildQuery,
				success: function(serverResponse)
				{
					if($.TSUE.findresponsecode(serverResponse))
					{
						$.TSUE.dialog(serverResponse);
					}
					else
					{
						$('.countryMember').attr('src', $newCountrySRC);
						$.TSUE.alert(TSUEPhrases['message_saved']);
					}
				}
			});
			return false;
		});
		$(document).on('click', '#countrySelect .close', function(e)
		{
			e.preventDefault();
			$.TSUE.etoggle('#countrySelect');
			return false;
		});
		//Country

		//unread alerts
		$('li[rel="checkAlerts"]').on('hover', function()
		{
			$.TSUE.alertsCheck($(this));
			return false;
		});
		$('#reload_alerts').on('click', function(e)
		{
			e.preventDefault();
			TSUESettings['alertsCache'] = false;
			$.TSUE.alertsCheck('li[rel="checkAlerts"]');
			return false;
		});
		//unread alerts

		//unread messages
		$('li[rel="checkMessages"]').on('hover', function()
		{
			$.TSUE.checkMessages($(this));
			return false;
		});
		$('#reload_messages').on('click', function(e)
		{
			e.preventDefault();
			TSUESettings['messagesChecksCache'] = false;
			$.TSUE.checkMessages('li[rel="checkMessages"]');
			return false;
		});
		//unread messages
	},

	showMoreTorrents: function()
	{
		$.ajax
		({
			url:TSUESettings['website_url']+'/ajax/switch_recent_torrents.php',
			data: 'action=List&pn='+$currentRecentTorrentListPN+'&securitytoken='+TSUESettings['stKey'],
			success: function(serverResponse)
			{
				if($.TSUE.findresponsecode(serverResponse))
				{
					$('#show_more_torrents').remove();
				}
				else
				{
					$currentRecentTorrentListPN++;
					$('#recentTorrents').fadeOut('fast', function()
					{
						$(this).html(serverResponse).fadeIn('fast');
					});
				}
			}
		});
	},

	alertsCheck: function($this)
	{
		var $aText = $('.atext', $this);

		$('.alertBalloon', $this).fadeOut('slow');

		if(TSUESettings['alertsCache'] && !$aText.html().match(/fb_ajax-loader/gi))
		{
			return false;
		}

		$.TSUE.resetDOCTitle();

		buildQuery = 'action=view_alerts&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			data:buildQuery,
			success: function(serverResponse)
			{
				if(serverResponse)
				{
					$aText.html($.TSUE.clearresponse(serverResponse));
					TSUESettings['alertsCache'] = true;
				}
			}
		});
	},

	checkMessages: function($this)
	{
		var $aText = $('.atext', $this);

		$('.alertBalloon', $this).fadeOut('slow');

		if(TSUESettings['messagesChecksCache'] && !$aText.html().match(/fb_ajax-loader/gi))
		{
			return false;
		}

		$.TSUE.resetDOCTitle();

		buildQuery = 'action=view_unread_messages&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			data:buildQuery,
			success: function(serverResponse)
			{
				if(serverResponse)
				{						
					$aText.html($.TSUE.clearresponse(serverResponse));
					TSUESettings['messagesChecksCache'] = true;
					
					if(TSUESettings['currentActiveURL'].match(/p=messages/ig))
					{
						$aText.find('input').each(function()
						{
							$(this).remove();
						});
					}
				}
			}
		});
	},

	resetDOCTitle: function()
	{
		if(docTitleInterval)
		{
			clearInterval(docTitleInterval);
		}
		document.title = TSUESettings['currentDocumentTitle'];
	},

	setDOCTitle: function(newDOCTitle)
	{
		if(docTitleInterval)
		{
			clearInterval(docTitleInterval);
		}

		docTitleInterval = setInterval(function()
		{
			document.title = (document.title == newDOCTitle ? TSUESettings['currentDocumentTitle'] : newDOCTitle);
		}, 1000);
	},

	checkAlerts: function()
	{
		if(TSUESettings['isAjaxRunning'])
		{
			return false;
		}

		TSUESettings['disableButtonsWhileAjax']= false, TSUESettings['closeOverlayWhileAjax']= false, TSUESettings['showLoaderWhileAjax']= false;

		buildQuery = 'action=check_alerts&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			data:buildQuery,
			dataType: 'json',
			success: function(serverResponse)
			{
				if(serverResponse['total'] > 0)
				{
					$.TSUE.setDOCTitle('('+serverResponse['total']+') '+TSUEPhrases['you_have_new_alerts']);
				}
				else
				{
					$.TSUE.resetDOCTitle();
				}

				if(serverResponse['unread_alerts'] > 0)
				{
					TSUESettings['alertsCache'] = false;
					$('li[rel="checkAlerts"] .alertBalloon .count').html(serverResponse['unread_alerts']);
					$('li[rel="checkAlerts"] .alertBalloon').fadeIn('slow');
				}
				else
				{
					$('li[rel="checkAlerts"] .alertBalloon').hide();
				}

				if(serverResponse['unread_messages'] > 0)
				{
					TSUESettings['messagesChecksCache'] = false;
					$('li[rel="checkMessages"] .alertBalloon .count').html(serverResponse['unread_messages']);
					$('li[rel="checkMessages"] .alertBalloon').fadeIn('slow');
				}
				else
				{
					$('li[rel="checkMessages"] .alertBalloon').hide();
				}
			}
		});

		TSUESettings['disableButtonsWhileAjax']= true, TSUESettings['closeOverlayWhileAjax']= true, TSUESettings['showLoaderWhileAjax']= true;
	},

	refreshMemberStats: function()
	{
		$currentList = $('#ul_dl_stats').html();
		$('#ul_dl_stats').html(TSUESettings['ajaxLoaderImage']);

		buildQuery = 'action=refreshMemberStats&securitytoken='+TSUESettings['stKey'];
		$.ajax
		({
			data:buildQuery,
			success: function(serverResponse)
			{
				if(!$.TSUE.findresponsecode(serverResponse))
				{
					$(serverResponse).insertAfter('#ul_dl_stats');
					$('#ul_dl_stats').remove();
				}
				else
				{
					$('#ul_dl_stats').html($currentList);
					$.TSUE.dialog(serverResponse);
				}
			}
		});
	},

	previewButton: function()
	{
		$(document).on('click', '#tinymce_button_preview', function(e)
		{
			e.preventDefault();
			TSUESettings['closeOverlayWhileAjax'] = false;

			var message = tinyMCE.activeEditor.getContent();
			if(message === '')
			{
				$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
				return false;
			}

			$.TSUE.insertLoaderAfter($.TSUE.getOverlayDiv().find('input[type="button"]').last());

			buildQuery = 'action=preview_message&message='+$.TSUE.urlEncode(message)+'&securitytoken='+TSUESettings['stKey'];
			$.ajax
			({
				data: buildQuery,
				success: function(serverResponse)
				{
					$.TSUE.dialog(serverResponse);
					$($.TSUE.findOverlayTextDiv()).find('span#expand_button').remove();
				}
			});
			return false;
		});
	},

	autoRemoveBodyDIV: function(autoRemoveHTML, timeOut)
	{
		setInterval(function()
		{
			$(autoRemoveHTML).fadeOut('slow');
		}, (timeOut ? timeOut : 5000));
	},

	autoExpand: function()
	{
		$(document).on('click', '#expand_button', function(e)
		{
			e.preventDefault();
			var $clicked = $(this), codeMD5 = $clicked.attr('md5'), mainDiv = $('#'+codeMD5), workWidth = $(mainDiv).find('code'), codeWidth = $(workWidth).width(), offsetTop = $(mainDiv).offset().top, offsetTop = offsetTop+10, offsetLeft = $(mainDiv).offset().left, codeWidth = codeWidth+40, cloneCode = $(mainDiv).clone().css({width: codeWidth+'px'});
			$(cloneCode).find('span#expand_button').remove();
			var headerMSG = $(cloneCode).find('.title').html(), codeContent = $(cloneCode).html().replace(headerMSG, '')
			$.TSUE.dialog(codeContent, headerMSG);
			return false;
		});
	},

	initializeTooltip: function()
	{
		// nw | n | ne | w | e | sw | s | se
		$('.tipsy').remove();
		
		$('#content input[title!=""],#content textarea[title!=""],#content select[title!=""]').tipsy({trigger: 'focus', gravity: 'w', html: true});

		$('img[title!=""],a[title!=""],span[title!=""],:header[title!=""]').tipsy({trigger: 'hover', gravity: 'sw', html: true});
	},

	initializeFancyBOX: function()
	{
		$('[rel="fancybox"],#fancybox,[rel="screenshootFancybox"]').fancybox({'type':'image','openEffect':'elastic','closeEffect':'elastic', 'maxWidth': '90%', 'maxHeight': '90%'});
	},

	scrollInfoBoxes: function()
	{
		$(window).scroll(function()
		{
			$('#scrolling').remove();
			if($(window).scrollTop() > 1)
			{
				$('<div id="scrolling" style="position: fixed; right: 15px; bottom: 25px; padding: 0; cursor: pointer; z-index: 1000; display: none;"><img src="'+TSUESettings['theme_dir']+'buttons/up.png" alt="" title="" /></div>').prependTo('body').click(function(e)
				{
					e.preventDefault();
					$('html, body').animate({ scrollTop: 0 }, 'slow', TSUESettings['toggleEffect']);
					return false;
				}).fadeIn('slow');
			}
		});
	},

	resizeImages: function()
	{
		if(!TSUESettings['autoResizeImages'])
		{
			return;
		}

		$('#content img').not('[rel=resized_by_tsue]').not('#toggle').each(function()
		{
			var $image = $(this), $resizedHTML = null,  $width = $image.width(), $height = $image.height(), $ratio = 0, $newHeight = null;
			if($width >= TSUESettings['website_resize_images_max_width'])
			{
				$resizedHTML = $('<div class="resized" style="width: '+(TSUESettings['website_resize_images_max_width']-6)+'px">'+TSUESettings['ajaxLoaderImage']+'</div>').insertBefore($image);
				$ratio = (TSUESettings['website_resize_images_max_width'] / $width);
				$newHeight = ($height * $ratio);

				$image.css('cursor', 'pointer')
				.animate({width: TSUESettings['website_resize_images_max_width'], height: $newHeight}, 'slow', '', function()
				{
					$resizedHTML.html(TSUEPhrases['javascript_resized'].replace(/%1/i, parseInt($width)).replace(/%2/i, parseInt($height)).replace(/%3/i, parseInt(TSUESettings['website_resize_images_max_width'])).replace(/%4/i, Math.round($newHeight)))
				}).attr('rel', 'resized_by_tsue').click(function()
				{
					$.fancybox({'href':$image.attr('src'),'type':'image','openEffect':'elastic','closeEffect':'elastic', 'maxWidth': '90%', 'maxHeight': '90%'});
					return false;
				});
			}
			else
			{
				$(this).attr('rel', 'resized_by_tsue');//prevent re-scale after ajax calls.
			}
		});
	},

	isGuest: function()
	{
		return TSUESettings['memberid'] == "0" ? true : false;
	},

	showMessageInOverlay: function(message, skipClass)
	{
		if(!$.TSUE.inOverlay())
		{
			return false;
		}
		$.TSUE.findOverlayTextDiv().find('#inOverlayPreview').remove();
		$('<div class="'+(skipClass ? '' : 'previewBox')+'" id="inOverlayPreview">'+message+'</div>').prependTo($.TSUE.findOverlayTextDiv());
	},

	inOverlay: function()
	{
		return $.TSUE.overlayCache ? true : false;
	},

	findOverlayTextDiv: function()
	{
		return $('.overlay_text');
	},

	getOverlayDiv: function()
	{
		return $('#overlay');
	},
	
	findOverlayDiv: function()
	{
		return $('body').find('#overlay');
	},

	resizeOverlay: function()
	{
		if($.TSUE.findOverlayDiv().length)
		{
			//var top = ($(window).height() / 2) - ($.TSUE.findOverlayDiv().outerHeight() / 2);
			var left = ($(window).width() / 2) - ($.TSUE.findOverlayDiv().outerWidth() / 2);
			//$.TSUE.findOverlayDiv().css('top', top);
			$.TSUE.findOverlayDiv().css('left', left);
		}
	},

	resizeMemberCard: function()
	{
		if($('#showMemberCard').length)
		{
			//var top = ($(window).height() / 2) - ($('#showMemberCard').outerHeight() / 2);
			var left = ($(window).width() / 2) - ($('#showMemberCard').outerWidth() / 2);
			//$('#showMemberCard').css('top', top);
			$('#showMemberCard').css('left', left);
		}
	},

	toggleTinyMCEinOverlay: function()
	{
		$inline_editor = $('body').find('textarea[id="inline_editor"]');
		if($inline_editor.length)
		{
			$.TSUE.etoggle($inline_editor, 1, function()
			{
				tinyMCE.execCommand('mceAddControl', false, 'inline_editor');
				tinymce.execCommand('mceFocus', false, 'inline_editor');
			});
		}
	},

	removeTinyMCEinOverlay: function()
	{
		$inline_editor = $('body').find('textarea[id="inline_editor"]');
		if($inline_editor.length)
		{
			tinyMCE.execCommand('mceFocus', false, 'inline_editor');
			tinyMCE.execCommand('mceRemoveControl', false, 'inline_editor');
			$inline_editor.html('').remove();
		}
	},

	closedialog: function()
	{
		$.TSUE.removeTinyMCEinOverlay();

		if($.TSUE.overlayCache)
		{
			$.TSUE.overlayCache.close();
			$.TSUE.overlayCache = null;
		}

		var findOverlay = $.TSUE.findOverlayDiv();

		if(findOverlay.length)
		{
			$(findOverlay).empty().remove();
		}
	},

	dialog: function(message, header, closeAction)
	{
		if($.TSUE.findOverlayDiv().length)
		{
			$.TSUE.findOverlayDiv().empty().remove();
		}

		var messageHeader = $(message).attr('header') || header || TSUESettings['website_title'], friendlyOverlayMessage = $.TSUE.clearresponse(TSUESettings['overlayDiv'].replace(/-MESSAGE-/, message)).replace(/-HEADER-/, messageHeader), $fixedPos = true, $leftPos = 'center', $speed = 'normal', $topPos=30;

		$.TSUE.overlayCache = $(friendlyOverlayMessage).prependTo('body').overlay
		({
			mask:{color:TSUESettings['maskColor'],loadSpeed:TSUESettings['maskSpeed'],opacity:TSUESettings['maskOpacity']},oneInstance:false,top:$topPos,fixed: $fixedPos, left: $leftPos, speed: $speed,
			onLoad: function()
			{
				$.TSUE.overlayCount++;
				$.TSUE.findOverlayTextDiv().find('#tinymce_button_preview').remove();
				$.TSUE.getOverlayDiv().find('input, textarea, select').first().focus();
				$.TSUE.toggleTinyMCEinOverlay();

				if($('#overlay ul.tabs').length)
				{
					$.TSUE.resizeOverlay();
					$('#overlay').on('click', 'ul.tabs li', function(e)
					{
						$.TSUE.resizeOverlay();
					});
				}
			},
			onBeforeClose: function()
			{
				$.TSUE.removeTinyMCEinOverlay();
			},
			onClose: function()
			{
				closeAction ? closeAction() : '';
				$.TSUE.overlayCache = null;
			}
		}).data('overlay');
		$.TSUE.overlayCache.load();
		return;
	},		

	memberCard: function(memberInfo)
	{
		if(memberInfo && !$.TSUE.findresponsecode(memberInfo))
		{
			$(memberInfo).appendTo('body').overlay
			({
				top: 48, mask: {color: '#fff', loadSpeed: 200, opacity: 0.6}, closeOnClick: false, load: true
			});
		}
		else
		{
			$.TSUE.dialog(memberInfo);
		}
	},

	confirmAction: function(message, callback)
	{
		var searchPrevForm = $('body').find('#ConfirmButtons');
		if(searchPrevForm.length)
		{
			$('#ConfirmButtons').remove();
		}

		var addButtons = '<p id="ConfirmButtons"><input type="button" class="submit" rel="button_yes" value="'+TSUEPhrases['button_okay']+'" />&nbsp;&nbsp;<input type="button" class="submit" rel="button_no" value="'+TSUEPhrases['button_cancel']+'" /></p>', message = message+addButtons, friendlyOverlayMessage = $.TSUE.clearresponse(TSUESettings['overlayDiv'].replace(/-MESSAGE-/, message)).replace(/-HEADER-/, TSUEPhrases['confirmation_required']);

		$.TSUE.overlayCache = $(friendlyOverlayMessage).appendTo('body').overlay({onClose: function() {$.TSUE.closedialog()},mask:{color:TSUESettings['maskColor'],loadSpeed:TSUESettings['maskSpeed'],opacity:TSUESettings['maskOpacity']},top:30}).data('overlay');
		$.TSUE.overlayCache.load();

		$('#ConfirmButtons').on('click', 'input[type="button"]', function(e)
		{
			e.preventDefault();
			$.TSUE.closedialog();
			callback($(this).attr('rel') == 'button_yes' ? true : false);
			return false;
		});
	},

	alert: function(message, timeOut)
	{
		var message = $.TSUE.clearresponse(message), timeOut = timeOut ? timeOut : 4000, $alert = $('<div id="alert">'+message+'</div>').appendTo('body');
		var alerttimer = window.setTimeout(function (){$alert.trigger('click');}, timeOut);
		$alert.animate({height: $alert.css('line-height') || '40px'}, 200).click(function ()
		{
			window.clearTimeout(alerttimer);
			$alert.animate({height: '0'}, 200);
			return false;
		});
	},	

	urlEncode: function(str)
	{
		return encodeURIComponent(str);
	},

	strip_tags: function(input, allowed)
	{
		allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
		var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
		commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
		return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1){return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';});
	},

	htmlspecialchars: function(str)
	{
		return str.replace(/<\/?[^>]+>/gi, '');
	},

	jumpInternal: function(query)
	{
		window.location = TSUESettings['website_url']+(query ? query : '');
	},

	unixTimestamp: function()
	{
		return Math.round(new Date().getTime() / 1000);
	},

	autoScroller: function(workWith, qhash, speed)
	{
		if($(workWith).length == 0)
		{
			return false;
		}

		$('html, body').animate({ scrollTop: $(workWith).offset().top }, (speed ? speed : 'slow'),  TSUESettings['toggleEffect'], function()
		{
			if(qhash)
			{
				window.location.hash = qhash;
			}
		});
	},

	removeSpaces: function(str)
	{
		return str.replace(/\s+/g, ' ');
	},

	putValueintoInput: function(value, inputName)
	{
		$('input[name="'+inputName+'"]').val(value);
		$('.ac_results').fadeOut('slow');
	},

	insertLoaderAfter: function(workWith)
	{
		$(TSUESettings['ajaxInsertLoaderAfter']).insertAfter($(workWith));
	},

	insertLoaderBefore: function(workWith)
	{
		$(TSUESettings['ajaxInsertLoaderBefore']).insertBefore($(workWith));
	},

	appendLoaderAfter: function(workWith)
	{
		$(TSUESettings['ajaxInsertLoaderAfter']).appendTo($(workWith));
	},

	appendLoaderBefore: function(workWith)
	{
		$(TSUESettings['ajaxInsertLoaderBefore']).prependTo($(workWith));
	},

	insertLoader: function(workWith)
	{
		$(workWith).html(TSUESettings['ajaxInsertLoaderBefore']);
	},

	showajaxloader: function(show)
	{
		if(show == 1)
		{
			$(TSUESettings['ajaxHolder']).appendTo('body');
		}
		else
		{
			$('#'+TSUESettings['ajaxHolderID']).remove();
		}
	},

	inputLoader: function(inputButton, skipDisable)
	{
		if(!skipDisable)
		{
			$.TSUE.disableInputButton(inputButton);
		}

		$(inputButton).css
		({
			'background-image': 'url('+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif)',
			'backgroundRepeat': 'no-repeat',
			'backgroundPosition': 'center center'
		});
	},

	removeinputLoader: function(inputButton, skipDisable)
	{
		if(!skipDisable)
		{
			$.TSUE.enableInputButton(inputButton);
		}

		$(inputButton).css({'background-image': ''});
	},

	autoComplete: function(inputButton, action)
	{
		if(TSUESettings['isAutoCompleteRunning'])
		{
			return false;
		}

		TSUESettings['isAutoCompleteRunning'] = true;

		var keywords = $(inputButton).val(), useDIV = $(inputButton).next('div');

		$(inputButton).focus(function()
		{
			if($(useDIV).html() && keywords.length > 0)
			{
				$(useDIV).fadeIn('slow');
			}
		});

		$(inputButton).focusout(function()
		{
			$(useDIV).fadeOut('slow');
		});

		runAC(keywords, action, useDIV, inputButton);

		function runAC(keywords, action, useDIV, inputButton)
		{
			if(keywords.length > 0)//typing..
			{
				buildQuery = 'action='+$.TSUE.urlEncode(action)+'&keywords='+$.TSUE.urlEncode(keywords)+'&securitytoken='+TSUESettings['stKey'];

				TSUESettings['isAutoCompleteRunning']=true, TSUESettings['disableButtonsWhileAjax']= false, TSUESettings['closeOverlayWhileAjax']= false, TSUESettings['showLoaderWhileAjax']= false, TSUESettings['autoResizeImages']= false;

				$.TSUE.inputLoader(inputButton, true);

				$.ajax
				({
					data:buildQuery,
					success: function(serverResponse)
					{
						if(serverResponse && !$.TSUE.findresponsecode(serverResponse))
						{
							$(useDIV).html(serverResponse).fadeIn('slow', function()
							{
								$(useDIV).find('li').each(function(i, e)
								{
									$(this).css('cursor', 'pointer')
										.hover
										(
											function()
											{
												$(this).addClass('ac_over');
											},
											function()
											{
												$(this).removeClass('ac_over');
											}
										)
										.on('click', function(e)
										{
											var selectedContent = $(this).find('span').html();
											$(inputButton).val(selectedContent);
											e.preventDefault();
											$(useDIV).fadeOut('slow', function()
											{
												$(useDIV).html('');
											});
										});
								});
							});
						}
						else
						{
							$(useDIV).fadeOut('slow', function()
							{
								$(useDIV).html('');
							});
						}

						$.TSUE.removeinputLoader(inputButton, true);
					}
				});

				TSUESettings['isAutoCompleteRunning']=false, TSUESettings['disableButtonsWhileAjax']= true, TSUESettings['closeOverlayWhileAjax']= true, TSUESettings['showLoaderWhileAjax']= true, TSUESettings['autoResizeImages']= true;
			}
			else
			{
				$(useDIV).fadeOut('slow', function()
				{
					$(useDIV).html('');
				});
			}
			TSUESettings['isAutoCompleteRunning'] = false;
		}
	},

	astarted: function()
	{
		if(TSUESettings['showLoaderWhileAjax'])
		{
			$.TSUE.showajaxloader(1);
		}

		if(TSUESettings['disableButtonsWhileAjax'])
		{
			$.TSUE.disableInputButton('body input:not([data="force-disabled"]),select:not([data="force-disabled"]),textarea:not([data="force-disabled"])');
		}
	},

	acompleted: function()
	{
		$('#insertLoaderAfter,#insertLoaderBefore').remove();

		if(TSUESettings['showLoaderWhileAjax'])
		{
			$.TSUE.showajaxloader(0);
		}

		if(TSUESettings['disableButtonsWhileAjax'])
		{
			$.TSUE.enableInputButton('body input:not([data="force-disabled"]),select:not([data="force-disabled"]),textarea:not([data="force-disabled"])');
		}

		$.TSUE._timeAgo();
		$.TSUE.resizeImages();
		$.TSUE.initializeFancyBOX();
		$.TSUE.initializeTooltip();
		$.TSUE.initDropDownMenu();
		$.TSUE.initAutoDescription();
		$.TSUE.initFilterSearch();
	},

	clearresponse: function(message)
	{
		return message.replace(/-ERROR-|-DONE-|-INFORMATION-/g, '').replace(/<div class="" header="">(.*)<\/div>/g, "$1");
	},

	findresponsecode: function(response)
	{
		return (response.substring(0,7) == '-ERROR-' ? 'E' : (response.substring(0,13) == '-INFORMATION-' ? 'I' : (response.substring(0,6) == '-DONE-' ? 'D' : false)));
	},

	buildLinkQuery: function(buildQuery, requiredFields)
	{
		$.each(requiredFields, function(i, name){ buildQuery += '&'+name+'='+$.TSUE.urlEncode($('#'+name).val()); });
		return buildQuery;
	},

	disableInputButton: function(inputButton)
	{
		$(inputButton).attr('disabled', true).addClass('disabled');
	},

	enableInputButton: function(inputButton)
	{
		$(inputButton).attr('disabled', false).removeClass('disabled');
	},

	etoggle: function(tid, show, callback)
	{
		if (show)
		{
			$(tid).slideDown({ duration: TSUESettings['toggleSpeed'], easing: TSUESettings['toggleEffect'], complete: callback});
		}
		else
		{
			$(tid).slideUp({ duration: TSUESettings['toggleSpeed'], easing: TSUESettings['toggleEffect'], complete: callback});
		}
	}
};

$(document).ready(function()
{
	$.TSUE.init();
});

$(window).load(function()
{
	$.TSUE.resizeImages();
	$.TSUE.initFacebook();
	$.TSUE.autoScrollTo();
});

$(window).resize(function()
{
	$.TSUE.resizeOverlay();
	$.TSUE.resizeMemberCard();
});