var EmotionsDialog =
{
	init : function(ed)
	{
		document.getElementById('smilies').style.display='block';
		tinyMCEPopup.storeSelection();
		tinyMCEPopup.resizeToInnerSize();
	},

	insert : function(file, title)
	{
		var ed = tinyMCEPopup.editor;
		
		ed.execCommand('mceInsertContent', false, '<img src="'+file+'" alt="'+title+'" id="_mce_temp_rob" />');
		ed.focus();
		ed.selection.select(ed.dom.select('#_mce_temp_rob')[0]);
		ed.selection.collapse(0);
		ed.dom.setAttrib('_mce_temp_rob', 'id', '');
		tinyMCEPopup.storeSelection();
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(EmotionsDialog.init, EmotionsDialog);