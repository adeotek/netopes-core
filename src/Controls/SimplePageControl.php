<?php
/**
 * Short desc
 * description
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppSession;
use Translate;

/**
 * ClassName description
 * long_description
 *
 * @property int          with_loader* @package  NETopes\Controls
 * @property int          total_rows
 * @property int          current_page
 * @property mixed        onclickparams
 * @property mixed        js_callback
 * @property mixed        target_id
 * @property mixed        ajax_method
 * @property mixed|string onclick_action
 * @property mixed        onclick_params
 */
class SimplePageControl extends Control {
    /**
     * SimplePageControl constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->buffered=TRUE;
        $this->no_label=TRUE;
        $this->container=FALSE;
        $this->with_loader=1;
        $this->total_rows=0;
        $this->current_page=0;
        parent::__construct($params);
    }//END public function __construct

    /**
     * @param null $search
     * @param null $replace
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    protected function GetAjaxActionString($search=NULL,$replace=NULL) {
        if(is_array($this->onclick_params) && count($this->onclick_params)) {
            $command=NApp::Ajax()->GetCommand($this->onclick_params);
        } elseif(is_string($this->onclick_action) && strlen($this->onclick_action)) {
            $command=$this->onclick_action;
        } else {
            $targetId=NULL;
            $jParams=NULL;
            $eParams=NULL;
            $method=NULL;
            $command=NApp::Ajax()->LegacyProcessParamsString($this->onclickparams,$targetId,$jParams,$eParams,$method);
            $this->target_id=strlen($this->target_id) ? $this->target_id : $targetId;
            $this->ajax_method=strlen($this->ajax_method) ? $this->ajax_method : $method;
        }
        if($search && isset($replace)) {
            $command=str_replace($search,$replace,$command);
        }
        return NApp::Ajax()->Prepare($command,$this->target_id,NULL,TRUE,NULL,TRUE,$this->js_callback,NULL,TRUE,$this->ajax_method ?? 'ControlAjaxRequest');
    }//END protected function GetAjaxActionString

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $limit=NApp::GetParam('rows_per_page');
        $limit=(is_numeric($limit)>0 && $limit>0) ? $limit : 20;
        $pages_no=($this->total_rows>0 ? ceil($this->total_rows / $limit) : 1);
        $cpage=(is_numeric($this->current_page) && $this->current_page<>0) ? $this->current_page : 1;
        $lstyle=strlen($this->width)>0 ? ($this->width!='100%' ? ' style="width: '.$this->width.'; margin: 0 auto;"' : ' style="width: '.$this->width.';"') : '';
        $result='<div class="pagination-container"'.$lstyle.'>'."\n";
        $result.="\t".'<span class="pag-label">'.Translate::Get('label_page').'</span>'."\n";
        if($pages_no>1) {
            if($cpage==1 || $cpage<0) {
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-double-left"></i></div>'."\n";
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-left"></i></div>'."\n";
            } else {
                $lOnclick=$this->GetAjaxActionString('{!page!}',1);
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lOnclick.'"><i class="fa fa-angle-double-left"></i></div>'."\n";
                $lOnclick=$this->GetAjaxActionString('{!page!}',($cpage - 1));
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lOnclick.'"><i class="fa fa-angle-left"></i></div>'."\n";
            }//if($cpage==1 || $cpage<0)
            $psid=AppSession::GetNewUID(NULL,'md5');
            $lOnclick=$this->GetAjaxActionString('{!page!}','\'{nGet|'.$psid.':value}\'');
            $result.="\t".'<select id="'.$psid.'" onchange="'.$lOnclick.'">'."\n";
            for($i=1; $i<=$pages_no; $i++) {
                $lSelected=$cpage==$i ? ' selected="selected"' : '';
                $result.="\t\t".'<option value="'.$i.'"'.$lSelected.'>'.number_format($i,0).'</option>'."\n";
            }//END for
            $lSelected=$cpage<0 ? ' selected="selected"' : '';
            $result.="\t\t".'<option class="special" value="-1"'.$lSelected.'>'.Translate::Get('label_all').'</option>'."\n";
            $result.="\t".'</select>'."\n";
            $result.="\t".'<span class="pag-part-label">'.Translate::Get('label_of').'</span>'."\n";
            $result.="\t".'<span class="pag-no">'.$pages_no.'</span>'."\n";
            if($cpage==$pages_no || $cpage<0) {
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-right"></i></div>'."\n";
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnDefaultClass('io btn-xxs') : 'btn btn-default io btn-xxs').'"><i class="fa fa-angle-double-right"></i></div>'."\n";
            } else {
                $lOnclick=$this->GetAjaxActionString('{!page!}',($cpage + 1));
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lOnclick.'"><i class="fa fa-angle-right"></i></div>'."\n";
                $lOnclick=$this->GetAjaxActionString('{!page!}',$pages_no);
                $result.="\t".'<div class="'.(is_object(NApp::$theme) ? NApp::$theme->GetBtnInfoClass('io btn-xxs') : 'btn btn-info io btn-xxs').'" onclick="'.$lOnclick.'"><i class="fa fa-angle-double-right"></i></div>'."\n";
            }//if($cpage==$pages_no || $cpage<0)
        } else {
            $result.="\t".'<span class="cpag">1</span>'."\n";
            $result.="\t".'<span class="pag-part-label">'.Translate::Get('label_of').'</span>'."\n";
            $result.="\t".'<span class="pag-no">'.$pages_no.'</span>'."\n";
        }//if($pages_no>1)
        $result.="\t".'<span class="rec-label">'.Translate::Get('label_records').'</span><span class="rec-no">'.number_format($this->total_rows,0).'</span>'."\n";
        $result.='<div class="clearfix"></div>'."\n";
        $result.='</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class SimplePageControl extends Control