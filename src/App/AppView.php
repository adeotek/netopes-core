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
 * Application BaseView class
 *
 * @package    NETopes\Core\App
 * @abstract
 */
class AppView {
    /**
	 * @var string|null View container type
	 * @access protected
	 */
	protected $_containerType = NULL;
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
	 * @var string|null View container class
	 * @access protected
	 */
	protected $_containerClass = NULL;
	/**
	 * @var string|null View title
	 * @access protected
	 */
	protected $_title = NULL;
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
	 * @var bool Is modal view
	 * @access protected
	 */
	protected $_isModal = FALSE;
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
	 * @param null|object $parent
     * @param null|string $containerType
	 * @access public
	 */
	public function __construct(array $params,$parent = NULL,?string $containerType = NULL) {
		$this->_params = $params;
		if(is_object($parent)) {
			$this->parent = $parent;
			foreach(get_object_vars($parent) as $pn=>$pv) { $this->_passTrough[$pn] = $pv; }
		}//if(is_object($parent))
		if(isset($containerType)) { $this->_containerType = $containerType; }
	}//END public function __construct
	/**
     * @param string|null $type
     * @return void
     * @access public
     */
	public function SetContainerType(?string $type): void {
		$this->_containerType = $type;
	}//END public function SetContainerType
	/**
     * @param string|null $class
     * @return void
     */
	public function SetContainerClass(?string $class): void {
		$this->_containerClass = $class;
	}//END public function SetContainerClass
	/**
	 * @return bool
	 */
	public function IsModalView(): bool {
		return $this->_isModal;
	}//END public function IsModalView
	/**
	 * @param bool $isModal
	 * @return void
	 * @access public
	 */
	public function SetIsModalView(bool $isModal): void {
		$this->_isModal = $isModal;
	}//END public function SetIsModalView
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
	 * @return string|null
	 */
	public function GetJsScript(): ?string {
		if(!count($this->_jsScripts)) { return NULL; }
		$result = '';
		foreach($this->_jsScripts as $js) {
			$js = trim($js);
			$result .= (strlen($result) ? "\n" : '').(substr($js,-1)=='}' ? $js : rtrim($js,';').';');
		}//END foreach
		return $result;
	}//END public function GetJsScript
    /**
     * @param string $script
     * @param bool   $first
     * @return void
     */
	public function AddJsScript(string $script,bool $first = FALSE): void {
	    if(!strlen(trim($script))) { return; }
	    if($first) {
	        array_unshift($this->_jsScripts,$script);
	    } else {
	        $this->_jsScripts[] = $script;
	    }//if($first)
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
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
	 * @return void
	 * @access public
	 */
	public function AddHtmlContent(string $content,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'string','value'=>$content,'container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
	}//END public function AddHtmlContent
	/**
	 * @param string $file
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
	 * @return void
	 * @access public
	 */
	public function AddContent(string $file,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'file','value'=>$file,'container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
	}//END public function AddContent
	/**
     * @param object      $object
     * @param string      $method
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
     * @return void
     * @access public
     */
	public function AddObjectContent(object $object, string $method,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'object','object'=>$object,'method'=>$method,'container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
	}//END public function AddContent
	/**
	 * @param string $module
	 * @param string $method
	 * @param null   $params
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
	 * @return void
	 * @access public
	 */
	public function AddModuleContent(string $module,string $method,$params = NULL,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'module','module'=>$module,'method'=>$method,'params'=>$params,'container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
	}//END public function AddModuleContent
	/**
	 * @param string $file
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
	 * @return void
	 * @access public
	 */
	public function AddTableView(string $file,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\TableView','container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
	}//END public function AddTableView
	/**
	 * @param string $file
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
	 * @return void
	 * @access public
	 */
	public function AddBasicForm(string $file,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\BasicForm','container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
	}//END public function AddBasicForm
	/**
	 * @param string $file
     * @param null|string $containerType
     * @param null|string $containerId
     * @param null|string $tag
	 * @return void
	 * @access public
	 */
	public function AddTabControl(string $file,?string $containerType = NULL,?string $containerId = NULL,?string $tag = NULL): void {
		$this->_content[] = ['type'=>'control','value'=>$file,'class'=>'\NETopes\Core\Controls\TabControl','container_type'=>$containerType,'container_id'=>$containerId,'tag'=>$tag];
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
		$mainContainer = $this->GetContainer($this->_containerType,$this->HasActions(),$this->HasTitle());
		foreach($this->_content as $k=>$c) {
			$type = get_array_value($c,'type','','is_string');
			$value = get_array_value($c,'value','','is_string');
			$cContainerType = get_array_value($c,'container_type',NULL,'?is_string');
			$cContainerId = get_array_value($c,'container_id',NULL,'?is_string');
			$cTag = get_array_value($c,'tag',NULL,'?is_string');
			$cContent = '';
			switch($type) {
				case 'control':
					$class = get_array_value($c,'class','','is_string');
					if(!strlen($class) || !strlen($value)) {
						if($this->_debug) { NApp::_Dlog('Invalid content class/value [control:index:'.$k.']!'); }
						continue;
					}//if(!strlen($class) || !strlen($value))
					$cContent = $this->GetControlContent($value,$class);
					break;
				case 'file':
					if(!strlen($value)) {
						if($this->_debug) { NApp::_Dlog('Invalid content value [file:index:'.$k.']!'); }
						continue;
					}//if(!strlen($value))
					$cContent = $this->GetFileContent($value);
					break;
				case 'module':
					$module = get_array_value($c,'module','','is_string');
					$method = get_array_value($c,'method','','is_string');
					if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
						if($this->_debug) { NApp::_Dlog('Invalid module content parameters [index:'.$k.':'.print_r($c,1).']!'); }
						continue;
					}//if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method))
					$params = get_array_value($c,'params',NULL,'isset');
					$cContent = $this->GetModuleContent($module,$method,$params);
					break;
				case 'object':
				    $object = get_array_value($c,'object',NULL,'?is_object');
				    $method = get_array_value($c,'method','','is_string');
					if(!$object || !strlen($method) || !method_exists($object,$method)) {
						if($this->_debug) { NApp::_Dlog('Invalid content class/value [object:method:'.$method.']!'); }
						continue;
					}//if(!$object || !strlen($method) || !method_exists($object,$method))
				    $cContent = $object->$method();
					break;
				case 'string':
				    $cContent = $value;
					break;
				default:
					if($this->_debug) { NApp::_Dlog('Invalid content type [index:'.$k.']!'); }
					break;
			}//END switch
			$processed = FALSE;
            if(strlen($cContainerType)) { $processed = $this->ProcessSubContainer($cContent,$cContainerType,$cContainerId,$cTag); }
            if(!$processed && strlen($cTag)) {
                $placeholder = '{{'.trim($cTag,'{}').'}}';
                if(strpos($mainContainer,$placeholder)===FALSE) {
                    if($this->_debug) { NApp::_Dlog($placeholder.' placeholder is missing for view container ['.$cContainerType.']!'); }
                } else {
                    $mainContainer = str_replace($placeholder,$cContent,$mainContainer);
                    $processed = TRUE;
                }//if(strpos($mainContainer,$placeholder)===FALSE)
            }//if(!$processed && strlen($cTag))
            if(!$processed) { $content .= (strlen($content) ? "\n" : '').$cContent; }
		}//END foreach
		if($this->_isModal && $this->_modalAutoJs) {
            $mJsScript = strlen($this->_targetId) ? "ShowDynamicModalForm('{$this->_targetId}'," : "ShowModalForm(";
            $mJsScript .= is_numeric($this->_modalWidth) && $this->_modalWidth>0 ? $this->_modalWidth : (is_string($this->_modalWidth) && strlen($this->_modalWidth) ? "'{$this->_modalWidth}'" : 300);
            $mJsScript .= strlen($this->_titleTagId) ? ",($('#{$this->_titleTagId}').html()".(strlen($this->_title) ? "+': {$this->_title}'" : '')."));" : ",'{$this->_title}');";
            $this->AddJsScript($mJsScript,TRUE);
        }//if($this->_isModal && $this->_modalAutoJs)
		if(strlen($mainContainer)) {
		    if(strpos($mainContainer,'{{CONTENT}}')===FALSE) {
		        if($this->_debug) { NApp::_Dlog('{{CONTENT}} placeholder is missing for view container ['.$this->_containerType.']!'); }
		    } else {
		        if($this->_debug && strlen($this->_targetId) && strpos($mainContainer,'{{TARGETID}}')===FALSE) { NApp::_Dlog('{{TARGETID}} placeholder is missing for view container ['.$this->_containerType.']!'); }
                if($this->_debug && count($this->_actions) && strpos($mainContainer,'{{ACTIONS}}')===FALSE) { NApp::_Dlog('{{ACTIONS}} placeholder is missing for view container ['.$this->_containerType.']!'); }
                if($this->_debug && strlen($this->_title) && strpos($mainContainer,'{{TITLE}}')===FALSE) { NApp::_Dlog('{{TITLE}} placeholder is missing for view container ['.$this->_containerType.']!'); }
                $mainContainer = str_replace('{{TITLE}}',$this->_title,$mainContainer);
                $mainContainer = str_replace('{{TARGETID}}',$this->_targetId,$mainContainer);
                $mainContainer = str_replace('{{ACTIONS}}',implode("\n",$this->_actions),$mainContainer);
                $content = str_replace('{{CONTENT}}',$content,$mainContainer);
                $content = $this->ReplaceEmptyPlaceholders($content);
		    }//if(strpos($mainContainer,'{{CONTENT}}')===FALSE)
        } else {
		    $content = implode("\n",$this->_actions)."\n".$content;
		}//if(strlen($mainContainer))
		if($return) { return $content; }
		echo $content;
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
		$result = $_control->Show();
		$jsScript = $_control->GetJsScript();
		if(strlen(trim($jsScript)) && method_exists($_control,'GetJsScript')) { $this->AddJsScript($jsScript); }
		return $result;
	}//END protected function GetControlContent
	/**
	 * @param string $_v_file
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
     * @param string $containerType
     * @param bool   $hasActions
     * @param bool   $hasTitle
     * @return string|null
	 */
	protected function GetContainer(?string $containerType,bool $hasActions = FALSE,bool $hasTitle = FALSE): ?string {
	    if($containerType===NULL) { return NULL; }
	    $containerMethod = 'Get'.(strlen($containerType) ? ucfirst($containerType) : 'Default').'Container';
		if(strlen($this->_theme)) {
			$themeObj = NApp::_GetTheme($this->_theme);
		} else {
			$themeObj = NApp::$theme;
			if(is_null($themeObj)) { $themeObj = NApp::_GetTheme(); }
		}//if(strlen($this->theme))
        if(!is_object($themeObj)) {
		    if($this->_debug) { NApp::_Dlog('Invalid view object ['.$this->_theme.']!'); }
		    return NULL;
		}//if(!is_object($themeObj))
		if(!method_exists($themeObj,$containerMethod)) {
		    if($this->_debug) { NApp::_Dlog('View container method ['.$containerMethod.'] not found!'); }
		    return NULL;
		}//if(!method_exists($themeObj,$containerMethod))
				ob_start();
		$themeObj->$containerMethod($hasActions,$hasTitle);
				$container = ob_get_clean();
		return $container;
	}//END protected function GetContainer
    /**
     * @param string      $content
     * @param string      $containerType
     * @param null|string $targetId
     * @param null|string $tag
     * @return bool
     */
	protected function ProcessSubContainer(string &$content,string $containerType,?string $targetId = NULL,?string $tag = NULL): bool {
	    $container = $this->GetContainer($containerType);
	    if(!strlen($container)) { return FALSE; }
	    if(strlen($targetId)) {
	        if(strpos($container,'{{TARGETID}}')===FALSE) {
	            if($this->_debug) { NApp::_Dlog('{{TARGETID}} placeholder is missing for view container ['.$containerType.']!'); }
		} else {
            $container = str_replace('{{TARGETID}}',$targetId,$container);
	        }//if(strpos($container,'{{TARGETID}}')===FALSE)
	    }//if(strlen($targetId))
	    if(strlen($tag)) {
	        $placeholder = '{{'.trim($tag,'{}').'}}';
	        if(strpos($container,$placeholder)===FALSE) {
	            if($this->_debug) { NApp::_Dlog($placeholder.' placeholder is missing for view container ['.$containerType.']!'); }
	            return FALSE;
            }//if(strpos($container,$placeholder)===FALSE)
            $content = str_replace($placeholder,$content,$container);
            $content = $this->ReplaceEmptyPlaceholders($content);
            return TRUE;
	    }//if(strlen($tag))
        if(strpos($container,'{{CONTENT}}')===FALSE) {
            if($this->_debug) { NApp::_Dlog('{{CONTENT}} placeholder is missing for view container ['.$containerType.']!'); }
            return FALSE;
        }//if(strpos($container,'{{CONTENT}}')===FALSE)
        $content = str_replace('{{CONTENT}}',$content,$container);
        $content = $this->ReplaceEmptyPlaceholders($content);
		return FALSE;
	}//END protected function ProcessSubContainer
    /**
     * @param string $content
     * @return string
     */
	protected function ReplaceEmptyPlaceholders(string $content): string {
		return preg_replace('/{{[^}]*}}/i','',$content);
    }//END protected function ReplaceEmptyPlaceholders
}//END class AppView
?>