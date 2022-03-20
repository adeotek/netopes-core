<?php
/**
 * Application Theme interface file
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\App;
/**
 * ITheme interface
 */
interface ITheme {
    /**
     * Get application theme type
     * Values:
     * - native/NULL -> custom HTML+CSS
     * - jqueryui -> jQuery UI
     * - bootstrap3 -> Tweeter Bootstrap 3
     * - bootstrap4 -> Tweeter Bootstrap 4
     * - bootstrap5 -> Tweeter Bootstrap 5
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
     * @return string
     */
    public function GetTabsControlPlugin(): string;

    /**
     * @param Params $tags
     * @param bool   $hasActions
     * @param bool   $hasTitle
     * @return void
     */
    public function GetDefaultContainer(Params $tags,bool $hasActions=FALSE,bool $hasTitle=FALSE): void;
}//END interface ITheme