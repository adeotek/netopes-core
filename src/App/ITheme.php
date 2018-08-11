<?php
/**
 * Application Theme interface file
 *
 *
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.5.7
 * @filesource
 */
namespace NETopes\Core\App;
/**
 * Application Theme interface
 *
 * @package    NETopes\Core\App
 */
interface ITheme {
	/**
	 * @param null|string $type
	 * @param null|string $extra
	 * @return string
	 */
	public function GetButtonClass(?string $type,?string $extra = NULL): string;
	/**
	 * @param bool $hasActions
	 * @param bool $hasTitle
	 * @return void
	 * @access public
	 */
	public function GetMainContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
	/**
	 * @param bool $hasActions
	 * @param bool $hasTitle
	 * @return void
	 * @access public
	 */
	public function GetModalContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
	/**
	 * @param bool $hasActions
	 * @param bool $hasTitle
	 * @return void
	 * @access public
	 */
	public function GetSecondaryContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
	/**
	 * @param bool $hasActions
	 * @param bool $hasTitle
	 * @return void
	 * @access public
	 */
	public function GetGenericContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
}//END interface ITheme
?>