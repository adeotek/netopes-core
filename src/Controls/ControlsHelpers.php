<?php
/**
 * Control helpers class file
 * Static helpers for controls
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use GibberishAES;
use NETopes\Core\AppException;
use NETopes\Core\Data\DataProvider;
use NETopes\Core\Data\VirtualEntity;
use NETopes\Core\Validators\Validator;
use NApp;

/**
 * Class ControlsHelpers
 *
 * @package NETopes\Core\Controls
 */
class ControlsHelpers {
    /**
     * Regular expression for finding placeholders for dynamic parameters
     */
    const PLACEHOLDERS_REG_EXP='/{\![^}]*\!}/i';
    /**
     * Chars to trim for removing placeholders for dynamic parameters
     */
    const PLACEHOLDERS_TRIM_CHARS='{!}';

    /**
     * Generate parameters URL hash
     *
     * @param array  $params         An array of parameters
     * @param bool   $encrypt        Encrypt or not the parameters
     * @param string $hash_separator Separator for the hash parameters
     * @param string $epass
     * @return string Returns the computed hash
     */
    public static function GetUrlHash($params=[],$encrypt=TRUE,$hash_separator='|',$epass='eUrlHash') {
        if(!is_array($params) || !count($params)) {
            return NULL;
        }
        $result='';
        foreach($params as $v) {
            $result.=(strlen($result) ? $hash_separator : '').$v;
        }
        if(strlen($result) && $encrypt!==FALSE) {
            $result=GibberishAES::enc($result,$epass);
        }
        return rawurlencode($result);
    }//END public static function GetUrlHash

    /**
     * @param string      $command
     * @param string|null $targetId
     * @param array|null  $actionParams
     * @param null        $triggerOnInitEvent
     * @return string|null
     */
    public static function GetAjaxActionString(string $command,?string $targetId=NULL,?array $actionParams=NULL,$triggerOnInitEvent=NULL): ?string {
        if(strpos($command,'#action_params#')!==FALSE) {
            $actParams='';
            if(is_array($actionParams) && count($actionParams)) {
                foreach($actionParams as $pk=>$pv) {
                    $actParams.=($actParams ? ', ' : '')."'{$pk}': '{$pv}'";
                }//END foreach
            }//if(is_array($actionParams) && count($actionParams))
            $command=str_replace('#action_params#',$actParams,$command);
        }//if(strpos($command,'#action_params#')!==FALSE)
        return NApp::Ajax()->Prepare($command,$targetId,NULL,TRUE,NULL,TRUE,NULL,NULL,(isset($triggerOnInitEvent) ? (bool)$triggerOnInitEvent : TRUE));
    }//END public static function GetAjaxActionString

    /**
     * @param string     $command
     * @param array|null $actionParams
     * @param null       $triggerOnInitEvent
     * @return string|null
     */
    public static function LegacyGetAjaxActionString(string $command,?array $actionParams=NULL,$triggerOnInitEvent=NULL): ?string {
        if(strpos($command,'#action_params#')!==FALSE) {
            $actParams='';
            if(is_array($actionParams) && count($actionParams)) {
                foreach($actionParams as $pk=>$pv) {
                    $actParams.=($actParams ? '~' : '')."'{$pk}'|'{$pv}'";
                }//END foreach
            }//if(is_array($actionParams) && count($actionParams))
            $command=str_replace('#action_params#',$actParams,$command);
        }//if(strpos($command,'#action_params#')!==FALSE)
        return NApp::Ajax()->LegacyPrepare($command,1,NULL,NULL,1,(isset($triggerOnInitEvent) ? (bool)$triggerOnInitEvent : TRUE));
    }//END public static function LegacyGetAjaxActionString

    /**
     * Replace dynamic parameters
     *
     * @param array|string $params    The parameters array to be parsed
     * @param object|array $row       Data row object to be used for replacements
     * @param bool         $recursive Flag indicating if the array should be parsed recursively
     * @param string|null  $paramsPrefix
     * @param string|null  $validation
     * @return array|string Return processed parameters array
     * @throws \NETopes\Core\AppException
     */
    public static function ReplaceDynamicParams($params,$row,$recursive=TRUE,$paramsPrefix=NULL,?string $validation=NULL) {
        $lRow=is_object($row) ? $row : new VirtualEntity(is_array($row) ? $row : []);
        if(is_string($params)) {
            if(!strlen($params)) {
                return $params;
            }
            if(is_string($paramsPrefix) && strlen($paramsPrefix)) {
                $result=str_replace('{'.$paramsPrefix.'!','{!',$params);
            } else {
                $result=$params;
            }//if(is_string($params_prefix) && strlen($params_prefix))
            $dynParams=[];
            preg_match_all(self::PLACEHOLDERS_REG_EXP,$result,$dynParams);
            if(is_array($dynParams[0])) {
                foreach($dynParams[0] as $pfr) {
                    if(strpos($result,$pfr)===FALSE) {
                        continue;
                    }
                    $pfrValue=$lRow->getProperty(trim($pfr,self::PLACEHOLDERS_TRIM_CHARS),NULL,$validation ?? 'isset');
                    $result=is_object($pfrValue) ? $pfrValue : str_replace($pfr,addslashes($pfrValue),$result);
                }//END foreach
            }//if(is_array($dynParams[0]))
            return $result;
        }//if(is_string($params))
        if(!is_array($params) || !count($params)) {
            return $params;
        }
        $result=[];
        foreach(array_keys($params) as $pk) {
            if(is_string($params[$pk]) || (is_array($params[$pk]) && ($recursive===TRUE || $recursive===1 || $recursive==='1'))) {
                $result[$pk]=self::ReplaceDynamicParams($params[$pk],$lRow,TRUE,$paramsPrefix);
            } else {
                $result[$pk]=$params[$pk];
            }//if(is_string($params[$pk]) || (is_array($params[$pk]) && ($recursive===TRUE || $recursive===1 || $recursive==='1')))
        }//END foreach
        return $result;
    }//END public static function ReplaceDynamicParams

    /**
     * Check row conditions
     *
     * @param object $row        Data row object
     * @param array  $conditions The conditions array
     * @return bool Returns TRUE when all conditions are verified or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function CheckRowConditions(&$row,$conditions) {
        $result=FALSE;
        if(!is_array($conditions) || !count($conditions) || !is_object($row)) {
            return $result;
        }
        foreach($conditions as $cond) {
            $cond_field=get_array_value($cond,'field',NULL,'is_notempty_string');
            $cond_value=get_array_value($cond,'value',NULL,'isset');
            $cond_type=get_array_value($cond,'type','=','is_notempty_string');
            try {
                switch($cond_type) {
                    case '<':
                        $result=$row->getProperty($cond_field)<$cond_value;
                        break;
                    case '>':
                        $result=$row->getProperty($cond_field)>$cond_value;
                        break;
                    case '<=':
                        $result=$row->getProperty($cond_field)<=$cond_value;
                        break;
                    case '>=':
                        $result=$row->getProperty($cond_field)>=$cond_value;
                        break;
                    case '!=':
                        $result=$row->getProperty($cond_field)!=$cond_value;
                        break;
                    case 'empty':
                        $result=!$row->getProperty($cond_field);
                        break;
                    case '!empty':
                        $result=$row->getProperty($cond_field);
                        break;
                    case 'in':
                        $result=(is_array($cond_value) && in_array($row->getProperty($cond_field),$cond_value));
                        break;
                    case 'notin':
                        $result=!(is_array($cond_value) && in_array($row->getProperty($cond_field),$cond_value));
                        break;
                    case 'fileexists':
                        $result=is_file($cond_value);
                        break;
                    case '==':
                    default:
                        $result=$row->getProperty($cond_field)==$cond_value;
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
    }//END public static function CheckRowConditions

    /**
     * Gets the record from the database and sets the values in the tab array
     *
     * @param array $params Parameters array
     * @return array Returns processed tab array
     * @throws \NETopes\Core\AppException
     */
    public static function GetTranslationData($params=[]) {
        if(!is_array($params) || !count($params)) {
            return NULL;
        }
        $ds_name=get_array_value($params,'ds_class','','is_string');
        $ds_method=get_array_value($params,'ds_method','','is_string');
        if(!strlen($ds_name) || !strlen($ds_method) || !DataProvider::MethodExists($ds_name,$ds_method)) {
            return NULL;
        }
        $record_key=get_array_value($params,'record_key',0,'is_integer');
        $record_key_field=get_array_value($params,'record_key_field','language_id','is_notempty_string');
        $ds_params=get_array_value($params,'ds_params',[],'is_array');
        $ds_params[$record_key_field]=$record_key;
        $ds_key=get_array_value($params,'ds_key','','is_string');
        if(strlen($ds_key)) {
            $result=DataProvider::GetKeyValueArray($ds_name,$ds_method,$ds_params,['keyfield'=>$ds_key]);
        } else {
            $result=DataProvider::GetArray($ds_name,$ds_method,$ds_params);
        }//if(strlen($ds_key))
        return $result;
    }//END public static function GetTranslationData

    /**
     * description
     *
     * @param null $firstRow
     * @param null $lastRow
     * @param null $currentPage
     * @param null $rpp
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public static function GetPaginationParams(&$firstRow=NULL,&$lastRow=NULL,$currentPage=NULL,$rpp=NULL) {
        $cpage=is_numeric($currentPage) ? $currentPage : 1;
        if($cpage==-1) {
            $firstRow=-1;
            $lastRow=-1;
            return ['first_row'=>$firstRow,'last_row'=>$lastRow];
        }//if($cpage==-1)
        if(is_numeric($rpp) && $rpp>0) {
            $lrpp=$rpp;
        } else {
            $lrpp=Validator::ValidateValue(NApp::GetParam('rows_per_page'),20,'is_not0_integer');
        }//if(is_numeric($rpp) && $rpp>0)
        if(Validator::IsValidValue($firstRow,'is_not0_integer')) {
            $lastRow=$firstRow + $lrpp - 1;
        } else {
            $firstRow=($cpage - 1) * $lrpp + 1;
            $lastRow=$firstRow + $lrpp - 1;
        }//if(Validator::IsValidValue($firstrow,NULL,'is_not0_numeric'))
        return ['first_row'=>$firstRow,'last_row'=>$lastRow];
    }//END public static function GetPaginationParams
}//END class ControlsHelpers