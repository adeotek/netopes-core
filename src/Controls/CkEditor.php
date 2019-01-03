<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
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
use PAF\AppException;

/**
 * ClassName description
 *
 * long_description
 *
 * @package  NETopes\Controls
 * @access   public
 */
class CkEditor extends Control {
    /**
     * @var array|string|null
     */
    public $extra_config = NULL;
    /**
     * description
     *
     * extra_config:
     * - toolbarStartupExpanded: false (hide toolbars on initialization)
     *
     * @return string|null
     * @access public
     * @throws \PAF\AppException
     */
    protected function SetControl(): ?string {
        if(!strlen($this->tag_id)) { throw new AppException('Invalid tag ID!',E_ERROR,1); }
        $lextratagparam = strlen($this->extra_tag_params)>0 ? ' '.$this->extra_tag_params : '';
        $result = "\t\t".'<textarea'.$this->GetTagId(TRUE).$this->GetTagClass('textarea').$this->GetTagAttributes(FALSE).$lextratagparam.'>'.$this->value.'</textarea>'."\n";
        $lwidth = $this->width ? (is_numeric($this->width) ? ','.$this->width : ',\''.$this->width.'\'') : '';
        $lheight = $this->height ? (is_numeric($this->height) ? ','.$this->height : ',\''.$this->height.'\'') : '';
        $lextraconfig = 'undefined';
        if(is_array($this->extra_config)) {
            try {
                $lextraconfig = json_encode($this->extra_config);
            } catch(\Exception $je) {
                NApp::_Elog($je->getMessage());
                $lextraconfig = 'undefined';
            }//END try
        } elseif(is_string($this->extra_config) && strlen($this->extra_config)) {
            $lextraconfig = '{'.trim($this->extra_config,'}{').'}';
        }//if(is_array($this->extra_config))
        NApp::_ExecJs("CreateCkEditor('{$this->phash}','{$this->tag_id}',false,".$lextraconfig.$lwidth.$lheight.");");
        return $result;
    }//END protected function SetControl
    /**
     * description
     *
     * @param bool $all
     * @return string
     * @access public
     */
    public function GetDestroyJsCommand($all = FALSE) {
        return $all ? "DestroyCkEditor('{$this->phash}');" : "DestroyCkEditor('{$this->phash}','{$this->tag_id}',false);";
    }//END public function GetDestroyJsCommand
}//END class CkEditor extends Control