<?php
/**
 * DbAdapter base class file
 *
 * All specific database adapters classes extends this base class.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
namespace NETopes\Core\Classes\Data;
use NApp;
use PAF\AppException;
/**
 * DbAdapter is the base abstract class for all database adapters
 *
 * All database adapters must extend this class.
 *
 * @package  NETopes\Database
 * @access   public
 * @abstract
 */
abstract class DataAdapter {
	/**
	 * @var    array Databases instances array
	 * @access protected
	 * @static
	 */
	protected static $DatabaseAdapterInstances = [];
	/**
	 * @var    bool Debug flag for database calls
	 * TRUE - with debug and FALSE - no debug
	 * @access public
	 */
	public $debug = FALSE;
	/**
	 * @var    bool If set to TRUE the debug data
	 * will be sent to a log file
	 * @access public
	 */
	public $debug2file = FALSE;
	/**
	 * @var    object Database connection object
	 * @access protected
	 */
	protected $connection = NULL;
	/**
	 * @var    string Database name
	 * @access protected
	 */
	protected $dbname = NULL;
	/**
	 * @var    string Database type
	 * @access protected
	 */
	protected $dbtype = NULL;
	/**
	 * @var    array Database transactions array
	 * @access protected
	 */
	protected $transactions = [];
	/**
	 * @var    type description
	 * @access protected
	 */
	protected $use_pdo = FALSE;
	/**
	 * @var    int Flag for setting the result keys case
	 * @access public
	 */
	public $results_keys_case = CASE_LOWER;
	/**
	 * Class initialization abstract method
	 * (called automatically on class constructor)
	 *
	 * @param  array $connection Database connection array
	 * @return void
	 * @access protected
	 * @abstract
	 */
	abstract protected function Init($connection);
	/**
	 * Database class constructor
	 *
	 * @param  array $connection Database connection array
	 * @throws \PAF\AppException
	 * @return void
	 * @access public
	 */
	protected function __construct($connection) {
		$this->debug = NApp::$db_debug;
		$this->debug2file = NApp::$db_debug2file;
		if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name']) { throw new AppException('Incorect database connection',E_ERROR,1); }
		$this->dbname = $connection['db_name'];
		$this->dbtype = $connection['db_type'];
		$this->results_keys_case = get_array_param($connection,'results_keys_case',$this->results_keys_case,'numeric');
		$this->Init($connection);
	}//END protected function __construct
	/**
	 * Gets the singleton instance for database class
	 *
	 * @param  string $type Database type (Firebird/MySql/SqLite/SqlSrv/MongoDb/Oracle)
	 * @param  array $connection Database connection array
	 * @param bool    $existing_only
	 * @return object Returns the singleton database instance
	 * @access public
	 */
	public static function GetInstance($type,$connection,$existing_only = FALSE) {
		if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_name',$connection) || !$connection['db_name'] || !$type) { return NULL; }
		$dbclass = get_called_class();
		$dbiname = NApp::GetNewUID($type.'|'.serialize($connection),'sha1',TRUE);
		if(!array_key_exists($dbiname,self::$DatabaseAdapterInstances) || is_null(self::$DatabaseAdapterInstances[$dbiname]) || !is_resource(self::$DatabaseAdapterInstances[$dbiname]->connection)) {
			if($existing_only) { return NULL; }
			self::$DatabaseAdapterInstances[$dbiname] = new $dbclass($connection);
		}//if(!array_key_exists($dbiname,self::$DatabaseAdapterInstances) || is_null(self::$DatabaseAdapterInstances[$dbiname]) || !is_resource(self::$DatabaseAdapterInstances[$dbiname]->connection))
		return self::$DatabaseAdapterInstances[$dbiname];
	}//END public static function GetInstance
	/**
	 * Gets the database name
	 * (name of the database from the connection array)
	 *
	 * @return string Returns name of the database
	 * @access public
	 */
	public function GetName() {
		return $this->dbname;
	}//END public function GetName
	/**
	 * Gets the database connection object
	 *
	 * @return object Returns the current connection to the database
	 * @access public
	 */
	public function GetConnection() {
		return $this->connection;
	}//END public function GetConnection

	protected function DbDebug($query,$label = NULL,$time = NULL,$forced = FALSE) {
		if(!$this->debug && !$forced) { return; }
		$llabel = strlen($label) ? $label : 'DbDebug';
		$lquery = $query.($time ? '   =>   Duration: '.number_format((microtime(TRUE)-$time),3,'.','').' sec' : '');
		NApp::_Dlog($lquery,$llabel);
		if($this->debug2file) { NApp::_Write2LogFile($llabel.': '.$lquery,'debug'); }
	}//END protected function DbDebug
}//END abstract class BaseAdapter
?>