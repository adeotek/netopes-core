<?php
/**
 * DatePicker control class file
 *
 * DatePicker control (jQuery UI/Bootstrap)
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.6.1
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * DatePicker control class
 *
 * DatePicker control (jQuery UI/Bootstrap)
 *
 * @package  NETopes\Controls
 * @access   public
 */
class DatePicker extends Control {
	/**
	 * DatePicker constructor.
	 *
	 * @param null $params
	 */
	public function __construct($params = NULL) {
		$this->button = TRUE;
		$this->width_offset = is_object(NApp::$theme) ? NApp::$thme->GetControlsActionWidth() : 20;
		$this->plugin_type = is_object(NApp::$theme) ? NApp::$thme->GetDateTimePickerControlsType() : '';
		$this->plugin = is_object(NApp::$theme) ? NApp::$thme->GetDateTimePickerControlsPlugin() : '';
		parent::__construct($params);
		if($this->button!==TRUE) { $this->width_offset = 0; }
		if(!strlen($this->locale)) { $this->locale = NApp::_GetLanguageCode(); }
		if(!strlen($this->format)) { $this->format = ($this->timepicker!==TRUE && $this->timepicker!==1) ? 'DD.MM.YYYY' : 'DD.MM.YYYY HH:mm'; }
		if(!is_integer($this->minutesStepping) || $this->minutesStepping<=0) { $this->minutesStepping = 5; }
		if(!is_string($this->align)) { $this->align = 'center'; }
		if(!is_bool($this->today_button)) { $this->today_button = TRUE; }
	}//END public function __construct
	/**
	 * Set control HTML tag
	 *
	 * @return string
	 * @access protected
	 */
	protected function SetControl(): string {
		switch(strtolower($this->plugin_type)) {
			case 'bootstrap3':
				return $this->SetBootstrap3Control();
			case 'jqueryui':
			default:
				return $this->SetJQueryUIControl();
		}//END switch
	}//END protected function SetControl
	/**
	 * Set jQuery UI control HTML tag
	 *
	 * @return string
	 * @access protected
	 */
	protected function SetJQueryUIControl(): string {
		$dpclass = '';
		if($this->disabled!==TRUE && $this->readonly!==TRUE) {
			$dpclass = $this->timepicker ? 'clsJqDateTimePicker' : 'clsJqDatePicker';
		}//if($this->disabled!==TRUE && $this->readonly!==TRUE)
		$ldateformat = strlen($this->dateformat) ? $this->dateformat : 'dd.MM.yyyy';
		$ldata = ' data-format="'.$ldateformat.'"';
		if($this->timepicker) { $ldata .= ' data-timeformat="HH:mm:ss"'; }
		if(strlen($this->jsparams)) {
			$ldata .= ' data-jqdpparams="'.$this->jsparams.'"';
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
	/**
	 * Set Bootstrap 3 control HTML tag
	 *
	 * @return string
	 * @access protected
	 */
	protected function SetBootstrap3Control(): string {
		if(strlen($this->jsparams)) {
			$jsparams = $this->jsparams;
		} else {
			$jsparams = "{ "
				."locale: '{$this->locale}', "
				."format: '{$this->format}', "
				."showTodayButton: ".($this->today_button ? 'true' : 'false').", "
				."stepping: {$this->minutesStepping}"
				." }";
		}//if(strlen($this->jsparams))
		// NApp::_Dlog($jsparams);

		$this->ProcessActions();
		if($this->button) {
			$result = "\t\t".'<div class="input-group date" id="'.$this->tagid.'_control">'."\n";
	        $result .= "\t\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
	        $result .= "\t\t\t".'<span class="input-group-addon">'."\n";
			$result .= "\t\t\t\t".'<span class="glyphicon glyphicon-calendar"></span>'."\n";
			$result .= "\t\t\t".'</span>'."\n";
	        $result .= "\t\t".'</div>'."\n";
	    } else {
	        $result = "\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
	    }//if($this->button)
		$result .= $this->GetActions();
		if($this->disabled!==TRUE && $this->readonly!==TRUE) { NApp::_ExecJs("$('#{$this->tagid}_control').{$this->plugin}({$jsparams});"); }
		return $result;
	}//END protected function SetBootstrap3Control
}//END class DatePicker extends Control
?>