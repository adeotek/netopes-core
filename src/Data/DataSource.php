<?php
/**
 * Data source base class file
 * This contains an class which every data source extends.
 *
 * @package    @package NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppSession;

/**
 * Class DataSource
 *
 * @package NETopes\Core\Data
 */
class DataSource {
    /**
     * @var    array A static array containing all data adapters instances
     * @access private
     */
    private static $_dsInstances=[];
    /**
     * @var    string Data adapter type
     */
    public $type=NULL;
    /**
     * @var    object Data adapter object for the current data source
     */
    public $adapter=NULL;
    /**
     * @var    string|null Entity class name
     */
    protected $entityName=NULL;

    /**
     * Constructor for DaTaAdapter class
     *
     * @param string $type       Database type (_Custom/FirebirdSql/MySql/MariaDb/SqLite/SqlSrv/MongoDb/Oracle)
     * @param mixed  $connection Database connection array
     * @param null   $entityName
     * @access private
     */
    private function __construct($type,$connection=NULL,$entityName=NULL) {
        $this->type=$type;
        if($this->type=='_Custom') {
            return;
        }
        $className='\NETopes\Core\Data\\'.($type=='Doctrine' ? 'Doctrine\\DataAdapter' : $type.'Adapter');
        $this->adapter=$className::GetInstance($type,$connection);
        $this->entityName=$entityName;
    }//END private function __construct

    /**
     * Gets the singleton instance for the specified data adaper
     *
     * @param string $type       Database type (_Default/Firebird/MySql/MariaDb/SqLite/SqlSrv/MongoDb/Oracle)
     * @param array  $connection Database connection array
     * @param bool   $existing_only
     * @param null   $entityName
     * @return object Returns the data adapter object
     */
    public static function GetInstance($type,$connection=NULL,$existing_only=FALSE,$entityName=NULL) {
        $name=get_called_class();
        $ikey=AppSession::GetNewUID($type.'|'.$name.'|'.$entityName.'|'.serialize($connection),'sha1',TRUE);
        if(!array_key_exists($ikey,self::$_dsInstances) || is_null(self::$_dsInstances[$ikey])) {
            if($existing_only) {
                return NULL;
            }
            self::$_dsInstances[$ikey]=new $name($type,$connection,$entityName);
        }//if (!array_key_exists($ikey,self::$_dsInstances) || is_null(self::$_dsInstances[$ikey]))
        return self::$_dsInstances[$ikey];
    }//END public static function GetInstance

    /**
     * Prepares and executes the database call twice:
     * * first to get the total rows count
     * * second to get data limited to certain rows
     * (used mainly for generating paged views)
     *
     * @param string $procedure    The name of the stored procedure
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extra_params An array of parameters that may contain:
     *                             - 'transaction'= name of transaction in which the query will run
     *                             - 'type' = request type: select, count, execute (default 'select')
     *                             - 'first_row' = integer to limit number of returned rows
     *                             (if used with 'last_row' represents the offset of the returned rows)
     *                             - 'last_row' = integer to limit number of returned rows
     *                             (to be used only with 'first_row')
     *                             - 'sort' = an array of fields to compose ORDER BY clause
     *                             - 'filters' = an array of condition to be applied in WHERE clause
     *                             - 'out_params' = an array of output params
     * @param bool   $cache        Flag indicating if the cache should be used or not
     * @param string $tag          Cache key tag
     * @param bool   $sp_count     Flag indicating if the count is done inside the stored procedure
     *                             or in the procedure call (default value is FALSE)
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     */
    public function GetCountAndData($procedure,$params=[],&$extra_params=[],$cache=FALSE,$tag=NULL,$sp_count=FALSE) {
        if($cache && NApp::CacheDbCall()) {
            $params_salt=$procedure;
            $params_salt.=serialize(!is_array($params) ? [] : $params);
            $params_salt.=serialize(!is_array($extra_params) ? [] : $extra_params);
            $key=AppSession::GetNewUID($params_salt,'sha1',TRUE);
            $ltag=is_string($tag) && strlen($tag) ? $tag : $procedure;
            $result=RedisCacheHelpers::GetCacheData($key,$ltag);
            if($result!==FALSE) {
                if(AppConfig::GetValue('db_debug')) {
                    NApp::Dlog('Cache loaded data for procedure: '.$procedure,'GetCountAndData');
                }//if(AppConfig::GetValue('db_debug'))
                return $result;
            }//if($result!==FALSE)
        }//if($cache && NApp::CacheDbCall())
        $out_params=get_array_value($extra_params,'out_params',[],'is_array');
        $count_select=strtolower(get_array_value($extra_params,'type','','is_string'))=='count-select';
        if($count_select) {
            switch(intval($sp_count)) {
                case 1:
                    $extra_params['type']='select';
                    $lextra_params=$extra_params;
                    $lextra_params['first_row']=NULL;
                    $lextra_params['last_row']=NULL;
                    $params['with_count']=1;
                    $result=$this->adapter->ExecuteProcedure($procedure,$params,$lextra_params);
                    $result=['count'=>$result[0]['rcount']];
                    $params['with_count']=0;
                    unset($extra_params['out_params']);
                    $extra_params['out_params']=$out_params;
                    $result['data']=$this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
                    break;
                case 2:
                    $extra_params['type']='select';
                    $params['with_count']=1;
                    $lresult=$this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
                    if(is_array($lresult) && count($lresult)) {
                        $result=['count'=>$lresult[0]['rcount'],'data'=>$lresult];
                    } else {
                        $result=['count'=>0,'data'=>[]];
                    }//if(is_array($lresult) && count($lresult))
                    break;
                default:
                    $extra_params['type']='count';
                    $result=change_array_keys_case($this->adapter->ExecuteProcedure($procedure,$params,$extra_params),TRUE,CASE_LOWER);
                    $result=['count'=>$result[0]['count']];
                    $extra_params['type']='select';
                    unset($extra_params['out_params']);
                    $extra_params['out_params']=$out_params;
                    $result['data']=$this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
                    break;
            }//END switch
        } else {
            $result=$this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
        }//if($count_select)
        if($cache && NApp::CacheDbCall()) {
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$ltag,$count_select);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetCountAndData

    /**
     * Prepares and executes the database call twice:
     * * first to get the total rows count
     * * second to get data limited to certain rows
     * (used mainly for generating paged views)
     *
     * @param string $query        The query string
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extra_params An array of parameters that may contain:
     *                             - 'transaction'= name of transaction in which the query will run
     *                             - 'type' = request type: select, count, execute (default 'select')
     *                             - 'first_row' = integer to limit number of returned rows
     *                             (if used with 'last_row' represents the offset of the returned rows)
     *                             - 'last_row' = integer to limit number of returned rows
     *                             (to be used only with 'first_row')
     *                             - 'sort' = an array of fields to compose ORDER BY clause
     *                             - 'filters' = an array of condition to be applied in WHERE clause
     *                             - 'out_params' = an array of output params
     * @param bool   $cache        Flag indicating if the cache should be used or not
     * @param string $tag          Cache key tag
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     */
    public function GetQueryCountAndData($query,$params=[],&$extra_params=[],$cache=FALSE,$tag=NULL) {
        if($cache && NApp::CacheDbCall()) {
            $params_salt=AppSession::GetNewUID($query,'md5',TRUE);
            $params_salt.=serialize(!is_array($params) ? [] : $params);
            $params_salt.=serialize(!is_array($extra_params) ? [] : $extra_params);
            $key=AppSession::GetNewUID($params_salt,'sha1',TRUE);
            $ltag=is_string($tag) && strlen($tag) ? $tag : AppSession::GetNewUID($query,'sha1',TRUE);
            $result=RedisCacheHelpers::GetCacheData($key,$ltag);
            if($result!==FALSE) {
                if(AppConfig::GetValue('db_debug')) {
                    NApp::Dlog('Cache loaded data for query: '.$query,'GetCountAndData');
                }//if(AppConfig::GetValue('db_debug'))
                return $result;
            }//if($result!==FALSE)
        }//if($cache && NApp::CacheDbCall())
        $out_params=get_array_value($extra_params,'out_params',[],'is_array');
        $select_stmt=get_array_value($extra_params,'select_statement','SELECT * ','is_notempty_string');
        $count_select=strtolower(get_array_value($extra_params,'type','','is_string'))=='count-select';
        if($count_select) {
            $extra_params['type']='count';
            $result=change_array_keys_case($this->adapter->ExecuteQuery('SELECT COUNT(1) AS RCOUNT '.$query,$params,$extra_params),TRUE,CASE_LOWER);
            $result=['count'=>$result[0]['rcount']];
            $extra_params['type']='select';
            unset($extra_params['out_params']);
            $extra_params['out_params']=$out_params;
            $result['data']=$this->adapter->ExecuteQuery($select_stmt.$query,$params,$extra_params);
        } else {
            if(strtolower(substr(trim($query),0,6))=='select') {
                $qry=$query;
            } elseif(strtolower(get_array_value($extra_params,'type','','is_string'))=='count') {
                $qry='SELECT COUNT(1) AS RCOUNT '.$query;
            } else {
                $qry=$select_stmt.$query;
            }//if(strtolower(substr(trim($query),0,6))=='select')
            $result=$this->adapter->ExecuteQuery($qry,$params,$extra_params);
        }//if($count_select)
        if($cache && NApp::CacheDbCall()) {
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$ltag,$count_select);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetQueryCountAndData

    /**
     * Prepares and executes the database stored procedure call
     *
     * @param string $procedure    The name of the stored procedure
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extra_params An array of parameters that may contain:
     *                             - 'transaction'= name of transaction in which the query will run
     *                             - 'type' = request type: select, count, execute (default 'select')
     *                             - 'first_row' = integer to limit number of returned rows
     *                             (if used with 'last_row' represents the offset of the returned rows)
     *                             - 'last_row' = integer to limit number of returned rows
     *                             (to be used only with 'first_row')
     *                             - 'sort' = an array of fields to compose ORDER BY clause
     *                             - 'filters' = an array of condition to be applyed in WHERE clause
     *                             - 'out_params' = an array of output params
     * @param bool   $cache        Flag indicating if the cache should be used or not
     * @param string $tag          Cache key tag
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     */
    public function GetProcedureData($procedure,$params=[],&$extra_params=[],$cache=FALSE,$tag=NULL) {
        if($cache && NApp::CacheDbCall()) {
            $params_salt=$procedure;
            $params_salt.=serialize(!is_array($params) ? [] : $params);
            $params_salt.=serialize(!is_array($extra_params) ? [] : $extra_params);
            $key=AppSession::GetNewUID($params_salt,'sha1',TRUE);
            $ltag=is_string($tag) && strlen($tag) ? $tag : $procedure;
            $result=RedisCacheHelpers::GetCacheData($key,$ltag);
            // NApp::Dlog($result,$procedure.':'.$key);
            if($result!==FALSE) {
                if(AppConfig::GetValue('db_debug')) {
                    NApp::Dlog('Cache loaded data for procedure: '.$procedure,'GetProcedureData');
                }//if(AppConfig::GetValue('db_debug'))
                return $result;
            }//if($result!==FALSE)
            $result=$this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
            // NApp::Dlog($result,$procedure);
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$ltag);
        } else {
            $result=$this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetProcedureData

    /**
     * Prepares and executes the database stored procedure call
     *
     * @param string $query        The query string
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extra_params An array of parameters that may contain:
     *                             - 'transaction'= name of transaction in which the query will run
     *                             - 'type' = request type: select, count, execute (default 'select')
     *                             - 'first_row' = integer to limit number of returned rows
     *                             (if used with 'last_row' represents the offset of the returned rows)
     *                             - 'last_row' = integer to limit number of returned rows
     *                             (to be used only with 'first_row')
     *                             - 'sort' = an array of fields to compose ORDER BY clause
     *                             - 'filters' = an array of condition to be applyed in WHERE clause
     *                             - 'out_params' = an array of output params
     * @param bool   $cache        Flag indicating if the cache should be used or not
     * @param string $tag          Cache key tag
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     */
    public function GetQueryData($query,$params=[],&$extra_params=[],$cache=FALSE,$tag=NULL) {
        if($cache && NApp::CacheDbCall()) {
            $params_salt=AppSession::GetNewUID($query,'md5',TRUE);
            $params_salt.=serialize(!is_array($params) ? [] : $params);
            $params_salt.=serialize(!is_array($extra_params) ? [] : $extra_params);
            $key=AppSession::GetNewUID($params_salt,'sha1',TRUE);
            $ltag=is_string($tag) && strlen($tag) ? $tag : AppSession::GetNewUID($query,'sha1',TRUE);
            $result=RedisCacheHelpers::GetCacheData($key,$ltag);
            if($result!==FALSE) {
                if(AppConfig::GetValue('db_debug')) {
                    NApp::Dlog('Cache loaded data for query: '.$query,'GetQueryData');
                }//if(AppConfig::GetValue('db_debug'))
                return $result;
            }//if($result!==FALSE)
            $result=$this->adapter->ExecuteQuery($query,$params,$extra_params);
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$ltag);
        } else {
            $result=$this->adapter->ExecuteQuery($query,$params,$extra_params);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetQueryData
}//END class DataSource