<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\AppException;
/**
 * Container control
 * Control for a container (DIV)
 * The [value] property is the initial content of the container.
 * @package  NETopes\Controls
 */
class Container extends Control {
    /**
     * Container constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
	public function __construct($params = NULL) {
		$this->postable = FALSE;
		parent::__construct($params);
	}//END public function __construct
    /**
     * @return string|null
     */
	protected function SetControl(): ?string {
        try {
            $result='<div'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().'>'.$this->value.'</div>'."\n";
        } catch(AppException $e) {
            $result = NULL;
        }//END try
		return $result;
	}//END protected function SetControl
}//END class Container extends Control