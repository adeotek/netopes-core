<?php
/**
 * MongoDB database implementation class file
 * This file contains the implementing class for MongoDB database.
 *
 * @package    Hinter\NETopes\Database
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2004 - 2015 Hinter Software
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */

/**
 * MongoDbDatabase Is implementing the MongoDB database
 * This class contains all methods for interacting with MongoDB database.
 *
 * @package  Hinter\NETopes\Database
 */
class MongoDbDbAdapter extends DataAdapter {
    /**
     * @var    int Command timeout (in milliseconds)
     */
    protected $timeout=5000;

    /**
     * Class initialization abstract method
     * (called automatically on class constructor)
     *
     * @param array $connection Database connection array
     * @return void
     */
    protected function Init($connection) {
        $db_port=(array_key_exists('db_port',$connection) && $connection['db_port']) ? ':'.$connection['db_port'] : ':27017';
        try {
            NApp::StartTimeTrack('mongodb_connect');
            $mg_conn=new MongoClient('mongodb://'.$connection['db_user'].':'.get_array_value($connection,'db_password','','is_string').'@'.$connection['db_server'].$db_port.'/'.$this->dbName);
            $mg_dbname=$this->dbName;
            $this->connection=$mg_conn->$mg_dbname;
            NApp::Dlog(NApp::ShowTimeTrack('mongodb_connect'),'mongodb_connect');
        } catch(MongoConnectionException $e) {
            throw new AException("FAILED TO CONNECT TO DATABASE: ".$this->dbName." (".$e->getMessage().")",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        } catch(MongoException $e) {
            throw new AException("FAILED TO CONNECT TO DATABASE: ".$this->dbName." (".$e->getMessage().")",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        } catch(Exception $e) {
            throw new AException("FAILED TO CONNECT TO DATABASE: ".$this->dbName." (".$e->getMessage().")",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
    }//END protected function Init

    /**
     * Begins a MongoDB two-stage commit
     *
     * @param string $name      Transaction name
     * @param bool   $overwrite Flag for overwriting the transaction
     *                          if exists (defaul value FALSE)
     * @return object Returns the transaction instance
     */
    public function MongoDbBeginTran($name,$log=TRUE,$overwrite=TRUE) {
        if(array_key_exists($name,$this->transactions) && $this->transactions[$name] && !$overwrite) {
            return NULL;
        }
        // TODO: to be implemented
        $this->transactions[$name]=NULL;
        NApp::AppLoggerAddEvent(['action'=>__FUNCTION__,'data'=>$name],$log);
        return $this->transactions[$name];
    }//END public function MongoDbBeginTran

    /**
     * Rolls back a MongoDB two-stage commit
     *
     * @param string $name Transaction name
     * @return bool Returns TRUE on success or FALSE otherwise
     */
    public function MongoDbRollbackTran($name,$log=TRUE) {
        if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
            // TODO: to be implemented
            unset($this->transactions[$name]);
            NApp::AppLoggerAddEvent(['action'=>__FUNCTION__,'data'=>$name],$log);
            return TRUE;
        }//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
        return FALSE;
    }//END public function MongoDbRollbackTran

    /**
     * Commits a MongoDB two-stage commit
     *
     * @param string $name Transaction name
     * @return bool Returns TRUE on success or FALSE otherwise
     */
    public function MongoDbCommitTran($name,$log=TRUE,$preserve=FALSE) {
        if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name])) {
            // TODO: to be implemented
            unset($this->transactions[$name]);
            if($preserve) {
                $this->MongoDbBeginTran($name);
            }
            NApp::AppLoggerAddEvent(['action'=>__FUNCTION__,'data'=>$name],$log);
            return TRUE;
        }//if(array_key_exists($name,$this->transactions) && isset($this->transactions[$name]))
        return FALSE;
    }//END public function MongoDbCommitTran

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
    public function MongoDbPrepareQuery(&$query,$params=[],$out_params=[],$type='',$firstrow=NULL,$lastrow=NULL,$sort=NULL,$filters=NULL) {
        // TODO: to be implemented
        return FALSE;
    }//public function MongoDbPrepareQuery

    /**
     * Executes a query against the database
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
    public function MongoDbExecuteQuery($query,$params=[],$out_params=[],$tran_name=NULL,$type='',$firstrow=NULL,$lastrow=NULL,$sort=NULL,$filters=NULL,$log=TRUE) {
        $time=microtime(TRUE);
        //$this->MongoDbPrepareQuery($query,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
        if(strlen($tran_name)>0) {
            if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name])) {
                $transaction=$tran_name;
            } else {
                throw new AException("FAILED QUERY: NULL database transaction in statement: ".$query,E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
            }//if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name]))
        } else {
            $transaction='DefaultTransaction';
            if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]) || !is_resource($this->transactions[$transaction])) {
                $this->MongoDbBeginTran($transaction);
            }
        }//if(strlen($tran_name)>0)
        $final_result=NULL;
        try {
            $result=$this->connection->command($query,['timeout'=>$this->timeout]);
        } catch(MongoException $e) {
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        } catch(Exception $e) {
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
        NApp::Dlog($result,'MongoDbCommandResult');
        /*if(ibase_errmsg() || $result===FALSE) {
            $iberror = ibase_errmsg();
            $iberrorcode = ibase_errcode();
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED QUERY: #ErrorCode:$iberrorcode# $iberror in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',$iberrorcode);
        }//if(ibase_errmsg() || $result===FALSE)*/
        try {
            if(is_object($result) && is_a($result,'MongoCursor')) {
                while($result->hasNext()) {
                    $final_result[]=$result->getNext();
                }
            } else {
                $final_result=$result;
            }//if(is_object($result) && is_a($result,'MongoCursor'))
        } catch(Exception $e) {
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE QUERY: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
        if(strlen($tran_name)==0) {
            $this->MongoDbCommitTran($transaction);
        }
        if($this->debug) {
            NApp::Dlog($query.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec','Query');
            if($this->debug2file) {
                NApp::Write2LogFile('Query: '.$query.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec','debug');
            }//if($this->debug2file)
            if($this->debug2screen) {
                echo '#Query: '.$query.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec#<br/>';
            }//if($this->debug2screen)
        }//if($this->debug)
        NApp::AppLoggerAddEvent(['action'=>__FUNCTION__,'data'=>$query,'duration'=>(microtime(TRUE) - $time)],$log);
        return change_array_keys_case($final_result,TRUE);
    }//END public function MongoDbExecuteQuery

    /**
     * Prepares the command string to be executed
     *
     * @param string $procedure  The name of the stored procedure
     * @param array  $params     An array of parameters
     *                           to be passed to the query/stored procedure
     * @param array  $out_params An array of output params
     * @param string $type       Request type: select, count, execute (default 'select')
     * @param int    $firstrow   Integer to limit number of returned rows
     *                           (if used with 'last_row' reprezents the offset of the returned rows)
     * @param int    $lastrow    Integer to limit number of returned rows
     *                           (to be used only with 'first_row')
     * @param array  $sort       An array of fields to compose ORDER BY clause
     * @param array  $filters    An array of condition to be applyed in WHERE clause
     * @return string Returns processed command string
     */
    protected function MongoDbPrepareProcedureStatement($procedure,$params=[],$out_params=[],$type='',$firstrow=NULL,$lastrow=NULL,$sort=NULL,$filters=NULL) {
        // TODO: to be implemented
        return FALSE;
    }//END protected function MongoDbPrepareProcedureStatement

    /**
     * Executes a stored procedure against the database
     *
     * @param string $procedure  The name of the stored procedure
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
     * @param array  $filters    An array of condition to be applyed in WHERE clause
     * @return array|bool Returns database request result
     */
    public function MongoDbExecuteProcedure($procedure,$params=[],$out_params=[],$tran_name=NULL,$type='',$firstrow=NULL,$lastrow=NULL,$sort=NULL,$filters=NULL,$log=TRUE) {
        $time=microtime(TRUE);
        //$query = $this->MongoDbPrepareProcedureStatement($procedure,$params,$out_params,$type,$firstrow,$lastrow,$sort,$filters);
        if(strlen($tran_name)>0) {
            if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name])) {
                $transaction=$tran_name;
            } else {
                throw new AException("FAILED QUERY: NULL database transaction in statement: ".$query,E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
            }//if(array_key_exists($tran_name,$this->transactions) && isset($this->transactions[$tran_name]))
        } else {
            $transaction='DefaultTransaction';
            if(!array_key_exists($transaction,$this->transactions) || !isset($this->transactions[$transaction]) || !is_resource($this->transactions[$transaction])) {
                $this->MongoDbBeginTran($transaction);
            }
        }//if(strlen($tran_name)>0)
        $final_result=NULL;
        try {
            $scope=[];
            $result=$this->connection->execute(new MongoCode($query,$scope),$params);
        } catch(MongoException $e) {
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        } catch(Exception $e) {
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
        NApp::Dlog($result,'MongoDbExecuteResult');
        /*if(strlen(ibase_errmsg())>0) { //|| $result===FALSE) {
            $iberror = ibase_errmsg();
            $iberrorcode = ibase_errcode();
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE PROCEDURE: #ErrorCode:$iberrorcode# $iberror in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',$iberrorcode);
        }//if(strlen(ibase_errmsg())>0 || $result===false)*/
        try {
            if(is_object($result) && is_a($result,'MongoCursor')) {
                while($result->hasNext()) {
                    $final_result[]=$result->getNext();
                }
            } else {
                $final_result=$result;
            }//if(is_object($result) && is_a($result,'MongoCursor'))
        } catch(Exception $e) {
            $this->MongoDbRollbackTran($transaction);
            throw new AException("FAILED EXECUTE PROCEDURE: ".$e->getMessage()." in statement: $query",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
        if(strlen($tran_name)==0) {
            $this->MongoDbCommitTran($transaction);
        }
        if($this->debug) {
            NApp::Dlog($query.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec','Query');
            if($this->debug2file) {
                NApp::Write2LogFile('Query: '.$query.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec','debug');
            }//if($this->debug2file)
            if($this->debug2screen) {
                echo '#Query: '.$query.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec#<br/>';
            }//if($this->debug2screen)
        }//if($this->debug)
        NApp::AppLoggerAddEvent(['action'=>__FUNCTION__,'data'=>$query,'duration'=>(microtime(TRUE) - $time)],$log);
        return change_array_keys_case($final_result,TRUE);
    }//END public function MongoDbExecuteProcedure

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
    public function MongoDbExecuteMethod($method,$property=NULL,$params=[],$extra_params=[],$log=TRUE) {
        $dbg_method=(strlen($property) ? $property.'->' : '').$method;
        $time=microtime(TRUE);
        $m_obj=strlen($property) ? $this->connection->$property : $this->connection;
        if(!strlen($method) || !method_exists($m_obj,$method)) {
            return FALSE;
        }
        $params=is_array($params) ? $params : [];
        try {
            $result=call_user_func_array([$m_obj,$method],$params);
        } catch(MongoException $e) {
            throw new AException("FAILED EXECUTE METHOD: ".$e->getMessage()." in statement: $dbg_method",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        } catch(Exception $e) {
            throw new AException("FAILED EXECUTE METHOD: ".$e->getMessage()." in statement: $dbg_method",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
        $final_result=NULL;
        try {
            if(is_object($result) && is_a($result,'MongoCursor')) {
                while($result->hasNext()) {
                    $final_result[]=$result->getNext();
                }
            } else {
                $final_result=$result;
            }//if(is_object($result) && is_a($result,'MongoCursor'))
        } catch(Exception $e) {
            throw new AException("FAILED EXECUTE METHOD: ".$e->getMessage()." in statement: $dbg_method",E_USER_ERROR,1,__FILE__,__LINE__,'mongodb',0);
        }//END try
        if($this->debug) {
            NApp::Dlog($dbg_method.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec','DbMethod');
            if($this->debug2file) {
                NApp::Write2LogFile('DbMethod: '.$dbg_method.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec','debug');
            }//if($this->debug2file)
            if($this->debug2screen) {
                echo '#DbMethod: '.$dbg_method.'   =>   Duration: '.number_format((microtime(TRUE) - $time),3,'.','').' sec#<br/>';
            }//if($this->debug2screen)
        }//if($this->debug)
        NApp::AppLoggerAddEvent(['action'=>__FUNCTION__,'data'=>$dbg_method.'('.print_r($params,TRUE).')','duration'=>(microtime(TRUE) - $time)],$log);
        return change_array_keys_case($final_result,TRUE);
    }//END public function MongoDbExecuteMethod

    /**
     * Escapes single quote charcater from a string
     * !!!DUMMY FUNCTION!!!
     *
     * @param string|array $param String to be escaped or
     *                            an array of strings
     * @return string|array Returns the escaped string or array
     */
    public function MongoDbEscapeString($param) {
        $result=NULL;
        // TODO: Dummy function !!! Change if necessary
        if(is_array($param)) {
            $result=[];
            foreach($param as $k=>$v) {
                $result[$k]=$v;
            }
        } else {
            $result=$param;
        }
        return $result;
    }//END public function MongoDbEscapeString
}//END class MongoDbDatabase extends Database
?>