$(function()
{
	if($('#wizard').length)
	{
		var root = $('#wizard').scrollable(), api = root.scrollable();
		// validation logic is done inside the onBeforeSeek callback
		api.onBeforeSeek(function(event, i) 
		{
			// we are going 1 step backwards so no need for validation
			if(api.getIndex() < i)
			{
				var error = false, 
				page = root.find('.page').eq(api.getIndex()),
				inputs = page.find('.required :input').removeClass('werror'),
				tinymce = page.find('.required_tinymce'),
				pageCache = page.html(),
				category = page.find('select[name="cid"]').removeClass('werror'),

				empty = inputs.filter(function()
				{
					return $(this).val().replace(/\s*/g, '') == '';
				});

				if(tinymce.length)//Check Tinymce Content.
				{
					if(tinyMCE.activeEditor.getContent() == '')
					{
						error = true;
					}
				}

				if(category.length)
				{
					if(!category.val())
					{
						error = true;
						category.addClass('werror');
					}
				}

				if(empty.length)
				{
					error = true;
				}

				if(error)
				{
					$.TSUE.alert(TSUEPhrases['message_required_fields_error']);
					empty.addClass('werror');
					return false;
				}
				else
				{
					//No Error detected.. Lets move to next step.
				}
			}
			// update status bar
			$('#status li').removeClass('active').eq(i).addClass('active');
		});
	}
});