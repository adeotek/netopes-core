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
 * ClassName description
 * long_description
 *
 * @package  NETopes\Controls
 */
class QSearchTextBox extends Control {
    public function __construct($params=NULL) {
        $this->max_length=255;
        $this->auto_select=TRUE;
        parent::__construct($params);
    }//END public function __construct

    protected function SetControl(): ?string {
        $baseact=[];
        if($this->auto_select===TRUE) {
            $baseact['onclick']='this.select();';
        }
        $lmaxlength=(is_numeric($this->max_length) && $this->max_length>0) ? ' maxlength="'.$this->max_length.'"' : '';
        $this->ProcessActions();
        $result="\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions($baseact).$lmaxlength.' value="'.$this->value.'">'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class QSearchTextBox extends Control
?>