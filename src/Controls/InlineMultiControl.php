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

/**
 * Inline multi-control control
 * Control for an inline multi-control
 *
 * @property bool|null auto_height
 * @package  NETopes\Controls
 */
class InlineMultiControl extends Control {
    use TControlConditions;

    /**
     * @var    array Controls parameters array
     */
    public $items=[];

    /**
     * InlineMultiControl constructor.
     *
     * @param null $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        $this->postable=FALSE;
        $this->label_position='left';
        parent::__construct($params);
    }//END public function __construct

    /**
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function SetControl(): ?string {
        if(!is_array($this->items) || !count($this->items)) {
            return NULL;
        }
        $content='';
        foreach($this->items as $element) {
            $controlType=get_array_value($element,'control_type',NULL,'is_string');
            $className=strlen($controlType) ? '\NETopes\Core\Controls\\'.$controlType : NULL;
            if(!strlen($className) || !class_exists($className)) {
                NApp::Elog('Control class ['.$className.'] not found!');
                continue;
            }
            $controlParams=get_array_value($element,'control_params',[],'is_array');
            if(isset($controlParams['conditions']) && is_array($controlParams['conditions']) && !$this->CheckConditions($controlParams['conditions'])) {
                continue;
            }
            $ctrl=new $className($controlParams);
            $content.=$ctrl->Show();
        }//END foreach
        $result="\t\t".'<div'.$this->GetTagId().$this->GetTagClass($this->auto_height ? 'can-grow-v' : NULL).$this->GetTagAttributes().$this->GetTagActions().'>'.$content.'</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class InlineMultiControl extends Control