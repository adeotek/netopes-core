<?php
/**
 * ComboBox control class file
 *
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
    namespace NETopes\Core\Controls;
	/**
	 * ComboBox control
	 *
	 * Standard ComboBox control
	 *
	 * @package  NETopes\Controls
	 * @access   public
	 */
	class ComboBox extends Control {
		protected function SetControl() {
			$this->ProcessActions();
			$tmpresult = '';
			$ph_class = '';
			$t_required = '';
			if(strlen($this->pleaseselecttext)) {
				$tmpresult .= "\t\t\t".'<option class="option_std" value="'.$this->pleaseselectvalue.'">'.html_entity_decode($this->pleaseselecttext).'</option>'."\n";
			} elseif($this->pleaseselectvalue=='_blank') {
				$tmpresult .= "\t\t\t".'<option></option>'."\n";
			} elseif(strlen($this->placeholder)) {
				$ph_class = 'clsPlaceholder';
				$t_required = ' required="required"';
				$tmpresult .= "\t\t\t".'<option value="" disabled="disabled" selected="selected" hidden="hidden">'.$this->placeholder.'</option>'."\n";
			}//if(strlen($this->pleaseselectvalue))
			$lselclass = $this->GetTagClass($ph_class);
			if(is_array($this->value)) {
				$implicit = FALSE;
				foreach($this->value as $v) {
					$lcolorfield = strlen($this->colorfield) ? $this->colorfield : 'color';
					$loptionclass = ((array_key_exists($lcolorfield,$v) && strlen($v[$lcolorfield])>0) ? ' '.$v[$lcolorfield] : '');
					$lcolorcodefield = strlen($this->colorcodefield) ? ' color: '.$this->colorcodefield.';' : '';
					if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && array_key_exists($this->option_conditional_class['field'],$v)) {
						if($v[$this->option_conditional_class['field']]===$this->option_conditional_class['condition']) {
							$loptionclass = $this->option_conditional_class['class'];
						}//if($this->option_conditional_class['field']==$this->option_conditional_class['condition'])
					}//if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && array_key_exists($this->option_conditional_class['field'],$v))
					$lselected = '';
                    if($v[$this->valfield]==$this->selectedvalue && !(($this->selectedvalue==='null' && $v[$this->valfield]!=='null') || ($this->selectedvalue!=='null' && $v[$this->valfield]==='null'))) {
						$lselected = ' selected="selected"';
						$lselclass .= ((array_key_exists($lcolorfield,$v) && strlen($v[$lcolorfield])>0) ? ' '.$v[$lcolorfield] : '');
					}//if($v[$this->valfield]==$this->selectedvalue)
					if(!$implicit && !strlen($lselected) && strlen($this->default_value_field) && get_array_param($v,$this->default_value_field,0,'is_numeric')==1) {
						$implicit = TRUE;
						$lselected = ' selected="selected"';
						$lselclass .= ((array_key_exists($lcolorfield,$v) && strlen($v[$lcolorfield])>0) ? ' '.$v[$lcolorfield] : '');
					}//if(!$implicit && !strlen($lselected) && strlen($this->default_value_field) && get_array_param($v,$this->default_value_field,0,'is_numeric')==1)
					if(is_array($this->displayfield)) {
						$ldisplayfield = '';
						foreach($this->displayfield as $dk=>$dv) {
							if(is_array($dv)) {
								$ov_items = get_array_param($dv,'items',array(),'is_notempty_array');
								$ov_value = get_array_param($dv,'value','','is_string');
								$ov_mask = get_array_param($dv,'mask','','is_string');
								$ldisplayfield .= strlen($ov_mask)>0 ? str_replace('~',get_array_param($ov_items[$v[$dk]],$ov_value,$v[$dk],'isset'),$ov_mask) : get_array_param($ov_items[$v[$dk]],$ov_value,$v[$dk],'isset');
							} else {
								$ldisplayfield .= strlen($dv)>0 ? str_replace('~',$v[$dk],$dv) : $v[$dk];
							}//if(is_array($dv))
						}//foreach ($this->displayfield as $dk=>$dv)
					}else{
						$ldisplayfield = $this->withtranslate===TRUE ? \Translate::Get($this->translate_prefix.$v[$this->displayfield]) : $v[$this->displayfield];
					}//if(is_array($v[$this->displayfield]))
					$o_data = '';
					if(is_array($this->option_data)) {
						foreach($this->option_data as $ok=>$ov) {
							$o_data .= ' data-'.$ok.'="'.get_array_param($v,$ov,'null','is_string').'"';
						}//END foreach
					} elseif(is_string($this->option_data) && count($this->option_data)) {
						$o_data = ' data="'.get_array_param($v,$this->option_data,'null','is_string').'"';
					}//if(is_array($this->option_data))
					$tmpresult .= "\t\t\t".'<option value="'.$v[$this->valfield].'"'.$lselected.(strlen($loptionclass) ? ' class="'.$loptionclass.'"' : '').$o_data.(strlen($lcolorcodefield) ? ' style="'.$lcolorcodefield.'"' : '').'>'.html_entity_decode($ldisplayfield).'</option>'."\n";
				}//foreach
			}//if(is_array($this->value))
			$result = "\t\t".'<select'.$t_required.$this->GetTagId(TRUE).$lselclass.$this->GetTagAttributes().$this->GetTagActions().'>'."\n";
			$result .= $tmpresult;
			$result .= "\t\t".'</select>'."\n";
			$result .= $this->GetActions();
			return $result;
		}//END protected function SetControl
	}//END class ComboBox extends Control
?>