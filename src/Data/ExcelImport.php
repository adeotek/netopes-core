<?php
/**
 * ExcelImport class file
 *
 * Class used for reading data from excel files
 *
 * @package    NETopes\DataImport
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Classes\Data;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
/**
 * ExcelImport class
 *
 * Class used for reading data from excel files
 *
 * @package  NETopes\DataImport
 * @access   public
 */
class ExcelImport {
	/**
	 * @var    string Decimal separator
	 * @access protected
	 */
	protected $dsep = NULL;
	/**
	 * @var    string Decimal group separator
	 * @access protected
	 */
    protected $gsep = NULL;
	/**
	 * @var    array PHPExcel accepted file types
	 * @access protected
	 */
	protected $file_types = array('xlsx'=>'Excel2007','xls'=>'Excel5','odc'=>'OOCalc','csv'=>'CSV');
	/**
	 * @var    array List of fields to be read
	 * @access protected
	 */
	protected $fields = NULL;
	/**
	 * @var    object PHPExcel object instance
	 * @access protected
	 */
    protected $excel = NULL;
	/**
	 * @var    object PHPExcel sheet object instance
	 * @access protected
	 */
	protected $sheet = NULL;
	/**
	 * @var    bool Flag indicating if read data should be send to a data adapter
	 * @access protected
	 */
	protected $send_to_db = FALSE;
	/**
	 * @var    string Data adapter name
	 * @access public
	 */
	public $da_name = NULL;
	/**
	 * @var    string Data adapter method
	 * @access public
	 */
	public $ds_method = NULL;
	/**
	 * @var    array Data adapter parameters array
	 * @access public
	 */
	public $ds_params = NULL;
	/**
	 * @var    array An array containing data read from the import file
	 * @access public
	 */
	public $data = NULL;
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access public
	 */
    public function __construct($fields,$params = array()) {
		if(!is_array($fields) || !count($fields)) { throw new Exception('Invalid ExcelImport fields list!',E_ERROR,1); }
		$this->fields = $fields;
        $this->dsep = get_array_param($params,'decimal_separator',NApp::_GetParam('decimal_separator'),'is_string');
		$this->gsep = get_array_param($params,'group_separator',NApp::_GetParam('group_separator'),'is_string');
		$this->da_name = get_array_param($params,'da_name','','is_notempty_string');
		$this->ds_method = get_array_param($params,'ds_method','','is_notempty_string');
		$this->ds_params = get_array_param($params,'ds_params',array(),'is_array');
		$this->send_to_db = strlen($this->da_name) && strlen($this->ds_method) && DataProvider::MethodExists($this->da_name,$this->ds_method);
		ini_set('max_execution_time',7200);
		ini_set('max_input_time',-1);
	}//END public function __construct
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access public
	 */
	public function ProcessFile($file,$params = array(),$file_type = NULL) {
		if(!$file || !file_exists($file)) { throw new Exception('Invalid input file!',E_ERROR,1); }
		$file_type = get_array_param($params,'file_type','','is_notempty_string');
		if(!strlen($file_type)) {
			if(strpos($file,'.')!==FALSE) {
				$fext = substr($file,strpos($file,'.')+1);
				$file_type = array_key_exists($fext,$this->file_types) ? $this->file_types : 'Excel2007';
			} else {
				$file_type = 'Excel2007';
			}//if(strpos($file,'.')!==FALSE)
		} else {
			$file_type = array_key_exists($file_type,$this->file_types) ? $this->file_types[$file_type] : (in_array($file_type,$this->file_types,TRUE) ? $file_type : 'Excel2007');
		}//if(!strlen($file_type))
		$sheet_index = get_array_param($params,'sheet_index',0,'is_not0_numeric');
		$header_row = get_array_param($params,'header_row',1,'is_not0_numeric');
		$start_row = get_array_param($params,'start_row',2,'is_not0_numeric');
		$max_rows = get_array_param($params,'max_rows',-1,'is_numeric');
		require_once(NApp::app_path().'/lib/phpexcel/PHPExcel.php');
		$this->excel = PHPExcel_IOFactory::load($file);
        $this->excel->setActiveSheetIndex($sheet_index);
        $this->sheet = $this->excel->getActiveSheet();
		//// NEW VERSION
		// $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		// $this->excel = $reader->load($file);
		// Read header row and associate fields to columns
		$hrow = $this->sheet->getRowIterator($header_row)->current();
		foreach($hrow->getCellIterator() as $cell) {
			$colname = strtolower(trim($cell->getValue()));
			if(!array_key_exists($colname,$this->fields)) { continue; }
			$this->fields[$colname]['column'] = $cell->getColumn();
			// $this->fields[$colname]['eindex'] = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
		}//END foreach
		// Read data line by line
		foreach($this->fields as $k=>$v) {
			if(isset($v['column']) && strlen($v['column'])) { continue; }
			if(get_array_param($v,'optional',FALSE,'bool')) {
				$this->fields[$k]['column'] = NULL;
				continue;
			}//if(get_array_param($v,'optional',FALSE,'bool'))
			throw new \PAF\AppException("Invalid data: missing column [{$k}]!",E_USER_ERROR,1);
		}//END foreach
		$rowno = 0;
		if(!is_array($this->data)) { $this->data = array(); }
		if($max_rows>0) {
			$end_row = $start_row + $max_rows - 1;
			foreach($this->sheet->getRowIterator($start_row,$end_row) as $row) { $this->ReadLine($row,$rowno); }
		} else {
			foreach($this->sheet->getRowIterator($start_row) as $row) { $this->ReadLine($row,$rowno); }
		}//if($max_rows>0)
	}//END public function ProcessFile
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access protected
	 */
    protected function ReadLine(&$row,&$rowno) {
    	$rdata = array('_has_error'=>0,'_error'=>'','_rowno'=>$row->getRowIndex());
		$rowno++;
		try {
			$eno = 0;
			foreach($this->fields as $k=>$v) {
	    		$val = $v['column'] ? $this->sheet->getCell($v['column'].$rdata['_rowno'])->getValue() : NULL;
				$cell = $v['column'].$rdata['_rowno'];
				if(!get_array_param($v,'optional',FALSE,'bool') && (!isset($val) || !strlen($val))) {
					$rdata['_has_error'] = 1;
					$rdata['_error'] .= "Invalid value at column [{$k}], cell [{$cell}]! ";
				} else {
					$rdata[$k] = $this->FormatValue($val,$v);
					if(!isset($rdata[$k])) { $eno++; }
				}//if(!get_array_param($v,'optional',FALSE,'bool') && (!isset($val) || !strlen($val)))
			}//END foreach
			if(count($this->fields)==$eno) { return; }
			$this->ProcessDataRow($rdata);
		} catch(Exception $e) {
			$rdata['_has_error'] = 1;
			$rdata['_error'] .= " Exception: ".$e->getMessage()." at column [{$k}], row [{$rdata['_rowno']}]!";
		}//END try
		$this->data[] = $rdata;
	}//END protected function ReadLine
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access protected
	 */
    protected function FormatValue($value,$field) {
		$format_value_func = get_array_param($field,'format_value_func',NULL,'is_notempty_string');
		if($format_value_func && method_exists($this,$format_value_func)) {
			return $this->$format_value_func($value,$field);
		}//if($format_value_func && method_exists($this,$format_value_func))
	    $format = get_array_param($field,'format',get_array_param($field,'type','','is_string'),'is_string');
		$validation = get_array_param($field,'validation','isset','is_notempty_string');
		return \NETopes\Core\Classes\App\Validator::ValidateParam($value,NULL,$validation,$format);
	}//END protected function FormatValue
	/**
	 * description
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @return void
	 * @access protected
	 */
    protected function ProcessDataRow(&$row) {
    	if(!$this->send_to_db || !is_array($row) || !count($row) || !isset($row['_has_error']) || $row['_has_error']==1) { return FALSE; }
		try {
			$lparams = $this->ds_params;
			foreach($this->fields as $k=>$v) {
				$da_param = get_array_param($v,'da_param','','is_string');
				if(!strlen($da_param) || !array_key_exists($da_param,$lparams)) { continue; }
				$lparams[$da_param] = get_array_param($row,$k,'null','isset');
			}//END foreach
			$result = DataProvider::GetArray($this->da_name,$this->ds_method,$lparams);
			if(get_array_param($result,0,0,'is_numeric','inserted_id')==0) {
				$row['_has_error'] = 1;
				$row['_error'] = 'unknown_item';
				return FALSE;
			}//if(get_array_param($result,0,0,'is_numeric','inserted_id')==0)
			return TRUE;
		} catch(\PAF\AppException $e) {
			$row['_has_error'] = 1;
			$row['_error'] = $e->getMessage();
			NApp::_Elog($e->getMessage(),'row['.$row['_rowno'].']');
			return FALSE;
		}//END try
	}//END protected function ProcessDataRow
}//END class ExcelImport
?>