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
	 * ClassName description
	 *
	 * long_description
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class DivButton extends Control {

		public function __construct($params = NULL){
			$this->postable = FALSE;
			$this->no_label = TRUE;
			$this->container = FALSE;
			parent::__construct($params);
		}//END public function __construct
		/**
		 * description
		 *
		 * @param object|null $params Parameters object (instance of [Params])
		 * @return void
		 * @access public
		 */
		protected function SetControl() {
			$ltooltip = '';
			$ttclass = '';
			if(strlen($this->tooltip)) {
				$ltooltip = ' title="'.$this->tooltip.'"';
				$ttclass = 'clsTitleSToolTip';
			}//if(strlen($this->tooltip))
			$licon = strlen($this->icon)>0 ? '<i class="'.$this->icon.'"></i>' : '';
			$result = "\t\t".'<div'.$this->GetTagId().$this->GetTagClass($ttclass).$this->GetTagAttributes().$this->GetTagActions().$ltooltip.'>'.$licon.$this->value.'</div>'."\n";
			return $result;
		}//END protected function SetControl
	}//END class DivButton extends Control
?>