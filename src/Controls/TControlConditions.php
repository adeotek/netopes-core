<?php
/**
 * Control conditions trait file
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
use NETopes\Core\AppException;

/**
 * Trait TControlConditions
 *
 * @package NETopes\Core\Controls
 */
trait TControlConditions {
    /**
     * Check control conditions
     *
     * @param array $conditions The conditions array
     * @return bool Returns TRUE when all conditions are verified or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    protected function CheckConditions($conditions) {
        $result=FALSE;
        if(!is_array($conditions) || !count($conditions)) {
            return $result;
        }
        foreach($conditions as $cond) {
            $cond_field=get_array_value($cond,'field',NULL,'is_notempty_string');
            $cond_value=get_array_value($cond,'value',NULL,'isset');
            if(!$cond_field) {
                continue;
            }
            $cond_type=get_array_value($cond,'type','=','is_notempty_string');
            $validation=get_array_value($cond,'validation','','is_string');
            if($validation) {
                $lvalue=validate_param($this->{$cond_field},get_array_value($cond,'default_value',NULL,'isset'),$validation);
            } else {
                $lvalue=$this->{$cond_field};
            }//if($validation)
            try {
                switch($cond_type) {
                    case '<':
                        $result=$lvalue<$cond_value;
                        break;
                    case '>':
                        $result=$lvalue>$cond_value;
                        break;
                    case '<=':
                        $result=$lvalue<=$cond_value;
                        break;
                    case '>=':
                        $result=$lvalue>=$cond_value;
                        break;
                    case '!=':
                        $result=$lvalue!=$cond_value;
                        break;
                    case 'empty':
                        $result=!$lvalue;
                        break;
                    case '!empty':
                        $result=$lvalue;
                        break;
                    case 'in':
                        $result=(is_array($cond_value) && in_array($lvalue,$cond_value));
                        break;
                    case 'notin':
                        $result=!(is_array($cond_value) && in_array($lvalue,$cond_value));
                        break;
                    case '==':
                    default:
                        $result=$lvalue==$cond_value;
                        break;
                }//END switch
            } catch(AppException $ne) {
                if(NApp::$debug) {
                    throw $ne;
                }
                $result=FALSE;
            }//END try
            if(!$result) {
                break;
            }
        }//END forach
        return $result;
    }//END protected function CheckConditions
}//END trait TControlConditions