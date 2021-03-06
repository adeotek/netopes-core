<?php
/**
 * ColorPicker control classes file
 * Bootstrap ColorPicker control
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

/**
 * ColorPicker control classes
 * Bootstrap ColorPicker control
 *
 * @package  NETopes\Controls
 */
class ColorPicker extends Control {
    public function __construct($params=NULL) {
        $this->preview=TRUE;
        $this->width_offset=20;
        $this->format='hex';
        $this->use_alpha=FALSE;
        parent::__construct($params);
        if($this->preview!==TRUE) {
            $this->width_offset=0;
        }
        if(!strlen($this->format)) {
            $this->format='hex';
        }
    }//END public function __construct

    /**
     * Set control HTML tag
     *
     * @return string
     */
    protected function SetControl(): ?string {
        if(strlen($this->js_params)) {
            $jsparams=$this->js_params;
        } else {
            $jsparams="{ "
                ."format: '{$this->format}', " //format:'rgb'|'hex'|'hsl'|'auto'|null
                ."useAlpha: ".($this->use_alpha ? 'true' : 'false')
                .(strlen($this->value) ? ", color: '{$this->value}'" : '')
                ." }";
            //customClass : string
            //useHashPrefix : bool
        }//if(strlen($this->js_params))
        // NApp::Dlog($jsparams);
        $this->ProcessActions();
        if($this->preview) {
            $result="\t\t".'<div class="input-group" id="'.$this->tag_id.'_control">'."\n";
            $result.="\t\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
            // $result .= "\t\t\t".'<span class="input-group-append">'."\n";
            $result.="\t\t\t".'<span class="input-group-addon">'."\n";
            $result.="\t\t\t\t".'<span class="input-group-text colorpicker-input-addon"><i></i></span>'."\n";
            $result.="\t\t\t".'</span>'."\n";
            $result.="\t\t".'</div>'."\n";
        } else {
            $result="\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
        }//if($this->preview)
        $result.=$this->GetActions();
        if($this->disabled!==TRUE && $this->readonly!==TRUE) {
            NApp::AddJsScript("$('#{$this->tag_id}_control').colorpicker({$jsparams});");
        }
        return $result;
    }//END protected function SetControl
}//END class ColorPicker extends Control
?>