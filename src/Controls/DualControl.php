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
 * @property bool|null   auto_height
 * @property string|null secondary_control_class
 * @package  NETopes\Controls
 */
class DualControl extends Control {
    use TControlConditions;
    /**
     * @var    array Main control parameters array
     */
    public $main_control=[];

    /**
     * @var    array Secondary control parameters array
     */
    public $secondary_control=[];

    /**
     * @var    bool Secondary control as first element
     */
    public $secondary_control_first=FALSE;

    /**
     * DualControl constructor.
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
        $mControlType=get_array_value($this->main_control,'control_type',NULL,'?is_string');
        $mControlParams=get_array_value($this->main_control,'control_params',[],'is_array');
        $mControlConditions=get_array_value($mControlParams,'conditions',NULL,'is_notempty_array');
        $mClassName=is_string($mControlType) && strlen($mControlType) ? '\NETopes\Core\Controls\\'.$mControlType : NULL;
        if(!$mClassName || !class_exists($mClassName) || !count($mControlParams) || (is_array($mControlConditions) && !$this->CheckConditions($mControlConditions))) {
            NApp::Elog('Invalid main control ['.$mControlType.']!');
            $content='&nbsp;';
        } else {
            $ctrl=new $mClassName($mControlParams);
            $content=$ctrl->Show();

            $sControlType=get_array_value($this->secondary_control,'control_type',NULL,'?is_string');
            $sControlParams=get_array_value($this->secondary_control,'control_params',[],'is_array');
            $sControlConditions=get_array_value($sControlParams,'conditions',NULL,'is_notempty_array');
            $sClassName=is_string($sControlType) && strlen($sControlType) ? '\NETopes\Core\Controls\\'.$sControlType : NULL;
            if(!$sClassName || !class_exists($sClassName) || !count($sControlParams) || (is_array($sControlConditions) && !$this->CheckConditions($sControlConditions))) {
                NApp::Elog('Invalid secondary control ['.$sControlType.']!');
            } else {
                $ctrl=new $sClassName($sControlParams);
                if($this->secondary_control_first) {
                    $content='<span class="input-group-addon'.(strlen($this->secondary_control_class) ? ' '.$this->secondary_control_class : '').'">'.$ctrl->Show().'</span>'.$content;
                } else {
                    $content.='<span class="input-group-addon'.(strlen($this->secondary_control_class) ? ' '.$this->secondary_control_class : '').'">'.$ctrl->Show().'</span>';
                }
            }//if(!$sClassName || !class_exists($sClassName) || !count($sControlParams) || (is_array($sControlConditions) && !$this->CheckConditions($sControlConditions)))
        }//if(!$mClassName || !class_exists($mClassName) || !count($mControlParams) || (is_array($mControlConditions) && !$this->CheckConditions($mControlConditions)))
        $lClass='input-group'.(!$this->clear_base_class ? ' '.$this->base_class : '');
        if(strlen($this->class)) {
            $lClass.=' '.$this->class;
        }
        if($this->auto_height) {
            $lClass.=' can-grow-v';
        }
        $result="\t\t".'<div'.$this->GetTagId().(strlen($lClass) ? ' class="'.$lClass.'"' : '').$this->GetTagAttributes().$this->GetTagActions().'>'.$content.'</div>'."\n";
        return $result;
    }//END protected function SetControl
}//END class DualControl extends Control