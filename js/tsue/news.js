//NEWS
$(document).on('click', '#news_item', function(e)
{
	e.preventDefault();
	var $nid = parseInt($(this).attr('rel'));

	if(!$nid)
	{
		$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
		return false;
	}

	var $nItem = $('#news_item_'+$nid).html(),
	$nTitle = $('#news_item_title_'+$nid).html();

	$.TSUE.dialog($nItem, $nTitle);
});