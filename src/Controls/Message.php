<?php
/**
 * Message control class file
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * Class Message
 * Control for displaying a label/message
 *
 * @property string|null text
 * @property string|null value
 * @package  NETopes\Controls
 */
class Message extends Control {
    /**
     * Message constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->postable=FALSE;
        $this->no_label=TRUE;
        $this->text=NULL;
        parent::__construct($params);
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $lValue=strlen($this->text) ? $this->text : $this->value;
        $result="\t\t".'<span'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().'>'.$lValue.'</span>'."\n";
        return $result;
    }//END protected function SetControl
}//END class Message extends Control