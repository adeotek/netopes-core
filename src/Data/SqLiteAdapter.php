<?php
/**
 * SQLite database implementation class file
 * This file contains the implementing class for SQLite database.
 *
 * @package    Hinter\NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */

/**
 * SqLiteDatabase Is implementing the SQLite database
 * This class contains all methods for interacting with SQLite database.
 *
 * @package  Hinter\NETopes\Database
 */
class SqLiteAdapter extends SqlDataAdapter {
    /**
     * Set global variables to a temporary table
     *
     * @param array $params Key-value array of variables to be set
     * @return bool  Returns TRUE on success or FALSE otherwise
     */
    public function SqLiteSetGlobalVariables($params=[]) {
        if(!is_array($params) || !count($params)) {
            return TRUE;
        }
        return FALSE;
    }//END public function SqLiteSetGlobalVariables

    /**
     * Class initialization abstract method
     * (called automatically on class constructor)
     *
     * @param array $connection Database connection
     * @return void
     */
    protected function Init($connection) {
        try {
            $this->connection=new SQLite3(NApp::$appPath.$connection['db_path'].$connection['db_name'],SQLITE3_OPEN_READWRITE);
        } catch(Exception $e) {
            throw new AException("SQLITE failed to open file: ".$connection['db_path'].$connection['db_name'].' ('.$e->getMessage().")",E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',0);
        }//END try
    }//END protected function Init

    /**
     * Begins a sqlite transaction
     *
     * @param string $name      Transaction name
     * @param bool   $overwrite Flag for overwriting the transaction
     *                          if exists (defaul value FALSE)
     * @return object Returns the transaction instance
     */
    public function SqLiteBeginTran($name,$log=TRUE,$overwrite=TRUE) {
        return NULL;
    }//END public function SqLiteBeginTran

    /**
     * Rolls back a sqlite transaction
     *
     * @param string $name Transaction name
     * @return bool Returns TRUE on success or FALSE otherwise
     */
    public function SqLiteRollbackTran($name,$log=TRUE) {
        return FALSE;
    }//END public function SqLiteRollbackTran

    /**
     * Commits a sqlite transaction
     *
     * @param string $name Transaction name
     * @return bool Returns TRUE on success or FALSE otherwise
     */
    public function SqLiteCommitTran($name,$log=TRUE,$preserve=FALSE) {
        return FALSE;
    }//END public function SqLiteCommitTran

    /**
     * Prepares the query string for execution
     *
     * @param string $query      The query string (by reference)
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $out_params An array of output params
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstrow   Integer to limit number of returned rows
     *                           (if used with 'last_row' reprezents the offset of the returned rows)
     * @param int    $lastrow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @return void
     */
    public function SqLitePrepareQuery(&$query,$params=[],$out_params=[],$type='',$firstrow=NULL,$lastrow=NULL,$sort=NULL,$filters=NULL) {
        if(is_array($params) && count($params)) {
            foreach($params as $k=>$v) {
                $query=str_replace('{{'.$k.'}}',$this->SqLiteEscapeString($v),$query);
            }
        }//if(is_array($params) && count($params))
        $filter_str='';
        if(is_array($filters)) {
            if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtolower($query),' where ')===FALSE) {
                $filter_prefix=' where ';
                $filter_sufix=' ';
            } else {
                $filter_prefix=' and (';
                $filter_sufix=') ';
            }//if(get_array_value($filters,'where',FALSE,'bool') || strpos(strtolower($query),' where ')===FALSE)
            foreach($filters as $v) {
                if(is_array($v)) {
                    if(count($v)==4) {
                        $ffield=get_array_value($v,'field',NULL,'is_notempty_string');
                        $fvalue=get_array_value($v,'value',NULL,'is_notempty_string');
                        if(!$ffield || !$fvalue || strtolower($fvalue)=='null') {
                            continue;
                        }
                        $fcond=get_array_value($v,'condition_type','=','is_notempty_string');
                        $sep=get_array_value($v,'logical_separator','and','is_notempty_string');
                        switch(strtolower($fcond)) {
                            case 'like':
                            case 'not like':
                                $filter_str.=($filter_str ? ' '.strtolower($sep) : '').' "'.$ffield.'" '.strtolower($fcond)." '%".$fvalue."%'";
                                break;
                            case '==':
                            case '<>':
                            case '<=':
                            case '>=':
                                $filter_str.=($filter_str ? ' '.strtolower($sep) : '').' "'.$ffield.'" '.$fcond." '".$fvalue."'";
                                break;
                            default:
                                continue;
                                break;
                        }//END switch
                    } elseif(count($v==2)) {
                        $cond=get_array_value($v,'condition',NULL,'is_notempty_string');
                        if(!$cond) {
                            continue;
                        }
                        $sep=get_array_value($v,'logical_separator','and','is_notempty_string');
                        $filter_str.=($filter_str ? ' '.strtolower($sep).' ' : ' ').$cond;
                    }//if(count($v)==4)
                } else {
                    $filter_str.=($filter_str ? ' AND ' : ' ').$v;
                }//if(is_array($v))
            }//END foreach
            $filter_str=strlen(trim($filter_str))>0 ? $filter_prefix.$filter_str.$filter_sufix : '';
        } elseif(strlen($filters)>0) {
            $filter_str=" $filters ";
        }//if(is_array($filters))
        $query.=$filter_str;
        if($type=='count') {
            return;
        }
        $sort_str='';
        if(is_array($sort)) {
            foreach($sort as $k=>$v) {
                $sort_str.=($sort_str ? ' ,' : ' ')."$k $v";
            }
            $sort_str=strlen(trim($sort_str))>0 ? ' order by'.$sort_str.' ' : '';
        } elseif(strlen($sort)>0) {
            $sort_str=" order by $sort asc ";
        }//if(is_array($sort))
        $query.=$sort_str;
        if(is_numeric($firstrow) && $firstrow>0 && is_numeric($lastrow) && $lastrow>0) {
            $query.=' limit '.($lastrow - ($firstrow - 1)).(($firstrow - 1)>0 ? ' offset '.($firstrow - 1) : '');
        } elseif(is_numeric($firstrow) && $firstrow>0) {
            $query.=' limit '.$firstrow;
        }//if(is_numeric($firstrow) && $firstrow>0 && is_numeric($lastrow) && $lastrow>0)
    }//public function SqLitePrepareQuery

    /**
     * Executs a query against the database
     *
     * @param string $query      The query string
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $out_params An array of output params
     * @param string $tran_name  Name of transaction in which the query will run
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstrow   Integer to limit number of returned rows
     *                           (if used with 'last_row' reprezents the offset of the returned rows)
     * @param int    $lastrow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @return array|bool Returns database request result
     */
    public function SqLiteExecuteQuery($query,$params=[],$out_params=[],$tran_name=NULL,$type='',$firstrow=NULL,$lastrow=NULL,$sort=NULL,$filters=NULL,$log=TRUE,$results_keys_case=NULL,$custom_tran_params=NULL) {
        $time=microtime(TRUE);
        $this->SqLitePrepareQuery($query,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
        $final_result=NULL;
        try {
            if($type=='execute') {
                $result=$this->connection->exec($query);
            } else {
                $result=$this->connection->query($query);
            }//if($type=='execute')
            if(is_object($result)) {
                while($data=$result->fetchArray(SQLITE3_ASSOC)) {
                    $final_result[]=$data;
                }
                $result->finalize();
            } else {
                $final_result=$result;
            }//if(is_object($result))
        } catch(Exception $e) {
            throw new AException("SQLITE execute query failed: ".$e->getMessage()." at statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',$e->getCode());
        }//END try
        $this->DbDebug($query,'Query',$time);
        return change_array_keys_case($final_result,TRUE,(isset($results_keys_case) ? $results_keys_case : $this->resultsKeysCase));
    }//END public function SqLiteExecuteQuery

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
     */
    public function SqLiteExecuteMethod($method,$property=NULL,$params=[],$extra_params=[],$log=TRUE) {
        throw new AException("FAILED EXECUTE METHOD: #ErrorCode:N/A# Execute method not implemented for SQLite !!! in statement: ".$method.trim('->'.$property,'->'),E_USER_ERROR,1,__FILE__,__LINE__,'sqlite',0);
    }//END public function SqLiteExecuteMethod

    /**
     * Escapes SQLite special charcaters from a string
     *
     * @param string|array $param String to be escaped or
     *                            an array of strings
     * @return string|array Returns the escaped string or array
     */
    public function SqLiteEscapeString($param) {
        $result=NULL;
        if(is_array($param)) {
            $result=[];
            foreach($param as $k=>$v) {
                $result[$k]=$this->connection->escapeString($v);
            }
        } else {
            $result=$this->connection->escapeString($param);
        }
        return $result;
    }//END public function SqLiteEscapeString
}//END class SqLiteAdapter extends Database
?>