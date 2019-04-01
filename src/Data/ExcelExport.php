<?php
/**
 * Excel export class file
 * Wrapper for exporting data to excel
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use DateTime;
use ErrorHandler;
use NETopes\Core\Validators\TDateTimeHelpers;
use NETopes\Core\Validators\Validator;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NApp;
/**
 * Excel export class
 * Wrapper for exporting data to excel
 * @package  NETopes\Core\Data
 */
class ExcelExport {
    use TDateTimeHelpers;
    /**
     * @var    array PHP Spreadsheet accepted file types
     */
    protected $file_types = array('xlsx'=>'Xlsx','xls'=>'Xls','ods'=>'Ods','csv'=>'Csv'/*,'xml'=>'Xml','html'=>'Html','htm'=>'Html'*/);
    /**
     * @var    \PhpOffice\PhpSpreadsheet\Spreadsheet PhpSpreadsheet object
     */
    protected $obj = NULL;
    /**
     * @var    string Decimal separator
     */
    protected $decimal_separator = NULL;
    /**
     * @var    string Group separator
     */
    protected $group_separator = NULL;
    /**
     * @var    string Date separator
     */
    protected $date_separator = NULL;
    /**
     * @var    string Time separator
     */
    protected $time_separator = NULL;
    /**
     * @var    string User's time zone
     */
    protected $timezone = NULL;
    /**
     * @var    string Language code
     */
    protected $langcode = NULL;
    /**
     * @var    array An array containing default formats
     */
    protected $default_formats = array(
        'header'=>array('align_h'=>'h_center','bold'=>TRUE,'background_color'=>'D8D8D8'),
        'footer'=>array('bold'=>TRUE,'background_color'=>'DBEEF3'),
        'standard'=>array('align_h'=>'h_general'),
        'left'=>array('align_h'=>'h_left'),
        'center'=>array('align_h'=>'h_center'),
        'right'=>array('align_h'=>'h_right'),
        'number0_c'=>array('align_h'=>'h_center','number'=>'#,##0'),
        'number0_r'=>array('align_h'=>'h_right','number'=>'#,##0'),
        'number2_c'=>array('align_h'=>'h_center','number'=>'#,##0.00'),
        'number2_r'=>array('align_h'=>'h_right','number'=>'#,##0.00'),
        'number3_c'=>array('align_h'=>'h_center','number'=>'#,##0.000'),
        'number3_r'=>array('align_h'=>'h_right','number'=>'#,##0.000'),
        'decimal2_c'=>array('align_h'=>'h_center','number'=>'#,##0.00'),
        'decimal2_r'=>array('align_h'=>'h_right','number'=>'#,##0.00'),
        'decimal3_c'=>array('align_h'=>'h_center','number'=>'#,##0.000'),
        'decimal3_r'=>array('align_h'=>'h_right','number'=>'#,##0.000'),
        'euro0_r'=>array('align_h'=>'h_right','number'=>'#,##0 €'),
        'euro2_r'=>array('align_h'=>'h_right','number'=>'#,##0.00 €'),
        'percent0_c'=>array('align_h'=>'h_center','number'=>'#,##0 %'),
        'percent0_r'=>array('align_h'=>'h_right','number'=>'#,##0 %'),
        'percent2_c'=>array('align_h'=>'h_center','number'=>'#,##0.00 %'),
        'percent2_r'=>array('align_h'=>'h_right','number'=>'#,##0.00 %'),
        'percent0x100_c'=>array('align_h'=>'h_center','number'=>'#,##0 %'),
        'percent0x100_r'=>array('align_h'=>'h_right','number'=>'#,##0 %'),
        'percent2x100_c'=>array('align_h'=>'h_center','number'=>'#,##0.00 %'),
        'percent2x100_r'=>array('align_h'=>'h_right','number'=>'#,##0.00 %'),
        'date_c'=>array('align_h'=>'h_center','datetime'=>'dd.mm.yyyy'),
        'datetime_c'=>array('align_h'=>'h_center','datetime'=>'dd.mm.yyyy hh:mm:ss')
    );
    /**
     * @var    bool Flag indicating if borders are used
     */
    protected $with_borders = FALSE;
    /**
     * @var    array An array containing all instance formats
     */
    protected $formats = [];
    /**
     * @var    string Default column format
     */
    protected $default_format = NULL;
    /**
     * @var    array An array containing table totals
     */
    protected $total_row = [];
    /**
     * @var    array An array containing extra params or extra data
     */
    protected $extra_params = NULL;
    /**
     * @var    bool Flag indicating if the data is pre-processed
     */
    public $pre_processed_data = FALSE;

    /**
     * Class constructor function
     * @param  array $params An array of params (required)
     * - 'version'(string): version of the excel data to be output
     * ('xlsx'/'xls'/'csv'/'ods'/'html')
     * - 'output'(bool): if set TRUE the constructor will output the data
     * - 'save_path'(string): absolute path where the output excel
     * file will be saved (if NULL or empty, output will be
     * sent to the browser for download)
     * @throws \NETopes\Core\AppException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function __construct(array $params) {
        if(!count($params) || !array_key_exists('layouts',$params) || !is_array($params['layouts']) || !count($params['layouts'])) { throw new AppException('ExcelExport: Invalid parameters !',E_ERROR,1); }
        $this->pre_processed_data = get_array_value($params,'pre_processed_data',FALSE,'bool');
        $this->decimal_separator = get_array_value($params,'decimal_separator',NApp::GetParam('decimal_separator'),'is_string');
        $this->group_separator = get_array_value($params,'group_separator',NApp::GetParam('group_separator'),'is_string');
        $this->date_separator = get_array_value($params,'date_separator',NApp::GetParam('date_separator'),'is_string');
        $this->time_separator = get_array_value($params,'time_separator',NApp::GetParam('time_separator'),'is_string');
        $this->langcode = get_array_value($params,'lang_code',NApp::GetLanguageCode(),'is_string');
        $this->timezone = get_array_value($params,'timezone',NApp::GetParam('timezone'),'is_notempty_string');
        $file_type = get_array_value($params,'version','xlsx','is_notempty_string');
        if(!in_array($file_type,array_keys($this->file_types))) { throw new AppException('ExcelExport: Invalid output file type!',E_ERROR,1); }
        $output = get_array_value($params,'output',FALSE,'bool');
        $save_path = get_array_value($params,'save_path',NULL,'is_string');
        $file_name = get_array_value($params,'file_name','','is_string');
        Cell::setValueBinder(new AdvancedValueBinder());
        $this->obj = new Spreadsheet();
        $this->obj->getDefaultStyle()->getFont()->setName('Calibri');
        $this->obj->getDefaultStyle()->getFont()->setSize(10);
        $sheet_index = -1;
        $sheet_name = '';
        $active_sheet = NULL;
        foreach($params['layouts'] as $layout) {
            if(!is_array($layout) || !array_key_exists('columns',$layout) || !count($layout['columns']) || !array_key_exists('data',$layout)) {
                $this->obj = NULL;
                throw new AppException('ExcelExport: Invalid sheet parameters !',E_ERROR,1);
            }//if(!is_array($layout) || !array_key_exists('columns',$layout) || !count($layout['columns']) || !array_key_exists('data',$layout))
            $c_sheet_name = get_array_value($layout,'sheet_name','','is_string');
            if($sheet_index<0 || $c_sheet_name!=$sheet_name) {
                $sheet_index++;
                $row_no = 1;
                $this->total_row = [];
                if($sheet_index>0 || $this->obj->getSheetCount()==0) { $this->obj->createSheet(); }
                $this->obj->setActiveSheetIndex($sheet_index);
                $active_sheet = $this->obj->getActiveSheet();
                $sheet_name = strlen($c_sheet_name) ? $c_sheet_name : 'sheet'.($sheet_index+1);
                $active_sheet->setTitle($sheet_name);
            }//if($sheet_index<0 || $c_sheet_name!=$sheet_name)
            //Define default format
            if(array_key_exists('default_width',$layout)) {
                $active_sheet->getDefaultColumnDimension()->setWidth($layout['default_width']/10);
            }//if(array_key_exists('default_width',$layout))
            if(array_key_exists('default_height',$layout)) {
                $active_sheet->getDefaultRowDimension()->setRowHeight($layout['default_height']/10);
            }//if(array_key_exists('default_height',$layout))
            if(array_key_exists('with_borders',$layout) && $layout['with_borders']) {
                $this->with_borders = TRUE;
                $this->default_formats['border_std']=['borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'000000']]]];
                if(array_key_exists('with_outer_border',$layout) && $layout['with_outer_border']) {
                    $this->default_formats['border_out']=['borders'=>['outline'=>['borderStyle'=>Border::BORDER_MEDIUM,'color'=>['rgb'=>'000000']]]];
                }//if(array_key_exists('with_outer_border',$layout) && $layout['with_outer_border'])
            }//if(array_key_exists('with_borders',$layout) && $layout['with_borders'])
            $this->SetFormats(array_key_exists('formats',$layout) ? $layout['formats'] : []);
            $this->default_format = (array_key_exists('default_format',$layout) && $layout['default_format']) ? $layout['default_format'] : 'standard';
            //insert header row
            if($this->with_borders) {
                $this->ApplyStyleArray($active_sheet,'A'.$row_no.':'.$this->IndexToColumn(count($layout['columns'])).$row_no,'border_std');
                $this->ApplyStyleArray($active_sheet,'A'.$row_no.':'.$this->IndexToColumn(count($layout['columns'])).$row_no,'border_out');
            }//if($this->with_borders)
            $header_format = (array_key_exists('header_format',$layout) && $layout['header_format']) ? $layout['header_format'] : 'header';
            $this->ApplyStyleArray($active_sheet,'A'.$row_no.':'.$this->IndexToColumn(count($layout['columns'])).$row_no,$header_format);
            $col_no = 0;
            foreach($layout['columns'] as $k=>$column) {
                $col_no++;
                if(get_array_value($column,'summarize',FALSE,'bool')) {
                    $this->total_row[$col_no] = array(
                        'value'=>0,
                        'key'=>$k,
                        'type'=>strtolower(get_array_value($column,'summarize_type','count','is_notempty_string')),
                    );
                }//if(get_array_value($column,'summarize',FALSE,'bool'))
                $c_width = get_array_value($column,'width',get_array_value($column,'ewidth',NULL,'is_notempty_string'),'is_notempty_string');
                if($c_width && strpos($c_width,'%')===FALSE) {
                    if(strpos($c_width,'px')!==FALSE) { $c_width = str_replace('px','',trim($c_width)); }
                    if(is_numeric($c_width)) {
                        $active_sheet->getColumnDimension($this->IndexToColumn($col_no))->setWidth($c_width/10);
                    }//if(is_numeric($c_width))
                }//if($c_width && strpos($c_width,'%')===FALSE)
                if(array_key_exists('header_format',$column) && $column['header_format']) {
                    $this->ApplyStyleArray($active_sheet,$this->IndexToColumn($col_no).$row_no,$column['header_format']);
                }//if(array_key_exists('header_format',$column) && $column['header_format'])
                $active_sheet->setCellValue($this->IndexToColumn($col_no).$row_no,get_array_value($column,'label',$col_no,'is_string'));
            }//END foreach
            if(!is_array($layout['data']) || !count($layout['data'])) { continue; }
            foreach($layout['data'] as $data_row) {
                if(!is_array($data_row) || !count($data_row)) { continue; }
                $col_no = 0;
                $row_no++;
                $row_format = (array_key_exists('format_row_func',$layout) && $layout['format_row_func']) ? $this->$layout['format_row_func']($data_row) : [];
                foreach($layout['columns'] as $column) {
                    $col_no++;
                    $col_format_name = get_array_value($column,'format',NULL,'is_notempty_string');
                    if($col_format_name) {
                        $col_format_name .= '_'.substr(get_array_value($column,'halign','center','is_notempty_string'),0,1);
                    } else {
                        $col_format_name = get_array_value($column,'eformat','standard','is_notempty_string');
                    }//if($col_format_name)
                    $col_def_format = get_array_value($this->formats,$col_format_name,[],'is_array');
                    $col_custom_format = (array_key_exists('format_func',$column) && $column['format_func']) ? $this->$column['format_func']($data_row,$column) : [];
                    $col_format = array_merge((is_array($col_def_format) ? $col_def_format : []),(is_array($row_format) ? $row_format : []),(is_array($col_custom_format) ? $col_custom_format : []));
                    $this->ApplyStyleArray($active_sheet,$this->IndexToColumn($col_no).$row_no,$col_format);
                    $this->SetCellValue($active_sheet,$row_no,$col_no,$column,$data_row);
                }//END foreach
            }//END foreach
            if($this->with_borders) {
                $this->ApplyStyleArray($active_sheet,'A2:'.$this->IndexToColumn(count($layout['columns'])).$row_no,'border_std');
                $this->ApplyStyleArray($active_sheet,'A2:'.$this->IndexToColumn(count($layout['columns'])).$row_no,'border_out');
            }//if($this->with_borders)
            if(count($this->total_row)>0) {
                $row_no++;
                if($this->with_borders) {
                    $this->ApplyStyleArray($active_sheet,'A'.$row_no.':'.$this->IndexToColumn(count($layout['columns'])).$row_no,'border_std');
                    $this->ApplyStyleArray($active_sheet,'A'.$row_no.':'.$this->IndexToColumn(count($layout['columns'])).$row_no,'border_out');
                }//if($this->with_borders)
                foreach($this->total_row as $c=>$v) {
                    if(!get_array_value($layout['columns'][$v['key']],'summarize',FALSE,'bool')) { continue; }
                    $col_def_format = get_array_value($layout['columns'][$v['key']],'format','standard','is_notempty_string');
                    $this->ApplyStyleArray($active_sheet,$this->IndexToColumn($c).$row_no,$col_def_format);
                    switch($v['type']) {
                        case 'sum':
                        case 'count':
                        case 'average':
                            $tformula = '='.strtoupper($v['type']).'('.$this->IndexToColumn($c).'2:'.$this->IndexToColumn($c).($row_no-1).')';
                            break;
                        default:
                            continue;
                            break;
                    }//END switch
                    $active_sheet->setCellValue($this->IndexToColumn($c).$row_no,$tformula);
                }//END foreach
                $footer_format = (array_key_exists('footer_format',$layout) && $layout['footer_format']) ? $layout['footer_format'] : 'footer';
                $this->ApplyStyleArray($active_sheet,'A'.$row_no.':'.$this->IndexToColumn(count($layout['columns'])).$row_no,$footer_format);
            }//if(count($this->total_row)>0)
            if(!array_key_exists('freeze_pane',$layout) || $layout['freeze_pane']) { $active_sheet->freezePane('A2'); }
        }//END foreach
        $this->obj->setActiveSheetIndex(0);
        if(ErrorHandler::HasErrors()) { ErrorHandler::ShowErrors();
            return;
        }
        if($output) {
            $this->OutputData($file_name,$save_path,$file_type); }
    }//END public function __construct

    /**
     * Set table cell value
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int                                           $row
     * @param int                                           $col
     * @param array                                         $column
     * @param array                                         $data
     * @param bool                                          $return
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function SetCellValue(Worksheet &$sheet,int $row,int $col,array $column,array $data,bool $return = FALSE) {
        if(array_key_exists('format_value_func',$column) && $column['format_value_func']) {
            $col_value = $this->$column['format_value_func']($data,$column);
        } elseif(array_key_exists('format_formula_func',$column) && $column['format_formula_func']) {
            $col_value = $this->$column['format_formula_func']($row,$col,$data,$column);
        } else {
            if($this->pre_processed_data) {
                $col_name = get_array_value($column,'name',NULL,'is_string');
                $col_value = get_array_value($data,$col_name,get_array_value($column,'default_value',NULL,'isset'),'isset');
            } else {
                $col_value = '';
                $dbfield = is_array($column['db_field']) ? $column['db_field'] : [$column['db_field']];
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
                    } else {
                        $tmp_value = array_key_exists($field,$data) ? $data[$field] : '';
                    }//if(array_key_exists('indexof',$column) && $column['indexof'])
                    $col_value .= ($tmp_value ? ((array_key_exists('separator',$column) && $column['separator']) ? $column['separator'] : ' ').$tmp_value : '');
                }//END foreach
            }//if($this->pre_processed_data)
        }//if(array_key_exists('format_value_func',$column) && $column['format_value_func'])
        $col_value = (isset($col_value) ? $col_value : '');
        if(count($this->total_row) && get_array_value($column,'summarize',FALSE,'bool')) {
            if(get_array_value($column,'values_total_row',FALSE,'bool')) {
                switch($this->total_row[$col]['type']) {
                    case 'sum':
                    case 'average':
                        $this->total_row[$col]['value'] += is_numeric($col_value) ? $col_value : 0;
                        break;
                    case 'count':
                        $this->total_row[$col]['value'] += $col_value ? 1 : 0;
                        break;
                    default:
                        break;
                }//END switch
            } else {
                $this->total_row[$col]['value'] = TRUE;
            }//if(get_array_value($column,'values_total_row',FALSE,'bool'))
        }//if(count($this->total_row) && get_array_value($column,'summarize',FALSE,'bool'))
        if($return) { return $col_value; }
        if(array_key_exists('format_formula_func',$column) && $column['format_formula_func']) {
            $sheet->setCellValue($this->IndexToColumn($col).$row,$col_value);
        } else {
            $ccol = is_integer($col) ? $this->IndexToColumn($col) : $col;
            $v_dtype = is_numeric($col_value) ? 'numeric' : 'string';
            $data_type = get_array_value($column,'data_type',$v_dtype,'is_notempty_string');
            if($data_type=='date' || $data_type=='datetime' || $data_type=='date_obj' || $data_type=='datetime_obj') {
                $dt_value = static::datetimeToExcelTimestamp(Validator::ConvertDateTimeToObject($col_value,NULL,$this->timezone),$this->timezone);
                if($dt_value) {
                    $col_value = $dt_value;
                    $data_type = 'datetime';
                } else {
                    $data_type = 'string';
                }//if($dt_value)
            } elseif($data_type=='numeric' && $v_dtype=='string') {
                $data_type = 'string';
            }//if($data_type=='date' || $data_type=='datetime' || $data_type=='date_obj' || $data_type=='datetime_obj')
            if($data_type=='string') {
                $col_value .= get_array_value($column,'sufix','','is_string');
            }//if($data_type=='string')
            $sheet->getCell($ccol.$row)->setValueExplicit($col_value,$this->GetDataType($data_type));
        }//if(array_key_exists('format_formula_func',$column) && $column['format_formula_func'])
    }//END protected function SetCellValue

    /**
     * Sets formats to be used in current instance
     * @param  array $formats Custom formats array
     * @return void
     */
    protected function SetFormats(array $formats = []): void {
        $this->formats = array_merge($this->default_formats,$formats);
    }//END protected function SetFormats

    /**
     * Get column name in excel format (literal)
     * @param  int $index Index of a column
     * @return string Returns column name in excel format
     */
    protected function IndexToColumn(int $index): string {
        if($index<=26) { return chr($index+64); }
        $div = intval($index/26);
        $mod = $index % 26;
        if($mod==0) {
            $div--;
            $mod = 26;
        }//if($mod==0)
        $result = chr($div+64).chr($mod+64);
        return $result;
    }//END protected function IndexToColumn

    /**
     * Convert data type to \PhpOffice\PhpSpreadsheet\Spreadsheet format
     * @param  string $type Data type
     * @return int Returns data type in \PhpOffice\PhpSpreadsheet\Spreadsheet format
     */
    protected function GetDataType(string $type) {
        switch($type) {
            case 'numeric':
            case 'date':
            case 'datetime':
                return DataType::TYPE_NUMERIC;
            case 'boolean':
                return DataType::TYPE_BOOL;
            case 'string':
            default:
                return DataType::TYPE_STRING;
        }//switch($type)
    }//END protected function GetDataType

    /**
     * Convert border style string to \PhpOffice\PhpSpreadsheet\Spreadsheet format
     * @param  string $type Border style name
     * @return int Returns border style in \PhpOffice\PhpSpreadsheet\Spreadsheet format
     */
    protected function GetBorderStyle(string $type) {
        switch($type) {
            case 'dashdot':
                return Border::BORDER_DASHDOT;
            case 'dashdotdot':
                return Border::BORDER_DASHDOTDOT;
            case 'dashed':
                return Border::BORDER_DASHED;
            case 'dotted':
                return Border::BORDER_DOTTED;
            case 'double':
                return Border::BORDER_DOUBLE;
            case 'hair':
                return Border::BORDER_HAIR;
            case 'medium':
                return Border::BORDER_MEDIUM;
            case 'mediumdashdot':
                return Border::BORDER_MEDIUMDASHDOT;
            case 'mediumdashdotdot':
                return Border::BORDER_MEDIUMDASHDOTDOT;
            case 'mediumdasher':
                return Border::BORDER_MEDIUMDASHED;
            case 'bordernone':
                return Border::BORDER_NONE;
            case 'slantdashdot':
                return Border::BORDER_SLANTDASHDOT;
            case 'borderthick':
                return Border::BORDER_THICK;
            case 'borderthin':
                return Border::BORDER_THIN;
            default:
                return Border::BORDER_NONE;
        }//switch($type)
    }//END protected function GetBorderStyle

    /**
     * Convert border style string to \PhpOffice\PhpSpreadsheet\Spreadsheet format
     * @param  string $type Alignment style name
     * @return int Returns alignment style in \PhpOffice\PhpSpreadsheet\Spreadsheet format
     */
    protected function GetAlignmentStyle(string $type) {
        switch($type) {
            case 'h_center':
                return Alignment::HORIZONTAL_CENTER;
            case 'h_venter_continuous':
                return Alignment::HORIZONTAL_CENTER_CONTINUOUS;
            case 'h_general':
                return Alignment::HORIZONTAL_GENERAL;
            case 'h_justify':
                return Alignment::HORIZONTAL_JUSTIFY;
            case 'h_left':
                return Alignment::HORIZONTAL_LEFT;
            case 'h_right':
                return Alignment::HORIZONTAL_RIGHT;
            case 'v_bottom':
                return Alignment::VERTICAL_BOTTOM;
            case 'v_center':
                return Alignment::VERTICAL_CENTER;
            case 'v_justify':
                return Alignment::VERTICAL_JUSTIFY;
            case 'v_top':
                return Alignment::VERTICAL_TOP;
            default:
                return Alignment::HORIZONTAL_GENERAL;
        }//switch($type)
    }//END protected function GetAlignmentStyle

    /**
     * Apply style array to a range of cells
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet Target sheet instance
     * @param  string                                       $range Target cells range in excel format
     * @param  string|array                                 $style Style array to be applied
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function ApplyStyleArray(Worksheet &$sheet,string $range,$style) {
        if(!is_object($sheet) || !$range || !$style) { return FALSE; }
        if(is_array($style)) {
            $style_arr = $style;
        } elseif(isset($this->formats[$style])) {
            $style_arr = $this->formats[$style];
        } else {
            return FALSE;
        }//if(is_array($style))
        $fstyle = [];
        $nformat = '';
        $fill = '';
        foreach($style_arr as $key=>$val) {
            switch($key) {
                case 'font':
                    $fstyle['font']['name'] = $val;
                    break;
                case 'bold':
                    $fstyle['font']['bold'] = $val;
                    break;
                case 'italic':
                    $fstyle['font']['italic'] = $val;
                    break;
                case 'strike':
                    $fstyle['font']['strike'] = $val;
                    break;
                case 'color':
                    $fstyle['font']['color'] = array('rgb'=>$val);
                    break;
                case 'border_color':
                    $fstyle['border']['top']['color'] = array('rgb'=>$val);
                    $fstyle['border']['bottom']['color'] = array('rgb'=>$val);
                    break;
                case 'border_style':
                    $fstyle['border']['top']['borderStyle'] = $this->GetBorderStyle($val);
                    $fstyle['border']['bottom']['borderStyle'] = $style['border']['top']['borderStyle'];
                    break;
                case 'align_h':
                    $fstyle['alignment']['horizontal'] = $this->GetAlignmentStyle($val);
                    break;
                case 'align_v':
                    $fstyle['alignment']['vertical'] = $this->GetAlignmentStyle($val);
                    break;
                case 'align_rotation':
                    $fstyle['alignment']['rotation'] = $val;
                    break;
                case 'align_wrap':
                    $fstyle['alignment']['wrap'] = $val;
                    break;
                case 'background_color':
                    $fill = $val;
                    break;
                case 'number':
                case 'datetime':
                    $nformat = $val;
                default:
                    $fstyle[$key] = $val;
                    break;
            }//END switch
        }//END foreach
        if($fstyle) { $sheet->getStyle($range)->applyFromArray($fstyle); }
        if($nformat) { $sheet->getStyle($range)->getNumberFormat()->setFormatCode($nformat); }
        if($fill) { $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fill);
        }
        return TRUE;
    }//protected function ApplyStyleArray

    /**
     * Outputs excel data to a file on disk or for download
     * @param  string     $file_name Target file name
     * @param  string     $path      Target file path
     * @param null|string $file_type
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function OutputData(string $file_name,?string $path = NULL,?string $file_type = NULL): bool {
        if(!is_object($this->obj)) { return FALSE; }
        if(!strlen($file_type)) {
            $file_type = 'xlsx';
        } else {
            $file_type = strtolower($file_type);
        }//if(!strlen($file_type))
        $file = (strlen($file_name) ? $file_name : date('YmdHis')).'.'.$file_type;
        $this->obj->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($this->obj,$this->file_types[$file_type]);
        if(strlen($path)) {
            $writer->save($path.$file);
            return TRUE;
        }//if(strlen($path))
        header('Content-Description: File Transfer');
        header($this->getContentTypeHeader($file_type));
        header('Content-Disposition: attachment; filename='.$file);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: max-age=0, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        $writer->save('php://output');
        return TRUE;
    }//END public function OutputData

    /**
     * Get header content type value
     * @param string $file_type
     * @return string
     */
    protected function getContentTypeHeader(string $file_type): string {
        switch(strtolower($file_type)) {
            case 'xls':
                return 'Content-Type: application/vnd.ms-excel';
            case 'ods':
                return 'Content-Type: application/vnd.oasis.opendocument.spreadsheet';
            case 'csv':
                return 'Content-Type: text/html; charset=UTF-8';
            default:
                return 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }//END switch
    }//END protected function getContentTypeHeader

    /**
     * Convert datetime value to excel format
     * @param  array  $data Data row array
     * @param  array $column Column configuration array
     * @return float Returns timestamp in excel format
     */
    protected function FormatDateTimeValue(array $data,array $column): ?float {
        if(!isset($data[$column['db_field']])) { return NULL; }
        if(is_object($data[$column['db_field']])) {
            $dt = $data[$column['db_field']];
            $value = 25569 + $dt->getTimestamp() / 86400;
            return $value;
        } elseif(is_string($data[$column['db_field']]) && !strlen($data[$column['db_field']])) {
            $dt = new DateTime($data[$column['db_field']]);
            $value=25569 + $dt->getTimestamp() / 86400;
            return $value;
        }//if(is_object($data[$column['db_field']]))
        return NULL;
    }//END protected function FormatDateTimeValue
}//END class ExcelExport
?>