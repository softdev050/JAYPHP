//FAQ
var $recentlyOpenedCID = 0

function openCloseCategory(e)
{
	var $clickedCategory = $(e), $categoryCID = parseInt($clickedCategory.attr('cid')) , $categoryItems = $('#faq_items_'+$categoryCID);
	$.TSUE.etoggle($categoryItems, $categoryItems.is(':visible') ? 0 : 1);
	$recentlyOpenedCID = $categoryCID;
}

$('[rel=FAQCategory]').click(function(e)
{
	e.preventDefault();

	if($recentlyOpenedCID)
	{
		$.TSUE.etoggle($('#faq_items_'+$recentlyOpenedCID), 0);
	}

	openCloseCategory(this);
	
	return false;
});

$(document).on('click', '[rel=faq_item]', function(e)
{
	e.preventDefault();

	var $faqItem = $('#faq_item_'+$(this).attr('fid'));
	$.TSUE.etoggle($faqItem, $faqItem.is(':visible') ? 0 : 1);

	return false;
});

$(window).load(function()
{
	var $cid = TSUESettings['currentActiveURL'].match(/cid=([0-9]+)/);
	if($cid)
	{
		$.TSUE.autoScroller('#faq_items_'+$cid[1], 'faq_items_'+$cid[1]);
	}
});