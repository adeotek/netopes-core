<?php
/**
 * FirebirdSql database adapter class file
 *
 * This file contains the adapter class for FirebirdSQL database.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use NApp;
use PAF\AppConfig;
use PAF\AppException;

/**
	 * FirebirdSqlDbAdapter is the adapter for the FirebirdSQL database
	 *
	 * This class contains all methods for interacting with FirebirdSQL database.
	 *
	 * @package  NETopes\Database
	 * @access   public
	 */
class DoctrineAdapter extends DataAdapter {
	/**
	 * @var EntityManager
	 */
	public $em;
	/**
	 * @var object
	 */
	public $platform;
	/**
	 * Doctrine bootstrap
	 *
	 * @param  string $base_path
	 * @param  array  $connection Database connection array
	 * @param  object $platform
	 * @return EntityManager
	 * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function GetEntityManager($base_path,$connection,&$platform) {
		$entities_path = rtrim($base_path,'\/').DIRECTORY_SEPARATOR.trim(AppConfig::doctrine_entities_path(),'\/');
		$proxy_dir = rtrim($base_path,'\/').DIRECTORY_SEPARATOR.trim(AppConfig::doctrine_proxies_path(),'\/');
		$dbtype = array_key_exists('db_type',$connection) && is_string($connection['db_type']) ? $connection['db_type'] : '';
		$dbdriver = array_key_exists('doctrine_driver',$connection) && is_string($connection['doctrine_driver']) ? $connection['doctrine_driver'] : '';
		if(!is_array($connection) || !count($connection) || !strlen($dbtype) || !strlen($dbdriver)) { return NULL; }
		try {
		    $persistentCache = FALSE;
			$platform = NULL;
			$cacheDriver = NULL;
			$cacheDriverName = AppConfig::doctrine_cache_driver();
			if(strlen($cacheDriverName) && class_exists('\Redis',FALSE)) {
			    $cacheDriverClass = '\Doctrine\Common\Cache\\'.$cacheDriverName;
			    $cacheDriver = new $cacheDriverClass();
			    if($cacheDriverName=='RedisCache') {
			        $redis = DataSource::GetRedisInstance('REDIS_DOCTRINE_CACHE_CONNECTION');
			        if($redis) {
                        $cacheDriver->setRedis($redis);
                        $persistentCache = TRUE;
			        } else {
			            $cacheDriver = NULL;
			        }//if($redis->connect('redis_host', 6379))
			    }//if($cacheDriverName=='Redis')
			}//if(strlen($cacheDriverName) && class_exists('\Redis',FALSE))
			if($cacheDriver==NULL) { $cacheDriver = new \Doctrine\Common\Cache\ArrayCache; }
			// Create a simple "default" Doctrine ORM configuration for Annotations
			$config = Setup::createAnnotationMetadataConfiguration([$entities_path],AppConfig::doctrine_develop_mode(),$proxy_dir,$cacheDriver);
			$anno_reader = new AnnotationReader();
			$anno_driver = new AnnotationDriver($anno_reader,[$entities_path]);
	        $config->setMetadataDriverImpl($anno_driver);
	        $config->setProxyNamespace(AppConfig::doctrine_proxies_namespace());
	        $config->setAutoGenerateProxyClasses(AppConfig::doctrine_develop_mode());
	        if($persistentCache) { $config->setQueryCacheImpl($cacheDriver); }
			if($dbtype=='FirebirdSql') {
				$conn_arr = [
				    'host'=>$connection['db_server'],
				    'dbname'=>$connection['db_name'],
				    'user'=>$connection['db_user'],
				    'password'=>$connection['db_password'],
				];
				if(isset($connection['db_port'])) { $conn_arr['port'] = $connection['db_port']; }
				if(isset($connection['charset'])) { $conn_arr['charset'] = $connection['charset']; }
				if(isset($connection['persistent'])) { $conn_arr['isPersistent'] = $connection['persistent']; }
				// NApp::_Dlog($conn_arr,'$conn_arr');
				$driver = new $dbdriver();
				$conn = new Connection($conn_arr,$driver,$config);
		        $conn->setNestTransactionsWithSavepoints(TRUE);
			} else {
				$conn = [
				    'driver'=>$dbdriver,
				    'host'=>$connection['db_server'],
				    'dbname'=>$connection['db_name'],
				    'user'=>$connection['db_user'],
				    'password'=>$connection['db_password'],
				];
				if(isset($connection['db_port'])) { $conn['port'] = $connection['db_port']; }
				if(isset($connection['charset'])) { $conn['charset'] = $connection['charset']; }
				// NApp::_Dlog($conn,'$conn');
			}//if($dbtype=='FirebirdSql')
			// obtaining the entity manager
			return EntityManager::create($conn,$config);
		} catch(\Doctrine\DBAL\DBALException $e1) {
			throw new AppException($e1->getMessage(),$e1->getCode(),1);
		} catch(\Doctrine\ORM\ORMException $e2) {
			throw new AppException($e2->getMessage(),$e2->getCode(),1);
		} catch(\Exception $e3) {
			throw new AppException($e3->getMessage(),$e3->getCode(),1);
		}//END try
	}//END public static function GetEntityManager
	/**
	 * Class initialization abstract method
	 * (called automatically on class constructor)
	 *
	 * @param  array $connection Database connection array
	 * @return void
	 * @access protected
	 * @throws \PAF\AppException
	 */
	protected function Init($connection) {
		$this->em = self::GetEntityManager(NApp::app_path(),$connection,$this->platform);
	}//END protected function Init
}//END class DoctrineAdapter extends DataSource