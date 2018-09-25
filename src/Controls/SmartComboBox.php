<?php
/**
 * ComboBox control class file
 *
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.7.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\Data\DataProvider;
use NApp;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSource;
use NETopes\Core\Data\VirtualEntity;
use PAF\AppConfig;
use PAF\AppException;
/**
 * ComboBox control
 *
 * Standard ComboBox control
 *
 * @package  NETopes\Controls
 * @access   public
 */
class SmartComboBox extends Control {
	/**
	 * @var null|string
	 */
	public $load_type = NULL;
	/**
	 * @var null
	 */
	public $data_source = NULL;
	/**
	 * @var null
	 */
	public $extra_items = NULL;
	/**
	 * @var null
	 */
	public $template_selection = NULL;
	/**
	 * SmartComboBox constructor.
	 *
	 * @param null $params
	 */
	public function __construct($params = NULL) {
		parent::__construct($params);
		if(strlen($this->placeholder)) {
			$this->cbo_placeholder = $this->placeholder;
			$this->placeholder = NULL;
		} else {
			$this->cbo_placeholder = NULL;
		}//if(strlen($this->placeholder))
		$this->onenter = NULL;
		$this->onenterbutton = NULL;
		if(!strlen($this->tagid)) { $this->tagid = $this->uid; }
		if(!is_string($this->valfield) || !strlen($this->valfield)) { $this->valfield = 'id'; }
		if(is_null($this->displayfield) || $this->displayfield=='') { $this->displayfield = 'name'; }
		// one of the values: value/database/ajax
		if(!strlen($this->load_type)) { $this->load_type = 'value'; }
		if(!is_array($this->option_data)) {
		    if(is_string($this->option_data) && strlen($this->option_data)) {
		        $this->option_data = [$this->option_data];
		    } else {
		        $this->option_data = [];
		    }//if(is_string($this->option_data) && strlen($this->option_data))
		}//if(!is_array($this->option_data))
	}//END public function __construct
	/**
	 * @param $item
	 * @return null|string
     * @throws \PAF\AppException
	 */
	protected function GetDisplayFieldValue($item): ?string {
	    if(!is_object($item) && !is_array($item)) { return NULL; }
	    if(!is_object($item)) { $item = new VirtualEntity($item); }
		$ldisplayvalue = '';
		if(is_array($this->displayfield)) {
			foreach($this->displayfield as $dk=>$dv) {
				if(is_array($dv)) {
					$ov_items = get_array_value($dv,'items',[],'is_notempty_array');
					$ov_value = get_array_value($dv,'value','','is_string');
					$ov_mask = get_array_value($dv,'mask','','is_string');
					$ltext = $item->getProperty($dk,'N/A','is_string');
					$ldisplayvalue .= strlen($ov_mask)>0 ? str_replace('~',get_array_value($ov_items[$ltext],$ov_value,$ltext,'isset'),$ov_mask) : get_array_value($ov_items[$ltext],$ov_value,$ltext,'isset');
				} else {
				    $ltext = $item->getProperty($dk,'N/A','is_string');
					$ldisplayvalue .= strlen($dv)>0 ? str_replace('~',$ltext,$dv) : $ltext;
				}//if(is_array($dv))
			}//foreach ($this->displayfield as $dk=>$dv)
		} else {
		    $ltext = $item->getProperty($this->displayfield,'N/A','is_string');
			$ldisplayvalue = $this->withtranslate===TRUE ? \Translate::Get($this->translate_prefix.$ltext) : $ltext;
		}//if(is_array($this->displayfield))
		return html_entity_decode($ldisplayvalue);
	}//END protected function GetDisplayFieldValue
	/**
	 * @return string|null
	 * @throws \PAF\AppException
	 */
	protected function SetControl(): ?string {
		$this->ProcessActions();
		$js_script = "\t\t({\n";
		$raw_class = $this->GetTagClass(NULL,TRUE);
		if(strlen($raw_class)) { $js_script .= "\t\t\tcontainerCssClass: '{$raw_class}',\n"; }
		if(is_string($this->dropdown_class) && strlen($this->dropdown_class)) {
			$js_script .= "\t\t\tdropdownCssClass: '{$this->dropdown_class}',\n";
		} elseif(is_string($this->size) && strlen($this->size)) {
			$js_script .= "\t\t\tdropdownCssClass: 'size-{$this->size}',\n";
		}//if(is_string($this->dropdown_class) && strlen($this->dropdown_class))
		if(strlen($this->cbo_placeholder)) { $js_script .= "\t\t\tplaceholder: '{$this->cbo_placeholder}',\n"; }
		if($this->load_type=='ajax' || $this->allow_clear) { $js_script .= "\t\t\tallowClear: true,\n"; }
		if($this->load_type!='ajax' && isset($this->minimum_results_for_search) && $this->minimum_results_for_search==0) {
			$js_script .= "\t\t\tminimumResultsForSearch: Infinity,\n";
		} elseif(is_numeric($this->minimum_results_for_search) && $this->minimum_results_for_search>0) {
			$js_script .= "\t\t\tminimumResultsForSearch: {$this->minimum_results_for_search},\n";
		}//if($this->load_type!='ajax' && isset($this->minimum_results_for_search) && $this->minimum_results_for_search==0)
		if($this->load_type=='ajax') {
			$js_script .= "\t\t\tminimumInputLength: ".($this->minimum_input_length>0 ? $this->minimum_input_length : '3').",\n";
		} elseif(is_numeric($this->minimum_input_length) && $this->minimum_input_length>0) {
			$js_script .= "\t\t\tminimumInputLength: {$this->minimum_input_length},\n";
		}//if($this->load_type=='ajax')
		$litems = DataSource::ConvertArrayToDataSet(is_array($this->extra_items) ? $this->extra_items : [],VirtualEntity::class);

		if(is_array($this->selectedvalue)) {
			$s_values = $this->selectedvalue;
		} elseif(is_scalar($this->selectedvalue) && strlen($this->selectedvalue) && $this->selectedvalue!=='null') {
		    $s_values = [[
			    $this->valfield=>$this->selectedvalue,
			    (is_string($this->displayfield)?$this->displayfield:'_text_')=>$this->selectedtext,
			]];
        } else {
            $s_values = [];
		}//if(is_array($this->selectedvalue))
        $s_values = DataSource::ConvertArrayToDataSet($s_values,VirtualEntity::class);
		switch($this->load_type) {
			case 'ajax':
			    $initData = [];
			    if($s_values->count()) {
			        foreach($s_values as $sv) {
                         $s_item = [
                            'id'=>$sv->getProperty($this->valfield),
                            'name'=>$this->GetDisplayFieldValue($sv),
                            'selected'=>TRUE,
                        ];
                        if(is_string($this->state_field) && strlen($this->state_field)) {
                            $s_item['disabled'] = $sv->getProperty($this->state_field,1,'is_numeric')<=0;
                        }//if(is_string($this->state_field) && strlen($this->state_field))
                        foreach($this->option_data as $od) { $s_item[$od] = $sv->getProperty($od); }
                        $initData[] = $s_item;
                    }//END foreach
				} else {
			        $initData[] = [''=>''];
			    }//if($s_values->count())
			    $initData = json_encode($initData);
				$tagauid = \PAF\AppSession::GetNewUID($this->tagid,'md5');
				NApp::_SetSessionAcceptedRequest($tagauid);
				$cns = NApp::current_namespace();
				$ac_module = get_array_value($this->data_source,'ds_class','','is_string');
				$ac_method = get_array_value($this->data_source,'ds_method','','is_string');
				if(strlen($ac_module) && strlen($ac_method)) {
					$ac_module = convert_from_camel_case($ac_module);
					$ac_method = convert_from_camel_case($ac_method);
					$ac_params = '';
					$ac_params_arr = get_array_value($this->data_source,'ds_params',[],'is_array');
					if(is_array($ac_params_arr) && count($ac_params_arr)) {
						foreach($ac_params_arr as $acpk=>$acpv) { $ac_params .= '&'.$acpk.'='.rawurlencode($acpv); }
					}//if(is_array($ac_params_arr) && count($ac_params_arr))
					$rpp = get_array_value($this->data_source,'rows_limit',20,'is_not0_numeric');
					$ac_js_params = get_array_value($this->data_source,'ds_js_params',[],'is_array');
					if(is_array($ac_js_params) && count($ac_js_params)) {
						$ac_data_func = "function (params) { return { q: params.term, page_limit: {$rpp}";
						foreach($ac_js_params as $acpk=>$acpv) { $ac_data_func .= ', '.$acpk.': '.$acpv; }
						$ac_data_func .= " }; }";
					} else {
						$ac_data_func = "function (params) { return { q: params.term, page_limit: {$rpp} }; }";
					}//if(is_array($ac_js_params) && count($ac_js_params))
					$js_script .= "\t\t\tajax: {
						url: xAppWebLink+'/".AppConfig::app_ajax_target()."?namespace={$cns}&module={$ac_module}&method={$ac_method}&type=json{$ac_params}&uid={$tagauid}&phash='+window.name,
						dataType: 'json',
						delay: 0,
						cache: false,
						data: {$ac_data_func},
				processResults: function(data,params) { return { results: data }; }
					},
            data: {$initData},
			escapeMarkup: function(markup) { return markup; },
			templateResult: function(item) { return item.name; },\n";
					if(is_string($this->template_selection) && strlen($this->template_selection)) {
						$js_script .= "\t\t\ttemplateSelection: {$this->template_selection},\n";
					} else {
						$js_script .= "\t\t\ttemplateSelection: function(item) { return item.name || item.text; },\n";
					}//if(is_string($this->template_selection) && strlen($this->template_selection))
				}//if(strlen($ac_module) && strlen($ac_method))
				break;
			case 'database':
				if($this->allow_clear && strlen($this->cbo_placeholder)) { $litems->add([''=>''],TRUE); }
				$ds_name = get_array_value($this->data_source,'ds_class','','is_string');
				$ds_method = get_array_value($this->data_source,'ds_method','','is_string');
				if(strlen($ds_name) && strlen($ds_method)) {
					$ds_params = get_array_value($this->data_source,'ds_params',[],'is_array');
					$da_eparams = get_array_value($this->data_source,'ds_extra_params',[],'is_array');
					$data = DataProvider::Get($ds_name,$ds_method,$ds_params,$da_eparams);
					if(is_object($data) && $data->count()) { $litems->merge($data->toArray()); }
				}//if(strlen($ds_name) && strlen($ds_method))
				if(is_string($this->template_selection) && strlen($this->template_selection)) {
					$js_script .= "\t\t\ttemplateSelection: {$this->template_selection},\n";
				}//if(is_string($this->template_selection) && strlen($this->template_selection))
				break;
			case 'value':
				if($this->allow_clear && strlen($this->cbo_placeholder)) { $litems->add([''=>''],TRUE); }
				if(is_object($this->value) && $this->value->count()) {
				    $litems->merge($this->value->toArray());
				} elseif(is_array($this->value) && count($this->value)) {
				    $litems->merge($this->value);
				}//if(is_object($this->value) && $this->value->count())
				if(is_string($this->template_selection) && strlen($this->template_selection)) {
					$js_script .= "\t\t\ttemplateSelection: {$this->template_selection},\n";
				}//if(is_string($this->template_selection) && strlen($this->template_selection))
				break;
			default:
				throw new AppException('Invalid SmartComboBox load type!');
		}//END switch
		$js_script .= "\t\t})";
		// NApp::_Dlog($this->tagid,'$this->tagid');
		NApp::_Dlog($js_script,'$js_script');
		$roptions = '';
		$def_record = FALSE;
		$s_multiple = $this->multiple===TRUE || $this->multiple===1 || $this->multiple==='1' ? ' multiple="multiple"' : '';
		foreach($litems as $item) {
		    if(!is_object($item) || !$item->hasProperty($this->valfield)) {
				$roptions .= "\t\t\t<option></option>\n";
				continue;
			}//if(!is_object($item) || !$item->hasProperty($this->valfield))
			if($this->load_type=='ajax') {
				// $lval = get_array_value($item,$this->valfield,'null','isset');
				// $ltext = get_array_value($item,is_string($this->displayfield)?$this->displayfield:'_text_','','is_string');
				// $lselected = ' selected="selected"';
				// $o_data = '';
				// NApp::_Dlog($this->option_data,'$this->option_data');
                // foreach($this->option_data as $od) {
                //     $o_data .= ' data-'.$od.'="'.get_array_value($item,$od,'null','is_string').'"';
                // }//END foreach
			} else {
				$lval = $item->getProperty($this->valfield,'null','isset');
				$ltext = $this->GetDisplayFieldValue($item);
				$lselected = '';
				foreach($s_values as $sv) {
				    $lsval = $sv->getProperty($this->valfield,NULL,'isset');
				    if($lval==$lsval && !(($lsval==='null' && $lval!=='null') || ($lsval!=='null' && $lval==='null'))) {
							$lselected = ' selected="selected"';
							break;
                    }//if($lval==$lsval && !(($lsval==='null' && $lval!=='null') || ($lsval!=='null' && $lval==='null')))
					}//END foreach
				if(!$s_values->count() && !$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1) {
					$def_record = TRUE;
					$lselected = ' selected="selected"';
				}//if(!$s_values->count() && !$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1)
				$o_data = (is_string($this->state_field) && strlen($this->state_field) && $item->getProperty($this->state_field,1,'is_numeric')<=0) ? ' disabled="disabled"' : '';
                foreach($this->option_data as $od) {
                    $o_data .= ' data-'.$od.'="'.$item->getProperty($od,'null','is_string').'"';
					}//END foreach
			}//if($this->load_type=='ajax')
			$roptions .= "\t\t\t<option value=\"{$lval}\"{$lselected}{$o_data}>{$ltext}</option>\n";
		}//END foreach
		// final result processing
		$result = "\t\t".'<select'.$this->GetTagId(TRUE).$this->GetTagClass('SmartCBO').$this->GetTagAttributes().$this->GetTagActions().$s_multiple.' data-smartcbo="'.(strlen($js_script) ? rawurlencode(\GibberishAES::enc($js_script,$this->tagid)) : '').'">'."\n";
		$result .= $roptions;
		$result .= "\t\t".'</select>'."\n";
		$result .= $this->GetActions();
		return $result;
	}//END protected function SetControl
}//END class SmartComboBox extends Control
?>