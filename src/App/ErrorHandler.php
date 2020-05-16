<?php
/**
 * Errors handler initialization file
 * Contains ErrorHandler class and its initialization
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.3.2.0
 * @filesource
 */
use NETopes\Core\App\IErrorHandler;
use NETopes\Core\AppException;

/**
 * Class ErrorHandler
 * Treats all errors and displays them if necessary
 *
 * @package  NETopes\Base
 */
class ErrorHandler implements IErrorHandler {
    /**
     * @var    string Error log file path
     */
    protected static $errorLogPath=NULL;
    /**
     * @var    string Error log file name
     */
    protected static $errorLogFile=NULL;
    /**
     * @var    bool Flag for marking shutdown handler
     */
    protected static $shutdown=FALSE;
    /**
     * @var    array Non fatal errors stack
     */
    protected static $errorsStack=NULL;
    /**
     * @var    bool Flag for backtrace activation/inactivation
     */
    protected static $backtrace=FALSE;
    /**
     * @var    callable|null Optional hook for the shutdown function
     */
    protected static $executeOnShutdown=NULL;
    /**
     * @var    bool Silent mode on/off
     * If on all warnings/notices/uncaught exceptions are dropped
     */
    public static $silentMode=FALSE;
    /**
     * @var    bool Re-throw warnings as AppException
     */
    public static $rethrowWarnings=TRUE;
    /**
     * @var    bool Re-throw notices as AppException
     */
    public static $rethrowNotices=FALSE;
    /**
     * @var    string Javascript show error function name
     */
    public static $jsShowError='ShowErrorDialog';

    /**
     * Sets error log file name
     *
     * @param string $errorLogFile
     * @return void
     */
    public static function SetErrorLogFile(string $errorLogFile) {
        self::$errorLogFile=$errorLogFile;
    }//END public static function SetErrorLogFile

    /**
     * Sets error log file path
     *
     * @param string $errorLogPath
     * @return void
     */
    public static function SetErrorLogPath(string $errorLogPath) {
        self::$errorLogPath=$errorLogPath;
    }//END public static function SetErrorLogPath

    /**
     * Sets on shutdown callable hook
     *
     * @param callable $onShutdown
     * @return void
     */
    public static function SetExecuteOnShutdown(callable $onShutdown) {
        self::$executeOnShutdown=$onShutdown;
    }//END public static function SetExecuteOnShutdown

    /**
     * Gets error reporting mode
     *
     * @return bool Returns TRUE if silent mode is on or error_reporting() is off
     */
    public static function IsSilent() {
        return (self::$silentMode || error_reporting()===0);
    }//END public static function IsSilent

    /**
     * Gets previous errors property state (with or without errors)
     *
     * @return bool Returns TRUE if there are errors in the error stack
     * or FALSE otherwise
     */
    public static function HasErrors() {
        return (is_array(self::$errorsStack) && count(self::$errorsStack));
    }//END public static function HasErrors

    /**
     * Gets previous errors stack
     *
     * @param bool $clear
     * @return array Returns Errors stack array
     */
    public static function GetErrors($clear=FALSE) {
        $result=self::$errorsStack;
        if($clear) {
            self::$errorsStack=[];
        }
        return $result;
    }//END public static function GetErrors

    /**
     * Adds an Exception or an Error to the error stack
     *
     * @param \Throwable $e The exception/error to be added to the stack
     * @return void
     */
    public static function AddError(Throwable $e) {
        $errFile=str_replace(_NAPP_ROOT_PATH,'',$e->getFile());
        if(!is_array(self::$errorsStack)) {
            self::$errorsStack=[];
        }
        self::$errorsStack[]=['errMessage'=>$e->getMessage(),'errNo'=>$e->getCode(),'errFile'=>$errFile,'errLine'=>$e->getLine()];
        if(class_exists('NApp') && NApp::GetLoggerState()) {
            NApp::Elog($e,'ErrorHandler');
            if(self::$backtrace) {
                NApp::Elog(debug_backtrace(),'Backtrace');
            }
        }//if(class_exists('NApp') && NApp::GetLoggerState())
    }//END public static function AddError

    /**
     * Method called through set_exception_handler() on exception thrown
     *
     * @param \Throwable $exception The thrown exception
     * @return void
     * @throws AppException
     */
    public static function ExceptionHandlerFunction($exception) {
        if($exception instanceof AppException) {
            $e=$exception;
        } elseif($exception instanceof Error) {
            $e=AppException::GetInstance($exception,'php',-1);
        } elseif($exception instanceof PDOException) {
            $e=AppException::GetInstance($exception,'pdo');
        } else {
            $e=AppException::GetInstance($exception,'other');
        }//if($exception instanceof AppException)
        if(self::IsSilent()) {
            self::AddError($e);
        } else {
            self::ShowErrors($e);
        }//if(self::IsSilent())
    }//END public static function ExceptionHandlerFunction

    /**
     * Method called through set_error_handler() on error
     *
     * @param int         $errNo      Error code
     * @param string      $errMessage Error location (file)
     * @param string|null $errFile
     * @param int|null    $errLine    Error location (line)
     * @param array       $errContext Error context
     * @return void
     * @throws AppException
     */
    public static function ErrorHandlerFunction(int $errNo=-1,string $errMessage='Unknown error',?string $errFile=NULL,?int $errLine=NULL,?array $errContext=[]) {
        $errFile=str_replace(_NAPP_ROOT_PATH,'',$errFile);
        switch($errNo) {
            case E_NOTICE:
                if(self::IsSilent() || self::$rethrowNotices!==TRUE) {
                    if(!is_array(self::$errorsStack)) {
                        self::$errorsStack=[];
                    }
                    self::$errorsStack[]=['errMessage'=>$errMessage,'errNo'=>$errNo,'errFile'=>$errFile,'errLine'=>$errLine];
                    if(class_exists('NApp') && NApp::GetLoggerState()) {
                        $errException=new AppException($errMessage,$errNo,0,$errFile,$errLine);
                        NApp::Elog($errException,'ErrorHandler');
                        if(self::$backtrace) {
                            NApp::Elog(debug_backtrace(),'BACKTRACE>>');
                        }
                    }//if(class_exists('NApp') && NApp::GetLoggerState())
                } else {
                    throw new AppException($errMessage,$errNo,0,$errFile,$errLine);
                }//if(self::IsSilent() || self::$rethrow_notices!==TRUE)
                break;
            case E_WARNING:
                $ibase_error=(strpos($errMessage,'ibase_fetch_assoc()')!==FALSE
                    || strpos($errMessage,'ibase_query()')!==FALSE
                    || strpos($errMessage,'ibase_execute()')!==FALSE
                    || strpos($errMessage,'ibase_prepare()')!==FALSE
                    || strpos($errMessage,'ibase_trans()')!==FALSE
                    || strpos($errMessage,'ibase_rollback()')!==FALSE
                    || strpos($errMessage,'ibase_commit()')!==FALSE);
                if(!$ibase_error && (self::IsSilent() || self::$rethrowWarnings!==TRUE)) {
                    if(!is_array(self::$errorsStack)) {
                        self::$errorsStack=[];
                    }
                    self::$errorsStack[]=['errMessage'=>$errMessage,'errNo'=>$errNo,'errFile'=>$errFile,'errLine'=>$errLine];
                    if(class_exists('NApp') && NApp::GetLoggerState()) {
                        $errException=new AppException($errMessage,$errNo,0,$errFile,$errLine);
                        NApp::Elog($errException,'ErrorHandler');
                        if(self::$backtrace) {
                            NApp::Elog(debug_backtrace(),'BACKTRACE>>');
                        }
                    }//if(class_exists('NApp') && NApp::GetLoggerState())
                } else {
                    throw new AppException($errMessage,$errNo,0,$errFile,$errLine);
                }//if(!$ibase_error && (self::IsSilent() || self::$rethrow_warnings!==TRUE))
                break;
            default:
                if(self::IsSilent()) {
                    if(!is_array(self::$errorsStack)) {
                        self::$errorsStack=[];
                    }
                    self::$errorsStack[]=['errMessage'=>$errMessage,'errNo'=>$errNo,'errFile'=>$errFile,'errLine'=>$errLine];
                    if(class_exists('NApp') && NApp::GetLoggerState()) {
                        $errException=new AppException($errMessage,$errNo,0,$errFile,$errLine);
                        NApp::Elog($errException,'ErrorHandler');
                        if(self::$backtrace) {
                            NApp::Elog(debug_backtrace(),'BACKTRACE>>');
                        }
                    }//if(class_exists('NApp') && NApp::GetLoggerState())
                } else {
                    throw new AppException($errMessage,$errNo,1,$errFile,$errLine);
                }//if(self::IsSilent())
                break;
        }//END switch
    }//END public static function ErrorHandlerFunction

    /**
     * Method called through register_shutdown_function() on shutdown
     *
     * @param bool $output Flag to allow or restrict output
     * @return void
     * @throws AppException
     */
    public static function ShutDownHandlerFunction(bool $output=TRUE) {
        if(is_callable(self::$executeOnShutdown)) {
            try {
                call_user_func(self::$executeOnShutdown);
            } catch(Exception $e) {
                self::AddError(AppException::GetInstance($e));
            }//END try
        }
        if(!$output) {
            return;
        }
        // $error_types = array('E_ERROR'=>1,'E_PARSE'=>4,'E_CORE_ERROR'=>16,'E_CORE_WARNING'=>32,'E_COMPILE_ERROR'=>64,'E_COMPILE_WARNING'=>128,'E_STRICT'=>2048);
        $e=error_get_last();
        if(is_array($e) && count($e)) {
            $errFile=str_replace(_NAPP_ROOT_PATH,'',$e['file']);
            self::AddError(new AppException($e['message'],$e['type'],0,$errFile,$e['line']));
        }//if(is_array($e) && count($e))
        if(!self::IsSilent() && self::HasErrors()) {
            self::ShowErrors();
        }
        self::$shutdown=TRUE;
    }//END public static function ShutDownHandlerFunction

    /**
     * Processes the errors stack (optionaly adding a last error)
     * and sends errors to be displayed or logged
     *
     * @param AppException $exception The last exception to be added to the stack
     *                                befor showing error
     * @return void
     * @throws AppException
     */
    public static function ShowErrors(AppException $exception=NULL) {
        if(is_object($exception)) {
            self::AddError($exception);
        }
        if(!is_array(self::$errorsStack) || !count(self::$errorsStack)) {
            return;
        }
        if(count(self::$errorsStack)==1) {
            $errFile=$errLine=NULL;
            $err=self::$errorsStack[0];
            $errNo=array_key_exists('errNo',$err) && is_numeric($err['errNo']) ? $err['errNo'] : NULL;
            $errMessage=(array_key_exists('errMessage',$err) && $err['errMessage']) ? $err['errMessage'] : 'Unknown error';
            if(strpos($errMessage,' deadlock ')!==FALSE) { //FirebirdSQL specific error
                $errNo=$errNo ? $errNo : 0;
                $errMessage='<span>'.(method_exists('\Translate','Get') ? Translate::Get('server_busy_message') : 'Server busy !').'</span>';
            } elseif((strpos($errMessage,'ibase_fetch_assoc()')!==FALSE || strpos($errMessage,'ibase_query()')!==FALSE) && strpos($errMessage,'EXP_')!==FALSE) { //FirebirdSQL specific error
                $errNo=substr($errMessage,strpos($errMessage,'EXP_'),8);
                $errMessage=method_exists('\Translate','Get') ? Translate::Get($errNo) : $errMessage;
            } elseif($errNo==8001 && strpos($errMessage,'[SQLSTATE] => 08001')!==FALSE) { //SQL Server specific error
                $errMessage='<span>'.(method_exists('\Translate','Get') ? Translate::Get('msg_db_connection_error') : 'Database connection error!').'</span>';
            } else {
                $errNo=$errNo ? $errNo : -1;
                $errFile=(array_key_exists('errFile',$err) && $err['errFile']) ? $err['errFile'] : NULL;
                $errLine=(array_key_exists('errLine',$err) && $err['errLine']) ? $err['errLine'] : NULL;
            }//if(strpos($errMessage,' deadlock ')!==FALSE)
            self::$errorsStack=NULL;
            if(class_exists('NApp')) {
                NApp::LogToFile(['level'=>'error','message'=>$errMessage,'file'=>$errFile,'line'=>$errLine],__FILE__,self::$errorLogPath.self::$errorLogFile);
            }
            self::DisplayError($errMessage,$errNo,$errFile,$errLine);
            return;
        }//if(count(self::$errorsStack)==1)
        $errMessage='';
        foreach(self::$errorsStack as $err) {
            $errMessage.=($errMessage ? '<br><br>' : '');
            $cerrMessage=(array_key_exists('errMessage',$err) && $err['errMessage']) ? $err['errMessage'] : 'Unknown error';
            if(strpos($cerrMessage,' deadlock ')!==FALSE) {
                $errMessage.='<span>'.(method_exists('\Translate','Get') ? Translate::Get('server_busy_message') : 'Server busy !').'</span><br>';
            } elseif((strpos($cerrMessage,'ibase_fetch_assoc()')!==FALSE || strpos($cerrMessage,'ibase_query()')!==FALSE) && strpos($cerrMessage,'EXP_')!==FALSE) {
                $errNo=substr($cerrMessage,strpos($cerrMessage,'EXP_'),8);
                $errMessage.=$errNo ? 'Code: '.$errNo.'<br>' : '';
                $errMessage.=(method_exists('\Translate','Get') ? Translate::Get($errNo) : $cerrMessage).'<br>';
            } else {
                $errMessage.='Code: '.((array_key_exists('errNo',$err) && $err['errNo']) ? $err['errNo'] : '-1').'<br>';
                $errMessage.=$cerrMessage.'<br>';
                $errloc=((array_key_exists('errFile',$err) && $err['errFile']) ? ' -> file: '.$err['errFile'].' ' : '');
                $errloc.=((array_key_exists('errLine',$err) && $err['errLine']) ? ' -> line: '.$err['errLine'] : '');
                $errMessage.=$errloc ? 'Location: '.$errloc.'<br>' : '';
            }//if(strpos($errMessage,' deadlock ')!==FALSE)
        }//END foreach
        self::$errorsStack=NULL;
        if(class_exists('NApp')) {
            NApp::LogToFile(['level'=>'error','message'=>$errMessage],__FILE__,self::$errorLogPath.self::$errorLogFile);
        }
        self::DisplayError($errMessage);
    }//END public static function ShowErrors

    /**
     * If silent errors is not off or debug mode is on
     * displays an error (modal if GUI is loaded)
     *
     * @param string $errMessage Error location (file)
     * @param int    $errNo      Error code
     * @param null   $errFile
     * @param int    $errLine    Error location (line)
     * @return void
     */
    public static function DisplayError($errMessage='',$errNo=NULL,$errFile=NULL,$errLine=NULL) {
        if(class_exists('NApp') && NApp::$silentErrors && !NApp::$debug) {
            return;
        }
        if(!$errNo && !$errFile && !$errLine) {
            $error_str=($errMessage ? $errMessage : 'Unknown error!').'<br>';
        } else {
            if(strpos($errMessage,' deadlock ')!==FALSE) {
                $error_str='<span>'.(method_exists('\Translate','Get') ? Translate::Get('server_busy_message') : 'Server busy !').'</span><br>';
            } elseif((strpos($errMessage,'ibase_fetch_assoc()')!==FALSE || strpos($errMessage,'ibase_query()')!==FALSE) && strpos($errMessage,'EXP_')!==FALSE) {
                $errNo=substr($errMessage,strpos($errMessage,'EXP_'),8);
                $error_str=$errNo ? 'Code: '.$errNo.'<br>' : '';
                $error_str.=(method_exists('\Translate','Get') ? Translate::Get($errNo) : $errMessage).'<br>';
            } else {
                if($errNo==-1) {
                    $error_str=($errMessage ? $errMessage : 'Unknown error!');
                } else {
                    $error_str=$errNo ? 'Code: '.$errNo.'<br>' : '';
                    $error_str.=($errMessage ? $errMessage : 'Unknown error !').'<br>';
                    $error_loc=($errFile ? ' -> file: '.$errFile.' ' : '').($errFile ? ' -> line: '.$errLine : '');
                    $error_str.=$error_loc ? 'Location: '.$error_loc.'<br>' : '';
                }//if($errNo==-1)
            }//if(strpos($errMessage,' deadlock ')!==FALSE)
            $error_str.=self::$backtrace ? '<br>Backtrace: '.print_r(debug_backtrace(),TRUE).'<br>' : '';
        }//if(!$errNo && !$errFile && !$errLine)
        if(strlen(self::$jsShowError) && class_exists('NApp') && NApp::$guiLoaded) {
            $error_str=GibberishAES::enc($error_str,'HTML');
            if(NApp::ajax()) {
                if(NApp::IsValidAjaxRequest() && !self::$shutdown) {
                    NApp::AddJsScript(self::$jsShowError."('{$error_str}',true);");
                } else {
                    $result='<input type="hidden" class="IPEPText" value="'.$error_str.'">';
                    echo $result;
                }//if(NApp::IsValidAjaxRequest() && !self::$shutdown)
            } else {
                NApp::AddJsScript(self::$jsShowError."('{$error_str}.',TRUE);");
            }//if(NApp::ajax())
        } else {
            $result="\t".'<div style="background-color: #CF0000; color: #FFFFFF; padding: 5px; font-size: 16px; text-align: center;">'."\n";
            $result.="\t\t".'<strong>Error</strong>'."\n";
            $result.="\t\t".'<div style="background-color: #FFFFFF; color: #CF0000; padding: 10px; font-size: 14px; text-align: left;">'."\n";
            $result.="\t\t\t".'<span>'.str_replace('\\','/',$error_str).'</span>'."\n";
            $result.="\t\t".'</div>'."\n";
            $result.="\t".'</div>'."\n";
            echo $result;
        }//if(strlen(self::$jsShowError) && class_exists('NApp') && NApp::$guiLoaded)
    }//END public function DisplayError
}//END class ErrorHandler