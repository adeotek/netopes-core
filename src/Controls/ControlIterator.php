<?php
/**
 * Basic controls classes file
 *
 * File containing basic controls classes
 *
 * @package    NETopes\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\ModulesProvider;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\Data\DataSet;
use NETopes\Core\Data\DataSource;
use NETopes\Core\Data\VirtualEntity;

/**
 * Control iterator control
 *
 * Control for iterating a specific simple control
 *
 * @package  NETopes\Controls
 * @access   public
 */
class ControlIterator extends Control {
    /**
     * @var    string Iterator type (array/DataSource/Module)
     * @access public
     */
    public $iterator_type = 'list';
    /**
     * @var    string Iterator class name (DataSource/Module name)
     * @access public
     */
    public $iterator_name = NULL;
    /**
     * @var    string Iterator method (DataSource/Module method)
     * @access public
     */
    public $iterator_method = NULL;
    /**
     * @var    array Iterator parameters array
     * @access public
     */
    public $iterator_params = NULL;
    /**
     * @var    array Iterator extra parameters array
     * @access public
     */
    public $iterator_extra_params = [];
    /**
     * @var    array Iterator items array
     * @access public
     */
    public $items = [];
    /**
     * @var    string Dynamic control parameters prefix
     * @access public
     */
    public $params_prefix = '';
    /**
     * @var    string Control class name
     * @access public
     */
    public $control = NULL;
    /**
     * @var    array Control parameters array
     * @access public
     */
    public $params = [];
    /**
     * @var    array Iterator conditions (if TRUE control is shown, else not)
     * @access public
     */
    public $conditions = [];
    /**
     * ControlIterator constructor.
     *
     * @param null $params
     */
    public function __construct($params = NULL) {
        $this->postable = FALSE;
        parent::__construct($params);
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $this->container = FALSE;
                $this->no_label = TRUE;
                break;
            default:
                break;
        }//END switch
    }//END public function __construct
    /**
     * @return array|mixed|\NETopes\Core\Data\DataSet|null
     * @throws \PAF\AppException
     */
    protected function GetItems() {
        $items = NULL;
        switch(strtolower($this->iterator_type)) {
            case 'module':
                if(is_string($this->iterator_name) && strlen($this->iterator_name) && is_string($this->iterator_method) && strlen($this->iterator_method) && ModulesProvider::ModuleMethodExists($this->iterator_name,$this->iterator_method)) {
                    $iparams = is_array($this->iterator_params) ? $this->iterator_params : [];
                    $items = ModulesProvider::Exec($this->iterator_name,$this->iterator_method,$iparams);
                }//if(...
                break;
            case 'datasource':
                $iparams = is_array($this->iterator_params) ? $this->iterator_params : [];
                $ieparams = is_array($this->iterator_extra_params) ? $this->iterator_extra_params : [];
                if(is_string($this->iterator_name) && strlen($this->iterator_name) && is_string($this->iterator_method) && strlen($this->iterator_method)) {
                    if(DataProvider::MethodExists($this->iterator_name,$this->iterator_method,get_array_value($ieparams,'mode',NULL,'is_notempty_string'))) {
                        $items = DataProvider::Get($this->iterator_name,$this->iterator_method,$iparams,$ieparams);
                    }//if(...
                }//if(...
                break;
            case 'list':
            default:
                $items = $this->items;
                break;
        }//END switch
        if(is_object($this->items)) { return $this->items; }
        return DataSource::ConvertArrayToDataSet($items,VirtualEntity::class);
    }//END protected function GetItems
    /**
     * @return string|null
     * @throws \PAF\AppException
     */
    protected function SetControl(): ?string {
        $this->items = $this->GetItems();
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $lcontent = $this->container ? "\n" : '';
                $lprefix = '';
                $lsufix = '';
                break;
            default:
                if($this->container) {
                    $lcontent = '';
                    $lprefix = '';
                    $lsufix = '';
                } else {
                    $lcontent = "\n";
                    $lprefix = '<div class="'.$this->base_class.' clsRow">'."\n";
                    $lsufix = '</div>'."\n";
                }//if($this->container)
                break;
        }//END switch
        if(strlen($this->control) && is_object($this->items) && $this->items->count()) {
            $controlClass = 'NETopes\Core\Controls\\'.$this->control;
            if(class_exists($controlClass)) {
            foreach($this->items as $k=>$v) {
                if(is_array($this->conditions) && count($this->conditions)) {
                    $iconditions = self::ReplaceDynamicParams($this->conditions,$v,TRUE,$this->params_prefix);
                    if(!self::CheckRowConditions($v->toArray(),$iconditions)) { continue; }
                }//if(is_array($this->conditions) && count($this->conditions))
                if(is_array($this->params)) {
                    $lparams = self::ReplaceDynamicParams($this->params,$v,TRUE,$this->params_prefix);
                } else {
                    $lparams = [];
                }//if(is_array($this->params))
                    $ctrl = new $controlClass($lparams);
                $lcontent .= $lprefix.$ctrl->Show()."\n".$lsufix;
                unset($ctrl);
                unset($lparams);
            }//END foreach
            }//if(class_exists($controlClass))
        }//if(strlen($this->control) && class_exists($this->control) && is_object($this->items) && $this->items->count())
        switch($this->theme_type) {
            case 'bootstrap2':
            case 'bootstrap3':
            case 'bootstrap4':
                $result = $lcontent;
                break;
            default:
                if($this->container) {
                    $result = $lcontent;
                } else {
                    $result = '<div'.$this->GetTagId().$this->GetTagClass().$this->GetTagAttributes().'>'.$lcontent.'</div>'."\n";
                }//if($this->container)
                break;
        }//END switch
        return $result;
    }//END protected function SetControl
}//END class ControlIterator extends Control
?>