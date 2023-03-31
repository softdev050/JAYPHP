$('#sls_form').submit(function(e)
{
	e.preventDefault();
	
	buildQuery = $.TSUE.buildLinkQuery('action=login&loginbox_remember='+$('#loginbox_remember').is(':checked'),  ['loginbox_membername', 'loginbox_password']);
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/login.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			if($.TSUE.findresponsecode(serverResponse) == 'D')
			{
				setTimeout(function()
				{
					window.location.reload();
				}, 1000);
			}
		}
	});

	return false;
});

$(window).load(function()
{
	$('#loginbox_membername').focus();

	if($('#ballonTooltip').length)
	{
		setTimeout(function(){$('#ballonTooltip').fadeIn('slow')}, 1000);
		setTimeout(function(){$('#ballonTooltip').fadeOut('slow')}, 6000);
	}
});