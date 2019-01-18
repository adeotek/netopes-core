<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\Data\DataSet;
use NApp;
use NETopes\Core\Data\DataSource;
use NETopes\Core\Data\VirtualEntity;
use Translate;
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
     * @var    DataSet Elements list
     * @access public
     */
    public $items = NULL;
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
        if(!is_object($this->items)) { $this->items = DataSource::ConvertArrayToDataSet($this->items,VirtualEntity::class);}
        if(!strlen($this->tag_id)) { $this->tag_id = \NETopes\Core\AppSession::GetNewUID('GroupCheckBox','md5'); }
    }//END public function __construct
    protected function GetItems() {
        if(isset($this->items)) { return; }
        $ds_class = get_array_value($this->data_source,'ds_class','','is_string');
        $ds_method = get_array_value($this->data_source,'ds_method','','is_string');
        $ds_params = get_array_value($this->data_source,'ds_params',[],'is_array');
        $ds_extra_params = get_array_value($this->data_source,'ds_extra_params',[],'is_array');
        $mode = get_array_value($ds_extra_params,'mode',NULL,'is_notempty_string');
        if(!strlen($ds_class) || !strlen($ds_method) || !DataProvider::MethodExists($ds_class,$ds_method,$mode)) { return; }
        $this->items = DataProvider::Get($ds_class,$ds_method,$ds_params,$ds_extra_params);
    }//END protected function GetItems
    protected function SetControl(): ?string {
        $this->GetItems();
        $idfield = is_string($this->id_field) && strlen($this->id_field) ? $this->id_field : 'id';
        $labelfield = is_string($this->label_field) && strlen($this->label_field) ? $this->label_field : 'name';
        $relation = is_string($this->relation) && strlen($this->relation) ? $this->relation : '';
        $statefield = is_string($this->state_field) && strlen($this->state_field) ? $this->state_field : NULL;
        $activestate = is_string($this->active_state) && strlen($this->active_state) ? $this->active_state : '1';
        $this->ProcessActions();
        $result = '<div id="'.$this->tag_id.'-container" class='.$this->GetTagClass('clsGCKBContainer',TRUE).'">'."\n";
        $result .= "\t".'<input type="hidden" '.$this->GetTagId(TRUE).$this->GetTagClass().$this->GetTagActions().' value="'.$this->value.'">'."\n";
        $ul_class = 'clsGCKBList '.(strtolower($this->orientation)==='vertical' ? 'oVertical' : 'oHorizontal');
        $result .= "\t".'<ul class="'.$ul_class.'">'."\n";
        // $items = $this->items['data'] ?? $this->items;
        if(is_object($this->items) && $this->items->count()) {
            foreach($this->items as $k=>$v) {
                $i_value = $v->getProperty($idfield,NULL,'is_string');
                if(strlen($relation)) {
                    $relObj = $v->getProperty($relation,'','is_string');
                    if(is_object($relObj)) {
                        $i_label = $relObj->getProperty($labelfield,NULL,'is_object');
                    } else {
                        $i_label = '';
                    }//if(is_object($relObj))
                } else {
                    $i_label = $v->getProperty($labelfield,'','is_string');
                }//if(strlen($relation))
                if(isset($this->value)) {
                    $i_val = $i_value==$this->value ? 1 : 0;
                } elseif(strlen($this->default_value_field)) {
                    $i_val = $v->getProperty($this->default_value_field,0,'is_numeric')==1 ? 1 : 0;
                } else {
                    $i_val = 0;
                }//if(isset($this->value))
                if($this->disabled || $this->readonly) {
                    $i_active = FALSE;
                } else {
                    $i_active = strlen($statefield) ? $v->getProperty($statefield,NULL,'is_string')==$activestate : TRUE;
                }//if($this->disabled || $this->readonly)
                $result .= "\t\t".'<li><input type="image" class="clsGCKBItem'.($i_active ? ' active' : ' disabled').'" data-id="'.$this->tag_id.'" data-val="'.$i_value.'" src="'.NApp::$appBaseUrl.AppConfig::GetValue('app_js_path').'/controls/images/transparent.gif" value="'.$i_val.'"><label class="clsGCKBLabel">'.$i_label.'</label></li>'."\n";
            }//END foreach
        } else {
            $result .= "\t\t<li><span class=\"clsGCKBBlank\">".Translate::GetLabel('no_elements')."</span></li>\n";
        }//if(is_array($this->items) && count($this->items))
        $result .= "\t".'</ul>'."\n";
        $result .= '</div>'."\n";
        $result .= $this->GetActions();
        return $result;
    }//END protected function SetControl
}//END class GroupCheckBox extends Control
?>
