<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use Exception;
use NApp;
use NETopes\Core\AppException;

/**
 * ClassName description
 * long_description
 *
 * @package  NETopes\Controls
 */
class CkEditor extends Control {
    /**
     * @var array|string|null
     */
    public $extra_config=NULL;

    /**
     * description
     * extra_config:
     * - toolbarStartupExpanded: false (hide toolbars on initialization)
     *
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        if(!strlen($this->tag_id)) {
            throw new AppException('Invalid tag ID!',E_ERROR,1);
        }
        $lextratagparam=strlen($this->extra_tag_params)>0 ? ' '.$this->extra_tag_params : '';
        $result="\t\t".'<textarea'.$this->GetTagId(TRUE).$this->GetTagClass('textarea').$this->GetTagAttributes(FALSE).$lextratagparam.'>'.$this->value.'</textarea>'."\n";
        $lwidth=$this->width ? (is_numeric($this->width) ? ','.$this->width : ',\''.$this->width.'\'') : '';
        $lheight=$this->height ? (is_numeric($this->height) ? ','.$this->height : ',\''.$this->height.'\'') : '';
        $lextraconfig='undefined';
        if(is_array($this->extra_config)) {
            try {
                $lextraconfig=json_encode($this->extra_config);
            } catch(Exception $je) {
                NApp::Elog($je);
                $lextraconfig='undefined';
            }//END try
        } elseif(is_string($this->extra_config) && strlen($this->extra_config)) {
            $lextraconfig='{'.trim($this->extra_config,'}{').'}';
        }//if(is_array($this->extra_config))
        NApp::AddJsScript("CreateCkEditor('{$this->phash}','{$this->tag_id}',false,".$lextraconfig.$lwidth.$lheight.");");
        return $result;
    }//END protected function SetControl

    /**
     * description
     *
     * @param bool $all
     * @return string
     */
    public function GetDestroyJsCommand($all=FALSE) {
        return $all ? "DestroyCkEditor('{$this->phash}');" : "DestroyCkEditor('{$this->phash}','{$this->tag_id}',false);";
    }//END public function GetDestroyJsCommand
}//END class CkEditor extends Control