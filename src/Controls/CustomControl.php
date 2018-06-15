<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
    namespace NETopes\Core\Classes\Controls;
	/**
	 * CustomControl class
	 *
	 * Control class for passing HTML elements directly
	 *
	 * @package  Hinter\NETopes\Controls
	 * @access   public
	 */
	class CustomControl extends Control {
		public function __construct($params = NULL){
			$this->postable = FALSE;
			$this->container = FALSE;
			$this->no_label = TRUE;
			parent::__construct($params);
		}//END public function __construct

		protected function SetControl() {
			return $this->value;
		}//END protected function SetControl
	}//END class CustomControl extends Control
?>