<?php
/**
 * NETopes application path class file
 *
 * The NETopes path class contains helper methods for application paths.
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.0.1
 * @filesource
 */
namespace NETopes\Core;
/**
 * NETopes path class
 *
 * The NETopes path class contains helper methods for application paths.
 *
 * @package  NETopes\Core
 * @access   public
 */
class AppPath {
	/**
	 * Get PAF path
	 *
	 * @return string
	 */
	public static function GetPath(): string {
		return __DIR__;
	}//END public static function GetPath
	/**
	 * Get PAF boot file
	 *
	 * @return string
	 */
	public static function GetBootFile(): string {
		return __DIR__.'/boot.php';
	}//END public static function GetBootFile
}//END class AppPath
?>