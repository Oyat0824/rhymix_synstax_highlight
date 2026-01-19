function getCode()
{
	if(typeof(opener) == "undefined") return;

	var node = opener.editorPrevNode;
	var form$ = jQuery('#fo');

	if(!node || node.nodeName != 'DIV')
	{
		var code = opener.editorGetSelectedHtml(opener.editorPrevSrl);
		code = getArrangedCode(code, 'textarea');
		form$.find('textarea[name=code]').val(code);
		return;
	}

	var opt = getArrangedOption(jQuery(node));
	opt.code = getArrangedCode(opt.code, 'textarea');

	form$.find('select[name=code_type]').val(opt.code_type || 'javascript');
	form$.find('textarea[name=code]').val(opt.code);
}

function insertCode()
{
	if(typeof(opener) == "undefined") return;

	var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl);
	var prevNode = opener.editorPrevNode;

	// 수정 모드인지 확인 (기존 코드 블록을 수정하는 경우)
	var isEditMode = prevNode && prevNode.nodeName == 'DIV' &&
					prevNode.getAttribute('editor_component') === 'synstax_highlight';

	// 수정 모드가 아닐 때만 중첩 체크 수행
	if(!isEditMode) {
		try {
			if(iframe_obj) {
				var actualIframe = iframe_obj.querySelector('iframe');
				if(actualIframe && actualIframe.contentWindow && actualIframe.contentWindow.document) {
					var iframeDoc = actualIframe.contentWindow.document;
					var selection = iframeDoc.getSelection ? iframeDoc.getSelection() :
									(iframeDoc.defaultView && iframeDoc.defaultView.getSelection ?
									iframeDoc.defaultView.getSelection() : null);

					if(selection && selection.rangeCount > 0) {
						var range = selection.getRangeAt(0);
						var container = range.startContainer;
						var node = container.nodeType === 3 ? container.parentNode : container;

						while(node && node.nodeType !== 9) {
							if(node.nodeType === 1 && node.nodeName === 'DIV' &&
							node.getAttribute('editor_component') === 'synstax_highlight') {
								alert('코드 블록 내부에는 다른 코드 블록을 삽입할 수 없습니다.');
								return;
							}
							node = node.parentNode;
						}
					}
				}
			}
		} catch(e) {}
	}

	var form$ = jQuery('#fo');
	var opt = getArrangedOption(form$);
	opt.code = getArrangedCode(opt.code, 'wyswig');

	var style = "font-family: 'Consolas', 'Monaco', 'Courier New', monospace !important; " +
		"border: 1px solid #e1e8ed !important; " +
		"border-left: 4px solid #4a90e2 !important; " +
		"padding: 12px !important; " +
		"background: #f8f9fa url('./modules/editor/components/synstax_highlight/component_icon.gif') no-repeat top right !important; " +
		"border-radius: 4px !important; " +
		"margin: 10px 0 !important;";

	var html = '<div editor_component="synstax_highlight" ' +
		'code_type="' + (opt.code_type || 'javascript') + '" ' +
		'style="' + style + '">' +
		opt.code + '</div><br />';

	if (prevNode && prevNode.nodeName == 'DIV' && prevNode.getAttribute('editor_component') != null) {
		prevNode.setAttribute('code_type', opt.code_type || 'javascript');
		prevNode.setAttribute('style', style);
		prevNode.innerHTML = opt.code;
	}
	else
	{
		opener.editorReplaceHTML(iframe_obj, html);
	}
	opener.editorFocus(opener.editorPrevSrl);

	window.close();
}

function getArrangedOption(elem$)
{
	if(!elem$.size()) return {};

	var node = elem$[0];
	var opt = {};

	if(node.nodeName == 'FORM')
	{
		opt.code_type = elem$.find('select[name=code_type]').val() || 'javascript';
		opt.code = elem$.find('textarea[name=code]').val() || '';
	}
	else
	{
		opt.code_type = node.getAttribute('code_type') || 'javascript';
		opt.code = elem$.html() || '';
	}

	return opt;
}

function getArrangedCode(code, outputType)
{
	if(!outputType) outputType = 'textarea';

	if(outputType == 'wyswig')
	{
		code = code.replace(/</g, "&lt;");
		code = code.replace(/>/g, "&gt;");
		var lines = code.split('\n');
		lines = lines.map(function(line) {
			return line.replace(/^(\s+)/, function(match) {
				return match.replace(/ /g, '&nbsp;');
			});
		});
		code = lines.join('<br />\n');
	}

	if(outputType == 'textarea')
	{
		code = code.replace(/\r|\n/g, '');
		code = code.replace(/<\/p>/gi, "\n");
		code = code.replace(/<br\s*\/?>/gi, "\n");
		code = code.replace(/(<([^>]+)>)/gi,"");;
		code = code.replace(/&nbsp;/g, ' ');
		code = code.replace(/&lt;/g, '<');
		code = code.replace(/&gt;/g, '>');
	}
	else if(outputType == 'preview')
	{
		code = code.replace(/</g, '&lt;');
		code = code.replace(/>/g, '&gt;');
	}

	code = jQuery.trim(code);

	return code;
}

window.copyCodeToClipboard = function(btn) {
	var text = btn.getAttribute("data-clipboard-text") || btn.closest("pre").querySelector("code").textContent;
	if(!text) return;
	if(navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(function() {
			var originalText = btn.textContent;
			btn.textContent = "복사 완료!";
			btn.classList.add("copy-message");
			setTimeout(function() {
				btn.textContent = originalText;
				btn.classList.remove("copy-message");
			}, 2000);
		}).catch(function(err) {
		});
	} else {
		var textArea = document.createElement("textarea");
		textArea.value = text;
		textArea.style.position = "fixed";
		textArea.style.left = "-999999px";
		document.body.appendChild(textArea);
		textArea.select();
		try {
			document.execCommand("copy");
			var originalText = btn.textContent;
			btn.textContent = "복사 완료!";
			btn.classList.add("copy-message");
			setTimeout(function() {
				btn.textContent = originalText;
				btn.classList.remove("copy-message");
			}, 2000);
		} catch(err) {
		}
		document.body.removeChild(textArea);
	}
};
