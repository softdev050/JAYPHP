//Forgot Password
$(document).on('click', '[rel="forgot-password"]', function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter(this);
	forgotPassword();
	return false;
});

function forgotPassword()
{
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/forgot_password.php',
		data:'action=forgot_password&do=form',
		success: function(serverResponse)
		{
			if(!$.TSUE.findresponsecode(serverResponse))
			{
				if(TSUESettings['security_enable_captcha'] == "1")
				{
					if(window.Recaptcha)
					{
						window.Recaptcha.destroy();
					}

					if(TSUESettings['currentActiveURL'].match(/p=signup/i))//Already Loaded before? Such as for Signup..
					{
						$('#content').html(serverResponse);
					}
					else
					{
						$.TSUE.dialog(serverResponse);
					}
					$.TSUE.loadCaptcha();
				}
				else
				{
					$.TSUE.dialog(serverResponse);
				}

				$('#forgotpassword_form').submit(function(e)
				{
					e.preventDefault();
					$.TSUE.insertLoaderAfter('#forgotpassword-buttons');

					TSUESettings['showLoaderWhileAjax'] = false, TSUESettings['closeOverlayWhileAjax'] = false;
					buildQuery = $.TSUE.buildLinkQuery('action=forgot_password',  ['forgotpassword_email']);

					if(TSUESettings['security_enable_captcha'] == "1")
					{
						buildQuery += '&recaptcha_challenge_field='+$.TSUE.urlEncode($('#recaptcha_challenge_field').val())+'&recaptcha_response_field='+$.TSUE.urlEncode($('#recaptcha_response_field').val());
					}

					$.ajax
					({
						url:TSUESettings['website_url']+'/ajax/forgot_password.php',
						data:buildQuery,
						success: function(serverResponse)
						{
							if($.TSUE.findresponsecode(serverResponse) == 'D')
							{
								$.TSUE.etoggle('#overlay div.comment-box');
							}
							else if(TSUESettings['security_enable_captcha'] == "1" && window.Recaptcha)
							{
								window.Recaptcha.reload();
							}

							$('#server_response').removeClass('information').html($.TSUE.clearresponse(serverResponse));
						}
					});
					TSUESettings['showLoaderWhileAjax'] = true, TSUESettings['closeOverlayWhileAjax'] = true;
				});
			}
			else
			{
				$.TSUE.dialog(serverResponse);
			}
		}		
	});
}

$(window).load(function()
{
	//forgot-password
	if(TSUESettings['currentActiveURL'].match(/dialog=forgot-password/ig))
	{
		forgotPassword();
	}
});