<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppConfig;

/**
 * ClassName description
 *
 * long_description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class CheckBox extends Control {
    public function __construct($params = null){
        $this->colors = array('pred'=>'clsCheckBoxPRed','round'=>'clsCheckBoxRound');
        parent::__construct($params);
    }//END public function __construct
    protected function SetControl(): ?string {
        $this->base_class = (strlen($this->color) && array_key_exists($this->color,$this->colors)) ? $this->colors[$this->color] : $this->base_class;
        if(is_array($this->value)) {
            $lvalue = $this->value;
            switch(get_array_value($lvalue,'type','','is_string')) {
                case 'eval':
                    $arg = get_array_value($lvalue,'arg','','is_string');
                    if(strlen($arg)) {
                        try {
                            $lvalue = eval($arg);
                        } catch(\NETopes\Core\AppException $ee) {
                            $lvalue = 0;
                            NApp::Elog($ee->getMessage(),'CheckBox');
                        }//END try
                    } else {
                        $lvalue = 0;
                    }//if(strlen($arg))
                    break;
                default:
                    $lvalue = get_array_value($lvalue,'arg',0,'isset');
                    break;
            }//END switch
        } else {
            $lvalue = $this->value===TRUE || $this->value===1 || $this->value==='1';
        }//if(is_array($this->value))
        if($this->invert_value) {
            $lvalue = $lvalue ? 0 : 1;
        } else {
            $lvalue = $lvalue ? 1 : 0;
        }//if($this->invert_value)
        $baseact = array(
            'onclick'=>'CheckBoxClickBaseEvent(this);',
            'onkeypress'=>'if(event.keyCode==13){CheckBoxClickBaseEvent(this);}',
        );
        $lstyle = strlen($this->style)>0 ? ' style="'.$this->style.'"' : '';
        $result = "\t\t".'<input type="image"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes(FALSE).$this->GetTagActions($baseact).' src="'.NApp::app_web_link().AppConfig::GetValue('app_js_path').'/controls/images/transparent.gif" value="'.$lvalue.'">'."\n";
        return $result;
    }//END protected function SetControl
}//END class CheckBox extends Control