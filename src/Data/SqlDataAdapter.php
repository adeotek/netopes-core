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
/** @noinspection PhpMissingParentConstructorInspection */
namespace NETopes\Core\Data;
use Exception;
use NETopes\Core\AppException;
use PDO;
use PdoException;

/**
 * DbAdapter is the base abstract class for all database adapters
 * All database adapters must extend this class.
 *
 * @package  NETopes\Database
 */
abstract class SqlDataAdapter extends DataAdapter {
    /**
     * Database class constructor
     *
     * @param array $connection Database connection array
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected function __construct($connection) {
        if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name']) {
            throw new AppException('Incorrect database connection',E_ERROR,1);
        }
        $this->dbName=$connection['db_name'];
        $this->dbType=$connection['db_type'];
        $this->resultsKeysCase=get_array_value($connection,'results_keys_case',$this->resultsKeysCase,'is_integer');
        if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_mssql')) {
            $this->usePdo=TRUE;
            $this->connection=$this->SetPdoConnection($connection);
        } else {
            $this->Init($connection);
        }//if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_mssql'))
    }//END protected function __construct

    /**
     * Sets database connection to new connection
     *
     * @param array $connection Database connection array
     * @return bool Returns TRUE on success or FALSE on failure
     * @throws \NETopes\Core\AppException
     */
    public function SetConnection($connection) {
        if(!is_array($connection) || count($connection)==0 || !array_key_exists('db_server',$connection) || !$connection['db_server'] || !array_key_exists('db_user',$connection) || !$connection['db_user'] || !array_key_exists('db_name',$connection) || !$connection['db_name'] || !array_key_exists('db_type',$connection) || !$connection['db_type']) {
            return FALSE;
        }
        $this->dbName=$connection['db_name'];
        $this->dbType=$connection['db_type'];
        if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_firebird')) {
            $this->usePdo=TRUE;
            $this->connection=$this->SetPdoConnection($connection);
        } else {
            $this->Init($connection);
        }//if(array_key_exists('use_pdo',$connection) && $connection['use_pdo'] && extension_loaded('pdo_mssql'))
        return TRUE;
    }//END public function SetConnection

    /**
     * Close current database connection
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function CloseConnection() {
        if($this->usePdo || !method_exists($this,$this->dbType.'CloseConnection')) {
            $this->connection=NULL;
            return TRUE;
        }//if($this->usePdo || !method_exists($this,$this->dbType.'CloseConnection'))
        $method=$this->dbType.'CloseConnection';
        return $this::$method();
    }//END public function CloseConnection

    /**
     * Executes a method of the database connection object
     *
     * @param string $method The method name
     * @param mixed  $params The method params if any
     * @return mixed Returns the result of the executed method
     */
    public function ExecuteConnectionMethod($method,$params=[]) {
        return call_user_func_array([$this->connection,$method],(is_array($params) ? $params : []));
    }//END public function ExecuteConnectionMethod

    /**
     * Sets the connection to a new pdo connection
     *
     * @param array $connection Database connection array
     * @return PDO|null Returns PDO object on success or NULL on failure
     * @throws \NETopes\Core\AppException
     */
    protected function SetPdoConnection($connection) {
        if($this->dbType=='MongoDb') {
            throw new AppException('MongoDB PDO not implemented!',E_ERROR,1,NULL,NULL,'pdo',0);
        }
        $conn=NULL;
        try {
            $conn_str=strtolower($connection['db_type']).':dbname='.$connection['db_server'].':'.$connection['db_name'].';charset=UTF8';
            $conn=new PDO($conn_str,$connection['db_user'],(array_key_exists('db_password',$connection) ? $connection['db_password'] : ''));
            $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            throw new AppException("PDO failed to open connection: ".$connection['db_type'].'>>'.$connection['db_name'].' ('.$e->getMessage().")",E_ERROR,1,NULL,NULL,'pdo',0);
        }//END try
        return $conn;
    }//END protected function SetPdoConnection

    /**
     * Set database connection global variables
     *
     * @param array $params Key-value array of variables to be set
     * @return bool  Returns TRUE on success or FALSE otherwise
     */
    public function SetGlobalVariables($params=NULL) {
        if($this->usePdo) {
            return FALSE;
        }
        $method=$this->dbType.str_replace(__CLASS__.'::','',__METHOD__);
        return $this::$method($params);
    }//END public function SetGlobalVariables

    /**
     * Begins a database transaction
     *
     * @param string $name      Transaction name
     * @param bool   $log
     * @param bool   $overwrite Flag for overwriting the transaction
     *                          if exists (defaul value FALSE)
     * @param null   $customTranParams
     * @return void
     */
    public function BeginTran(&$name=NULL,$log=FALSE,$overwrite=TRUE,$customTranParams=NULL) {
        $method=($this->usePdo ? 'Pdo' : $this->dbType).str_replace(__CLASS__.'::','',__METHOD__);
        return $this::$method($name,$log,$overwrite,$customTranParams);
    }//END public function BeginTran

    /**
     * Rolls back a database transaction
     *
     * @param string $name Transaction name
     * @return bool Returns TRUE on success or FALSE otherwise
     */
    public function RollbackTran($name=NULL,$log=FALSE) {
        $method=($this->usePdo ? 'Pdo' : $this->dbType).str_replace(__CLASS__.'::','',__METHOD__);
        return $this::$method($name,$log);
    }//END public function RollbackTran

    /**
     * Commits a database transaction
     *
     * @param string $name Transaction name
     * @param bool   $log
     * @param bool   $preserve
     * @return bool Returns TRUE on success or FALSE otherwise
     */
    public function CommitTran($name=NULL,$log=FALSE,$preserve=FALSE) {
        $method=($this->usePdo ? 'Pdo' : $this->dbType).str_replace(__CLASS__.'::','',__METHOD__);
        return $this::$method($name,$log,$preserve);
    }//END public function CommitTran

    /**
     * Executes a query against the database
     *
     * @param string $query        The query string
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extraParams  An array of parameters that may contain:
     *                             * 'transaction'= name of transaction in which the query will run
     *                             * 'type' = request type: select, count, execute (default 'select')
     *                             * 'first_row' = integer to limit number of returned rows
     *                             (if used with 'last_row' represents the offset of the returned rows)
     *                             * 'last_row' = integer to limit number of returned rows
     *                             (to be used only with 'first_row')
     *                             * 'sort' = an array of fields to compose ORDER BY clause
     *                             * 'filters' = an array of condition to be applied in WHERE clause
     *                             * 'out_params' = an array of output params
     * @return array|bool Returns database request result
     */
    public function ExecuteQuery($query,$params=[],&$extraParams=[]) {
        $tranName=get_array_value($extraParams,'transaction',NULL,'is_notempty_string');
        $type=strtolower(get_array_value($extraParams,'type','','is_notempty_string'));
        $firstRow=get_array_value($extraParams,'first_row',NULL,'is_not0_numeric');
        $lastRow=get_array_value($extraParams,'last_row',NULL,'is_not0_numeric');
        $sort=get_array_value($extraParams,'sort',NULL,'isset');
        $filters=get_array_value($extraParams,'filters',NULL,'is_notempty_array');
        $outParams=get_array_value($extraParams,'out_params',[],'is_array');
        $log=get_array_value($extraParams,'log',FALSE,'bool');
        $method=($this->usePdo ? 'Pdo' : $this->dbType).str_replace(__CLASS__.'::','',__METHOD__);
        $resultsKeysCase=get_array_value($extraParams,'results_keys_case',NULL,'is_integer');
        $customTranParams=get_array_value($extraParams,'custom_tran_params',NULL,'isset');
        return $this::$method($query,$params,$outParams,$tranName,$type,$firstRow,$lastRow,$sort,$filters,$log,$resultsKeysCase,$customTranParams);
    }//END public function ExecuteQuery

    /**
     * Executes a stored procedure against the database
     *
     * @param string $procedure    The name of the stored procedure
     * @param array  $params       An array of parameters
     *                             to be passed to the query/stored procedure
     * @param array  $extraParams  An array of parameters that may contain:
     *                             * 'transaction'= name of transaction in which the query will run
     *                             * 'type' = request type: select, count, execute (default 'select')
     *                             * 'first_row' = integer to limit number of returned rows
     *                             (if used with 'last_row' represents the offset of the returned rows)
     *                             * 'last_row' = integer to limit number of returned rows
     *                             (to be used only with 'first_row')
     *                             * 'sort' = an array of fields to compose ORDER BY clause
     *                             * 'filters' = an array of condition to be applied in WHERE clause
     *                             * 'out_params' = an array of output params
     * @return array|bool Returns database request result
     */
    public function ExecuteProcedure($procedure,$params=[],&$extraParams=[]) {
        $tranName=get_array_value($extraParams,'transaction',NULL,'is_notempty_string');
        $type=strtolower(get_array_value($extraParams,'type','','is_notempty_string'));
        $firstRow=get_array_value($extraParams,'first_row',NULL,'is_not0_numeric');
        $lastRow=get_array_value($extraParams,'last_row',NULL,'is_not0_numeric');
        $sort=get_array_value($extraParams,'sort',NULL,'is_notempty_array');
        $filters=get_array_value($extraParams,'filters',NULL,'is_notempty_array');
        $outParams=get_array_value($extraParams,'out_params',[],'is_array');
        $log=get_array_value($extraParams,'log',FALSE,'bool');
        $method=($this->usePdo ? 'Pdo' : $this->dbType).str_replace(__CLASS__.'::','',__METHOD__);
        $resultsKeysCase=get_array_value($extraParams,'results_keys_case',NULL,'is_numeric');
        $customTranParams=get_array_value($extraParams,'custom_tran_params',NULL,'isset');
        $result=$this::$method($procedure,$params,$outParams,$tranName,$type,$firstRow,$lastRow,$sort,$filters,$log,$resultsKeysCase,$customTranParams);
        if($outParams) {
            if(!is_array($extraParams)) {
                $extraParams=[];
            }
            $extraParams['out_params']=$outParams;
        }//if($outParams)
        return $result;
    }//END public function ExecuteProcedure

    /**
     * Executes a method of the database object or a sub-object of it
     *
     * @param string $method       The name of the method to be executed
     * @param string $property     The name of the sub-object containing the method
     *                             to be executed
     * @param array  $params       An array of parameters
     *                             to be passed to the method
     * @param array  $extraParams  An array of extra parameters
     *                             to be passed to the invoking method
     * @return mixed Returns database method result
     */
    public function ExecuteMethod($method,$property=NULL,$params=[],$extraParams=[]) {
        $log=get_array_value($extraParams,'log',FALSE,'bool');
        $cmethod=$this->dbType.str_replace(__CLASS__.'::','',__METHOD__);
        return $this::$cmethod($method,$property,$params,$extraParams,$log);
    }//END public function ExecuteQuery

    /**
     * @param      $name
     * @param bool $log
     * @param bool $overwrite
     * @return resource|null
     * @throws \NETopes\Core\AppException
     */
    public function PdoBeginTran($name,$log=TRUE,$overwrite=TRUE) {
        if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
            if($overwrite===TRUE) {
                try {
                    $this->transactions[$name]=new PDO($this->pdo_connection_string,$this->pdo_connection_user,$this->pdo_connection_password);
                    $this->transactions[$name]->beginTransaction();
                    return $this->transactions[$name];
                } catch(PDOException $e) {
                    throw new AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
                }//try
            }//if($overwrite===TRUE)
            return NULL;
        }//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
        try {
            $this->transactions[$name]=new PDO($this->pdo_connection_string,$this->pdo_connection_user,$this->pdo_connection_password);
            $this->transactions[$name]->beginTransaction();
            return $this->transactions[$name];
        } catch(PDOException $e) {
            throw new AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
        }//try
    }//END public function PdoBeginTran

    /**
     * @param      $name
     * @param bool $log
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public function PdoRollbackTran($name,$log=FALSE) {
        if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
            try {
                $this->transactions[$name]->rollBack();
                unset($this->transactions[$name]);
                return TRUE;
            } catch(PDOException $e) {
                throw new AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
            }//try
        }//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
        return FALSE;
    }//END public function PdoRollbackTran

    /**
     * @param      $name
     * @param bool $log
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public function PdoCommitTran($name,$log=FALSE) {
        if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
            try {
                $this->transactions[$name]->commit();
                unset($this->transactions[$name]);
                return TRUE;
            } catch(PDOException $e) {
                throw new AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
            }//try
        }//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
        return FALSE;
    }//END public function PdoCommitTran

    /**
     * @param        $query
     * @param array  $params
     * @param array  $outParams
     * @param null   $tranName
     * @param string $type
     * @param null   $firstRow
     * @param null   $lastRow
     * @param null   $sort
     * @param bool   $log
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public function PdoExecuteQuery($query,$params=[],$outParams=[],$tranName=NULL,$type='',$firstRow=NULL,$lastRow=NULL,$sort=NULL,$log=FALSE) {
        // $time=microtime(TRUE);
        $trans=FALSE;
        $method=$this->dbType.'PrepareQuery';
        self::$method($query,$params,$outParams,$type,$firstRow,$lastRow,$sort);
        $conn=$this->connection;
        if(strlen($tranName)>0) {
            $trans=TRUE;
            if(array_key_exists($tranName,$this->transactions) && isset($this->transactions[$tranName])) {
                $conn=$this->transactions[$tranName];
            } else {
                throw new AppException("FAILED QUERY: NULL database transaction in statement: ".$query,E_ERROR,1,NULL,NULL,'pdo',0);
            }//if(array_key_exists($tranName,$this->transactions))
        }//if(strlen($tranName)>0)
        $final_result=NULL;
        try {
            $result=$conn->query($query);
            if(is_object($result)) {
                $final_result=$result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $final_result=$result;
            }//if(is_object($result))
        } catch(Exception $e) {
            if($trans) {
                $this->PdoRollbackTran($tranName);
            }
            throw new AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode());
        }//try
        return change_array_keys_case($final_result,TRUE);
    }//END public function PdoExecuteQuery

    /**
     * @param        $procedure
     * @param array  $params
     * @param array  $outParams
     * @param null   $tranName
     * @param string $type
     * @param null   $firstRow
     * @param null   $lastRow
     * @param null   $sort
     * @param null   $filters
     * @param bool   $log
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public function PdoExecuteProcedure($procedure,$params=[],$outParams=[],$tranName=NULL,$type='',$firstRow=NULL,$lastRow=NULL,$sort=NULL,$filters=NULL,$log=FALSE) {
        // $time=microtime(TRUE);
        $trans=FALSE;
        $transaction=NULL;
        $method=$this->dbType.'PrepareProcedureStatement';
        $query=self::$method($procedure,$params,$outParams,$type,$firstRow,$lastRow,$sort,$filters);
        $conn=$this->connection;
        if(strlen($tranName)>0) {
            $trans=TRUE;
            if(array_key_exists($tranName,$this->transactions)) {
                $transaction=$tranName;
            } else {
                throw new AppException("FAILED QUERY: NULL database transaction in statement: ".$query,E_ERROR,1,NULL,NULL,'pdo',0);
            }//if(array_key_exists($tranName,$this->transactions))
        }//if(strlen($tranName)>0)
        $final_result=NULL;
        try {
            $result=$conn->query($query);
            if(is_object($result)) {
                $final_result=$result->fetchAll(PDO::FETCH_ASSOC);
            } elseif(is_int($result) || is_bool($result)) {
                $final_result=$result;
            }//if($withselect)
        } catch(PdoException $e) {
            if($trans) {
                $this->PdoRollbackTran($transaction);
            }//if($trans)
            throw new AppException($e->getMessage(),E_ERROR,1,$e->getFile(),$e->getLine(),'pdo',$e->getCode(),$e->errorInfo);
        }//END try
        return change_array_keys_case($final_result,TRUE);
    }//END public function PdoExecuteProcedure
}//END abstract class SqlDataAdapter extends DataAdapter