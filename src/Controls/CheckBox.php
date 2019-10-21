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
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;

/**
 * ClassName description
 * long_description
 *
 * @property bool|null   invert_value
 * @property mixed       value
 * @property string|null checked_color
 * @property string|null unchecked_color
 * @package  NETopes\Controls
 */
class CheckBox extends Control {
    /**
     * Render types constants
     */
    const TYPE_STANDARD='checkbox';
    const TYPE_ROUND='round_checkbox';
    const TYPE_SWITCH='switch';
    const TYPE_SMALL_SWITCH='small_switch';

    /**
     * @var array Available checkboxes colors
     */
    public $colors=['grey'=>'cb-grey','blue'=>'cb-blue','red'=>'cb-red','green'=>'cb-green','green-c'=>'cb-ggreen'];

    /**
     * @var string Render type ('checkbox','round_checkbox','switch','small_switch')
     */
    public $type='checkbox';

    /**
     * CheckBox constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
        if(!in_array($this->type,[static::TYPE_STANDARD,static::TYPE_ROUND,static::TYPE_SWITCH,static::TYPE_SMALL_SWITCH])) {
            $this->type=static::TYPE_STANDARD;
        }
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        if(is_array($this->value)) {
            $currentValue=$this->value;
            switch(get_array_value($currentValue,'type','','is_string')) {
                case 'eval':
                    $arg=get_array_value($currentValue,'arg','','is_string');
                    if(strlen($arg)) {
                        try {
                            $currentValue=eval($arg);
                        } catch(AppException $ee) {
                            $currentValue=0;
                            NApp::Elog($ee);
                        }//END try
                    } else {
                        $currentValue=0;
                    }//if(strlen($arg))
                    break;
                default:
                    $currentValue=get_array_value($currentValue,'arg',0,'isset');
                    break;
            }//END switch
        } else {
            $currentValue=$this->value===TRUE || $this->value===1 || $this->value==='1';
        }//if(is_array($this->value))
        if($this->invert_value) {
            $currentValue=$currentValue ? 0 : 1;
        } else {
            $currentValue=$currentValue ? 1 : 0;
        }//if($this->invert_value)
        $result="\t\t".'<input type="image"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes(FALSE).' value="'.$currentValue.'">'."\n";
        $onClick=addcslashes($this->GetOnClickAction(NULL,TRUE),'\\');
        $onChange=addcslashes($this->GetOnChangeAction(NULL,TRUE),'\\');
        NApp::AddJsScript("$('#{$this->tag_id}').NetopesCheckBox({
            type: '{$this->type}',
            baseUrl: '".NApp::$appBaseUrl.AppConfig::GetValue('app_js_path')."/controls/',
            checkedClass: '".get_array_value($this->colors,$this->checked_color,'cb-blue','is_notempty_string')."-ck',
            uncheckedClass: '".get_array_value($this->colors,$this->unchecked_color,'cb-grey','is_notempty_string')."-uk',
            onChange: ".(strlen($onChange) ? 'function(obj,e) { '.$onChange.' }' : 'false').",
            onClick: ".(strlen($onClick) ? 'function(obj,e) { '.$onClick.' }' : 'false')."
         });");
        return $result;
    }//END protected function SetControl
}//END class CheckBox extends Control