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
/**
 * JqCheckBox description
 * long_description
 * @package  NETopes\Controls
 */
class JqCheckBox extends Control {
	public function __construct($params = NULL){
		parent::__construct($params);
		$this->data_onchange = TRUE;
	}//END public function __construct
	protected function SetControl(): ?string {
		if($this->invert_value) {
			$lvalue = ($this->value===TRUE || $this->value==1) ? 0 : 1;
		} else {
			$lvalue = ($this->value===TRUE || $this->value==1) ? 1 : 0;
		}//if($this->invert_value)
		$ljqparams = strlen($this->jq_params) ? $this->jq_params : '';
		$lstyle = strlen($this->style)>0 ? ' style="'.$this->style.'"' : '';
		$result = "\t\t".'<input type="image"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes(FALSE).$this->GetTagActions().$lstyle.' value="'.$lvalue.'">'."\n";
		NApp::AddJsScript("$('#{$this->tag_id}').jqCheckBox({$ljqparams});");
		return $result;
	}//END protected function SetControl
}//END class JqCheckBox extends Control