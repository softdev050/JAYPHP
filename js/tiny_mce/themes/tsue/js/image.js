var ImageDialog = 
{
	init : function()
	{
		var f = document.forms[0], ed = tinyMCEPopup.editor;
		e = ed.selection.getNode();
		if (e.nodeName == 'IMG')
		{
			f.src.value = ed.dom.getAttrib(e, 'src');
		}
	},

	update : function()
	{
		var f = document.forms[0], nl = f.elements, ed = tinyMCEPopup.editor, args = {}, el;

		tinyMCEPopup.restoreSelection();

		if (f.src.value === '')
		{
			if (ed.selection.getNode().nodeName == 'IMG')
			{
				ed.dom.remove(ed.selection.getNode());
				ed.execCommand('mceRepaint');
			}

			tinyMCEPopup.close();
			return;
		}

		tinymce.extend(args,
		{
			src : f.src.value
		});

		el = ed.selection.getNode();

		if (el && el.nodeName == 'IMG')
		{
			ed.dom.setAttribs(el, args);
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.editor.focus();
		}
		else
		{
			ed.execCommand('mceInsertContent', false, '<img src="'+args['src']+'" id="_mce_temp_rob" alt="" />', {skip_undo : 1});
			ed.undoManager.add();
			ed.focus();
			ed.selection.select(ed.dom.select('#_mce_temp_rob')[0]);
			ed.selection.collapse(0);
			ed.dom.setAttrib('_mce_temp_rob', 'id', '');
			tinyMCEPopup.storeSelection();
		}

		tinyMCEPopup.close();
	},

	getImageData : function()
	{
		var f = document.forms[0];
		this.preloadImg = new Image();
		this.preloadImg.src = tinyMCEPopup.editor.documentBaseURI.toAbsolute(f.src.value);
	}
};

tinyMCEPopup.onInit.add(ImageDialog.init, ImageDialog);