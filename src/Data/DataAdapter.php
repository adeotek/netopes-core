<?php
/**
 * DbAdapter base class file
 * All specific database adapters classes extends this base class.
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NETopes\Core\AppConfig;
use NETopes\Core\AppSession;
use NETopes\Core\AppException;
use NApp;
/**
 * DbAdapter is the base abstract class for all database adapters
 * All database adapters must extend this class.
 * @package  NETopes\Database
 */
abstract class DataAdapter {
    /**
     * @var    array Databases instances array
     */
	protected static $_dbAdapterInstances = [];
	/**
	 * @var    bool Debug flag for database calls TRUE - with debug and FALSE - no debug
     */
	public $debug = FALSE;
	/**
	 * @var    bool If set to TRUE the debug data will be sent to a log file
	 */
	public $debug2file = FALSE;
	/**
	 * @var    object Database connection object
	 */
	protected $connection = NULL;
	/**
	 * @var    string Database name
	 */
	protected $dbName = NULL;
	/**
	 * @var    string Database type
	 */
	protected $dbType = NULL;
	/**
	 * @var    array Database transactions array
	 */
	protected $transactions = [];
	/**
	 * @var    bool description
	 */
	protected $usePdo = FALSE;
	/**
	 * @var    int Flag for setting the result keys case
	 */
	public $resultsKeysCase = CASE_LOWER;
	/**
	 * Class initialization abstract method
	 * (called automatically on class constructor)
	 * @param  array $connection Database connection array
	 * @return void
	 */
	abstract protected function Init($connection);
    /**
     * Database class constructor
     * @param  array $connection Database connection array
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     * @return void
     */
	protected function __construct($connection) {
		$this->debug = AppConfig::GetValue('db_debug');
		$this->debug2file = AppConfig::GetValue('db_debug2file');
		if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name']) { throw new AppException('Incorect database connection',E_ERROR,1); }
		$this->dbName = $connection['db_name'];
		$this->dbType = $connection['db_type'];
		$this->resultsKeysCase = get_array_value($connection,'resultsKeysCase',$this->resultsKeysCase,'is_integer');
		$this->Init($connection);
	}//END protected function __construct
	/**
	 * Gets the singleton instance for database class
	 * @param  string $type Database type (Firebird/MySql/SqLite/SqlSrv/MongoDb/Oracle)
	 * @param  array $connection Database connection array
	 * @param bool    $existing_only
	 * @return object Returns the singleton database instance
	 */
	public static function GetInstance($type,$connection,$existing_only = FALSE) {
		if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_name',$connection) || !$connection['db_name'] || !$type) { return NULL; }
		$dbclass = get_called_class();
		$dbiname = AppSession::GetNewUID($type.'|'.serialize($connection),'sha1',TRUE);
		if(!array_key_exists($dbiname,self::$_dbAdapterInstances) || is_null(self::$_dbAdapterInstances[$dbiname]) || !is_resource(self::$_dbAdapterInstances[$dbiname]->connection)) {
			if($existing_only) { return NULL; }
			self::$_dbAdapterInstances[$dbiname] = new $dbclass($connection);
		}//if(!array_key_exists($dbiname,self::$_dbAdapterInstances) || is_null(self::$_dbAdapterInstances[$dbiname]) || !is_resource(self::$_dbAdapterInstances[$dbiname]->connection))
		return self::$_dbAdapterInstances[$dbiname];
	}//END public static function GetInstance
	/**
	 * Gets the database name
	 * (name of the database from the connection array)
	 * @return string Returns name of the database
	 */
	public function GetName(): string {
		return $this->dbName;
	}//END public function GetName
	/**
	 * Gets the database connection object
	 * @return object Returns the current connection to the database
	 */
	public function GetConnection() {
		return $this->connection;
	}//END public function GetConnection
	protected function DbDebug($query,$label = NULL,$time = NULL,$forced = FALSE) {
		if(!$this->debug && !$forced) { return; }
		$llabel = strlen($label) ? $label : 'DbDebug';
		$lquery = $query.($time ? '   =>   Duration: '.number_format((microtime(TRUE)-$time),3,'.','').' sec' : '');
		NApp::Dlog($lquery,$llabel);
		if($this->debug2file) { NApp::Write2LogFile($llabel.': '.$lquery,'debug'); }
	}//END protected function DbDebug
}//END abstract class BaseAdapter