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
    namespace NETopes\Core\Controls;
	/**
	 * ClassName description
	 *
	 * long_description
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class DatePicker extends Control {
		public function __construct($params = null){
			$this->button = TRUE;
			$this->width_offset = 20;
			parent::__construct($params);
			if($this->button!==TRUE) { $this->width_offset = 0; }
		}//END public function __construct

		protected function SetControl() {
			$dpclass = '';
			if($this->disabled!==TRUE && $this->readonly!==TRUE) {
				$dpclass = $this->timepicker ? 'clsJqDateTimePicker' : 'clsJqDatePicker';
			}//if($this->disabled!==TRUE && $this->readonly!==TRUE)
			$ldateformat = strlen($this->dateformat) ? $this->dateformat : 'dd.MM.yyyy';
			$ldata = ' data-format="'.$ldateformat.'"';
			if($this->timepicker) { $ldata .= ' data-timeformat="HH:mm:ss"'; }
			if(strlen($this->jqdpparams)) {
				$ldata .= ' data-jqdpparams="'.$this->jqdpparams.'"';
			} else {
				$ldateformat = str_replace('yyyy','yy',str_replace('M','m',$ldateformat));
				//Valorile default pentru parametri jQuery DatePicker
				$ljqdpparams = "dateFormat: '{$ldateformat}',"
					."constrainInput: true,"
					."showOtherMonths: true,"
					."selectOtherMonths: true,"
					."changeMonth: true,"
					."changeYear: true,"
					."showWeek: true,"
					."numberOfMonths: 1,"
					.($this->allow_text ? "constrainInput: false," : '')
					."showButtonPanel: true"
					.($this->timepicker ? ","
						."controlType: 'select',"
						."oneLine: true,"
						//."showSecond: false,"
						//."pickerTimeFormat: 'HH:mm',"
						."timeFormat: 'HH:mm:ss'"
					: '');
				$ldata .= ' data-jqdpparams="'.$ljqdpparams.'"';
			}//if(strlen($this->jqdpparams))
			$this->ProcessActions();
			if($this->button) {
				$result = "\t\t".'<div class="control-set">'."\n";
				$result .= "\t\t\t".'<span class="input-group-addon" onclick="$(\'#'.$this->tagid.'\').focus();"><i class="fa fa-calendar"></i></span>'."\n";
				$result .= "\t\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'">'."\n";
				$result .= "\t\t".'</div>'."\n";
			} else {
				$result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'">'."\n";
			}//if($this->button)
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class DatePicker extends Control
?>