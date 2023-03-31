//Signup
$('#signupbox_form').submit(function(e)
{
	e.preventDefault();
	$.TSUE.insertLoaderAfter('#signup-buttons');
	
	var gender = $('#memberinfo_gender_female').is(':checked') ? 'f' : ($('#memberinfo_gender_male').is(':checked') ? 'm' : '');
	buildQuery = $.TSUE.buildLinkQuery('action=signup&signupbox_gender='+gender,  ['signupbox_membername', 'signupbox_date_of_birth', 'signupbox_email', 'signupbox_email2', 'signupbox_password', 'signupbox_password2', 'signupbox_timezone', 'invite_hash', 'a_hash']);

	if(TSUESettings['security_enable_captcha'] == "1")
	{
		buildQuery += '&recaptcha_challenge_field='+$.TSUE.urlEncode($('#recaptcha_challenge_field').val())+'&recaptcha_response_field='+$.TSUE.urlEncode($('#recaptcha_response_field').val());
	}
	
	$.ajax
	({
		url:TSUESettings['website_url']+'/ajax/signup.php',
		data:buildQuery,
		success: function(serverResponse)
		{
			$.TSUE.dialog(serverResponse);
			if($.TSUE.findresponsecode(serverResponse) == 'D')
			{
				$.TSUE.jumpInternal();
			}
			else if(TSUESettings['security_enable_captcha'] == "1" && window.Recaptcha)
			{
				window.Recaptcha.reload();
			}
		}		
	});
});

$('input[name="agree_terms_of_service_and_rules"]').click(function(e)
{
	e.preventDefault();
	window.location = window.location+'&agree_terms_of_service_and_rules=yes';
	return false;
});

if(TSUESettings['security_enable_captcha'] == "1")
{
	$.TSUE.loadCaptcha();
}

showPasswordStrength('#signupbox_password');