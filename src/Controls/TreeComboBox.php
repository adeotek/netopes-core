<?php
/**
 * ComboBox control class file
 * Standard ComboBox control
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppSession;
/**
 * TreeComboBox control
 * Tree ComboBox control
 * @package  NETopes\Controls
 */
class TreeComboBox extends Control {
	/**
	 * @var    array|null Data source configuration
	 */
	public $data_source = NULL;
	/**
	 * @var    bool Encrypt url parameters
	 */
	public $encrypted = NULL;
	/**
	 * Control class constructor
	 * @param  array $params An array of params
	 * @return void
	 */
	public function __construct($params = NULL) {
		parent::__construct($params);
	}//END public function __construct
	/**
	 * Set control HTML string
	 * @return string|null
	 */
	protected function SetControl(): ?string {
		$this->ProcessActions();
		$lalign = strlen($this->align)>0 ? ' text-align: '.$this->align.';' : '';
		$lwidth = (is_numeric($this->width) && $this->width>0) ? $this->width - $this->GetActionsWidth() : NULL;
		$ccstyle = $lwidth ? ' style="width: '.$lwidth.'px;"' : '';
		if($this->dropdown_width) {
			$ddstyle = ' style="display: none; width: '.$this->dropdown_width.(is_numeric($this->dropdown_width) ? 'px' : '').';"';
		} else {
			$ddstyle = ' style="display: none;'.($lwidth ? ' width: '.$lwidth.'px;' : '').'"';
		}//if($this->dropdown_width)
		// NApp::Dlog($ddstyle,'$ddstyle');
		$lstyle = strlen($this->style) ? ' style="'.$lalign.' '.$this->style.'"' : '';
		$ltabindex = (is_numeric($this->tabindex) && $this->tabindex>0) ? ' tabindex="'.$this->tabindex.'"' : '';
		$lextratagparam = strlen($this->extra_tag_params)>0 ? ' '.$this->extra_tag_params : '';
		$lonchange = strlen($this->onchange)>0 ? ' data-onchange="'.$this->onchange.'"' : '';
		$lplaceholder = '';
		if(strlen($this->please_select_text)>0) {
			$lplaceholder = ' placeholder="'.$this->please_select_text.'"';
		}//if(strlen($this->please_select_text)>0)
		$rclass = $this->required===TRUE ? ' clsRequiredField' : '';
		$lclass = $this->base_class.(strlen($this->class)>0 ? ' '.$this->class : '').$rclass;
		switch($this->theme_type) {
			case 'bootstrap2':
			case 'bootstrap3':
			case 'bootstrap4':
				$lclass .= ' form-control';
				break;
			default:
				break;
		}//END switch
		$cclass = $this->base_class.' ctrl-container'.(strlen($this->class)>0 ? ' '.$this->class : '');
		$ddbtnclass = $this->base_class.' ctrl-dd-i-btn'.(strlen($this->class)>0 ? ' '.$this->class : '');
		if($this->disabled || $this->readonly) {
			$result = '<div id="'.$this->tag_id.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
			$result .= "\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selected_value.'" class="'.$lclass.($this->postable ? ' postable' : '').'">'."\n";
			$result .= "\t".'<input type="text" id="'.$this->tag_id.'-cbo" value="'.$this->selected_text.'" data-value="'.$this->selected_value.'" class="'.$lclass.'"'.$lstyle.$lplaceholder.($this->disabled ? ' disabled="disabled"' : ' readonly="readonly"').$ltabindex.$lextratagparam.'>'."\n";
			$result .= "\t".'<div id="'.$this->tag_id.'-ddbtn" class="'.$ddbtnclass.'"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
			$result .= '</div>'."\n";
			return $result;
		} else {
		    $lclass = trim($lclass.' stdro');
		}//if($this->disabled || $this->readonly)
		$cbtnclass = $this->base_class.' ctrl-clear'.(strlen($this->class) ? ' '.$this->class : '');
		$lddcclass = $this->base_class.' ctrl-ctree'.(strlen($this->class)>0 ? ' '.$this->class : '');
		$ldivclass = $this->base_class.' ctrl-dropdown';
		$result = '<div id="'.$this->tag_id.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
		$result .= "\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selected_value.'" class="'.$lclass.($this->postable ? ' postable' : '').'"'.$lonchange.'>'."\n";
		$result .= "\t".'<input type="text" id="'.$this->tag_id.'-cbo" value="'.$this->selected_text.'" class="'.$lclass.'"'.$lstyle.$lplaceholder.' readonly="readonly"'.$ltabindex.$lextratagparam.' data-value="'.$this->selected_value.'" data-id="'.$this->tag_id.'" onclick="CBODDBtnClick(\''.$this->tag_id.'\');">'."\n";
		$result .= "\t".'<div id="'.$this->tag_id.'-ddbtn" class="'.$ddbtnclass.'" onclick="CBODDBtnClick(\''.$this->tag_id.'\');"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
		$result .= "\t".'<div id="'.$this->tag_id.'-clear" class="'.$cbtnclass.'" onclick="TCBOSetValue(\''.$this->tag_id.'\',\'\',\'\',true);"></div>'."\n";
		$result .= "\t".'<div id="'.$this->tag_id.'-dropdown" class="'.$ldivclass.'"'.$ddstyle.'>';
		$result .= "\t\t".'<div id="'.$this->tag_id.'-ctree" class="'.$lddcclass.'"></div>';
		$result .= "\t".'</div>'."\n";
		$result .= '</div>'."\n";
		$ds_module = get_array_value($this->data_source,'ds_class','','is_string');
        $ds_method = get_array_value($this->data_source,'ds_method','','is_string');
        if(strlen($ds_module) && strlen($ds_method)) {
            $ds_module = convert_from_camel_case($ds_module);
            $ds_method = convert_from_camel_case($ds_method);
            $urlParams = '';
            $ds_params = get_array_value($this->data_source,'ds_params',[],'is_array');
            if(count($ds_params)) {
                foreach($ds_params as $pk=>$pv) { $urlParams .= '&'.$pk.'='.$pv; }
            }//if(count($ds_params))
            $urlJsParams = strlen($urlParams) ? "urlParams: '".$urlParams."'" : '';
            $ds_js_params = get_array_value($this->data_source,'ds_js_params',[],'is_array');
            if(count($ds_js_params)) {
                foreach($ds_js_params as $acpk=>$acpv) { $urlJsParams .= (strlen($urlJsParams) ? ', ' : '').$acpk.': '.$acpv; }
            }//if(count($ds_js_params))
		    $this->encrypted = $this->encrypted ? 1 : 0;
		    $this->hide_parents_checkbox = $this->hide_parents_checkbox ? TRUE : FALSE;
		    AppSession::SetSessionAcceptedRequest($this->uid);
            NApp::AddJsScript("InitTCBOFancyTree('{$this->tag_id}','{$this->selected_value}','{$ds_module}','{$ds_method}',{{$urlJsParams}},'".NApp::$currentNamespace."','{$this->uid}',{$this->encrypted},".intval($this->hide_parents_checkbox).",".($this->icon ? 'true' : 'false').");");
        }//if(strlen($ds_module) && strlen($ds_method))
		$result .= $this->GetActions();
		return $result;
	}//END protected function SetControl
}//END class TreeComboBox extends Control