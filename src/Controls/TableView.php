<?php
/**
 * Data grid control file
 *
 * description
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.6.1
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\Module;
use NETopes\Core\App\Params;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\Data\DataSource;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\ExcelExport;
use GibberishAES;
use PAF\AppException;
use NApp;
/**
 * Data grid control
 *
 * description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class TableView {
	/**
	 * @var    string Control instance hash
	 * @access protected
	 */
	protected $chash = NULL;
	/**
	 * @var    string Control base class
	 * @access protected
	 */
	protected $baseclass = '';
	/**
	 * @var    int Current page (for pagination)
	 * @access protected
	 */
	protected $currentpage = NULL;
	/**
	 * @var    bool Export only flag
	 * @access protected
	 */
	protected $export_only = FALSE;
	/**
	 * @var    bool Show or hide export button
	 * @access public
	 */
	protected $export_button = FALSE;
	/**
	 * @var    string Filter condition type value source (for filter PAF action)
	 * @access protected
	 */
	protected $filter_cond_val_source = NULL;
	/**
	 * @var    array Data to be exported
	 * @access protected
	 */
	protected $export_data = NULL;
	/**
	 * @var    array Filters values
	 * @access protected
	 */
	protected $filters = [];
	/**
	 * @var    array Totals values
	 * @access protected
	 */
	protected $totals = [];
	/**
	 * @var    array Embedded row forms (initialized for each row)
	 * @access protected
	 */
	protected $row_embedded_form = NULL;
	/**
	 * @var    bool Page hash (window.name)
	 * @access public
	 */
	public $phash = NULL;
	/**
	 * @var    string Module name
	 * @access public
	 */
	public $module = NULL;
	/**
	 * @var    string Module method name
	 * @access public
	 */
	public $method = NULL;
	/**
	 * @var    string Main container id
	 * @access public
	 */
	public $tagid = NULL;
	/**
	 * @var    string Theme type
	 * @access public
	 */
	public $theme_type = NULL;
	/**
	 * @var    bool Is individual panel or integrated in other view
	 * @access public
	 */
	public $is_panel = TRUE;
	/**
	 * @var    bool|array Defines a tree grid
	 * @access public
	 */
	public $tree = FALSE;
	/**
	 * @var    string Tree level ident string
	 * @access public
	 */
	public $tree_ident = '&nbsp;&nbsp;&nbsp;&nbsp;';
	/**
	 * @var    integer Tree top level
	 * @access protected
	 */
	protected $tree_top_lvl = 1;
	/**
	 * @var    string Control elements class
	 * @access public
	 */
	public $class = NULL;
	/**
	 * @var    mixed TableView width (numeric in px or as string percent)
	 * @access public
	 */
	public $width = NULL;
	/**
	 * @var    int TableView width
	 * @access public
	 */
	public $min_width = NULL;
	/**
	 * @var    int TableView cell padding
	 * @access public
	 */
	public $cell_padding = 10;
	/**
	 * @var    bool Switch alternate row collor on/off
	 * @access public
	 */
	public $alternate_row_collor = FALSE;
	/**
	 * @var    string Color row dynamically from a data field value
	 * @access public
	 */
	public $row_color_field = NULL;
	/**
	 * @var    bool Switch compact mode on/off
	 * @access public
	 */
	public $compact_mode = FALSE;
	/**
	 * @var    string Table rows fixed height
	 * @access public
	 */
	public $row_height = NULL;
	/**
	 * @var    mixed Row tooltip as string or array
	 * @access public
	 */
	public $row_tooltip = NULL;
	/**
	 * @var    bool Switch horizontal scroll on/off
	 * @access public
	 */
	public $scrollable = TRUE;
	/**
	 * @var    array Sort state (column, direction)
	 * @access public
	 */
	public $sortby = [];
	/**
	 * @var    bool Switch quick search on/off
	 * @access public
	 */
	public $qsearch = NULL;
	/**
	 * @var    bool Switch filter box on/off
	 * @access public
	 */
	public $with_filter = TRUE;
	/**
	 * @var    bool Switch actions box on/off (only without filters)
	 * @access public
	 */
	public $hide_actions_bar = FALSE;
	/**
	 * @var    bool Switch export feature on/off
	 * @access public
	 */
	public $exportable = TRUE;
	/**
	 * @var    bool Switch export all feature on/off
	 * @access public
	 */
	public $export_all = TRUE;
	/**
	 * @var    string Export format (Excel2007/Excel5/csv)
	 * @access public
	 */
	public $export_format = 'Excel2007';
	/**
	 * @var    bool Switch datetime export as text on/off
	 * @access public
	 */
	public $export_datetime_as_text = FALSE;
	/**
	 * @var    bool Switch pagination on/off
	 * @access public
	 */
	public $with_pagination = TRUE;
	/**
	 * @var    bool Switch totals on/off
	 * @access public
	 */
	public $with_totals = FALSE;
	/**
	 * @var    bool Switch totals position as first row on/off
	 * @access public
	 */
	public $totals_row_first = FALSE;
	/**
	 * @var    bool Switch status bar on/off
	 * (applyes only with $with_pagination = FALSE)
	 * @access public
	 */
	public $hide_status_bar = FALSE;
	/**
	 * @var    int Table header rows number (default: 1)
	 * @access public
	 */
	public $th_rows_no = 1;
	/**
	 * @var    string TableView target
	 * @access public
	 */
	public $target = '';
	/**
	 * @var    mixed Ajax calls loader: 1=default loader; 0=no loader;
	 * [string]=html element id or javascript function
	 * @access public
	 */
	public $loader = 1;
	/**
	 * @var    string Data call data adapter name
	 * @access public
	 */
	public $data_source = NULL;
	/**
	 * @var    string Data call method
	 * @access public
	 */
	public $ds_method = NULL;
	/**
	 * @var    array Data call params array
	 * @access public
	 */
	public $ds_params = [];
	/**
	 * @var    array Data call extra params array
	 * @access public
	 */
	public $ds_extra_params = [];
	/**
	 * @var    array Data call out params array
	 * @access public
	 */
	public $ds_out_params = NULL;
	/**
	 * @var    array Data array
	 * @access public
	 */
	public $data = NULL;
	/**
	 * @var    bool Switch auto data loading on/off
	 * @access public
	 */
	public $auto_load_data = TRUE;
	/**
	 * @var    array Array for setting custom css class for rows, based on a condition
	 * @access public
	 */
	public $row_conditional_class = NULL;
	/**
	 * @var    string Java script on data load/refresh/filter
	 * @access public
	 */
	public $onload_js_callback = NULL;
	/**
	 * @var    string Java script on data load/refresh/page change/filter/sort callback
	 * @access public
	 */
	public $onchange_js_callback = NULL;
	/**
	 * @var    string Auto-generated javascript callback string (onload_js_callback + onchange_js_callback)
	 * @access public
	 */
	public $js_callbacks = NULL;
	/**
	 * @var    array Columns configuration params
	 * @access public
	 */
	public $columns = [];
	/**
	 * @var    bool Flag to indicate if filters are persistent
	 * @access public
	 */
	public $persistent_state = FALSE;
	/**
	 * @var    array Initial filters values (are destroyed after the control initialization)
	 * @access public
	 */
	public $initial_filters = NULL;
	/**
	 * TableView class constructor method
	 *
	 * @param  array $params Parameters array
	 * @return void
	 * @access public
	 */
	public function __construct($params = NULL) {
		$this->chash = \PAF\AppSession::GetNewUID();
		$this->baseclass = 'cls'.get_class_basename($this);
		$this->currentpage = 1;
		$this->theme_type = is_object(NApp::$theme) ? NApp::$theme->GetThemeType() : 'bootstrap3';
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) {
				if(property_exists($this,$k)) { $this->$k = $v; }
			}//foreach ($params as $k=>$v)
		}//if(is_array($params) && count($params))
		if($this->persistent_state) {
			$this->tagid = $this->tagid ? $this->tagid : get_class_basename($this->module).$this->method;
			if(!strlen($this->tagid)) {
				$this->persistent_state = FALSE;
				$this->tagid = $this->chash;
			}//if(!strlen($this->tagid))
		} else {
			$this->tagid = $this->tagid ? $this->tagid : $this->chash;
		}//if($this->persistent_state)
		if(Module::GetDRights($this->module,$this->method,'export')) { $this->exportable = FALSE; }
		if(!is_array($this->sortby) || !count($this->sortby) || !array_key_exists('column',$this->sortby) || !array_key_exists('direction',$this->sortby)) { $this->sortby = array('column'=>'','direction'=>''); }
	}//END public function __construct
	/**
	 * Gets this instance as a serialized string
	 *
	 * @param  bool $encrypted Switch on/off encrypted result
	 * @return string Return serialized control instance
	 * @access protected
	 */
	protected function GetThis($encrypted = TRUE) {
		if($encrypted){ return GibberishAES::enc(serialize($this),$this->chash); }
		return serialize($this);
	}//END protected function GetThis

	/**
	 * Apply format to a cell value
	 *
	 * @param  mixed  $value Cell value
	 * @param  mixed  $format The format to be applied
	 * @return string Return formated value as string
	 * @access protected
	 */
	protected function FormatValue($value,$format,$def_value = NULL) {
		if(is_string($format) && strlen($format)) {
			$result = \NETopes\Core\App\Validator::FormatValue($value,$format);
		} elseif(is_array($format) && count($format)) {
			$result = \NETopes\Core\App\Validator::FormatValue($value,get_array_param($format,'mode','','is_string'),get_array_param($format,'html_entities',FALSE,'bool'),get_array_param($format,'prefix','','is_string'),get_array_param($format,'sufix','','is_string'),get_array_param($format,'def_value','','is_string'),get_array_param($format,'format','','is_string'),get_array_param($format,'validation','','is_string'));
		} else {
			$result = $value;
		}//if(is_string($format) && strlen($format))
		if((!is_string($result) && !is_numeric($result)) || !strlen($result)) { return $def_value; }
		return $result;
	}//END protected function FormatValue
	/**
	 * Gets the javascript callback string
	 *
	 * @param  bool $onload_callback Include or not on load callback
	 * @param  bool $onchange_callback Include or not on change callback
	 * @return string Returns javascript callback string
	 * @access protected
	 */
	protected function ProcessJsCallbacks($onload_callback = TRUE,$onchange_callback = TRUE) {
		if($onload_callback && $onchange_callback) {
			if(is_null($this->js_callbacks)) {
				$this->js_callbacks = '';
				if(strlen($this->onload_js_callback)) { $this->js_callbacks = $this->onload_js_callback; }
				if(strlen($this->onchange_js_callback) && $this->onchange_js_callback!=$this->js_callbacks) {
					$this->js_callbacks = strlen($this->js_callbacks) ? rtrim($this->js_callbacks,"'\"").ltrim($this->onchange_js_callback,"'\"") : $this->onchange_js_callback;
				}//if(strlen($this->onchange_js_callback) && $this->onchange_js_callback!=$this->js_callbacks)
			}//if(is_null($this->js_callbacks))
			return $this->js_callbacks;
		} elseif($onload_callback && strlen($this->onload_js_callback)) {
			return $this->onload_js_callback;
		} elseif($onchange_callback && strlen($this->onchange_js_callback)) {
			return $this->onchange_js_callback;
		}//if($onload_callback && $onchange_callback)
		return '';
	}//END protected function ProcessJsCallbacks
	/**
	 * Gets the action javascript command string
	 *
	 * @param string $type
	 * @param null   $params
	 * @param bool   $process_call
	 * @return string Returns action javascript command string
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function GetActionCommand($type = '',$params = NULL,$process_call = TRUE) {
		$params = is_object($params) ? $params : new Params($params);
		$exec_callback = TRUE;
		$onload_callback = TRUE;
		switch($type) {
			case 'update_filter':
				$exec_callback = FALSE;
				$call = "ControlAjaxRequest('{$this->chash}','ShowFiltersBox','fop'|{$this->tagid}-f-operator:value~'type'|{$this->tagid}-f-type:value~'f-cond-type'|".$params->safeGet('fctype',"''",'is_string').",'".$this->GetThis()."',1)->{$this->tagid}-filters";
				break;
			case 'remove_filter':
				$call = "ControlAjaxRequest('{$this->chash}','Show','faction'|'remove'~'fkey'|'".$params->safeGet('fkey','','is_numeric')."'~'sessact'|'filters','".$this->GetThis()."',1)->{$this->target}";
				break;
			case 'clear_filters':
				$call = "ControlAjaxRequest('{$this->chash}','Show','faction'|'clear'~'sessact'|'filters','".$this->GetThis()."',1)->{$this->target}";
				break;
			case 'add_filter':
				$fdtype = $params->safeGet('data_type','','is_string');
				//~'fkey'|'".$params->safeGet('fkey','','is_notempty_string')."'
				//~'fcond'|{$this->tagid}-f-cond-type:value
				$isdaparam = $params->safeGet('is_ds_param',0,'is_numeric');
				$call = "ControlAjaxRequest('{$this->chash}','Show',
						'faction'|'add'
						~'sessact'|'filters'
						~'fop'|".((is_array($this->filters) && count($this->filters)) ? $this->tagid."-f-operator:value" : "'and'")."
						~'ftype'|{$this->tagid}-f-type:value
						~'fcond'|{$this->filter_cond_val_source}
						~'fvalue'|".$params->safeGet('fvalue',$this->tagid.'-f-value:value','is_notempty_string')."
						~'fsvalue'|".$params->safeGet('fsvalue',"''",'is_notempty_string')."
						~'fdvalue'|".$params->safeGet('fdvalue',$this->tagid.'-f-value:value','is_notempty_string')."
						~'fsdvalue'|".$params->safeGet('fsdvalue',"''",'is_notempty_string')."
						~'data_type'|'{$fdtype}'
						~'is_ds_param'|'{$isdaparam}'
					,'".$this->GetThis()."',1)->{$this->target}";
				break;
			case 'sort':
				$onload_callback = FALSE;
				$sdir = $params->safeGet('direction','asc','is_notempty_string');
				$sdir = $sdir=='asc' ? 'desc' : 'asc';
				$call = "ControlAjaxRequest('{$this->chash}','Show','sort_by'|'".$params->safeGet('column','','is_string')."'~'sort_dir'|'{$sdir}'~'sessact'|'sort','".$this->GetThis()."',1)->{$this->target}";
				break;
			case 'gotopage':
				$onload_callback = FALSE;
				$call = "ControlAjaxRequest('{$this->chash}','Show','page'|{{page}}~'sessact'|'page','".$this->GetThis()."',1)->{$this->target}";
				break;
			case 'export_all':
				$exec_callback = FALSE;
				$call = "ControlAjaxRequest('{$this->chash}','ExportAll','','".$this->GetThis()."',1)->errors";
				break;
			case 'refresh':
			default:
				$call = "ControlAjaxRequest('{$this->chash}','Show','','".$this->GetThis()."',1)->{$this->target}";
				break;
		}//END switch
		if(!$process_call) { return $call; }
		$js_callback = $this->ProcessJsCallbacks($onload_callback);
		if(!$exec_callback || !strlen($js_callback)) { return NApp::arequest()->Prepare($call,$this->loader); }
		return NApp::arequest()->PrepareWithCallback($call,$js_callback,$this->loader);
	}//END protected function GetActionCommand
	/**
	 * Gets the processed data call params
	 *
	 * @return array Returns data call params array
	 * @access protected
	 */
	protected function ProcessDataCallParams(&$params = NULL,&$extra_params = NULL) {
		$params = array_merge((is_array($this->ds_params) ? $this->ds_params : []),$params);
		$extra_params = array_merge((is_array($this->ds_extra_params) ? $this->ds_extra_params : []),$extra_params);
		if(!isset($extra_params['filters']) || !is_array($extra_params['filters'])) { $extra_params['filters'] = []; }
		// NApp::_Dlog($this->filters,'ProcessDataCallParams>>$this->filters');
		if(is_array($this->initial_filters) && count($this->initial_filters)) {
			foreach($this->initial_filters as $ifk=>$ifa) {
				if($ifk=='qsearch') {
					if(!strlen($this->qsearch) || !array_key_exists($this->qsearch,$params)) { continue; }
					$params[$this->qsearch] = $ifa;
				} else {
					if(array_key_exists($ifk,$params) && !is_array($ifa)) {
						$params[$ifk] = $ifa;
					} else {
						$fkey = get_array_param($this->columns[$ifk],'da_param');
						if(strlen($fkey) && array_key_exists($fkey,$params)) {
							$params[$fkey] = is_array($ifa) ? $ifa['value'] : $ifa;
						} else {
							$extra_params['filters'][] = array(
								'field'=>$ifk,
								'condition_type'=>get_array_param($ifa,'condition_type','==','is_string'),
								'value'=>is_array($ifa) ? $ifa['value'] : $ifa,
								'svalue'=>get_array_param($ifa,'svalue','','is_string'),
								'logical_separator'=>get_array_param($ifa,'operator','and','is_string'),
								'data_type'=>get_array_param($ifa,'data_type','','is_string'),
							);
						}//if(strlen($fkey) && array_key_exists($fkey,$params))
					}//if(array_key_exists($ifk,$params) && !is_array($ifa))
				}//if($ifk=='qsearch')
			}//END foreach
			$this->initial_filters = NULL;
		}//if(is_array($this->initial_filters) && count($this->initial_filters))
		if(is_array($this->filters) && count($this->filters)) {
			foreach($this->filters as $k=>$a) {
				if((string)$a['type']=='0') {
					//NApp::_Dlog($a['type'],'$a[type]');
					if(!strlen($this->qsearch) || !array_key_exists($this->qsearch,$params)) { continue; }
					$params[$this->qsearch] = $a['value'];
				} else {
					$fkey = get_array_param($this->columns[$a['type']],'da_param');
					if(strlen($fkey) && array_key_exists($fkey,$params)) {
						$params[$fkey] = $a['value'];
					} else {
						$extra_params['filters'][] = array(
							//'field'=>$a['type'],
							'field'=>get_array_param($this->columns,$a['type'],$a['type'],'is_notempty_string','db_field'),
							'condition_type'=>$a['condition_type'],
							'value'=>$a['value'],
							'svalue'=>$a['svalue'],
							'logical_separator'=>$a['operator'],
							'data_type'=>$a['data_type'],
						);
					}//if(strlen($fkey) && array_key_exists($fkey,$params))
				}//if($a['type']==0)
			}//END foreach
		}//if(is_array($this->filters) && count($this->filters))
		if($this->with_pagination && !$this->export_only) {
			$extra_params['type'] = 'count-select';
			$firstrow = $lastrow = NULL;
			Module::GlobalGetPagintionParams($firstrow,$lastrow,$this->currentpage);
			$extra_params['firstrow'] = $firstrow;
			$extra_params['lastrow'] = $lastrow;
		}//if($this->with_pagination && !$this->export_only)
		$sortcolumn = get_array_param($this->sortby,'column',NULL,'is_notempty_string');
		$extra_params['sort'] = [];
		if($this->tree) { $extra_params['sort']['"LVL"'] = 'ASC'; }
		if(strlen($sortcolumn)) {
			$extra_params['sort']['"'.strtoupper($sortcolumn).'"'] = strtoupper(get_array_param($this->sortby,'direction','asc','is_notempty_string'));
		}//if(strlen($sortcolumn))
	}//END protected function ProcessDataCallParams
	/**
	 * Gets data to be displayed
	 *
	 * @return DataSet
	 * @access protected
	 * @throws \PAF\AppException
	 */
	protected function GetData() {
		$this->totals = [];
		if(!strlen($this->data_source) || !strlen($this->ds_method)) {
			if(is_object($this->data)) {
				$result = $this->data;
			} else {
				$result = DataSource::ConvertResultsToDataSet($this->data,'\NETopes\Core\Data\VirtualEntity');
			}//if(is_object($this->data))
			$result->total_count = $result->count();
			return $result;
		}//if(!strlen($this->data_source) || !strlen($this->ds_method))
		$daparams = $daeparams = [];
		$this->ProcessDataCallParams($daparams,$daeparams);
		// NApp::_Dlog($daparams,'$daparams');
		// NApp::_Dlog($daeparams,'$daeparams');
		$data = DataProvider::Get($this->data_source,$this->ds_method,$daparams,$daeparams,FALSE,$this->ds_out_params);
		NApp::_SetPageParam($this->tagid.'#ds_out_params',$this->ds_out_params);
		if(!is_object($data)) { return new DataSet(); }
		return $data;
	}//END private function GetData
	/**
	 * Process the active filters (adds/removes filters)
	 *
	 * @param  array $params An array of parametrs for processing
	 * @return array Returns the updated filters array
	 * @access protected
	 */
	protected function ProcessActiveFilters($params) {
		$action = $params->safeGet('faction',NULL,'is_notempty_string');
		// NApp::_Dlog($action,'$action');
		if(!$action || ($action!='add' && $action!='remove' && $action!='clear')) { return $this->filters; }
		if($action=='clear') { return []; }
		if($action=='remove') {
			$key = $params->safeGet('fkey',NULL,'is_numeric');
			if(!is_numeric($key) || !array_key_exists($key,$this->filters)) { return $this->filters; }
			$lfilters = $this->filters;
			unset($lfilters[$key]);
			return $lfilters;
		}//if($action=='remove')
		$lfilters = $this->filters;
		$multif = $params->safeGet('multif',[],'is_array');
		if(count($multif)) {
			foreach($multif as $fparams) {
				$op = get_array_param($fparams,'fop',NULL,'is_notempty_string');
				$type = get_array_param($fparams,'ftype',NULL,'is_notempty_string');
				$cond = get_array_param($fparams,'fcond',NULL,'is_notempty_string');
				$value = get_array_param($fparams,'fvalue',NULL,'isset');
				$svalue = get_array_param($fparams,'fsvalue',NULL,'isset');
				$dvalue = get_array_param($fparams,'fdvalue',NULL,'isset');
				$sdvalue = get_array_param($fparams,'fsdvalue',NULL,'isset');
				$fdtype = get_array_param($fparams,'data_type','','is_string');
				$isdaparam = get_array_param($fparams,'is_ds_param',0,'is_numeric');
				if(!$op || !isset($type) || !$cond || !isset($value)) { continue; }
				$lfilters[] = array('operator'=>$op,'type'=>$type,'condition_type'=>$cond,'value'=>$value,'svalue'=>$svalue,'dvalue'=>$dvalue,'sdvalue'=>$sdvalue,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam);
			}//END foreach
		} else {
			$op = $params->safeGet('fop',NULL,'is_notempty_string');
			$type = $params->safeGet('ftype',NULL,'is_notempty_string');
			$cond = $params->safeGet('fcond',NULL,'is_notempty_string');
			$value = $params->safeGet('fvalue',NULL,'isset');
			$svalue = $params->safeGet('fsvalue',NULL,'isset');
			$dvalue = $params->safeGet('fdvalue',NULL,'isset');
			$sdvalue = $params->safeGet('fsdvalue',NULL,'isset');
			$fdtype = $params->safeGet('data_type','','is_string');
			$isdaparam = $params->safeGet('is_ds_param',0,'is_numeric');
			if(!$op || !isset($type) || !$cond || !isset($value)) { return $this->filters; }
			$lfilters[] = array('operator'=>$op,'type'=>$type,'condition_type'=>$cond,'value'=>$value,'svalue'=>$svalue,'dvalue'=>$dvalue,'sdvalue'=>$sdvalue,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam);
		}//if(count($multif))
		// NApp::_Dlog($lfilters,'$lfilters');
		return $lfilters;
	}//END protected function ProcessActiveFilters
	/**
	 * Gets the actions bar controls html (except controls for filters)
	 *
	 * @param bool $with_filters
	 * @return string Returns the actions bar controls html
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function GetActionsBarControls($with_filters = FALSE) {
		//NApp::_Dlog($params,'GetFilterBox>>$params');
		$result = '';
		if($this->exportable && $this->export_all && $this->with_pagination) {
			if($this->compact_mode) {
				$result .= "\t\t\t".'<button class="dg-export-btn compact clsTitleSToolTip" onclick="'.$this->GetActionCommand('export_all').'" title="'.\Translate::Get('button_export_all').'"><i class="fa fa-download"></i></button>'."\n";
			} else {
				$result .= "\t\t\t".'<button class="dg-export-btn" onclick="'.$this->GetActionCommand('export_all').'" ><i class="fa fa-download"></i>'.\Translate::Get('button_export_all').'</button>'."\n";
			}//if($this->compact_mode)
		}//if($this->exportable && $this->export_all && $this->with_pagination)
		if($this->export_button) {
			if($this->compact_mode) {
				$result .= "\t\t\t".'<a class="dg-export-btn compact clsTitleSToolTip" href="'.NApp::app_web_link().'/pipe/download.php?namespace='.NApp::current_namespace().'&dtype=datagridexcelexport&chash='.$this->chash.'" target="_blank" title="'.\Translate::Get('button_export').'"><i class="fa fa-file-excel-o"></i></a>'."\n";
			} else {
				$result .= "\t\t\t".'<a class="dg-export-btn" href="'.NApp::app_web_link().'/pipe/download.php?namespace='.NApp::current_namespace().'&dtype=datagridexcelexport&chash='.$this->chash.'" target="_blank"><i class="fa fa-file-excel-o"></i>'.\Translate::Get('button_export').'</a>'."\n";
			}//if($this->compact_mode)
		}//if($this->export_button)
		if(strlen($this->data_source) && strlen($this->ds_method)) {
		if($this->compact_mode) {
			$result .= "\t\t\t".'<button class="dg-refresh-btn compact clsTitleSToolTip" onclick="'.$this->GetActionCommand('refresh').'" title="'.\Translate::Get('button_refresh').'"><i class="fa fa-refresh"></i></button>'."\n";
			if($with_filters) {
				$result .= "\t\t\t".'<button class="f-clear-btn compact clsTitleSToolTip" onclick="'.$this->GetActionCommand('clear_filters').'" title="'.\Translate::Get('button_clear_filters').'"><i class="fa fa-times"></i></button>'."\n";
			}//if($with_filters)
		} else {
			$result .= "\t\t\t".'<button class="dg-refresh-btn" onclick="'.$this->GetActionCommand('refresh').'"><i class="fa fa-refresh"></i>'.\Translate::Get('button_refresh').'</button>'."\n";
			if($with_filters) {
				$result .= "\t\t\t".'<button class="f-clear-btn" onclick="'.$this->GetActionCommand('clear_filters').'"><i class="fa fa-times"></i>'.\Translate::Get('button_clear_filters').'</button>'."\n";
			}//if($with_filters)
		}//if($this->compact_mode)
		}//if(strlen($this->data_source) && strlen($this->ds_method))
		return $result;
	}//END protected function GetActionsBarControls
	/**
	 * Gets the filter box html
	 *
	 * @param  string|int Key (type) of the filter to be checked
	 * @return bool Returns TRUE if filter is used and FALSE otherwise
	 * @access protected
	 */
	protected function CheckIfFilterIsActive($key) {
		if(!is_numeric($key) && (!is_string($key) || !strlen($key))) { return FALSE; }
		if(!is_array($this->filters) || !count($this->filters)) { return FALSE; }
		foreach($this->filters as $f) { if(get_array_param($f,'type','','is_string').''==$key.'') { return TRUE; } }
		return FALSE;
	}//protected function CheckIfFilterIsActive
	/**
	 * Gets the filter box html
	 *
	 * @param null $params
	 * @return string Returns the filter box html
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function GetFilterBox($params = NULL): string {
		// NApp::_Dlog($params,'GetFilterBox>>$params');
		// NApp::_Dlog($this->filters,'GetFilterBox>>$this->filters');
		if(!$this->with_filter) {
				$filters = '';
			$filters .= "\t\t".'<div class="f-container">'."\n";
			$filters .= $this->GetActionsBarControls();
			$filters .= "\t\t".'</div>'."\n";
			return $filters;
		}//if(!$this->with_filter)
		$cftype = $params->safeGet('type','','is_string');
		if($this->compact_mode) {
			$filters = '';
		} else {
			$filters = "\t\t".'<span class="f-title">'.\Translate::Get('label_filters').'</span>'."\n";
		}//if($this->compact_mode)
		$filters .= "\t\t".'<div class="f-container">'."\n";
		if(is_array($this->filters) && count($this->filters)) {
			$filters .= "\t\t\t".'<select id="'.$this->tagid.'-f-operator" class="f-operator">'."\n";
			foreach(DataProvider::GetArray('_Custom\Offline','FilterOperators') as $c) {
				$fo_selected = $params->safeGet('fop','','is_string')==$c['value'] ? ' selected="selected"' : '';
				$filters .= "\t\t\t\t".'<option value="'.$c['value'].'"'.$fo_selected.'>'.$c['name'].'</option>'."\n";
			}//END foreach
			$filters .= "\t\t\t".'</select>'."\n";
		} else {
			$filters .= "\t\t\t".'<input id="'.$this->tagid.'-f-operator" type="hidden" value="'.$params->safeGet('fop','','is_string').'">'."\n";
		}//if(is_array($this->filters) && count($this->filters))
		$filters .= "\t\t\t".'<select id="'.$this->tagid.'-f-type" class="f-type" onchange="'.$this->GetActionCommand('update_filter').'">'."\n";
		$is_qsearch = FALSE;
		$is_qsearch_active = $this->CheckIfFilterIsActive(0);
		if($this->qsearch && !$is_qsearch_active) {
			if($cftype.''=='0' || !strlen($cftype)) {
				$lselected = ' selected="selected"';
				$cfctype = 'qsearch';
				$is_qsearch = TRUE;
			} else {
				$lselected = '';
				$cfctype = '';
			}//if($cftype.''=='0' || !strlen($cftype))
			$filters .= "\t\t\t\t".'<option value="0"'.$lselected.'>'.\Translate::Get('label_qsearch').'</option>'."\n";
		}//if($this->qsearch && !$is_qsearch_active)
		$selectedv = NULL;
		$cfctype = '';
		foreach($this->columns as $k=>$v) {
			if(!get_array_param($v,'filterable',FALSE,'bool')) { continue; }
			$isdaparam = intval(strlen(get_array_param($v,'da_param','','is_string'))>0);
			if($isdaparam && $this->CheckIfFilterIsActive($k)) { continue; }
			if($cftype==$k || (!strlen($cftype) && !$is_qsearch && !$selectedv)) {
				$lselected = ' selected="selected"';
				$cfctype = get_array_param($v,'filter_type','','is_string');
				$selectedv = $v;
			} else {
				$lselected = '';
			}//if($cftype==$k || (!strlen($cftype) && !$is_qsearch && !$selectedv))
			$filters .= "\t\t\t\t".'<option value="'.$k.'"'.$lselected.'>'.get_array_param($v,'label',$k,'is_notempty_string').'</option>'."\n";
		}//END foreach
		$filters .= "\t\t\t".'</select>'."\n";
		$fdtype = get_array_param($selectedv,'data_type','','is_string');
		$fc_type = $params->safeGet('f-cond-type','','is_string');
		$this->filter_cond_val_source = $this->tagid.'-f-cond-type:value';
		$fc_cond_type = get_array_param($selectedv,'show_filter_cond_type',get_array_param($selectedv,'show_filter_cond_type',TRUE,'bool'),'is_notempty_string');
		if($fc_cond_type===TRUE && !$is_qsearch) {
			$filter_cts = '';
			$filter_ct_onchange = '';
			$p_fctype = strtolower(strlen($cfctype) ? $cfctype : $fdtype);
			$f_conditions = DataProvider::GetArray('_Custom\Offline','FilterConditionsTypes',array('type'=>$p_fctype));
			foreach($f_conditions as $c) {
				$fct_selected = $fc_type==$c['value'] ? ' selected="selected"' : '';
				$filter_cts .= "\t\t\t\t".'<option value="'.$c['value'].'"'.$fct_selected.'>'.$c['name'].'</option>'."\n";
				if(!strlen($filter_ct_onchange) && $c['value']=='><') {
					$filter_ct_onchange = ' onchange="'.$this->GetActionCommand('update_filter',array('fctype'=>$this->tagid.'-f-cond-type:value')).'"';
				}//if(!strlen($filter_ct_onchange) && $c['value']=='><')
			}//END foreach
			$filters .= "\t\t\t".'<select id="'.$this->tagid.'-f-cond-type" class="f-cond-type"'.$filter_ct_onchange.'>'."\n";
			$filters .= $filter_cts;
			$filters .= "\t\t\t".'</select>'."\n";
		} elseif($fc_cond_type!=='data') {
			$filters .= "\t\t\t".'<input type="hidden" id="'.$this->tagid.'-f-cond-type" value="'.($is_qsearch ? 'like' : '==').'"/>'."\n";
		} else {
			$this->filter_cond_val_source = NULL;
		}//if($fc_cond_type===TRUE && !$is_qsearch)
		$ctrl_params = get_array_param($selectedv,'filter_params',[],'is_array');
		$ctrl_params['tagid'] = $this->tagid.'-f-value';
		$ctrl_params['class'] = 'f-value';
		$ctrl_params['clear_base_class'] = TRUE;
		$ctrl_params['container'] = FALSE;
		$ctrl_params['no_label'] = TRUE;
		$ctrl_params['postable'] = FALSE;
		$aoc_check = NULL;
		$fval = NULL;
		$fsval = NULL;
		$f_subtype = NULL;
		switch(strtolower($cfctype)) {
			case 'smartcombobox':
				$ctrl_filter_value = new SmartComboBox($ctrl_params);
				$dvalue = $this->tagid.'-f-value:option';
				if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
				$ctrl_filter_value->ClearBaseClass();
				$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
				if($this->compact_mode) {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn compact clsTitleSToolTip" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam)).($aoc_check ? ' }' : '').'" title="'.\Translate::Get('button_add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
				} else {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam)).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.\Translate::Get('button_add_filter').'</button>'."\n";
				}//if($this->compact_mode)
				break;
			case 'combobox':
				$ctrl_filter_items = [];
				$cf_dc = get_array_param($selectedv,'filter_data_call',NULL,'is_notempty_array');
				if($cf_dc) {
					$cf_da = get_array_param($cf_dc,'data_source',NULL,'is_notempty_string');
					$cf_dm = get_array_param($cf_dc,'ds_method',NULL,'is_notempty_string');
					$cf_dep = get_array_param($cf_dc,'ds_extra_params',[],'is_array');
					if($cf_da && $cf_dm) { $ctrl_filter_items = DataProvider::GetArray($cf_da,$cf_dm,get_array_param($cf_dc,'ds_params',[],'is_array'),$cf_dep); }
				}//if($cf_dc)
				$ctrl_params['value'] = $ctrl_filter_items;
				$ctrl_filter_value = new ComboBox($ctrl_params);
				$dvalue = $this->tagid.'-f-value:option';
				if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
				$ctrl_filter_value->ClearBaseClass();
				$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
				if($this->compact_mode) {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn compact clsTitleSToolTip" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam)).($aoc_check ? ' }' : '').'" title="'.\Translate::Get('button_add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
				} else {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam)).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.\Translate::Get('button_add_filter').'</button>'."\n";
				}//if($this->compact_mode)
				break;
			case 'treecombobox':
				$ctrl_filter_value = new TreeComboBox($ctrl_params);
				$dvalue = $this->tagid.'-f-value-cbo:value';
				if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
				// $ctrl_filter_value->ClearBaseClass();
				$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
				if($this->compact_mode) {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn compact clsTitleSToolTip" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype)).($aoc_check ? ' }' : '').'" title="'.\Translate::Get('button_add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
				} else {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype)).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.\Translate::Get('button_add_filter').'</button>'."\n";
				}//if($this->compact_mode)
				break;
		  	case 'checkbox':
				$ctrl_params['value'] = 0;
				$ctrl_filter_value = new CheckBox($ctrl_params);
				$dvalue = $this->tagid.'-f-value:value';
				if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
				$ctrl_filter_value->ClearBaseClass();
				$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
				if($this->compact_mode) {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn compact clsTitleSToolTip" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam)).($aoc_check ? ' }' : '').'" title="'.\Translate::Get('button_add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
				} else {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam)).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.\Translate::Get('button_add_filter').'</button>'."\n";
				}//if($this->compact_mode)
				break;
			case 'datepicker':
			case 'date':
			case 'datetime':
				$f_subtype = 'DatePicker';
			case 'numerictextbox':
			case 'numeric':
				$f_subtype = $f_subtype ? $f_subtype : 'NumericTextBox';
			default:
				if(!$f_subtype) {
					switch($fdtype) {
						case 'date':
						case 'date_obj':
						case 'datetime':
						case 'datetime_obj':
							$f_subtype = 'DatePicker';
							break;
						case 'numeric':
							$f_subtype = 'NumericTextBox';
							break;
						default:
							$f_subtype = 'TextBox';
							break;
					}//END switch
				}//if(!$f_subtype)
				switch($f_subtype) {
					case 'DatePicker':
						$ctrl_params['value'] = '';
						$ctrl_params['onenterbutton'] = $this->tagid.'-f-add-btn';
						if(strtolower($cfctype)!='date' && ($fdtype=='datetime' || $fdtype=='datetime_obj')) {
							$ctrl_params['timepicker'] = TRUE;
							// $ctrl_params['extratagparam'] = ' data-out-format="dd'.NApp::_GetParam('date_separator').'MM'.NApp::_GetParam('date_separator').'yyyy HH'.NApp::_GetParam('time_separator').'mm'.NApp::_GetParam('time_separator').'ss"';
						} else {
							$ctrl_params['timepicker'] = FALSE;
							// $ctrl_params['extratagparam'] = ' data-out-format="dd'.NApp::_GetParam('date_separator').'MM'.NApp::_GetParam('date_separator').'yyyy"';
						}//if(strtolower($cfctype)!='date' && ($fdtype=='datetime' || $fdtype=='datetime_obj'))
						$ctrl_params['align'] = 'center';
						$ctrl_filter_value = new DatePicker($ctrl_params);
						$ctrl_filter_value->ClearBaseClass();
						$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
						$fval = $this->tagid.'-f-value:dvalue';
						if($fc_type=='><') {
							$filters .= "\t\t\t".'<span class="f-i-lbl">'.\Translate::Get('label_and').'</span>'."\n";
							$ctrl_params['tagid'] = $this->tagid.'-f-svalue';
							$ctrl_filter_value = new DatePicker($ctrl_params);
							$ctrl_filter_value->ClearBaseClass();
							$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
							$fsval = $this->tagid.'-f-svalue:dvalue';
							$sdvalue = $this->tagid.'-f-svalue:value';
							if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
						}//if($fc_type=='><')
						break;
					case 'NumericTextBox':
						$ctrl_params['value'] = '';
						$ctrl_params['onenterbutton'] = $this->tagid.'-f-add-btn';
						$ctrl_params['numberformat'] = get_array_param($selectedv,'filter_format','0|||','is_notempty_string');
						$ctrl_params['align'] = 'center';
						$ctrl_filter_value = new NumericTextBox($ctrl_params);
						$ctrl_filter_value->ClearBaseClass();
						$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
						$fval = $this->tagid.'-f-value:nvalue';
						if($fc_type=='><') {
							$filters .= "\t\t\t".'<span class="f-i-lbl">'.\Translate::Get('label_and').'</span>'."\n";
							$ctrl_params['tagid'] = $this->tagid.'-f-svalue';
							$ctrl_filter_value = new NumericTextBox($ctrl_params);
							$ctrl_filter_value->ClearBaseClass();
							$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
							$fsval = $this->tagid.'-f-svalue:nvalue';
							$sdvalue = $this->tagid.'-f-svalue:value';
							if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
						}//if($fc_type=='><')
						//$aoc_check = "if(\$('#".$this->tagid."-f-value').val()!=0){ ";
						break;
					case 'TextBox':
					default:
						$ctrl_params['value'] = '';
						$ctrl_params['onenterbutton'] = $this->tagid.'-f-add-btn';
						$ctrl_filter_value = new TextBox($ctrl_params);
						$ctrl_filter_value->ClearBaseClass();
						$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
						$fval = $this->tagid.'-f-value:value';
						if($fc_type=='><') {
							$filters .= "\t\t\t".'<span class="f-i-lbl">'.\Translate::Get('label_and').'</span>'."\n";
							$ctrl_params['tagid'] = $this->tagid.'-f-svalue';
							$ctrl_filter_value = new TextBox($ctrl_params);
							$ctrl_filter_value->ClearBaseClass();
							$filters .= "\t\t\t".$ctrl_filter_value->Show()."\n";
							$fsval = $this->tagid.'-f-svalue:value';
							$sdvalue = $this->tagid.'-f-svalue:value';
							if(!$this->filter_cond_val_source) { $this->filter_cond_val_source = $this->tagid.'-f-value:option:data-ctype'; }
						}//if($fc_type=='><')
						//$aoc_check = "if(\$('#".$this->tagid."-f-value').val()!=''){ ";
						break;
				}//END switch
				$dvalue = $this->tagid.'-f-value:value';
				$f_b_params = $fc_type=='><' ? array('fdvalue'=>$dvalue,'fsdvalue'=>$sdvalue,'fvalue'=>$fval,'fsvalue'=>$fsval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam) : array('fdvalue'=>$dvalue,'fvalue'=>$fval,'data_type'=>$fdtype,'is_ds_param'=>$isdaparam);
				if($this->compact_mode) {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn compact clsTitleSToolTip" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',$f_b_params).($aoc_check ? ' }' : '').'" title="'.\Translate::Get('button_add_filter').'"><i class="fa fa-plus"></i></button>'."\n";
				} else {
					$filters .= "\t\t\t".'<button id="'.$this->tagid.'-f-add-btn" class="f-add-btn" onclick="'.$aoc_check.$this->GetActionCommand('add_filter',$f_b_params).($aoc_check ? ' }' : '').'"><i class="fa fa-plus"></i>'.\Translate::Get('button_add_filter').'</button>'."\n";
				}//if($this->compact_mode)
				break;
		}//END switch
		$filters .= $this->GetActionsBarControls(TRUE);
		if(is_array($this->filters) && count($this->filters)) {
			$filters .= "\t\t\t".'<div class="f-active">'."\n";
			$first = TRUE;
			$fctypes = DataProvider::GetKeyValueArray('_Custom\Offline','FilterConditionsTypes',array('type'=>'all'),array('keyfield'=>'value'));
			foreach($this->filters as $k=>$a) {
				if($first) {
					$filters .= "\t\t\t\t".'<span class="f-active-title">'.\Translate::Get('label_active_filters').':</span>'."\n";
					$af_op = '';
					$first = FALSE;
				} else {
					$af_op = \Translate::Get('label_'.$a['operator']).' ';
				}//if($first)
				if($a['condition_type']=='><') {
					$filters .= "\t\t\t\t".'<div class="f-active-item"><div class="b-remove" onclick="'.$this->GetActionCommand('remove_filter',array('fkey'=>$k)).'"><i class="fa fa-times"></i></div>'.$af_op.'<strong>'.((is_numeric($a['type']) && $a['type']==0) ? \Translate::Get('label_qsearch') : get_array_param($this->columns[$a['type']],'label',$a['type'],'is_notempty_string')).'</strong>&nbsp;'.$fctypes[$a['condition_type']]['name'].'&nbsp;&quot;<strong>'.$a['dvalue'].'</strong>&quot;&nbsp;'.\Translate::Get('label_and').'&nbsp;&quot;<strong>'.$a['sdvalue'].'</strong>&quot;</div>'."\n";
				} else {
					$filters .= "\t\t\t\t".'<div class="f-active-item"><div class="b-remove" onclick="'.$this->GetActionCommand('remove_filter',array('fkey'=>$k)).'"><i class="fa fa-times"></i></div>'.$af_op.'<strong>'.((is_numeric($a['type']) && $a['type']==0) ? \Translate::Get('label_qsearch') : get_array_param($this->columns[$a['type']],'label',$a['type'],'is_notempty_string')).'</strong>&nbsp;'.$fctypes[$a['condition_type']]['name'].'&nbsp;&quot;<strong>'.$a['dvalue'].'</strong>&quot;</div>'."\n";
				}//if($a['condition_type']=='><')
			}//END foreach
			$filters .= "\t\t\t".'</div>'."\n";
		}//if(is_array($this->filters) && count($this->filters))
		$filters .= "\t\t\t".'<div class="clearfix"></div>'."\n";
		$filters .= "\t\t".'</div>'."\n";
		return $filters;
	}//END protected function GetFilterBox
	/**
	 * Gets the pagination box html
	 *
	 * @param  array $items The data array
	 * @return string Returns the pagination box html
	 * @access protected
	 * @throws \PAF\AppException
	 */
	protected function GetPaginationBox($items): ?string {
		$records_no = $items->getTotalCount();
		if(!$this->with_pagination) {
			if($this->hide_status_bar) { return NULL; }
			//$lstyle = strlen($this->width)>0 ? ($this->width!='100%' ? ' style="width: '.$this->width.'; margin: 0 auto;"' : ' style="width: '.$this->width.';"') : '';
			$result = "\t".'<div class="'.$this->baseclass.'Footer'.(strlen($this->class)>0 ? ' '.$this->class : '').'">'."\n";
			$result .= "\t\t".'<div class="pagination-container"><span class="rec-label">'.\Translate::Get('label_records').'</span><span class="rec-no">'.number_format($records_no,0).'</span><div class="clearfix"></div></div>';
			$result .= "\t".'</div>'."\n";
			return $result;
		}//if(!$this->with_pagination)
		$pagination = new SimplePageControl(array('phash'=>$this->phash,'theme_type'=>$this->theme_type,'width'=>'100%','totalrows'=>$records_no,'onclickparams'=>$this->GetActionCommand('gotopage',[],FALSE),'js_callback'=>$this->onchange_js_callback,'currentpage'=>$this->currentpage));
		$result = "\t".'<div class="'.$this->baseclass.'Footer'.(strlen($this->class)>0 ? ' '.$this->class : '').'">'."\n";
		$result .= $pagination->Show();
		$result .= "\t".'</div>'."\n";
		return $result;
	}//END protected function GetPaginationBox
	/**
	 * Gets the table header row(s)
	 *
	 * @return string Returns the header row(s) HTML as string
	 * @access protected
	 */
	protected function GetTableHeader(&$t_c_width) {
		$t_c_width = 0;
		$result = "\t\t".'<thead>'."\n";
		$th_result = [];
		$th_temp = [];
		// NApp::_Dlog($this->th_rows_no,'$this->th_rows_no');
		for($i=0;$i<$this->th_rows_no;$i++) {
			$th_result[$i] = '';
			$th_temp[$i] = array('text'=>NULL,'colspan'=>NULL,'skip'=>0,'width'=>0,'wtype'=>NULL);
		}//for($i=0;$i<$this->th_rows_no;$i++)
		foreach($this->columns as $k=>$v) {
			if(!is_array($v) || !count($v) || strtolower(get_array_param($v,'type','','is_string'))=='filter-only' || get_array_param($v,'hidden',FALSE,'bool')) { continue; }
			$ch_style = '';
			$ch_width = get_array_param($v,'width',NULL,'is_notempty_string');
			$ch_n_width = 0;
			$ch_p_width = 0;
			$ch_w_type = NULL;
			if(strlen($ch_width)) {
				if(strpos($ch_width,'%')!==FALSE) {
					$ch_style .= ($ch_style ? '' : ' style="').'width: '.$ch_width.';';
					$ch_p_width = trim(str_replace('%','',$ch_width));
					if(is_numeric($ch_p_width) && $ch_p_width) {
						$ch_w_type = 'p';
					} else {
						$ch_p_width = 0;
					}//if(!is_numeric($ch_p_width) && $ch_p_width)
				} else {
					$ch_n_width = str_replace('px','',$ch_width);
					if(is_numeric($ch_n_width) && $ch_n_width) {
						$ch_style .= ($ch_style ? '' : ' style="').'width: '.$ch_n_width.'px;';
						$ch_w_type = 'n';
					} else {
						$ch_n_width = 0;
					}//if(is_numeric($ch_n_width) && $ch_n_width)
				}//if(strpos($ch_width,'%')!==FALSE)
			}//if(strlen($ch_width))
			$ch_style .= $ch_style ? '"' : '';
			$ch_sort_act = '';
			$ch_sort_icon = '';
			$ch_sclass = '';
			if(get_array_param($v,'sortable',FALSE,'true')){
				$ch_sclass .= 'sortable';
				if(get_array_param($this->sortby,'column','','is_notempty_string')==$k) {
					$ch_sortdir = strtolower(get_array_param($this->sortby,'direction','asc','is_notempty_string'));
					$ch_iclass = ' active';
					$ch_psortdir = $ch_sortdir;
				} else {
					$ch_sortdir = 'asc';
					$ch_iclass = '';
					$ch_psortdir = 'desc';
				}//if(get_array_param($this->sortby,'column','','is_notempty_string')==$k)
				$ch_sort_icon = '<i class="fa '.($ch_sortdir=='desc' ? 'fa-arrow-down' : 'fa-arrow-up').$ch_iclass.'"></i>';
				$ch_sort_act = ' onclick="'.$this->GetActionCommand('sort',array('column'=>$k,'direction'=>$ch_psortdir)).'"';
			}//if(get_array_param($v,'sortable',FALSE,'true'))
			$ch_class = get_array_param($v,'class','','is_notempty_string');
			$ch_class = strlen(trim($ch_class.' '.$ch_sclass))>0 ? ' class="'.trim($ch_class.' '.$ch_sclass).'"' : '';
			$iterator = get_array_param($v,'iterator',[],'is_array');
			if(count($iterator)) {
				$ch_label_arr = get_array_param($v,'label',NULL,'is_notempty_array');
				foreach($iterator as $it) {
					if(is_array($ch_label_arr)) {
						foreach($ch_label_arr as $lk=>$lv) {
							$ch_colspan = get_array_param($lv,'colspan',0,'is_numeric');
							if($lk==$this->th_rows_no-1 || $ch_colspan<2) { continue; }
							$th_temp[$lk]['wtype'] = $ch_w_type;
							$th_temp[$lk]['width'] = $ch_w_type=='p' ? $ch_p_width : ($ch_w_type=='n' ? $ch_n_width : 0);
							$th_temp[$lk]['skip'] = $ch_colspan-1;
							$th_temp[$lk]['colspan'] = $ch_colspan;
							$th_temp[$lk]['text'] = get_array_param($lv,'text','&nbsp;','is_notempty_string');
						}//END foreach
						$ch_label = get_array_param($ch_label_arr,$this->th_rows_no-1,NULL,'is_notempty_string','text');
					} else {
						$ch_label = get_array_param($v,'label',NULL,'is_notempty_string');
					}//if(is_array($ch_label_arr))
					$ch_label = get_array_param($it,$ch_label,'&nbsp;','is_notempty_string');
					$ch_rowspan_no = 0;
					$ch_lvl = 0;
					for($i=0;$i<$this->th_rows_no-1;$i++) {
						if($th_temp[$i]['skip']>0) {
							$th_temp[$i]['skip']--;
							$ch_lvl++;
							continue;
						}//if($th_temp[$i]['skip']>0)
						if(is_null($th_temp[$i]['text'])) {
							$ch_rowspan_no++;
							continue;
						}//if(is_null($th_temp[$i]['text']))
						$ch_rowspan = $ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no+1).'"' : '';
						$ch_colspan = $th_temp[$i]['colspan'] && $th_temp[$i]['colspan'] > 1 ? ' colspan="'.$th_temp[$i]['colspan'].'"' : '';
						$ch_r_style = '';
						if($th_temp[$i]['wtype']=='p') {
							$ch_r_style = ' style="width: '.$th_temp[$i]['width'].'%;"';
						} elseif($th_temp[$i]['wtype']=='n') {
							$ch_r_style = ' style="width: '.$th_temp[$i]['width'].'px;"';
						}//if($th_result[$i]['wtype']=='p')
						$th_result[$ch_lvl] .= "\t\t\t\t".'<th'.$ch_colspan.$ch_rowspan.$ch_r_style.$ch_class.'><label>'.($th_temp[$i]['text'] ? $th_temp[$i]['text'] : '&nbsp;').'</label></th>'."\n";
						$ch_lvl++;
						$th_temp[$i]['wtype'] = NULL;
						$th_temp[$i]['width'] = 0;
						$th_temp[$i]['skip'] = 0;
						$th_temp[$i]['colspan'] = NULL;
						$th_temp[$i]['text'] = NULL;
					}//END for
					$t_c_width += $ch_n_width>0 ? $ch_n_width + $this->cell_padding : 0;
					$ch_rowspan = $ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no+1).'"' : '';
					$th_result[$ch_lvl] .= "\t\t\t\t".'<th'.$ch_rowspan.$ch_style.$ch_class.$ch_sort_act.'><label>'.$ch_label.'</label>'.$ch_sort_icon.'</th>'."\n";
				}//END foreach
			} else {
				$ch_label_arr = get_array_param($v,'label',NULL,'is_notempty_array');
				if(is_array($ch_label_arr)) {
					foreach($ch_label_arr as $lk=>$lv) {
						$ch_colspan = get_array_param($lv,'colspan',0,'is_numeric');
						if($lk==$this->th_rows_no-1 || $ch_colspan<2) { continue; }
						$th_temp[$lk]['wtype'] = $ch_w_type;
						$th_temp[$lk]['width'] = $ch_w_type=='p' ? $ch_p_width : ($ch_w_type=='n' ? $ch_n_width : 0);
						$th_temp[$lk]['skip'] = $ch_colspan-1;
						$th_temp[$lk]['colspan'] = $ch_colspan;
						$th_temp[$lk]['text'] = get_array_param($lv,'text','&nbsp;','is_notempty_string');
					}//END foreach
					$ch_label = get_array_param($ch_label_arr,$this->th_rows_no-1,'&nbsp;','is_notempty_string','text');
				} else {
					$ch_label = get_array_param($v,'label','&nbsp;','is_notempty_string');
				}//if(is_array($ch_label_arr))
				// NApp::_Dlog($ch_label,'$ch_label');
				// NApp::_Dlog($th_temp,'$th_temp');
				$ch_rowspan_no = 0;
				$ch_lvl = 0;
				for($i=0;$i<$this->th_rows_no-1;$i++) {
					if($th_temp[$i]['skip']>0) {
						$th_temp[$i]['skip']--;
						$ch_lvl++;
						continue;
					}//if($th_temp[$i]['skip']>0)
					if(is_null($th_temp[$i]['text'])) {
						$ch_rowspan_no++;
						continue;
					}//if(is_null($th_temp[$i]['text']))
					$ch_rowspan = $ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no+1).'"' : '';
					$ch_colspan = $th_temp[$i]['colspan'] && $th_temp[$i]['colspan'] > 1 ? ' colspan="'.$th_temp[$i]['colspan'].'"' : '';
					$ch_r_style = '';
					if($th_temp[$i]['wtype']=='p') {
						$ch_r_style = ' style="width: '.$th_temp[$i]['width'].'%;"';
					} elseif($th_temp[$i]['wtype']=='n') {
						$ch_r_style = ' style="width: '.$th_temp[$i]['width'].'px;"';
					}//if($th_result[$i]['wtype']=='p')
					$th_result[$ch_lvl] .= "\t\t\t\t".'<th'.$ch_colspan.$ch_rowspan.$ch_r_style.$ch_class.'><label>'.($th_temp[$i]['text'] ? $th_temp[$i]['text'] : '&nbsp;').'</label></th>'."\n";
					$ch_lvl++;
					$th_temp[$i]['wtype'] = NULL;
					$th_temp[$i]['width'] = 0;
					$th_temp[$i]['skip'] = 0;
					$th_temp[$i]['colspan'] = NULL;
					$th_temp[$i]['text'] = NULL;
				}//END for
				$t_c_width += $ch_n_width>0 ? $ch_n_width + $this->cell_padding : 0;
				$ch_rowspan = $ch_rowspan_no>0 ? ' rowspan="'.($ch_rowspan_no+1).'"' : '';
				// NApp::_Dlog($ch_style,'$ch_style');
				$th_result[$ch_lvl] .= "\t\t\t\t".'<th'.$ch_rowspan.$ch_style.$ch_class.$ch_sort_act.'><label>'.$ch_label.'</label>'.$ch_sort_icon.'</th>'."\n";
			}//if(count($iterator))
		}//END foreach
		for($i=0;$i<$this->th_rows_no;$i++) {
			$result .= "\t\t\t".'<tr>'."\n";
			$result .= $th_result[$i];
			$result .= "\t\t\t".'</tr>'."\n";
		}//END for
		$result .= "\t\t".'</thead>'."\n";
		return $result;
	}//protected function GetTableHeader
	/**
	 * Add the cell value to sub-totals array
	 *
	 * @return void
	 * @access protected
	 */
	protected function SetCellSubTotal($name,$value,$type) {
		if(!is_array($this->totals)) { $this->totals = []; }
		if(!isset($this->totals[$name]['type'])) { $this->totals[$name]['type'] = $type; }
		if(!isset($this->totals[$name]['value']) || !is_numeric($this->totals[$name]['value'])) { $this->totals[$name]['value'] = 0; }
		switch($type) {
			case 'count':
				$this->totals[$name]['value'] += (is_null($value) || $value===0 || $value==='') ? 0 : 1;
				break;
			case 'sum':
				$this->totals[$name]['value'] += is_numeric($value) ? $value : 0;
				break;
			case 'average':
				if(!isset($this->totals[$name]['count']) || !is_numeric($this->totals[$name]['count'])) { $this->totals[$name]['count'] = 0; }
				$this->totals[$name]['value'] += is_numeric($value) ? $value : 0;
				$this->totals[$name]['count'] += is_numeric($value) ? 1 : 0;
				break;
			default:
				break;
		}//END switch
	}//END protected function SetCellSubTotal
	/**
	 * Gets the table cell value (un-formatted)
	 *
	 * @param object $row
	 * @param array $v
	 * @param string $name
	 * @param string $type
	 * @param bool $is_iterator
	 * @return mixed Returns the table cell value
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function GetCellValue(&$row,&$v,$name,$type,$is_iterator = FALSE) {
		$result = NULL;
		switch($type) {
			case 'actions':
				if(!check_array_key('actions',$v,'is_notempty_array')) { break; }
				$result = '';
				foreach($v['actions'] as $act) {
					if(get_array_param($act,'hidden',FALSE,'bool')) { continue; }
					$aparams = get_array_param($act,'params',[],'is_array');
					$aparams = Control::ReplaceDynamicParams($aparams,$row);
					// Check conditions for displaing action
					$conditions = get_array_param($aparams,'conditions',NULL,'is_array');
					if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) { continue; }
					$atype = get_array_param($act,'type','DivButton','is_notempty_string');
					$atype = '\NETopes\Core\Controls\\'.$atype;
					if(!class_exists($atype)) {
						\NApp::_Elog('Control class ['.$atype.'] not found!');
						continue;
					}//if(!class_exists($atype))
					$a_dright = get_array_param($act,'dright','','is_string');
					if(strlen($a_dright)) {
						$dright = Module::GetDRights($this->module,$this->method,$a_dright);
						if($dright) { continue; }
					}//if(strlen($a_dright))
					$acommand = get_array_param($act,'command_string',NULL,'is_notempty_string');
					if($acommand){
						$ac_params = explode('}}',$acommand);
						$acommand = '';
						foreach($ac_params as $ce){
							$ce_arr = explode('{{',$ce);
							if(count($ce_arr)>1){
								$acommand .= $ce_arr[0].$row->GetProperty($ce_arr[1],'','true');
							}else{
								$acommand .= $ce_arr[0];
							}//if(count($ce_arr)>1)
						}//END foreach
						$aparams['onclick'] = NApp::arequest()->Prepare($acommand,$this->loader);
					}//if($acommand)
					$btn_action = new $atype($aparams);
					if(get_array_param($act,'clear_base_class',FALSE,'bool')){ $btn_action->ClearBaseClass(); }
					$result .= $btn_action->Show();
					$embedded_form = get_array_param($act,'embedded_form','','is_string');
					if(strlen($embedded_form)) {
						$this->row_embedded_form[] = Control::ReplaceDynamicParams($embedded_form,$row);
					}//if(strlen($embedded_form))
				}//END foreach
				break;
			case 'conditional_control':
			case 'control':
				$params_prefix = '';
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					if($type=='conditional_control') {
						$params_prefix = 'alt_';
					} else {
						$result = NULL;
						break;
					}//if($type=='conditional_control')
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					$c_data_type = get_array_param($v,'data_type','','is_string');
					if(is_null($v['db_field'])) {
						$c_value = NULL;
					} else {
						$c_value = $row->getProperty($v['db_field']);
						if($c_data_type=='datetime_obj' || (!is_string($c_value) && !is_numeric($c_value))) {
							$c_value = is_object($c_value) ? $c_value : NULL;
						} else {
							$c_value = strlen($c_value) ? $c_value : NULL;
						}//if($c_data_type=='datetime_obj' || (!is_string($c_value) && !is_numeric($c_value)))
					}//if(is_null($v['db_field']))
					if($c_data_type=='numeric') {
						$c_summarize_type = get_array_param($v,'summarize_type','sum','is_notempty_string');
				} else {
						$c_summarize_type = 'count';
					}//if($c_data_type=='numeric')
					$this->SetCellSubTotal($name,$c_value,$c_summarize_type);
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$c_type_s = get_array_param($v,$params_prefix.'control_type',NULL,'is_notempty_string');
				$c_type = '\NETopes\Core\Controls\\'.$c_type_s;
				if(!$c_type_s || !class_exists($c_type)) {
					\NApp::_Elog('Control class ['.$c_type.'] not found!');
					continue;
				}//if(!$c_type_s || !class_exists($c_type))
				$c_params = Control::ReplaceDynamicParams(get_array_param($v,$params_prefix.'control_params',[],'is_array'),$row);
					if($is_iterator) {
					$c_params['value'] = $row->getProperty($c_params['value'],get_array_param($v,'default_value','','is_string'),'is_notempty_string');
					} elseif($c_type!='ConditionalControl' && $c_type!='InlineMultiControl' && !isset($c_params['value']) && isset($v['db_field'])) {
					$c_params['value'] = $row->getProperty($v['db_field'],get_array_param($v,'default_value','','is_string'),'is_notempty_string');
					}//if($is_iterator)
					if(isset($c_params['tagid'])) {
					$key_value = $row->getProperty(get_array_param($v,'db_key','id','is_notempty_string'),NULL,'isset');
						$key_value = strlen($key_value) ? $key_value : \PAF\AppSession::GetNewUID();
						$c_params['tagid'] .= '_'.$key_value;
					}//if(isset($c_params['tagid']))
					$p_pafreq = get_array_param($v,$params_prefix.'control_pafreq',NULL,'is_array');
					if(is_array($p_pafreq) && count($p_pafreq)) {
						foreach($p_pafreq as $pr) {
							if(!isset($c_params[$pr]) || !is_string($c_params[$pr]) || !strlen($c_params[$pr])) { continue; }
							$c_params[$pr] = NApp::arequest()->Prepare($c_params[$pr],$this->loader);
						}//END foreach
					}//if(is_array($p_pafreq) && count($p_pafreq))
					$c_action_params = NULL;
					$pp_params = get_array_param($v,$params_prefix.'passtrough_params',NULL,'is_array');
					if(is_array($pp_params) && count($pp_params)) {
					$c_action_params = [];
						foreach($pp_params as $pk=>$pv) {
						$c_action_params[$pk] = $row->getProperty($pv,NULL,'isset');
						}//END foreach
					}//if(is_array($pp_params) && count($pp_params))
					// Add [action_params] array to each action of the control
					if($c_action_params && isset($c_params['actions']) && is_array($c_params['actions'])) {
						foreach($c_params['actions'] as $ak=>$av) {
							$c_params['actions'][$ak]['action_params'] = $c_action_params;
						}//END foreach
					}//if($c_action_params && isset($c_params['actions']) && is_array($c_params['actions']))
					$control = new $c_type($c_params);
					if(get_array_param($v,$params_prefix.'clear_base_class',FALSE,'bool')){ $control->ClearBaseClass(); }
					$result = $control->Show();
				break;
			case 'value':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				$c_data_type = get_array_param($v,'data_type','','is_string');
				if(is_null($v['db_field'])) {
					$c_value = NULL;
				} else {
					$c_value = $row->getProperty($v['db_field']);
					if($c_data_type=='datetime_obj' || (!is_string($c_value) && !is_numeric($c_value))) {
						$c_value = is_object($c_value) ? $c_value : NULL;
					} else {
						$c_value = strlen($c_value) ? $c_value : NULL;
					}//if($c_data_type=='datetime_obj' || (!is_string($c_value) && !is_numeric($c_value)))
				}//if(is_null($v['db_field']))
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					if($c_data_type=='numeric') {
						$c_summarize_type = get_array_param($v,'summarize_type','sum','is_notempty_string');
					} else {
						$c_summarize_type = 'count';
					}//if($c_data_type=='numeric')
					$this->SetCellSubTotal($name,$c_value,$c_summarize_type);
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$result = $c_value;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format','','is_string'),$row);
					if(strlen($c_format) && $c_data_type=='numeric') {
						if(substr($c_format,0,7)=='percent' && substr($c_format,-4)!='x100') { $c_value = $c_value/100; }
					}//if(strlen($c_format) && $c_data_type=='numeric')
					$this->export_data['data'][$row->__rowid][$name] = $c_value;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'sum':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				$c_data_type = get_array_param($v,'data_type','','is_string');
				if(!in_array($c_data_type,array('numeric','integer','string'))) {
					$result = NULL;
					break;
				}//if(!in_array($c_data_type,array('numeric','integer','string'))
				if($c_data_type=='string') {
					$c_value = '';
					$c_s_sep = get_array_param($v,'sum_separator',' ','is_string');
				} else {
					$c_value = 0;
					$c_s_sep = NULL;
				}//if($c_data_type=='string')
				foreach(get_array_param($v,'db_field',[],'is_array') as $s_db_field) {
					if(!is_string($s_db_field) || !strlen($s_db_field) || !$row->hasProperty($s_db_field,TRUE)) { continue; }
					if($c_data_type=='string') {
						$c_value .= (strlen($c_value) ? $c_s_sep : '');
						$c_value .= $row->getProperty($s_db_field,'','is_string');
					} else {
						$c_value += $row->getProperty($s_db_field,0,'is_numeric');
					}//if($c_data_type=='string')
				}//END foreach
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					if($c_data_type=='numeric') {
						$c_summarize_type = get_array_param($v,'summarize_type','sum','is_notempty_string');
					} else {
						$c_summarize_type = 'count';
					}//if($c_data_type=='numeric')
					$this->SetCellSubTotal($name,$c_value,$c_summarize_type);
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$result = $c_value;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format','','is_string'),$row);
					if(strlen($c_format) && $c_data_type=='numeric') {
						if(substr($c_format,0,7)=='percent' && substr($c_format,-4)!='x100') { $c_value = $c_value/100; }
					}//if(strlen($c_format) && $c_data_type=='numeric')
					$this->export_data['data'][$row->__rowid][$name] = $c_value;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case '__rowno':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				$c_value = $result = isset($row->__rowno) ? $row->__rowno : NULL;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format','','is_string'),$row);
					$this->export_data['data'][$row->__rowid][$name] = $c_value;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'multi-value':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				if(is_array($v['db_field']) && count($v['db_field'])) {
					$m_value = '';
					$c_f_separator = get_array_param($v,'field_separator',' ','is_string');
					foreach($v['db_field'] as $f) {
						$f_value = $row->getProperty($f);
						if(!isset($f_value) || !strlen($f_value)) { continue; }
						$m_value .= (strlen($m_value) ? $c_f_separator : '').$f_value;
					}//END foreach
					if(!strlen(str_replace($c_f_separator,'',$m_value))) {
						$m_value = get_array_param($v,'default_value','','is_string');
					}//if(!strlen(str_replace($c_f_separator,'',$m_value)))
					if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
						$this->SetCellSubTotal($name,$m_value,'count');
					}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
					$c_def_value = get_array_param($v,'default_value','','is_string');
					$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format',NULL,'isset'),$row);
					$m_value = $this->FormatValue($m_value,$c_format,$c_def_value);
				} else {
					$m_value = get_array_param($v,'default_value',NULL,'is_string');
				}//if(is_array($v['db_field']) && count($v['db_field']))
				$result = $m_value;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$this->export_data['data'][$row->__rowid][$name] = $result;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'indexof':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				$ci_def_index = get_array_param($v,'default_index',NULL,'is_string');
				$ci_value = $row->getProperty($v['db_field']);
				if($ci_def_index && is_null($ci_value)) {
					$ci_value = $ci_def_index;
				} else {
					$ci_value = is_null($ci_value) ? 'null' : $ci_value;
				}//if($ci_def_value && is_null($ci_value))
				$ci_values = get_array_param($v,'values_collection',[],'is_array');
				$i_field = get_array_param($v,'index_field','name','is_notempty_field');
				$ci_def_value = get_array_param($v,'default_value',NULL,'is_string');
				$c_value = get_array_param($ci_values,$ci_value,$ci_def_value,'is_string',$i_field);
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					$this->SetCellSubTotal($name,$c_value,'count');
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$result = ($c_value ? $c_value : NULL);
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$this->export_data['data'][$row->__rowid][$name] = $result;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'custom_function':
				$c_function = get_array_param($v,'function_name','','is_string');
				if(strlen($c_function) && method_exists($this,$c_function)) {
					$c_value = $this->$c_function($row,$v);
				} else {
					$c_value = get_array_param($v,'default_value','','is_string');
				}//if(strlen($c_function) && method_exists($this,$c_function))
				$c_data_type = get_array_param($v,'data_type','','is_string');
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					if($c_data_type=='numeric') {
						$c_summarize_type = get_array_param($v,'summarize_type','sum','is_notempty_string');
					} else {
						$c_summarize_type = 'count';
					}//if($c_data_type=='numeric')
					$this->SetCellSubTotal($name,$c_value,$c_summarize_type);
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$result = $c_value;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$c_format = get_array_param($v,'format','','is_string');
					if(strlen($c_format) && $c_data_type=='numeric') {
						if(substr($c_format,0,7)=='percent' && substr($c_format,-4)!='x100') { $c_value = $c_value/100; }
					}//if(strlen($c_format) && $c_data_type=='numeric')
					$this->export_data['data'][$row->__rowid][$name] = $c_value;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'translate':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				$c_value = $row->getProperty($v['db_field']);
				if(!isset($c_value) || !strlen($c_value)) { $c_value = get_array_param($v,'default_value','','is_string'); }
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					$this->SetCellSubTotal($name,$c_value,'count');
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$c_value = \Translate::Get(get_array_param($v,'prefix','','is_string').$c_value.get_array_param($v,'sufix','','is_string'));
				$result = $c_value;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$this->export_data['data'][$row->__rowid][$name] = $result;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'checkbox':
				// Check conditions for displaing action
				$conditions = get_array_param($v,'conditions',NULL,'is_array');
				if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions)) {
					$result = NULL;
					break;
				}//if(is_array($conditions) && !Control::CheckRowConditions($row,$conditions))
				if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name)) {
					$this->SetCellSubTotal($name,$row->getProperty($v['db_field']),'count');
				}//if($this->with_totals && get_array_param($v,'summarize',FALSE,'bool') && strlen($name))
				$cb_classes = get_array_param($v,'checkbox_classes',NULL,'is_array');
				$cb_val = $row->getProperty($v['db_field']);
				if(is_array($cb_classes) && count($cb_classes)) {
					if(get_array_param($v,'checkbox_eval_as_bool',FALSE,'bool')) {
						$bool_evl = is_numeric($cb_val) ? ($cb_val<0 ? -1 : ($cb_val>0 ? 1 : 0)) : ($cb_val ? 1 : 0);
						$result = '<div class="'.get_array_param($cb_classes,$bool_evl,'','is_string').'"></div>';
					} else {
						$result = '<div class="'.get_array_param($cb_classes,$cb_val,'','is_string').'"></div>';
					}//if(get_array_param($v,'checkbox_eval_as_bool',FALSE,'bool'))
				} else {
					if(get_array_param($v,'checkbox_eval_as_bool',FALSE,'bool')) {
						$bool_evl = is_numeric($cb_val) ? ($cb_val<0 ? -1 : ($cb_val>0 ? 1 : 0)) : ($cb_val ? 1 : 0);
						$result = '<div class="'.($bool_evl ? 'clsChecked' : 'clsUnchecked').'"></div>';
					} else {
						$result = '<div class="'.($cb_val==1 ? 'clsChecked' : 'clsUnchecked').'"></div>';
					}//if(get_array_param($v,'checkbox_eval_as_bool',FALSE,'bool'))
				}//if(is_array($cb_classes) && count($cb_classes))
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$this->export_data['data'][$row->__rowid][$name] = $cb_val;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			case 'filter-only':
				$result = NULL;
				if($this->exportable && get_array_param($v,'export',FALSE,'bool')) {
					$c_data_type = get_array_param($v,'data_type','','is_string');
					if(is_null($v['db_field'])) {
						$c_value = NULL;
					} else {
						$c_value = $row->getProperty($v['db_field']);
						if($c_data_type=='datetime_obj' || (!is_string($c_value) && !is_numeric($c_value))) {
							$c_value = is_object($c_value) ? $c_value : NULL;
						} else {
							$c_value = strlen($c_value) ? $c_value : NULL;
						}//if($c_data_type=='datetime_obj' || (!is_string($c_value) && !is_numeric($c_value)))
					}//if(is_null($v['db_field']))
					$this->export_data['data'][$row->__rowid][$name] = $c_value;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
			default:
				$result = NULL;
				if($this->exportable && get_array_param($v,'export',TRUE,'bool')) {
					$this->export_data['data'][$row->__rowid][$name] = $result;
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				break;
		}//END switch
		return $result;
	}//END protected function GetCellValue
	/**
	 * Gets the table row/cell tooltip html string
	 *
	 * @param object $row
	 * @param string $class
	 * @param array $tooltip
	 * @return string Returns the table row tooltip string
	 * @access protected
	 */
	protected function GetToolTip(&$row,&$class,$tooltip) {
		if(!$tooltip) { return NULL; }
		$c_tooltip = '';
		if(is_array($tooltip) && count($tooltip)) {
			$c_ttclass = get_array_param($tooltip,'tooltip_class','clsWebuiPopover','is_notempty_string');
			$c_t_type = get_array_param($tooltip,'type','','is_string');
			switch($c_t_type) {
				case 'image':
					$c_t_db_field = get_array_param($tooltip,'db_field','','is_string');
					$c_t_dbdata = get_array_param($row,$c_t_db_field,'','is_string');
					if(!strlen($c_t_dbdata)) { return ''; }
					$c_t_width = get_array_param($tooltip,'width',100,'is_not0_numeric');
					$c_t_height = get_array_param($tooltip,'height',100,'is_not0_numeric');
					$c_t_path = get_array_param($tooltip,'path','','is_string');
					if(strlen($c_t_path) && strlen($c_t_dbdata) && file_exists(NApp::app_public_path().rtrim($c_t_path,'/').'/'.$c_t_dbdata)) {
						$c_tooltip = NApp::app_web_link().rtrim($c_t_path,'/').'/'.$c_t_dbdata;
					} else {
						$c_tooltip = NApp::app_web_link().'/repository/no-photo-300.png';
					}//if(strlen($c_t_path) && strlen($c_tooltip) && file_exists(NApp::app_public_path.rtrim($c_t_path,'/').'/'.$c_tooltip))
					$c_tt_label = get_array_param($tooltip,'label','','is_string');
					$c_tooltip = "<div class=\"clsDGToolTip\" style=\"width: {$c_t_width}px; height: {$c_t_height}px; background: transparent url({$c_tooltip}) no-repeat center center; background-size: {$c_t_width}px auto;\"></div>";
					$c_tooltip = ' data="'.GibberishAES::enc($c_tooltip,'HTML').'"';
					if(strlen($c_tt_label)) { $c_tooltip .= ' data-title="'.$c_tt_label.'"'; }
					$c_tt_left_offset = get_array_param($tooltip,'left_offset',0,'is_integer');
					if(strlen($c_tt_left_offset)) { $c_tooltip .= ' data-loffset="'.$c_tt_left_offset.'"'; }
					break;
				case 'db_field':
					if($c_t_db_field = get_array_param($tooltip,'db_field',NULL,'is_array')) {
						$c_t_fsep = get_array_param($tooltip,'field_separator',' ','is_string');
						$c_tooltip = '';
						foreach($c_t_db_field as $field) {
							$c_tooltip .= (strlen($c_tooltip) ? $c_t_fsep : '').nl2br($row->getProperty($field,'','is_string'));
						}//END foreach
					} elseif($c_t_db_field = get_array_param($tooltip,'db_field','','is_string')) {
						$c_tooltip = nl2br($row->getProperty($c_t_db_field,'','is_string'));
					} else {
						$c_tooltip = '';
					}//if($c_t_db_field = get_array_param($tooltip,'db_field',NULL,'is_array'))
					if(!strlen($c_tooltip)) { return ''; }
					$c_tt_label = '';
					if(!get_array_param($tooltip,'text_only',FALSE,'bool')) {
						$c_tt_label = get_array_param($tooltip,'label','','is_string');
					}//if(!get_array_param($tooltip,'text_only',FALSE,'bool'))
					$c_tooltip = ' data-placement="auto-top" data="'.GibberishAES::enc($c_tooltip,'HTML').'"';
					if(strlen($c_tt_label)) { $c_tooltip .= ' data-title="'.$c_tt_label.'"'; }
					$c_tt_left_offset = get_array_param($tooltip,'left_offset',0,'is_integer');
					if($c_tt_left_offset>0) { $c_tooltip .= ' data-offset-left="'.$c_tt_left_offset.'"'; }
					break;
				default:
					break;
			}//END switch
		} elseif(is_string($tooltip) && strlen($tooltip)) {
			$c_ttclass = 'clsWebuiPopover';
			$c_tooltip = ' data="'.GibberishAES::enc($tooltip,'HTML').'"';
		}//if(is_array($tooltip) && count($tooltip))
		if(strlen($c_tooltip)) { $class .= ($class ? ' ' : '').$c_ttclass; }
		return $c_tooltip;
	}//END protected function GetToolTip
	/**
	 * Gets the table cell html
	 *
	 * @param object $row
	 * @param      $v
	 * @param      $name
	 * @param null $has_child
	 * @param null $r_lvl
	 * @param null $r_tree_state
	 * @param bool $is_iterator
	 * @return string Returns the table cell html
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function SetCell(&$row,&$v,$name,$has_child = NULL,$r_lvl = NULL,$r_tree_state = NULL,$is_iterator = FALSE) {
		$cell_type = strtolower(get_array_param($v,'type','','is_string'));
		$result = '';
		$c_style = '';
		$c_halign = get_array_param($v,'halign',NULL,'is_notempty_string');
		$c_style .= $c_halign ? ($c_style ? '' : ' style="').'text-align: '.$c_halign.';' : '';
		$c_valign = get_array_param($v,'valign',NULL,'is_notempty_string');
		$c_style .= $c_valign ? ($c_style ? '' : ' style="').'vertical-align: '.$c_valign.';' : '';
		if($cell_type!='actions') {
			$ac_width = get_array_param($v,'width',NULL,'is_notempty_string');
			$ac_width = is_numeric($ac_width) && $ac_width>0 ? $ac_width.'px' : $ac_width;
			$c_style .= $ac_width ? ($c_style ? '' : ' style="').'width: '.$ac_width.';' : '';
			$c_style .= $c_style ? '"' : '';
		}//if($cell_type!='actions')
		$c_class = get_array_param($v,'class','','is_string');
		$c_tooltip = $this->GetToolTip($row,$c_class,get_array_param($v,'tooltip',NULL,'isset'));
		$c_cond_format_class = get_array_param($v,'conditional_class','','is_string','class');
		$c_cond_format_conditions = get_array_param($v,'conditional_class',[],'is_array','conditions');
		if(strlen($c_cond_format_class) && count($c_cond_format_conditions)) {
			if($this->CheckRowConditions($row,$c_cond_format_conditions)) {
				$c_class = trim($c_class.' '.$c_cond_format_class);
			}//if($this->CheckRowConditions($row,$c_cond_format_conditions))
		}//if(strlen($c_cond_format_class) && count($c_cond_format_conditions))
		if($cell_type=='actions') { $c_class = trim('act-col '.$c_class); }
		$c_class = $c_class ? ' class="'.$c_class.'"' : '';
		switch($cell_type) {
			case 'actions':
				$ac_width = get_array_param($v,'width',NULL,'is_notempty_string');
				if(is_null($ac_width) && is_object(NApp::$theme)) {
					$ac_width = NApp::$theme->GetTableViewActionsWidth(get_array_param($v,'count',0,'is_integer'));
				}//if(is_null($ac_width) && is_object(NApp::$theme))
				$ac_width = is_numeric($ac_width) && $ac_width>0 ? $ac_width.'px' : $ac_width;
				$c_style .= $ac_width ? ($c_style ? '' : ' style="').'width: '.$ac_width.';' : '';
				$c_style .= $c_style ? '"' : '';
				if(!check_array_key('actions',$v,'is_notempty_array')) {
					$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>&nbsp;</td>'."\n";
					break;
				}//if(!check_array_key('actions',$v,'is_notempty_array'))
				$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'."\n";
				$result .= $this->GetCellValue($row,$v,$name,$cell_type,$is_iterator);
				$result .= "\t\t\t\t".'</td>'."\n";
				break;
			case 'control':
				$c_value = $this->GetCellValue($row,$v,$name,$cell_type,$is_iterator);
				$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.(is_null($c_value) ? '&nbsp;' : $c_value).'</td>'."\n";
				break;
			case 'value':
				if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
					$this->export_data['columns'][$name] = array_merge($v,array('name'=>$name));
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
				$t_value = '';
				if($this->tree && $v['db_field']==get_array_param($this->tree,'main_field','name','is_notempty_string')) {
					$t_value .= ($r_lvl>1 ? str_pad('',strlen($this->tree_ident)*($r_lvl-1),$this->tree_ident) : '');
					if($has_child) {
						$t_s_val = $r_tree_state ? 1 : 0;
						$t_value .= '<input type="image" value="'.$t_s_val.'" class="clsTreeGridBtn" onclick="TreeGridViewAction(this,'.$row->safeGetId().',\''.($this->tagid ? $this->tagid : $this->chash).'_table\')" src="'.NApp::app_web_link().'/lib/controls/images/transparent12.gif">';
					} else {
						$t_value .= '<span class="clsTreeGridBtnNoChild"></span>';
					}//if($has_child)
				}//if($this->tree && $v['db_field']==get_array_param($this->tree,'main_field','name','is_notempty_string'))
				$c_value = $this->GetCellValue($row,$v,$name,$cell_type,$is_iterator);
				$c_def_value = get_array_param($v,'default_value','','is_string');
				$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format',NULL,'isset'),$row);
				$c_cond_format = get_array_param($v,'conditional_format',NULL,'is_notempty_array');
				if($c_cond_format) {
					$c_cf_field = get_array_param($c_cond_format,'field','','is_string');
					if(strlen($c_cf_field)) {
						$c_cf_values = get_array_param($c_cond_format,'values',[],'is_array');
						$c_format = get_array_param($c_cf_values,$row->getProperty($c_cf_field),$c_format,'isset');
					}//if(strlen($c_cf_field))
				}//if($c_cond_format)
				$c_value = $this->FormatValue($c_value,$c_format,$c_def_value);
				$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.$t_value.$c_value.'</td>'."\n";
				break;
			case 'sum':
				if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
					$this->export_data['columns'][$name] = array_merge($v,array('name'=>$name));
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
				$t_value = '';
				$c_value = $this->GetCellValue($row,$v,$name,$cell_type,$is_iterator);
				$c_def_value = get_array_param($v,'default_value','','is_string');
				$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format',NULL,'isset'),$row);
				$c_cond_format = get_array_param($v,'conditional_format',NULL,'is_notempty_array');
				if($c_cond_format) {
					$c_cf_field = get_array_param($c_cond_format,'field','','is_string');
					if(strlen($c_cf_field)) {
						$c_cf_values = get_array_param($c_cond_format,'values',[],'is_array');
						$c_format = get_array_param($c_cf_values,$row->getProperty($c_cf_field),$c_format,'isset');
					}//if(strlen($c_cf_field))
				}//if($c_cond_format)
				$c_value = $this->FormatValue($c_value,$c_format,$c_def_value);
				$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.$t_value.$c_value.'</td>'."\n";
				break;
			case '__rowno':
				if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
					$this->export_data['columns'][$name] = array_merge($v,array('name'=>$name));
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
				$c_value = $this->GetCellValue($row,$v,$name,$cell_type,$is_iterator);
				$c_def_value = get_array_param($v,'default_value','','is_string');
				$c_format = Control::ReplaceDynamicParams(get_array_param($v,'format',NULL,'isset'),$row);
				$c_cond_format = get_array_param($v,'conditional_format',NULL,'is_notempty_array');
				if($c_cond_format) {
					$c_cf_field = get_array_param($c_cond_format,'field','','is_string');
					if(strlen($c_cf_field)) {
						$c_cf_values = get_array_param($c_cond_format,'values',[],'is_array');
						$c_format = get_array_param($c_cf_values,$row->getProperty($c_cf_field),$c_format,'isset');
					}//if(strlen($c_cf_field))
				}//if($c_cond_format)
				$c_value = $this->FormatValue($c_value,$c_format,$c_def_value);
				$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.$c_value.'</td>'."\n";
				break;
			case 'multi-value':
			case 'indexof':
			case 'translate':
			case 'checkbox':
			default:
				if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns'])) {
					$this->export_data['columns'][$name] = array_merge($v,array('name'=>$name));
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool') && !array_key_exists($name,$this->export_data['columns']))
				$c_value = $this->GetCellValue($row,$v,$name,$cell_type,$is_iterator);
				$result .= "\t\t\t\t".'<td'.$c_class.$c_style.$c_tooltip.'>'.(is_null($c_value) ? '&nbsp;' : $c_value).'</td>'."\n";
				break;
		}//END switch
		return $result;
	}//END protected function SetCell
	/**
	 * Gets the table row html
	 *
	 * @param object $row
	 * @param null $r_cclass
	 * @param bool $has_child
	 * @return string Returns the table row html
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function SetRow($row,$r_cclass = NULL,$has_child = FALSE) {
		$result = '';
		$r_style = '';
		$r_tdata = '';
		$col_no = 0;
		$this->row_embedded_form = [];
		if(!$this->export_only) {
			$r_color = '';
			if(strlen($this->row_color_field)) { $r_color = $row->getProperty($this->row_color_field,'','is_string'); }
			$r_lvl = $row->safeGetLvl(1,'is_integer');
			$r_tree_state = get_array_param($this->tree,'opened',FALSE,'bool');
			if($this->tree && $r_lvl>$this->tree_top_lvl) {
				if(strlen($r_color)) { $r_style .= ' background-color: '.$r_color.';'; }
				if(!$r_tree_state) { $r_style .= ' display: none;'; }
				$r_style = strlen($r_style) ? ' style="'.$r_style.'"' : '';
				$r_cclass .= (strlen($r_cclass) ? ' ' : '').'clsTreeGridChildOf'.$row->safeGetIdParent(NULL,'is_integer');
				$r_tdata = $row->safeGetHasChild(0,'is_integer') ? ' data-id="'.$row->safeGetId(NULL,'is_integer').'"' : '';
			} elseif(strlen($r_color)) {
				$r_style = ' style="background-color: '.$r_color.';"';
			}//if($this->tree && $r_lvl>$this->tree_top_lvl)
			$r_cc = FALSE;
			$r_cc_class = get_array_param($this->row_conditional_class,'class','','is_string');
			$r_cc_cond = get_array_param($this->row_conditional_class,'conditions',NULL,'is_array');
			if(strlen($r_cc_class) && Control::CheckRowConditions($row,$r_cc_cond)) {
				$r_cclass = ($r_cclass ? ' ' : '').$r_cc_class;
				$r_cc = TRUE;
			}//if(strlen($r_cc_class) && Control::CheckRowConditions($row,$r_cc_cond))
			$r_class = $r_cclass ? $r_cclass : ($this->alternate_row_collor && !$r_cc ? 'stdc' : '');
			$r_tooltip = $this->GetToolTip($row,$r_class,$this->row_tooltip);
			$r_class = strlen($r_class) ? ' class="'.$r_class.'"' : '';
			$result .= "\t\t\t".'<tr'.$r_class.$r_style.$r_tooltip.$r_tdata.'>'."\n";
		}//if(!$this->export_only)
		foreach($this->columns as $k=>$v) {
			$c_type = strtolower(get_array_param($v,'type','','is_string'));
			if(!is_array($v) || !count($v)) { continue; }
			if($c_type=='filter-only' || get_array_param($v,'hidden',FALSE,'bool')) {
				if($this->exportable && get_array_param($v,'export',($c_type!='filter-only'),'bool')) {
					if(!array_key_exists($k,$this->export_data['columns'])) { $this->export_data['columns'][$k] = array_merge($v,array('name'=>$k)); }
					$this->GetCellValue($row,$v,$k,$c_type);
				}//if($this->exportable && get_array_param($v,'export',TRUE,'bool'))
				continue;
			}//if($c_type=='filter-only' || get_array_param($v,'hidden',FALSE,'bool'))
			if($c_type=='actions' && $this->export_only) { continue; }
			$iterator = get_array_param($v,'iterator',[],'is_array');
			if(count($iterator)) {
				$ik = get_array_param($v,'iterator_key','id','is_notempty_string');
				$values = [];
				try {
					$ids_arr = explode(',',$row->getProperty(get_array_param($v,'db_ids_field','','is_string')));
					$keys_arr = explode(',',$row->getProperty(get_array_param($v,'db_keys_field','','is_string')));
					$values_arr = explode(':#:',$row->getProperty(get_array_param($v,'db_field','','is_string')));
					foreach($keys_arr as $kl=>$vl) {
						$values[$vl] = array('id'=>$ids_arr[$kl],'key'=>$vl,'value'=>$values_arr[$kl]);
					}//END foreach
				} catch(AppException $e) {
					NApp::_Elog($e->getMessage(),'TableView::SetRow');
					throw $e;
				}//END try
				foreach($iterator as $it) {
					$i_row_def = array('id'=>NULL,'key'=>$it[$ik],'value'=>NULL);
					$i_row = clone $row;
					$i_row->merge(get_array_param($values,$it[$ik],$i_row_def,'is_array'));
					$i_v = $v;
					$i_v['db_field'] = 'value';
					$i_v['db_key'] = 'id';
					if($this->export_only) {
						if(get_array_param($v,'export',TRUE,'bool')) {
							if(!array_key_exists($k,$this->export_data['columns'])) { $this->export_data['columns'][$k] = array_merge($v,array('name'=>$k)); }
							$this->GetCellValue($i_row,$i_v,$k.'-'.$it[$ik],$c_type);
						}//if(get_array_param($v,'export',TRUE,'bool'))
					} else {
						$result .= $this->SetCell($i_row,$i_v,$k.'-'.$it[$ik],$has_child,$r_lvl,$r_tree_state,TRUE);
						$col_no++;
					}//if($this->export_only)
				}//END foreach
			} else {
				if($this->export_only) {
					if(get_array_param($v,'export',TRUE,'bool')) {
						if(!array_key_exists($k,$this->export_data['columns'])) { $this->export_data['columns'][$k] = array_merge($v,array('name'=>$k)); }
						$this->GetCellValue($row,$v,$k,$c_type);
					}//if(get_array_param($v,'export',TRUE,'bool'))
				} else {
					$result .= $this->SetCell($row,$v,$k,$has_child,$r_lvl,$r_tree_state);
					$col_no++;
				}//if($this->export_only)
			}//if(count($iterator))
		}//END foreach
		if(!$this->export_only) {
			$result .= "\t\t\t".'</tr>'."\n";
			if(is_array($this->row_embedded_form) && count($this->row_embedded_form)) {
				foreach($this->row_embedded_form as $ef) {
					if(!is_string($ef) || !strlen($ef)) { continue; }
					$re_colspan = $col_no>1 ? ' colspan="'.$col_no.'"' : '';
					$result .= "\t\t\t".'<tr id="'.$ef.'-row" class="clsFormRow" style="display: none;"><td id="'.$ef.'"'.$re_colspan.'></td></tr>'."\n";
				}//END foreach
			}//if(is_array($this->row_embedded_form) && count($this->row_embedded_form))
		}//if(!$this->export_only)
		return $result;
	}//END protected function SetRow
	/**
	 * Gets the total row html
	 *
	 * @return string Returns the total row html
	 * @access protected
	 */
	protected function SetTotalRow() {
		if(!is_array($this->totals) || !count($this->totals)) { return NULL; }
		$result = "\t\t\t".'<tr class="tr-totals">'."\n";
		foreach($this->columns as $k=>$v) {
			if(!is_array($v) || !count($v) || strtolower(get_array_param($v,'type','','is_string'))=='filter-only'){ continue; }
			$c_style = '';
			$c_halign = get_array_param($v,'total_halign',get_array_param($v,'halign','','is_string'),'is_string');
			if(strlen($c_halign)) { $c_style .= 'text-align: '.$c_halign.'; '; }
			$c_valign = get_array_param($v,'total_valign',get_array_param($v,'valign','','is_string'),'is_string');
			if(strlen($c_valign)) { $c_style .= 'vertical-align: '.$c_valign.'; '; }
			if(strlen($c_style)) { $c_style =  ' style="'.trim($c_style).'"'; }
			$c_class = ' class="'.trim('td-totals '.get_array_param($v,'class','','is_string')).'"';
			$td_tagid = get_array_param($v,'total_cell_id','','is_string');
			if(strlen($td_tagid)) { $td_tagid = ' id="'.$td_tagid.'"'; }
			if(array_key_exists($k,$this->totals)) {
				$c_sumtype = $this->totals[$k]['type'];
				if($c_sumtype=='average') {
					$c_value = $this->totals[$k]['value']/($this->totals[$k]['count']>0 ? $this->totals[$k]['value'] : 1);
				} else {
					$c_value = $this->totals[$k]['value'];
				}//if($c_sumtype=='average')
				if($c_sumtype=='count') {
					$c_format = 'decimal0';
					$c_value = \NETopes\Core\App\Validator::FormatValue($c_value,$c_format);
				} elseif(get_array_param($v,'data_type','','is_string')!='numeric') {
					$c_format = 'decimal2';
					$c_value = \NETopes\Core\App\Validator::FormatValue($c_value,$c_format);
				} else {
					$c_format = get_array_param($v,'format',NULL,'is_notempty_string');
					if($c_format) {
						$c_value = \NETopes\Core\App\Validator::FormatValue($c_value,$c_format);
					} else {
						$c_format = get_array_param($v,'format',NULL,'is_notempty_array');
						if($c_format) {
							$c_value = \NETopes\Core\App\Validator::FormatValue($c_value,get_array_param($c_format,'mode','','is_string'),get_array_param($c_format,'html_entities',FALSE,'bool'),get_array_param($c_format,'prefix','','is_string'),get_array_param($c_format,'sufix','','is_string'),get_array_param($c_format,'def_value','','is_string'),get_array_param($c_format,'format','','is_string'),get_array_param($c_format,'validation','','is_string'));
						}//if($c_format)
					}//if($c_format)
				}//if($c_sumtype=='count')
				$result .= "\t\t\t\t".'<td'.$td_tagid.$c_class.$c_style.'>'.$c_value.'</td>'."\n";
			} else {
				$c_value = get_array_param($v,'summarize_label','&nbsp;','is_notempty_string');
				$result .= "\t\t\t\t".'<td'.$td_tagid.$c_class.$c_style.'>'.$c_value.'</td>'."\n";
			}//if(array_key_exists($k,$this->totals))
		}//END foreach
		$result .= "\t\t\t".'</tr>'."\n";
		return $result;
	}//END protected function SetTotalRow
	/**
	 * Gets the table html iterating data array
	 *
	 * @param DataSet $data
	 * @param array $params
	 * @param null $r_cclass
	 * @param null $lvl
	 * @param null $id_parent
	 * @return string Returns the table html
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function IterateData($data,&$params,$r_cclass = NULL,$lvl = NULL,$id_parent = NULL) {
		// NApp::_Dlog(array('params'=>$params,'lvl'=>$lvl,'id_parent'=>$id_parent,'r_cclass'=>$r_cclass),'IterateData');
		if(!is_object($data) || !count($data)) { return NULL; }
		$result = '';
		if($this->tree) {
			if(is_null($lvl)) { $this->tree_top_lvl = $lvl = $data->first()->safeGetLvl(1,'is_integer'); }
			$ldata = new DataSet();
			$odata = new DataSet();
			$has_parent = is_numeric($id_parent) && $id_parent>0;
			foreach($data as $rowid=>$row) {
				$row->__rowid = $rowid;
				$row->__rowno = $rowid;
				if($row->safeGetLvl(1,'is_integer')==$lvl && (!$has_parent || $row->safeGetIdParent(0,'is_integer')==$id_parent)) {
					$ldata->add($row);
				} else {
					$odata->add($row);
				}//if($row->safeGetLvl(1,'is_integer')==$lvl && (!$has_parent || $row->safeGetIdParent(0,'is_integer')==$id_parent))
			}//END foreach
			foreach($ldata as $row) {
				if($this->export_only) {
					if(count($odata)) { $this->IterateData($odata,$params,$r_cclass,$lvl+1,$row->safeGetId(NULL,'is_integer')); }
					$this->SetRow($row,$r_cclass,FALSE);
				} else {
					$children = '';
					$r_cclass = $this->alternate_row_collor ? ($r_cclass ? '' : 'altc') : '';
					if(count($odata)) {
						$children = $this->IterateData($odata,$params,$r_cclass,$lvl+1,$row->safeGetId(NULL,'is_integer'));
					}//if(count($odata))
					$result .= $this->SetRow($row,$r_cclass,strlen($children) ? TRUE : FALSE);
					$result .= $children;
				}//if($this->export_only)
			}//END foreach
		} else {
			if($this->export_only) {
				foreach($data as $rowid=>$row) {
					$row->__rowid = $rowid;
					$row->__rowno = $rowid;
					$this->SetRow($row,$r_cclass);
				}//END foreach
			} else {
				$firstrow = $lastrow = NULL;
				Module::GlobalGetPagintionParams($firstrow,$lastrow,$this->currentpage);
				$rowid = 0;
				foreach($data as $row) {
					$row->__rowid = $rowid;
					$row->__rowno = abs($firstrow) + $rowid;
					$r_cclass = $this->alternate_row_collor ? ($r_cclass ? '' : 'altc') : '';
					$result .= $this->SetRow($row,$r_cclass);
					$rowid++;
				}//END foreach
			}//if($this->export_only)
		}//if($this->tree)
		if(is_null($lvl) || $lvl==1) {
			if($this->export_only) {
				if($this->with_totals) { $this->SetTotalRow(); }
			} else {
				if($this->with_totals) {
					$totals_row = $this->SetTotalRow();
					if($this->totals_row_first) {
						$result = "\t\t".'<tbody>'."\n".$totals_row.$result;
						$result .= "\t\t".'</tbody>'."\n";
					} else {
						$result = "\t\t".'<tbody>'."\n".$result;
						$result .= "\t\t".'</tbody>'."\n";
						$result .= "\t\t".'<tfoot>'."\n";
						$result .= $totals_row;
						$result .= "\t\t".'</tfoot>'."\n";
					}//if($this->totals_row_first)
				}//if($this->with_totals)
			}//if($this->export_only)
		}//if(is_null($lvl) || $lvl==1)
		return $result;
	}//END protected function IterateData
	/**
	 * Gets the persistent state from session, if it is the case
	 *
	 * @param null $params
	 * @return void
	 * @access protected
	 */
	protected function LoadState($params = NULL) {
		// NApp::_Dlog($params,'LoadState>>$params');
		if($this->persistent_state) {
			$sessact = $params->safeGet('sessact','','is_string');
			switch($sessact) {
				case 'page':
					$this->currentpage = $params->safeGet('page',$this->currentpage,'is_numeric');
					$ssortby = NApp::_GetPageParam($this->tagid.'#sortby');
					$this->sortby = get_array_param($ssortby,'column',NULL,'is_notempty_string') ? $ssortby : $this->sortby ;
					$this->filters = NApp::_GetPageParam($this->tagid.'#filters');
					break;
				case 'sort':
					$this->currentpage = $params->safeGet('page',1,'is_numeric');
					$this->sortby = array(
						'column'=>$params->safeGet('sort_by',$this->sortby['column'],'is_notempty_string'),
						'direction'=>$params->safeGet('sort_dir',$this->sortby['direction'],'is_notempty_string'),
					);
					$this->filters = NApp::_GetPageParam($this->tagid.'#filters');
					break;
				case 'filters':
					$this->currentpage = $params->safeGet('page',1,'is_numeric');
					$this->sortby = get_array_param($ssortby,'column',NULL,'is_notempty_string') ? $ssortby : $this->sortby ;
					$this->filters = $this->ProcessActiveFilters($params);
					break;
				case 'reset':
					$this->currentpage = $params->safeGet('page',1,'is_numeric');
					$this->sortby = array(
						'column'=>$params->safeGet('sort_by',$this->sortby['column'],'is_notempty_string'),
						'direction'=>$params->safeGet('sort_dir',$this->sortby['direction'],'is_notempty_string'),
					);
					$this->filters = $this->ProcessActiveFilters($params);
					break;
				default:
					$this->currentpage = NApp::_GetPageParam($this->tagid.'#currentpage');
					if(!$this->currentpage) {
						$this->currentpage = $params->safeGet('page',$this->currentpage,'is_numeric');
					}//if(!$this->currentpage)
					$ssortby = NApp::_GetPageParam($this->tagid.'#sortby');
					if(!get_array_param($ssortby,'column',NULL,'is_notempty_string')) {
						$this->sortby = array(
							'column'=>$params->safeGet('sort_by',$this->sortby['column'],'is_notempty_string'),
							'direction'=>$params->safeGet('sort_dir',$this->sortby['direction'],'is_notempty_string'),
						);
					} else {
						$this->sortby = $ssortby;
					}//if(!get_array_param($ssortby,'column',NULL,'is_notempty_string'))
					$this->filters = NApp::_GetPageParam($this->tagid.'#filters');
					if(!$this->filters) {
						$this->filters = $this->ProcessActiveFilters($params);
					}//if(!$this->filters)
					break;
			}//END switch
			NApp::_SetPageParam($this->tagid.'#currentpage',$this->currentpage);
			NApp::_SetPageParam($this->tagid.'#sortby',$this->sortby);
			NApp::_SetPageParam($this->tagid.'#filters',$this->filters);
		} else {
			$this->currentpage = $params->safeGet('page',$this->currentpage,'is_numeric');
			$this->sortby = array(
				'column'=>$params->safeGet('sort_by',$this->sortby['column'],'is_notempty_string'),
				'direction'=>$params->safeGet('sort_dir',$this->sortby['direction'],'is_notempty_string'),
			);
			$this->filters = $this->ProcessActiveFilters($params);
		}//if($this->persistent_state)
	}//END protected function LoadState
	/**
	 * Sets the output buffer value
	 *
	 * @param null $params
	 * @return void
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function SetControl($params = NULL) {
		// NApp::_Dlog($params,'SetControl>>$params');
		$this->LoadState($params);
		// NApp::_Dlog($this->filters,'SetControl>>$this->filters');
		$items = $this->auto_load_data ? $this->GetData() : new DataSet();
		// NApp::_Dlog($items,'SetControl>>$items');
		$this->auto_load_data = TRUE;
		$table_data = NULL;
		if(is_object($items)) {
			if($this->exportable) { $this->export_data = array('columns'=>[],'data'=>[]); }
			$table_data = $this->IterateData($items,$params,'altc');
			// NApp::_Dlog($this->export_data,'export_data');
			if($this->exportable && $this->export_data) {
				$this->export_button = TRUE;
				$this->export_data['with_borders'] = TRUE;
				$this->export_data['freeze_pane'] = TRUE;
				$this->export_data['default_width'] = 150;
				$params = array(
					'pre_processed_data'=>TRUE,
					'output'=>TRUE,
					'layouts'=>array($this->export_data),
					'summarize'=>$this->with_totals,
				);
				$cachefile = NApp::_GetCachePath().'datagrid/'.$this->chash.'.tmpexp';
				//NApp::_Dlog($cachefile,'$cachefile');
				try {
					if(!file_exists(NApp::_GetCachePath().'datagrid')) {
						mkdir(NApp::_GetCachePath().'datagrid',755);
					}//if(!file_exists(NApp::_GetCachePath().'datagrid'))
					if(file_exists($cachefile)) { unlink($cachefile); }
					file_put_contents($cachefile,serialize($params));
				} catch(\Exception $e) {
					NApp::_Elog($e->getMessage(),'TableView::CreateExportCacheFile');
					$this->export_button = FALSE;
					$this->export_data = NULL;
				}//END try
			}//if($this->exportable && $this->export_data)
		}//if(isset($items['data']))
		$lclass = $this->baseclass.(strlen($this->class) ? ' '.$this->class : '').(($this->scrollable || ($this->min_width && !$this->width)) ? ' clsScrollable' : ' clsFixedWidth');
		switch($this->theme_type) {
			case 'bootstrap3':
				$result = '<div class="row">'."\n";
				if($this->is_panel===TRUE) {
					$result .= "\t".'<div class="col-md-12">'."\n";
					$result .= "\t\t".'<div class="panel panel-flat '.$lclass.'" id="'.$this->tagid.'">'."\n";
					$closing_tags = "\t\t".'</div>'."\n";
					$closing_tags .= "\t".'</div>'."\n";
				} else {
					$result .= "\t".'<div class="col-md-12 '.$lclass.'" id="'.$this->tagid.'">'."\n";
					$closing_tags = "\t".'</div>'."\n";
				}//if($this->is_panel===TRUE)
				if($this->with_filter || $this->export_button || !$this->hide_actions_bar) {
					$result .= "\t\t\t".'<div id="'.$this->tagid.'-filters" class="'.($this->baseclass.'Filters'.(strlen($this->class)>0 ? ' '.$this->class : '')).'">'."\n";
					$result .= $this->GetFilterBox(new Params());
					$result .= "\t\t\t".'</div>'."\n";
				}//if($this->with_filter || $this->export_button || !$this->hide_actions_bar)
				$result .= "\t".'<div class="clsTContainer">'."\n";
				$t_c_width = NULL;
				$th_result = $this->GetTableHeader($t_c_width);
				$tcontainerfull = '';
				if(strlen($this->width) && $this->width!=='100%') {
					$tcontainerfull = '<div class="clsTContainerFull" style="width: '.$this->width.'px;">';
				} else {
					if($this->min_width) {
						$tcontainerfull = '<div class="clsTContainerFull" style="min-width: '.$this->min_width.'px; width: 100%;">';
					} elseif($this->scrollable && $t_c_width>0) {
						$tcontainerfull = '<div class="clsTContainerFull" style="width: '.$t_c_width.'px;">';
					}//if($this->min_width)
				}//if(strlen($this->width) && $this->width!=='100%')
				if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0') {
					$lclass .= ' rh-'.(is_numeric($this->row_height) ? $this->row_height.'px' : str_replace('%','p',$this->row_height));
				}//if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0')
				$result .= "\t".$tcontainerfull.'<table id="'.($this->tagid ? $this->tagid : $this->chash).'_table" class="'.$lclass.'"'.'>'."\n";
				$result .= $th_result;
				$result .= $table_data;
				$result .= "\t".'</table>'.(strlen($tcontainerfull) ? '</div>' : '')."\n";
				$result .= "\t".'</div>'."\n";
				$result .= $this->GetPaginationBox($items);
				$result .= $closing_tags;
				$result .= '</div>'."\n";
				break;
			default:
				$result = '<div id="'.$this->tagid.'" class="'.$lclass.'">'."\n";
				if($this->with_filter || $this->export_button || !$this->hide_actions_bar) {
					$result .= "\t".'<div id="'.$this->tagid.'-filters" class="'.($this->baseclass.'Filters'.(strlen($this->class)>0 ? ' '.$this->class : '')).'">'."\n";
					$result .= $this->GetFilterBox(new Params());
					$result .= "\t".'</div>'."\n";
				}//if($this->with_filter || $this->export_button || !$this->hide_actions_bar)
				$result .= "\t".'<div class="clsTContainer">'."\n";
				$t_c_width = NULL;
				$th_result = $this->GetTableHeader($t_c_width);
				$tcontainerfull = '';
				if(strlen($this->width) && $this->width!=='100%') {
					$tcontainerfull = '<div class="clsTContainerFull" style="width: '.$this->width.'px;">';
				} else {
					if($this->min_width) {
						$tcontainerfull = '<div class="clsTContainerFull" style="min-width: '.$this->min_width.'px; width: 100%;">';
					} elseif($this->scrollable && $t_c_width>0) {
						$tcontainerfull = '<div class="clsTContainerFull" style="width: '.$t_c_width.'px;">';
					}//if($this->min_width)
				}//if(strlen($this->width) && $this->width!=='100%')
				if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0') {
					$lclass .= ' rh-'.(is_numeric($this->row_height) ? $this->row_height.'px' : str_replace('%','p',$this->row_height));
				}//if(strlen($this->row_height) && str_replace('px','',$this->row_height)!='0')
				$result .= "\t".$tcontainerfull.'<table id="'.($this->tagid ? $this->tagid : $this->chash).'_table" class="'.$lclass.'"'.'>'."\n";
				$result .= $th_result;
				$result .= $table_data;
				$result .= "\t".'</table>'.(strlen($tcontainerfull) ? '</div>' : '')."\n";
				$result .= "\t".'</div>'."\n";
				$result .= $this->GetPaginationBox($items);
				$result .= '</div>'."\n";
				break;
		}//END switch
		return $result;
	}//END private function SetControl
	/**
	 * Gets the control's content (html)
	 *
	 * @param  array $params An array of parameters
	 * * phash (string) = new page hash (window.name)
	 * * output (bool|numeric) = flag indicating direct (echo)
	 * or indirect (return) output (default FALSE - indirect (return) output)
	 * * other passthru params
	 * @return string Returns the control's content (html)
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function Show($params = NULL) {
		// NApp::_Dlog($params,'TableView>>Show');
		$o_params = is_object($params) ? $params : new Params($params);
		$phash = $o_params->safeGet('phash',NULL,'is_notempty_string');
		$output = $o_params->safeGet('output',FALSE,'bool');
		if($phash){ $this->phash = $phash; }
		if(!$output){ return $this->SetControl($o_params); }
		echo $this->SetControl($o_params);
	}//END public function Show
	/**
	 * Gets (shows) the control's filters box content
	 *
	 * @param  array $params An array of parameters
	 * * phash (string) = new page hash (window.name)
	 * * output (bool|numeric) = flag indicating direct (echo)
	 * or indirect (return) output (default FALSE - indirect (return) output)
	 * * other passthru params
	 * @return string Returns the control's filters box content
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function ShowFiltersBox($params = NULL) {
		$o_params = is_object($params) ? $params : new Params($params);
		$phash = $o_params->safeGet('phash',NULL,'is_notempty_string');
		$output = $o_params->safeGet('output',FALSE,'bool');
		if($phash) { $this->phash = $phash; }
		if(!$output) { return $this->GetFilterBox($o_params); }
		echo $this->GetFilterBox($o_params);
	}//END public function ShowFiltersBox
	/**
	 * Sets new value for base class property
	 *
	 * @param  string $value The new value to be set as base class
	 * @return void
	 * @access public
	 */
	public function SetBaseClass($value) {
		$this->baseclass = strlen($value) ? $value : $this->baseclass;
	}//END public function SetBaseClass
	/**
	 * Sets new value for base class property
	 *
	 * @param  string $value The new value to be set as base class
	 * @return void
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function ExportAll($params = NULL) {
		// NApp::_Dlog($params,'ExportAll');
		$phash = $params->safeGet('phash',NULL,'is_notempty_string');
		$output = $params->safeGet('output',FALSE,'bool');
		if($phash){ $this->phash = $phash; }
		$this->export_only = TRUE;
		$items = $this->GetData();
		if(!is_object($items) || !count($items)) { throw new \PAF\AppException(\Translate::Get('msg_no_data_to_export'),E_ERROR,1); }
		$tmp_export_data = $this->export_data;
		$this->export_data = array('columns'=>[],'data'=>[]);
		$tmpparams = [];
		$this->IterateData($items,$tmpparams);
		if(!$this->export_data) { throw new \PAF\AppException(\Translate::Get('msg_no_data_to_export'),E_ERROR,1); }
		$this->export_data['with_borders'] = TRUE;
		$this->export_data['freeze_pane'] = TRUE;
		$this->export_data['default_width'] = 150;
		$params = array(
			'pre_processed_data'=>TRUE,
			'output'=>TRUE,
			'layouts'=>array($this->export_data),
			'summarize'=>$this->with_totals,
		);
		$cachefile = NApp::_GetCachePath().'datagrid/'.$this->chash.'_all.tmpexp';
		// NApp::_Dlog($cachefile,'$cachefile');
		try {
			if(!file_exists(NApp::_GetCachePath().'datagrid')) {
				mkdir(NApp::_GetCachePath().'datagrid',755);
			}//if(!file_exists(NApp::_GetCachePath().'datagrid'))
			if(file_exists($cachefile)) { unlink($cachefile); }
			file_put_contents($cachefile,serialize($params));
		} catch(\Exception $e) {
			NApp::_Elog($e->getMessage(),'TableView::ExportAll');
			$output = FALSE;
		}//END try
		$this->export_data = $tmp_export_data;
		$this->export_only = FALSE;
		if(!$output) { return; }
		$url = NApp::app_web_link().'/pipe/download.php?namespace='.NApp::current_namespace().'&dtype=datagridexcelexport&exportall=1&chash='.$this->chash;
		NApp::arequest()->ExecuteJs("OpenUrl('{$url}',true)");
	}//END public function ExportAll
	/**
	 * Get export data
	 *
	 * @param  array $params An array of parameters
	 * @return string|bool Returns file content or FALSE on error
	 * @access public
	 * @throws \PAF\AppException
	 */
	public static function ExportData(array $params = []) {
		$chash = get_array_param($params,'chash',NULL,'is_notempty_string');
		if(!$chash) { return; }
		$export_all = get_array_param($params,'exportall',FALSE,'bool');
		//NApp::StartTimeTrack('TableViewExportData');
		$cachefile = NApp::_GetCachePath().'datagrid/'.$chash.($export_all ? '_all' : '').'.tmpexp';
		try {
			if(!file_exists($cachefile)) {
				NApp::_Elog('File '.$cachefile.' not found !','TableView::GetExportCacheFile');
				return;
			}//if(!file_exists($cachefile))
			$export_data = unserialize(file_get_contents($cachefile));
			 // NApp::Log2File(print_r($export_data,TRUE),NApp::app_path().AppConfig::logs_path().'/test.log');
			// NApp::_Dlog(NApp::ShowTimeTrack('TableViewExportData',FALSE),'BP:0');
			if(!is_array($export_data) || !count($export_data)) { return; }
			$excel = new ExcelExport($export_data);
			// NApp::_Dlog(NApp::ShowTimeTrack('TableViewExportData'),'BP:END');
		} catch(AppException $e) {
			NApp::_Elog($e->getMessage(),'TableView::GetExportCacheFile');
			throw $e;
		}//END try
	}//END public static function ExportData
}//END class TableView
?>