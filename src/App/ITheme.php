<?php
/**
 * Application Theme interface file
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
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
     * @param null|string $extra
     * @return string
     */
    public function GetBtnDefaultClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnPrimaryClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnInfoClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnSuccessClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnWarningClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnDangerClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnSpecialLightClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnSpecialDarkClass(?string $extra=NULL): string;

    /**
     * @param null|string $extra
     * @return string
     */
    public function GetBtnSpecialWarningClass(?string $extra=NULL): string;

    /**
     * @param int $actionsCount
     * @return int
     */
    public function GetTableViewActionsWidth(int $actionsCount): int;

    /**
     * @return int
     */
    public function GetControlsActionWidth(): int;

    /**
     * @return string
     */
    public function GetActionsSeparatorClass(): string;

    /**
     * @return string
     */
    public function GetDateTimePickerControlsType(): string;

    /**
     * @return string
     */
    public function GetDateTimePickerControlsPlugin(): string;

    /**
     * @param Params $tags
     * @param bool   $hasActions
     * @param bool   $hasTitle
     * @return void
     */
    public function GetDefaultContainer(Params $tags,bool $hasActions=FALSE,bool $hasTitle=FALSE): void;
}//END interface ITheme