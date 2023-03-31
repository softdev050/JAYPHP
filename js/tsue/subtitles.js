$(document).on('click', '#selectLanguage', function(e)
{
	e.preventDefault();
	$this = $(this);

	$.TSUE.etoggle('#languageSelect', 1);
});

$(document).on('click', '#languageSelect img', function(e)
{
	e.preventDefault();
	$this = $(this);
	$('#selectedLanguage').remove();

	$.TSUE.etoggle('#languageSelect');
	$newLanguageID = $this.attr('id');
	$newLanguageSRC = $this.attr('src');

	$selectedLanguage = $('<span id="selectedLanguage"><img src="'+$newLanguageSRC+'" alt="" title="" class="middle" border="0" /><input type="hidden" name="language" value="'+$newLanguageID+'" /> </span>');
	$selectedLanguage.prependTo('#selectLanguage');
});

$(document).on('click', '#languageSelect .close', function(e)
{
	e.preventDefault();
	$.TSUE.etoggle('#languageSelect');
});