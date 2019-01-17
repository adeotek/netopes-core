<?php
/**
 * NETopes application interface file.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
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
	 * Application initializer method
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $sessionInit Flag indicating if session should be started or not
	 * @param  bool|null  $doNotKeepAlive Flag indicating if session should be kept alive by the current request
	 * @param  bool  $isCli Run in CLI mode
	 * @return void
	 * @throws \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static function Start(bool $ajax = FALSE,array $params = [],bool $sessionInit = TRUE,$doNotKeepAlive = NULL,bool $isCli = FALSE): void;
	/**
	 * Gets application state
	 *
	 * @return bool Application (session if started) state
	 * @access public
	 */
	public static function GetAppState(): bool;
	/**
	 * Page hash getter
	 *
	 * @return string|null
	 * @access public
	 */
	public static function GetPhash(): ?string;
	/**
	 * Page hash setter
	 *
	 * @param  string $value The new value for phash property
	 * @return void
	 * @access public
	 */
	public static function SetPhash(?string $value): void;
	/**
	 * Get theme object
	 *
	 * @param string $theme
	 * @return ITheme|null
	 * @access public
	 */
	public static function GetTheme(string $theme = ''): ?ITheme;
	/**
	 * Get current user ID
	 *
	 * @return int|null Returns current user ID
	 * @access public
	 */
	public static function GetCurrentUserId(): ?int;
}//END interface IApp