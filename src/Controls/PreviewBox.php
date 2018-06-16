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
    namespace NETopes\Core\Controls;
	/**
	 * PreviewBox control
	 *
	 * Control for previewing HTML content
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class PreviewBox extends Control {
		public function __construct($params = NULL){
			$this->postable = FALSE;
			$this->labelposition = 'left';
			parent::__construct($params);
		}//END public function __construct

		protected function SetControl() {
			$result = "\t\t".'<div'.$this->GetTagId(2).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().'>'.$this->value.'</div>'."\n";
			return $result;
		}//END protected function SetControl
	}//END class PreviewBox extends Control
?>