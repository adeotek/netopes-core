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
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use Translate;
use NApp;
/**
 * ComboBox control
 * Standard ComboBox control
 * @property null   placeholder
 * @property null   onenter_button
 * @property null   cbo_placeholder
 * @property string display_field
 * @package  NETopes\Controls
 */
class SmartComboBox extends Control {
    use TControlDataSource;
    use TControlFields;
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
	 * @param null $params
     * @throws \NETopes\Core\AppException
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
		$this->onenter_button = NULL;
		if(!strlen($this->tag_id)) { $this->tag_id = $this->uid; }
		if(!is_string($this->value_field) || !strlen($this->value_field)) { $this->value_field = 'id'; }
		if(is_null($this->display_field) || $this->display_field=='') { $this->display_field = 'name'; }
		// one of the values: value/database/ajax
		if(!strlen($this->load_type)) { $this->load_type = 'value'; }
		if(!is_array($this->option_data)) {
		    if(is_string($this->option_data) && strlen($this->option_data)) {
		        $this->option_data = [$this->option_data];
		    } else {
		        $this->option_data = [];
		    }//if(is_string($this->option_data) && strlen($this->option_data))
		}//if(!is_array($this->option_data))
		if(!strlen($this->width) && !strlen($this->fixed_width)) { $this->fixed_width = '100%'; }
	}//END public function __construct
    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
	protected function SetControl(): ?string {
		$this->ProcessActions();
		$js_script_prefix = '';
		$js_script = "\t\t({\n";
		$raw_class = $this->GetTagClass(NULL,TRUE);
		if(is_string($this->theme) && strlen($this->theme)) { $js_script .= "\t\t\ttheme: '{$this->theme}',\n"; }
		if(strlen($raw_class)) { $js_script .= "\t\t\tcontainerCssClass: '{$raw_class}',\n"; }
		if(is_string($this->dropdown_class) && strlen($this->dropdown_class)) {
			$js_script .= "\t\t\tdropdownCssClass: '{$this->dropdown_class}',\n";
		} elseif(is_string($this->size) && strlen($this->size)) {
			$js_script .= "\t\t\tdropdownCssClass: 'size-{$this->size}',\n";
		}//if(is_string($this->dropdown_class) && strlen($this->dropdown_class))
		if(strlen($this->cbo_placeholder)) { $js_script .= "\t\t\tplaceholder: '{$this->cbo_placeholder}',\n"; }
		if(strlen($this->fixed_width)) { $js_script .= "\t\t\twidth: '{$this->fixed_width}',\n"; }
		if($this->load_type=='ajax' || $this->allow_clear) { $js_script .= "\t\t\tallowClear: true,\n"; }
		if($this->load_type!='ajax' && strlen($this->minimum_results_for_search) && $this->minimum_results_for_search==0) {
			$js_script .= "\t\t\tminimumResultsForSearch: Infinity,\n";
		} elseif(is_numeric($this->minimum_results_for_search) && $this->minimum_results_for_search>0) {
			$js_script .= "\t\t\tminimumResultsForSearch: {$this->minimum_results_for_search},\n";
		}//if($this->load_type!='ajax' && strlen($this->minimum_results_for_search) && $this->minimum_results_for_search==0)
		if($this->load_type=='ajax') {
			$js_script .= "\t\t\tminimumInputLength: ".($this->minimum_input_length>0 ? $this->minimum_input_length : '3').",\n";
		} elseif(is_numeric($this->minimum_input_length) && $this->minimum_input_length>0) {
			$js_script .= "\t\t\tminimumInputLength: {$this->minimum_input_length},\n";
		}//if($this->load_type=='ajax')
		$litems = DataSourceHelpers::ConvertArrayToDataSet(is_array($this->extra_items) ? $this->extra_items : [],VirtualEntity::class);
        if(is_object($this->selected_value)) {
            if(is_iterable($this->selected_value)) {
			    $s_values = $this->selected_value;
            } else {
                $s_values = new DataSet([$this->selected_value]);
            }//if(is_iterable($this->selected_value))
        } elseif(is_array($this->selected_value)) {
            $s_values = DataSourceHelpers::ConvertArrayToDataSet($this->selected_value,VirtualEntity::class);
        } else {
            if(is_scalar($this->selected_value)) {
                $s_values = [[
                    $this->value_field=>$this->selected_value,
                    (is_string($this->display_field)?$this->display_field:'_text_')=>$this->selected_text,
                ]];
            } else {
                $s_values = [];
            }//if(is_scalar($this->selected_value))
            $s_values = DataSourceHelpers::ConvertArrayToDataSet($s_values,VirtualEntity::class);
        }//if(is_object($this->selected_value))
		switch($this->load_type) {
			case 'ajax':
			    $litems->add(new VirtualEntity(),TRUE);
			    $initData = [];
			    if($s_values->count()) {
			        foreach($s_values as $sv) {
                         $s_item = [
                            'id'=>$sv->getProperty($this->value_field),
                            'name'=>$this->GetDisplayFieldValue($sv),
                            'selected'=>TRUE,
                        ];
                        if(is_string($this->state_field) && strlen($this->state_field)) {
                            $s_item['disabled'] = $sv->getProperty($this->state_field,1,'is_numeric')<=0;
                        }//if(is_string($this->state_field) && strlen($this->state_field))
                        foreach($this->option_data as $od) { $s_item[$od] = $sv->getProperty($od); }
                        $initData[] = $s_item;
                    }//END foreach
			    }//if($s_values->count())
				$tagauid = AppSession::GetNewUID($this->tag_id,'md5');
				AppSession::SetSessionAcceptedRequest($tagauid,NApp::$currentNamespace);
				$cns = NApp::$currentNamespace;
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
					$rpp = get_array_value($this->data_source,'rows_limit',10,'is_not0_numeric');
					$ac_js_params = get_array_value($this->data_source,'ds_js_params',[],'is_array');
					if(is_array($ac_js_params) && count($ac_js_params)) {
						$ac_data_func = "function (params) { return { q: params.term, page_limit: {$rpp}";
						foreach($ac_js_params as $acpk=>$acpv) { $ac_data_func .= ', '.$acpk.': '.$acpv; }
						$ac_data_func .= " }; }";
					} else {
						$ac_data_func = "function (params) { return { q: params.term, page_limit: {$rpp} }; }";
					}//if(is_array($ac_js_params) && count($ac_js_params))
					$errCallback = is_string($this->ajax_error_callback) ? trim($this->ajax_error_callback) : '';
					if(!strlen($errCallback)) { $js_script_prefix .= "$('#{$this->tag_id}').data('hasError','0');\n"; }
					$js_script .= "\t\t\tajax: {
						url: nAppBaseUrl+'/".AppConfig::GetValue('app_ajax_target')."?namespace={$cns}&module={$ac_module}&method={$ac_method}&type=json{$ac_params}&uid={$tagauid}&phash='+window.name,
						dataType: 'json',
						delay: 0,
						cache: false,
						data: {$ac_data_func},
						error: ".(strlen($errCallback) ? $errCallback : "function(response) {
                            if($('#{$this->tag_id}').data('hasError')==='0' && response.responseText==='Unauthorized access!') {
                                $('#{$this->tag_id}').data('hasError','1');
                                window.location.reload();
                            }
                        }").",
				        processResults: function(data,params) { return { results: data }; }
					},
            ".(count($initData) ? 'data: '.json_encode($initData).',' : '')."
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
				if($this->allow_clear && strlen($this->cbo_placeholder)) { $litems->add(new VirtualEntity(),TRUE); }
				$data = $this->LoadData($this->data_source);
				if(is_object($data) && $data->count()) { $litems->merge($data->toArray()); }
				if(is_string($this->template_selection) && strlen($this->template_selection)) {
					$js_script .= "\t\t\ttemplateSelection: {$this->template_selection},\n";
				}//if(is_string($this->template_selection) && strlen($this->template_selection))
				break;
			case 'value':
				if($this->allow_clear && strlen($this->cbo_placeholder)) { $litems->add(new VirtualEntity(),TRUE); }
				if(is_object($this->value) && $this->value->count()) {
				    $litems->merge($this->value->toArray());
				} elseif(is_array($this->value) && count($this->value)) {
				    $lValue = DataSourceHelpers::ConvertArrayToDataSet($this->value,VirtualEntity::class);
				    $litems->merge($lValue->toArray());
				}//if(is_object($this->value) && $this->value->count())
				if(is_string($this->template_selection) && strlen($this->template_selection)) {
					$js_script .= "\t\t\ttemplateSelection: {$this->template_selection},\n";
				}//if(is_string($this->template_selection) && strlen($this->template_selection))
				break;
			default:
				throw new AppException('Invalid SmartComboBox load type!');
		}//END switch
		$js_script .= "\t\t})";
		// NApp::Dlog($this->tag_id,'$this->tag_id');
		// NApp::Dlog($js_script,'$js_script');
		// NApp::Dlog($litems,'$litems');
		$rOptions = [''=>[]];
		$def_record = FALSE;
		$s_multiple = '';
		if((bool)$this->multiple) { $s_multiple = ' multiple="multiple"'; }
		foreach($litems as $item) {
		    if($this->load_type=='ajax') { continue; }
		    if(!is_object($item) || !$item->hasProperty($this->value_field)) {
				$rOptions[''][] = "\t\t\t<option></option>\n";
				continue;
			}//if(!is_object($item) || !$item->hasProperty($this->value_field))
			$lval = $item->getProperty($this->value_field,NULL,'isset');
			$ltext = $this->GetDisplayFieldValue($item);
			$lselected = '';
            foreach($s_values as $sv) {
                $lsval = $sv->getProperty($this->value_field,NULL,'isset');
                if($lval==$lsval && !(($lsval===NULL && $lval!==NULL) || ($lsval!==NULL && $lval===NULL))) {
                        $lselected = ' selected="selected"';
                        break;
                }//if($lval==$lsval && !(($lsval===NULL && $lval!==NULL) || ($lsval!==NULL && $lval===NULL)))
                }//END foreach
            if(!$s_values->count() && !$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1) {
                $def_record = TRUE;
                $lselected = ' selected="selected"';
            }//if(!$s_values->count() && !$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1)
            $o_data = (is_string($this->state_field) && strlen($this->state_field) && $item->getProperty($this->state_field,1,'is_numeric')<=0) ? ' disabled="disabled"' : '';
            foreach($this->option_data as $od) {
                $o_data .= ' data-'.$od.'="'.$item->getProperty($od,'','is_string').'"';
            }//END foreach
			if(is_string($this->group_field) && strlen($this->group_field)) {
                $groupName = $item->getProperty($this->group_field,'','is_string');
                if(!array_key_exists($groupName,$rOptions)) { $rOptions[$groupName] = []; }
                $rOptions[$groupName][] = "\t\t\t<option value=\"{$lval}\"{$lselected}{$o_data}>{$ltext}</option>\n";
            } else {
                $rOptions[''][] = "\t\t\t<option value=\"{$lval}\"{$lselected}{$o_data}>{$ltext}</option>\n";
            }//if(is_string($this->group_field) && strlen($this->group_field))
		}//END foreach
		// NApp::Dlog($rOptions,'$rOptions');
		$rOptionsStr = '';
		foreach(array_keys($rOptions) as $group) {
		    if(strlen($group)) { $rOptionsStr .= "\t\t\t<optgroup label=\"{$group}\">\n"; }
            $rOptionsStr .= implode('',$rOptions[$group]);
            if(strlen($group)) { $rOptionsStr .= "\t\t\t</optgroup>\n"; }
		}//END foreach
		// NApp::Dlog($rOptionsStr,'$rOptionsStr');
		// final result processing
		$result = "\t\t".'<select'.$this->GetTagId(TRUE).$this->GetTagClass('SmartCBO').$this->GetTagAttributes().$this->GetTagActions().$s_multiple.' data-smartcbo="'.(strlen($js_script) ? rawurlencode(\GibberishAES::enc($js_script_prefix.$js_script,$this->tag_id)) : '').'">'."\n";
		$result .= $rOptionsStr;
		$result .= "\t\t".'</select>'."\n";
		$result .= $this->GetActions();
		return $result;
	}//END protected function SetControl
}//END class SmartComboBox extends Control