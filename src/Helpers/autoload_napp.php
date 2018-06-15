<?php
/**
 * NETopes autoloader file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
	/**
	 * NETopes autoloader function
	 *
	 * @param  string $class Called class name
	 * @return bool
	 * @throws \PAF\AppException
	 */
	function _napp_autoload($class) {
		if(strpos(trim($class,'\\'),'\\')===FALSE) { return FALSE; }
		$a_class = explode('\\',trim($class,'\\'));
		$r_ns = array_shift($a_class);
		$s_ns = isset($a_class[0]) ? $a_class[0] : '';
		if(strtolower($r_ns)!='netopes' || strtolower($s_ns)=='libraries') { return FALSE; }
		if(!file_exists(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php')) {
			$dbg_bt = debug_backtrace();
			switch(strtoupper($s_ns)) {
				case 'MODULES':
				case 'DATASOURCES':
					$dbg_item = count($dbg_bt)<5 ? $dbg_bt[count($dbg_bt)-1] : $dbg_bt[4];
					$ex = new \PAF\AppException('Class file not found ['.$class.']',E_ERROR,1,$dbg_item['file'],$dbg_item['line'],'app',NULL,$dbg_bt);
					break;
				default:
					$ex = new \PAF\AppException('Class file not found ['.$class.']',E_ERROR,1,$dbg_bt[1]['file'],$dbg_bt[1]['line'],'app',NULL,$dbg_bt);
					break;
			}//END switch
			throw $ex;
		}
		require_once(_AAPP_ROOT_PATH._AAPP_APPLICATION_PATH.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$a_class).'.php');
		return TRUE;
	}//END function _napp_autoload
?>