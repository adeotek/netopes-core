<?php
/**
 * Module class file
 *
 * @package    NETopes\Modules
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use GibberishAES;
use NApp;
use PAF\AppConfig;
use PAF\AppException;
/**
 * Module class
 *
 * All applications modules extend this base class
 *
 * @package  NETopes\Modules
 * @access   public
 */
class Module {
	/**
	 * @var    array Modules instances array
	 * @access private
	 * @static
	 */
	private static $ModuleInstances = [];
	/**
	 * @var    array Module request array
	 * @access protected
	 */
	protected $req_params = [];
	/**
	 * @var    array Module instance debug data
	 * @access protected
	 */
	protected $debug_data = NULL;
	/**
	 * @var    string Short class name (called class without base prefix)
	 * @access public
	 */
	public $name;
	/**
	 * @var    string Full qualified class name
	 * @access public
	 */
	public $class;
	/**
	 * @var    bool Is custom class (NameCustom extended class)
	 * @access public
	 */
	public $custom = FALSE;
	/**
	 * @var    bool Page hash (window.name)
	 * @access public
	 */
	public $phash = NULL;
	/**
	 * Module class initializer
	 *
	 * @return void
	 * @access protected
	 */
	protected function _Init() {
	}//END protected function _Init
	/**
	 * Method to be invoked before a standard method call
	 *
	 * @param  array $params An array of parameters
	 * @return bool  Returns TRUE by default
	 * If FALSE is return the call is canceled
	 * @access protected
	 */
	protected function _BeforeExec($params = NULL) {
		return TRUE;
	}//END protected function _BeforeExec
	/**
	 * Module class constructor
	 *
	 * @return void
	 * @access protected
	 */
	protected function __construct() {
		$this->_Init();
	}//END protected function __construct
	/**
	 * Module class method call
	 *
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function __call($name,$arguments) {
		if(strpos($name,'DRights')===FALSE) { throw new \PAF\AppException('Undefined module method ['.$name.']!',E_ERROR,1); }
		$method = get_array_param($arguments,0,call_back_trace(),'is_notempty_string');
		$module = get_array_param($arguments,1,get_called_class(),'is_notempty_string');
		return self::GetDRights($module,$method,str_replace('DRights','',$name));
	}//END public function __call
	/**
	 * Module class static method call
	 *
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 * @access public
	 * @throws \PAF\AppException
	 * @static
	 */
	public static function __callStatic($name,$arguments) {
		if(strpos($name,'DRights')===FALSE) { throw new \PAF\AppException('Undefined module method ['.$name.']!',E_ERROR,1); }
		$method = get_array_param($arguments,0,'','is_notempty_string');
		$module = get_array_param($arguments,1,get_called_class(),'is_notempty_string');
		return self::GetDRights($module,$method,str_replace('DRights','',$name));
	}//END public static function __callStatic
	/**
	 * Gets the user rights
	 *
	 * @param string $module
	 * @param string $method
	 * @param string $type
	 * @return mixed
	 * @access public
	 * @static
	 */
	public static function GetDRights($module,$method = '',$type = 'All') {
		if(NApp::_GetParam('sadmin')==1) { return FALSE; }
		// NApp::_Dlog($module,'$module');
		// NApp::_Dlog($method,'$method');
		// NApp::_Dlog($type,'$type');
		if(is_null($module) || is_null($method) || !strlen($type)) { return NULL; }
		$module = $module=='Module' ? '' : $module;
		$rights = NApp::_GetParam('user_rights_revoked');
		$rights = get_array_param($rights,$module,NULL,'is_array',$method);
		// NApp::_Dlog($rights,'$rights');
		if(is_null($rights)) { return NULL; }
		if(get_array_param($rights,'state',0,'is_numeric')!=1 || (get_array_param($rights,'sadmin',0,'is_numeric')==1 && NApp::_GetParam('sadmin')!=1)) { return TRUE; }
		if(strtolower($type)=='all') { return $rights; }
		// NApp::_Dlog(get_array_param($rights,strtolower('d'.$type),NULL,'bool'),'dright');
		return get_array_param($rights,strtolower('d'.$type),NULL,'bool');
	}//END public static function GetDRights
	/**
	 * description
	 *
	 * @param string $name
	 * @param string $class
	 * @param bool   $custom
	 * @return object
	 * @access public
	 */
	public static function GetInstance(string $name,string $class,bool $custom = FALSE) {
		if(!array_key_exists($class,self::$ModuleInstances) || !is_object(self::$ModuleInstances[$class])) {
			self::$ModuleInstances[$class] = new $class();
			self::$ModuleInstances[$class]->name = $name;
			self::$ModuleInstances[$class]->class = $class;
			self::$ModuleInstances[$class]->custom = $custom;
		}//if(!array_key_exists($name,self::$ModuleInstances) || !is_object(self::$ModuleInstances[$name]))
		return self::$ModuleInstances[$class];
	}//END public static function GetInstance
	/**
	 * description
	 *
	 * @param string $module
	 * @param string $method
	 * @param string $key
	 * @param mixed $def_value
	 * @return mixed
	 * @access public
	 * @static
	 */
	public static function GlobalGetCustomisationsData($module,$method,$key = NULL,$def_value = NULL) {
		if(!is_array(NApp::customizations()) || !count(NApp::customizations()) || !array_key_exists($module,NApp::customizations()) || !is_array(NApp::customizations()[$module]) || !count(NApp::customizations()[$module]) || !array_key_exists($method,NApp::customizations()[$module]) || !count(NApp::customizations()[$module][$method])) { return $def_value; }
		if(!$key) { return NApp::customizations()[$module][$method]; }
		return (array_key_exists($key,NApp::customizations()[$module][$method]) ? NApp::customizations()[$module][$method][$key] : $def_value);
	}//END public static function GlobalGetCustomisationsData
	/**
	 * description
	 *
	 * @param null $firstrow
	 * @param null $lastrow
	 * @param null $current_page
	 * @param null $rpp
	 * @return array
	 * @access public
	 * @static
	 */
	public static function GlobalGetPagintionParams(&$firstrow = NULL,&$lastrow = NULL,$current_page = NULL,$rpp = NULL) {
		$cpage = is_numeric($current_page) ? $current_page : 1;
		if($cpage==-1){
			$firstrow = -1;
			$lastrow = -1;
			return array('firstrow'=>$firstrow,'lastrow'=>$lastrow);
		}//if($cpage==-1)
		if(is_numeric($rpp) && $rpp>0) {
			$lrpp = $rpp;
		} else {
			$lrpp = Validator::ValidateParam(NApp::_GetParam('rows_per_page'),20,'is_not0_numeric');
		}//if(is_numeric($rpp) && $rpp>0)
		if(Validator::IsValidParam($firstrow,NULL,'is_not0_numeric')){
			$firstrow = $firstrow;
			$lastrow = $firstrow + $lrpp - 1;
		} else {
			$firstrow = ($cpage - 1) * $lrpp + 1;
			$lastrow = $firstrow + $lrpp - 1;
		}//if(Validator::IsValidParam($firstrow,NULL,'is_not0_numeric'))
		return array('firstrow'=>$firstrow,'lastrow'=>$lastrow);
	}//END public static function GlobalGetPagintionParams
	/**
	 * description
	 *
	 * @param string $method
	 * @param mixed  $params
	 * @param bool   $reset_session_params
	 * @param mixed  $before_call
	 * @return mixed return description
	 * @throws \PAF\AppException
	 * @access public
	 */
	public function Exec($method,$params = NULL,$reset_session_params = FALSE,$before_call = NULL) {
		$o_before_call = is_object($before_call) ? $before_call : new Params($before_call);
		if($o_before_call->count() && !$this->_BeforeExec($before_call)) { return FALSE; }
		$o_params = is_object($params) ? $params : new Params($params);
		if($reset_session_params) { $this->SetSessionParamValue(NULL,$method); }
		return $this->$method($o_params);
	}//END public function Exec
	/**
	 * description
	 *
	 * @param null  $def_value
	 * @param  type $method = '',$module = '' param description
	 * @param null  $page_hash
	 * @param null  $key
	 * @return void
	 * @access public
	 */
	public function GetSessionParamValue($def_value = NULL,$method = NULL,$page_hash = NULL,$key = NULL) {
		if($key) {
			$result = NApp::_GetParam($key);
			return ($result ? $result : $def_value);
		}//if($key)
		$lmethod = $method ? $method : call_back_trace();
		$pagehash = $page_hash ? $page_hash : $this->phash;
		$result = NApp::_GetParam($this->name.$lmethod.$pagehash);
		return ($result ? $result : $def_value);
	}//END public function GetSessionParamValue
	/**
	 * description
	 *
	 * @param       $value
	 * @param  type $method = '',$module = '' param description
	 * @param null  $page_hash
	 * @param null  $key
	 * @return void
	 * @access public
	 */
	public function SetSessionParamValue($value,$method = NULL,$page_hash = NULL,$key = NULL) {
		if($key) {
			NApp::_SetParam($key,$value);
			return;
		}//if($key)
		$lmethod = $method ? $method : call_back_trace();
		$pagehash = $page_hash ? $page_hash : $this->phash;
		NApp::_SetParam($this->name.$lmethod.$pagehash,$value);
	}//END public function SetSessionParamValue
	/**
	 * description
	 *
	 * @param object|null $params
	 * @return void
	 * @throws \PAF\AppException
	 * @access public
	 */
	public function SetFilter($params = NULL) {
		$method = $params->safeGet('method','Listing','is_notempty_string');
		$pagehash = $params->safeGet('phash','','is_string');
		$target = $params->safeGet('target','','is_string');
		$lxparam = $this->GetSessionParamValue([],$method,$pagehash);
		$params->remove('method');
		$params->remove('target');
		$params->remove('phash');
		if(is_array($lxparam)) {
			foreach($params as $k=>$v) {
				switch($k) {
					case 'qsearch':
						$lxparam[$k] = $v==\Translate::Get('qsearch_label') ? '' : $v;
						break;
				  	default:
						$lxparam[$k] = $v;
						break;
				}//END switch
			}//END foreach
			$lxparam['currentpage'] = 1;
			$this->SetSessionParamValue($lxparam,$method,$pagehash);
		} else {
			$lparams['currentpage'] = 1;
			$this->SetSessionParamValue($lparams,$method,$pagehash);
		}//if(is_array($lxparamp))
		$this->Exec($method,array('target'=>$target,'phash'=>$pagehash));
	}//END public function SetFilter
	/**
	 * description
	 *
	 * @param null $firstrow
	 * @param null $lastrow
	 * @param null $current_page
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return array
	 * @access public
	 */
	public function GetPagintionParams(&$firstrow = NULL,&$lastrow = NULL,$current_page = NULL,$params = NULL) {
		$cpage = is_numeric($current_page) ? $current_page : $params->safeGetValue('currentpage',1,'numeric','is_numeric');
		$firstrow = $params->safeGetValue('firstrow',0,'numeric','is_numeric');
		return self::GlobalGetPagintionParams($firstrow,$lastrow,$cpage);
	}//END public function GetPagintionParams
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function CloseForm($params = NULL) {
		if(!is_object($params)) { $params = new Params($params); }
		$targetid = $params->safeGet('targetid','','is_string');
		if($params->safeGet('modal',TRUE,'bool')) {
			$callback = $params->safeGet('callback','','is_string');
			if($callback) { GibberishAES::enc($callback ,'cmf'); }
			$dynamic = intval($params->safeGet('dynamic',TRUE,'bool'));
			NApp::arequest()->ExecuteJs("CloseModalForm('{$callback}','{$targetid}','{$dynamic}');");
		} elseif(strlen($targetid)) {
			NApp::arequest()->ExecuteJs("$(document).off('keypress');");
			NApp::arequest()->hide($targetid);
		}//if($params->safeGet('modal',TRUE,'bool'))
	}//END public function CloseForm
	/**
	 * @param string $module Module full qualified name
	 * @return array|null Returns and array containing parent class name, full class name, path
	 * or NULL if invalid module is provided
	 */
	public static function GetParents($module) {
		if(!is_string($module) || !strlen($module) || !class_exists($module)) { return NULL; }
		$result = [];
		$m_parent_class = get_parent_class($module);
		while($m_parent_class) {
			$m_parent_arr = explode('\\',trim($m_parent_class,'\\'));
			$m_parent = array_pop($m_parent_arr);
			if($m_parent=='Module') { break; }
			array_shift($m_parent_arr);
			array_shift($m_parent_arr);
			$m_path = implode(DIRECTORY_SEPARATOR,$m_parent_arr);
			$result[] = ['name'=>$m_parent,'class'=>$m_parent_class,'path'=>$m_path];
			$m_parent_class = get_parent_class($m_parent_class);
		}//END while
		return $result;
	}//END public static function GetParents
	/**
	 * Gets the view full file name (including absolute path)
	 *
	 * @param  string $name View name (without extension)
	 * @param  string $sub_dir View sub-directory or empty/NULL for none
	 * @param  string $theme_dir View theme sub-directory
	 * (if empty/NULL) application configuration will be used
	 * @return string Returns view full name (including absolute path and extension)
	 * @throws \PAF\AppException
	 * @access public
	 */
	private function ViewFileProvider($name,$sub_dir = NULL,$theme_dir = NULL) {
		$fname = (is_string($sub_dir) && strlen($sub_dir) ? '/'.trim($sub_dir,'/') : '').'/'.$name.AppConfig::app_views_extension();
		// Get theme directory and theme views base directory
		$app_theme = is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
		$views_def_dir = AppConfig::app_default_views_dir();
		$theme_modules_views_path = AppConfig::app_theme_modules_views_path();
		$defdir = (is_string($views_def_dir) ? (strlen(trim($views_def_dir,'/')) ? '/'.trim($views_def_dir,'/') : '') : '/_default');
		$themedir = (is_string($theme_dir) && strlen($theme_dir)) ? $theme_dir : (is_string($app_theme) && strlen($app_theme) ? $app_theme : NULL);
		$basedir = NULL;
		if(isset($themedir)) {
			if(is_string($theme_modules_views_path) && strlen($theme_modules_views_path)) {
				if(!file_exists(NApp::app_path().'/'.trim($theme_modules_views_path,'/\\'))) { throw new AppException('Invalid views theme path!'); }
				$basedir = NApp::app_path().'/'.trim($theme_modules_views_path,'/\\').DIRECTORY_SEPARATOR;
			}//if(is_string($theme_modules_views_path) && strlen($theme_modules_views_path))
		}//if(isset($themedir))
		// NApp::_Dlog($fname,'$fname');
		// NApp::_Dlog($theme_dir,'$theme_dir');
		// NApp::_Dlog($basedir,'$basedir');
		$parents = self::GetParents($this->class);
		$m_path_arr = explode('\\',trim($this->class,'\\'));
		array_shift($m_path_arr);
		array_shift($m_path_arr);
		array_pop($m_path_arr);
		$m_path = implode(DIRECTORY_SEPARATOR,$m_path_arr);
		// NApp::_Dlog($m_path,'$m_path');
		// For themed views stored outside "modules" directory (with fallback on "modules" directory)
		if($basedir) {
			if(file_exists($basedir.$m_path.$fname)) { return $basedir.$m_path.$fname; }
			if($parents) {
				foreach($parents as $parent) {
					$p_path = get_array_param($parent,'path','','is_string');
					if(file_exists($basedir.$p_path.$fname)) { return $basedir.$parent.$fname; }
				}//END foreach
			}//if($parents)
		}//if($basedir)
		// Get theme view file
		$m_full_path = NApp::app_path().DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$m_path;
			if($themedir && !$basedir) {
			// NApp::_Dlog($m_full_path.'/'.$themedir.$fname,'Check[T.C]');
			if(file_exists($m_full_path.'/'.$themedir.$fname)) { return $m_full_path.'/'.$themedir.$fname; }
			}//if($themedir && !$basedir)
		// Get default theme view file
		// NApp::_Dlog($m_full_path.$defdir.$fname,'Check[D.C]');
		if(file_exists($m_full_path.$defdir.$fname)) { return $m_full_path.$defdir.$fname; }
			// Get view from parent classes hierarchy
		if($parents) {
			foreach($parents as $parent) {
				// NApp::_Dlog($parent,'$parent');
				$p_path = get_array_param($parent,'path','','is_string');
				$p_full_path = NApp::app_path().DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.$p_path;
				// Get from parent theme dir
				if($themedir && !$basedir) {
					// NApp::_Dlog($p_full_path.'/'.$themedir.$fname,'Check[T.P]');
					if(file_exists($p_full_path.'/'.$themedir.$fname)) { return $p_full_path.'/'.$themedir.$fname; }
					}//if($themedir && !$basedir)
				// Get view from current parent class path
				// NApp::_Dlog($p_full_path.$defdir.$fname,'Check[D.P]');
				if(file_exists($p_full_path.$defdir.$fname)) { return $p_full_path.$defdir.$fname; }
			}//END foreach
		}//if($parents)
		throw new AppException('View file ['.$fname.'] not found!');
	}//private function ViewFileProvider
	/**
	 * Gets the view full file name (including absolute path)
	 *
	 * @param  string $name View name (without extension)
	 * @param  string $sub_dir View sub-directory or empty/NULL for none
	 * @param  string $theme_dir View theme sub-directory
	 * (if empty/NULL) application configuration will be used
	 * @return string Returns view full name (including absolute path and extension)
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function GetViewFile($name,$sub_dir = NULL,$theme_dir = NULL) {
		// NApp::StartTimeTrack('MGetViewFile');
		$result = $this->ViewFileProvider($name,$sub_dir,$theme_dir);
		// NApp::_Dlog(number_format(NApp::ShowTimeTrack('MGetViewFile'),3,'.','').' sec.','GetViewFile::'.$name);
		// NApp::_Dlog($result,'GetViewFile::'.$name);
		return $result;
	}//END public function GetViewFile
	/**
	 * description
	 *
	 * @param string $key
	 * @param mixed $def_value
	 * @param string $method
	 * @param string $module
	 * @return mixed
	 * @access public
	 */
	public function GetCustomisationsData($key = NULL,$def_value = NULL,$method = NULL,$module = NULL) {
		$current_module = $module ? $module : get_class($this);
		$current_method = $method ? $method : call_back_trace();
		return self::GlobalGetCustomisationsData($current_module,$current_method,$key,$def_value);
	}//END public function GetCustomisationsData
	/**
	 * Converts the rights revoked database array to an nested array
	 * (on 3 levels - module=>method=>rights_revoked)
	 *
	 * @param $array
	 * @return array
	 * @access public
	 * @static
	 */
	public static function ConvertRightsRevokedArray($array) {
		if(!is_array($array) || !count($array)) { return []; }
		$result = [];
		foreach($array as $line) {
			if(!strlen($line['module']) || !strlen($line['method'])) { continue; }
			$result[$line['module']][$line['method']] = $line;
		}//END foreach
		return $result;
	}//END public static function ConvertRightsRevokedArray

	protected function SetDebugData($data,$label = NULL,$reset = FALSE,$method = NULL) {
		$current_method = $method ? $method : call_back_trace();
		if($reset || !is_array($this->debug_data)) { $this->debug_data = []; }
		if(!isset($this->debug_data[$current_method]) || !is_array($this->debug_data[$current_method])) { $this->debug_data[$current_method] = []; }
		if(is_string($label) && strlen($label)) {
			$this->debug_data[$current_method][$label] = $data;
		} else {
			$this->debug_data[$current_method][] = $data;
		}//if(is_string($label) && strlen($label))
	}//END protected function SetDebugData

	protected function GetDebugData($label,$method = NULL) {
		if(!is_string($label) && !is_numeric($label)) { return NULL; }
		$current_method = $method ? $method : call_back_trace();
		if(!is_array($this->debug_data) || !isset($this->debug_data[$current_method][$label])) { return NULL; }
		return $this->debug_data[$current_method][$label];
	}//END protected function GetDebugData

	public function GetCallDebugData($params = NULL) {
		$clear = $params->safeGet('clear',FALSE,'bool');
		$method = $params->safeGet('method',NULL,'is_string');
		$result = NULL;
		if(is_null($method)) {
			$result = $this->debug_data;
			if($clear) { $this->debug_data = NULL; }
		} elseif(is_string($method) && strlen($method) && isset($this->debug_data[$method])) {
			$result = $this->debug_data[$method];
			if($clear) { $this->debug_data[$method] = NULL; }
		}//if(is_null($method))
		return $result;
	}//END public function GetCallDebugData
}//END class Module
?>