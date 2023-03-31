// custom easing called 'custom'
$.easing.custom = function (x, t, b, c, d)
{
	var s = 1.70158; 
	if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
	return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
}

// use the custom easing
$(document).ready(function()
{
	if($('div.scrollable').length)
	{
		$('div.scrollable').scrollable({easing: 'custom', speed: 700, circular: true});

		var api = $('div.scrollable').data('scrollable');
		var autoScroll = setInterval(function(){api.move(1)}, 5000);

		//$('div.scrollable img[title]').tooltip();

		$('div.scrollable').hover(function(e)
		{
			clearInterval(autoScroll);
		},
		function(e)
		{
			autoScroll = setInterval(function(){api.move(1)}, 5000);
		});

		$(document).on('click', '#sItemPrev', function(e)
		{
			e.preventDefault();
			clearInterval(autoScroll);
			api.next();
			autoScroll = setInterval(function(){api.move(1)}, 5000);
			return false;
		});
		$(document).on('click', '#sItemNext', function(e)
		{
			e.preventDefault();
			clearInterval(autoScroll);
			api.prev();
			autoScroll = setInterval(function(){api.move(1)}, 5000);
			return false;
		});
	}
});