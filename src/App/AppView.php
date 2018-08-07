<?php
/**
 * Application BaseView class file
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.5.5
 * @filesource
 */
namespace NETopes\Core\App;
use PAF\AppException;
use \NApp;

/**
 * Generic view type (no theme container)
 */
define('GENERIC_CONTENT_VIEW',0);
/**
 * Main content view type (main content theme container)
 */
define('MAIN_CONTENT_VIEW',1);
/**
 * Modal content view type (modal content theme container)
 */
define('MODAL_CONTENT_VIEW',2);
/**
 * Application BaseView class
 *
 * @package    NETopes\Core\App
 * @abstract
 */
class AppView {
	/**
	 * View type
	 *
	 * @var int
	 * @access protected
	 */
	protected $type = GENERIC_CONTENT_VIEW;
	/**
	 * View theme
	 *
	 * @var string|null
	 * @access protected
	 */
	protected $theme = NULL;
	/**
	 * Debug mode on/off
	 *
	 * @var bool
	 * @access protected
	 */
	protected $debug = FALSE;
	/**
	 * View title
	 *
	 * @var string
	 * @access protected
	 */
	protected $title = '';
	/**
	 * Modal view width
	 *
	 * @var string|integer|null
	 * @access protected
	 */
	protected $modalWidth = 300;
	/**
	 * Auto-generate js script for modal view
	 *
	 * @var bool
	 * @access protected
	 */
	protected $modalAutoJs = TRUE;
	/**
	 * View pass trough params
	 *
	 * @var array
	 * @access protected
	 */
	protected $params = [];
	/**
	 * View actions
	 *
	 * @var array
	 * @access protected
	 */
	protected $actions = [];
	/**
	 * View content
	 *
	 * @var array
	 * @access protected
	 */
	protected $content = [];
	/**
	 * View JS scripts to be executed on render
	 *
	 * @var array
	 * @access protected
	 */
	protected $jsScripts = [];
	/**
	 * BaseView constructor.
	 *
	 * @param array $params Pass trough variables array
	 * Obtained by calling: get_defined_vars()
	 * @param int   $type View type
	 * @return void
	 * @access public
	 */
	public function __construct(array $params,?int $type = NULL,?string $theme = NULL) {
		$this->params = $params;
		$this->theme = $theme;
		if(isset($type)) { $this->type = $type; }
	}//END public function __construct
	/**
	 * @return bool
	 */
	public function IsModalAutoJs(): bool {
		return $this->modalAutoJs;
	}//END public function IsModalAutoJs
	/**
	 * @param bool $modalAutoJs
	 * @return void
	 * @access public
	 */
	public function SetModalAutoJs(bool $modalAutoJs): void {
		$this->modalAutoJs = $modalAutoJs;
	}//END public function SetModalAutoJs
	/**
	 * @return array
	 */
	public function GetJsScripts(): array {
		return $this->jsScripts;
	}//END public function GetJsScripts
	/**
	 * @return string
	 */
	public function GetJsScript(): string {
		if(!count($this->jsScripts)) { return ''; }
		$result = '';
		foreach($this->jsScripts as $js) {
			$js = trim($js);
			$result .= (strlen($result) ? "\n" : '').(substr($js,-1)=='}' ? $js : rtrim($js,';').';');
		}//END foreach
		return $result;
	}//END public function GetJsScript
	/**
	 * @param string $script
	 * @return void
	 */
	public function AddJsScript(string $script): void {
		if(strlen(trim($script))) { $this->jsScripts = $script; }
	}//END public function GetJsScripts
	/**
	 * @return void
	 */
	public function ClearJsScripts(): void {
		$this->jsScripts = [];
	}//END public function GetJsScripts
	/**
	 * @param bool $debug
	 * @return void
	 * @access public
	 */
	public function SetDebug(bool $debug): void {
		$this->debug = $debug;
	}//END public function SetDebug
	/**
	 * @param string|null $theme
	 * @return void
	 * @access public
	 */
	public function SetTheme(?string $theme): void {
		$this->theme = $theme;
	}//END public function SetTheme
	/**
	 * @param string $title
	 * @return void
	 * @access public
	 */
	public function SetTitle(string $title): void {
		$this->title = $title;
	}//END public function SetTitle
	/**
	 * @param mixed $width
	 * @return void
	 * @access public
	 */
	public function SetModalWidth($width): void {
		$this->modalWidth = $width;
	}//END public function SetModalWidth
	/**
	 * @param string $action
	 * @return void
	 * @access public
	 */
	public function AddAction(string $action): void {
		$this->actions[] = $action;
	}//END public function AddAction
	/**
	 * @param string $content
	 * @return void
	 * @access public
	 */
	public function AddHtmlContent(string $content): void {
		$this->content[] = ['type'=>'string','value'=>$content];
	}//END public function AddHtmlContent
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddContent(string $file): void {
		$this->content[] = ['type'=>'file','value'=>$file];
	}//END public function AddContent
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddTableView(string $file): void {
		$this->content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\TableView'];
	}//END public function AddTableView
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddBasicForm(string $file): void {
		$this->content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\BasicForm'];
	}//END public function AddBasicForm
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddTabControl(string $file): void {
		$this->content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\TabControl'];
	}//END public function AddTabControl
	/**
	 * Render view content
	 *
	 * @param bool $return If TRUE view content is returned as string, else is outputted
	 * @return string|null
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function Render(bool $return = FALSE): ?string {
		$content = '';
		foreach($this->content as $k=>$c) {
			$type = get_array_param($c,'type','','is_string');
			$value = get_array_param($c,'value','','is_string');
			switch($type) {
				case 'control':
					$class = get_array_param($c,'class','','is_string');
					if(!strlen($class) || !count($value)) {
						if($this->debug) { NApp::_Dlog('Invalid content class/value [control:index:'.$k.']!'); }
						continue;
					}//if(!strlen($class) || !count($value))
					$content .= (strlen($content) ? "\n" : '').$this->GetControlContent($value,$class);
					break;
				case 'file':
					if(!count($value)) {
						if($this->debug) { NApp::_Dlog('Invalid content value [file:index:'.$k.']!'); }
						continue;
					}//if(!count($value))
					$content .= (strlen($content) ? "\n" : '').$this->GetFileContent($value);
					break;
				case 'string':
					$content .= ($content && $value ? "\n" : '').$value;
					break;
				default:
					if($this->debug) { NApp::_Dlog('Invalid content type [index:'.$k.']!'); }
					break;
			}//END switch
		}//END foreach
		$result = $this->ProcessViewTheme($content);
		if($return) { return $result; }
		echo $result;
		if(count($this->jsScripts)) { NApp::_ExecJs($this->GetJsScript()); }
		return NULL;
	}//END public function Render
	/**
	 * @param string $_v_file File full name (including absolute path)
	 * @param string $_c_class Control class fully qualified name
	 * @return string
	 * @throws \PAF\AppException
	 */
	protected function GetControlContent(string $_v_file,string $_c_class): string {
		if(count($this->params)) { extract($this->params); }
		require($_v_file);
		if(!isset($ctrl_params)) { throw new AppException('Undefined control parameters variable [$ctrl_params:'.$_v_file.']!'); }
		$_control = new $_c_class($ctrl_params);
		return $_control->Show();
	}//END protected function GetControlContent
	/**
	 * @param string $file
	 * @return string
	 */
	protected function GetFileContent(string $_v_file): string {
		if(count($this->params)) { extract($this->params); }
		ob_start();
		require($_v_file);
		$result = ob_get_clean();
		return $result;
	}//END protected function GetFileContent
	/**
	 * @param string $content
	 * @return string
	 */
	protected function ProcessViewTheme(string $content): string {
		if(strlen($this->theme)) {
			$themeObj = NApp::_GetTheme($this->theme);
		} else {
			$themeObj = NApp::$theme;
			if(is_null($themeObj)) { $themeObj = NApp::_GetTheme(); }
		}//if(strlen($this->theme))
		if(!is_object($themeObj)) { return implode("\n",$this->actions)."\n".$content; }
		switch($this->type) {
			case MAIN_CONTENT_VIEW:
				ob_start();
				$themeObj->GetMainContainer();
				$container = ob_get_clean();
				break;
			case MODAL_CONTENT_VIEW:
				ob_start();
				$themeObj->GetModalContainer();
				$container = ob_get_clean();
				if($this->modalAutoJs) {
					$mWidth = is_numeric($this->modalWidth) && $this->modalWidth>0 ? $this->modalWidth : (is_string($this->modalWidth) && strlen($this->modalWidth) ? "'{$this->modalWidth}'" : 300);
					$this->AddJsScript("ShowModalForm({$mWidth},'{$this->title}');");
				}//if($this->modalAutoJs)
				break;
			case GENERIC_CONTENT_VIEW:
				ob_start();
				$themeObj->GetGenericContainer();
				$container = ob_get_clean();
				break;
			default:
				if($this->debug) { NApp::_Dlog('Invalid view type ['.$this->type.']!'); }
				return implode("\n",$this->actions)."\n".$content;
		}//END switch
		if($this->debug && strpos($container,'{{CONTENT}}')===FALSE) { NApp::_Dlog('{{CONTENT}} placeholder is missing for view container ['.$this->type.']!'); }
		if($this->debug && count($this->actions) && strpos($container,'{{ACTIONS}}')===FALSE) { NApp::_Dlog('{{ACTIONS}} placeholder is missing for view container ['.$this->type.']!'); }
		$container = str_replace('{{TITLE}}',$this->title,$container);
		$container = str_replace('{{CONTENT}}',$content,$container);
		$container = str_replace('{{ACTIONS}}',implode("\n",$this->actions),$container);
		return $container;
	}//END protected function ProcessViewTheme
}//END class AppView
?>