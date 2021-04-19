<?php
/**
 * Data grid control file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.3.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use DateTime;
use Exception;
use GibberishAES;
use NApp;
use NETopes\Core\App\AppHelpers;
use NETopes\Core\App\Module;
use NETopes\Core\App\Params;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\ExcelExport;
use NETopes\Core\Data\IEntity;
use NETopes\Core\Data\VirtualEntity;
use NETopes\Core\Validators\Validator;
use Translate;

/**
 * Class TableView
 *
 * @package NETopes\Core\Controls
 */
class TableView extends FilterControl {
    use TControlFields;

    /**
     * @var    string Control instance session hash
     */
    protected $sessionHash=NULL;
    /**
     * @var    int Current page (for pagination)
     */
    protected $current_page=NULL;
    /**
     * @var    bool Export only flag
     */
    protected $export_only=FALSE;
    /**
     * @var    bool Show or hide export button
     */
    protected $export_button=FALSE;
    /**
     * @var    array Data to be exported
     */
    protected $export_data=NULL;
    /**
     * @var    array Totals values
     */
    protected $totals=[];
    /**
     * @var    array Running totals values
     */
    protected $running_totals=[];
    /**
     * @var    array Embedded row forms (initialized for each row)
     */
    protected $row_embedded_form=NULL;
    /**
     * @var    bool Page hash (window.name)
     */
    public $phash=NULL;
    /**
     * @var    string|null DRights menu GUID
     */
    public $drights_uid=NULL;
    /**
     * @var    bool Is individual panel or integrated in other view
     */
    public $is_panel=TRUE;
    /**
     * @var    bool|array Defines a tree grid
     */
    public $tree=FALSE;
    /**
     * @var    string Tree level ident string
     */
    public $tree_ident='&nbsp;&nbsp;&nbsp;&nbsp;';
    /**
     * @var    integer Tree top level
     */
    protected $tree_top_lvl=1;
    /**
     * @var    string Control elements class
     */
    public $container_class=NULL;
    /**
     * @var    mixed TableView width (numeric in px or as string percent)
     */
    public $width=NULL;
    /**
     * @var    int TableView width
     */
    public $min_width=NULL;
    /**
     * @var    int TableView cell padding
     */
    public $cell_padding=10;
    /**
     * @var    bool Switch alternate row collor on/off
     */
    public $alternate_row_color=FALSE;
    /**
     * @var    string|null Row custom CSS class
     */
    public $row_class=NULL;
    /**
     * @var    string|null Row tag extra attributes
     */
    public $row_extra_tag_params=NULL;
    /**
     * @var    string|null Color row dynamically from a data field value
     */
    public $row_color_field=NULL;
    /**
     * @var    string|null Row CSS class dynamically from a data field value
     */
    public $row_class_field=NULL;
    /**
     * @var    string Table rows fixed height
     */
    public $row_height=NULL;
    /**
     * @var    mixed Row tooltip as string or array
     */
    public $row_tooltip=NULL;
    /**
     * @var    bool Switch horizontal scroll on/off
     */
    public $scrollable=TRUE;
    /**
     * @var    array Sort state (column, direction)
     */
    public $sortby=[];
    /**
     * @var    bool Switch filter box on/off
     */
    public $with_filter=TRUE;
    /**
     * @var    bool Switch actions box on/off (only without filters)
     */
    public $hide_actions_bar=FALSE;
    /**
     * @var    bool Switch export feature on/off
     */
    public $exportable=TRUE;
    /**
     * @var    bool Switch export all feature on/off
     */
    public $export_all=TRUE;
    /**
     * @var    string Export format (Excel2007/Excel5/csv)
     */
    public $export_format='Excel2007';
    /**
     * @var    bool Switch datetime export as text on/off
     */
    public $export_datetime_as_text=FALSE;
    /**
     * @var    bool Switch pagination on/off
     */
    public $with_pagination=TRUE;
    /**
     * @var    bool Switch totals on/off
     */
    public $with_totals=FALSE;
    /**
     * @var    bool Switch totals position as first row on/off
     */
    public $totals_row_first=FALSE;
    /**
     * @var    bool Switch status bar on/off
     * (applyes only with $with_pagination = FALSE)
     */
    public $hide_status_bar=FALSE;
    /**
     * @var    int Table header rows number (default: 1)
     */
    public $th_rows_no=1;
    /**
     * @var    string Data call data adapter name
     */
    public $ds_class=NULL;
    /**
     * @var    string Data call method
     */
    public $ds_method=NULL;
    /**
     * @var    array Data call params array
     */
    public $ds_params=[];
    /**
     * @var    array Data call extra params array
     */
    public $ds_extra_params=[];
    /**
     * @var    array Data call out params array
     */
    public $ds_out_params=NULL;
    /**
     * @var    array Data array
     */
    public $data=NULL;
    /**
     * @var    bool Switch auto data loading on/off
     */
    public $auto_load_data=TRUE;
    /**
     * @var    bool Switch auto data loading on filters change on/off
     */
    public $auto_load_data_on_filter_change=TRUE;
    /**
     * @var    array Array for setting custom css class for rows, based on a condition
     */
    public $row_conditional_class=NULL;
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
     * @var    array Columns configuration params
     */
    public $columns=[];
    /**
     * @var    bool Flag to indicate if filters are persistent
     */
    public $persistent_state=FALSE;
    /**
     * @var    string|null Table view title
     */
    public $title=NULL;
    /**
     * @var    bool Table view actions container full row
     */
    public $full_row_actions_container=TRUE;
    /**
     * @var    array Custom actions list
     */
    public $custom_actions=[];

    /**
     * TableView class constructor method
     *
     * @param array|null $params Parameters array
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->current_page=1;
        parent::__construct($params);
        $this->items_default_filterable_value=FALSE;
        if($this->persistent_state) {
            $this->sessionHash=$this->tag_id ? $this->tag_id : (strlen($this->target) ? $this->target.'_tview' : NULL);
            if(!strlen($this->sessionHash)) {
                $this->persistent_state=FALSE;
            }
        }//if($this->persistent_state)
        $this->tag_id=$this->tag_id ? $this->tag_id : $this->cHash;
        if(Module::GetDRights($this->drights_uid,Module::DRIGHT_EXPORT)) {
            $this->exportable=FALSE;
        }
        if(!is_array($this->sortby)) {
            $this->sortby=[];
        } elseif(array_key_exists('column',$this->sortby) && strlen($this->sortby['column'])) {
            $this->sortby=[$this->sortby['column']=>get_array_value($this->sortby,'direction','ASC','is_notempty_string')];
        }
    }//END public function __construct

    /**
     * Apply format to a cell value
     *
     * @param mixed      $value  Cell value
     * @param mixed      $format The format to be applied
     * @param mixed|null $formatFunc
     * @param mixed|null $defValue
     * @return string Return formatted value as string
     * @throws \NETopes\Core\AppException
     */
    protected function FormatValue($value,$format,$formatFunc=NULL,$defValue=NULL) {
        if(isset($formatFunc) && is_callable($formatFunc)) {
            $fValue=call_user_func($formatFunc,$value);
        } else {
            $fValue=$value;
        }
        if(is_string($format) && strlen($format)) {
            $result=Validator::FormatValue($fValue,$format);
        } elseif(is_array($format) && count($format)) {
            $result=Validator::FormatValue($fValue,get_array_value($format,'mode','','is_string'),get_array_value($format,'html_entities',FALSE,'bool'),get_array_value($format,'prefix','','is_string'),get_array_value($format,'sufix','','is_string'),get_array_value($format,'def_value','','is_string'),get_array_value($format,'format','','is_string'),get_array_value($format,'validation','','is_string'));
        } else {
            $result=$fValue;
        }//if(is_string($format) && strlen($format))
        if((!is_string($result) && !is_numeric($result)) || !strlen($result)) {
            return $defValue;
        }
        return $result;
    }//END protected function FormatValue

    /**
     * Gets the javascript callback string
     *
     * @param bool $onloadCallback    Include or not on load callback
     * @param bool $onchange_callback Include or not on change callback
     * @return string|null Returns javascript callback string
     */
    protected function ProcessJsCallbacks($onloadCallback=TRUE,$onchange_callback=TRUE): ?string {
        if($onloadCallback && $onchange_callback) {
            if(is_null($this->js_callbacks)) {
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
        return NULL;
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
    protected function GetActionCommand(?string $type=NULL,$params=NULL,bool $processCall=TRUE): ?string {
        $params=is_object($params) ? $params : new Params($params);
        $targetId=NULL;
        $execCallback=TRUE;
        $onloadCallback=TRUE;
        $command=NULL;
        if(substr($type,0,8)=='filters.') {
            if($type=='filters.update') {
                $execCallback=FALSE;
            }
            $command=$this->GetFilterActionCommand($type,$params,$targetId);
        }//if(substr($type,0,8)=='filters.')
        if(is_null($command)) {
            switch($type) {
                case 'sort':
                    $targetId=$this->target;
                    $onloadCallback=FALSE;
                    $sdir=$params->safeGet('direction','asc','is_notempty_string');
                    $sdir=$sdir=='asc' ? 'desc' : 'asc';
                    $command="{ 'control_hash': '{$this->cHash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'sort_by': '".$params->safeGet('column','','is_string')."', 'sort_dir': '{$sdir}', 'sessact': 'sort' } }";
                    break;
                case 'gotopage':
                    $targetId=$this->target;
                    $onloadCallback=FALSE;
                    $command="{ 'control_hash': '{$this->cHash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'page': {!page!}, 'sessact': 'page' } }";
                    break;
                case 'export_all':
                    $targetId='errors';
                    $execCallback=FALSE;
                    $command="{ 'control_hash': '{$this->cHash}', 'method': 'ExportAll', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': {} }";
                    break;
                case 'refresh':
                default:
                    $targetId=$this->target;
                    $command="{ 'control_hash': '{$this->cHash}', 'method': 'Show', 'control': '".$this->GetThis()."', 'via_post': 1, 'params': { 'f_action': 'refresh' } }";
                    break;
            }//END switch
        }//if(is_null($command))
        if(!$processCall) {
            return $command;
        }
        $jsCallback=$this->ProcessJsCallbacks($onloadCallback);
        return NApp::Ajax()->Prepare($command,$targetId,NULL,$this->loader,NULL,TRUE,($execCallback && strlen($jsCallback) ? $jsCallback : NULL),NULL,TRUE,'ControlAjaxRequest');
    }//END protected function GetActionCommand

    /**
     * Gets the processed data call params
     *
     * @param null $params
     * @param null $extra_params
     * @return void Returns data call params array
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessDataCallParams(&$params=NULL,&$extra_params=NULL) {
        $params=array_merge((is_array($this->ds_params) ? $this->ds_params : []),$params);
        $extra_params=array_merge((is_array($this->ds_extra_params) ? $this->ds_extra_params : []),$extra_params);
        if(!isset($extra_params['filters']) || !is_array($extra_params['filters'])) {
            $extra_params['filters']=[];
        }
        foreach($this->filters as $k=>$filter) {
            $fType=get_array_value($filter,'type','','is_string');
            if($fType=='0') {
                if(strlen($this->qsearch) && array_key_exists($this->qsearch,$params)) {
                    $params[$this->qsearch]=get_array_value($filter,'value','','is_string');
                }
            } elseif($dsParam=get_array_value($this->columns,[$fType,'ds_param'],NULL,'is_notempty_string')) {
                $params[$dsParam]=get_array_value($filter,'value',NULL,'isset');
            } else {
                $fField=get_array_value($filter,'field',NULL,'is_notempty_string');
                $processField=get_array_value($this->columns,[$fType,'filter_process_field'],FALSE,'bool');
                // NApp::Dlog($fField,'$fField[0]');
                if(is_null($fField) || $processField) {
                    $fField=get_array_value($this->columns,[$fType,'entity_property'],NULL,'is_notempty_string');
                    if(is_null($fField)) {
                        $fField=get_array_value($this->columns,[$fType,'db_field'],$fType,'is_notempty_string');
                    }
                    // NApp::Dlog($fField,'$fField[0.1]');
                    $fcRelations=get_array_value($this->columns,[$fType,'relation'],[],'is_array');
                    $fieldPrefix='';
                    if(count($fcRelations)) {
                        end($fcRelations);
                        $fieldPrefix=key($fcRelations).'.';
                    }//if(count($fcRelations))
                    // NApp::Dlog($fieldPrefix,'$fieldPrefix');
                    $fcFilterFields=[];
                    if(get_array_value($extra_params,'mode','','is_string')=='Doctrine') {
                        $fcFilterField=get_array_value($this->columns,[$fType,'filter_target_fields'],NULL,'?is_string');
                        if(strlen($fcFilterField)) {
                            $fcFilterFields=[$fcFilterField];
                        } else {
                            $fcFilterFields=get_array_value($this->columns,[$fType,'filter_target_fields'],[],'is_array');
                        }
                    }//if(get_array_value($extra_params,'mode','','is_string')=='Doctrine')
                    if(count($fcFilterFields)) {
                        $fField=[];
                        foreach($fcFilterFields as $fItem) {
                            $fField[]=$fieldPrefix.$fItem;
                        }
                    } else {
                        $fField=$fieldPrefix.$fField;
                    }//if(count($fcFilterFields))
                    // NApp::Dlog($fField,'$fField[1]');
                    // NApp::Dlog($filter,'$filter');
                }//if(is_null($fField))
                $filter['field']=$fField;
                $extra_params['filters'][]=$filter;
            }//if($fType=='0')
        }//END foreach
        if($this->with_pagination && !$this->export_only) {
            $extra_params['type']='count-select';
            $firstRow=$lastRow=NULL;
            ControlsHelpers::GetPaginationParams($firstRow,$lastRow,$this->current_page);
            $extra_params['first_row']=$firstRow;
            $extra_params['last_row']=$lastRow;
        }//if($this->with_pagination && !$this->export_only)
        if($this->tree) {
            $extra_params['sort']=is_array($this->sortby) && count($this->sortby) ? $this->sortby : ['LVL'=>'ASC'];
        } else {
            $extra_params['sort']=$this->sortby;
        }
    }//END protected function ProcessDataCallParams

    /**
     * Gets data to be displayed
     *
     * @return DataSet
     * @throws \NETopes\Core\AppException
     */
    protected function GetData() {
        $this->running_totals=[];
        $this->totals=[];
        if(!strlen($this->ds_class) || !strlen($this->ds_method)) {
            if(is_object($this->data)) {
                $result=$this->data;
            } else {
                $result=DataSourceHelpers::ConvertResultsToDataSet($this->data,VirtualEntity::class);
            }//if(is_object($this->data))
            $result->total_count=$result->count();
            return $result;
        }//if(!strlen($this->ds_class) || !strlen($this->ds_method))
        $daparams=$daeparams=[];
        $this->ProcessDataCallParams($daparams,$daeparams);
        // NApp::Dlog($daparams,'$daparams');
        // NApp::Dlog($daeparams,'$daeparams');
        $data=DataProvider::Get($this->ds_class,$this->ds_method,$daparams,$daeparams,FALSE,$this->ds_out_params);
        NApp::SetPageParam($this->tag_id.'#ds_out_params',$this->ds_out_params);
        if(!is_object($data)) {
            return new DataSet();
        }
        return $data;
    }//END private function GetData

    /**
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function ProcessCustomActions(): string {
        $result='';
        if(!is_array($this->custom_actions) || !count($this->custom_actions)) {
            return $result;
        }
        foreach($this->custom_actions as $action) {
            if(is_string($action)) {
                $result.=$action;
            } elseif(is_array($action)) {
                $result.=$this->GetControlFieldData($action,NULL,NULL,FALSE,NULL,FALSE);
            } else {
                NApp::Elog($action,'Invalid TableView::custom_actions item:');
            }
        }//END foreach
        return $result;
    }//END protected function ProcessCustomActions

    /**
     * Gets the actions bar controls html (except controls for filters)
     *
     * @return string|null Returns the actions bar controls html
     * @throws \NETopes\Core\AppException
     */
    protected function GetActionsBarControls(): ?string {
        //NApp::Dlog($params,'GetActionsBarControls>>$params');
        if(is_array($this->custom_actions) && count($this->custom_actions)) {
            $actions=$this->ProcessCustomActions();
        } else {
            $actions='';
        }//if(is_array($this->custom_actions) && count($this->custom_actions))
        if($this->exportable && $this->export_all && $this->with_pagination) {
            if($this->compact_mode) {
                $actions.="\t\t\t".'<button class="'.NApp::$theme->GetBtnInfoClass('tw-export-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('export_all').'" title="'.Translate::GetButton('export_all').'"><i class="fa fa-download"></i></button>'."\n";
            } else {
                $actions.="\t\t\t".'<button class="'.NApp::$theme->GetBtnInfoClass('tw-export-btn'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('export_all').'" ><i class="fa fa-download"></i>'.Translate::GetButton('export_all').'</button>'."\n";
            }//if($this->compact_mode)
        }//if($this->exportable && $this->export_all && $this->with_pagination)
        if($this->export_button) {
            if($this->compact_mode) {
                $actions.="\t\t\t".'<a class="'.NApp::$theme->GetBtnInfoClass('tw-export-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" href="'.NApp::$appBaseUrl.'/pipe/download.php?namespace='.NApp::$currentNamespace.'&dtype=datagridexcelexport&chash='.$this->cHash.'" target="_blank" title="'.Translate::GetButton('export').'"><i class="fa fa-file-excel-o"></i></a>'."\n";
            } else {
                $actions.="\t\t\t".'<a class="'.NApp::$theme->GetBtnInfoClass('tw-export-btn'.$this->buttons_size_class).'" href="'.NApp::$appBaseUrl.'/pipe/download.php?namespace='.NApp::$currentNamespace.'&dtype=datagridexcelexport&chash='.$this->cHash.'" target="_blank"><i class="fa fa-file-excel-o"></i>'.Translate::GetButton('export').'</a>'."\n";
            }//if($this->compact_mode)
        }//if($this->export_button)
        if(strlen($this->ds_class) && strlen($this->ds_method)) {
            if($this->compact_mode) {
                $actions.="\t\t\t".'<button class="'.NApp::$theme->GetBtnSuccessClass('tw-refresh-btn compact clsTitleSToolTip'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('refresh').'" title="'.Translate::GetButton('refresh').'"><i class="fa fa-refresh"></i></button>'."\n";
            } else {
                $actions.="\t\t\t".'<button class="'.NApp::$theme->GetBtnSuccessClass('tw-refresh-btn'.$this->buttons_size_class).'" onclick="'.$this->GetActionCommand('refresh').'"><i class="fa fa-refresh"></i>'.Translate::GetButton('refresh').'</button>'."\n";
            }//if($this->compact_mode)
        }//if(strlen($this->ds_class) && strlen($this->ds_method))
        if(!strlen($actions)) {
            return NULL;
        }
        $result="\t\t\t".'<div class="tw-header-item tw-actions'.(is_string($this->controls_size) && strlen($this->controls_size) ? ' form-'.$this->controls_size : '').($this->full_row_actions_container ? ' tw-full-row' : '').'">'."\n";
        $result.=$actions;
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetActionsBarControls

    /**
     * Gets the filter box HTML
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null Returns the filter box html
     * @throws \NETopes\Core\AppException
     */
    protected function GetFilterBox(Params $params): ?string {
        // NApp::Dlog($params,'GetFilterBox>>$params');
        // NApp::Dlog($this->filters,'GetFilterBox>>$this->filters');
        if(!$this->with_filter) {
            return NULL;
        }
        $filters=$this->GetFilterControls($this->columns,$params);
        $filters.=$this->GetFilterGlobalActions(!$this->auto_load_data_on_filter_change);
        $filters.=$this->GetActiveFilters($this->columns);
        if($params->safeGet('f_action','','is_string')=='update') {
            return $filters;
        }
        $result="\t\t\t".'<div class="tw-header-item tw-filters'.(is_string($this->controls_size) && strlen($this->controls_size) ? ' form-group-'.$this->controls_size : '').'" id="'.$this->tag_id.'-filter-box">'."\n";
        $result.=$filters;
        $result.="\t\t\t".'</div>'."\n";
        return $result;
    }//END protected function GetFilterBox

    /**
     * Gets the filter box html
     *
     * @param \NETopes\Core\App\Params|null $params
     * @return string|null Returns the filter box html
     * @throws \NETopes\Core\AppException
     */
    protected function GetActionsBox(?Params $params=NULL): ?string {
        if(is_null($params)) {
            $params=new Params();
        }
        $content=NULL;
        if(is_string($this->title) && strlen($this->title)) {
            $content.="\t\t\t".'<span class="tw-title">'.$this->title.'</span>'."\n";
        }
        if($this->with_filter || $this->export_button || count($this->custom_actions) || !$this->hide_actions_bar) {
            $content.="\t\t".'<div class="tw-actions-container">'."\n";
            if($this->with_filter) {
                $content.=$this->GetFilterBox($params);
            }
            $content.=$this->GetActionsBarControls();
            $content.="\t\t".'</div>'."\n";
        }//if($this->with_filter || $this->export_button || count($this->custom_actions) || !$this->hide_actions_bar)
        $result=NULL;
        if(strlen($content)) {
            $result="\t\t\t".'<div id="'.$this->tag_id.'-actions" class="'.($this->base_class.'Actions'.(strlen($this->class)>0 ? ' '.$this->class : '')).'">'."\n";
            $result.=$content;
            $result.="\t\t\t".'</div>'."\n";
        }
        return $result;
    }//END protected function GetActionsBox

    /**
     * Gets the pagination box html
     *
     * @param DataSet $items The data array
     * @return string Returns the pagination box html
     * @throws \NETopes\Core\AppException
     */
    protected function GetPaginationBox(DataSet $items): ?string {
        if(method_exists($items,'getTotalCount')) {
            $records_no=$items->getTotalCount();
        } else {
            $records_no=count($items);
        }//if(method_exists($items,'getTotalCount'))
        if(!$this->with_pagination) {
            if($this->hide_status_bar) {
                return NULL;
            }
            //$lstyle = strlen($this->width)>0 ? ($this->width!='100%' ? ' style="width: '.$this->width.'; margin: 0 auto;"' : ' style="width: '.$this->width.';"') : '';
            $result="\t".'<div class="'.$this->base_class.'Footer'.(strlen($this->class)>0 ? ' '.$this->class : '').'">'."\n";
            $result.="\t\t".'<div class="pagination-container"><span class="rec-label">'.Translate::GetLabel('records').'</span><span class="rec-no">'.number_format($records_no,0).'</span><div class="clearfix"></div></div>';
            $result.="\t".'</div>'."\n";
            return $result;
        }//if(!$this->with_pagination)
        $pagination=new SimplePageControl(['phash'=>$this->phash,'theme_type'=>$this->theme_type,'width'=>'100%','total_rows'=>$records_no,'target_id'=>$this->target,'ajax_method'=>'ControlAjaxRequest','onclick_action'=>$this->GetActionCommand('gotopage',NULL,FALSE),'js_callback'=>$this->onchange_js_callback,'current_page'=>$this->current_page]);
        $result="\t".'<div class="'.$this->base_class.'Footer'.(strlen($this->class)>0 ? ' '.$this->class : '').'">'."\n";
        $result.=$pagination->Show();
        $result.="\t".'</div>'."\n";
        return $result;
    }//END protected function GetPaginationBox

    /**
     * Gets the table header row(s)
     *
     * @param $t_c_width
     * @return string Returns the header row(s) HTML as string
     * @throws \NETopes\Core\AppException
     */
    protected function GetTableHeader(&$t_c_width): string {
        $t_c_width=0;
        $result="\t\t".'<thead>'."\n";
        $th_result=[];
        $th_temp=[];
        // NApp::Dlog($this->th_rows_no,'$this->th_rows_no');
        for($i=0; $i<$this->th_rows_no; $i++) {
            $th_result[$i]='';
            $th_temp[$i]=['text'=>NULL,'colspan'=>NULL,'skip'=>0,'width'=>0,'wtype'=>NULL];
        }//for($i=0;$i<$this->th_rows_no;$i++)
        foreach($this->columns as $k=>$v) {
            if(!is_array($v) || !count($v) || strtolower(get_array_value($v,'type','','is_string'))=='filter-only' || get_array_value($v,'hidden',FALSE,'bool')) {
                continue;
            }
            $ch_style='';
            $ch_width=get_array_value($v,'width',NULL,'is_notempty_string');
            $ch_n_width=0;
            $ch_p_width=0;
            $ch_w_type=NULL;
            if(strtolower(get_array_value($v,'type','','is_string'))=='actions') {
                if(is_null($ch_width) && is_object(NApp::$theme)) {
                    $ch_width=NApp::$theme->GetTableViewActionsWidth(get_array_value($v,'visual_count',0,'is_integer'));
                }//if(is_null($ac_width) && is_object(NApp::$theme))
                $ch_width=is_numeric($ch_width) && $ch_width>0 ? $ch_width.'px' : $ch_width;
                $ch_style.=$ch_width ? ($ch_style ? '' : ' style="').'width: '.$ch_width.';' : '';
            } else {
                if(strlen($ch_width)) {
                    if(strpos($ch_width,'%')!==FALSE) {
                        $ch_style.=($ch_style ? '' : ' style="').'width: '.$ch_width.';';
                        $ch_p_width=trim(str_replace('%','',$ch_width));
                        if(is_numeric($ch_p_width) && $ch_p_width) {
                            $ch_w_type='p';
                        } else {
                            $ch_p_width=0;
                        }//if(!is_numeric($ch_p_width) && $ch_p_width)
                    } else {
                        $ch_n_width=str_replace('px','',$ch_width);
                        if(is_numeric($ch_n_width) && $ch_n_width) {
                            $ch_style.=($ch_style ? '' : ' style="').'width: '.$ch_n_width.'px;';
                            $ch_w_type='n';
                        } else {
                            $ch_n_width=0;
                        }//if(is_numeric($ch_n_width) && $ch_n_width)
                    }//if(strpos($ch_width,'%')!==FALSE)
                }//if(strlen($ch_width))
            }//if(strtolower(get_array_value($v,'type','','is_string'))=='actions')
            $ch_style.=$ch_style ? '"' : '';
            $ch_sort_act='';
            $ch_sort_icon='';
            $ch_sclass='';
            if(get_array_value($v,'sortable',FALSE,'bool')) {
                $ch_sclass.='sortable';
                if(in_array($k,array_keys($this->sortby))) {
                    $ch_sortdir=strtolower(get_array_value($this->sortby,$k,'asc','is_notempty_string'));
                    $ch_iclass=' active';
                    $ch_psortdir=$ch_sortdir;
                } else {
                    $ch_sortdir='asc';
                    $ch_iclass='';
                    $ch_psortdir='desc';
                }//if(in_array($k,array_keys($this->sortby)))
                $ch_sort_icon='<i class="fa '.($ch_sortdir=='desc' ? 'fa-arrow-down' : 'fa-arrow-up').$ch_iclass.'"></i>';
                $ch_sort_act=' onclick="'.$this->GetActionCommand('sort',['column'=>$k,'direction'=>$ch_psortdir]).'"';
            }//if(get_array_value($v,'sortable',FALSE,'bool'))
            $ch_class=get_array_value($v,'class','','is_notempty_string');
            $ch_class=strlen(trim($ch_class.' '.$ch_sclass))>0 ? ' class="'.trim($ch_class.' '.$ch_sclass).'"' : '';
            $iterator=get_array_value($v,'iterator',[],'is_array');
            $ch_label_arr=get_array_value($v,'label',NULL,'is_notempty_array');
            if(count($iterator)) {
                foreach($iterator as $it) {
                    if(is_array($ch_label_arr)) {
                        foreach($ch_label_arr as $lk=>$lv) {
                            $ch_colspan=get_array_value($lv,'colspan',0,'is_numeric');
                            if($lk==$this->th_rows_no - 1 || $ch_colspan<2) {
                                continue;
                            }
                            $th_temp[$lk]['wtype']=$ch_w_type;
                            $th_temp[$lk]['width']=$ch_w_type=='p' ? $ch_p_width : ($ch_w_type=='n' ? $ch_n_width : 0);
                            $th_temp[$lk]['skip']=$ch_colspan - 1;
                            $th_temp[$lk]['colspan']=$ch_colspan;
                            $th_temp[$lk]['text']=get_array_value($lv,'text','&nbsp;','is_notempty_string');
                        }//END foreach
                        $ch_label=get_array_value($ch_label_arr,[$this->th_rows_no - 1,'text'],NULL,'is_notempty_string');
                    } else {
                        $ch_label=get_array_value($v,'iterator_label',get_array_value($v,'label',NULL,'is_notempty_string'),'is_notempty_string');
                    }//if(is_array($ch_label_arr))
                    $ch_label=get_array_value($it,$ch_label,'&nbsp;','is_notempty_string');
                    $ch_rowspan_no=0;
                    $ch_lvl=0;
                    for($i=0; $i<$this->th_rows_no - 1; $i++) {
                        if($th_temp[$i]['skip']>0) {
                            $th_temp[$i]['skip']--;
                            $ch_lvl++;
                            continue;
                        }//if($th_temp[$i]['skip']>0)
                        if(is_null($th_temp[$i]['text'])) {
                            $ch_rowspan_no++;
                            continue;
                        }//if(is_null($th_temp[$i]['text']))
                        $ch_rowspan=$ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no + 1).'"' : '';
                        $ch_colspan=$th_temp[$i]['colspan'] && $th_temp[$i]['colspan']>1 ? ' colspan="'.$th_temp[$i]['colspan'].'"' : '';
                        $ch_r_style='';
                        if($th_temp[$i]['wtype']=='p') {
                            $ch_r_style=' style="width: '.$th_temp[$i]['width'].'%;"';
                        } elseif($th_temp[$i]['wtype']=='n') {
                            $ch_r_style=' style="width: '.$th_temp[$i]['width'].'px;"';
                        }//if($th_result[$i]['wtype']=='p')
                        $th_result[$ch_lvl].="\t\t\t\t".'<th'.$ch_colspan.$ch_rowspan.$ch_r_style.$ch_class.'><label>'.($th_temp[$i]['text'] ? $th_temp[$i]['text'] : '&nbsp;').'</label></th>'."\n";
                        $ch_lvl++;
                        $th_temp[$i]['wtype']=NULL;
                        $th_temp[$i]['width']=0;
                        $th_temp[$i]['skip']=0;
                        $th_temp[$i]['colspan']=NULL;
                        $th_temp[$i]['text']=NULL;
                    }//END for
                    $t_c_width+=$ch_n_width>0 ? $ch_n_width + $this->cell_padding : 0;
                    $ch_rowspan=$ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no + 1).'"' : '';
                    $th_result[$ch_lvl].="\t\t\t\t".'<th'.$ch_rowspan.$ch_style.$ch_class.$ch_sort_act.'><label>'.$ch_label.'</label>'.$ch_sort_icon.'</th>'."\n";
                }//END foreach
            } else {
                if(is_array($ch_label_arr)) {
                    foreach($ch_label_arr as $lk=>$lv) {
                        $ch_colspan=get_array_value($lv,'colspan',0,'is_numeric');
                        if($lk==$this->th_rows_no - 1 || $ch_colspan<2) {
                            continue;
                        }
                        $th_temp[$lk]['wtype']=$ch_w_type;
                        $th_temp[$lk]['width']=$ch_w_type=='p' ? $ch_p_width : ($ch_w_type=='n' ? $ch_n_width : 0);
                        $th_temp[$lk]['skip']=$ch_colspan - 1;
                        $th_temp[$lk]['colspan']=$ch_colspan;
                        $th_temp[$lk]['text']=get_array_value($lv,'text','&nbsp;','is_notempty_string');
                    }//END foreach
                    $ch_label=get_array_value($ch_label_arr,[$this->th_rows_no - 1,'text'],'&nbsp;','is_notempty_string');
                } else {
                    $ch_label=get_array_value($v,'label','&nbsp;','is_notempty_string');
                }//if(is_array($ch_label_arr))
                // NApp::Dlog($ch_label,'$ch_label');
                // NApp::Dlog($th_temp,'$th_temp');
                $ch_rowspan_no=0;
                $ch_lvl=0;
                for($i=0; $i<$this->th_rows_no - 1; $i++) {
                    if($th_temp[$i]['skip']>0) {
                        $th_temp[$i]['skip']--;
                        $ch_lvl++;
                        continue;
                    }//if($th_temp[$i]['skip']>0)
                    if(is_null($th_temp[$i]['text'])) {
                        $ch_rowspan_no++;
                        continue;
                    }//if(is_null($th_temp[$i]['text']))
                    $ch_rowspan=$ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no + 1).'"' : '';
                    $ch_colspan=$th_temp[$i]['colspan'] && $th_temp[$i]['colspan']>1 ? ' colspan="'.$th_temp[$i]['colspan'].'"' : '';
                    $ch_r_style='';
                    if($th_temp[$i]['wtype']=='p') {
                        $ch_r_style=' style="width: '.$th_temp[$i]['width'].'%;"';
                    } elseif($th_temp[$i]['wtype']=='n') {
                        $ch_r_style=' style="width: '.$th_temp[$i]['width'].'px;"';
                    }//if($th_result[$i]['wtype']=='p')
                    $th_result[$ch_lvl].="\t\t\t\t".'<th'.$ch_colspan.$ch_rowspan.$ch_r_style.$ch_class.'><label>'.($th_temp[$i]['text'] ? $th_temp[$i]['text'] : '&nbsp;').'</label></th>'."\n";
                    $ch_lvl++;
                    $th_temp[$i]['wtype']=NULL;
                    $th_temp[$i]['width']=0;
                    $th_temp[$i]['skip']=0;
                    $th_temp[$i]['colspan']=NULL;
                    $th_temp[$i]['text']=NULL;
                }//END for
                $t_c_width+=$ch_n_width>0 ? $ch_n_width + $this->cell_padding : 0;
                $ch_rowspan=$ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no + 1).'"' : '';
                // NApp::Dlog($ch_style,'$ch_style');
                $th_result[$ch_lvl].="\t\t\t\t".'<th'.$ch_rowspan.$ch_style.$ch_class.$ch_sort_act.'><label>'.$ch_label.'</label>'.$ch_sort_icon.'</th>'."\n";
            }//if(count($iterator))
        }//END foreach
        for($i=0; $i<$this->th_rows_no; $i++) {
            $result.="\t\t\t".'<tr>'."\n";
            $result.=$th_result[$i];
            $result.="\t\t\t".'</tr>'."\n";
        }//END for
        $result.="\t\t".'</thead>'."\n";
        return $result;
    }//protected function GetTableHeader

    /**
     * Add the cell value to sub-totals array
     *
     * @param $name
     * @param $value
     * @param $type
     * @return void
     */
    protected function SetCellSubTotal($name,$value,$type) {
        if(!is_array($this->totals)) {
            $this->totals=[];
        }
        if(!isset($this->totals[$name]['type'])) {
            $this->totals[$name]['type']=$type;
        }
        if(!isset($this->totals[$name]['value']) || !is_numeric($this->totals[$name]['value'])) {
            $this->totals[$name]['value']=0;
        }
        switch($type) {
            case 'count':
                $this->totals[$name]['value']+=(is_null($value) || $value===0 || $value==='') ? 0 : 1;
                break;
            case 'sum':
                $this->totals[$name]['value']+=is_numeric($value) ? $value : 0;
                break;
            case 'average':
                if(!isset($this->totals[$name]['count']) || !is_numeric($this->totals[$name]['count'])) {
                    $this->totals[$name]['count']=0;
                }
                $this->totals[$name]['value']+=is_numeric($value) ? $value : 0;
                $this->totals[$name]['count']+=is_numeric($value) ? 1 : 0;
                break;
            case 'running_total':
                $this->totals[$name]['value']=is_numeric($value) ? $value : 0;
                break;
            default:
                break;
        }//END switch
    }//END protected function SetCellSubTotal

    /**
     * Gets the table cell raw value
     *
     * @param \NETopes\Core\Data\IEntity $row
     * @param array                      $v
     * @param string|null                $fieldName
     * @return mixed Returns the table cell raw value
     * @throws \NETopes\Core\AppException
     */
    protected function GetCellData(IEntity &$row,array &$v,?string $fieldName=NULL) {
        $cellValue=NULL;
        $fieldName=$fieldName ?? $v['db_field'];
        $valueSource=get_array_value($v,'value_source',NULL,'?is_string');
        switch(strtolower($valueSource)) {
            case 'relation':
                if($row instanceof IEntity) {
                    $rObj=NULL;
                    $rFirst=TRUE;
                    $cRelations=get_array_value($v,'relation',[],'is_array');
                    foreach($cRelations as $rAlias=>$cRelation) {
                        if($rFirst) {
                            $rFirst=FALSE;
                        } elseif($rObj===NULL) {
                            break;
                        }//if($rFirst)
                        if(!strlen($cRelation)) {
                            continue;
                        }
                        $rGetter='get'.ucfirst($cRelation);
                        $rObj=$rObj===NULL ? $row->$rGetter() : $rObj->$rGetter();
                    }//END foreach
                    if($rObj) {
                        $pGetter='get'.ucfirst($fieldName);
                        $cellValue=$rObj->$pGetter();
                    }//if($rObj)
                }//if(is_object($row))
                break;
            default:
                if(isset($fieldName)) {
                    $cellValue=$row->getProperty($fieldName);
                    if(!is_scalar($cellValue)) {
                        $cellValue=is_object($cellValue) ? $cellValue : NULL;
                    } else {
                        $cellValue=strlen($cellValue) ? $cellValue : NULL;
                    }//if(!is_scalar($cellValue))
                }//if(isset($fieldName))
                break;
        }//END switch
        return $cellValue;
    }//END protected function GetCellData

    /**
     * @param IEntity $row
     * @param array   $v
     * @param string  $name
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function GetRunningTotalHash(&$row,array &$v,string $name): string {
        $runningTotalOver=get_array_value($v,'running_total_over',[],'is_array');
        if(!count($runningTotalOver)) {
            return AppSession::GetNewUID($name,'sha1',TRUE);
        }
        $salt='';
        foreach($runningTotalOver as $rtField) {
            $value=$this->GetCellData($row,$v,$rtField);
            $salt.=$value instanceof DateTime ? $value->format('Y-m-d H:i:s') : $value ?? '';
        }//END foreach
        return AppSession::GetNewUID($salt,'sha1',TRUE);
    }//END protected function GetRunningTotalHash

    /**
     * Gets the table cell value (un-formatted)
     *
     * @param IEntity     $row
     * @param array       $v
     * @param string      $name
     * @param string      $type
     * @param bool        $isIterator
     * @param string|null $cClass
     * @return mixed Returns the table cell value
     * @throws \NETopes\Core\AppException
     */
    protected function GetCellValue(IEntity &$row,array &$v,string $name,string $type,bool $isIterator=FALSE,?string &$cClass=NULL) {
        $result=NULL;
        switch($type) {
            case 'actions':
                if(!check_array_key('actions',$v,'is_notempty_array')) {
                    break;
                }
                $result='';
                foreach($v['actions'] as $act) {
                    if(get_array_value($act,'hidden',FALSE,'bool')) {
                        continue;
                    }
                    $act=ControlsHelpers::ReplaceDynamicParams($act,$row);
                    $actParams=get_array_value($act,'params',[],'is_array');
                    // Check conditions for displaying action
                    $conditions=get_array_value($actParams,'conditions',NULL,'is_array');
                    if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                        continue;
                    }
                    $actType=get_array_value($act,'type','DivButton','is_notempty_string');
                    $actType='\NETopes\Core\Controls\\'.$actType;
                    if(!class_exists($actType)) {
                        NApp::Elog('Control class ['.$actType.'] not found!');
                        continue;
                    }//if(!class_exists($actType))
                    $a_dright=get_array_value($act,'dright','','is_string');
                    if(strlen($a_dright)) {
                        $dright=Module::GetDRights($this->drights_uid,$a_dright);
                        if($dright) {
                            continue;
                        }
                    }//if(strlen($a_dright))
                    $ajaxCommand=get_array_value($act,'ajax_command',NULL,'is_notempty_string');
                    $targetId=get_array_value($act,'ajax_target_id',NULL,'is_notempty_string');
                    if(!$ajaxCommand) {
                        $ajaxCommand=get_array_value($act,'command_string',NULL,'?is_string');
                    }//if(!$ajaxCommand)
                    if($ajaxCommand) {
                        $actParams['onclick']=NApp::Ajax()->Prepare($ajaxCommand,$targetId,NULL,$this->loader);
                    }//if($ajaxCommand)
                    $actControl=new $actType($actParams);
                    if(get_array_value($act,'clear_base_class',FALSE,'bool')) {
                        $actControl->ClearBaseClass();
                    }//if(get_array_value($act,'clear_base_class',FALSE,'bool'))
                    $result.=$actControl->Show();
                    $embedded_form=get_array_value($act,'embedded_form',NULL,'isset');
                    if(is_string($embedded_form) && strlen($embedded_form)) {
                        $this->row_embedded_form[]=['tag_id'=>ControlsHelpers::ReplaceDynamicParams($embedded_form,$row)];
                    } elseif(is_array($embedded_form) && count($embedded_form)) {
                        $this->row_embedded_form[]=ControlsHelpers::ReplaceDynamicParams($embedded_form,$row);
                    }//if(is_string($embedded_form) && strlen($embedded_form))
                }//END foreach
                break;
            case 'conditional_control':
            case 'control':
                $params_prefix='';
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    if($type=='conditional_control') {
                        $params_prefix='alt_';
                    } else {
                        $result=NULL;
                        break;
                    }//if($type=='conditional_control')
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    $cellDataType=get_array_value($v,'data_type','','is_string');
                    $cellValue=$this->GetCellData($row,$v);
                    if($cellDataType=='numeric') {
                        $c_summarize_type=get_array_value($v,'summarize_type','sum','is_notempty_string');
                    } else {
                        $c_summarize_type='count';
                    }//if($cellDataType=='numeric')
                    $this->SetCellSubTotal($name,$cellValue,$c_summarize_type);
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $cValue=$row->getProperty($v['db_field'],NULL,'isset');
                $defaultDisplayValue=get_array_value($v,'default_display_value',NULL,'is_string');
                if(is_null($cValue) && isset($defaultDisplayValue)) {
                    $result=$defaultDisplayValue;
                } else {
                    $result=$this->GetControlFieldData($v,$row,$cValue,$isIterator,$params_prefix);
                }//if(is_null($cValue))
                break;
            case 'value':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                $cellDataType=get_array_value($v,'data_type','','is_string');
                $cellValue=$this->GetCellData($row,$v);
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    if($cellDataType=='numeric') {
                        $c_summarize_type=get_array_value($v,'summarize_type','sum','is_notempty_string');
                    } else {
                        $c_summarize_type='count';
                    }//if($cellDataType=='numeric')
                    $this->SetCellSubTotal($name,$cellValue,$c_summarize_type);
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $result=$cellValue;
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format','','is_string'),$row);
                    if(strlen($cFormat) && $cellDataType=='numeric') {
                        if(substr($cFormat,0,7)=='percent' && substr($cFormat,-4)!='x100') {
                            $cellValue=$cellValue / 100;
                        }
                    }//if(strlen($cFormat) && $cellDataType=='numeric')
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cellValue;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'sum':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                $cellDataType=get_array_value($v,'data_type','','is_string');
                if(!in_array($cellDataType,['numeric','integer','string'])) {
                    $result=NULL;
                    break;
                }//if(!in_array($cellDataType,array('numeric','integer','string'))
                if($cellDataType=='string') {
                    $cellValue='';
                    $c_s_sep=get_array_value($v,'sum_separator',' ','is_string');
                } else {
                    $cellValue=0;
                    $c_s_sep=NULL;
                }//if($cellDataType=='string')
                foreach(get_array_value($v,'db_field',[],'is_array') as $s_db_field) {
                    if(!is_string($s_db_field) || !strlen($s_db_field) || !$row->hasProperty($s_db_field,TRUE)) {
                        continue;
                    }
                    if($cellDataType=='string') {
                        $cellValue.=(strlen($cellValue) ? $c_s_sep : '');
                        $cellValue.=$row->getProperty($s_db_field,'','is_string');
                    } else {
                        $cellValue+=$row->getProperty($s_db_field,0,'is_numeric');
                    }//if($cellDataType=='string')
                }//END foreach
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    if($cellDataType=='numeric') {
                        $c_summarize_type=get_array_value($v,'summarize_type','sum','is_notempty_string');
                    } else {
                        $c_summarize_type='count';
                    }//if($cellDataType=='numeric')
                    $this->SetCellSubTotal($name,$cellValue,$c_summarize_type);
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $result=$cellValue;
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format','','is_string'),$row);
                    if(strlen($cFormat) && $cellDataType=='numeric') {
                        if(substr($cFormat,0,7)=='percent' && substr($cFormat,-4)!='x100') {
                            $cellValue=$cellValue / 100;
                        }
                    }//if(strlen($cFormat) && $cellDataType=='numeric')
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cellValue;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case '__rowno':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                $cellValue=$result=$row->getProperty('__rowNo',NULL,'is_integer');
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cellValue;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'running_total':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                $cellDataType=get_array_value($v,'data_type','','is_string');
                $runningTotalHash=$this->GetRunningTotalHash($row,$v,$name);
                $cellPreviousValue=get_array_value($this->running_totals,[$name,$runningTotalHash],0,'is_numeric');
                $cellValue=get_array_value($v,'running_total_value',$cellPreviousValue,'?is_numeric');
                $runningTotalField=get_array_value($v,'data_type','','is_string');
                if(strlen($runningTotalField)) {
                    $cellValue=get_array_value($row,$runningTotalField,$cellValue,'?is_numeric');
                }//if(strlen($runningTotalField))
                $cellCurrentValue=$this->GetCellData($row,$v);
                if($cellDataType=='numeric') {
                    $cellValue+=is_numeric($cellCurrentValue) ? $cellCurrentValue : 0;
                } else {
                    $cellValue+=strlen($cellCurrentValue) ? 1 : 0;
                }//if($cellDataType=='numeric')
                $this->running_totals[$name][$runningTotalHash]=$cellValue;
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    $this->SetCellSubTotal($name,$cellValue,'running_total');
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $result=$cellValue;
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format','','is_string'),$row);
                    if(strlen($cFormat) && $cellDataType=='numeric') {
                        if(substr($cFormat,0,7)=='percent' && substr($cFormat,-4)!='x100') {
                            $cellValue=$cellValue / 100;
                        }
                    }//if(strlen($cFormat) && $cellDataType=='numeric')
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cellValue;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'multi-value':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                if(is_array($v['db_field']) && count($v['db_field'])) {
                    $mValue='';
                    $c_f_separator=get_array_value($v,'field_separator',' ','is_string');
                    foreach($v['db_field'] as $f) {
                        $f_value=$row->getProperty($f);
                        if(!isset($f_value) || !strlen($f_value)) {
                            continue;
                        }
                        $mValue.=(strlen($mValue) ? $c_f_separator : '').$f_value;
                    }//END foreach
                    if(!strlen(str_replace($c_f_separator,'',$mValue))) {
                        $mValue=get_array_value($v,'default_value','','is_string');
                    }//if(!strlen(str_replace($c_f_separator,'',$mValue)))
                    if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                        $this->SetCellSubTotal($name,$mValue,'count');
                    }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                    $cDefValue=get_array_value($v,'default_value','','is_string');
                    $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format',NULL,'isset'),$row);
                    $cFormatFunc=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format_func',NULL,'isset'),$row);
                    $mValue=$this->FormatValue($mValue,$cFormat,$cFormatFunc,$cDefValue);
                } else {
                    $mValue=get_array_value($v,'default_value',NULL,'is_string');
                }//if(is_array($v['db_field']) && count($v['db_field']))
                $result=$mValue;
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$result;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'indexof':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                $ci_def_index=get_array_value($v,'default_index',NULL,'is_string');
                $ci_value=$this->GetCellData($row,$v);
                if($ci_def_index && is_null($ci_value)) {
                    $ci_value=$ci_def_index;
                } else {
                    $ci_value=is_null($ci_value) ? '' : $ci_value;
                }//if($ci_def_value && is_null($ci_value))
                $ci_values=get_array_value($v,'values_collection',NULL,'isset');
                if(is_array($ci_values)) {
                    $ci_values=DataSourceHelpers::ConvertArrayToDataSet($ci_values,VirtualEntity::class);
                } elseif(!is_object($ci_values) || !is_iterable($ci_values)) {
                    $ci_values=DataSourceHelpers::ConvertArrayToDataSet([],VirtualEntity::class);
                }//if(is_array($this->value))
                $i_field=get_array_value($v,'index_field','name','is_notempty_string');
                $ci_def_value=get_array_value($v,'default_value',NULL,'is_string');
                $c_collection_class=get_array_value($v,'collection_class_field','','is_string');
                if(isset($cClass) && strlen($c_collection_class) && $ci_values->safeGet($ci_value)) {
                    $cClass=trim($cClass.' '.$ci_values->safeGet($ci_value)->getProperty($c_collection_class,'','is_string'));
                }//if(strlen($c_collection_class) && $ci_values->safeGet($ci_value))
                $cellValue=$ci_values->safeGet($ci_value) ? $ci_values->safeGet($ci_value)->getProperty($i_field,$ci_def_value,'is_string') : $ci_def_value;
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    $this->SetCellSubTotal($name,$cellValue,'count');
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $result=($cellValue ? $cellValue : NULL);
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$result;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'custom_function':
                $c_function=get_array_value($v,'function_name','','is_string');
                if(strlen($c_function) && method_exists($this,$c_function)) {
                    $cellValue=$this->$c_function($row,$v);
                } else {
                    $cellValue=get_array_value($v,'default_value','','is_string');
                }//if(strlen($c_function) && method_exists($this,$c_function))
                $cellDataType=get_array_value($v,'data_type','','is_string');
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    if($cellDataType=='numeric') {
                        $c_summarize_type=get_array_value($v,'summarize_type','sum','is_notempty_string');
                    } else {
                        $c_summarize_type='count';
                    }//if($cellDataType=='numeric')
                    $this->SetCellSubTotal($name,$cellValue,$c_summarize_type);
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $result=$cellValue;
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $cFormat=get_array_value($v,'format','','is_string');
                    if(strlen($cFormat) && $cellDataType=='numeric') {
                        if(substr($cFormat,0,7)=='percent' && substr($cFormat,-4)!='x100') {
                            $cellValue=$cellValue / 100;
                        }
                    }//if(strlen($cFormat) && $cellDataType=='numeric')
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cellValue;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'translate':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                $cellValue=$this->GetCellData($row,$v);
                if(!isset($cellValue) || !strlen($cellValue)) {
                    $cellValue=get_array_value($v,'default_value','','is_string');
                }
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    $this->SetCellSubTotal($name,$cellValue,'count');
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $cellRawValue=get_array_value($v,'prefix','','is_string').$cellValue.get_array_value($v,'sufix','','is_string');
                if(substr($cellRawValue,0,2)==='[[') {
                    $cellRawValue=substr($cellRawValue,1,-1);
                }
                $result=Translate::Get($cellRawValue);
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$result;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'checkbox':
                // Check conditions for displaing action
                $conditions=get_array_value($v,'conditions',NULL,'is_array');
                if(is_array($conditions) && !ControlsHelpers::CheckRowConditions($row,$conditions)) {
                    $result=NULL;
                    break;
                }//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
                if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name)) {
                    $this->SetCellSubTotal($name,$row->getProperty($v['db_field']),'count');
                }//if($this->with_totals && get_array_value($v,'summarize',FALSE,'bool') && strlen($name))
                $cb_classes=get_array_value($v,'checkbox_classes',NULL,'is_array');
                $cb_val=$this->GetCellData($row,$v);
                if(is_array($cb_classes) && count($cb_classes)) {
                    if(get_array_value($v,'checkbox_eval_as_bool',FALSE,'bool')) {
                        $bool_evl=is_numeric($cb_val) ? ($cb_val<0 ? -1 : ($cb_val>0 ? 1 : 0)) : ($cb_val ? 1 : 0);
                        $result='<div class="'.get_array_value($cb_classes,$bool_evl,'','is_string').'"></div>';
                    } else {
                        $result='<div class="'.get_array_value($cb_classes,$cb_val,'','is_string').'"></div>';
                    }//if(get_array_value($v,'checkbox_eval_as_bool',FALSE,'bool'))
                } else {
                    if(get_array_value($v,'checkbox_eval_as_bool',FALSE,'bool')) {
                        $bool_evl=is_numeric($cb_val) ? ($cb_val<0 ? -1 : ($cb_val>0 ? 1 : 0)) : ($cb_val ? 1 : 0);
                        $result='<div class="'.($bool_evl ? 'clsChecked' : 'clsUnchecked').'"></div>';
                    } else {
                        $result='<div class="'.($cb_val==1 ? 'clsChecked' : 'clsUnchecked').'"></div>';
                    }//if(get_array_value($v,'checkbox_eval_as_bool',FALSE,'bool'))
                }//if(is_array($cb_classes) && count($cb_classes))
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cb_val;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            case 'filter-only':
                $result=NULL;
                if($this->exportable && get_array_value($v,'export',FALSE,'bool')) {
                    $cellDataType=get_array_value($v,'data_type','','is_string');
                    $cellValue=$this->GetCellData($row,$v);
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$cellValue;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
            default:
                $result=NULL;
                if($this->exportable && get_array_value($v,'export',TRUE,'bool')) {
                    $this->export_data['data'][$row->getProperty('__rowId')][$name]=$result;
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                break;
        }//END switch
        return $result;
    }//END protected function GetCellValue

    /**
     * Gets the table row/cell tooltip html string
     *
     * @param mixed       $row
     * @param string|null $class
     * @param array|null  $tooltip
     * @return string|null Returns the table row tooltip string
     */
    protected function GetToolTip(&$row,?string &$class,?array $tooltip): ?string {
        if(!$tooltip) {
            return NULL;
        }
        $c_tooltip='';
        if(is_array($tooltip) && count($tooltip)) {
            $c_ttclass=get_array_value($tooltip,'tooltip_class','clsWebuiPopover','is_notempty_string');
            $c_t_type=get_array_value($tooltip,'type','','is_string');
            switch($c_t_type) {
                case 'image':
                    $c_t_db_field=get_array_value($tooltip,'db_field','','is_string');
                    $c_t_dbdata=get_array_value($row,$c_t_db_field,'','is_string');
                    if(!strlen($c_t_dbdata)) {
                        return '';
                    }
                    $c_t_width=get_array_value($tooltip,'width',100,'is_not0_numeric');
                    $c_t_height=get_array_value($tooltip,'height',100,'is_not0_numeric');
                    $c_t_path=get_array_value($tooltip,'path','','is_string');
                    if(strlen($c_t_path) && strlen($c_t_dbdata) && file_exists(NApp::$appPublicPath.rtrim($c_t_path,'/').'/'.$c_t_dbdata)) {
                        $c_tooltip=NApp::$appBaseUrl.rtrim($c_t_path,'/').'/'.$c_t_dbdata;
                    } else {
                        $c_tooltip=NApp::$appBaseUrl.'/repository/no-photo-300.png';
                    }//if(strlen($c_t_path) && strlen($c_tooltip) && file_exists(NApp::app_public_path.rtrim($c_t_path,'/').'/'.$c_tooltip))
                    $c_tt_label=get_array_value($tooltip,'label','','is_string');
                    $c_tooltip="<div class=\"clsDGToolTip\" style=\"width: {$c_t_width}px; height: {$c_t_height}px; background: transparent url({$c_tooltip}) no-repeat center center; background-size: {$c_t_width}px auto;\"></div>";
                    $c_tooltip=' data="'.GibberishAES::enc($c_tooltip,'HTML').'"';
                    if(strlen($c_tt_label)) {
                        $c_tooltip.=' data-title="'.$c_tt_label.'"';
                    }
                    $c_tt_left_offset=get_array_value($tooltip,'left_offset',0,'is_integer');
                    if(strlen($c_tt_left_offset)) {
                        $c_tooltip.=' data-loffset="'.$c_tt_left_offset.'"';
                    }
                    break;
                case 'db_field':
                    if($c_t_db_field=get_array_value($tooltip,'db_field',NULL,'is_array')) {
                        $c_t_fsep=get_array_value($tooltip,'field_separator',' ','is_string');
                        $c_tooltip='';
                        foreach($c_t_db_field as $field) {
                            $c_tooltip.=(strlen($c_tooltip) ? $c_t_fsep : '').nl2br($row->getProperty($field,'','is_string'));
                        }//END foreach
                    } elseif($c_t_db_field=get_array_value($tooltip,'db_field','','is_string')) {
                        $c_tooltip=nl2br($row->getProperty($c_t_db_field,'','is_string'));
                    } else {
                        $c_tooltip='';
                    }//if($c_t_db_field = get_array_value($tooltip,'db_field',NULL,'is_array'))
                    if(!strlen($c_tooltip)) {
                        return '';
                    }
                    $c_tt_label='';
                    if(!get_array_value($tooltip,'text_only',FALSE,'bool')) {
                        $c_tt_label=get_array_value($tooltip,'label','','is_string');
                    }//if(!get_array_value($tooltip,'text_only',FALSE,'bool'))
                    $c_tooltip=' data-placement="auto-top" data="'.GibberishAES::enc($c_tooltip,'HTML').'"';
                    if(strlen($c_tt_label)) {
                        $c_tooltip.=' data-title="'.$c_tt_label.'"';
                    }
                    $c_tt_left_offset=get_array_value($tooltip,'left_offset',0,'is_integer');
                    if($c_tt_left_offset>0) {
                        $c_tooltip.=' data-offset-left="'.$c_tt_left_offset.'"';
                    }
                    break;
                default:
                    break;
            }//END switch
        } elseif(is_string($tooltip) && strlen($tooltip)) {
            $c_ttclass='clsWebuiPopover';
            $c_tooltip=' data="'.GibberishAES::enc($tooltip,'HTML').'"';
        }//if(is_array($tooltip) && count($tooltip))
        if(strlen($c_tooltip)) {
            $class.=($class ? ' ' : '').$c_ttclass;
        }
        return $c_tooltip;
    }//END protected function GetToolTip

    /**
     * Gets the table cell html
     *
     * @param mixed       $row
     * @param             $v
     * @param             $name
     * @param null        $hasChild
     * @param null        $rLvl
     * @param null        $rTreeState
     * @param bool        $isIterator
     * @param string|null $iteratorLabel
     * @return string Returns the table cell html
     * @throws \NETopes\Core\AppException
     */
    protected function SetCell(&$row,&$v,$name,$hasChild=NULL,$rLvl=NULL,$rTreeState=NULL,$isIterator=FALSE,?string $iteratorLabel=NULL): string {
        $cell_type=strtolower(get_array_value($v,'type','','is_string'));
        $result='';
        $c_style='';
        $c_halign=get_array_value($v,'halign',NULL,'is_notempty_string');
        $c_style.=$c_halign ? ($c_style ? '' : ' style="').'text-align: '.$c_halign.';' : '';
        $c_valign=get_array_value($v,'valign',NULL,'is_notempty_string');
        $c_style.=$c_valign ? ($c_style ? '' : ' style="').'vertical-align: '.$c_valign.';' : '';
        if($cell_type!='actions') {
            $ac_width=get_array_value($v,'width',NULL,'is_notempty_string');
            $ac_width=is_numeric($ac_width) && $ac_width>0 ? $ac_width.'px' : $ac_width;
            $c_style.=$ac_width ? ($c_style ? '' : ' style="').'width: '.$ac_width.';' : '';
            $c_style.=$c_style ? '"' : '';
        }//if($cell_type!='actions')
        $c_class=get_array_value($v,'class','','is_string');
        $c_class_field=get_array_value($v,'class_field','','is_string');
        if(strlen($c_class_field)) {
            $c_class=trim($c_class.' '.get_array_value($row,$c_class_field,'','is_string'));
        }
        $c_tooltip=$this->GetToolTip($row,$c_class,get_array_value($v,'tooltip',NULL,'isset'));
        $c_cond_format_class=get_array_value($v,['conditional_class','class'],'','is_string');
        $c_cond_format_conditions=get_array_value($v,['conditional_class','conditions'],[],'is_array');
        if(strlen($c_cond_format_class) && count($c_cond_format_conditions)) {
            if(ControlsHelpers::CheckRowConditions($row,$c_cond_format_conditions)) {
                $c_class=trim($c_class.' '.$c_cond_format_class);
            }//if(ControlsHelpers::CheckRowConditions($row,$c_cond_format_conditions))
        }//if(strlen($c_cond_format_class) && count($c_cond_format_conditions))
        if($cell_type=='actions') {
            $c_class=trim('act-col '.$c_class);
        }
        switch($cell_type) {
            case 'actions':
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                $ac_width=get_array_value($v,'width',NULL,'is_notempty_string');
                if(is_null($ac_width) && is_object(NApp::$theme)) {
                    $ac_width=NApp::$theme->GetTableViewActionsWidth(get_array_value($v,'visual_count',0,'is_integer'));
                }//if(is_null($ac_width) && is_object(NApp::$theme))
                $ac_width=is_numeric($ac_width) && $ac_width>0 ? $ac_width.'px' : $ac_width;
                $c_style.=$ac_width ? ($c_style ? '' : ' style="').'width: '.$ac_width.';' : '';
                $c_style.=$c_style ? '"' : '';
                if(!check_array_key('actions',$v,'is_notempty_array')) {
                    $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>&nbsp;</td>'."\n";
                    break;
                }//if(!check_array_key('actions',$v,'is_notempty_array'))
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'."\n";
                $result.=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator);
                $result.="\t\t\t\t".'</td>'."\n";
                break;
            case 'control':
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                $cellValue=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator);
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.(is_null($cellValue) ? '&nbsp;' : $cellValue).'</td>'."\n";
                break;
            case 'running_total':
            case 'value':
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
                    if($isIterator) {
                        $this->export_data['columns'][$name]=array_merge($v,['name'=>$name,'label'=>$iteratorLabel]);
                    } else {
                        $this->export_data['columns'][$name]=array_merge($v,['name'=>$name]);
                    }
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
                $t_value='';
                if($this->tree && $v['db_field']==get_array_value($this->tree,'main_field','name','is_notempty_string')) {
                    $t_value.=($rLvl>1 ? str_pad('',strlen($this->tree_ident) * ($rLvl - 1),$this->tree_ident) : '');
                    if($hasChild) {
                        $t_s_val=$rTreeState ? 1 : 0;
                        $t_value.='<input type="image" value="'.$t_s_val.'" class="clsTreeGridBtn" onclick="TreeGridViewAction(this,'.$row->getProperty('id').',\''.($this->tag_id ? $this->tag_id : $this->cHash).'_table\')" src="'.NApp::$appBaseUrl.AppConfig::GetValue('app_js_path').'/controls/images/transparent12.gif">';
                    } else {
                        $t_value.='<span class="clsTreeGridBtnNoChild"></span>';
                    }//if($hasChild)
                }//if($this->tree && $v['db_field']==get_array_value($this->tree,'main_field','name','is_notempty_string'))
                $cellValue=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator);
                $cDefValue=get_array_value($v,'default_value','','is_string');
                $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format',NULL,'isset'),$row);
                $c_cond_format=get_array_value($v,'conditional_format',NULL,'is_notempty_array');
                if($c_cond_format) {
                    $c_cf_field=get_array_value($c_cond_format,'field','','is_string');
                    if(strlen($c_cf_field)) {
                        $c_cf_values=get_array_value($c_cond_format,'values',[],'is_array');
                        $cFormat=get_array_value($c_cf_values,$row->getProperty($c_cf_field),$cFormat,'isset');
                    }//if(strlen($c_cf_field))
                }//if($c_cond_format)
                $cFormatFunc=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format_func',NULL,'isset'),$row);
                $cellValue=$this->FormatValue($cellValue,$cFormat,$cFormatFunc,$cDefValue);
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.$t_value.$cellValue.'</td>'."\n";
                break;
            case 'sum':
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
                    $this->export_data['columns'][$name]=array_merge($v,['name'=>$name]);
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
                $t_value='';
                $cellValue=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator);
                $cDefValue=get_array_value($v,'default_value','','is_string');
                $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format',NULL,'isset'),$row);
                $c_cond_format=get_array_value($v,'conditional_format',NULL,'is_notempty_array');
                if($c_cond_format) {
                    $c_cf_field=get_array_value($c_cond_format,'field','','is_string');
                    if(strlen($c_cf_field)) {
                        $c_cf_values=get_array_value($c_cond_format,'values',[],'is_array');
                        $cFormat=get_array_value($c_cf_values,$row->getProperty($c_cf_field),$cFormat,'isset');
                    }//if(strlen($c_cf_field))
                }//if($c_cond_format)
                $cFormatFunc=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format_func',NULL,'isset'),$row);
                $cellValue=$this->FormatValue($cellValue,$cFormat,$cFormatFunc,$cDefValue);
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.$t_value.$cellValue.'</td>'."\n";
                break;
            case '__rowno':
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
                    $this->export_data['columns'][$name]=array_merge($v,['name'=>$name]);
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
                $cellValue=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator);
                $cDefValue=get_array_value($v,'default_value','','is_string');
                $cFormat=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format',NULL,'isset'),$row);
                $c_cond_format=get_array_value($v,'conditional_format',NULL,'is_notempty_array');
                if($c_cond_format) {
                    $c_cf_field=get_array_value($c_cond_format,'field','','is_string');
                    if(strlen($c_cf_field)) {
                        $c_cf_values=get_array_value($c_cond_format,'values',[],'is_array');
                        $cFormat=get_array_value($c_cf_values,$row->getProperty($c_cf_field),$cFormat,'isset');
                    }//if(strlen($c_cf_field))
                }//if($c_cond_format)
                $cFormatFunc=ControlsHelpers::ReplaceDynamicParams(get_array_value($v,'format_func',NULL,'isset'),$row);
                $cellValue=$this->FormatValue($cellValue,$cFormat,$cFormatFunc,$cDefValue);
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.$cellValue.'</td>'."\n";
                break;
            case 'multi-value':
            case 'indexof':
                if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
                    $this->export_data['columns'][$name]=array_merge($v,['name'=>$name]);
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
                $cellValue=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator,$c_class);
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.(is_null($cellValue) ? '&nbsp;' : $cellValue).'</td>'."\n";
                break;
            case 'translate':
            case 'checkbox':
            default:
                $c_class=$c_class ? ' class="'.$c_class.'"' : '';
                if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
                    $this->export_data['columns'][$name]=array_merge($v,['name'=>$name]);
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
                $cellValue=$this->GetCellValue($row,$v,$name,$cell_type,$isIterator);
                $result.="\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.(is_null($cellValue) ? '&nbsp;' : $cellValue).'</td>'."\n";
                break;
        }//END switch
        return $result;
    }//END protected function SetCell

    /**
     * Gets the table row html
     *
     * @param IEntity     $row
     * @param string|null $rcClass
     * @param bool        $hasChild
     * @return string Returns the table row html
     * @throws \NETopes\Core\AppException
     */
    protected function SetRow(IEntity $row,?string $rcClass=NULL,bool $hasChild=FALSE): string {
        $result='';
        $r_style='';
        $rTData='';
        $col_no=0;
        $this->row_embedded_form=[];
        $rLvl=$row->getProperty('lvl',1,'is_integer');
        $rTreeState=get_array_value($this->tree,'opened',FALSE,'bool');
        if(!$this->export_only) {
            $r_color='';
            if(strlen($this->row_color_field)) {
                $r_color=$row->getProperty($this->row_color_field,'','is_string');
            }
            if($this->tree && $rLvl>$this->tree_top_lvl) {
                if(strlen($r_color)) {
                    $r_style.=' background-color: '.$r_color.';';
                }
                if(!$rTreeState) {
                    $r_style.=' display: none;';
                }
                $r_style=strlen($r_style) ? ' style="'.$r_style.'"' : '';
                $rcClass.=(strlen($rcClass) ? ' ' : '').'clsTreeGridChildOf'.$row->getProperty('id_parent',NULL,'is_integer');
                $rTData=$row->getProperty('has_child',0,'is_integer') ? ' data-id="'.$row->getProperty('id',NULL,'is_integer').'"' : '';
            } else {
                if(strlen($r_color)) {
                    $r_style=' style="background-color: '.$r_color.';"';
                }
                if(strlen($this->row_extra_tag_params)) {
                    $rTData=' '.ControlsHelpers::ReplaceDynamicParams($this->row_extra_tag_params,$row);
                }
            }//if($this->tree && $rLvl>$this->tree_top_lvl)
            $r_cc=FALSE;
            $r_cc_class=get_array_value($this->row_conditional_class,'class','','is_string');
            $r_cc_cond=get_array_value($this->row_conditional_class,'conditions',NULL,'is_array');
            if(strlen($r_cc_class) && ControlsHelpers::CheckRowConditions($row,$r_cc_cond)) {
                $rcClass.=($rcClass ? ' ' : '').$r_cc_class;
                $r_cc=TRUE;
            }//if(strlen($r_cc_class) && Control::CheckRowConditions($row,$r_cc_cond))
            if(strlen($this->row_class_field)) {
                $rClassFromField=$row->getProperty($this->row_class_field,'','is_string');
                if(strlen($rClassFromField)) {
                    $rcClass=strlen($rcClass) ? $rcClass.' '.$rClassFromField : $rClassFromField;
                }
            }//if(strlen($this->row_class_field))
            $rClass=($rcClass ? $rcClass : ($this->alternate_row_color && !$r_cc ? 'stdc' : '')).(strlen($this->row_class) ? ' '.$this->row_class : '');
            $r_tooltip=$this->GetToolTip($row,$rClass,$this->row_tooltip);
            $rClass=strlen($rClass) ? ' class="'.$rClass.'"' : '';
            $result.="\t\t\t".'<tr'.$rClass.$r_style.$r_tooltip.$rTData.'>'."\n";
        }//if(!$this->export_only)
        foreach($this->columns as $k=>$v) {
            $c_type=strtolower(get_array_value($v,'type','','is_string'));
            if(!is_array($v) || !count($v)) {
                continue;
            }
            if($c_type=='filter-only' || get_array_value($v,'hidden',FALSE,'bool')) {
                if($this->exportable && get_array_value($v,'export',($c_type!='filter-only'),'bool')) {
                    if(!array_key_exists($k,$this->export_data['columns'])) {
                        $this->export_data['columns'][$k]=array_merge($v,['name'=>$k]);
                    }
                    $this->GetCellValue($row,$v,$k,$c_type);
                }//if($this->exportable && get_array_value($v,'export',TRUE,'bool'))
                continue;
            }//if($c_type=='filter-only' || get_array_value($v,'hidden',FALSE,'bool'))
            if($c_type=='actions' && $this->export_only) {
                continue;
            }
            $iterator=get_array_value($v,'iterator',[],'is_array');
            if(count($iterator)) {
                $ik=get_array_value($v,'iterator_key','id','is_notempty_string');
                $rowSep=get_array_value($v,'row_separator',':#:','is_notempty_string');
                $keySep=get_array_value($v,'key_separator',',','is_notempty_string');
                $isKeyValueField=get_array_value($v,'is_key_value_field',FALSE,'bool');
                $values=[];
                try {
                    $valuesArray=explode($rowSep,$row->getProperty(get_array_value($v,'db_field','','is_string')));
                    if($isKeyValueField) {
                        foreach($valuesArray as $vs) {
                            $vsArray=explode($keySep,$vs);
                            if(count($vsArray)<2) {
                                continue;
                            } elseif(count($vsArray)>2) {
                                $values[$vsArray[0]]=['__it_id'=>$vsArray[2],'__it_key'=>$vsArray[0],'__it_value'=>$vsArray[1]];
                            } else {
                                $values[$vsArray[0]]=['__it_id'=>$vsArray[0],'__it_key'=>$vsArray[0],'__it_value'=>$vsArray[1]];
                            }
                        }//END foreach
                    } else {
                        $keysArray=explode($keySep,$row->getProperty(get_array_value($v,'db_keys_field','','is_string')));
                        $idsArray=explode($keySep,$row->getProperty(get_array_value($v,'db_ids_field','','is_string')));
                        foreach($keysArray as $kl=>$vl) {
                            $values[$vl]=['__it_id'=>$idsArray[$kl],'__it_key'=>$vl,'__it_value'=>$valuesArray[$kl]];
                        }//END foreach
                    }
                } catch(AppException $e) {
                    NApp::Elog($e);
                    throw $e;
                }//END try
                foreach($iterator as $it) {
                    $i_row_def=['__it_id'=>NULL,'__it_key'=>$it[$ik],'__it_value'=>NULL];
                    $i_row=clone $row;
                    $i_row->merge(get_array_value($values,$it[$ik],$i_row_def,'is_array'));
                    $i_v=$v;
                    $i_v['db_field']='__it_value';
                    $i_v['db_key']='__it_id';
                    $iteratorColumnName=$k.'-'.$it[$ik];
                    $iteratorLabel=get_array_value($it,get_array_value($v,'iterator_label','name','is_notempty_string'),NULL,'is_string');
                    if($this->export_only) {
                        if(get_array_value($v,'export',TRUE,'bool')) {
                            if(!array_key_exists($k,$this->export_data['columns'])) {
                                $this->export_data['columns'][$iteratorColumnName]=array_merge($v,['name'=>$iteratorColumnName,'label'=>$iteratorLabel]);
                            }
                            $this->GetCellValue($i_row,$i_v,$iteratorColumnName,$c_type);
                        }//if(get_array_value($v,'export',TRUE,'bool'))
                    } else {
                        $result.=$this->SetCell($i_row,$i_v,$iteratorColumnName,$hasChild,$rLvl,$rTreeState,TRUE,$iteratorLabel);
                        $col_no++;
                    }//if($this->export_only)
                }//END foreach
            } else {
                if($this->export_only) {
                    if(get_array_value($v,'export',TRUE,'bool')) {
                        if(!array_key_exists($k,$this->export_data['columns'])) {
                            $this->export_data['columns'][$k]=array_merge($v,['name'=>$k]);
                        }
                        $this->GetCellValue($row,$v,$k,$c_type);
                    }//if(get_array_value($v,'export',TRUE,'bool'))
                } else {
                    $result.=$this->SetCell($row,$v,$k,$hasChild,$rLvl,$rTreeState);
                    $col_no++;
                }//if($this->export_only)
            }//if(count($iterator))
        }//END foreach
        if(!$this->export_only) {
            $result.="\t\t\t".'</tr>'."\n";
            if(is_array($this->row_embedded_form) && count($this->row_embedded_form)) {
                foreach($this->row_embedded_form as $eForm) {
                    if(!isset($eForm['tag_id']) || !is_string($eForm['tag_id']) || !strlen($eForm['tag_id'])) {
                        continue;
                    }
                    $re_colspan=$col_no>1 ? ' colspan="'.$col_no.'"' : '';
                    $eFormClass=trim(get_array_value($eForm,'class','','is_string'));
                    if(get_array_value($eForm,'clear_base_class',FALSE,'bool')) {
                        $eFormClass=strlen($eFormClass) ? ' class="'.$eFormClass.'"' : '';
                    } else {
                        $eFormClass=' class="clsFormRow'.(strlen($eFormClass) ? ' '.$eFormClass : '').'"';
                    }//if(get_array_value($eForm,'clear_base_class',FALSE,'bool'))
                    $eFormExtraAttr=trim(get_array_value($eForm,'extra_attributes','','is_string'));
                    $eFormExtraAttr=(strlen($eFormExtraAttr) ? ' ' : '').$eFormExtraAttr;
                    $hidden=get_array_value($eForm,'hidden',TRUE,'bool');
                    $result.="\t\t\t".'<tr id="'.$eForm['tag_id'].'-row"'.$eFormClass.$eFormExtraAttr.($hidden ? ' style="display: none;"' : '').'><td id="'.$eForm['tag_id'].'"'.$re_colspan.'></td></tr>'."\n";
                }//END foreach
            }//if(is_array($this->row_embedded_form) && count($this->row_embedded_form))
        }//if(!$this->export_only)
        return $result;
    }//END protected function SetRow

    /**
     * Gets the total row html
     *
     * @return string Returns the total row html
     * @throws \NETopes\Core\AppException
     */
    protected function SetTotalRow() {
        if(!is_array($this->totals) || !count($this->totals)) {
            return NULL;
        }
        $result="\t\t\t".'<tr class="tr-totals">'."\n";
        foreach($this->columns as $k=>$v) {
            if(!is_array($v) || !count($v) || strtolower(get_array_value($v,'type','','is_string'))=='filter-only') {
                continue;
            }
            $c_style='';
            $c_halign=get_array_value($v,'total_halign',get_array_value($v,'halign','','is_string'),'is_string');
            if(strlen($c_halign)) {
                $c_style.='text-align: '.$c_halign.'; ';
            }
            $c_valign=get_array_value($v,'total_valign',get_array_value($v,'valign','','is_string'),'is_string');
            if(strlen($c_valign)) {
                $c_style.='vertical-align: '.$c_valign.'; ';
            }
            if(strlen($c_style)) {
                $c_style=' style="'.trim($c_style).'"';
            }
            $c_class=' class="'.trim('td-totals '.get_array_value($v,'class','','is_string')).'"';
            $tdTagId=get_array_value($v,'total_cell_id','','is_string');
            if(strlen($tdTagId)) {
                $tdTagId=' id="'.$tdTagId.'"';
            }
            if(array_key_exists($k,$this->totals)) {
                $c_sumtype=$this->totals[$k]['type'];
                if($c_sumtype=='average') {
                    $cellValue=$this->totals[$k]['value'] / ($this->totals[$k]['count']>0 ? $this->totals[$k]['value'] : 1);
                } else {
                    $cellValue=$this->totals[$k]['value'];
                }//if($c_sumtype=='average')
                if($c_sumtype=='count') {
                    $cFormat='decimal0';
                    $cellValue=Validator::FormatValue($cellValue,$cFormat);
                } elseif(get_array_value($v,'data_type','','is_string')!='numeric') {
                    $cFormat='decimal2';
                    $cellValue=Validator::FormatValue($cellValue,$cFormat);
                } else {
                    $cFormat=get_array_value($v,'format',NULL,'is_notempty_string');
                    if($cFormat) {
                        $cellValue=Validator::FormatValue($cellValue,$cFormat);
                    } else {
                        $cFormat=get_array_value($v,'format',NULL,'is_notempty_array');
                        if($cFormat) {
                            $cellValue=Validator::FormatValue($cellValue,get_array_value($cFormat,'mode',NULL,'?is_array'),get_array_value($cFormat,'regionals',NULL,'?is_string'),get_array_value($cFormat,'prefix',NULL,'?is_string'),get_array_value($cFormat,'sufix',NULL,'?is_string'),get_array_value($cFormat,'def_value',NULL,'?is_string'),get_array_value($cFormat,'validation',NULL,'?is_string'),get_array_value($cFormat,'html_entities',FALSE,'bool'));
                        }//if($cFormat)
                    }//if($cFormat)
                }//if($c_sumtype=='count')
                $result.="\t\t\t\t".'<td'.$tdTagId.$c_class.$c_style.'>'.$cellValue.'</td>'."\n";
            } else {
                $cellValue=get_array_value($v,'summarize_label','&nbsp;','is_notempty_string');
                $result.="\t\t\t\t".'<td'.$tdTagId.$c_class.$c_style.'>'.$cellValue.'</td>'."\n";
            }//if(array_key_exists($k,$this->totals))
        }//END foreach
        $result.="\t\t\t".'</tr>'."\n";
        return $result;
    }//END protected function SetTotalRow

    /**
     * Gets the table html iterating data array
     *
     * @param DataSet|null             $data
     * @param \NETopes\Core\App\Params $params
     * @param string|null              $rcClass
     * @param int|null                 $lvl
     * @param int|null                 $id_parent
     * @return string|null Returns the table html
     * @throws \NETopes\Core\AppException
     */
    protected function IterateData(?DataSet $data,Params $params,?string $rcClass=NULL,?int $lvl=NULL,?int $id_parent=NULL): ?string {
        // NApp::Dlog(array('params'=>$params,'lvl'=>$lvl,'id_parent'=>$id_parent,'r_cclass'=>$rcClass),'IterateData');
        if(!isset($data) || !count($data)) {
            return NULL;
        }
        $result='';
        if($this->tree) {
            $has_parent=is_numeric($id_parent) && $id_parent>0;
            if(is_null($lvl)) {
                $this->tree_top_lvl=$lvl=$data->first()->getProperty('lvl',1,'is_integer');
            }
            foreach($data as $rowId=>$row) {
                if($row->getProperty('lvl',1,'is_integer')!=$lvl || ($has_parent && $row->getProperty('id_parent',1,'is_integer')!=$id_parent)) {
                    continue;
                }
                $row->setProperty('__rowId',$rowId);
                $row->setProperty('__rowNo',$rowId);
                $data->remove($rowId);
                if($this->export_only) {
                    if($row->getProperty('has_child',0,'is_integer')==1) {
                        $this->IterateData($data,$params,$rcClass,$lvl + 1,$row->safeGetId(NULL,'is_integer'));
                    }//if($row->getProperty('has_child',0,'is_integer')==1)
                    $this->SetRow($row,$rcClass,FALSE);
                } else {
                    $children='';
                    $rcClass=$this->alternate_row_color ? ($rcClass ? '' : 'altc') : '';
                    if($row->getProperty('has_child',0,'is_integer')==1) {
                        $children=$this->IterateData($data,$params,$rcClass,$lvl + 1,$row->safeGetId(NULL,'is_integer'));
                    }//if($row->getProperty('has_child',0,'is_integer')==1)
                    $result.=$this->SetRow($row,$rcClass,(bool)strlen($children));
                    $result.=$children;
                }//if($this->export_only)
            }//END foreach
        } else {
            $rowId=0;
            if($this->export_only) {
                foreach($data as $row) {
                    $row->setProperty('__rowId',$rowId);
                    $row->setProperty('__rowNo',$rowId + 1);
                    $this->SetRow($row,$rcClass);
                    $rowId++;
                }//END foreach
            } else {
                $firstRow=$lastRow=NULL;
                ControlsHelpers::GetPaginationParams($firstRow,$lastRow,$this->current_page);
                /** @var IEntity $row */
                foreach($data as $row) {
                    $row->setProperty('__rowId',$rowId);
                    $row->setProperty('__rowNo',abs($firstRow) + $rowId);
                    $rcClass=$this->alternate_row_color ? ($rcClass ? '' : 'altc') : '';
                    $result.=$this->SetRow($row,$rcClass);
                    $rowId++;
                }//END foreach
            }//if($this->export_only)
        }//if($this->tree)
        if(is_null($lvl) || $lvl==1) {
            if($this->export_only) {
                if($this->with_totals) {
                    $this->SetTotalRow();
                }
            } else {
                if($this->with_totals) {
                    $totals_row=$this->SetTotalRow();
                    if($this->totals_row_first) {
                        $result="\t\t".'<tbody>'."\n".$totals_row.$result;
                        $result.="\t\t".'</tbody>'."\n";
                    } else {
                        $result="\t\t".'<tbody>'."\n".$result;
                        $result.="\t\t".'</tbody>'."\n";
                        $result.="\t\t".'<tfoot>'."\n";
                        $result.=$totals_row;
                        $result.="\t\t".'</tfoot>'."\n";
                    }//if($this->totals_row_first)
                }//if($this->with_totals)
            }//if($this->export_only)
        }//if(is_null($lvl) || $lvl==1)
        return $result;
    }//END protected function IterateData

    /**
     * Gets the persistent state from session, if it is the case
     *
     * @param \NETopes\Core\App\Params $params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected function LoadState(Params $params) {
        // NApp::Dlog($params,'LoadState>>$params');
        // NApp::Dlog($this->sessionHash,'$this->sessionHash');
        if($this->persistent_state) {
            $sessact=$params->safeGet('sessact','','is_string');
            switch($sessact) {
                case 'page':
                    $this->current_page=$params->safeGet('page',$this->current_page,'is_numeric');
                    $ssortby=NApp::GetPageParam($this->sessionHash.'#sortby');
                    $this->sortby=is_array($ssortby) && count($ssortby) ? $ssortby : $this->sortby;
                    $this->filters=NApp::GetPageParam($this->sessionHash.'#filters');
                    break;
                case 'sort':
                    $this->current_page=$params->safeGet('page',1,'is_numeric');
                    if(strlen($params->safeGet('sort_by',NULL,'?is_string'))) {
                        $this->sortby=[$params->safeGet('sort_by',NULL,'?is_string')=>$params->safeGet('sort_dir','ASC','is_notempty_string')];
                    }
                    $this->filters=NApp::GetPageParam($this->sessionHash.'#filters');
                    break;
                case 'filters':
                    $this->current_page=$params->safeGet('page',1,'is_numeric');
                    $ssortby=NApp::GetPageParam($this->sessionHash.'#sortby');
                    $this->sortby=is_array($ssortby) && count($ssortby) ? $ssortby : $this->sortby;
                    $this->ProcessActiveFilters($params);
                    break;
                case 'reset':
                    $this->current_page=$params->safeGet('page',1,'is_numeric');
                    if(strlen($params->safeGet('sort_by',NULL,'?is_string'))) {
                        $this->sortby=[$params->safeGet('sort_by',NULL,'?is_string')=>$params->safeGet('sort_dir','ASC','is_notempty_string')];
                    }
                    $this->ProcessActiveFilters($params);
                    break;
                default:
                    $this->current_page=NApp::GetPageParam($this->sessionHash.'#currentpage');
                    if(!$this->current_page) {
                        $this->current_page=$params->safeGet('page',$this->current_page,'is_numeric');
                    }//if(!$this->current_page)
                    $ssortby=NApp::GetPageParam($this->sessionHash.'#sortby');
                    if(!is_array($ssortby) || !count($ssortby)) {
                        if(strlen($params->safeGet('sort_by',NULL,'?is_string'))) {
                            $this->sortby=[$params->safeGet('sort_by',NULL,'?is_string')=>$params->safeGet('sort_dir','ASC','is_notempty_string')];
                        }
                    } else {
                        $this->sortby=$ssortby;
                    }//if(!is_array($ssortby) && count($ssortby))
                    $this->filters=NApp::GetPageParam($this->sessionHash.'#filters');
                    $this->ProcessActiveFilters($params);
                    break;
            }//END switch
            NApp::SetPageParam($this->sessionHash.'#currentpage',$this->current_page);
            NApp::SetPageParam($this->sessionHash.'#sortby',$this->sortby);
            NApp::SetPageParam($this->sessionHash.'#filters',$this->filters);
        } else {
            $this->current_page=$params->safeGet('page',$this->current_page,'is_numeric');
            if(strlen($params->safeGet('sort_by',NULL,'?is_string'))) {
                $this->sortby=[$params->safeGet('sort_by',NULL,'?is_string')=>$params->safeGet('sort_dir','ASC','is_notempty_string')];
            }
            $this->ProcessActiveFilters($params);
        }//if($this->persistent_state)
    }//END protected function LoadState

    /**
     * Generate and return the control HTML
     *
     * @param \NETopes\Core\App\Params $params
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(Params $params): ?string {
        // NApp::Dlog($params,'SetControl>>$params');
        // NApp::Dlog($this->auto_load_data_on_filter_change,'SetControl>>auto_load_data_on_filter_change');
        $fActionsParam=$params->safeGet('f_action',NULL,'?is_string');
        // NApp::Dlog($fActionsParam,'SetControl>>$fActionsParam');
        $this->LoadState($params);
        // NApp::Dlog($this->filters,'SetControl>>$this->filters');
        // NApp::Dlog($this->groups,'SetControl>>$this->groups');
        if(isset($fActionsParam)) {
            if($this->auto_load_data_on_filter_change || in_array($fActionsParam,['apply','refresh'])) {
                $items=$this->GetData();
            } else {
                $items=new DataSet();
            }//if($this->auto_load_data_on_filter_change || in_array($fActionsParam,['apply','refresh']))
        } else {
            $items=$this->auto_load_data ? $this->GetData() : new DataSet();
            $this->auto_load_data=TRUE;
        }//if(isset($fActionsParam))
        // NApp::Dlog($items,'SetControl>>$items');
        $table_data=NULL;
        if(is_object($items)) {
            if($this->exportable) {
                $this->export_data=['columns'=>[],'data'=>[]];
            }
            $table_data=$this->IterateData($items,$params,'altc');
            // NApp::Dlog($this->export_data,'export_data');
            if($this->exportable && $this->export_data) {
                $this->export_button=TRUE;
                $this->export_data['with_borders']=TRUE;
                $this->export_data['freeze_pane']=TRUE;
                $this->export_data['default_width']=150;
                $params=[
                    'pre_processed_data'=>TRUE,
                    'output'=>TRUE,
                    'layouts'=>[$this->export_data],
                    'summarize'=>$this->with_totals,
                ];
                $cachefile=AppHelpers::GetCachePath().'datagrid/'.$this->cHash.'.tmpexp';
                //NApp::Dlog($cachefile,'$cachefile');
                try {
                    if(!file_exists(AppHelpers::GetCachePath().'datagrid')) {
                        mkdir(AppHelpers::GetCachePath().'datagrid',755);
                    }//if(!file_exists(AppHelpers::GetCachePath().'datagrid'))
                    if(file_exists($cachefile)) {
                        unlink($cachefile);
                    }
                    file_put_contents($cachefile,serialize($params));
                } catch(Exception $e) {
                    NApp::Elog($e);
                    $this->export_button=FALSE;
                    $this->export_data=NULL;
                }//END try
            }//if($this->exportable && $this->export_data)
        }//if(isset($items['data']))
        $lClass=$this->base_class.(strlen($this->class) ? ' '.$this->class : '').(($this->scrollable || ($this->min_width && !$this->width)) ? ' clsScrollable' : ' clsFixedWidth');
        switch($this->theme_type) {
            case 'bootstrap3':
                $result='<div class="row">'."\n";
                if($this->is_panel===TRUE) {
                    $result.="\t".'<div class="col-md-12">'."\n";
                    $result.="\t\t".'<div class="panel panel-flat '.$lClass.'" id="'.$this->tag_id.'">'."\n";
                    $closing_tags="\t\t".'</div>'."\n";
                    $closing_tags.="\t".'</div>'."\n";
                } else {
                    $result.="\t".'<div class="col-md-12 '.$lClass.'" id="'.$this->tag_id.'">'."\n";
                    $closing_tags="\t".'</div>'."\n";
                }//if($this->is_panel===TRUE)
                $result.=$this->GetActionsBox(new Params());
                $result.="\t".'<div class="clsTContainer'.(strlen($this->container_class) ? ' '.$this->container_class : '').'">'."\n";
                $t_c_width=NULL;
                $th_result=$this->GetTableHeader($t_c_width);
                $tContainerFull='';
                if(strlen($this->width) && $this->width!=='100%') {
                    $tContainerFull='<div class="clsTContainerFull" style="width: '.$this->width.'px;">';
                } else {
                    if($this->min_width) {
                        $tContainerFull='<div class="clsTContainerFull" style="min-width: '.$this->min_width.'px;">';
                    } elseif($this->scrollable && $t_c_width>0) {
                        $tContainerFull='<div class="clsTContainerFull" style="width: '.$t_c_width.'px;">';
                    }//if($this->min_width)
                }//if(strlen($this->width) && $this->width!=='100%')
                if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0') {
                    $lClass.=' rh-'.(is_numeric($this->row_height) ? $this->row_height.'px' : str_replace('%','p',$this->row_height));
                }//if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0')
                $result.="\t".$tContainerFull.'<table id="'.($this->tag_id ? $this->tag_id : $this->cHash).'_table" class="'.$lClass.'"'.'>'."\n";
                $result.=$th_result;
                $result.=$table_data;
                $result.="\t".'</table>'.(strlen($tContainerFull) ? '</div>' : '')."\n";
                $result.="\t".'</div>'."\n";
                $result.=$this->GetPaginationBox($items);
                $result.=$closing_tags;
                $result.='</div>'."\n";
                break;
            default:
                $result='<div id="'.$this->tag_id.'" class="'.$lClass.'">'."\n";
                if($this->with_filter || $this->export_button || !$this->hide_actions_bar) {
                    $result.="\t".'<div id="'.$this->tag_id.'-filters" class="'.($this->base_class.'Filters'.(strlen($this->class)>0 ? ' '.$this->class : '')).'">'."\n";
                    $result.=$this->GetActionsBox(new Params());
                    $result.="\t".'</div>'."\n";
                }//if($this->with_filter || $this->export_button || !$this->hide_actions_bar)
                $result.="\t".'<div class="clsTContainer">'."\n";
                $t_c_width=NULL;
                $th_result=$this->GetTableHeader($t_c_width);
                $tContainerFull='';
                if(strlen($this->width) && $this->width!=='100%') {
                    $tContainerFull='<div class="clsTContainerFull" style="width: '.$this->width.'px;">';
                } else {
                    if($this->min_width) {
                        $tContainerFull='<div class="clsTContainerFull" style="min-width: '.$this->min_width.'px; width: 100%;">';
                    } elseif($this->scrollable && $t_c_width>0) {
                        $tContainerFull='<div class="clsTContainerFull" style="width: '.$t_c_width.'px;">';
                    }//if($this->min_width)
                }//if(strlen($this->width) && $this->width!=='100%')
                if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0') {
                    $lClass.=' rh-'.(is_numeric($this->row_height) ? $this->row_height.'px' : str_replace('%','p',$this->row_height));
                }//if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0')
                $result.="\t".$tContainerFull.'<table id="'.($this->tag_id ? $this->tag_id : $this->cHash).'_table" class="'.$lClass.'"'.'>'."\n";
                $result.=$th_result;
                $result.=$table_data;
                $result.="\t".'</table>'.(strlen($tContainerFull) ? '</div>' : '')."\n";
                $result.="\t".'</div>'."\n";
                $result.=$this->GetPaginationBox($items);
                $result.='</div>'."\n";
                break;
        }//END switch
        return $result;
    }//END private function SetControl

    /**
     * @param string $name
     * @param array  $config
     * @param bool   $overwrite
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function SetColumn(string $name,array $config,bool $overwrite=FALSE): void {
        if(array_key_exists($name,$this->columns) && !$overwrite) {
            throw new AppException('Column already set!');
        }
        $this->columns[$name]=$config;
    }//END public function SetColumn

    /**
     * @param string $name
     * @return void
     */
    public function RemoveColumn(string $name): void {
        if(array_key_exists($name,$this->columns)) {
            unset($this->columns[$name]);
        }
    }//END public function RemoveColumn

    /**
     * @param string   $name
     * @param array    $action
     * @param bool     $updateVisibleCount
     * @param int|null $index
     * @param bool     $overwrite
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function AddAction(string $name,array $action,bool $updateVisibleCount=TRUE,?int $index=NULL,bool $overwrite=FALSE): void {
        if(!array_key_exists($name,$this->columns) || !is_array($this->columns[$name])) {
            $this->columns[$name]=[];
        }
        if($index!==NULL && array_key_exists($index,$this->columns[$name]) && !$overwrite) {
            throw new AppException('Action already set!');
        }
        $this->columns[$name][$index]=$action;
        if($updateVisibleCount) {
            $this->columns[$name]['visible_count']=get_array_value($this->columns[$name],'visible_count',0,'is_integer') + 1;
        }
    }//END public function AddAction

    /**
     * @param string $name
     * @param int    $index
     * @param bool   $updateVisibleCount
     * @return void
     */
    public function RemoveAction(string $name,int $index,bool $updateVisibleCount=TRUE): void {
        if(!array_key_exists($name,$this->columns) || !is_array($this->columns[$name]) || !count($this->columns[$name]) || array_key_exists($index,$this->columns[$name])) {
            return;
        }
        unset($this->columns[$name][$index]);
        if($updateVisibleCount) {
            $this->columns[$name]['visible_count']=get_array_value($this->columns[$name],'visible_count',1,'is_not0_integer') - 1;
        }
    }//END public function RemoveAction

    /**
     * @param string $name
     * @return void
     */
    public function ClearActions(string $name): void {
        if(!array_key_exists($name,$this->columns) || !is_array($this->columns[$name]) || !count($this->columns[$name])) {
            return;
        }
        $this->columns[$name]=[];
    }//END public function ClearActions

    /**
     * Sets new value for base class property
     *
     * @param \NETopes\Core\App\Params $params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function ExportAll(Params $params) {
        // NApp::Dlog($params,'ExportAll');
        $phash=$params->safeGet('phash',NULL,'is_notempty_string');
        $output=$params->safeGet('output',FALSE,'bool');
        if($phash) {
            $this->phash=$phash;
        }
        $this->export_only=TRUE;
        $items=$this->GetData();
        if(!is_object($items) || !count($items)) {
            throw new AppException(Translate::Get('msg_no_data_to_export'),E_ERROR,1);
        }
        $tmp_export_data=$this->export_data;
        $this->export_data=['columns'=>[],'data'=>[]];
        $tmpParams=new Params();
        $this->IterateData($items,$tmpParams);
        if(!$this->export_data) {
            throw new AppException(Translate::Get('msg_no_data_to_export'),E_ERROR,1);
        }
        $this->export_data['with_borders']=TRUE;
        $this->export_data['freeze_pane']=TRUE;
        $this->export_data['default_width']=150;
        $params=[
            'pre_processed_data'=>TRUE,
            'output'=>TRUE,
            'layouts'=>[$this->export_data],
            'summarize'=>$this->with_totals,
        ];
        $cacheFile=AppHelpers::GetCachePath().'datagrid/'.$this->cHash.'_all.tmpexp';
        // NApp::Dlog($cacheFile,'$cacheFile');
        try {
            if(!file_exists(AppHelpers::GetCachePath().'datagrid')) {
                mkdir(AppHelpers::GetCachePath().'datagrid',755);
            }
            if(file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            file_put_contents($cacheFile,serialize($params));
        } catch(Exception $e) {
            NApp::Elog($e);
            $output=FALSE;
        }//END try
        $this->export_data=$tmp_export_data;
        $this->export_only=FALSE;
        if(!$output) {
            return;
        }
        $url=NApp::$appBaseUrl.'/pipe/download.php?namespace='.NApp::$currentNamespace.'&dtype=datagridexcelexport&exportall=1&chash='.$this->cHash;
        NApp::Ajax()->ExecuteJs("OpenUrl('{$url}',true)");
    }//END public function ExportAll

    /**
     * Get export data
     *
     * @param array $params An array of parameters
     * @return void
     * @throws \NETopes\Core\AppException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function ExportData(array $params=[]) {
        $chash=get_array_value($params,'chash',NULL,'is_notempty_string');
        if(!$chash) {
            return;
        }
        $export_all=get_array_value($params,'exportall',FALSE,'bool');
        //\NETopes\Core\Logging\Logger::StartTimeTrack('TableViewExportData');
        $cacheFile=AppHelpers::GetCachePath().'datagrid/'.$chash.($export_all ? '_all' : '').'.tmpexp';
        try {
            if(!file_exists($cacheFile)) {
                NApp::Elog('File '.$cacheFile.' not found !','TableView::GetExportCacheFile');
                return;
            }//if(!file_exists($cacheFile))
            $exportData=unserialize(file_get_contents($cacheFile));
            // NApp::Log2File(print_r($export_data,TRUE),NApp::$appPath.AppConfig::GetValue('logs_path').'/test.log');
            // NApp::Dlog(\NETopes\Core\Logging\Logger::ShowTimeTrack('TableViewExportData',FALSE),'BP:0');
            if(!is_array($exportData) || !count($exportData)) {
                return;
            }
            $excel=new ExcelExport($exportData);
            // NApp::Dlog(\NETopes\Core\Logging\Logger::ShowTimeTrack('TableViewExportData'),'BP:END');
        } catch(AppException $e) {
            NApp::Elog($e);
            throw $e;
        }//END try
    }//END public static function ExportData
}//END class TableView extends FilterControl