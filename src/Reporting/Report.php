<?php
/**
 * Report class file
 * Used for generating in-page reports.
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Reporting;
use Exception;
use GibberishAES;
use NApp;
use NETopes\Core\App\AppHelpers;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NETopes\Core\Validators\Validator;
use Translate;

/**
 * Reports class
 * Used for generating in-page reports.
 * @package  NETopes\Reporting
 */
class Report extends ExcelExport {
    /**
     * @var    string HTML result string
     */
    protected $result = NULL;
    /**
     * @var    string CSS class used for the main table container
     */
    protected $base_class = 'listing lineslisting span-cent';
    /**
     * @var    string Report display name
     */
    protected $report_name = NULL;
    /**
     * @var    array Report formats array
     */
    protected $r_formats = [];
    /**
     * @var    bool Flag for turning excel export on/off
     */
    public $excel_export = TRUE;
    /**
     * @var    string The excel export file name
     */
    protected $export_file_name = NULL;
    /**
     * @var    string The cached excel export file name (including full path)
     */
    protected $cached_file = '';
    /**
     * @var    string The excel export download module
     */
    protected $export_module = 'Statistics';
    /**
     * @var    string The excel export download method
     */
    protected $export_method = 'DownloadCachedFile';
    /**
     * @var    int Report records number
     */
    public $records_no = 0;
    /**
     * @var    bool Flag for displaying or not the report records number
     */
    public $display_records_no = TRUE;
    /**
     * Class constructor function
     * @param  array $params An array of params (required)
     * - 'version'(string): version of the excel data to be output
     * ('Excel2007'/'Excel5'/...)
     * - 'output'(bool): if set TRUE the constructor will output the data
     * - 'save_path'(string): absolute path where the output excel
     * file will be saved (if NULL or empty, output will be
     * sent to the browser for download)
     * @return void
     */
    //public function __construct(&$layout = [],&$data = [],&$class) {
    public function __construct(&$params = []) {
        if(!is_array($params) || !count($params) || !array_key_exists('layouts',$params) || !is_array($params['layouts']) || !count($params['layouts'])) {
            throw new AppException('Invalid object parameters !',E_ERROR,1);
        }
        $this->report_name = get_array_value($params,'report_name',NULL,'is_notempty_string');
        $this->export_module = get_array_value($params,'export_module',$this->export_module,'is_string');
        $this->export_method = get_array_value($params,'export_method',$this->export_method,'is_string');
        $phash = get_array_value($params,'phash',NULL,'is_notempty_string');
        $this->excel_export = (strlen($this->export_module) && strlen($this->export_method) && strlen($phash)) ? get_array_value($params,'excel_export',$this->excel_export,'bool') : false;
        if($this->excel_export) {
            $this->dhash=AppSession::GetNewUID(get_class_basename($this),'sha1');
            $this->cached_file='cache_'.AppSession::GetNewUID(get_class_basename($this).$phash,'sha1',TRUE);
            $def_fname = str_replace(' ','_',trim($this->report_name)).'_'.date('d.m.Y-H.i').'.xlsx';
            $this->export_file_name = GibberishAES::enc(get_array_value($params,'file_name',$def_fname,'is_notempty_string'),$this->dhash);
            $this->excel_export = $this->CreateCacheExcelFile($params);
        }//if($this->excel_export && $this->report_run_type=='export')
        $this->decimal_separator = (array_key_exists('decimal_separator',$params) && $params['decimal_separator']) ? $params['decimal_separator'] : NApp::GetParam('decimal_separator');
        $this->group_separator = (array_key_exists('group_separator',$params) && $params['group_separator']) ? $params['group_separator'] : NApp::GetParam('group_separator');
        $this->date_separator = (array_key_exists('date_separator',$params) && $params['date_separator']) ? $params['date_separator'] : NApp::GetParam('date_separator');
        $this->time_separator = (array_key_exists('time_separator',$params) && $params['time_separator']) ? $params['time_separator'] : NApp::GetParam('time_separator');
        $this->langcode = (array_key_exists('lang_code',$params) && $params['lang_code']) ? $params['lang_code'] : NApp::GetLanguageCode();
        $this->result = '';
        foreach($params['layouts'] as $layout) {
            $this->r_formats = [];
            foreach(get_array_value($layout,'css_formats',[],'is_array') as $fkey=>$format) {
                $style = '';
                foreach($format as $frm=>$value) {
                    if($frm=='font')
                        $style .= 'font-family: '.$value.';';
                    elseif($frm=='bold' && $value==TRUE)
                        $style .= 'font-weight: bold;';
                    elseif($frm=='italic' && $value==TRUE)
                        $style .= 'font-style: italic;';
                    elseif($frm=='color')
                        $style .= 'color: #'.$value.';';
                    elseif($frm=='align')
                        $style .= 'text-align: '.$value.';';
                }//END foreach
                $this->r_formats[$fkey] = $style;
            }//END foreach
            // add columns
            $def_cell_format = get_array_value($this->r_formats,get_array_value($layout,'default_cell_format','','is_notempty_string'),NULL,'is_string');
            $header = "\t\t".'<thead>'."\n";
            $header .= "\t\t\t".'<tr>'."\n";
            $colnr = 0;
            $total_row = [];
            foreach($layout['columns'] as $column) {
                if(get_array_value($column,'hidden',false,'bool')) { $colnr++; continue; }
                $tip = get_array_value($column,'htip','','is_string');
                $hclass = strlen($tip) ? ' class="clsTitleToolTip"' : '';
                $tip = strlen($tip) ? ' title="'.$column['htip'].'" ' : '';
                $hstyle = get_array_value($column,'css_width',0,'is_numeric');
                $hstyle = $hstyle>0 ? ' style="width: '.$hstyle.'px;"' : '';
                $header .= "\t\t\t\t".'<th'.$hclass.$tip.$hstyle.'>'.$column['name'].'</th>'."\n";
                $tr_val = get_array_value($column,'total_row','','isset');
                if(strlen($tr_val)) { $total_row[$colnr] = array('formula'=>$tr_val,'value'=>0); }
                $colnr++;
            }//foreach($layout_item['columns'] as $column)
            $header .= "\t\t\t".'</tr>'."\n";
            $header .= "\t\t".'</thead>'."\n";
            $body = "\t\t".'<tbody>'."\n";
            // add data
            $data = get_array_value($layout,'data',NULL,'is_notempty_array');
            if(!is_array($data)) { continue; }
            foreach($data as $data_row) {
                $this->records_no++;
                $format_row_call = get_array_value($layout,'r_format_row_func','','is_string');
                if(strlen($format_row_call) && method_exists($this,$format_row_call)) {
                    $row_format = $this->$format_row_call($data_row,TRUE);
                } else {
                    $row_format = get_array_value($this->r_formats,get_array_value($layout,'r_format_row','','is_string'),NULL,'is_string');
                }//if(strlen($format_row_call) && method_exists($this,$format_row_call))
                $body .= "\t\t\t".'<tr'.(strlen($row_format) ? 'style="'.$row_format.'"' : '').'>'."\n";
                $colnr = 0;
                foreach($layout['columns'] as $column) {
                    if(get_array_value($column,'hidden',false,'bool')) { continue; }
                    $format_value_call = get_array_value($column,'r_format_value_func','','is_string');
                    if(strlen($format_value_call) && method_exists($this,$format_value_call)) {
                        $value = $this->$format_value_call($data_row,$column,TRUE);
                    } else {
                        $value = $this->GetValue($data_row,$column);
                    }//if(strlen($format_value_call) && method_exists($this,$format_value_call))
                    if(array_key_exists($colnr,$total_row) && is_array($total_row[$colnr]) && array_key_exists('formula',$total_row[$colnr]) && $total_row[$colnr]['formula'] && is_string($total_row[$colnr]['formula'])) {
                        switch(strtolower($total_row[$colnr]['formula'])) {
                            case 'sum':
                                $total_row[$colnr]['value'] += is_numeric($value) ? $value : 0;
                                break;
                            case 'average':
                                $total_row[$colnr]['value'] += is_numeric($value) ? $value : 0;
                                $total_row[$colnr]['rcount'] += 1;
                                break;
                            case 'count':
                                $total_row[$colnr]['value'] += $value ? 1 : 0;
                                break;
                        }//END switch
                    }//if(array_key_exists($colnr,$total_row) && is_array($total_row[$colnr]) && array_key_exists('formula',$total_row[$colnr]) && $total_row[$colnr]['formula'] && is_string($total_row[$colnr]['formula']))
                    $format_string_call = get_array_value($column,'r_format_string_func','','is_string');
                    if(strlen($format_string_call) && method_exists($this,$format_string_call)) { $value = $this->$format_string_call($value); }
                    if(strlen($value)) {
                        $value = $value.get_array_value($column,'sufix','','is_string');
                    } else {
                        $value = get_array_value($column,'defaultvalue','&nbsp;','is_string');
                    }//if(strlen($value))
                    $format_cell_call = get_array_value($column,'r_format_func','','is_string');
                    if(strlen($format_cell_call) && method_exists($this,$format_cell_call)) {
                        $format_style = $this->$format_cell_call($data_row,$column,TRUE);
                    } else {
                        $format_style = get_array_value($this->r_formats,get_array_value($column,'r_format','','is_string'),$def_cell_format,'is_string');
                    }//if(strlen($format_cell_call) && method_exists($this,$format_cell_call))
                    $body .= "\t\t\t\t".'<td'.(strlen($format_style) ? ' style="'.$format_style.'"' : '').'>'.$value.'</td>'."\n";
                    $colnr++;
                }//END foreach
                $body .= "\t\t\t".'</tr>'."\n";
            }//END foreach
            $body .= "\t\t".'</tbody>'."\n";
            $total = '';
            if(is_array($total_row) && count($total_row)) {
                $total = "\t\t".'<tfoot>'."\n";
                $total .= "\t\t\t".'<tr>'."\n";
                $bc_no = 0;
                foreach($layout['columns'] as $colnr=>$column) {
                    if(get_array_value($column,'hidden',false,'bool')) { $colnr++; continue; }
                    if(!array_key_exists($colnr,$total_row)) { $bc_no++; continue; }
                    $tr_formula = get_array_value($total_row[$colnr],'formula','','isset');
                    $tr_value = get_array_value($total_row[$colnr],'value',NULL,'is_numeric');
                    if(is_string($tr_formula)) {
                        switch($tr_formula) {
                            case 'sum':
                            case 'count':
                                break;
                            case 'average':
                                $tr_value = $tr_value / get_array_value($total_row[$colnr],'rcount',1,'is_not0_numeric');
                                break;
                            default:
                                $tr_value = NULL;
                                break;
                        }//END switch
                    } elseif(is_array($tr_formula) && count($tr_formula) && get_array_value($tr_formula,'type')=='method' && check_array_key('method',$tr_formula,'is_notempty_string')) {
                        $tr_method = get_array_value($tr_formula,'method');
                        $tr_value = $this->$tr_method($tr_value,$column);
                    } else {
                        $tr_value = NULL;
                    }//if(is_string($tr_formula))
                    if(!is_numeric($tr_value)) { $bc_no++; continue; }
                    if($bc_no) {
                        $total .= "\t\t\t\t".'<td class="tcfirst" colspan="'.$bc_no.'">&nbsp;</td>'."\n";
                        $bc_no = 0;
                    }//if($bc_no)
                    $tr_format_string_call = get_array_value($column,'r_format_string_func','','is_string');
                    if(strlen($tr_format_string_call) && method_exists($this,$tr_format_string_call)) { $tr_value = $this->$tr_format_string_call($tr_value); }
                    $tr_value = $tr_value.get_array_value($column,'sufix','','is_string');
                    $tr_style = get_array_value($column,'total_row_style',get_array_value($this->r_formats,get_array_value($column,'r_format','','is_string'),$def_cell_format,'is_string'),'is_string');
                    $total .= "\t\t\t\t".'<td class="tcolumn bold"'.(strlen($tr_style) ? ' style="'.$tr_style.'"' : '').'>'.$tr_value.'</td>'."\n";
                }//END foreach
                if($bc_no) { $total .= "\t\t\t\t".'<td class="tcfirst" colspan="'.$bc_no.'">&nbsp;</td>'."\n"; }
                $total .= "\t\t\t".'</tr>'."\n";
                $total .= "\t\t".'</tfoot>'."\n";
            }//if(is_array($total_row) && count($total_row))
            if($this->report_name) {
                $this->result .= "\t".'<div class="option_blue_bold" style="margin-bottom: 10px; font-size: 14px; text-align: center;">'.$this->report_name.'</div>'."\n";
            }//if($this->report_name)
            $ee_button = '';
            if($this->excel_export) {
                $ee_button.="\t\t".'<span style="float: right; width: 300px; margin-right: 50px;"><div  class="round_button right" onclick="'.NApp::Ajax()->Prepare(
                        "{ 'module': '{$this->export_module}', 'method': '{$this->export_method}', 'params': { 'fsource': '{$this->cached_file}', 'fname': '{$this->export_file_name}', 'dhash': '{$this->dhash}' }}",'excelexport_errors').'">'.Translate::Get('download_label',$this->langcode).'</div></span>'."\n";
            }//if($this->excel_export)
            if($this->display_records_no) {
                $this->result .= "\t".'<div style="height: 20px; margin-bottom: 10px;">'."\n";
                $this->result.="\t\t".'<span style="float: left; width: 300px; margin-left: 50px;"><strong>'.$this->records_no.'</strong> '.Translate::Get('results_label',$this->langcode).'</span>'."\n";
                $this->result .= $ee_button;
                $this->result .= "\t".'</div>'."\n";
            } elseif(strlen($ee_button)) {
                $this->result .= "\t".'<div style="height: 20px; margin-bottom: 10px;">'.$ee_button."\n"."\t".'</div>'."\n";
            }//if($this->display_records_no)
            if(strlen($ee_button)) { $this->result .= "\t".'<div id="excelexport_errors"></div>'."\n"; }
            $table_class = get_array_value($layout,'table_class',$this->base_class,'is_string');
            $this->result .= "\t".'<table'.(strlen($table_class) ? ' class="'.$table_class.'"' : '').'>'."\n";
            $this->result .= $header;
            $this->result .= $body;
            $this->result .= $total;
            $this->result .= "\t".'</table>'."\n";
            $this->result .= "\t".'<div>&nbsp;</div>'."\n";
        }//END foreach
    }//END public function __construct

    protected function GetValue(&$data,&$column) {
        $col_value = '';
        $dbfield = $column['dbfield'];
        if(!is_array($column['dbfield'])) { $dbfield = array($column['dbfield']); }
        foreach($dbfield as $field) {
            if(array_key_exists('indexof',$column) && $column['indexof']) {
                if(is_array($column['indexof'])) {
                    $aname = $column['indexof'][0];
                    $kvalue = count($column['indexof'])>1 ? $column['indexof'][1] : 'name';
                } else {
                    $aname = $column['indexof'];
                    $kvalue = 'name';
                }//if(is_array($column['indexof']))
                $tmp_value = (array_key_exists($aname,$this->extra_params) && is_array($this->extra_params[$aname]) && array_key_exists($field,$data) && array_key_exists($data[$field],$this->extra_params[$aname]) && array_key_exists($kvalue,$this->extra_params[$aname][$data[$field]])) ? $this->extra_params[$aname][$data[$field]][$kvalue] : '';
            }else{
                $tmp_value = array_key_exists($field,$data) ? $data[$field] : '';
            }//if(array_key_exists('indexof',$column) && $column['indexof'])
            $col_value .= ($tmp_value ? ((array_key_exists('separator',$column) && $column['separator']) ? $column['separator'] : ' ').$tmp_value : '');
        }//END foreach
        return $col_value;
    }//END protected function GetValue

    protected function CreateCacheExcelFile(&$params) {
        if(!strlen($this->cached_file)) { return FALSE; }
        try {
            $cf_path = AppHelpers::GetCachePath().'reports';
            if(!file_exists($cf_path)) { mkdir($cf_path,0755,TRUE); }
            if(file_exists($cf_path.'/'.$this->cached_file)) { unlink($cf_path.'/'.$this->cached_file); }
            $params['output'] = TRUE;
            $params['save_path'] = $cf_path.'/';
            $params['file_name'] = $this->cached_file;
            parent::__construct($params);
            $this->cached_file = GibberishAES::enc($cf_path.'/'.$this->cached_file.'.xlsx',$this->dhash);
        } catch(Exception $e) {
            return FALSE;
        }//END try
        return TRUE;
    }//END protected function CreateCacheExcelFile

    public function Show() {
        return $this->result;
    }//END public function Show

    protected function NumberFormat0($value) {
        return number_format((is_numeric($value) ? $value : 0),0,$this->decimal_separator,$this->group_separator);
    }//END protected function NumberFormat0

    protected function NumberFormat2($value) {
        return number_format((is_numeric($value) ? $value : 0),2,$this->decimal_separator,$this->group_separator);
    }//END protected function NumberFormat2

    protected function PercentFormat0($value) {
        return number_format((is_numeric($value) ? $value : 0),0,$this->decimal_separator,$this->group_separator).' %';
    }//END protected function PercentFormat0

    protected function PercentFormat2($value) {
        return number_format((is_numeric($value) ? $value : 0),2,$this->decimal_separator,$this->group_separator).' %';
    }//END protected function PercentFormat2

    protected function DateFormat($value){
        return Validator::ConvertDateTime($value,NApp::GetParam('timezone'),TRUE);
    }//END protected function DateFormat

    protected function DateTimeFormat($value){
        return Validator::ConvertDateTime($value,NApp::GetParam('timezone'),FALSE);
    }//END protected function DateTimeFormat

    protected function NoTimezoneDateFormat($value){
        return Validator::ConvertDateTime($value,'',TRUE);
    }//END protected function DateFormat

    protected function NoTimezoneDateTimeFormat($value){
        return Validator::ConvertDateTime($value,'',FALSE);
    }//END protected function DateTimeFormat
}//class Report extends ExcelExport