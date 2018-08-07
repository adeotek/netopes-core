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
use \NApp;
use PAF\AppException;

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
abstract class BaseView {
	/**
	 * View type
	 *
	 * @var int
	 * @access protected
	 */
	protected $type = GENERIC_CONTENT_VIEW;
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
		if(isset($type)) { $this->type = $type; }
	}//END public function __construct
	/**
	 * @param bool $debug
	 * @return void
	 * @access public
	 */
	public function SetDebug(bool $debug): void {
		$this->debug = $debug;
	}//END public function SetDebug
	/**
	 * @param string $title
	 * @return void
	 * @access public
	 */
	public function SetTitle(string $title): void {
		$this->title = $title;
	}//END public function SetTitle
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
						if($this->debug) {
							NApp::_Dlog('Invalid content class/value [control:index:'.$k.']!');
						}//if($this->debug)
						continue;
					}//if(!strlen($class) || !count($value))
					$content .= (strlen($content) ? "\n" : '').$this->GetControlContent($value,$class);
					break;
				case 'file':
					if(!count($value)) {
						if($this->debug) {
							NApp::_Dlog('Invalid content value [file:index:'.$k.']!');
						}//if($this->debug)
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
		return '';
	}//END protected function ProcessViewTheme
}//END abstract class BaseView
?>