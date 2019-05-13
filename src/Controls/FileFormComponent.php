<?php
/**
 * Files upload and preview form component class file
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.2.0
 * @filesource
 */
namespace NETopes\Core\Controls;

use NETopes\Core\AppException;

/**
 * Class FileFormComponent
 *
 * Files upload and preview form component
 *
 * @property string|null filter
 * @package NETopes\Core\Controls
 */
class FileFormComponent extends Control {
    /**
     * FileFormComponent class constructor
     *
     * @param array $params An array of params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
        $this->buffered=FALSE;
    }//END public function __construct

    /**
     * Process control to generate HTML
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        switch($this->theme_type) {
            case 'bootstrap3':
                return $this->GetBootstrap3Control();
            case 'bootstrap4':
                return $this->GetBootstrap4Control();
            case 'standard':
            case 'native':
            case 'table':
            default:
                return $this->GetTableControl();
        }//END switch
    }//END protected function SetControl

    /**
     * description
     *
     * @return string|null
     */
    protected function GetBootstrap3Control(): ?string {
        $lClass=strlen($this->class) ? ' '.$this->class : '';
        $result='<div'.($this->tag_id ? ' id="'.$this->tag_id.'-container"' : '').' class="clsFormContainer '.$lclass.'">'."\n";
        // TODO: Implement GetBootstrap3Control() method.
    }//END protected function GetBootstrap3Control

    /**
     * description
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetTableControl(): ?string {
        throw new AppException('Not implemented yet!');
        // TODO: Implement GetTableControl() method.
    }//END protected function GetTableControl

    /**
     * description
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetBootstrap4Control(): ?string {
        throw new AppException('Not implemented yet!');
        // TODO: Implement GetBootstrap4Control() method.
    }//END protected function GetBootstrap3Control
}//END class FileFormComponent extends Control