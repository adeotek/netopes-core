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
use Translate;

/**
 * ClassName description
 * long_description
 *
 * @package  NETopes\Controls
 */
class SitePageControl extends Control {
    public function __construct($params=NULL) {
        $this->buffered=TRUE;
        $this->no_label=TRUE;
        $this->container=FALSE;
        $this->total_rows=0;
        $this->current_page=1;
        parent::__construct($params);
    }//END public function __construct

    protected function SetControl(): ?string {
        $limit=NApp::GetParam('rows_per_page');
        $limit=$limit>0 ? $limit : 20; //crapa cu division by zero daca nu are inregistrare in admin_user_options
        $totalnrofpages=ceil($this->total_rows / $limit);
        $cpage=(is_numeric($this->current_page) && $this->current_page>0) ? $this->current_page : -1;
        $result='';
        $result.="\t".'<div class="paginationcontainer">'."\n";
        $result.="\t\t".'<div class="total_rows"><strong>'.$this->total_rows.'</strong> '.Translate::Get('results_label').'</div>'."\n";
        $result.="\t\t".'<div class="pagination">Pag.';
        $this->hreflink=strpos($this->hreflink,'?')===FALSE ? $this->hreflink.'?' : $this->hreflink.'&';
        if($cpage>0) {
            if($cpage==1) {
                $result.="\t\t\t".'<span class="page_btn next_prev nohover">«</span>'."\n";
            } else {
                $result.="\t\t\t".'<a class="page_btn next_prev" href="'.$this->hreflink.'pag='.($cpage - 1).'">«</a>'."\n";
            }//if ($cpage==1)
        }//if($cpage>0)
        if($cpage<=5) {
            for($p=1; $p<=min(($cpage<=3 ? 5 : $cpage + 2),$totalnrofpages); $p++) {
                if($p==$cpage) {
                    $result.="\t\t\t".'<span class="page_btn selected">'.$p.'</span>'."\n";
                } else {
                    $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag='.$p.'">'.$p.'</a>'."\n";
                }//if($p==$cpage){
            }//for($p=1; $p<=min(($cpage<=3 ? 5 : $cpage+2),$totalnrofpages); $p++)
            if($totalnrofpages>$p) {
                $result.="\t\t\t".'<span class="page_btn nohover">...</span>'."\n";
                if($totalnrofpages>($p + 1)) {
                    $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag='.($totalnrofpages - 1).'">'.($totalnrofpages - 1).'</a>'."\n";
                }//if($totalnrofpages>($p+1)
                $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag='.$totalnrofpages.'">'.$totalnrofpages.'</a>'."\n";
            }//if ($totalnrofpages>$p)
        } else if($cpage>5) {
            $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag=1">1</a>'."\n";
            $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag=2">2</a>'."\n";
            if($cpage!=6) {
                $result.="\t\t\t".'<span class="page_btn nohover">...</span>'."\n";
            }//if($thisp!=6)
            for($p=($cpage==6 ? 3 : min($cpage - 2,$totalnrofpages - 4)); $p<=(($totalnrofpages - $cpage)<=5 ? $totalnrofpages : $cpage + 2); $p++) {
                if($p==$cpage) {
                    $result.="\t\t\t".'<span class="page_btn selected">'.$p.'</span>'."\n";
                } else if($p<=$totalnrofpages) {
                    $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag='.$p.'">'.$p.'</a>'."\n";
                }//if($p==$cpage)
            }//for($p = ($cpage==6 ? 3 : min($cpage-2,$totalnrofpages-4)); $p<=(($totalnrofpages-$cpage)<=5 ? $totalnrofpages : $cpage+2); $p++)
            if($totalnrofpages>($p + 1)) {
                $result.="\t\t\t".'<span class="page_btn nohover">...</span>'."\n";
                $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag='.($totalnrofpages - 1).'">'.($totalnrofpages - 1).'</a>'."\n";
                $result.="\t\t\t".'<a class="page_btn" href="'.$this->hreflink.'pag='.$totalnrofpages.'">'.$totalnrofpages.'</a>'."\n";
            }//if($totalnrofpages>($p+1))
        }//if($cpage<=5)
        if($cpage>0) {
            if($cpage==$totalnrofpages) {
                $result.="\t\t\t".'<span class="page_btn next_prev nohover">»</span>'."\n";
            } else {
                $result.="\t\t\t".'<a class="page_btn next_prev" href="'.$this->hreflink.'pag='.($cpage + 1).'">»</a>'."\n";
            }//if($cpage==$totalnrofpages)
        }//if($cpage>0)
        $result.="\t\t".'</div>'."\n";
        $result.="\t\t".'<div class="jump_to">'.Translate::Get('jump_to_label').' ';
        $result.="\t\t\t".'<select onchange="window.location.href = \''.$this->hreflink.'pag=\'+this.value;">'."\n";
        for($i=1; $i<=$totalnrofpages; $i++) {
            $selected=$i==$cpage ? 'selected="selected"' : '';
            $result.="\t\t".'<option '.$selected.' value="'.$i.'">'.$i.'</option>'."\n";
        }//for($i=1; $i<=$totalnrofpages; $i++)
        $allselected=$cpage==-1 ? 'selected="selected"' : '';
        $result.="\t\t\t\t".'<option '.$allselected.' value="-1">'.Translate::Get('cboall').'</option>'."\n";
        $result.="\t\t\t".'</select>'."\n";
        $result.="\t\t".'</div>'."\n";
        $result.="\t".'</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class SitePageControl extends Control
?>