<?php
/**
 * PdfDocument class file
 *
 * PDF document generator that implements class PdfCreator
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Reporting;
use PAF\AppException;
use NETopes\Core\Data\DataProvider;
use NApp;
use Translate;
/**
 * General purpose PDF
 */
define('X_GENERAL_TYPE_PDF',0);
/**
 * General purpose PDF generated from HTML
 */
define('X_HTML_TYPE_PDF',1);
/**
 * Master-detail stocks document (e.g. invoice)
 */
define('X_STOCK_DOC_TYPE_PDF',2);
/**
 * Payment documents (e.g. receipt)
 */
define('X_PAY_DOC_TYPE_PDF',3);
/**
 * Reports
 */
define('X_REPORT_TYPE_PDF',4);
/**
 * PdfDocumentBase class
 *
 * PDF document generator that implements class PdfCreator
 *
 * @package  NETopes\Reporting
 * @access   public
 */
class PdfDocument {
	/**
	 * @var    array An array containing default formats
	 * @access protected
	 */
	protected $default_formats = array(
		'header'=>array('padding'=>'0 2px','font'=>'helvetica','font_size'=>8,'align_h'=>'h_center','align_v'=>'v_middle','bold'=>TRUE,'background_color'=>'F0F0F0'),
		'footer'=>array('font'=>'helvetica','font_size'=>8,'align_h'=>'h_center','align_v'=>'v_middle','bold'=>TRUE,'background_color'=>'DBEEF3'),
		'standard'=>array('font'=>'helvetica','font_size'=>8,'align_h'=>'h_left','align_v'=>'v_middle'),
		'left'=>array('align_h'=>'h_left'),
		'center'=>array('align_h'=>'h_center'),
		'right'=>array('align_h'=>'h_right'),
	);
	/**
	 * @var    array An array containing default footer parameters
	 * @access public
	 */
	public $footer_params = FALSE;
	/**
	 * @var    array Borders settings array
	 * @access protected
	 */
	protected $border_settings = array('LTRB'=>array('width'=>0.1));
	//, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)
	/**
	 * @var    bool Flag indicating if borders are used
	 * @access protected
	 */
	protected $with_borders = FALSE;
	/**
	 * @var    array An array containing all instance formats
	 * @access protected
	 */
	protected $formats = [];
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
	 * @var    string Language code
	 * @access protected
	 */
	protected $langcode = NULL;
	/**
	 * @var    string Document HTML data
	 * @access protected
	 */
	protected $orientation = 'P';
	/**
	 * @var    string Document font
	 * @access protected
	 */
	protected $font = 'freesans';
	/**
	 * @var    string Document HTML data
	 * @access protected
	 */
	protected $page_size = 'A4';
	/**
	 * @var    array An array containing extra parameters or extra data
	 * @access protected
	 */
	protected $params = NULL;
	/**
	 * @var    int Document type
	 * @access public
	 */
	public $type = X_GENERAL_TYPE_PDF;
	/**
	 * @var    bool Unicode TCPDF initialization value
	 * @access public
	 */
	public $unicode = TRUE;
	/**
	 * @var    string Charset TCPDF initialization value
	 * @access public
	 */
	public $charset = 'UTF-8';
	/**
	 * @var    object TCPDF instance
	 * @access protected
	 */
	public $pdf = NULL;
	/**
	 * @var    string PDF file name (including extension)
	 * @access public
	 */
	public $file_name = NULL;
	/**
	 * @var    string HTML data to be write in the PDF
	 * @access public
	 */
	public $html_data = NULL;
	/**
	 * @var    array Class dynamic properties array
	 * @access private
	 */
	private $pdata = [];
	/**
	 * Class dynamic getter method
	 *
	 * @param  string $name The name o the property
	 * @return mixed Returns the value of the property
	 * @access public
	 */
	public function __get($name) {
		return (is_array($this->pdata) && array_key_exists($name,$this->pdata)) ? $this->pdata[$name] : NULL;
	}//END public function __get
	/**
	 * Class dynamic setter method
	 *
	 * @param  string $name The name o the property
	 * @param  mixed  $value The value to be set
	 * @return void
	 * @access public
	 */
	public function __set($name,$value) {
		if(!is_array($this->pdata)) { $this->pdata = []; }
		$this->pdata[$name] = $value;
	}//END public function __set
	/**
	 * PdfDocumentBase class constructor
	 *
	 * @param  array $params Constructor parameters array
	 * @throws \PAF\AppException
	 * @return void
	 * @access public
	 */
	public function __construct($params = NULL) {
		$this->decimal_separator = NApp::_GetParam('decimal_separator');
		$this->group_separator = NApp::_GetParam('group_separator');
		$this->date_separator = NApp::_GetParam('date_separator');
		$this->time_separator = NApp::_GetParam('time_separator');
		$this->langcode = NApp::_GetLanguageCode();
		$this->file_name = date('YmdHis').'.pdf';
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) {
				if(!property_exists($this,$k)) { continue; }
				$this->$k = $v;
				unset($params[$k]);
			}//END foreach
			$this->params = $params ? $params : NULL;
		}//if(is_array($params) && count($params))
		$this->_Init();
	}//END public function __construct
	/**
	 * PDF class initializer
	 *
	 * @return void
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function _Init() {
		set_time_limit(1800);
        $this->pdf = new PdfCreator($this->orientation,'mm',$this->page_size,$this->unicode,$this->charset);
		$this->pdf->SetCreator('NETopes');
        $this->pdf->SetAuthor(NApp::_GetAppName());
		switch($this->type) {
			case X_STOCK_DOC_TYPE_PDF:
			case X_PAY_DOC_TYPE_PDF:
				//$this->pdf->SetTitle(isset($pdfTitle)?$pdfTitle:'');
        		//$this->pdf->SetSubject(isset($pdfSubject)?$pdfSubject:'');
        		$this->pdf->SetMargins(8,10,8,TRUE);
				$this->x = 8;
				$this->y = 10;
				//set auto page breaks
				$this->pdf->SetAutoPageBreak(TRUE,10);
				//set image scale factor
				$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				break;
			case X_REPORT_TYPE_PDF:
			case X_HTML_TYPE_PDF:
			case X_GENERAL_TYPE_PDF:
				//$this->pdf->SetTitle(isset($pdfTitle)?$pdfTitle:'');
        		//$this->pdf->SetSubject(isset($pdfSubject)?$pdfSubject:'');
        		$this->pdf->SetMargins(10,10,10,TRUE);
				$this->x = 10;
				$this->y = 10;
				//set auto page breaks
				$this->pdf->SetAutoPageBreak(TRUE,10);
				//set image scale factor
				$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				break;
			default:
				throw new AppException('Invalid PDF type!',E_ERROR,1,basename(__FILE__));
				break;
		}//END switch
	}//END protected function _Init
	/**
	 * description
	 *
	 * @param bool  $new
	 * @param string|null $orientation
	 * @param string|null $page_size
	 * @return void
	 * @access public
	 */
	protected function AddPage($new = FALSE,$orientation = NULL,$page_size = NULL) {
		if($this->pages_no && $new!==TRUE) { return; }
		if($this->pages_no) { $this->SetPageFooter(); }
		$this->pages_no++;
		$this->pdf->AddPage($orientation ? $orientation : $this->orientation,$page_size ? $page_size : $this->page_size);
	}//END protected function AddPage
	/**
	 * description
	 *
	 * @param string|null $orientation
	 * @param string|null $page_size
	 * @return void
	 * @access public
	 */
	protected function StartPageGroup($newPage = FALSE,$orientation = NULL,$page_size = NULL) {
		$this->pdf->startPageGroup();
		if($newPage) { $this->AddPage(TRUE,$orientation,$page_size); }
	}//END protected function StartPageGroup
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetHeader() {
		$this->pdf->setPrintHeader(FALSE);
		$this->pdf->SetHeaderMargin(0);
		return FALSE;
	}//END protected function SetHeader
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetFooter() {
		if(!$this->footer_params) {
			$this->pdf->setPrintFooter(FALSE);
			$this->pdf->SetFooterMargin(0);
			return FALSE;
		}//if(!$this->footer_params)
		$this->pdf->setPrintFooter(TRUE);
		$this->pdf->SetFooterMargin(abs(get_array_value($this->footer_params,'bottom_margin',12,'is_numeric')));
		$this->pdf->custom_footer = TRUE;
		$this->pdf->custom_footer_params = $this->footer_params;
		return TRUE;
	}//END protected function SetFooter
	/**
	 * description
	 *
	 * @param int|null $docId Data key (ID)
	 * @return void
	 * @access public
	 * @throws \PAF\AppException
	 */
	protected function LoadData(?int $docId): void {
		switch($this->type) {
			case X_STOCK_DOC_TYPE_PDF:
				if(!$docId) { throw new AppException('Invalid document!',E_ERROR,1,basename(__FILE__)); }
				$document = DataProvider::GetArray('Tran\Documents','GetItemData',['for_id'=>$docId]);
				if(!$document) { throw new AppException('Invalid document data!',E_ERROR,1,basename(__FILE__)); }
				$this->langcode = get_array_value($document,'lang_code',$this->langcode,'is_notempty_string');
				$this->decimal_separator = get_array_value($document,'decimal_separator',$this->decimal_separator,'is_notempty_string');
				$this->group_separator = get_array_value($document,'group_separator',$this->group_separator,'is_notempty_string');
				$this->date_separator = get_array_value($document,'date_separator',$this->date_separator,'is_notempty_string');
				if(is_array($this->footer_params) && isset($this->footer_params['mask'])) {
					$this->footer_params['mask'] = Translate::Get('dlabel_page',$this->langcode).' {{page}} '.Translate::Get('dlabel_from',$this->langcode).' {{pages_no}}';
				}//if(is_array($this->footer_params) && isset($this->footer_params['mask']))
				$id_entity = get_array_value($document,'id_entity',NULL,'is_integer');
				$id_location = get_array_value($document,'id_location',NULL,'is_integer');
				if(!$id_entity || !$id_location) { throw new AppException('Invalid entity!',E_ERROR,1,basename(__FILE__)); }
				$lines = DataProvider::GetArray('Tran\Documents','GetItemLines',array('document_id'=>$docId,'taxes_as_lines'=>1,'discount_as_lines'=>1));
				if(!$lines) { throw new AppException('Invalid document lines!',E_ERROR,1,basename(__FILE__)); }
				$this->document_data = is_array($document) && count($document) ? $document : NULL;
				$this->details_data = is_array($lines) && count($lines) ? $lines : NULL;
				break;
			case X_PAY_DOC_TYPE_PDF:
				if(!$docId) { throw new AppException('Invalid document!',E_ERROR,1,basename(__FILE__)); }
				$document = DataProvider::GetArray('Tran\Payments','GetItemData',['for_id'=>$docId]);
				if(!$document) { throw new AppException('Invalid document data!',E_ERROR,1,basename(__FILE__)); }
				$this->langcode = get_array_value($document,'lang_code',$this->langcode,'is_notempty_string');
				$this->decimal_separator = get_array_value($document,'decimal_separator',$this->decimal_separator,'is_notempty_string');
				$this->group_separator = get_array_value($document,'group_separator',$this->group_separator,'is_notempty_string');
				$this->date_separator = get_array_value($document,'date_separator',$this->date_separator,'is_notempty_string');
				if(is_array($this->footer_params) && isset($this->footer_params['mask'])) {
					$this->footer_params['mask'] = Translate::Get('dlabel_page',$this->langcode).' {{page}} '.Translate::Get('dlabel_from',$this->langcode).' {{pages_no}}';
				}//if(is_array($this->footer_params) && isset($this->footer_params['mask']))
				$id_entity = get_array_value($document,'id_entity',NULL,'is_integer');
				$id_location = get_array_value($document,'id_location',NULL,'is_integer');
				if(!$id_entity || !$id_location) { throw new AppException('Invalid entity!',E_ERROR,1,basename(__FILE__)); }
				$this->document_data = is_array($document) && count($document) ? $document : NULL;
				break;
			case X_REPORT_TYPE_PDF:
			case X_HTML_TYPE_PDF:
			case X_GENERAL_TYPE_PDF:
			default:
				throw new AppException('Not implemented!',E_ERROR,1,basename(__FILE__));
				break;
		}//END switch
	}//END protected function LoadData
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetDocument() {
		return FALSE;
	}//END protected function SetDocument
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetDocumentHeader() {
		return FALSE;
	}//END protected function SetDocumentHeader
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetPageHeader() {
		return FALSE;
	}//protected function SetPageHeader
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetPageFooter() {
		return FALSE;
	}//protected function SetPageFooter
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetDocumentLines() {
		return $this->SetPageHeader();
	}//END protected function SetDocumentLines
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetDocumentFooter() {
		return FALSE;
	}//END protected function SetDocumentFooter
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetDocumentContent() {
		if($this->type==X_HTML_TYPE_PDF && is_string($this->html_data) && strlen($this->html_data)) {
			// set default monospaced font
			$this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
			return TRUE;
		}//if($this->type==X_HTML_TYPE_PDF && is_string($this->html_data) && strlen($this->html_data))
		return FALSE;
	}//END protected function SetDocumentContent
	/**
	 * description
	 *
	 * @return bool
	 * @access public
	 */
	protected function SetContentFromHtml() {
		$html = [];
		if(is_array($this->html_data)) {
			if(!count($this->html_data)) { return FALSE; }
			$html = $this->html_data;
		} elseif(is_string($this->html_data)) {
			if(!strlen($this->html_data)) { return FALSE; }
			$html[] = $this->html_data;
		} else {
			return FALSE;
		}//if(is_array($this->html_data))
		if(!count($html)) { return FALSE; }

		// set default monospaced font
		$this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		// set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE,PDF_MARGIN_BOTTOM);
		foreach($html as $k=>$v) {
			if(!is_string($v) || !strlen($v)) { continue; }
			if($k>0) { $this->pdf->lastPage(); }
			$this->pdf->setFont('freeserif');
			// add a page
			$this->pdf->AddPage();
			// output the HTML content
			$this->pdf->writeHTML($v,TRUE,FALSE,TRUE,FALSE,'');
		}//END foreach
		return TRUE;
	}//END protected function SetContentFromHtml
	/**
	 * description
	 *
	 * @return void
	 * @access public
	 * @throws \PAF\AppException
	 */
	protected function SetContent() {
		switch($this->type) {
			case X_STOCK_DOC_TYPE_PDF:
				$this->SetDocumentHeader();
				$this->SetDocumentLines();
				$this->SetDocumentFooter();
				break;
			case X_PAY_DOC_TYPE_PDF:
				$this->SetDocument();
				break;
			case X_HTML_TYPE_PDF:
				$this->SetContentFromHtml();
				break;
			case X_GENERAL_TYPE_PDF:
				$this->SetDocumentContent();
				break;
			case X_REPORT_TYPE_PDF:
			default:
				throw new AppException('Not implemented!',E_ERROR,1,basename(__FILE__));
				break;
		}//END switch
	}//END protected function SetContent
	/**
	 * description
	 *
	 * @return void
	 * @throws \PAF\AppException
	 * @throws \Exception
	 * @access public
	 */
	protected function WriteData() {
		switch($this->type) {
			case X_HTML_TYPE_PDF:
				$this->SetContent();
				break;
			case X_STOCK_DOC_TYPE_PDF:
			case X_PAY_DOC_TYPE_PDF:
				$docId = get_array_value($this->params,'key',NULL,'isset');
				$docIds = [];
				if(is_array($docId)) {
					$docIds = $docId;
				} elseif(is_numeric($docId)) {
					$docIds = [$docId];
				}//if(is_array($docId))
				if(!count($docIds)) { throw new AppException('Invalid document identifier!',E_ERROR,1,basename(__FILE__)); }
				foreach($docIds as $i=>$docId) {
					$this->StartPageGroup($i>0);
					$this->LoadData($docId);
					$this->SetHeader();
					$this->SetFooter();
					$this->SetContent();
				}//END foreach
				break;
			case X_REPORT_TYPE_PDF:
			case X_GENERAL_TYPE_PDF:
				$this->SetHeader();
				$this->SetFooter();
				$this->SetContent();
				break;
			default:
				throw new AppException('Not implemented!',E_ERROR,1,basename(__FILE__));
				break;
		}//END switch
	}//END protected function WriteData
	/**
	 * description
	 *
	 * @param array $params Input parameters array
	 * @return mixed
	 * @access public
	 */
	public function Output($params = NULL) {
		try {
			$this->WriteData();
			if(get_array_value($params,'auto_print',FALSE,'bool')) { $this->pdf->IncludeJS('print(true);'); }
		} catch(\Exception $e){
			NApp::_Write2LogFile($e->getMessage(),'error');
			NApp::_Elog($e->getMessage());
			echo $e->getMessage();
			return FALSE;
		}//END try
		$file_name = get_array_value($params,'file_name',$this->file_name,'is_notempty_string');
		$output_type = get_array_value($params,'output_type','S','is_notempty_string');
		if(strtoupper($output_type)=='S') {
			if(get_array_value($params,'base64',FALSE,'bool')) { return base64_encode($this->pdf->Output($file_name,$output_type)); }
			return $this->pdf->Output($file_name,$output_type);
		}//if(strtoupper($output_type)=='S')
		return $this->pdf->Output($file_name,$output_type);
	}//END public function Output
	/**
	 * description
	 *
	 * @param array $params Input parameters array
	 * @return void
	 * @access public
	 */
	public function Show($params = NULL) {
		if(!is_array($params)) { $params = []; }
		$params['output_type'] = 'I';
		$this->Output($params);
	}//END public function Show
// For Report type PDF
	/**
	 * description
	 *
	 * @return void
	 * @access public
	 */
	protected function SetReportContent() {
		/*$this->pdf->custom_footer = TRUE;
        $cline = 0;
		$first = TRUE;
		foreach($params['layouts'] as $layout) {
			if(!is_array($layout) || !array_key_exists('columns',$layout) || !count($layout['columns']) || !array_key_exists('data',$layout)) { throw new AppException('Invalid object parameters !',E_USER_ERROR,0,basename(__FILE__),__LINE__); }
			if(array_key_exists('with_borders',$layout)) { $this->with_borders = $layout['with_borders']; }
			$borders = $this->with_borders ? $this->border_settings : [];
			$this->SetFormats((array_key_exists('formats',$layout) ? $layout['formats'] : NULL));
			$default_width = get_array_value($layout,'default_width',20,'is_not0_numeric');
			$default_format = array_key_exists('default_format',$layout) ? (is_array($layout['default_format']) ? $layout['default_format'] : (array_key_exists($layout['default_format'],$this->formats) ? $this->formats[$layout['default_format']] : [])) : $this->formats['standard'];
			$header_format = array_key_exists('header_format',$layout) ? (is_array($layout['header_format']) ? $layout['header_format'] : (array_key_exists($layout['header_format'],$this->formats) ? $this->formats[$layout['header_format']] : [])) : $this->formats['header'];
			$this->pdf->custom_header = TRUE;
			$this->pdf->custom_header_params = array('type'=>'table','columns'=>$layout['columns'],'format'=>$header_format,'default_width'=>$default_width,'border'=>$borders);
			if($first || get_array_value($layout,'new_page',FALSE,'bool')) {
				$first = FALSE;
				$this->pdf->AddPage("P","A4");
			} else {
				$this->pdf->Cell(10,15,'',0,TRUE,'C',0,'',0,FALSE,'T','M');
				$this->pdf->SetCustomHeader(TRUE);
			}//if($first || get_array_value($layout,'new_page',FALSE,'bool'))
			if(!count($layout['data'])) { continue; }
			$set_totals = TRUE;
			foreach($layout['data'] as $data_row) {
				if(!is_array($data_row) || !count($data_row)) { continue; }
				$col_no = 0;
				foreach($layout['columns'] as $column) {
					$col_no++;
					if($set_totals && array_key_exists('total_row',$column) && $column['total_row']) { $this->total_row[$col_no] = 0; }
					$col_def_format = array_key_exists('format',$column) ? (is_array($column['format']) ? $column['format'] : (array_key_exists($column['format'],$this->formats) ? $this->formats[$column['format']] : [])) : [];
					$col_custom_format = (array_key_exists('format_func',$column) && $column['format_func']) ? $this->$column['format_func']($data_row,$column) : [];
					$cformat = array_merge($default_format,$col_def_format,$col_custom_format);
					$fr = $this->pdf->SetFormat($cformat);
					$w = get_array_value($column,'width',$default_width,'is_not0_numeric');
					$cvalue = $this->GetCellValue($data_row,$column,$col_no);
					$this->pdf->Cell($w,0,$cvalue,$borders,($col_no==count($layout['columns'])),$this->pdf->GetAlign($cformat),$fr['fc'],'',1,FALSE,'T',$this->pdf->GetAlign($cformat,'v'));
				}//END foreach
				if($set_totals) { $set_totals = FALSE; }
			}//END foreach
			// TODO: de adaugat la tabel linia de totaluri
		}//END foreach
		//die();*/
	}//END protected function SetReportContent
	/**
	 * description
	 *
	 * @param array $formats
	 * @return void
	 * @access protected
	 */
	protected function SetFormats($formats = []) {
		if(!is_array($formats) || !count($formats)) {
			$this->formats = $this->default_formats;
		} else {
			$this->formats = array_merge($this->default_formats,$formats);
		}//if(!is_array($formats) || !count($formats))
	}//END protected function SetFormats
	/**
	 * description
	 *
	 * @param $data
	 * @param $column
	 * @param $col
	 * @return string
	 * @access protected
	 */
	protected function GetCellValue($data,$column,$col) {
		if(array_key_exists('format_value_func',$column) && $column['format_value_func']) {
			$col_value = $this->$column['format_value_func']($data,$column);
		} else {
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
		}//if(array_key_exists('format_value_func',$column) && $column['format_value_func'])
		$col_value = (isset($col_value) ? $col_value : '');
		if(array_key_exists('total_row',$column) && $column['total_row']) {
			if(array_key_exists('value_total_row',$column) && $column['total_row']) {
				switch(strtolower($column['total_row'])) {
					case 'sum':
					case 'average':
						$this->total_row[$col] += is_numeric($col_value) ? $col_value : 0;
						break;
				  	case 'count':
						$this->total_row[$col] += $col_value ? 1 : 0;
						break;
				  	default:
						break;
				}//END switch
			} else {
				$this->total_row[$col] = TRUE;
			}//if(array_key_exists('value_total_row',$column) && $column['total_row'])
        }//if(array_key_exists('total_row',$column) && $column['total_row'])
		$col_value .= (array_key_exists('sufix',$column) && $column['sufix']) ? $column['sufix'] : '';
		return $col_value;
	}//END protected function GetCellValue
	/**
	 * @param $data
	 * @param $column
	 * @return null|string
	 */
	protected function NumberFormat0($data,$column) {
		if(is_array($column['dbfield'])) { return NULL; }
		return number_format($data[$column['dbfield']],0,$this->decimal_separator,$this->group_separator);;
	}//END protected function NumberFormat0
	/**
	 * @param $data
	 * @param $column
	 * @return null|string
	 */
	protected function NumberFormat2($data,$column) {
		if(is_array($column['dbfield'])) { return NULL; }
		return number_format($data[$column['dbfield']],2,$this->decimal_separator,$this->group_separator);
	}//END protected function NumberFormat2
}//END class PdfDocument
?>