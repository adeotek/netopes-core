<?php
/**
 * Control container interface file
 *
 * Interface for controls containers
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * Control container interface
	 *
	 * Interface for controls containers
	 *
	 * @package  NETopes\Core\Controls
	 */
	interface IControlContainer {
		function __construct($control);
		function GetHtml($content);
	}//END interface IControlContainer