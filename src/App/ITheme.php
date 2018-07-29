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
 * @version    2.2.0.1
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
	public function GetMainContentStart(?string $title): void;
	public function GetMainContentEnd(): void;
	public function GetModalStart(): void;
	public function GetModalEnd(): void;
}//END interface ITheme
?>