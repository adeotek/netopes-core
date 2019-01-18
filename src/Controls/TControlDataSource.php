<?php
/**
 * Control data source trait file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Controls;
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
     * @return mixed Returns processed tab array
     * @throws \NETopes\Core\AppException
     * @access public
     * @static
     */
	protected function LoadData(array $params) {
		if(!is_array($params) || !count($params)) { return NULL; }
		$ds_name = get_array_value($params,'ds_class','','is_string');
		$ds_method = get_array_value($params,'ds_method','','is_string');
		if(!strlen($ds_name) || !strlen($ds_method)) { return NULL; }
		$ds_params = get_array_value($params,'ds_params',[],'is_array');
		$da_eparams = get_array_value($params,'ds_extra_params',[],'is_array');
        $result = DataProvider::Get($ds_name,$ds_method,$ds_params,$da_eparams);
		return $result;
	}//END protected function LoadData
}//END trait TControlDataSource