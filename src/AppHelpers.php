<?php
/**
 * NETopes application helpers class file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
 * @filesource
 */
namespace NETopes\Core;
use NApp;

/**
 * Class AppHelpers
 *
 * @package NETopes\Core
 */
class AppHelpers {
    /**
     * @var array
     */
    private static $globals = [];
    /**
     * Get application non-public repository path
     *
     * @return string
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetRepositoryPath() {
		$repository_path = AppConfig::GetValue('repository_path');
		if(is_string($repository_path) && strlen($repository_path) && file_exists($repository_path)) {
			return rtrim($repository_path,'/\\').'/';
		}//if(is_string($repository_path) && strlen($repository_path) && file_exists($repository_path))
		return NApp::$app_path.'/repository/';
	}//END public static function GetRepositoryPath
    /**
     * Get application cache path
     *
     * @return string
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetCachePath() {
		$cache_path = AppConfig::GetValue('app_cache_path');
		if(is_string($cache_path) && strlen($cache_path) && file_exists($cache_path)) {
			return rtrim($cache_path,'/\\').'/';
		}//if(is_string($cache_path) && strlen($cache_path) && file_exists($cache_path))
		if(!file_exists(NApp::$app_path.'/.cache')) {
			mkdir(NApp::$app_path.'/.cache',755);
		}//if(!file_exists(NApp::$app_path.'/.cache'))
		return NApp::$app_path.'/.cache/';
	}//END public static function GetCachePath
    /**
	 * description
	 *
	 * @param      $key
	 * @param null $defaultValue
	 * @param null $validation
	 * @return mixed
	 * @access public
	 */
	public static function GetGlobalVar($key,$defaultValue = NULL,$validation = NULL) {
	    return get_array_value(self::$globals,$key,$defaultValue,$validation);
	}//END public static function _GetGlobalVar
	/**
	 * description
	 *
	 * @param $key
	 * @param $value
	 * @return bool
	 * @access public
	 */
	public static function SetGlobalVar($key,$value) {
		if(!is_numeric($key) && (!is_string($key) || !strlen($key))) { return FALSE; }
		if(!is_array(self::$globals)) { self::$globals = []; }
		self::$globals[$key] = $value;
		return TRUE;
	}//END public static function SetGlobalVar
	/**
	 * description
	 *
	 * @param      $key
	 * @param null $defaultValue
	 * @param null $validation
	 * @return mixed
	 * @access public
	 */
	public function _GetRequestParamValue($key,$defaultValue = NULL,$validation = NULL) {
		return get_array_value(self::$globals,['req_params',$key],$defaultValue,$validation);
	}//END public function _GetRequestParamValue
	/**
	 * description
	 *
	 * @return void
	 * @access public
	 */
	public static function ProcessRequestParams() {
		if(!is_array(self::$globals)) { self::$globals = []; }
		if(!array_key_exists('req_params',self::$globals) || !is_array(self::$globals['req_params'])) { self::$globals['req_params'] = []; }
		$url = NApp::Url();
		$uripage = $url->GetParamElement('page');
		$uripag = $url->GetParamElement('pag');
		self::$globals['req_params']['id_page'] = is_numeric($uripage) ? $uripage : NULL;
		self::$globals['req_params']['pagination'] = is_numeric($uripag) ? $uripag : NULL;
		$urlid = strtolower(trim($url->GetParamElement('urlid'),'/'));
		if(strpos($urlid,'/')===FALSE) {
			self::$globals['req_params']['category'] = NULL;
			self::$globals['req_params']['subcategories'] = NULL;
			self::$globals['req_params']['page'] = $urlid;
		} else {
			$urlid_arr = explode('/',$urlid);
			$e_page = array_pop($urlid_arr);
			$e_cat = array_shift($urlid_arr);
			$e_scat = is_array($urlid_arr) && count($urlid_arr) ? implode('/',$urlid_arr) : NULL;
			self::$globals['req_params']['category'] = $e_cat;
			self::$globals['req_params']['subcategories'] = $e_scat;
			self::$globals['req_params']['page'] = $e_page;
		}//if(strpos($urlid,'/')===FALSE)
		self::$globals['req_params']['module'] = $url->GetParam('module');
		self::$globals['req_params']['action'] = $url->GetParam('a');
	}//END public static function _ProcessRequestParams
	/**
	 * Add javascript code to the dynamic js queue (executed at the end of the current request)
	 *
	 * @param  string $value Javascript code
	 * @param bool    $dynamic
	 * @return void
	 * @access public
	 */
	public static function AddJsScript(string $value,bool $dynamic = FALSE) {
		if(!strlen($value)) { return; }
		if(!$dynamic && NApp::IsAjax() && NApp::IsValidAjaxRequest()) {
			NApp::Ajax()->ExecuteJs($value);
		} else {
			$dynamic_js_scripts = self::GetGlobalVar('dynamic_js_scripts',[],'is_array');
			$dynamic_js_scripts[] = ['js'=>$value];
			self::SetGlobalVar('dynamic_js_scripts',$dynamic_js_scripts);
		}//if(!$dynamic && NApp::IsAjax() && NApp::IsValidAjaxRequest())
	}//END public static function AddJsScript
	/**
	 * Get dynamic javascript to be executed
	 *
	 * @return string Returns scripts to be executed
	 * @access public
	 */
	public static function GetDynamicJs() {
		$scripts = self::GetGlobalVar('dynamic_js_scripts',[],'is_array');
		if(!count($scripts)) { return NULL; }
		$html_data = '';
		$data = '';
		foreach($scripts as $s) {
			if(is_array($s)) {
				$d_js = get_array_value($s,'js','','is_string');
				$d_html = get_array_value($s,'html','','is_string');
				if(strlen($d_js)) { $data .= $d_js."\n"; }
				if(strlen($d_html)) { $html_data .= $d_html."\n"; }
			} elseif(is_string($s) && strlen($s)) {
				$data .= $s."\n\n";
			}//if(is_array($s))
		}//END foreach
		return $html_data.'<script type="text/javascript">'."\n".$data."\n".'</script>'."\n";
	}//END public static function GetDynamicJs
	/**
     * Gets the application copyright
     *
     * @return string Returns the application copyright
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function GetAppCopyright() {
		$copyright = AppConfig::GetValue('app_copyright');
		return ($copyright ? $copyright : '&copy; ').date('Y');
	}//END public function GetAppCopyright
    /**
     * Gets the current application version
     *
     * @param  string $type Specifies the return type:
     * - NULL or empty string (default) for return as string
     * - 'array' for return as array (key-value)
     * - 'major' for return only the major version as int
     * - 'minor' for return only the minor version as int
     * - 'build' for return only the build version as int
     * @return mixed Returns the application version as a string or an array or a specific part of the version
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetVersion($type = NULL) {
		switch($type) {
		  	case 'array':
				$ver_arr = explode('.',AppConfig::GetValue('app_version'));
				return array('major'=>$ver_arr[0],'minor'=>$ver_arr[1],'build'=>$ver_arr[2]);
				break;
			case 'major':
				$ver_arr = explode('.',AppConfig::GetValue('app_version'));
				return intval($ver_arr[0]);
				break;
			case 'minor':
				$ver_arr = explode('.',AppConfig::GetValue('app_version'));
				return intval($ver_arr[1]);
				break;
			case 'buid':
				$ver_arr = explode('.',AppConfig::GetValue('app_version'));
				return intval($ver_arr[2]);
				break;
		  	default:
				return AppConfig::GetValue('app_version');
				break;
		}//END switch
	}//END public static function GetVersion
    /**
     * Gets the current application framework version
     *
     * @param  string $type Specifies the return type:
     * - NULL or empty string (default) for return as string
     * - 'array' for return as array (key-value)
     * - 'major' for return only the major version as int
     * - 'minor' for return only the minor version as int
     * - 'build' for return only the build version as int
     * @return mixed Returns the application framework version
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetFrameworkVersion($type = NULL) {
		switch($type) {
		    case 'array':
				$ver_arr = explode('.',AppConfig::GetValue('framework_version'));
				return array('major'=>$ver_arr[0],'minor'=>$ver_arr[1],'build'=>$ver_arr[2]);
				break;
			case 'major':
				$ver_arr = explode('.',AppConfig::GetValue('framework_version'));
				return intval($ver_arr[0]);
				break;
			case 'minor':
				$ver_arr = explode('.',AppConfig::GetValue('framework_version'));
				return intval($ver_arr[1]);
				break;
			case 'buid':
				$ver_arr = explode('.',AppConfig::GetValue('framework_version'));
				return intval($ver_arr[2]);
				break;
		    default:
				return AppConfig::GetValue('framework_version');
				break;
		}//END switch
	}//END public static function GetFrameworkVersion
}//END class AppHelpers