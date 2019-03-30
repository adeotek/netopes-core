<?php
/**
 * short description
 * description
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.2.3
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\ModulesProvider;
use NETopes\Core\Data\DataProvider;
use NApp;
/**
 * ClassName description
 * description
 * @package  NETopes\Controls
 */
class TabControl {
    /**
     * AJAX content constant
     */
    const AJAX_CONTENT = 'ajax';
    /**
     * Control content constant
     */
    const CONTROL_CONTENT = 'control';
    /**
     * File content constant
     */
    const FILE_CONTENT = 'file';
    /**
     * Module content contant
     */
    const MODULE_CONTENT = 'module';
    /**
     * Control content constant
     */
    const STRING_CONTENT = 'string';
	/**
	 * @var    bool Generate content on construct and cache it
	 */
    public $cached = FALSE;
	/**
	 * @var    string TabControl table id
	 */
	public $tag_id = NULL;
	/**
	 * @var    string TabControl response target id
	 */
	public $response_target = NULL;
	/**
	 * @var    string TabControl width
	 */
	public $width = NULL;
	/**
	 * @var    string|null TabControl additional class
	 */
	public $class = NULL;
	/**
	 * @var    array tabs descriptor array
	 */
	public $tabs = [];
	/**
	 * @var    string|null TabControl mode (null/tabs=standard, accordion=accordion)
	 */
	public $mode = NULL;
	/**
	 * @var    string Basic form base class
	 */
	protected $base_class = NULL;
	/**
	 * @var    string Output (resulting html) buffer
	 */
	protected $output_buffer = NULL;
	/**
	 * @var    array Javascript code execution queue
	 */
	protected $js_scripts = [];
	/**
	 * BasicForm class constructor method
	 * @param  array $params Parameters array
	 * @throws \NETopes\Core\AppException
	 * @return void
	 */
	public function __construct($params = NULL) {
		$this->base_class = get_array_value($params,'clear_base_class',FALSE,'bool') ? '' : 'cls'.get_class_basename($this);
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) {
				if(property_exists($this,$k)) { $this->$k = $v; }
			}//foreach ($params as $k=>$v)
		}//if(is_array($params) && count($params))
		if($this->cached) { $this->output_buffer = $this->SetControl(); }
	}//END public function __construct
    /**
     * Gets the content for a tab
     * @param  array      $tab Tab parameters array
     * @param string|null $contentType
     * @return string Returns content HTML for one tab
     * @throws \NETopes\Core\AppException
     */
	protected function SetContent(array $tab,?string &$contentType = NULL): string {
		$result = '';
		$ctResult = '';
		$ct_data = '';
		$contentType = get_array_value($tab,'content_type','content','is_notempty_string');
		switch($contentType) {
            case static::FILE_CONTENT:
				$tContent = get_array_value($tab,'content',NULL,'is_notempty_string');
				if($tContent && file_exists($tContent)) {
					ob_start();
					$data = get_array_value($tab,'data',NULL,'is_notempty_array');
					require($tContent);
					$ctResult .= ob_get_contents();
					ob_end_clean();
				}//if($tContent && file_exists($tContent))
				break;
			case static::AJAX_CONTENT:
				$tContent = get_array_value($tab,'content',NULL,'is_notempty_string');
				if(!$tContent) { $ctResult .= '&nbsp;'; break; }
				$reload_onchange = get_array_value($tab,'reload_onchange',FALSE,'bool');
				$ct_data .= $reload_onchange ? ' data-reload="1"' : '';
				$tContent = str_replace('{{t_uid}}',$tab['t_uid'],$tContent);
				$tContent = str_replace('{{t_name}}',$tab['t_name'],$tContent);
				$tContent = str_replace('{{t_target}}',$this->tag_id.'-'.$tab['t_uid'],$tContent);
				$tscript = get_array_value($tab,'load_script','','is_string');
                $js_command=NApp::Ajax()->LegacyPrepare($tContent,1,NULL,$tscript);
				$ct_data .= $reload_onchange ? ' data-reload-action="'.$js_command.'"' : '';
				if(get_array_value($tab,'autoload',TRUE,'bool')) {
					NApp::AddJsScript($js_command);
				}//if(get_array_value($tab,'autoload',TRUE,'bool'))
				break;
			case static::CONTROL_CONTENT:
				$tContent = get_array_value($tab,'content',[],'is_array');
				$c_type = get_array_value($tContent,'control_type',NULL,'is_notempty_string');
				$c_type = $c_type ? '\NETopes\Core\Controls\\'.$c_type : $c_type;
				if(!is_array($tContent) || !count($tContent) || !$c_type || !class_exists($c_type)) {
					NApp::Elog('Control class ['.$c_type.'] not found!');
					continue;
				}//if(!is_array($tContent) || !count($tContent) || !$c_type || !class_exists($c_type))
				$c_params = get_array_value($tContent,'control_params',[],'is_array');
				$tt_params = get_array_value($tContent,'template_params',[],'is_array');
				foreach($tt_params as $ttkey=>$ttparam) {
					if(array_key_exists($ttkey,$c_params)) { $c_params[$ttkey] = $ttparam; }
				}//END foreach
				$control = new $c_type($c_params);
				if(get_array_value($col,'clear_base_class',FALSE,'bool')) { $control->ClearBaseClass(); }
				$ctResult .= $control->Show();
                if(method_exists($control,'GetJsScript')) {
                    $jsScript = $control->GetJsScript();
                    if(strlen($jsScript)) { $this->js_scripts[] = $jsScript; }
                }//if(method_exists($control,'GetJsScript'))
				break;
			case self::MODULE_CONTENT:
			    $tContent = get_array_value($tab,'content',NULL,'is_array');
                $module = get_array_value($tContent,'module','','is_string');
                $method = get_array_value($tContent,'method','','is_string');
                if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method)) {
                    NApp::Wlog('Invalid module content parameters [tab:'.get_array_value($tab,'uid','-','is_string').':'.print_r($tContent,1).']!');
                    continue;
                }//if(!strlen($module) || !strlen($method) || !ModulesProvider::ModuleMethodExists($module,$method))
                $customParams = get_array_value($tContent,'params',[],'is_array');
                $customParams = ControlsHelpers::ReplaceDynamicParams($customParams,[
                    't_uid'=>$tab['t_uid'],
                    't_name'=>$tab['t_name'],
                    't_target'=>$this->tag_id.'-'.$tab['t_uid'],
                ]);
                ob_start();
                ModulesProvider::Exec($module,$method,$customParams);
                $ctResult = ob_get_clean();
                break;
			case static::STRING_CONTENT:
			default:
				$ctResult .= get_array_value($tab,'content','&nbsp;','is_notempty_string');
				break;
		}//END switch
		$tabClass = get_array_param($tab,'class','','is_string');
        $result .= "\t".'<div id="'.$this->tag_id.'-'.$tab['t_uid'].'"'.$ct_data.(strlen($tabClass) ? ' class="'.$tabClass.'"' : '').'>'."\n";
		$result .= $ctResult;
		$result .= "\t".'</div>'."\n";
		return $result;
	}//END protected function SetContent
	/**
	 * Replaces a string with another in a multilevel array (recursively)
	 * @param  array $params An array of parameters
	 * @param  mixed $search String to be replaced
	 * @param  mixed $replace String replacement value
	 * @param bool   $regex
	 * @return array Returns processed parameters array
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
	 * @param  array $tab Tab parameters array
	 * @return array Returns processed tab array
	 * @throws \NETopes\Core\AppException
	 */
	protected function GetTabData($tab) {
		if(!is_array($tab)) { return $tab; }
		$result = $tab;
		$ds_class = get_array_value($tab,'ds_class','','is_string');
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
	 * @return string|null
	 * @throws \NETopes\Core\AppException
	 */
	protected function GetTabs(): ?string {
		// Set Tab header
		$result = "\t".'<ul>'."\n";
		$ltabs = [];
		foreach($this->tabs as $tab) {
			if(!is_array($tab) || !count($tab)) { continue; }
			$cssClass = get_array_value($tab, 'css_class', NULL, '?is_string');
			switch(get_array_value($tab,'type','fixed','is_notempty_string')) {
				case 'template':
					$tCollection = get_array_value($tab,'source_array',[],'is_array');
					unset($tab['source_array']);
					foreach($tCollection as $ctab) {
						$ct_uid = get_array_value($ctab,get_array_value($tab,'uid_field','id','is_notempty_string'),'','isset');
						$ct_name = get_array_value($ctab,get_array_value($tab,'name_field','name','is_notempty_string'),'','is_string');
						$result .= "\t\t".'<li'.(strlen($cssClass) ? ' class="'.$cssClass.'"' : '').'><a href="#'.$this->tag_id.'-'.$ct_uid.'">'.$ct_name.'</a></li>'."\n";
						$ltabs[] = array_merge($tab,array('t_type'=>'template','t_name'=>$ct_name,'t_uid'=>$ct_uid,'t_row'=>$ctab));
					}//END foreach
					break;
				case 'fixed':
					$ct_uid = get_array_value($tab,'uid','def','isset');
					$ct_name = get_array_value($tab,'name','','is_string');
					$result .= "\t\t".'<li'.(strlen($cssClass) ? ' class="'.$cssClass.'"' : '').'><a href="#'.$this->tag_id.'-'.$ct_uid.'">'.$ct_name.'</a></li>'."\n";
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
		$thtype = get_array_value($tab,'height_type','content','is_notempty_string');
		$jsScript = <<<JS
            $('#{$this->tag_id}').tabs({
				heightStyle: '{$thtype}',
				activate: function(e,ui) {
					let tcr = $(ui.newPanel).attr('data-reload');
					if(!tcr && tcr!=1) { return true; }
					let tcr_action = $(ui.newPanel).attr('data-reload-action');
					if(tcr_action.length>0) { eval(tcr_action); }
	            }
			});
JS;
		NApp::AddJsScript($jsScript);
		return $result;
	}//END private function GetTabs
	/**
	 * Sets the output buffer value
	 * @return string|null
	 * @throws \NETopes\Core\AppException
	 */
	protected function GetAccordion(): ?string {
        $result = '<div id="'.$this->tag_id.'_accordion" class="clsAccordion clsControlContainer">'."\n";
        foreach($this->tabs as $tab) {
			if(!is_array($tab) || !count($tab)) { continue; }
            switch(get_array_value($tab,'type','fixed','is_notempty_string')) {
				case 'template':
					$tCollection = get_array_value($tab,'source_array',[],'is_array');
					unset($tab['source_array']);
					foreach($tCollection as $cTab) {
					    $tUid = get_array_value($cTab,get_array_value($tab,'uid_field','id','is_notempty_string'),'','isset');
                        $tName = get_array_value($cTab,get_array_value($tab,'name_field','name','is_notempty_string'),'','is_string');
                        $result .= "\t".'<h3 data-for="'.$this->tag_id.'-'.$tUid.'">'.$tName.'</h3>'."\n";
                        $tTab = $this->ProcessParamsArray($cTab,'{{t_uid}}',$tUid);
                        $tTab = $this->GetTabData($tTab);
                        $tTab = array_merge($tTab,['t_type'=>'fixed','t_name'=>$tName,'t_uid'=>$tUid]);
                        $contentType = NULL;
                        $result .= $this->SetContent($tTab,$contentType);
					}//END foreach
					break;
				case 'fixed':
					$tUid = get_array_value($tab,'uid','def','isset');
					$tName = get_array_value($tab,'name','','is_string');
                    $result .= "\t".'<h3 data-for="'.$this->tag_id.'-'.$tUid.'">'.$tName.'</h3>'."\n";
                    $tTab = $this->ProcessParamsArray($tab,'{{t_uid}}',$tUid);
                    $tTab = array_merge($tTab,['t_type'=>'fixed','t_name'=>$tName,'t_uid'=>$tUid]);
                    $contentType = NULL;
                    $result .= $this->SetContent($tTab,$contentType);
					break;
			}//END switch
        }//END foreach
        $result .= '</div>'."\n";
        $thtype = get_array_value($tab,'height_type','content','is_notempty_string');
        $jsScript = <<<JS
            $('#{$this->tag_id}_accordion').accordion({
                heightStyle: '{$thtype}',
                create: function(e,ui) {
                    if(ui.panel.length>0) {
                        let tcr = $(ui.panel).attr('data-reload');
                        if(!tcr && tcr!=1) { return true; }
                        let tcr_action = $(ui.panel).attr('data-reload-action');
                        if(tcr_action.length>0) { eval(tcr_action); }
                    }
                },
				activate: function(e,ui) {
					let tcr = $(ui.newPanel).attr('data-reload');
					if(!tcr && tcr!=1) { return true; }
					let tcr_action = $(ui.newPanel).attr('data-reload-action');
					if(tcr_action.length>0) { eval(tcr_action); }
	            }
			});
JS;
		NApp::AddJsScript($jsScript);
		return $result;
	}//END private function GetAccordion
	/**
	 * Sets the output buffer value
	 * @return string|null
	 * @throws \NETopes\Core\AppException
	 */
	protected function SetControl(): ?string {
		if(!strlen($this->tag_id) || !is_array($this->tabs) || !count($this->tabs)) { return NULL; }
		$lclass = trim($this->base_class.' '.$this->class);
		$result = '<div id="'.$this->tag_id.'" class="'.$lclass.'">'."\n";
		switch(strtolower($this->mode)) {
            case 'accordion':
                $result .= $this->GetAccordion();
                break;
            case 'tabs':
            default:
                $result .= $this->GetTabs();
                break;
		}//END switch
		$result .= '</div>'."\n";
		return $result;
	}//END private function SetControl
	/**
     * @return array
     */
    public function GetJsScripts(): array {
        return $this->js_scripts;
    }//END public function GetJsScripts
    /**
     * @return null|string
     */
    public function GetJsScript(): ?string {
        if(!count($this->js_scripts)) { return NULL; }
		$result = '';
		foreach($this->js_scripts as $js) {
			$js = trim($js);
			$result .= (strlen($result) ? "\n" : '').(substr($js,-1)=='}' ? $js : rtrim($js,';').';');
		}//END foreach
		return $result;
    }//END public function GetJsScript
	/**
	 * Gets the output buffer content
	 * @return string Returns the output buffer content (html)
     * @throws \NETopes\Core\AppException
	 */
	public function Show() {
	    if($this->cached) { return $this->output_buffer; }
		return $this->SetControl();
	}//END public function Show
}//END class TabControl