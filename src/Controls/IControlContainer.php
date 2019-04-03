<?php
/**
 * Control container interface file
 * Interface for controls containers
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
/**
 * Control container interface
 * Interface for controls containers
 *
 * @package  NETopes\Core\Controls
 */
interface IControlContainer {
    /**
     * Control container class constructor
     *
     * @param $control
     */
    function __construct($control);

    /**
     * @param string      $content
     * @param string|null $secondaryContent
     * @return string
     */
    public function GetHtml(string $content,?string $secondaryContent=NULL);
}//END interface IControlContainer