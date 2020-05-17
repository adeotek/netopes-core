<?php
/**
 * DbAdapter base class file
 * All specific database adapters classes extends this base class.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NApp;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;

/**
 * DbAdapter is the base abstract class for all database adapters
 * All database adapters must extend this class.
 *
 * @package  NETopes\Database
 */
abstract class DataAdapter {
    /**
     * @var    array Databases instances array
     */
    protected static $_dbAdapterInstances=[];
    /**
     * @var    resource Database connection object
     */
    protected $connection=NULL;
    /**
     * @var    string Database name
     */
    protected $dbName=NULL;
    /**
     * @var    string Database type
     */
    protected $dbType=NULL;
    /**
     * @var    array Database transactions array
     */
    protected $transactions=[];
    /**
     * @var    bool description
     */
    protected $usePdo=FALSE;
    /**
     * @var    int Flag for setting the result keys case
     */
    public $resultsKeysCase=CASE_LOWER;

    /**
     * Class initialization abstract method
     * (called automatically on class constructor)
     *
     * @param array $connection Database connection array
     * @return void
     */
    abstract protected function Init($connection);

    /**
     * Database class constructor
     *
     * @param array $connection Database connection array
     * @return void
     * @throws \Exception
     * @throws \NETopes\Core\AppException
     */
    protected function __construct($connection) {
        if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name']) {
            throw new AppException('Incorrect database connection',E_ERROR,1);
        }
        $this->dbName=$connection['db_name'];
        $this->dbType=$connection['db_type'];
        $this->resultsKeysCase=get_array_value($connection,'resultsKeysCase',$this->resultsKeysCase,'is_integer');
        $this->Init($connection);
    }//END protected function __construct

    /**
     * Gets the singleton instance for database class
     *
     * @param string $type       Database type (Firebird/MySql/SqLite/SqlSrv/MongoDb/Oracle)
     * @param array  $connection Database connection array
     * @param bool   $existingOnly
     * @return object Returns the singleton database instance
     */
    public static function GetInstance($type,$connection,$existingOnly=FALSE) {
        if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_name',$connection) || !$connection['db_name'] || !$type) {
            return NULL;
        }
        $dbClass=get_called_class();
        $dbiName=AppSession::GetNewUID($type.'|'.serialize($connection),'sha1',TRUE);
        if(!array_key_exists($dbiName,self::$_dbAdapterInstances) || is_null(self::$_dbAdapterInstances[$dbiName]) || !is_resource(self::$_dbAdapterInstances[$dbiName]->connection)) {
            if($existingOnly) {
                return NULL;
            }
            self::$_dbAdapterInstances[$dbiName]=new $dbClass($connection);
        }//if(!array_key_exists($dbiName,self::$_dbAdapterInstances) || is_null(self::$_dbAdapterInstances[$dbiName]) || !is_resource(self::$_dbAdapterInstances[$dbiName]->connection))
        return self::$_dbAdapterInstances[$dbiName];
    }//END public static function GetInstance

    /**
     * Gets the database name
     * (name of the database from the connection array)
     *
     * @return string Returns name of the database
     */
    public function GetName(): string {
        return $this->dbName;
    }//END public function GetName

    /**
     * Gets the database connection object
     *
     * @return resource Returns the current connection to the database
     */
    public function GetConnection() {
        return $this->connection;
    }//END public function GetConnection

    /**
     * @param string      $query
     * @param string|null $label
     * @param float|null  $time
     * @param array       $debugMode
     */
    protected function DbDebug(string $query,?string $label=NULL,?float $time=NULL,array $debugMode=[]) {
        try {
            $lLabel=strlen($label) ? $label : 'DbDebug';
            $lQuery=$query.($time ? '   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec' : '');
            NApp::DbDebug($lQuery,$lLabel,[],$debugMode);
        } catch(AppException $e) {
            NApp::Elog($e);
        }//END try
    }//END protected function DbDebug
}//END abstract class BaseAdapter