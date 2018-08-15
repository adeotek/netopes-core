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
use NApp;

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
		$this->button = TRUE;
		$this->width_offset = 20;
		parent::__construct($params);
		if($this->button!==TRUE) { $this->width_offset = 0; }
		if(!strlen($this->locale)) { $this->locale = NApp::_GetLanguageCode(); }
		if(!strlen($this->format)) { $this->format = ($this->dateonly!==FALSE || $this->dateonly!==0) ? 'DD.MM.YYYY' : 'DD.MM.YYYY HH:mm'; }
		if(!is_integer($this->minutesStepping) || $this->minutesStepping<=0) { $this->minutesStepping = 5; }
		if(!is_string($this->align)) { $this->align = 'center'; }
	}//END public function __construct

	protected function SetControl() {
		$dpclass = '';
		if($this->disabled!==TRUE && $this->readonly!==TRUE) { $dpclass = 'clsBsDateTimePicker'; }

		if(strlen($this->jsparams)) {
			$jsparams = $this->jsparams;
		} else {
			$jsparams = "{ "
				."locale: '{$this->locale}', "
				."format: '{$this->format}', "
				."showTodayButton: true, "
				."stepping: {$this->minutesStepping}"
				." }";
		}//if(strlen($this->jsparams))
		NApp::_Dlog($jsparams);

		$this->ProcessActions();
		if($this->button) {
			$result = "\t\t".'<div class="input-group date" id="'.$this->tagid.'_control">'."\n";
	        $result .= "\t\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
	        $result .= "\t\t\t".'<span class="input-group-addon">'."\n";
			$result .= "\t\t\t\t".'<span class="glyphicon glyphicon-calendar"></span>'."\n";
			$result .= "\t\t\t".'</span>'."\n";
	        $result .= "\t\t".'</div>'."\n";
	    } else {
	        $result = "\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
	    }//if($this->button)
		$result .= $this->GetActions();
		if($this->disabled!==TRUE && $this->readonly!==TRUE) { NApp::_ExecJs("$('#{$this->tagid}_control').datetimepicker({$jsparams});"); }
		return $result;
	}//END protected function SetControl
}//END class BootstrapDateTimePicker extends Control
?>