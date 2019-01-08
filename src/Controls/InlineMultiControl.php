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
	 * Inline multi-control control
	 *
	 * Control for an inline multi-control
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class InlineMultiControl extends Control {
		/**
		 * @var    array Controls parameters array
		 * @access public
		 */
		public $items = [];
		public function __construct($params = NULL){
			$this->postable = FALSE;
			parent::__construct($params);
		}//END public function __construct
		protected function SetControl(): ?string {
			$lcontent = '';
			if(is_array($this->items) && count($this->items)) {
				foreach($this->items as $c_name=>$c_params) {
					if(!is_string($c_name) || !strlen($c_name) || !class_exists($c_name)) { continue; }
					if(isset($c_params['conditions']) && is_array($c_params['conditions']) && !$this->CheckConditions($c_params['conditions'])) { continue; }
					$ctrl = new $c_name($c_params);
					$lcontent .= $ctrl->Show();
				}//END foreach
			}//if(is_array($this->items) && count($this->items))
			$result = '<div'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().'>'.$lcontent.'</div>'."\n";
			return $result;
		}//END protected function SetControl
	}//END class InlineMultiControl extends Control
?>