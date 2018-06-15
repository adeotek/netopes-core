<?php
/**
 * Control container interface file
 *
 * Interface for controls containers
 *
 * @package    NETopes\Core\Classes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
    namespace NETopes\Core\Classes\Controls;
	/**
	 * Control container interface
	 *
	 * Interface for controls containers
	 *
	 * @package  NETopes\Core\Classes\Controls
	 */
	interface IControlContainer {
		function __construct($control);
		function GetHtml($content);
	}//END interface IControlContainer