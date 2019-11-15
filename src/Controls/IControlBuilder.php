<?php
/**
 * IControlBuilder interface file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.9.7
 * @filesource
 */
namespace NETopes\Core\Controls;

/**
 * Interface IControlBuilder
 *
 * @package NETopes\Core\Controls
 */
interface IControlBuilder {
    /**
     * @return array
     */
    public function GetConfig(): array;
}//END interface IControlBuilder