<?php
/**
 * Data provider file
 *
 * All data request are made using the DataProvider class static methods.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.5.7
 * @filesource
 */
namespace NETopes\Core\Data;
use PAF\AppConfig;
use PAF\AppException;
use NApp;
/**
  * DataProvider prepares and makes the data requests
  *
  * All data request are made using the DataProvider class static methods.
  *
  * @package  NETopes\Database
  * @access   public
  */
class DataProvider {
	/**
	 * @var    array An array containing the used connections arrays
	 * @access private
	 * @static
	 */
	private static $connections_arrays = NULL;
	/**
	 * @var    array Entity managers instances array
	 * @access private
	 * @static
	 */
	private static $entity_managers = [];
	/**
	 * @var    string Data source class prefix
	 * @access private
	 * @static
	 */
	private static $ns_path = 'DataSources\\';
	/**
	 * Gets the connection array by name from the connections.inc file
	 *
	 * @param  string $name Connection name
	 * (name of the array in the connection.inc file)
	 * @return array Connection array
	 * @access private
	 * @static
	 */
	private static function GetConnectionArray($name) {
		if(is_array(self::$connections_arrays) && array_key_exists($name,self::$connections_arrays) && is_array(self::$connections_arrays[$name])) {
			return self::$connections_arrays[$name];
		}//if(is_array(self::$connections_arrays) && array_key_exists($name,self::$connections_arrays) && is_array(self::$connections_arrays[$name]))
		try {
			global $$name;
			if(!isset($$name)) { return FALSE; }
		} catch(\Exception $e) {
			return FALSE;
		}//END try
		self::$connections_arrays[$name] = $$name;
		return self::$connections_arrays[$name];
	}//END private static function GetConnectionArray
	/**
	 * description
	 *
	 * @param string $ds_name
	 * @param array|string|null   $connection
	 * @param string|null   $mode
	 * @param bool   $existing_only
	 * @return object Adapter instance
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function GetDataSource(string $ds_name,$connection = NULL,?string $mode = NULL,bool $existing_only = FALSE) {
		$ns_prefix = AppConfig::app_root_namespace().'\\'.self::$ns_path;
		$ds_arr = explode('\\',trim($ds_name,'\\'));
		$ds_type = array_shift($ds_arr);
		$ds_class = trim($ds_name,'\\');
		if($ds_type=='_Custom') {
			$dbmode = '_Custom';
			$conn = NULL;
			$ds_full_name = '\\'.(substr($ds_class,0,20)==$ns_prefix ? '' : $ns_prefix).$ds_class;
			$entity = NULL;
		} else {
			if((is_array($connection) && count($connection))) {
				$conn = $connection;
			} elseif(is_string($connection) && strlen($connection)) {
				$conn = self::GetConnectionArray($connection);
			} else {
				$conn = self::GetConnectionArray(NApp::default_db_connection());
			}//if((is_array($connection) && count($connection)))
			if(!is_array($conn) || count($conn)==0) { throw new AppException('Invalid database connection',E_ERROR,1); }
			$dbtype = get_array_param($conn,'db_type','','is_string');
			if(!strlen($dbtype)) { throw new AppException('Invalid database type',E_ERROR,1); }
			if(strlen($mode)) {
				$dbmode = strtolower($mode)=='native' ? $dbtype : $mode;
			} else {
				$dbmode = get_array_param($conn,'mode',$dbtype,'is_notempty_string');
			}//if(strlen($mode))
			$ds_full_name = NULL;
			if($dbmode=='Doctrine') {
				$entity = '\DataEntities\\'.$ds_class;
				if(class_exists($entity)) {
					if(!$entity::$isCustomDS) { $ds_full_name = '\NETopes\Core\Data\DoctrineDataSource'; }
				}//if(class_exists($entity))
			} else {
				$entity = NULL;
			}//if($dbmode=='Doctrine')
			if(!$ds_full_name) { $ds_full_name = '\\'.$ns_prefix.$dbmode.'\\'.$ds_class; }
		}//if($ds_type=='_Custom')
		return $ds_full_name::GetInstance($dbmode,$conn,$existing_only,$entity);
	}//END public static function GetDataSource
	/**
	 * Check if data adapter method exists
	 *
	 * @param  string $name Data adapter name
	 * @param  string $method Method to be searched
	 * @return bool Returns TRUE if the method exist of FALSE otherwise
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function MethodExists(string $name,string $method,?string $mode = NULL): bool {
		if(!strlen($name) || !strlen($method)) { return FALSE; }
		$da = self::GetDataSource($name,NULL,$mode);
		return method_exists($da,$method);
	}//END public static function MethodExists
	/**
	 * Get data from data source method
	 *
	 * @param  string $ds_name Data source name
	 * @param  string $ds_method Data source method
	 * @param  array $params An array of parameters to be passed to the method
	 * @param  array $extra_params An array of extra parameters to be passed to the method
	 * @param  bool $debug Flag debug activation/deactivation on this method
	 * @param  array $out_params An array passed by reference for the output parameters
	 * @return array|bool Returns the data source method response
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function GetArray(string $ds_name,string $ds_method,$params = [],$extra_params = [],bool $debug = FALSE,&$out_params = []) {
		$connection = NULL;
		if(is_array($extra_params) && array_key_exists('connection',$extra_params)) {
			if((is_array($extra_params['connection']) && count($extra_params['connection'])) || (is_string($extra_params['connection']) && strlen($extra_params['connection']))) { $connection = $extra_params['connection']; }
			unset($extra_params['connection']);
		}//if(is_array($extra_params) && array_key_exists('connection',$extra_params))
		$mode = get_array_param($extra_params,'mode','','is_string');
		try {
			$datasource = self::GetDataSource($ds_name,$connection,$mode);
			if($debug===TRUE) {
				$org_debug = $datasource->adapter->debug;
				$datasource->adapter->debug = TRUE;
			}//if($debug===TRUE)
			$result = $datasource->$ds_method($params,$extra_params);
			if($debug===TRUE) { $datasource->adapter->debug = $org_debug; }
			$out_params = get_array_param($extra_params,'out_params',[],'is_array');
			return $result;
		} catch(\Exception $e) {
			throw new AppException($e->getMessage(),$e->getCode(),0,$e->getFile(),$e->getLine());
		}//END try
	}//END public static function GetArray
	/**
	 * Call a data source method and return a key-value array
	 * (one column values as keys for the rows array)
	 *
	 * @param  string $ds_name Data source name
	 * @param  string $ds_method Data source method
	 * @param  array $params An array of parameters to be passed to the method
	 * @param  array $extra_params An array of extra parameters to be passed to the method
	 * @param  bool $debug Flag debug activation/deactivation on this method
	 * @param  array $out_params An array passed by reference for the output parameters
	 * @return array|bool Returns the data source method response
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function GetKeyValueArray(string $ds_name,string $ds_method,$params = [],$extra_params = [],bool $debug = FALSE,&$out_params = []) {
		$keyfield = get_array_param($extra_params,'keyfield','id','is_notempty_string');
		unset($extra_params['keyfield']);
		$result = self::GetArray($ds_name,$ds_method,$params,$extra_params,$debug,$out_params);
		return DataSource::ConvertResultsToKeyValue($result,$keyfield);
	}//END public static function GetKeyValueArray
	/**
	 * Get data from data source method
	 *
	 * @param  string $ds_name Data adapter name
	 * @param  string $ds_method Data adapter method
	 * @param  array $params An array of parameters to be passed to the method
	 * @param  array $extra_params An array of extra parameters to be passed to the method
	 * @param  bool $debug Flag debug activation/deactivation on this method
	 * @param  array $out_params An array passed by reference for the output parameters
	 * @return mixed Returns the data adapter method response
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function Get(string $ds_name,string $ds_method,$params = [],$extra_params = [],bool $debug = FALSE,&$out_params = []) {
		$entity = get_array_param($extra_params,'entity_class','\NETopes\Core\Data\VirtualEntity','is_notempty_string');
		unset($extra_params['entity_class']);
		$result = self::GetArray($ds_name,$ds_method,$params,$extra_params,$debug,$out_params);
		return DataSource::ConvertResultsToDataSet($result,$entity);
	}//END public static function Get
	/**
	 * Call a data source method and return a key-value DataSet
	 * (one column values as keys for the collection items)
	 *
	 * @param  string $ds_name Data source name
	 * @param  string $ds_method Data source method
	 * @param  array $params An array of parameters to be passed to the method
	 * @param  array $extra_params An array of extra parameters to be passed to the method
	 * @param  bool $debug Flag debug activation/deactivation on this method
	 * @param  array $out_params An array passed by reference for the output parameters
	 * @return DataSet|bool Returns the data source method response as DataSet
	 * @access public
	 * @static
	 * @throws \PAF\AppException
	 */
	public static function GetKeyValue($ds_name,$ds_method,$params = [],$extra_params = [],$debug = FALSE,&$out_params = []) {
		$entity = get_array_param($extra_params,'entity_class','\NETopes\Core\Data\VirtualEntity','is_notempty_string');
		unset($extra_params['entity_class']);
		$result = self::GetKeyValueArray($ds_name,$ds_method,$params,$extra_params,$debug,$out_params);
		return DataSource::ConvertResultsToDataSet($result,$entity);
	}//END public static function GetKeyValue
	/**
	 * description
	 *
	 * @param array $params
	 * @param array $connection
	 * @return mixed
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function SetGlobalVariables($params = [],$connection = []) {
		try {
			$datasource = self::GetDataSource('System\System',$connection);
			return $datasource->adapter->SetGlobalVariables($params);
		} catch (\Exception $e) {
			throw new AppException($e->getMessage(),$e->getCode(),0,$e->getFile(),$e->getLine());
		}//END try
	}//END public static function SetGlobalVariables
	/**
	 * description
	 *
	 * @param       $da_name
	 * @param array $connection
	 * @return bool
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function CloseConnection($da_name,$connection = []) {
		$result = FALSE;
		try {
			$datasource = self::GetDataSource($da_name,$connection,NULL,TRUE);
			if(is_object($datasource)) { $result = $datasource->adapter->CloseConnection(); }
		} catch (\Exception $e) {
			throw new AppException($e->getMessage(),$e->getCode(),0,$e->getFile(),$e->getLine());
		}//END try
		return $result;
	}//END public static function StartTransaction
	/**
	 * description
	 *
	 * @param       $da_name
	 * @param null  $transaction
	 * @param array $connection
	 * @param bool  $log
	 * @param bool  $overwrite
	 * @param null  $custom_tran_params
	 * @return mixed
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function StartTransaction($da_name,&$transaction = NULL,$connection = [],$log = FALSE,$overwrite = TRUE,$custom_tran_params = NULL) {
		try {
			$datasource = self::GetDataSource($da_name,$connection);
			return $datasource->adapter->BeginTran($transaction,$log,$overwrite,$custom_tran_params);
		} catch (\Exception $e) {
			throw new AppException($e->getMessage(),$e->getCode(),0,$e->getFile(),$e->getLine());
		}//END try
	}//END public static function StartTransaction
	/**
	 * description
	 *
	 * @param       $da_name
	 * @param null  $transaction
	 * @param bool  $error
	 * @param array $connection
	 * @param bool  $log
	 * @return mixed
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function CloseTransaction($da_name,$transaction = NULL,$error = FALSE,$connection = [],$log = FALSE) {
		try {
			$datasource = self::GetDataSource($da_name,$connection);
			if($error===TRUE || $error===1){
				return $datasource->adapter->RollbackTran($transaction,$log);
			} else {
				return $datasource->adapter->CommitTran($transaction,$log);
			}//if($error===TRUE || $error===1)
		} catch (\Exception $e) {
			throw new AppException($e->getMessage(),$e->getCode(),0,$e->getFile(),$e->getLine());
		}//END try
	}//END public static function CloseTransaction

	/**
	 * @param null $connection
	 * @param null $platform
	 * @return \Doctrine\ORM\EntityManager|null|object
	 */
	public static function GetEntityManager($connection = NULL,&$platform = NULL) {
		if((is_array($connection) && count($connection))) {
			$conn = $connection;
		} elseif(is_string($connection) && strlen($connection)) {
			$conn = self::GetConnectionArray($connection);
		} else {
			$conn = self::GetConnectionArray(NApp::default_db_connection());
		}//if((is_array($connection) && count($connection)))
		if(!is_array($conn) || !count($conn)) { throw new AppException('Invalid database connection!',E_ERROR,1); }
		$emKey = serialize($conn);
		if(is_array(self::$entity_managers) && isset(self::$entity_managers[$emKey]) && is_object(self::$entity_managers[$emKey])) { return self::$entity_managers[$emKey]; }
		if(!is_array(self::$entity_managers)) { self::$entity_managers = []; }
		self::$entity_managers[$emKey] = DoctrineAdapter::GetEntityManager(NApp::app_path(),$conn,$platform);
		return self::$entity_managers[$emKey];
	}//END public static function GetEntityManager
}//class DataProvider
?>
