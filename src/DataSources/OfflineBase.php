<?php
/**
 * Arrays data source file
 * Contains calls for arrays data.
 *
 * @package    NETopes\Core\DataSources
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2018 HTSS
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\DataSources;
use NETopes\Core\Data\DataSource;
use Translate;

/**
 * Arrays data source class
 * Contains calls for arrays data.
 *
 * @package  NETopes\Core\DataSources
 */
class OfflineBase extends DataSource {
    /**
     * description
     *
     * @param array $params Parameters array
     * @param array $extra_params
     * @return array|bool
     */
    public function GetGenericArrays($params=[],$extra_params=[]) {
        $type=get_array_value($params,'type',NULL,'is_notempty_string');
        if(!$type) {
            return FALSE;
        }
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        switch($type) {
            case 'active':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('active',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('inactive',$langcode)],
                ];
                break;
            case 'state':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('active',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('inactive',$langcode)],
                    ['id'=>-1,'name'=>Translate::GetLabel('deleted',$langcode)],
                ];
                break;
            case 'msgstate':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('unread',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('read',$langcode)],
                    ['id'=>-1,'name'=>Translate::GetLabel('deleted',$langcode)],
                ];
                break;
            case 'in_stock':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('in_stock',$langcode),'condition_type'=>'>='],
                    ['id'=>0,'name'=>Translate::GetLabel('not_in_stock',$langcode),'condition_type'=>'='],
                ];
                break;
            case 'valid':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('valid',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('invalid',$langcode)],
                ];
                break;
            case 'select':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('selected',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('not_selected',$langcode)],
                ];
                break;
            case 'block':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('blocked',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('not_blocked',$langcode)],
                ];
                break;
            case 'yes-no':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('yes',$langcode)],
                    ['id'=>0,'name'=>Translate::GetLabel('no',$langcode)],
                ];
                break;
            case 'users_type':
                $result=[
                    ['id'=>1,'name'=>Translate::GetLabel('administrator',$langcode)],
                    ['id'=>2,'name'=>Translate::GetLabel('operator',$langcode)],
                ];
                break;
            case 'texts_type':
                $result=[
                    ['id'=>4,'name'=>Translate::GetLabel('message')],
                    ['id'=>1,'name'=>Translate::GetLabel('banner')],
                    ['id'=>2,'name'=>Translate::GetLabel('email_body')],
                    ['id'=>3,'name'=>Translate::GetLabel('email_subject')],
                ];
                break;
            case 'upload':
                $result=[
                    ['id'=>'files','name'=>'Files'],
                    ['id'=>'images','name'=>'Images'],
                ];
                break;
            default:
                return FALSE;
        }//END switch
        return $result;
    }//END public function GetGenericArrays

    /**
     * description
     *
     * @param array $params Parameters array
     * @return array|bool
     */
    public function GetCboColors($params=[],$extra_params=[]) {
        $result=[
            ['value'=>'black-std','name'=>'Black','css'=>'color: #000000; font-weight: normal;'],
            ['value'=>'black-bold','name'=>'Black Bold','css'=>'color: #000000; font-wight: bold;'],
            ['value'=>'blue-std','name'=>'Blue','css'=>' color: #2E539A; font-weight: normal;'],
            ['value'=>'blue-bold','name'=>'Blue Bold','css'=>' color: #2E539A; font-wight: bold;'],
            ['value'=>'green-std','name'=>'Green','css'=>' color: #368000; font-weight: normal;'],
            ['value'=>'green-bold','name'=>'Green Bold','css'=>' color: #368000; font-wight: bold;'],
            ['value'=>'red-std','name'=>'Red','css'=>' color: #CF0000; font-weight: normal;'],
            ['value'=>'red-bold','name'=>'Red Bold','css'=>' color: #CF0000; font-wight: bold;'],
        ];
        if(get_array_value($params,'with_blank',FALSE,'bool')) {
            $result[]=['value'=>'','name'=>'','css'=>''];
        }
        return $result;
    }//END public function GetCboColors

    /**
     * description
     *
     * @param array $params Parameters array
     * @return array|bool
     */
    public function GetAppThemes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $raw=get_array_value($params,'raw',0,'is_integer');
        if($raw==1) {
            $result=[
                ['value'=>'','name'=>'Default','type'=>''],
                ['value'=>'_default','name'=>'Default','type'=>'bootstrap3'],
            ];
        } else {
            $result=[
                ['value'=>'','name'=>Translate::GetLabel('default',$langcode),'type'=>''],
                ['value'=>'_default','name'=>'Default','type'=>'bootstrap3'],
            ];
        }//if($raw==1)
        return $result;
    }//END public function GetAppThemes

    /**
     * description
     *
     * @param array $params Parameters array
     * @return array|bool
     * @throws \NETopes\Core\AppException
     */
    public function FilterOperators($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['value'=>'and','name'=>Translate::GetLabel('and',$langcode)],
            ['value'=>'or','name'=>Translate::GetLabel('or',$langcode)],
        ];
        return $result;
    }//END public function FilterOperators

    /**
     * description
     *
     * @param array $params Parameters array
     * @param array $extra_params
     * @return array|bool
     * @throws \NETopes\Core\AppException
     */
    public function FilterConditionsTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        switch(get_array_value($params,'type','','is_string')) {
            case 'combobox':
            case 'smartcombobox':
            case 'checkbox':
                $result=[
                    ['value'=>'==','name'=>Translate::GetLabel('equal',$langcode)],
                    ['value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)],
                ];
                break;
            case 'numerictextbox':
            case 'integer':
            case 'numeric':
            case 'datepicker':
            case 'date':
            case 'datetime':
            case 'datetime_obj':
                $result=[
                    ['value'=>'==','name'=>Translate::GetLabel('equal',$langcode)],
                    ['value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)],
                    ['value'=>'><','name'=>Translate::GetLabel('between',$langcode)],
                    ['value'=>'>=','name'=>Translate::GetLabel('greater_or_equal',$langcode)],
                    ['value'=>'<=','name'=>Translate::GetLabel('smaller_or_equal',$langcode)],
                ];
                break;
            case 'all':
                $result=[
                    ['value'=>'like','name'=>Translate::GetLabel('contains',$langcode)],
                    ['value'=>'notlike','name'=>Translate::GetLabel('not_contains',$langcode)],
                    ['value'=>'==','name'=>Translate::GetLabel('equal',$langcode)],
                    ['value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)],
                    ['value'=>'><','name'=>Translate::GetLabel('between',$langcode)],
                    ['value'=>'>=','name'=>Translate::GetLabel('greater_or_equal',$langcode)],
                    ['value'=>'<=','name'=>Translate::GetLabel('smaller_or_equal',$langcode)],
                ];
                break;
            case 'qsearch':
            default:
                $result=[
                    ['value'=>'like','name'=>Translate::GetLabel('contains',$langcode)],
                    ['value'=>'notlike','name'=>Translate::GetLabel('not_contains',$langcode)],
                    ['value'=>'==','name'=>Translate::GetLabel('equal',$langcode)],
                    ['value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)],
                ];
                break;
        }//END switch
        return $result;
    }//END public function FilterConditionsTypes

    /**
     * description
     *
     * @param array $params Parameters array
     * @return array|bool
     */
    public function GetRestrictedAccessTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['id'=>0,'name'=>Translate::GetLabel('unrestricted',$langcode)],
            ['id'=>1,'name'=>Translate::GetLabel('own_items',$langcode)],
            ['id'=>2,'name'=>Translate::GetLabel('group_items',$langcode)],
        ];
        return $result;
    }//END public function GetRestrictedAccessTypes

    /**
     * Gets the translations configs types
     *
     * @return array|bool
     */
    public function GetTranslationsConfigsTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        if(get_array_value($params,'sadmin',0,'is_numeric')==1) {
            $result=[
                ['id'=>1,'name'=>Translate::GetLabel('resources_translations',$langcode)],
                ['id'=>2,'name'=>Translate::GetLabel('records_translations',$langcode)],
            ];
        } else {
            $result=[
                ['id'=>2,'name'=>Translate::GetLabel('records_translations',$langcode)],
            ];
        }//if(get_array_value($params,'sadmin',0,'is_numeric')==1)
        return $result;
    }//END public function GetTranslationsConfigsTypes

    /**
     * Gets Modules Types
     *
     * @param array $params
     * @param array $extra_params
     * @return array
     */
    public function GetModulesATypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['id'=>0,'name'=>Translate::GetLabel('master_only',$langcode)],
            ['id'=>1,'name'=>Translate::GetLabel('sas_only',$langcode)],
        ];
        return $result;
    }//END public function GetModulesATypes

    /**
     * Gets Modules special Types
     *
     * @return array|bool
     */
    public function GetModulesSTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        switch(get_array_value($params,'for_type',-1,'is_numeric')) {
            case 0:
            case 1:
                $result=[
                    ['id'=>0,'name'=>Translate::GetLabel('group',$langcode)],
                    ['id'=>1,'name'=>Translate::GetLabel('item',$langcode)],
                    ['id'=>2,'name'=>Translate::GetLabel('action',$langcode)],
                ];
                break;
            case 2:
                $result=[
                    ['id'=>20,'name'=>Translate::GetLabel('standard_action',$langcode),'dright'=>NULL],
                    ['id'=>202,'name'=>Translate::GetLabel('view_action',$langcode),'dright'=>'view'],
                    ['id'=>203,'name'=>Translate::GetLabel('search_action',$langcode),'dright'=>'search'],
                    ['id'=>204,'name'=>Translate::GetLabel('add_action',$langcode),'dright'=>'add'],
                    ['id'=>205,'name'=>Translate::GetLabel('edit_action',$langcode),'dright'=>'edit'],
                    ['id'=>206,'name'=>Translate::GetLabel('delete_action',$langcode),'dright'=>'delete'],
                    ['id'=>207,'name'=>Translate::GetLabel('print_action',$langcode),'dright'=>'print'],
                    ['id'=>208,'name'=>Translate::GetLabel('import_action',$langcode),'dright'=>'import'],
                    ['id'=>209,'name'=>Translate::GetLabel('export_action',$langcode),'dright'=>'export'],
                    ['id'=>210,'name'=>Translate::GetLabel('validate_action',$langcode),'dright'=>'validate'],
                    ['id'=>21,'name'=>Translate::GetLabel('section_selector',$langcode),'dright'=>NULL],
                    ['id'=>22,'name'=>Translate::GetLabel('zone_selector',$langcode),'dright'=>NULL],
                    ['id'=>25,'name'=>Translate::GetLabel('company_selector',$langcode),'dright'=>NULL],
                    ['id'=>26,'name'=>Translate::GetLabel('location_selector',$langcode),'dright'=>NULL],
                    ['id'=>27,'name'=>Translate::GetLabel('storage_selector',$langcode),'dright'=>NULL],
                ];
                break;
            case -1:
            default:
                $result=[
                    ['id'=>0,'name'=>Translate::GetLabel('group',$langcode)],
                    ['id'=>1,'name'=>Translate::GetLabel('item',$langcode)],
                    ['id'=>2,'name'=>Translate::GetLabel('action',$langcode)],
                    ['id'=>20,'name'=>Translate::GetLabel('standard_action',$langcode)],
                    ['id'=>202,'name'=>Translate::GetLabel('view_action',$langcode)],
                    ['id'=>203,'name'=>Translate::GetLabel('search_action',$langcode)],
                    ['id'=>204,'name'=>Translate::GetLabel('add_action',$langcode)],
                    ['id'=>205,'name'=>Translate::GetLabel('edit_action',$langcode)],
                    ['id'=>206,'name'=>Translate::GetLabel('delete_action',$langcode)],
                    ['id'=>207,'name'=>Translate::GetLabel('print_action',$langcode)],
                    ['id'=>208,'name'=>Translate::GetLabel('import_action',$langcode)],
                    ['id'=>209,'name'=>Translate::GetLabel('export_action',$langcode)],
                    ['id'=>210,'name'=>Translate::GetLabel('validate_action',$langcode)],
                    ['id'=>21,'name'=>Translate::GetLabel('section_selector',$langcode)],
                    ['id'=>22,'name'=>Translate::GetLabel('zone_selector',$langcode)],
                    ['id'=>25,'name'=>Translate::GetLabel('company_selector',$langcode)],
                    ['id'=>26,'name'=>Translate::GetLabel('location_selector',$langcode)],
                    ['id'=>27,'name'=>Translate::GetLabel('storage_selector',$langcode)],
                ];
                break;
        }//END switch
        return $result;
    }//END public function GetModulesSTypes

    /**
     * Gets Modules display Types
     *
     * @param array $params
     * @param array $extra_params
     * @return array
     */
    public function GetModulesDTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['id'=>0,'name'=>Translate::GetLabel('default',$langcode)],
            ['id'=>1,'name'=>Translate::GetLabel('primary',$langcode)],
            ['id'=>2,'name'=>Translate::GetLabel('info',$langcode)],
            ['id'=>3,'name'=>Translate::GetLabel('success',$langcode)],
            ['id'=>4,'name'=>Translate::GetLabel('danger',$langcode)],
        ];
        return $result;
    }//END public function GetModulesDTypes

    /**
     * Gets Cron Jobs Types
     *
     * @return array|bool
     */
    public function GetCronJobsTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['id'=>1,'name'=>Translate::GetLabel('standard_shell',$langcode)],
            ['id'=>2,'name'=>Translate::GetLabel('standard_web_request',$langcode)],
            ['id'=>100,'name'=>Translate::GetLabel('api',$langcode)],
            ['id'=>200,'name'=>Translate::GetLabel('rabbitmq',$langcode)],
        ];
        return $result;
    }//END public function GetCronJobsTypes

    /**
     * Gets Run interval types
     *
     * @return array|bool
     */
    public function GetRunIntervalTypes($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['id'=>1,'name'=>Translate::GetLabel('minutes',$langcode),'minutes'=>1],
            ['id'=>2,'name'=>Translate::GetLabel('hours',$langcode),'minutes'=>60],
            ['id'=>3,'name'=>Translate::GetLabel('days',$langcode),'minutes'=>1440],
            ['id'=>4,'name'=>Translate::GetLabel('weeks',$langcode),'minutes'=>10080],
            ['id'=>5,'name'=>Translate::GetLabel('months',$langcode),'minutes'=>302400],
        ];
        return $result;
    }//END public function GetRunIntervalTypes

    /**
     * Price/Quantity decimals number value list
     *
     * @return array Returns an array with all values for price/quantity decimals no
     */
    public function GetDecimalsNoList($params=[],$extra_params=[]) {
        $langcode=get_array_value($params,'lang_code','','is_notempty_string');
        $result=[
            ['id'=>NULL,'name'=>Translate::GetLabel('default',$langcode),'implicit'=>1],
            ['id'=>0,'name'=>'0 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0],
            ['id'=>1,'name'=>'1 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0],
            ['id'=>2,'name'=>'2 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0],
            ['id'=>3,'name'=>'3 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0],
            ['id'=>4,'name'=>'4 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0],
        ];
        return $result;
    }//END public function GetDecimalsNoList
}//END class OfflineBase extends DataSource