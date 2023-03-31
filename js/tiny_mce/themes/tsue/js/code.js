var CodeDialog = {
	init: function()
	{
	},

	submit: function()
	{
		var f = document.forms[0], ed = tinyMCEPopup.editor, tag, code, output;
		
		switch (f.elements['type'].value)
		{
			case 'php':
				tag = 'PHP';
			break;

			case 'youtube':
				tag = 'YOUTUBE';
			break;

			case 'facebook':
				tag = 'FACEBOOK';
			break;

			case 'nfo':
				tag = 'NFO';
			break;

			case 'spoiler':
				tag = 'SPOILER';
			break;

			default:
				tag = 'CODE';
			break;
		}
		
		code = f.elements['code'].value;
		
		if(code == '')
		{
			return;
		}

		code = code.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '</p>\n<p>');
		
		output = '[' + tag + ']' + code + '[/' + tag + ']';
		if (output.match(/\n/))
		{
			output = '<p>' + output + '</p>';
		}
		
		ed.execCommand('mceInsertContent', false, output);
		tinyMCEPopup.close();
		
		return false;
	}
};

tinyMCEPopup.onInit.add(CodeDialog.init, CodeDialog);