<?php
/**
 * Basic controls classes file
 * File containing basic controls classes
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
use NETopes\Core\AppConfig;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSourceHelpers;
use NETopes\Core\Data\VirtualEntity;
use Translate;

/**
 * GroupCheckBox class
 * Control class for group checkbox (radio button alternative)
 *
 * @property string|null active_state
 * @property string|null state_field
 * @property string|null relation
 * @property string|null label_field
 * @property string|null id_field
 * @package  Hinter\NETopes\Controls
 */
class GroupCheckBox extends Control {
    use TControlDataSource;

    const MULTI_VALUE_SEPARATOR='|';
    /**
     * @var    array Elements data source
     */
    public $data_source=[];
    /**
     * @var    DataSet Elements list
     */
    public $items=NULL;
    /**
     * @var    string Elements list orientation
     */
    public $orientation='horizontal';
    /**
     * @var    string Elements default value field name
     */
    public $default_value_field=NULL;
    /**
     * @var    mixed Initial value
     */
    public $value=NULL;
    /**
     * @var    bool Initial value
     */
    public $multiple=FALSE;
    /**
     * @var    bool Initial value
     */
    public $multiResult=TRUE;

    /**
     * Control class constructor
     *
     * @param array $params An array of params
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        parent::__construct($params);
        if(isset($this->items) && !is_object($this->items)) {
            $this->items=DataSourceHelpers::ConvertArrayToDataSet($this->items,VirtualEntity::class);
        }
        if(!strlen($this->tag_id)) {
            $this->tag_id=AppSession::GetNewUID('GroupCheckBox','md5');
        }
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        if(is_null($this->items)) {
            $this->items=$this->LoadData($this->data_source);
        }
        $idField=is_string($this->id_field) && strlen($this->id_field) ? $this->id_field : 'id';
        $labelField=is_string($this->label_field) && strlen($this->label_field) ? $this->label_field : 'name';
        $relation=is_string($this->relation) && strlen($this->relation) ? $this->relation : '';
        $stateField=is_string($this->state_field) && strlen($this->state_field) ? $this->state_field : NULL;
        $activeState=is_string($this->active_state) && strlen($this->active_state) ? $this->active_state : '1';
        $this->ProcessActions();
        $result='<div id="'.$this->tag_id.'-container" class="'.$this->GetTagClass('clsGCKBContainer',TRUE).'">'."\n";
        if($this->multiple && $this->multiResult) {
            $hiddenTagSufix='_value';
            $iHiddenClass=$this->GetTagClass(NULL,TRUE);
            $result.="\t".'<input type="hidden" '.$this->GetTagId(FALSE,$hiddenTagSufix).(strlen($iHiddenClass) ? ' class="'.$iHiddenClass.'"' : '').$this->GetTagActions().' value="'.$this->GetValue().'" />'."\n";
        } else {
            $hiddenTagSufix='';
            $result.="\t".'<input type="hidden" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagActions().' value="'.$this->GetValue().'" />'."\n";
        }//if($this->multiple && $this->multiResult)
        $ul_class='clsGCKBList '.(strtolower($this->orientation)==='vertical' ? 'oVertical' : 'oHorizontal');
        $result.="\t".'<ul class="'.$ul_class.'">'."\n";
        if(is_object($this->items) && $this->items->count()) {
            foreach($this->items as $k=>$v) {
                $iValue=$v->getProperty($idField,NULL,'is_string');
                if(strlen($relation)) {
                    $relObj=$v->getProperty($relation,'','is_string');
                    if(is_object($relObj)) {
                        /** @var VirtualEntity $relObj */
                        $iLabel=$relObj->getProperty($labelField,NULL,'is_object');
                    } else {
                        $iLabel='';
                    }//if(is_object($relObj))
                } else {
                    $iLabel=$v->getProperty($labelField,'','is_string');
                }//if(strlen($relation))
                if(isset($this->value)) {
                    if($this->multiple) {
                        $iVal=in_array($iValue,$this->GetMultiSelectedValue()) ? 1 : 0;
                    } else {
                        $iVal=$iValue==$this->value ? 1 : 0;
                    }//if($this->multiple)
                } elseif(strlen($this->default_value_field)) {
                    $iVal=$v->getProperty($this->default_value_field,0,'is_numeric')==1 ? 1 : 0;
                } else {
                    $iVal=0;
                }//if(isset($this->value))
                if($this->disabled || $this->readonly) {
                    $iActive=FALSE;
                } else {
                    $iActive=strlen($stateField) ? $v->getProperty($stateField,NULL,'is_string')==$activeState : TRUE;
                }//if($this->disabled || $this->readonly)
                if($this->multiple && $this->multiResult) {
                    $itemTagId=' id="'.$this->tag_id.'_'.$iValue.'"';
                    $itemTagName=' name="'.(strlen($this->tag_name) ? $this->tag_name : $this->tag_id).'['.$iValue.']"';
                } else {
                    $itemTagId=$itemTagName='';
                }
                $result.="\t\t".'<li><input type="image"'.$itemTagId.$itemTagName.' class="clsGCKBItem'.($this->multiple && $this->multiResult ? ' postable' : '').($iActive ? ' active' : ' disabled').'" data-id="'.$this->tag_id.$hiddenTagSufix.'" data-val="'.$iValue.'" data-multiple="'.((int)$this->multiple).'" src="'.NApp::$appBaseUrl.AppConfig::GetValue('app_js_path').'/controls/images/transparent.gif" value="'.$iVal.'"><label class="clsGCKBLabel">'.$iLabel.'</label></li>'."\n";
            }//END foreach
        } else {
            $result.="\t\t<li><span class=\"clsGCKBBlank\">".Translate::GetLabel('no_elements')."</span></li>\n";
        }//if(is_array($this->items) && count($this->items))
        $result.="\t".'</ul>'."\n";
        $result.='</div>'."\n";
        $result.=$this->GetActions();
        return $result;
    }//END protected function SetControl

    /**
     * Determines what kind of value to return.
     *
     * @return string|null
     */
    protected function GetValue(): ?string {
        if($this->multiple) {
            if(is_iterable($this->value)) {
                $value=implode('|',$this->value);
            } elseif(is_scalar($this->value)) {
                $value=$this->value;
            } else {
                $value=NULL;
            }
        } else {
            $value=$this->value;
        }
        return $value;
    }//END protected function GetValue

    protected function GetMultiSelectedValue() {
        if(is_string($this->value)) {
            $value=strlen($this->value) ? explode(self::MULTI_VALUE_SEPARATOR,$this->value) : [];
        } elseif(is_array($this->value)) {
            $value=$this->value;
        } else {
            $value=[];
        }
        return $value;
    }//END protected function GetSelectedValue
}//END class GroupCheckBox extends Control