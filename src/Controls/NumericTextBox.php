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
	/**
	 * ClassName description
	 *
	 * long_description
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
				$this->placeholder = \NETopes\Core\App\Validator::FormatNumberValue($this->placeholder,$this->numberformat);
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
				$nclass = 'clsSetNumberFormat';
				if($this->jsvalidation===TRUE) { $nclass .= ' clsSetNumericValidation'; }
			}//if($this->disabled!==TRUE && $this->readonly!==TRUE)
			if($this->allownull && (is_null($this->value) || !is_numeric($this->value))) {
				$lvalue = '';
			} else {
				$lvalue = is_numeric($this->value) ? $this->value : 0;
				if($this->numberformat!==FALSE && strlen($this->numberformat)) {
					$format_arr = explode('|',$this->numberformat);
					$lvalue = number_format($lvalue,$format_arr[0],$format_arr[1],$format_arr[2]).$format_arr[3];
				}//if($this->numberformat!==FALSE && strlen($this->numberformat))
			}//if($this->allownull && (is_null($this->value) || !is_numeric($this->value)))
			$baseact = array();
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