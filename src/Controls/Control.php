<?php
/**
 * Control abstract class file
 *
 * Base abstract class for controls
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.6.1
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\Module;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\Data\VirtualEntity;
use PAF\AppSession;
use PAF\AppException;
use NApp;
use GibberishAES;

/**
 * Control abstract class file
 *
 * Base abstract class for controls
 *
 * @package  NETopes\Controls
 * @access   public
 * @abstract
 */
abstract class Control {
	/**
	 * @var    array Control dynamic properties array
	 * @access protected
	 */
	protected $pdata = [];
	/**
	 * @var    string Control instance hash
	 * @access protected
	 */
	protected $chash = NULL;
	/**
	 * @var    string Control instance UID
	 * @access protected
	 */
	protected $uid = NULL;
	/**
	 * @var    string Control base class
	 * @access protected
	 */
	protected $baseclass = '';
	/**
	 * @var    bool Page hash (window.name)
	 * @access public
	 */
	public $phash = NULL;
	/**
	 * @var    string Main tag html id
	 * @access public
	 */
	public $tagid = NULL;
	/**
	 * @var    bool Postable in PAF ajax calls (default: TRUE)
	 * @access public
	 */
	public $postable = TRUE;
	/**
	 * @var    string Theme type
	 * @access public
	 */
	public $theme_type = NULL;
	/**
	 * @var    boolean Output container
	 * @access public
	 */
	public $container = TRUE;
	/**
	 * @var    boolean Flag for no label
	 * @access public
	 */
	public $no_label = FALSE;
	/**
	 * @var    bool Output buffer on/off
	 * @access protected
	 */
	protected $buffered = FALSE;
	/**
	 * @var    string Output (resulting html) buffer
	 * @access protected
	 */
	protected $output_buffer = NULL;
	/**
	 * @var    array Control processed actions
	 * @access private
	 */
	private $ctrl_actions = NULL;
	/**
	 * Control class dynamic getter method
	 *
	 * @param  string $name The name o the property
	 * @return mixed Returns the value of the property
	 * @access public
	 */
	public function __get($name) {
		return (is_array($this->pdata) && array_key_exists($name,$this->pdata)) ? $this->pdata[$name] : NULL;
	}//END public function __get
	/**
	 * Control class dynamic setter method
	 *
	 * @param  string $name The name o the property
	 * @param  mixed  $value The value to be set
	 * @return void
	 * @access public
	 */
	public function __set($name,$value) {
		if(!is_array($this->pdata)) { $this->pdata = []; }
		$this->pdata[$name] = $value;
	}//END public function __set
	/**
	 * Control class constructor
	 *
	 * @param  array $params An array of params
	 * @return void
	 * @access public
	 */
	public function __construct($params = NULL) {
		$this->chash = AppSession::GetNewUID(get_class_basename($this));
		$this->uid = AppSession::GetNewUID(get_class_basename($this),'md5');
		$this->required = FALSE;
		$this->labelposition = 'left';
		$this->labelwidth = 0;
		$this->label_cols = 3;
		$this->width = 0;
		$this->cols = 0;
		$this->height = 0;
		// $this->size = 'xxs';
		$this->tabindex = NULL;
		$this->baseclass = 'cls'.get_class_basename($this);
		$this->theme_type = is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) { $this->$k = $v; }
		}//if(is_array($params) && count($params))
		$this->tagid = $this->tagid=='__auto' ? AppSession::GetNewUID() : $this->tagid;
		if($this->required===1 || $this->required==='1') { $this->required = TRUE; }
		switch($this->theme_type) {
			case 'bootstrap2':
			case 'bootstrap3':
			case 'bootstrap4':
				// For backwards compatibility
				switch(strtolower($this->container)) {
					case 'bootstrap':
					case 'bootstrap3':
						$this->container = TRUE;
						$this->labelposition = 'left';
						break;
					case 'bootstrap_horizontal':
					case 'horizontalbootstrap3':
						$this->container = TRUE;
						$this->labelposition = 'left';
						break;
					case 'table':
					case 'form':
					case 'div':
					case 'simpletable':
						$this->container = TRUE;
						break;
					default:
						if(is_numeric($this->container)) { $this->container = $this->container==1; }
						elseif(is_string($this->container)) { $this->container = FALSE; }
						break;
				}//END switch
				$this->ProcessWidth(TRUE);
				break;
			default:
				$this->theme_type = 'table';
				// For backwards compatibility
				switch(strtolower($this->container)) {
					case 'bootstrap':
					case 'bootstrap3':
					case 'bootstrap_horizontal':
					case 'horizontalbootstrap3':
					case 'table':
					case 'form':
					case 'div':
					case 'simpletable':
						$this->container = TRUE;
						break;
					default:
						$this->container = FALSE;
						break;
				}//END switch
				$this->ProcessWidth(FALSE);
				break;
		}//END switch
		if($this->buffered){ $this->output_buffer = $this->SetContainer($this->SetControl()); }
	}//END public function __construct
	/**
	 * Gets this instance as a serialized string
	 *
	 * @param  bool $encrypted Switch on/off encrypted result
	 * @return string Return serialized control instance
	 * @access protected
	 */
	protected function GetThis($encrypted = TRUE) {
		if($encrypted && strlen($this->chash)){ return GibberishAES::enc(serialize($this),$this->chash); }
		return serialize($this);
	}//END protected function GetThis
	/**
	 * Process the control actions
	 *
	 * @return void
	 * @access protected
	 */
	protected function ProcessActions() {
		if(!$this->disabled && !$this->readonly && is_array($this->actions) && count($this->actions)) {
			$this->ctrl_actions = [];
			foreach($this->actions as $av) {
				$c_a_params = get_array_value($av,'params',NULL,'is_notempty_array');
				if(!$c_a_params) { continue; }
				$c_a_params['theme_type'] = $this->theme_type;
				$c_a_params['size'] = $this->size;
				$this->ctrl_actions[] = array(
					'params'=>$c_a_params,
					'action_params'=>get_array_value($av,'action_params',NULL,'is_notempty_array'),
				);
			}//END foreach
		}//if(!$this->disabled && !$this->readonly && is_array($this->actions) && count($this->actions))
		return $this->ctrl_actions;
	}//END protected function ProcessActions
	/**
	 * Get the actions total css width
	 *
	 * @return int Returns actions total width
	 * @access protected
	 */
	protected function GetActionsWidth() {
		if(!is_array($this->ctrl_actions)) { return 0; }
		return (count($this->ctrl_actions) * 28);
	}//END protected function GetActionsWidth
	/**
	 * Check if control has actions or not
	 *
	 * @return bool Returns TRUE if the control has actions or FALSE otherwise
	 * @access public
	 */
	public function HasActions(): bool {
		return (is_array($this->ctrl_actions) && count($this->ctrl_actions));
	}//END public function HasActions
	/**
	 * Get the actions html string
	 *
	 * @return int Returns actions string
	 * @access protected
	 */
	protected function GetActions() {
		if(!$this->HasActions()) { return NULL; }
		$result = '';
		if($this->container) { $result .= "\t\t\t\t".'<span class="input-group-btn">'."\n"; }
		if(strlen($this->dynamic_target)) {
			if(NApp::ajax() && is_object(NApp::arequest())) {
				NApp::arequest()->ExecuteJs("AppendDynamicModal('{$this->dynamic_target}');");
			} else {
				$result .= "\t"."<script type=\"text/javascript\">AppendDynamicModal('{$this->dynamic_target}');</script>"."\n";
			}//if(NApp::ajax() && is_object(NApp::arequest()))
		}//if(strlen($this->dynamic_target))
		foreach($this->ctrl_actions as $act) {
			$act_params = $act['params'];
			$act_params['action_params'] = get_array_value($act,'action_params',NULL,'is_notempty_array');
			$act_params['onclick'] = 'var thisval = $(\'#'.$this->tagid.'\').val(); '.get_array_value($act_params,'onclick','','is_string');
			$act_params['disabled'] = $this->disabled;
			$act_params['style'] = get_array_value($act,'style','','is_string');
			$act_button = new Button($act_params);
			$act_button->ClearBaseClass();
			$result .= $act_button->Show();
		}//END foreach
		if($this->container) { $result .= "\t\t\t\t".'</span>'."\n"; }
		return $result;
	}//END protected function GetActions
	/**
	 * Gets the html tag id string (' id="..."')
	 *
	 * @param  bool   $tagname Include the tag name in the result TRUE/FALSE (default FALSE)
	 * @param null  $sufix
	 * @return string Returns the html tag id
	 * @access protected
	 */
	protected function GetTagId($tagname = FALSE,$sufix = NULL) {
		if(!strlen($this->tagid)) { return ''; }
		if(!$tagname) { return ' id="'.$this->tagid.(strlen($sufix) ? $sufix : '').'"'; }
		if($tagname===2) { return ' id="'.$this->tagid.(strlen($sufix) ? $sufix : '').'" data-name="'.(strlen($this->tagname) ? $this->tagname : $this->tagid).(strlen($sufix) ? $sufix : '').'"'; }
		return ' id="'.$this->tagid.(strlen($sufix) ? $sufix : '').'" name="'.(strlen($this->tagname) ? $this->tagname : $this->tagid).(strlen($sufix) ? $sufix : '').'"';
	}//END protected function GetTagId
	/**
	 * Gets the html tag class string (' class="..."')
	 *
	 * @param  string $extra Other html classes to be included
	 * @return string Returns the html tag class
	 * @access protected
	 */
	protected function GetTagClass($extra = NULL,$raw = FALSE) {
		$lclass = (!$this->clear_base_class ? $this->baseclass : '');
		if(strlen($this->class)) { $lclass .= ' '.$this->class; }
		if(!$this->clear_base_class) {
			switch($this->theme_type) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					switch(get_class_basename($this)) {
						case 'Button':
						case 'DivButton':
						case 'Link':
						case 'Container':
							if(strlen($this->size)) { $lclass .= ' btn-'.$this->size; }
							break;
						case 'KVList':
							break;
						case 'CheckBox':
							if(strlen($this->size)) { $lclass .= ' checkbox-'.$this->size; }
							break;
						case 'SmartComboBox':
							if($this->theme_type!='bootstrap2') { $lclass .= ' form-control'; }
							if(strlen($this->size)) { $lclass .= ' input-'.$this->size; }
							if($raw===TRUE && ($this->required===TRUE || $this->required===1 || $this->required==='1')) { $lclass .= ' clsRequiredField'; }
							break;
						default:
							if($this->theme_type!='bootstrap2') { $lclass .= ' form-control'; }
							if(strlen($this->size)) { $lclass .= ' input-'.$this->size; }
							break;
					}//END switch
					break;
				default:
					switch(get_class_basename($this)) {
						case 'SmartComboBox':
							if($raw===TRUE && ($this->required===TRUE || $this->required===1 || $this->required==='1')) { $lclass .= ' clsRequiredField'; }
							break;
						default:
							break;
					}//END switch
					break;
			}//END switch
		}//if(!$this->clear_base_class)
		if(strlen($extra)) { $lclass .= ' '.$extra; }
		if($raw===TRUE) { return trim($lclass); }
		if($this->required===TRUE || $this->required===1 || $this->required==='1') { $lclass .= ' clsRequiredField'; }
		if($this->postable) { $lclass .= ' postable'; }
		if(strlen($this->onenter)) { $lclass .= ' clsOnEnterAction'; }
		if(strlen($this->onenterbutton)) { $lclass .= ' clsOnEnterActionButton'; }
		if(strlen(trim($lclass))) { return ' class="'.trim($lclass).'"'; }
		return '';
	}//END protected function GetTagClass
	/**
	 * Gets the html tag style attribute string (' style="...")
	 *
	 * @param  mixed  $width Custom css width
	 * @param  bool   $halign Include the tag text-align css attribute TRUE/FALSE (default TRUE)
	 * @param  string $extra Other css attributes to be included
	 * @return string Returns the html tag style attribute
	 * @access protected
	 */
	protected function GetTagStyle($width = NULL,$halign = TRUE,$extra = NULL) {
		$lstyle = '';
		$f_width = $this->fixed_width;
		switch($this->theme_type) {
			case 'bootstrap2':
			case 'bootstrap3':
			case 'bootstrap4':
				$fwidth = FALSE;
				if(isset($f_width) && strlen($f_width)) {
					if(is_numeric($f_width)) {
						if($f_width>0) {
							$lstyle .= ' display: inline-block; width: '.$f_width.'px;';
							$fwidth = TRUE;
						}//if($this->fixed_width>0)
					} else {
						$lstyle .= ' display: inline-block; width: '.$f_width.';';
						$fwidth = TRUE;
					}//if(is_numeric($f_width))
				}//if(isset($f_width) && strlen($f_width))
				if(!$fwidth) {
				    if($this->container) {
				        $lstyle .= ' display: inline-block;';
				    } else {
				        $wo = is_numeric($this->width_offset) ? $this->width_offset : 0;
                        $a_width = $this->GetActionsWidth();
                        if($a_width>0) { $lstyle .= ' display: inline-block; width: calc(100% - '.($a_width+$wo).'px);'; }
				    }//if($this->container)
				}//if(!$fwidth)
				break;
			default:
				if(isset($f_width) && strlen($f_width)) { $this->width = $f_width; }
				$wo = is_numeric($this->width_offset) ? $this->width_offset : 0;
				if(strlen($width)) {
					$lwidth = is_numeric($width) ? ($width-$this->GetActionsWidth()-$wo).'px' : $width;
				} else {
					$lwidth = (is_numeric($this->width) && $this->width>0) ? ($this->width-$this->GetActionsWidth()-$wo).'px' : $this->width;
				}//if(strlen($width))
				if($lwidth) { $lstyle .= ' box-sizing: border-box; width: '.$lwidth.';'; }
				break;
		}//END switch
		if($halign && strlen($this->align)) { $lstyle .= ' text-align: '.$this->align.';'; }
		if($this->height) { $lstyle .= ' height: '.$this->height.(is_numeric($this->height) ? 'px' : '').';'; }
		if(strlen($this->style)) { $lstyle .= ' '.$this->style; }
		$lstyle = trim($lstyle).(strlen($extra) ? ' '.$extra : '');
		if(strlen($lstyle)) { return ' style="'.$lstyle.'"'; }
		return '';
	}//END protected function GetTagStyle
	/**
	 * Gets the html tag attributes string (' placeholder="..." disabled="..." ...')
	 *
	 * @param  bool   $style Include the tag style attribute TRUE/FALSE (default TRUE)
	 * @param  string $extra Other html attributes to be included
	 * @return string Returns the html tag attributes
	 * @access protected
	 */
	protected function GetTagAttributes($style = TRUE,$extra = NULL) {
		$lattr = '';
		if($style) { $lattr .= $this->GetTagStyle(); }
		if($this->disabled===TRUE || $this->disabled===1 || $this->disabled==='1') { $lattr .= ' disabled="disabled"'; }
		if($this->readonly===TRUE || $this->readonly===1 || $this->readonly==='1') { $lattr .= ' readonly="readonly"'; }
		if(strlen($this->placeholder)) { $lattr .= ' placeholder="'.$this->placeholder.'"'; }
		if(is_numeric($this->tabindex) && $this->tabindex>0) { $lattr .= ' tabindex="'.$this->tabindex.'"'; }
		if(strlen($this->onenter)) { $lattr .= ' data-onenter="'.$this->onenter.'"'; }
		if(strlen($this->onenterbutton)) { $lattr .= ' data-onenterbtn="'.$this->onenterbutton.'"'; }
		if(strlen($this->paf_property)) { $lattr .= ' data-paf-prop="'.$this->paf_property.'"'; }
		if(strlen($this->extratagparam)) { $lattr .= ' '.$this->extratagparam; }
		$lattr = trim($lattr).(strlen($extra) ? ' '.$extra : '');
		return $lattr;
	}//END protected function GetTagAttributes
	/**
	 * Gets the html tag action attributes string (' onclick="..." onchange="..." ...')
	 *
	 * @param  bool   $style Include the tag style attribute TRUE/FALSE (default TRUE)
	 * @param  string $extra Other html attributes to be included
	 * @return string Returns the html tag attributes
	 * @access protected
	 */
	protected function GetTagActions($base = NULL,$extra = NULL) {
		if($this->readonly || $this->disabled) { return ''; }
		$lactions = [];
		if(is_array($base)) {
			foreach($base as $ak=>$av) { if(strlen($av)) { $lactions[$ak] = trim($av); } }
		}//if(is_array($base))
		// OnClick
		$lonclick = (isset($lactions['onclick']) && strlen($lactions['onclick']) ? $lactions['onclick'] : '').((strlen($this->onclick) && ($this->data_onclick!==TRUE || $this->disabled!==TRUE)) ? ' '.trim(trim($this->onclick),';').';' : '');
		if(strlen($this->onclick_str) && ($this->data_onclick!==TRUE || $this->disabled!==TRUE)) {
			if(strpos($this->onclick_str,'#action_params#')!==FALSE) {
				$act_params = '';
				if(is_array($this->action_params) && count($this->action_params)) {
					foreach($this->action_params as $pk=>$pv) {
						$act_params .= ($act_params ? '~' : '')."'{$pk}'|'{$pv}'";
					}//END foreach
				}//if(is_array($this->action_params) && count($this->action_params))
				$lonclick_scr = str_replace('#action_params#',$act_params,$this->onclick_str);
			} else {
				$lonclick_scr = $this->onclick_str;
			}//if(strpos($this->onclick_str,'#action_params#')!==FALSE)
			if(isset($this->run_oninit_func) && is_numeric($this->run_oninit_func)) {
				$lonclick .= ($lonclick ? ' ' : '').NApp::arequest()->Prepare($lonclick_scr,1,NULL,NULL,1,$this->run_oninit_func);
			} else {
				$lonclick .= ($lonclick ? ' ' : '').NApp::arequest()->Prepare($lonclick_scr);
			}//if(isset($this->run_oninit_func) && is_numeric($this->run_oninit_func))
		}//if(strlen($this->onclick_str) && ($this->data_onclick!==TRUE || $this->disabled!==TRUE))
		if(strlen(trim($lonclick))) {
			if(strlen($this->confirm_text)) {
				$lactions['onclick'] = ($this->data_onclick===TRUE ? 'data-' : '').'onclick="var cCB=function(){'.trim($lonclick).'}; ShowConfirmDialog(\''.$this->confirm_text.'\',cCB,false,{title:\''.\Translate::Get('title_confirm').'\',ok:\''.\Translate::Get('button_ok').'\',cancel:\''.\Translate::Get('button_cancel').'\'});"';
			} else {
				$lactions['onclick'] = ($this->data_onclick===TRUE ? 'data-' : '').'onclick="'.trim($lonclick).'"';
			}//if(strlen($this->confirm_text))
		}//if(strlen(trim($lonclick)))
		// OnChange
		$lonchange = (isset($lactions['onchange']) && strlen($lactions['onchange']) ? $lactions['onchange'] : '').((strlen($this->onchange) && ($this->data_onchange!==TRUE || $this->disabled!==TRUE)) ? ' '.trim(trim($this->onchange),';').';' : '');
		if(strlen($this->onchange_str) && ($this->data_onchange!==TRUE || $this->disabled!==TRUE)) {
			if(strpos($this->onchange_str,'#action_params#')!==FALSE) {
				$act_params = '';
				if(is_array($this->action_params) && count($this->action_params)) {
					foreach($this->action_params as $pk=>$pv) {
						$act_params .= ($act_params ? '~' : '')."'{$pk}'|'{$pv}'";
					}//END foreach
				}//if(is_array($this->action_params) && count($this->action_params))
				$onchange_str = str_replace('#action_params#',$act_params,$this->onchange_str);
			} else {
				$onchange_str = $this->onchange_str;
			}//if(strpos($this->onclick_str,'#action_params#')!==FALSE)
			$lonchange .= ($lonchange ? ' ' : '').NApp::arequest()->Prepare($onchange_str);
		}//if(strlen($this->onchange_str) && ($this->data_onchange!==TRUE || $this->disabled!==TRUE))
		if(strlen(trim($lonchange))) {
			if(strlen($this->confirm_text)) {
				$lactions['onchange'] = ($this->data_onclick===TRUE ? 'data-' : '').'onchange="var cCB=function(){'.trim($lonchange).'}; ShowConfirmDialog(\''.$this->confirm_text.'\',cCB,false,{title:\''.\Translate::Get('title_confirm').'\',ok:\''.\Translate::Get('button_ok').'\',cancel:\''.\Translate::Get('button_cancel').'\'});"';
			} else {
				$lactions['onchange'] = ($this->data_onclick===TRUE ? 'data-' : '').'onchange="'.trim($lonchange).'"';
			}//if(strlen($this->confirm_text))
		}//if(strlen(trim($lonchange)))
		// OnKeyPress
		$lonkeypress = (isset($lactions['onkeypress']) && strlen($lactions['onkeypress']) ? $lactions['onkeypress'] : '').((strlen($this->onkeypress) && ($this->data_onkeypress!==TRUE || $this->disabled!==TRUE)) ? ' '.trim(trim($this->onkeypress),';').';' : '');
		if(strlen($this->onkeypress_str) && ($this->data_onkeypress!==TRUE || $this->disabled!==TRUE)) {
			if(strpos($this->onkeypress_str,'#action_params#')!==FALSE) {
				$act_params = '';
				if(is_array($this->action_params) && count($this->action_params)) {
					foreach($this->action_params as $pk=>$pv) {
						$act_params .= ($act_params ? '~' : '')."'{$pk}'|'{$pv}'";
					}//END foreach
				}//if(is_array($this->action_params) && count($this->action_params))
				$onkeypress_str = str_replace('#action_params#',$act_params,$this->onkeypress_str);
			} else {
				$onkeypress_str = $this->onkeypress_str;
			}//if(strpos($this->onclick_str,'#action_params#')!==FALSE)
			$lonkeypress .= ($lonkeypress ? ' ' : '').NApp::arequest()->Prepare($onkeypress_str);
		}//if(strlen($this->onkeypress_str) && ($this->data_onkeypress!==TRUE || $this->disabled!==TRUE))
		if(strlen(trim($lonkeypress))) {
			if(strlen($this->confirm_text)) {
				$lactions['onkeypress'] = ($this->data_onclick===TRUE ? 'data-' : '').'onkeypress="var cCB=function(){'.trim($lonkeypress).'}; ShowConfirmDialog(\''.$this->confirm_text.'\',cCB,false,{title:\''.\Translate::Get('title_confirm').'\',ok:\''.\Translate::Get('button_ok').'\',cancel:\''.\Translate::Get('button_cancel').'\'});"';
			} else {
				$lactions['onkeypress'] = ($this->data_onclick===TRUE ? 'data-' : '').'onkeypress="'.trim($lonkeypress).'"';
			}//if(strlen($this->confirm_text))
		}//if(strlen(trim($lonkeypress)))
		$lactions = implode(' ',$lactions).(strlen($extra) ? ' '.$extra : '');
		return $lactions;
	}//END protected function GetTagActions
	/**
	 * Convert Ncol width to standard
	 *
	 * @param  bool $bootstrap Flag indicating use of bootstrap grid (default FALSE)
	 * @return void
	 * @access protected
	 */
	protected function ProcessWidth($bootstrap = FALSE) {
		if($bootstrap) {
			if(strpos($this->labelwidth,'col')!==FALSE && strpos($this->width,'col')!==FALSE) {
				$llw = str_replace('col','',$this->labelwidth);
				$lw = str_replace('col','',$this->width);
				if(is_numeric($lw) && $lw>0 && $lw<=12) {
					$this->labelwidth = NULL;
					$this->width = NULL;
					$this->label_cwidth = (12 - $lw);
					$this->cwidth = $lw;
				} elseif(is_numeric($llw) && $llw>0 && $llw<12) {
					$this->labelwidth = NULL;
					$this->width = NULL;
					$this->cwidth = (12 - $llw);
					$this->label_cwidth = $llw;
				} else {
					$this->width = $lw;
					$this->labelwidth = $llw;
				}//if(is_numeric($lw) && $lw>0 && $lw<=12)
			} elseif(strpos($this->labelwidth,'col')!==FALSE) {
				$llw = str_replace('col','',$this->labelwidth);
				if(is_numeric($llw) && $llw>0 && $llw<12) {
					$this->labelwidth = NULL;
					$this->width = NULL;
					$this->cwidth = (12 - $llw);
					$this->label_cwidth = $llw;
				} else {
					$this->labelwidth = $llw;
				}//if(is_numeric($llw) && $llw>0 && $llw<12)
			} elseif(strpos($this->width,'col')!==FALSE) {
				$lw = str_replace('col','',$this->width);
				if(is_numeric($lw) && $lw>0 && $lw<=12) {
					$this->labelwidth = NULL;
					$this->width = NULL;
					$this->label_cwidth = (12 - $lw);
					$this->cwidth = $lw;
				} else {
					$this->width = $lw;
				}//if(is_numeric($lw) && $lw>0 && $lw<=12)
			}//if(strpos($this->labelwidth,'col')!==FALSE && strpos($this->width,'col')!==FALSE)
		} else {
			if(strpos($this->labelwidth,'col')!==FALSE && strpos($this->width,'col')!==FALSE) {
				$llw = str_replace('col','',$this->labelwidth);
				$lw = str_replace('col','',$this->width);
				if(is_numeric($lw) && $lw>0 && $lw<=12) {
					$this->labelwidth = round((12 - $lw) / 12 * 100,0).'%';
					$this->width = round($lw / 12 * 100,0).'%';
				} elseif(is_numeric($llw) && $llw>0 && $llw<12) {
					$this->width = round((12 - $llw) / 12 * 100,0).'%';
					$this->labelwidth = round($llw / 12 * 100,0).'%';
				} else {
					$this->width = $lw;
					$this->labelwidth = $llw;
				}//if(is_numeric($lw) && $lw>0 && $lw<=12)
			} elseif(strpos($this->labelwidth,'col')!==FALSE) {
				$llw = str_replace('col','',$this->labelwidth);
				if(is_numeric($llw) && $llw>0 && $llw<12) {
					$this->width = round((12 - $llw) / 12 * 100,0).'%';
					$this->labelwidth = round($llw / 12 * 100,0).'%';
				} else {
					$this->labelwidth = $llw;
				}//if(is_numeric($llw) && $llw>0 && $llw<12)
			} elseif(strpos($this->width,'col')!==FALSE) {
				$lw = str_replace('col','',$this->width);
				if(is_numeric($lw) && $lw>0 && $lw<=12) {
					$this->labelwidth = round((12 - $lw) / 12 * 100,0).'%';
					$this->width = round($lw / 12 * 100,0).'%';
				} else {
					$this->width = $lw;
				}//if(is_numeric($lw) && $lw>0 && $lw<=12)
			}//if(strpos($this->labelwidth,'col')!==FALSE && strpos($this->width,'col')!==FALSE)
		}//if($bootstrap)
	}//END protected function ProcessWidth
	/**
	 * Convert Ncol width to standard
	 *
	 * @return string Custom actions HTML string
	 * @access protected
	 */
	protected function ProcessCustomActions() {
		// NApp::_Dlog($this->custom_actions,'$this->custom_actions');
		if(!is_array($this->custom_actions) || !count($this->custom_actions)) { return NULL; }
		$result = '';
		foreach($this->custom_actions as $ca) {
			if(!is_array($ca) || !count($ca)) { continue; }
			$ca_params = get_array_value($ca,'params',[],'is_array');
			$ca_params['theme_type'] = $this->theme_type;
			$ca_params['size'] = $this->size;
			$ca_type = get_array_value($ca,'type','DivButton','is_notempty_string');
			if(!class_exists($ca_type)){ continue; }
			$ca_dright = get_array_value($ca,'dright','','is_string');
			if(strlen($ca_dright)) {
				$dright = Module::GetDRights($this->module,$this->method,$ca_dright);
				if($dright) { continue; }
			}//if(strlen($a_dright))
			$ca_command = get_array_value($ca,'command_string',NULL,'is_notempty_string');
			if($ca_command) {
				$ac_params = explode('}}',$ca_command);
				$ca_command = '';
				foreach($ac_params as $ce) {
					$ce_arr = explode('{{',$ce);
					if(count($ce_arr)>1) {
						$ca_command .= $ce_arr[0].get_array_value($ca,$ce_arr[1],'','true');
					}else{
						$ca_command .= $ce_arr[0];
					}//if(count($ce_arr)>1)
				}//END foreach
				$ca_params['onclick'] = NApp::arequest()->Prepare($ca_command,$this->loader);
			}//if($acommand)
			$ca_ctrl = new $ca_type($ca_params);
			if(get_array_value($ca,'clear_base_class',FALSE,'bool')){ $ca_ctrl->ClearBaseClass(); }
			$result .= $ca_ctrl->Show();
		}//END foreach
		// NApp::_Dlog($result,'custom_actions>$result');
		return $result;
	}//END protected function ProcessCustomActions
	/**
	 * description
	 *
	 * @param $tag
	 * @return void
	 * @access protected
	 */
	protected function SetContainer($tag) {
		$tag .= $this->ProcessCustomActions();
		$container_class = 'NETopes\Core\Controls\Container'.ucfirst($this->theme_type);
		$ctrl_container = new $container_class($this);
		$result = $ctrl_container->GetHtml($tag);
		return $result;
	}//END protected function SetContainer
	/**
	 * description
	 *
	 * @return void
	 * @access protected
	 * @abstract
	 */
	abstract protected function SetControl();
	/**
	 * description
	 *
	 * @return string
	 * @access public
	 */
	public function Show() {
		if($this->buffered){ return $this->output_buffer; }
		return $this->SetContainer($this->SetControl());
	}//END public function Show
	/**
	 * Clears the base class of the control
	 *
	 * @return void
	 * @access public
	 */
	public function ClearBaseClass() {
		$this->baseclass = '';
	}//END public function ClearBaseClass
	/**
	 * Check control conditions
	 *
	 * @param  array $conditions The conditions array
	 * @return bool Returns TRUE when all conditions are verified or FALSE otherwise
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function CheckConditions($conditions) {
		$result = FALSE;
		if(!is_array($conditions) || !count($conditions)) { return $result; }
		foreach($conditions as $cond) {
			$cond_field = get_array_value($cond,'field',NULL,'is_notempty_string');
			$cond_value = get_array_value($cond,'value',NULL,'isset');
			if(!$cond_field) { continue; }
			$cond_type = get_array_value($cond,'type','=','is_notempty_string');
			$validation = get_array_value($cond,'validation','','is_string');
			if($validation) {
				$lvalue = validate_param($this->{$cond_field},get_array_value($cond,'default_value',NULL,'isset'),$validation);
			} else {
				$lvalue = $this->{$cond_field};
			}//if($validation)
			try {
				switch($cond_type) {
					case '<':
						$result = $lvalue<$cond_value;
						break;
					case '>':
						$result = $lvalue>$cond_value;
						break;
					case '<=':
						$result = $lvalue<=$cond_value;
						break;
					case '>=':
						$result = $lvalue>=$cond_value;
						break;
					case '!=':
						$result = $lvalue!=$cond_value;
						break;
					case 'empty':
						$result = !$lvalue;
						break;
					case '!empty':
						$result = $lvalue;
						break;
					case 'in':
						$result = (is_array($cond_value) && in_array($lvalue,$cond_value));
						break;
					case 'notin':
						$result = !(is_array($cond_value) && in_array($lvalue,$cond_value));
						break;
					case '==':
					default:
						$result = $lvalue==$cond_value;
						break;
				}//END switch
			} catch(AppException $ne) {
				if(NApp::$debug) { throw $ne; }
				$result = FALSE;
			}//END try
			if(!$result) { break; }
		}//END forach
		return $result;
	}//END protected function CheckConditions
    /**
     * Generate parameters URL hash
     *
     * @param  array  $params An array of parameters
     * @param  bool   $encrypt Encrypt or not the parameters
     * @param  string $hash_separator Separator for the hash parameters
     * @param string  $epass
     * @return string Returns the computed hash
     * @access public
     * @static
     */
	 public static function GetUrlHash($params = [],$encrypt = TRUE,$hash_separator = '|',$epass = 'eUrlHash') {
		if(!is_array($params) || !count($params)) { return NULL; }
		$result = '';
		foreach($params as $v) { $result .= (strlen($result) ? $hash_separator : '').$v; }
		if(strlen($result) && $encrypt!==FALSE) { $result = GibberishAES::enc($result,$epass); }
		return rawurlencode($result);
	 }//END public static function GetUrlHash
   /**
	* Replace dynamic parameters
	*
	* @param  array $params The parameters array to be parsed
	* @param  object $row Data row object to be used for replacements
	* @param  bool  $recursive Flag indicating if the array should be parsed recursively
     * @param null    $params_prefix
     * @return array|string Return processed parameters array
	* @access public
	* @static
     * @throws \PAF\AppException
	*/
	public static function ReplaceDynamicParams($params,$row,$recursive = TRUE,$params_prefix = NULL) {
	    $lRow = is_object($row) ? $row : new VirtualEntity(is_array($row) ? $row : []);
		if(is_string($params)) {
			if(!strlen($params)) { return $params; }
			if(is_string($params_prefix) && strlen($params_prefix)) {
				$result = str_replace('{'.$params_prefix.'{','{{',$params);
			} else {
			    $result = $params;
			}//if(is_string($params_prefix) && strlen($params_prefix))
			$rv_arr = [];
			preg_match_all('/{{[^}]*}}/i',$result,$rv_arr);
			if(is_array($rv_arr[0])) {
				foreach($rv_arr[0] as $pfr) {
					if(strpos($result,$pfr)===FALSE) { continue; }
					$result = str_replace($pfr,addslashes($lRow->getProperty(trim($pfr,'{}'),NULL,'isset')),$result);
				}//END foreach
			}//if(is_array($rv_arr[0]))
			return $result;
		}//if(is_string($params))
		if(!is_array($params) || !count($params)) { return $params; }
		$result = [];
		foreach(array_keys($params) as $pk) {
			if(is_string($params[$pk]) || (is_array($params[$pk]) && ($recursive===TRUE || $recursive===1 || $recursive==='1'))) {
				$result[$pk] = self::ReplaceDynamicParams($params[$pk],$lRow,TRUE,$params_prefix);
			} else {
				$result[$pk] = $params[$pk];
			}//if(is_string($params[$pk]) || (is_array($params[$pk]) && ($recursive===TRUE || $recursive===1 || $recursive==='1')))
		}//END foreach
		return $result;
	}//END public static function ReplaceDynamicParams
	/**
	 * Check row conditions
	 *
	 * @param  object $row Data row object
	 * @param  array $conditions The conditions array
	 * @return bool Returns TRUE when all conditions are verified or FALSE otherwise
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function CheckRowConditions(&$row,$conditions) {
		$result = FALSE;
		if(!is_array($conditions) || !count($conditions) || !is_object($row)) { return $result; }
		foreach($conditions as $cond) {
			$cond_field = get_array_value($cond,'field',NULL,'is_notempty_string');
			$cond_value = get_array_value($cond,'value',NULL,'isset');
			$cond_type = get_array_value($cond,'type','=','is_notempty_string');
			try {
				switch($cond_type) {
					case '<':
						$result = $row->getProperty($cond_field)<$cond_value;
						break;
					case '>':
						$result = $row->getProperty($cond_field)>$cond_value;
						break;
					case '<=':
						$result = $row->getProperty($cond_field)<=$cond_value;
						break;
					case '>=':
						$result = $row->getProperty($cond_field)>=$cond_value;
						break;
					case '!=':
						$result = $row->getProperty($cond_field)!=$cond_value;
						break;
					case 'empty':
						$result = !$row->getProperty($cond_field);
						break;
					case '!empty':
						$result = $row->getProperty($cond_field);
						break;
					case 'in':
						$result = (is_array($cond_value) && in_array($row->getProperty($cond_field),$cond_value));
						break;
					case 'notin':
						$result = !(is_array($cond_value) && in_array($row->getProperty($cond_field),$cond_value));
						break;
					case 'fileexists':
						$result = is_file($cond_value);
						break;
					case '==':
					default:
						$result = $row->getProperty($cond_field)==$cond_value;
						break;
				}//END switch
			} catch(AppException $ne) {
				if(NApp::$debug) { throw $ne; }
				$result = FALSE;
			}//END try
			if(!$result) { break; }
		}//END forach
		return $result;
	}//END public static function CheckRowConditions
	/**
	 * Gets the record from the database and sets the values in the tab array
	 *
	 * @param  array $params Parameters array
	 * @return array Returns processed tab array
	 * @access public
	 * @throws \PAF\AppException
	 * @static
	 */
	public static function GetTranslationData($params = []) {
		if(!is_array($params) || !count($params)) { return NULL; }
		$ds_name = get_array_value($params,'ds_class','','is_string');
		$ds_method = get_array_value($params,'ds_method','','is_string');
		if(!strlen($ds_name) || !strlen($ds_method) || !DataProvider::MethodExists($ds_name,$ds_method)) { return NULL; }
		$record_key = get_array_value($params,'record_key',0,'is_integer');
		$record_key_field = get_array_value($params,'record_key_field','language_id','is_notempty_string');
		$ds_params = get_array_value($params,'ds_params',[],'is_array');
		$ds_params[$record_key_field] = $record_key;
		$ds_key = get_array_value($params,'ds_key','','is_string');
		if(strlen($ds_key)) {
			$result = DataProvider::GetKeyValueArray($ds_name,$ds_method,$ds_params,['keyfield'=>$ds_key]);
		} else {
			$result = DataProvider::GetArray($ds_name,$ds_method,$ds_params);
		}//if(strlen($ds_key))
		return $result;
	}//END public static function GetTranslationData
}//END abstract class Control
?>