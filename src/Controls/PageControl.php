<?php
/**
 * Short desc
 * description
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use Translate;

/**
 * ClassName description
 * long_description
 *
 * @property mixed passparams
 * @property int   totalrows
 * @property mixed target
 * @property mixed current_page
 * @property mixed withcount
 * @package  NETopes\Controls
 */
class PageControl extends Control {

    /**
     * PageControl constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->buffered=TRUE;
        $this->no_label=TRUE;
        $this->container=FALSE;
        $this->totalrows=0;
        parent::__construct($params);
    }//END public function __construct

    /**
     * @param int $first
     * @param int $last
     * @param int $page
     * @return mixed
     */
    protected function GetAjaxCommandString(?int $first,?int $last,$page) {
        return NApp::Ajax()->Prepare("{ 'module': '{$this->module}', 'method': '{$this->method}', 'params': { ".(isset($first) ? "'first_row': {$first}, " : '').(isset($last) ? "'last_row': {$last}, " : '')."'current_page': {$page}, 'fullpagination': 1,".($this->passparams ? $this->passparams.', ' : '')."'target': '{$this->target}' }}",$this->target);
    }//END protected function GetAjaxCommandString

    /**
     * @return string|null
     */
    protected function SetControl(): ?string {
        $result='';
        $rpp=NApp::GetParam('rows_per_page');
        $rpp=$rpp>0 ? $rpp : 20;
        $p_no=ceil($this->totalrows / $rpp);
        if(!$this->withcount || !$this->current_page) {
            $p_current_arr=NApp::GetParam($this->module.$this->method.$this->phash);
            $withcount=get_array_value($p_current_arr,'fullpagination',0,'is_numeric');
            $p_current=get_array_value($p_current_arr,'current_page',1,'is_numeric');
        } else {
            $withcount=$this->withcount;
            $p_current=$this->current_page;
        }//if(!$this->withcount || !$this->current_page)
        if(is_numeric($this->width) && $this->width>0) {
            $c_width=' style="width: '.$this->width.'px;"';
            $c_class='';
        } else {
            $c_width='';
            $c_class=' span-cent';
        }//if(is_numeric($this->width) && $this->width>0)
        $result.="\t".'<div class="paginationcontainer'.$c_class.'"'.$c_width.'>'."\n";
        if($withcount==1) {
            $result.="\t\t".'<div class="itemscount span-cent15"><strong>'.$this->totalrows.'</strong> '.Translate::Get('results_label').'</div>'."\n";
            $result.="\t\t".'<div class="span-cent70 pagination">Pag.';
            if($p_current>0) {
                if($p_current>1) {
                    $p_new=$p_current - 1;
                    $r_first=1 + ($p_new - 1) * $rpp;
                    $r_last=$r_first + $rpp;
                    $result.="\t\t\t".'<span class="next_prev"  onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p_new).'">«</span>'."\n";
                } else {
                    $result.="\t\t\t".'<span class="next_prev nohover">«</span>'."\n";
                }//if ($p_current==1)
            }//if($p_current>0)
            if($p_no<=8) {
                for($p=1; $p<=$p_no; $p++) {
                    if($p==$p_current) {
                        $result.="\t\t\t".'<span class="pagbtn selected">'.$p.'</span>'."\n";
                    } else {
                        $r_first=1 + ($p - 1) * $rpp;
                        $r_last=$r_first + $rpp;
                        $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p).'">'.$p.'</span>'."\n";
                    }//if($p==$p_current)
                }//END for
            } else {
                if($p_current>=5 && $p_current<=($p_no - 4)) {
                    $r_first=1 + $rpp;
                    $r_last=$r_first + $rpp;
                    $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,1).'">1</span>'."\n";
                    $result.="\t\t\t".'<span class="pagbtn nohover">...</span>'."\n";
                    for($p=($p_current - 1); $p<=($p_current + 1); $p++) {
                        if($p==$p_current) {
                            $result.="\t\t\t".'<span class="pagbtn selected">'.$p.'</span>'."\n";
                        } else {
                            $r_first=1 + ($p - 1) * $rpp;
                            $r_last=$r_first + $rpp;
                            $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p).'">'.$p.'</span>'."\n";
                        }//if($p==$p_current)
                    }//END for
                    $result.="\t\t\t".'<span class="pagbtn nohover">...</span>'."\n";
                    $r_first=1 + ($p_no - 1) * $rpp;
                    $r_last=$r_first + $rpp;
                    $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p_no).'">'.$p_no.'</span>'."\n";
                } else {
                    if($p_current>($p_no - 4)) {
                        for($p=1; $p<=2; $p++) {
                            $r_first=1 + ($p - 1) * $rpp;
                            $r_last=$r_first + $rpp;
                            $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p).'">'.$p.'</span>'."\n";
                        }//END for
                        $result.="\t\t\t".'<span class="pagbtn nohover">...</span>'."\n";
                    }//if($p_current>($p_no-4))
                    $p_start=$p_current<5 ? 1 : $p_no - 4;
                    for($p=$p_start; $p<=($p_start + 4); $p++) {
                        if($p==$p_current) {
                            $result.="\t\t\t".'<span class="pagbtn selected">'.$p.'</span>'."\n";
                        } else {
                            $r_first=1 + ($p - 1) * $rpp;
                            $r_last=$r_first + $rpp;
                            $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p).'">'.$p.'</span>'."\n";
                        }//if($p==$p_current)
                    }//END for
                    if($p_current<5) {
                        $result.="\t\t\t".'<span class="pagbtn nohover">...</span>'."\n";
                        for($p=($p_no - 1); $p<=$p_no; $p++) {
                            $r_first=1 + ($p - 1) * $rpp;
                            $r_last=$r_first + $rpp;
                            $result.="\t\t\t".'<span class="pagbtn" onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p).'">'.$p.'</span>'."\n";
                        }//END for
                    }//if($p_current<5)
                }//if($p_current>=5 && $p_current<=($p_no-4))
            }//if($p_no<=8)
            if($p_current>0) {
                if($p_current<$p_no) {
                    $p_new=$p_current + 1;
                    $r_first=1 + ($p_new - 1) * $rpp;
                    $r_last=$r_first + $rpp;
                    $result.="\t\t\t".'<span class="next_prev"  onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p_new).'">»</span>'."\n";
                } else {
                    $result.="\t\t\t".'<span class="next_prev nohover">»</span>'."\n";
                }//if ($p_current==1)
            }//if($p_current>0)
            $result.="\t\t".'</div>'."\n";
            $result.="\t\t".'<div class="span-cent15 salt">';
            $result.="\t\t\t".'<select class="clsComboBox right" id="pagination-cbo-'.$this->module.$this->method.$this->phash.'" onchange="'.$this->GetAjaxCommandString(NULL,NULL,'{nGet:pagination-cbo-'.$this->module.$this->method.$this->phash.':value').'">'."\n";
            for($i=1; $i<=$p_no; $i++) {
                $selected=$i==$p_current ? 'selected="selected"' : '';
                $result.="\t\t".'<option '.$selected.' value="'.$i.'">'.$i.'</option>'."\n";
            }//END for
            $allselected=$p_current==-1 ? 'selected="selected"' : '';
            $result.="\t\t\t\t".'<option '.$allselected.' value="-1">'.Translate::Get('cboall').'</option>'."\n";
            $result.="\t\t\t".'</select>'."\n";
            $result.="\t\t".'<span class="right">'.Translate::Get('jump_to_label').'&nbsp;</span>';
            $result.="\t\t".'</div>'."\n";
        } else {
            $result.="\t\t".'<div class="span-cent15">&nbsp;</div>'."\n";
            $result.="\t\t".'<div class="span-cent70 pagination">Pag.';
            if($p_current>1) {
                $p_new=$p_current - 1;
                $r_first=1 + ($p_new - 1) * $rpp;
                $r_last=$r_first + $rpp;
                $result.="\t\t\t".'<span class="next_prev"  onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p_new).'">«</span>'."\n";
            } else {
                $result.="\t\t\t".'<span class="next_prev nohover">«</span>'."\n";
            }//if($p_current>1)
            $result.="\t\t\t".'<span class="pagbtn nohover">'.$p_current.'</span>'."\n";
            $p_new=$p_current + 1;
            $r_first=1 + ($p_new - 1) * $rpp;
            $r_last=$r_first + $rpp;
            $result.="\t\t\t".'<span class="next_prev"  onclick="'.$this->GetAjaxCommandString($r_first,$r_last,$p_new).'">»</span>'."\n";
            $result.="\t\t".'</div>'."\n";
            $result.="\t\t".'<div class="span-cent15 salt">&nbsp;</div>'."\n";
        }//if($withcount==1)
        $result.="\t".'</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class PageControl extends Control