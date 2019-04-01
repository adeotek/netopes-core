<?php
/**
 * NumericTextBox control classes file
 * File containing NumericTextBox control class
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\Validators\Validator;
use NApp;
/**
 * NumericTextBox
 * @package  NETopes\Controls
 */
class NumericTextBox extends Control {
    public function __construct($params = NULL) {
        $this->js_validation = TRUE;
        $this->max_length = 255;
        $this->auto_select = TRUE;
        parent::__construct($params);
        if(strlen($this->number_format) && is_numeric($this->placeholder)) {
            $this->placeholder = Validator::FormatNumberValue($this->placeholder,$this->number_format);
        }//if(strlen($this->number_format) && is_numeric($this->placeholder))
        //Number format settings (decimals_no|decimal_separator|group_separator|sufix)
        if($this->number_format!==FALSE && !strlen($this->number_format)) {
            $def_decno = (is_numeric($this->decimals_no) && $this->decimals_no>=0) ? $this->decimals_no : 2;
            $def_dsep = ($this->decimal_separator || $def_decno!=0) ? NApp::GetParam('decimal_separator') : '';
            $def_gsep = $this->group_separator ? NApp::GetParam('group_separator') : '';
            $this->number_format = $def_decno.'|'.$def_dsep.'|'.$def_gsep.'|'.$this->sufix;
        }//if($this->number_format!==FALSE && !strlen($this->number_format))
    }//END public function __construct
    protected function SetControl(): ?string {
        $nclass = '';
        if($this->disabled!==TRUE && $this->readonly!==TRUE) {
            if($this->number_format!==FALSE) { $nclass = 'clsSetNumberFormat'; }
            if(is_string($this->js_validation) && strlen(trim($this->js_validation))) { $nclass .= ' '.trim($this->js_validation); }
            elseif($this->js_validation===TRUE) { $nclass .= ' clsSetNumericValidation'; }
        }//if($this->disabled!==TRUE && $this->readonly!==TRUE)
        if($this->allow_null && !strlen($this->value)) {
            $lvalue = '';
        } else {
           if($this->number_format===FALSE) {
               $lvalue = $this->value;
           } else {
               $lvalue = is_numeric($this->value) ? $this->value : 0;
               if(strlen($this->number_format)) {
                   $format_arr = explode('|',$this->number_format);
                   $lvalue = number_format($lvalue,$format_arr[0],$format_arr[1],$format_arr[2]).$format_arr[3];
               }//if(strlen($this->number_format))
           }//if($this->number_format===FALSE)
        }//if($this->allow_null && !strlen($this->value))
        $baseact = [];
        if($this->auto_select===TRUE) { $baseact['onclick'] = 'this.select();'; }
        $ldata = '';
        if($this->number_format!==FALSE && strlen($this->number_format)) { $ldata .= ' data-format="'.$this->number_format.'"'; }
        if($this->allow_null===TRUE) { $ldata .= ' data-anull="1"'; }
        $this->ProcessActions();
        $result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($nclass).$this->GetTagAttributes().$this->GetTagActions($baseact).$ldata.' value="'.$lvalue.'">'."\n";
        $result .= $this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class NumericTextBox extends Control
?>