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
 * @version    2.2.5.3
 * @filesource
 */
namespace NETopes\Core\App;
/**
 * Application Theme interface
 *
 * @package    NETopes\Core\App
 */
interface ITheme {
	public function GetButtonClass(?string $type): string;
	public function GetMainContainer(): void;
	public function GetModalContainer(): void;
}//END interface ITheme
?>