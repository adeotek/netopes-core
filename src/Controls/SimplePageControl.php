<?php
/**
 * Short desc
 *
 * description
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use Translate;

/**
 * ClassName description
 *
 * long_description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class SimplePageControl extends Control {
	public function __construct($params = NULL) {
		$this->buffered = TRUE;
		$this->no_label = TRUE;
		$this->container = FALSE;
		$this->withloader = 1;
		$this->totalrows = 0;
		$this->currentpage = 0;
		parent::__construct($params);
	}//END public function __construct

	protected function AreqRun($search = NULL,$replace = NULL) {
		$run_str = $this->onclickparams;
		if($search && isset($replace)) { $run_str = str_replace($search,$replace,$run_str); }
		if(strlen($this->js_callback)) {
		return NApp::arequest()->PrepareWithCallback($run_str,$this->js_callback);
		}//if(strlen($this->js_callback))
		return NApp::arequest()->Prepare($run_str);
	}//END protected function AreqRun

	protected function SetControl() {
		$limit = NApp::_GetParam('rows_per_page');
		$limit = (is_numeric($limit)>0 && $limit>0) ? $limit : 20;
		$pages_no = ($this->totalrows>0 ? ceil($this->totalrows/$limit) : 1);
		$cpage = (is_numeric($this->currentpage) && $this->currentpage<>0) ? $this->currentpage : 1;
		$lstyle = strlen($this->width)>0 ? ($this->width!='100%' ? ' style="width: '.$this->width.'; margin: 0 auto;"' : ' style="width: '.$this->width.';"') : '';
		$result = '<div class="pagination-container"'.$lstyle.'>'."\n";
		$result .= "\t".'<span class="pag-label">'.Translate::Get('label_page').'</span>'."\n";
		if($pages_no>1) {
		    if($cpage==1 || $cpage<0) {
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-double-left"></i></div>'."\n";
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-left"></i></div>'."\n";
            } else {
                $lonclick = $this->AreqRun('{{page}}',1);
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lonclick.'"><i class="fa fa-angle-double-left"></i></div>'."\n";
                $lonclick = $this->AreqRun('{{page}}',($cpage-1));
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lonclick.'"><i class="fa fa-angle-left"></i></div>'."\n";
            }//if($cpage==1 || $cpage<0)
            $psid = \PAF\AppSession::GetNewUID(NULL,'md5');
            $lonclick = $this->AreqRun('{{page}}',$psid.':value');
            $result .= "\t".'<select id="'.$psid.'" onchange="'.$lonclick.'">'."\n";
            for($i=1; $i<=$pages_no; $i++) {
                $lselected = $cpage==$i ? ' selected="selected"' : '';
                $result .= "\t\t".'<option value="'.$i.'"'.$lselected.'>'.number_format($i,0).'</option>'."\n";
            }//END for
            $lselected = $cpage<0 ? ' selected="selected"' : '';
            $result .= "\t\t".'<option class="special" value="-1"'.$lselected.'>'.Translate::Get('label_all').'</option>'."\n";
            $result .= "\t".'</select>'."\n";
            $result .= "\t".'<span class="pag-part-label">'.Translate::Get('label_of').'</span>'."\n";
            $result .= "\t".'<span class="pag-no">'.$pages_no.'</span>'."\n";
            if($cpage==$pages_no || $cpage<0) {
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-right"></i></div>'."\n";
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-double-right"></i></div>'."\n";
            } else {
                $lonclick = $this->AreqRun('{{page}}',($cpage+1));
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lonclick.'"><i class="fa fa-angle-right"></i></div>'."\n";
                $lonclick = $this->AreqRun('{{page}}',$pages_no);
                $result .= "\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lonclick.'"><i class="fa fa-angle-double-right"></i></div>'."\n";
            }//if($cpage==$pages_no || $cpage<0)
		} else {
			$result .= "\t".'<span class="cpag">1</span>'."\n";
			$result .= "\t".'<span class="pag-part-label">'.Translate::Get('label_of').'</span>'."\n";
			$result .= "\t".'<span class="pag-no">'.$pages_no.'</span>'."\n";
		}//if($pages_no>1)
		$result .= "\t".'<span class="rec-label">'.Translate::Get('label_records').'</span><span class="rec-no">'.number_format($this->totalrows,0).'</span>'."\n";
		$result .= '<div class="clearfix"></div>'."\n";
		$result .= '</div>'."\n";
		return $result;
	}//END protected function SetControl
}//END class SimplePageControl extends Control