//TinyMCE TSUE Template.
(function(tinymce)
{
	var DOM = tinymce.DOM, Event = tinymce.dom.Event, extend = tinymce.extend, each = tinymce.each, Cookie = tinymce.util.Cookie, lastExtID, explode = tinymce.explode;
	var pasteTag = function(TagName, Editor)
	{
		var Newtext = Editor.selection.getContent();
		Newtext = '[' + TagName + ']' + Newtext + '[/' + TagName + ']';
		Editor.execCommand('mceReplaceContent', false, Newtext);
	};

	//AUTO LINK
	(function(){tinymce.create("tinymce.plugins.AutolinkPlugin",{init:function(a,b){var c=this;a.onKeyDown.addToTop(function(d,f){if(f.keyCode==13){return c.handleEnter(d)}});if(tinyMCE.isIE){return}a.onKeyPress.add(function(d,f){if(f.which==41){return c.handleEclipse(d)}});a.onKeyUp.add(function(d,f){if(f.keyCode==32){return c.handleSpacebar(d)}})},handleEclipse:function(a){this.parseCurrentLine(a,-1,"(",true)},handleSpacebar:function(a){this.parseCurrentLine(a,0,"",true)},handleEnter:function(a){this.parseCurrentLine(a,-1,"",false)},parseCurrentLine:function(i,d,b,g){var a,f,c,n,k,m,h,e,j;a=i.selection.getRng(true).cloneRange();if(a.startOffset<5){e=a.endContainer.previousSibling;if(e==null){if(a.endContainer.firstChild==null||a.endContainer.firstChild.nextSibling==null){return}e=a.endContainer.firstChild.nextSibling}j=e.length;a.setStart(e,j);a.setEnd(e,j);if(a.endOffset<5){return}f=a.endOffset;n=e}else{n=a.endContainer;if(n.nodeType!=3&&n.firstChild){while(n.nodeType!=3&&n.firstChild){n=n.firstChild}a.setStart(n,0);a.setEnd(n,n.nodeValue.length)}if(a.endOffset==1){f=2}else{f=a.endOffset-1-d}}c=f;do{a.setStart(n,f-2);a.setEnd(n,f-1);f-=1}while(a.toString()!=" "&&a.toString()!=""&&a.toString().charCodeAt(0)!=160&&(f-2)>=0&&a.toString()!=b);if(a.toString()==b||a.toString().charCodeAt(0)==160){a.setStart(n,f);a.setEnd(n,c);f+=1}else{if(a.startOffset==0){a.setStart(n,0);a.setEnd(n,c)}else{a.setStart(n,f);a.setEnd(n,c)}}var m=a.toString();if(m.charAt(m.length-1)=="."){a.setEnd(n,c-1)}m=a.toString();h=m.match(/^(https?:\/\/|ssh:\/\/|ftp:\/\/|file:\/|www\.|(?:mailto:)?[A-Z0-9._%+-]+@)(.+)$/i);if(h){if(h[1]=="www."){h[1]="http://www."}else{if(/@$/.test(h[1])&&!/^mailto:/.test(h[1])){h[1]="mailto:"+h[1]}}k=i.selection.getBookmark();i.selection.setRng(a);tinyMCE.execCommand("createlink",false,h[1]+h[2]);i.selection.moveToBookmark(k);i.nodeChanged();if(tinyMCE.isWebKit){i.selection.collapse(false);var l=Math.min(n.length,c+1);a.setStart(n,l);a.setEnd(n,l);i.selection.setRng(a)}}},getInfo:function(){return{longname:"Autolink",author:"Moxiecode Systems AB",authorurl:"http://tinymce.moxiecode.com",infourl:"http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/autolink",version:tinymce.majorVersion+"."+tinymce.minorVersion}}});tinymce.PluginManager.add("autolink",tinymce.plugins.AutolinkPlugin)})();

	//CODE
	tinymce.create('tinymce.plugins.TSUECodePlugin',
	{
		init : function(ed, url)
		{
			// Register commands
			ed.addCommand('opendCodeEditor', function()
			{
				ed.windowManager.open(
				{
					width: 440,
					url : TSUESettings['website_url']+'/?p=dialog&dialog=code',
					inline : true,
					resizable : true,
					maximizable : true,
					translate_i18n : false
				});
			});
			// Register buttons
			ed.addButton('tsuecode', {title : 'tsue.code_desc', cmd : 'opendCodeEditor', image: TSUESettings['theme_dir']+'/editor/code.png'});
		},

		getInfo : function() {
			return {
				longname : 'TSUECodePlugin',
				author : 'xam Templateshares',
				authorurl : 'http://templateshares.net',
				infourl : 'http://templateshares.net',
				version : '1.1'
			};
		}
	});

	//SMILIES
	tinymce.create('tinymce.plugins.SmiliesPlugin',
	{
		init : function(ed, url)
		{
			// Register commands
			ed.addCommand('SmiliesPopup', function() {
				ed.windowManager.open({
					url : TSUESettings['website_url']+'/?p=dialog&dialog=smilies',
					inline : 1,
					width: 490,
					translate_i18n : false
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('smilies', {title : 'tsue.smilies_desc', cmd : 'SmiliesPopup', image: TSUESettings['theme_dir']+'/editor/smilie.png'});
		},

		getInfo : function() {
			return {
				longname : 'Smilies',
				author : 'xam Templateshares',
				authorurl : 'http://templateshares.net',
				infourl : 'http://templateshares.net',
				version : '1.0'
			};
		}
	});

	//QUOTE
	tinymce.create('tinymce.plugins.QuotePlugin',
	{
		init : function(ed, url)
		{
			// Register commands
			ed.addCommand('mceQuote', function()
			{
				pasteTag('QUOTE', ed);
			});

			// Register buttons
			ed.addButton('quote', {title : 'tsue.quote', cmd : 'mceQuote'});
		},

		getInfo : function() {
			return {
				longname : 'Quote',
				author : 'xam Templateshares',
				authorurl : 'http://templateshares.net',
				infourl : 'http://templateshares.net',
				version : '1.0'
			};
		}
	});

	// Register plugins
	tinymce.PluginManager.add('autolink', tinymce.plugins.AutolinkPlugin);
	tinymce.PluginManager.add('tsuecode', tinymce.plugins.TSUECodePlugin);
	tinymce.PluginManager.add('smilies', tinymce.plugins.SmiliesPlugin);
	tinymce.PluginManager.add('quote', tinymce.plugins.QuotePlugin);
	
	// Tell it to load theme specific language pack(s)
	//tinymce.ThemeManager.requireLangPack('tsue');

	tinymce.create('tinymce.themes.TSUETheme',
	{
		sizes : [8, 10, 12, 14, 18, 24, 36],

		// Control name lookup, format: title, command
		controls :
		{
			bold : ['bold_desc', 'Bold'],
			italic : ['italic_desc', 'Italic'],
			underline : ['underline_desc', 'Underline'],
			strikethrough : ['striketrough_desc', 'Strikethrough'],
			justifyleft : ['justifyleft_desc', 'JustifyLeft'],
			justifycenter : ['justifycenter_desc', 'JustifyCenter'],
			justifyright : ['justifyright_desc', 'JustifyRight'],
			bullist : ['bullist_desc', 'InsertUnorderedList'],
			numlist : ['numlist_desc', 'InsertOrderedList'],
			undo : ['undo_desc', 'Undo'],
			redo : ['redo_desc', 'Redo'],
			link : ['link_desc', 'mceLink'],
			unlink : ['unlink_desc', 'unlink'],
			image : ['image_desc', 'mceImage'],
			cleanup : ['cleanup_desc', 'mceCleanup'],
			code : ['code_desc', 'mceCodeEditor'],
			removeformat : ['removeformat_desc', 'RemoveFormat'],
			forecolor : ['forecolor_desc', 'ForeColor'],
			forecolorpicker : ['forecolor_desc', 'mceForeColor']
		},

		stateControls : ['bold', 'italic', 'underline', 'strikethrough', 'bullist', 'numlist', 'justifyleft', 'justifycenter', 'justifyright'],

		init : function(ed, url)
		{
			var t = this, s, v, o;
			t.editor = ed, t.url = url, t.onResolveName = new tinymce.util.Dispatcher(this);

			/* TSUE Skin Default Settings for TinyMCE */
			ed.settings.skin = 'TSUE', ed.settings.dialog_type = 'modal';
			ed.settings.inline_styles = false, ed.settings.convert_urls= false, ed.settings.relative_urls= false, ed.settings.remove_script_host= false;
			ed.settings.valid_elements = 'p[style],div[style],span[style],img[src|alt|title|id|class],a[href],strong/b,em,ul,li,ol,br';
			ed.settings.forced_root_block = false;
			/* TSUE Skin Default Settings for TinyMCE */
			
			ed.settings.popup_css = false, ed.settings.paste_remove_styles_if_webkit = false, ed.settings.gecko_spellcheck = true, ed.settings.entities = '160,nbsp,38,amp,34,quot,60,lt,62,gt';

			if(ed.id == 'tinymceShoutbox')
			{
				var buttons1 = 'removeformat,cleanup,|,undo,redo,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,fontselect,fontsizeselect,link,unlink,image,emailbutton,|,forecolor,smilies';
				var buttons2 = '';
				var buttons3 = '';

				ed.onKeyDown.addToTop(function(ed, e)
				{
					if(e.keyCode == 13)
					{
						$('#postShout').submit();
						return tinymce.dom.Event.cancel(e);
					}
				});
			}
			else
			{
				var buttons1 = 'removeformat,cleanup,|,undo,redo,|,fontselect,fontsizeselect,forecolor,smilies';
				var buttons2 = 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,|,link,unlink,image,emailbutton,|,tsuecode,quote';
				var buttons3 = '';
			}

			// Default settings
			t.settings = s = extend
			({
				theme_tsue_path : true,
				theme_tsue_buttons1 : buttons1,
				theme_tsue_buttons2 : buttons2,
				theme_tsue_buttons3 : buttons3,
				theme_tsue_toolbar_location : 'top',
				theme_tsue_toolbar_align : 'left',
				theme_tsue_blockformats : 'p,address,pre,h1,h2,h3,h4,h5,h6',
				theme_tsue_fonts : 'Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats',
				theme_tsue_more_colors : 0,
				theme_tsue_row_height : 23,
				theme_tsue_resize_horizontal : false,
				theme_tsue_resizing_use_cookie : 1,
				theme_tsue_resizing_min_height: 100,
				theme_tsue_resizing: true,
				theme_tsue_statusbar_location: 'bottom',
				theme_tsue_font_sizes : '1,2,3,4,5,6,7',
				theme_tsue_font_selector : 'span',
				theme_tsue_dialog_url : '',
				readonly : ed.settings.readonly
			}, ed.settings);

			// Setup default font_size_style_values
			if (!s.font_size_style_values)
				s.font_size_style_values = '8pt,10pt,12pt,14pt,18pt,24pt,36pt';

			if (tinymce.is(s.theme_tsue_font_sizes, 'string'))
			{
				s.font_size_style_values = tinymce.explode(s.font_size_style_values);
				s.font_size_classes = tinymce.explode(s.font_size_classes || '');

				// Parse string value
				o = {};
				ed.settings.theme_tsue_font_sizes = s.theme_tsue_font_sizes;
				each(ed.getParam('theme_tsue_font_sizes', '', 'hash'), function(v, k) {
					var cl;

					if (k == v && v >= 1 && v <= 7)
					{
						//k = v + ' (' + t.sizes[v - 1] + 'pt)';

						if (ed.settings.convert_fonts_to_spans)
						{
							cl = s.font_size_classes[v - 1];
							v = s.font_size_style_values[v - 1] || (t.sizes[v - 1] + 'pt');
						}
					}

					if (/^\s*\./.test(v))
						cl = v.replace(/\./g, '');

					o[k] = cl ? {'class' : cl} : {fontSize : v};
				});

				s.theme_tsue_font_sizes = o;
			}

			if ((v = s.theme_tsue_path_location) && v != 'none')
				s.theme_tsue_statusbar_location = s.theme_tsue_path_location;

			if (s.theme_tsue_statusbar_location == 'none')
				s.theme_tsue_statusbar_location = 0;

			// Init editor
			ed.onInit.add(function()
			{
				ed.onNodeChange.add(t._nodeChanged, t);
				if (ed.settings.content_css !== false)
				{
					ed.dom.loadCSS(TSUESettings['website_url']+'/style.php?l=tinymce_content');
				}
			});

			ed.onSetProgressState.add(function(ed, b, ti)
			{
				var co, id = ed.id, tb;

				if (b)
				{
					t.progressTimer = setTimeout(function()
					{
						co = ed.getContainer();
						co = co.insertBefore(DOM.create('DIV', {style : 'position:relative'}), co.firstChild);
						tb = DOM.get(ed.id + '_tbl');

						DOM.add(co, 'div', {id : id + '_blocker', 'class' : 'mceBlocker', style : {width : tb.clientWidth + 2, height : tb.clientHeight + 2}});
						DOM.add(co, 'div', {id : id + '_progress', 'class' : 'mceProgress', style : {left : tb.clientWidth / 2, top : tb.clientHeight / 2}});
					}, ti || 0);
				} else {
					DOM.remove(id + '_blocker');
					DOM.remove(id + '_progress');
					clearTimeout(t.progressTimer);
				}
			});

			//DOM.loadCSS(s.editor_css ? ed.documentBaseURI.toAbsolute(s.editor_css) : TSUESettings['website_url']+'/js/tiny_mce/themes/tsue/css/tinymce_ui.css');
		},

		createControl : function(n, cf) {
			var cd, c;

			if (c = cf.createControl(n))
				return c;

			switch (n) {
				case 'fontselect':
					return this._createFontSelect();

				case 'fontsizeselect':
					return this._createFontSizeSelect();

				case 'forecolor':
					return this._createForeColorMenu();
			}

			if ((cd = this.controls[n]))
				return cf.createButton(n, {title : 'tsue.' + cd[0], cmd : cd[1], ui : cd[2], value : cd[3]});
		},

		execCommand : function(cmd, ui, val) {
			var f = this['_' + cmd];

			if (f) {
				f.call(this, ui, val);
				return true;
			}

			return false;
		},

		_createFontSelect : function() {
			var c, t = this, ed = t.editor;

			c = ed.controlManager.createListBox('fontselect', {title : 'tsue.fontdefault', cmd : 'FontName'});
			if (c) {
				each(ed.getParam('theme_tsue_fonts', t.settings.theme_tsue_fonts, 'hash'), function(v, k) {
					c.add(ed.translate(k), v, {style : v.indexOf('dings') == -1 ? 'font-family:' + v : ''});
				});
			}

			return c;
		},

		_createFontSizeSelect : function() {
			var t = this, ed = t.editor, c, i = 0, cl = [];

			c = ed.controlManager.createListBox('fontsizeselect', {title : 'tsue.font_size', onselect : function(v) {
				if (v.fontSize)
					ed.execCommand('FontSize', false, v.fontSize);
				else {
					each(t.settings.theme_tsue_font_sizes, function(v, k) {
						if (v['class'])
							cl.push(v['class']);
					});

					ed.editorCommands._applyInlineStyle('span', {'class' : v['class']}, {check_classes : cl});
				}
			}});

			if (c) {
				each(t.settings.theme_tsue_font_sizes, function(v, k) {
					var fz = v.fontSize;

					if (fz >= 1 && fz <= 7)
						fz = t.sizes[parseInt(fz) - 1] + 'pt';

					c.add(k, v, {'style' : 'font-size:' + fz, 'class' : 'mceFontSize' + (i++) + (' ' + (v['class'] || ''))});
				});
			}

			return c;
		},

		_createForeColorMenu : function() {
			var c, t = this, s = t.settings, o = {}, v;

			if (v = s.theme_tsue_text_colors)
				o.colors = v;

			if (s.theme_tsue_default_foreground_color)
				o.default_color = s.theme_tsue_default_foreground_color;

			o.title = 'tsue.forecolor_desc';
			o.cmd = 'ForeColor';
			o.scope = this;

			c = t.editor.controlManager.createColorSplitButton('forecolor', o);

			return c;
		},

		renderUI : function(o) {
			var n, ic, tb, t = this, ed = t.editor, s = t.settings, sc, p, nl;

			n = p = DOM.create('span', {id : ed.id + '_parent', 'class' : 'mceEditor ' + ed.settings.skin + 'Skin' + (s.skin_variant ? ' ' + ed.settings.skin + 'Skin' + t._ufirst(s.skin_variant) : '')});

			if (!DOM.boxModel)
				n = DOM.add(n, 'div', {'class' : 'mceOldBoxModel'});

			n = sc = DOM.add(n, 'table', {id : ed.id + '_tbl', 'class' : 'mceLayout', cellSpacing : 0, cellPadding : 0});
			n = tb = DOM.add(n, 'tbody');

			switch ((s.theme_tsue_layout_manager || '').toLowerCase()) {
				case 'rowlayout':
					ic = t._rowLayout(s, tb, o);
					break;

				case 'customlayout':
					ic = ed.execCallback('theme_tsue_custom_layout', s, tb, o, p);
					break;

				default:
					ic = t._simpleLayout(s, tb, o, p);
			}

			n = o.targetNode;

			// Add classes to first and last TRs
			nl = DOM.stdMode ? sc.getElementsByTagName('tr') : sc.rows; // Quick fix for IE 8
			DOM.addClass(nl[0], 'mceFirst');
			DOM.addClass(nl[nl.length - 1], 'mceLast');

			// Add classes to first and last TDs
			each(DOM.select('tr', tb), function(n) {
				DOM.addClass(n.firstChild, 'mceFirst');
				DOM.addClass(n.childNodes[n.childNodes.length - 1], 'mceLast');
			});

			if (DOM.get(s.theme_tsue_toolbar_container))
				DOM.get(s.theme_tsue_toolbar_container).appendChild(p);
			else
				DOM.insertAfter(p, n);

			Event.add(ed.id + '_path_row', 'click', function(e) {
				e = e.target;

				if (e.nodeName == 'A') {
					t._sel(e.className.replace(/^.*mcePath_([0-9]+).*$/, '$1'));

					return Event.cancel(e);
				}
			});

			if (!ed.getParam('accessibility_focus'))
				Event.add(DOM.add(p, 'a', {href : '#'}, '<!-- IE -->'), 'focus', function() {tinyMCE.get(ed.id).focus();});

			if (s.theme_tsue_toolbar_location == 'external')
				o.deltaHeight = 0;

			t.deltaHeight = o.deltaHeight;
			o.targetNode = null;

			return {
				iframeContainer : ic,
				editorContainer : ed.id + '_parent',
				sizeContainer : sc,
				deltaHeight : o.deltaHeight
			};
		},

		getInfo : function() {
			return {
				longname : 'TSUE theme',
				author : 'xam Templateshares',
				authorurl : 'http://templateshares.net',
				version : tinymce.majorVersion + '.' + tinymce.minorVersion
			}
		},

		resizeBy : function(dw, dh) {
			var e = DOM.get(this.editor.id + '_tbl');

			this.resizeTo(e.clientWidth + dw, e.clientHeight + dh);
		},

		resizeTo : function(w, h) {
			var ed = this.editor, s = ed.settings, e = DOM.get(ed.id + '_tbl'), ifr = DOM.get(ed.id + '_ifr'), dh;

			// Boundery fix box
			w = Math.max(s.theme_tsue_resizing_min_width || 100, w);
			h = Math.max(s.theme_tsue_resizing_min_height || 100, h);
			w = Math.min(s.theme_tsue_resizing_max_width || 0xFFFF, w);
			h = Math.min(s.theme_tsue_resizing_max_height || 0xFFFF, h);

			// Calc difference between iframe and container
			dh = e.clientHeight - ifr.clientHeight;

			// Resize iframe and container
			DOM.setStyle(ifr, 'height', h - dh);
			DOM.setStyles(e, {width : w, height : h});
		},

		destroy : function() {
			var id = this.editor.id;

			Event.clear(id + '_resize');
			Event.clear(id + '_path_row');
			Event.clear(id + '_external_close');
		},

		// Internal functions

		_simpleLayout : function(s, tb, o, p) {
			var t = this, ed = t.editor, lo = s.theme_tsue_toolbar_location, sl = s.theme_tsue_statusbar_location, n, ic, etb, c;

			if (s.readonly) {
				n = DOM.add(tb, 'tr');
				n = ic = DOM.add(n, 'td', {'class' : 'mceIframeContainer'});
				return ic;
			}

			// Create toolbar container at top
			if (lo == 'top')
				t._addToolbars(tb, o);

			// Create external toolbar
			if (lo == 'external') {
				n = c = DOM.create('div', {style : 'position:relative'});
				n = DOM.add(n, 'div', {id : ed.id + '_external', 'class' : 'mceExternalToolbar'});
				DOM.add(n, 'a', {id : ed.id + '_external_close', href : 'javascript:;', 'class' : 'mceExternalClose'});
				n = DOM.add(n, 'table', {id : ed.id + '_tblext', cellSpacing : 0, cellPadding : 0});
				etb = DOM.add(n, 'tbody');

				if (p.firstChild.className == 'mceOldBoxModel')
					p.firstChild.appendChild(c);
				else
					p.insertBefore(c, p.firstChild);

				t._addToolbars(etb, o);

				ed.onMouseUp.add(function() {
					var e = DOM.get(ed.id + '_external');
					DOM.show(e);

					DOM.hide(lastExtID);

					var f = Event.add(ed.id + '_external_close', 'click', function() {
						DOM.hide(ed.id + '_external');
						Event.remove(ed.id + '_external_close', 'click', f);
					});

					DOM.show(e);
					DOM.setStyle(e, 'top', 0 - DOM.getRect(ed.id + '_tblext').h - 1);

					// Fixes IE rendering bug
					DOM.hide(e);
					DOM.show(e);
					e.style.filter = '';

					lastExtID = ed.id + '_external';

					e = null;
				});
			}

			if (sl == 'top')
				t._addStatusBar(tb, o);

			// Create iframe container
			if (!s.theme_tsue_toolbar_container) {
				n = DOM.add(tb, 'tr');
				n = ic = DOM.add(n, 'td', {'class' : 'mceIframeContainer'});
			}

			// Create toolbar container at bottom
			if (lo == 'bottom')
				t._addToolbars(tb, o);

			if (sl == 'bottom')
				t._addStatusBar(tb, o);

			return ic;
		},

		_rowLayout : function(s, tb, o) {
			var t = this, ed = t.editor, dc, da, cf = ed.controlManager, n, ic, to, a;

			dc = s.theme_tsue_containers_default_class || '';
			da = s.theme_tsue_containers_default_align || 'center';

			each(explode(s.theme_tsue_containers || ''), function(c, i) {
				var v = s['theme_tsue_container_' + c] || '';

				switch (v.toLowerCase()) {
					case 'mceeditor':
						n = DOM.add(tb, 'tr');
						n = ic = DOM.add(n, 'td', {'class' : 'mceIframeContainer'});
						break;

					case 'mceelementpath':
						t._addStatusBar(tb, o);
						break;

					default:
						a = (s['theme_tsue_container_' + c + '_align'] || da).toLowerCase();
						a = 'mce' + t._ufirst(a);

						n = DOM.add(DOM.add(tb, 'tr'), 'td', {
							'class' : 'mceToolbar ' + (s['theme_tsue_container_' + c + '_class'] || dc) + ' ' + a || da
						});

						to = cf.createToolbar('toolbar' + i);
						t._addControls(v, to);
						DOM.setHTML(n, to.renderHTML());
						o.deltaHeight -= s.theme_tsue_row_height;
				}
			});

			return ic;
		},

		_addControls : function(v, tb) {
			var t = this, s = t.settings, di, cf = t.editor.controlManager;

			if (s.theme_tsue_disable && !t._disabled) {
				di = {};

				each(explode(s.theme_tsue_disable), function(v) {
					di[v] = 1;
				});

				t._disabled = di;
			} else
				di = t._disabled;

			each(explode(v), function(n) {
				var c;

				if (di && di[n])
					return;

				// Compatiblity with 2.x
				if (n == 'tablecontrols') {
					each(['table','|','row_props','cell_props','|','row_before','row_after','delete_row','|','col_before','col_after','delete_col','|','split_cells','merge_cells'], function(n) {
						n = t.createControl(n, cf);

						if (n)
							tb.add(n);
					});

					return;
				}

				c = t.createControl(n, cf);

				if (c)
					tb.add(c);
			});
		},

		_addToolbars : function(c, o) {
			var t = this, i, tb, ed = t.editor, s = t.settings, v, cf = ed.controlManager, di, n, h = [], a;

			a = s.theme_tsue_toolbar_align.toLowerCase();
			a = 'mce' + t._ufirst(a);

			n = DOM.add(DOM.add(c, 'tr'), 'td', {'class' : 'mceToolbar ' + a});

			if (!ed.getParam('accessibility_focus'))
				h.push(DOM.createHTML('a', {href : '#', onfocus : 'tinyMCE.get(\'' + ed.id + '\').focus();'}, '<!-- IE -->'));

			h.push(DOM.createHTML('a', {href : '#', accesskey : 'q', title : '', onfocus : 'tinyMCE.getInstanceById(\'' + ed.id + '\').focus();'}, '<!-- IE -->'));

			// Create toolbar and add the controls
			for (i=1; (v = s['theme_tsue_buttons' + i]); i++) {
				tb = cf.createToolbar('toolbar' + i, {'class' : 'mceToolbarRow' + i});

				if (s['theme_tsue_buttons' + i + '_add'])
					v += ',' + s['theme_tsue_buttons' + i + '_add'];

				if (s['theme_tsue_buttons' + i + '_add_before'])
					v = s['theme_tsue_buttons' + i + '_add_before'] + ',' + v;

				t._addControls(v, tb);

				//n.appendChild(n = tb.render());
				h.push(tb.renderHTML());

				o.deltaHeight -= s.theme_tsue_row_height;
			}

			h.push(DOM.createHTML('a', {href : '#', accesskey : 'z', title : '', onfocus : 'tinyMCE.getInstanceById(\'' + ed.id + '\').focus();'}, '<!-- IE -->'));
			DOM.setHTML(n, h.join(''));
		},

		_addStatusBar : function(tb, o) {
			var n, t = this, ed = t.editor, s = t.settings, r, mf, me, td;

			n = DOM.add(tb, 'tr');
			n = td = DOM.add(n, 'td', {'class' : 'mceStatusbar'});
			n = DOM.add(n, 'div', {id : ed.id + '_path_row'}, '&#160;');
			DOM.add(n, 'a', {href : '#', accesskey : 'x'});

			if (s.theme_tsue_resizing) {
				DOM.add(td, 'a', {id : ed.id + '_resize', href : 'javascript:;', onclick : 'return false;', 'class' : 'mceResize'});

				if (s.theme_tsue_resizing_use_cookie) {
					ed.onPostRender.add(function() {
						var o = Cookie.getHash('TinyMCE_' + ed.id + '_size'), c = DOM.get(ed.id + '_tbl');

						if (!o)
							return;

						if (s.theme_tsue_resize_horizontal)
							c.style.width = Math.max(10, o.cw) + 'px';

						c.style.height = Math.max(10, o.ch) + 'px';
						DOM.get(ed.id + '_ifr').style.height = Math.max(10, parseInt(o.ch) + t.deltaHeight) + 'px';
					});
				}

				ed.onPostRender.add(function() {
					Event.add(ed.id + '_resize', 'click', function(e) {
						e.preventDefault();
					});

					Event.add(ed.id + '_resize', 'mousedown', function(e) {
						var mouseMoveHandler1, mouseMoveHandler2,
							mouseUpHandler1, mouseUpHandler2,
							startX, startY, startWidth, startHeight, width, height, ifrElm;

						function resizeOnMove(e) {
							e.preventDefault();

							width = startWidth + (e.screenX - startX);
							height = startHeight + (e.screenY - startY);

							t.resizeTo(width, height);
						};

						function endResize(e) {
							// Stop listening
							Event.remove(DOM.doc, 'mousemove', mouseMoveHandler1);
							Event.remove(ed.getDoc(), 'mousemove', mouseMoveHandler2);
							Event.remove(DOM.doc, 'mouseup', mouseUpHandler1);
							Event.remove(ed.getDoc(), 'mouseup', mouseUpHandler2);

							width = startWidth + (e.screenX - startX);
							height = startHeight + (e.screenY - startY);
							t.resizeTo(width, height, true);
						};

						e.preventDefault();

						// Get the current rect size
						startX = e.screenX;
						startY = e.screenY;
						ifrElm = DOM.get(t.editor.id + '_ifr');
						startWidth = width = ifrElm.clientWidth;
						startHeight = height = ifrElm.clientHeight;

						// Register envent handlers
						mouseMoveHandler1 = Event.add(DOM.doc, 'mousemove', resizeOnMove);
						mouseMoveHandler2 = Event.add(ed.getDoc(), 'mousemove', resizeOnMove);
						mouseUpHandler1 = Event.add(DOM.doc, 'mouseup', endResize);
						mouseUpHandler2 = Event.add(ed.getDoc(), 'mouseup', endResize);
					});
				});
			}

			o.deltaHeight -= 21;
			n = tb = null;
		},

		_nodeChanged : function(ed, cm, n, co, ob) {
			var t = this, p, de = 0, v, c, s = t.settings, cl, fz, fn, formatNames, matches;

			if (s.readonly)
				return;

			tinymce.each(t.stateControls, function(c) {
				cm.setActive(c, ed.queryCommandState(t.controls[c][1]));
			});

			cm.setActive('visualaid', ed.hasVisual);
			cm.setDisabled('undo', !ed.undoManager.hasUndo() && !ed.typing);
			cm.setDisabled('redo', !ed.undoManager.hasRedo());
			cm.setDisabled('outdent', !ed.queryCommandState('Outdent'));

			p = DOM.getParent(n, 'A');
			if (c = cm.get('link')) {
				if (!p || !p.name) {
					c.setDisabled(!p && co);
					c.setActive(!!p);
				}
			}

			if (c = cm.get('unlink')) {
				c.setDisabled(!p && co);
				c.setActive(!!p && !p.name);
			}

			if (c = cm.get('anchor')) {
				c.setActive(!!p && p.name);

				if (tinymce.isWebKit) {
					p = DOM.getParent(n, 'IMG');
					c.setActive(!!p && DOM.getAttrib(p, 'mce_name') == 'a');
				}
			}

			p = DOM.getParent(n, 'IMG');
			if (c = cm.get('image'))
				c.setActive(!!p && n.className.indexOf('mceItem') == -1);

			if (c = cm.get('styleselect')) {
				if (n.className) {
					t._importClasses();
					c.select(n.className);
				} else
					c.select();
			}

			if (c = cm.get('formatselect')) {
				p = DOM.getParent(n, DOM.isBlock);

				if (p)
					c.select(p.nodeName.toLowerCase());
			}

			// Find out current fontSize, fontFamily and fontClass
			if (ed.settings.convert_fonts_to_spans) {
				ed.dom.getParent(n, function(n) {
					if (n.nodeName === 'SPAN') {
						if (!cl && n.className)
							cl = n.className;

						if (!fz && n.style.fontSize)
							fz = n.style.fontSize;

						if (!fn && n.style.fontFamily)
							fn = n.style.fontFamily.replace(/[\"\']+/g, '').replace(/^([^,]+).*/, '$1').toLowerCase();
					}

					return false;
				});

				if (c = cm.get('fontselect')) {
					c.select(function(v) {
						return v.replace(/^([^,]+).*/, '$1').toLowerCase() == fn;
					});
				}

				if (c = cm.get('fontsizeselect')) {
					c.select(function(v) {
						if (v.fontSize && v.fontSize === fz)
							return true;

						if (v['class'] && v['class'] === cl)
							return true;
					});
				}
			} else {
				if (c = cm.get('fontselect'))
					c.select(ed.queryCommandValue('FontName'));

				if (c = cm.get('fontsizeselect')) {
					v = ed.queryCommandValue('FontSize');
					c.select(function(iv) {
						return iv.fontSize == v;
					});
				}
			}

			if (c = cm.get('fontselect')) {
				c.select(function(v) {
					return v.replace(/^([^,]+).*/, '$1').toLowerCase() == fn;
				});
			}

			// Select font size
			if (c = cm.get('fontsizeselect')) {
				// Use computed style
				if (s.theme_tsue_runtime_fontsize && !fz && !cl)
					fz = ed.dom.getStyle(n, 'fontSize', true);

				c.select(function(v) {
					if (v.fontSize && v.fontSize === fz)
						return true;

					if (v['class'] && v['class'] === cl)
						return true;
				});
			}

			if (s.theme_tsue_path && s.theme_tsue_statusbar_location) {
				p = DOM.get(ed.id + '_path') || DOM.add(ed.id + '_path_row', 'span', {id : ed.id + '_path'});
				DOM.setHTML(p, '');

				ed.dom.getParent(n, function(n) {
					var na = n.nodeName.toLowerCase(), u, pi, ti = '';

					// Ignore non element and hidden elements
					if (n.nodeType != 1 || n.nodeName === 'BR' || (DOM.hasClass(n, 'mceItemHidden') || DOM.hasClass(n, 'mceItemRemoved')))
						return;

					// Fake name
					if (v = DOM.getAttrib(n, 'mce_name'))
						na = v;

					// Handle prefix
					if (tinymce.isIE && n.scopeName !== 'HTML')
						na = n.scopeName + ':' + na;

					// Remove internal prefix
					na = na.replace(/mce\:/g, '');

					// Handle node name
					switch (na) {
						case 'b':
							na = 'strong';
							break;

						case 'i':
							na = 'em';
							break;

						case 'img':
							if (v = DOM.getAttrib(n, 'src'))
								ti += 'src: ' + v + ' ';

							break;

						case 'a':
							if (v = DOM.getAttrib(n, 'name')) {
								ti += 'name: ' + v + ' ';
								na += '#' + v;
							}

							if (v = DOM.getAttrib(n, 'href'))
								ti += 'href: ' + v + ' ';

							break;

						case 'font':
							if (s.convert_fonts_to_spans)
								na = 'span';

							if (v = DOM.getAttrib(n, 'face'))
								ti += 'font: ' + v + ' ';

							if (v = DOM.getAttrib(n, 'size'))
								ti += 'size: ' + v + ' ';

							if (v = DOM.getAttrib(n, 'color'))
								ti += 'color: ' + v + ' ';

							break;

						case 'span':
							if (v = DOM.getAttrib(n, 'style'))
								ti += 'style: ' + v + ' ';

							break;
					}

					if (v = DOM.getAttrib(n, 'id'))
						ti += 'id: ' + v + ' ';

					if (v = n.className) {
						v = v.replace(/(webkit-[\w\-]+|Apple-[\w\-]+|mceItem\w+|mceVisualAid)/g, '');

						if (v && v.indexOf('mceItem') == -1) {
							ti += 'class: ' + v + ' ';

							if (DOM.isBlock(n) || na == 'img' || na == 'span')
								na += '.' + v;
						}
					}

					na = na.replace(/(html:)/g, '');
					na = {name : na, node : n, title : ti};
					t.onResolveName.dispatch(t, na);
					ti = na.title;
					na = na.name;

					//u = "javascript:tinymce.EditorManager.get('" + ed.id + "').theme._sel('" + (de++) + "');";
					pi = DOM.create('a', {'href' : "javascript:;", onmousedown : "return false;", title : ti, 'class' : 'mcePath_' + (de++)}, na);

					if (p.hasChildNodes()) {
						p.insertBefore(DOM.doc.createTextNode(' \u00bb '), p.firstChild);
						p.insertBefore(pi, p.firstChild);
					} else
						p.appendChild(pi);
				}, ed.getBody());
			}
		},

		// Commands gets called by execCommand

		_sel : function(v) {
			this.editor.execCommand('mceSelectNodeDepth', false, v);
		},

		_mceColorPicker : function(u, v) {
			var ed = this.editor;

			v = v || {};

			ed.windowManager.open({
				url : TSUESettings['website_url']+'/?p=dialog&dialog=color_picker',
				width : 375 + parseInt(ed.getLang('tsue.colorpicker_delta_width', 0)),
				height : 250 + parseInt(ed.getLang('tsue.colorpicker_delta_height', 0)),
				close_previous : false,
				inline : true,
				translate_i18n : false
			}, {
				input_color : v.color,
				func : v.func,
				theme_url : this.url
			});
		},

		_mceImage : function(ui, val) {
			var ed = this.editor;

			// Internal image object like a flash placeholder
			if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
				return;

			ed.windowManager.open({
				url : TSUESettings['website_url']+'/?p=dialog&dialog=image',
				width : 960,
				height : ($(window).height()-150),
				inline : false,
				scrollbars:true,
				resizable: true,
				maximizable: true,
				translate_i18n : false
			}, {
				theme_url : this.url
			});
		},

		_mceLink : function(ui, val) {
			var ed = this.editor;

			ed.windowManager.open({
				url : TSUESettings['website_url']+'/?p=dialog&dialog=link',
				width : 400,
				height : 30,
				inline : true,
				translate_i18n : false
			}, {
				theme_url : this.url
			});
		},

		_mceForeColor : function() {
			var t = this;

			this._mceColorPicker(0, {
				color: t.fgColor,
				func : function(co) {
					t.fgColor = co;
					t.editor.execCommand('ForeColor', false, co);
				}
			});
		},

		_ufirst : function(s) {
			return s.substring(0, 1).toUpperCase() + s.substring(1);
		}
	});

	tinymce.ThemeManager.add('tsue', tinymce.themes.TSUETheme);
}(tinymce));

(function() {
	var DOM = tinymce.DOM, Element = tinymce.dom.Element, Event = tinymce.dom.Event, each = tinymce.each, is = tinymce.is;

	tinymce.create('tinymce.plugins.InlinePopups', {
		init : function(ed, url) {
			// Replace window manager
			ed.onBeforeRenderUI.add(function() {
				ed.windowManager = new tinymce.InlineWindowManager(ed);
			});
		},

		getInfo : function() {
			return {
				longname : 'InlinePopups',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/inlinepopups',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	tinymce.create('tinymce.InlineWindowManager:tinymce.WindowManager', {
		InlineWindowManager : function(ed) {
			var t = this;

			t.parent(ed);
			t.zIndex = 300000;
			t.count = 0;
			t.windows = {};
		},
		open : function(f, p) {
			var t = this, id, opt = '', ed = t.editor, dw = 0, dh = 0, vp, po, mdf, clf, we, w, u;

			f = f || {};
			p = p || {};

			// Run native windows
			if (!f.inline)
				return t.parent(f, p);

			// Only store selection if the type is a normal window
			if (!f.type)
				t.bookmark = ed.selection.getBookmark(1);

			id = DOM.uniqueId();
			vp = DOM.getViewPort();
			f.width = parseInt(f.width || 320);
			f.height = parseInt(f.height || 240) + (tinymce.isIE ? 8 : 0);
			f.min_width = parseInt(f.min_width || 150);
			f.min_height = parseInt(f.min_height || 100);
			f.max_width = parseInt(f.max_width || 2000);
			f.max_height = parseInt(f.max_height || 2000);
			f.left = f.left || Math.round(Math.max(vp.x, vp.x + (vp.w / 2.0) - (f.width / 2.0)));
			f.top = f.top || Math.round(Math.max(vp.y, vp.y + (vp.h / 2.0) - (f.height / 2.0)));
			f.movable = f.resizable = true;
			p.mce_width = f.width;
			p.mce_height = f.height;
			p.mce_inline = true;
			p.mce_window_id = id;
			p.mce_auto_focus = f.auto_focus;

			// Transpose
//			po = DOM.getPos(ed.getContainer());
//			f.left -= po.x;
//			f.top -= po.y;

			t.features = f;
			t.params = p;
			t.onOpen.dispatch(t, f, p);

			if (f.type) {
				opt += ' mceModal';

				if (f.type)
					opt += ' mce' + f.type.substring(0, 1).toUpperCase() + f.type.substring(1);

				f.resizable = false;
			}

			t._addAll(DOM.doc.body,
				['div', {id: id, 'class': 'editorInlinePopup', style: 'width:100px; height:100px'},
					['div', {id: id + '_content', 'class': 'popupContent', style: 'height:100%'}]
				]
			);

			DOM.setStyles(id, {top : -10000, left : -10000});
			DOM.setStyles(id, {top : f.top, left : f.left, width : f.width + dw});

			u = f.url || f.file;
			if (u) {
				if (tinymce.relaxedDomain)
					u += (u.indexOf('?') == -1 ? '?' : '&') + 'mce_rdomain=' + tinymce.relaxedDomain;

				u = tinymce._addVer(u);
			}

			if (!f.type) {
				DOM.add(id + '_content', 'iframe', {id : id + '_ifr', src : 'javascript:;', frameBorder : 0, scrolling: 'no', style : 'border:0; width:100%; height: 100%;'});
				Event.add(id + '_ifr', 'load', function(e) {
					DOM.setStyles(id, { height: e.target.contentWindow.document.documentElement.scrollHeight });
					DOM.setStyles(id + '_ifr', { height: e.target.contentWindow.document.documentElement.scrollHeight });
				});
				DOM.setAttrib(id + '_ifr', 'src', u);
			} else {
				DOM.add(id + '_wrapper', 'a', {id : id + '_ok', 'class' : 'mceButton mceOk', href : 'javascript:;', onmousedown : 'return false;'}, 'Ok');

				if (f.type == 'confirm')
					DOM.add(id + '_wrapper', 'a', {'class' : 'mceButton mceCancel', href : 'javascript:;', onmousedown : 'return false;'}, 'Cancel');

				DOM.add(id + '_middle', 'div', {'class' : 'mceIcon'});
				DOM.setHTML(id + '_content', f.content.replace('\n', '<br />'));
			}

			// Add window
			w = t.windows[id] = {
				id : id,
				mousedown_func : mdf,
				click_func : clf,
				element : new Element(id, {blocker : 1, container : ed.getContainer()}),
				iframeElement : new Element(id + '_ifr'),
				features : f,
				deltaWidth : dw,
				deltaHeight : dh
			};

			w.iframeElement.on('focus', function() {
				t.focus(id);
			});

			// Setup blocker
			if (t.count == 0 && t.editor.getParam('dialog_type', 'modal') == 'modal') {
				DOM.add(DOM.doc.body, 'div', {
					id : 'mceModalBlocker',
					'class' : 'editorInlinePopup_modalBlocker',
					style : {zIndex : t.zIndex - 1}
				});
				Event.add('mceModalBlocker', 'click', function()
				{
					t.close(null, id);
				});

				DOM.show('mceModalBlocker'); // Reduces flicker in IE
			} else
				DOM.setStyle('mceModalBlocker', 'z-index', t.zIndex - 1);

			if (tinymce.isIE6 || /Firefox\/2\./.test(navigator.userAgent) || (tinymce.isIE && !DOM.boxModel))
				DOM.setStyles('mceModalBlocker', {position : 'absolute', left : vp.x, top : vp.y, width : vp.w - 2, height : vp.h - 2});

			t.focus(id);
			t._fixIELayout(id, 1);

			// Focus ok button
			if (DOM.get(id + '_ok'))
				DOM.get(id + '_ok').focus();

			t.count++;

			return w;
		},

		focus : function(id) {
			var t = this, w;

			if (w = t.windows[id]) {
				w.zIndex = this.zIndex++;
				w.element.setStyle('zIndex', w.zIndex);
				w.element.update();

				id = id + '_wrapper';
				DOM.removeClass(t.lastId, 'mceFocus');
				DOM.addClass(id, 'mceFocus');
				t.lastId = id;
			}
		},

		_addAll : function(te, ne) {
			var i, n, t = this, dom = tinymce.DOM;

			if (is(ne, 'string'))
				te.appendChild(dom.doc.createTextNode(ne));
			else if (ne.length) {
				te = te.appendChild(dom.create(ne[0], ne[1]));

				for (i=2; i<ne.length; i++)
					t._addAll(te, ne[i]);
			}
		},

		resizeBy : function(dw, dh, id) {
			return;
		},

		close : function(win, id) {
			var t = this, w, d = DOM.doc, ix = 0, fw, id;

			id = t._findId(id || win);

			// Probably not inline
			if (!t.windows[id]) {
				t.parent(win);
				return;
			}

			t.count--;

			if (t.count == 0)
				DOM.remove('mceModalBlocker');

			if (w = t.windows[id]) {
				t.onClose.dispatch(t);
				Event.remove(d, 'mousedown', w.mousedownFunc);
				Event.remove(d, 'click', w.clickFunc);
				Event.clear(id);
				Event.clear(id + '_ifr');

				DOM.setAttrib(id + '_ifr', 'src', 'javascript:""'); // Prevent leak
				w.element.remove();
				delete t.windows[id];

				// Find front most window and focus that
				each (t.windows, function(w) {
					if (w.zIndex > ix) {
						fw = w;
						ix = w.zIndex;
					}
				});

				if (fw)
					t.focus(fw.id);
			}
		},

		setTitle : function(w, ti) {
			var e;

			w = this._findId(w);

			if (e = DOM.get(w + '_title'))
				e.innerHTML = DOM.encode(ti);
		},

		alert : function(txt, cb, s) {
			var t = this, w;

			w = t.open({
				title : t,
				type : 'alert',
				button_func : function(s) {
					if (cb)
						cb.call(s || t, s);

					t.close(null, w.id);
				},
				content : DOM.encode(t.editor.getLang(txt, txt)),
				inline : 1,
				width : 400,
				height : 130
			});
		},

		confirm : function(txt, cb, s) {
			var t = this, w;

			w = t.open({
				title : t,
				type : 'confirm',
				button_func : function(s) {
					if (cb)
						cb.call(s || t, s);

					t.close(null, w.id);
				},
				content : DOM.encode(t.editor.getLang(txt, txt)),
				inline : 1,
				width : 400,
				height : 130
			});
		},

		// Internal functions

		_findId : function(w) {
			var t = this;

			if (typeof(w) == 'string')
				return w;

			each(t.windows, function(wo) {
				var ifr = DOM.get(wo.id + '_ifr');

				if (ifr && w == ifr.contentWindow) {
					w = wo.id;
					return false;
				}
			});

			return w;
		},

		_fixIELayout : function(id, s) {
			var w, img;

			if (!tinymce.isIE6)
				return;

			// Fixes graphics glitch
			if (w = this.windows[id]) {
				// Fixes rendering bug after resize
				w.element.hide();
				w.element.show();

				// Forced a repaint of the window
				//DOM.get(id).style.filter = '';

				// IE has a bug where images used in CSS won't get loaded
				// sometimes when the cache in the browser is disabled
				// This fix tries to solve it by loading the images using the image object
				each(DOM.select('div,a', id), function(e, i) {
					if (e.currentStyle.backgroundImage != 'none') {
						img = new Image();
						img.src = e.currentStyle.backgroundImage.replace(/url\(\"(.+)\"\)/, '$1');
					}
				});

				DOM.get(id).style.filter = '';
			}
		}
	});

	// Register plugin
	tinymce.PluginManager.add('inlinepopups', tinymce.plugins.InlinePopups);
})();