<?php
/**
 * Data source base class file
 *
 * This contains an class which every data source extends.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NETopes\Core\AppConfig;
use NETopes\Core\AppSession;
use NETopes\Core\AppException;
use NApp;
use Redis;
/**
 * DataSource is the base class for all data sources
 *
 * All data sources must extend this class.
 *
 * @package  NETopes\Database
 * @access   public
 */
class DataSource {
	/**
	 * @var    array A static array containing all data adapters instances
	 * @access private
	 * @static
	 */
	private static $DataSourcesInstances = [];
	/**
	 * @var    string Data adapter type
	 * @access public
	 */
	public $type = NULL;
	/**
	 * @var    object Data adapter object for the current data source
	 * @access public
	 */
	public $adapter = NULL;
	/**
	 * @var    string|null Entity class name
	 * @access public
	 */
	protected $entityName = NULL;
	/**
	 * Constructor for DaTaAdapter class
	 *
	 * @param  string $type Database type (_Custom/FirebirdSql/MySql/MariaDb/SqLite/SqlSrv/MongoDb/Oracle)
	 * @param  mixed $connection Database connection array
	 * @param null    $entityName
	 * @access private
	 */
	private function __construct($type,$connection = NULL,$entityName = NULL) {
		$this->type = $type;
		if($this->type=='_Custom') { return; }
		$class_name = 'NETopes\Core\Data\\'.$type.'Adapter';
		$this->adapter = $class_name::GetInstance($type,$connection);
		$this->entityName = $entityName;
	}//END private function __construct
	/**
	 * Gets the singleton instance for the specified data adaper
	 *
	 * @param string $type Database type (_Default/Firebird/MySql/MariaDb/SqLite/SqlSrv/MongoDb/Oracle)
	 * @param  array $connection Database connection array
	 * @param bool   $existing_only
	 * @param null   $entityName
	 * @return object Returns the data adapter object
	 * @access public
	 */
	public static function GetInstance($type,$connection = NULL,$existing_only = FALSE,$entityName = NULL) {
		$name = get_called_class();
		$ikey = AppSession::GetNewUID($type.'|'.$name.'|'.$entityName.'|'.serialize($connection),'sha1',TRUE);
		if(!array_key_exists($ikey,self::$DataSourcesInstances) || is_null(self::$DataSourcesInstances[$ikey])) {
			if($existing_only) { return NULL; }
			self::$DataSourcesInstances[$ikey] = new $name($type,$connection,$entityName);
		}//if (!array_key_exists($ikey,self::$DataSourcesInstances) || is_null(self::$DataSourcesInstances[$ikey]))
		return self::$DataSourcesInstances[$ikey];
	}//END public static function GetInstance
    /**
     * Replaces the keys (first level only) of the $results array
     * with the values of a specified key in the second level of the array
     * Obs. The $results array is usualy an results array from a database query.
     *
     * @param  array  $results The array to be converted
     * @param  string $keyField The key for the second level value to be
     * set as the new main key.
     * If $keyfield is empty or NULL, the 'id' key will be used.
     * @param bool    $convertToDataSet
     * @param int|null $case
     * @return array Returns the converted array
     * @access public
     * @static
     */
	public static function ConvertResultsToKeyValue($results,?string $keyField = NULL,bool $convertToDataSet = FALSE,?int $case = NULL) {
	    if(!is_iterable($results)) { return $results; }
	    $key = strlen($keyField) ? $keyField : 'id';
	    if(is_object($results)) {
            $tempResults = new DataSet();
            foreach($results as $v) {
                if(is_object($v)) {
                    $tempResults->set(change_case($v->getProperty($key),$case),$v);
                } else {
                    $tempResults->set(change_case(get_array_value($v,$key,NULL,'isset'),$case),$v);
                }//if(is_object($v))
            }//END foreach
	    } else {
	        $tempResults = [];
            foreach($results as $v) {
                if(is_object($v)) {
                    $tempResults[change_case($v->getProperty($key),$case)] = $v;
                } else {
                    $tempResults[change_case(get_array_value($v,$key,NULL,'isset'),$case)] = $v;
                }//if(is_object($v))
            }//END foreach
            if($convertToDataSet) { $tempResults = new DataSet($tempResults); }
	    }//if(is_object($results))
		return $tempResults;
	}//END public static function ConvertResultsToKeyValue
    /**
     * Convert array to a DataSet of entities or row arrays
     *
     * @param  array       $data The array to be converted
     * @param  string|null $entity_class Name of the entity class
     * @param null|string  $fieldToUseAsKey
     * @param int          $case
     * @return mixed Returns the DataSet or NULL on error
     * @access public
     * @static
     */
	public static function ConvertArrayToDataSet($data = [],$entity_class = NULL,?string $fieldToUseAsKey = NULL,?int $case = CASE_LOWER) {
		if(!is_array($data)) {
		    if($fieldToUseAsKey===NULL) { return $data; }
		    return static::ConvertResultsToKeyValue($data,$fieldToUseAsKey,FALSE,$case);
		}//if(!is_array($data))
		if(!is_string($entity_class) || !strlen($entity_class) || !class_exists($entity_class)) {
		    $result = static::ConvertResultsToKeyValue($data,$fieldToUseAsKey,TRUE,$case);
		} else {
			if(count($data)) {
			    $fElement = reset($data);
                if(is_null($fElement) || is_scalar($fElement)) {
                    $result = new $entity_class($data);
                } else {
					if(is_object($fElement)) {
					    if(isset($fieldToUseAsKey)) {
				        	$result = static::ConvertResultsToKeyValue($data,$fieldToUseAsKey,TRUE,$case);
					    } else {
					        $result = new DataSet($data);
					    }//if(isset($fieldToUseAsKey))
			        } else {
				        $result = new DataSet();
				        foreach($data as $k=>$v) {
				            if(strlen($fieldToUseAsKey)) {
				                $result->set(change_case(get_array_value($v,$fieldToUseAsKey,$k,'isset'),$case),new $entity_class($v));
				            } else {
				                $result->set(change_case($k,$case),new $entity_class($v));
				            }//if(strlen($fieldToUseAsKey))
				        }//END foreach
					}//if(is_object($fElement))
				}//if(is_scalar($fElement))
			} else {
				$result = new DataSet([]);
			}//if(count($data))
		}//if(!is_string($entity_class) || !strlen($entity_class) || !class_exists($entity_class))
		return $result;
	}//END public static function ConvertArrayToDataSet
	/**
	 * Convert results array to a DataSet of entities or row arrays
	 *
	 * @param  array $results The array to be converted
	 * @param  string|null $entity_class Name of the entity class
	 * @return DataSet|null Returns the DataSet or NULL on error
	 * @access public
	 * @static
	 */
	public static function ConvertResultsToDataSet($results = [],$entity_class = NULL) {
		if(!is_array($results)) { return $results; }
		if(array_key_exists('data',$results)) {
			if(!is_array($results['data'])) {
				$result = self::ConvertArrayToDataSet([],$entity_class);
			} else {
				$result = self::ConvertArrayToDataSet($results['data'],$entity_class);
			}//if(!is_array($results['data']))
			if(isset($results['count']) && $results['count']>=0) { $result->setTotalCount($results['count']); }
		} else {
			$result = self::ConvertArrayToDataSet($results,$entity_class);
		}//if(isset($results['data']) && is_array($results['data']))
		return $result;
	}//END public static function ConvertResultsToDataSet
	/**
	 * Prepares and executes the database call twice:
	 * * first to get the total rows count
	 * * second to get data limited to certain rows
	 * (used mainly for generating paged views)
	 *
	 * @param  string $procedure The name of the stored procedure
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $extra_params An array of parameters that may contain:
	 * - 'transaction'= name of transaction in which the query will run
	 * - 'type' = request type: select, count, execute (default 'select')
	 * - 'first_row' = integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * - 'last_row' = integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * - 'sort' = an array of fields to compose ORDER BY clause
	 * - 'filters' = an array of condition to be applied in WHERE clause
	 * - 'out_params' = an array of output params
	 * @param  bool $cache Flag indicating if the cache should be used or not
	 * @param  string $tag Cache key tag
	 * @param  bool $sp_count Flag indicating if the count is done inside the stored procedure
	 * or in the procedure call (default value is FALSE)
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function GetCountAndData($procedure,$params = [],&$extra_params = [],$cache = FALSE,$tag = NULL,$sp_count = FALSE) {
		if($cache && NApp::_CacheDbCall()) {
			$params_salt = $procedure;
			$params_salt .= serialize(!is_array($params) ? [] : $params);
			$params_salt .= serialize(!is_array($extra_params) ? [] : $extra_params);
			$key = AppSession::GetNewUID($params_salt,'sha1',TRUE);
			$ltag = is_string($tag) && strlen($tag) ? $tag : $procedure;
			$result = self::GetCacheData($key,$ltag);
			if($result!==FALSE) {
				if(AppConfig::GetValue('db_debug')) {
					NApp::Dlog('Cache loaded data for procedure: '.$procedure,'GetCountAndData');
				}//if(AppConfig::GetValue('db_debug'))
				return $result;
			}//if($result!==FALSE)
		}//if($cache && NApp::_CacheDbCall())
		$out_params = get_array_value($extra_params,'out_params',[],'is_array');
		$count_select = strtolower(get_array_value($extra_params,'type','','is_string'))=='count-select';
		if($count_select) {
			switch(intval($sp_count)) {
				case 1:
				$extra_params['type'] = 'select';
				$lextra_params = $extra_params;
				$lextra_params['first_row'] = NULL;
				$lextra_params['last_row'] = NULL;
					$params['with_count'] = 1;
				$result = $this->adapter->ExecuteProcedure($procedure,$params,$lextra_params);
				$result = array('count'=>$result[0]['rcount']);
					$params['with_count'] = 0;
					unset($extra_params['out_params']);
					$extra_params['out_params'] = $out_params;
					$result['data'] = $this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
					break;
				case 2:
					$extra_params['type'] = 'select';
					$params['with_count'] = 1;
					$lresult = $this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
					if(is_array($lresult) && count($lresult)) {
						$result = array('count'=>$lresult[0]['rcount'],'data'=>$lresult);
			} else {
						$result = array('count'=>0,'data'=>[]);
					}//if(is_array($lresult) && count($lresult))
					break;
				default:
				$extra_params['type'] = 'count';
				$result = change_array_keys_case($this->adapter->ExecuteProcedure($procedure,$params,$extra_params),TRUE,CASE_LOWER);
				$result = array('count'=>$result[0]['count']);
				$extra_params['type'] = 'select';
			unset($extra_params['out_params']);
			$extra_params['out_params'] = $out_params;
			$result['data'] = $this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
					break;
			}//END switch
		} else {
			$result = $this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
		}//if($count_select)
		if($cache && NApp::_CacheDbCall()) {
			self::SetCacheData($key,(is_null($result) ? [] : $result),$ltag,$count_select);
		}//if($cache && NApp::_CacheDbCall())
		return $result;
	}//END public function GetCountAndData
	/**
	 * Prepares and executes the database call twice:
	 * * first to get the total rows count
	 * * second to get data limited to certain rows
	 * (used mainly for generating paged views)
	 *
	 * @param  string $query The query string
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $extra_params An array of parameters that may contain:
	 * - 'transaction'= name of transaction in which the query will run
	 * - 'type' = request type: select, count, execute (default 'select')
	 * - 'first_row' = integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * - 'last_row' = integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * - 'sort' = an array of fields to compose ORDER BY clause
	 * - 'filters' = an array of condition to be applied in WHERE clause
	 * - 'out_params' = an array of output params
	 * @param  bool $cache Flag indicating if the cache should be used or not
	 * @param  string $tag Cache key tag
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function GetQueryCountAndData($query,$params = [],&$extra_params = [],$cache = FALSE,$tag = NULL) {
		if($cache && NApp::_CacheDbCall()) {
			$params_salt = AppSession::GetNewUID($query,'md5',TRUE);
			$params_salt .= serialize(!is_array($params) ? [] : $params);
			$params_salt .= serialize(!is_array($extra_params) ? [] : $extra_params);
			$key = AppSession::GetNewUID($params_salt,'sha1',TRUE);
			$ltag = is_string($tag) && strlen($tag) ? $tag : AppSession::GetNewUID($query,'sha1',TRUE);
			$result = self::GetCacheData($key,$ltag);
			if($result!==FALSE) {
				if(AppConfig::GetValue('db_debug')) {
					NApp::Dlog('Cache loaded data for query: '.$query,'GetCountAndData');
				}//if(AppConfig::GetValue('db_debug'))
				return $result;
			}//if($result!==FALSE)
		}//if($cache && NApp::_CacheDbCall())
		$out_params = get_array_value($extra_params,'out_params',[],'is_array');
		$select_stmt =  get_array_value($extra_params,'select_statement','SELECT * ','is_notempty_string');
		$count_select = strtolower(get_array_value($extra_params,'type','','is_string'))=='count-select';
		if($count_select) {
			$extra_params['type'] = 'count';
			$result = change_array_keys_case($this->adapter->ExecuteQuery('SELECT COUNT(1) AS RCOUNT '.$query,$params,$extra_params),TRUE,CASE_LOWER);
			$result = array('count'=>$result[0]['rcount']);
			$extra_params['type'] = 'select';
			unset($extra_params['out_params']);
			$extra_params['out_params'] = $out_params;
			$result['data'] = $this->adapter->ExecuteQuery($select_stmt.$query,$params,$extra_params);
		} else {
			if(strtolower(substr(trim($query),0,6))=='select') {
				$qry = $query;
			} elseif(strtolower(get_array_value($extra_params,'type','','is_string'))=='count') {
				$qry = 'SELECT COUNT(1) AS RCOUNT '.$query;
			} else {
				$qry = $select_stmt.$query;
			}//if(strtolower(substr(trim($query),0,6))=='select')
			$result = $this->adapter->ExecuteQuery($qry,$params,$extra_params);
		}//if($count_select)
		if($cache && NApp::_CacheDbCall()) {
			self::SetCacheData($key,(is_null($result) ? [] : $result),$ltag,$count_select);
		}//if($cache && NApp::_CacheDbCall())
		return $result;
	}//END public function GetQueryCountAndData
	/**
	 * Prepares and executes the database stored procedure call
	 *
	 * @param  string $procedure The name of the stored procedure
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $extra_params An array of parameters that may contain:
	 * - 'transaction'= name of transaction in which the query will run
	 * - 'type' = request type: select, count, execute (default 'select')
	 * - 'first_row' = integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * - 'last_row' = integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * - 'sort' = an array of fields to compose ORDER BY clause
	 * - 'filters' = an array of condition to be applyed in WHERE clause
	 * - 'out_params' = an array of output params
	 * @param  bool $cache Flag indicating if the cache should be used or not
	 * @param  string $tag Cache key tag
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function GetProcedureData($procedure,$params = [],&$extra_params = [],$cache = FALSE,$tag = NULL) {
		if($cache && NApp::_CacheDbCall()) {
			$params_salt = $procedure;
			$params_salt .= serialize(!is_array($params) ? [] : $params);
			$params_salt .= serialize(!is_array($extra_params) ? [] : $extra_params);
			$key = AppSession::GetNewUID($params_salt,'sha1',TRUE);
			$ltag = is_string($tag) && strlen($tag) ? $tag : $procedure;
			$result = self::GetCacheData($key,$ltag);
			// NApp::Dlog($result,$procedure.':'.$key);
			if($result!==FALSE) {
				if(AppConfig::GetValue('db_debug')) {
					NApp::Dlog('Cache loaded data for procedure: '.$procedure,'GetProcedureData');
				}//if(AppConfig::GetValue('db_debug'))
				return $result;
			}//if($result!==FALSE)
			$result = $this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
			// NApp::Dlog($result,$procedure);
			self::SetCacheData($key,(is_null($result) ? [] : $result),$ltag);
		} else {
			$result = $this->adapter->ExecuteProcedure($procedure,$params,$extra_params);
		}//if($cache && NApp::_CacheDbCall())
		return $result;
	}//END public function GetProcedureData
	/**
     * @param string $connectionName
     * @return null|\Redis
     */
    public static function GetRedisInstance(string $connectionName) {
	    $redis = NULL;
	    if(!strlen($connectionName) || !class_exists('\Redis',FALSE)) { return $redis; }
        global $$connectionName;
        $redisConnection = $$connectionName;
        $rdb_server = get_array_value($redisConnection,'db_server','','is_string');
        $rdb_port = get_array_value($redisConnection,'db_port',0,'is_integer');
        if(strlen($rdb_server) && $rdb_port>0) {
            $rdb_index = get_array_value($redisConnection,'db_index',1,'is_integer');
            $rdb_timeout = get_array_value($redisConnection,'timeout',2,'is_integer');
            $rdb_password = get_array_value($redisConnection,'db_password','','is_string');
            try {
                $redis = new \Redis();
                if(!$redis->connect($rdb_server,$rdb_port,$rdb_timeout)) { throw new AppException('Unable to connect to Redis server!'); }
                if(strlen($rdb_password)) { $redis->auth($rdb_password); }
                if(!$redis->select($rdb_index)) { throw new AppException('Unable to select Redis database[1]!'); }
            } catch(\RedisException $re) {
                NApp::Elog($re->getMessage(),'RedisException');
                $redis = NULL;
            } catch(AppException $xe) {
                NApp::Elog($xe->getMessage(),'NETopes\Core\AppException');
                $redis = NULL;
            } catch(\Exception $e) {
                NApp::Elog($e->getMessage(), 'Exception');
                $redis = NULL;
            }//END try
        }//if(strlen($rdb_server) && $rdb_port>0)
        return $redis;
    }//END public static function GetRedisInstance
	/**
	 * Prepares and executes the database stored procedure call
	 *
	 * @param  string $query The query string
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $extra_params An array of parameters that may contain:
	 * - 'transaction'= name of transaction in which the query will run
	 * - 'type' = request type: select, count, execute (default 'select')
	 * - 'first_row' = integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * - 'last_row' = integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * - 'sort' = an array of fields to compose ORDER BY clause
	 * - 'filters' = an array of condition to be applyed in WHERE clause
	 * - 'out_params' = an array of output params
	 * @param  bool $cache Flag indicating if the cache should be used or not
	 * @param  string $tag Cache key tag
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function GetQueryData($query,$params = [],&$extra_params = [],$cache = FALSE,$tag = NULL) {
		if($cache && NApp::_CacheDbCall()) {
			$params_salt = AppSession::GetNewUID($query,'md5',TRUE);
			$params_salt .= serialize(!is_array($params) ? [] : $params);
			$params_salt .= serialize(!is_array($extra_params) ? [] : $extra_params);
			$key = AppSession::GetNewUID($params_salt,'sha1',TRUE);
			$ltag = is_string($tag) && strlen($tag) ? $tag : AppSession::GetNewUID($query,'sha1',TRUE);
			$result = self::GetCacheData($key,$ltag);
			if($result!==FALSE) {
				if(AppConfig::GetValue('db_debug')) {
					NApp::Dlog('Cache loaded data for query: '.$query,'GetQueryData');
				}//if(AppConfig::GetValue('db_debug'))
				return $result;
			}//if($result!==FALSE)
			$result = $this->adapter->ExecuteQuery($query,$params,$extra_params);
			self::SetCacheData($key,(is_null($result) ? [] : $result),$ltag);
		} else {
			$result = $this->adapter->ExecuteQuery($query,$params,$extra_params);
		}//if($cache && NApp::_CacheDbCall())
		return $result;
	}//END public function GetQueryData
	/**
	 * Set data call cache
	 *
	 * @param  string $key The unique identifier key
	 * @param  string $tag Cache key tag
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 * @static
	 */
	public static function GetCacheData($key,$tag = NULL) {
		// NApp::Dlog($key,'GetCacheData');
		if(!is_string($key) || !strlen($key)) { return FALSE; }
		$lkey = is_string($tag) && strlen($tag) ? $tag.':'.$key : $key;
		$result = FALSE;
		$handled = FALSE;
		if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
				try {
                $redis = static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) { throw new AppException('Invalid Redis instance!'); }
					try {
						$result = $redis->get($lkey);
						// NApp::Dlog($result,'$result[raw]');
						if(is_string($result) && strlen($result)) { $result = @unserialize($result); }
						$handled = TRUE;
					} catch(\Exception $e) {
						$result = FALSE;
					}//END try
				} catch(\RedisException $re) {
					NApp::Elog($re->getMessage(),'RedisException');
				} catch(AppException $xe) {
					NApp::Elog($xe->getMessage(),'NETopes\Core\AppException');
				} catch(\Exception $e) {
					NApp::Elog($e->getMessage(),'Exception');
				}//END try
		}//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
		if(!$handled) {
			$fname = str_replace(':','][',$lkey).'.cache';
			if(!file_exists(NApp::_GetCachePath().'dataadapters/'.$fname)) { return FALSE; }
			try {
				$result = file_get_contents(NApp::_GetCachePath().'dataadapters/'.$fname);
				if(is_string($result) && strlen($result)) { $result = @unserialize($result); }
			} catch(\Exception $e) {
				$result = FALSE;
			}//END try
		}//if(!$handled)
		return (isset($result) && $result!==FALSE && $result!='' ? $result : FALSE);
	}//public static function GetCacheData
	/**
	 * Set data call cache
	 *
	 * @param  string $key The unique identifier key
	 * @param  mixed $data Data to be cached
	 * If $data is NULL, the key will be deleted
	 * @param  string $tag Cache key tag
	 * @param  boolean $count_select If TRUE $data contains an array
	 * like: ['count'=>total_records_no,'data'=>records]
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 * @static
	 */
	public static function SetCacheData($key,$data = NULL,$tag = NULL,$count_select = FALSE) {
		// NApp::Dlog($key,'SetCacheData');
		if(!is_string($key) || !strlen($key)) { return FALSE; }
		if(is_string($tag) && strlen($tag)) {
			$lkey = $tag.':'.$key;
		} else {
			$lkey = $key;
		}//if(is_string($tag) && strlen($tag))
		$handled = FALSE;
		if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
				try {
                $redis = static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) { throw new AppException('Invalid Redis instance!'); }
					try {
						if(is_null($data)) {
							$result = $redis->delete($tag.':'.$key);
						} else {
							// NApp::Dlog(serialize($data),'Cache data');
							$result = $redis->set($lkey,@serialize($data));
							// NApp::Dlog($key,'Cache set');
						}//if(is_null($data))
						if(AppConfig::GetValue('db_debug')) {
							NApp::Dlog('Cache data stored to REDIS for: '.$lkey,'SetCacheData');
						}//if(AppConfig::GetValue('db_debug'))
						$handled = TRUE;
					} catch(\Exception $e) {
						$result = NULL;
					}//END try
				} catch(\RedisException $re) {
					NApp::Elog($re->getMessage(),'RedisException');
				} catch(AppException $xe) {
					NApp::Elog($xe->getMessage(),'NETopes\Core\AppException');
				} catch(\Exception $e) {
					NApp::Elog($e->getMessage(),'Exception');
				}//END try
		}//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
		if(!$handled) {
			$fname = str_replace(':','][',$lkey).'.cache';
			if(is_null($data)) {
				if(file_exists(NApp::_GetCachePath().'dataadapters/'.$fname)) {
					@unlink(NApp::_GetCachePath().'dataadapters/'.$fname);
				}//if(file_exists(NApp::_GetCachePath().'dataadapters/'.$fname))
				$result = TRUE;
			} else {
				if(!file_exists(NApp::_GetCachePath().'dataadapters')) {
					@mkdir(NApp::_GetCachePath().'dataadapters',755);
				}//if(!file_exists(NApp::_GetCachePath().'dataadapters'))
				$result = file_put_contents(NApp::_GetCachePath().'dataadapters/'.$fname,serialize($data));
			}//if(is_null($data))
			if(AppConfig::GetValue('db_debug')) {
				NApp::Dlog('Cache data stored to FILES for: '.$lkey,'SetCacheData');
			}//if(AppConfig::GetValue('db_debug'))
		}//if(!$handled)
		return ($result!==0 && $result!==FALSE);
	}//public static function SetCacheData
	/**
	 * Delete data calls cache
	 *
	 * @param  string $key The unique identifier key
	 * @param  string $tag Cache key tag
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 * @static
	 */
	public static function UnsetCacheData($tag,$key = NULL) {
		// NApp::Dlog(['tag'=>$tag,'key'=>$key],'UnsetCacheData');
		if(!is_string($tag) || !strlen($tag)) { return FALSE; }
		$handled = FALSE;
		if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
				try {
                $redis = static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) { throw new AppException('Invalid Redis instance!'); }
					try {
						if(strlen($key)) {
							$result = $redis->delete($tag.':'.$key);
						} else {
							// NApp::Dlog($redis->keys($tag.':*'),'tags');
							$result = $redis->delete($redis->keys($tag.':*'));
						}//if(strlen($key))
						$handled = TRUE;
						if(AppConfig::GetValue('db_debug')) {
							NApp::Dlog('Cache data deleted ['.print_r($result,1).'] for: '.$tag.(strlen($key) ? ':'.$key : ''),'UnsetCacheData');
						}//if(AppConfig::GetValue('db_debug'))
					} catch(\Exception $e) {
						$result = NULL;
					}//END try
				} catch(\RedisException $re) {
					NApp::Elog($re->getMessage(),'RedisException');
				} catch(AppException $xe) {
					NApp::Elog($xe->getMessage(),'NETopes\Core\AppException');
				} catch(\Exception $e) {
					NApp::Elog($e->getMessage(),'Exception');
				}//END try
		}//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
		if(!$handled) {
			if(file_exists(NApp::_GetCachePath().'dataadapters/')) {
				$filter = $key.']['.(strlen($tag) ? $tag : '*').'.cache';
				array_map('unlink',glob(NApp::_GetCachePath().'dataadapters/'.$filter));
			}//if(file_exists(NApp::_GetCachePath().'dataadapters/'.$fname))
			if(AppConfig::GetValue('db_debug')) {
				NApp::Dlog('Cache data deleted for: '.$tag.(strlen($key) ? ':'.$key : ''),'UnsetCacheData');
			}//if(AppConfig::GetValue('db_debug'))
			$result = TRUE;
		}//if(!$handled)
		return ($result!==0 && $result!==FALSE);
	}//END public static function UnsetCacheData
	/**
	 * Clear all cached data
	 *
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 * @static
	 */
	public static function ClearAllCache() {
		$result = NULL;
		if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
				try {
                $redis = static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) { throw new AppException('Invalid Redis instance!'); }
					try {
						$result = $redis->flushDb();
					} catch(\Exception $e) {
						$result = FALSE;
					}//END try
				} catch(\RedisException $re) {
					NApp::Elog($re->getMessage(),'RedisException');
					$result = FALSE;
				} catch(AppException $xe) {
					NApp::Elog($xe->getMessage(),'NETopes\Core\AppException');
					$result = FALSE;
				} catch(\Exception $e) {
					NApp::Elog($e->getMessage(),'Exception');
					$result = FALSE;
				}//END try
		}//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
		try {
			if(file_exists(NApp::_GetCachePath().'dataadapters')) {
				array_map('unlink', glob(NApp::_GetCachePath().'dataadapters/*.cache'));
				if(is_null($result)) { $result = TRUE; }
			}//if(file_exists(NApp::_GetCachePath().'dataadapters'))
		} catch(\Exception $e) {
			NApp::Elog($e->getMessage(),'Exception');
			if($result) { $result = FALSE; }
		}//END try
		return $result;
	}//public static function ClearAllCache
}//END class DataSource