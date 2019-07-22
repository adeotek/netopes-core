<?php
/**
 * ComboBox control class file
 * Standard ComboBox control
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppException;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;

/**
 * ComboBox control
 * Standard ComboBox control
 *
 * @package  NETopes\Controls
 */
class ComboBox extends Control {
    use TControlDataSource;
    use TControlFields;

    /**
     * SmartComboBox constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
        $this->onenter=NULL;
        $this->onenter_button=NULL;
        if(!strlen($this->tag_id)) {
            $this->tag_id=$this->uid;
        }
        if(!is_string($this->value_field) || !strlen($this->value_field)) {
            $this->value_field='id';
        }
        if(is_null($this->display_field) || $this->display_field=='') {
            $this->display_field='name';
        }
        // one of the values: value/database/ajax
        if(!strlen($this->load_type)) {
            $this->load_type='value';
        }
        if(!is_array($this->option_data)) {
            if(is_string($this->option_data) && strlen($this->option_data)) {
                $this->option_data=[$this->option_data];
            } else {
                $this->option_data=[];
            }//if(is_string($this->option_data) && strlen($this->option_data))
        }//if(!is_array($this->option_data))
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        $this->ProcessActions();
        $litems=DataSourceHelpers::ConvertArrayToDataSet(is_array($this->extra_items) ? $this->extra_items : [],VirtualEntity::class);
        $placeholderFieldName=(is_string($this->display_field) ? $this->display_field : '__text__');
        $ph_class='';
        $t_required='';
        if(strlen($this->please_select_text)) {
            $litems->add(new VirtualEntity([
                '__special_type__'=>'default_value',
                $this->value_field=>$this->please_select_value,
                $placeholderFieldName=>html_entity_decode($this->please_select_text),
            ]));
        } elseif($this->please_select_value=='_blank') {
            $litems->add(new VirtualEntity([]));
        } elseif(strlen($this->placeholder)) {
            $ph_class='clsPlaceholder';
            $t_required=' required="required"';
            $litems->add(new VirtualEntity([
                '__special_type__'=>'placeholder',
                $placeholderFieldName=>html_entity_decode($this->placeholder),
            ]));
        }//if(strlen($this->please_select_value))
        if(is_object($this->selected_value)) {
            $selectedValue=$this->selected_value->getProperty($this->value_field,NULL,'isset');
        } elseif(is_array($this->selected_value)) {
            $selectedValue=get_array_value($this->selected_value,$this->value_field,NULL,'isset');
        } else {
            $selectedValue=$this->selected_value;
        }//if(is_object($this->selected_value))
        $lselclass=$this->GetTagClass($ph_class);
        switch($this->load_type) {
            case 'database':
                $data=$this->LoadData($this->data_source);
                if(is_object($data) && $data->count()) {
                    $litems->merge($data->toArray());
                }
                break;
            case 'value':
                if(is_array($this->value)) {
                    $this->value=DataSourceHelpers::ConvertArrayToDataSet($this->value,VirtualEntity::class);
                } elseif(!is_object($this->value)) {
                    $this->value=DataSourceHelpers::ConvertArrayToDataSet([],VirtualEntity::class);
                }//if(is_array($this->value))
                if(is_object($this->value) && $this->value->count()) {
                    $litems->merge($this->value->toArray());
                }
                break;
            default:
                throw new AppException('Invalid ComboBox load type!');
        }//END switch
        // NApp::Dlog($this->tag_id,'$this->tag_id');
        // NApp::Dlog($litems,'$litems');
        $rOptions=[''=>[]];
        $def_record=FALSE;
        foreach($litems as $item) {
            if(!is_object($item) || !$item->hasProperty($this->value_field)) {
                $rOptions[''][]="\t\t\t<option></option>\n";
                continue;
            }//if(!is_object($item) || !$item->hasProperty($this->value_field))
            if($item->getProperty('__special_type__')==='placeholder') {
                array_unshift($rOptions[''],"\t\t\t<option value=\"\" disabled=\"disabled\" selected=\"selected\" hidden=\"hidden\">".$item->getProperty($placeholderFieldName)."</option>\n");
                continue;
            }//if($item->getProperty('__special_type__')==='placeholder')
            $lcolorfield=strlen($this->colorfield) ? $this->colorfield : 'color';
            $loptionclass=strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
            $lcolorcodefield=strlen($this->colorcodefield) ? ' color: '.$this->colorcodefield.';' : '';
            if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && $item->hasProperty($this->option_conditional_class['field'])) {
                if($item->getProperty($this->option_conditional_class['field'],'','is_string')===$this->option_conditional_class['condition']) {
                    $loptionclass=$this->option_conditional_class['class'];
                }//if($item->getProperty($this->option_conditional_class['field'],'','is_string')===$this->option_conditional_class['condition'])
            }//if(!strlen($loptionclass) && is_array($this->option_conditional_class) && count($this->option_conditional_class) && array_key_exists('field',$this->option_conditional_class) && array_key_exists('condition',$this->option_conditional_class) && array_key_exists('class',$this->option_conditional_class) && $item->hasProperty($this->option_conditional_class['field']))
            $lval=$item->getProperty($this->value_field,NULL,'isset');
            if($item->getProperty('__special_type__')==='default_value') {
                $ltext=$item->getProperty($placeholderFieldName);
            } else {
                $ltext=$this->GetDisplayFieldValue($item);
            }//if($item->getProperty('__special_type__')==='default_value')
            $lselected='';
            if($lval==$selectedValue && !(($selectedValue===NULL && $lval!==NULL) || ($selectedValue!==NULL && $lval===NULL))) {
                $lselected=' selected="selected"';
                $lselclass.=strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
            }//if($lval==$selectedValue && !(($selectedValue===NULL && $lval!==NULL) || ($selectedValue!==NULL && $lval===NULL)))
            if(!$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1) {
                $def_record=TRUE;
                $lselected=' selected="selected"';
                $lselclass.=strlen($item->getProperty($lcolorfield,'','is_string')) ? ' '.$item->getProperty($lcolorfield,'','is_string') : '';
            }//if(!$def_record && !strlen($lselected) && strlen($this->default_value_field) && $item->getProperty($this->default_value_field,0,'is_numeric')==1)
            $o_data=(is_string($this->state_field) && strlen($this->state_field) && $item->getProperty($this->state_field,1,'is_numeric')<=0) ? ' disabled="disabled"' : '';
            foreach($this->option_data as $od) {
                $o_data.=' data-'.$od.'="'.$item->getProperty($od,'','is_string').'"';
            }//END foreach
            if(is_string($this->group_field) && strlen($this->group_field)) {
                $groupName=$item->getProperty($this->group_field,'','is_string');
                if(!array_key_exists($groupName,$rOptions)) {
                    $rOptions[$groupName]=[];
                }
                $rOptions[$groupName][]="\t\t\t".'<option value="'.$lval.'"'.$lselected.(strlen($loptionclass) ? ' class="'.$loptionclass.'"' : '').$o_data.(strlen($lcolorcodefield) ? ' style="'.$lcolorcodefield.'"' : '').'>'.html_entity_decode($ltext).'</option>'."\n";
            } else {
                $rOptions[''][]="\t\t\t".'<option value="'.$lval.'"'.$lselected.(strlen($loptionclass) ? ' class="'.$loptionclass.'"' : '').$o_data.(strlen($lcolorcodefield) ? ' style="'.$lcolorcodefield.'"' : '').'>'.html_entity_decode($ltext).'</option>'."\n";
            }//if(is_string($this->group_field) && strlen($this->group_field))
        }//END foreach
        // NApp::Dlog($rOptions,'$rOptions');
        $rOptionsStr='';
        foreach(array_keys($rOptions) as $group) {
            if(strlen($group)) {
                $rOptionsStr.="\t\t\t<optgroup label=\"{$group}\">\n";
            }
            $rOptionsStr.=implode('',$rOptions[$group]);
            if(strlen($group)) {
                $rOptionsStr.="\t\t\t</optgroup>\n";
            }
        }//END foreach
        // NApp::Dlog($rOptionsStr,'$rOptionsStr');
        // final result processing
        //$this->GetTagClass('SmartCBO')
        $result="\t\t".'<select'.$t_required.$this->GetTagId(TRUE).$lselclass.$this->GetTagAttributes().$this->GetTagActions().'>'."\n";
        $result.=$rOptionsStr;
        $result.="\t\t".'</select>'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class ComboBox extends Control