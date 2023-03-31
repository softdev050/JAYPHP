var $uploaderApplicationForm = $('#uploader_application'), $Queries = [], $speedtestURL = '', $stuff = '', $stuffFilled = false;

TSUESettings['disableButtonsWhileAjax'] = false;

$uploaderApplicationForm.submit(function(e)
{
	e.preventDefault();
	return false;
});

function disableOption(element)
{
	$('#'+element).css({'color':'#ccc'});
	$('input[name="'+element+'"],textarea[name="'+element+'"]').attr('disabled', true);
	$('.tipsy').remove();
}

$('input[name="computer_running_all_the_time"]').click(function(e)
{
	$Queries[0] = 'computer_running_all_the_time='+parseInt($(this).val());
	disableOption('computer_running_all_the_time');
	$('#seedbox').fadeIn('slow');
});

$('input[name="seedbox"]').click(function(e)
{
	$Queries[1] = 'seedbox='+parseInt($(this).val());
	disableOption('seedbox');
	$('#speedtest').fadeIn('slow');
});

$('input[name="speedtest"]').on('keyup', function(e)
{
	$speedtestURL = $(this).val();
	if($speedtestURL.match(/^http:\/\/(www\.)?speedtest\.net\/result\/[0-9]+\.png$/))
	{
		$Queries[2] = 'speedtest='+$.TSUE.urlEncode($speedtestURL);
		disableOption('speedtest');
		$('#stuff').show();

		$('textarea[name="stuff"]').on('keyup', function(e)
		{
			if(!$stuffFilled)
			{
				$stuffFilled = true;
				$('input[name="send"]').show().click(function(e)
				{
					e.preventDefault();

					var $sendButton = $(this);
					$stuff = $('textarea[name="stuff"]').val();

					if($stuff)
					{
						$Queries[3] = 'stuff='+$.TSUE.urlEncode($stuff);

						$sendButton.hide();
						disableOption('stuff');
						$('#sending').show();

						TSUESettings['disableButtonsWhileAjax'] = false;

						$.ajax
						({
							url:TSUESettings['website_url']+'/ajax/uploaderapplication.php',
							data: 'action=uploaderapplication&'+$Queries.join('&')+'&securitytoken='+TSUESettings['stKey'],
							success: function(serverResponse)
							{
								$('#sending').hide();
								if($.TSUE.findresponsecode(serverResponse) == 'D')
								{
									$('ul.uploader_application').html($.TSUE.clearresponse(serverResponse));
									return;
								}
								else
								{
									$sendButton.show();
									$('div.error').remove();
									$($.TSUE.clearresponse(serverResponse)).insertBefore($sendButton);
								}
							}
						});
					}
					else
					{
						$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
					}
					
					return false;
				});
			}
		});
	}
});