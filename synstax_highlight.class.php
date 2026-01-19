<?php
/**
 * 코드 문법 강조 컴포넌트
 * Highlight.js를 사용하여 코드 블록에 문법 강조를 적용합니다.
 */
class synstax_highlight extends EditorHandler
{
	public $editor_sequence = 0;
	public $component_path = '';
	public $theme = 'atom-one-dark-reasonable';
	private $available_languages_cache = null;

	public $themes = array(
		array('atom-one-dark-reasonable', 'Atom One Dark (Reasonable)'),
		array('atom-one-dark', 'Atom One Dark'),
		array('monokai', 'Monokai'),
		array('vs2015', 'Visual Studio 2015 Dark'),
		array('night-owl', 'Night Owl'),
	);

	private function getThemeName(string $theme_id): string
	{
		foreach($this->themes as $theme_item) {
			if($theme_item[0] === $theme_id) {
				return $theme_item[1];
			}
		}
		return 'Atom One Dark (Reasonable)';
	}

	public $languages = array(
		array('AppleScript', 'applescript', 'applescript'),
		array('Bash (Shell)', 'bash', 'bash shell sh zsh'),
		array('C', 'c', 'c'),
		array('C/C++', 'cpp', 'cpp c++ c'),
		array('C#', 'csharp', 'c# c-sharp csharp cs'),
		array('CSS', 'css', 'css'),
		array('Groovy', 'groovy', 'groovy'),
		array('Java', 'java', 'java'),
		array('JavaScript', 'javascript', 'js jscript javascript'),
		array('JSON', 'json', 'json'),
		array('Perl', 'perl', 'perl pl'),
		array('PHP', 'php', 'php'),
		array('PowerShell', 'powershell', 'powershell ps ps1'),
		array('Python', 'python', 'python py'),
		array('Ruby', 'ruby', 'ruby rails ror rb'),
		array('SCSS/Sass', 'scss', 'scss sass'),
		array('Scala', 'scala', 'scala'),
		array('SQL', 'sql', 'sql'),
		array('TypeScript', 'typescript', 'typescript ts'),
		array('XML/HTML', 'xml', 'xml xhtml xslt html svg'),
		array('YAML', 'yaml', 'yaml yml'),
	);

	/**
	 * 컴포넌트 초기화 및 테마 설정
	 * @param int $editor_sequence 에디터 시퀀스
	 * @param string $component_path 컴포넌트 경로
	 */
	function __construct($editor_sequence, $component_path)
	{
		$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;

		$theme_value = $this->getComponentInfoValue('theme');
		if($theme_value) {
			$this->theme = $this->getThemeString($theme_value);
		} else {
			$this->theme = $this->getThemeFromConfig() ?? 'atom-one-dark-reasonable';
		}

		if(!$this->theme || !is_string($this->theme)) {
			$this->theme = 'atom-one-dark-reasonable';
		}
	}

	/**
	 * 실제 파일이 존재하는 언어 목록 반환 (캐시 사용)
	 * @return array 사용 가능한 언어 배열
	 */
	private function getAvailableLanguages(): array
	{
		if($this->available_languages_cache !== null) {
			return $this->available_languages_cache;
		}

		$available_languages = array();
		foreach($this->languages as $lang) {
			$lang_file = $this->component_path.'highlightjs/languages/'.$lang[1].'.min.js';
			if(file_exists($lang_file)) {
				$available_languages[] = $lang;
			}
		}

		$this->available_languages_cache = $available_languages;
		return $available_languages;
	}

	private function getComponentInfoValue(string $key)
	{
		$sources = array();
		if(isset($this->component_info) && isset($this->component_info->extra_vars)) {
			$sources[] = $this->component_info->extra_vars;
		}
		$component_info = Context::get('component_info');
		if($component_info && isset($component_info->extra_vars)) {
			$sources[] = $component_info->extra_vars;
		}

		foreach($sources as $extra_vars) {
			if(is_object($extra_vars) && isset($extra_vars->$key)) {
				return $extra_vars->$key;
			}
			if(is_array($extra_vars) && isset($extra_vars[$key])) {
				return $extra_vars[$key];
			}
		}

		return null;
	}

	private function getThemeFromConfig(): ?string
	{
		$oModuleModel = getModel('module');
		$editor_config = $oModuleModel->getModuleConfig('editor');
		if($editor_config && isset($editor_config->synstax_highlight) && isset($editor_config->synstax_highlight->theme)) {
			return $this->getThemeString($editor_config->synstax_highlight->theme);
		}
		return null;
	}

	private function getThemeString($theme_value): string
	{
		if(is_object($theme_value) && isset($theme_value->theme)) {
			return (string)$theme_value->theme;
		}
		$str = $this->getStringValue($theme_value);
		return $str ?: 'atom-one-dark-reasonable';
	}

	private function getStringValue($value): ?string
	{
		if(is_string($value)) return $value;
		if(is_object($value)) {
			if(isset($value->value)) return (string)$value->value;
			if(method_exists($value, '__toString')) return (string)$value;
			$vars = get_object_vars($value);
			return !empty($vars) ? (string)reset($vars) : null;
		}
		if(is_array($value)) {
			return isset($value['value']) ? (string)$value['value'] : (isset($value[0]) ? (string)$value[0] : null);
		}
		return $value !== null ? (string)$value : null;
	}

	/**
	 * 팝업에 표시할 언어 목록 반환
	 * 설정된 언어가 있으면 해당 언어만, 없으면 기본 추천 언어 반환
	 * @return array 표시할 언어 배열
	 */
	private function getDisplayLanguages(): array
	{
		$available_languages = $this->getAvailableLanguages();
		$enabled_lang_str = $this->getStringValue($this->getComponentInfoValue('enabled_languages'));

		if($enabled_lang_str && trim($enabled_lang_str) !== '') {
			$enabled_lang_ids = array_map('strtolower', array_filter(array_map('trim', explode(',', $enabled_lang_str))));
			if(!empty($enabled_lang_ids)) {
				$filtered = array_values(array_filter($available_languages, function($lang) use ($enabled_lang_ids) {
					return in_array(strtolower($lang[1]), $enabled_lang_ids);
				}));
				return !empty($filtered) ? $filtered : $available_languages;
			}
		}

		$default_languages = array('javascript', 'python', 'java', 'php', 'cpp', 'xml', 'css', 'sql', 'json', 'bash');
		$filtered = array_values(array_filter($available_languages, function($lang) use ($default_languages) {
			return in_array($lang[1], $default_languages);
		}));
		return !empty($filtered) ? $filtered : $available_languages;
	}

	private function getLanguageName(string $code_type, string $lang_id): string
	{
		$code_type_lower = strtolower($code_type);

		foreach($this->languages as $lang) {
			$lang_id_lower = strtolower($lang[1]);
			$lang_display_lower = strtolower($lang[0]);
			$aliases = explode(' ', strtolower($lang[2]));

			if($code_type_lower === $lang_id_lower || $code_type_lower === $lang_display_lower || in_array($code_type_lower, $aliases, true)) {
				return $lang[0];
			}
		}

		foreach($this->languages as $lang) {
			if(strtolower($lang[1]) === strtolower($lang_id)) {
				return $lang[0];
			}
		}

		return 'JavaScript';
	}

	/**
	 * HTML 태그 제거 및 텍스트 정규화
	 * @param string $text 원본 텍스트
	 * @return string 정규화된 텍스트
	 */
	private function normalizeText(string $text): string
	{
		$text = strip_tags($text, '<br>');
		$text = preg_replace("/(<br\s*\/?>)(\n|\r)*/i", "\n", $text);
		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = str_replace('&nbsp;', ' ', $text);
		return $text;
	}

	/**
	 * 전역 하이라이트 스크립트 생성
	 * 페이지의 모든 코드 블록에 하이라이트 적용
	 * @return string JavaScript 코드
	 */
	private function generateGlobalHighlightScript(): string
	{
		$js = '<script type="text/javascript">'.PHP_EOL;
		$js .= 'if(typeof hljs !== "undefined"){'.PHP_EOL;
		$js .= 'hljs.configure({languages: []});'.PHP_EOL;
		$js .= 'function initHighlightJS(){'.PHP_EOL;
		$js .= 'var codeBlocks = document.querySelectorAll("code[class*=\'language-\']:not([data-lang]):not([data-inline-script]), code:not([data-lang]):not([data-inline-script])");'.PHP_EOL;
		$js .= 'codeBlocks.forEach(function(block) {'.PHP_EOL;
		$js .= 'if(block.dataset.highlighted === "yes") return;'.PHP_EOL;
		$js .= 'if(block.getAttribute("data-inline-script") === "true") return;'.PHP_EOL;
		$js .= 'var lang = block.getAttribute("data-lang");'.PHP_EOL;
		$js .= 'if(!lang) {'.PHP_EOL;
		$js .= 'var langMatch = block.className.match(/language-(\\w+)/);'.PHP_EOL;
		$js .= 'if(langMatch && langMatch[1]) lang = langMatch[1];'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= 'if(!lang) return;'.PHP_EOL;
		$js .= 'var originalText = block.innerHTML;'.PHP_EOL;
		$js .= 'if(originalText) {'.PHP_EOL;
		$js .= 'var div = document.createElement("div");'.PHP_EOL;
		$js .= 'div.innerHTML = originalText;'.PHP_EOL;
		$js .= 'originalText = div.textContent || div.innerText;'.PHP_EOL;
		$js .= 'originalText = originalText.replace(/&nbsp;/g, " ");'.PHP_EOL;
		$js .= 'originalText = originalText.replace(/[\u00A0\u2000-\u200B\u2028\u2029\uFEFF]/g, " ");'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= 'if(!originalText || originalText.trim() === "") return;'.PHP_EOL;
		$js .= 'try {'.PHP_EOL;
		$js .= 'var result = hljs.highlight(originalText, {language: lang, ignoreIllegals: true});'.PHP_EOL;
		$js .= 'if(result && result.value) {'.PHP_EOL;
		$js .= 'block.innerHTML = result.value;'.PHP_EOL;
		$js .= 'block.className = "hljs language-" + lang;'.PHP_EOL;
		$js .= 'block.setAttribute("data-lang", lang);'.PHP_EOL;
		$js .= 'block.dataset.highlighted = "yes";'.PHP_EOL;
		$js .= 'if(pre) pre.classList.add("hljs");'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '} catch(e) {'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '});'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= 'if(document.readyState === "loading"){'.PHP_EOL;
		$js .= 'document.addEventListener("DOMContentLoaded", initHighlightJS);'.PHP_EOL;
		$js .= '}else{setTimeout(initHighlightJS, 200);}'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '</script>'.PHP_EOL;
		return $js;
	}

	/**
	 * 코드 복사 버튼 스크립트 생성
	 * @return string JavaScript 코드
	 */
	private function generateCopyButtonScript(): string
	{
		$js = '<script type="text/javascript">'.PHP_EOL;
		$js .= 'window.copyCodeToClipboard = function(btn) {'.PHP_EOL;
		$js .= 'var text = btn.getAttribute("data-clipboard-text") || btn.closest("pre").querySelector("code").textContent;'.PHP_EOL;
		$js .= 'if(!text) return;'.PHP_EOL;
		$js .= 'if(navigator.clipboard && navigator.clipboard.writeText) {'.PHP_EOL;
		$js .= 'navigator.clipboard.writeText(text).then(function() {'.PHP_EOL;
		$js .= 'var originalText = btn.textContent;'.PHP_EOL;
		$js .= 'btn.textContent = "Copied!"; btn.classList.add("copy-message");'.PHP_EOL;
		$js .= 'setTimeout(function() { btn.textContent = originalText; btn.classList.remove("copy-message"); }, 2000);'.PHP_EOL;
		$js .= '}).catch(function(err) { });'.PHP_EOL;
		$js .= '} else {'.PHP_EOL;
		$js .= 'var textArea = document.createElement("textarea");'.PHP_EOL;
		$js .= 'textArea.value = text; textArea.style.position = "fixed"; textArea.style.left = "-999999px";'.PHP_EOL;
		$js .= 'document.body.appendChild(textArea); textArea.select();'.PHP_EOL;
		$js .= 'try { document.execCommand("copy"); var originalText = btn.textContent;'.PHP_EOL;
		$js .= 'btn.textContent = "Copied!"; btn.classList.add("copy-message");'.PHP_EOL;
		$js .= 'setTimeout(function() { btn.textContent = originalText; btn.classList.remove("copy-message"); }, 2000);'.PHP_EOL;
		$js .= '} catch(err) { }'.PHP_EOL;
		$js .= 'document.body.removeChild(textArea);'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '};'.PHP_EOL;
		$js .= '</script>'.PHP_EOL;
		return $js;
	}

	/**
	 * 인라인 하이라이트 스크립트 생성
	 * 개별 코드 블록에 하이라이트 적용
	 * @param string $lang_id 언어 ID
	 * @return string JavaScript 코드
	 */
	private function generateInlineHighlightScript(string $lang_id): string
	{
		$js = '<script>'.PHP_EOL;
		$js .= '(function() {'.PHP_EOL;
		$js .= 'var pre = document.currentScript.previousElementSibling;'.PHP_EOL;
		$js .= 'function applyHighlight() {'.PHP_EOL;
		$js .= 'if(typeof hljs === "undefined") {'.PHP_EOL;
		$js .= 'setTimeout(applyHighlight, 100);'.PHP_EOL;
		$js .= 'return;'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= 'var code = pre.querySelector("code");'.PHP_EOL;
		$js .= 'if(code && !code.dataset.highlighted) {'.PHP_EOL;
		$js .= 'var lang = code.getAttribute("data-lang");'.PHP_EOL;
		$js .= 'if(!lang) return;'.PHP_EOL;
		$js .= 'var originalText = code.innerHTML;'.PHP_EOL;
		$js .= 'if(originalText) {'.PHP_EOL;
		$js .= 'var div = document.createElement("div");'.PHP_EOL;
		$js .= 'div.innerHTML = originalText;'.PHP_EOL;
		$js .= 'originalText = div.textContent || div.innerText;'.PHP_EOL;
		$js .= 'originalText = originalText.replace(/&nbsp;/g, " ");'.PHP_EOL;
		$js .= 'originalText = originalText.replace(/[\u00A0\u2000-\u200B\u2028\u2029\uFEFF]/g, " ");'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= 'if(!originalText || originalText.trim() === "") return;'.PHP_EOL;
		$js .= 'try {'.PHP_EOL;
		$js .= 'var result = hljs.highlight(originalText, {language: lang, ignoreIllegals: true});'.PHP_EOL;
		$js .= 'if(result && result.value) {'.PHP_EOL;
		$js .= 'code.innerHTML = result.value;'.PHP_EOL;
		$js .= 'code.className = "hljs language-" + lang;'.PHP_EOL;
		$js .= 'code.dataset.highlighted = "yes";'.PHP_EOL;
		$js .= 'pre.classList.add("hljs");'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '} catch(e) {'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= 'if(document.readyState === "loading") {'.PHP_EOL;
		$js .= 'document.addEventListener("DOMContentLoaded", applyHighlight);'.PHP_EOL;
		$js .= '} else {'.PHP_EOL;
		$js .= 'setTimeout(applyHighlight, 150);'.PHP_EOL;
		$js .= '}'.PHP_EOL;
		$js .= '})();'.PHP_EOL;
		$js .= '</script>'.PHP_EOL;
		return $js;
	}

	/**
	 * 팝업 창 콘텐츠 생성
	 * 언어 선택 및 코드 입력 UI 반환
	 * @return string 팝업 HTML
	 */
	function getPopupContent()
	{
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';
		$script_path = getScriptPath().'modules/editor/components/synstax_highlight/highlightjs/';

		$theme_value = $this->getComponentInfoValue('theme');
		$theme = $theme_value ? $this->getThemeString($theme_value) : $this->theme;
		if(!$theme || !is_string($theme)) {
			$theme = 'atom-one-dark-reasonable';
		}
		$this->theme = $theme;

		$theme_file = getScriptPath().'modules/editor/components/synstax_highlight/highlightjs/styles/'.$theme.'.min.css';
		Context::addCSSFile($theme_file);
		Context::addJsFile($script_path.'highlight.min.js');

		$available_languages = $this->getAvailableLanguages();
		foreach($available_languages as $lang) {
			Context::addJsFile($script_path.'languages/'.$lang[1].'.min.js');
		}

		$display_languages = $this->getDisplayLanguages();

		Context::set('script_path', $script_path);
		Context::set('languages', $display_languages);
		Context::set('current_theme', $theme);
		Context::set('current_theme_name', $this->getThemeName($theme));

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * 코드 블록을 HTML로 변환
	 * 하이라이트 적용된 코드 블록 HTML 생성
	 * @param object $xml_obj XML 객체
	 * @return string 변환된 HTML
	 */
	function transHTML($xml_obj)
	{
		$script_path = getScriptPath().'modules/editor/components/synstax_highlight/highlightjs/';
		$code_type = $xml_obj->attrs->code_type ? trim($xml_obj->attrs->code_type) : 'javascript';
		$lang_id = $this->validateLanguageId($code_type);
		$theme = $this->theme;

		$body = $this->normalizeText($xml_obj->body);
		$code_for_copy = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
		$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

		if(!$GLOBALS['_called_editor_component_synstax_highlight_'])
		{
			$GLOBALS['_called_editor_component_synstax_highlight_'] = true;

			$component_css = getScriptPath().'modules/editor/components/synstax_highlight/tpl/popup.css';
			Context::addCSSFile($component_css);
			Context::addCSSFile(getScriptPath().'modules/editor/components/synstax_highlight/highlightjs/styles/'.$theme.'.min.css');
			Context::addJsFile($script_path.'highlight.min.js');

			foreach($this->getAvailableLanguages() as $lang) {
				Context::addJsFile($script_path.'languages/'.$lang[1].'.min.js');
			}

			Context::addHtmlFooter($this->generateGlobalHighlightScript() . $this->generateCopyButtonScript());
		}

		$lang_name = $this->getLanguageName($code_type, $lang_id);
		$lang_id_escaped = htmlspecialchars($lang_id, ENT_QUOTES, 'UTF-8');

		$output = '<pre>'.PHP_EOL;
		$output .= '<div class="code-lang-label">'.PHP_EOL;
		$output .= '<span class="code-lang-text">'.htmlspecialchars($lang_name, ENT_QUOTES, 'UTF-8').'</span>'.PHP_EOL;
		$output .= '<button class="copy-button" data-clipboard-text="'.$code_for_copy.'" onclick="copyCodeToClipboard(this)">Copy</button>'.PHP_EOL;
		$output .= '</div>'.PHP_EOL;
		$output .= '<code class="language-'.$lang_id_escaped.'" data-lang="'.$lang_id_escaped.'" data-inline-script="true">'.$body.'</code>'.PHP_EOL;
		$output .= '</pre>'.PHP_EOL;
		$output .= $this->generateInlineHighlightScript($lang_id);

		return $output;
	}

	/**
	 * 언어 ID 검증 및 정규화
	 * @param string $code_type 언어 타입
	 * @return string 검증된 언어 ID
	 */
	private function validateLanguageId(string $code_type): string
	{
		$code_type = trim($code_type);
		if(empty($code_type)) {
			return 'javascript';
		}

		foreach($this->languages as $lang) {
			if($code_type === $lang[1]) {
				return $lang[1];
			}
		}

		$code_type_lower = strtolower($code_type);
		foreach($this->languages as $lang) {
			if($code_type_lower === strtolower($lang[1])) {
				return $lang[1];
			}
		}

		return 'javascript';
	}
}
