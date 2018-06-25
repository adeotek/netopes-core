<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
    use NApp;
	/**
	 * JqCheckBox description
	 *
	 * long_description
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class JqCheckBox extends Control {

		public function __construct($params = NULL){
			parent::__construct($params);
			$this->data_onchange = TRUE;
		}//END public function __construct

		protected function SetControl() {
			if($this->invertvalue) {
				$lvalue = ($this->value===TRUE || $this->value==1) ? 0 : 1;
			} else {
				$lvalue = ($this->value===TRUE || $this->value==1) ? 1 : 0;
			}//if($this->invertvalue)
			$ljqparams = strlen($this->jqparams) ? $this->jqparams : '';
			$lstyle = strlen($this->style)>0 ? ' style="'.$this->style.'"' : '';
			$result = "\t\t".'<input type="image"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes(FALSE).$this->GetTagActions().$lstyle.' value="'.$lvalue.'">'."\n";
			if(NApp::ajax() && is_object(NApp::arequest())) {
				NApp::arequest()->ExecuteJs("$('#{$this->tagid}').jqCheckBox({$ljqparams});");
			} else {
				$result .= "\t\t"."<script type=\"text/javascript\">$('#{$this->tagid}').jqCheckBox({$ljqparams});</script>"."\n";
			}//if(NApp::ajax() && is_object(NApp::arequest()))
			return $result;
		}//END protected function SetControl
	}//END class JqCheckBox extends Control
?>