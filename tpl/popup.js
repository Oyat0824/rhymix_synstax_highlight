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
		"border: 1px solid #4a4a4a !important; " +
		"border-left: 4px solid #4a90e2 !important; " +
		"padding: 12px !important; " +
		"background: #2a2a2a url('./modules/editor/components/synstax_highlight/component_icon.gif') no-repeat right 8px top 8px !important; " +
		"color: #d0d0d0 !important; " +
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

	// 중복 클릭 방지
	if(btn.classList.contains("copy-message")) {
		return;
	}

	// 이전 타이머 클리어
	var existingTimer = btn.getAttribute("data-copy-timer");
	if(existingTimer) {
		clearTimeout(parseInt(existingTimer));
	}

	// 리셋 타이머 클리어 및 재설정
	var resetTimer = btn.getAttribute("data-reset-timer");
	if(resetTimer) {
		clearTimeout(parseInt(resetTimer));
	}

	// 클릭 횟수 추적
	var clickCount = parseInt(btn.getAttribute("data-copy-count") || "0");
	clickCount++;
	btn.setAttribute("data-copy-count", clickCount);

	// 10초 후 클릭 횟수 리셋
	var resetTimerId = setTimeout(function() {
		btn.setAttribute("data-copy-count", "0");
		btn.removeAttribute("data-reset-timer");
	}, 10000);
	btn.setAttribute("data-reset-timer", resetTimerId);

	// 이스터에그 메시지들
	var messages = [
		"복사 완료!",
		"또 복사?",
		"계속 복사하네?",
		"진짜 많이 복사하시네...",
		"복사 마니아시군요!",
		"복사 중독자 발견!",
		"복사왕 등장!",
		"복사 신이시네요!",
		"복사의 신!!!!!!!!!!"
	];
	var messageIndex = Math.min(clickCount - 1, messages.length - 1);
	var message = messages[messageIndex];

	// 떨림 효과 클래스 (3단계 이상부터 적용)
	var shakeClass = "";
	if(clickCount >= 3 && clickCount < 7) {
		shakeClass = "copy-shake";
	} else if(clickCount >= 7) {
		shakeClass = "copy-shake-intense";
	}

	var copySuccess = function() {
		var originalText = btn.getAttribute("data-original-text") || "Copy";
		if(!btn.getAttribute("data-original-text")) {
			btn.setAttribute("data-original-text", originalText);
		}

		btn.textContent = message;
		btn.classList.add("copy-message");
		if(shakeClass) {
			btn.classList.add(shakeClass);
		}

		var timer = setTimeout(function() {
			btn.textContent = originalText;
			btn.classList.remove("copy-message", shakeClass);
			btn.removeAttribute("data-copy-timer");
		}, 2500);

		btn.setAttribute("data-copy-timer", timer);
	};

	if(navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(copySuccess).catch(function(err) {
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
			copySuccess();
		} catch(err) {
		}
		document.body.removeChild(textArea);
	}
};
