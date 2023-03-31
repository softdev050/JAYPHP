//Contact US
$('#contactus_form').submit(function(e)
{
	e.preventDefault();
	
	$.TSUE.insertLoaderAfter('#contact-buttons');
	
	var $thisForm = $(this), buildQuery = $.TSUE.buildLinkQuery('action=contact&securitytoken='+TSUESettings['stKey'],  ['membername', 'email', 'message']);
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/contact.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if($.TSUE.findresponsecode(serverResponse) == 'D')
			{
				$(serverResponse).insertBefore($thisForm);
				$thisForm.remove();
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}		
	});

	return false;
});