<?php
/**
 * BasicForm control class file
 *
 * Control class for generating basic forms
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\Validator;
use PAF\AppConfig;
use NApp;
/**
 * BasicForm control class
 *
 * Control class for generating basic forms
 *
 * @package  NETopes\Controls
 * @access   public
 */
class BasicForm {
	/**
	 * @var    string Theme (form) type: native(table)/bootstrap2/bootstrap3/bootstrap4
	 * @access public
	 */
	public $theme_type = NULL;
	/**
	 * @var    string BasicForm table id
	 * @access public
	 */
	public $tagid = NULL;
	/**
	 * @var    string BasicForm response target id
	 * @access public
	 */
	public $response_target = NULL;
	/**
	 * @var    string BasicForm width
	 * @access public
	 */
	public $width = NULL;
	/**
	 * @var    string Form horizontal align (default "center")
	 * @access public
	 */
	public $align = 'center';
	/**
	 * @var    string Separator column width
	 * @access public
	 */
	public $separator_width = NULL;
	/**
	 * @var    string BasicForm additional class
	 * @access public
	 */
	public $class = NULL;
	/**
	 * @var    int Columns number
	 * @access public
	 */
	public $colsno = NULL;
	/**
	 * @var    string Labels position type (horizontal/vertical)
	 * @access public
	 */
	public $positioning_type = NULL;
	/**
	 * @var    int Labels width in grid columns number
	 * @access public
	 */
	public $label_cols = NULL;
	/**
	 * @var    string Controls size CSS class
	 * @access public
	 */
	public $controls_size = NULL;
	/**
	 * @var    string Actions controls size CSS class
	 * @access public
	 */
	public $actions_size = NULL;
	/**
	 * @var    array fields descriptor array
	 * @access public
	 */
	public $content = [];
	/**
	 * @var    array form actions descriptor array
	 * @access public
	 */
	public $actions = [];
	/**
	 * @var    string Tags IDs sufix
	 * @access public
	 */
	public $tags_ids_sufix = NULL;
	/**
	 * @var    string Tags names sufix
	 * @access public
	 */
	public $tags_names_sufix = NULL;
	/**
	 * @var    string Sub-form tag ID
	 * @access public
	 */
	public $sub_form_tagid = NULL;
	/**
	 * @var    string Sub-form tag extra CSS class
	 * @access public
	 */
	public $sub_form_class = NULL;
	/**
	 * @var    string Sub-form tag extra attributes
	 * @access public
	 */
	public $sub_form_extratagparam = NULL;
	/**
	 * @var    string Basic form base class
	 * @access protected
	 */
	protected $baseclass = NULL;
	/**
	 * @var    string Output (resulting html) buffer
	 * @access protected
	 */
	protected $output_buffer = NULL;
	/**
	 * BasicForm class constructor method
	 *
	 * @param  array $params Parameters array
	 * @return void
	 * @access public
	 */
	public function __construct($params = NULL) {
		$this->baseclass = get_array_param($params,'clear_baseclass',FALSE,'bool') ? '' : 'cls'.get_class_basename($this);
		$this->theme_type = AppConfig::app_theme_type();
		$this->controls_size = AppConfig::app_theme_def_controls_size();
		$this->actions_size = AppConfig::app_theme_def_actions_size();
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) {
				if(property_exists($this,$k)) { $this->$k = $v; }
			}//foreach ($params as $k=>$v)
		}//if(is_array($params) && count($params))
		if(!is_numeric($this->colsno) || $this->colsno<=0) { $this->colsno = 1; }
		$this->output_buffer = $this->SetControl();
	}//END public function __construct
	/**
	 * Gets the form actions string
	 *
	 * @param int $tabindex
	 * @return void
	 * @access protected
	 */
	protected function GetActions($tabindex = 0) {
		$result = '';
		foreach($this->actions as $action) {
			$act_params = get_array_param($action,'params',[],'is_array');
			if(!count($act_params)) { continue; }
			$a_class = get_array_param($act_params,'class','','is_string');
			switch($this->theme_type) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					$ml_class = strlen($result) ? ' ml10' : '';
					if(get_array_param($action,'type','','is_string')=='CloseModal') {
						$act_params['onclick'] = "CloseModalForm('".get_array_param($action,'custom_action','','is_string')."','".get_array_param($action,'targetid','','is_string')."',".intval(get_array_param($action,'dynamic',1,'bool')).")";
						$act_params['class'] = strlen($a_class) ? $a_class : 'btn btn-default';
					} else {
						$act_params['class'] = strlen($a_class) ? $a_class : 'btn btn-primary';
					}//if(get_array_param($action,'type','','is_string')=='CloseModal')
					$act_params['class'] .= $ml_class;
					if(strlen($this->actions_size)) { $act_params['size'] = $this->actions_size; }
					break;
				default:
					if(get_array_param($action,'type','','is_string')=='CloseModal') {
						$act_params['onclick'] = "CloseModalForm('".get_array_param($action,'custom_action','','is_string')."','".get_array_param($action,'targetid','','is_string')."',".intval(get_array_param($action,'dynamic',1,'bool')).")";
						$act_params['class'] = strlen($a_class) ? $a_class : 'type-2';
					}//if(get_array_param($action,'type','','is_string')=='CloseModal')
					break;
			}//END switch
			$act_class = get_array_param($action,'type','Button','is_notempty_string');
			if(!is_string($act_class) || !strlen($act_class)) { continue; }
			$act_class = '\NETopes\Core\Controls\\'.$act_class;
			if(!class_exists($act_class)) { continue; }
			$act_instance = new $act_class($act_params);
			if(!Validator::IsValidParam($act_instance->tabindex,'','is_not0_numeric')){ $act_instance->tabindex = $tabindex++; }
			if(get_array_param($action,'clear_base_class',FALSE,'bool')){ $act_instance->ClearBaseClass(); }
			$result .= $act_instance->Show();
		}//END foreach
		return $result;
	}//END protected function GetActions
	/**
	 * Gets the form content as table
	 *
	 * @return void
	 * @access protected
	 */
	protected function GetTableControl() {
		$ltabindex = 101;
		$lclass = trim($this->baseclass.' '.$this->class);
		$lstyle = strlen($this->width)>0 ? ' style="width: '.$this->width.(strpos($this->width,'%')===FALSE ? 'px' : '').';"' : '';
		$sc_style = strlen($this->separator_width)>0 ? ' style="width: '.$this->separator_width.(strpos($this->separator_width,'%')===FALSE ? 'px' : '').';"' : '';
		if(!$this->width || $this->width='100%') {
			switch(strtolower($this->align)) {
				case 'left':
					$lsidecolumn = '';
					$rsidecolumn = "\t".'<td>&nbsp;</td>'."\n";
					break;
				case 'right':
					$lsidecolumn = "\t".'<td>&nbsp;</td>'."\n";
					$rsidecolumn = '';
					break;
				case 'center':
				default:
					$lsidecolumn = $rsidecolumn = "\t".'<td>&nbsp;</td>'."\n";
					break;
			}//END switch
		} else {
			$lsidecolumn = $rsidecolumn = '';
		}//if(!$this->width || $this->width='100%')
		$result = '';
		if(strlen($this->sub_form_tagid)) {
			$sfclass = 'clsSubForm'.(strlen($this->sub_form_class) ? ' '.$this->sub_form_class : '');
			$sfextratagparam = (strlen($this->sub_form_extratagparam) ? ' '.$this->sub_form_extratagparam : '');
			$result .= '<div class="'.$sfclass.'" id="'.$this->sub_form_tagid.'"'.$sfextratagparam.'>'."\n";
		}//if(strlen($this->sub_form_tagid))
		$result .= '<table'.($this->tagid ? ' id="'.$this->tagid.'"' : '').' class="'.$lclass.'"'.$lstyle.'>'."\n";
		foreach($this->content as $row) {
			if(!is_array($row) || !count($row)) { continue; }
			$sr_type = get_array_param($row,'separator',NULL,'is_notempty_string');
			if($sr_type) {
				$result .= "\t".'<tr>'."\n";
				$result .= $lsidecolumn;
				switch(strtolower($sr_type)) {
					case 'empty':
						$sr_class = ' class="clsTRS"';
						$sr_hr = '&nbsp;';
						break;
					case 'title':
						$sr_hr = get_array_param($row,'value','&nbsp;','is_notempty_string');
						$sr_c_class = get_array_param($row,'class','','is_string');
						$sr_class = ' class="clsTRS form-title'.($sr_c_class ? ' '.$sr_c_class : '').'"';
						break;
					case 'subtitle':
						$sr_hr = get_array_param($row,'value','&nbsp;','is_notempty_string');
						$sr_c_class = get_array_param($row,'class','','is_string');
						$sr_class = ' class="clsTRS sub-title'.($sr_c_class ? ' '.$sr_c_class : '').'"';
						break;
					case 'line':
					default:
						$sr_class = ' class="clsTRS"';
						$sr_hr = '<hr>';
						break;
				}//END switch
				$sr_span = ' colspan="'.($this->colsno * 2 - 1).'"';
				$result .= "\t\t".'<td'.$sr_span.$sr_class.'>'.$sr_hr.'</td>'."\n";
				$result .= $rsidecolumn;
				$result .= "\t".'</tr>'."\n";
				continue;
			}//if($sr_type)
			$hidden = get_array_param($row,0,FALSE,'bool','hidden_row');
			$result .= "\t".'<tr'.($hidden ? ' class="hidden"' : '').'>'."\n";
			$result .= $lsidecolumn;
			$first = TRUE;
			foreach($row as $col) {
				if($first) {
					$first = FALSE;
				} else {
					$result .= "\t\t".'<td'.$sc_style.'>&nbsp;</td>'."\n";
				}//if($first)
				$c_type = get_array_param($col,'control_type',NULL,'is_notempty_string');
				$c_width = get_array_param($col,'width',NULL,'is_notempty_string');
				$cstyle = strlen($c_width)>0 ? ' style="width: '.$c_width.(strpos($c_width,'%')===FALSE ? 'px' : '').';"' : '';
				$c_span = get_array_param($col,'colspan',1,'is_numeric');
				$cspan = $c_span>1 ? ' colspan="'.($c_span+($c_span-1)).'"' : '';
				$c_type = $c_type ? '\NETopes\Core\Controls\\'.$c_type : $c_type;
				if(!$c_type || !class_exists($c_type)) {
					\NApp::_Elog('Control class ['.$c_type.'] not found!');
					$result .= "\t\t".'<td'.$cspan.$cstyle.'>&nbsp;</td>'."\n";
					continue;
				}//if(!$c_type || !class_exists($c_type))
				$result .= "\t\t".'<td'.$cspan.$cstyle.'>'."\n";
				$ctrl_params = get_array_param($col,'control_params',[],'is_array');
				if(strlen($this->tags_ids_sufix) && isset($ctrl_params['tagid'])) { $ctrl_params['tagid'] .= $this->tags_ids_sufix; }
				if(strlen($this->tags_names_sufix) && isset($ctrl_params['tagname'])) { $ctrl_params['tagname'] .= $this->tags_names_sufix; }
				$control = new $c_type($ctrl_params);
				if(property_exists($c_type,'tabindex')&& !\NETopes\Core\App\Validator::IsValidParam($control->tabindex,'','is_not0_numeric')){ $control->tabindex = $ltabindex++; }
				if(get_array_param($col,'clear_base_class',FALSE,'bool')){ $control->ClearBaseClass(); }
				$result .= $control->Show();
				$result .= "\t\t".'</td>'."\n";
			}//END foreach
			$result .= $rsidecolumn;
			$result .= "\t".'</tr>'."\n";
		}//END foreach
			$lcolspan = (2 * $this->colsno - 1)>1 ? ' colspan="'.(2 * $this->colsno - 1).'"' : '';
		if(is_string($this->response_target) && strlen($this->response_target)) {
			$result .= "\t".'<tr>'."\n";
			$result .= $lsidecolumn;
			$result .= "\t\t".'<td'.$lcolspan.' id="'.$this->response_target.'" class="'.($this->baseclass ? $this->baseclass : 'cls').'ErrMsg'.'"></td>'."\n";
			$result .= $rsidecolumn;
			$result .= "\t".'</tr>'."\n";
		}//if(is_string($this->response_target) && strlen($this->response_target))
		if(is_array($this->actions) && count($this->actions)) {
			$result .= "\t".'<tr>'."\n";
			$result .= $lsidecolumn;
			$result .= "\t\t".'<td'.$lcolspan.'>'."\n";
			$result .= $this->GetActions($ltabindex);
			$result .= "\t\t".'</td>'."\n";
			$result .= $rsidecolumn;
			$result .= "\t".'</tr>'."\n";
		}//if(is_array($this->actions) && count($this->actions))
		$result .= '</table>';
		if(strlen($this->sub_form_tagid)) { $result .= '</div>'."\n"; }
		return $result;
	}//END protected function GetTableControl
	/**
	 * Gets the form content as bootstrap3 form
	 *
	 * @return void
	 * @access protected
	 */
	protected function GetBootstrap3Control() {
		$ltabindex = 101;
		$lclass = trim($this->baseclass.' '.$this->class);
		if($this->positioning_type!='vertical') { $lclass .= ' form-horizontal'; }
		if(strlen($this->controls_size)) { $lclass .= ' form-'.$this->controls_size; }
		if($this->colsno>1) { $lclass .= ' multi'; }
		if(strlen($this->sub_form_tagid)) {
			$sfclass = 'clsSubForm'.(strlen($this->sub_form_class) ? ' '.$this->sub_form_class : '');
			$sfextratagparam = (strlen($this->sub_form_extratagparam) ? ' '.$this->sub_form_extratagparam : '');
			$result = '<div class="'.$sfclass.'" id="'.$this->sub_form_tagid.'"'.$sfextratagparam.'>'."\n";
			$result .= '<div'.($this->tagid ? ' id="'.$this->tagid.'"' : '').' class="clsFormContainer '.$lclass.'">'."\n";
		} else {
			$result = '<div class="row"><div class="col-md-12 '.$lclass.'"'.($this->tagid ? ' id="'.$this->tagid.'"' : '').'>'."\n";
		}//if(strlen($this->sub_form_tagid))
		foreach($this->content as $row) {
			if(!is_array($row) || !count($row)) { continue; }
			$sr_type = get_array_param($row,'separator',NULL,'is_notempty_string');
			if($sr_type) {
				$result .= "\t".'<div class="row">'."\n";
				switch(strtolower($sr_type)) {
					case 'empty':
						$sr_class = ' clsTRS';
						$sr_hr = '&nbsp;';
						break;
					case 'title':
						$sr_hr = get_array_param($row,'value','&nbsp;','is_notempty_string');
						$sr_c_class = get_array_param($row,'class','','is_string');
						$sr_class = ' clsTRS form-title'.($sr_c_class ? ' '.$sr_c_class : '');
						break;
					case 'subtitle':
						$sr_hr = get_array_param($row,'value','&nbsp;','is_notempty_string');
						$sr_c_class = get_array_param($row,'class','','is_string');
						$sr_class = ' clsTRS sub-title'.($sr_c_class ? ' '.$sr_c_class : '');
						break;
					case 'line':
					default:
						$sr_class = ' clsTRS';
						$sr_hr = '<hr>';
						break;
				}//END switch
				$result .= "\t\t".'<div class="col-md-12'.$sr_class.'">'.$sr_hr.'</div>'."\n";
				$result .= "\t".'</div>'."\n";
				continue;
			}//if($sr_type)
			$lgs = 12;
			$hidden = get_array_param($row,0,FALSE,'bool','hidden_row');
			if($this->colsno>1) {
				$lgs = round(12/$this->colsno,0,PHP_ROUND_HALF_DOWN);
				$result .= "\t".'<div class="row'.($hidden ? ' hidden' : '').'">'."\n";
			}//if($this->colsno>1)
			$ci = 0;
			foreach($row as $col) {
				$c_type = get_array_param($col,'control_type',NULL,'is_notempty_string');
				$csi = 0;
				if($this->colsno>1) {
					$c_span = get_array_param($col,'colspan',1,'is_numeric');
					if($c_span==$this->colsno) {
						$c_class = get_array_param($col,'class','col-md-12','is_notempty_string');
						$csi = $this->colsno;
					} else {
						$c_cols = get_array_param($col,'cols',0,'is_integer');
						if($c_cols>0 && $c_cols<=12) {
							$c_gs = $c_cols;
						} else {
							$c_gs = $c_span>1 ? $lgs*$c_span : $lgs;
						}//if($c_cols>0 && $c_cols<=12)
						$c_class = get_array_param($col,'class','col-md-'.$c_gs,    'is_notempty_string');
						$csi += $c_span;
					}//if($c_span==$this->colsno)
				} else {
					$c_class = get_array_param($col,'class','form-group','is_notempty_string');
					if($hidden) { $c_class .= ' hidden'; }
				}//if($this->colsno>1)
				if(strlen($c_type)) {
					$c_type = '\NETopes\Core\Controls\\'.$c_type;
					if(!class_exists($c_type)) {
					NApp::_Elog('Control class ['.$c_type.'] not found!');
					$result .= "\t\t".'<div class="'.$c_class.'">&nbsp;</div>'."\n";
						$ci += $csi;
					continue;
					}//if(!class_exists($c_type))
				} else {
					continue;
				}//if(strlen($c_type))
				$ci += $csi;
				$ctrl_params = get_array_param($col,'control_params',[],'is_array');
				$ctrl_params['theme_type'] = $this->theme_type;
				if(strlen($this->tags_ids_sufix) && isset($ctrl_params['tagid'])) { $ctrl_params['tagid'] .= $this->tags_ids_sufix; }
				if(strlen($this->tags_names_sufix) && isset($ctrl_params['tagname'])) { $ctrl_params['tagname'] .= $this->tags_names_sufix; }
				// Label and input CSS columns calculation
				$ctrl_label_cols = get_array_param($ctrl_params,'label_cols',0,'is_integer');
				if($ctrl_label_cols<1 || $ctrl_label_cols>11) {
				$c_c_lcols = get_array_param($col,'label_cols',0,'is_integer');
				$c_lcols = $c_c_lcols>0 ? $c_c_lcols : $this->label_cols;
				if(is_numeric($c_lcols) && $c_lcols>0) {
					if($this->colsno>1 && $c_span!=$this->colsno && $c_c_lcols==0) {
						$c_lcols = $c_lcols * $this->colsno / ($c_span>0 ? $c_span : 1);
						$ctrl_params['label_cols'] = $c_lcols;
					} else {
						$ctrl_params['label_cols'] = $c_lcols;
					}//if($this->colsno>1 && $c_span!=$this->colsno && $c_c_lcols==0)
				}//if(is_numeric($c_lcols) && $c_lcols>0)
				}//if($ctrl_label_cols<1 || $ctrl_label_cols>11)
				$c_cols = get_array_param($col,'control_cols',0,'is_integer');
				if($c_cols>0) { $ctrl_params['cols'] = $c_cols; }
				//$this->controls_size
				$c_size = get_array_param($col,'control_size','','is_string');
				if(strlen($c_size)) { $ctrl_params['size'] = $c_size; }
				$c_label_pos = get_array_param($ctrl_params,'labelposition',($this->positioning_type=='vertical' ? 'top' : 'left'),'is_notempty_string');
				$ctrl_params['labelposition'] = $c_label_pos;

				$control = new $c_type($ctrl_params);
				if(property_exists($c_type,'tabindex')&& !\NETopes\Core\App\Validator::IsValidParam($control->tabindex,'','is_not0_numeric')){ $control->tabindex = $ltabindex++; }
				if(get_array_param($col,'clear_base_class',FALSE,'bool')){ $control->ClearBaseClass(); }
				if($this->colsno>1) {
					$ctrl_params['container'] = FALSE;
					//form-group
					$result .= "\t\t".'<div class="'.$c_class.'">'."\n";
					$result .= $control->Show();
					$result .= "\t\t".'</div>'."\n";
				} else {
					$ctrl_params['class'] = trim(get_array_param($ctrl_params,'class','','is_string').($hidden ? ' hidden' : ''));
					$ctrl_params['container'] = TRUE;
					$result .= $control->Show();
				}//if($this->colsno>1)
			}//END foreach
			if($this->colsno>1) {
				if($ci<$this->colsno) { for($i=0;$i<($this->colsno-$ci);$i++) { $result .= "\t".'<div class="col-md-'.$lgs.'"></div>'."\n"; } }
				$result .= "\t".'</div>'."\n";
			}//if($this->colsno>1)
		}//END foreach
		if(is_string($this->response_target) && strlen($this->response_target)) {
			$result .= "\t".'<div class="row">'."\n";
			$result .= "\t\t".'<div class="col-md-12 '.($this->baseclass ? $this->baseclass : 'cls').'ErrMsg" id="'.$this->response_target.'">&nbsp;</div>'."\n";
			$result .= "\t".'</div>'."\n";
		}//if(is_string($this->response_target) && strlen($this->response_target))
		if(is_array($this->actions) && count($this->actions)) {
			$result .= "\t".'<div class="row '.($this->baseclass ? $this->baseclass : 'clsForm').'Footer">'."\n";
			$result .= "\t\t".'<div class="col-md-12">'."\n";
			$result .= $this->GetActions($ltabindex);
			$result .= "\t\t".'</div>'."\n";
			$result .= "\t".'</div>'."\n";
		}//if(is_array($this->actions) && count($this->actions))
        $result .= '</div></div>'."\n";
        return $result;
	}//END protected function GetBootstrap3Control
	/**
	 * Sets the output buffer value
	 *
	 * @return void
	 * @access protected
	 */
	protected function SetControl() {
		if(!is_array($this->content) || !count($this->content)) { return NULL; }
		switch($this->theme_type) {
			case 'bootstrap3':
			case 'bootstrap4':
				return $this->GetBootstrap3Control();
			case 'standard':
			case 'native':
			case 'table':
			default:
				return $this->GetTableControl();
		}//END switch
	}//END private function SetControl
	/**
	 * Gets the output buffer content
	 *
	 * @return string Returns the output buffer content (html)
	 * @access public
	 */
	public function Show() {
		return $this->output_buffer;
	}//END public function Show
}//END class BasicForm
?>