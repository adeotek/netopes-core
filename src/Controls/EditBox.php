<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
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
	class EditBox extends Control {
		public function __construct($params = null){
			$this->uc_first = 'none'; // posible values: none, first, all
			$this->maxlength = 255;
			$this->autoselect = TRUE;
			$this->textareacols = NULL;
			$this->textarearows = NULL;
			$this->height = NULL;
			parent::__construct($params);
		}//END public function __construct

		protected function SetControl() {
			switch (strtolower($this->uc_first)) {
				case 'first':
					$fclass = ' clsSetUcFirst';
					break;
				case 'all':
					$fclass = ' clsSetUcFirstAll';
					break;
				default:
					$fclass = '';
					break;
			}//switch (strtolower($this->uc_first))
			$lcols = $this->textareacols ? ' cols='.$this->textareacols : '';
			$lrows = $this->textarearows ? ' rows='.($this->textarearows-1) : '';
			$this->ProcessActions();
			$result = "\t\t".'<textarea'.$this->GetTagId(TRUE).$this->GetTagClass($fclass).$this->GetTagAttributes().$this->GetTagActions().$lcols.$lrows.'>'.$this->value.'</textarea>'."\n";
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class EditBox extends Control
?>