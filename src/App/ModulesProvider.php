<?php
/**
 * Modules controller (provider) class file
 *
 * @package    NETopes\Modules
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.10.0
 * @filesource
 */
namespace NETopes\Core\App;
use PAF\AppConfig;
use PAF\AppException;

/**
 * Modules controller (provider) class
 *
 * @package  NETopes\Modules
 * @access   public
 */
class ModulesProvider {
	/**
	 * @var    string Module class prefix
	 * @access public
	 * @static
	 */
	public static $nsPath = 'Modules\\';
	/**
	 * Get a module instance
	 *
	 * @param  string $name Module name
	 * @param  bool   $base If set to TRUE, gets the base module not the custom module (if there is one)
	 * @return object Returns the module instance
	 * @access public
	 * @static
	 */
	public static function GetModule($name,$base = FALSE) {
	    $nsPrefix = AppConfig::app_root_namespace().'\\'.self::$nsPath;
	    if(substr($name,0,1)==='\\' && substr(trim($name,'\\'),0,strlen($nsPrefix)-1)!==$nsPrefix) {
		    return $name::GetInstance($name,$name,FALSE);
		}//if(substr($name,0,1)==='\\' && substr(trim($name,'\\'),0,strlen($nsPrefix)-1)!==$nsPrefix)
        $mName = '\\'.(substr(trim($name,'\\'),0,strlen($nsPrefix)-1)==$nsPrefix ? '' : $nsPrefix).trim($name,'\\');
        if($base) { return $mName::GetInstance($name,$mName,FALSE); }
        $mArr = explode('\\',trim($mName,'\\'));
        $bName = array_pop($mArr);
        $mDir = array_pop($mArr);
        $cmName = ($bName==$mDir ? $bName.'Custom' : $mDir).'\\'.$bName.'Custom';
        array_shift($mArr);
        $cPath = implode('\\',$mArr).'\\';
        if(file_exists(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.$cPath.$cmName.'.php')) {
            $cName = AppConfig::app_root_namespace().'\\'.$cPath.$cmName;
            $custom = TRUE;
        } else {
            $cName = $mName;
            $custom = FALSE;
        }//if(file_exists(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.$c_path.$c_name.'.php'))
		return $cName::GetInstance($name,$cName,$custom);
	}//END public static function GetModule
	/**
	 * Check if module method exists
	 *
	 * @param  string $module Module name
	 * @param  string $method Method to be searched
	 * @param  bool   $base If set to TRUE, searches the base module only, not the custom one (if there is one)
	 * @return bool Returns TRUE if the method exist of FALSE otherwise
	 * @access public
	 * @static
	 */
	public static function ModuleMethodExists($module,$method,$base = FALSE) {
		if(!strlen($module) || !strlen($method)) { return FALSE; }
		$module = self::GetModule($module,$base);
		return method_exists($module,$method);
	}//END public static function ModuleMethodExists
	/**
	 * Invoke a module method
	 *
	 * @param  string $module Module name
	 * @param  string $method Method to be searched
	 * @param  array  $params An array of parameters
     * @param null|string $dynamicTargetId
	 * @param  bool   $reset_session_params If set to TRUE the session parameters for this module method,
	 * will be deleted
	 * @param  array  $before_call An array of parameters to be passed to the _BeforeCall method
	 * If FALSE is supplied, the _BeforeCall method will not be invoked
	 * @return mixed Returns the method result
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function Exec(string $module,string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $reset_session_params = FALSE,$before_call = NULL) {
		if(!self::ModuleMethodExists($module,$method)) { throw new AppException("Undefined method [$method] in module [$module] !",E_ERROR,1); }
		try {
			$module_instance = self::GetModule($module);
			return $module_instance->Exec($method,$params,$dynamicTargetId,$reset_session_params,$before_call);
		} catch(AppException $e) {
			if($e->getSeverity()<=0) { throw $e; }
			\ErrorHandler::AddError($e);
			return NULL;
		}//END try
	}//END public static function Exec
	/**
	 * Invoke a module method (unsafe)
	 *
	 * @param  string $module Module name
	 * @param  string $method Method to be searched
	 * @param  array  $params An array of parameters
     * @param null|string $dynamicTargetId
	 * @param  bool   $reset_session_params If set to TRUE the session parameters for this module method,
	 * will be deleted
	 * @param  array  $before_call An array of parameters to be passed to the _BeforeCall method
	 * If FALSE is supplied, the _BeforeCall method will not be invoked
	 * @return mixed Returns the method result
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function ExecUnsafe(string $module,string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $reset_session_params = FALSE,$before_call = NULL) {
		if(!self::ModuleMethodExists($module,$method)) { throw new AppException("Undefined method [$method] in module [$module] !",E_ERROR,1); }
		$module_instance = self::GetModule($module);
		return $module_instance->Exec($method,$params,$dynamicTargetId,$reset_session_params,$before_call);
	}//END public static function ExecUnsafe
	/**
	 * Invoke a module method of the base module, not the custom one (if there is one)
	 *
	 * @param  string $module Module name
	 * @param  string $method Method to be searched
	 * @param  array  $params An array of parameters
     * @param null|string $dynamicTargetId
	 * @param  bool   $reset_session_params If set to TRUE the session parameters for this module method,
	 * will be deleted
	 * @param  array  $before_call An array of parameters to be passed to the _BeforeCall method
	 * If FALSE is supplied, the _BeforeCall method will not be invoked
	 * @return mixed Returns the method result
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function ExecNonCustom(string $module,string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $reset_session_params = FALSE,$before_call = NULL) {
		if(!self::ModuleMethodExists($module,$method,TRUE)) { throw new AppException("Undefined method [$method] in module [$module] !",E_ERROR,1); }
		try {
			$module_instance = self::GetModule($module,TRUE);
			return $module_instance->Exec($method,$params,$dynamicTargetId,$reset_session_params,$before_call);
		} catch(AppException $e) {
			if($e->getSeverity()<=0) { throw $e; }
			\ErrorHandler::AddError($e);
			return NULL;
		}//END try
	}//END public static function ExecNonCustom
}//END class ModulesProvider