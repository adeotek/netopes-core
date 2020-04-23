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
use NETopes\Core\Data\VirtualEntity;
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
    protected $cHash=NULL;
    /**
     * @var    array Active filters array
     * Item elements:
     * - 'type'                     >> 'f_type'      : string (mandatory)   - filter type (filter field or unique key)
     * - 'group_id'                 >> 'g_id'        : string (mandatory)   - group id
     * - 'logical_separator'         >> 'l_op'        : string (mandatory)   - logical operator
     * - 'condition_type'           >> 'f_c_type'    : string (mandatory)   - filter condition type
     * - 'field'                    >> 'f_field'     : string               - filter field
     * - 'data_type'                >> 'f_d_type'    : string               - filter data type
     * - 'value'                    >> 'f_value'     : mixed (mandatory)    - filter value
     * - 'display_value'            >> 'f_d_value'   : mixed                - filter display value
     * - 'end_value'                >> 'f_e_value'   : mixed                - filter end value
     * - 'end_display_value'        >> 'f_e_d_value' : string               - filter value field
     * - 'value_field'              >> 'v_field'     : string               - filter value field
     * - 'value_data_type'          >> 'v_d_type'    : string               - filter value data type
     * - 'value_value'              >> 'v_value'     : mixed                - filter value value
     * - 'value_display_value'      >> 'v_d_value'   : string               - filter value display value
     * - 'value_end_value'          >> 'v_e_value'   : mixed                - filter value end value
     * - 'value_end_display_value'  >> 'v_e_d_value' : mixed                - filter value end display value
     */
    protected $filters=[];
    /**
     * @var    array Filters groups list
     */
    protected $groups=[];
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
     * @var    string|null Buttons size CSS class
     */
    protected $buttons_size_class=NULL;
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
     * @var    bool Switch filter groups on/off
     */
    public $with_filter_groups=FALSE;
    /**
     * @var    array|null Initial filters values (are destroyed after construct)
     */
    public $initial_filters=NULL;
    /**
     * @var    bool Switch active filter title on/off
     */
    public $active_filters_title=FALSE;

    /**
     * FilterControl class constructor method
     *
     * @param array|null $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        $this->cHash=AppSession::GetNewUID();
        $this->base_class='cls'.get_class_basename($this);
        $this->theme_type=is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
        $this->controls_size=NApp::$theme->GetControlsDefaultSize();
        $this->buttons_size_class=(is_string($this->controls_size) && strlen($this->controls_size) ? ' btn-'.$this->controls_size : '');
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                if(property_exists($this,$k)) {
                    $this->$k=$v;
                }
            }//foreach ($params as $k=>$v)
        }//if(is_array($params) && count($params))
        $this->ProcessInitialFilters($this->initial_filters);
    }//END public function __construct

    /**
     * Gets this instance as a serialized string
     *
     * @param bool $encrypted Switch on/off encrypted result
     * @return string Return serialized control instance
     */
    protected function GetThis($encrypted=TRUE): string {
        if($encrypted) {
            return GibberishAES::enc(serialize($this),$this->cHash);
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
     * Check if filter is active
     *
     * @param string|null Key (type) of the filter to be checked
     * @return bool Returns TRUE if filter is used and FALSE otherwise
     */
    protected function CheckIfFilterIsActive(?string $key): bool {
        if(!strlen($key) || !is_array($this->filters) || !count($this->filters)) {
            return FALSE;
        }
        foreach($this->filters as $f) {
            if(get_array_value($f,'type','','is_string')==$key) {
                return TRUE;
            }
        }//END foreach
        return FALSE;
    }//protected function CheckIfFilterIsActive

    /**
     * Generate multi-dimensional array with filter groups from an array of filters
     *
     * @param array|null $filters
     * @return array Returns filter groups array
     */
    protected function GenerateFilterGroups(?array $filters): array {
        if(!is_array($filters) || !count($filters)) {
            return [];
        }
        $groups=array_group_by_hierarchical('group_id',$filters,FALSE,'_');
        return $groups;
    }//END protected function GenerateFilterGroups

    /**
     * Process the active filters (adds/removes filters)
     *
     * @param array|null $initialFilters
     */
    protected function ProcessInitialFilters(?array &$initialFilters): void {
        // NApp::Dlog($initialFilters,'ProcessInitialFilters>>$initialFilters');
        if(!is_array($this->filters)) {
            $this->filters=[];
        }
        if(!is_array($initialFilters) || !count($initialFilters)) {
            $initialFilters=NULL;
            return;
        }
        foreach($initialFilters as $filter) {
            $fField=get_array_value($filter,'field',NULL,'?is_string');
            $fType=get_array_value($filter,'type',NULL,'?is_string');
            $cType=get_array_value($filter,'condition_type',NULL,'?is_string');
            $operator=get_array_value($filter,'logical_separator',NULL,'?is_string');
            if((!strlen($fType) && !strlen($fField)) || !strlen($cType) || !strlen($operator)) {
                continue;
            }
            $fValue=get_array_value($filter,'value');
            $fDisplayValue=get_array_value($filter,'display_value',NULL,'is_string');
            $this->filters[]=[
                'type'=>$fType ?? $fField,
                'group_id'=>get_array_value($filter,'group_id',NULL,'?is_string'),
                'logical_separator'=>$operator,
                'is_ds_param'=>get_array_value($filter,'is_ds_param',0,'is_integer'),
                'condition_type'=>$cType,
                'data_type'=>get_array_value($filter,'data_type',NULL,'?is_string'),
                'field'=>$fField,
                'value'=>$fValue,
                'end_value'=>get_array_value($filter,'end_value'),
                'display_value'=>$fDisplayValue ?? $fValue,
                'end_display_value'=>get_array_value($filter,'end_display_value',NULL,'is_string'),
                'value_field'=>get_array_value($filter,'value_field',NULL,'?is_string'),
                'value_data_type'=>get_array_value($filter,'value_data_type',NULL,'?is_string'),
                'value_value'=>get_array_value($filter,'value_value'),
                'value_end_value'=>get_array_value($filter,'value_end_value'),
                'value_display_value'=>get_array_value($filter,'value_display_value',NULL,'is_string'),
                'value_end_display_value'=>get_array_value($filter,'value_end_display_value',NULL,'is_string'),
                'guid'=>uniqid(),
            ];
        }//END foreach
        // NApp::Dlog($this->filters,'$this->filters');
        $this->groups=$this->GenerateFilterGroups($this->filters);
        // NApp::Dlog($this->groups,'$this->groups');
        $initialFilters=NULL;
    }//END protected function ProcessInitialFilters

    /**
     * Process active filter item
     *
     * @param \NETopes\Core\App\Params $item
     * @param array                    $filters
     * @return bool Returns TRUE if filter is valid and FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessFilterItem(Params $item,array &$filters): bool {
        // NApp::Dlog($item->toArray(),'ProcessFilterItem>>$item');
        $op=$item->safeGet('l_op',NULL,'?is_string');
        $cType=$item->safeGet('f_c_type',NULL,'?is_string');
        $type=$item->safeGet('f_type',NULL,'?is_string');
        if(!strlen($op) || !strlen($cType) || !strlen($type)) {
            return FALSE;
        }
        $isDsParam=$item->safeGet('is_ds_param',0,'is_numeric');
        $groupId=$item->safeGet('g_id',NULL,'?is_string');
        $groupType=$item->safeGet('g_type',1,'is_integer');
        // filter
        $dType=$item->safeGet('f_d_type','','is_string');
        $field=$item->safeGet('f_field',NULL,'?is_string');
        if(in_array($dType,['','string'])) {
            $value=$item->safeGet('f_value',NULL,'?is_string');
            $eValue=$item->safeGet('f_e_value',NULL,'?is_string');
        } else {
            $value=$item->safeGet('f_value',NULL,'?is_notempty_string');
            $eValue=$item->safeGet('f_e_value',NULL,'?is_notempty_string');
        }//if(in_array($dType,['','string']))
        $dValue=$item->safeGet('f_d_value',NULL,'is_string');
        $edValue=$item->safeGet('f_e_d_value',NULL,'is_string');
        // filter value field
        $vField=$item->safeGet('v_field',NULL,'?is_string');
        $vdType=$item->safeGet('v_d_type',NULL,'?is_string');
        if(in_array($vdType,['','string'])) {
            $vValue=$item->safeGet('v_value',NULL,'?is_string');
            $veValue=$item->safeGet('v_e_value',NULL,'?is_string');
        } else {
            $vValue=$item->safeGet('v_value',NULL,'?is_notempty_string');
            $veValue=$item->safeGet('v_e_value',NULL,'?is_notempty_string');
        }//if(in_array($dType,['','string']))
        $vdValue=$item->safeGet('v_d_value',NULL,'isset');
        $vedValue=$item->safeGet('v_e_d_value',NULL,'isset');
        if($groupType==0) {
            $groupId='_'.(count($this->groups) + 1);
        } elseif($groupType==2) {
            $mKey=array_map(function($i) { return '_'.$i; },explode('-',trim($groupId,'_')));
            $currentGroup=get_array_value($this->groups,$mKey,[],'is_array');
            $groupId.='-'.(count($currentGroup) + 1);
        } elseif(!strlen($groupId)) {
            $groupId='_1';
        }
        $filters[]=['type'=>$type,'group_id'=>$groupId,'logical_separator'=>$op,'is_ds_param'=>$isDsParam,'condition_type'=>$cType,'data_type'=>$dType,'field'=>$field,'value'=>$value,'end_value'=>$eValue,'display_value'=>$dValue,'end_display_value'=>$edValue,'value_field'=>$vField,'value_data_type'=>$vdType,'value_value'=>$vValue,'value_end_value'=>$veValue,'value_display_value'=>$vdValue,'value_end_display_value'=>$vedValue,'guid'=>uniqid()];
        return TRUE;
    }//END protected function ProcessFilterItem

    /**
     * Process the active filters (adds/removes filters)
     *
     * @param \NETopes\Core\App\Params $params Parameters for processing
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessActiveFilters(Params &$params): void {
        // NApp::Dlog($params->toArray(),'ProcessActiveFilters>>$params');
        $action=$params->safeGet('f_action',NULL,'is_notempty_string');
        // NApp::Dlog($action,'$action');
        // NApp::Dlog($this->filters,'$this->filters');
        if(!is_array($this->filters)) {
            $this->filters=[];
        }
        if(!$action || !in_array($action,['add','remove','group_remove','clear'])) {
            $this->groups=$this->GenerateFilterGroups($this->filters);
            return;
        }
        if($action=='clear') {
            $params->clear();
            $this->filters=[];
            $this->groups=[];
            return;
        }
        if($action=='remove') {
            $key=$params->safeGet('f_guid',NULL,'is_string');
            if(strlen($key)) {
                $this->filters=array_filter($this->filters,function($filter) use ($key) {
                    return $filter['guid']!==$key;
                });
            }
        } elseif($action=='group_remove') {
            $groupId=$params->safeGet('g_id',NULL,'?is_string');
            if(strlen($groupId)) {
                $this->filters=array_filter($this->filters,function($filter) use ($groupId) {
                    return $filter['group_id']!==$groupId;
                });
            }//if(strlen($groupId))
        } else {
            $this->ProcessFilterItem($params,$this->filters);
            $multiple=$params->safeGet('multi_filters',[],'is_array');
            if(count($multiple)) {
                foreach($multiple as $fParams) {
                    $this->ProcessFilterItem(new Params($fParams),$this->filters);
                }//END foreach
            }//if(count($multiple))
        }//if($action=='remove')
        // NApp::Dlog($this->filters,'$this->filters');
        $this->groups=$this->GenerateFilterGroups($this->filters);
        $params->clear();
        // NApp::Dlog($this->groups,'$this->groups');
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
        $targetId=$this->target;
        switch($type) {
            case 'filters.render':
                $method=$method ?? 'ShowFiltersBox';
                $targetId=$this->tag_id.'-filter-box';
                $conditionType=$params->safeGet('f_c_type','','is_string');
                $command="{ 'control_hash': '{$this->cHash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action': 'render', 'f_type': '{nGet|{$this->tag_id}-f-type:value}', 'g_id': '{nGet|{$this->tag_id}-f-group:value}', 'g_type': '{nGet|{$this->tag_id}-f-group-type:value}', 'l_op': '{nGet|{$this->tag_id}-f-l-op:value}',  'f_c_type': '".(strlen($conditionType) ? '{nGet|'.$conditionType.'}' : '')."',";
                if($params->safeGet('f_v_f_mode',FALSE,'bool')) {
                    $fdValue=$params->safeGet('f_d_value','{nGet|'.$this->tag_id.'-f-value:value}','is_notempty_string');
                    if(strlen($fdValue) && strpos($fdValue,'{nEval|')===FALSE && strpos($fdValue,'{nGet|')===FALSE) {
                        $fdValue='{nGet|'.$fdValue.'}';
                    }
                    $command.=" 'f_value': '{nGet|".$params->safeGet('f_value',$this->tag_id.'-f-value:value','is_notempty_string')."}', 'f_d_value': '{$fdValue}', ";
                }//if($params->safeGet('f_v_f_mode',FALSE,'bool'))
                $command.=" 'sessact': 'filters' } }";
                break;
            case 'filters.add':
                $method=$method ?? 'Show';
                $dataType=$params->safeGet('f_d_type','','is_string');
                $field=$params->safeGet('f_field','','is_string');
                $isDsParam=$params->safeGet('is_ds_param',0,'is_integer');
                $fdValue=$params->safeGet('f_d_value','{nGet|'.$this->tag_id.'-f-value:value}','is_notempty_string');
                if(strlen($fdValue) && strpos($fdValue,'{nEval|')===FALSE && strpos($fdValue,'{nGet|')===FALSE) {
                    $fdValue='{nGet|'.$fdValue.'}';
                }
                $command="{ 'control_hash': '{$this->cHash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action': 'add', 'f_type': '{nGet|{$this->tag_id}-f-type:value}', 'f_d_type': '{$dataType}',".(strlen($field) ? " 'f_field': '{$field}'," : '')." 'g_id': '{nGet|{$this->tag_id}-f-group:value}', 'g_type': '{nGet|{$this->tag_id}-f-group-type:value}', 'l_op': '{nGet|{$this->tag_id}-f-l-op:value}',  'f_c_type': '{nGet|{$this->filter_cond_value_source}}', 'f_value': '{nGet|".$params->safeGet('f_value',$this->tag_id.'-f-value:value','is_notempty_string')."}', 'f_d_value': '{$fdValue}', ";
                $feValue=$params->safeGet('f_e_value','','is_string');
                if(strlen($feValue)) {
                    if(strpos($feValue,'{nEval|')===FALSE && strpos($feValue,'{nGet|')===FALSE) {
                        $feValue='{nGet|'.$feValue.'}';
                    }
                    $fedValue=$params->safeGet('f_e_d_value','','is_string');
                    if(strlen($fedValue) && strpos($fedValue,'{nEval|')===FALSE && strpos($fedValue,'{nGet|')===FALSE) {
                        $fedValue='{nGet|'.$fedValue.'}';
                    }
                    $command.="'f_e_value': '{$feValue}', 'f_e_d_value': '{$fedValue}', ";
                }//if(strlen($feValue))
                $vField=$params->safeGet('v_field',NULL,'?is_string');
                if(strlen($vField)) {
                    $vDataType=$params->safeGet('v_data_type','','is_string');
                    $vdValue=$params->safeGet('v_d_value','{nGet|'.$this->tag_id.'-f-v-value:value}','is_notempty_string');
                    if(strlen($vdValue) && strpos($vdValue,'{nEval|')===FALSE && strpos($vdValue,'{nGet|')===FALSE) {
                        $vdValue='{nGet|'.$vdValue.'}';
                    }
                    $command.="'v_field': '{$vField}', 'v_d_type': '{$vDataType}', 'v_value': '{nGet|".$params->safeGet('v_value',$this->tag_id.'-f-v-value:value','is_notempty_string')."}', 'v_d_value': '{$vdValue}', ";
                    $veValue=$params->safeGet('v_e_value','','is_string');
                    if(strlen($veValue)) {
                        if(strpos($veValue,'{nEval|')===FALSE && strpos($veValue,'{nGet|')===FALSE) {
                            $veValue='{nGet|'.$veValue.'}';
                        }
                        $vedValue=$params->safeGet('v_e_d_value','','is_string');
                        if(strlen($vedValue) && strpos($vedValue,'{nEval|')===FALSE && strpos($vedValue,'{nGet|')===FALSE) {
                            $vedValue='{nGet|'.$vedValue.'}';
                        }
                        $command.="'v_e_value': '{$veValue}', 'v_e_d_value': '{$vedValue}', ";
                    }//if(strlen($veValue))
                }//if(strlen($vField))
                $command.="'is_ds_param': '{$isDsParam}', 'sessact': 'filters' } }";
                break;
            case 'filters.remove':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->cHash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action': 'remove', 'f_guid': '".$params->safeGet('f_guid','','is_string')."', 'sessact': 'filters' } }";
                break;
            case 'filters.group_remove':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->cHash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action': 'group_remove', 'g_id': '".$params->safeGet('g_id','','is_string')."', 'sessact': 'filters' } }";
                break;
            case 'filters.clear':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->cHash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action':'clear', 'sessact': 'filters' } }";
                break;
            case 'filters.apply':
                $method=$method ?? 'Show';
                $command="{ 'control_hash': '{$this->cHash}', 'method': '{$method}', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action': 'apply' } }";
                break;
            default:
                $command=NULL;
                break;
        }//END switch
        return $command;
    }//END protected function GetFilterActionCommand

    /**
     * @param array|null  $groups
     * @param string|null $selectedValue
     * @param string|null $parent
     * @param int         $level
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterGroupsOptions(?array $groups,?string &$selectedValue=NULL,?string $parent=NULL,int $level=0): ?string {
        $result='';
        foreach($groups as $gKey=>$group) {
            $gId=(strlen($parent) ? $parent.'-' : '_').trim($gKey,'_');
            $gName=(strlen($parent) ? str_replace('-','.',trim($parent,'_')).'.' : '').trim($gKey,'_');
            $lSelected=$selectedValue==$gId ? ' selected="selected"' : '';
            $result.="\t\t\t\t\t".'<option value="'.$gId.'"'.$lSelected.'>&nbsp;['.$gName.']</option>'."\n";
            $result.=$this->GetFilterGroupsOptions($group,$selectedValue,$gId,($level + 1));
        }//END foreach
        return $result;
    }//END protected function GetFilterGroupsOptions

    /**
     * @param string|null $selectedValue
     * @param string|null $selectedType
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterGroupControl(?string $selectedValue=NULL,?string $selectedType=NULL): ?string {
        if(!$this->with_filter_groups || !is_array($this->filters) || !count($this->filters)) {
            $result="\t\t\t\t".'<input id="'.$this->tag_id.'-f-group" type="hidden" value="'.($this->with_filter_groups ? $selectedValue : '_1').'">'."\n";
            $result.="\t\t\t\t".'<input id="'.$this->tag_id.'-f-group-type" type="hidden" value="1">'."\n";
            return $result;
        }//if(!$this->with_filter_groups || !is_array($this->filters) || !count($this->filters))
        $result="\t\t\t\t".'<select id="'.$this->tag_id.'-f-group" class="clsComboBox form-control f-ctrl f-group">'."\n";
        $result.=$this->GetFilterGroupsOptions($this->groups,$selectedValue);
        $result.="\t\t\t\t".'</select>'."\n";
        $result.="\t\t\t\t".'<select id="'.$this->tag_id.'-f-group-type" class="clsComboBox form-control f-ctrl f-group-type">'."\n";
        $result.="\t\t\t\t\t".'<option value="1"'.($selectedType=='1' ? ' selected="selected"' : '').'>'.Translate::GetLabel('current_group').'</option>'."\n";
        $result.="\t\t\t\t\t".'<option value="2"'.($selectedType=='2' ? ' selected="selected"' : '').'>'.Translate::GetLabel('new_sub_group').'</option>'."\n";
        $result.="\t\t\t\t\t".'<option value="0"'.($selectedType=='0' ? ' selected="selected"' : '').'>'.Translate::GetLabel('new_group').'</option>'."\n";
        $result.="\t\t\t\t".'</select>'."\n";
        return $result;
    }//END protected function GetFilterGroupControl

    /**
     * @param string|null $selectedValue
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetLogicalSeparatorControl(?string $selectedValue=NULL): string {
        if(!is_array($this->filters) || !count($this->filters)) {
            $result="\t\t\t\t".'<input id="'.$this->tag_id.'-f-l-op" type="hidden" value="'.($selectedValue ?? 'and').'">'."\n";
            return $result;
        }//if(is_array($this->filters) && count($this->filters))
        $result="\t\t\t\t".'<select id="'.$this->tag_id.'-f-l-op" class="clsComboBox form-control f-ctrl f-l-op">'."\n";
        $result.="\t\t\t\t\t".'<option value="and"'.($selectedValue=='and' ? ' selected="selected"' : '').'>'.Translate::GetLabel('and').'</option>'."\n";
        $result.="\t\t\t\t\t".'<option value="or"'.($selectedValue=='or' ? ' selected="selected"' : '').'>'.Translate::GetLabel('or').'</option>'."\n";
        $result.="\t\t\t\t".'</select>'."\n";
        return $result;
    }//END protected function GetLogicalSeparatorControl

    /**
     * @param \NETopes\Core\App\Params $params
     * @param array                    $items
     * @param string|null              $filterType
     * @param array|null               $selectedItem
     * @param bool                     $isQuickSearch
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFiltersSelectorControl(Params $params,array $items,?string &$filterType=NULL,?array &$selectedItem=NULL,bool &$isQuickSearch=FALSE): string {
        $result="\t\t\t\t".'<select id="'.$this->tag_id.'-f-type" class="clsComboBox form-control f-ctrl f-type" onchange="'.$this->GetActionCommand('filters.render').'">'."\n";
        $lFilterType=$params->safeGet('f_type','','is_string');
        $isQuickSearchActive=$this->CheckIfFilterIsActive(0);
        if($this->qsearch && !$isQuickSearchActive) {
            if($lFilterType=='0' || !strlen($lFilterType)) {
                $lSelected=' selected="selected"';
                $isQuickSearch=TRUE;
                $filterType='0';
                $selectedItem=NULL;
            } else {
                $lSelected='';
            }//if($lFilterType=='0' || !strlen($lFilterType))
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
            if($lFilterType==$k || (!strlen($lFilterType) && !$isQuickSearch && !$selectedItem)) {
                $lSelected=' selected="selected"';
                $filterType=get_array_value($v,'filter_type','','is_string');
                $selectedItem=$v;
            } else {
                $lSelected='';
                if(is_null($filterType)) {
                    $filterType=get_array_value($v,'filter_type','','is_string');
                    $selectedItem=$v;
                }//if(is_null($filterType))
            }//if($cfType==$k || (!strlen($cfType) && !$isQuickSearch && !$selectedItem))
            $result.="\t\t\t\t\t".'<option value="'.$k.'"'.$lSelected.'>'.get_array_value($v,'label',$k,'is_notempty_string').'</option>'."\n";
        }//END foreach
        $result.="\t\t\t".'</select>'."\n";
        return $result;
    }//END protected function GetFiltersSelectorControl

    /**
     * @param \NETopes\Core\App\Params $params
     * @param string|null              $filterType
     * @param array|null               $selectedItem
     * @param bool                     $isQuickSearch
     * @param bool                     $withFilterValueField
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetConditionTypeControl(Params $params,?string $filterType,?array $selectedItem,bool $isQuickSearch,bool $withFilterValueField=FALSE): string {
        $fDataType=get_array_value($selectedItem,'data_type','','is_string');
        $conditionType=$params->safeGet('f_c_type','','is_string');
        $this->filter_cond_value_source=$this->tag_id.'-f-c-type:value';
        $conditionDisplayType=get_array_value($selectedItem,'show_filter_cond_type',get_array_value($selectedItem,'show_filter_cond_type',TRUE,'bool'),'is_notempty_string');
        if($conditionDisplayType===TRUE && !$isQuickSearch) {
            $filterOptions='';
            $filterConditionTypeOnChange='';
            $conditionsType=strtolower(strlen($filterType) ? $filterType : $fDataType);
            $filterConditions=DataProvider::Get('_Custom\Offline','FilterConditionsTypes',['type'=>$conditionsType]);
            /** @var \NETopes\Core\Data\VirtualEntity $c */
            foreach($filterConditions as $c) {
                $lSelected=$conditionType==$c->getProperty('value') ? ' selected="selected"' : '';
                $filterOptions.="\t\t\t\t".'<option value="'.$c->getProperty('value').'"'.$lSelected.'>'.$c->getProperty('name').'</option>'."\n";
                if(!strlen($filterConditionTypeOnChange) && $c->getProperty('value')=='><') {
                    $filterConditionTypeOnChange=' onchange="'.$this->GetActionCommand('filters.render',['f_c_type'=>$this->tag_id.'-f-c-type:value','f_v_f_mode'=>$withFilterValueField,'f_d_value'=>get_array_param($selectedItem,'f_d_value_source',NULL,'?is_string')]).'"';
                }//if(!strlen($filterConditionTypeOnChange) && $c->getProperty('value')=='><')
            }//END foreach
            $result="\t\t\t".'<select id="'.$this->tag_id.'-f-c-type" class="clsComboBox form-control f-ctrl f-c-type"'.$filterConditionTypeOnChange.'>'."\n";
            $result.=$filterOptions;
            $result.="\t\t\t".'</select>'."\n";
        } elseif($conditionDisplayType!=='data') {
            $result="\t\t\t\t".'<input type="hidden" id="'.$this->tag_id.'-f-c-type" value="'.($isQuickSearch ? 'like' : '==').'">'."\n";
        } else {
            $this->filter_cond_value_source=NULL;
            $result='';
        }//if($conditionDisplayType===TRUE && !$isQuickSearch)
        return $result;
    }//END protected function GetConditionTypeControl

    /**
     * @param \NETopes\Core\App\Params $params
     * @param string|null              $filterType
     * @param array|null               $selectedItem
     * @param array|null               $onClickActionParams
     * @param string|null              $filterValueField
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterValueControl(Params $params,?string $filterType,?array &$selectedItem,?array &$onClickActionParams=[],?string $filterValueField=NULL): string {
        $isDsParam=intval(strlen(get_array_value($selectedItem,'ds_param','','is_string'))>0);
        $conditionType=$params->safeGet('f_c_type','','is_string');
        if(is_array($onClickActionParams)) {
            $onClickActionParams['f_field']=get_array_value($selectedItem,'filter_field',get_array_value($selectedItem,'db_field',NULL,'is_notempty_string'),'is_notempty_string');
        } else {
            $onClickActionParams=['f_field'=>get_array_value($selectedItem,'filter_field',get_array_value($selectedItem,'db_field',NULL,'is_notempty_string'),'is_notempty_string')];
        }
        if(strlen($filterValueField)) {
            $isFilterValueField=TRUE;
            $fvName='f-v-value';
            $fevName='f-v-e-value';
            $dataType=get_array_value($selectedItem,'filter_value_data_type','','is_string');
            $ctrlParams=get_array_value($selectedItem,'filter_value_params',[],'is_array');
            $selectedValue=NULL;
            $onClickActionParams['v_field']=get_array_value($selectedItem,'filter_value_field','','is_string');
        } else {
            $isFilterValueField=FALSE;
            $fvName='f-value';
            $fevName='f-e-value';
            $dataType=get_array_value($selectedItem,'data_type','','is_string');
            $ctrlParams=get_array_value($selectedItem,'filter_params',[],'is_array');
            $selectedValue=$params->safeGet('f_value',NULL,'isset');
        }//if(strlen($filterValueField))
        $ctrlParams['tag_id']=$this->tag_id.'-'.$fvName;
        $ctrlParams['class']='f-ctrl '.$fvName;
        $ctrlParams['container']=FALSE;
        $ctrlParams['no_label']=TRUE;
        $ctrlParams['postable']=FALSE;
        $subType=NULL;
        $fValue=NULL;
        $dValue=NULL;
        $eValue=NULL;
        $edValue=NULL;
        switch(strtolower($filterType)) {
            case 'smartcombobox':
                $ctrlParams['placeholder']=get_array_value($ctrlParams,'placeholder',Translate::GetLabel('please_select'),'is_notempty_string');
                $ctrlParams['allow_clear']=get_array_value($ctrlParams,'allow_clear',TRUE,'is_bool');
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if($isFilterValueField || !isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedItem,'filter_data_source',NULL,'is_notempty_array');
                }//if($isFilterValueField || !isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $dValue='{nEval|GetSmartCBOText(\''.$this->tag_id.'-'.$fvName.'\',false)}';
                $selectedItem['f_d_value_source']=$dValue;
                $ctrlParams['selected_value']=$selectedValue;
                $ctrlParams['selected_text']=$params->safeGet('f_d_value',$selectedValue,'is_string');
                if(!$isFilterValueField && !$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-'.$fvName.':option:data-ctype';
                }
                $filterValueControl=new SmartComboBox($ctrlParams);
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                if($isFilterValueField) {
                    $onClickActionParams=array_merge($onClickActionParams,['v_d_value'=>$dValue,'v_d_type'=>$dataType]);
                } else {
                    $onClickActionParams=array_merge($onClickActionParams,['f_d_value'=>$dValue,'f_d_type'=>$dataType,'is_ds_param'=>$isDsParam]);
                }//if($isFilterValueField)
                break;
            case 'combobox':
                if(!isset($ctrlParams['please_select_text']) || !strlen($ctrlParams['please_select_text'])) {
                    $ctrlParams['please_select_text']=Translate::GetLabel('please_select');
                    $ctrlParams['please_select_value']=NULL;
                }//if(!isset($ctrlParams['please_select_text']) || !strlen($ctrlParams['please_select_text']))
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if(!$isFilterValueField || !isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedItem,'filter_data_source',NULL,'is_notempty_array');
                }//if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $dValue=$this->tag_id.'-'.$fvName.':option';
                $ctrlParams['selected_value']=$selectedValue;
                if(!$isFilterValueField && !$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-'.$fvName.':option:data-ctype';
                }
                $filterValueControl=new ComboBox($ctrlParams);
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                if($isFilterValueField) {
                    $onClickActionParams=array_merge($onClickActionParams,['v_d_value'=>$dValue,'v_d_type'=>$dataType]);
                } else {
                    $onClickActionParams=array_merge($onClickActionParams,['f_d_value'=>$dValue,'f_d_type'=>$dataType,'is_ds_param'=>$isDsParam]);
                }//if($isFilterValueField)
                break;
            case 'treecombobox':
                $ctrlParams['load_type']=get_array_value($ctrlParams,'load_type','database','is_notempty_string');
                if(!$isFilterValueField || !isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source'])) {
                    $ctrlParams['data_source']=get_array_value($selectedItem,'filter_data_source',NULL,'is_notempty_array');
                }//if(!isset($ctrlParams['data_source']) || !is_array($ctrlParams['data_source']) || !count($ctrlParams['data_source']))
                $dValue=$this->tag_id.'-'.$fvName.'-cbo:value';
                $ctrlParams['selected_value']=$selectedValue;
                $ctrlParams['selected_text']=$params->safeGet('f_d_value',$selectedValue,'is_string');
                if(!$isFilterValueField && !$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-'.$fvName.':option:data-ctype';
                }
                $filterValueControl=new TreeComboBox($ctrlParams);
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                if($isFilterValueField) {
                    $onClickActionParams=array_merge($onClickActionParams,['v_d_value'=>$dValue,'v_d_type'=>$dataType]);
                } else {
                    $onClickActionParams=array_merge($onClickActionParams,['f_d_value'=>$dValue,'f_d_type'=>$dataType,'is_ds_param'=>$isDsParam]);
                }//if($isFilterValueField)
                break;
            case 'checkbox':
                $dValue=$this->tag_id.'-'.$fvName.':value';
                $ctrlParams['value']=$selectedValue;
                if(!$isFilterValueField && !$this->filter_cond_value_source) {
                    $this->filter_cond_value_source=$this->tag_id.'-'.$fvName.':option:data-ctype';
                }
                $filterValueControl=new CheckBox($ctrlParams);
                // $filterValueControl->ClearBaseClass();
                $result="\t\t\t\t".$filterValueControl->Show()."\n";
                if($isFilterValueField) {
                    $onClickActionParams=array_merge($onClickActionParams,['v_d_value'=>$dValue,'v_d_type'=>$dataType]);
                } else {
                    $onClickActionParams=array_merge($onClickActionParams,['f_d_value'=>$dValue,'f_d_type'=>$dataType,'is_ds_param'=>$isDsParam]);
                }//if($isFilterValueField)
                break;
            case 'datepicker':
            case 'date':
            case 'datetime':
                $subType='DatePicker';
            case 'numerictextbox':
            case 'numeric':
                $subType=$subType ? $subType : 'NumericTextBox';
            default:
                if(!$subType) {
                    switch($dataType) {
                        case 'date':
                        case 'date_obj':
                        case 'datetime':
                        case 'datetime_obj':
                            $subType='DatePicker';
                            break;
                        case 'numeric':
                            $subType='NumericTextBox';
                            break;
                        default:
                            $subType='TextBox';
                            break;
                    }//END switch
                }//if(!$subType)
                $dValue=$this->tag_id.'-'.$fvName.':value';
                $ctrlParams['value']=$selectedValue;
                $ctrlParams['onenter_button']=$this->tag_id.'-f-add-btn';
                switch($subType) {
                    case 'DatePicker':
                        if(strtolower($filterType)!='date' && ($dataType=='datetime' || $dataType=='datetime_obj')) {
                            $ctrlParams['timepicker']=TRUE;
                        } else {
                            $ctrlParams['timepicker']=FALSE;
                        }//if(strtolower($filterType)!='date' && ($dataType=='datetime' || $dataType=='datetime_obj'))
                        $ctrlParams['align']='center';
                        $filterValueControl=new DatePicker($ctrlParams);
                        // $filterValueControl->ClearBaseClass();
                        $result="\t\t\t\t".$filterValueControl->Show()."\n";
                        $fValue=$this->tag_id.'-'.$fvName.':dvalue';
                        if(!strlen($dataType)) {
                            $dataType='datetime';
                        }
                        if($conditionType=='><') {
                            $result.="\t\t\t\t".'<span class="f-i-lbl">'.Translate::GetLabel('and').'</span>'."\n";
                            // $ctrlParams['tag_id']=$this->tag_id.'-'.$fevName;
                            // $filterValueControl=new NumericTextBox($ctrlParams);
                            // $filterValueControl->ClearBaseClass();
                            $filterValueControl->tag_id=$this->tag_id.'-'.$fevName;
                            $result.="\t\t\t\t".$filterValueControl->Show()."\n";
                            $eValue=$this->tag_id.'-'.$fevName.':dvalue';
                            $edValue=$this->tag_id.'-'.$fevName.':value';
                        }//if($fConditionType=='><')
                        break;
                    case 'NumericTextBox':
                        $ctrlParams['class'].=' t-box';
                        $ctrlParams['number_format']=get_array_value($selectedItem,'filter_format','0|||','is_notempty_string');
                        $ctrlParams['align']='center';
                        $filterValueControl=new NumericTextBox($ctrlParams);
                        // $filterValueControl->ClearBaseClass();
                        $result="\t\t\t\t".$filterValueControl->Show()."\n";
                        $fValue=$this->tag_id.'-'.$fvName.':nvalue';
                        if(!strlen($dataType)) {
                            $dataType='numeric';
                        }
                        if($conditionType=='><') {
                            $result.="\t\t\t\t".'<span class="f-i-lbl">'.Translate::GetLabel('and').'</span>'."\n";
                            // $ctrlParams['tag_id']=$this->tag_id.'-'.$fevName;
                            // $filterValueControl=new NumericTextBox($ctrlParams);
                            // $filterValueControl->ClearBaseClass();
                            $filterValueControl->tag_id=$this->tag_id.'-'.$fevName;
                            $result.="\t\t\t\t".$filterValueControl->Show()."\n";
                            $eValue=$this->tag_id.'-'.$fevName.':nvalue';
                            $edValue=$this->tag_id.'-'.$fevName.':value';
                        }//if($fConditionType=='><')
                        break;
                    case 'TextBox':
                    default:
                        $ctrlParams['class'].=' t-box';
                        $filterValueControl=new TextBox($ctrlParams);
                        // $filterValueControl->ClearBaseClass();
                        $result="\t\t\t\t".$filterValueControl->Show()."\n";
                        $fValue=$this->tag_id.'-'.$fvName.':value';
                        break;
                }//END switch
                if($isFilterValueField) {
                    if($conditionType=='><') {
                        $onClickActionParams=array_merge($onClickActionParams,['v_value'=>$fValue,'v_d_value'=>$dValue,'v_e_value'=>$eValue,'v_e_d_value'=>$edValue,'v_d_type'=>$dataType]);
                    } else {
                        $onClickActionParams=array_merge($onClickActionParams,['v_value'=>$fValue,'v_d_value'=>$dValue,'v_d_type'=>$dataType]);
                    }
                } else {
                    if($conditionType=='><') {
                        $onClickActionParams=array_merge($onClickActionParams,['f_value'=>$fValue,'f_d_value'=>$dValue,'f_e_value'=>$eValue,'f_e_d_value'=>$edValue,'f_d_type'=>$dataType,'is_ds_param'=>$isDsParam]);
                    } else {
                        $onClickActionParams=array_merge($onClickActionParams,['f_value'=>$fValue,'f_d_value'=>$dValue,'f_d_type'=>$dataType,'is_ds_param'=>$isDsParam]);
                    }
                }//if($isFilterValueField)
                break;
        }//END switch
        return $result;
    }//END protected function GetFilterValueControl

    /**
     * Get filter box actions HTML string
     *
     * @param string $onClick
     * @return string Returns actions HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterAddAction(string $onClick): string {
        if($this->compact_mode) {
            $result="\t\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="'.NApp::$theme->GetBtnInfoClass('f-add-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" onclick="'.$onClick.'" title="'.Translate::GetButton('add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
        } else {
            $result="\t\t\t\t".'<button id="'.$this->tag_id.'-f-add-btn" class="'.NApp::$theme->GetBtnInfoClass('f-add-btn'.$this->buttons_size_class).'" onclick="'.$onClick.'"><i class="fa fa-plus"></i>'.Translate::GetButton('add_filter').'</button>'."\n";
        }//if($this->compact_mode)
        return $result;
    }//END protected function GetFilterAddAction

    /**
     * Get current filter controls HTML string
     *
     * @param array                    $items
     * @param \NETopes\Core\App\Params $params
     * @return string Returns HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterControls(array $items,Params $params): string {
        $filterType=$selectedItem=NULL;
        $isQuickSearch=FALSE;
        $onClickActionParams=[];
        $result=$this->GetFilterGroupControl($params->safeGet('g_id',NULL,'?is_string'),$params->safeGet('g_type',NULL,'?is_string'));
        $result.=$this->GetLogicalSeparatorControl($params->safeGet('l_op',NULL,'?is_string'));
        $result.=$this->GetFiltersSelectorControl($params,$items,$filterType,$selectedItem,$isQuickSearch);
        $filterValueField=get_array_value($selectedItem,'filter_value_field',NULL,'?is_string');
        if(!strlen($filterValueField)) {
            $result.=$this->GetConditionTypeControl($params,$filterType,$selectedItem,$isQuickSearch);
            $result.=$this->GetFilterValueControl($params,$filterType,$selectedItem,$onClickActionParams);
        } else {
            $filterValueType=get_array_value($selectedItem,'filter_value_type',NULL,'?is_string');
            $result.=$this->GetFilterValueControl($params,$filterType,$selectedItem,$onClickActionParams);
            $result.=$this->GetConditionTypeControl($params,$filterValueType,$selectedItem,$isQuickSearch,TRUE);
            $result.=$this->GetFilterValueControl($params,$filterValueType,$selectedItem,$onClickActionParams,$filterValueField);
        }//if(!strlen($filterValueField))
        $onClickAction=$this->GetActionCommand('filters.add',$onClickActionParams);
        $result.=$this->GetFilterAddAction($onClickAction);
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
            $result="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnDefaultClass('f-clear-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('filters.clear').'" title="'.Translate::GetButton('clear_filters').'"><i class="fa fa-times"></i></button>'."\n";
        } else {
            $result="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnDefaultClass('f-clear-btn'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('filters.clear').'"><i class="fa fa-times"></i>'.Translate::GetButton('clear_filters').'</button>'."\n";
        }//if($this->compact_mode)
        if(!$withApply) {
            return $result;
        }
        if($this->compact_mode) {
            $result.="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('f-apply-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('filters.apply').'" title="'.Translate::GetButton('apply_filters').'"><i class="fa fa-filter" aria-hidden="true"></i></button>'."\n";
        } else {
            $result.="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('f-apply-btn'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('filters.apply').'"><i class="fa fa-filter" aria-hidden="true"></i>'.Translate::GetButton('apply_filters').'</button>'."\n";
        }//if($this->compact_mode)
        return $result;
    }//END protected function GetFilterGlobalActions

    /**
     * Get active filter item HTML string
     *
     * @param array                           $items
     * @param array                           $filters
     * @param \NETopes\Core\Data\DataSet|null $fcTypes
     * @param bool                            $first
     * @return string|null Returns active filters HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActiveFilterItem(array $items,array $filters,$fcTypes,bool $first=TRUE): ?string {
        $result='';
        foreach($filters as $filter) {
            if($first) {
                $first=FALSE;
                $logicalPrefix='';
            } else {
                $logicalPrefix='<span class="f-i-l-op">'.Translate::GetLabel(get_array_value($filter,'logical_separator','and','is_string')).'</span>';
            }//if($first)
            $result.="\t\t\t\t".'<div class="f-active-item"><div class="b-remove" onclick="'.$this->GetActionCommand('filters.remove',['f_guid'=>$filter['guid']]).'"><i class="fa fa-times"></i></div>'.$logicalPrefix.'<strong>'.((string)$filter['type']=='0' ? Translate::GetLabel('quick_search') : get_array_value($items[$filter['type']],'label',$filter['type'],'is_notempty_string')).'</strong>';
            if(strlen(get_array_value($filter,'value_field',NULL,'?is_string'))) {
                $result.=':&nbsp;<strong>'.$filter['display_value'].'</strong>&nbsp;'.$fcTypes->safeGet($filter['condition_type'])->getProperty('name').'&nbsp;&quot;<strong>'.get_array_value($filter,'value_display_value','N/A','is_string');
                if($filter['condition_type']=='><') {
                    $result.='</strong>&quot;&nbsp;'.Translate::GetLabel('and').'&nbsp;&quot;<strong>'.get_array_value($filter,'value_end_display_value','N/A','is_string');
                }//if($item['condition_type']=='><')
                $result.='</strong>';
            } else {
                $result.='&nbsp;'.$fcTypes->safeGet($filter['condition_type'])->getProperty('name').'&nbsp;&quot;<strong>'.$filter['display_value'];
                if($filter['condition_type']=='><') {
                    $result.='</strong>&quot;&nbsp;'.Translate::GetLabel('and').'&nbsp;&quot;<strong>'.$filter['end_display_value'];
                }//if($item['condition_type']=='><')
                $result.='</strong>';
            }//if(strlen(get_array_value($filter,'v_field',null,'?is_string')))
            $result.='&quot;</div>'."\n";
        }//END foreach
        return $result;
    }//END protected function GetActiveFilterItem

    /**
     * Get active filters groups HTML string
     *
     * @param array                           $items Filter configuration items
     * @param array                           $filters
     * @param \NETopes\Core\Data\DataSet|null $fcTypes
     * @param string|null                     $parent
     * @param int                             $level
     * @return string|null Returns active filters HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActiveFilterGroups(array $items,array $filters,$fcTypes,?string $parent=NULL,int $level=0): ?string {
        $result='';
        $first=TRUE;
        foreach($filters as $gKey=>$group) {
            if(substr($gKey,0,1)!='_') {
                $result.=$this->GetActiveFilterItem($items,array_key_exists(0,$group) ? $group : [$group],$fcTypes,$first);
                $first=FALSE;
                continue;
            }//if(substr($gKey,0,1)!='_')
            if($first) {
                $first=FALSE;
                $logicalPrefix='';
            } else {
                $logicalPrefix='<span class="f-g-l-op">'.Translate::GetLabel(get_array_value($group,[0,'logical_separator'],'and','is_string')).'</span>';
            }//if($first)
            $gId=(strlen($parent) ? $parent.'-' : '_').trim($gKey,'_');
            $gName=(strlen($parent) ? str_replace('-','.',trim($parent,'_')).'.' : '').trim($gKey,'_');
            $result.="\t\t\t\t\t".'<div class="f-items-group g-offset-'.$level.'">'.$logicalPrefix.'<span class="f-g-title">'.Translate::GetLabel('group').' ['.$gName.']</span>'."\n";
            $result.="\t\t\t\t\t\t".'<div class="g-remove" onclick="'.$this->GetActionCommand('filters.group_remove',['g_id'=>$gId]).'"><i class="fa fa-times"></i></div>'."\n";
            $result.=$this->GetActiveFilterGroups($items,$group,$fcTypes,$gId,($level + 1));
            $result.="\t\t\t\t\t".'</div>'."\n";
        }//END foreach
        return $result;
    }//END protected function GetActiveFilterGroups

    /**
     * Get active filters HTML string
     *
     * @param array $items Filter configuration items
     * @return string|null Returns active filters HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActiveFilters(array $items): ?string {
        // NApp::Dlog($this->filters,'GetActiveFilters>>$this->filters');
        if(!is_array($this->filters) || !count($this->filters)) {
            return NULL;
        }
        $filters="\t\t\t\t".'<div class="f-active-items">'."\n";
        if($this->active_filters_title) {
            $filters.="\t\t\t\t\t".'<span class="f-active-title">'.Translate::GetTitle('active_filters').':</span>'."\n";
        }
        $fcTypes=DataProvider::GetKeyValue('_Custom\Offline','FilterConditionsTypes',['type'=>'all'],['keyfield'=>'value']);
        if($this->with_filter_groups) {
            $groupedFilters=array_group_by_hierarchical('group_id',$this->filters,TRUE,'_');
            // NApp::Dlog($groupedFilters,'$groupedFilters');
            $filters.=$this->GetActiveFilterGroups($items,$groupedFilters,$fcTypes);
        } else {
            $filters.=$this->GetActiveFilterItem($items,$this->filters,$fcTypes);
        }//if($this->with_filter_groups)
        $filters.="\t\t\t".'</div>'."\n";
        return $filters;
    }//END protected function GetActiveFilters

    /**
     * Get active filters array
     *
     * @param bool $flat
     * @return array
     */
    public function GetFilters(bool $flat=FALSE): array {
        if($flat || !$this->with_filter_groups) {
            return $this->filters;
        }//if($this->with_filter_groups)
        $groupedFilters=array_group_by_hierarchical('group_id',$this->filters,TRUE,'_');
        // NApp::Dlog($groupedFilters,'$groupedFilters');
        return $groupedFilters;
    }//END public function GetFilters

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
        $oParams=($params instanceof Params) ? $params : new Params($params);
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
        // NApp::Dlog($params,'ShowFiltersBox>>Show');
        $oParams=($params instanceof Params) ? $params : new Params($params);
        $pHash=$oParams->safeGet('phash',NULL,'is_notempty_string');
        $output=$oParams->safeGet('output',FALSE,'bool');
        if($pHash) {
            $this->phash=$pHash;
        }
        if(!$output) {
            return $this->GetFilterBox($oParams);
        }
        echo $this->GetFilterBox($oParams);
        return NULL;
    }//END public function ShowFiltersBox

    /**
     * Convert flat filters array to a hierarchical array
     *
     * @param array|null    $items
     * @param string|null   $defaultGroup
     * @param callable|null $filter
     * @return array
     */
    public static function ConvertFiltersToHierarchy(?array $items,?string $defaultGroup=NULL,?callable $filter=NULL): array {
        if(!is_array($items) || !count($items)) {
            return [];
        }
        return array_group_by_hierarchical('group_id',$items,TRUE,'_',$defaultGroup,$filter);
    }//END public static function ConvertFiltersToHierarchy

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
     * @param \NETopes\Core\App\Params $params
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    abstract protected function SetControl(Params $params): ?string;

    /**
     * Gets the filter box HTML
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null Returns the filter box HTML string
     * @throws \NETopes\Core\AppException
     */
    abstract protected function GetFilterBox(Params $params): ?string;
}//END abstract class FilterControl