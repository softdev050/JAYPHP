var $mid = [];
$(document).on('click', 'input[name="mid[]"]', function(e)
{
	var $input = $(this), $val = $input.val();

	$('#massAction').remove();

	$.TSUE.removeItem($mid, $val);//Remove item from the array!

	if($input.is(':checked') && $.inArray($val, $mid) == -1)
	{
		$mid.push($val);
	}

	if($mid.length)
	{
		$('<div id="massAction"><span id="massDeleteStaffMessages"><img src="'+TSUESettings['theme_dir']+'buttons/delete.png" class="middle" alt="" title="" /> '+TSUEPhrases['button_delete']+'</div>').insertBefore($input).slideDown('slow');
		
		$('#massDeleteStaffMessages').click(function(e)
		{
			e.preventDefault();
			
			var $this = $('#massAction');

			$this.html(TSUEPhrases['confirm_mass_delete_staff_messages']).click(function(e)
			{
				e.preventDefault();

				$this.html('<img src="'+TSUESettings['theme_dir']+'ajax/fb_ajax-loader.gif" alt="" title="" class="middle" /> '+TSUEPhrases['loading']);
				
				buildQuery = 'action=delete_staff_message&mid='+$mid+'&securitytoken='+TSUESettings['stKey'];
				$.ajax
				({
					url:TSUESettings['website_url']+'/ajax/staffmessages.php',
					data: buildQuery,
					success: function(serverResponse)
					{
						if(!$.TSUE.findresponsecode(serverResponse))
						{
							var j = 0;
							while (j < $mid.length)
							{
								var $m = $mid[j], $work = $('#staff_message_'+$m);
								j++;
								$work.remove();
							}
						}
						else
						{
							$.TSUE.dialog(serverResponse);
						}
					}
				});
				
				$this.slideUp('slow', function() {$(this).remove()});

				return false;
			});

			return false;
		});
	}
});