<?php
/**
 * TcpdfAdapter class file
 *
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.10.1
 * @filesource
 */
namespace NETopes\Core\Reporting;
use Exception;
use NApp;
use NETopes\Core\AppException;
use NETopes\Core\DataHelpers;
use NETopes\Core\Helpers;
use TCPDF;
use TCPDF_STATIC;

/*
 * TCPDF config initialization
 */
require_once(NApp::$appPath._NAPP_CONFIG_PATH.'/TcpdfConfig.php');

/**
 * Class TcpdfAdapter
 *
 * @package NETopes\Core\Reporting
 */
class TcpdfAdapter extends TCPDF implements IPdfAdapter {
    use TPdfAdapter;

    /**
     * IPdfAdapter constructor.
     *
     * @param array $params
     */
    public function __construct(array $params=[]) {
        $this->ProcessInitialParams($params);
        parent::__construct($this->orientation,$this->unit,$this->pageSize,$this->unicode,$this->encoding,FALSE,FALSE);
        if(get_array_value($params,'page_init',TRUE,'bool')) {
            $this->SetPrintHeader(FALSE);
            $this->AddPage();
        }
    }//END public function __construct

    /**
     * @param array|null $params
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function GetOutput(?array $params=NULL) {
        $destination=get_array_value($params,'destination','S','is_notempty_string');
        $currentFileName=get_array_value($params,'file_name',NULL,'?is_string');
        if(!strlen($currentFileName)) {
            $currentFileName=$this->fileName ? DataHelpers::normalizeString($this->fileName) : date("Y-m-d-H-i-s").'.pdf';
        }
        try {
            $first=TRUE;
            foreach($this->content as $pageContent) {
                if($first) {
                    $first=FALSE;
                } else {
                    $this->AddPage();
                }
                $this->writeHTML($pageContent,TRUE,FALSE,FALSE,FALSE,'');
            }//END foreach
            return $this->Output($currentFileName,$destination);
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public function GetOutput

    /**
     * @param array|null $params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function Render(?array $params=NULL) {
        if(!is_array($params)) {
            $params=[];
        }
        $params['destination']='I';
        $this->GetOutput($params);
    }//END public function Render

    /**
     * @param string $content
     * @param string $name
     * @param string $dest
     * @throws \NETopes\Core\AppException
     */
    public function OutputContent(string $content,?string $name=NULL,string $dest='I') {
        if(!strlen($content)) {
            throw new AppException('Invalid PDF content!');
        }
        $name=strlen($name) ? DataHelpers::normalizeString($name) : date("Y-m-d-H-i-s").'.pdf';
        try {
            switch(strtoupper($dest)) {
                case 'I':
                    // send output to a browser
                    header('Content-Type: application/pdf');
                    if(headers_sent()) {
                        throw new AppException('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    header('Content-Disposition: inline; filename="'.basename($name).'"');
                    break;
                case 'D':
                    // download PDF as file
                    header('Content-Description: File Transfer');
                    if(headers_sent()) {
                        throw new AppException('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
                    // force download dialog
                    header('Content-Type: application/force-download');
                    header('Content-Type: application/octet-stream',FALSE);
                    header('Content-Type: application/download',FALSE);
                    header('Content-Type: application/pdf',FALSE);
                    // use the Content-Disposition header to supply a recommended filename
                    header('Content-Disposition: attachment; filename="'.basename($name).'"');
                    header('Content-Transfer-Encoding: binary');
                    break;
                default:
                    throw new AppException('Incorrect output destination: '.$dest);
            }
            TCPDF_STATIC::sendOutputData($content,strlen($content));
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public function OutputContent

    /**
     * @param array|null $params
     * @return void
     */
    public function SetCustomHeader(?array $params=NULL) {
        if(is_null($params)) {
            $params=$this->customHeaderParams;
        }
        if(!count($params)) {
            return;
        }
        switch(get_array_value($params,'type','','is_string')) {
            case 'table':
                $columns=get_array_value($params,'columns',NULL,'is_notempty_array');
                if(!$columns) {
                    return;
                }
                $format=get_array_value($params,'format',[],'is_array');
                $col_no=0;
                foreach($columns as $column) {
                    $col_no++;
                    $cformat=array_merge($format,get_array_value($column,'header_format',[],'is_array'));
                    $fr=$this->SetFormat($cformat);
                    $border=get_array_value($params,'border',0,'is_notempty_array');
                    $w=get_array_value($column,'width',get_array_value($params,'default_width',20,'is_not0_numeric'),'is_not0_numeric');
                    $this->Cell($w,0,$column['name'],$border,($col_no==count($columns)),$this->GetAlign($cformat),$fr['fc'],'',0,FALSE,'T',$this->GetAlign($cformat,'v'));
                }//END foreach
                break;
            case 'html':
                $html=get_array_value($params,'html','','is_notempty_string');
                // $w=0,$h=0,$x='',$y='',$html,$border=0,$ln=1,$fill=0,$reseth=TRUE,$align='top',$autopadding=TRUE
                $this->writeHTMLCell(0,0,'','',$html,0,1,0,TRUE,'top',TRUE);
                break;
            default:
                break;
        }//END switch
    }//END public function SetCustomHeader

    /**
     * @param array|null $params
     * @return void
     */
    public function SetCustomFooter(?array $params=NULL) {
        if(is_null($params)) {
            $params=$this->customHeaderParams;
        }
        if(!count($params)) {
            return;
        }
        switch(get_array_value($params,'type','','is_string')) {
            case 'table':
                $columns=get_array_value($params,'columns',NULL,'is_notempty_array');
                if(!$columns) {
                    return;
                }
                $format=get_array_value($params,'format',[],'is_array');
                $col_no=0;
                foreach($columns as $column) {
                    $col_no++;
                    $cformat=array_merge($format,get_array_value($column,'header_format',[],'is_array'));
                    $fr=$this->SetFormat($cformat);
                    $border=get_array_value($params,'border',0,'is_notempty_array');
                    $w=get_array_value($column,'width',get_array_value($params,'default_width',20,'is_not0_numeric'),'is_not0_numeric');
                    $this->Cell($w,0,$column['name'],$border,($col_no==count($columns)),$this->GetAlign($cformat),$fr['fc'],'',0,FALSE,'T',$this->GetAlign($cformat,'v'));
                }//END foreach
                break;
            case 'html':
                $html=get_array_value($params,'html','','is_notempty_string');
                // $w=0,$h=0,$x='',$y='',$html,$border=0,$ln=1,$fill=0,$reseth=TRUE,$align='top',$autopadding=TRUE
                $this->writeHTMLCell(0,0,'','',$html,0,1,0,TRUE,'top',TRUE);
                break;
            default:
                break;
        }//END switch
    }//END public function SetCustomFooter

    /**
     * @return string|null
     */
    public function GetTitle() {
        return $this->title;
    }

    /**
     * @param float $timestamp
     */
    public function SetModificationTimestamp(float $timestamp): void {
        $this->doc_creation_timestamp=$this->doc_modification_timestamp=$timestamp;
    }

    /**
     * @param string|null $fileId
     */
    public function SetFileId(?string $fileId): void {
        $this->file_id=$fileId;
    }

    /**
     * This is a overwritten TCPDF method used to render the page header.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     *
     * @return void
     */
    public function Header() {
        if($this->customHeader) {
            $this->SetCustomHeader($this->customHeaderParams);
        } else {
            parent::Header();
        }//if($this->customHeader)
    }//END public function Header

    /**
     * This is a overwritten TCPDF method used to render the page footer.
     * It is automatically called by AddPage() and could be overwritten in your own inherited class.
     *
     * @return void
     */
    public function Footer() {
        if($this->customFooter) {
            $this->SetCustomFooter($this->customFooterParams);
        } else {
            parent::Footer();
        }//if($this->customFooter)
    }//END public function Footer

    /**
     * @param array $format
     * @return array
     */
    public function SetFormat($format=[]) {
        $f=$c=$dc=$bgc=FALSE;
        $font=get_array_value($format,'font',NULL,'is_notempty_string');
        $font_size=get_array_value($format,'font_size',NULL,'is_not0_numeric');
        $bold=get_array_value($format,'bold',NULL,'bool');
        $italic=get_array_value($format,'italic',NULL,'bool');
        $color_arr=get_array_value($format,'color',NULL,'is_notempty_array');
        if(!$color_arr) {
            $color=get_array_value($format,'color',NULL,'is_notempty_string');
            $color_arr=$color ? Helpers::hex2rgb($color) : NULL;
        }//if(!$color_arr)
        $dcolor_arr=get_array_value($format,'draw_color',NULL,'is_notempty_array');
        if(!$dcolor_arr) {
            $dcolor=get_array_value($format,'draw_color',NULL,'is_notempty_string');
            $dcolor_arr=$dcolor ? Helpers::hex2rgb($dcolor) : NULL;
        }//if(!$color_arr)
        $bgcolor_arr=get_array_value($format,'background_color',NULL,'is_notempty_array');
        if(!$bgcolor_arr) {
            $bgcolor=get_array_value($format,'background_color',NULL,'is_notempty_string');
            $bgcolor_arr=$bgcolor ? Helpers::hex2rgb($bgcolor) : NULL;
        }//if(!$color_arr)
        if($font || $font_size || $bold || $italic) {
            $f=TRUE;
            $this->SetFont(($font ? $font : 'helvetica'),($bold ? 'B' : '').($italic ? 'I' : ''),($font_size ? $font_size : 10));
        }
        if(is_array($color_arr) && count($color_arr)==3) {
            $c=TRUE;
            $this->SetTextColorArray($color_arr);
        }
        if(is_array($dcolor_arr) && count($dcolor_arr)==3) {
            $dc=TRUE;
            $this->SetDrawColorArray($dcolor_arr);
        }
        if(is_array($bgcolor_arr) && count($bgcolor_arr)==3) {
            $bgc=TRUE;
            $this->SetFillColorArray($bgcolor_arr);
        }
        return ['f'=>$f,'c'=>$c,'dc'=>$dc,'fc'=>$bgc];
    }//END public function SetFormat

    /**
     * @param array  $format
     * @param string $mode
     * @return string
     */
    public function GetAlign($format=[],$mode='h') {
        $align='';
        switch(strtolower($mode)) {
            case 'v':
                $halign=get_array_value($format,'align_v',NULL,'is_notempty_string');
                if(!$halign) {
                    return $align;
                }
                switch($halign) {
                    case 'v_middle':
                        $align='C';
                        break;
                    case 'v_bottom':
                        $align='B';
                        break;
                    case 'v_top':
                    default:
                        $align='T';
                        break;
                }//END switch
                break;
            case 'h':
            default:
                $halign=get_array_value($format,'align_h',NULL,'is_notempty_string');
                if(!$halign) {
                    return $align;
                }
                switch($halign) {
                    case 'h_center':
                        $align='C';
                        break;
                    case 'h_right':
                        $align='R';
                        break;
                    case 'h_left':
                    default:
                        $align='L';
                        break;
                }//END switch
                break;
        }//END switch
        return $align;
    }//END public function GetAlign

    /**
     * @param        $lft_txt
     * @param        $rgt_txt
     * @param        $lft_w
     * @param        $rgt_w
     * @param int    $border
     * @param int    $x_offset
     * @param null   $lft_font
     * @param null   $rgt_font
     * @param string $lft_align
     * @param string $rgt_align
     */
    public function DoubleCellRow($lft_txt,$rgt_txt,$lft_w,$rgt_w,$border=0,$x_offset=0,$lft_font=NULL,$rgt_font=NULL,$lft_align='L',$rgt_align='L') {
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)
        $page_start=$this->getPage();
        $y_start=$this->GetY();
        // write the left cell
        if(is_array($lft_font) && count($lft_font)==3) {
            $this->SetFont($lft_font[0],$lft_font[1],$lft_font[2]);
        }//if(is_array($lft_font) && count($lft_font)==3)
        $this->MultiCell($lft_w,0,$lft_txt,$border,$lft_align,0,2,'','',TRUE,0);
        $page_end_1=$this->getPage();
        $y_end_1=$this->GetY();
        $this->setPage($page_start);
        // write the right cell
        if(is_array($rgt_font) && count($rgt_font)==3) {
            $this->SetFont($rgt_font[0],$rgt_font[1],$rgt_font[2]);
        }//if(is_array($rgt_font) && count($rgt_font)==3)
        $this->MultiCell($rgt_w,0,$rgt_txt,$border,$rgt_align,0,1,$this->GetX(),$y_start,TRUE,0);
        $page_end_2=$this->getPage();
        $y_end_2=$this->GetY();
        // set the new row position by case
        if(max($page_end_1,$page_end_2)==$page_start) {
            $ynew=max($y_end_1,$y_end_2);
        } elseif($page_end_1==$page_end_2) {
            $ynew=max($y_end_1,$y_end_2);
        } elseif($page_end_1>$page_end_2) {
            $ynew=$y_end_1;
        } else {
            $ynew=$y_end_2;
        }//if(max($page_end_1,$page_end_2)==$page_start)
        $this->setPage(max($page_end_1,$page_end_2));
        $this->SetXY($this->GetX() + $x_offset,$ynew);
    }//END public function DoubleCellRow

    /**
     * description
     *
     * @param        $x
     * @param        $y
     * @param        $width
     * @param        $height
     * @param null   $color
     * @param string $text
     * @param string $align
     * @param null   $fillcolor
     * @return void
     */
    public function RoundCornerBox($x,$y,$width,$height,$color=NULL,$text='',$align='C',$fillcolor=NULL) {
        if(is_array($color)) {
            if(count($color)==3) {
                $this->SetDrawColor($color[0],$color[1],$color[2]);
            } elseif(count($color)==4) {
                $this->SetDrawColor($color[0],$color[1],$color[2],$color[3]);
            }//if(count($color)==3)
        }//if(is_array($color))
        $fill=FALSE;
        if(is_array($fillcolor)) {
            if(count($fillcolor)==3) {
                $fill=TRUE;
                $this->SetFillColor($fillcolor[0],$fillcolor[1],$fillcolor[2]);
            } elseif(count($fillcolor)==4) {
                $fill=TRUE;
                $this->SetFillColor($fillcolor[0],$fillcolor[1],$fillcolor[2],$fillcolor[3]);
            }//if(count($color)==3)
        }//if(is_array($fillcolor))
        //line-top
        $this->Line($x + 2,$y,$x + $width - 2,$y);
        //corner-top-right
        $this->Circle($x + $width - 2,$y + 2,2,0,90);
        //line-right
        $this->Line($x + $width,$y + 2,$x + $width,$y + $height - 2);
        //corner-buttom-right
        $this->Circle($x + $width - 2,$y + $height - 2,2,270,360);
        //line-buttom
        $this->Line($x + 2,$y + $height,$x + $width - 2,$y + $height);
        //corner-buttom-left
        $this->Circle($x + 2,$y + $height - 2,2,180,270);
        //line-right
        $this->Line($x,$y + 2,$x,$y + $height - 2);
        //corner-top-left
        $this->Circle($x + 2,$y + 2,2,90,180);
        //header text box
        if($fill) {
            //left
            $this->Circle($x + $width - 2,$y + 2,2,0,90,'F',[],[$fillcolor[0],$fillcolor[1],$fillcolor[2]]);
            $this->Polygon([$x + 0.3,$y + 1.8,$x + 0.15,$y + 2,$x + 2,$y + 2,$x + 2,$y + 0.15,$x + 1.8,$y + 0.3],'DF',[],[$fillcolor[0],$fillcolor[1],$fillcolor[2]]);
            $this->MultiCell(2,1.9,'',0,'C',$fill,0,$x,$y + 2.04);
            //right
            $this->Circle($x + 2,$y + 2,2,90,180,'F',[],[$fillcolor[0],$fillcolor[1],$fillcolor[2]]);
            $this->Polygon([$x + $width - 0.3,$y + 1.8,$x + $width - 0.15,$y + 2,$x + $width - 2,$y + 2,$x + $width - 2,$y + 0.15,$x + $width - 1.8,$y + 0.3],'DF',[],[$fillcolor[0],$fillcolor[1],$fillcolor[2]]);
            $this->MultiCell(2,1.9,'',0,'C',$fill,0,$x + $width - 2,$y + 2.04);
        }//if($fill)
        if(is_array($text)) {
            $lx=$x + 2;
            $twidth=0;
            foreach($text as $v) {
                $twidth+=(array_key_exists('width',$v) && is_numeric($v['width']) && $v['width']>0) ? $v['width'] : 0;
            }//foreach($text as $v)
            foreach($text as $v) {
                if($lx!=$x + 2) {
                    $this->Line($lx,$y + 4,$lx,$y + $height);
                }//if($lx!=$x+2)
                $lwidth=(array_key_exists('width',$v) && is_numeric($v['width']) && $v['width']>0) ? $v['width'] : ($width - 4 - $twidth);
                $this->MultiCell($lwidth,0,$v['text'],0,$v['align'],$fill,0,$lx,$y);
                $lx+=$lwidth;
            }//foreach($text as $v)
        } else {
            $this->MultiCell($width - 4,0,$text,0,$align,$fill,0,$x + 2,$y);
        }//if(is_array($text))
    }//END public function RoundCornerBox
}//END class TcpdfAdapter extends TCPDF implements IPdfAdapter