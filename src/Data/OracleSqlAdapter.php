<?php
/**
 * Oracle database implementation class file
 *
 * This file contains the implementing class for Oracle SQL database.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
/**
 * OracleDatabase is implementing the Oracle database
 *
 * This class contains all methods for interacting with Oracle database.
 *
 * @package  NETopes\Database
 * @access   public
 */
class OracleSqlAdapter extends SqlDataAdapter {
	/**
	 * Get startup query string
	 *
	 * @param  array $params Key-value array of variables to be set
	 * @return string  Returns the query to be executed after connection
	 * @access public
	 * @static
	 */
	public static function GetStartUpQuery($params = []) {
		if(!is_array($params) || !count($params)) { return NULL; }
		throw new AException('Method [GetStartUpQuery] not implemented for Oracle !!!',E_ERROR,1,__FILE__,__LINE__,'oracle',0);
	}//public static function GetStartUpQuery
	/**
	 * Set global variables to a temporary table
	 *
	 * @param  array $params Key-value array of variables to be set
	 * @return bool  Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function OracleSetGlobalVariables($params = []) {
		if(!is_array($params) || !count($params)) { return TRUE; }
		throw new AException('Method [OracleSetGlobalVariables] not implemented for Oracle !!!',E_ERROR,1,__FILE__,__LINE__,'oracle',0);
	}//END public function OracleSetGlobalVariables
	/**
	 * Class initialization abstract method
	 * (called automatically on class constructor)
	 *
	 * @param  array $connection Database connection array
	 * @return void
	 * @access protected
	 */
	protected function Init($connection) {
		$tns = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = {$connection['db_server']})";
		$tns .= "(PORT = ".(isset($connection['db_port']) && strlen($connection['db_port']) ? $connection['db_port'] : '1521')."))";
		$tns .= " (CONNECT_DATA =";
		if(isset($connection['db_server_type']) && strlen($connection['db_server_type'])) {
			$tns .= " (SERVER = ".strtoupper($connection['db_server_type']).")";
		}//if(isset($connection['db_server_type']) && strlen($connection['db_server_type']))
		if(isset($connection['db_conn_type']) && strlen($connection['db_conn_type'])) {
			$tns .= " ({$connection['db_conn_type']} = {$this->dbname})";
		} else {
			$tns .= " (SERVICE_NAME = {$this->dbname})";
		}//if(isset($connection['db_conn_type']) && strlen($connection['db_conn_type']))
		$tns .= "))";
		vprint('$tns: '.$tns);
		$time = microtime(TRUE);
		try {
			$this->connection = oci_connect($connection['db_user'],(array_key_exists('db_password',$connection) ? $connection['db_password'] : ''),$tns,'AL32UTF8');
			if(!is_resource($this->connection)) {
				$dbe = oci_error();
				throw new AException("FAILED TO CONNECT TO DATABASE: ({$dbe['message']})",E_ERROR,1,__FILE__,__LINE__,'oracle',0);
			}//if(!is_resource($this->connection))
			//$this->DbDebug('Connected to ['.$connection['db_server'].$db_port.':'.$this->dbname.']','Connection',$time);
		} catch(Exception $e) {
			throw new AException("FAILED TO CONNECT TO DATABASE: ".$this->dbname." (".$e->getMessage().")",E_ERROR,1,__FILE__,__LINE__,'oracle',0);
		}//END try
	}//END protected function Init
	/**
	 * Begins a oracle transaction
	 *
	 * @param  string $name Transaction name
	 * @param  bool $overwrite Flag for overwriting the transaction
	 * if exists (defaul value FALSE)
	 * @return object Returns the transaction instance
	 * @access public
	 */
	public function OracleBeginTran($name,$log = TRUE,$overwrite = TRUE,$custom_tran_params = NULL) {
		if(array_key_exists($name,$this->transactions) && $this->transactions[$name] && !$overwrite){ return NULL; }
		// $this->transactions[$name] = $this->connection;
		$this->transactions[$name] = TRUE;
		if($name!='DefaultTransaction') { $this->DbDebug($name.'   =>   BEGIN','BeginTran'); }
		return $this->transactions[$name];
	}//END public function OracleBeginTran
	/**
	 * Rolls back a oracle transaction
	 *
	 * @param  string $name Transaction name
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function OracleRollbackTran($name,$log = TRUE) {
		if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
			// oci_rollback($this->transactions[$name]);
			oci_rollback($this->connection);
			unset($this->transactions[$name]);
			$this->DbDebug($name.'   =>   ROLLBACK','RollbackTran');
			return TRUE;
		}//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
		return FALSE;
	}//END public function OracleRollbackTran
	/**
	 * Commits a oracle transaction
	 *
	 * @param  string $name Transaction name
	 * @return bool Returns TRUE on success or FALSE otherwise
	 * @access public
	 */
	public function OracleCommitTran($name,$log = TRUE) {
		if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
			// oci_commit($this->transactions[$name]);
			oci_commit($this->connection);
			unset($this->transactions[$name]);
			if($name!='DefaultTransaction') { $this->DbDebug($name.'   =>   COMMIT','CommitTran'); }
			return TRUE;
		}//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
		return FALSE;
	}//END public function OracleCommitTran
	/**
	 * Prepares the query string for execution
	 *
	 * @param  string $query The query string (by reference)
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $out_params An array of output params
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * @param  int $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array $sort An array of fields to compose ORDER BY clause
	 * @param  array $filters An array of condition to be applied in WHERE clause
	 * @param  string $row_query By reference parameter that will store row query string
	 * @return void
	 * @access public
	 */
	public function OraclePrepareQuery(&$query,$params = [],$out_params = [],$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,&$raw_query = NULL) {
		//OCI_NO_AUTO_COMMIT
		if(is_array($params) && count($params)){
			foreach($params as $k=>$v) { $query = str_replace('{{'.$k.'}}',self::OracleEscapeString($v),$query); }
		}//if(is_array($params) && count($params))
		$filter_str = '';
		if(is_array($filters)) {
			if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtoupper($query),' WHERE ')===FALSE) {
				$filter_prefix = ' WHERE ';
				$filter_sufix = ' ';
			} else {
				$filter_prefix =  ' AND (';
				$filter_sufix = ') ';
			}//if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtoupper($query),' WHERE ')===FALSE)
			foreach ($filters as $v) {
				if(is_array($v)) {
					if(count($v)==4) {
						$ffield = get_array_value($v,'field',NULL,'is_notempty_string');
						$fvalue = get_array_value($v,'value',NULL,'is_notempty_string');
						if(!$ffield || !$fvalue || strtolower($fvalue)==='null') { continue; }
						$fcond = get_array_value($v,'condition_type','=','is_notempty_string');
						$sep = get_array_value($v,'logical_separator','AND','is_notempty_string');
						switch(strtolower($fcond)) {
							case 'like':
							case 'not like':
								$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.strtoupper($fcond).(strtolower($fvalue)==='null' ? ' NULL' : " '%{$fvalue}%'");
								break;
							case '==':
							case '<>':
							case '<=':
							case '>=':
								$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.$fcond.(strtolower($fvalue)==='null' ? ' NULL' : " '{$fvalue}'");
								break;
							default:
								continue;
								break;
						}//END switch
					} elseif(count($v==2)) {
						$cond = get_array_value($v,'condition',NULL,'is_notempty_string');
						if(!$cond){ continue; }
						$sep = get_array_value($v,'logical_separator','AND','is_notempty_string');
						$filter_str .= ($filter_str ? ' '.strtoupper($sep).' ' : ' ').$cond;
					}//if(count($v)==4)
				}else{
					$filter_str .= ($filter_str ? ' AND ' : ' ').$v;
				}//if(is_array($v))
			}//END foreach
			$filter_str = strlen(trim($filter_str))>0 ? $filter_prefix.$filter_str.$filter_sufix : '';
		} elseif(strlen($filters)>0) {
			$filter_str = " {$filters} ";
		}//if(is_array($filters))
		$query .= $filter_str;
		$raw_query = $query;
		if($type=='count'){ return; }
		$sort_str = '';
		if(is_array($sort)) {
			foreach ($sort as $k=>$v) { $sort_str .= ($sort_str ? ' ,' : ' ')."{$k} {$v}"; }
			$sort_str = strlen(trim($sort_str))>0 ? ' ORDER BY'.$sort_str.' ' : '';
		} elseif(strlen($sort)>0) {
			$sort_str = " ORDER BY {$sort} ASC ";
		}//if(is_array($sort))
		$query .= $sort_str;
		if(is_numeric($firstrow) && $firstrow>0 && is_numeric($lastrow) && $lastrow>0) {
			$query .= ' ROWS '.$firstrow.' TO '.$lastrow;
		} elseif(is_numeric($firstrow) && $firstrow>0) {
			$query .= ' ROWS '.$firstrow;
		}//if(is_numeric($firstrow) && $firstrow>0 && is_numeric($lastrow) && $lastrow>0)
	}//public function OraclePrepareQuery
	/**
	 * Executes a query against the database
	 *
	 * @param  string $query The query string
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $out_params An array of output params
	 * @param  string $tran_name Name of transaction in which the query will run
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * @param  int $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array $sort An array of fields to compose ORDER BY clause
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function OracleExecuteQuery($query,$params = [],&$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = TRUE,$results_keys_case = NULL,$custom_tran_params = NULL) {
		$time = microtime(TRUE);
		$raw_query = NULL;
		$this->OraclePrepareQuery($query,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
		if(!is_array($out_params)) { $out_params = []; }
		$out_params['rawsqlqry'] = $raw_query;
		$out_params['sqlqry'] = $query;
		if(strlen($tran_name)) {
		if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name])) {
			$transaction = $tran_name;
		}else{
			throw new AException("FAILED QUERY: NULL database transaction in statement: ".$query,E_ERROR,1,__FILE__,__LINE__,'oracle',0);
		}//if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name]))
	} else {
$transaction = 'DefaultTransaction';
if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction])) {
$this->OracleBeginTran($transaction,TRUE,TRUE,$custom_tran_params);
}//if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]))
}//if(strlen($tran_name))
		$final_result = NULL;
		try {
			$result = oci_parse($this->connection,$query);
			oci_execute($result,OCI_NO_AUTO_COMMIT);
		} catch(Exception $e) {
			$this->OracleRollbackTran($transaction);
			throw new AException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'oracle',0);
		}//END try
		// if(ibase_errmsg() || $result===FALSE) {
		// 	$iberror = ibase_errmsg();
		// 	$iberrorcode = ibase_errcode();
		// 	$this->OracleRollbackTran($transaction);
		// 	throw new AException("FAILED QUERY: #ErrorCode:{$iberrorcode}# {$iberror} in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'oracle',$iberrorcode);
		// }//if(ibase_errmsg() || $result===FALSE)
		try {
			if(is_resource($result)) {
				$rno = oci_fetch_all($result,$final_result,0,10,OCI_FETCHSTATEMENT_BY_ROW);
				// vprint('rno: '.$rno);
				oci_free_statement($result);
			}else{
				$final_result = $result;
			}//if(is_resource($result))
		} catch(Exception $e) {
			// $this->OracleRollbackTran($transaction);
			throw new AException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'oracle',0);
		}//END try
		if(!strlen($tran_name)) { $this->OracleCommitTran($transaction); }
		$this->DbDebug($query,'Query',$time);
		return arr_change_key_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->results_keys_case));
	}//END public function OracleExecuteQuery
	/**
	 * Prepares the command string to be executed
	 *
	 * @param  string $procedure The name of the stored procedure
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $out_params An array of output params
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * @param  int $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array $sort An array of fields to compose ORDER BY clause
	 * @param  array $filters An array of condition to be applied in WHERE clause
	 * @param  string $row_query By reference parameter that will store row query string
	 * @return string|resource Returns processed command string or the statement resource
	 * @access protected
	 */
	protected function OraclePrepareProcedureStatement($procedure,$params = [],&$out_params = [],$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,&$raw_query = NULL) {
		if(is_array($params)) {
			if(count($params)>0) {
				$parameters = '';
				foreach(self::OracleEscapeString($params) as $p) {
					$parameters .= (strlen($parameters)>0 ? ',' : '(').(strtolower($p)=='null' ? 'NULL' : "'{$p}'");
				}//END foreach
				$parameters .= ')';
			}else{
				$parameters = '';
			}//if(count($params)>0)
		} else {
			$parameters = $params;
		}//if(is_array($params))
		$filter_str = '';
		if(is_array($filters)) {
			$filter_prefix = ' WHERE ';
			$filter_sufix = ' ';
			foreach($filters as $v) {
				if(is_array($v)) {
					if(count($v)>2) {
						$ffield = get_array_value($v,'field',NULL,'is_notempty_string');
						$fvalue = self::OracleEscapeString(get_array_value($v,'value',NULL,'is_notempty_string'));
						if(!$ffield || is_null($fvalue)) { continue; }
						$fcond = get_array_value($v,'condition_type','=','is_notempty_string');
						$sep = get_array_value($v,'logical_separator','AND','is_notempty_string');
						$dtype = get_array_value($v,'data_type',NULL,'is_notempty_string');
						switch(strtolower($fcond)) {
							case 'like':
							case 'not like':
								$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.strtoupper($fcond).(strtolower($fvalue)==='null' ? ' NULL' : " '%{$fvalue}%'");
								break;
							case '==':
							case '<>':
							case '<=':
							case '>=':
								$daypart = NULL;
								$sdaypart = NULL;
								switch(strtolower($dtype)) {
									case 'date':
									case 'date_obj':
										$daypart = 0;
										$sdaypart = 1;
									case 'datetime':
									case 'datetime_obj':
										switch(strtolower($fcond)) {
											case '==':
												$fvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$daypart);
												$fsvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$sdaypart);
												$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' ("'.strtoupper($ffield)."\" BETWEEN '{$fvalue}' AND '{$fsvalue}') ";
												break;
											case '<>':
												$fvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$daypart);
												$fsvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$sdaypart);
												$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' ("'.strtoupper($ffield)."\" NOT BETWEEN '{$fvalue}' AND '{$fsvalue}') ";
												break;
											case '<=':
												$fvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$sdaypart);
												$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.$fcond." '".$fvalue."'";
												break;
											case '>=':
												$fvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$daypart);
												$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.$fcond." '".$fvalue."'";
												break;
										}//END switch
										break;
									default:
										$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.$fcond.(strtolower($fvalue)==='null' ? ' NULL' : " '{$fvalue}'");
										break;
								}//END switch
								break;
							case 'is':
							case 'is not':
								$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' "'.strtoupper($ffield).'" '.strtoupper($fcond).(strtolower($fvalue)==='null' ? ' NULL' : " {$fvalue}");
								break;
							case '><':
								$fsvalue = self::OracleEscapeString(get_array_value($v,'svalue',NULL,'is_notempty_string'));
								if(is_null($fsvalue)) { continue; }
								$daypart = NULL;
								$sdaypart = NULL;
								switch(strtolower($dtype)) {
									case 'date':
									case 'date_obj':
										$daypart = 0;
										$sdaypart = 1;
									case 'datetime':
									case 'datetime_obj':
										$fvalue = NValidator::ConvertDateTimeToDbFormat($fvalue,NULL,$daypart);
										$fsvalue = NValidator::ConvertDateTimeToDbFormat($fsvalue,NULL,$sdaypart);
										$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' ("'.strtoupper($ffield)."\" BETWEEN '{$fvalue}' AND '{$fsvalue}') ";
										break;
									default:
										$filter_str .= ($filter_str ? ' '.strtoupper($sep) : '').' ("'.strtoupper($ffield)."\" BETWEEN '{$fvalue}' AND '{$fsvalue}') ";
										break;
								}//END switch
								break;
							default:
								continue;
								break;
						}//END switch
					} elseif(count($v==2)) {
						$cond = get_array_value($v,'condition',NULL,'is_notempty_string');
						if(!$cond){ continue; }
						$sep = get_array_value($v,'logical_separator','AND','is_notempty_string');
						$filter_str .= ($filter_str ? ' '.strtoupper($sep).' ' : ' ').$cond;
					}//if(count($v)==4)
				} else {
					$filter_str .= ($filter_str ? ' AND ' : ' ').$v;
				}//if(is_array($v))
			}//END foreach
			$filter_str = strlen(trim($filter_str))>0 ? $filter_prefix.$filter_str.$filter_sufix : '';
		} elseif(strlen($filters)>0) {
			$filter_str = strtoupper(substr(trim($filters),0,5))=='WHERE' ? " {$filters} " : " WHERE {$filters} ";
		}//if(is_array($filters))
		if(strtolower($type)=='execute') {
			$raw_query = $procedure.$parameters;
		} else {
			$raw_query = $procedure.'('.$parameters.')'.$filter_str;
		}//if(strtolower($type)=='execute')
		switch(strtolower($type)) {
			case 'execute':
				$query = 'EXECUTE PROCEDURE '.$procedure.$parameters;
				break;
			case 'count':
				$query = 'SELECT count(1) FROM '.$procedure.$parameters.$filter_str;
				break;
			case 'select':
			default:
				$sort_str = '';
				if(is_array($sort)) {
					foreach ($sort as $k=>$v) { $sort_str .= ($sort_str ? ' ,' : ' ')."{$k} {$v}"; }
					$sort_str = strlen(trim($sort_str))>0 ? ' ORDER BY'.$sort_str.' ' : '';
				} elseif(strlen($sort)>0) {
					$sort_str = " ORDER BY {$sort} ASC ";
				}//if(is_array($sort))
				if(is_numeric($firstrow) && $firstrow>0) {
					if(is_numeric($lastrow) && $lastrow>0) {
						$query = 'SELECT * FROM '.$procedure.$parameters.$filter_str.$sort_str.' ROWS '.$firstrow.' TO '.$lastrow;
					} else {
						$query = 'SELECT * FROM '.$procedure.$parameters.$filter_str.$sort_str.' ROWS '.$firstrow;
					}//if(is_numeric($lastrow) && $lastrow>0)
				} else {
					$query = 'SELECT * FROM '.$procedure.$parameters.$filter_str.$sort_str;
				}//if(is_numeric($firstrow) && $firstrow>0)
				break;
		}//END switch
		return $query;
	}//END protected function OraclePrepareProcedureStatement
	/**
	 * Executs a stored procedure against the database
	 *
	 * @param  string $procedure The name of the stored procedure
	 * @param  array $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array $out_params An array of output params
	 * @param  string $tran_name Name of transaction in which the query will run
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * @param  int $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array $sort An array of fields to compose ORDER BY clause
	 * @param  array $filters An array of condition to be applied in WHERE clause
	 * @return array|bool Returns database request result
	 * @access public
	 */
	public function OracleExecuteProcedure($procedure,$params = [],&$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = TRUE,$results_keys_case = NULL,$custom_tran_params = NULL) {
		$time = microtime(TRUE);
		if(!is_array($out_params)) { $out_params = []; }
		$sql_params = NULL;
		$raw_query = NULL;
		$query = $this->OraclePrepareProcedureStatement($procedure,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
		$sql_params4dbg = $sql_params ? '>>Param: '.print_r($sql_params,TRUE) : '';
		$out_params['rawsqlqry'] = $raw_query;
		$out_params['sqlqry'] = $query;
		//if($this->debug2file) { NApp::_Write2LogFile('Query: '.$query,'debug'); }
		if(strlen($tran_name)) {
			if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name])) {
				$transaction = $tran_name;
			}else{
				throw new AException("FAILED EXECUTE PROCEDURE: NULL database transaction in statement: ".$query,E_ERROR,1,__FILE__,__LINE__,'oracle',0);
			}//if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name]))
		} else {
			$transaction = 'DefaultTransaction';
			if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction])) {
				$this->OracleBeginTran($transaction,TRUE,TRUE,$custom_tran_params);
			}//if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]))
		}//if(strlen($tran_name))
		$final_result = NULL;
		try {
			$result = oci_parse($this->connection,$query);
			oci_execute($result,OCI_NO_AUTO_COMMIT);
		} catch(Exception $e) {
			$this->OracleRollbackTran($transaction);
			throw new AException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'oracle',0);
		}//END try
		// if(strlen(ibase_errmsg())>0) { //|| $result===FALSE) {
		// 	$iberror = ibase_errmsg();
		// 	$iberrorcode = ibase_errcode();
		// 	$this->OracleRollbackTran($transaction);
		// 	throw new AException("FAILED EXECUTE PROCEDURE: #ErrorCode:{$iberrorcode}# {$iberror} in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'oracle',$iberrorcode);
		// }//if(strlen(ibase_errmsg())>0 || $result===false)
		try {
			if(is_resource($result)){
				$final_result = oci_fetch_all($result,$out_params,0,-1,OCI_FETCHSTATEMENT_BY_ROW);
				oci_free_statement($result);
			}else{
				$final_result = $result;
			}//if(is_resource($result))
		} catch(Exception $e) {
			$this->OracleRollbackTran($transaction);
			throw new AException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: $query",E_ERROR,1,__FILE__,__LINE__,'oracle',0);
		}//END try
		if(!strlen($tran_name)) { $this->OracleCommitTran($transaction); }
		$this->DbDebug($query.$sql_params4dbg,'Query',$time);
		return arr_change_key_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->results_keys_case));
	}//END public function OracleExecuteProcedure
	/**
	 * Executes a method of the database object or of one of its sub-objects
	 *
	 * @param  string $method Name of the method to be called
	 * @param  string $property The name of the sub-object containing the method
	 * to be executed
	 * @param  array $params An array of parameters
	 * to be passed to the method
	 * @param  array $extra_params An array of extra parameters
	 * @param  bool   $log Flag to turn logging on/off
	 * @return void   return description
	 * @access public
	 */
	public function OracleExecuteMethod($method,$property = NULL,$params = [],$extra_params = [],$log = TRUE) {
		throw new AException("FAILED EXECUTE METHOD: #ErrorCode:N/A# Execute method not implemented for Oracle !!! in statement: ".$method.trim('->'.$property,'->'),E_ERROR,1,__FILE__,__LINE__,'oracle',0);
	}//END public function OracleExecuteMethod
	/**
	 * Escapes single quote charcater from a string
	 *
	 * @param  string|array $param String to be escaped or
	 * an array of strings
	 * @return string|array Returns the escaped string or array
	 * @access public
	 * @static
	 */
	public static function OracleEscapeString($param) {
		$result = NULL;
		if(is_array($param)) {
			$result = [];
			foreach ($param as $k=>$v) { $result[$k] = str_replace("'","''",$v); }
		} else { $result = str_replace("'","''",$param); }
		return $result;
	}//END public function OracleEscapeString
}//END class OracleAdapter extends SqlDataAdapter
?>