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
	 * Hidden input control
	 *
	 * Creates a hidden input with an initial value
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class HiddenInput extends Control {
		public function __construct($params = NULL){
			$this->container = FALSE;
			$this->no_label = TRUE;
			parent::__construct($params);
		}//END public function __construct

		protected function SetControl() {
			$result = '<input type="hidden"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().' value="'.$this->value.'">'."\n";
			return $result;
		}//END protected function SetControl
	}//END class HiddenInput extends Control
?>