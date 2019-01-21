<?php
/**
 * MySql database implementation class file
 * This file contains the implementing class for MySql SQL database.
 * @package    Hinter\NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2004 - 2015 Hinter Software
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NETopes\Core\AppException;
/**
 * MySqlDatabase Is implementing the MySql database
 * This class contains all methods for interacting with MySql database.
 * @package  Hinter\NETopes\Database
 */
class MySqlAdapter extends SqlDataAdapter {
	/**
	 * Set global variables to a temporary table
	 * @param  array $params Key-value array of variables to be set
	 * @return bool  Returns TRUE on success or FALSE otherwise
	 */
	public function MySqlSetGlobalVariables($params = []) {
		if(!is_array($params) || !count($params)) { return TRUE; }
		return FALSE;
	}//END public function MySqlSetGlobalVariables
	/**
	 * Class initialization abstract method
	 * (called automatically on class constructor)
	 * @param  array $connection Database connection
	 * @return void
	 * @throws \NETopes\Core\AppException
	 */
	protected function Init($connection) {
		$db_port = (array_key_exists('db_port',$connection) && $connection['db_port']) ? ':'.$connection['db_port'] : '';
		try {
			//\NETopes\Core\App\Debugger::StartTimeTrack('mysqli_connect');
			if(!($this->connection = new \mysqli($connection['db_server'].$db_port,$connection['db_user'],(array_key_exists('db_password',$connection) ? $connection['db_password'] : ''),$this->dbName))) { throw new \Exception('Error connecting to mysql server: '.mysqli_error(),E_USER_ERROR); }
			// NApp::Dlog(\NETopes\Core\App\Debugger::ShowTimeTrack('mysqli_connect'),'mysqli_connect');
			if(!$this->connection->set_charset("utf8")) { throw new \Exception('Error setting default mysql charset: '.mysqli_error(),E_USER_ERROR); }
		} catch(\Exception $e){
			throw new AppException($e->getMessage(),E_USER_ERROR,1,__FILE__,__LINE__,'mysql',0);
		}//END try
	}//END protected function Init
	/**
	 * Begins a mysql transaction
	 * @param  string $name Transaction name
	 * @param  bool $overwrite Flag for overwriting the transaction
	 * if exists (defaul value FALSE)
	 * @return object Returns the transaction instance
	 */
	public function MySqlBeginTran($name,$log = TRUE,$overwrite = TRUE) {
		return NULL;
	}//END public function MySqlBeginTran
	/**
	 * Rolls back a mysql transaction
	 * @param  string $name Transaction name
	 * @return bool Returns TRUE on success or FALSE otherwise
	 */
	public function MySqlRollbackTran($name,$log = TRUE) {
		return FALSE;
	}//END public function MySqlRollbackTran
	/**
	 * Commits a mysql transaction
	 * @param  string $name Transaction name
	 * @return bool Returns TRUE on success or FALSE otherwise
	 */
	public function MySqlCommitTran($name,$log = TRUE,$preserve = FALSE) {
		return FALSE;
	}//END public function MySqlCommitTran
	/**
	 * Prepares the query string for execution
	 * @param  string $query The query string (by reference)
	 * @param  array  $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array  $out_params An array of output params
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int    $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' reprezents the offset of the returned rows)
	 * @param  int    $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array  $sort An array of fields to compose ORDER BY clause
	 * @param null    $filters
	 * @param null    $raw_query
	 * @param null    $bind_params
	 * @param null    $transaction
	 * @return void
	 */
	public function MySqlPrepareQuery(&$query,$params = [],$out_params = [],$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,&$raw_query = NULL,&$bind_params = NULL,$transaction = NULL) {
		if(is_array($params) && count($params)) {
			foreach($params as $k=>$v) { $query = str_replace('{{'.$k.'}}',$this->MySqlEscapeString($v),$query); }
		}//if(is_array($params) && count($params))
		$filter_str = '';
		if(is_array($filters)) {
			$t_alias = get_array_value($filters,'table__alias','','is_string');
			$t_alias = strlen($t_alias) ? $t_alias.'.' : '';
			$f_addwhere = is_array($filters) && array_key_exists('add__where',$filters) ? $filters['add__where'] : NULL;
			if($f_addwhere || (is_null($f_addwhere) && strpos(strtolower($query),' where ')===FALSE)) {
				$filter_prefix = ' where ';
				$filter_sufix = ' ';
			} else {
				$filter_prefix =  ' and (';
				$filter_sufix = ') ';
			}//if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtolower($query),' where ')===FALSE)
			foreach($filters as $k=>$v) {
				if(!is_numeric($k) && $k=='table__alias') { continue; }
				if(is_array($v)) {
					if(count($v)==4) {
						$ffield = get_array_value($v,'field',NULL,'is_notempty_string');
						$fvalue = get_array_value($v,'value',NULL,'is_notempty_string');
						if(!$ffield || !$fvalue || strtolower($fvalue)=='null') { continue; }
						$fcond = get_array_value($v,'condition_type','=','is_notempty_string');
						$sep = get_array_value($v,'logical_separator','and','is_notempty_string');
						switch(strtolower($fcond)) {
							case 'like':
							case 'not like':
								$filter_str .= ($filter_str ? ' '.strtolower($sep) : '').' '.$t_alias.'`'.$ffield.'` '.strtolower($fcond)." '%".$fvalue."%'";
								break;
							case '==':
							case '<>':
							case '<=':
							case '>=':
								$filter_str .= ($filter_str ? ' '.strtolower($sep) : '').' '.$t_alias.'`'.$ffield.'` '.$fcond." '".$fvalue."'";
								break;
							default:
								continue;
								break;
						}//END switch
					} elseif(count($v)==2) {
						$cond = get_array_value($v,'condition',NULL,'is_notempty_string');
						if(!$cond){ continue; }
						$sep = get_array_value($v,'logical_separator','and','is_notempty_string');
						$filter_str .= ($filter_str ? ' '.strtolower($sep).' ' : ' ').$cond;
					}//if(count($v)==4)
				} elseif(is_string($v) && strlen($v)) {
					$filter_str .= ($filter_str ? ' AND ' : ' ').$v;
				}//if(is_array($v))
			}//END foreach
			$filter_str = strlen(trim($filter_str))>0 ? $filter_prefix.$filter_str.$filter_sufix : '';
		} elseif(strlen($filters)>0) {
			$filter_str = " $filters ";
		}//if(is_array($filters))
		$query .= $filter_str;
		if($type=='count'){ return; }
		$sort_str = '';
		if(is_array($sort)) {
			foreach ($sort as $k=>$v) { $sort_str .= ($sort_str ? ' ,' : ' ').str_replace('"','`',strtolower($k)).' '.$v; }
			$sort_str = strlen(trim($sort_str))>0 ? ' order by'.$sort_str.' ' : '';
		} elseif(strlen($sort)>0) {
			$sort_str = " order by $sort asc ";
		}//if(is_array($sort))
		$query .= $sort_str;
		if(is_numeric($firstrow) && $firstrow>0 && is_numeric($lastrow) && $lastrow>0) {
			$query .= ' limit '.($firstrow-1).', '.($lastrow-$firstrow+1);
		} elseif(is_numeric($firstrow) && $firstrow>0) {
			$query .= ' limit '.$firstrow;
		}//if(is_numeric($firstrow) && $firstrow>0 && is_numeric($lastrow) && $lastrow>0)
	}//public function MySqlPrepareQuery
	/**
	 * Executs a query against the database
	 * @param  string $query The query string
	 * @param  array  $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array  $out_params An array of output params
	 * @param  string $tran_name Name of transaction in which the query will run
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int    $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' reprezents the offset of the returned rows)
	 * @param  int    $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array  $sort An array of fields to compose ORDER BY clause
	 * @param null    $filters
	 * @param bool    $log
	 * @param null    $results_keys_case
	 * @param null    $custom_tran_params
	 * @return array|bool Returns database request result
	 * @throws \NETopes\Core\AppException
	 */
	public function MySqlExecuteQuery($query,$params = [],&$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = FALSE,$results_keys_case = NULL,$custom_tran_params = NULL) {
		$time = microtime(TRUE);
		$this->MySqlPrepareQuery($query,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
		/*
		if(strlen($tran_name)>0) {
			if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name])) {
				$transaction = $tran_name;
			}else{
				throw new FbDbException("FAILED QUERY: NULL database transaction in statement: ".$query);
			}//if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name]))
		} else {
			$transaction = 'DefaultTransaction';
			if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]) || !is_resource($this->transactions[$transaction])) {
				$this->BeginTran($transaction);
			}//if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]) || !is_resource($this->transactions[$transaction]))
		}//if(strlen($tran_name)>0)
		*/
		$final_result = NULL;
		if($this->connection->connect_errno) {
			throw new AppException("MySQL connection failed: #ErrorCode:".$this->connection->connect_errno."# ".$this->connection->connect_error." at statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mysql',$this->connection->connect_errno);
		}//if($this->connection->connect_errno)
		$result = $this->connection->query($query);
		if($this->connection->error || $result===FALSE) {
			//$this->RollbackTran($tran_name);
			throw new AppException("FAILED QUERY: #ErrorCode:".$this->connection->connect_errno."# ".$this->connection->connect_error." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mysql',$this->connection->connect_errno);
		}//if(mysqli_error($this->connection) || $result===FALSE)
		if(is_object($result)){
			if(method_exists('mysqli_result','fetch_all')) {
				$final_result = $result->fetch_all(MYSQLI_ASSOC);
			} else {
				while($data = $result->fetch_array(MYSQLI_ASSOC)) {
					$final_result[] = $data;
				}//END while
			}//if(method_exists('mysqli_result','fetch_all'))
			$result->close();
		}else{
			$final_result = $result;
		}//if(is_object($result))
		if($this->connection->more_results()) { $this->connection->next_result(); }
		/*
		if(strlen($tran_name)==0) {
			$this->CommitTran($transaction);
		}//if(strlen($tran_name)==0)
		*/
		$this->DbDebug($query,'Query',$time);
		return change_array_keys_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->resultsKeysCase));
	}//END public function MySqlExecuteQuery
	/**
	 * Prepares the command string to be executed
	 * @param  string $procedure The name of the stored procedure
	 * @param  array  $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array  $out_params An array of output params
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int    $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' reprezents the offset of the returned rows)
	 * @param  int    $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array  $sort An array of fields to compose ORDER BY clause
	 * @param  array  $filters An array of condition to be applyed in WHERE clause
	 * @param null    $raw_query
	 * @param null    $bind_params
	 * @param null    $transaction
	 * @return string Returns processed command string
	 */
	protected function MySqlPrepareProcedureStatement($procedure,$params = [],&$out_params = [],$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,&$raw_query = NULL,&$bind_params = NULL,$transaction = NULL) {
		if(is_array($params)) {
			if(count($params)>0) {
				$parameters_in = '';
				foreach($this->MysqlEscapeString($params) as $p) {
					$parameters_in .= (strlen($parameters_in)>0 ? ',' : '').(strtolower($p)=='null' ? 'NULL' : "'$p'");
				}//END foreach
			}else{
				$parameters_in = '';
			}//if(count($params)>0)
		} else {
			$parameters_in = $params;
		}//if(is_array($params))
		if(is_array($out_params)) {
			if(empty($out_params)) {
				$parameters_out = '';
			}else{
				$parameters_out = implode(',',$out_params);
			}//if(empty($params))
		} else {
			$parameters_out = $out_params;
		}//if(is_array($params))
		$query = 'CALL '.$procedure.'('.$parameters_in.(strlen($parameters_out)>0 ? ','.$parameters_out : '').')';
		return $query;
	}//END protected function MySqlPrepareProcedureStatement
	/**
	 * Executs a stored procedure against the database
	 * @param  string $procedure The name of the stored procedure
	 * @param  array  $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array  $out_params An array of output params
	 * @param  string $tran_name Name of transaction in which the query will run
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int    $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' reprezents the offset of the returned rows)
	 * @param  int    $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array  $sort An array of fields to compose ORDER BY clause
	 * @param  array  $filters An array of condition to be applyed in WHERE clause
	 * @param bool    $log
	 * @param null    $results_keys_case
	 * @param null    $custom_tran_params
	 * @return array|bool Returns database request result
	 * @throws \NETopes\Core\AppException
	 */
	public function MySqlExecuteProcedure($procedure,$params = [],&$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = FALSE,$results_keys_case = NULL,$custom_tran_params = NULL) {
		$time = microtime(TRUE);
		$query = $this->MySqlPrepareProcedureStatement($procedure,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
		/*
		if(strlen($tran_name)>0) {
			if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name])) {
				$transaction = $tran_name;
			}else{
				throw new FbDbException("FAILED QUERY: NULL database transaction in statement: ".$query);
			}//if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name]))
		} else {
			$transaction = 'DefaultTransaction';
			if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]) || !is_resource($this->transactions[$transaction])) {
				$this->BeginTran($transaction);
			}//if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]) || !is_resource($this->transactions[$transaction]))
		}//if(strlen($tran_name)>0)
		*/
		$final_result = NULL;
		if($this->connection->connect_errno) {
			throw new AppException("MySQL connection failed: #ErrorCode:".$this->connection->connect_errno."# ".$this->connection->connect_error." at statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mysql',$this->connection->connect_errno);
		}//if($this->connection->connect_errno)
		$result = $this->connection->query($query);
		if($this->connection->error || $result===FALSE) {
			//$this->RollbackTran($tran_name);
			throw new AppException("FAILED EXECUTE PROCEDURE: #ErrorCode:".$this->connection->connect_errno."# ".$this->connection->connect_error." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mysql',$this->connection->connect_errno);
		}//if(mysqli_error($this->connection) || $result===FALSE)
		if((is_array($out_params) && count($out_params)>0) || (!is_array($out_params) && strlen($out_params)>0)) {
			$parameters_out = implode(",",$out_params);
			$out_result = $this->connection->query("SELECT $parameters_out");
			if($this->connection->error || $out_result===FALSE) {
				//$this->RollbackTran($tran_name);
				throw new AppException("FAILED EXECUTE PROCEDURE: #ErrorCode:".$this->connection->connect_errno."# ".$this->connection->connect_error." in statement: $query -> SELECT $parameters_out",E_USER_ERROR,1,__FILE__,__LINE__,'mysql',$this->connection->connect_errno);
			}//if(mysqli_error($this->connection) || $out_result===FALSE)
			if(is_object($out_result)){
				if(method_exists('mysqli_result','fetch_all')) {
					$final_result = $out_result->fetch_all(MYSQLI_ASSOC);
				} else {
					while($data = $out_result->fetch_array(MYSQLI_ASSOC)) {
						$final_result[] = $data;
					}//END while
				}//if(method_exists('mysqli_result','fetch_all'))
				$out_result->close();
			}else{
				$final_result = $out_result;
			}//if(is_object($result))
			if(is_object($result)) { $result->close(); }
			if($this->connection->more_results()) { $this->connection->next_result(); }
		} else {
			if(is_object($result)){
				if(!$firstrow) {
					if(method_exists('mysqli_result','fetch_all')) {
						$final_result = $result->fetch_all(MYSQLI_ASSOC);
					} else {
						while($data = $result->fetch_array(MYSQLI_ASSOC)) {
							$final_result[] = $data;
						}//END while
					}//if(method_exists('mysqli_result','fetch_all'))
				} else {
					if(!$lastrow) {
						while($data = $result->fetch_array(MYSQLI_ASSOC)) {
							$final_result[] = $data;
							if(count($final_result)>=$firstrow) { break; }
						}//END while
					} else {
						$rowid = 0;
						while($data = $result->fetch_array(MYSQLI_ASSOC)) {
							$rowid++;
							if($rowid<$firstrow) { continue; }
							$final_result[] = $data;
							if($rowid>=$lastrow) { break; }
						}//END while
					}//if(!$lastrow)
				}//if(!$firstrow)
				$result->close();
			}else{
				$final_result = $result;
			}//if(is_object($result))
			if($this->connection->more_results()) { $this->connection->next_result(); }
		}//if((is_array($out_params) && count($out_params)>0) || (!is_array($out_params) && strlen($out_params)>0))
		/*
		if(strlen($tran_name)==0) {
			$this->CommitTran($transaction);
		}//if(strlen($tran_name)==0)
		*/
		$this->DbDebug($query,'Query',$time);
		return change_array_keys_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->resultsKeysCase));
	}//END public function MySqlExecuteProcedure
	/**
	 * Executes a method of the database object or of one of its sub-objects
	 * @param  string $method Name of the method to be called
	 * @param  string $property The name of the sub-object containing the method
	 * to be executed
	 * @param  array $params An array of parameters
	 * to be passed to the method
	 * @param  array $extra_params An array of extra parameters
	 * @param  bool   $log Flag to turn logging on/off
	 * @return void   return description
	 * @throws \NETopes\Core\AppException
	 */
	public function MySqlExecuteMethod($method,$property = NULL,$params = [],$extra_params = [],$log = TRUE) {
		throw new AppException("FAILED EXECUTE METHOD: #ErrorCode:N/A# Execute method not implemented for MySQL !!! in statement: ".$method.trim('->'.$property,'->'),E_USER_ERROR,1,__FILE__,__LINE__,'mysql',0);
	}//END public function MySqlExecuteMethod
	/**
	 * Escapes MySql special charcaters from a string
	 * @param  string|array $param String to be escaped or
	 * an array of strings
	 * @return string|array Returns the escaped string or array
	 */
	public function MySqlEscapeString($param) {
		$result = NULL;
		if(is_array($param)) {
			$result = [];
			foreach ($param as $k=>$v) { $result[$k] = $this->connection->escape_string($v); }
		} else { $result = $this->connection->escape_string($param); }
		return $result;
	}//END public function MySqlEscapeString
}//END class MySqlAdapter extends SqlDataAdapter