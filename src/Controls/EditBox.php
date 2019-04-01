<?php
/**
 * EditBox class file
 * File containing EditBox control class
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
 * Class EditBox
 *
 * @package  NETopes\Controls
 */
class EditBox extends Control {
    public function __construct($params=NULL) {
        $this->uc_first='none'; // posible values: none, first, all
        $this->max_length=255;
        $this->auto_select=TRUE;
        $this->textarea_cols=NULL;
        $this->textarea_rows=NULL;
        $this->height=NULL;
        parent::__construct($params);
    }//END public function __construct

    protected function SetControl(): ?string {
        switch(strtolower($this->uc_first)) {
            case 'first':
                $fclass=' clsSetUcFirst';
                break;
            case 'all':
                $fclass=' clsSetUcFirstAll';
                break;
            default:
                $fclass='';
                break;
        }//switch (strtolower($this->uc_first))
        $lcols=$this->textarea_cols ? ' cols='.$this->textarea_cols : '';
        $lrows=$this->textarea_rows ? ' rows='.($this->textarea_rows - 1) : '';
        $this->ProcessActions();
        $result="\t\t".'<textarea'.$this->GetTagId(TRUE).$this->GetTagClass($fclass).$this->GetTagAttributes().$this->GetTagActions().$lcols.$lrows.'>'.$this->value.'</textarea>'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class EditBox extends Control