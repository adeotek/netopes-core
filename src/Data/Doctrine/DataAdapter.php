<?php
/**
 * DataAdapter database adapter class file
 * This file contains the adapter class for Doctrine ORM.
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data\Doctrine;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use Exception;
use NApp;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Data\RedisCacheHelpers;

/**
 * DataAdapter class
 */
class DataAdapter extends \NETopes\Core\Data\DataAdapter {
    /**
     * @var EntityManager
     */
    public $em;
    /**
     * @var object
     */
    public $platform;

    /**
     * Class initialization abstract method
     * (called automatically on class constructor)
     *
     * @param array $connection Database connection array
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected function Init($connection) {
        $this->em=self::GetEntityManager(NApp::$appPath,$connection,$this->platform);
    }//END public static function GetEntityManager

    /**
     * Doctrine bootstrap
     *
     * @param string $base_path
     * @param array  $connection Database connection array
     * @param object $platform
     * @return EntityManager
     * @throws \NETopes\Core\AppException
     */
    public static function GetEntityManager($base_path,$connection,&$platform) {
        $entities_path=rtrim($base_path,'\/').DIRECTORY_SEPARATOR.trim(AppConfig::GetValue('doctrine_entities_path'),'\/');
        $proxy_dir=rtrim($base_path,'\/').DIRECTORY_SEPARATOR.trim(AppConfig::GetValue('doctrine_proxies_path'),'\/');
        $dbtype=array_key_exists('db_type',$connection) && is_string($connection['db_type']) ? $connection['db_type'] : '';
        $dbdriver=array_key_exists('doctrine_driver',$connection) && is_string($connection['doctrine_driver']) ? $connection['doctrine_driver'] : '';
        if(!is_array($connection) || !count($connection) || !strlen($dbtype) || !strlen($dbdriver)) {
            return NULL;
        }
        try {
            $persistentCache=FALSE;
            $platform=NULL;
            $cacheDriver=NULL;
            $cacheDriverName=AppConfig::GetValue('doctrine_cache_driver');
            if(strlen($cacheDriverName) && class_exists('\Redis',FALSE)) {
                $cacheDriverClass='\Doctrine\Common\Cache\\'.$cacheDriverName;
                $cacheDriver=new $cacheDriverClass();
                if($cacheDriverName=='RedisCache') {
                    $redis=RedisCacheHelpers::GetRedisInstance('REDIS_DOCTRINE_CACHE_CONNECTION');
                    if($redis) {
                        /** @var \Doctrine\Common\Cache\RedisCache $cacheDriver */
                        $cacheDriver->setNamespace('DOCTRINE_DATA_PROXIES');
                        $cacheDriver->setRedis($redis);
                        $persistentCache=TRUE;
                    } else {
                        $cacheDriver=NULL;
                    }//if($redis->connect('redis_host', 6379))
                }//if($cacheDriverName=='Redis')
            }//if(strlen($cacheDriverName) && class_exists('\Redis',FALSE))
            if($cacheDriver==NULL) {
                $cacheDriver=new ArrayCache;
            }
            // Create a simple "default" Doctrine ORM configuration for Annotations
            $config=Setup::createAnnotationMetadataConfiguration([$entities_path],AppConfig::GetValue('doctrine_develop_mode'),$proxy_dir,$cacheDriver);
            $annotationReader=new AnnotationReader();
            $annotationDriver=new AnnotationDriver($annotationReader,[$entities_path]);
            $config->setMetadataDriverImpl($annotationDriver);
            $config->setProxyNamespace(AppConfig::GetValue('doctrine_proxies_namespace'));
            $config->setAutoGenerateProxyClasses(AppConfig::GetValue('doctrine_develop_mode'));
            if($persistentCache) {
                $config->setQueryCacheImpl($cacheDriver);
            }
            if(NApp::GetDbDebugState()) {
                $config->setSQLLogger(new Logger());
            }
            if($dbtype=='FirebirdSql') {
                $conn_arr=[
                    'host'=>$connection['db_server'],
                    'dbname'=>$connection['db_name'],
                    'user'=>$connection['db_user'],
                    'password'=>$connection['db_password'],
                ];
                if(isset($connection['db_port'])) {
                    $conn_arr['port']=$connection['db_port'];
                }
                if(isset($connection['charset'])) {
                    $conn_arr['charset']=$connection['charset'];
                }
                if(isset($connection['persistent'])) {
                    $conn_arr['isPersistent']=$connection['persistent'];
                }
                // NApp::Dlog($conn_arr,'$conn_arr');
                $driver=new $dbdriver();
                $conn=new Connection($conn_arr,$driver,$config);
                $conn->setNestTransactionsWithSavepoints(TRUE);
            } else {
                $conn=[
                    'driver'=>$dbdriver,
                    'host'=>$connection['db_server'],
                    'dbname'=>$connection['db_name'],
                    'user'=>$connection['db_user'],
                    'password'=>$connection['db_password'],
                ];
                if(isset($connection['db_port'])) {
                    $conn['port']=$connection['db_port'];
                }
                if(isset($connection['charset'])) {
                    $conn['charset']=$connection['charset'];
                }
                // NApp::Dlog($conn,'$conn');
            }//if($dbtype=='FirebirdSql')
            // obtaining the entity manager
            return EntityManager::create($conn,$config);
        } catch(DBALException $e1) {
            throw new AppException($e1->getMessage(),$e1->getCode(),1);
        } catch(ORMException $e2) {
            throw new AppException($e2->getMessage(),$e2->getCode(),1);
        } catch(Exception $e3) {
            throw new AppException($e3->getMessage(),$e3->getCode(),1);
        }//END try
    }//END protected function Init
}//END class DataAdapter extends \NETopes\Core\Data\DataAdapter