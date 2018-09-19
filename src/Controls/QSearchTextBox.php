<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * ClassName description
	 *
	 * long_description
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class QSearchTextBox extends Control {
		public function __construct($params = null){
			$this->maxlength = 255;
			$this->autoselect = TRUE;
			parent::__construct($params);
		}//END public function __construct

		protected function SetControl() {
			$baseact = [];
			if($this->autoselect===TRUE) { $baseact['onclick'] = 'this.select();'; }
			$lmaxlength = (is_numeric($this->maxlength) && $this->maxlength>0) ? ' maxlength="'.$this->maxlength.'"' : '';
			$this->ProcessActions();
			$result = "\t\t".'<input type="text"'.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagAttributes().$this->GetTagActions($baseact).$lmaxlength.' value="'.$this->value.'">'."\n";
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class QSearchTextBox extends Control
?>