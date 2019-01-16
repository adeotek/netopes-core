<?php
/**
 * DbAdapter base class file
 *
 * All specific database adapters classes extends this base class.
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
use NApp;
/**
 * DbAdapter is the base abstract class for all database adapters
 *
 * All database adapters must extend this class.
 *
 * @package  NETopes\Database
 * @access   public
 * @abstract
 */
abstract class SqlDataAdapter extends DataAdapter {
	/**
	 * Database class constructor
	 *
	 * @param  array $connection Database connection array
	 * @throws \NETopes\Core\AppException
	 * @return void
	 * @access public
	 */
	protected function __construct($connection) {
		$this->debug = AppConfig::GetValue('db_debug');
		$this->debug2file = AppConfig::db_debug2file();
		if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name']) { throw new \NETopes\Core\AppException('Incorect database connection',E_ERROR,1); }
		$this->dbname = $connection['db_name'];
		$this->dbtype = $connection['db_type'];
		$this->results_keys_case = get_array_value($connection,'results_keys_case',$this->results_keys_case,'is_integer');
		if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_mssql')) {
			$this->use_pdo = TRUE;
			$this->connection = $this->SetPdoConnection($connection);
		} else {
			$this->Init($connection);
		}//if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_mssql'))
	}//END protected function __construct
	/**
	 * Sets database connection to new connection
	 *
	 * @param  array $connection Database connection array
	 * @return bool Returns TRUE on success or FALSE on failure
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public function SetConnection($connection) {
		if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name'] || !array_key_exists('db_type',$connection) || !$connection['db_type']) { return FALSE; }
		$this->dbname = $connection['db_name'];
		$this->dbtype = $connection['db_type'];
		if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_firebird')) {
			$this->use_pdo = TRUE;
			$this->connection = $this->SetPdoConnection($connection);
		} else {
			$this->Init($connection);
		}//if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_mssql'))
		return TRUE;
	}//END public function SetConnection
	/**
	 * Close current database connection
	 *
	 * @return bool Returns TRUE on success or FALSE on failure
	 * @access public
	 */
	public function CloseConnection() {
		if($this->use_pdo || !method_exists($this,$this->dbtype.'CloseConnection')) {
			$this->connection = NULL;
			return TRUE;
		}//if($this->use_pdo || !method_exists($this,$this->dbtype.'CloseConnection'))
		$method = $this->dbtype.'CloseConnection';
		return $this::$method();
	}//END public function CloseConnection
	/**
	 * Executes a method of the database connection object
	 *
	 * @param  string $method The method name
	 * @param  mixed $params The method params if any
	 * @return mixed Returns the result of the executed method
	 * @access public
	 */
	public function ExecuteConnectionMethod($method,$params = []) {
		return call_user_func_array(array($this->connection,$method),(is_array($params) ? $params : []));
	}//END public function ExecuteConnectionMethod
	/**
	 * Sets the connection to a new pdo connection
	 *
	 * @param  array $connection Database connection array
	 * @return bool Returns TRUE on success or FALSE on failure
	 * @access protected
	 * @throws \NETopes\Core\AppException
	 */
	protected function SetPdoConnection($connection) {
		if($this->dbtype=='MongoDb') { throw new \NETopes\Core\AppException('MongoDB PDO not implemented!',E_ERROR,1,NULL,NULL,'pdo',0); }
		$conn = NULL;
		try {
			$conn_str = strtolower($connection['db_type']).':dbname='.$connection['db_server'].':'.$connection['db_name'].';charset=UTF8';
			$conn = new \PDO($conn_str,$connection['db_user'],(array_key_exists('db_password',$connection) ? $connection['db_password'] : ''));
			$conn->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
		} catch(\Exception $e) {
			throw new \NETopes\Core\AppException("PDO failed to open connection: ".$connection['db_type'].'>>'.$connection['db_name'].' ('.$e->getMessage().")",E_ERROR,1,NULL,NULL,'pdo',0);
		}//END try
		return $conn;
	}//END protected function SetPdoConnection
	/**
	 * Set database connection global variables
	 *
	 * @param  array $params Key-value array of variables to be set
	 * @return bool  Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function SetGlobalVariables($params = NULL) {
		if($this->use_pdo) { return FALSE; }
		$method = $this->dbtype.str_replace(__CLASS__.'::','',__METHOD__);
		return $this::$method($params);
	}//END public function SetGlobalVariables
	/**
	 * Begins a database transaction
	 *
	 * @param  string $name Transaction name
	 * @param  bool $overwrite Flag for overwriting the transaction
	 * if exists (defaul value FALSE)
	 * @return void
	 * @access public
	 */
	public function BeginTran(&$name = NULL,$log = FALSE,$overwrite = TRUE,$custom_tran_params = NULL) {
		$method = ($this->use_pdo ? 'Pdo' : $this->dbtype).str_replace(__CLASS__.'::','',__METHOD__);
		return $this::$method($name,$log,$overwrite,$custom_tran_params);
	}//END public function BeginTran
	/**
	 * Rolls back a database transaction
	 *
	 * @param  string $name Transaction name
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function RollbackTran($name = NULL,$log = FALSE) {
		$method = ($this->use_pdo ? 'Pdo' : $this->dbtype).str_replace(__CLASS__.'::','',__METHOD__);
		return $this::$method($name,$log);
	}//END public function RollbackTran
	/**
	 * Commits a database transaction
	 *
	 * @param  string $name Transaction name
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function CommitTran($name = NULL,$log = FALSE,$preserve = FALSE) {
		$method = ($this->use_pdo ? 'Pdo' : $this->dbtype).str_replace(__CLASS__.'::','',__METHOD__);
		return $this::$method($name,$log,$preserve);
	}//END public function CommitTran
	/**
	 * Executs a query against the database
	 *
	 * @param  string $query The query string
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $extra_params An array of parameters that may contain:
	 * * 'transaction'= name of transaction in which the query will run
	 * * 'type' = request type: select, count, execute (default 'select')
	 * * 'first_row' = integer to limit number of returned rows
	 * (if used with 'last_row' reprezents the offset of the returned rows)
	 * * 'last_row' = integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * * 'sort' = an array of fields to compose ORDER BY clause
	 * * 'filters' = an array of condition to be applyed in WHERE clause
	 * * 'out_params' = an array of output params
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function ExecuteQuery($query,$params = [],&$extra_params = []) {
		$this->debug = get_array_value($extra_params,'debug',$this->debug,'bool');
		$this->debug2file = get_array_value($extra_params,'debug2file',$this->debug2file,'bool');
		$tran_name = get_array_value($extra_params,'transaction',NULL,'is_notempty_string');
		$type = strtolower(get_array_value($extra_params,'type','','is_notempty_string'));
		$firstrow = get_array_value($extra_params,'first_row',NULL,'is_not0_numeric');
		$lastrow = get_array_value($extra_params,'last_row',NULL,'is_not0_numeric');
		$sort = get_array_value($extra_params,'sort',NULL,'is_notempty_array');
		$filters = get_array_value($extra_params,'filters',NULL,'is_notempty_array');
		$out_params = get_array_value($extra_params,'out_params',[],'is_array');
		$log = get_array_value($extra_params,'log',FALSE,'bool');
		$method = ($this->use_pdo ? 'Pdo' : $this->dbtype).str_replace(__CLASS__.'::','',__METHOD__);
		$results_keys_case = get_array_value($extra_params,'results_keys_case',NULL,'is_integer');
		$custom_tran_params = get_array_value($extra_params,'custom_tran_params',NULL,'isset');
		return $this::$method($query,$params,$out_params,$tran_name,$type,$firstrow,$lastrow,$sort,$filters,$log,$results_keys_case,$custom_tran_params);
	}//END public function ExecuteQuery
	/**
	 * Executs a stored procedure against the database
	 *
	 * @param  string $procedure The name of the stored procedure
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $extra_params An array of parameters that may contain:
	 * * 'transaction'= name of transaction in which the query will run
	 * * 'type' = request type: select, count, execute (default 'select')
	 * * 'first_row' = integer to limit number of returned rows
	 * (if used with 'last_row' reprezents the offset of the returned rows)
	 * * 'last_row' = integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * * 'sort' = an array of fields to compose ORDER BY clause
	 * * 'filters' = an array of condition to be applyed in WHERE clause
	 * * 'out_params' = an array of output params
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function ExecuteProcedure($procedure,$params = [],&$extra_params = []) {
		$this->debug = get_array_value($extra_params,'debug',$this->debug,'bool');
		$this->debug2file = get_array_value($extra_params,'debug2file',$this->debug2file,'bool');
		$tran_name = get_array_value($extra_params,'transaction',NULL,'is_notempty_string');
		$type = strtolower(get_array_value($extra_params,'type','','is_notempty_string'));
		$firstrow = get_array_value($extra_params,'first_row',NULL,'is_not0_numeric');
		$lastrow = get_array_value($extra_params,'last_row',NULL,'is_not0_numeric');
		$sort = get_array_value($extra_params,'sort',NULL,'is_notempty_array');
		$filters = get_array_value($extra_params,'filters',NULL,'is_notempty_array');
		$out_params = get_array_value($extra_params,'out_params',[],'is_array');
		$log = get_array_value($extra_params,'log',FALSE,'bool');
		$method = ($this->use_pdo ? 'Pdo' : $this->dbtype).str_replace(__CLASS__.'::','',__METHOD__);
		$results_keys_case = get_array_value($extra_params,'results_keys_case',NULL,'is_numeric');
		$custom_tran_params = get_array_value($extra_params,'custom_tran_params',NULL,'isset');
		$result = $this::$method($procedure,$params,$out_params,$tran_name,$type,$firstrow,$lastrow,$sort,$filters,$log,$results_keys_case,$custom_tran_params);
		if($out_params) {
			if(!is_array($extra_params)) { $extra_params = []; }
			$extra_params['out_params'] = $out_params;
		}//if($out_params)
		return $result;
	}//END public function ExecuteProcedure
	/**
	 * Executs a method of the database object or a sub-object of it
	 *
	 * @param  string $method The name of the method to be executed
	 * @param  string $property The name of the sub-object containing the method
	 * to be executed
	 * @param  array $params An array of parameters
	 * to be passed to the method
	 * @param  array $extra_params An array of extra parameters
	 * to be passed to the invoking method
	 * @return mixed Returns database method result
	 * @access public
	 */
	public function ExecuteMethod($method,$property = NULL,$params = [],$extra_params = []) {
		$this->debug = get_array_value($extra_params,'debug',$this->debug,'bool');
		$this->debug2file = get_array_value($extra_params,'debug2file',$this->debug2file,'bool');
		$log = get_array_value($extra_params,'log',FALSE,'bool');
		$cmethod = $this->dbtype.str_replace(__CLASS__.'::','',__METHOD__);
		return $this::$cmethod($method,$property,$params,$extra_params,$log);
	}//END public function ExecuteQuery
	public function PdoBeginTran($name,$log = TRUE,$overwrite = TRUE) {
		if(array_key_exists($name,$this->pdo_transactions) && isset($this->pdo_transactions[$name])) {
			if($overwrite===TRUE) {
				try {
					$this->pdo_transactions[$name] = new PDO($this->pdo_connection_string,$this->pdo_connection_user,$this->pdo_connection_password);
					$this->pdo_transactions[$name]->beginTransaction();
					return $this->pdo_transactions[$name];
				}catch(\PDOException $e){
					throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
				}//try
			}//if($overwrite===TRUE)
			return null;
		}//if(array_key_exists($name,$this->pdo_transactions) && isset($this->pdo_transactions[$name]))
		try {
			$this->pdo_transactions[$name] = new PDO($this->pdo_connection_string,$this->pdo_connection_user,$this->pdo_connection_password);
			$this->pdo_transactions[$name]->beginTransaction();
			return $this->pdo_transactions[$name];
		}catch(\PDOException $e){
			throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
		}//try
	}//END public function PdoBeginTran
	public function PdoRollbackTran($name,$log = FALSE) {
		if(array_key_exists($name,$this->pdo_transactions) && isset($this->pdo_transactions[$name])) {
			try {
				$this->pdo_transactions[$name]->rollBack();
				unset($this->pdo_transactions[$name]);
				return TRUE;
			}catch(\PDOException $e){
				throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
			}//try
		}//if(array_key_exists($name,$this->pdo_transactions) && isset($this->pdo_transactions[$name]))
		return FALSE;
	}//END public function PdoRollbackTran
	public function PdoCommitTran($name,$log = FALSE) {
		if(array_key_exists($name,$this->pdo_transactions) && isset($this->pdo_transactions[$name])) {
			try {
				$this->pdo_transactions[$name]->commit();
				unset($this->pdo_transactions[$name]);
				return TRUE;
			}catch(\PDOException $e){
				throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
			}//try
		}//if(array_key_exists($name,$this->pdo_transactions) && isset($this->pdo_transactions[$name]))
		return FALSE;
	}//END public function PdoCommitTran
	public function PdoExecuteQuery($query,$params = [],$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$log = FALSE) {
		$time = microtime(TRUE);
		$trans = FALSE;
		$method = $this->dbtype.'PrepareQuery';
		self::$method($query,$params,$out_params,$type,$firstrow,$lastrow,$sort);
		$conn = $this->pdo_connection;
		if(strlen($tran_name)>0) {
			$trans = TRUE;
			if(array_key_exists($tran_name,$this->pdo_transactions) && isset($this->pdo_transactions[$tran_name])) {
				$conn = $this->pdo_transactions[$tran_name];
			}else{
				throw new \NETopes\Core\AppException("FAILED QUERY: NULL database transaction in statement: ".$query,E_ERROR,1,NULL,NULL,'pdo',0);
			}//if(array_key_exists($tran_name,$this->pdo_transactions))
		}//if(strlen($tran_name)>0)
		$final_result = null;
		try {
			$result = $conn->query($query);
			if(is_object($result)) {
				$final_result = $result->fetchAll(PDO::FETCH_ASSOC);
			} else {
				$final_result = $result;
			}//if(is_object($result))
		} catch(\Exception $e) {
			if($trans) { $this->PdoRollbackTran($tran_name); }
			throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
		}//try
		//if($this->debug==1) {echo " #Duration: ".number_format((microtime(TRUE)-$time),3,'.','')." sec#<br/>";}
		return change_array_keys_case($final_result,TRUE);
	}//END public function PdoExecuteQuery
	public function PdoExecuteProcedure($procedure,$params = [],$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = FALSE) {
		$time = microtime(TRUE);
		$trans = FALSE;
		$method = $this->dbtype.'PrepareProcedureStatement';
		$query = self::$method($procedure,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
		$conn = $this->connection;
		if(strlen($tran_name)>0) {
			$trans = TRUE;
			if(array_key_exists($tran_name,$this->pdo_transactions)) {
				$transaction = $tran_name;
			}else{
				throw new \NETopes\Core\AppException("FAILED QUERY: NULL database transaction in statement: ".$query,E_ERROR,1,NULL,NULL,'pdo',0);
			}//if(array_key_exists($tran_name,$this->pdo_transactions))
		}//if(strlen($tran_name)>0)
		$final_result = null;
		try {
			$result = $conn->query($query);
			if(is_object($result)) {
				$final_result = $result->fetchAll(PDO::FETCH_ASSOC);
			}elseif(is_int($result) || is_bool($result)) {
				$final_result = $result;
			}//if($withselect)
		} catch(\PdoException $e) {
			if($trans) {
				$this->PdoRollbackTran($transaction);
			}//if($trans)
			throw new \NETopes\Core\AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
		}//END try
		//if($this->debug==1) {echo " #Duration: ".number_format((microtime(TRUE)-$time),3,'.','')." sec#<br/>";}
		return change_array_keys_case($final_result,TRUE);
	}//END public function PdoExecuteProcedure
}//END abstract class SqlDataAdapter extends DataAdapter
?>