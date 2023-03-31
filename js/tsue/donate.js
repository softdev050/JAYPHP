function refreshDonateUS()
{
	if($('#fillProgressBar').length)
	{
		var $Percent = parseInt($('#fillProgressBar').attr('rel'));
		if($Percent)
		{
			$('#fillProgressBar').animate({width: $Percent+'%'}, 1500, function()
			{
				if($Percent >= 12)
				{
					$('#progressAmount').fadeIn('slow');
				}
			});
		}
		else
		{
			$('#fillProgressBar').html(' 0% ').show();
		}
	}
}

$(document).on('click', 'img[rel="refreshDonateUs"]', function(e)
{
	e.preventDefault();

	var $current = $('#refreshDonateUs');
	
	$current.html(TSUESettings['ajaxLoaderImage']);

	buildQuery = 'action=refreshDonateUs&securitytoken='+TSUESettings['stKey'];
	$.ajax
	({
		data:buildQuery,
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				$(serverResponse).insertBefore($current);
				$current.remove();
				refreshDonateUS();
			}
			else
			{
				$current.html($.TSUE.clearresponse(serverResponse));
			}
		}
	});

	return false;
});

$(window).load(function()
{
	refreshDonateUS();
});