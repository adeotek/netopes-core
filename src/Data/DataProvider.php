<?php
/**
 * Data provider file
 * All data request are made using the DataProvider class static methods.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use Exception;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Data\Doctrine\DataAdapter as DoctrineAdapter;
use NETopes\Core\Data\Doctrine\DataSource as DoctrineDataSource;
use NETopes\Core\Helpers;

/**
 * DataProvider prepares and makes the data requests
 * All data request are made using the DataProvider class static methods.
 *
 * @package  NETopes\Database
 */
class DataProvider {
    /**
     * @var    array An array containing the used connections arrays
     * @access private
     */
    private static $connectionsArrays=NULL;
    /**
     * @var    array Entity managers instances array
     * @access private
     */
    private static $entityManagers=[];

    /**
     * Gets the connection array by name from the connections.inc file
     *
     * @param string $name Connection name
     *                     (name of the array in the connection.inc file)
     * @return array|bool Connection array
     * @access private
     */
    private static function GetConnectionArray(string $name) {
        if(is_array(self::$connectionsArrays) && array_key_exists($name,self::$connectionsArrays) && is_array(self::$connectionsArrays[$name])) {
            return self::$connectionsArrays[$name];
        }//if(is_array(self::$connectionsArrays) && array_key_exists($name,self::$connectionsArrays) && is_array(self::$connectionsArrays[$name]))
        try {
            global $$name;
            if(!isset($$name)) {
                return FALSE;
            }
        } catch(Exception $e) {
            return FALSE;
        }//END try
        self::$connectionsArrays[$name]=$$name;
        return self::$connectionsArrays[$name];
    }//END private static function GetConnectionArray

    /**
     * description
     *
     * @param string            $dsName
     * @param array|string|null $connection
     * @param string|null       $mode
     * @param bool              $existing_only
     * @return object Adapter instance
     * @throws \NETopes\Core\AppException
     */
    public static function GetDataSource(string $dsName,$connection=NULL,?string $mode=NULL,bool $existing_only=FALSE) {
        $ns_prefix=AppConfig::GetValue('app_root_namespace').'\\'.AppConfig::GetValue('app_data_sources_namespace_prefix');
        $ds_arr=explode('\\',trim($dsName,'\\'));
        $ds_type=array_shift($ds_arr);
        $ds_class=trim($dsName,'\\');
        if($ds_type=='_Custom') {
            $dbmode='_Custom';
            $conn=NULL;
            $ds_full_name='\\'.(substr($ds_class,0,20)==$ns_prefix ? '' : $ns_prefix).$ds_class;
            $entity=NULL;
        } else {
            if((is_array($connection) && count($connection))) {
                $conn=$connection;
            } elseif(is_string($connection) && strlen($connection)) {
                $conn=self::GetConnectionArray($connection);
            } else {
                $conn=self::GetConnectionArray(NApp::$defaultDbConnection);
            }//if((is_array($connection) && count($connection)))
            if(!is_array($conn) || count($conn)==0) {
                throw new AppException('Invalid database connection',E_ERROR,1);
            }
            $dbtype=get_array_value($conn,'db_type','','is_string');
            if(!strlen($dbtype)) {
                throw new AppException('Invalid database type',E_ERROR,1);
            }
            if(strlen($mode)) {
                $dbmode=strtolower($mode)=='native' ? $dbtype : $mode;
            } else {
                $dbmode=get_array_value($conn,'mode',$dbtype,'is_notempty_string');
            }//if(strlen($mode))
            $ds_full_name=NULL;
            if($dbmode=='Doctrine') {
                $entity='\\'.trim(AppConfig::GetValue('doctrine_entities_namespace'),'\\').'\\'.$ds_class;
                if(class_exists($entity)) {
                    if(!$entity::$isCustomDS) {
                        $ds_full_name=DoctrineDataSource::class;
                    }
                }//if(class_exists($entity))
            } else {
                $entity=NULL;
            }//if($dbmode=='Doctrine')
            if(!$ds_full_name) {
                $ds_full_name='\\'.$ns_prefix.$dbmode.'\\'.$ds_class;
            }
        }//if($ds_type=='_Custom')
        return $ds_full_name::GetInstance($dbmode,$conn,$existing_only,$entity);
    }//END public static function GetDataSource

    /**
     * Check if data adapter method exists
     *
     * @param string      $name   Data adapter name
     * @param string      $method Method to be searched
     * @param null|string $mode
     * @return bool Returns TRUE if the method exist of FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function MethodExists(string $name,string $method,?string $mode=NULL): bool {
        if(!strlen($name) || !strlen($method)) {
            return FALSE;
        }
        $da=self::GetDataSource($name,NULL,$mode);
        return method_exists($da,$method);
    }//END public static function MethodExists

    /**
     * Get data from data source method
     *
     * @param string $dsName      Data source name
     * @param string $dsMethod    Data source method
     * @param array  $params      An array of parameters to be passed to the method
     * @param array  $extraParams An array of extra parameters to be passed to the method
     * @param bool   $debug       Flag debug activation/deactivation on this method
     * @param array  $outParams   An array passed by reference for the output parameters
     * @return array|bool Returns the data source method response
     * @throws \NETopes\Core\AppException
     */
    public static function GetArray(string $dsName,string $dsMethod,$params=[],$extraParams=[],bool $debug=FALSE,&$outParams=[]) {
        $connection=NULL;
        if(is_array($extraParams) && array_key_exists('connection',$extraParams)) {
            if((is_array($extraParams['connection']) && count($extraParams['connection'])) || (is_string($extraParams['connection']) && strlen($extraParams['connection']))) {
                $connection=$extraParams['connection'];
            }
            unset($extraParams['connection']);
        }//if(is_array($extraParams) && array_key_exists('connection',$extraParams))
        $orgDebug=FALSE;
        $mode=get_array_value($extraParams,'mode','','is_string');
        try {
            $dataSource=self::GetDataSource($dsName,$connection,$mode);
            if($debug===TRUE) {
                $orgDebug=$dataSource->adapter->debug;
                $dataSource->adapter->debug=TRUE;
            }//if($debug===TRUE)
            $result=$dataSource->$dsMethod($params,$extraParams);
            if($debug===TRUE) {
                $dataSource->adapter->debug=$orgDebug;
            }
            $outParams=get_array_value($extraParams,'out_params',[],'is_array');
            return $result;
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public static function GetArray

    /**
     * Call a data source method and return a key-value array
     * (one column values as keys for the rows array)
     *
     * @param string $dsName      Data source name
     * @param string $dsMethod    Data source method
     * @param array  $params      An array of parameters to be passed to the method
     * @param array  $extraParams An array of extra parameters to be passed to the method
     * @param bool   $debug       Flag debug activation/deactivation on this method
     * @param array  $outParams   An array passed by reference for the output parameters
     * @return array|bool Returns the data source method response
     * @throws \NETopes\Core\AppException
     */
    public static function GetKeyValueArray(string $dsName,string $dsMethod,$params=[],$extraParams=[],bool $debug=FALSE,&$outParams=[]) {
        $keyfield=get_array_value($extraParams,'keyfield','id','is_notempty_string');
        unset($extraParams['keyfield']);
        $result=self::GetArray($dsName,$dsMethod,$params,$extraParams,$debug,$outParams);
        return DataSourceHelpers::ConvertResultsToKeyValue($result,$keyfield);
    }//END public static function GetKeyValueArray

    /**
     * Get data from data source method
     *
     * @param string $dsName      Data adapter name
     * @param string $dsMethod    Data adapter method
     * @param array  $params      An array of parameters to be passed to the method
     * @param array  $extraParams An array of extra parameters to be passed to the method
     * @param bool   $debug       Flag debug activation/deactivation on this method
     * @param array  $outParams   An array passed by reference for the output parameters
     * @return mixed Returns the data adapter method response
     * @throws \NETopes\Core\AppException
     */
    public static function Get(string $dsName,string $dsMethod,$params=[],$extraParams=[],bool $debug=FALSE,&$outParams=[]) {
        $entity=get_array_value($extraParams,'entity_class',VirtualEntity::class,'is_notempty_string');
        unset($extraParams['entity_class']);
        $result=self::GetArray($dsName,$dsMethod,$params,$extraParams,$debug,$outParams);
        return DataSourceHelpers::ConvertResultsToDataSet($result,$entity);
    }//END public static function Get

    /**
     * Call a data source method and return a key-value DataSet
     * (one column values as keys for the collection items)
     *
     * @param string $dsName      Data source name
     * @param string $dsMethod    Data source method
     * @param array  $params      An array of parameters to be passed to the method
     * @param array  $extraParams An array of extra parameters to be passed to the method
     * @param bool   $debug       Flag debug activation/deactivation on this method
     * @param array  $outParams   An array passed by reference for the output parameters
     * @return DataSet|bool Returns the data source method response as DataSet
     * @throws \NETopes\Core\AppException
     */
    public static function GetKeyValue($dsName,$dsMethod,$params=[],$extraParams=[],$debug=FALSE,&$outParams=[]) {
        $entity=get_array_value($extraParams,'entity_class',VirtualEntity::class,'is_notempty_string');
        unset($extraParams['entity_class']);
        $result=self::GetKeyValueArray($dsName,$dsMethod,$params,$extraParams,$debug,$outParams);
        return DataSourceHelpers::ConvertResultsToDataSet($result,$entity);
    }//END public static function GetKeyValue

    /**
     * description
     *
     * @param array $params
     * @param array $connection
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public static function SetGlobalVariables($params=[],$connection=[]) {
        try {
            $dataSource=self::GetDataSource('System\System',$connection);
            return $dataSource->adapter->SetGlobalVariables($params);
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public static function SetGlobalVariables

    /**
     * description
     *
     * @param       $da_name
     * @param array $connection
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public static function CloseConnection($da_name,$connection=[]) {
        $result=FALSE;
        try {
            $dataSource=self::GetDataSource($da_name,$connection,NULL,TRUE);
            if(is_object($dataSource)) {
                $result=$dataSource->adapter->CloseConnection();
            }
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
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
     * @throws \NETopes\Core\AppException
     */
    public static function StartTransaction($da_name,&$transaction=NULL,$connection=[],$log=FALSE,$overwrite=TRUE,$custom_tran_params=NULL) {
        try {
            $dataSource=self::GetDataSource($da_name,$connection);
            return $dataSource->adapter->BeginTran($transaction,$log,$overwrite,$custom_tran_params);
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
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
     * @throws \NETopes\Core\AppException
     */
    public static function CloseTransaction($da_name,$transaction=NULL,$error=FALSE,$connection=[],$log=FALSE) {
        try {
            $dataSource=self::GetDataSource($da_name,$connection);
            if($error===TRUE || $error===1) {
                return $dataSource->adapter->RollbackTran($transaction,$log);
            } else {
                return $dataSource->adapter->CommitTran($transaction,$log);
            }//if($error===TRUE || $error===1)
        } catch(Exception $e) {
            throw AppException::GetInstance($e);
        }//END try
    }//END public static function CloseTransaction

    /**
     * @param null $connection
     * @param null $platform
     * @return \Doctrine\ORM\EntityManager|null
     * @throws \NETopes\Core\AppException
     */
    public static function GetEntityManager($connection=NULL,&$platform=NULL) {
        if((is_array($connection) && count($connection))) {
            $conn=$connection;
        } elseif(is_string($connection) && strlen($connection)) {
            $conn=self::GetConnectionArray($connection);
        } else {
            $conn=self::GetConnectionArray(NApp::$defaultDbConnection);
        }//if((is_array($connection) && count($connection)))
        if(!is_array($conn) || !count($conn)) {
            throw new AppException('Invalid database connection!',E_ERROR,1);
        }
        $emKey=serialize($conn);
        if(is_array(self::$entityManagers) && isset(self::$entityManagers[$emKey]) && is_object(self::$entityManagers[$emKey])) {
            return self::$entityManagers[$emKey];
        }
        if(!is_array(self::$entityManagers)) {
            self::$entityManagers=[];
        }
        self::$entityManagers[$emKey]=DoctrineAdapter::GetEntityManager(NApp::$appPath,$conn,$platform);
        return self::$entityManagers[$emKey];
    }//END public static function GetEntityManager

    /**
     * Generate new UUID (version 4, RFC4122) MSSQL UNIQUEIDENTIFIER compatible
     *
     * @return string
     */
    public static function NewUuid(): string {
        return Helpers::new_uuid();
    }//END public static function NewGuid
}//END class DataProvider
