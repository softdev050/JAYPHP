//Upgrade
$('[rel="purchase"]').click(function(e)
{
	e.preventDefault();

	var $upgrade_id = parseInt($(this).attr('id'));
	if(!$upgrade_id)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	$.TSUE.insertLoaderAfter(this);

	buildQuery = 'action=upgrade&upgrade_id='+$upgrade_id+'&securitytoken='+TSUESettings['stKey'];
	$.ajax(
	{
		url:TSUESettings['website_url']+'/ajax/upgrade.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			if(serverResponse)
			{
				$.TSUE.dialog(serverResponse);
			}
		}
	});

	return false;
});