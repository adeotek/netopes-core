<?php
/**
 * TimePicker control class file
 *
 * TimePicker control (jQuery UI/Bootstrap)
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.3.0.1
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;

/**
 * TimePicker control class
 *
 * TimePicker control (jQuery UI/Bootstrap)
 *
 * @package  NETopes\Controls
 * @access   public
 */
class TimePicker extends Control {
	/**
	 * TimePicker constructor.
	 *
	 * @param null $params
	 */
	public function __construct($params = NULL) {
		$this->button = TRUE;
		$this->width_offset = is_object(NApp::$theme) ? NApp::$theme->GetControlsActionWidth() : 20;
		$this->plugin_type = is_object(NApp::$theme) ? NApp::$theme->GetDateTimePickerControlsType() : '';
		$this->plugin = is_object(NApp::$theme) ? NApp::$theme->GetDateTimePickerControlsPlugin() : '';
		parent::__construct($params);
		if($this->button!==TRUE) { $this->width_offset = 0; }
		if(!strlen($this->locale)) { $this->locale = NApp::_GetLanguageCode(); }
		if(!strlen($this->format)) { $this->format = 'HH:mm'; }
		if(!is_integer($this->minutesStepping) || $this->minutesStepping<=0) { $this->minutesStepping = 5; }
		if(!is_string($this->align)) { $this->align = 'center'; }
		if(!is_bool($this->now_button)) { $this->now_button = TRUE; }
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
		if($this->disabled!==TRUE && $this->readonly!==TRUE) { $dpclass = 'clsJqTimePicker'; }
		$ldata = ' data-timeformat="HH:mm:ss"';
		if(strlen($this->jsparams)) {
			$ldata .= ' data-jqdpparams="'.$this->jsparams.'"';
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
		if($this->button) {
		    $groupAddonClass = strlen($this->size) ? ' input-'.$this->size : '';
			$result = "\t\t".'<div class="control-set">'."\n";
			$result .= "\t\t\t".'<span class="input-group-addon'.$groupAddonClass.'" onclick="$(\'#'.$this->tagid.'\').focus();"><i class="fa fa-clock"></i></span>'."\n";
			$result .= "\t\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'"autocomplete="off">'."\n";
			$result .= "\t\t".'</div>'."\n";
		} else {
			$result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'"autocomplete="off">'."\n";
		}//if($this->button)
		$result .= $this->GetActions();
		return $result;
	}//END protected function SetJQueryUIControl
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
				."showTodayButton: ".($this->now_button ? 'true' : 'false').", "
				."stepping: {$this->minutesStepping}"
				." }";
		}//if(strlen($this->jsparams))
		// NApp::_Dlog($jsparams);

		$this->ProcessActions();
		$onChange = '';
		if($this->button) {
		    if(!$this->readonly && !$this->disabled) {
		        $onChange = $this->GetOnChangeAction(NULL,TRUE);
		        $this->onchange = NULL;
		        $this->onchange_str = NULL;
		    }//if(!$this->readonly && !$this->disabled)
		    $groupAddonClass = strlen($this->size) ? ' input-'.$this->size : '';
			$result = "\t\t".'<div class="input-group date" id="'.$this->tagid.'_control">'."\n";
	        $result .= "\t\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'" autocomplete="off">'."\n";
	        $result .= "\t\t\t".'<span class="input-group-addon'.$groupAddonClass.'">'."\n";
			$result .= "\t\t\t\t".'<span class="glyphicon glyphicon-calendar"></span>'."\n";
			$result .= "\t\t\t".'</span>'."\n";
	        $result .= "\t\t".'</div>'."\n";
	    } else {
	        $result = "\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'" autocomplete="off">'."\n";
	    }//if($this->button)
		$result .= $this->GetActions();
		if($this->disabled!==TRUE && $this->readonly!==TRUE) {
			NApp::_ExecJs("$('#{$this->tagid}_control').{$this->plugin}({$jsparams});");
		    if(strlen($onChange)) { NApp::_ExecJs("$('#{$this->tagid}_control').on('dp.change',function(e) { {$onChange} });"); }
		}//if($this->disabled!==TRUE && $this->readonly!==TRUE)
		return $result;
	}//END protected function SetBootstrap3Control
}//END class TimePicker extends Control