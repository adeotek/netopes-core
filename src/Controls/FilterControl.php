<?php
/**
 * FilterControl abstract class file
 * Base abstract class for filter controls
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.3.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NApp;
use NETopes\Core\App\Params;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataProvider;
use Translate;

/**
 * Class FilterControl
 *
 * @package NETopes\Core\Controls
 */
abstract class FilterControl {
    /**
     * @var    string|null Control instance hash
     */
    protected $chash=NULL;
    /**
     * @var    array Filters values
     */
    protected $filters=[];
    /**
     * @var    string|null Filter condition type value source
     */
    protected $filter_cond_value_source=NULL;
    /**
     * @var    bool Items filterable config default value
     */
    protected $items_default_filterable_value=TRUE;
    /**
     * @var    string|null Control base CSS class
     */
    protected $base_class=NULL;
    /**
     * @var    string|null Page hash (window.name)
     */
    public $phash=NULL;
    /**
     * @var    string|null Main container id
     */
    public $tag_id=NULL;
    /**
     * @var    string|null Theme type
     */
    public $theme_type=NULL;
    /**
     * @var    bool Switch compact mode on/off
     */
    public $compact_mode=FALSE;
    /**
     * @var    string|null Controls size CSS prefix
     */
    public $controls_size=NULL;
    /**
     * @var    string|null Control target
     */
    public $target=NULL;
    /**
     * @var    mixed Ajax calls loader: 1=default loader; 0=no loader;
     * [string]=html element id or javascript function
     */
    public $loader=1;
    /**
     * @var    string|null Control elements CSS class
     */
    public $class=NULL;
    /**
     * @var    string|null Quick search data source parameter name (NULL=off)
     */
    public $qsearch=NULL;

    /**
     * FilterControl class constructor method
     *
     * @param array|null $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        $this->chash=AppSession::GetNewUID();
        $this->base_class='cls'.get_class_basename($this);
        $this->theme_type=is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
        $this->controls_size=NApp::$theme->GetControlsDefaultSize();
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                if(property_exists($this,$k)) {
                    $this->$k=$v;
                }
            }//foreach ($params as $k=>$v)
        }//if(is_array($params) && count($params))
    }//END public function __construct

    /**
     * Gets this instance as a serialized string
     *
     * @param bool $encrypted Switch on/off encrypted result
     * @return string Return serialized control instance
     */
    protected function GetThis($encrypted=TRUE): string {
        if($encrypted) {
            return GibberishAES::enc(serialize($this),$this->chash);
        }
        return serialize($this);
    }//END protected function GetThis

    /**
     * Sets new value for base class property
     *
     * @param string $value The new value to be set as base class
     * @return void
     */
    public function SetBaseClass($value): void {
        $this->base_class=strlen($value) ? $value : $this->base_class;
    }//END public function SetBaseClass

    /**
     * Gets the filter box html
     *
     * @param string|int Key (type) of the filter to be checked
     * @return bool Returns TRUE if filter is used and FALSE otherwise
     */
    protected function CheckIfFilterIsActive($key): bool {
        if(!is_numeric($key) && (!is_string($key) || !strlen($key))) {
            return FALSE;
        }
        if(!is_array($this->filters) || !count($this->filters)) {
            return FALSE;
        }
        foreach($this->filters as $f) {
            if((string)get_array_value($f,'type','','is_string')==(string)$key) {
                return TRUE;
            }
        }//END foreach
        return FALSE;
    }//protected function CheckIfFilterIsActive

    /**
     * Process the active filters (adds/removes filters)
     *
     * @param \NETopes\Core\App\Params $params Parameters for processing
     * @return array Returns the updated filters array
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessActiveFilters(Params $params): array {
        $action=$params->safeGet('faction',NULL,'is_notempty_string');
        // NApp::Dlog($action,'$action');
        // NApp::Dlog($this->filters,'$this->filters');
        if(!$action || !in_array($action,['add','remove','group_remove','clear'])) {
            return $this->filters ?? [];
        }
        if($action=='clear') {
            return [];
        }
        if($action=='remove') {
            $key=$params->safeGet('fkey',NULL,'is_string');
            if(!is_numeric($key) || !array_key_exists($key,$this->filters)) {
                return $this->filters;
            }
            $lFilters=$this->filters;
            unset($lFilters[$key]);
            return $lFilters;
        }//if($action=='remove')
        if($action=='group_remove') {
            $groupUid=$params->safeGet('group_uid',NULL,'is_string');
            return array_filter($this->filters,function($filter) use ($groupUid) {
                return $filter['group_uid']!==$groupUid;
            });
        }//if($action=='group_remove')
        $lFilters=$this->filters ?? [];
        $multiple=$params->safeGet('multi_f',[],'is_array');
        if(count($multiple)) {
            foreach($multiple as $fParams) {
                $op=get_array_value($fParams,'fop',NULL,'is_notempty_string');
                $type=get_array_value($fParams,'ftype',NULL,'is_notempty_string');
                $cond=get_array_value($fParams,'fcond',NULL,'is_notempty_string');
                $value=get_array_value($fParams,'fvalue',NULL,'isset');
                $sValue=get_array_value($fParams,'fsvalue',NULL,'isset');
                $dValue=get_array_value($fParams,'fdvalue',NULL,'isset');
                $sdValue=get_array_value($fParams,'fsdvalue',NULL,'isset');
                $fdType=get_array_value($fParams,'data_type','','is_string');
                $isDsParam=get_array_value($fParams,'is_ds_param',0,'is_numeric');
                $groupUid=get_array_value($fParams,'group_uid',uniqid(),'is_string');
                if(!$op || !isset($type) || !$cond || !isset($value)) {
                    continue;
                }
                $lFilters[]=['operator'=>$op,'type'=>$type,'condition_type'=>$cond,'value'=>$value,'svalue'=>$sValue,'dvalue'=>$dValue,'sdvalue'=>$sdValue,'data_type'=>$fdType,'is_ds_param'=>$isDsParam,'group_uid'=>$groupUid];
            }//END foreach
        } else {
            $op=$params->safeGet('fop',NULL,'is_notempty_string');
            $type=$params->safeGet('ftype',NULL,'is_notempty_string');
            $cond=$params->safeGet('fcond',NULL,'is_notempty_string');
            $value=$params->safeGet('fvalue',NULL,'isset');
            $sValue=$params->safeGet('fsvalue',NULL,'isset');
            $dValue=$params->safeGet('fdvalue',NULL,'isset');
            $sdValue=$params->safeGet('fsdvalue',NULL,'isset');
            $fdType=$params->safeGet('data_type','','is_string');
            $isDsParam=$params->safeGet('is_ds_param',0,'is_numeric');
            $groupUid=$params->safeGet('group_uid',uniqid(),'is_string');
            if(!$op || !isset($type) || !$cond || !isset($value)) {
                return $this->filters ?? [];
            }
            $lFilters[]=['operator'=>$op,'type'=>$type,'condition_type'=>$cond,'value'=>$value,'svalue'=>$sValue,'dvalue'=>$dValue,'sdvalue'=>$sdValue,'data_type'=>$fdType,'is_ds_param'=>$isDsParam,'group_uid'=>$groupUid];
        }//if(count($multif))
        // NApp::Dlog($lfilters,'$lfilters');
        return $lFilters;
    }//END protected function ProcessActiveFilters

    /**
     * Gets the action javascript command string
     *
     * @param string            $type
     * @param Params|array|null $params
     * @param string|null       $targetId
     * @param string|null       $method
     * @return string Returns action javascript command string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterActionCommand(string $type,Params $params,?string &$targetId=NULL,?string $method=NULL): ?string {
        $params=is_object($params) ? $params : new Params($params);
        $targetId=$this->target;
        switch($type) {
            case 'filters.apply':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->chash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'apply' } }";
                break;
            case 'filters.update':
                $method=$method ?? 'ShowFiltersBox';
                $targetId=$this->tag_id.'-filter-box';
                $fcType=$params->safeGet('fctype','','is_string');
                $command="{ 'control_hash': '{$this->chash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'update', 'group_uid': '{nGet|{$this->tag_id}-f-group:value}', 'fop': '{nGet|{$this->tag_id}-f-operator:value}', 'type': '{nGet|{$this->tag_id}-f-type:value}', 'f-cond-type': '".(strlen($fcType) ? '{nGet|'.$fcType.'}' : '')."' } }";
                break;
            case 'filters.remove':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->chash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'remove', 'fkey': '".$params->safeGet('fkey','','is_string')."', 'sessact': 'filters' } }";
                break;
            case 'filters.group_remove':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->chash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'group_remove', 'group_uid': '".$params->safeGet('group_uid','','is_string')."', 'sessact': 'filters' } }";
                break;
            case 'filters.clear':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->chash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction':'clear', 'sessact': 'filters' } }";
                break;
            case 'filters.add':
                $method=$method ?? 'Show';
                $fsValue=$params->safeGet('fsvalue','','is_string');
                $fdvalue=$params->safeGet('fdvalue','{nGet|'.$this->tag_id.'-f-value:value}','is_notempty_string');
                if(strlen($fdvalue) && strpos($fdvalue,'{nEval|')===FALSE && strpos($fdvalue,'{nGet|')===FALSE) {
                    $fdvalue='{nGet|'.$fdvalue.'}';
                }
                $fsdvalue=$params->safeGet('fsdvalue','','is_string');
                if(strlen($fsdvalue) && strpos($fsdvalue,'{nEval|')===FALSE && strpos($fsdvalue,'{nGet|')===FALSE) {
                    $fsdvalue='{nGet|'.$fsdvalue.'}';
                }
                $fDataType=$params->safeGet('data_type','','is_string');
                $isDsParam=$params->safeGet('is_ds_param',0,'is_numeric');
                $command="{ 'control_hash': '{$this->chash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'faction': 'add', 'sessact': 'filters', 'fop': '".((is_array($this->filters) && count($this->filters)) ? "{nGet|".$this->tag_id."-f-operator:value}" : 'and')."', 'ftype': '{nGet|{$this->tag_id}-f-type:value}', 'fcond': '{nGet|{$this->filter_cond_value_source}}', 'fvalue': '{nGet|".$params->safeGet('fvalue',$this->tag_id.'-f-value:value','is_notempty_string')."}', 'fsvalue': '".(strlen($fsValue) ? '{nGet|'.$fsValue.'}' : '')."', 'fdvalue': '{$fdvalue}', 'fsdvalue': '{$fsdvalue}', 'data_type': '{$fDataType}', 'is_ds_param': '{$isDsParam}', 'group_uid': '{nGet|{$this->tag_id}-f-group:value}' } }";
                break;
            default:
                $command=NULL;
                break;
        }//END switch
        return $command;
    }//END protected function GetFilterActionCommand

    /**
     * @param string|null $selectedValue
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterGroupControl(?string $selectedValue=NULL): ?string {
        $filtersGroups=array_group_by('group_uid',$this->filters);
        if(!count($filtersGroups)) {
            return NULL;
        }
        $result="\t\t\t\t".'<select id="'.$this->tag_id.'-f-group" class="clsComboBox form-control f-ctrl f-group">'."\n";
        $result.="\t\t\t\t\t".'<option value="'.uniqid().'"'.(strlen($selectedValue) ? '' : ' selected="selected"').'>'.Translate::GetLabel('new_group').'</option>'."\n";
        $gIndex=1;
        foreach($filtersGroups as $gid=>$group) {
            $lSelected=$selectedValue==$gid ? ' selected="selected"' : '';
            $result.="\t\t\t\t\t".'<option value="'.$gid.'"'.$lSelected.'>['.$gIndex++.']</option>'."\n";
        }//END foreach
        $result.="\t\t\t\t".'</select>'."\n";
        return $result;
    }//END protected function GetFilterGroupControl

    /**
     * @param string|null $selectedValue
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetLogicalSeparatorControl(?string $selectedValue=NULL): string {
        $result="\t\t\t\t".'<select id="'.$this->tag_id.'-f-operator" class="clsComboBox form-control f-ctrl f-operator">'."\n";
        /** @var \NETopes\Core\Data\VirtualEntity $c */
        foreach(DataProvider::Get('_Custom\Offline','FilterOperators') as $c) {
            $lSelected=$selectedValue==$c->getProperty('value') ? ' selected="selected"' : '';
            $result.="\t\t\t\t\t".'<option value="'.$c->getProperty('value').'"'.$lSelected.'>'.$c->getProperty('name').'</option>'."\n";
        }//END foreach
        $result.="\t\t\t\t".'</select>'."\n";
        return $result;
    }//END protected function GetLogicalSeparatorControl

    /**
     * @param array                    $items
     * @param \NETopes\Core\App\Params $params
     * @param string|null              $conditionType
     * @param array|null               $selectedItem
     * @param bool                     $isQuickSearch
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFiltersSelectorControl(array $items,Params $params,?string &$conditionType=NULL,?array &$selectedItem=NULL,bool &$isQuickSearch=FALSE): string {
        $result="\t\t\t\t".'<select id="'.$this->tag_id.'-f-type" class="clsComboBox form-control f-ctrl f-type" onchange="'.$this->GetActionCommand('filters.update').'">'."\n";
        $cfType=$params->safeGet('type','','is_string');
        $isQuickSearchActive=$this->CheckIfFilterIsActive(0);
        if($this->qsearch && !$isQuickSearchActive) {
            if($cfType=='0' || !strlen($cfType)) {
                $lSelected=' selected="selected"';
                $isQuickSearch=TRUE;
            } else {
                $lSelected='';
            }//if($cfType=='0' || !strlen($cfType))
            $result.="\t\t\t\t\t".'<option value="0"'.$lSelected.'>'.Translate::GetLabel('quick_search').'</option>'."\n";
        }//if($this->qsearch && !$isQuickSearchActive)
        foreach($items as $k=>$v) {
            if(!get_array_value($v,'filterable',$this->items_default_filterable_value,'bool')) {
                continue;
            }
            $isDsParam=intval(strlen(get_array_value($v,'ds_param','','is_string'))>0);
            if($isDsParam && $this->CheckIfFilterIsActive($k)) {
                continue;
            }
            if($cfType==$k || (!strlen($cfType) && !$isQuickSearch && !$selectedItem)) {
                $lSelected=' selected="selected"';
                $conditionType=get_array_value($v,'filter_type',NULL,'?is_string');
                $selectedItem=$v;
            } else {
                $lSelected='';
            }//if($cfType==$k || (!strlen($cfType) && !$isQuickSearch && !$selectedItem))
            $result.="\t\t\t\t\t".'<option value="'.$k.'"'.$lSelected.'>'.get_array_value($v,'label',$k,'is_notempty_string').'</option>'."\n";
        }//END foreach
        $result.="\t\t\t".'</select>'."\n";
        return $result;
    }//END protected function GetFiltersSelectorControl

    /**
     * @param \NETopes\Core\App\Params $params
     * @param string|null              $conditionType
     * @param array|null               $selectedItem
     * @param bool                     $isQuickSearch
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetConditionTypeControl(Params $params,?string $conditionType,?array $selectedItem,bool $isQuickSearch): string {
        $fDataType=get_array_value($selectedItem,'data_type','','is_string');
        $fConditionType=$params->safeGet('f-cond-type','','is_string');
        $this->filter_cond_value_source=$this->tag_id.'-f-cond-type:value';
        $fConditionDisplayType=get_array_value($selectedItem,'show_filter_cond_type',get_array_value($selectedItem,'show_filter_cond_type',TRUE,'bool'),'is_notempty_string');
        if($fConditionDisplayType===TRUE && !$isQuickSearch) {
            $filterOptions='';
            $filterConditionTypeOnChange='';
            $fConditionsType=strtolower(strlen($conditionType) ? $conditionType : $fDataType);
            $fConditions=DataProvider::Get('_Custom\Offline','FilterConditionsTypes',['type'=>$fConditionsType]);
            /** @var \NETopes\Core\Data\VirtualEntity $c */
            foreach($fConditions as $c) {
                $lSelected=$fConditionType==$c->getProperty('value') ? ' selected="selected"' : '';
                $filterOptions.="\t\t\t\t".'<option value="'.$c->getProperty('value').'"'.$lSelected.'>'.$c->getProperty('name').'</option>'."\n";
                if(!strlen($filterConditionTypeOnChange) && $c->getProperty('value')=='><') {
                    $filterConditionTypeOnChange=' onchange="'.$this->GetActionCommand('filters.update',['fctype'=>$this->tag_id.'-f-cond-type:value']).'"';
                }//if(!strlen($filterConditionTypeOnChange) && $c->getProperty('value')=='><')
            }//END foreach
            $result="\t\t\t".'<select id="'.$this->tag_id.'-f-cond-type" class="clsComboBox form-control f-ctrl f-cond-type"'.$filterConditionTypeOnChange.'>'."\n";
            $result.=$filterOptions;
            $result.="\t\t\t".'</select>'."\n";
        } elseif($fConditionDisplayType!=='data') {
            $result="\t\t\t\t".'<input type="hidden" id="'.$this->tag_id.'-f-cond-type" value="'.($isQuickSearch ? 'like' : '==').'">'."\n";
        } else {
            $this->filter_cond_value_source=NULL;
            $result='';
        }//if($fConditionDisplayType===TRUE && !$isQuickSearch)
        return $result;
    }//END protected function GetConditionTypeControl

    /**
     * Get filter box actions HTML string
     *
     * @param string $onClick
     * @return string Returns actions HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterAddAction(string $onClick): string {
        if($this->compact_mode) {
            $result="\t\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="'.NApp::$theme->GetBtnInfoClass('f-add-btn compact clsTitleSToolTip').'" onclick="'.$onClick.'" title="'.Translate::GetButton('add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
        } else {
            $result="\t\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="'.NApp::$theme->GetBtnInfoClass('f-add-btn').'" onclick="'.$onClick.'"><i class="fa fa-plus"></i>'.Translate::GetButton('add_filter').'</button>'."\n";
        }//if($this->compact_mode)
        return $result;
    }//END protected function GetFilterAddAction

    /**
     * @param \NETopes\Core\App\Params $params
     * @param string|null              $conditionType
     * @param array|null               $selectedItem
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterValueControl(Params $params,?string $conditionType,?array $selectedItem): string {
        $fDataType=get_array_value($selectedItem,'data_type','','is_string');
        $isDsParam=intval(strlen(get_array_value($selectedItem,'ds_param','','is_string'))>0);
        $fConditionType=$params->safeGet('f-cond-type','','is_string');
        $ctrlParams=get_array_value($selectedItem,'filter_params',[],'is_array');
        $ctrlParams['tag_id']=$this->tag_id.'-f-value';
        $ctrlParams['class']='f-ctrl f-value';
        $ctrlParams['container']=FALSE;
        $ctrlParams['no_label']=TRUE;
        $ctrlParams['postable']=FALSE;
        $aOnClickCheck=NULL;
        $fValue=NULL;
        $fsValue=NULL;
        $sdValue=NULL;
        $fSubType=NULL;
        switch(strtolower($conditionType)) {
            case 'smartcombobox':
                $ctrlParams['placeholder']=get_array_value($ctrlParams,'placeholder',Translate::GetLabel('please_select'),'is_notempty_string');
                $ctrlParams['allow_clear']=get_array_value($ctrlParams,'allow_clear',TRUE,'is_bool');
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedv,'filter_data_source',NULL,'is_notempty_array');
                }//if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $filterValueControl=new SmartComboBox($ctrlParams);
                $dValue='{nEval|GetSmartCBOText(\''.$this->tag_id.'-f-value\',false)}';
                if(!$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                $onclickAction=$this->GetActionCommand('filters.add',['fdvalue'=>$dValue,'fvalue'=>$fValue,'data_type'=>$fDataType,'is_ds_param'=>$isDsParam]);
                if(strlen($aOnClickCheck)) {
                    $onclickAction=$aOnClickCheck.' { '.$onclickAction.' }';
                }
                $result.=$this->GetFilterAddAction($onclickAction);
                break;
            case 'combobox':
                if(!isset($ctrlParams['please_select_text']) || !strlen($ctrlParams['please_select_text'])) {
                    $ctrlParams['please_select_text']=Translate::GetLabel('please_select');
                    $ctrlParams['please_select_value']=NULL;
                }//if(!isset($ctrlParams['please_select_text']) || !strlen($ctrlParams['please_select_text']))
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedItem,'filter_data_source',NULL,'is_notempty_array');
                }//if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $filterValueControl=new ComboBox($ctrlParams);
                $dValue=$this->tag_id.'-f-value:option';
                if(!$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                $onclickAction=$this->GetActionCommand('filters.add',['fdvalue'=>$dValue,'fvalue'=>$fValue,'data_type'=>$fDataType,'is_ds_param'=>$isDsParam]);
                if(strlen($aOnClickCheck)) {
                    $onclickAction=$aOnClickCheck.' { '.$onclickAction.' }';
                }
                $result.=$this->GetFilterAddAction($onclickAction);
                break;
            case 'treecombobox':
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                $ctrlParams['data_source']=get_array_value($selectedItem,'filter_data_source',NULL,'is_notempty_array');
                $filterValueControl=new TreeComboBox($ctrlParams);
                $dValue=$this->tag_id.'-f-value-cbo:value';
                if(!$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                $onclickAction=$this->GetActionCommand('filters.add',['fdvalue'=>$dValue,'fvalue'=>$fValue,'data_type'=>$fDataType,'is_ds_param'=>$isDsParam]);
                if(strlen($aOnClickCheck)) {
                    $onclickAction=$aOnClickCheck.' { '.$onclickAction.' }';
                }
                $result.=$this->GetFilterAddAction($onclickAction);
                break;
            case 'checkbox':
                $ctrlParams['value']=0;
                $filterValueControl=new CheckBox($ctrlParams);
                $dValue=$this->tag_id.'-f-value:value';
                if(!$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                }
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                $onclickAction=$this->GetActionCommand('filters.add',['fdvalue'=>$dValue,'fvalue'=>$fValue,'data_type'=>$fDataType,'is_ds_param'=>$isDsParam]);
                if(strlen($aOnClickCheck)) {
                    $onclickAction=$aOnClickCheck.' { '.$onclickAction.' }';
                }
                $result.=$this->GetFilterAddAction($onclickAction);
                break;
            case 'datepicker':
            case 'date':
            case 'datetime':
                $fSubType='DatePicker';
            case 'numerictextbox':
            case 'numeric':
                $fSubType=$fSubType ? $fSubType : 'NumericTextBox';
            default:
                if(!$fSubType) {
                    switch($fDataType) {
                        case 'date':
                        case 'date_obj':
                        case 'datetime':
                        case 'datetime_obj':
                            $fSubType='DatePicker';
                            break;
                        case 'numeric':
                            $fSubType='NumericTextBox';
                            break;
                        default:
                            $fSubType='TextBox';
                            break;
                    }//END switch
                }//if(!$fSubType)
                switch($fSubType) {
                    case 'DatePicker':
                        $ctrlParams['value']='';
                        $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                        if(strtolower($conditionType)!='date' && ($fDataType=='datetime' || $fDataType=='datetime_obj')) {
                            $ctrlParams['timepicker']=TRUE;
                        } else {
                            $ctrlParams['timepicker']=FALSE;
                        }//if(strtolower($conditionType)!='date' && ($fDataType=='datetime' || $fDataType=='datetime_obj'))
                        $ctrlParams['align']='center';
                        $filterValueControl=new DatePicker($ctrlParams);
                        // $filterValueControl->ClearBaseClass();
                        $result="\t\t\t\t".$filterValueControl->Show()."\n";
                        $fValue=$this->tag_id.'-f-value:dvalue';
                        if($fConditionType=='><') {
                            $result.="\t\t\t".'<span class="f-i-lbl">'.Translate::GetLabel('and').'</span>'."\n";
                            $ctrlParams['tag_id']=$this->tag_id.'-f-svalue';
                            // $filterValueControl=new DatePicker($ctrlParams);
                            // $filterValueControl->ClearBaseClass();
                            $result.="\t\t\t".$filterValueControl->Show()."\n";
                            $fsValue=$this->tag_id.'-f-svalue:dvalue';
                            $sdValue=$this->tag_id.'-f-svalue:value';
                            if(!$this->filter_cond_value_source) {
                                $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                            }
                        }//if($fConditionType=='><')
                        break;
                    case 'NumericTextBox':
                        $ctrlParams['class'].=' t-box';
                        $ctrlParams['value']='';
                        $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                        $ctrlParams['number_format']=get_array_value($selectedItem,'filter_format','0|||','is_notempty_string');
                        $ctrlParams['align']='center';
                        $filterValueControl=new NumericTextBox($ctrlParams);
                        // $filterValueControl->ClearBaseClass();
                        $result="\t\t\t\t".$filterValueControl->Show()."\n";
                        $fValue=$this->tag_id.'-f-value:nvalue';
                        if($fConditionType=='><') {
                            $result.="\t\t\t\t".'<span class="f-i-lbl">'.Translate::GetLabel('and').'</span>'."\n";
                            $ctrlParams['tag_id']=$this->tag_id.'-f-svalue';
                            // $filterValueControl=new NumericTextBox($ctrlParams);
                            // $filterValueControl->ClearBaseClass();
                            $result.="\t\t\t\t".$filterValueControl->Show()."\n";
                            $fsValue=$this->tag_id.'-f-svalue:nvalue';
                            $sdValue=$this->tag_id.'-f-svalue:value';
                            if(!$this->filter_cond_value_source) {
                                $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                            }
                        }//if($fConditionType=='><')
                        break;
                    case 'TextBox':
                    default:
                        $ctrlParams['class'].=' t-box';
                        $ctrlParams['value']='';
                        $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                        $filterValueControl=new TextBox($ctrlParams);
                        // $filterValueControl->ClearBaseClass();
                        $result="\t\t\t\t".$filterValueControl->Show()."\n";
                        $fValue=$this->tag_id.'-f-value:value';
                        if($fConditionType=='><') {
                            $result.="\t\t\t\t".'<span class="f-i-lbl">'.Translate::GetLabel('and').'</span>'."\n";
                            $ctrlParams['tag_id']=$this->tag_id.'-f-svalue';
                            // $filterValueControl=new TextBox($ctrlParams);
                            // $filterValueControl->ClearBaseClass();
                            $result.="\t\t\t\t".$filterValueControl->Show()."\n";
                            $fsValue=$this->tag_id.'-f-svalue:value';
                            $sdValue=$this->tag_id.'-f-svalue:value';
                            if(!$this->filter_cond_value_source) {
                                $this->filter_cond_value_source=$this->tag_id.'-f-value:option:data-ctype';
                            }
                        }//if($fConditionType=='><')
                        break;
                }//END switch
                $dValue=$this->tag_id.'-f-value:value';
                if($fConditionType=='><') {
                    $onclickAction=$this->GetActionCommand('filters.add',['fdvalue'=>$dValue,'fsdvalue'=>$sdValue,'fvalue'=>$fValue,'fsvalue'=>$fsValue,'data_type'=>$fDataType,'is_ds_param'=>$isDsParam]);
                } else {
                    $onclickAction=$this->GetActionCommand('filters.add',['fdvalue'=>$dValue,'fvalue'=>$fValue,'data_type'=>$fDataType,'is_ds_param'=>$isDsParam]);
                }
                if(strlen($aOnClickCheck)) {
                    $onclickAction=$aOnClickCheck.' { '.$onclickAction.' }';
                }
                $result.=$this->GetFilterAddAction($onclickAction);
                break;
        }//END switch
        return $result;
    }//END protected function GetFilterValueControl

    /**
     * Get current filter controls HTML string
     *
     * @param array                    $items
     * @param \NETopes\Core\App\Params $params
     * @return string Returns HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterControls(array $items,Params $params): string {
        if(is_array($this->filters) && count($this->filters)) {
            $result=$this->GetFilterGroupControl($params->safeGet('group_uid','','is_string'));
            $result.=$this->GetLogicalSeparatorControl($params->safeGet('fop','','is_string'));
        } else {
            $result="\t\t\t\t".'<input id="'.$this->tag_id.'-f-group" type="hidden" value="'.uniqid().'">'."\n";
            $result.="\t\t\t\t".'<input id="'.$this->tag_id.'-f-operator" type="hidden" value="'.$params->safeGet('fop','','is_string').'">'."\n";
        }//if(is_array($this->filters) && count($this->filters))
        $conditionType=$selectedItem=NULL;
        $isQuickSearch=FALSE;
        $result.=$this->GetFiltersSelectorControl($items,$params,$conditionType,$selectedItem,$isQuickSearch);
        $result.=$this->GetConditionTypeControl($params,$conditionType,$selectedItem,$isQuickSearch);
        $result.=$this->GetFilterValueControl($params,$conditionType,$selectedItem);
        return $result;
    }//END protected function GetFilterControls

    /**
     * Get filter box actions HTML string
     *
     * @param bool $withApply
     * @return string Returns actions HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterGlobalActions(bool $withApply=FALSE): string {
        if($this->compact_mode) {
            $result="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnDefaultClass('f-clear-btn compact clsTitleSToolTip').'" onclick="'.$this->GetActionCommand('filters.clear').'" title="'.Translate::GetButton('clear_filters').'"><i class="fa fa-times"></i></button>'."\n";
        } else {
            $result="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnDefaultClass('f-clear-btn').'" onclick="'.$this->GetActionCommand('filters.clear').'"><i class="fa fa-times"></i>'.Translate::GetButton('clear_filters').'</button>'."\n";
        }//if($this->compact_mode)
        if(!$withApply) {
            return $result;
        }
        if($this->compact_mode) {
            $result.="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('f-apply-btn compact clsTitleSToolTip').'" onclick="'.$this->GetActionCommand('filters.apply').'" title="'.Translate::GetButton('apply_filters').'"><i class="fa fa-filter" aria-hidden="true"></i></button>'."\n";
        } else {
            $result.="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('f-apply-btn').'" onclick="'.$this->GetActionCommand('filters.apply').'"><i class="fa fa-filter" aria-hidden="true"></i>'.Translate::GetButton('apply_filters').'</button>'."\n";
        }//if($this->compact_mode)
        return $result;
    }//END protected function GetFilterGlobalActions

    /**
     * Get active filters HTML string
     *
     * @param array $items Filter configuration items
     * @return string|null Returns active filters HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActiveFilters(array $items): ?string {
        if(!is_array($this->filters) || !count($this->filters)) {
            return NULL;
        }
        $filters="\t\t\t\t".'<div class="f-active-items">'."\n";
        $filters.="\t\t\t\t\t".'<span class="f-active-title">'.Translate::GetTitle('active_filters').':</span>'."\n";
        $filtersGroups=array_group_by('group_uid',$this->filters);
        $fcTypes=DataProvider::GetKeyValue('_Custom\Offline','FilterConditionsTypes',['type'=>'all'],['keyfield'=>'value']);
        $gLogicalOperator=NULL;
        $gIndex=1;
        foreach($filtersGroups as $gid=>$group) {
            if(!is_array($group) || !count($group)) {
                continue;
            }
            $fLogicalOperator=NULL;
            if(is_null($gLogicalOperator)) {
                $gLogicalOperator='';
            } else {
                $gLogicalOperator=Translate::GetLabel(get_array_value($group,[0,'operator'],'','is_string')).' ';
            }//if(is_null($gLogicalOperator))
            $filters.="\t\t\t\t\t".'<div class="f-items-group">'.$gLogicalOperator.Translate::GetLabel('group').' ['.$gIndex++.']'."\n";
            $filters.="\t\t\t\t\t\t".'<div class="g-remove" onclick="'.$this->GetActionCommand('filters.group_remove',['group_uid'=>$gid]).'"><i class="fa fa-times"></i></div>'."\n";
            foreach($group as $k=>$a) {
                $fLogicalOperator=is_null($fLogicalOperator) ? '' : Translate::GetLabel($a['operator']).' ';
            if($a['condition_type']=='><') {
                $filters.="\t\t\t\t".'<div class="f-active-item"><div class="b-remove" onclick="'.$this->GetActionCommand('filters.remove',['fkey'=>$k]).'"><i class="fa fa-times"></i></div>'.$fLogicalOperator.'<strong>'.((is_numeric($a['type']) && $a['type']==0) ? Translate::GetLabel('qsearch') : get_array_value($items[$a['type']],'label',$a['type'],'is_notempty_string')).'</strong>&nbsp;'.$fcTypes->safeGet($a['condition_type'])->getProperty('name').'&nbsp;&quot;<strong>'.$a['dvalue'].'</strong>&quot;&nbsp;'.Translate::GetLabel('and').'&nbsp;&quot;<strong>'.$a['sdvalue'].'</strong>&quot;</div>'."\n";
            } else {
                $filters.="\t\t\t\t".'<div class="f-active-item"><div class="b-remove" onclick="'.$this->GetActionCommand('filters.remove',['fkey'=>$k]).'"><i class="fa fa-times"></i></div>'.$fLogicalOperator.'<strong>'.((is_numeric($a['type']) && $a['type']==0) ? Translate::GetLabel('qsearch') : get_array_value($items[$a['type']],'label',$a['type'],'is_notempty_string')).'</strong>&nbsp;'.$fcTypes->safeGet($a['condition_type'])->getProperty('name').'&nbsp;&quot;<strong>'.$a['dvalue'].'</strong>&quot;</div>'."\n";
            }//if($a['condition_type']=='><')
            }//END foreach
            $filters.="\t\t\t\t\t".'</div>'."\n";
        }//END foreach
        $filters.="\t\t\t".'</div>'."\n";
        return $filters;
    }//END protected function GetActiveFilters

    /**
     * Gets the control's content (html)
     *
     * @param Params|array|null $params An array of parameters
     *                                  * phash (string) = new page hash (window.name)
     *                                  * output (bool|numeric) = flag indicating direct (echo)
     *                                  or indirect (return) output (default FALSE - indirect (return) output)
     *                                  * other pass through params
     * @return string Returns the control's content (html)
     * @throws \NETopes\Core\AppException
     */
    public function Show($params=NULL): ?string {
        // NApp::Dlog($params,'FilterControl>>Show');
        $oParams=is_object($params) ? $params : new Params($params);
        $pHash=$oParams->safeGet('phash',NULL,'?is_notempty_string');
        $output=$oParams->safeGet('output',FALSE,'bool');
        if($pHash) {
            $this->phash=$pHash;
        }
        if(!$output) {
            return $this->SetControl($oParams);
        }
        echo $this->SetControl($oParams);
        return NULL;
    }//END public function Show

    /**
     * Gets (shows) the control's filters box content
     *
     * @param array $params An array of parameters
     *                      * phash (string) = new page hash (window.name)
     *                      * output (bool|numeric) = flag indicating direct (echo)
     *                      or indirect (return) output (default FALSE - indirect (return) output)
     *                      * other pass through params
     * @return string|null Returns the control's filters box content
     * @throws \NETopes\Core\AppException
     */
    public function ShowFiltersBox($params=NULL): ?string {
        $o_params=is_object($params) ? $params : new Params($params);
        $phash=$o_params->safeGet('phash',NULL,'is_notempty_string');
        $output=$o_params->safeGet('output',FALSE,'bool');
        if($phash) {
            $this->phash=$phash;
        }
        if(!$output) {
            return $this->GetFilterBox($o_params);
        }
        echo $this->GetFilterBox($o_params);
        return NULL;
    }//END public function ShowFiltersBox

    /**
     * Gets the action javascript command string
     *
     * @param string            $type
     * @param Params|array|null $params
     * @param bool              $processCall
     * @return string Returns action javascript command string
     * @throws \NETopes\Core\AppException
     */
    abstract protected function GetActionCommand(?string $type=NULL,$params=NULL,bool $processCall=TRUE): ?string;

    /**
     * Generate and return the control HTML string
     *
     * @param \NETopes\Core\App\Params|null $params
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    abstract protected function SetControl(Params $params=NULL): ?string;

    /**
     * Gets the filter box HTML
     *
     * @param \NETopes\Core\App\Params|null $params
     * @return string|null Returns the filter box HTML string
     * @throws \NETopes\Core\AppException
     */
    abstract protected function GetFilterBox(Params $params=NULL): ?string;
}//END abstract class FilterControl