//Contact US
$('#contactstaff_form').submit(function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter('#contactstaff-buttons');
	
	buildQuery = $.TSUE.buildLinkQuery('action=contactstaff&securitytoken='+TSUESettings['stKey'],  ['message']);
	
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/contactstaff.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
		}		
	});
});