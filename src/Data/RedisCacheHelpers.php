<?php
/**
 * Data source Redis cache trait file
 * This contains an methods for Redis data cache operations.
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use Exception;
use NApp;
use NETopes\Core\App\AppHelpers;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use Redis;
use RedisException;

/**
 * Class TDataSourceRedisCache
 *
 * @package NETopes\Core\Data
 */
class RedisCacheHelpers {
    /**
     * @var bool
     */
    public static $useFilesFallback=TRUE;
    /**
     * @var string
     */
    public static $fallbackDir='dataadapters';

    /**
     * @param string $connectionName
     * @return null|\Redis
     */
    public static function GetRedisInstance(string $connectionName) {
        $redis=NULL;
        if(!strlen($connectionName) || !class_exists('\Redis',FALSE)) {
            return $redis;
        }
        global $$connectionName;
        $redisConnection=$$connectionName;
        $rdb_server=get_array_value($redisConnection,'db_server','','is_string');
        $rdb_port=get_array_value($redisConnection,'db_port',0,'is_integer');
        if(strlen($rdb_server) && $rdb_port>0) {
            $rdb_index=get_array_value($redisConnection,'db_index',1,'is_integer');
            $rdb_timeout=get_array_value($redisConnection,'timeout',2,'is_integer');
            $rdb_password=get_array_value($redisConnection,'db_password','','is_string');
            try {
                $redis=new Redis();
                if(!$redis->connect($rdb_server,$rdb_port,$rdb_timeout)) {
                    throw new AppException('Unable to connect to Redis server!');
                }
                if(strlen($rdb_password)) {
                    $redis->auth($rdb_password);
                }
                if(!$redis->select($rdb_index)) {
                    throw new AppException('Unable to select Redis database[1]!');
                }
            } catch(RedisException $re) {
                NApp::Elog($re);
            } catch(AppException $xe) {
                NApp::Elog($xe);
                $redis=NULL;
            } catch(Exception $e) {
                NApp::Elog($e);
                $redis=NULL;
            }//END try
        }//if(strlen($rdb_server) && $rdb_port>0)
        return $redis;
    }//END public static function GetRedisInstance

    /**
     * Set data call cache
     *
     * @param string $key The unique identifier key
     * @param string $tag Cache key tag
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function GetCacheData($key,$tag=NULL) {
        // NApp::Dlog($key,'GetCacheData');
        if(!is_string($key) || !strlen($key)) {
            return FALSE;
        }
        $lkey=is_string($tag) && strlen($tag) ? $tag.':'.$key : $key;
        $result=FALSE;
        $handled=FALSE;
        if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
            try {
                $redis=static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) {
                    throw new AppException('Invalid Redis instance!');
                }
                try {
                    $result=$redis->get($lkey);
                    // NApp::Dlog($result,'$result[raw]');
                    if(is_string($result) && strlen($result)) {
                        $result=@unserialize($result);
                    }
                    $handled=TRUE;
                } catch(Exception $e) {
                    $result=FALSE;
                }//END try
            } catch(RedisException $re) {
                NApp::Elog($re);
            } catch(AppException $xe) {
                NApp::Elog($xe);
            } catch(Exception $e) {
                NApp::Elog($e);
            }//END try
        }//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
        if(!$handled && static::$useFilesFallback) {
            $fName=str_replace(':','][',$lkey).'.cache';
            if(!file_exists(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$fName)) {
                return FALSE;
            }
            try {
                $result=file_get_contents(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$fName);
                if(is_string($result) && strlen($result)) {
                    $result=@unserialize($result);
                }
            } catch(Exception $e) {
                $result=FALSE;
            }//END try
        }//if(!$handled && static::$useFilesFallback)
        return (isset($result) && $result!==FALSE && $result!='' ? $result : FALSE);
    }//public static function GetCacheData

    /**
     * Set data call cache
     *
     * @param string  $key         The unique identifier key
     * @param mixed   $data        Data to be cached
     *                             If $data is NULL, the key will be deleted
     * @param string  $tag         Cache key tag
     * @param boolean $countSelect If TRUE $data contains an array
     *                             like: ['count'=>total_records_no,'data'=>records]
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function SetCacheData($key,$data=NULL,$tag=NULL,$countSelect=FALSE) {
        // NApp::Dlog($key,'SetCacheData');
        if(!is_string($key) || !strlen($key)) {
            return FALSE;
        }
        if(is_string($tag) && strlen($tag)) {
            $lkey=$tag.':'.$key;
        } else {
            $lkey=$key;
        }//if(is_string($tag) && strlen($tag))
        $handled=FALSE;
        $result=FALSE;
        if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
            try {
                $redis=static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) {
                    throw new AppException('Invalid Redis instance!');
                }
                try {
                    if(is_null($data)) {
                        $result=$redis->delete($tag.':'.$key);
                    } else {
                        // NApp::Dlog(serialize($data),'Cache data');
                        $result=$redis->set($lkey,@serialize($data));
                        // NApp::Dlog($key,'Cache set');
                    }//if(is_null($data))
                    NApp::DbDebug('Cache data stored to REDIS for: '.$lkey,'SetCacheData');
                    $handled=TRUE;
                } catch(Exception $e) {
                    $result=NULL;
                }//END try
            } catch(RedisException $re) {
                NApp::Elog($re);
            } catch(AppException $xe) {
                NApp::Elog($xe);
            } catch(Exception $e) {
                NApp::Elog($e);
            }//END try
        }//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
        if(!$handled && static::$useFilesFallback) {
            $fName=str_replace(':','][',$lkey).'.cache';
            if(is_null($data)) {
                if(file_exists(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$fName)) {
                    @unlink(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$fName);
                }//if(file_exists(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$fName))
                $result=TRUE;
            } else {
                if(!file_exists(AppHelpers::GetCachePath().static::$fallbackDir)) {
                    @mkdir(AppHelpers::GetCachePath().static::$fallbackDir,0755);
                }//if(!file_exists(AppHelpers::GetCachePath().static::$fallbackDir))
                $result=file_put_contents(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$fName,serialize($data));
            }//if(is_null($data))
            NApp::DbDebug('Cache data stored to FILES for: '.$lkey,'SetCacheData');
        }//if(!$handled && static::$useFilesFallback)
        return ($result!==0 && $result!==FALSE);
    }//public static function SetCacheData

    /**
     * Delete data calls cache
     *
     * @param string $key The unique identifier key
     * @param string $tag Cache key tag
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function UnsetCacheData($tag,$key=NULL) {
        // NApp::Dlog(['tag'=>$tag,'key'=>$key],'UnsetCacheData');
        if(!is_string($tag) || !strlen($tag)) {
            return FALSE;
        }
        $handled=FALSE;
        $result=FALSE;
        if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
            try {
                $redis=static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) {
                    throw new AppException('Invalid Redis instance!');
                }
                try {
                    if(strlen($key)) {
                        $result=$redis->delete($tag.':'.$key);
                    } else {
                        // NApp::Dlog($redis->keys($tag.':*'),'tags');
                        $result=$redis->delete($redis->keys($tag.':*'));
                    }//if(strlen($key))
                    $handled=TRUE;
                    NApp::DbDebug('Cache data deleted ['.print_r($result,1).'] for: '.$tag.(strlen($key) ? ':'.$key : ''),'UnsetCacheData');
                } catch(Exception $e) {
                    $result=NULL;
                }//END try
            } catch(RedisException $re) {
                NApp::Elog($re);
            } catch(AppException $xe) {
                NApp::Elog($xe);
            } catch(Exception $e) {
                NApp::Elog($e);
            }//END try
        }//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
        if(!$handled && static::$useFilesFallback) {
            if(file_exists(AppHelpers::GetCachePath().static::$fallbackDir)) {
                $filter=$key.']['.(strlen($tag) ? $tag : '*').'.cache';
                array_map('unlink',glob(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.$filter));
            }//if(file_exists(AppHelpers::GetCachePath().static::$fallbackDir))
            NApp::DbDebug('Cache data deleted for: '.$tag.(strlen($key) ? ':'.$key : ''),'UnsetCacheData');
            $result=TRUE;
        }//if(!$handled && static::$useFilesFallback)
        return ($result!==0 && $result!==FALSE);
    }//END public static function UnsetCacheData

    /**
     * Clear all cached data
     *
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function ClearAllCache() {
        $result=NULL;
        if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE)) {
            try {
                $redis=static::GetRedisInstance('REDIS_CACHE_DB_CONNECTION');
                if(!is_object($redis)) {
                    throw new AppException('Invalid Redis instance!');
                }
                try {
                    $result=$redis->flushDb();
                } catch(Exception $e) {
                    $result=FALSE;
                }//END try
            } catch(RedisException $re) {
                NApp::Elog($re);
                $result=FALSE;
            } catch(AppException $xe) {
                NApp::Elog($xe);
                $result=FALSE;
            } catch(Exception $e) {
                NApp::Elog($e);
                $result=FALSE;
            }//END try
        }//if(AppConfig::GetValue('app_cache_redis') && class_exists('\Redis',FALSE))
        try {
            if(file_exists(AppHelpers::GetCachePath().static::$fallbackDir)) {
                array_map('unlink',glob(AppHelpers::GetCachePath().static::$fallbackDir.DIRECTORY_SEPARATOR.'*.cache'));
                if(is_null($result)) {
                    $result=TRUE;
                }
            }//if(file_exists(AppHelpers::GetCachePath().static::$fallbackDir))
        } catch(Exception $e) {
            NApp::Elog($e);
            if($result) {
                $result=FALSE;
            }
        }//END try
        return $result;
    }//public static function ClearAllCache
}//END class RedisCacheHelpers