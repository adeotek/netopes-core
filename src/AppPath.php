<?php
/**
 * NETopes application path class file
 * The NETopes path class contains helper methods for application paths.
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core;
/**
 * NETopes path class
 * The NETopes path class contains helper methods for application paths.
 * @package  NETopes\Core
 */
class AppPath {
	/**
	 * Get NETopes path
	 * @return string
	 */
	public static function GetPath(): string {
		return __DIR__;
	}//END public static function GetPath
	/**
	 * Get NETopes boot file
	 * @return string
	 */
	public static function GetBootFile(): string {
		return __DIR__.'/boot.php';
	}//END public static function GetBootFile
}//END class AppPath