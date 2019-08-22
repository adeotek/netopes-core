<?php
/**
 * ComboBox control class file
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;

/**
 * ComboBox control
 * Standard ComboBox control
 *
 * @property string|null placeholder
 * @property string|null onenter_button
 * @property string|null cbo_placeholder
 * @property string      display_field
 * @property string|null template_result
 * @property bool|null   allow_clear
 * @property mixed       selected_value
 * @property array       option_data
 * @property string      value_field
 * @property string|null theme
 * @property int|null    minimum_input_length
 * @property string|null dropdown_class
 * @property int|null    minimum_results_for_search
 * @property string|null selected_text
 * @property string|null state_field
 * @property string|null ajax_error_callback
 * @property string|null default_value_field
 * @property string|null group_field
 * @property bool|null   multiple
 * @property mixed       value
 * @package  NETopes\Controls
 */
class SmartComboBox extends Control {
    use TControlDataSource;
    use TControlFields;
    /**
     * @var null|string
     */
    public $load_type=NULL;
    /**
     * @var null
     */
    public $data_source=NULL;
    /**
     * @var null
     */
    public $extra_items=NULL;
    /**
     * @var null
     */
    public $template_selection=NULL;
    /**
     * @var bool
     */
    public $ajax_auto_load=FALSE;

    /**
     * SmartComboBox constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
        if(strlen($this->placeholder)) {
            $this->cbo_placeholder=$this->placeholder;
            $this->placeholder=NULL;
        } else {
            $this->cbo_placeholder=NULL;
        }//if(strlen($this->placeholder))
        $this->onenter=NULL;
        $this->onenter_button=NULL;
        if(!strlen($this->tag_id)) {
            $this->tag_id=$this->uid;
        }
        if(!is_string($this->value_field) || !strlen($this->value_field)) {
            $this->value_field='id';
        }
        if(is_null($this->display_field) || $this->display_field=='') {
            $this->display_field='name';
        }
        // one of the values: value/database/ajax
        if(!strlen($this->load_type)) {
            $this->load_type='value';
        }
        if(!is_array($this->option_data)) {
            if(is_string($this->option_data) && strlen($this->option_data)) {
                $this->option_data=[$this->option_data];
            } else {
                $this->option_data=[];
            }//if(is_string($this->option_data) && strlen($this->option_data))
        }//if(!is_array($this->option_data))
        if(!strlen($this->width) && !strlen($this->fixed_width)) {
            $this->fixed_width='100%';
        }
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $this->ProcessActions();
        $jsScriptPrefix='';
        $jsScripts=[];
        $raw_class=$this->GetTagClass(NULL,TRUE);
        if(is_string($this->theme) && strlen($this->theme)) {
            $jsScripts[]="\t\t\ttheme: '{$this->theme}'";
        }
        if(strlen($raw_class)) {
            $jsScripts[]="\t\t\tcontainerCssClass: '{$raw_class}'";
        }
        if(is_string($this->dropdown_class) && strlen($this->dropdown_class)) {
            $jsScripts[]="\t\t\tdropdownCssClass: '{$this->dropdown_class}'";
        } elseif(is_string($this->size) && strlen($this->size)) {
            $jsScripts[]="\t\t\tdropdownCssClass: 'size-{$this->size}'";
        }//if(is_string($this->dropdown_class) && strlen($this->dropdown_class))
        if(strlen($this->cbo_placeholder)) {
            $jsScripts[]="\t\t\tplaceholder: '{$this->cbo_placeholder}'";
        }
        if(strlen($this->fixed_width)) {
            $jsScripts[]="\t\t\twidth: '{$this->fixed_width}'";
        }
        if($this->load_type=='ajax' || $this->allow_clear) {
            $jsScripts[]="\t\t\tallowClear: true";
        }
        $minResultsForSearch=is_integer($this->minimum_results_for_search) ? ($this->minimum_results_for_search>0 ? $this->minimum_results_for_search : ($this->minimum_results_for_search===0 ? 'Infinity' : NULL)) : NULL;
        if(isset($minResultsForSearch)) {
            $jsScripts[]="\t\t\tminimumResultsForSearch: {$minResultsForSearch}";
        }//if(isset($minResultsForSearch))
        $minInputLength=(is_integer($this->minimum_input_length) && $this->minimum_input_length>=0 ? $this->minimum_input_length : ($this->load_type=='ajax' ? 3 : NULL));
        if(isset($minInputLength)) {
            $jsScripts[]="\t\t\tminimumInputLength: {$minInputLength}";
        }//if(isset($minInputLength))
        $lItems=DataSourceHelpers::ConvertArrayToDataSet(is_array($this->extra_items) ? $this->extra_items : [],VirtualEntity::class);
        if(is_object($this->selected_value)) {
            if(is_iterable($this->selected_value)) {
                $selectedValues=$this->selected_value;
            } else {
                $selectedValues=new DataSet([$this->selected_value]);
            }//if(is_iterable($this->selected_value))
        } elseif(is_array($this->selected_value)) {
            $selectedValues=DataSourceHelpers::ConvertArrayToDataSet($this->selected_value,VirtualEntity::class);
        } else {
            if(is_scalar($this->selected_value)) {
                $selectedValues=[[
                    $this->value_field=>$this->selected_value,
                    (is_string($this->display_field) ? $this->display_field : '_text_')=>$this->selected_text,
                ]];
            } else {
                $selectedValues=[];
            }//if(is_scalar($this->selected_value))
            $selectedValues=DataSourceHelpers::ConvertArrayToDataSet($selectedValues,VirtualEntity::class);
        }//if(is_object($this->selected_value))
        switch($this->load_type) {
            case 'ajax':
                $lItems->add(new VirtualEntity(),TRUE);
                $initData=[];
                if($selectedValues->count()) {
                    foreach($selectedValues as $sv) {
                        $s_item=[
                            'id'=>$sv->getProperty($this->value_field),
                            'name'=>$this->GetDisplayFieldValue($sv),
                            'selected'=>TRUE,
                        ];
                        if(is_string($this->state_field) && strlen($this->state_field)) {
                            $s_item['disabled']=$sv->getProperty($this->state_field,1,'is_numeric')<=0;
                        }//if(is_string($this->state_field) && strlen($this->state_field))
                        foreach($this->option_data as $od) {
                            $s_item[$od]=$sv->getProperty($od);
                        }
                        $initData[]=$s_item;
                    }//END foreach
                }//if($selectedValues->count())
                $tagSessionUid=AppSession::GetNewUID($this->tag_id,'md5');
                AppSession::SetSessionAcceptedRequest($tagSessionUid,NApp::$currentNamespace);
                $cns=NApp::$currentNamespace;
                $acModule=get_array_value($this->data_source,'ds_class','','is_string');
                $acMethod=get_array_value($this->data_source,'ds_method','','is_string');
                if(strlen($acModule) && strlen($acMethod)) {
                    $acModule=convert_from_camel_case($acModule);
                    $acMethod=convert_from_camel_case($acMethod);
                    $acParams='';
                    $acParamsArray=get_array_value($this->data_source,'ds_params',[],'is_array');
                    if(is_array($acParamsArray) && count($acParamsArray)) {
                        foreach($acParamsArray as $acpk=>$acpv) {
                            $acParams.='&'.$acpk.'='.rawurlencode($acpv);
                        }
                    }//if(is_array($acParamsArray) && count($acParamsArray))
                    $rpp=get_array_value($this->data_source,'rows_limit',10,'is_not0_numeric');
                    $acJsParams=get_array_value($this->data_source,'ds_js_params',[],'is_array');
                    $acDataFunc="function (params) { return { q: params.term, page_limit: {$rpp}";
                    if(is_array($acJsParams) && count($acJsParams)) {
                        foreach($acJsParams as $acpk=>$acpv) {
                            $acDataFunc.=', '.$acpk.': '.$acpv;
                        }//END foreach
                    }//if(is_array($acJsParams) && count($acJsParams))
                    $acDataFunc.=" }; }";
                    $errCallback=is_string($this->ajax_error_callback) ? trim($this->ajax_error_callback) : '';
                    if(!strlen($errCallback)) {
                        $jsScriptPrefix.="$('#{$this->tag_id}').data('hasError','0');\n";
                    }
                    $jsScripts[]="\t\t\tajax: {
						url: nAppBaseUrl+'/".AppConfig::GetValue('app_ajax_target')."?namespace={$cns}&module={$acModule}&method={$acMethod}&type=json{$acParams}&uid={$tagSessionUid}&phash='+window.name,
						dataType: 'json',
						delay: 0,
						cache: false,
						data: {$acDataFunc},
						error: ".(strlen($errCallback) ? $errCallback : "function(response) {
                            if($('#{$this->tag_id}').data('hasError')==='0' && response.responseText==='Unauthorized access!') {
                                $('#{$this->tag_id}').data('hasError','1');
                                window.location.reload();
                            }
                        }").",
				        processResults: function(data,params) { return { results: data }; }
					}";
                    if(count($initData)) {
                        $jsScripts[]="\t\t\tdata: ".json_encode($initData);
                    }
                    $jsScripts[]="\t\t\tescapeMarkup: function(markup) { return markup; }";
                    if(is_string($this->template_result) && strlen($this->template_result)) {
                        $jsScripts[]="\t\t\ttemplateResult: {$this->template_result}";
                    } else {
                        $jsScripts[]="\t\t\ttemplateResult: function(item) { return item.name; }";
                    }//if(is_string($this->template_result) && strlen($this->template_result))
                    if(is_string($this->template_selection) && strlen($this->template_selection)) {
                        $jsScripts[]="\t\t\ttemplateSelection: {$this->template_selection}";
                    } else {
                        $jsScripts[]="\t\t\ttemplateSelection: function(item) { return item.name || item.text; }";
                    }//if(is_string($this->template_selection) && strlen($this->template_selection))
                }//if(strlen($acModule) && strlen($acMethod))
                break;
            case 'database':
                if($this->allow_clear && strlen($this->cbo_placeholder)) {
                    $lItems->add(new VirtualEntity(),TRUE);
                }
                $data=$this->LoadData($this->data_source);
                if(is_object($data) && $data->count()) {
                    $lItems->merge($data->toArray());
                }
                if(is_string($this->template_result) && strlen($this->template_result)) {
                    $jsScripts[]="\t\t\ttemplateResult: {$this->template_result}";
                }//if(is_string($this->template_result) && strlen($this->template_result))
                if(is_string($this->template_selection) && strlen($this->template_selection)) {
                    $jsScripts[]="\t\t\ttemplateSelection: {$this->template_selection}";
                }//if(is_string($this->template_selection) && strlen($this->template_selection))
                break;
            case 'value':
                if($this->allow_clear && strlen($this->cbo_placeholder)) {
                    $lItems->add(new VirtualEntity(),TRUE);
                }
                if(is_object($this->value) && $this->value->count()) {
                    $lItems->merge($this->value->toArray());
                } elseif(is_array($this->value) && count($this->value)) {
                    $lValue=DataSourceHelpers::ConvertArrayToDataSet($this->value,VirtualEntity::class);
                    $lItems->merge($lValue->toArray());
                }//if(is_object($this->value) && $this->value->count())
                if(is_string($this->template_result) && strlen($this->template_result)) {
                    $jsScripts[]="\t\t\ttemplateResult: {$this->template_result}";
                }//if(is_string($this->template_result) && strlen($this->template_result))
                if(is_string($this->template_selection) && strlen($this->template_selection)) {
                    $jsScripts[]="\t\t\ttemplateSelection: {$this->template_selection}";
                }//if(is_string($this->template_selection) && strlen($this->template_selection))
                break;
            default:
                throw new AppException('Invalid SmartComboBox load type!');
        }//END switch
        $jsScript="\t\t({\n".implode(",\n",$jsScripts)."\t\t})";
        // NApp::Dlog($this->tag_id,'$this->tag_id');
        // NApp::Dlog($jsScript,'$jsScript');
        // NApp::Dlog($lItems,'$lItems');
        $rOptions=[''=>[]];
        $def_record=FALSE;
        $s_multiple='';
        if((bool)$this->multiple) {
            $s_multiple=' multiple="multiple"';
        }
        /** @var VirtualEntity $item */
        foreach($lItems as $item) {
            if($this->load_type=='ajax') {
                continue;
            }
            if(!is_object($item) || !$item->hasProperty($this->value_field)) {
                $rOptions[''][]="\t\t\t<option></option>\n";
                continue;
            }//if(!is_object($item) || !$item->hasProperty($this->value_field))
            $lValue=$item->getProperty($this->value_field,NULL,'isset');
            $lText=$this->GetDisplayFieldValue($item);
            $lSelected='';
            /** @var VirtualEntity $sv */
            foreach($selectedValues as $sv) {
                $lsVal=$sv->getProperty($this->value_field);
                if($lValue==$lsVal && !(($lsVal===NULL && $lValue!==NULL) || ($lsVal!==NULL && $lValue===NULL))) {
                    $lSelected=' selected="selected"';
                    break;
                }//if($lValue==$lsVal && !(($lsVal===NULL && $lValue!==NULL) || ($lsVal!==NULL && $lValue===NULL)))
            }//END foreach
            if(!$selectedValues->count() && !$def_record && !strlen($lSelected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1) {
                $def_record=TRUE;
                $lSelected=' selected="selected"';
            }//if(!$selectedValues->count() && !$def_record && !strlen($lSelected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1)
            $o_data=(is_string($this->state_field) && strlen($this->state_field) && $item->getProperty($this->state_field,1,'is_numeric')<=0) ? ' disabled="disabled"' : '';
            foreach($this->option_data as $od) {
                $o_data.=' data-'.$od.'="'.$item->getProperty($od,'','is_string').'"';
            }//END foreach
            if(is_string($this->group_field) && strlen($this->group_field)) {
                $groupName=$item->getProperty($this->group_field,'','is_string');
                if(!array_key_exists($groupName,$rOptions)) {
                    $rOptions[$groupName]=[];
                }
                $rOptions[$groupName][]="\t\t\t<option value=\"{$lValue}\"{$lSelected}{$o_data}>{$lText}</option>\n";
            } else {
                $rOptions[''][]="\t\t\t<option value=\"{$lValue}\"{$lSelected}{$o_data}>{$lText}</option>\n";
            }//if(is_string($this->group_field) && strlen($this->group_field))
        }//END foreach
        // NApp::Dlog($rOptions,'$rOptions');
        $rOptionsStr='';
        foreach(array_keys($rOptions) as $group) {
            if(strlen($group)) {
                $rOptionsStr.="\t\t\t<optgroup label=\"{$group}\">\n";
            }
            $rOptionsStr.=implode('',$rOptions[$group]);
            if(strlen($group)) {
                $rOptionsStr.="\t\t\t</optgroup>\n";
            }
        }//END foreach
        // NApp::Dlog($rOptionsStr,'$rOptionsStr');
        // final result processing
        $result="\t\t".'<select'.$this->GetTagId(TRUE).$this->GetTagClass('SmartCBO').$this->GetTagAttributes().$this->GetTagActions().$s_multiple.' data-smartcbo="'.(strlen($jsScript) ? rawurlencode(GibberishAES::enc($jsScriptPrefix.$jsScript,$this->tag_id)) : '').'">'."\n";
        $result.=$rOptionsStr;
        $result.="\t\t".'</select>'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class SmartComboBox extends Control