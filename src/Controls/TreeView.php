<?php
/**
 * TreeView control class file
 * Tree data view control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.1
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppSession;

/**
 * Class TreeView
 *
 * @property mixed      icon
 * @property bool|mixed hide_parents_checkbox
 * @property int|mixed  encrypted
 * @property mixed      extra_tag_params
 * @property mixed      class
 * @property mixed      checkboxs
 * @package  NETopes\Controls
 */
class TreeView extends Control {
    /**
     * @var null
     */
    public $data_source=NULL;

    /**
     * description
     *
     * @return string|null
     */
    protected function SetControl(): ?string {
        if(!strlen($this->tag_id)) {
            $this->tag_id=AppSession::GetNewUID();
        }
        $cStyle=isset($this->width) && strlen($this->width) ? ' style="width: '.$this->width.(is_numeric($this->width) ? 'px' : '').';"' : '';
        $extraTagParams=strlen($this->extra_tag_params)>0 ? ' '.$this->extra_tag_params : '';
        $lClass=$this->base_class.(strlen($this->class)>0 ? ' '.$this->class : '');
        $result="\t\t".'<div'.$this->GetTagId(FALSE).' class="'.$lClass.'"'.$cStyle.$extraTagParams.'></div>';
        $ds_module=get_array_value($this->data_source,'ds_class','','is_string');
        $ds_method=get_array_value($this->data_source,'ds_method','','is_string');
        if(strlen($ds_module) && strlen($ds_method)) {
            $ds_module=convert_from_camel_case($ds_module);
            $ds_method=convert_from_camel_case($ds_method);
            $urlParams='';
            $ds_params=get_array_value($this->data_source,'ds_params',[],'is_array');
            if(count($ds_params)) {
                foreach($ds_params as $pk=>$pv) {
                    $urlParams.='&'.$pk.'='.$pv;
                }
            }//if(count($ds_params))
            $urlJsParams=strlen($urlParams) ? "urlParams: '".$urlParams."'" : '';
            $ds_js_params=get_array_value($this->data_source,'ds_js_params',[],'is_array');
            if(count($ds_js_params)) {
                foreach($ds_js_params as $acpk=>$acpv) {
                    $urlJsParams.=(strlen($urlJsParams) ? ', ' : '').$acpk.': '.$acpv;
                }
            }//if(count($ds_js_params))
            $this->encrypted=$this->encrypted ? 1 : 0;
            $this->hide_parents_checkbox=$this->hide_parents_checkbox ? TRUE : FALSE;
            $this->checkboxs=$this->checkboxs ? TRUE : FALSE;
            AppSession::SetSessionAcceptedRequest($this->uid,NApp::$currentNamespace);
            NApp::AddJsScript("InitFancyTree('{$this->tag_id}','{$ds_module}','{$ds_method}',{ {$urlJsParams} },'".NApp::$currentNamespace."','{$this->uid}',{$this->encrypted},".intval($this->checkboxs).",".intval($this->hide_parents_checkbox).",".($this->icon ? 'true' : 'false').");");
        }//if(strlen($ds_module) && strlen($ds_method))
        return $result;
    }//END protected function SetControl
}//END class TreeView extends Control