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
    namespace NETopes\Core\Controls;
	/**
	 * KVList class
	 *
	 * Control class for key-value list
	 *
	 * @package  Hinter\NETopes\Controls
	 * @access   public
	 */
	class KVList extends Control {
		protected $postable_elements = TRUE;
		public $lang_items = NULL;
		public $lang_ds = NULL;

		public function __construct($params = NULL) {
			parent::__construct($params);
			if(!$this->postable) { $this->postable_elements = FALSE; }
			else { $this->postable = FALSE; }
			if(!strlen($this->tagid)) { $this->tagid = \PAF\AppSession::GetNewUID('KVList'); }
			if(!strlen($this->tagname)) { $this->tagname = strlen($this->tagid) ? $this->tagid : ''; }
		}//END public function __construct

		protected function SetControlInstance($with_translations = FALSE,$values = NULL,$lang = NULL) {
			$this->ProcessActions();
			$lvalues = (is_null($values) ? $this->value : $values);
			if(is_string($lvalues) && strlen($lvalues)) {
				try {
					$lvalues = @json_decode($lvalues,TRUE);
				} catch(Exception $e) {
					NApp::_Elog($e->getMessage());
					$lvalues = [];
				}//END try
			}//if(is_string($lvalues) && strlen($lvalues))
			if($with_translations) {
				if(is_array($lang) && count($lang)) {
					$tsufix = '-'.$lang['code'];
					$pkey = '['.$lang['id'].']';
				} else {
					$tsufix = '-def';
					$pkey = '[0]';
				}//if(is_array($lang) && count($lang))
			} else {
				$tsufix = '';
				$pkey = '';
			}//if($with_translations)
			$result = '<div'.$this->GetTagId(TRUE,$tsufix).$this->GetTagClass('MainKVL').$this->GetTagAttributes().'>'."\n";
			$result .= "\t".'<input type="text" class="KVLNewKey" data-name="'.$this->tagname.$pkey.'" placeholder="[key]" value="">'."\n";
			$result .= "\t".'<button class="KVLAddBtn"><i class="fa fa-plus-circle"></i></button>'."\n";
			$result .= "\t".'<ul class="KVLList">'."\n";
			if(is_array($lvalues) && count($lvalues)) {
				$lpclass = $this->postable_elements ? ' postable' : '';
				foreach($lvalues as $k=>$v) {
					$result .= "\t\t<li><label class=\"KVLILabel\">{$k}</label><input type=\"text\" class=\"KVLIValue{$lpclass}\" name=\"{$this->tagname}{$pkey}[{$k}]\" placeholder=\"[value]\" value=\"{$v}\"><button class=\"KVLIDelBtn\"><i class=\"fa fa-minus-circle\"></i></button></li>\n";
				}//END foreach
			} else {
				$result .= "\t\t<li><span class=\"KVLBlank\">".\Translate::Get('label_empty')."</span></li>\n";
			}//if(is_array($lvalues) && count($lvalues))
			$result .= "\t".'</ul>'."\n";
			$result .= '</div>'."\n";
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControlInstance

		protected function SetControl() {
			$label = (is_string($this->label) && strlen($this->label) ? $this->label : NULL);
			if(is_array($this->lang_items) && count($this->lang_items)) {
				$ds_field = get_array_param($lang,'ds_field','value','is_notempty_string');
				$result = '<div id="'.$this->tagid.'" class="clsAccordion clsControlContainer">'."\n";
				$result .= "\t".'<h3>'.(strlen($label) ? $label.' - ' : '').\Translate::Get('label_general').'</h3>'."\n";
				$result .= "\t".'<div>'."\n";
				$result .= $this->SetControlInstance(TRUE);
				$result .= "\t".'</div>'."\n";
				foreach($this->lang_items as $lang) {
					if(!is_array($lang) || !count(!$lang)) { continue; }
					$value = get_array_param($lang,$ds_field,NULL,'is_string');
					if(is_null($value)) {
						if(!is_array($this->lang_ds) || !count($this->lang_ds)) { continue; }
						$rparams = $this->lang_ds;
						$rparams['record_key'] = $lang['id'];
						$record = Control::GetTranslationData($rparams);
						$value = get_array_param($record,0,NULL,'is_string',$ds_field);
					}//if(is_null($value))
					$result .= "\t".'<h3>'.(strlen($label) ? $label.' - ' : '').$lang['name'].'</h3>'."\n";
					$result .= "\t".'<div>'."\n";
					$result .= $this->SetControlInstance(TRUE,$value,$lang);
					$result .= "\t".'</div>'."\n";
				}//END foreach
				$result .= '</div>'."\n";
				$js_script = "$('#{$this->tagid}').accordion();";
				NApp::_ExecJs($js_script);
			} else {
				$result = $this->SetControlInstance();
			}//if(is_array($this->lang_items) && count($this->lang_items))
			return $result;
		}//END protected function SetControl
	}//END class KVList extends Control
?>