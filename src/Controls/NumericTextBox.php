<?php
/**
 * NumericTextBox control classes file
 *
 * File containing NumericTextBox control class
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.8.11
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\Validator;
use NApp;

/**
 * NumericTextBox
 *
 * @package  NETopes\Controls
 * @access   public
 */
class NumericTextBox extends Control {
    public function __construct($params = NULL) {
        $this->jsvalidation = TRUE;
        $this->maxlength = 255;
        $this->autoselect = TRUE;
        parent::__construct($params);
        if(strlen($this->numberformat) && is_numeric($this->placeholder)) {
            $this->placeholder = Validator::FormatNumberValue($this->placeholder,$this->numberformat);
        }//if(strlen($this->numberformat) && is_numeric($this->placeholder))
        //Number format settings (decimals_no|decimal_separator|group_separator|sufix)
        if($this->numberformat!==FALSE && !strlen($this->numberformat)) {
            $def_decno = (is_numeric($this->decimals_no) && $this->decimals_no>=0) ? $this->decimals_no : 2;
            $def_dsep = ($this->decimal_separator || $def_decno!=0) ? NApp::_GetParam('decimal_separator') : '';
            $def_gsep = $this->group_separator ? NApp::_GetParam('group_separator') : '';
            $this->numberformat = $def_decno.'|'.$def_dsep.'|'.$def_gsep.'|'.$this->sufix;
        }//if($this->numberformat!==FALSE && !strlen($this->numberformat))
    }//END public function __construct

    protected function SetControl() {
        $nclass = '';
        if($this->disabled!==TRUE && $this->readonly!==TRUE) {
            if($this->numberformat!==FALSE) { $nclass = 'clsSetNumberFormat'; }
            if(is_string($this->jsvalidation) && strlen(trim($this->jsvalidation))) { $nclass .= ' '.trim($this->jsvalidation); }
            elseif($this->jsvalidation===TRUE) { $nclass .= ' clsSetNumericValidation'; }
        }//if($this->disabled!==TRUE && $this->readonly!==TRUE)
        if($this->allownull && !strlen($this->value)) {
            $lvalue = '';
        } else {
           if($this->numberformat===FALSE) {
               $lvalue = $this->value;
           } else {
               $lvalue = is_numeric($this->value) ? $this->value : 0;
               if(strlen($this->numberformat)) {
                   $format_arr = explode('|',$this->numberformat);
                   $lvalue = number_format($lvalue,$format_arr[0],$format_arr[1],$format_arr[2]).$format_arr[3];
               }//if(strlen($this->numberformat))
           }//if($this->numberformat===FALSE)
        }//if($this->allownull && !strlen($this->value))
        $baseact = [];
        if($this->autoselect===TRUE) { $baseact['onclick'] = 'this.select();'; }
        $ldata = '';
        if($this->numberformat!==FALSE && strlen($this->numberformat)) { $ldata .= ' data-format="'.$this->numberformat.'"'; }
        if($this->allownull===TRUE) { $ldata .= ' data-anull="1"'; }
        $this->ProcessActions();
        $result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($nclass).$this->GetTagAttributes().$this->GetTagActions($baseact).$ldata.' value="'.$lvalue.'">'."\n";
        $result .= $this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class NumericTextBox extends Control
?>