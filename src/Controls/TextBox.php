<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * ClassName description
 * long_description
 * @package  NETopes\Controls
 */
class TextBox extends Control {
    public function __construct($params = null){
        $this->uc_first = NULL; // values: NULL, first, all
        $this->max_length = 255;
        $this->auto_select = TRUE;
        parent::__construct($params);
    }//END public function __construct
    protected function SetControl(): ?string {
        switch (strtolower($this->uc_first)) {
            case 'first':
                $fclass = ' clsSetUcFirst';
                break;
            case 'all':
                $fclass = ' clsSetUcFirstAll';
                break;
            default:
                $fclass = '';
                break;
        }//switch (strtolower($this->uc_first))
        $baseact = [];
        if($this->auto_select===TRUE) { $baseact['onclick'] = 'this.select();'; }
        $lmaxlength = (is_numeric($this->max_length) && $this->max_length>0) ? ' maxlength="'.$this->max_length.'"' : '';
        $ltype = $this->password ? 'password' : 'text';
        $this->ProcessActions();
        if(is_string($this->icon) && strlen($this->icon)) {
            $result = "\t\t".'<div class="control-set">'."\n";
            $result .= "\t\t\t".'<span class="input-group-addon" onclick="$(\'#'.$this->tag_id.'\').focus();">'.$this->icon.'</span>'."\n";
            $result .= "\t\t\t".'<input type="'.$ltype.'"'.$this->GetTagId(TRUE).$this->GetTagClass($fclass).$this->GetTagAttributes().$this->GetTagActions($baseact).$lmaxlength.' value="'.$this->value.'">'."\n";
            $result .= "\t\t".'</div>'."\n";
        } else {
            $result = "\t\t".'<input type="'.$ltype.'"'.$this->GetTagId(TRUE).$this->GetTagClass($fclass).$this->GetTagAttributes().$this->GetTagActions($baseact).$lmaxlength.' value="'.$this->value.'">'."\n";
        }//if($this->button)
        $result .= $this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class TextBox extends Control