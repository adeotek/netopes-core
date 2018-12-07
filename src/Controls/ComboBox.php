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
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSource;
use NETopes\Core\Data\VirtualEntity;
use PAF\AppException;
use NApp;

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
		$this->onenter = NULL;
		$this->onenterbutton = NULL;
		if(!strlen($this->tagid)) { $this->tagid = $this->uid; }
		if(!is_string($this->valfield) || !strlen($this->valfield)) { $this->valfield = 'id'; }
		if(is_null($this->displayfield) || $this->displayfield=='') { $this->displayfield = 'name'; }
		// one of the values: value/database/ajax
		if(!strlen($this->load_type)) { $this->load_type = 'value'; }
		if(!is_array($this->option_data)) {
		    if(is_string($this->option_data) && strlen($this->option_data)) {
		        $this->option_data = [$this->option_data];
		    } else {
		        $this->option_data = [];
		    }//if(is_string($this->option_data) && strlen($this->option_data))
		}//if(!is_array($this->option_data))
	}//END public function __construct
	/**
	 * @return string|null
	 * @throws \PAF\AppException
	 */
	protected function SetControl(): ?string {
        $this->ProcessActions();
        $litems = DataSource::ConvertArrayToDataSet(is_array($this->extra_items) ? $this->extra_items : [],VirtualEntity::class);
        $ph_class = '';
        $t_required = '';
        if(strlen($this->pleaseselecttext)) {
            $litems->add(new VirtualEntity([$this->valfield=>$this->pleaseselectvalue,$this->displayfield=>html_entity_decode($this->pleaseselecttext)]));
        } elseif($this->pleaseselectvalue=='_blank') {
            $litems->add(new VirtualEntity([]));
        } elseif(strlen($this->placeholder)) {
            $ph_class = 'clsPlaceholder';
            $t_required = ' required="required"';
            $litems->add(new VirtualEntity([$this->valfield=>'__is_placeholder__',$this->displayfield=>html_entity_decode($this->placeholder)]));
        }//if(strlen($this->pleaseselectvalue))
        if(is_object($this->selectedvalue)) {
            $selectedValue = $this->selectedvalue->getProperty($this->valfield,NULL,'isset');
        } elseif(is_array($this->selectedvalue)) {
            $selectedValue = get_array_value($this->selectedvalue,$this->valfield,NULL,'isset');
        } else {
            $selectedValue = $this->selectedvalue;
        }//if(is_object($this->selectedvalue))
        $lselclass = $this->GetTagClass($ph_class);

		switch($this->load_type) {
			case 'database':
				$data = $this->LoadData($this->data_source);
				if(is_object($data) && $data->count()) { $litems->merge($data->toArray()); }
				break;
			case 'value':
			    if(is_array($this->value)) {
                    $this->value = DataSource::ConvertArrayToDataSet($this->value,VirtualEntity::class);
                } elseif(!is_object($this->value)) {
                    $this->value = DataSource::ConvertArrayToDataSet([],VirtualEntity::class);
                }//if(is_array($this->value))
				if(is_object($this->value) && $this->value->count()) {  $litems->merge($this->value->toArray()); }
				break;
			default:
				throw new AppException('Invalid ComboBox load type!');
		}//END switch
		// NApp::_Dlog($this->tagid,'$this->tagid');
		// NApp::_Dlog($litems,'$litems');
		$rOptions = [''=>[]];
		$def_record = FALSE;
		foreach($litems as $item) {
		    if(!is_object($item) || !$item->hasProperty($this->valfield)) {
				$rOptions[''][] = "\t\t\t<option></option>\n";
				continue;
			}//if(!is_object($item) || !$item->hasProperty($this->valfield))
			if($item->getProperty($this->valfield)==='__is_placeholder__') {
			    array_unshift($rOptions[''],"\t\t\t<option value=\"\" disabled=\"disabled\" selected=\"selected\" hidden=\"hidden\">".$item->getProperty($this->displayfield)."</option>\n");
			    continue;
			}//if($item->getProperty($this->valfield)=='__is_placeholder__')

			$lcolorfield = strlen($this->colorfield) ? $this->colorfield : 'color';
            $loptionclass = strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
            $lcolorcodefield = strlen($this->colorcodefield) ? ' color: '.$this->colorcodefield.';' : '';
            if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && $item->hasProperty($this->option_conditional_class['field'])) {
                if($item->getProperty($this->option_conditional_class['field'],'','is_string')===$this->option_conditional_class['condition']) {
                    $loptionclass = $this->option_conditional_class['class'];
                }//if($item->getProperty($this->option_conditional_class['field'],'','is_string')===$this->option_conditional_class['condition'])
            }//if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && $item->hasProperty($this->option_conditional_class['field']))

            $lval = $item->getProperty($this->valfield,NULL,'isset');
			$ltext = $this->GetDisplayFieldValue($item);
            $lselected = '';
            if($lval==$selectedValue && !(($selectedValue===NULL && $lval!==NULL) || ($selectedValue!==NULL && $lval===NULL))) {
                $lselected = ' selected="selected"';
                $lselclass .= strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
            }//if($lval==$selectedValue && !(($selectedValue===NULL && $lval!==NULL) || ($selectedValue!==NULL && $lval===NULL)))
            if(!$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1) {
                $def_record = TRUE;
                $lselected = ' selected="selected"';
                $lselclass .= strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
            }//if(!$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1)
            $o_data = (is_string($this->state_field) && strlen($this->state_field) && $item->getProperty($this->state_field,1,'is_numeric')<=0) ? ' disabled="disabled"' : '';
            foreach($this->option_data as $od) {
                $o_data .= ' data-'.$od.'="'.$item->getProperty($od,'','is_string').'"';
            }//END foreach

			if(is_string($this->group_field) && strlen($this->group_field)) {
                $groupName = $item->getProperty($this->group_field,'','is_string');
                if(!array_key_exists($groupName,$rOptions)) { $rOptions[$groupName] = []; }
                $rOptions[$groupName][] = "\t\t\t".'<option value="'.$lval.'"'.$lselected.(strlen($loptionclass) ? ' class="'.$loptionclass.'"' : '').$o_data.(strlen($lcolorcodefield) ? ' style="'.$lcolorcodefield.'"' : '').'>'.html_entity_decode($ltext).'</option>'."\n";
            } else {
                $rOptions[''][] = "\t\t\t".'<option value="'.$lval.'"'.$lselected.(strlen($loptionclass) ? ' class="'.$loptionclass.'"' : '').$o_data.(strlen($lcolorcodefield) ? ' style="'.$lcolorcodefield.'"' : '').'>'.html_entity_decode($ltext).'</option>'."\n";
            }//if(is_string($this->group_field) && strlen($this->group_field))
		}//END foreach
		// NApp::_Dlog($rOptions,'$rOptions');
		$rOptionsStr = '';
		foreach(array_keys($rOptions) as $group) {
		    if(strlen($group)) { $rOptionsStr .= "\t\t\t<optgroup label=\"{$group}\">\n"; }
            $rOptionsStr .= implode('',$rOptions[$group]);
            if(strlen($group)) { $rOptionsStr .= "\t\t\t</optgroup>\n"; }
		}//END foreach
		// NApp::_Dlog($rOptionsStr,'$rOptionsStr');
		// final result processing
        //$this->GetTagClass('SmartCBO')
		$result = "\t\t".'<select'.$t_required.$this->GetTagId(TRUE).$lselclass.$this->GetTagAttributes().$this->GetTagActions().'>'."\n";
        $result .= $rOptionsStr;
        $result .= "\t\t".'</select>'."\n";
        $result .= $this->GetActions();
        return $result;
	}//END protected function SetControl
}//END class ComboBox extends Control