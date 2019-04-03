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
 * CustomControl class
 * Control class for passing HTML elements directly
 *
 * @package  Hinter\NETopes\Controls
 */
class CustomControl extends Control {
    public function __construct($params=NULL) {
        $this->postable=FALSE;
        $this->container=FALSE;
        $this->no_label=TRUE;
        parent::__construct($params);
    }//END public function __construct

    protected function SetControl(): ?string {
        return $this->value;
    }//END protected function SetControl
}//END class CustomControl extends Control
?>