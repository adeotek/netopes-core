<?php
/**
 * NETopes application interface file.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
namespace NETopes\Core\App;
/**
 * Interface IApp
 *
 * @package  NETopes\Core\App
 */
interface IApp {
	/**
	 * Classic singleton method for retrieving the NETopes application object
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $session_init Flag indicating if session should be started or not
	 * @param  bool  $do_not_keep_alive Flag indicating if session should be kept alive by the current request
	 * @param  bool  $shell Shell mode on/off
	 * @return \NETopes\Core\App\IApp Returns the NETopes application instance
	 * @throws \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static function GetInstance($ajax = FALSE,$params = [],$session_init = TRUE,$do_not_keep_alive = NULL,$shell = FALSE);
	/**
	 * Static setter for phash property
	 *
	 * @param  string $value The new value for phash property
	 * @return void
	 * @access public
	 * @static
	 */
	public static function SetPhash($value);
	/**
	 * Gets application absolute path
	 *
	 * @return string Returns the application absolute path
	 * @access public
	 */
	public function GetAppAbsolutePath();
}//END interface IApp