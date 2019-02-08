<?php
/**
 * ExcelImport class file
 * Class used for reading data from excel files
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NETopes\Core\DataHelpers;
use NETopes\Core\Validators\Validator;
use NETopes\Core\AppException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use NApp;
/**
 * ExcelImport class
 * Class used for reading data from excel files
 * @package  NETopes\Core\Data
 */
class ExcelImport {
	/**
	 * @var    bool Sanitize header row
	 */
    protected $sanitizeHeader = TRUE;
	/**
	 * @var    string Decimal separator
	 */
	protected $dsep = NULL;
	/**
	 * @var    string Decimal group separator
	 */
    protected $gsep = NULL;
	/**
	 * @var    array PHP Spreadsheet accepted file types
	 */
	protected $file_types = array('xlsx'=>'Xlsx','xls'=>'Xls','ods'=>'Ods','csv'=>'Csv','xml'=>'Xml'/*,'html'=>'Html','htm'=>'Html'*/);
	/**
	 * @var    array List of fields to be read
	 */
	protected $fields = NULL;
	/**
	 * @var    \PhpOffice\PhpSpreadsheet\Spreadsheet PhpSpreadsheet object instance
	 */
    protected $spreadsheet = NULL;
	/**
	 * @var    \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet PhpSpreadsheet sheet object instance
	 */
	protected $sheet = NULL;
	/**
	 * @var    bool Flag indicating if read data should be send to a data adapter
	 */
	protected $send_to_db = FALSE;
	/**
	 * @var    string Data adapter name
	 */
	public $ds_name = NULL;
	/**
	 * @var    string Data adapter method
	 */
	public $ds_method = NULL;
	/**
	 * @var    array Data adapter parameters array
	 */
	public $ds_params = NULL;
	/**
	 * @var    array An array containing data read from the import file
	 */
	public $data = NULL;
	/**
	 * description
	 * @param array $fields
	 * @param array $params Parameters object (instance of [Params])
	 * @return void
	 * @throws \NETopes\Core\AppException
	 */
    public function __construct(array $fields,array $params = []) {
		if(!count($fields)) { throw new AppException('Invalid ExcelImport fields list!',E_ERROR,1); }
		$this->fields = $fields;
		$this->sanitizeHeader = get_array_value($params,'sanitize_header',TRUE,'bool');
        $this->dsep = get_array_value($params,'decimal_separator',NApp::GetParam('decimal_separator'),'is_string');
		$this->gsep = get_array_value($params,'group_separator',NApp::GetParam('group_separator'),'is_string');
		$this->ds_name = get_array_value($params,'ds_name','','is_notempty_string');
		$this->ds_method = get_array_value($params,'ds_method','','is_notempty_string');
		$this->ds_params = get_array_value($params,'ds_params',[],'is_array');
		$this->send_to_db = strlen($this->ds_name) && strlen($this->ds_method) && DataProvider::MethodExists($this->ds_name,$this->ds_method);
		ini_set('max_execution_time',7200);
		ini_set('max_input_time',-1);
	}//END public function __construct
	/**
	 * description
	 * @param string      $file
	 * @param array       $params Parameters object (instance of [Params])
	 * @param null|string $file_type
	 * @return void
	 * @throws \NETopes\Core\AppException
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
	 */
	public function ProcessFile(string $file,array $params = [],?string $file_type = NULL): void {
		if(!$file || !file_exists($file)) { throw new AppException('Invalid input file!',E_ERROR,1); }
		if(strlen($file_type)) {
			$file_type = strtolower($file_type);
		} else {
			$file_type = strtolower(get_array_value($params,'file_type','','is_string'));
		}//if(strlen($file_type))
		if(!strlen($file_type) && strpos($file,'.')!==FALSE) {
			$file_type = strtolower(substr($file,strpos($file,'.')+1));
		}//if(!strlen($file_type))
		if(!strlen($file_type)) { throw new AppException('Invalid input file type!',E_ERROR,1); }
		if(!in_array($file_type,array_keys($this->file_types))) { throw new AppException('Unsupported file type!',E_ERROR,1); }
		$sheet_index = get_array_value($params,'sheet_index',0,'is_not0_numeric');
		$header_row = get_array_value($params,'header_row',1,'is_not0_numeric');
		$start_row = get_array_value($params,'start_row',2,'is_not0_numeric');
		$max_rows = get_array_value($params,'max_rows',-1,'is_numeric');
		// $this->spreadsheet = IOFactory::load($file);
		$reader = IOFactory::createReader($this->file_types[$file_type]);
		$this->spreadsheet = $reader->load($file);
        $this->spreadsheet->setActiveSheetIndex($sheet_index);
        $this->sheet = $this->spreadsheet->getActiveSheet();
		// Read header row and associate fields to columns
		$hrow = $this->sheet->getRowIterator($header_row)->current();
		foreach($hrow->getCellIterator() as $cell) {
		    if($this->sanitizeHeader) {
                $colname = strtolower(DataHelpers::normalizeString($cell->getValue(),TRUE,FALSE));
            } else {
			$colname = strtolower(trim($cell->getValue()));
		    }//if($this->sanitizeHeader)
			if(!array_key_exists($colname,$this->fields)) { continue; }
			$this->fields[$colname]['column'] = $cell->getColumn();
		}//END foreach
		// Read data line by line
		foreach($this->fields as $k=>$v) {
			if(isset($v['column']) && strlen($v['column'])) { continue; }
			if(get_array_value($v,'optional',FALSE,'bool')) {
				$this->fields[$k]['column'] = NULL;
				continue;
			}//if(get_array_value($v,'optional',FALSE,'bool'))
			throw new AppException("Invalid data: missing column [{$k}]!",E_USER_ERROR,1);
		}//END foreach
		$rowno = 0;
		if(!is_array($this->data)) { $this->data = []; }
		if($max_rows>0) {
			$end_row = $start_row + $max_rows - 1;
			foreach($this->sheet->getRowIterator($start_row,$end_row) as $row) { $this->ReadLine($row,$rowno); }
		} else {
			foreach($this->sheet->getRowIterator($start_row) as $row) { $this->ReadLine($row,$rowno); }
		}//if($max_rows>0)
	}//END public function ProcessFile
	/**
	 * description
	 * @param $row
	 * @param $rowno
	 * @return void
	 */
    protected function ReadLine(Row &$row,int &$rowno): void {
    	$rdata = array('_has_error'=>0,'_error'=>'','_rowno'=>$row->getRowIndex());
		$rowno++;
		try {
			$k = 0;
			$eno = 0;
			foreach($this->fields as $k=>$v) {
	    		$val = $v['column'] ? $this->sheet->getCell($v['column'].$rdata['_rowno'])->getValue() : NULL;
				$cell = $v['column'].$rdata['_rowno'];
				if(!get_array_value($v,'optional',FALSE,'bool') && (!isset($val) || !strlen($val))) {
					$rdata['_has_error'] = 1;
					$rdata['_error'] .= "Invalid value at column [{$k}], cell [{$cell}]! ";
				} else {
					$rdata[$k] = $this->FormatValue($val,$v);
					if(!isset($rdata[$k])) { $eno++; }
				}//if(!get_array_value($v,'optional',FALSE,'bool') && (!isset($val) || !strlen($val)))
			}//END foreach
			if(count($this->fields)==$eno) { return; }
			$this->ProcessDataRow($rdata);
		} catch(\Exception $e) {
			$rdata['_has_error'] = 1;
			$rdata['_error'] .= " Exception: ".$e->getMessage()." at column [{$k}], row [{$rdata['_rowno']}]!";
		}//END try
		$this->data[] = $rdata;
	}//END protected function ReadLine
	/**
	 * description
	 * @param mixed $value
	 * @param array $field
	 * @return mixed
	 */
    protected function FormatValue($value,array $field) {
		$format_value_func = get_array_value($field,'format_value_func',NULL,'is_notempty_string');
		if($format_value_func && method_exists($this,$format_value_func)) {
			return $this->$format_value_func($value,$field);
		}//if($format_value_func && method_exists($this,$format_value_func))
	    $format = get_array_value($field,'format',get_array_value($field,'type','','is_string'),'is_string');
		$validation = get_array_value($field,'validation','isset','is_notempty_string');
		return Validator::ValidateValue($value,NULL,$validation,$format);
	}//END protected function FormatValue
	/**
	 * description
	 * @param array $row
	 * @return bool
	 */
    protected function ProcessDataRow(array &$row): bool {
    	if(!$this->send_to_db || !count($row) || !isset($row['_has_error']) || $row['_has_error']==1) { return FALSE; }
		try {
			$lparams = $this->ds_params;
			foreach($this->fields as $k=>$v) {
				$da_param = get_array_value($v,'ds_param','','is_string');
				if(!strlen($da_param) || !array_key_exists($da_param,$lparams)) { continue; }
				$lparams[$da_param] = get_array_value($row,$k,NULL,'isset');
			}//END foreach
			$result = DataProvider::GetArray($this->ds_name,$this->ds_method,$lparams);
			if(get_array_value($result,[0,'inserted_id'],0,'is_numeric')==0) {
				$row['_has_error'] = 1;
				$row['_error'] = 'unknown_item';
				return FALSE;
			}//if(get_array_value($result,0,0,'is_numeric','inserted_id')==0)
			return TRUE;
		} catch(AppException $e) {
			$row['_has_error'] = 1;
			$row['_error'] = $e->getMessage();
			NApp::Elog($e,'row['.$row['_rowno'].']');
			return FALSE;
		}//END try
	}//END protected function ProcessDataRow
}//END class ExcelImport