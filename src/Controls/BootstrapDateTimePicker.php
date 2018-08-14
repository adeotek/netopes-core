<?php
/**
 * BootstrapDateTimePicker control classes file
 *
 * Bootstrap DatePicker/DateTimePicker
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.5.8
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * BootstrapDateTimePicker control classes
 *
 * Bootstrap DatePicker/DateTimePicker
 *
 * @package  NETopes\Controls
 * @access   public
 */
class BootstrapDateTimePicker extends Control {
	public function __construct($params = null) {
		$this->version = '2';
		$this->button = TRUE;
		$this->width_offset = 20;
		parent::__construct($params);
		if($this->button!==TRUE) { $this->width_offset = 0; }
		if(!strlen($this->version)) { $this->version = '2'; }
		if(!strlen($this->version)) { $this->version = '2'; }
	}//END public function __construct

	protected function SetControl() {
		$dpclass = '';
		if($this->disabled!==TRUE && $this->readonly!==TRUE) { $dpclass = 'clsBsDateTimePicker'; }

		$ldata = ' data-timeformat="HH:mm:ss"';
		if(strlen($this->bsdpparams)) {
			$ldata .= ' data-bsdpparams="'.$this->bsdpparams.'"';
		} else {
			$bsdpparams = "constrainInput: true,"
				."showButtonPanel: true"
				."controlType: 'select',"
				."oneLine: true,"
				//."showSecond: false,"
				//."pickerTimeFormat: 'HH:mm',"
				."timeFormat: 'HH:mm:ss'";
			$ldata .= ' data-bsdpparams="'.$bsdpparams.'"';
		}//if(strlen($this->jqdpparams))

		$this->ProcessActions();
		if($this->button) {
			$result = "\t\t".'<div class="input-group date" id="datetimepicker'.$this->version.'">'."\n";
	        $result .= "\t\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'">'."\n";
	        $result .= "\t\t\t".'<span class="input-group-addon">'."\n";
			$result .= "\t\t\t\t".'<span class="glyphicon glyphicon-calendar"></span>'."\n";
			$result .= "\t\t\t".'</span>'."\n";
	        $result .= "\t\t".'</div>'."\n";
	    } else {
	        $result = "\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'">'."\n";
	    }//if($this->button)
		$result .= $this->GetActions();
		return $result;
	}//END protected function SetControl
}//END class BootstrapDateTimePicker extends Control
?>