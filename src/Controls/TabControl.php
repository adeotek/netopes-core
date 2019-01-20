<?php
/**
 * short description
 *
 * description
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\Data\DataProvider;
use NApp;
/**
 * ClassName description
 *
 * description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class TabControl {
	/**
	 * @var    string BasicForm table id
	 * @access public
	 */
	public $tag_id = NULL;
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
	 * @var    string BasicForm additional class
	 * @access public
	 */
	public $class = NULL;
	/**
	 * @var    array tabs descriptor array
	 * @access public
	 */
	public $tabs = [];
	/**
	 * @var    string Basic form base class
	 * @access protected
	 */
	protected $base_class = NULL;
	/**
	 * @var    string Output (resulting html) buffer
	 * @access protected
	 */
	protected $output_buffer = NULL;
	/**
	 * BasicForm class constructor method
	 *
	 * @param  array $params Parameters array
	 * @throws \NETopes\Core\AppException
	 * @return void
	 * @access public
	 */
	public function __construct($params = NULL) {
		$this->base_class = get_array_value($params,'clear_base_class',FALSE,'bool') ? '' : 'cls'.get_class_basename($this);
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) {
				if(property_exists($this,$k)) { $this->$k = $v; }
			}//foreach ($params as $k=>$v)
		}//if(is_array($params) && count($params))
		$this->output_buffer = $this->SetControl();
	}//END public function __construct
	/**
	 * Gets the content for a tab
	 *
	 * @param  array $tab Tab parameters array
	 * @return string Returns content HTML for one tab
	 * @access protected
	 */
	protected function SetContent($tab) {
		$result = '';
		$ct_result = '';
		$ct_data = '';
		switch(get_array_value($tab,'content_type','content','is_notempty_string')) {
		    case 'file':
				$tcontent = get_array_value($tab,'content',NULL,'is_notempty_string');
				if($tcontent && file_exists($tcontent)) {
					ob_start();
					$data = get_array_value($tab,'data',NULL,'is_notempty_array');
					require($tcontent);
					$ct_result .= ob_get_contents();
					ob_end_clean();
				}//if($tcontent && file_exists($tcontent))
				break;
			case 'ajax':
				$tcontent = get_array_value($tab,'content',NULL,'is_notempty_string');
				if(!$tcontent) { $ct_result .= '&nbsp;'; break; }
				$reload_onchange = get_array_value($tab,'reload_onchange',FALSE,'bool');
				$ct_data .= $reload_onchange ? ' data-reload="1"' : '';
				$tcontent = str_replace('{{t_uid}}',$tab['t_uid'],$tcontent);
				$tcontent = str_replace('{{t_name}}',$tab['t_name'],$tcontent);
				$tcontent = str_replace('{{t_target}}',$this->tag_id.'-'.$tab['t_uid'],$tcontent);
				$tscript = get_array_value($tab,'load_script','','is_string');
				$js_command = NApp::Ajax()->Prepare($tcontent,1,NULL,$tscript);
				$ct_data .= $reload_onchange ? ' data-reload-action="'.$js_command.'"' : '';
				if(get_array_value($tab,'autoload',TRUE,'bool')) {
					NApp::AddJsScript($js_command);
				}//if(get_array_value($tab,'autoload',TRUE,'bool'))
				break;
			case 'control':
				$tcontent = get_array_value($tab,'content',[],'is_array');
				$c_type = get_array_value($tcontent,'control_type',NULL,'is_notempty_string');
				$c_type = $c_type ? '\NETopes\Core\Controls\\'.$c_type : $c_type;
				if(!is_array($tcontent) || !count($tcontent) || !$c_type || !class_exists($c_type)) {
					NApp::Elog('Control class ['.$c_type.'] not found!');
					continue;
				}//if(!is_array($tcontent) || !count($tcontent) || !$c_type || !class_exists($c_type))
				$c_params = get_array_value($tcontent,'control_params',[],'is_array');
				$tt_params = get_array_value($tcontent,'template_params',[],'is_array');
				foreach($tt_params as $ttkey=>$ttparam) {
					if(array_key_exists($ttkey,$c_params)) { $c_params[$ttkey] = $ttparam; }
				}//END foreach
				$control = new $c_type($c_params);
				if(get_array_value($col,'clear_base_class',FALSE,'bool')){ $control->ClearBaseClass(); }
				$ct_result .= $control->Show();
				break;
			case 'content':
			default:
				$ct_result .= get_array_value($tab,'content','&nbsp;','is_notempty_string');
				break;
		}//END switch
		$result .= "\t".'<div id="'.$this->tag_id.'-'.$tab['t_uid'].'"'.$ct_data.'>'."\n";
		$result .= $ct_result;
		$result .= "\t".'</div>'."\n";
		return $result;
	}//END protected function SetContent
	/**
	 * Replaces a string with another in a multilevel array (recursively)
	 *
	 * @param  array $params An array of parameters
	 * @param  mixed $search String to be replaced
	 * @param  mixed $replace String replacement value
	 * @param bool   $regex
	 * @return array Returns processed parameters array
	 * @access protected
	 */
	protected function ProcessParamsArray($params,$search,$replace,$regex = FALSE) {
		if(!strlen($search) || (!is_string($replace) && !is_numeric($replace))) { return $params; }
		if(is_string($params)) {
			if($regex) {
				return preg_replace($search,$replace,$params);
			} else {
				return str_replace($search,$replace,$params);
			}//if($regex)
		}//if(is_string($params))
		if(!is_array($params)) { return $params; }
		$result = [];
		foreach($params as $k=>$v) { $result[$k] = $this->ProcessParamsArray($v,$search,$replace,$regex); }
		return $result;
	}//END protected function ProcessParamsArray
	/**
	 * Gets the record from the database and sets the values in the tab array
	 *
	 * @param  array $tab Tab parameters array
	 * @return array Returns processed tab array
	 * @access protected
	 * @throws \NETopes\Core\AppException
	 */
	protected function GetTabData($tab) {
		if(!is_array($tab)) { return $tab; }
		$result = $tab;
		$ds_class = get_array_value($tab,'data_source','','is_string');
		$ds_method = get_array_value($tab,'ds_method','','is_string');
		if(strlen($ds_class) && strlen($ds_method)) {
			$ds_params = get_array_value($tab,'ds_params',[],'is_array');
			$ds_key = get_array_value($tab,'ds_key','','is_string');
			if(strlen($ds_key)) {
				$ds_field = get_array_value($tab,'ds_field','','is_string');
				if(strlen($ds_field)) {
					$ds_items = DataProvider::GetKeyValueArray($ds_class,$ds_method,$ds_params,array('keyfield'=>$ds_key));
					//NApp::Dlog($ds_items,'$ds_items1');
					if(is_array($ds_items) && count($ds_items)) {
						foreach($ds_items as $k=>$v) {
							$result = $this->ProcessParamsArray($result,'{{'.strtolower($k).'}}',get_array_value($v,$ds_field,'','isset'));
						}//END foreach
					}//if(is_array($ds_items) && count($ds_items))
				}//if(strlen($da_field))
			} else {
				$ds_items = DataProvider::GetArray($ds_class,$ds_method,$ds_params);
				//NApp::Dlog($ds_items,'$ds_items2');
				if(is_array($ds_items) && count($ds_items)) {
					foreach($ds_items as $k=>$v) {
						$result = $this->ProcessParamsArray($result,'{{'.strtolower($k).'}}',$v);
					}//END foreach
				}//if(is_array($ds_items) && count($ds_items))
			}//if(strlen($da_key))
		}//if(strlen($ds_class) && strlen($ds_method))
		// NApp::Dlog($result['content']['control_params'],'control_params>B');
		$result = $this->ProcessParamsArray($result,'/{{.*}}/','',TRUE);
		// NApp::Dlog($result['content']['control_params'],'control_params>A');
		return $result;
	}//END protected function GetTabData
	/**
	 * Sets the output buffer value
	 *
	 * @return string|null
	 * @access protected
	 * @throws \NETopes\Core\AppException
	 */
	protected function SetControl(): ?string {
		if(!strlen($this->tag_id) || !is_array($this->tabs) || !count($this->tabs)) { return NULL; }
		$lclass = trim($this->base_class.' '.$this->class);
		$result = '<div id="'.$this->tag_id.'" class="'.$lclass.'">'."\n";
		// Set Tab header
		$result .= "\t".'<ul>'."\n";
		$ltabs = [];
		foreach($this->tabs as $tab) {
			if(!is_array($tab) || !count($tab)) { continue; }
			switch(get_array_value($tab,'type','fixed','is_notempty_string')) {
				case 'template':
					$tcollection = get_array_value($tab,'source_array',[],'is_array');
					unset($tab['source_array']);
					foreach($tcollection as $ctab) {
						$ct_uid = get_array_value($ctab,get_array_value($tab,'uid_field','id','is_notempty_string'),'','isset');
						$ct_name = get_array_value($ctab,get_array_value($tab,'name_field','name','is_notempty_string'),'','is_string');
						$result .= "\t\t".'<li><a href="#'.$this->tag_id.'-'.$ct_uid.'">'.$ct_name.'</a></li>'."\n";
						$ltabs[] = array_merge($tab,array('t_type'=>'template','t_name'=>$ct_name,'t_uid'=>$ct_uid,'t_row'=>$ctab));
					}//END foreach
					break;
				case 'fixed':
					$ct_uid = get_array_value($tab,'uid','def','isset');
					$ct_name = get_array_value($tab,'name','','is_string');
					$result .= "\t\t".'<li><a href="#'.$this->tag_id.'-'.$ct_uid.'">'.$ct_name.'</a></li>'."\n";
					$ltabs[] = array_merge($tab,array('t_type'=>'fixed','t_name'=>$ct_name,'t_uid'=>$ct_uid));
					break;
			}//END switch
		}//END foreach
		$result .= "\t".'</ul>'."\n";
		// END Set Tab header
		foreach($ltabs as $tab) {
			switch(get_array_value($tab,'t_type','','is_string')) {
				case 'template':
					$tuid = get_array_value($tab,'t_uid',NULL,'is_string');
					$ttab = $this->ProcessParamsArray($tab,'{{t_uid}}',$tuid);
					$ttab = $this->GetTabData($ttab);
					$result .= $this->SetContent($ttab);
					break;
				case 'fixed':
				default:
					$result .= $this->SetContent($tab);
					break;
			}//END switch
		}//END foreach
		$result .= '</div>'."\n";
		$thtype = get_array_value($tab,'height_type','content','is_notempty_string');
		$js_script = "
			$('#{$this->tag_id}').tabs({
				heightStyle: '{$thtype}',
				activate: function(event,ui) {
					var tcr = $(ui.newPanel).attr('data-reload');
					if(!tcr && tcr!=1) { return false; }
					var tcr_action = $(ui.newPanel).attr('data-reload-action');
					if(tcr_action.length>0) { eval(tcr_action); }
	            }
			});
		";
		NApp::AddJsScript($js_script);
		return $result;
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
}//END class TabControl