<?php
/**
 * FirebirdSql database adapter class file
 * This file contains the adapter class for FirebirdSQL database.
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.5
 * @filesource
 */
namespace NETopes\Core\Data;
use NETopes\Core\Validators\Validator;
use NETopes\Core\AppSession;
use NETopes\Core\AppException;
use NApp;
/**
 * FirebirdSqlDbAdapter is the adapter for the FirebirdSQL database
 * This class contains all methods for interacting with FirebirdSQL database.
 * @package  NETopes\Database
 */
class FirebirdSqlAdapter extends SqlDataAdapter {
    /**
     * Objects names enclosing start symbol
     */
    const ENCLOSING_START_SYMBOL = '"';
    /**
     * Objects names enclosing end symbol
     */
    const ENCLOSING_END_SYMBOL = '"';
	/**
	 * Get startup query string
	 * @param  array $params Key-value array of variables to be set
	 * @return string|null  Returns the query to be executed after connection
	 */
	public static function GetStartUpQuery(?array $params = NULL): ?string {
		if(!is_array($params) || !count($params)) { return NULL; }
		$query = 'EXECUTE BLOCK AS DECLARE VARIABLE TVAR INT; BEGIN ';
		foreach(self::FirebirdSqlEscapeString($params) as $k=>$v) {
			$query .= "\n\t".'SELECT "RDB$SET_CONTEXT"(\'USER_SESSION\',\''.strtoupper($k).'\',\''.$v.'\') FROM "RDB$DATABASE" INTO :TVAR;';
		}//END foreach
		$query .= ' END';
		return $query;
	}//public static function GetStartUpQuery
	/**
	 * Set global variables to a temporary table
	 * @param  array $params Key-value array of variables to be set
	 * @return bool  Returns TRUE on success or FALSE otherwise
	 * @throws \NETopes\Core\AppException
	 */
	public function FirebirdSqlSetGlobalVariables(?array $params = NULL): bool {
		if(!is_array($params) || !count($params)) { return TRUE; }
		$time = microtime(TRUE);
		$query = self::GetStartUpQuery($params);
		$transaction = NULL;
		try {
			$this->FirebirdSqlBeginTran($transaction);
			$result = ibase_query($this->transactions[$transaction],$query);
		} catch(\Exception $e) {
			$this->FirebirdSqlRollbackTran($transaction);
			throw new AppException("FAILED EXECUTE SET GLOBALS QUERY: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
		if(ibase_errmsg() || $result===FALSE) {
			$iberror = ibase_errmsg();
			$iberrorcode = ibase_errcode();
			$this->FirebirdSqlRollbackTran($transaction);
			throw new AppException("FAILED EXECUTE SET GLOBALS QUERY: #ErrorCode:{$iberrorcode}# {$iberror} in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',$iberrorcode);
		}//if(ibase_errmsg() || $result===FALSE)
		$this->FirebirdSqlCommitTran($transaction);
		$this->DbDebug($query,'Query',$time);
		return ($result!==FALSE);
	}//END public function FirebirdSqlSetGlobalVariables
	/**
	 * Class initialization abstract method
	 * (called automatically on class constructor)
	 * @param  array $connection Database connection array
	 * @return void
	 * @throws \NETopes\Core\AppException
	 */
	protected function Init($connection): void {
		$db_port = (array_key_exists('db_port',$connection) && $connection['db_port']) ? '/'.$connection['db_port'] : '';
		$charset = (array_key_exists('charset',$connection) && strlen($connection['charset'])) ? str_replace([' ','-'],['',''],$connection['charset']) : 'UTF8';
		$persistent = (array_key_exists('persistent',$connection) && is_bool($connection['persistent'])) ? $connection['persistent'] : FALSE;
		// $time = microtime(TRUE);
		try {
			if($persistent) {
				$this->connection = ibase_pconnect($connection['db_server'].$db_port.':'.$this->dbName,$connection['db_user'],(array_key_exists('db_password',$connection) ? $connection['db_password'] : ''),$charset,0,3);
			} else {
				$this->connection = ibase_connect($connection['db_server'].$db_port.':'.$this->dbName,$connection['db_user'],(array_key_exists('db_password',$connection) ? $connection['db_password'] : ''),$charset,0,3);
			}//if($persistent)
			//$this->DbDebug('Connected to ['.$connection['db_server'].$db_port.':'.$this->dbName.']','Connection',$time);
		} catch(\Exception $e) {
			throw new AppException("FAILED TO CONNECT TO DATABASE: {$this->dbName} (".$e->getMessage().")",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
	}//END protected function Init
	/**
	 * Close current database connection
	 * @return bool
	 * @throws \NETopes\Core\AppException
	 */
	protected function FirebirdSqlCloseConnection(): bool {
		if(!is_resource($this->connection)) {
			$this->connection = NULL;
			return TRUE;
		}//if(!is_resource($this->connection))
		$result = FALSE;
		try {
			if(ibase_close($this->connection)) {
				$this->connection = NULL;
				$result = TRUE;
			}//if(ibase_close($this->connection))
		} catch(\Exception $e) {
			throw new AppException("FAILED TO CLOSE CONNECTION TO DATABASE: {$this->dbName} (".$e->getMessage().")",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
		return $result;
	}//END protected function FirebirdSqlCloseConnection
    /**
     * Get a firebird transaction
     * @param  string $name Transaction name
     * @param bool    $log
     * @param  bool   $start Flag for starting the transaction
     * if not exists (defaul value FALSE)
     * @param  array  $tran_params Custom transaction arguments
     * @return object Returns the transaction instance
     */
	public function FirebirdSqlGetTran($name,$log = FALSE,$start = TRUE,$tran_params = NULL) {
		if(!is_string($name) || !strlen($name)) { return NULL; }
		if(array_key_exists($name,$this->transactions) && is_resource($this->transactions[$name])) {
			return $this->transactions[$name];
		} elseif($start) {
			return $this->FirebirdSqlBeginTran($name,$log,FALSE,$tran_params);
		} else {
			return NULL;
		}//if(array_key_exists($name,$this->transactions) && is_resource($this->transactions[$name]))
	}//END public function FirebirdSqlGetTran
    /**
     * Begins a firebird transaction
     * @param  string $name Transaction name
     * @param bool    $log
     * @param  bool   $overwrite Flag for overwriting the transaction
     * if exists (defaul value FALSE)
     * @param  array  $tran_params Custom transaction arguments
     * @return object Returns the transaction instance
     */
	public function FirebirdSqlBeginTran(&$name,$log = FALSE,$overwrite = TRUE,$tran_params = NULL) {
		if(!is_string($name) || !strlen($name)) {
			$name = AppSession::GetNewUID(chr(rand(48,57)).chr(rand(48,57)));
		} else {
			if(array_key_exists($name,$this->transactions) && is_resource($this->transactions[$name]) && !$overwrite){ return NULL; }
		}//if(!is_string($name) || !strlen($name))
		if(is_array($tran_params) && count($tran_params)) {
			array_unshift($tran_params,$this->connection);
		} else {
			$tran_params = [IBASE_WRITE,IBASE_COMMITTED,IBASE_REC_NO_VERSION,IBASE_WAIT];
		}//if(is_array($custom_tran_params) && count($custom_tran_params))
		$this->transactions[$name] = call_user_func_array('ibase_trans',$tran_params);
		// $this->DbDebug($name.' => TRANSACTION STARTED >>'.print_r($tran_params,1),'BeginTran',NULL,$log);
		$this->DbDebug($name.' => TRANSACTION STARTED','BeginTran',NULL,$log);
		return $this->transactions[$name];
	}//END public function FirebirdSqlBeginTran
    /**
     * Rolls back a firebird transaction
     * @param  string $name Transaction name
     * @param bool    $log
     * @return bool Returns TRUE on success or FALSE otherwise
     */
	public function FirebirdSqlRollbackTran($name,$log = FALSE) {
		$result = FALSE;
		if(is_null($name)) {
			$result = ibase_rollback($this->connection);
			$this->DbDebug('!DEFAULT! => ROLLBACK','RollbackTran',NULL,$log);
		} elseif(is_string($name) && array_key_exists($name,$this->transactions)) {
			if(is_resource($this->transactions[$name])) {
				$result = ibase_rollback($this->transactions[$name]);
				unset($this->transactions[$name]);
				$this->DbDebug($name.' => ROLLBACK','RollbackTran',NULL,$log);
			}//if(is_resource($this->transactions[$name]))
		}//if(array_key_exists($name,$this->transactions) && is_resource($this->transactions[$name]))
		return $result;
	}//END public function FirebirdSqlRollbackTran
    /**
     * Commits a firebird transaction
     * @param  string $name Transaction name
     * @param bool    $log
     * @param bool    $preserve
     * @return bool Returns TRUE on success or FALSE otherwise
     */
	public function FirebirdSqlCommitTran($name,$log = FALSE,$preserve = FALSE) {
		$result = FALSE;
		if(is_null($name)) {
			if($preserve) {
				$result = ibase_commit_ret($this->connection);
				$this->DbDebug('!DEFAULT! => COMMIT (retain)','CommitTran',NULL,$log);
			} else {
				$result = ibase_commit($this->connection);
				if($log) { $this->DbDebug('!DEFAULT! => COMMIT','CommitTran',NULL,$log); }
			}//if($preserve)
		} elseif(is_string($name) && array_key_exists($name,$this->transactions)) {
			if(is_resource($this->transactions[$name])) {
				if($preserve) {
					$result = ibase_commit_ret($this->transactions[$name]);
					$this->DbDebug($name.' => COMMIT (retain)','CommitTran',NULL,$log);
				} else {
					$result = ibase_commit($this->transactions[$name]);
					unset($this->transactions[$name]);
					$this->DbDebug($name.' => COMMIT','CommitTran',NULL,$log);
				}//if($preserve)
			}//if(is_resource($this->transactions[$name]))
		}//if(array_key_exists($name,$this->transactions) && is_resource($this->transactions[$name]))
		return $result;
	}//END public function FirebirdSqlCommitTran
    /**
     * Add blob parameter to query
     * @param  string $param Parameter string value
     * @param  string|null    $transaction
     * @return mixed Returns the blob ID for write, TRUE on success for read or FALSE otherwise
     */
	public function AddBlobParam($param,?string $transaction = NULL) {
		if(!is_string($param)) { return NULL; }
		try {
			if(is_resource($transaction)) {
				$blob_handle = ibase_blob_create($transaction);
			} else {
				$blob_handle = ibase_blob_create($this->connection);
			}//if(is_resource($transaction))
			if($blob_handle===FALSE) { throw new \Exception('Invalid blob handle!'); }
			ibase_blob_add($blob_handle,$param);
			$blob_id = ibase_blob_close($blob_handle);
		} catch(\Exception $e) {
			NApp::Elog($e);
			$blob_id = NULL;
		}//END try
		return $blob_id;
	}//END public function AddBlobParam
	/**
     * @param mixed $sort
     * @return string
     */
    private function GetOrderBy($sort): string {
        $result = '';
		if(is_array($sort)) {
			foreach ($sort as $k=>$v) { $result .= ($result ? ' ,' : ' ').self::ENCLOSING_START_SYMBOL.strtoupper(trim($k,' "')).self::ENCLOSING_END_SYMBOL.' '.strtoupper($v); }
			$result = strlen(trim($result)) ? ' ORDER BY'.$result.' ' : '';
		} elseif(strlen($sort)) {
			$result = " ORDER BY {$sort} ASC ";
		}//if(is_array($sort))
		return $result;
    }//END private function GetOrderBy
    /**
     * @param $firstrow
     * @param $lastrow
     * @return string
     */
    private function GetOffsetAndLimit($firstrow,$lastrow): string {
        $result = '';
        if(is_numeric($firstrow) && $firstrow>0) {
            if(is_numeric($lastrow) && $lastrow>0) {
                $result .= ' ROWS '.$firstrow.' TO '.$lastrow;
            } else {
                $result .= ' ROWS '.$firstrow;
            }//if(is_numeric($lastrow) && $lastrow>0)
        }//if(is_numeric($firstrow) && $firstrow>0)
		return $result;
    }//END private function GetOffsetAndLimit
    /**
     * @param array $condition
     * @return string
     * @throws \Exception
     */
    private function GetFilterCondition(array $condition): string {
        $cond = get_array_value($condition,'condition',NULL,'is_notempty_string');
        if($cond) { return $cond; }
        $field = get_array_value($condition,'field',NULL,'is_notempty_string');
        if(!$field) { return ''; }
        $dataType = get_array_value($condition,'data_type',NULL,'is_notempty_string');
        $conditionType = get_array_value($condition,'condition_type','==','is_notempty_string');
        $conditionString = NULL;
        $filterValue = NULL;
        switch(strtolower($conditionType)) {
            case 'like':
            case 'notlike':
                $conditionString = strtolower($conditionType)=='like' ? 'LIKE' : 'NOT LIKE';
                $filterValue = $this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                if(isset($filterValue)) { $filterValue = "'%".$filterValue."%'"; }
                break;
            case '==':
            case '<>':
                $conditionString = $conditionType=='==' ? '=' : $conditionType;
                $nullConditionString = 'IS '.($conditionType=='<>' ? ' NOT ' : '').'NULL';
                if(strtolower($dataType)=='string') {
                    $filterValue = $this->EscapeString(get_array_value($condition,'value',NULL,'?is_string'));
                    $filterValue = isset($filterValue) ? "'".$filterValue."'" : $nullConditionString;
                } else {
                    $filterValue = $this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                    if(in_array(strtolower($dataType),['date','date_obj'])) {
                        $filterValueS = Validator::ConvertDateTimeToDbFormat($filterValue,NULL,0);
                        $filterValueE = Validator::ConvertDateTimeToDbFormat($filterValue,NULL,1);
                        $conditionString = ($conditionType=='<>' ? 'NOT ' : '').'BETWEEN';
                        $filterValue = "'".$filterValueS."' AND '".$filterValueE."'";
                    } elseif(in_array(strtolower($dataType),['datetime','datetime_obj'])) {
                        $filterValue = Validator::ConvertDateTimeToDbFormat($filterValue);
                        $filterValue = isset($filterValue) ? "'".$filterValue."'" : $nullConditionString;
                    } else {
                        $filterValue = isset($filterValue) ? "'".$filterValue."'" : $nullConditionString;
                    }//if(in_array(strtolower($dataType),['date','datetime','datetime_obj']))
                }//if(strtolower($dataType)=='string')
                break;
            case '<=':
            case '>=':
                $conditionString = $conditionType;
                $filterValue = $this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                if(in_array(strtolower($dataType),['date','date_obj','datetime','datetime_obj'])) {
                    if(in_array(strtolower($dataType),['date','date_obj'])) {
                        $daypart = ($conditionType=='<=' ? 1 : 0);
                    } else {
                        $daypart = NULL;
                    }//if(in_array(strtolower($dataType),['date','date_obj']))
                    $filterValue = Validator::ConvertDateTimeToDbFormat($filterValue,NULL,$daypart);
                }//if(in_array(strtolower($dataType),['date','datetime','datetime_obj']))
                if(isset($filterValue)) { $filterValue = "'".$filterValue."'"; }
                break;
            case '><':
                $conditionString = 'BETWEEN';
                $filterValueS = $this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                $filterValueE = $this->EscapeString(get_array_value($condition,'svalue',NULL,'?is_notempty_string'));
                if(in_array(strtolower($dataType),['date','date_obj','datetime','datetime_obj'])) {
                    if(in_array(strtolower($dataType),['date','date_obj'])) {
                        $daypart = 0;
                        $sdaypart = 1;
                    } else {
                        $daypart = NULL;
                        $sdaypart = NULL;
                    }//if(in_array(strtolower($dataType),['date','date_obj']))
                    $filterValueS = Validator::ConvertDateTimeToDbFormat($filterValueS,NULL,$daypart);
                    $filterValueE = Validator::ConvertDateTimeToDbFormat($filterValueE,NULL,$sdaypart);
                }//if(in_array(strtolower($dataType),['date','datetime','datetime_obj']))
                if(isset($filterValueS) && isset($filterValueE)) { $filterValue = "'".$filterValueS."' AND '".$filterValueE."'"; }
                break;
            case 'is':
            case 'isnot':
                $conditionString = strtolower($conditionType)=='like' ? 'IS' : 'IS NOT';
                $filterValue = $this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                $filterValue = isset($filterValue) ? "'".$filterValue."'" : 'NULL';
                break;
		}//END switch
        if(!$conditionString || !$filterValue) { return ''; }
		$result = ' '.self::ENCLOSING_START_SYMBOL.strtoupper($field).self::ENCLOSING_END_SYMBOL;
		$result .= ' '.$conditionString.' '.$filterValue;
        return $result;
	}//END private function GetFilterCondition
    /**
     * Prepares the query string for execution
     * @param  string $query The query string (by reference)
     * @param  array  $params An array of parameters
     * to be passed to the query/stored procedure
     * @param  array  $out_params An array of output params
     * @param  string $type Request type: select, count, execute (default 'select')
     * @param  int    $firstrow Integer to limit number of returned rows
     * (if used with 'last_row' represents the offset of the returned rows)
     * @param  int    $lastrow Integer to limit number of returned rows
     * (to be used only with 'first_row')
     * @param  array  $sort An array of fields to compose ORDER BY clause
     * @param  array  $filters An array of condition to be applied in WHERE clause
     * @param null    $raw_query
     * @param null    $bind_params
     * @param null    $transaction
     * @return void
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     */
	public function FirebirdSqlPrepareQuery(&$query,$params = [],$out_params = [],$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,&$raw_query = NULL,&$bind_params = NULL,$transaction = NULL) {
		if(is_array($params) && count($params)) {
		    foreach($params as $k=>$p) {
		        if($p instanceof \DateTime) {
		            $query = str_replace('{{'.$k.'}}',$p->format('Y-m-d H:i:s'),$query);
                } else {
                    $p = $this->EscapeString($p);
                    if(strlen($p)>4000) {
                        $bpid = $this->AddBlobParam($p,$transaction);
                        if(!isset($bpid) || (!is_string($bpid) && !strlen($bpid))) { throw new AppException('Invalid query parameter!',E_USER_ERROR); }
                        if(!is_array($bind_params)) { $bind_params = []; }
                        $bind_params[] = $bpid;
                        $query = str_replace('{{'.$k.'}}','?',$query);
                    } else {
                        $query = str_replace('{{'.$k.'}}',$p,$query);
                    }//if(strlen($v)>4000)
                }//if($p instanceof \DateTime)
            }//END foreach
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
			foreach($filters as $v) {
				if(is_array($v)) {
				    $sep = strtoupper(get_array_value($v,'logical_separator','AND','is_notempty_string'));
                    $filter_str .= ($filter_str ? ' '.strtoupper($sep).' ' : ' ').'('.$this->GetFilterCondition($v).')';
				} else {
					$filter_str .= ($filter_str ? ' AND ' : ' ').'('.$v.')';
				}//if(is_array($v))
			}//END foreach
			$filter_str = strlen(trim($filter_str)) ? $filter_prefix.$filter_str.$filter_sufix : '';
		} elseif(is_string($filters) && strlen($filters)) {
			$filter_str = " {$filters} ";
		}//if(is_array($filters))
		$query .= $filter_str;
		$raw_query = $query;
		if($type=='count') { return; }
		$query .= $this->GetOrderBy($sort);
		$query .= $this->GetOffsetAndLimit($firstrow,$lastrow);
	}//END public function FirebirdSqlPrepareQuery
	/**
	 * Executes a query against the database
	 * @param  string $query The query string
	 * @param  array  $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array  $out_params An array of output params
	 * @param  string $tran_name Name of transaction in which the query will run
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int    $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
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
	public function FirebirdSqlExecuteQuery($query,$params = [],&$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = FALSE,$results_keys_case = NULL,$custom_tran_params = NULL) {
		$time = microtime(TRUE);
		$transaction = NULL;
		if(is_string($tran_name) && strlen($tran_name)) {
			try {
				$transaction = $this->FirebirdSqlGetTran($tran_name,$log,FALSE,$custom_tran_params);
				if(is_null($transaction)) { throw new AppException('Invalid transaction: '.$tran_name); }
			} catch(\Exception $e) {
				throw new AppException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
			}//END try
		} else {
			$tran_name = NULL;
		}//if(is_string($tran_name) && strlen($tran_name))
		$raw_query = NULL;
		$bind_params = NULL;
		$this->FirebirdSqlPrepareQuery($query,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters,$raw_query,$bind_params,$transaction);
		if(!is_array($out_params)) { $out_params = []; }
		$out_params['rawsqlqry'] = $raw_query;
		$out_params['sqlqry'] = $query;
		$final_result = NULL;
		try {
			if(is_resource($transaction)) {
				$pqry = ibase_prepare($this->connection,$transaction,$query);
			} else {
				$pqry = ibase_prepare($this->connection,$query);
			}//if(is_resource($transaction))
			if(!is_array($bind_params) || !count($bind_params)) {
				$result = ibase_execute($pqry);
			} else {
				array_unshift($bind_params,$pqry);
				$result = call_user_func_array('ibase_execute',$bind_params);
			}//if(!is_array($bind_params) || !count($bind_params))
		} catch(\Exception $e) {
			$this->FirebirdSqlRollbackTran($tran_name,$log);
			throw new AppException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
		if(strlen(ibase_errmsg())) { // || $result===FALSE
			$iberror = ibase_errmsg();
			$iberrorcode = ibase_errcode();
			$this->FirebirdSqlRollbackTran($tran_name,$log);
			throw new AppException("FAILED QUERY: #ErrorCode:{$iberrorcode}# {$iberror} in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',$iberrorcode);
		}//if(strlen(ibase_errmsg()))
		try {
			if(is_resource($result)){
				while($data = ibase_fetch_assoc($result,IBASE_TEXT)) { $final_result[] = $data; }
				ibase_free_result($result);
			} else {
				$final_result = $result;
			}//if(is_resource($result))
		} catch(\Exception $e) {
			$this->FirebirdSqlRollbackTran($tran_name,$log);
			throw new AppException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
		if(is_null($tran_name)) { $this->FirebirdSqlCommitTran(NULL,FALSE); }
		$this->DbDebug($query,'Query',$time,$log);
		return change_array_keys_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->resultsKeysCase));
	}//END public function FirebirdSqlExecuteQuery
    /**
     * Prepares the command string to be executed
     * @param  string $procedure The name of the stored procedure
     * @param  array  $params An array of parameters
     * to be passed to the query/stored procedure
     * @param  array  $out_params An array of output params
     * @param  string $type Request type: select, count, execute (default 'select')
     * @param  int    $firstrow Integer to limit number of returned rows
     * (if used with 'last_row' represents the offset of the returned rows)
     * @param  int    $lastrow Integer to limit number of returned rows
     * (to be used only with 'first_row')
     * @param  array  $sort An array of fields to compose ORDER BY clause
     * @param  array  $filters An array of condition to be applied in WHERE clause
     * @param null    $raw_query
     * @param null    $bind_params
     * @param null    $transaction
     * @return string|resource Returns processed command string or the statement resource
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     */
	protected function FirebirdSqlPrepareProcedureStatement($procedure,$params = [],&$out_params = [],$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,&$raw_query = NULL,&$bind_params = NULL,$transaction = NULL) {
		if(is_array($params)) {
			if(count($params)) {
				$parameters = '';
				foreach($params as $p) {
				    if($p instanceof \DateTime) {
				        $parameters .= (strlen($parameters) ? ',' : '(')."'".$p->format('Y-m-d H:i:s')."'";
				    } else {
				        $p = $this->EscapeString($p);
				        if(strlen($p)>4000) {
                            $bpid = $this->AddBlobParam($p,$transaction);
                            if(!isset($bpid) || (!is_string($bpid) && !strlen($bpid))) { throw new AppException('Invalid query parameter!',E_USER_ERROR); }
                            if(!is_array($bind_params)) { $bind_params = []; }
                            $bind_params[] = $bpid;
                            $parameters .= (strlen($parameters) ? ',?' : '(?');
                        } else {
                            $parameters .= (strlen($parameters) ? ',' : '(').(is_null($p) ? 'NULL' : "'{$p}'");
                        }//if(strlen($p)>4000)
				    }//if($p instanceof \DateTime)
				}//END foreach
				$parameters .= ')';
			} else {
				$parameters = '';
			}//if(count($params))
		} else {
			$parameters = $params;
		}//if(is_array($params))
		$filter_str = '';
		if(strtolower($type)=='execute') {
			$raw_query = $procedure.$parameters;
		} else {
            if(is_array($filters)) {
                $filter_prefix = ' WHERE ';
                $filter_sufix = ' ';
                foreach($filters as $v) {
                    if(is_array($v)) {
                        $sep = strtoupper(get_array_value($v,'logical_separator','AND','is_notempty_string'));
                        $filter_str .= ($filter_str ? ' '.strtoupper($sep).' ' : ' ').'('.$this->GetFilterCondition($v).')';
                    } else {
                        $filter_str .= ($filter_str ? ' AND ' : ' ').'('.$v.')';
                    }//if(is_array($v))
                }//END foreach
                $filter_str = strlen(trim($filter_str)) ? $filter_prefix.$filter_str.$filter_sufix : '';
            } elseif(is_string($filters) && strlen($filters)) {
                $filter_str = strtoupper(substr(trim($filters),0,5))=='WHERE' ? " {$filters} " : " WHERE {$filters} ";
            }//if(is_array($filters))
			$raw_query = $procedure.$parameters.$filter_str;
		}//if(strtolower($type)=='execute')
		switch(strtolower($type)) {
			case 'execute':
				$query = 'EXECUTE PROCEDURE '.$procedure.$parameters;
				break;
			case 'count':
				$query = 'SELECT COUNT (1) FROM '.$procedure.$parameters.$filter_str;
				break;
			case 'select':
			default:
			    $query = 'SELECT * FROM '.$procedure.$parameters.$filter_str;
			    $query .= $this->GetOrderBy($sort);
		        $query .= $this->GetOffsetAndLimit($firstrow,$lastrow);
				break;
		}//END switch
		return $query;
	}//END protected function FirebirdSqlPrepareProcedureStatement
	/**
	 * Executs a stored procedure against the database
	 * @param  string $procedure The name of the stored procedure
	 * @param  array  $params An array of parameters
	 * to be passed to the query/stored procedure
	 * @param  array  $out_params An array of output params
	 * @param  string $tran_name Name of transaction in which the query will run
	 * @param  string $type Request type: select, count, execute (default 'select')
	 * @param  int    $firstrow Integer to limit number of returned rows
	 * (if used with 'last_row' represents the offset of the returned rows)
	 * @param  int    $lastrow Integer to limit number of returned rows
	 * (to be used only with 'first_row')
	 * @param  array  $sort An array of fields to compose ORDER BY clause
	 * @param  array  $filters An array of condition to be applied in WHERE clause
	 * @param bool    $log
	 * @param null    $results_keys_case
	 * @param null    $custom_tran_params
	 * @return array|bool Returns database request result
	 * @throws \NETopes\Core\AppException
	 */
	public function FirebirdSqlExecuteProcedure($procedure,$params = [],&$out_params = [],$tran_name = NULL,$type = '',$firstrow = NULL,$lastrow = NULL,$sort = NULL,$filters = NULL,$log = FALSE,$results_keys_case = NULL,$custom_tran_params = NULL) {
		$time = microtime(TRUE);
		$transaction = NULL;
		if(is_string($tran_name) && strlen($tran_name)) {
			try {
				$transaction = $this->FirebirdSqlGetTran($tran_name,$log,FALSE,$custom_tran_params);
				if(is_null($transaction)) { throw new AppException('Invalid transaction: '.$tran_name); }
			} catch(\Exception $e) {
				throw new AppException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: {$procedure}",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
			}//END try
		} else {
			$tran_name = NULL;
		}//if(is_string($tran_name) && strlen($tran_name))
		if(!is_array($out_params)) { $out_params = []; }
		$sql_params = NULL;
		$raw_query = NULL;
		$bind_params = NULL;
		$query = $this->FirebirdSqlPrepareProcedureStatement($procedure,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters,$raw_query,$bind_params,$transaction);
		$out_params['rawsqlqry'] = $raw_query;
		$out_params['sqlqry'] = $query;
		//if($this->debug2file) { NApp::Write2LogFile('Query: '.$query,'debug'); }
		$final_result = NULL;
		try {
			if(is_resource($transaction)) {
				$pqry = ibase_prepare($this->connection,$transaction,$query);
			} else {
				$pqry = ibase_prepare($this->connection,$query);
			}//if(is_resource($transaction))
			if(!is_array($bind_params) || !count($bind_params)) {
				$result = ibase_execute($pqry);
			} else {
				array_unshift($bind_params,$pqry);
				$result = call_user_func_array('ibase_execute',$bind_params);
			}//if(!is_array($bind_params) || !count($bind_params))
		} catch(\Exception $e) {
			$this->FirebirdSqlRollbackTran($tran_name,$log);
			throw new AppException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
		if(strlen(ibase_errmsg())) { //|| $result===FALSE) {
			$iberror = ibase_errmsg();
			$iberrorcode = ibase_errcode();
			$this->FirebirdSqlRollbackTran($tran_name,$log);
			throw new AppException("FAILED EXECUTE PROCEDURE: #ErrorCode:{$iberrorcode}# {$iberror} in statement: {$query}",E_ERROR,1,__FILE__,__LINE__,'firebird',$iberrorcode);
		}//if(strlen(ibase_errmsg()))
		try {
			if(is_resource($result)) {
				while($data = ibase_fetch_assoc($result,IBASE_TEXT)) { $final_result[] = $data; }
				ibase_free_result($result);
			} else {
				$final_result = $result;
			}//if(is_resource($result))
		} catch(\Exception $e) {
			$this->FirebirdSqlRollbackTran($tran_name,$log);
			throw new AppException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: $query",E_ERROR,1,__FILE__,__LINE__,'firebird',0);
		}//END try
		if(is_null($tran_name)) { $this->FirebirdSqlCommitTran(NULL,FALSE); }
		$this->DbDebug($query,'Query',$time,$log);
		return change_array_keys_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->resultsKeysCase));
	}//END public function FirebirdSqlExecuteProcedure
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
	public function FirebirdSqlExecuteMethod($method,$property = NULL,$params = [],$extra_params = [],$log = TRUE) {
		throw new AppException("FAILED EXECUTE METHOD: #ErrorCode:N/A# Execute method not implemented for FirebirdSQL !!! in statement: ".$method.trim('->'.$property,'->'),E_ERROR,1,__FILE__,__LINE__,'firebird',0);
	}//END public function FirebirdSqlExecuteMethod
	/**
	 * Escapes single quote character from a string
	 * @param  string|array $param String to be escaped or
	 * an array of strings
	 * @return string|array Returns the escaped string or array
	 */
	public static function FirebirdSqlEscapeString($param) {
		if(is_array($param)) {
			$result = [];
			foreach($param as $k=>$v) { $result[$k] = static::FirebirdSqlEscapeString($v); }
		    return $result;
		}//if(is_array($param))
		if(is_scalar($param)) { return str_replace("'","''",$param); }
		return NULL;
	}//END public static function FirebirdSqlEscapeString
	/**
	 * Escapes single quote character from a string
	 * @param  string|array $param String to be escaped or
	 * an array of strings
	 * @return string|array Returns the escaped string or array
	 */
	public function EscapeString($param) {
		return static::FirebirdSqlEscapeString($param);
	}//END public function EscapeString
}//END class FirebirdSqlDbAdapter extends SqlDataAdapter