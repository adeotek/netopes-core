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
	use NETopes\Core\App\ModuleProvider;
    use NETopes\Core\Data\DataProvider;
	/**
	 * Control iterator control
	 *
	 * Control for iterating a specific simple control
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class ControlIterator extends Control {
		/**
		 * @var    string Iterator type (array/dataadapter/module)
		 * @access public
		 */
		public $iterator_type = 'array';
		/**
		 * @var    string Iterator class name (dataadapter/module name)
		 * @access public
		 */
		public $iterator_name = NULL;
		/**
		 * @var    string Iterator method (dataadapter/module method)
		 * @access public
		 */
		public $iterator_method = NULL;
		/**
		 * @var    array Iterator parameters array
		 * @access public
		 */
		public $iterator_params = NULL;
		/**
		 * @var    array Iterator items array
		 * @access public
		 */
		public $items = [];
		/**
		 * @var    string Dynamic control parameters prefix
		 * @access public
		 */
		public $params_prefix = '';
		/**
		 * @var    string Control class name
		 * @access public
		 */
		public $control = NULL;
		/**
		 * @var    array Control parameters array
		 * @access public
		 */
		public $params = [];
		/**
		 * @var    array Iterator conditions (if TRUE control is shown, else not)
		 * @access public
		 */
		public $conditions = [];
		// /**
		//  * @var    array Control dynamic parameters array
		//  * @access public
		//  */
		// public $dynamic_params = [];

		public function __construct($params = NULL) {
			$this->postable = FALSE;
			parent::__construct($params);
			switch($this->theme_type) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					$this->container = FALSE;
					$this->no_label = TRUE;
					break;
				default:
					break;
			}//END switch
		}//END public function __construct

		protected function GetItems() {
			switch(strtolower($this->iterator_type)) {
				case 'module':
					$result = NULL;
					if(is_string($this->iterator_name) && strlen($this->iterator_name) && is_string($this->iterator_method) && strlen($this->iterator_method) && ModulesProvider::ModuleMethodExists($this->iterator_name,$this->iterator_method)) {
						$iparams = is_array($this->iterator_params) ? $this->iterator_params : [];
						$result = ModulesProvider::Exec($this->iterator_name,$this->iterator_method,$iparams);
					}//if(...
					$items = is_array($result) ? $result : [];
					break;
				case 'DataSource':
					$result = NULL;
					if(is_string($this->iterator_name) && strlen($this->iterator_name) && is_string($this->iterator_method) && strlen($this->iterator_method) && DataProvider::MethodExists($this->iterator_name,$this->iterator_method)) {
						$iparams = is_array($this->iterator_params) ? $this->iterator_params : [];
						$result = DataProvider::GetArray($this->iterator_name,$this->iterator_method,$iparams);
					}//if(...
					$items = is_array($result) ? $result : [];
					break;
				case 'array':
				default:
					$items = is_array($this->items) ? $this->items : [];
					break;
			}//END switch
			return $items;
		}//END protected function GetItems

		protected function SetControl() {
			$this->items = $this->GetItems();
			switch($this->theme_type) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					$lcontent = $this->container ? "\n" : '';
					$lprefix = '';
					$lsufix = '';
					break;
				default:
					if($this->container) {
						$lcontent = '';
						$lprefix = '';
						$lsufix = '';
					} else {
						$lcontent = "\n";
						$lprefix = '<div class="'.$this->baseclass.' clsRow">'."\n";
						$lsufix = '</div>'."\n";
					}//if($this->container)
					break;
			}//END switch
			if(strlen($this->control) && class_exists($this->control)) {
				foreach($this->items as $k=>$v) {
					if(is_array($this->conditions) && count($this->conditions)) {
						$iconditions = self::ReplaceDynamicParams($this->conditions,$v,TRUE,$this->params_prefix);
						if(!self::CheckRowConditions($v,$iconditions)) { continue; }
					}//if(is_array($this->conditions) && count($this->conditions))
					if(is_array($this->params)) {
						$lparams = self::ReplaceDynamicParams($this->params,$v,TRUE,$this->params_prefix);
					} else {
						$lparams = [];
					}//if(is_array($this->params))
					$ctrl = new $this->control($lparams);
					$lcontent .= $lprefix.$ctrl->Show()."\n".$lsufix;
					unset($ctrl);
					unset($lparams);
				}//END foreach
			}//if(strlen($this->control) && class_exists($this->control))
			switch($this->theme_type) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					$result = $lcontent;
					break;
				default:
					if($this->container) {
						$result = $lcontent;
					} else {
						$result = '<div'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().'>'.$lcontent.'</div>'."\n";
					}//if($this->container)
					break;
			}//END switch
			return $result;
		}//END protected function SetControl
	}//END class ControlIterator extends Control
?>