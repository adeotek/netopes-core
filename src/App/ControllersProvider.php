<?php
/**
 * Controllers provider class
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\App;
use Error;
use ErrorHandler;
use Exception;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;

/**
 * ControllersProvider class
 */
class ControllersProvider {
    /**
     * @param string $name
     * @param bool   $base
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public static function GetDRightsUid(string $name,bool $base=FALSE): ?string {
        return static::GetModule($name,$base)->GetDRightsUid();
    }//END public static function GetInstance

    /**
     * Invoke a controller method
     *
     * @param string      $controller         Module name
     * @param string      $method             Method to be searched
     * @param array       $params             An array of parameters
     * @param null|string $dynamicTargetId
     * @param bool        $resetSessionParams If set to TRUE the session parameters for this controller method,
     *                                        will be deleted
     * @param array       $beforeCall         An array of parameters to be passed to the _BeforeCall method
     *                                        If FALSE is supplied, the _BeforeCall method will not be invoked
     * @return mixed Returns the method result
     * @throws \NETopes\Core\AppException
     */
    public static function Exec(string $controller,string $method,$params=NULL,?string $dynamicTargetId=NULL,bool $resetSessionParams=FALSE,$beforeCall=NULL) {
        if(!self::MethodExists($controller,$method)) {
            throw new AppException("Undefined method [$method] in controller [$controller] !",E_ERROR,1);
        }
        try {
            $controllerInstance=self::GetInstance($controller);
            $callerClass=AppHelpers::GetParentControllerCaller();
            return $controllerInstance->Exec($method,$params,$dynamicTargetId,$resetSessionParams,$beforeCall,$callerClass);
        } catch(Error $er) {
            throw AppException::GetInstance($er,'php',-1);
        } catch(AppException $e) {
            if($e->getSeverity()<=0) {
                throw $e;
            }
            ErrorHandler::AddError($e);
            return NULL;
        }//END try
    }//END public static function GetDRightsUid

    /**
     * Check if controller method exists
     *
     * @param string $controller Module name
     * @param string $method     Method to be searched
     * @param bool   $base       If set to TRUE, searches the base controller only, not the custom one (if there is one)
     * @return bool Returns TRUE if the method exist of FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function MethodExists($controller,$method,$base=FALSE) {
        if(!strlen($controller) || !strlen($method)) {
            return FALSE;
        }
        $controller=self::GetModule($controller,$base);
        return is_callable([$controller,$method]);
    }//END public static function MethodExists

    /**
     * Get a controller instance
     *
     * @param string $name Module name
     * @param bool   $base If set to TRUE, gets the base controller not the custom controller (if there is one)
     * @return object Returns the controller instance
     * @throws \NETopes\Core\AppException
     */
    public static function GetInstance(string $name,bool $base=FALSE) {
        $nsPrefix=AppConfig::GetValue('app_root_namespace').'\\'.AppConfig::GetValue('app_controllers_namespace_prefix');
        if(class_exists('\\'.trim($name,'\\'))) {
            $mName='\\'.trim($name,'\\');
        } else {
            $mName='\\'.$nsPrefix.trim($name,'\\');
        }//if(class_exists('\\'.trim($name,'\\')))
        if($base) {
            return $mName::GetInstance($name,$mName,FALSE);
        }
        $mArr=explode('\\',trim($mName,'\\'));
        $bName=array_pop($mArr);
        $mDir=array_pop($mArr);
        $cName='\\'.implode('\\',$mArr).'\\'.($bName==$mDir ? $bName.'Custom' : $mDir).'\\'.$bName.'Custom';
        $custom=TRUE;
        try {
            if(!class_exists($cName)) {
                $cName=$mName;
                $custom=FALSE;
            }//if(!class_exists($cName))
        } catch(Exception $ne) {
            $cName=$mName;
            $custom=FALSE;
        }//END try
        return $cName::GetInstance($name,$cName,$custom);
    }//END public static function Exec

    /**
     * Invoke a controller method (unsafe)
     *
     * @param string      $controller         Module name
     * @param string      $method             Method to be searched
     * @param array       $params             An array of parameters
     * @param null|string $dynamicTargetId
     * @param bool        $resetSessionParams If set to TRUE the session parameters for this controller method,
     *                                        will be deleted
     * @param array       $beforeCall         An array of parameters to be passed to the _BeforeCall method
     *                                        If FALSE is supplied, the _BeforeCall method will not be invoked
     * @return mixed Returns the method result
     * @throws \NETopes\Core\AppException
     */
    public static function ExecUnsafe(string $controller,string $method,$params=NULL,?string $dynamicTargetId=NULL,bool $resetSessionParams=FALSE,$beforeCall=NULL) {
        if(!self::MethodExists($controller,$method)) {
            throw new AppException("Undefined method [$method] in controller [$controller] !",E_ERROR,1);
        }
        $controllerInstance=self::GetInstance($controller);
        return $controllerInstance->Exec($method,$params,$dynamicTargetId,$resetSessionParams,$beforeCall);
    }//END public static function ExecUnsafe

    /**
     * Invoke a controller method of the base controller, not the custom one (if there is one)
     *
     * @param string      $controller         Module name
     * @param string      $method             Method to be searched
     * @param array       $params             An array of parameters
     * @param null|string $dynamicTargetId
     * @param bool        $resetSessionParams If set to TRUE the session parameters for this controller method,
     *                                        will be deleted
     * @param array       $beforeCall         An array of parameters to be passed to the _BeforeCall method
     *                                        If FALSE is supplied, the _BeforeCall method will not be invoked
     * @return mixed Returns the method result
     * @throws \NETopes\Core\AppException
     */
    public static function ExecNonCustom(string $controller,string $method,$params=NULL,?string $dynamicTargetId=NULL,bool $resetSessionParams=FALSE,$beforeCall=NULL) {
        if(!self::MethodExists($controller,$method,TRUE)) {
            throw new AppException("Undefined method [$method] in controller [$controller] !",E_ERROR,1);
        }
        try {
            $controllerInstance=self::GetInstance($controller,TRUE);
            return $controllerInstance->Exec($method,$params,$dynamicTargetId,$resetSessionParams,$beforeCall);
        } catch(AppException $e) {
            if($e->getSeverity()<=0) {
                throw $e;
            }
            ErrorHandler::AddError($e);
            return NULL;
        }//END try
    }//END public static function ExecNonCustom
}//END class ModulesProvider