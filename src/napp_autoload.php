<?php
/**
 * NETopes autoloader file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.0.1
 * @filesource
 */
	/**
	 * NETopes autoloader function
	 *
	 * Used to autoload DataSources and Modules
	 *
	 * @param  string $class Called class name
	 * @return bool
	 * @throws \PAF\AppException
	 */
	function _napp_autoload($class) {
		if(strpos(trim($class,'\\'),'\\')===FALSE) { return FALSE; }
		$a_class = explode('\\',trim($class,'\\'));
		$r_ns = array_shift($a_class);
		if(strtolower($r_ns)!='netopes') { return FALSE; }
		$s_ns = isset($a_class[0]) ? $a_class[0] : '';
		switch(strtoupper($s_ns)) {
			case 'MODULES':
			case 'DATASOURCES':
			case 'DATAENTITIES':
				if(file_exists(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php')) {
					require_once(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php');
					return TRUE;
				}//if(file_exists(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php'))
				$dbg_bt = debug_backtrace();
				$dbg_item = count($dbg_bt)<5 ? $dbg_bt[count($dbg_bt)-1] : $dbg_bt[4];
				$ex = new \PAF\AppException('Class file not found ['.$class.']',E_ERROR,1,$dbg_item['file'],$dbg_item['line'],'app',NULL,$dbg_bt);
				throw $ex;
			default:
				return FALSE;
		}//END switch
	}//END function _napp_autoload
?>