<?php
/**
 * PdfCreator class file
 * Class for PDF creation that extends TCPDF class
 * @package    NETopes\Reporting
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Reporting;
use TCPDF;
use NApp;
/*
 * TCPDF config initialization
 */
require_once(NApp::$appPath._NAPP_CONFIG_PATH.'/TcpdfConfig.php');
/**
 * PdfCreator class
 * Class for PDF creation that extends TCPDF class
 * @package  NETopes\Reporting
 */
class PdfCreator extends TCPDF {
	/**
	 * @var    bool Flag for custom header method
	 */
	public $custom_header = FALSE;
	/**
	 * @var    array Custom header params
	 */
	public $custom_header_params = FALSE;
	/**
	 * @var    bool Flag for custom footer method
	 */
	public $custom_footer = FALSE;
	/**
	 * @var    array Custom footer params
	 */
	public $custom_footer_params = FALSE;
	/**
	 * description
     * @param array $format
     * @return array
	 */
	public function SetFormat($format = []) {
		$f = $c = $dc = $bgc = FALSE;
		$font = get_array_value($format,'font',NULL,'is_notempty_string');
		$font_size = get_array_value($format,'font_size',NULL,'is_not0_numeric');
		$bold = get_array_value($format,'bold',NULL,'bool');
		$italic = get_array_value($format,'italic',NULL,'bool');
		$color_arr = get_array_value($format,'color',NULL,'is_notempty_array');
		if(!$color_arr) {
			$color = get_array_value($format,'color',NULL,'is_notempty_string');
			$color_arr = $color ? hex2rgb($color) : NULL;
		}//if(!$color_arr)
		$dcolor_arr = get_array_value($format,'draw_color',NULL,'is_notempty_array');
		if(!$dcolor_arr) {
			$dcolor = get_array_value($format,'draw_color',NULL,'is_notempty_string');
			$dcolor_arr = $dcolor ? hex2rgb($dcolor) : NULL;
		}//if(!$color_arr)
		$bgcolor_arr = get_array_value($format,'background_color',NULL,'is_notempty_array');
		if(!$bgcolor_arr) {
			$bgcolor = get_array_value($format,'background_color',NULL,'is_notempty_string');
			$bgcolor_arr = $bgcolor ? hex2rgb($bgcolor) : NULL;
		}//if(!$color_arr)
		if($font || $font_size || $bold || $italic) { $f = TRUE; $this->SetFont(($font ? $font : 'helvetica'),($bold ? 'B' : '').($italic ? 'I' : ''),($font_size ? $font_size : 10)); }
		if(is_array($color_arr) && count($color_arr)==3) { $c = TRUE; $this->SetTextColorArray($color_arr); }
		if(is_array($dcolor_arr) && count($dcolor_arr)==3) { $dc = TRUE; $this->SetDrawColorArray($dcolor_arr); }
		if(is_array($bgcolor_arr) && count($bgcolor_arr)==3) { $bgc = TRUE; $this->SetFillColorArray($bgcolor_arr); }
		return array('f'=>$f,'c'=>$c,'dc'=>$dc,'fc'=>$bgc);
	}//END public function SetFormat
	/**
	 * description
     * @param array  $format
     * @param string $mode
	 * @return void
	 */
	public function GetAlign($format = [],$mode = 'h') {
		$align = '';
		switch(strtolower($mode)) {
			case 'v':
				$halign = get_array_value($format,'align_v',NULL,'is_notempty_string');
				if(!$halign) { return $align; }
				switch($halign) {
					case 'v_middle':
						$align = 'C';
						break;
					case 'v_bottom':
						$align = 'B';
						break;
					case 'v_top':
					default:
						$align = 'T';
						break;
				}//END switch
				break;
			case 'h':
			default:
				$halign = get_array_value($format,'align_h',NULL,'is_notempty_string');
				if(!$halign) { return $align; }
				switch($halign) {
					case 'h_center':
						$align = 'C';
						break;
					case 'h_right':
						$align = 'R';
						break;
					case 'h_left':
					default:
						$align = 'L';
						break;
				}//END switch
				break;
		}//END switch
		return $align;
	}//END public function GetAlign
	/**
	 * description
     * @param array $format
	 * @return void
	 */
    public function GetHtmlFormatString($format = []) {
        $style = '';
		if(!is_array($format) || !count($format)) { return $style; }
		foreach($format as $frm=>$value) {
			switch($frm) {
				case 'font':
					$style .= 'font-family: '.$value.';';
					break;
				case 'font_size':
					$style .= 'font-size: '.$value.';';
					break;
				case 'bold':
					$style .= $value ? 'font-weight: bold;' : '';
					break;
				case 'italic':
					$style .= $value ? 'font-style: italic;' : '';
					break;
				case 'color':
					$style .= 'color: '.$value.';';
					break;
				case 'background_color':
					$style .= 'background-color: '.$value.';';
					break;
				case 'align_h':
					$style .= 'text-align: '.str_replace('h_','',$value).';';
					break;
				case 'align_v':
					$style .= 'vertical-align: '.str_replace('v_','',$value).';';
					break;
				default:
					$style .= $frm.': '.$value.';';
					break;
			}//END switch
		}//foreach($format as $frm=>$value)
		return $style;
    }//END public function GetHtmlFormatString
	/**
	 * This is a overwritten TCPDF method used to render the page header.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @return void
	 */
	public function Header() {
		if($this->custom_header) {
			$this->setCustomHeader($this->custom_header_params);
		} else {
			parent::Header();
		}//if($this->custom_header)
	}//END public function Header
	/**
	 * This is a overwritten TCPDF method used to render the page footer.
	 * It is automatically called by AddPage() and could be overwritten in your own inherited class.
	 * @return void
	 */
	public function Footer() {
		if($this->custom_footer) {
			$this->setCustomFooter($this->custom_footer_params);
		} else {
			parent::Footer();
		}//if($this->custom_footer)
	}//END public function Footer
	/**
	 * description
	 * @return void
	 */
	public function SetCustomHeader($params = NULL) {
		if($params===TRUE) { $params = $this->custom_header_params; }
		if(!is_array($params) || !count($params)) { return; }
		switch(get_array_value($params,'type','','is_string')) {
			case 'table':
				$columns = get_array_value($params,'columns',NULL,'is_notempty_array');
				if(!$columns) { return; }
				$format = get_array_value($params,'format',[],'is_array');
				$col_no = 0;
				foreach($columns as $column) {
					$col_no++;
					$cformat = array_merge($format,get_array_value($column,'header_format',[],'is_array'));
					$fr = $this->SetFormat($cformat);
					$border = get_array_value($params,'border',0,'is_notempty_array');
					$w = get_array_value($column,'width',get_array_value($params,'default_width',20,'is_not0_numeric'),'is_not0_numeric');
					$this->Cell($w,0,$column['name'],$border,($col_no==count($columns)),$this->GetAlign($cformat),$fr['fc'],'',0,FALSE,'T',$this->GetAlign($cformat,'v'));
				}//END foreach
				break;
			default:
				break;
		}//END switch
	}//END public function SetCustomHeader
	/**
	 * description
	 * @return void
	 */
	public function SetCustomFooter($params = NULL) {
        $this->SetY(get_array_value($params,'bottom_margin',-12,'is_numeric'));
        $this->SetFont(
			get_array_value($params,'font','helvetica','is_notempty_string'),
			get_array_value($params,'font_style','','is_string'),
			get_array_value($params,'font_size',8,'is_not0_numeric')
		);
		if(is_array($this->pagegroups) && count($this->pagegroups)) {
			$cPageAlias = $this->getPageNumGroupAlias();
			$tPagesAlias = $this->getPageGroupAlias();
		} else {
			$cPageAlias = $this->getAliasNumPage();
			$tPagesAlias = $this->getAliasNbPages();
		}//if(is_array($this->pagegroups) && count($this->pagegroups))
		$mask = get_array_value($params,'mask','','is_string');
		if($mask) {
			$value = str_replace('{{pages_no}}',$cPageAlias,str_replace('{{page}}',$tPagesAlias,$mask));
		} else {
			$value = $cPageAlias.' / '.$tPagesAlias;
		}//if($mask)
		$align = get_array_value($params,'align','C','is_notempty_string');
		$this->Cell(0,0,$value,0,FALSE,$align,0,'',0,FALSE,'T','M');
	}//END public function SetCustomHeader
	/**
	 * @param      $lft_txt
	 * @param      $rgt_txt
	 * @param      $lft_w
	 * @param      $rgt_w
	 * @param int  $border
     * @param int    $x_offset
	 * @param null $lft_font
	 * @param null $rgt_font
     * @param string $lft_align
     * @param string $rgt_align
	 */
	public function DoubleCellRow($lft_txt,$rgt_txt,$lft_w,$rgt_w,$border = 0,$x_offset = 0,$lft_font = NULL,$rgt_font = NULL,$lft_align = 'L',$rgt_align = 'L') {
		// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0)
		$page_start = $this->getPage();
		$y_start = $this->GetY();
		// write the left cell
		if(is_array($lft_font) && count($lft_font)==3) {
			$this->SetFont($lft_font[0],$lft_font[1],$lft_font[2]);
		}//if(is_array($lft_font) && count($lft_font)==3)
		$this->MultiCell($lft_w,0,$lft_txt,$border,$lft_align,0,2,'','',true,0);
		$page_end_1 = $this->getPage();
		$y_end_1 = $this->GetY();
		$this->setPage($page_start);
		// write the right cell
		if(is_array($rgt_font) && count($rgt_font)==3) {
			$this->SetFont($rgt_font[0],$rgt_font[1],$rgt_font[2]);
		}//if(is_array($rgt_font) && count($rgt_font)==3)
		$this->MultiCell($rgt_w,0,$rgt_txt,$border,$rgt_align,0,1,$this->GetX(),$y_start,true,0);
		$page_end_2 = $this->getPage();
		$y_end_2 = $this->GetY();
		// set the new row position by case
		if(max($page_end_1,$page_end_2)==$page_start) {
		    $ynew = max($y_end_1,$y_end_2);
		} elseif($page_end_1==$page_end_2) {
		    $ynew = max($y_end_1,$y_end_2);
		} elseif($page_end_1>$page_end_2) {
		    $ynew = $y_end_1;
		} else {
		    $ynew = $y_end_2;
		}//if(max($page_end_1,$page_end_2)==$page_start)
		$this->setPage(max($page_end_1,$page_end_2));
		$this->SetXY($this->GetX()+$x_offset,$ynew);
	}//END public function DoubleCellRow
	/**
	 * description
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
	public function RoundCornerBox($x,$y,$width,$height,$color = NULL,$text = '',$align = 'C',$fillcolor = NULL) {
		if(is_array($color)) {
			if(count($color)==3) {
				$this->SetDrawColor($color[0],$color[1],$color[2]);
			}elseif(count($color)==4) {
				$this->SetDrawColor($color[0],$color[1],$color[2],$color[3]);
			}//if(count($color)==3)
		}//if(is_array($color))
		$fill = FALSE;
		if(is_array($fillcolor)) {
			if(count($fillcolor)==3) {
				$fill = TRUE;
				$this->SetFillColor($fillcolor[0],$fillcolor[1],$fillcolor[2]);
			}elseif(count($fillcolor)==4) {
				$fill = TRUE;
				$this->SetFillColor($fillcolor[0],$fillcolor[1],$fillcolor[2],$fillcolor[3]);
			}//if(count($color)==3)
		}//if(is_array($fillcolor))
		//line-top
		$this->Line($x+2, $y, $x+$width-2, $y);
		//corner-top-right
		$this->Circle($x+$width-2, $y+2, 2, 0, 90);
		//line-right
		$this->Line($x+$width, $y+2, $x+$width, $y+$height-2);
		//corner-buttom-right
		$this->Circle($x+$width-2, $y+$height-2, 2, 270, 360);
		//line-buttom
		$this->Line($x+2, $y+$height, $x+$width-2, $y+$height);
		//corner-buttom-left
		$this->Circle($x+2, $y+$height-2, 2, 180, 270);
		//line-right
		$this->Line($x, $y+2, $x, $y+$height-2);
		//corner-top-left
		$this->Circle($x+2, $y+2, 2, 90, 180);
		//header text box
		if($fill) {
			//left
			$this->Circle($x+$width-2, $y+2, 2, 0, 90, 'F', [], array($fillcolor[0],$fillcolor[1],$fillcolor[2]));
			$this->Polygon(array($x+0.3,$y+1.8,$x+0.15,$y+2,$x+2,$y+2,$x+2,$y+0.15,$x+1.8,$y+0.3), 'DF', [], array($fillcolor[0],$fillcolor[1],$fillcolor[2]));
			$this->MultiCell(2, 1.9, '', 0, 'C', $fill, 0, $x, $y+2.04);
			//right
			$this->Circle($x+2, $y+2, 2, 90, 180, 'F', [], array($fillcolor[0],$fillcolor[1],$fillcolor[2]));
			$this->Polygon(array($x+$width-0.3,$y+1.8,$x+$width-0.15,$y+2,$x+$width-2,$y+2,$x+$width-2,$y+0.15,$x+$width-1.8,$y+0.3), 'DF', [], array($fillcolor[0],$fillcolor[1],$fillcolor[2]));
			$this->MultiCell(2, 1.9, '', 0, 'C', $fill, 0, $x+$width-2, $y+2.04);
		}//if($fill)
		if(is_array($text)) {
			$lx = $x+2;
			$twidth = 0;
			foreach($text as $v) {
				$twidth += (array_key_exists('width',$v) && is_numeric($v['width']) && $v['width']>0) ? $v['width'] : 0;
			}//foreach($text as $v)
			foreach($text as $v) {
				if($lx!=$x+2){
					$this->Line($lx, $y+4, $lx, $y+$height);
				}//if($lx!=$x+2)
				$lwidth = (array_key_exists('width',$v) && is_numeric($v['width']) && $v['width']>0) ? $v['width'] : ($width-4-$twidth);
				$this->MultiCell($lwidth, 0, $v['text'], 0, $v['align'], $fill, 0, $lx, $y);
				$lx += $lwidth;
			}//foreach($text as $v)
		} else {
			$this->MultiCell($width-4, 0, $text, 0, $align, $fill, 0, $x+2, $y);
		}//if(is_array($text))
	}//END public function RoundCornerBox
}//END class PdfCreator extends TCPDF