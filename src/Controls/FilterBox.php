<?php
/**
 * Generic filter generator.
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.3.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\App\Params;
use NETopes\Core\AppException;
use Translate;

/**
 * Class FilterBox
 *
 * @package NETopes\Core\Controls
 */
class FilterBox extends FilterControl {
    /**
     * @var    array Items configuration params
     */
    public $items=[];
    /**
     * @var    array|null Callback configuration for apply/get filters action
     */
    public $apply_callback_params=NULL;
    /**
     * @var    array|null Callback configuration for apply/get filters action
     */
    public $apply_callback_extra_params=NULL;
    /**
     * @var    string|null Callback (apply/get) button label
     */
    public $apply_button_label=NULL;
    /**
     * @var    string|null Control target for TableView configuration (overwrites $target property)
     */
    public $filter_box_target=NULL;
    /**
     * @var    bool Filters return mode: TRUE=hierarchy, FALSE=flat
     */
    public $get_filters_as_hierarchy=FALSE;

    /**
     * FilterControl class constructor method
     *
     * @param array|null $params Parameters array
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        if(isset($params['columns'])) {
            $this->items=$params['columns'];
            unset($params['columns']);
        } elseif(isset($params['items'])) {
            $this->items=$params['items'];
            unset($params['items']);
        }
        parent::__construct($params);
        if(!is_array($this->apply_callback_params) || !count($this->apply_callback_params)) {
            throw new AppException('Invalid apply/get action configuration!');
        }
        if(!is_array($this->apply_callback_extra_params)) {
            $this->apply_callback_extra_params=[];
        }
        if(is_string($this->filter_box_target) && strlen($this->filter_box_target)) {
            $this->target=$this->filter_box_target;
        }
        $this->tag_id=$this->tag_id ? $this->tag_id : $this->cHash;
    }//END public function __construct

    /**
     * Gets the action javascript command string
     *
     * @param string            $type
     * @param Params|array|null $params
     * @param bool              $processCall
     * @return string Returns action javascript command string
     * @throws \NETopes\Core\AppException
     */
    protected function GetActionCommand(?string $type=NULL,$params=NULL,bool $processCall=TRUE): ?string {
        // NApp::Dlog($params,'GetActionCommand>>$params');
        $params=$params instanceof Params ? $params : new Params($params);
        if(in_array($type,['filters.get','filters.apply'])) {
            $actionParams=$this->apply_callback_params;
            if(!isset($actionParams['params']) || !is_array($actionParams['params'])) {
                $actionParams['params']=[];
            }
            $actionParams['params'][$this->callback_params_key ?? 'filters']=$this->GetFilters(!$this->get_filters_as_hierarchy);
            if($processCall) {
                return NApp::Ajax()->PrepareAjaxRequest($actionParams,$this->apply_callback_extra_params);
            }
            return NApp::Ajax()->GetCommand($actionParams);
        }//if(in_array($type,['filters.get','filters.apply']))
        $targetId=NULL;
        $command=$this->GetFilterActionCommand($type,$params,$targetId);
        if(is_null($command)) {
            return NULL;
        }
        if(!$processCall) {
            return $command;
        }
        return NApp::Ajax()->Prepare($command,$targetId,NULL,$this->loader,NULL,TRUE,NULL,NULL,TRUE,'ControlAjaxRequest');
    }//END protected function GetActionCommand

    /**
     * Gets the apply action HTML
     *
     * @return string|null Returns the apply action HTML string
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterApplyAction(): ?string {
        if($this->compact_mode) {
            $result="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('f-apply-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('filters.get').'" title="'.($this->apply_button_label ?? Translate::GetButton('apply_filters')).'"><i class="fa fa-filter" aria-hidden="true"></i></button>'."\n";
        } else {
            $result="\t\t\t\t".'<button class="'.NApp::$theme->GetBtnPrimaryClass('f-apply-btn'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('filters.get').'"><i class="fa fa-filter" aria-hidden="true"></i>'.($this->apply_button_label ?? Translate::GetButton('apply_filters')).'</button>'."\n";
        }//if($this->compact_mode)
        return $result;
    }//END protected function GetFilterApplyAction

    /**
     * Gets the filter box HTML
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null Returns the filter box html
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterBox(Params $params): ?string {
        // NApp::Dlog($params->toArray(),'GetFilterBox>>$params');
        // NApp::Dlog($this->filters,'GetFilterBox>>$this->filters');
        $filters=$this->GetFilterControls($this->items,$params);
        $filters.=$this->GetFilterGlobalActions(FALSE);
        $filters.=$this->GetFilterApplyAction();
        $filters.=$this->GetActiveFilters($this->items);
        if($params->safeGet('f_action','','is_string')=='render') {
            return $filters;
        }
        $result="\t\t\t".'<div id="'.$this->tag_id.'-filter-box" class="tw-filters'.(is_string($this->controls_size) && strlen($this->controls_size) ? ' form-group-'.$this->controls_size : '').'">'."\n";
        $result.=$filters;
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetFilterBox

    /**
     * Generate and return the control HTML
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(Params $params): ?string {
        if(is_array($this->initial_filters)) {
            $this->filters=$this->initial_filters;
        }
        $this->ProcessActiveFilters($params);
        $lClass=trim($this->base_class.' '.$this->class);
        switch($this->theme_type) {
            case 'bootstrap3':
                $result='<div id="'.$this->tag_id.'" class="panel panel-flat '.$lClass.(is_string($this->controls_size) && strlen($this->controls_size) ? ' form-'.$this->controls_size : '').'">'."\n";
                $result.=$this->GetFilterBox($params);
                $result.="\t".'<div class="clearfix"></div>'."\n";
                $result.='</div>'."\n";
                break;
            default:
                $result='<div id="'.$this->tag_id.'" class="'.$lClass.(is_string($this->controls_size) && strlen($this->controls_size) ? ' form-'.$this->controls_size : '').'">'."\n";
                $result.=$this->GetFilterBox($params);
                $result.='</div>'."\n";
                break;
        }//END switch
        return $result;
    }//END private function SetControl

    /**
     * @param array       $filter
     * @param string|null $filterType
     * @param string|null $conditionType
     * @param string      $fieldPrefix
     * @param string      $validation
     * @param null        $defVal
     * @param null        $result
     * @param null        $startResult
     * @param null        $endResult
     * @return int
     */
    public static function ProcessFilterCondition(array $filter,?string $filterType,?string $conditionType,string $fieldPrefix,string $validation,$defVal=NULL,&$result=NULL,&$startResult=NULL,&$endResult=NULL): int {
        switch($conditionType) {
            case '><':
                $startResult=get_array_value($filter,$fieldPrefix.'value',$defVal,$validation);
                $endResult=get_array_value($filter,$fieldPrefix.'end_value',$defVal,$validation);
                return 22;
            case '>=':
                $result=get_array_value($filter,$fieldPrefix.'value',$defVal,$validation);
                return 23;
            case '<=':
                $result=get_array_value($filter,$fieldPrefix.'value',$defVal,$validation);
                return 24;
            case '!=':
            case '<>':
                $result=get_array_value($filter,$fieldPrefix.'value',$defVal,$validation);
                return ($filterType==='numeric' || $filterType==='integer' || $filterType==='date' ? 25 : 13);
            case 'notlike':
                $result=get_array_value($filter,$fieldPrefix.'value',$defVal,$validation);
                return 11;
            default:
                $result=get_array_value($filter,$fieldPrefix.'value',$defVal,$validation);
                if($filterType==='numeric' || $filterType==='integer' || $filterType==='date') {
                    return 21;
                } else {
                    return ($conditionType==='==' ? 12 : 10);
                }
        }//END switch
    }//END public static function ProcessFilterCondition

    /**
     * @param array       $filter
     * @param string|null $filterType
     * @param int|null    $recordId
     * @param string|null $fieldPrefix
     * @return array
     */
    public static function ProcessFilterItemToArray(array $filter,?string $filterType,?int $recordId=NULL,?string $fieldPrefix=NULL): array {
        $fieldPrefix=$fieldPrefix ?? 'value_';
        $stringValue=NULL;
        $numericValue=NULL;
        $intValue=NULL;
        $datetimeValue=NULL;
        $startValue=NULL;
        $endValue=NULL;
        if(in_array($filterType,['numeric','integer','date'])) {
            if($filterType==='date') {
                $type=static::ProcessFilterCondition($filter,$filterType,strtolower($filter['condition_type']),$fieldPrefix,'is_datetime',NULL,$datetimeValue,$startValue,$endValue);
            } elseif($filterType==='integer') {
                $type=static::ProcessFilterCondition($filter,$filterType,strtolower($filter['condition_type']),$fieldPrefix,'is_integer',NULL,$intValue,$startValue,$endValue);
            } else {
                $type=static::ProcessFilterCondition($filter,$filterType,strtolower($filter['condition_type']),$fieldPrefix,'is_numeric',0,$numericValue,$startValue,$endValue);
            }
        } else {
            $type=static::ProcessFilterCondition($filter,$filterType,strtolower($filter['condition_type']),$fieldPrefix,'is_string','',$stringValue);
        }
        return [
            'id_record'=>$recordId,
            'type'=>$type,
            'svalue'=>$stringValue,
            'nvalue'=>$numericValue,
            'ivalue'=>$intValue,
            'dvalue'=>$datetimeValue,
            'vfrom'=>$startValue,
            'vto'=>$endValue,
        ];
    }//END public static function ProcessFilterItemToArray
}//END class FilterBox extends FilterControl