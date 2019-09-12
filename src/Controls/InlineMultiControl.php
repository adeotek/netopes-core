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
        $content='';
        if(is_array($this->items) && count($this->items)) {
            foreach($this->items as $cName=>$cParams) {
                $className=is_string($cName) && strlen($cName) ? '\NETopes\Core\Controls\\'.$cName : NULL;
                if(!$className || !class_exists($className)) {
                    NApp::Elog('Control class ['.$className.'] not found!');
                    continue;
                }//if(!$className || !class_exists($className))
                if(isset($cParams['conditions']) && is_array($cParams['conditions']) && !$this->CheckConditions($cParams['conditions'])) {
                    continue;
                }
                $ctrl=new $className($cParams);
                $content.=$ctrl->Show();
            }//END foreach
        }//if(is_array($this->items) && count($this->items))
        $lClass=(!$this->clear_base_class ? $this->base_class : '');
        if(strlen($this->class)) {
            $lClass.=' '.$this->class;
        }
        if($this->auto_height) {
            $lClass.=' can-grow-v';
        }
        $result="\t\t".'<div'.$this->GetTagId().(strlen($lClass) ? ' class="'.$lClass.'"' : '').$this->GetTagAttributes().$this->GetTagActions().'>'.$content.'</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class InlineMultiControl extends Control