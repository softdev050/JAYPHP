//Login Box
$(document).on('submit', '#loginbox_form', function(e)
{
	e.preventDefault();

	var $thisForm = $(this);

	$('#loginErrorInOverlay').remove();

	$.TSUE.appendLoaderAfter('#loginbox-buttons');

	buildQuery = 'action=login&loginbox_remember='+$('#loginbox_remember', $thisForm).is(':checked')+'&loginbox_membername='+$.TSUE.urlEncode($('#loginbox_membername', $thisForm).val())+'&loginbox_password='+$.TSUE.urlEncode($('#loginbox_password', $thisForm).val())+'&securitytoken='+TSUESettings['stKey'];

	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/login.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			var responseCode = $.TSUE.findresponsecode(serverResponse);

			if(responseCode == 'E' && $.TSUE.inOverlay())
			{				
				$('<div id="loginErrorInOverlay" style="margin-bottom: 5px;">'+$.TSUE.clearresponse(serverResponse)+'</div>').prependTo($.TSUE.findOverlayTextDiv());
			}
			else if(responseCode == 'E')
			{
				$.TSUE.dialog(serverResponse);
			}
			else
			{
				$.TSUE.dialog(serverResponse);
				setTimeout(function()
				{
					window.location.reload();
				}, 1000);
			}
		}
	});

	return false;
});