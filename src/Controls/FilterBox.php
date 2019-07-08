<?php
/**
 * Generic filter generator.
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NApp;
use NETopes\Core\App\Params;
use NETopes\Core\Data\DataProvider;
use Translate;

/**
 * Class FilterBox
 *
 * @package NETopes\Core\Controls
 */
class FilterBox extends FilterControl {
    /**
     * @var    string Module name
     */
    public $module=NULL;
    /**
     * @var    string Module method name
     */
    public $method=NULL;
    /**
     * @var    array Items configuration params
     */
    public $items=[];
    /**
     * @var    string Java script on data load/refresh/filter
     */
    public $onload_js_callback=NULL;
    /**
     * @var    string Java script on data load/refresh/page change/filter/sort callback
     */
    public $onchange_js_callback=NULL;
    /**
     * @var    string Auto-generated javascript callback string (onload_js_callback + onchange_js_callback)
     */
    public $js_callbacks=NULL;
    /**
     * @var    array Apply filters button configuration.
     */
    public $apply_filters_conf=[];

    /**
     * Gets the javascript callback string
     *
     * @param bool $onloadCallback    Include or not on load callback
     * @param bool $onchange_callback Include or not on change callback
     * @return string Returns javascript callback string
     */
    protected function ProcessJsCallbacks($onloadCallback=TRUE,$onchange_callback=TRUE) {
        if($onloadCallback && $onchange_callback) {
            if(is_null($this->js_callbacks)) {
                $this->js_callbacks='';
                if(strlen($this->onload_js_callback)) {
                    $this->js_callbacks=$this->onload_js_callback;
                }
                if(strlen($this->onchange_js_callback) && $this->onchange_js_callback!=$this->js_callbacks) {
                    $this->js_callbacks=strlen($this->js_callbacks) ? rtrim($this->js_callbacks,"'\"").ltrim($this->onchange_js_callback,"'\"") : $this->onchange_js_callback;
                }//if(strlen($this->onchange_js_callback) && $this->onchange_js_callback!=$this->js_callbacks)
            }//if(is_null($this->js_callbacks))
            return $this->js_callbacks;
        } elseif($onloadCallback && strlen($this->onload_js_callback)) {
            return $this->onload_js_callback;
        } elseif($onchange_callback && strlen($this->onchange_js_callback)) {
            return $this->onchange_js_callback;
        }//if($onloadCallback && $onchange_callback)
        return '';
    }//END protected function ProcessJsCallbacks

    /**
     * Gets the action javascript command string
     *
     * @param string            $type
     * @param Params|array|null $params
     * @param bool              $processCall
     * @return string Returns action javascript command string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActionCommand(string $type='',$params=NULL,bool $processCall=TRUE): ?string {
        $params=is_object($params) ? $params : new Params($params);
        $targetId=NULL;
        $execCallback=TRUE;
        $onloadCallback=TRUE;
        $targetId=$this->tag_id;
        switch($type) {
            case 'apply_filters':
                // TODO: generate an array with filters.
                $command="{ 'control_hash': '{$this->chash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'apply' } }";
                break;
            case 'update_filter':
                $execCallback=FALSE;
                $fcType=$params->safeGet('fctype','','is_string');
                $command="{ 'control_hash': '{$this->chash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'fop': '{nGet|{$this->tag_id}-f-operator:value}', 'type': '{nGet|{$this->tag_id}-f-type:value}', 'f-cond-type': '".(strlen($fcType) ? '{nGet|'.$fcType.'}' : '')."' } }";
                break;
            case 'remove_filter':
                $command="{ 'control_hash': '{$this->chash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'remove', 'fkey': '".$params->safeGet('fkey','','is_string')."', 'sessact': 'filters' } }";
                break;
            case 'clear_filters':
                $command="{ 'control_hash': '{$this->chash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction':'clear', 'sessact': 'filters' } }";
                break;
            case 'add_filter':
                $fsValue=$params->safeGet('fsvalue','','is_string');
                $fdvalue=$params->safeGet('fdvalue','{nGet|'.$this->tag_id.'-f-value:value}','is_notempty_string');
                if(strlen($fdvalue) && strpos($fdvalue,'{nEval|')===FALSE && strpos($fdvalue,'{nGet|')===FALSE) {
                    $fdvalue='{nGet|'.$fdvalue.'}';
                }
                $fsdvalue=$params->safeGet('fsdvalue','','is_string');
                if(strlen($fsdvalue) && strpos($fsdvalue,'{nEval|')===FALSE && strpos($fsdvalue,'{nGet|')===FALSE) {
                    $fsdvalue='{nGet|'.$fsdvalue.'}';
                }
                $fdtype=$params->safeGet('data_type','','is_string');
                //~'fkey'|'".$params->safeGet('fkey','','is_notempty_string')."'
                //~'fcond'|{$this->tag_id}-f-cond-type:value
                $isDSParam=$params->safeGet('is_ds_param',0,'is_numeric');
                $command="{ 'control_hash': '{$this->chash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'add', 'sessact': 'filters', 'fop': '".((is_array($this->filters) && count($this->filters)) ? "{nGet|".$this->tag_id."-f-operator:value}" : 'and')."', 'ftype': '{nGet|{$this->tag_id}-f-type:value}', 'fcond': '{nGet|{$this->filter_cond_val_source}}', 'fvalue': '{nGet|".$params->safeGet('fvalue',$this->tag_id.'-f-value:value','is_notempty_string')."}', 'fsvalue': '".(strlen($fsValue) ? '{nGet|'.$fsValue.'}' : '')."', 'fdvalue': '{$fdvalue}', 'fsdvalue': '{$fsdvalue}', 'data_type': '{$fdtype}', 'is_ds_param': '{$isDSParam}', 'groupid': '{nGet|{$this->tag_id}-f-group:value}' } }";
                break;
            default:
                $command=NULL;
                break;
        }//END switch
        if(is_null($command)) {
            return NULL;
        }
        if(!$processCall) {
            return $command;
        }
        $jsCallback=$this->ProcessJsCallbacks($onloadCallback);
        return NApp::Ajax()->Prepare($command,$targetId,NULL,$this->loader,NULL,TRUE,(!$execCallback || !strlen($jsCallback) ? $jsCallback : NULL),NULL,TRUE,'ControlAjaxRequest');
    }//END protected function GetActionCommand

    /**
     * Gets the actions bar controls html (except controls for filters)
     *
     * @param array $filtersGroups
     * @return string Returns the actions bar controls html
     * @throws \NETopes\Core\AppException
     */
    protected function GetActionsBarControls(array $filtersGroups=[]) {
        $result='';
        $result.="\t\t\t".'<button class="f-clear-btn" onclick="'.$this->GetActionCommand('clear_filters').'"><i class="fa fa-times"></i>'.Translate::Get('button_clear_filters').'</button>'."\n";
        if(is_array($this->apply_filters_conf) && count($this->apply_filters_conf) && isset($this->apply_filters_conf['params'])) {
            $params=$this->apply_filters_conf['params'];
            $params['module']=get_array_value($params,'module','','is_string');
            $params['method']=get_array_value($params,'method','','is_string');
            $params['params']=get_array_value($params,'params',[],'is_array');
            $params['params']['filters']=$filtersGroups;
            $onClick=NApp::Ajax()->PrepareAjaxRequest($params,get_array_value($this->apply_filters_conf,'extra_params',[],'is_array'));
        } else {
            $onClick='';
        }
        $result.="\t\t\t".'<button class="f-apply-btn pull-right" onclick="'.$onClick.'"><i class="fa fa-filter" aria-hidden="true"></i>'.Translate::Get('button_apply_filters').'</button>'."\n";
        return $result;
    }//END protected function GetActionsBarControls

    /**
     * Gets the filter box html
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null Returns the filter box html
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterBox(Params $params=NULL): ?string {
        $cftype=$params->safeGet('type','','is_string');
        $filters="\t\t".'<span class="f-title">'.Translate::Get('label_filters').'</span>'."\n";
        $filters.="\t\t".'<div class="f-container">'."\n";
        if(is_array($this->filters) && count($this->filters)) {
            $filters.="\t\t\t".'<select id="'.$this->tag_id.'-f-operator" class="f-operator">'."\n";
            foreach(DataProvider::GetArray('_Custom\Offline','FilterOperators') as $c) {
                $fo_selected=$params->safeGet('fop','','is_string')==$c['value'] ? ' selected="selected"' : '';
                $filters.="\t\t\t\t".'<option value="'.$c['value'].'"'.$fo_selected.'>'.$c['name'].'</option>'."\n";
            }//END foreach
            $filters.="\t\t\t".'</select>'."\n";
        } else {
            $filters.="\t\t\t".'<input id="'.$this->tag_id.'-f-operator" type="hidden" value="'.$params->safeGet('fop','','is_string').'">'."\n";
        }//if(is_array($this->filters) && count($this->filters))
        $filters.="\t\t\t".'<select id="'.$this->tag_id.'-f-type" class="f-type" onchange="'.$this->GetActionCommand('update_filter').'">'."\n";
        $selectedv=NULL;
        $cfctype='';
        $isDSParam=0;
        foreach($this->items as $k=>$v) {
            if($cftype==$k || (!strlen($cftype) && !$selectedv)) {
                $lselected=' selected="selected"';
                $cfctype=get_array_value($v,'filter_type','','is_string');
                $selectedv=$v;
            } else {
                $lselected='';
            }//if($cftype==$k || (!strlen($cftype) && !$selectedv))
            $filters.="\t\t\t\t".'<option value="'.$k.'"'.$lselected.'>'.get_array_value($v,'label',$k,'is_notempty_string').'</option>'."\n";
        }//END foreach
        $filters.="\t\t\t".'</select>'."\n";
        $fdtype=get_array_value($selectedv,'data_type','','is_string');
        $fc_type=$params->safeGet('f-cond-type','','is_string');
        $this->filter_cond_val_source=$this->tag_id.'-f-cond-type:value';
        $fc_cond_type=get_array_value($selectedv,'show_filter_cond_type',get_array_value($selectedv,'show_filter_cond_type',TRUE,'bool'),'is_notempty_string');
        if($fc_cond_type===TRUE) {
            $filter_cts='';
            $filter_ct_onchange='';
            $p_fctype=strtolower(strlen($cfctype) ? $cfctype : $fdtype);
            $fConditions=DataProvider::Get('_Custom\Offline','FilterConditionsTypes',['type'=>$p_fctype]);
            foreach($fConditions as $c) {
                $fct_selected=$fc_type==$c->getProperty('value') ? ' selected="selected"' : '';
                $filter_cts.="\t\t\t\t".'<option value="'.$c->getProperty('value').'"'.$fct_selected.'>'.$c->getProperty('name').'</option>'."\n";
                if(!strlen($filter_ct_onchange) && $c->getProperty('value')=='><') {
                    $filter_ct_onchange=' onchange="'.$this->GetActionCommand('update_filter',['fctype'=>$this->tag_id.'-f-cond-type:value']).'"';
                }//if(!strlen($filter_ct_onchange) && $c->getProperty('value')=='><')
            }//END foreach
            $filters.="\t\t\t".'<select id="'.$this->tag_id.'-f-cond-type" class="f-cond-type"'.$filter_ct_onchange.'>'."\n";
            $filters.=$filter_cts;
            $filters.="\t\t\t".'</select>'."\n";
        } elseif($fc_cond_type!=='data') {
            $filters.="\t\t\t".'<input type="hidden" id="'.$this->tag_id.'-f-cond-type" value="=="/>'."\n";
        } else {
            $this->filter_cond_val_source=NULL;
        }//if($fc_cond_type===TRUE)
        $ctrlParams=get_array_value($selectedv,'filter_params',[],'is_array');
        $ctrlParams['tag_id']=$this->tag_id.'-f-value';
        $ctrlParams['class']='f-value';
        $ctrlParams['clear_base_class']=TRUE;
        $ctrlParams['container']=FALSE;
        $ctrlParams['no_label']=TRUE;
        $ctrlParams['postable']=FALSE;
        $aoc_check=NULL;
        $fval=NULL;
        $fsval=NULL;
        $f_subtype=NULL;
        $filtersGroups=array_group_by('groupid',$this->filters);
        switch(strtolower($cfctype)) {
            case 'smartcombobox':
                $ctrlParams['placeholder']=get_array_value($ctrlParams,'placeholder',Translate::GetLabel('please_select'),'is_notempty_string');
                $ctrlParams['allow_clear']=get_array_value($ctrlParams,'allow_clear',TRUE,'is_bool');
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedv,'filter_data_source',NULL,'is_notempty_array');
                }//if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $ctrl_filter_value=new SmartComboBox($ctrlParams);
                $dvalue='{nEval|GetSmartCBOText(\''.$this->tag_id.'-f-value\',false)}';
                if(!$this->filter_cond_val_source) {
                    $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                $ctrl_filter_value->ClearBaseClass();
                $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                $filters.=$this->GetFiltersGroups($filtersGroups);
                $filters.="\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',['fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam]).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.Translate::Get('button_add_filter').'</button>'."\n";
                break;
            case 'combobox':
                if(!isset($ctrlParams['please_select_text']) || !strlen($ctrlParams['please_select_text'])) {
                    $ctrlParams['please_select_text']=Translate::GetLabel('please_select');
                    $ctrlParams['please_select_value']=NULL;
                }//if(!isset($ctrlParams['please_select_text']) || !strlen($ctrlParams['please_select_text']))
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedv,'filter_data_source',NULL,'is_notempty_array');
                }//if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $ctrl_filter_value=new ComboBox($ctrlParams);
                $dvalue=$this->tag_id.'-f-value:option';
                if(!$this->filter_cond_val_source) {
                    $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                $ctrl_filter_value->ClearBaseClass();
                $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                $filters.=$this->GetFiltersGroups($filtersGroups);
                $filters.="\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',['fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam]).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.Translate::Get('button_add_filter').'</button>'."\n";
                break;
            case 'treecombobox':
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                $ctrlParams['data_source']=get_array_value($selectedv,'filter_data_source',NULL,'is_notempty_array');
                $ctrl_filter_value=new TreeComboBox($ctrlParams);
                $dvalue=$this->tag_id.'-f-value-cbo:value';
                if(!$this->filter_cond_val_source) {
                    $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                // $ctrl_filter_value->ClearBaseClass();
                $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                $filters.=$this->GetFiltersGroups($filtersGroups);
                $filters.="\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',['fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype]).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.Translate::Get('button_add_filter').'</button>'."\n";
                break;
            case 'checkbox':
                $ctrlParams['value']=0;
                $ctrl_filter_value=new CheckBox($ctrlParams);
                $dvalue=$this->tag_id.'-f-value:value';
                if(!$this->filter_cond_val_source) {
                    $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                $ctrl_filter_value->ClearBaseClass();
                $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                $filters.=$this->GetFiltersGroups($filtersGroups);
                $filters.="\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',['fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam]).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.Translate::Get('button_add_filter').'</button>'."\n";
                break;
            case 'datepicker':
            case 'date':
            case 'datetime':
                $f_subtype='DatePicker';
            case 'numerictextbox':
            case 'numeric':
                $f_subtype=$f_subtype ? $f_subtype : 'NumericTextBox';
            default:
                if(!$f_subtype) {
                    switch($fdtype) {
                        case 'date':
                        case 'date_obj':
                        case 'datetime':
                        case 'datetime_obj':
                            $f_subtype='DatePicker';
                            break;
                        case 'numeric':
                            $f_subtype='NumericTextBox';
                            break;
                        default:
                            $f_subtype='TextBox';
                            break;
                    }//END switch
                }//if(!$f_subtype)
                switch($f_subtype) {
                    case 'DatePicker':
                        $ctrlParams['size']='xxs';
                        $ctrlParams['value']='';
                        $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                        if(strtolower($cfctype)!='date' && ($fdtype=='datetime' || $fdtype=='datetime_obj')) {
                            $ctrlParams['timepicker']=TRUE;
                        } else {
                            $ctrlParams['timepicker']=FALSE;
                        }//if(strtolower($cfctype)!='date' && ($fdtype=='datetime' || $fdtype=='datetime_obj'))
                        $ctrlParams['align']='center';
                        $ctrl_filter_value=new DatePicker($ctrlParams);
                        $ctrl_filter_value->ClearBaseClass();
                        $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                        $fval=$this->tag_id.'-f-value:dvalue';
                        if($fc_type=='><') {
                            $filters.="\t\t\t".'<span class="f-i-lbl">'.Translate::Get('label_and').'</span>'."\n";
                            $ctrlParams['tag_id']=$this->tag_id.'-f-svalue';
                            $ctrl_filter_value=new DatePicker($ctrlParams);
                            $ctrl_filter_value->ClearBaseClass();
                            $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                            $fsval=$this->tag_id.'-f-svalue:dvalue';
                            $sdvalue=$this->tag_id.'-f-svalue:value';
                            if(!$this->filter_cond_val_source) {
                                $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                            }
                        }//if($fc_type=='><')
                        break;
                    case 'NumericTextBox':
                        $ctrlParams['class'].=' t-box';
                        $ctrlParams['value']='';
                        $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                        $ctrlParams['number_format']=get_array_value($selectedv,'filter_format','0|||','is_notempty_string');
                        $ctrlParams['align']='center';
                        $ctrl_filter_value=new NumericTextBox($ctrlParams);
                        $ctrl_filter_value->ClearBaseClass();
                        $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                        $fval=$this->tag_id.'-f-value:nvalue';
                        if($fc_type=='><') {
                            $filters.="\t\t\t".'<span class="f-i-lbl">'.Translate::Get('label_and').'</span>'."\n";
                            $ctrlParams['tag_id']=$this->tag_id.'-f-svalue';
                            $ctrl_filter_value=new NumericTextBox($ctrlParams);
                            $ctrl_filter_value->ClearBaseClass();
                            $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                            $fsval=$this->tag_id.'-f-svalue:nvalue';
                            $sdvalue=$this->tag_id.'-f-svalue:value';
                            if(!$this->filter_cond_val_source) {
                                $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                            }
                        }//if($fc_type=='><')
                        break;
                    case 'TextBox':
                    default:
                        $ctrlParams['class'].=' t-box';
                        $ctrlParams['value']='';
                        $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                        $ctrl_filter_value=new TextBox($ctrlParams);
                        $ctrl_filter_value->ClearBaseClass();
                        $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                        $fval=$this->tag_id.'-f-value:value';
                        if($fc_type=='><') {
                            $filters.="\t\t\t".'<span class="f-i-lbl">'.Translate::Get('label_and').'</span>'."\n";
                            $ctrlParams['tag_id']=$this->tag_id.'-f-svalue';
                            $ctrl_filter_value=new TextBox($ctrlParams);
                            $ctrl_filter_value->ClearBaseClass();
                            $filters.="\t\t\t".$ctrl_filter_value->Show()."\n";
                            $fsval=$this->tag_id.'-f-svalue:value';
                            $sdvalue=$this->tag_id.'-f-svalue:value';
                            if(!$this->filter_cond_val_source) {
                                $this->filter_cond_val_source=$this->tag_id.'-f-value:option:data-ctype';
                            }
                        }//if($fc_type=='><')
                        break;
                }//END switch
                $filters.=$this->GetFiltersGroups($filtersGroups);
                $dvalue=$this->tag_id.'-f-value:value';
                $f_b_params=$fc_type=='><' ? ['fdvalue'=>$dvalue,'fsdvalue'=>$sdvalue,'fvalue'=>$fval,'fsvalue'=>$fsval,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam] : ['fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isDSParam];
                $filters.="\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',$f_b_params).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.Translate::Get('button_add_filter').'</button>'."\n";
                break;
        }//END switch
        $filters.=$this->GetActionsBarControls($filtersGroups);
        if(is_array($filtersGroups) && count($filtersGroups)) {
            $filters.="\t\t\t".'<div class="f-active">'."\n";
            $fcTypes=DataProvider::GetKeyValue('_Custom\Offline','FilterConditionsTypes',['type'=>'all'],['keyfield'=>'value']);
            $filters.="\t\t\t\t".'<span class="f-active-title">'.Translate::Get('label_active_filters').':</span>'."\n";
            $first=TRUE;
            $gIndex=1;
            foreach($filtersGroups as $gid=>$group) {
                if(is_array($group) && count($group)) {
                    $filters.="\t\t\t\t".'<div class="f-active-item"><div class="b-remove" onclick="'.$this->GetActionCommand('remove_filter',['fkey'=>$gid]).'"><i class="fa fa-times"></i></div><span>'.$gIndex.'&nbsp;[</span></span>';
                    foreach($group as $item) {
                        if($first) {
                            $af_op='';
                            $first=FALSE;
                        } else {
                            $af_op=Translate::Get('label_'.$item['operator']).' ';
                        }//if($first) {
                        if($item['condition_type']=='><') {
                            $filters.=$af_op.'<strong>'.(get_array_value($this->items[$item['type']],'label',$item['type'],'is_notempty_string')).'</strong>&nbsp;'.$fcTypes->safeGet($item['condition_type'])->getProperty('name').'&nbsp;&quot;<strong>'.$item['dvalue'].'</strong>&quot;&nbsp;'.Translate::Get('label_and').'&nbsp;&quot;<strong>'.$item['sdvalue'].'</strong>'."\n";
                        } else {
                            $filters.=$af_op.'<strong>'.(get_array_value($this->items[$item['type']],'label',$item['type'],'is_notempty_string')).'</strong>&nbsp;'.$fcTypes->safeGet($item['condition_type'])->getProperty('name').'&nbsp;&quot;<strong>'.$item['dvalue'].'</strong>'."\n";
                        }//if($item['condition_type']=='><')
                    }//foreach($group as $items) {
                    $filters.='&quot;]</div>';
                    $gIndex++;
                }//if(is_array($group) && count($group)) {
            }//foreach ($filtersGroups as $group) {
            $filters.="\t\t\t".'</div>'."\n";
        }//if(is_array($filtersGroups) && count($filtersGroups)) {

        $filters.="\t\t\t".'<div class="clearfix"></div>'."\n";
        $filters.="\t\t".'</div>'."\n";
        return $filters;
    }//END protected function GetFilterBox

    /**
     * Sets the output buffer value
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(Params $params=NULL): ?string {
        $this->filters=$this->ProcessActiveFilters($params);
        $lclass=$this->base_class.(strlen($this->class) ? ' '.$this->class : '').' clsFixedWidth';
        switch($this->theme_type) {
            case 'bootstrap3':
                $result='<div class="row">'."\n";
                $result.="\t".'<div class="col-md-12 '.$lclass.'" id="'.$this->tag_id.'">'."\n";
                $result.="\t\t\t".'<div id="'.$this->tag_id.'" class="'.($this->base_class.(strlen($this->class)>0 ? ' '.$this->class : '')).'">'."\n";
                $result.=$this->GetFilterBox($params);
                $result.='</div>'."\n";
                break;
            default:
                $result='<div id="'.$this->tag_id.'" class="'.$lclass.'">'."\n";
                $result.="\t".'<div id="'.$this->tag_id.'" class="'.($this->base_class.(strlen($this->class)>0 ? ' '.$this->class : '')).'">'."\n";
                $result.=$this->GetFilterBox($params);
                $result.="\t".'</div>'."\n";
                $result.='</div>'."\n";
                break;
        }//END switch
        return $result;
    }//END private function SetControl
}//END class FilterBox extends FilterControl