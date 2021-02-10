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
use NETopes\Core\AppSession;

/**
 * Class DataSource
 *
 * @package NETopes\Core\Data
 */
class DataSource {
    const SP_COUNT_EXPLICIT=0;
    const SP_COUNT_IN_PROC_WITH_OUTPUT=1;
    const SP_COUNT_IN_PROC_RESULTS=2;
    const SP_COUNT_IN_PROC_RESULTS_WITH_LIMIT=3;

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
     * @param array  $extraParams  An array of parameters that may contain:
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
     * @param int    $spCount      Flag indicating how the count is done:
     *                             inside the stored procedure or in the procedure call
     *                             (default value is 0=NONE)
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     */
    public function GetCountAndData(string $procedure,$params=[],&$extraParams=[],$cache=FALSE,$tag=NULL,int $spCount=self::SP_COUNT_EXPLICIT) {
        $key=$lTag=NULL;
        if($cache && NApp::CacheDbCall()) {
            $paramsSalt=$procedure;
            $paramsSalt.=serialize(!is_array($params) ? [] : $params);
            $paramsSalt.=serialize(!is_array($extraParams) ? [] : $extraParams);
            $key=AppSession::GetNewUID($paramsSalt,'sha1',TRUE);
            $lTag=is_string($tag) && strlen($tag) ? $tag : $procedure;
            $result=RedisCacheHelpers::GetCacheData($key,$lTag);
            if($result!==FALSE) {
                NApp::DbDebug('Cache loaded data for procedure: '.$procedure,'GetCountAndData');
                return $result;
            }//if($result!==FALSE)
        }//if($cache && NApp::CacheDbCall())
        $outParams=get_array_value($extraParams,'out_params',[],'is_array');
        $countSelect=strtolower(get_array_value($extraParams,'type','','is_string'))=='count-select';
        if($countSelect) {
            switch(intval($spCount)) {
                case self::SP_COUNT_IN_PROC_WITH_OUTPUT:
                    $extraParams['type']='select';
                    $lExtraParams=$extraParams;
                    $lExtraParams['first_row']=NULL;
                    $lExtraParams['last_row']=NULL;
                    $params['with_count']=1;
                    $result=$this->adapter->ExecuteProcedure($procedure,$params,$lExtraParams);
                    $result=['count'=>$result[0]['rcount']];
                    $params['with_count']=0;
                    unset($extraParams['out_params']);
                    $extraParams['out_params']=$outParams;
                    $result['data']=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
                    break;
                case self::SP_COUNT_IN_PROC_RESULTS:
                    $extraParams['type']='select';
                    $params['with_count']=1;
                    $lResult=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
                    if(is_array($lResult) && count($lResult)) {
                        $result=['count'=>$lResult[0]['rcount'],'data'=>$lResult];
                    } else {
                        $result=['count'=>0,'data'=>[]];
                    }//if(is_array($lResult) && count($lResult))
                    break;
                case self::SP_COUNT_IN_PROC_RESULTS_WITH_LIMIT:
                    $extraParams['type']='select';
                    $params['with_count']=1;
                    $params['first_row']=$extraParams['first_row'];
                    $params['last_row']=$extraParams['last_row'];
                    $extraParams['first_row']=NULL;
                    $extraParams['last_row']=NULL;
                    $lResult=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
                    if(is_array($lResult) && count($lResult)) {
                        $result=['count'=>$lResult[0]['rcount'],'data'=>$lResult];
                    } else {
                        $result=['count'=>0,'data'=>[]];
                    }//if(is_array($lResult) && count($lResult))
                    break;
                case self::SP_COUNT_EXPLICIT:
                default:
                    $extraParams['type']='count';
                    $result=change_array_keys_case($this->adapter->ExecuteProcedure($procedure,$params,$extraParams),TRUE,CASE_LOWER);
                    $result=['count'=>$result[0]['count']];
                    $extraParams['type']='select';
                    unset($extraParams['out_params']);
                    $extraParams['out_params']=$outParams;
                    $result['data']=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
                    break;
            }//END switch
        } else {
            $result=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
        }//if($countSelect)
        if($cache && NApp::CacheDbCall()) {
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$lTag,$countSelect);
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
     * @param array  $extraParams  An array of parameters that may contain:
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
    public function GetQueryCountAndData(string $query,$params=[],&$extraParams=[],$cache=FALSE,$tag=NULL) {
        $key=$lTag=NULL;
        if($cache && NApp::CacheDbCall()) {
            $paramsSalt=AppSession::GetNewUID($query,'md5',TRUE);
            $paramsSalt.=serialize(!is_array($params) ? [] : $params);
            $paramsSalt.=serialize(!is_array($extraParams) ? [] : $extraParams);
            $key=AppSession::GetNewUID($paramsSalt,'sha1',TRUE);
            $lTag=is_string($tag) && strlen($tag) ? $tag : AppSession::GetNewUID($query,'sha1',TRUE);
            $result=RedisCacheHelpers::GetCacheData($key,$lTag);
            if($result!==FALSE) {
                NApp::DbDebug('Cache loaded data for query: '.$query,'GetCountAndData');
                return $result;
            }//if($result!==FALSE)
        }//if($cache && NApp::CacheDbCall())
        $outParams=get_array_value($extraParams,'out_params',[],'is_array');
        $select_stmt=get_array_value($extraParams,'select_statement','SELECT * ','is_notempty_string');
        $countSelect=strtolower(get_array_value($extraParams,'type','','is_string'))=='count-select';
        if($countSelect) {
            $extraParams['type']='count';
            $result=change_array_keys_case($this->adapter->ExecuteQuery('SELECT COUNT(1) AS RCOUNT '.$query,$params,$extraParams),TRUE,CASE_LOWER);
            $result=['count'=>$result[0]['rcount']];
            $extraParams['type']='select';
            unset($extraParams['out_params']);
            $extraParams['out_params']=$outParams;
            $result['data']=$this->adapter->ExecuteQuery($select_stmt.$query,$params,$extraParams);
        } else {
            if(strtolower(substr(trim($query),0,6))=='select') {
                $qry=$query;
            } elseif(strtolower(get_array_value($extraParams,'type','','is_string'))=='count') {
                $qry='SELECT COUNT(1) AS RCOUNT '.$query;
            } else {
                $qry=$select_stmt.$query;
            }//if(strtolower(substr(trim($query),0,6))=='select')
            $result=$this->adapter->ExecuteQuery($qry,$params,$extraParams);
        }//if($countSelect)
        if($cache && NApp::CacheDbCall()) {
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$lTag,$countSelect);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetQueryCountAndData

    /**
     * Prepares and executes the database stored procedure call
     *
     * @param string $procedure    The name of the stored procedure
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extraParams  An array of parameters that may contain:
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
    public function GetProcedureData($procedure,$params=[],&$extraParams=[],$cache=FALSE,$tag=NULL) {
        if($cache && NApp::CacheDbCall()) {
            $paramsSalt=$procedure;
            $paramsSalt.=serialize(!is_array($params) ? [] : $params);
            $paramsSalt.=serialize(!is_array($extraParams) ? [] : $extraParams);
            $key=AppSession::GetNewUID($paramsSalt,'sha1',TRUE);
            $lTag=is_string($tag) && strlen($tag) ? $tag : $procedure;
            $result=RedisCacheHelpers::GetCacheData($key,$lTag);
            // NApp::Dlog($result,$procedure.':'.$key);
            if($result!==FALSE) {
                NApp::DbDebug('Cache loaded data for procedure: '.$procedure,'GetProcedureData');
                return $result;
            }//if($result!==FALSE)
            $result=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
            // NApp::Dlog($result,$procedure);
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$lTag);
        } else {
            $result=$this->adapter->ExecuteProcedure($procedure,$params,$extraParams);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetProcedureData

    /**
     * Prepares and executes the database stored procedure call
     *
     * @param string $query        The query string
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extraParams  An array of parameters that may contain:
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
    public function GetQueryData($query,$params=[],&$extraParams=[],$cache=FALSE,$tag=NULL) {
        if($cache && NApp::CacheDbCall()) {
            $paramsSalt=AppSession::GetNewUID($query,'md5',TRUE);
            $paramsSalt.=serialize(!is_array($params) ? [] : $params);
            $paramsSalt.=serialize(!is_array($extraParams) ? [] : $extraParams);
            $key=AppSession::GetNewUID($paramsSalt,'sha1',TRUE);
            $lTag=is_string($tag) && strlen($tag) ? $tag : AppSession::GetNewUID($query,'sha1',TRUE);
            $result=RedisCacheHelpers::GetCacheData($key,$lTag);
            if($result!==FALSE) {
                NApp::DbDebug('Cache loaded data for query: '.$query,'GetQueryData');
                return $result;
            }//if($result!==FALSE)
            $result=$this->adapter->ExecuteQuery($query,$params,$extraParams);
            RedisCacheHelpers::SetCacheData($key,(is_null($result) ? [] : $result),$lTag);
        } else {
            $result=$this->adapter->ExecuteQuery($query,$params,$extraParams);
        }//if($cache && NApp::CacheDbCall())
        return $result;
    }//END public function GetQueryData
}//END class DataSource