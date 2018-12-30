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
 * @version    2.3.0.1
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;

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
		$this->width_offset = is_object(NApp::$theme) ? NApp::$theme->GetControlsActionWidth() : 20;
		$this->plugin_type = is_object(NApp::$theme) ? NApp::$theme->GetDateTimePickerControlsType() : '';
		$this->plugin = is_object(NApp::$theme) ? NApp::$theme->GetDateTimePickerControlsPlugin() : '';
		parent::__construct($params);
		if($this->button!==TRUE) { $this->width_offset = 0; }
		if(!strlen($this->locale)) { $this->locale = NApp::_GetLanguageCode(); }
		if(!is_integer($this->minutesStepping) || $this->minutesStepping<=0) { $this->minutesStepping = 1; }
		if(!is_string($this->align)) { $this->align = 'center'; }
		if(!is_bool($this->today_button)) { $this->today_button = TRUE; }
	}//END public function __construct
	/**
	 * Set control HTML tag
	 *
	 * @return string
	 * @access protected
	 */
	protected function SetControl(): ?string {
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
		if($this->timepicker) { $ldata .= ' data-timeformat="'.(strlen($this->timeformat) ? $this->timeformat : 'HH:mm:ss').'"'; }
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
					."timeFormat: '".(strlen($this->timeformat) ? $this->timeformat : 'HH:mm:ss')."'"
				: '');
			$ldata .= ' data-jqdpparams="'.$ljqdpparams.'"';
		}//if(strlen($this->jqdpparams))
		$this->ProcessActions();
		if($this->button) {
		    $groupAddonClass = strlen($this->size) ? ' input-'.$this->size : '';
			$result = "\t\t".'<div class="control-set">'."\n";
			$result .= "\t\t\t".'<span class="input-group-addon'.$groupAddonClass.'" onclick="$(\'#'.$this->tag_id.'\').focus();"><i class="fa fa-calendar"></i></span>'."\n";
			$result .= "\t\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'"autocomplete="off">'."\n";
			$result .= "\t\t".'</div>'."\n";
		} else {
			$result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass($dpclass).$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'"autocomplete="off">'."\n";
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
	    if(strlen($this->format)) {
            $lFormat = $this->format;
            if($this->timepicker!==TRUE && $this->timepicker!==1) {
	            $lDateFormat = str_replace('m','M',strtolower($this->format));
	            $lTimeFormat = '';
	        } else {
	            $fParts = explode(' ',$lFormat);
	            $lDateFormat = str_replace('m','M',strtolower($fParts[0]));
	            $lTimeFormat = count($fParts)>1 ? $fParts[1] : 'HH:mm:ss';
	        }//if($this->timepicker!==TRUE && $this->timepicker!==1)
	    } elseif(strlen($this->dateformat)) {
            $lDateFormat = $this->dateformat;
            if($this->timepicker!==TRUE && $this->timepicker!==1) {
                $lFormat = strtoupper($this->dateformat);
                $lTimeFormat = '';
            } else {
                $lTimeFormat = strlen($this->timeformat) ? $this->timeformat : 'HH:mm:ss';
                $lFormat = strtoupper($this->dateformat).' '.$lTimeFormat;
            }//if($this->timepicker!==TRUE && $this->timepicker!==1)
	    } else {
	        $lDateFormat = 'dd.MM.yyyy';
	        if($this->timepicker!==TRUE && $this->timepicker!==1) {
	            $lTimeFormat = '';
	            $lFormat = 'DD.MM.YYYY';
	        } else {
	            $lTimeFormat = strlen($this->timeformat) ? $this->timeformat : 'HH:mm:ss';
	            $lFormat = 'DD.MM.YYYY HH:mm:ss';
	        }//if($this->timepicker!==TRUE && $this->timepicker!==1)
	    }//if(strlen($this->format))
	    // NApp::_Dlog($lFormat,'$lFormat');

		$ldata = ' data-format="'.$lDateFormat.'"';
		if(strlen($lTimeFormat)) { $ldata .= ' data-timeformat="'.$lTimeFormat.'"'; }
		if(strlen($this->jsparams)) {
			$jsparams = $this->jsparams;
		} else {
			$jsparams = "{ "
				."locale: '{$this->locale}', "
				."format: '{$lFormat}', "
				."showTodayButton: ".($this->today_button ? 'true' : 'false').", "
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
			$result = "\t\t".'<div class="input-group date" id="'.$this->tag_id.'_control">'."\n";
	        $result .= "\t\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'" autocomplete="off">'."\n";
	        $result .= "\t\t\t".'<span class="input-group-addon'.$groupAddonClass.'">'."\n";
			$result .= "\t\t\t\t".'<span class="glyphicon glyphicon-calendar"></span>'."\n";
			$result .= "\t\t\t".'</span>'."\n";
	        $result .= "\t\t".'</div>'."\n";
	    } else {
	        $result = "\t\t".'<input type="text" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().$ldata.' value="'.$this->value.'" autocomplete="off">'."\n";
	    }//if($this->button)
		$result .= $this->GetActions();
		if($this->disabled!==TRUE && $this->readonly!==TRUE) {
			NApp::_ExecJs("$('#{$this->tag_id}_control').{$this->plugin}({$jsparams});");
		    if(strlen($onChange)) { NApp::_ExecJs("$('#{$this->tag_id}_control').on('dp.change',function(e) { {$onChange} });"); }
		}//if($this->disabled!==TRUE && $this->readonly!==TRUE)
		return $result;
	}//END protected function SetBootstrap3Control
}//END class DatePicker extends Control