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
 * @package  NETopes\Core\DataSources
 */
class OfflineBase extends DataSource {
	/**
	 * description
	 * @param array $params Parameters array
	 * @param array $extra_params
	 * @return array|bool
	 */
	public function GetGenericArrays($params = [],$extra_params = []) {
		$type = get_array_value($params,'type',NULL,'is_notempty_string');
		if(!$type) { return FALSE; }
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		switch($type) {
			case 'active':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('active',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('inactive',$langcode)),
				);
				break;
			case 'state':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('active',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('inactive',$langcode)),
				array('id'=>-1,'name'=>Translate::GetLabel('deleted',$langcode)),
				);
				break;
			case 'msgstate':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('unread',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('read',$langcode)),
				array('id'=>-1,'name'=>Translate::GetLabel('deleted',$langcode)),
				);
				break;
			case 'in_stock':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('in_stock',$langcode),'condition_type'=>'>='),
				array('id'=>0,'name'=>Translate::GetLabel('not_in_stock',$langcode),'condition_type'=>'='),
				);
				break;
		    case 'valid':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('valid',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('invalid',$langcode)),
				);
				break;
			case 'select':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('selected',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('not_selected',$langcode)),
				);
				break;
			case 'block':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('blocked',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('not_blocked',$langcode)),
				);
				break;
			case 'yes-no':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('yes',$langcode)),
				array('id'=>0,'name'=>Translate::GetLabel('no',$langcode)),
				);
				break;
			case 'users_type':
				$result = array(
				array('id'=>1,'name'=>Translate::GetLabel('administrator',$langcode)),
				array('id'=>2,'name'=>Translate::GetLabel('operator',$langcode)),
				);
				break;
			case 'texts_type':
				$result = array(
				array('id'=>4,'name'=>Translate::GetLabel('message')),
				array('id'=>1,'name'=>Translate::GetLabel('banner')),
				array('id'=>2,'name'=>Translate::GetLabel('email_body')),
				array('id'=>3,'name'=>Translate::GetLabel('email_subject')),
				);
				break;
			case 'upload':
				$result = array(
					array('id'=>'files','name'=>'Files'),
					array('id'=>'images','name'=>'Images')
				);
				break;
		    default:
				return FALSE;
		}//END switch
		return $result;
	}//END public function GetGenericArrays
	/**
	 * description
	 * @param array $params Parameters array
	 * @return array|bool
	 */
	public function GetCboColors($params = [],$extra_params = []) {
		$result = array(
			array('value'=>'black-std','name'=>'Black','css'=>'color: #000000; font-weight: normal;'),
			array('value'=>'black-bold','name'=>'Black Bold','css'=>'color: #000000; font-wight: bold;'),
			array('value'=>'blue-std','name'=>'Blue','css'=>' color: #2E539A; font-weight: normal;'),
			array('value'=>'blue-bold','name'=>'Blue Bold','css'=>' color: #2E539A; font-wight: bold;'),
			array('value'=>'green-std','name'=>'Green','css'=>' color: #368000; font-weight: normal;'),
			array('value'=>'green-bold','name'=>'Green Bold','css'=>' color: #368000; font-wight: bold;'),
			array('value'=>'red-std','name'=>'Red','css'=>' color: #CF0000; font-weight: normal;'),
			array('value'=>'red-bold','name'=>'Red Bold','css'=>' color: #CF0000; font-wight: bold;'),
		);
		if(get_array_value($params,'with_blank',FALSE,'bool')) { $result[] = array('value'=>'','name'=>'','css'=>''); }
		return $result;
	}//END public function GetCboColors
	/**
	 * description
	 * @param array $params Parameters array
	 * @return array|bool
	 */
	public function GetAppThemes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$raw = get_array_value($params,'raw',0,'is_integer');
		if($raw==1) {
			$result = array(
				array('value'=>'','name'=>'Default','type'=>''),
				array('value'=>'_default','name'=>'Default','type'=>'bootstrap3'),
			);
		} else {
		$result = array(
			array('value'=>'','name'=>Translate::GetLabel('default',$langcode),'type'=>''),
				array('value'=>'_default','name'=>'Default','type'=>'bootstrap3'),
			);
		}//if($raw==1)
		return $result;
	}//END public function GetAppThemes
	/**
	 * description
	 * @param array $params Parameters array
	 * @return array|bool
     */
	public function FilterOperators($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('value'=>'and','name'=>Translate::GetLabel('and',$langcode)),
		array('value'=>'or','name'=>Translate::GetLabel('or',$langcode)),
		);
		return $result;
	}//END public function FilterOperators
	/**
	 * description
	 * @param array $params Parameters array
	 * @return array|bool
	 */
	public function FilterConditionsTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		switch(get_array_value($params,'type','','is_string')) {
			case 'combobox':
			case 'checkbox':
				$result = array(
				array('value'=>'==','name'=>Translate::GetLabel('equal',$langcode)),
				array('value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)),
				);
				break;
			case 'numerictextbox':
			case 'integer':
			case 'numeric':
			case 'datepicker':
			case 'date':
			case 'datetime':
			case 'datetime_obj':
				$result = array(
				array('value'=>'==','name'=>Translate::GetLabel('equal',$langcode)),
				array('value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)),
				array('value'=>'><','name'=>Translate::GetLabel('between',$langcode)),
				array('value'=>'>=','name'=>Translate::GetLabel('greater_or_equal',$langcode)),
				array('value'=>'<=','name'=>Translate::GetLabel('smaller_or_equal',$langcode)),
				);
				break;
			case 'all':
				$result = array(
				array('value'=>'like','name'=>Translate::GetLabel('contains',$langcode)),
				array('value'=>'notlike','name'=>Translate::GetLabel('not_contains',$langcode)),
				array('value'=>'==','name'=>Translate::GetLabel('equal',$langcode)),
				array('value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)),
				array('value'=>'><','name'=>Translate::GetLabel('between',$langcode)),
				array('value'=>'>=','name'=>Translate::GetLabel('greater_or_equal',$langcode)),
				array('value'=>'<=','name'=>Translate::GetLabel('smaller_or_equal',$langcode)),
				);
				break;
			case 'qsearch':
			default:
				$result = array(
				array('value'=>'like','name'=>Translate::GetLabel('contains',$langcode)),
				array('value'=>'notlike','name'=>Translate::GetLabel('not_contains',$langcode)),
				array('value'=>'==','name'=>Translate::GetLabel('equal',$langcode)),
				array('value'=>'<>','name'=>Translate::GetLabel('not_equal',$langcode)),
				);
				break;
		}//END switch
		return $result;
	}//END public function FilterConditionsTypes
	/**
	 * description
	 * @param array $params Parameters array
	 * @return array|bool
	 */
	public function GetRestrictedAccessTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('id'=>0,'name'=>Translate::GetLabel('unrestricted',$langcode)),
		array('id'=>1,'name'=>Translate::GetLabel('own_items',$langcode)),
		array('id'=>2,'name'=>Translate::GetLabel('group_items',$langcode)),
		);
		return $result;
	}//END public function GetRestrictedAccessTypes
	/**
	 * Gets the translations configs types
	 * @return array|bool
	 */
	public function GetTranslationsConfigsTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		if(get_array_value($params,'sadmin',0,'is_numeric')==1) {
			$result = array(
			array('id'=>1,'name'=>Translate::GetLabel('resources_translations',$langcode)),
			array('id'=>2,'name'=>Translate::GetLabel('records_translations',$langcode)),
			);
		} else {
			$result = array(
			array('id'=>2,'name'=>Translate::GetLabel('records_translations',$langcode)),
			);
		}//if(get_array_value($params,'sadmin',0,'is_numeric')==1)
		return $result;
	}//END public function GetTranslationsConfigsTypes
	/**
	 * Gets Modules Types
	 * @param array $params
	 * @param array $extra_params
	 * @return array
	 */
	public function GetModulesATypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('id'=>0,'name'=>Translate::GetLabel('master_only',$langcode)),
		array('id'=>1,'name'=>Translate::GetLabel('sas_only',$langcode)),
		);
		return $result;
	}//END public function GetModulesATypes
	/**
	 * Gets Modules special Types
	 * @return array|bool
	 */
	public function GetModulesSTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		switch(get_array_value($params,'for_type',-1,'is_numeric')) {
			case 0:
			case 1:
				$result = array(
				array('id'=>0,'name'=>Translate::GetLabel('group',$langcode)),
				array('id'=>1,'name'=>Translate::GetLabel('item',$langcode)),
				array('id'=>2,'name'=>Translate::GetLabel('action',$langcode)),
				);
				break;
			case 2:
				$result = array(
				array('id'=>20,'name'=>Translate::GetLabel('standard_action',$langcode),'dright'=>NULL),
				array('id'=>202,'name'=>Translate::GetLabel('view_action',$langcode),'dright'=>'view'),
				array('id'=>203,'name'=>Translate::GetLabel('search_action',$langcode),'dright'=>'search'),
				array('id'=>204,'name'=>Translate::GetLabel('add_action',$langcode),'dright'=>'add'),
				array('id'=>205,'name'=>Translate::GetLabel('edit_action',$langcode),'dright'=>'edit'),
				array('id'=>206,'name'=>Translate::GetLabel('delete_action',$langcode),'dright'=>'delete'),
				array('id'=>207,'name'=>Translate::GetLabel('print_action',$langcode),'dright'=>'print'),
				array('id'=>208,'name'=>Translate::GetLabel('import_action',$langcode),'dright'=>'import'),
				array('id'=>209,'name'=>Translate::GetLabel('export_action',$langcode),'dright'=>'export'),
				array('id'=>210,'name'=>Translate::GetLabel('validate_action',$langcode),'dright'=>'validate'),
				array('id'=>21,'name'=>Translate::GetLabel('section_selector',$langcode),'dright'=>NULL),
				array('id'=>22,'name'=>Translate::GetLabel('zone_selector',$langcode),'dright'=>NULL),
				array('id'=>25,'name'=>Translate::GetLabel('company_selector',$langcode),'dright'=>NULL),
				array('id'=>26,'name'=>Translate::GetLabel('location_selector',$langcode),'dright'=>NULL),
				array('id'=>27,'name'=>Translate::GetLabel('storage_selector',$langcode),'dright'=>NULL),
				);
				break;
			case -1:
			default:
				$result = array(
				array('id'=>0,'name'=>Translate::GetLabel('group',$langcode)),
				array('id'=>1,'name'=>Translate::GetLabel('item',$langcode)),
				array('id'=>2,'name'=>Translate::GetLabel('action',$langcode)),
				array('id'=>20,'name'=>Translate::GetLabel('standard_action',$langcode)),
				array('id'=>202,'name'=>Translate::GetLabel('view_action',$langcode)),
				array('id'=>203,'name'=>Translate::GetLabel('search_action',$langcode)),
				array('id'=>204,'name'=>Translate::GetLabel('add_action',$langcode)),
				array('id'=>205,'name'=>Translate::GetLabel('edit_action',$langcode)),
				array('id'=>206,'name'=>Translate::GetLabel('delete_action',$langcode)),
				array('id'=>207,'name'=>Translate::GetLabel('print_action',$langcode)),
				array('id'=>208,'name'=>Translate::GetLabel('import_action',$langcode)),
				array('id'=>209,'name'=>Translate::GetLabel('export_action',$langcode)),
				array('id'=>210,'name'=>Translate::GetLabel('validate_action',$langcode)),
				array('id'=>21,'name'=>Translate::GetLabel('section_selector',$langcode)),
				array('id'=>22,'name'=>Translate::GetLabel('zone_selector',$langcode)),
				array('id'=>25,'name'=>Translate::GetLabel('company_selector',$langcode)),
				array('id'=>26,'name'=>Translate::GetLabel('location_selector',$langcode)),
				array('id'=>27,'name'=>Translate::GetLabel('storage_selector',$langcode)),
				);
				break;
		}//END switch
		return $result;
	}//END public function GetModulesSTypes
	/**
	 * Gets Modules display Types
	 * @param array $params
	 * @param array $extra_params
	 * @return array
	 */
	public function GetModulesDTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('id'=>0,'name'=>Translate::GetLabel('default',$langcode)),
		array('id'=>1,'name'=>Translate::GetLabel('primary',$langcode)),
		array('id'=>2,'name'=>Translate::GetLabel('info',$langcode)),
		array('id'=>3,'name'=>Translate::GetLabel('success',$langcode)),
		array('id'=>4,'name'=>Translate::GetLabel('danger',$langcode)),
		);
		return $result;
	}//END public function GetModulesDTypes
	/**
	 * Gets Cron Jobs Types
	 * @return array|bool
	 */
	public function GetCronJobsTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('id'=>1,'name'=>Translate::GetLabel('standard_shell',$langcode)),
		array('id'=>2,'name'=>Translate::GetLabel('standard_web_request',$langcode)),
		array('id'=>100,'name'=>Translate::GetLabel('api',$langcode)),
		array('id'=>200,'name'=>Translate::GetLabel('rabbitmq',$langcode)),
		);
		return $result;
	}//END public function GetCronJobsTypes
	/**
	 * Gets Run interval types
	 * @return array|bool
	 */
	public function GetRunIntervalTypes($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('id'=>1,'name'=>Translate::GetLabel('minutes',$langcode),'minutes'=>1),
		array('id'=>2,'name'=>Translate::GetLabel('hours',$langcode),'minutes'=>60),
		array('id'=>3,'name'=>Translate::GetLabel('days',$langcode),'minutes'=>1440),
		array('id'=>4,'name'=>Translate::GetLabel('weeks',$langcode),'minutes'=>10080),
		array('id'=>5,'name'=>Translate::GetLabel('months',$langcode),'minutes'=>302400),
		);
		return $result;
	}//END public function GetRunIntervalTypes
	/**
	 * Price/Quantity decimals number value list
	 * @return array Returns an array with all values for price/quantity decimals no
	 */
	public function GetDecimalsNoList($params = [],$extra_params = []) {
		$langcode = get_array_value($params,'lang_code','','is_notempty_string');
		$result = array(
		array('id'=>NULL,'name'=>Translate::GetLabel('default',$langcode),'implicit'=>1),
		array('id'=>0,'name'=>'0 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0),
		array('id'=>1,'name'=>'1 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0),
		array('id'=>2,'name'=>'2 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0),
		array('id'=>3,'name'=>'3 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0),
		array('id'=>4,'name'=>'4 '.Translate::GetLabel('decimals',$langcode),'implicit'=>0),
		);
		return $result;
	}//END public function GetDecimalsNoList
}//END class OfflineBase extends DataSource