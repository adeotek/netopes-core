<?php
/**
 * Excel export class file
 *
 * Wrapper for exporting data to excel
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Classes\Data;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * Excel export class
 *
 * Wrapper for exporting data to excel
 *
 * @package  NETopes\Reporting
 * @access   public
 */
class ExcelExport {
	/**
	 * @var    object PHPExcel object
	 * @access protected
	 */
	protected $php_excel = NULL;
	/**
	 * @var    string Decimal separator
	 * @access protected
	 */
	protected $decimal_separator = NULL;
    /**
	 * @var    string Group separator
	 * @access protected
	 */
    protected $group_separator = NULL;
	/**
	 * @var    string Date separator
	 * @access protected
	 */
	protected $date_separator = NULL;
	/**
	 * @var    string Time separator
	 * @access protected
	 */
	protected $time_separator = NULL;
	/**
	 * @var    string User's time zone
	 * @access protected
	 */
	protected $timezone = NULL;
	/**
	 * @var    string Language code
	 * @access protected
	 */
	protected $langcode = NULL;
	/**
	 * @var    array An array containing default formats
	 * @access protected
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
	 * @access protected
	 */
	protected $with_borders = FALSE;
	/**
	 * @var    array An array containing all instance formats
	 * @access protected
	 */
	protected $formats = array();
	/**
	 * @var    string Default column format
	 * @access protected
	 */
	protected $default_format = NULL;
	/**
	 * @var    array An array containing table totals
	 * @access protected
	 */
	protected $total_row = array();
	/**
	 * @var    array An array containing extra params or extra data
	 * @access protected
	 */
	protected $extra_params = NULL;
	/**
	 * @var    bool Flag indicating if the data is pre-processed
	 * @access protected
	 */
	public $pre_processed_data = FALSE;
	/**
	 * Class constructor function
	 *
	 * @param  array $params An array of params (required)
	 * - 'version'(string): version of the excel data to be output
	 * ('Excel2007'/'Excel5'/...)
	 * - 'output'(bool): if set TRUE the constructor will output the data
	 * - 'save_path'(string): absolute path where the output excel
	 * file will be saved (if NULL or empty, output will be
	 * sent to the browser for download)
	 * @throws \PAF\AppException|\PHPExcel_Exception
	 * @access public
	 */
	public function __construct($params) {
		if(!is_array($params) || !count($params) || !array_key_exists('layouts',$params) || !is_array($params['layouts']) || !count($params['layouts'])) { throw new \PAF\AppException('ExcelExport: Invalid parameters !',E_ERROR,1); }
		$this->pre_processed_data = get_array_param($params,'pre_processed_data',FALSE,'bool');

		$this->decimal_separator = get_array_param($params,'decimal_separator',NApp::_GetParam('decimal_separator'),'is_string');
		$this->group_separator = get_array_param($params,'group_separator',NApp::_GetParam('group_separator'),'is_string');
		$this->date_separator = get_array_param($params,'date_separator',NApp::_GetParam('date_separator'),'is_string');
		$this->time_separator = get_array_param($params,'time_separator',NApp::_GetParam('time_separator'),'is_string');
		$this->langcode = get_array_param($params,'lang_code',NApp::_GetLanguageCode(),'is_string');
		$this->timezone = get_array_param($params,'timezone',NApp::_GetParam('timezone'),'is_notempty_string');

		$excel_version = get_array_param($params,'version','Excel2007','is_notempty_string');
		$output = get_array_param($params,'output',FALSE,'bool');
		$save_path = get_array_param($params,'save_path',NULL,'is_notempty_string');
		$file_name = get_array_param($params,'file_name',NULL,'is_notempty_string');

		require_once(NApp::app_path().'/lib/phpexcel/PHPExcel.php');
		require_once(NApp::app_path().'/lib/phpexcel/PHPExcel/Cell/AdvancedValueBinder.php');
		require_once(NApp::app_path().'/lib/phpexcel/PHPExcel/IOFactory.php');
		//$PHPExcelCached = PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_memcached,array('memcacheServer'=>'localhost','memcachePort'=>11211,'cacheTime'=>600));
		// NApp::_DLog((int)$PHPExcelCached,'$PHPExcelCached');
		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());
		$this->php_excel = new PHPExcel();
		$this->php_excel->getDefaultStyle()->getFont()->setName('Calibri');
		$this->php_excel->getDefaultStyle()->getFont()->setSize(10);
		$sheet_index = -1;
		$sheet_name = '';

		foreach($params['layouts'] as $layout) {
			if(!is_array($layout) || !array_key_exists('columns',$layout) || !count($layout['columns']) || !array_key_exists('data',$layout)) {
				$this->php_excel = NULL;
				throw new \PAF\AppException('ExcelExport: Invalid sheet parameters !',E_ERROR,1);
			}//if(!is_array($layout) || !array_key_exists('columns',$layout) || !count($layout['columns']) || !array_key_exists('data',$layout))
			$c_sheet_name = get_array_param($layout,'sheet_name','','is_string');
			if($sheet_index<0 || $c_sheet_name!=$sheet_name) {
				$sheet_index++;
				$row_no = 1;
				$this->total_row = array();
				if($sheet_index>0 || $this->php_excel->getSheetCount()==0) { $this->php_excel->createSheet(); }
				$this->php_excel->setActiveSheetIndex($sheet_index);
            	$active_sheet = $this->php_excel->getActiveSheet();
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
				$this->default_formats['border_std'] =  array('borders'=>array('allborders'=>array('style'=>PHPExcel_Style_Border::BORDER_THIN,'color'=>array('rgb'=>'000000'))));
				$this->default_formats['border_out'] = array('borders'=>array('outline'=>array('style'=>PHPExcel_Style_Border::BORDER_MEDIUM,'color'=>array('rgb'=>'000000'))));
			}//if(array_key_exists('with_borders',$layout) && $layout['with_borders'])
			$this->SetFormats(array_key_exists('formats',$layout) ? $layout['formats'] : NULL);
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
				if(get_array_param($column,'summarize',FALSE,'bool')) {
					$this->total_row[$col_no] = array(
						'value'=>0,
						'key'=>$k,
						'type'=>strtolower(get_array_param($column,'summarize_type','count','is_notempty_string')),
					);
				}//if(get_array_param($column,'summarize',FALSE,'bool'))
				$c_width = get_array_param($column,'width',get_array_param($column,'ewidth',NULL,'is_notempty_string'),'is_notempty_string');
				if($c_width && strpos($c_width,'%')===FALSE) {
					if(strpos($c_width,'px')!==FALSE) { $c_width = str_replace('px','',trim($c_width)); }
					if(is_numeric($c_width)) {
						$active_sheet->getColumnDimension($this->IndexToColumn($col_no))->setWidth($c_width/10);
					}//if(is_numeric($c_width))
				}//if($c_width && strpos($c_width,'%')===FALSE)
				if(array_key_exists('header_format',$column) && $column['header_format']) {
					$this->ApplyStyleArray($active_sheet,$this->IndexToColumn($col_no).$row_no,$column['header_format']);
				}//if(array_key_exists('header_format',$column) && $column['header_format'])
				$active_sheet->setCellValue($this->IndexToColumn($col_no).$row_no,get_array_param($column,'label',$col_no,'is_string'));
			}//END foreach
			if(!is_array($layout['data']) || !count($layout['data'])) { continue; }
			foreach($layout['data'] as $data_row) {
				if(!is_array($data_row) || !count($data_row)) { continue; }
				$col_no = 0;
				$row_no++;
				$row_format = (array_key_exists('format_row_func',$layout) && $layout['format_row_func']) ? $this->$layout['format_row_func']($data_row) : array();
				foreach($layout['columns'] as $column) {
					$col_no++;
					$col_format_name = get_array_param($column,'format',NULL,'is_notempty_string');
					if($col_format_name) {
						$col_format_name .= '_'.substr(get_array_param($column,'halign','center','is_notempty_string'),0,1);
					} else {
						$col_format_name = get_array_param($column,'eformat','standard','is_notempty_string');
					}//if($col_format_name)
					$col_def_format = get_array_param($this->formats,$col_format_name,array(),'is_array');

					$col_custom_format = (array_key_exists('format_func',$column) && $column['format_func']) ? $this->$column['format_func']($data_row,$column) : array();
					$col_format = array_merge((is_array($col_def_format) ? $col_def_format : array()),(is_array($row_format) ? $row_format : array()),(is_array($col_custom_format) ? $col_custom_format : array()));
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
					if(!get_array_param($layout['columns'][$v['key']],'summarize',FALSE,'bool')) { continue; }
					$col_def_format = get_array_param($layout['columns'][$v['key']],'format','standard','is_notempty_string');
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
		$this->php_excel->setActiveSheetIndex(0);
		if(\ErrorHandler::HasErrors()) { \ErrorHandler::ShowErrors(); return; }
		//NApp::_DLog((memory_get_peak_usage(true)/1024/1024).' MB','Peak memory usage');
		// throw new Exception('Done at:'.date('Y-m-d H:i:s').' >> '.(memory_get_peak_usage(true)/1024/1024).' MB', 1);
		// die('<br>DONE!!!');
		if($output) { $this->OutputData($file_name,$save_path,$excel_version); }
	}//END public function __construct
	/**
	 * Set table cell value
	 *
	 * @return void
	 * @access protected
	 */
	protected function SetCellValue(&$sheet,$row,$col,$column,$data,$return = FALSE) {
		if(array_key_exists('format_value_func',$column) && $column['format_value_func']) {
			$col_value = $this->$column['format_value_func']($data,$column);
		} elseif(array_key_exists('format_formula_func',$column) && $column['format_formula_func']) {
			$col_value = $this->$column['format_formula_func']($row,$col,$data,$column);
		} else {
			if($this->pre_processed_data) {
				$col_name = get_array_param($column,'name',NULL,'is_string');
				$col_value = get_array_param($data,$col_name,get_array_param($column,'default_value',NULL,'isset'),'isset');
			} else {
				$col_value = '';
				$dbfield = $column['db_field'];
				if(!is_array($column['db_field'])) { $dbfield = array($column['db_field']); }
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
			}//if($this->pre_processed_data)
		}//if(array_key_exists('format_value_func',$column) && $column['format_value_func'])
		$col_value = (isset($col_value) ? $col_value : '');
		if(count($this->total_row) && get_array_param($column,'summarize',FALSE,'bool')) {
			if(get_array_param($column,'values_total_row',FALSE,'bool')) {
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
			}//if(get_array_param($column,'values_total_row',FALSE,'bool'))
		}//if(count($this->total_row) && get_array_param($column,'summarize',FALSE,'bool'))
		if($return) { return $col_value; }
		if(array_key_exists('format_formula_func',$column) && $column['format_formula_func']) {
			$sheet->setCellValue($this->IndexToColumn($col).$row,$col_value);
		} else {
			$col = is_integer($col) ? $this->IndexToColumn($col) : $col;
			$v_dtype = is_numeric($col_value) ? 'numeric' : 'string';
			$data_type = get_array_param($column,'data_type',$v_dtype,'is_notempty_string');
			if($data_type=='date' || $data_type=='datetime' || $data_type=='date_obj' || $data_type=='datetime_obj') {
				if($dt_value = unixts2excel($col_value,NApp::$server_timezone,$this->timezone)) {
					$col_value = $dt_value;
					$data_type = 'datetime';
				} else {
					$data_type = 'string';
				}//if($dt_value = unixts2excel($col_value,NApp::$server_timezone,$this->timezone))
			} elseif($data_type=='numeric' && $v_dtype=='string') {
				$data_type = 'string';
			}//if($data_type=='date' || $data_type=='datetime' || $data_type=='date_obj' || $data_type=='datetime_obj')
			if($data_type=='string') {
				$col_value .= get_array_param($column,'sufix','','is_string');
			}//if($data_type=='string')
			$sheet->getCell($col.$row)->setValueExplicit($col_value,$this->GetDataType($data_type));
		}//if(array_key_exists('format_formula_func',$column) && $column['format_formula_func'])
	}//END protected function SetCellValue
	/**
	 * Sets formats to be used in current instance
	 *
	 * @param  array $formats Custom formats array
	 * @return array Returns current formats array
	 * @access protected
	 */
	protected function SetFormats($formats = array()) {
		if(!is_array($formats) || !count($formats)) {
			$this->formats = $this->default_formats;
		} else {
			$this->formats = array_merge($this->default_formats,$formats);
		}//if(!is_array($formats) || !count($formats))
	}//END protected function SetFormats
	/**
	 * Get column name in excel format (literal)
	 *
	 * @param  int $index Index of a column
	 * @return string Returns column name in excel format
	 * @access protected
	 */
	protected function IndexToColumn($index) {
		if(!is_int($index)) { return NULL; }
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
	 * Convert data type to PHPExcel format
	 *
	 * @param  string $type Data type
	 * @return int Returns data type in PHPExcel format
	 * @access protected
	 */
	protected function GetDataType($type) {
		switch($type) {
			case 'numeric':
			case 'date':
			case 'datetime':
				return PHPExcel_Cell_DataType::TYPE_NUMERIC;
			case 'boolean':
				return PHPExcel_Cell_DataType::TYPE_BOOL;
			case 'string':
			default:
				return PHPExcel_Cell_DataType::TYPE_STRING;
		}//switch($type)
	}//END protected function GetDataType
	/**
	 * Convert border style string to PHPExcel format
	 *
	 * @param  string $type Border style name
	 * @return int Returns border style in PHPExcel format
	 * @access protected
	 */
	protected function GetBorderStyle($type) {
		switch($type) {
			case 'dashdot':
				return PHPExcel_Style_Border::BORDER_DASHDOT;
			case 'dashdotdot':
				return PHPExcel_Style_Border::BORDER_DASHDOTDOT;
			case 'dashed':
				return PHPExcel_Style_Border::BORDER_DASHED;
			case 'dotted':
				return PHPExcel_Style_Border::BORDER_DOTTED;
			case 'double':
				return PHPExcel_Style_Border::BORDER_DOUBLE;
			case 'hair':
				return PHPExcel_Style_Border::BORDER_HAIR;
			case 'medium':
				return PHPExcel_Style_Border::BORDER_MEDIUM;
			case 'mediumdashdot':
				return PHPExcel_Style_Border::BORDER_MEDIUMDASHDOT;
			case 'mediumdashdotdot':
				return PHPExcel_Style_Border::BORDER_MEDIUMDASHDOTDOT;
			case 'mediumdasher':
				return PHPExcel_Style_Border::BORDER_MEDIUMDASHED;
			case 'bordernone':
				return PHPExcel_Style_Border::BORDER_NONE;
			case 'slantdashdot':
				return PHPExcel_Style_Border::BORDER_SLANTDASHDOT;
			case 'borderthick':
				return PHPExcel_Style_Border::BORDER_THICK;
			case 'borderthin':
				return PHPExcel_Style_Border::BORDER_THIN;
			default:
				return PHPExcel_Style_Border::BORDER_NONE;
		}//switch($type)
	}//END protected function GetBorderStyle
	/**
	 * Convert border style string to PHPExcel format
	 *
	 * @param  string $type Alignment style name
	 * @return int Returns alignment style in PHPExcel format
	 * @access protected
	 */
	protected function GetAlignmentStyle($type) {
		switch($type) {
			case 'h_center':
				return PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
			case 'h_venter_continuous':
				return PHPExcel_Style_Alignment::HORIZONTAL_CENTER_CONTINUOUS;
			case 'h_general':
				return PHPExcel_Style_Alignment::HORIZONTAL_GENERAL;
			case 'h_justify':
				return PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY;
			case 'h_left':
				return PHPExcel_Style_Alignment::HORIZONTAL_LEFT;
			case 'h_right':
				return PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
			case 'v_bottom':
				return PHPExcel_Style_Alignment::VERTICAL_BOTTOM;
			case 'v_center':
				return PHPExcel_Style_Alignment::VERTICAL_CENTER;
			case 'v_justify':
				return PHPExcel_Style_Alignment::VERTICAL_JUSTIFY;
			case 'v_top':
				return PHPExcel_Style_Alignment::VERTICAL_TOP;
			default:
				return PHPExcel_Style_Alignment::HORIZONTAL_GENERAL;
		}//switch($type)
	}//END protected function GetAlignmentStyle
	/**
	 * Apply style array to a range of cells
	 *
	 * @param  object $sheet Target sheet instance
	 * @param  string $range Target cells range in excel format
	 * @param  array  $style Style array to be applied
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access protected
	 */
	protected function ApplyStyleArray(&$sheet,$range,$style) {
		if(!is_object($sheet) || !$range || !$style) { return FALSE; }
		$style_arr = is_array($style) ? $style : $this->formats[$style];
		$fstyle = array();
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
					$fstyle['border']['top']['style'] = $this->GetBorderStyle($val);
					$fstyle['border']['bottom']['style'] = $style['border']['top']['style'];
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
		if($fill) { $sheet->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($fill); }
	    return TRUE;
	}//protected function ApplyStyleArray
	/**
	 * Outputs excel data to a file on disk or for download
	 *
	 * @param  string $file_name Target file name
	 * @param  string $path Target file path
	 * @param  string $excel_version Target excel version
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function OutputData($file_name,$path = NULL,$excel_version = NULL) {
		if(!is_object($this->php_excel)) { return FALSE; }
		$file = strlen($file_name) ? $file_name : date('YmdHis');
		switch($excel_version) {
			case 'Excel5':
				$version = 'Excel5';
				$header_content_type = 'Content-Type: application/vnd.ms-excel';
				$file .= '.xls';
				break;
			default:
				$version = 'Excel2007';
				$header_content_type = 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				$file .= '.xlsx';
				break;
		}//switch($excel_version)
		$this->php_excel->setActiveSheetIndex(0);
		$writer = PHPExcel_IOFactory::createWriter($this->php_excel,$version);
        if($path) {
        	$writer->save($path.$file);
			return TRUE;
		}//if($path)
    	header('Content-Description: File Transfer');
		header($header_content_type);
		header('Content-Disposition: attachment; filename='.$file);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: max-age=0, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
        $writer->save('php://output');
        return TRUE;
	}//END public function OutputData
	/**
	 * Convert datetime value to excel format
	 *
	 * @param  array  $data Data row array
	 * @param  array $column Column configuration array
	 * @return numeric Returns timestamp in excel format
	 * @access protected
	 */
	protected function FormatDateTimeValue($data,$column) {
		if(!isset($data[$column['db_field']])) { return NULL; }
		if(is_object($data[$column['db_field']])) {
			$dt = $data[$column['db_field']];
			$value = 25569 + $dt->getTimestamp() / 86400;
			return $value;
		} elseif(is_string($data[$column['db_field']]) && !strlen($data[$column['db_field']])) {
			$dt = new DateTime($data[$column['db_field']]);
			$value = 25569 + $dt->getTimestamp() / 86400;
			return $value;
		}//if(is_object($data[$column['db_field']]))
		return NULL;
	}//END protected function FormatDateTimeValue
}//END class ExcelExport
?>