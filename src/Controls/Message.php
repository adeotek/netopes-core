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
 * @version    2.5.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * Message control
	 *
	 * Control for displaying a label/message
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class Message extends Control {
		public function __construct($params = NULL) {
			$this->postable = FALSE;
			$this->no_label = TRUE;
			$this->text = NULL;
			parent::__construct($params);
		}//END public function __construct
		/**
		 * description
		 *
		 * @param object|null $params Parameters object (instance of [Params])
		 * @return void
		 * @access public
		 */
		protected function SetControl(): ?string {
			$lvalue = strlen($this->text) ? $this->text : $this->value;
			$result = "\t\t".'<span'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions().'>'.$lvalue.'</span>'."\n";
			return $result;
		}//END protected function SetControl
	}//END class Message extends Control
?>