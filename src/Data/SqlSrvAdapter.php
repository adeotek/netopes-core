<?php
/**
 * SqlSrv (MS SQL) database implementation class file
 * This file contains the implementing class for MS SQL database.
 *
 * @package    NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use DateTime;
use Exception;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\Validators\Validator;

/**
 * SqlSrvDatabase is implementing the MS SQL database
 * This class contains all methods for interacting with MS SQL database.
 *
 * @package  NETopes\Database
 */
class SqlSrvAdapter extends SqlDataAdapter {
    /**
     * @const string Objects names enclosing start symbol
     */
    const ENCLOSING_START_SYMBOL='[';
    /**
     * @const string Objects names enclosing end symbol
     */
    const ENCLOSING_END_SYMBOL=']';
    /**
     * @const string Key of filters grouping field
     */
    const FILTERS_GROUP_KEY='group_id';
    /**
     * @var    int Time to wait befor rising deadlock error (in seconds)
     */
    protected $wait_timeout=5;
    /**
     * @var    string Default transaction name
     */
    protected $default_tran='_GlobalDBTran';
    /**
     * @var    array Regex array for string escaping
     */
    protected static $non_displayables=[
        '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
        '/%1[0-9a-f]/',             // url encoded 16-31
        '/[\x00-\x08]/',            // 00-08
        '/\x0b/',                   // 11
        '/\x0c/',                   // 12
        '/[\x0e-\x1f]/'             // 14-31
    ];
    /**
     * @var    array Error codes to be ignored
     */
    protected $false_positive_error_codes=[5701,5703];

    /**
     * Get startup query string
     *
     * @param array $params Key-value array of variables to be set
     * @return string  Returns the query to be executed after connection
     */
    public static function GetStartUpQuery($params=NULL): ?string {
        if(!is_array($params) || !count($params)) {
            return NULL;
        }
        $fields='';
        foreach(self::SqlSrvEscapeString($params) as $k=>$v) {
            $fields.=($fields ? '~' : '')."{$k}|{$v}";
        }//END foreach
        return "EXEC [dbo].[SetGlobalVars] @PARAMS = '{$fields}';";
    }//public static function GetStartUpQuery

    /**
     * Set global variables to a temporary table
     *
     * @param array $params Key-value array of variables to be set
     * @return bool  Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public function SqlSrvSetGlobalVariables($params=NULL): bool {
        if(!is_array($params) || !count($params)) {
            return TRUE;
        }
        $result=NULL;
        $time=microtime(TRUE);
        $query=self::GetStartUpQuery($params);
        try {
            $result=sqlsrv_query($this->connection,$query);
        } catch(Exception $e) {
            throw new AppException("FAILED EXECUTE SET GLOBALS QUERY: ".$e->getMessage()." in statement: {$query}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//END try
        $this->DbDebug($query,'Query',$time);
        /*
        if($this->debug) {
            $spid = NULL;
            if(is_resource($result)) {
                $spid = [];
                if(sqlsrv_rows_affected($result)>0) {
                    while(!is_null($nextResult = sqlsrv_next_result($result))) {
                        if($nextResult && sqlsrv_rows_affected($result)) {
                            while($data = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) { $spid[] = $data; }
                        }//if($nextResult && sqlsrv_rows_affected($result))
                    }//END while
                } else {
                    while($data = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) { $spid[] = $data; }
                }//if(sqlsrv_rows_affected($result)>0)
                $spid = $spid[0]['SPID'];
            }//if(is_resource($result) && ($data = sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)))
            NApp::Dlog($spid,'SPID');
        }//if($this->debug)
        */
        if(is_resource($result)) {
            sqlsrv_free_stmt($result);
        }
        return ($result!==FALSE);
    }//END public function SqlSrvSetGlobalVariables

    /**
     * Class initialization abstract method
     * (called automatically on class constructor)
     *
     * @param array $connection Database connection array
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected function Init($connection): void {
        $dbconnect_options=[
            'APP'=>AppConfig::GetValue('app_name'),
            'UID'=>$connection['db_user'],
            'PWD'=>get_array_value($connection,'db_password','','is_string'),
            'Database'=>$this->dbName,
            'CharacterSet'=>get_array_value($connection,'CharacterSet','UTF-8','is_notempty_string'),
            'TrustServerCertificate'=>1,
            'ReturnDatesAsStrings'=>get_array_value($connection,'ReturnDatesAsStrings',TRUE,'bool'),
            'ConnectionPooling'=>get_array_value($connection,'ConnectionPooling',FALSE,'bool'),
            'MultipleActiveResultSets'=>get_array_value($connection,'MultipleActiveResultSets',FALSE,'bool'),
            'TransactionIsolation'=>get_array_value($connection,'TransactionIsolation',SQLSRV_TXN_READ_COMMITTED,'is_numeric'),
        ];
        $db_port=(array_key_exists('db_port',$connection) && strlen($connection['db_port'])) ? ', '.$connection['db_port'] : '';
        try {
            //NApp::TimerStart('sqlsrv_connect');
            $this->connection=sqlsrv_connect($connection['db_server'].$db_port,$dbconnect_options);
            if(!is_resource($this->connection)) {
                $errors=sqlsrv_errors();
                foreach($errors as $i=>$error) {
                    if(!in_array($error['code'],$this->false_positive_error_codes)) {
                        continue;
                    }
                    unset($errors[$i]);
                }//END foreach
                if(count($errors)) {
                    throw new  AppException(print_r($errors,TRUE),E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
                } else {
                    $this->connection=sqlsrv_connect($connection['db_server'].$db_port,$dbconnect_options);
                    if(!is_resource($this->connection)) {
                        throw new  AppException(print_r(sqlsrv_errors(),TRUE),E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
                    }//if(!is_resource($this->connection))
                }//if(count($errors))
            }//if(!is_resource($this->connection))
            $WarningsReturnAsErrors=get_array_value($connection,'WarningsReturnAsErrors',NULL,'is_numeric');
            if(isset($WarningsReturnAsErrors) && is_numeric($WarningsReturnAsErrors)) {
                sqlsrv_configure('WarningsReturnAsErrors',$WarningsReturnAsErrors);
            }//if(isset($WarningsReturnAsErrors) && is_numeric($WarningsReturnAsErrors))
            //NApp::Dlog(NApp::TimerShow('sqlsrv_connect'),'sqlsrv_connect');
        } catch(Exception $e) {
            throw new  AppException("FAILED TO CONNECT TO DATABASE: ".$this->dbName." (".$e->getMessage().")",8001,1,__FILE__,__LINE__,'sqlsrv',0);
        }//END try
    }//END protected function Init

    /**
     * Begins a sqlsrv transaction
     *
     * @param string $name      Unused!!!
     * @param bool   $log       Flag for logging or not the operation
     * @param bool   $overwrite Unused!!!
     * @param null   $customTranParams
     * @return object Returns the transaction instance
     * @throws \NETopes\Core\AppException
     */
    public function SqlSrvBeginTran(&$name=NULL,$log=TRUE,$overwrite=TRUE,$customTranParams=NULL) {
        $name=$this->default_tran;
        if(array_key_exists($name,$this->transactions) && $this->transactions[$name] && !$overwrite) {
            return NULL;
        }
        $this->transactions[$name]=sqlsrv_begin_transaction($this->connection);
        if($this->transactions[$name]===FALSE) {
            throw new  AppException("FAILED TO BEGIN TRANSACTION: ".print_r(sqlsrv_errors(),TRUE),E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }
        return $this->transactions[$name];
    }//END public function SqlSrvBeginTran

    /**
     * Rolls back a sqlsrv transaction
     *
     * @param string $name Transaction name
     * @param bool   $log
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public function SqlSrvRollbackTran($name=NULL,$log=TRUE) {
        $lName=$this->default_tran;
        if(array_key_exists($lName,$this->transactions) && $this->transactions[$lName]) {
            $this->transactions=[];
            if(sqlsrv_rollback($this->connection)===FALSE) {
                throw new  AppException("FAILED TO ROLLBACK TRANSACTION: ".print_r(sqlsrv_errors(),TRUE),E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
            }
            return TRUE;
        }//if(array_key_exists($lName,$this->transactions) && $this->transactions[$lName])
        return FALSE;
    }//END public function SqlSrvRollbackTran

    /**
     * Commits a sqlsrv transaction
     *
     * @param string $name Transaction name
     * @param bool   $log
     * @param bool   $preserve
     * @return bool Returns TRUE on success or FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public function SqlSrvCommitTran($name=NULL,$log=TRUE,$preserve=FALSE) {
        $lName=$this->default_tran;
        if(array_key_exists($lName,$this->transactions) && $this->transactions[$lName]) {
            $this->transactions=[];
            if(sqlsrv_commit($this->connection)===FALSE) {
                throw new  AppException("FAILED TO COMMIT TRANSACTION: ".print_r(sqlsrv_errors(),TRUE),E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
            }
            if($preserve) {
                return $this->SqlSrvBeginTran($name,$log)!==NULL;
            }
            return TRUE;
        }//if(array_key_exists($lName,$this->transactions) && $this->transactions[$lName])
        return FALSE;
    }//END public function SqlSrvCommitTran

    /**
     * @param mixed $sort
     * @return string
     */
    private function GetOrderBy($sort): string {
        $result='';
        if(is_array($sort)) {
            foreach($sort as $k=>$v) {
                $result.=($result ? ' ,' : ' ').self::ENCLOSING_START_SYMBOL.strtoupper(trim($k,' "')).self::ENCLOSING_END_SYMBOL.' '.strtoupper($v);
            }
            $result=strlen(trim($result)) ? ' ORDER BY'.$result.' ' : '';
        } elseif(strlen($sort)) {
            $result=" ORDER BY {$sort} ";
        }//if(is_array($sort))
        return $result;
    }//END private function GetOrderBy

    /**
     * @param $firstRow
     * @param $lastRow
     * @return string
     */
    private function GetOffsetAndLimit($firstRow,$lastRow): string {
        $result='';
        if(is_numeric($firstRow) && $firstRow>0) {
            if(is_numeric($lastRow) && $lastRow>0) {
                $result.=' OFFSET '.($firstRow - 1).' ROWS FETCH NEXT '.($lastRow - $firstRow + 1).' ROWS ONLY';
            } else {
                $result.=' OFFSET 0 ROWS FETCH NEXT '.$firstRow.' ROWS ONLY';
            }//if(is_numeric($lastRow) && $lastRow>0)
        }//if(is_numeric($firstRow) && $firstRow>0)
        return $result;
    }//END private function GetOffsetAndLimit

    /**
     * @param array $condition
     * @return string
     * @throws \Exception
     */
    private function GetFilterCondition(array $condition): string {
        $cond=get_array_value($condition,'condition',NULL,'is_notempty_string');
        if($cond) {
            return $cond;
        }
        $field=get_array_value($condition,'field',get_array_value($condition,'type',NULL,'is_notempty_string'),'is_notempty_string');
        if(!$field) {
            return '';
        }
        $dataType=get_array_value($condition,'data_type',NULL,'is_notempty_string');
        $conditionType=get_array_value($condition,'condition_type','==','is_notempty_string');
        $conditionString=NULL;
        $filterValue=NULL;
        switch(strtolower($conditionType)) {
            case 'like':
            case 'notlike':
                $conditionString=strtolower($conditionType)=='like' ? 'LIKE ' : 'NOT LIKE ';
                $filterValue=$this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                if(isset($filterValue)) {
                    $filterValue=$conditionString.(is_string($filterValue) && !mb_detect_encoding($filterValue,'ASCII',TRUE) ? 'N' : '')."'%".$filterValue."%'";
                }
                break;
            case '==':
            case '<>':
                $conditionString=$conditionType=='==' ? '= ' : $conditionType.' ';
                $nullConditionString='IS '.($conditionType=='<>' ? ' NOT ' : '').'NULL';
                if(strtolower($dataType)=='string') {
                    $filterValue=$this->EscapeString(get_array_value($condition,'value',NULL,'?is_string'));
                    $filterValue=isset($filterValue) ? $conditionString.(is_string($filterValue) && !mb_detect_encoding($filterValue,'ASCII',TRUE) ? 'N' : '')."'".$filterValue."'" : $nullConditionString;
                } else {
                    $filterValue=$this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                    if(in_array(strtolower($dataType),['date','date_obj'])) {
                        $filterValueS=Validator::ConvertDateTimeToDbFormat($filterValue,NULL,0);
                        $filterValueE=Validator::ConvertDateTimeToDbFormat($filterValue,NULL,1);
                        $conditionString=($conditionType=='<>' ? 'NOT ' : '').'BETWEEN ';
                        $filterValue=$conditionString."'".$filterValueS."' AND '".$filterValueE."'";
                    } elseif(in_array(strtolower($dataType),['datetime','datetime_obj'])) {
                        $filterValue=Validator::ConvertDateTimeToDbFormat($filterValue);
                        $filterValue=isset($filterValue) ? $conditionString."'".$filterValue."'" : $nullConditionString;
                    } else {
                        $filterValue=isset($filterValue) ? $conditionString."'".$filterValue."'" : $nullConditionString;
                    }//if(in_array(strtolower($dataType),['date','datetime','datetime_obj']))
                }//if(strtolower($dataType)=='string')
                break;
            case '<=':
            case '>=':
                $conditionString=$conditionType.' ';
                $filterValue=$this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                if(in_array(strtolower($dataType),['date','date_obj','datetime','datetime_obj'])) {
                    if(in_array(strtolower($dataType),['date','date_obj'])) {
                        $daypart=($conditionType=='<=' ? 1 : 0);
                    } else {
                        $daypart=NULL;
                    }//if(in_array(strtolower($dataType),['date','date_obj']))
                    $filterValue=Validator::ConvertDateTimeToDbFormat($filterValue,NULL,$daypart);
                }//if(in_array(strtolower($dataType),['date','datetime','datetime_obj']))
                if(isset($filterValue)) {
                    $filterValue=$conditionString."'".$filterValue."'";
                }
                break;
            case '><':
                $conditionString='BETWEEN ';
                $filterValueS=$this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                $filterValueE=$this->EscapeString(get_array_value($condition,'end_value',NULL,'?is_notempty_string'));
                if(in_array(strtolower($dataType),['date','date_obj','datetime','datetime_obj'])) {
                    if(in_array(strtolower($dataType),['date','date_obj'])) {
                        $daypart=0;
                        $sdaypart=1;
                    } else {
                        $daypart=NULL;
                        $sdaypart=NULL;
                    }//if(in_array(strtolower($dataType),['date','date_obj']))
                    $filterValueS=Validator::ConvertDateTimeToDbFormat($filterValueS,NULL,$daypart);
                    $filterValueE=Validator::ConvertDateTimeToDbFormat($filterValueE,NULL,$sdaypart);
                }//if(in_array(strtolower($dataType),['date','datetime','datetime_obj']))
                if(isset($filterValueS) && isset($filterValueE)) {
                    $filterValue=$conditionString."'".$filterValueS."' AND '".$filterValueE."'";
                }
                break;
            case 'is':
            case 'isnot':
                $conditionString=strtolower($conditionType)=='like' ? 'IS ' : 'IS NOT ';
                $filterValue=$this->EscapeString(get_array_value($condition,'value',NULL,'?is_notempty_string'));
                $filterValue=isset($filterValue) ? $conditionString.(is_string($filterValue) && !mb_detect_encoding($filterValue,'ASCII',TRUE) ? 'N' : '')."'".$filterValue."'" : 'NULL';
                break;
        }//END switch
        if(!$filterValue) {
            return '';
        }
        if(get_array_value($condition,'escape_field_name',TRUE,'bool')) {
            $result=' '.self::ENCLOSING_START_SYMBOL.strtoupper($field).self::ENCLOSING_END_SYMBOL;
        } else {
            $result=' '.strtoupper($field);
        }
        $result.=' '.$filterValue;
        return $result;
    }//END private function GetFilterCondition

    /**
     * @param array       $filters
     * @param string|null $logicalOperator
     * @return string
     * @throws \Exception
     */
    private function GetFiltersCondition(array $filters,?string &$logicalOperator=NULL): string {
        $result='';
        $first=TRUE;
        foreach($filters as $k=>$v) {
            if(substr($k,0,1)=='_') {
                if(!is_array($v)) {
                    continue;
                }
                $condition=$this->GetFiltersCondition($v,$sep);
            } elseif(is_array($v)) {
                $sep=strtoupper(get_array_value($v,'logical_separator','AND','is_notempty_string'));
                $condition=$this->GetFilterCondition($v);
            } else {
                $sep='AND';
                $condition=$v;
            }//if(is_array($v))
            if(!strlen(trim($condition))) {
                continue;
            }
            $result.=($result ? ' '.strtoupper($sep).' ' : ' ').'('.trim($condition).')';
            if($first) {
                $logicalOperator=$sep;
                $first=FALSE;
            }
        }//END foreach
        return $result;
    }//END private function GetFiltersCondition

    /**
     * Prepares the query string for execution
     *
     * @param string $query      The query string (by reference)
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $outParams  An array of output params
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstRow   Integer to limit number of returned rows
     *                           (if used with 'last_row' represents the offset of the returned rows)
     * @param int    $lastRow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @param array  $filters    An array of condition to be applied in WHERE clause
     * @param null   $rawQuery
     * @param null   $bindParams
     * @param null   $transaction
     * @return void
     * @throws \Exception
     */
    public function SqlSrvPrepareQuery(&$query,$params=[],$outParams=[],$type='',$firstRow=NULL,$lastRow=NULL,$sort=NULL,$filters=NULL,&$rawQuery=NULL,&$bindParams=NULL,$transaction=NULL) {
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                $query=str_replace('{!'.$k.'!}',$this->EscapeString($v),$query);
            }
        }//if(is_array($params) && count($params))
        $filterCondition='';
        if(is_array($filters)) {
            $logicalOperator=NULL;
            $groupedFilters=array_group_by_hierarchical(static::FILTERS_GROUP_KEY,$filters,TRUE,'_','_99');
            $filterCondition=$this->GetFiltersCondition($groupedFilters,$logicalOperator);
            if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtoupper($query),' WHERE ')===FALSE) {
                $filterPrefix=' WHERE ';
                $filterSufix=' ';
            } else {
                $filterPrefix=' '.($logicalOperator ?? 'AND').' (';
                $filterSufix=') ';
            }//if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtoupper($query),' WHERE ')===FALSE)
            $filterCondition=strlen(trim($filterCondition)) ? $filterPrefix.$filterCondition.$filterSufix : '';
        } elseif(is_string($filters) && strlen($filters)) {
            $filterCondition=" {$filters} ";
        }//if(is_array($filters))
        $query.=$filterCondition;
        $rawQuery=$query;
        if($type=='count') {
            return;
        }
        $query.=$this->GetOrderBy($sort);
        $query.=$this->GetOffsetAndLimit($firstRow,$lastRow);
    }//public function SqlSrvPrepareQuery

    /**
     * Executes a query against the database
     *
     * @param string $query      The query string
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $outParams  An array of output params
     * @param string $tranName   Name of transaction in which the query will run
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstRow   Integer to limit number of returned rows
     *                           (if used with 'last_row' represents the offset of the returned rows)
     * @param int    $lastRow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @param null   $filters
     * @param bool   $log
     * @param null   $resultsKeysCase
     * @param null   $customTranParams
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     */
    public function SqlSrvExecuteQuery($query,$params=[],&$outParams=[],$tranName=NULL,$type='',$firstRow=NULL,$lastRow=NULL,$sort=NULL,$filters=NULL,$log=TRUE,$resultsKeysCase=NULL,$customTranParams=NULL) {
        $time=microtime(TRUE);
        $rawQuery=NULL;
        $this->SqlSrvPrepareQuery($query,$params,$outParams,$type,$firstRow,$lastRow,$sort,$filters,$rawQuery);
        if(!is_array($outParams)) {
            $outParams=[];
        }
        $outParams['__raw_sql_qry']=$rawQuery;
        $outParams['__sql_qry']=$query;
        if(strlen($tranName)) {
            if(!array_key_exists($tranName,$this->transactions) || !$this->transactions[$tranName]) {
                throw new  AppException("FAILED QUERY: NULL database transaction in statement: ".$query,E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
            }
        }//if(strlen($tranName))
        $finalResult=NULL;
        try {
            if($type=='insert') {
                $result=sqlsrv_query($this->connection,$query.'; SELECT SCOPE_IDENTITY() AS ID');
            } else {
                $result=sqlsrv_query($this->connection,$query);
            }//if($type=='insert')
        } catch(Exception$e) {
            if(strlen($tranName)) {
                $this->SqlSrvRollbackTran($tranName);
            }
            throw new  AppException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//END try
        if($result===FALSE) {
            $dbError=print_r(sqlsrv_errors(),TRUE);
            if(strlen($tranName)) {
                $this->SqlSrvRollbackTran($tranName);
            }
            throw new  AppException("FAILED QUERY: {$dbError} in statement: {$query}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//if($result===FALSE)
        try {
            if(is_resource($result)) {
                if($type=='insert') {
                    sqlsrv_next_result($result);
                    sqlsrv_fetch($result);
                    $finalResult=sqlsrv_get_field($result,0);
                } else {
                    $finalResult=[];
                    if(sqlsrv_rows_affected($result)>0) {
                        while(!is_null($nextResult=sqlsrv_next_result($result))) {
                            if($nextResult && sqlsrv_rows_affected($result)) {
                                while($data=sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) {
                                    $finalResult[]=$data;
                                }
                            }//if($nextResult && sqlsrv_rows_affected($result))
                        }//END while
                    } else {
                        while($data=sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) {
                            $finalResult[]=$data;
                        }
                    }//if(sqlsrv_rows_affected($result)>0)
                }//if($type=='insert')
                sqlsrv_free_stmt($result);
            } else {
                $finalResult=$result;
            }//if(is_resource($result))
        } catch(Exception$e) {
            if(strlen($tranName)) {
                $this->SqlSrvRollbackTran($tranName);
            }
            throw new  AppException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: {$query}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//END try
        $this->DbDebug($query,'Query',$time);
        return change_array_keys_case($finalResult,TRUE,(isset($resultsKeysCase) ? $resultsKeysCase : $this->resultsKeysCase));
    }//END public function SqlSrvExecuteQuery

    /**
     * Prepares the command string to be executed
     *
     * @param string $procedure  The name of the stored procedure
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $outParams  An array of output params
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstRow   Integer to limit number of returned rows
     *                           (if used with 'last_row' represents the offset of the returned rows)
     * @param int    $lastRow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @param array  $filters    An array of condition to be applied in WHERE clause
     * @param null   $rawQuery
     * @param null   $sqlParams
     * @param null   $transaction
     * @return string|resource Returns processed command string or the statement resource
     * @throws \Exception
     */
    protected function SqlSrvPrepareProcedureStatement($procedure,$params=[],&$outParams=[],$type='',$firstRow=NULL,$lastRow=NULL,$sort=NULL,$filters=NULL,&$rawQuery=NULL,&$sqlParams=NULL,$transaction=NULL) {
        $parameters='';
        // With output parameters
        if(is_array($outParams) && count($outParams)) {
            if(!is_array($sqlParams)) {
                $sqlParams=[];
            }
            if(is_array($params) && count($params)) {
                foreach(self::SqlSrvEscapeString($params) as $n=>$p) {
                    $parameters.='?,';
                    $sqlParams[]=[$p,SQLSRV_PARAM_IN];
                }//END foreach
            }//if(is_array($params) && count($params))
            foreach($outParams as $n=>$p) {
                if(is_array($p)) {
                    $pType=get_array_value($p,'type',NULL,'is_integer');
                    $outParams[$n]=self::SqlSrvEscapeString(get_array_value($p,'value',NULL));
                } else {
                    $pType=NULL;
                    $outParams[$n]=self::SqlSrvEscapeString($p);
                }
                $parameters.='?,';
                $pType=$pType ?? SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
                $sqlParams[]=[&$outParams[$n],SQLSRV_PARAM_OUT,$pType];
            }//END foreach
            $parameters=trim($parameters,',');
            return "{ call {$procedure}({$parameters}) }";
        }//if(is_array($outParams) && count($outParams))
        // Without output parameters
        if(is_array($params) && count($params)) {
            foreach(self::SqlSrvEscapeString($params) as $n=>$p) {
                if(is_null($p)) {
                    $pVal='NULL';
                } elseif(mb_detect_encoding($p,'ASCII',TRUE)) {
                    $pVal="'{$p}'";
                } else {
                    $pVal="N'{$p}'";
                }//if(is_null($p))
                $parameters.=(strlen($parameters)>0 ? ', ' : ' ').(strtolower($type)=='execute' ? '@'.strtoupper($n).' = ' : '').$pVal;
            }//END foreach
        }//if(is_array($params) && count($params))
        $filterCondition='';
        if(strtolower($type)=='execute') {
            $rawQuery=$procedure.$parameters;
        } else {
            if(is_array($filters)) {
                $filterPrefix=' WHERE ';
                $filterSufix=' ';
                $groupedFilters=array_group_by_hierarchical(static::FILTERS_GROUP_KEY,$filters,TRUE,'_','_99');
                $filterCondition=$this->GetFiltersCondition($groupedFilters);
                $filterCondition=strlen(trim($filterCondition)) ? $filterPrefix.$filterCondition.$filterSufix : '';
            } elseif(is_string($filters) && strlen($filters)) {
                $filterCondition=strtoupper(substr(trim($filters),0,5))=='WHERE' ? " {$filters} " : " WHERE {$filters} ";
            }//if(is_array($filters))
            $rawQuery=$procedure.'('.$parameters.')'.$filterCondition;
        }//if(strtolower($type)=='execute')
        switch(strtolower($type)) {
            case 'execute':
                $query='EXEC '.$procedure.$parameters;
                break;
            case 'count':
                $query='SELECT COUNT(1) AS [COUNT] FROM '.$procedure.'('.$parameters.')'.$filterCondition;
                break;
            case 'select':
            default:
                $query='SELECT * FROM '.$procedure.'('.$parameters.')'.$filterCondition;
                $query.=$this->GetOrderBy($sort);
                $query.=$this->GetOffsetAndLimit($firstRow,$lastRow);
                break;
        }//END switch
        return $query;
    }//END protected function SqlSrvPrepareProcedureStatement

    /**
     * Executes a stored procedure against the database
     *
     * @param string $procedure  The name of the stored procedure
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $outParams  An array of output params
     * @param string $tranName   Name of transaction in which the query will run
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstRow   Integer to limit number of returned rows
     *                           (if used with 'last_row' represents the offset of the returned rows)
     * @param int    $lastRow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @param array  $filters    An array of condition to be applied in WHERE clause
     * @param bool   $log
     * @param null   $resultsKeysCase
     * @param null   $customTranParams
     * @return array|bool Returns database request result
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     */
    public function SqlSrvExecuteProcedure($procedure,$params=[],&$outParams=[],$tranName=NULL,$type='',$firstRow=NULL,$lastRow=NULL,$sort=NULL,$filters=NULL,$log=FALSE,$resultsKeysCase=NULL,$customTranParams=NULL) {
        $time=microtime(TRUE);
        if(!is_array($outParams)) {
            $outParams=[];
        }
        $sqlParams=NULL;
        $rawQuery=NULL;
        $query=$this->SqlSrvPrepareProcedureStatement($procedure,$params,$outParams,$type,$firstRow,$lastRow,$sort,$filters,$rawQuery,$sqlParams);
        $sqlParams4dbg=$sqlParams ? '>>Param: '.print_r($sqlParams,TRUE) : '';
        $outParams['__raw_sql_qry']=$rawQuery;
        $outParams['__sql_qry']=$query;
        if(strlen($tranName)) {
            if(!array_key_exists($tranName,$this->transactions) || !$this->transactions[$tranName]) {
                throw new  AppException("FAILED EXECUTE PROCEDURE: NULL database transaction in statement: {$query}{$sqlParams4dbg}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
            }
        }//if(strlen($tranName))
        $finalResult=NULL;
        try {
            if($sqlParams) {
                $result=sqlsrv_query($this->connection,$query,$sqlParams);
            } else {
                $result=sqlsrv_query($this->connection,$query);
            }//if($sqlParams)
        } catch(Exception $e) {
            if(strlen($tranName)) {
                $this->SqlSrvRollbackTran($tranName);
            }
            throw new  AppException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: {$query}{$sqlParams4dbg}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//END try
        if($result===FALSE) {
            $dbError=print_r(sqlsrv_errors(),TRUE);
            if(strlen($tranName)) {
                $this->SqlSrvRollbackTran($tranName);
            }
            throw new  AppException("FAILED EXECUTE PROCEDURE: $dbError in statement: {$query}{$sqlParams4dbg}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//if($result===FALSE)
        try {
            if(is_resource($result)) {
                $finalResult=[];
                $nextResult=TRUE;
                while($nextResult===FALSE || !is_null($nextResult)) {
                    if($nextResult && sqlsrv_rows_affected($result)) {
                        $tmp_result=[];
                        while($data=sqlsrv_fetch_array($result,SQLSRV_FETCH_ASSOC)) {
                            $tmp_result[]=$data;
                        }
                        $finalResult[]=$tmp_result;
                    }//if($nextResult && sqlsrv_rows_affected($result))
                    $nextResult=sqlsrv_next_result($result);
                }//END while
                if(count($finalResult)==1) {
                    $finalResult=$finalResult[0];
                }
                sqlsrv_free_stmt($result);
            } else {
                $finalResult=$result;
            }//if(is_resource($result))
        } catch(Exception$e) {
            if(strlen($tranName)) {
                $this->SqlSrvRollbackTran($tranName);
            }
            throw new  AppException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: {$query}{$sqlParams4dbg}",E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
        }//END try
        //if(strlen($tranName)==0) { $this->SqlSrvCommitTran(); }
        $this->DbDebug($query.$sqlParams4dbg,'Query',$time);
        return change_array_keys_case($finalResult,TRUE,(isset($resultsKeysCase) ? $resultsKeysCase : $this->resultsKeysCase));
    }//END public function SqlSrvExecuteProcedure

    /**
     * Executes a method of the database object or of one of its sub-objects
     *
     * @param string $method       Name of the method to be called
     * @param string $property     The name of the sub-object containing the method
     *                             to be executed
     * @param array  $params       An array of parameters
     *                             to be passed to the method
     * @param array  $extra_params An array of extra parameters
     * @param bool   $log          Flag to turn logging on/off
     * @return void   return description
     * @throws \NETopes\Core\AppException
     */
    public function SqlSrvExecuteMethod($method,$property=NULL,$params=[],$extra_params=[],$log=TRUE) {
        throw new  AppException("FAILED EXECUTE METHOD: #ErrorCode:N/A# Execute method not implemented for SqlSrvSQL !!! in statement: ".$method.trim('->'.$property,'->'),E_USER_ERROR,1,__FILE__,__LINE__,'sqlsrv',0);
    }//END public function SqlSrvExecuteMethod

    /**
     * Escapes single quote character from a string
     *
     * @param string|array $param String to be escaped or
     *                            an array of strings
     * @return string|array Returns the escaped string or array
     */
    public function EscapeString($param) {
        return self::SqlSrvEscapeString($param);
    }//END public function EscapeString

    /**
     * Convert string from unknown character set to UTF-8
     *
     * @param string $value The string to be converted
     * @return     string Returns the converted string
     */
    public static function UTF8Encode($value) {
        $enc=mb_detect_encoding($value,mb_detect_order(),TRUE);
        if(strtoupper($enc)=='UTF-8' || !function_exists('iconv')) {
            return $value;
        }
        return iconv($enc,'UTF-8',$value);
    }//END public static function UTF8Encode

    /**
     * Escapes single quote character from a string
     *
     * @param string|array $param String to be escaped or
     *                            an array of strings
     * @return string|array Returns the escaped string or array
     */
    public static function SqlSrvEscapeString($param) {
        $result=NULL;
        if(is_array($param)) {
            $result=[];
            foreach($param as $k=>$v) {
                $result[$k]=static::SqlSrvEscapeString($v);
            }//END foreach
        } else {
            $result=$param;
            if($result instanceof DateTime) {
                $result=$result->format('c');
            } elseif(isset($result) && !is_numeric($result)) {
                foreach(self::$non_displayables as $regex) {
                    $result=preg_replace($regex,'',$result);
                }
                $result=str_replace("'","''",self::UTF8Encode($result));
            }//if(isset($result) && !is_numeric($result))
        }//if(is_array($result))
        return $result;
    }//END public function SqlSrvEscapeString
}//END class SqlSrvAdapter extends SqlDataAdapter