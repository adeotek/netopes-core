<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * Container control
	 *
	 * Control for a container (DIV)
	 * The [value] property is the initial content of the container.
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class Container extends Control {
		public function __construct($params = NULL) {
			$this->postable = FALSE;
			parent::__construct($params);
		}//END public function __construct
		protected function SetControl(): ?string {
			$result = '<div'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().'>'.$this->value.'</div>'."\n";
			return $result;
		}//END protected function SetControl
	}//END class Container extends Control
?>