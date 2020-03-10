<?php
/**
 * Control data source trait file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
use NETopes\Core\App\ModulesProvider;
use NETopes\Core\Data\DataProvider;

/**
 * Trait TControlDataSource
 *
 * @package NETopes\Core\Controls
 */
trait TControlDataSource {
    /**
     * Gets the records from the database
     *
     * @param array $params
     * @param bool  $fromModule
     * @param bool  $asArray
     * @return mixed Returns data
     * @throws \NETopes\Core\AppException
     */
    protected function LoadData(array $params,bool $fromModule=FALSE,bool $asArray=FALSE) {
        if(!is_array($params) || !count($params)) {
            return NULL;
        }
        $ds_name=get_array_value($params,'ds_class','','is_string');
        $ds_method=get_array_value($params,'ds_method','','is_string');
        if(!strlen($ds_name) || !strlen($ds_method)) {
            return NULL;
        }
        $ds_params=get_array_value($params,'ds_params',[],'is_array');
        $ds_eparams=get_array_value($params,'ds_extra_params',[],'is_array');
        if($fromModule) {
            $ds_params['extra_params']=$ds_eparams;
            return ModulesProvider::Exec($ds_name,$ds_method,$ds_params);
        }//if($fromModule)
        if($asArray) {
            return DataProvider::GetArray($ds_name,$ds_method,$ds_params,$ds_eparams);
        }
        return DataProvider::Get($ds_name,$ds_method,$ds_params,$ds_eparams);
    }//END protected function LoadData
}//END trait TControlDataSource