<?php
/**
 * Application Theme interface file
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.6.1
 * @filesource
 */
namespace NETopes\Core\App;

/**
 * Button type DEFAULT
 */
define('THEME_BTN_DEFAULT','default');
/**
 * Button type PRIMARY
 */
define('THEME_BTN_PRIMARY','primary');
/**
 * Button type INFO
 */
define('THEME_BTN_INFO','info');
/**
 * Button type SUCCESS
 */
define('THEME_BTN_SUCCESS','success');
/**
 * Button type WARNING
 */
define('THEME_BTN_WARNING','warning');
/**
 * Button type DANGER
 */
define('THEME_BTN_DANGER','danger');
/**
 * Button type SPECIAL WARNING
 */
define('THEME_BTN_SPECIAL_W','special-w');
/**
 * Button type SPECIAL DARK
 */
define('THEME_BTN_SPECIAL_D','special-d');
/**
 * Button type SPECIAL LIGHT
 */
define('THEME_BTN_SPECIAL_L','special-l');
/**
 * Application Theme interface
 *
 * @package    NETopes\Core\App
 */
interface ITheme {
	/**
	 * Get application theme type
	 * Values:
	 * - native/NULL -> custom HTML+CSS
	 * - jqueryui -> jQuery UI
	 * - bootstrap2 -> Tweeter Bootstrap 2
	 * - bootstrap3 -> Tweeter Bootstrap 3
	 * - bootstrap4 -> Tweeter Bootstrap 4
	 *
	 * @return string
	 */
	public function GetThemeType(): string;
	/**
	 * Get application theme default controls size
	 * Values: xlg/lg/sm/xs/xxs
	 *
	 * @return string
	 */
	public function GetControlsDefaultSize(): string;
	/**
	 * Get application theme default actions (buttons) size
	 * Values: xlg/lg/sm/xs/xxs
	 *
	 * @return string
	 */
	public function GetButtonsDefaultSize(): string;
	/**
	 * @param null|string $type
	 * @param null|string $extra
	 * @return string
	 */
	public function GetButtonClass(?string $type,?string $extra = NULL): string;
	/**
	 * @param int $actionsCount
	 * @return int
	 * @access public
	 */
	public function GetTableViewActionsWidth(int $actionsCount): int;
	/**
	 * @return int
	 * @access public
	 */
	public function GetControlsActionWidth(): int;
	/**
	 * @return string
	 * @access public
	 */
	public function GetActionsSeparatorClass(): string;
	/**
	 * @return string
	 * @access public
	 */
	public function GetDateTimePickerControlsType(): string;
	/**
	 * @return string
	 * @access public
	 */
	public function GetDateTimePickerControlsPlugin(): string;
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
	public function GetSubContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
	/**
	 * @param bool $hasActions
	 * @param bool $hasTitle
	 * @return void
	 * @access public
	 */
	public function GetTableViewContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
	/**
	 * @param bool $hasActions
	 * @param bool $hasTitle
	 * @return void
	 * @access public
	 */
	public function GetGenericContainer(bool $hasActions = FALSE,bool $hasTitle = FALSE): void;
}//END interface ITheme
?>