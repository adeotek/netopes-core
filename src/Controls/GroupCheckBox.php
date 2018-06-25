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
	 * GroupCheckBox class
	 *
	 * Control class for group checkbox (radio button alternative)
	 *
	 * @package  Hinter\NETopes\Controls
	 * @access   public
	 */
	class GroupCheckBox extends Control {
		/**
		 * @var    array Elements data source
		 * @access public
		 */
		public $data_source = [];
		/**
		 * @var    array Elements list
		 * @access public
		 */
		public $items = [];
		/**
		 * @var    string Elements list orientation
		 * @access public
		 */
		public $orientation = 'horizontal';
		/**
		 * @var    string Elements default value field name
		 * @access public
		 */
		public $default_value_field = NULL;
		/**
		 * @var    mixed Initial value
		 * @access public
		 */
		public $value = NULL;
		/**
		 * Control class constructor
		 *
		 * @param  array $params An array of params
		 * @return void
		 * @access public
		 */
		public function __construct($params = NULL) {
			parent::__construct($params);
			if(!strlen($this->tagid)) { $this->tagid = \PAF\AppSession::GetNewUID('GroupCheckBox','md5'); }
		}//END public function __construct

		protected function GetItems() {
			if(is_array($this->items)) { return; }
			$ds_class = get_array_param($this->data_source,'ds_class','','is_string');
			$ds_method = get_array_param($this->data_source,'ds_method','','is_string');
			if(!strlen($ds_class) || !strlen($ds_method) || !DataProvider::MethodExists($ds_class,$ds_method)) { return; }
			$ds_params = get_array_param($this->data_source,'ds_params',[],'is_array');
			$ds_extra_params = get_array_param($this->data_source,'ds_extra_params',[],'is_array');
			$this->items = DataProvider::GetArray($ds_class,$ds_method,$ds_params,$ds_extra_params);
		}//END protected function GetItems

		protected function SetControl() {
			$this->GetItems();
			$idfield = isset($this->id_field) && strlen($this->id_field) ? $this->id_field : 'id';
			$labelfield = isset($this->label_field) && strlen($this->label_field) ? $this->label_field : 'name';
			$statefield = isset($this->state_field) && strlen($this->state_field) ? $this->state_field : NULL;
			$activestate = isset($this->active_state) && strlen($this->active_state) ? $this->active_state : '1';
			$this->ProcessActions();
			$result = '<div id="'.$this->tagid.'-container" class='.$this->GetTagClass('clsGCKBContainer',TRUE).'">'."\n";
			$result .= "\t".'<input type="hidden" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagActions().' value="'.$this->value.'">'."\n";
			$ul_class = 'clsGCKBList '.(strtolower($this->orientation)==='vertical' ? 'oVertical' : 'oHorizontal');
			$result .= "\t".'<ul class="'.$ul_class.'">'."\n";
			if(is_array($this->items) && count($this->items)) {
				foreach($this->items as $k=>$v) {
					$i_value = get_array_param($v,$idfield,NULL,'is_string');
					$i_label = get_array_param($v,$labelfield,'','is_string');
					if(isset($this->value)) {
						$i_val = $i_value==$this->value ? 1 : 0;
					} elseif(strlen($this->default_value_field)) {
						$i_val = get_array_param($v,$this->default_value_field,0,'is_numeric')==1 ? 1 : 0;
					} else {
						$i_val = 0;
					}//if(isset($this->value))
					if($this->disabled || $this->readonly) {
						$i_active = FALSE;
					} else {
						$i_active = strlen($statefield) ? get_array_param($v,$statefield,NULL,'is_string')==$activestate : TRUE;
					}//if($this->disabled || $this->readonly)
					$result .= "\t\t".'<li><input type="image" class="clsGCKBItem'.($i_active ? ' active' : ' disabled').'" data-id="'.$this->tagid.'" data-val="'.$i_value.'" src="'.NApp::app_web_link().'/lib/controls/images/transparent.gif" value="'.$i_val.'"><label class="clsGCKBLabel">'.$i_label.'</label></li>'."\n";
				}//END foreach
			} else {
				$result .= "\t\t<li><span class=\"clsGCKBBlank\">".\Translate::Get('label_no_elements')."</span></li>\n";
			}//if(is_array($this->items) && count($this->items))
			$result .= "\t".'</ul>'."\n";
			$result .= '</div>'."\n";
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class GroupCheckBox extends Control
?>