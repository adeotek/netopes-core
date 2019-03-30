<?php
/**
 * Modules provider class file
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.1
 * @filesource
 */
namespace NETopes\Core\App;
use Error;
use ErrorHandler;
use Exception;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
/**
 * Class ModulesProvider
 * @package  NETopes\Core\App
 */
class ModulesProvider {
    /**
     * Get a module instance
     * @param  string $name Module name
     * @param  bool   $base If set to TRUE, gets the base module not the custom module (if there is one)
     * @return object Returns the module instance
     * @throws \NETopes\Core\AppException
     */
    public static function GetModule($name,$base = FALSE) {
        $nsPrefix = AppConfig::GetValue('app_root_namespace').'\\'.AppConfig::GetValue('app_modules_namespace_prefix');
        if(class_exists('\\'.trim($name,'\\'))) {
            $mName = '\\'.trim($name,'\\');
        } else {
            $mName = '\\'.$nsPrefix.trim($name,'\\');
        }//if(class_exists('\\'.trim($name,'\\')))
        if($base) { return $mName::GetInstance($name,$mName,FALSE); }
        $mArr = explode('\\',trim($mName,'\\'));
        $bName = array_pop($mArr);
        $mDir = array_pop($mArr);
        $cName = '\\'.implode('\\',$mArr).'\\'.($bName==$mDir ? $bName.'Custom' : $mDir).'\\'.$bName.'Custom';
        $custom = TRUE;
        try {
            if(!class_exists($cName)) {
                $cName = $mName;
                $custom = FALSE;
            }//if(!class_exists($cName))
        } catch(Exception $ne) {
            $cName = $mName;
            $custom = FALSE;
        }//END try
        return $cName::GetInstance($name,$cName,$custom);
    }//END public static function GetModule

    /**
     * Check if module method exists
     * @param  string $module Module name
     * @param  string $method Method to be searched
     * @param  bool   $base   If set to TRUE, searches the base module only, not the custom one (if there is one)
     * @return bool Returns TRUE if the method exist of FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function ModuleMethodExists($module,$method,$base = FALSE) {
        if(!strlen($module) || !strlen($method)) { return FALSE; }
        $module = self::GetModule($module,$base);
        return method_exists($module,$method);
    }//END public static function ModuleMethodExists

    /**
     * Invoke a module method
     * @param  string $module Module name
     * @param  string $method Method to be searched
     * @param  array  $params An array of parameters
     * @param null|string $dynamicTargetId
     * @param  bool   $resetSessionParams If set to TRUE the session parameters for this module method,
     * will be deleted
     * @param  array  $beforeCall An array of parameters to be passed to the _BeforeCall method
     * If FALSE is supplied, the _BeforeCall method will not be invoked
     * @return mixed Returns the method result
     * @throws \NETopes\Core\AppException
     */
    public static function Exec(string $module,string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $resetSessionParams = FALSE,$beforeCall = NULL) {
        if(!self::ModuleMethodExists($module,$method)) { throw new AppException("Undefined method [$method] in module [$module] !",E_ERROR,1); }
        try {
            $moduleInstance = self::GetModule($module);
            return $moduleInstance->Exec($method,$params,$dynamicTargetId,$resetSessionParams,$beforeCall);
        } catch(Error $er) {
            throw AppException::GetInstance($er,'php',-1);
        } catch(AppException $e) {
            if($e->getSeverity()<=0) { throw $e; }
            ErrorHandler::AddError($e);
            return NULL;
        }//END try
    }//END public static function Exec

    /**
     * Invoke a module method (unsafe)
     * @param  string     $module             Module name
     * @param  string     $method             Method to be searched
     * @param  array      $params             An array of parameters
     * @param null|string $dynamicTargetId
     * @param  bool       $resetSessionParams If set to TRUE the session parameters for this module method,
     * will be deleted
     * @param  array      $beforeCall         An array of parameters to be passed to the _BeforeCall method
     * If FALSE is supplied, the _BeforeCall method will not be invoked
     * @return mixed Returns the method result
     * @throws \NETopes\Core\AppException
     */
    public static function ExecUnsafe(string $module,string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $resetSessionParams = FALSE,$beforeCall = NULL) {
        if(!self::ModuleMethodExists($module,$method)) { throw new AppException("Undefined method [$method] in module [$module] !",E_ERROR,1); }
        $moduleInstance = self::GetModule($module);
        return $moduleInstance->Exec($method,$params,$dynamicTargetId,$resetSessionParams,$beforeCall);
    }//END public static function ExecUnsafe

    /**
     * Invoke a module method of the base module, not the custom one (if there is one)
     * @param  string $module Module name
     * @param  string $method Method to be searched
     * @param  array  $params An array of parameters
     * @param null|string $dynamicTargetId
     * @param  bool   $resetSessionParams If set to TRUE the session parameters for this module method,
     * will be deleted
     * @param  array  $beforeCall An array of parameters to be passed to the _BeforeCall method
     * If FALSE is supplied, the _BeforeCall method will not be invoked
     * @return mixed Returns the method result
     * @throws \NETopes\Core\AppException
     */
    public static function ExecNonCustom(string $module,string $method,$params = NULL,?string $dynamicTargetId = NULL,bool $resetSessionParams = FALSE,$beforeCall = NULL) {
        if(!self::ModuleMethodExists($module,$method,TRUE)) { throw new AppException("Undefined method [$method] in module [$module] !",E_ERROR,1); }
        try {
            $moduleInstance = self::GetModule($module,TRUE);
            return $moduleInstance->Exec($method,$params,$dynamicTargetId,$resetSessionParams,$beforeCall);
        } catch(AppException $e) {
            if($e->getSeverity()<=0) { throw $e; }
            ErrorHandler::AddError($e);
            return NULL;
        }//END try
    }//END public static function ExecNonCustom
}//END class ModulesProvider