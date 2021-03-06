<?php
/**
 * PDF Report class file
 * Used for generating PDF reports.
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Reporting;
use NETopes\Core\AppException;

/**
 * ClassName description
 * long_description
 *
 * @package  NETopes\Reporting
 */
class PdfReport {
    /**
     * @var    string Decimal separator
     */
    protected $decimal_separator=NULL;
    /**
     * @var    string Group separator
     */
    protected $group_separator=NULL;
    /**
     * @var    string Date separator
     */
    protected $date_separator=NULL;
    /**
     * @var    string Time separator
     */
    protected $time_separator=NULL;
    /**
     * @var    string Language code
     */
    protected $langcode=NULL;
    /**
     * @var    array An array containing extra params or extra data
     */
    protected $extra_params=NULL;
    /**
     * @var    array An array containing default formats
     */
    protected $default_formats=[
        'header'=>['padding'=>'0 2px','font'=>'helvetica','font_size'=>8,'align_h'=>'h_center','align_v'=>'v_middle','bold'=>TRUE,'background_color'=>'F0F0F0'],
        'footer'=>['font'=>'helvetica','font_size'=>8,'align_h'=>'h_center','align_v'=>'v_middle','bold'=>TRUE,'background_color'=>'DBEEF3'],
        'standard'=>['font'=>'helvetica','font_size'=>8,'align_h'=>'h_left','align_v'=>'v_middle'],
        'left'=>['align_h'=>'h_left'],
        'center'=>['align_h'=>'h_center'],
        'right'=>['align_h'=>'h_right'],
    ];
    protected $border_settings=['LTRB'=>['width'=>0.1/*, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)*/]];
    /**
     * @var    bool Flag indicating if borders are used
     */
    protected $with_borders=TRUE;
    /**
     * @var    array An array containing all instance formats
     */
    protected $formats=[];
    /**
     * @var    array An array containing table totals
     */
    protected $total_row=[];
    /**
     * @var    string The result string
     */
    protected $result='';
    /**
     * @var    object The TCPDF object
     */
    protected $pdf=NULL;

    /**
     * description
     *
     * @param array|null $params Parameters object
     * @return void
     */
    public function __construct(&$params=[]) {
        if(!is_array($params) || !count($params) || !array_key_exists('layouts',$params) || !is_array($params['layouts']) || !count($params['layouts'])) {
            throw new AppException('Invalid object parameters !',E_ERROR,1);
        }
        //reset($params);
        $this->decimal_separator=(array_key_exists('decimal_separator',$params) && $params['decimal_separator']) ? $params['decimal_separator'] : NApp::GetParam('decimal_separator');
        $this->group_separator=(array_key_exists('group_separator',$params) && $params['group_separator']) ? $params['group_separator'] : NApp::GetParam('group_separator');
        $this->date_separator=(array_key_exists('date_separator',$params) && $params['date_separator']) ? $params['date_separator'] : NApp::GetParam('date_separator');
        $this->time_separator=(array_key_exists('time_separator',$params) && $params['time_separator']) ? $params['time_separator'] : NApp::GetParam('time_separator');
        $this->langcode=(array_key_exists('lang_code',$params) && $params['lang_code']) ? $params['lang_code'] : NApp::GetLanguageCode();
        set_time_limit(3600);
        $this->pdf=new PdfCreator('P','mm','A4',TRUE);
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->setPrintHeader();
        $this->pdf->setPrintFooter();
        $this->pdf->SetMargins(10,14,5,TRUE);
        $this->pdf->SetHeaderMargin(11);
        $this->pdf->SetFooterMargin(8);
        //set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE,14);
        //set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->pdf->custom_footer=TRUE;
        $cline=0;
        $first=TRUE;
        foreach($params['layouts'] as $layout) {
            if(!is_array($layout) || !array_key_exists('columns',$layout) || !count($layout['columns']) || !array_key_exists('data',$layout)) {
                throw new AppException('Invalid object parameters !',E_ERROR,1);
            }
            if(array_key_exists('with_borders',$layout)) {
                $this->with_borders=$layout['with_borders'];
            }
            $borders=$this->with_borders ? $this->border_settings : [];
            $this->SetFormats((array_key_exists('formats',$layout) ? $layout['formats'] : NULL));
            $default_width=get_array_value($layout,'default_width',20,'is_not0_numeric');
            $default_format=array_key_exists('default_format',$layout) ? (is_array($layout['default_format']) ? $layout['default_format'] : (array_key_exists($layout['default_format'],$this->formats) ? $this->formats[$layout['default_format']] : [])) : $this->formats['standard'];
            $header_format=array_key_exists('header_format',$layout) ? (is_array($layout['header_format']) ? $layout['header_format'] : (array_key_exists($layout['header_format'],$this->formats) ? $this->formats[$layout['header_format']] : [])) : $this->formats['header'];
            $this->pdf->custom_header=TRUE;
            $this->pdf->custom_header_params=['type'=>'table','columns'=>$layout['columns'],'format'=>$header_format,'default_width'=>$default_width,'border'=>$borders];
            if($first || get_array_value($layout,'new_page',FALSE,'bool')) {
                $first=FALSE;
                $this->pdf->AddPage("P","A4");
            } else {
                $this->pdf->Cell(10,15,'',0,TRUE,'C',0,'',0,FALSE,'T','M');
                $this->pdf->SetCustomHeader(TRUE);
            }//if($first || get_array_value($layout,'new_page',FALSE,'bool'))
            if(!count($layout['data'])) {
                continue;
            }
            $set_totals=TRUE;
            foreach($layout['data'] as $data_row) {
                if(!is_array($data_row) || !count($data_row)) {
                    continue;
                }
                $col_no=0;
                foreach($layout['columns'] as $column) {
                    $col_no++;
                    if($set_totals && array_key_exists('total_row',$column) && $column['total_row']) {
                        $this->total_row[$col_no]=0;
                    }
                    $col_def_format=array_key_exists('format',$column) ? (is_array($column['format']) ? $column['format'] : (array_key_exists($column['format'],$this->formats) ? $this->formats[$column['format']] : [])) : [];
                    $col_custom_format=(array_key_exists('format_func',$column) && $column['format_func']) ? $this->$column['format_func']($data_row,$column) : [];
                    $cformat=array_merge($default_format,$col_def_format,$col_custom_format);
                    $fr=$this->pdf->SetFormat($cformat);
                    $w=get_array_value($column,'width',$default_width,'is_not0_numeric');
                    $cvalue=$this->GetCellValue($data_row,$column,$col_no);
                    $this->pdf->Cell($w,0,$cvalue,$borders,($col_no==count($layout['columns'])),$this->pdf->GetAlign($cformat),$fr['fc'],'',1,FALSE,'T',$this->pdf->GetAlign($cformat,'v'));
                }//END foreach
                if($set_totals) {
                    $set_totals=FALSE;
                }
            }//END foreach
            // TODO: de adaugat la tabel linia de totaluri
        }//END foreach
        //die();
    }//END public function __construct

    /**
     * description
     *
     * @param array $formats |null Formats array
     * @return void
     */
    protected function SetFormats($formats=[]) {
        if(!is_array($formats) || !count($formats)) {
            $this->formats=$this->default_formats;
        } else {
            $this->formats=array_merge($this->default_formats,$formats);
        }//if(!is_array($formats) || !count($formats))
    }//END protected function SetFormats

    /**
     * description
     *
     * @param $data
     * @param $column
     * @param $col
     * @return void
     */
    protected function GetCellValue($data,$column,$col) {
        if(array_key_exists('format_value_func',$column) && $column['format_value_func']) {
            $col_value=$this->$column['format_value_func']($data,$column);
        } else {
            $col_value='';
            $dbfield=$column['dbfield'];
            if(!is_array($column['dbfield'])) {
                $dbfield=[$column['dbfield']];
            }
            foreach($dbfield as $field) {
                if(array_key_exists('indexof',$column) && $column['indexof']) {
                    if(is_array($column['indexof'])) {
                        $aname=$column['indexof'][0];
                        $kvalue=count($column['indexof'])>1 ? $column['indexof'][1] : 'name';
                    } else {
                        $aname=$column['indexof'];
                        $kvalue='name';
                    }//if(is_array($column['indexof']))
                    $tmp_value=(array_key_exists($aname,$this->extra_params) && is_array($this->extra_params[$aname]) && array_key_exists($field,$data) && array_key_exists($data[$field],$this->extra_params[$aname]) && array_key_exists($kvalue,$this->extra_params[$aname][$data[$field]])) ? $this->extra_params[$aname][$data[$field]][$kvalue] : '';
                } else {
                    $tmp_value=array_key_exists($field,$data) ? $data[$field] : '';
                }//if(array_key_exists('indexof',$column) && $column['indexof'])
                $col_value.=($tmp_value ? ((array_key_exists('separator',$column) && $column['separator']) ? $column['separator'] : ' ').$tmp_value : '');
            }//END foreach
        }//if(array_key_exists('format_value_func',$column) && $column['format_value_func'])
        $col_value=(isset($col_value) ? $col_value : '');
        if(array_key_exists('total_row',$column) && $column['total_row']) {
            if(array_key_exists('value_total_row',$column) && $column['total_row']) {
                switch(strtolower($column['total_row'])) {
                    case 'sum':
                    case 'average':
                        $this->total_row[$col]+=is_numeric($col_value) ? $col_value : 0;
                        break;
                    case 'count':
                        $this->total_row[$col]+=$col_value ? 1 : 0;
                        break;
                    default:
                        break;
                }//END switch
            } else {
                $this->total_row[$col]=TRUE;
            }//if(array_key_exists('value_total_row',$column) && $column['total_row'])
        }//if(array_key_exists('total_row',$column) && $column['total_row'])
        $col_value.=(array_key_exists('sufix',$column) && $column['sufix']) ? $column['sufix'] : '';
        return $col_value;
    }//END protected function GetCellValue

    public function PdfOutput() {
        return $this->pdf->Output('','S');
    }//END public function PdfOutput

    protected function NumberFormat0($data,$column) {
        if(is_array($column['dbfield'])) {
            return NULL;
        }
        return number_format($data[$column['dbfield']],0,$this->decimal_separator,$this->group_separator);
    }//END protected function NumberFormat0

    protected function NumberFormat2($data,$column) {
        if(is_array($column['dbfield'])) {
            return NULL;
        }
        return number_format($data[$column['dbfield']],2,$this->decimal_separator,$this->group_separator);
    }//END protected function NumberFormat2
}//END class PdfReport
?>