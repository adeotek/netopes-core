<?php
/**
 * ComboBox control class file
 *
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\Data\DataSource;
use NETopes\Core\Data\VirtualEntity;

/**
 * ComboBox control
 *
 * Standard ComboBox control
 *
 * @package  NETopes\Controls
 * @access   public
 */
class ComboBox extends Control {
    /**
	 * SmartComboBox constructor.
	 *
	 * @param null $params
	 */
	public function __construct($params = NULL) {
		parent::__construct($params);
		if(is_array($this->value)) {
            $this->value = DataSource::ConvertArrayToDataSet($this->value,'\NETopes\Core\Data\VirtualEntity');
		} elseif(!is_object($this->value)) {
            $this->value = DataSource::ConvertArrayToDataSet([],'\NETopes\Core\Data\VirtualEntity');
		}//if(is_array($this->value))
	}//END public function __construct
    /**
	 * @return string|void
	 */
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
        if(is_object($this->selectedvalue)) {
            $selectedValue = $this->selectedvalue->getProperty($this->valfield,NULL,'isset');
        } elseif(is_array($this->selectedvalue)) {
            $selectedValue = get_array_value($this->selectedvalue,$this->valfield,NULL,'isset');
        } else {
            $selectedValue = $this->selectedvalue;
        }//if(is_object($this->selectedvalue))
        $lselclass = $this->GetTagClass($ph_class);
        if(is_object($this->value) && $this->value->count()) {
            $implicit = FALSE;
            foreach($this->value as $item) {
                $lcolorfield = strlen($this->colorfield) ? $this->colorfield : 'color';
                $loptionclass = strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
                $lcolorcodefield = strlen($this->colorcodefield) ? ' color: '.$this->colorcodefield.';' : '';
                if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && $item->hasProperty($this->option_conditional_class['field'])) {
                    if($item->getProperty($this->option_conditional_class['field'],'','is_string')===$this->option_conditional_class['condition']) {
                        $loptionclass = $this->option_conditional_class['class'];
                    }//if($item->getProperty($this->option_conditional_class['field'],'','is_string')===$this->option_conditional_class['condition'])
                }//if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && $item->hasProperty($this->option_conditional_class['field']))
                $lselected = '';
                $cValue = $item->getProperty($this->valfield,NULL,'isset');
                if($cValue==$selectedValue && !(($selectedValue===NULL && $cValue!==NULL) || ($selectedValue!==NULL && $cValue===NULL))) {
                    $lselected = ' selected="selected"';
                    $lselclass .= strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
                }//if($cValue==$selectedValue && !(($selectedValue===NULL && $cValue!==NULL) || ($selectedValue!==NULL && $cValue===NULL)))
                if(!$implicit && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1) {
                    $implicit = TRUE;
                    $lselected = ' selected="selected"';
                    $lselclass .= strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
                }//if(!$implicit && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1)
                if(is_array($this->displayfield)) {
                    $ldisplayfield = '';
                    foreach($this->displayfield as $dk=>$dv) {
                        if(is_array($dv)) {
                            $ov_items = get_array_value($dv,'items',[],'is_notempty_array');
                            $ov_value = get_array_value($dv,'value','','is_string');
                            $ov_mask = get_array_value($dv,'mask','','is_string');
                            $ldisplayfield .= strlen($ov_mask)>0 ? str_replace('~',get_array_value($ov_items[$item->getProperty($dk,NULL,'isset')],$ov_value,$item->getProperty($dk,NULL,'isset'),'isset'),$ov_mask) : get_array_value($ov_items[$item->getProperty($dk,NULL,'isset')],$ov_value,$item->getProperty($dk,NULL,'isset'),'isset');
                        } else {
                            $ldisplayfield .= strlen($dv)>0 ? str_replace('~',$item->getProperty($dk,NULL,'isset'),$dv) : $item->getProperty($dk,NULL,'isset');
                        }//if(is_array($dv))
                    }//foreach ($this->displayfield as $dk=>$dv)
                } else {
                    $ldisplayfield = $this->withtranslate===TRUE ? \Translate::Get($this->translate_prefix.$item->getProperty($this->displayfield,NULL,'isset')) : $item->getProperty($this->displayfield,NULL,'isset');
                }//if(is_array($this->displayfield))
                $o_data = '';
                if(is_array($this->option_data)) {
                    foreach($this->option_data as $ok=>$ov) {
                        $o_data .= ' data-'.$ok.'="'.$item->getProperty($ov,'','is_string').'"';
                    }//END foreach
                } elseif(is_string($this->option_data) && strlen($this->option_data)) {
                    $o_data = ' data-'.$this->option_data.'="'.$item->getProperty($this->option_data,'','is_string').'"';
                }//if(is_array($this->option_data))
                $tmpresult .= "\t\t\t".'<option value="'.$cValue.'"'.$lselected.(strlen($loptionclass) ? ' class="'.$loptionclass.'"' : '').$o_data.(strlen($lcolorcodefield) ? ' style="'.$lcolorcodefield.'"' : '').'>'.html_entity_decode($ldisplayfield).'</option>'."\n";
            }//foreach
        }//if(is_object($this->value) && $this->value->count())
        $result = "\t\t".'<select'.$t_required.$this->GetTagId(TRUE).$lselclass.$this->GetTagAttributes().$this->GetTagActions().'>'."\n";
        $result .= $tmpresult;
        $result .= "\t\t".'</select>'."\n";
        $result .= $this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class ComboBox extends Control
?>