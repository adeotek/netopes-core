<?php
/**
 * Control fields trait file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NApp;
use NETopes\Core\AppSession;
use NETopes\Core\Data\VirtualEntity;
use Translate;

/**
 * Trait TControlFields
 *
 * @package NETopes\Core\Controls
 */
trait TControlFields {
    /**
     * @param $item
     * @return null|string
     * @throws \NETopes\Core\AppException
     */
    protected function GetDisplayFieldValue($item): ?string {
        if(!is_object($item) && !is_array($item)) {
            return NULL;
        }
        if(!is_object($item)) {
            $item=new VirtualEntity($item);
        }
        $ldisplayvalue='';
        $ldisplayfield=is_string($this->selected_text_field) && strlen($this->selected_text_field) ? $this->selected_text_field : $this->display_field;
        if(is_array($ldisplayfield)) {
            foreach($ldisplayfield as $dk=>$dv) {
                if(is_array($dv)) {
                    $ov_items=get_array_value($dv,'items',[],'is_notempty_array');
                    $ov_value=get_array_value($dv,'value','','is_string');
                    $ov_mask=get_array_value($dv,'mask','','is_string');
                    $ltext=$item->getProperty($dk,'N/A','is_string');
                    $ldisplayvalue.=strlen($ov_mask)>0 ? str_replace('~',get_array_value($ov_items[$ltext],$ov_value,$ltext,'isset'),$ov_mask) : get_array_value($ov_items[$ltext],$ov_value,$ltext,'isset');
                } else {
                    $ltext=$item->getProperty($dk,'N/A','is_string');
                    $ldisplayvalue.=strlen($dv) ? str_replace('~',$ltext,$dv) : $ltext;
                }//if(is_array($dv))
            }//foreach ($this->display_field as $dk=>$dv)
        } else {
            $ltext=$item->getProperty($ldisplayfield,'N/A','is_string');
            $ldisplayvalue=$this->with_translate===TRUE ? Translate::Get($this->translate_prefix.$ltext) : $ltext;
        }//if(is_array($this->display_field))
        return html_entity_decode($ldisplayvalue);
    }//END protected function GetDisplayFieldValue

    /**
     * @param array|null  $params
     * @param object|null $data
     * @param mixed       $controlValue
     * @param bool        $isIterator
     * @param string|null $paramsPrefix
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    protected function GetControlFieldData(?array $params,$data=NULL,$controlValue=NULL,bool $isIterator=FALSE,?string $paramsPrefix=NULL): ?string {
        $controlType=get_array_value($params,$paramsPrefix.'control_type',NULL,'is_notempty_string');
        $controlClass='\NETopes\Core\Controls\\'.$controlType;
        if(!$controlType || !class_exists($controlClass)) {
            NApp::Elog('Control class ['.$controlClass.'] not found!');
            return NULL;
        }//if(!$c_type_s || !class_exists($c_type))
        $controlParams=get_array_value($params,$paramsPrefix.'control_params',[],'is_array');
        if(is_object($data)) {
            $controlParams=ControlsHelpers::ReplaceDynamicParams($controlParams,$data);
        }
        if($isIterator) {
            $controlParams['value']=$data->getProperty($controlParams['value'],get_array_value($params,'default_value','','is_string'),'is_notempty_string');
        } elseif($controlClass!='ConditionalControl' && $controlClass!='InlineMultiControl' && !isset($controlParams['value']) && isset($params['db_field'])) {
            $controlParams['value']=isset($controlValue) && $controlValue!=='' ? $controlValue : get_array_value($params,'default_value','','is_string');
        }//if($isIterator)
        if(isset($controlParams['tag_id'])) {
            $keyValue=$data->getProperty(get_array_value($params,'db_key','id','is_notempty_string'),NULL,'isset');
            $keyValue=strlen($keyValue) ? $keyValue : AppSession::GetNewUID();
            $controlParams['tag_id'].='_'.$keyValue;
        }//if(isset($controlParams['tag_id']))
        $pAjaxCommands=get_array_value($params,$paramsPrefix.'control_ajax_commands',NULL,'?is_array');
        if($pAjaxCommands) {
            if(is_array($pAjaxCommands) && count($pAjaxCommands)) {
                foreach($pAjaxCommands as $pr=>$prTargetId) {
                    if(!isset($controlParams[$pr]) || !is_string($controlParams[$pr]) || !strlen($controlParams[$pr])) {
                        continue;
                    }
                    $controlParams[$pr]=NApp::Ajax()->Prepare($controlParams[$pr],$prTargetId,NULL,$this->loader);
                }//END foreach
            }//if(is_array($pAjaxCommands) && count($pAjaxCommands))
        } else {
            $controlPafReq=get_array_value($params,$paramsPrefix.'control_pafreq',NULL,'?is_array');
            if(is_array($controlPafReq) && count($controlPafReq)) {
                foreach($controlPafReq as $pr) {
                    if(!isset($controlParams[$pr]) || !is_string($controlParams[$pr]) || !strlen($controlParams[$pr])) {
                        continue;
                    }
                    $controlParams[$pr]=NApp::Ajax()->LegacyPrepare($controlParams[$pr],$this->loader);
                }//END foreach
            }//if(is_array($controlPafReq) && count($controlPafReq))
        }//if($pAjaxCommands)
        $actionParams=NULL;
        $passTroughParams=get_array_value($params,$paramsPrefix.'passtrough_params',NULL,'is_array');
        if(is_array($passTroughParams) && count($passTroughParams)) {
            $actionParams=[];
            foreach($passTroughParams as $pk=>$pv) {
                $actionParams[$pk]=$data->getProperty($pv,NULL,'isset');
            }//END foreach
        }//if(is_array($passTroughParams) && count($passTroughParams))
        // Add [action_params] array to each action of the control
        if($actionParams && isset($controlParams['actions']) && is_array($controlParams['actions'])) {
            foreach($controlParams['actions'] as $ak=>$av) {
                $controlParams['actions'][$ak]['action_params']=$actionParams;
            }//END foreach
        }//if($actionParams && isset($controlParams['actions']) && is_array($controlParams['actions']))
        $control=new $controlClass($controlParams);
        if(get_array_value($params,$paramsPrefix.'clear_base_class',FALSE,'bool')) {
            $control->ClearBaseClass();
        }
        return $control->Show();
    }//END protected function GetControlFieldData
}//END trait TControlFields