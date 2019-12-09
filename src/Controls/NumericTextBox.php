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
use NApp;
use NETopes\Core\Validators\Validator;

/**
 * NumericTextBox
 *
 * @property bool   auto_select
 * @property bool   js_validation
 * @property int    max_length
 * @property string number_format
 * @property mixed  decimal_separator
 * @property mixed  group_separator
 * @property mixed  decimals_no
 * @property mixed  allow_null
 * @property mixed  sufix
 * @property mixed  value
 * @package  NETopes\Controls
 */
class NumericTextBox extends Control {
    /**
     * NumericTextBox constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->js_validation=TRUE;
        $this->max_length=255;
        $this->auto_select=TRUE;
        $this->align='center';
        parent::__construct($params);
        if(strlen($this->number_format) && is_numeric($this->placeholder)) {
            $this->placeholder=Validator::FormatNumberValue($this->placeholder,$this->number_format);
        }//if(strlen($this->number_format) && is_numeric($this->placeholder))
        //Number format settings (decimals_no|decimal_separator|group_separator|sufix)
        if($this->number_format!==FALSE && !strlen($this->number_format)) {
            $def_decno=(is_numeric($this->decimals_no) && $this->decimals_no>=0) ? $this->decimals_no : 2;
            $def_dsep=($this->decimal_separator || $def_decno!=0) ? NApp::GetParam('decimal_separator') : '';
            $def_gsep=$this->group_separator ? NApp::GetParam('group_separator') : '';
            $this->number_format=$def_decno.'|'.$def_dsep.'|'.$def_gsep.'|'.$this->sufix;
        }//if($this->number_format!==FALSE && !strlen($this->number_format))
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $nclass='';
        if($this->disabled!==TRUE && $this->readonly!==TRUE) {
            if($this->number_format!==FALSE) {
                $nclass='clsSetNumberFormat';
            }
            if(is_string($this->js_validation) && strlen(trim($this->js_validation))) {
                $nclass.=' '.trim($this->js_validation);
            } elseif($this->js_validation===TRUE) {
                $nclass.=' clsSetNumericValidation';
            }
        }//if($this->disabled!==TRUE && $this->readonly!==TRUE)
        if($this->allow_null && !strlen($this->value)) {
            $lvalue='';
        } else {
            if($this->number_format===FALSE) {
                $lvalue=$this->value;
            } else {
                $lvalue=static::FormatValue($this->value,$this->number_format,$this->allow_null);
            }//if($this->number_format===FALSE)
        }//if($this->allow_null && !strlen($this->value))
        $baseact=[];
        if($this->auto_select===TRUE) {
            $baseact['onclick']='this.select();';
        }
        $ldata='';
        if($this->number_format!==FALSE && strlen($this->number_format)) {
            $ldata.=' data-format="'.$this->number_format.'"';
        }
        if($this->allow_null===TRUE) {
            $ldata.=' data-anull="1"';
        }
        $this->ProcessActions();
        $result="\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($nclass).$this->GetTagAttributes().$this->GetTagActions($baseact).$ldata.' value="'.$lvalue.'">'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl

    /**
     * @param             $value
     * @param string|null $format
     * @param bool        $allowNull
     * @return string|null
     */
    public static function FormatValue($value,?string $format,bool $allowNull=FALSE): ?string {
        if(!is_numeric($value) && $allowNull) {
            return NULL;
        }
        $lValue=is_numeric($value) ? $value : 0;
        if(strlen($format)) {
            $formatArray=explode('|',$format);
            $lValue=number_format($lValue,$formatArray[0],$formatArray[1],$formatArray[2]).$formatArray[3];
        }//if(strlen($this->number_format))
        return $lValue;
    }//END public static function FormatValue
}//END class NumericTextBox extends Control