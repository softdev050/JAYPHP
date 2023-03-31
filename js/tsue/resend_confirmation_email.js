//Resend Confirmation email
$('[rel="resend_confirmation_email"]').click(function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/resend_confirmation_email.php',
		data: 'action=resend_confirmation_email&securitytoken='+TSUESettings['stKey'],
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}
	});
});