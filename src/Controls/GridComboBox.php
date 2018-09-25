<?php
/**
 * Grid combo box control class file
 *
 * long description
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
	 * Grid combo box control class
	 *
	 * long_description
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class GridComboBox extends Control {
		/**
		 * @var    string Data adapter name
		 * @access public
		 */
		public $data_source = NULL;
		/**
		 * @var    string Data adapter method name
		 * @access public
		 */
		public $ds_method = NULL;
		/**
		 * @var    array Data adapter method parameters array
		 * @access public
		 */
		public $ds_params = NULL;
		/**
		 * @var    array Data adapter method extra parameters array
		 * @access public
		 */
		public $ds_extra_params = NULL;
		/**
		 * @var    array Grid columns array
		 * @access public
		 */
		public $columns = NULL;
		/**
		 * @var    array Data grid control parameters array
		 * @access public
		 */
		public $grid_params = NULL;
		/**
		 * @var    string Target for ajax calls
		 * @access protected
		 */
		protected $target = NULL;
		/**
		 * @var    bool Drop down state (loaded or not)
		 * @access protected
		 */
		protected $loaded = FALSE;
		/**
		 * GridComboBox class constructor
		 *
		 * @param  array $params An array of params
		 * @return void
		 * @access public
		 */
		public function __construct($params = NULL) {
			$this->visible_tooltip = TRUE;
			$this->autoload = FALSE;
			parent::__construct($params);
			if(!strlen($this->data_source) || !strlen($this->ds_method) || !is_array($this->columns) || !count($this->columns)) {
				throw new \PAF\AppException('Wrong GridComboBox control parameters !',E_ERROR,1);
				return FALSE;
			}//if(!strlen($this->data_source) || !strlen($this->ds_method) || !is_array($this->columns) || !count($this->columns))
			$this->target = $this->tagid.'-gcbo-target';
		}//END public function __construct

		protected function SetControl() {
			$this->ProcessActions();
			$ar_class = '';
			if($this->required===TRUE) { $ar_class .= (strlen($ar_class) ? ' ' : '').'clsRequiredField'; }
			if($this->visible_tooltip===TRUE) { $ar_class .= (strlen($ar_class) ? ' ' : '').'clsGCBToolTip'; }
			$lclass =$this->GetTagClass($ar_class,TRUE);
			$lalign = strlen($this->align) ? ' text-align: '.$this->align.';' : '';
			$lwidth = (is_numeric($this->width) && $this->width>0) ? ($this->width-$this->GetActionsWidth()).'px' : $this->width;
			$ccstyle = $lwidth ? ' style="width: '.$lwidth.';"' : '';
			if($this->dropdown_width) {
				$ddstyle = ' style="display: none; width: '.$this->dropdown_width.(is_numeric($this->dropdown_width) ? 'px' : '').';"';
			} else {
				$ddstyle = ' style="display: none;'.($lwidth ? ' width: '.$lwidth.'px;' : '').'"';
			}//if($this->dropdown_width)
			$lstyle = (strlen($this->style) || strlen($lalign)) ? ' style="'.trim($lalign.' '.$this->style).'"' : '';
			$ltabindex = (is_numeric($this->tabindex) && $this->tabindex>0) ? ' tabindex="'.$this->tabindex.'"' : '';
			$lextratagparam = strlen($this->extratagparam)>0 ? ' '.$this->extratagparam : '';
			$lonchange = strlen($this->onchange)>0 ? ' data-onchange="'.$this->onchange.'"' : '';
			$lplaceholder = '';
			if(strlen($this->pleaseselecttext)>0) {
				$lplaceholder = ' placeholder="'.$this->pleaseselecttext.'"';
			}//if(strlen($this->pleaseselecttext)>0)
			$cclass = $this->baseclass.' ctrl-container'.(strlen($this->class)>0 ? ' '.$this->class : '');
			$ddbtnclass = $this->baseclass.' ctrl-dd-i-btn'.(strlen($this->class)>0 ? ' '.$this->class : '');
			if($this->disabled || $this->readonly) {
				$result = '<div id="'.$this->tagid.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
				$result .= "\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selectedvalue.'" class="'.$lclass.($this->postable ? ' postable' : '').'">'."\n";
				$result .= "\t".'<input type="text" id="'.$this->tagid.'-cbo" value="'.$this->selectedtext.'" data-value="'.$this->selectedvalue.'" class="'.$lclass.'"'.$lstyle.$lplaceholder.($this->disabled ? ' disabled="disabled"' : ' readonly="readonly"').$ltabindex.$lextratagparam.'>'."\n";
				$result .= "\t".'<div id="'.$this->tagid.'-ddbtn" class="'.$ddbtnclass.'"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
				$result .= '</div>'."\n";
				return $result;
			}//if($this->disabled || $this->readonly)
			$cbtnclass = $this->baseclass.' ctrl-clear'.(strlen($this->class) ? ' '.$this->class : '');
			$ldivclass = $this->baseclass.' ctrl-dropdown';
			$dparams = '';
			if(is_array($this->dynamic_params) && count($this->dynamic_params)) {
				foreach($this->dynamic_params as $dk=>$dv) { $dparams .= "~'dynf[{$dk}]'|$dv"; }
			}//if(is_array($this->dynamic_params) && count($this->dynamic_params))
			$dd_action = NApp::arequest()->Prepare("ControlAjaxRequest('$this->chash','ShowDropDown','selected_value'|{$this->tagid}:value~'qsearch'|{$this->tagid}-cbo:value~'text'|{$this->tagid}-cbo:attr:data-text{$dparams},'".$this->GetThis()."',1)->{$this->target}","function(s){ GCBOLoader(s,'{$this->tagid}'); }");
			$isvalue = strlen($this->selectedvalue) && $this->selectedvalue!=='null' ? $this->selectedvalue : NULL;
			$demptyval = strlen($this->empty_value) ? ' data-eval="'.$this->empty_value.'"' : '';
			$result = '<div id="'.$this->tagid.'-container" class="'.$cclass.'"'.$ccstyle.'>'."\n";
			$result .= "\t".'<input type="hidden"'.$this->GetTagId(TRUE).' value="'.$this->selectedvalue.'" class="'.$lclass.($this->postable ? ' postable' : '').'"'.$lonchange.' data-text="'.($isvalue ? $this->selectedtext : '').'"'.$demptyval.'>'."\n";
			$result .= "\t".'<input type="text" id="'.$this->tagid.'-cbo" value="'.($isvalue ? $this->selectedtext : '').'" class="'.$lclass.'"'.$lstyle.$lplaceholder.$ltabindex.$lextratagparam.' data-value="'.$this->selectedvalue.'" data-ajax="'.GibberishAES::enc($dd_action,$this->tagid).'" data-id="'.$this->tagid.'">'."\n";
			$result .= "\t".'<div id="'.$this->tagid.'-ddbtn" class="'.$ddbtnclass.'" onclick="GCBODDBtnClick(\''.$this->tagid.'\');"><i class="fa fa-caret-down" aria-hidden="true"></i></div>'."\n";
			$result .= "\t".'<div id="'.$this->tagid.'-clear" class="'.$cbtnclass.'" onclick="GCBOSetValue(\''.$this->tagid.'\',null,\'\',false);"></div>'."\n";
			if($this->autoload || $isvalue) {
				$result .= "\t".'<div id="'.$this->tagid.'-dropdown" class="'.$ldivclass.'"'.$ddstyle.' data-reload="0">';
				$result .= "\t\t".'<div class="gcbo-loader" style="display: none;"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i></div>'."\n";
				$result .= "\t\t".'<div id="'.$this->tagid.'-gcbo-target" class="gcbo-target">'."\n";
				if(strlen($dparams)) {
					if(NApp::ajax() && is_object(NApp::arequest())) {
						NApp::arequest()->ExecuteJs($dd_action);
					} else {
						$result .= "\t"."<script type=\"text/javascript\">{$dd_action}</script>"."\n";
					}//if(NApp::ajax() && is_object(NApp::arequest()))
				} else {
					$result .= $this->ShowDropDown(array('return'=>TRUE,'selected_value'=>$isvalue));
				}//if(strlen($dparams))
				$result .= "\t\t".'</div>'."\n";
				$result .= "\t".'</div>'."\n";
			} else {
				$result .= "\t".'<div id="'.$this->tagid.'-dropdown" class="'.$ldivclass.'"'.$ddstyle.' data-reload="1">'."\n";
				$result .= "\t\t".'<div class="gcbo-loader" style="display: none;"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i></div>'."\n";
				$result .= "\t\t".'<div id="'.$this->tagid.'-gcbo-target" class="gcbo-target"></div>'."\n";
				$result .= "\t".'</div>'."\n";
			}//if($this->autoload || $isvalue)
			$result .= '</div>'."\n";
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl

		public function ShowDropDown($params = NULL) {
			//NApp::_Dlog($params,'ShowDropDown');
			if(!is_object(NApp::arequest())) {
				throw new \PAF\AppException('Wrong ajax object for GridComboBox control !',E_ERROR,1);
				return;
			}//if(!is_object(NApp::arequest()))
			if(array_key_exists('params',$params)) {
				$lparams = $params['params'];
			} else {
				$lparams = $params;
			}//if(array_key_exists('params',$params))

			$qsearch_field = strlen($this->qsearch_da_param) ? $this->qsearch_da_param : 'for_text';
			$value_filter = strlen($this->value_da_param) ? $this->value_da_param : 'for_id';
			$ifilters = [];
			$s_params = [];
			$selectedvalue = get_array_value($lparams,'selected_value',NULL,'is_notempty_string');
			$qsearch = get_array_value($lparams,'qsearch','','is_string');
			$selectedtext = get_array_value($lparams,'text','','is_string');
			$dynf = get_array_value($lparams,'dynf',[],'is_array');
			foreach($dynf as $dk=>$dv) {
				if(!is_array($this->ds_params) || !array_key_exists($dk,$this->ds_params)) { continue; }
				$this->ds_params[$dk] = $dv;
			}//END foreach
			if($qsearch && $qsearch!='null' && $qsearch!=$selectedtext) {
				$s_params = array('faction'=>'add','sessact'=>'filters','fop'=>'and','ftype'=>0,'fcond'=>'like','fvalue'=>$qsearch,'fsvalue'=>'','fdvalue'=>$qsearch,'data_type'=>'');
			} else {
				if($selectedvalue && $selectedvalue!='null') { $ifilters[$value_filter] = $selectedvalue; }
			}//if($qsearch && $qsearch!='null' && $qsearch!=$selectedtext)
			$ctrl_params = array(
				'module'=>$this->module,
				'method'=>$this->method,
				'persistent_state'=>get_array_value($this->grid_params,'persistent_state',FALSE,'bool'),
				'exportable'=>get_array_value($this->grid_params,'exportable',FALSE,'bool'),
				'target'=>$this->target,
				'loader'=>"function(s){ GCBOLoader(s,'{$this->tagid}'); }",
				'alternate_row_collor'=>get_array_value($this->grid_params,'alternate_row_collor',TRUE,'bool'),
				'compact_mode'=>get_array_value($this->grid_params,'compact_mode',TRUE,'bool'),
				'scrollable'=>get_array_value($this->grid_params,'scrollable',FALSE,'bool'),
				'with_filter'=>get_array_value($this->grid_params,'with_filter',TRUE,'bool'),
				'with_pagination'=>get_array_value($this->grid_params,'with_pagination',TRUE,'bool'),
				'sortby'=>array('column'=>$this->displayfield,'direction'=>'asc'),
				'initial_filters'=>$ifilters,
				'qsearch'=>$qsearch_field,
				'data_source'=>$this->data_source,
				'ds_method'=>$this->ds_method,
				'ds_params'=>$this->ds_params,
				'ds_extra_params'=>$this->ds_extra_params,
				'auto_load_data'=>get_array_value($this->grid_params,'auto_load_data',TRUE,'bool'),
				'columns'=>array(
					'actions'=>array(
						'type'=>'actions',
						'width'=>'18',
						'actions'=>array(
							array(
								'type'=>'CheckBox',
								'params'=>array('container'=>FALSE,'no_label'=>TRUE,'tagid'=>$this->tagid.'-{{'.$this->valfield.'}}','tooltip'=>\Translate::Get('button_select'),'class'=>$this->baseclass.' gcbo-selector','postable'=>FALSE,'onclick'=>"GCBOSetValue('{$this->tagid}','{{{$this->valfield}}}','{{{$this->displayfield}}}',true)",'value'=>array('type'=>'eval','arg'=>"return ({{{$this->valfield}}}=='{$selectedvalue}' ? 1 : 0);")),
							),
						),
					),
				),
			);
			$ctrl_params['columns'] = array_merge($ctrl_params['columns'],$this->columns);
			$datagrid = new TableView($ctrl_params);
			if(get_array_value($lparams,'return',FALSE,'bool')) { return $datagrid->Show($s_params); }
			echo $datagrid->Show($s_params);
			if(!get_array_value($lparams,'open',FALSE,'bool')) { return; }
			NApp::_ExecJs("GCBODDBtnClick('{$this->tagid}',1);");
		}//END public function ShowDropDown
	}//END class GridComboBox extends Control
?>