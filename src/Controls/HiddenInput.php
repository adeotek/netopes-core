<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * Hidden input control
 * Creates a hidden input with an initial value
 *
 * @package  NETopes\Controls
 */
class HiddenInput extends Control {
    public function __construct($params=NULL) {
        $this->container=FALSE;
        $this->no_label=TRUE;
        parent::__construct($params);
    }//END public function __construct

    protected function SetControl(): ?string {
        $result='<input type="hidden"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
        return $result;
    }//END protected function SetControl
}//END class HiddenInput extends Control
?>