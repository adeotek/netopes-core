<?php
/**
 * AssociationManager control class file
 *
 * Control class for associations management
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
    use NApp;
	/**
	 * AssociationManager control class
	 *
	 * Control class for associations management
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 * @abstract
	 */
	abstract class AssociationManager {
		/**
		 * @var    array Control dynamic properties array
		 * @access protected
		 */
		protected $pdata = [];
		/**
		 * @var    string Control instance hash
		 * @access protected
		 */
		protected $chash = NULL;
		/**
		 * @var    string Control instance UID
		 * @access protected
		 */
		protected $uid = NULL;
		/**
		 * @var    bool Page hash (window.name)
		 * @access public
		 */
		public $phash = NULL;
		/**
		 * @var    string Control base class
		 * @access protected
		 */
		protected $baseclass = '';
		/**
		 * @var    string Layout type: native(css grid)/bootstrap
		 * @access public
		 */
		public $layout_type = NULL;
		/**
		 * @var    string Row container CSS class base
		 * @access public
		 */
		public $rowcls = '';
		/**
		 * @var    string Column container CSS class base
		 * @access public
		 */
		public $colcls = '';
		/**
		 * @var    int Associated items box width in CSS columns
		 * @access public
		 */
		public $associated_box_cols_no = 5;
		/**
		 * @var    string Associated items box title
		 * @access public
		 */
		public $associated_box_title = 'Associated items';
		/**
		 * @var    string Name of display name field in the associated item array
		 * @access public
		 */
		public $associated_name_field = NULL;
		/**
		 * @var    string Name of state field in the associated item array
		 * @access public
		 */
		public $associated_state_field = NULL;
		/**
		 * @var    int Assignable items box width in CSS columns
		 * @access public
		 */
		public $assignable_box_cols_no = 5;
		/**
		 * @var    string Assignable items box title
		 * @access public
		 */
		public $assignable_box_title = 'Assignable items';
		/**
		 * @var    string Name of display name field in the assignable item array
		 * @access public
		 */
		public $assignable_name_field = NULL;
		/**
		 * @var    string Name of state field in the assignable item array
		 * @access public
		 */
		public $assignable_state_field = NULL;
		/**
		 * @var    string Base tags id string
		 * @access public
		 */
		public $tagid = NULL;
		/**
		 * @var    bool Sortable associated items on/off
		 * @access public
		 */
		public $sortable = FALSE;
		/**
		 * @var    bool Allow multiple associations for one element
		 * @access public
		 */
		public $allow_multi_assoc = TRUE;
		/**
		 * @var    bool Display live version box on/off
		 * @access public
		 */
		public $show_live_version = FALSE;
		/**
		 * @var    int Live version associated items box width in CSS columns
		 * @access public
		 */
		public $live_version_box_cols_no = 2;
		/**
		 * @var    string Live version associated items box title
		 * @access public
		 */
		public $live_version_box_title = 'Live version';
		/**
		 * Control class dynamic getter method
		 *
		 * @param  string $name The name o the property
		 * @return mixed Returns the value of the property
		 * @access public
		 */
		public function __get($name) {
			return (is_array($this->pdata) && array_key_exists($name,$this->pdata)) ? $this->pdata[$name] : NULL;
		}//END public function __get
		/**
		 * Control class dynamic setter method
		 *
		 * @param  string $name The name o the property
		 * @param  mixed  $value The value to be set
		 * @return void
		 * @access public
		 */
		public function __set($name,$value) {
			if(!is_array($this->pdata)) { $this->pdata = []; }
			$this->pdata[$name] = $value;
		}//END public function __set
		/**
		 * AssociationManager class constructor method
		 *
		 * @param  array $params Parameters array
		 * @return void
		 * @access public
		 */
		public function __construct($params = NULL) {
			$this->chash = \PAF\AppSession::GetNewUID(get_class_basename($this));
			$this->uid = \PAF\AppSession::GetNewUID(get_class_basename($this),'md5');
			$this->baseclass = get_array_param($params,'clear_baseclass',FALSE,'bool') ? '' : 'clsAssociationManager';
			$this->layout_type = AppConfig::app_theme_type();
			if(is_array($params) && count($params)) {
				if(!is_array($this->pdata)) { $this->pdata = []; }
				foreach($params as $k=>$v) {
					if(property_exists($this,$k)) { $this->$k = $v; }
					else { $this->pdata[$k] = $v; }
				}//foreach ($params as $k=>$v)
			}//if(is_array($params) && count($params))
			if(!is_string($this->tagid) || !strlen($this->tagid)) { $this->tagid = date('siHdmY'); }
			$this->lis_box_tagid = $this->tagid.'-lis-list';
			$this->sis_box_tagid = $this->tagid.'-sis-list';
			$this->ais_box_tagid = $this->tagid.'-ais-list';
			switch(strtolower($this->layout_type)) {
				case 'bootstrap2':
				case 'bootstrap3':
				case 'bootstrap4':
					$this->rowcls = '';
					$this->colcls = 'col-md-';
					break;
				default:
					$this->rowcls = 'row';
					$this->colcls = 'col-md-';
					break;
			}//END switch
			if(!strlen($this->sort_module) || !strlen($this->sort_method)) { $this->sortable = FALSE; }
		}//END public function __construct
		/**
		 * Get associated items actions HTML
		 *
		 * @return void
		 * @access protected
		 */
		protected function GetAssociatedItemsActions() {
			$result = "\t\t\t".'<div class="subFormActions clearfix">'."\n";
			$btn_sel = new Button(['tagid'=>$this->tagid.'-sis-sel-all','class'=>'btn btn-info btn-xxs','value'=>\Translate::Get('button_select_all')]);
			$result .= "\t\t\t\t".$btn_sel->Show()."\n";
			$btn_desel = new Button(['tagid'=>$this->tagid.'-sis-desel-all','class'=>'btn btn-default btn-xxs','value'=>\Translate::Get('button_deselect_all')]);
			$result .= "\t\t\t\t".$btn_desel->Show()."\n";
			$result .= $this->GetDeAssignItemsAction();
			$result .= "\t\t\t".'</div>'."\n";
			return $result;
		}//END protected function GetAssociatedItemsActions
		/**
		 * Sets associated items javascript actions
		 *
		 * @return void
		 * @access protected
		 */
		protected function SetAssociatedItemsJs() {
			$sis_js = "
				$('#{$this->tagid}-sis-sel-all').on('click',function() {
					$('#{$this->sis_box_tagid} input[type=image].clsCheckBox').val('1');
				});
				$('#{$this->tagid}-sis-desel-all').on('click',function() {
					$('#{$this->sis_box_tagid} input[type=image].clsCheckBox').val('0');
				});
			";
			if($this->sortable) {
				$sis_js .= "
				$('#{$this->sis_box_tagid}').sortable({
					placeholder: 'ui-state-highlight',
					update: function(event,ui) {
						var elid = $(ui.item).attr('id');
						var previd = 0;
						var newindex = $(ui.item).index();
						if(newindex>0) { previd = $(ui.item).prev().attr('id'); }					
						".NApp::arequest()->Prepare("AjaxRequest('{$this->sort_module}','{$this->sort_method}','id'|elid~'after_id'|previd,'{$this->sort_target}')->errors-<elid-<previd")."
					}
				});
				$('#{$this->sis_box_tagid}').disableSelection();
				";
			}//if($this->sortable)
			NApp::_ExecJs($sis_js);
		}//END protected function SetAssociatedItemsJs
		/**
		 * Get associated item display name
		 *
		 * @return string Returns associated item name
		 * @access protected
		 */
		protected function GetAssociatedItemName($row) {
			return get_array_param($row,$this->associated_name_field,'N/A','is_string');
		}//END protected function GetAssociatedItemName
		/**
		 * Get associated item
		 *
		 * @return string Returns associated item HTML
		 * @access protected
		 */
		protected function GetAssociatedItem($row) {
			$item_id = get_array_param($row,'id','','is_integer');
			$item_name = $this->GetAssociatedItemName($row);
			$liclass = strlen($this->associated_item_class) ? ' '.$this->associated_item_class : '';
			$itclass = get_array_param($row,$this->associated_state_field,0,'is_numeric')<=0 ? ' inactive' : '';
			$result = "\t\t\t\t\t".'<li class="ui-state-default'.$liclass.'" id="'.$item_id.'">'."\n";
			$ckb_sel = new CheckBox(array('container'=>FALSE,'no_label'=>TRUE,'tagid'=>$this->tagid.'-sis-sel-'.$item_id,'tagname'=>$item_id,'value'=>0,'class'=>'FInLine'));
			$result .= "\t\t\t\t\t\t".$ckb_sel->Show()."\n";
			if($this->sortable) {
				$result .= "\t\t\t\t\t".'<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'."\n";
			}//if($this->sortable)
			$result .= "\t\t\t\t\t\t".'<span class="txt'.$itclass.'">'.$item_name.'</span>'."\n";
			$result .= "\t\t\t\t\t".'</li>'."\n";
			return $result;
		}//END protected function GetAssociatedItem
		/**
		 * Get associated items summary
		 *
		 * @param array   $data Data item array
		 * @return string Returns associated summary box HTML
		 * @access protected
		 */
		protected function GetAssociatedItemsSummary($data,$extra = NULL) {
			$items_no = is_array($data) ? count($data) : 0;
			$result = "\t\t\t\t".'<div class="subFormSummary">'."\n";
			$result .= "\t\t\t\t\t".'<span class="count">'.$items_no.'</span>'."\n";
			$result .= "\t\t\t\t\t".'<label>&nbsp;'.\Translate::Get('label_items').'</label>'."\n";
			$result .= "\t\t\t\t".'</div>'."\n";
			return $result;
		}//END protected function GetAssociatedItemsSummary
		/**
		 * Get associated items box
		 *
		 * @return string Returns associated box HTML
		 * @access protected
		 */
		protected function GetAssociatedItemsBox() {
			try {
				$items = $this->LoadAssociatedItems();
			} catch(\PAF\AppException $e) {
				NApp::_Elog($e->getMessage());
				$items = [];
			}//END try
			$result = "\t\t\t".'<div class="clsBlock clsAssociatedItems">'."\n";
			$result .= "\t\t\t\t".'<span class="clsBoxTitle">'.$this->associated_box_title.'</span>'."\n";
			$result .= $this->GetAssociatedItemsSummary($items);
			$result .= $this->GetAssociatedItemsActions();
			$result .= "\t\t\t\t".'<div class="subFormMsg msgErrors" id="'.$this->tagid.'-sis-errors">&nbsp;</div>'."\n";
			$result .= "\t\t\t\t".'<ul id="'.$this->sis_box_tagid.'" class="items '.($this->sortable ? ' sortable' : '').'">'."\n";
			if(is_array($items) && count($items)) {
				foreach($items as $v) { $result .= $this->GetAssociatedItem($v); }
				$this->SetAssociatedItemsJs();
			} else {
				$result .= "\t\t".'<li class="bold ErrorMsg">'.\Translate::Get('label_empty_list').'</li>'."\n";
			}//if(is_array($items) && count($items))
			$result .= "\t\t\t\t".'</ul>'."\n";
			$result .= "\t\t\t".'</div>'."\n";
			return $result;
		}//END public function GetAssociatedItemsBox
		/**
		 * Get assignable items actions HTML
		 *
		 * @return void
		 * @access protected
		 */
		protected function GetAssignableItemsActions() {
			$result = "\t\t\t".'<div class="subFormActions clearfix">'."\n";
			$btn_sel = new Button(['tagid'=>$this->tagid.'-ais-sel-all','class'=>'btn btn-info btn-xxs','value'=>\Translate::Get('button_select_all')]);
			$result .= "\t\t\t\t".$btn_sel->Show()."\n";
			$btn_desel = new Button(['tagid'=>$this->tagid.'-ais-desel-all','class'=>'btn btn-default btn-xxs','value'=>\Translate::Get('button_deselect_all')]);
			$result .= "\t\t\t\t".$btn_desel->Show()."\n";
			$result .= $this->GetAssignItemsAction();
			$result .= "\t\t\t".'</div>'."\n";
			return $result;
		}//END protected function GetAssignableItemsActions
		/**
		 * Sets assignable items javascript actions
		 *
		 * @return void
		 * @access protected
		 */
		protected function SetAssignableItemsJs() {
			$ais_js = "
				$('#{$this->tagid}-ais-sel-all').on('click',function() {
					$('#{$this->ais_box_tagid} input[type=image].clsCheckBox').val('1');
				});
				$('#{$this->tagid}-ais-desel-all').on('click',function() {
					$('#{$this->ais_box_tagid} input[type=image].clsCheckBox').val('0');
				});
			";
			NApp::_ExecJs($ais_js);
		}//END protected function SetAssignableItemsJs
		/**
		 * Get associated item display name
		 *
		 * @return string Returns associated item name
		 * @access protected
		 */
		protected function GetAssignableItemName($row) {
			return get_array_param($row,$this->assignable_name_field,'N/A','is_string');
		}//END protected function GetAssignableItemName
		/**
		 * Get assignable item
		 *
		 * @return string Returns assignable item HTML
		 * @access protected
		 */
		protected function GetAssignableItem($row) {
			$item_id = get_array_param($row,'id','','is_numeric');
			$is_associated = get_array_param($row,'assoc',0,'is_numeric')==1;
			if($this->allow_multi_assoc===FALSE && $is_associated) { return ''; }
			$item_name = $this->GetAssignableItemName($row);
			$liclass = strlen($this->assignable_item_class) ? ' '.$this->assignable_item_class : '';
			$itclass = $is_associated ? ' associated' : '';
			$itclass .= get_array_param($row,$this->assignable_state_field,0,'is_numeric')<=0 ? ' inactive' : '';
			$result = "\t\t\t\t\t".'<li class="ui-state-default'.$liclass.'" id="'.$item_id.'">'."\n";
			$ckb_sel = new CheckBox(array('container'=>FALSE,'no_label'=>TRUE,'tagid'=>$this->tagid.'-ais-sel-'.$item_id,'tagname'=>$item_id,'value'=>0,'class'=>'FInLine'));
			$result .= "\t\t\t\t\t\t".$ckb_sel->Show()."\n";
			$result .= "\t\t\t\t\t\t".'<span class="txt'.$itclass.'">'.$item_name.'</span>'."\n";
			$result .= "\t\t\t\t\t".'</li>'."\n";
			return $result;
		}//END protected function GetAssignableItem
		/**
		 * Get assignable items summary
		 *
		 * @param array   $data Data item array
		 * @return string Returns assignable summary box HTML
		 * @access protected
		 */
		protected function GetAssignableItemsSummary($data,$extra = NULL) {
			$items_no = is_array($data) ? count($data) : 0;
			$result = "\t\t\t\t".'<div class="subFormSummary">'."\n";
			$result .= "\t\t\t\t\t".'<span class="count">'.$items_no.'</span>'."\n";
			$result .= "\t\t\t\t\t".'<label>&nbsp;'.\Translate::Get('label_items').'</label>'."\n";
			$result .= "\t\t\t\t".'</div>'."\n";
			return $result;
		}//END protected function GetAssignableItemsSummary
		/**
		 * Get assignable items box
		 *
		 * @return string Returns assignable box HTML
		 * @access protected
		 */
		protected function GetAssignableItemsBox() {
			try {
				$items = $this->LoadAssignableItems();
			} catch(\PAF\AppException $e) {
				NApp::_Elog($e->getMessage());
				$items = [];
			}//END try
			$result = "\t\t\t".'<div class="clsBlock clsAssignableItems">'."\n";
			$result .= "\t\t\t\t".'<span class="clsBoxTitle">'.$this->assignable_box_title.'</span>'."\n";
			$result .= $this->GetAssignableItemsSummary($items);
			$result .= $this->GetAssignableItemsActions();
			$result .= "\t\t\t\t".'<div class="subFormMsg msgErrors clearfix" id="'.$this->tagid.'-ais-errors">&nbsp;</div>'."\n";
			$result .= "\t\t\t\t".'<ul id="'.$this->ais_box_tagid.'" class="items">'."\n";
			if(is_array($items) && count($items)) {
				foreach($items as $v) { $result .= $this->GetAssignableItem($v); }
				$result .= $this->SetAssignableItemsJs();
			} else {
				$result .= "\t\t\t\t".'<li class="bold ErrorMsg">'.\Translate::Get('label_empty_list').'</li>'."\n";
			}//if(is_array($items) && count($items))
			$result .= "\t\t\t\t".'</ul>'."\n";
			$result .= "\t\t\t".'</div>'."\n";
			return $result;
		}//END public function GetAssignableItemsBox
		/**
		 * Get live version associated item
		 *
		 * @return string Returns live version associated item HTML
		 * @access protected
		 */
		protected function GetLiveVersionItem($row) {
			$item_id = get_array_param($row,'id','','is_numeric');
			$item_name = $this->GetAssociatedItemName($row);
			$liclass = strlen($this->associated_item_class) ? ' '.$this->associated_item_class : '';
			$itclass = get_array_param($row,$this->associated_state_field,0,'is_numeric')<=0 ? ' inactive' : '';
			$result = "\t\t\t\t\t".'<li class="ui-state-default'.$liclass.'" id="'.$item_id.'">'."\n";
			$result .= "\t\t\t\t\t\t".'<span class="txt'.$itclass.'">'.$item_name.'</span>'."\n";
			$result .= "\t\t\t\t\t".'</li>'."\n";
			return $result;
		}//END protected function GetLiveVersionItem
		/**
		 * Get live version associated items summary
		 *
		 * @param array   $data Data item array
		 * @return string Returns live version associated summary box HTML
		 * @access protected
		 */
		protected function GetLiveVersionItemsSummary($data,$extra = NULL) {
			return $this->GetAssociatedItemsSummary($data,$extra);
		}//END protected function GetLiveVersionItemsSummary
		/**
		 * Get live version associated items box
		 *
		 * @return string Returns associated box HTML
		 * @access protected
		 */
		protected function GetLiveVersionItemsBox() {
			try {
				$items = $this->LoadLiveVersionItems();
			} catch(\PAF\AppException $e) {
				NApp::_Elog($e->getMessage());
				$items = [];
			}//END try
			if($items===FALSE) { return NULL; }
			$result = "\t\t\t".'<div class="clsBlock clsLiveVersionItems">'."\n";
			$result .= "\t\t\t\t".'<span class="clsBoxTitle">'.$this->live_version_box_title.'</span>'."\n";
			$result .= $this->GetLiveVersionItemsSummary($items);
			$result .= "\t\t\t\t".'<div class="subFormActions empty"></div>'."\n";
			$result .= "\t\t\t\t".'<div class="subFormMsg msgErrors" id="'.$this->tagid.'-lis-errors">&nbsp;</div>'."\n";
			$result .= "\t\t\t\t".'<ul id="'.$this->lis_box_tagid.'" class="items">'."\n";
			if(is_array($items) && count($items)) {
				foreach($items as $v) { $result .= $this->GetLiveVersionItem($v); }
			} else {
				$result .= "\t\t\t\t\t".'<li class="bold ErrorMsg">'.\Translate::Get('label_empty_list').'</li>'."\n";
			}//if(is_array($items) && count($items))
			$result .= "\t\t\t\t".'</ul>'."\n";
			$result .= "\t\t\t".'</div>'."\n";
			return $result;
		}//END public function GetLiveVersionItemsBox
		/**
		 * Sets the output buffer value
		 *
		 * @return string Returns the complete HTML for the control
		 * @access protected
		 */
		protected function SetControl() {
			$live_box = $this->GetLiveVersionItemsBox();
			$result = '<div class="'.$this->rowcls.' '.$this->baseclass.' clsPanel">'."\n";
			$result .= "\t".'<div class="clsDivTable">'."\n";
			if($this->show_live_version && strlen($live_box)) {
				$sis_cols = $ais_cols = $lv_cols = 4;
				if(is_numeric($this->live_version_box_cols_no) && $this->live_version_box_cols_no>0  && $this->live_version_box_cols_no<=10) {
					$lv_cols = $this->live_version_box_cols_no;
					$sis_cols = $ais_cols = ceil((12-$lv_cols)/2);
				}//if(is_numeric($this->live_version_box_cols_no) && $this->live_version_box_cols_no>0  && $this->live_version_box_cols_no<=10)
				$fcol_class = ' clsMiddlePanel';
				$result .= "\t\t".'<div class="'.$this->colcls.$lv_cols.' clsDivTableCell clsLeftPanel" id="'.$this->tagid.'-lis">'."\n";
				$result .= $live_box;
				$result .= "\t\t".'</div>'."\n";
			} else {
				$lv_cols = 0;
				$sis_cols = $ais_cols = 6;
				$fcol_class = ' clsLeftPanel';
			}//if($this->show_live_version && strlen($live_box))
			$fixed_sis_cols = FALSE;
			if(is_numeric($this->associated_box_cols_no) && $this->associated_box_cols_no>0  && $this->associated_box_cols_no<12) {
				$sis_cols = $this->associated_box_cols_no;
				$ais_cols = 12-$lv_cols-$sis_cols;
				$fixed_sis_cols = TRUE;
			}//if(is_numeric($this->associated_box_cols_no) && $this->associated_box_cols_no>0  && $this->associated_box_cols_no<=10)
			if(is_numeric($this->assignable_box_cols_no) && $this->assignable_box_cols_no>0  && $this->assignable_box_cols_no<12) {
				$ais_cols = $this->assignable_box_cols_no;
				if(!$fixed_sis_cols) { $sis_cols = 12-$lv_cols-$sis_cols; }
			}//if(is_numeric($this->assignable_box_cols_no) && $this->assignable_box_cols_no>0  && $this->assignable_box_cols_no<=10)
			$result .= "\t\t".'<div class="'.$this->colcls.$sis_cols.' clsDivTableCell'.$fcol_class.'" id="'.$this->tagid.'-sis">'."\n";
			$result .= $this->GetAssociatedItemsBox();
			$result .= "\t\t".'</div>'."\n";
			$result .= "\t\t".'<div class="'.$this->colcls.$ais_cols.' clsDivTableCell clsRightPanel" id="'.$this->tagid.'-ais">'."\n";
			$result .= $this->GetAssignableItemsBox();
			$result .= "\t\t".'</div>'."\n";
			$result .= "\t".'</div>'."\n";
			$result .= '</div>'."\n";
			return $result;
		}//END private function SetControl
		/**
		 * Gets the output buffer content
		 *
		 * @return string Returns or outputs the content (html)
		 * @access public
		 */
		public function Show($output = FALSE) {
			if(!$output) { return $this->SetControl(); }
			echo $this->SetControl();
		}//END public function Show
		/**
		 * Load live version associated items
		 *
		 * @return array Returns live version associated items array
		 * @access protected
		 */
		protected function LoadLiveVersionItems() {
			return FALSE;
		}//END protected function LoadLiveVersionItems
		/**
		 * Load associated items
		 *
		 * @return array Returns associated items array
		 * @access protected
		 * @abstract
		 */
		abstract protected function LoadAssociatedItems();
		/**
		 * Load assignable items
		 *
		 * @return array Returns associated items array
		 * @access protected
		 * @abstract
		 */
		abstract protected function LoadAssignableItems();
		/**
		 * Get assign item(s) action button
		 *
		 * @return string Assign item(s) action button HTML
		 * @access protected
		 * @abstract
		 */
		abstract protected function GetAssignItemsAction();
		/**
		 * Get de-assign item(s) action button
		 *
		 * @return string De-assign item(s) action button HTML
		 * @access protected
		 * @abstract
		 */
		abstract protected function GetDeAssignItemsAction();
	}//END abstract class AssociationManager
?>