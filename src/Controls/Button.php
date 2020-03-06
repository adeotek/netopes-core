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
/**
 * ClassName description
 * long_description
 *
 * @property mixed       tooltip
 * @property mixed       value
 * @property string|null icon
 * @package  NETopes\Controls
 */
class Button extends Control {
    /**
     * Button constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->postable=FALSE;
        $this->no_label=TRUE;
        $this->container=FALSE;
        parent::__construct($params);
    }//END public function __construct

    /**
     * @return string
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $tooltip='';
        $ttClass='';
        if(strlen($this->tooltip)) {
            $tooltip=' title="'.$this->tooltip.'"';
            $ttClass='clsTitleSToolTip';
        }//if(strlen($this->tooltip))
        $ttClass.=!strlen($this->value) ? (strlen($ttClass) ? ' ' : '').'io' : '';
        $icon=is_string($this->icon) && strlen($this->icon) ? '<i class="'.$this->icon.'" aria-hidden="true"></i>' : '';
        return "\t\t".'<button'.$this->GetTagId().$this->GetTagClass($ttClass).$this->GetTagAttributes().$this->GetTagActions().$tooltip.'>'.$icon.$this->value.'</button>'."\n";
    }//END protected function SetControl
}//END class Button extends Control