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
 * Content only (no theme container)
 */
define('CONTENT_ONLY_VIEW',0);
/**
 * Main content view type (main content theme container)
 */
define('MAIN_CONTENT_VIEW',1);
/**
 * Modal content view type (modal content theme container)
 */
define('MODAL_CONTENT_VIEW',2);
/**
 * Secondary content view type (sub-content in main content theme container)
 */
define('SECONDARY_CONTENT_VIEW',3);
/**
 * Generic view type (theme generic container)
 */
define('GENERIC_CONTENT_VIEW',10);
/**
 * Application BaseView class
 *
 * @package    NETopes\Core\App
 * @abstract
 */
class AppView {
	/**
	 * @var int View type
	 * @access protected
	 */
	protected $_type = CONTENT_ONLY_VIEW;
	/**
	 * @var string|null View theme
	 * @access protected
	 */
	protected $_theme = NULL;
	/**
	 * @var bool Debug mode on/off
	 * @access protected
	 */
	protected $_debug = FALSE;
	/**
	 * @var string View title
	 * @access protected
	 */
	protected $_title = '';
	/**
	 * @var string View dynamic title tag ID
	 * @access protected
	 */
	protected $_titleTagId = '';
	/**
	 * @var string|integer|null Modal view width
	 * @access protected
	 */
	protected $_modalWidth = 300;
	/**
	 * @var string Modal view dynamic target ID
	 * @access protected
	 */
	protected $_targetId = '';
	/**
	 * @var bool Auto-generate js script for modal view
	 * @access protected
	 */
	protected $_modalAutoJs = TRUE;
	/**
	 * @var array View pass trough params
	 * @access protected
	 */
	protected $_params = [];
	/**
	 * @var array View actions
	 * @access protected
	 */
	protected $_actions = [];
	/**
	 * @var array View content
	 * @access protected
	 */
	protected $_content = [];
	/**
	 * @var array View JS scripts to be executed on render
	 * @access protected
	 */
	protected $_jsScripts = [];
	/**
	 * @var array View pass trough properties
	 * @access protected
	 */
	protected $_passTrough = [];
	/**
	 * @var object Parent module object
	 * @access public
	 */
	public $parent = NULL;
	/**
	 * @var string|null Parent module short class name (called class without base prefix)
	 * @access public
	 */
	public $name = NULL;
	/**
	 * @var    string Parent module full qualified class name
	 * @access public
	 */
	public $class;
	/**
	 * Pass trough parameters magic getter
	 *
	 * @param  string $name The name of the property
	 * @return mixed Returns the value of the property
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function __get($name) {
		if(!array_key_exists($name,$this->_passTrough)) { throw new AppException('Undefined property ['.$name.']!',E_ERROR,1); }
		return $this->_passTrough[$name];
	}//END public function __get
	/**
	 * BaseView constructor.
	 *
	 * @param array $params Pass trough variables array
	 * Obtained by calling: get_defined_vars()
	 * @param int   $type View type
	 * @param null|object $parent
	 * @access public
	 */
	public function __construct(array $params,?int $type = NULL,$parent = NULL) {
		$this->_params = $params;
		if(is_object($parent)) {
			$this->parent = $parent;
			$this->name = $parent->name;
			$this->class = $parent->class;
			foreach(get_object_vars($parent) as $p) {
				$this->_passTrough[$p] = $parent->$p;
			}//END foreach
		}//if(is_object($parent))
		if(isset($type)) { $this->_type = $type; }
	}//END public function __construct
	/**
	 * @return bool
	 */
	public function IsModalAutoJs(): bool {
		return $this->_modalAutoJs;
	}//END public function IsModalAutoJs
	/**
	 * @param bool $modalAutoJs
	 * @return void
	 * @access public
	 */
	public function SetModalAutoJs(bool $modalAutoJs): void {
		$this->_modalAutoJs = $modalAutoJs;
	}//END public function SetModalAutoJs
	/**
	 * @return array
	 */
	public function GetJsScripts(): array {
		return $this->_jsScripts;
	}//END public function GetJsScripts
	/**
	 * @return string
	 */
	public function GetJsScript(): string {
		if(!count($this->_jsScripts)) { return ''; }
		$result = '';
		foreach($this->_jsScripts as $js) {
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
		if(strlen(trim($script))) { $this->_jsScripts[] = $script; }
	}//END public function GetJsScripts
	/**
	 * @return void
	 */
	public function ClearJsScripts(): void {
		$this->_jsScripts = [];
	}//END public function GetJsScripts
	/**
	 * @param bool $debug
	 * @return void
	 * @access public
	 */
	public function SetDebug(bool $debug): void {
		$this->_debug = $debug;
	}//END public function SetDebug
	/**
	 * @param string|null $theme
	 * @return void
	 * @access public
	 */
	public function SetTheme(?string $theme): void {
		$this->_theme = $theme;
	}//END public function SetTheme
	/**
	 * @param string $title
	 * @return void
	 * @access public
	 */
	public function SetTitle(string $title): void {
		$this->_title = $title;
	}//END public function SetTitle
	/**
	 * @param string $title
	 * @param string $tagId
	 * @return void
	 * @access public
	 */
	public function SetDynamicTitle(string $title,string $tagId): void {
		$this->_title = $title;
		$this->_titleTagId = $tagId;
	}//END public function SetDynamicTitle
	/**
	 * @param mixed $width
	 * @return void
	 * @access public
	 */
	public function SetModalWidth($width): void {
		$this->_modalWidth = $width;
	}//END public function SetModalWidth
	/**
	 * @param string $targetId
	 * @return void
	 * @access public
	 */
	public function SetTargetId(string $targetId): void {
		$this->_targetId = $targetId;
	}//END public function SetTargetId
	/**
	 * @param string $action
	 * @return void
	 * @access public
	 */
	public function AddAction(string $action): void {
		$this->_actions[] = $action;
	}//END public function AddAction
	/**
	 * @return bool
	 * @access public
	 */
	public function HasActions(): bool {
		return count($this->_actions)>0;
	}//END public function HasActions
	/**
	 * @return bool
	 * @access public
	 */
	public function HasTitle(): bool {
		return strlen($this->_title)>0;
	}//END public function HasTitle
	/**
	 * @param string $content
	 * @return void
	 * @access public
	 */
	public function AddHtmlContent(string $content): void {
		$this->_content[] = ['type'=>'string','value'=>$content];
	}//END public function AddHtmlContent
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddContent(string $file): void {
		$this->_content[] = ['type'=>'file','value'=>$file];
	}//END public function AddContent
	/**
	 * @param string $module
	 * @param string $method
	 * @param null   $params
	 * @return void
	 * @access public
	 */
	public function AddModuleContent(string $module,string $method,$params = NULL): void {
		$this->_content[] = ['type'=>'module','module'=>$module,'method'=>$method,'params'=>$params];
	}//END public function AddModuleContent
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddTableView(string $file): void {
		$this->_content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\TableView'];
	}//END public function AddTableView
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddBasicForm(string $file): void {
		$this->_content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\BasicForm'];
	}//END public function AddBasicForm
	/**
	 * @param string $file
	 * @return void
	 * @access public
	 */
	public function AddTabControl(string $file): void {
		$this->_content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\TabControl'];
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
		foreach($this->_content as $k=>$c) {
			$type = get_array_param($c,'type','','is_string');
			$value = get_array_param($c,'value','','is_string');
			switch($type) {
				case 'control':
					$class = get_array_param($c,'class','','is_string');
					if(!strlen($class) || !strlen($value)) {
						if($this->_debug) { NApp::_Dlog('Invalid content class/value [control:index:'.$k.']!'); }
						continue;
					}//if(!strlen($class) || !strlen($value))
					$content .= (strlen($content) ? "\n" : '').$this->GetControlContent($value,$class);
					break;
				case 'file':
					if(!strlen($value)) {
						if($this->_debug) { NApp::_Dlog('Invalid content value [file:index:'.$k.']!'); }
						continue;
					}//if(!strlen($value))
					$content .= (strlen($content) ? "\n" : '').$this->GetFileContent($value);
					break;
				case 'module':
					$module = get_array_param($c,'module','','is_string');
					$method = get_array_param($c,'method','','is_string');
					if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
						if($this->_debug) { NApp::_Dlog('Invalid module content parameters [index:'.$k.':'.print_r($c,1).']!'); }
						continue;
					}//if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method))
					$params = get_array_param($c,'params',NULL,'isset');
					$content .= (strlen($content) ? "\n" : '').$this->GetModuleContent($module,$method,$params);
					break;
				case 'string':
					$content .= ($content && $value ? "\n" : '').$value;
					break;
				default:
					if($this->_debug) { NApp::_Dlog('Invalid content type [index:'.$k.']!'); }
					break;
			}//END switch
		}//END foreach
		$result = $this->ProcessViewTheme($content);
		if($return) { return $result; }
		echo $result;
		if(count($this->_jsScripts)) { NApp::_ExecJs($this->GetJsScript()); }
		return NULL;
	}//END public function Render
	/**
	 * @param string $_v_file File full name (including absolute path)
	 * @param string $_c_class Control class fully qualified name
	 * @return string
	 * @throws \PAF\AppException
	 */
	protected function GetControlContent(string $_v_file,string $_c_class): string {
		if(count($this->_params)) { extract($this->_params); }
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
		if(count($this->_params)) { extract($this->_params); }
		ob_start();
		require($_v_file);
		$result = ob_get_clean();
		return $result;
	}//END protected function GetFileContent
	/**
	 * @param string $module
	 * @param string $method
	 * @param        $params
	 * @return string
	 * @throws \PAF\AppException
	 */
	protected function GetModuleContent(string $module,string $method,$params): string {
		ob_start();
		ModulesProvider::Exec($module,$method,$params);
		$result = ob_get_clean();
		return $result;
	}//END protected function GetModuleContent
	/**
	 * @param string $content
	 * @return string
	 */
	protected function ProcessViewTheme(string $content): string {
		if(strlen($this->_theme)) {
			$themeObj = NApp::_GetTheme($this->_theme);
		} else {
			$themeObj = NApp::$theme;
			if(is_null($themeObj)) { $themeObj = NApp::_GetTheme(); }
		}//if(strlen($this->theme))
		if(!is_object($themeObj)) { return implode("\n",$this->_actions)."\n".$content; }
		switch($this->_type) {
			case MAIN_CONTENT_VIEW:
				ob_start();
				$themeObj->GetMainContainer($this->HasActions(),$this->HasTitle());
				$container = ob_get_clean();
				break;
			case MODAL_CONTENT_VIEW:
				ob_start();
				$themeObj->GetModalContainer($this->HasActions(),$this->HasTitle());
				$container = ob_get_clean();
				if($this->_modalAutoJs) {
					$mJsScript = strlen($this->_targetId) ? "ShowDynamicModalForm('{$this->_targetId}'," : "ShowModalForm(";
					$mJsScript .= is_numeric($this->_modalWidth) && $this->_modalWidth>0 ? $this->_modalWidth : (is_string($this->_modalWidth) && strlen($this->_modalWidth) ? "'{$this->_modalWidth}'" : 300);
					$mJsScript .= strlen($this->_titleTagId) ? ",($('#{$this->_titleTagId}').html()".(strlen($this->_title) ? "+' - {$this->_title}'" : '')."));" : ",'{$this->_title}');";
					$this->AddJsScript($mJsScript);
				}//if($this->modalAutoJs)
				break;
			case SECONDARY_CONTENT_VIEW:
				ob_start();
				$themeObj->GetSecondaryContainer($this->HasActions(),$this->HasTitle());
				$container = ob_get_clean();
				break;
			case GENERIC_CONTENT_VIEW:
				ob_start();
				$themeObj->GetGenericContainer($this->HasActions(),$this->HasTitle());
				$container = ob_get_clean();
				break;
			case CONTENT_ONLY_VIEW:
				return $content;
			default:
				if($this->_debug) { NApp::_Dlog('Invalid view type ['.$this->_type.']!'); }
				return implode("\n",$this->_actions)."\n".$content;
		}//END switch
		if($this->_debug && strpos($container,'{{CONTENT}}')===FALSE) { NApp::_Dlog('{{CONTENT}} placeholder is missing for view container ['.$this->_type.']!'); }
		if($this->_debug && count($this->_actions) && strpos($container,'{{ACTIONS}}')===FALSE) { NApp::_Dlog('{{ACTIONS}} placeholder is missing for view container ['.$this->_type.']!'); }
		if($this->_debug && strlen($this->_title) && strpos($container,'{{TITLE}}')===FALSE) { NApp::_Dlog('{{TITLE}} placeholder is missing for view container ['.$this->_type.']!'); }
		if($this->_debug && strlen($this->_targetId) && strpos($container,'{{TARGETID}}')===FALSE) { NApp::_Dlog('{{TARGETID}} placeholder is missing for view container ['.$this->_type.']!'); }
		$container = str_replace('{{TITLE}}',$this->_title,$container);
		$container = str_replace('{{TARGETID}}',$this->_targetId,$container);
		$container = str_replace('{{CONTENT}}',$content,$container);
		$container = str_replace('{{ACTIONS}}',implode("\n",$this->_actions),$container);
		return $container;
	}//END protected function ProcessViewTheme
}//END class AppView
?>