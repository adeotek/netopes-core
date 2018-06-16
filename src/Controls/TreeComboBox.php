<?php
/**
 * ComboBox control class file
 *
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
    use NApp;
    /**
	 * TreeComboBox control
	 *
	 * Tree ComboBox control
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class TreeComboBox extends Control {
		/**
		 * @var    string Data adapter name
		 * @access public
		 */
		public $ajax_module = NULL;
		/**
		 * @var    string Data adapter method name
		 * @access public
		 */
		public $ajax_method = NULL;
		/**
		 * @var    array Data adapter method params array
		 * @access public
		 */
		public $ajax_params = NULL;
		/**
		 * @var    bool Encrypt url parameters
		 * @access public
		 */
		public $encrypted = NULL;

		protected function SetControl() {
			$this->ProcessActions();
			$lalign = strlen($this->align)>0 ? ' text-align: '.$this->align.';' : '';
			$lwidth = (is_numeric($this->width) && $this->width>0) ? $this->width - $this->GetActionsWidth() : NULL;
			$ccstyle = $lwidth ? ' style="width: '.$lwidth.'px;"' : '';
			if($this->dropdown_width) {
				$ddstyle = ' style="display: none; width: '.$this->dropdown_width.(is_numeric($this->dropdown_width) ? 'px' : '').';"';
			} else {
				$ddstyle = ' style="display: none;'.($lwidth ? ' width: '.$lwidth.'px;' : '').'"';
			}//if($this->dropdown_width)
			// NApp::_Dlog($ddstyle,'$ddstyle');
			$lstyle = strlen($this->style) ? ' style="'.$lalign.' '.$this->style.'"' : '';
			$ltabindex = (is_numeric($this->tabindex) && $this->tabindex>0) ? ' tabindex="'.$this->tabindex.'"' : '';
			$lextratagparam = strlen($this->extratagparam)>0 ? ' '.$this->extratagparam : '';
			$lonchange = strlen($this->onchange)>0 ? ' data-onchange="'.$this->onchange.'"' : '';
			$lplaceholder = '';
			if(strlen($this->pleaseselecttext)>0) {
				$lplaceholder = ' placeholder="'.$this->pleaseselecttext.'"';
			}//if(strlen($this->pleaseselecttext)>0)
			$rclass = $this->required===TRUE ? ' clsRequiredField' : '';
			$lclass = $this->baseclass.(strlen($this->class)>0 ? ' '.$this->class : '').$rclass;
			switch($this->theme_type) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					$lclass .= ' form-control';
					break;
				default:
					break;
			}//END switch
			$cclass = $this->baseclass.' ctrl-container'.(strlen($this->class)>0 ? ' '.$this->class : '');
			$ddbtnclass = $this->baseclass.' ctrl-dd-i-btn'.(strlen($this->class)>0 ? ' '.$this->class : '');
			if($this->disabled || $this->readonly) {
				$result = '<div id="'.$this->tagid.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
				$result .= "\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selectedvalue.'" class="'.$lclass.($this->postable ? ' postable' : '').'">'."\n";
				$result .= "\t".'<input type="text" id="'.$this->tagid.'-cbo" value="'.$this->selectedtext.'" data-value="'.$this->selectedvalue.'" class="'.$lclass.'"'.$lstyle.$lplaceholder.($this->disabled ? ' disabled="disabled"' : ' readonly="readonly"').$ltabindex.$lextratagparam.'>'."\n";
				$result .= "\t".'<div id="'.$this->tagid.'-ddbtn" class="'.$ddbtnclass.'"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
				$result .= '</div>'."\n";
				return $result;
			}//if($this->disabled || $this->readonly)
			$cbtnclass = $this->baseclass.' ctrl-clear'.(strlen($this->class) ? ' '.$this->class : '');
			$lddcclass = $this->baseclass.' ctrl-ctree'.(strlen($this->class)>0 ? ' '.$this->class : '');
			$ldivclass = $this->baseclass.' ctrl-dropdown';
			$result = '<div id="'.$this->tagid.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
			$result .= "\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selectedvalue.'" class="'.$lclass.($this->postable ? ' postable' : '').'"'.$lonchange.'>'."\n";
			$result .= "\t".'<input type="text" id="'.$this->tagid.'-cbo" value="'.$this->selectedtext.'" class="'.$lclass.'"'.$lstyle.$lplaceholder.' readonly="readonly"'.$ltabindex.$lextratagparam.' data-value="'.$this->selectedvalue.'" data-id="'.$this->tagid.'" onclick="CBODDBtnClick(\''.$this->tagid.'\');">'."\n";
			$result .= "\t".'<div id="'.$this->tagid.'-ddbtn" class="'.$ddbtnclass.'" onclick="CBODDBtnClick(\''.$this->tagid.'\');"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
			$result .= "\t".'<div id="'.$this->tagid.'-clear" class="'.$cbtnclass.'" onclick="TCBOSetValue(\''.$this->tagid.'\',\'null\',\'\',true);"></div>'."\n";
			$result .= "\t".'<div id="'.$this->tagid.'-dropdown" class="'.$ldivclass.'"'.$ddstyle.'>';
			$result .= "\t\t".'<div id="'.$this->tagid.'-ctree" class="'.$lddcclass.'"></div>';
			$result .= "\t".'</div>'."\n";
			$result .= '</div>'."\n";
			$urlparams = '';
			if(is_array($this->ajax_params)) {
				foreach($this->ajax_params as $pk=>$pv) { $urlparams .= '&'.$pk.'='.$pv; }
			}//if(is_array($this->ajax_params))
			$this->encrypted = $this->encrypted ? 1 : 0;
			$this->hide_parents_checkbox = $this->hide_parents_checkbox ? TRUE : FALSE;
			NApp::_SetSessionAcceptedRequest($this->uid);
			if(NApp::ajax() && is_object(NApp::arequest())) {
				NApp::arequest()->ExecuteJs("InitTCBOFancyTree('{$this->tagid}','{$this->selectedvalue}','{$this->ajax_module}','{$this->ajax_method}','{$urlparams}','".NApp::current_namespace()."','{$this->uid}',{$this->encrypted},".intval($this->hide_parents_checkbox).");");
			} else {
				$result .= "\t"."<script type=\"text/javascript\">InitTCBOFancyTree('{$this->tagid}','{$this->selectedvalue}','{$this->ajax_module}','{$this->ajax_method}','{$urlparams}','".NApp::current_namespace()."','{$this->uid}',{$this->encrypted},".intval($this->hide_parents_checkbox).");</script>"."\n";
			}//if(NApp::ajax() && is_object(NApp::arequest()))
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class TreeComboBox extends Control
?>