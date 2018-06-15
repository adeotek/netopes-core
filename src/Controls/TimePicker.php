<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
    namespace NETopes\Core\Classes\Controls;
	/**
	 * TimePicker description
	 *
	 * long_description
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class TimePicker extends Control {
		public function __construct($params = null){
			$this->button = TRUE;
			$this->width_offset = 20;
			parent::__construct($params);
			if($this->button!==TRUE) { $this->width_offset = 0; }
		}//END public function __construct

		protected function SetControl() {
			$dpclass = '';
			if($this->disabled!==TRUE && $this->readonly!==TRUE) {
				$dpclass = 'clsJqTimePicker';
			}//if($this->disabled!==TRUE && $this->readonly!==TRUE)
			$ldata = ' data-timeformat="HH:mm:ss"';
			if(strlen($this->jqdpparams)) {
				$ldata .= ' data-jqdpparams="'.$this->jqdpparams.'"';
			} else {
				//Valorile default pentru parametri jQuery DatePicker
				$ljqdpparams = "constrainInput: true,"
					."showButtonPanel: true"
					."controlType: 'select',"
					."oneLine: true,"
					//."showSecond: false,"
					//."pickerTimeFormat: 'HH:mm',"
					."timeFormat: 'HH:mm:ss'";
				$ldata .= ' data-jqdpparams="'.$ljqdpparams.'"';
			}//if(strlen($this->jqdpparams))
			$this->ProcessActions();
			$result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'">'."\n";
			if($this->button) {
				$result .= "\t\t".'<div id="'.$this->tagid.'_btn" class="'.$this->baseclass.' dp_button" onclick="$(\'#'.$this->tagid.'\').focus();"><i class="fa fa-clock"></i></div>'."\n";
			}//if($this->button)
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class TimePicker extends Control
?>