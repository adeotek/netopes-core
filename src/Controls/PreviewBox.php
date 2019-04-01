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
 * PreviewBox control
 * Control for previewing HTML content
 * @package  NETopes\Controls
 */
class PreviewBox extends Control {
    public function __construct($params = NULL){
        $this->postable = FALSE;
        $this->label_position = 'left';
        parent::__construct($params);
    }//END public function __construct
    protected function SetControl(): ?string {
        $tagClass = $this->auto_height ? 'can-grow-v' : NULL;
        $result = "\t\t".'<div'.$this->GetTagId(2).$this->GetTagClass($tagClass).$this->GetTagAttributes().$this->GetTagActions().'>'.$this->value.'</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class PreviewBox extends Control