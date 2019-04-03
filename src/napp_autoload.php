<?php
/**
 * NETopes autoloader file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
use NETopes\Core\AppException;

/**
 * NETopes autoloader function
 * Used to autoload DataSources and Modules
 *
 * @param string $class Called class name
 * @return bool
 * @throws \NETopes\Core\AppException
 */
function _napp_autoload($class) {
    if(strpos(trim($class,'\\'),'\\')===FALSE) {
        return FALSE;
    }
    $a_class=explode('\\',trim($class,'\\'));
    $r_ns=array_shift($a_class);
    if(strtolower($r_ns)!='netopes') {
        return FALSE;
    }
    $s_ns=isset($a_class[0]) ? $a_class[0] : '';
    switch(strtoupper($s_ns)) {
        case 'MODULES':
        case 'DATASOURCES':
        case 'DATAENTITIES':
            if(file_exists(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php')) {
                require_once(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php');
                return TRUE;
            }//if(file_exists(_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php'))
            $dbg_bt=debug_backtrace();
            $dbg_item=count($dbg_bt)<5 ? $dbg_bt[count($dbg_bt) - 1] : $dbg_bt[4];
            $ex=new AppException('Class file not found ['.$class.']',E_ERROR,1,$dbg_item['file'],$dbg_item['line'],'app',NULL,$dbg_bt);
            throw $ex;
        default:
            return FALSE;
    }//END switch
}//END function _napp_autoload