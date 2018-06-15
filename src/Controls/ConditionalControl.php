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
	 * Conditional control
	 *
	 * Control for an dynamically select a control based on given conditions
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class ConditionalControl extends Control {
		/**
		 * @var    array Controls parameters array
		 * @access public
		 */
		public $items = array();

		public function __construct($params = NULL){
			$this->postable = FALSE;
			$this->container = FALSE;
			$this->no_label = TRUE;
			parent::__construct($params);
		}//END public function __construct

		protected function SetControl() {
			$result = NULL;
			if(!is_array($this->items) || !count($this->items)) { return $result; }
			foreach($this->items as $c_name=>$c_params) {
				if(!is_string($c_name) || !strlen($c_name) || !class_exists($c_name)) { continue; }
				try {
					if(isset($c_params['conditions']) && is_array($c_params['conditions']) && !self::CheckConditions($c_params['conditions'])) { continue; }
					$ctrl = new $c_name($c_params);
					$result = $ctrl->Show();
					break;
				} catch(\PAF\AppException $e) {
					continue;
				}//END try
			}//END foreach
			return $result;
		}//END protected function SetControl
	}//END class ConditionalControl extends Control
?>