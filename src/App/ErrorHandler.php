<?php
/**
 * Errors handler initialization file
 *
 * Contains ErrorHandler class and its initialization
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
/**
 * Errors handler class
 *
 * Treats all errors and displays them if necessary
 *
 * @package  NETopes\Base
 * @access   public
 */
class ErrorHandler implements NETopes\Core\App\IErrorHandler {
	/**
	 * @var    string Error log file path
	 * @access public
	 * @static
	 */
	protected static $errorlog_path = NULL;
	/**
	 * @var    string Error log file name
	 * @access public
	 * @static
	 */
	protected static $errorlog_file = NULL;
	/**
	 * @var    bool Flag for marking shutdown handler
	 * @access protected
	 * @static
	 */
	protected static $shutdown = FALSE;
	/**
	 * @var    array Non fatal errors stack
	 * @access protected
	 * @static
	 */
	protected static $errors_stack = NULL;
	/**
	 * @var    bool Flag for backtrace activation/inactivation
	 * @access protected
	 * @static
	 */
	protected static $backtrace = FALSE;
	/**
	 * @var    bool Silent mode on/off
	 * If on all warnings/notices/uncaught exceptions are dropped
	 * @access public
	 * @static
	 */
	public static $silent_mode = FALSE;
	/**
	 * @var    bool Re-throw warnings as \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static $rethrow_warnings = TRUE;
	/**
	 * @var    bool Re-throw notices as \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static $rethrow_notices = FALSE;
	/**
	 * @var    string Javascript show error function name
	 * @access public
	 * @static
	 */
	public static $js_show_error = 'ShowErrorDialog';
	/**
	 * Sets error log file name
	 *
	 * @param string $errorLogFile
	 * @return void
	 */
	public static function SetErrorLogFile(string $errorLogFile) {
		self::$errorlog_file = $errorLogFile;
	}//END public static function SetErrorLogFile
	/**
	 * Sets error log file path
	 *
	 * @param string $errorLogPath
	 * @return void
	 */
	public static function SetErrorLogPath(string $errorLogPath) {
		self::$errorlog_path = $errorLogPath;
	}//END public static function SetErrorLogPath
	/**
	 * Gets error reporting mode
	 *
	 * @return bool Returns TRUE if silent mode is on or error_reporting() is off
	 * @access public
	 * @static
	 */
	public static function IsSilent() {
		return (self::$silent_mode || error_reporting()===0);
	}//END public static function IsSilent
	/**
	 * Gets previous errors property state (with or without errors)
	 *
	 * @return bool Returns TRUE if there are errors in the error stack
	 * or FALSE otherwise
	 * @access public
	 * @static
	 */
	public static function HasErrors() {
		return (is_array(self::$errors_stack) && count(self::$errors_stack));
	}//END public static function HasErrors
	/**
	 * Gets previous errors stack
	 *
	 * @param bool $clear
	 * @return array Returns Errors stack array
	 * @access public
	 * @static
	 */
	public static function GetErrors($clear = FALSE) {
		$result = self::$errors_stack;
		if($clear) { self::$errors_stack = []; }
		return $result;
	}//END public static function GetErrors
	/**
	 * Adds an exception (of \NETopes\Core\AppException type) to the error stack
	 *
	 * @param \NETopes\Core\AppException $exception The exception to be added to the stack
	 * @return void
	 * @throws \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static function AddError(\NETopes\Core\AppException $exception) {
		if(!is_object($exception) || get_class($exception)!=='NETopes\Core\AppException') { throw new \NETopes\Core\AppException('Invalid exception !',E_WARNING,0); }
		$errfile = str_replace(_NAPP_ROOT_PATH,'',$exception->getFile());
		if(!is_array(self::$errors_stack)) { self::$errors_stack = []; }
		self::$errors_stack[] = array('errstr'=>$exception->getMessage(),'errno'=>$exception->getCode(),'errfile'=>$errfile,'errline'=>$exception->getLine());
		if(class_exists('NApp') && NApp::GetDebuggerState()) {
			NApp::_Elog($exception->getMessage().' -> file: '.$errfile.' -> line: '.$exception->getLine(),'Error['.$exception->getCode().']');
			if(self::$backtrace) { NApp::_Elog(debug_backtrace(),'Backtrace'); }
		}//if(class_exists('NApp') && NApp::GetDebuggerState())
	}//END public static function AddError
	/**
	 * Method called through set_error_handler() on error
	 *
	 * @param  int         $errno Error code
	 * @param  string      $errstr Error location (file)
	 * @param  string|null $errfile
	 * @param  int|null    $errline Error location (line)
	 * @param  array       $errcontext Error context
	 * @return void
	 * @throws \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static function ErrorHandlerFunction(int $errno = -1,string $errstr = 'Unknown error',?string $errfile = NULL,?int $errline = NULL,array $errcontext = []) {
		$errfile = str_replace(_NAPP_ROOT_PATH,'',$errfile);
		switch($errno) {
			case E_NOTICE:
				if(self::IsSilent() || self::$rethrow_notices!==TRUE) {
					if(!is_array(self::$errors_stack)) { self::$errors_stack = []; }
					self::$errors_stack[] = array('errstr'=>$errstr,'errno'=>$errno,'errfile'=>$errfile,'errline'=>$errline);
					if(class_exists('NApp') && NApp::GetDebuggerState()) {
						NApp::_Elog("$errstr -> file: $errfile -> line: $errline","Error[$errno]");
						if(self::$backtrace) { NApp::_Elog(debug_backtrace(),'Backtrace:'); }
					}//if(class_exists('NApp') && NApp::GetDebuggerState())
				} else {
					throw new \NETopes\Core\AppException($errstr,$errno,0,$errfile,$errline);
				}//if(self::IsSilent() || self::$rethrow_notices!==TRUE)
				break;
			case E_WARNING:
				$ibase_error = (strpos($errstr,'ibase_fetch_assoc()')!==FALSE
					|| strpos($errstr,'ibase_query()')!==FALSE
					|| strpos($errstr,'ibase_execute()')!==FALSE
					|| strpos($errstr,'ibase_prepare()')!==FALSE
					|| strpos($errstr,'ibase_trans()')!==FALSE
					|| strpos($errstr,'ibase_rollback()')!==FALSE
					|| strpos($errstr,'ibase_commit()')!==FALSE);
				if(!$ibase_error && (self::IsSilent() || self::$rethrow_warnings!==TRUE)) {
					if(!is_array(self::$errors_stack)) { self::$errors_stack = []; }
					self::$errors_stack[] = array('errstr'=>$errstr,'errno'=>$errno,'errfile'=>$errfile,'errline'=>$errline);
					if(class_exists('NApp') && NApp::GetDebuggerState()) {
						NApp::_Elog("$errstr -> file: $errfile -> line: $errline","Error[$errno]");
						if(self::$backtrace) { NApp::_Elog(debug_backtrace(),'Backtrace:'); }
					}//if(class_exists('NApp') && NApp::GetDebuggerState())
				} else {
					throw new \NETopes\Core\AppException($errstr,$errno,0,$errfile,$errline);
				}//if(!$ibase_error && (self::IsSilent() || self::$rethrow_warnings!==TRUE))
				break;
			default:
				if(self::IsSilent()) {
					if(!is_array(self::$errors_stack)) { self::$errors_stack = []; }
					self::$errors_stack[] = array('errstr'=>$errstr,'errno'=>$errno,'errfile'=>$errfile,'errline'=>$errline);
					if(class_exists('NApp') && NApp::GetDebuggerState()) {
						NApp::_Elog("$errstr -> file: $errfile -> line: $errline","Error[$errno]");
						if(self::$backtrace) { NApp::_Elog(debug_backtrace(),'Backtrace:'); }
					}//if(class_exists('NApp') && NApp::GetDebuggerState())
				} else {
					throw new \NETopes\Core\AppException($errstr,$errno,1,$errfile,$errline);
				}//if(self::IsSilent())
				break;
		}//END switch
	}//END public static function ErrorHandlerFunction
	/**
	 * Method called through set_exception_handler() on exception thrown
	 *
	 * @param  object $exception The thrown exception
	 * @return void
	 * @access public
	 * @static
	 * @throws \NETopes\Core\AppException
	 */
	public static function ExceptionHandlerFunction($exception) {
		if(get_class($exception)!='NETopes\Core\AppException') {
			switch(get_class($exception)) {
				case 'PDOException':
					$e = new \NETopes\Core\AppException($exception->getMessage(),E_ERROR,1,$exception->getFile(),$exception->getLine(),'pdo',$exception->getCode(),$exception->errorInfo);
					break;
				default:
					$e = new \NETopes\Core\AppException($exception->getMessage(),$exception->getCode(),1,$exception->getFile(),$exception->getLine());
					break;
			}//END switch
		} else {
			$e = $exception;
		}//if(get_class($exception)!='NETopes\Core\AppException')
		if(self::IsSilent()) {
			self::AddError($e);
		} else {
			self::ShowErrors($e);
		}//if(self::IsSilent())
	}//END public static function ExceptionHandlerFunction
	/**
	 * Method called through register_shutdown_function() on shutdown
	 *
	 * @param  bool $output Flag to allow or restrict output
	 * @return void
	 * @access public
	 * @static
	 * @throws \NETopes\Core\AppException
	 */
	public static function ShutDownHandlerFunction(bool $output = TRUE) {
		if(!$output) { return; }
		// $error_types = array('E_ERROR'=>1,'E_PARSE'=>4,'E_CORE_ERROR'=>16,'E_CORE_WARNING'=>32,'E_COMPILE_ERROR'=>64,'E_COMPILE_WARNING'=>128,'E_STRICT'=>2048);
		$e = error_get_last();
		if(is_array($e) && count($e)) { //&& in_array($e['type'],$error_types)
			$errfile = str_replace(_NAPP_ROOT_PATH,'',$e['file']);
			self::AddError(new \NETopes\Core\AppException($e['message'],$e['type'],0,$errfile,$e['line']));
		}//if(is_array($e) && count($e))
		if(!self::IsSilent() && self::HasErrors()) { self::ShowErrors(); }
		self::$shutdown = TRUE;
	}//END public static function ShutDownHandlerFunction
	/**
	 * Processes the errors stack (optionaly adding a last error)
	 * and sends errors to be displayed or logged
	 *
	 * @param \NETopes\Core\AppException $exception The last exception to be added to the stack
	 * befor showing error
	 * @return void
	 * @throws \NETopes\Core\AppException
	 * @access public
	 * @static
	 */
	public static function ShowErrors(\NETopes\Core\AppException $exception = NULL) {
		if(is_object($exception)) { self::AddError($exception); }
		if(!is_array(self::$errors_stack) || !count(self::$errors_stack)) { return; }
		if(count(self::$errors_stack)==1) {
			$errfile = $errline = NULL;
			$err = self::$errors_stack[0];
			$errno = array_key_exists('errno',$err) && is_numeric($err['errno']) ? $err['errno'] : NULL;
			$errstr = (array_key_exists('errstr',$err) && $err['errstr']) ? $err['errstr'] : 'Unknown error';
			if(strpos($errstr,' deadlock ')!==FALSE) { //FirebirdSQL specific error
				$errno = $errno ? $errno : 0;
				$errstr = '<span>'.(method_exists('\Translate','Get') ? Translate::Get('server_busy_message') : 'Server busy !').'</span>';
			} elseif((strpos($errstr,'ibase_fetch_assoc()')!==FALSE || strpos($errstr,'ibase_query()')!==FALSE) && strpos($errstr,'EXP_')!==FALSE) { //FirebirdSQL specific error
				$errno = substr($errstr,strpos($errstr,'EXP_'),8);
				$errstr = method_exists('\Translate','Get') ? Translate::Get($errno) : $errstr;
			} elseif($errno==8001 && strpos($errstr,'[SQLSTATE] => 08001')!==FALSE) { //SQL Server specific error
				$errstr = '<span>'.(method_exists('\Translate','Get') ? Translate::Get('msg_db_connection_error') : 'Database connection error!').'</span>';
			} else {
				$errno = $errno ? $errno : -1;
				$errfile = (array_key_exists('errfile',$err) && $err['errfile']) ? $err['errfile'] : NULL;
				$errline = (array_key_exists('errline',$err) && $err['errline']) ? $err['errline'] : NULL;
			}//if(strpos($errstr,' deadlock ')!==FALSE)
			self::$errors_stack = NULL;
			if(class_exists('NApp')) { NApp::Log2File(array('type'=>'error','message'=>$errstr.' >>> ','no'=>$errno,'file'=>$errfile,'line'=>$errline),self::$errorlog_path.self::$errorlog_file); }
			self::DisplayError($errstr,$errno,$errfile,$errline);
			return;
		}//if(count(self::$errors_stack)==1)
		$errstr = '';
		foreach(self::$errors_stack as $err) {
			$errstr .= ($errstr ? '<br><br>' : '');
			$cerrstr = (array_key_exists('errstr',$err) && $err['errstr']) ? $err['errstr'] : 'Unknown error';
			if(strpos($cerrstr,' deadlock ')!==FALSE) {
				$errstr .= '<span>'.(method_exists('\Translate','Get') ? Translate::Get('server_busy_message') : 'Server busy !').'</span><br>';
			} elseif((strpos($cerrstr,'ibase_fetch_assoc()')!==FALSE || strpos($cerrstr,'ibase_query()')!==FALSE) && strpos($cerrstr,'EXP_')!==FALSE) {
				$errno = substr($cerrstr,strpos($cerrstr,'EXP_'),8);
				$errstr .= $errno ? 'Code: '.$errno.'<br>' : '';
				$errstr .= (method_exists('\Translate','Get') ? Translate::Get($errno) : $cerrstr).'<br>';
			} else {
				$errstr .= 'Code: '.((array_key_exists('errno',$err) && $err['errno']) ? $err['errno'] : '-1').'<br>';
				$errstr .= $cerrstr.'<br>';
				$errloc = ((array_key_exists('errfile',$err) && $err['errfile']) ? ' -> file: '.$err['errfile'].' ' : '');
				$errloc .= ((array_key_exists('errline',$err) && $err['errline']) ? ' -> line: '.$err['errline'] : '');
				$errstr .= $errloc ? 'Location: '.$errloc.'<br>' : '';
			}//if(strpos($errstr,' deadlock ')!==FALSE)
		}//END foreach
		self::$errors_stack = NULL;
		if(class_exists('NApp')) { NApp::Log2File(array('type'=>'error','message'=>$errstr),self::$errorlog_path.self::$errorlog_file); }
		self::DisplayError($errstr);
	}//END public static function ShowErrors
	/**
	 * If silent errors is not off or debug mode is on
	 * displays an error (modal if GUI is loaded)
	 *
	 * @param  string $errstr Error location (file)
	 * @param  int $errno Error code
	 * @param null    $errfile
	 * @param  int $errline Error location (line)
	 * @return void
	 * @access public
	 * @static
	 */
	public static function DisplayError($errstr = '',$errno = NULL,$errfile = NULL,$errline = NULL) {
		if(class_exists('NApp') && NApp::$silent_errors && !NApp::$debug) { return; }
		if(!$errno && !$errfile && !$errline) {
			$error_str = ($errstr ? $errstr : 'Unknown error!').'<br>';
		} else {
			if(strpos($errstr,' deadlock ')!==FALSE) {
				$error_str = '<span>'.(method_exists('\Translate','Get') ? Translate::Get('server_busy_message') : 'Server busy !').'</span><br>';
			} elseif((strpos($errstr,'ibase_fetch_assoc()')!==FALSE || strpos($errstr,'ibase_query()')!==FALSE) && strpos($errstr,'EXP_')!==FALSE) {
				$errno = substr($errstr,strpos($errstr,'EXP_'),8);
				$error_str = $errno ? 'Code: '.$errno.'<br>' : '';
				$error_str .= (method_exists('\Translate','Get') ? Translate::Get($errno) : $errstr).'<br>';
			} else {
				if($errno==-1) {
					$error_str = ($errstr ? $errstr : 'Unknown error!');
				} else {
					$error_str = $errno ? 'Code: '.$errno.'<br>' : '';
					$error_str .= ($errstr ? $errstr : 'Unknown error !').'<br>';
					$error_loc = ($errfile ? ' -> file: '.$errfile.' ' : '').($errfile ? ' -> line: '.$errline : '');
					$error_str .= $error_loc ? 'Location: '.$error_loc.'<br>' : '';
				}//if($errno==-1)
			}//if(strpos($errstr,' deadlock ')!==FALSE)
			$error_str .= self::$backtrace ? '<br>Backtrace: '.print_r(debug_backtrace(),TRUE).'<br>' : '';
		}//if(!$errno && !$errfile && !$errline)
		if(strlen(self::$js_show_error) && class_exists('NApp') && is_object(NApp::GetCurrentInstance()) && NApp::$gui_loaded) {
			$error_str = \GibberishAES::enc($error_str,'HTML');
			if(NApp::ajax()) {
				if(is_object(NApp::arequest()) && !self::$shutdown) {
					NApp::arequest()->ExecuteJs(self::$js_show_error."('{$error_str}',true);");
				} else {
					$result = '<input type="hidden" class="IPEPText" value="'.$error_str.'">';
					echo $result;
				}//if(is_object(NApp::arequest()) && !self::$shutdown)
			} else {
				NApp::_ExecJs(self::$js_show_error."('{$error_str}.',TRUE);");
			}//if(NApp::ajax())
		} else {
			$result = "\t".'<div style="background-color: #CF0000; color: #FFFFFF; padding: 5px; font-size: 16px; text-align: center;">'."\n";
			$result .= "\t\t".'<strong>Error</strong>'."\n";
			$result .= "\t\t".'<div style="background-color: #FFFFFF; color: #CF0000; padding: 10px; font-size: 14px; text-align: left;">'."\n";
			$result .= "\t\t\t".'<span>'.str_replace(array('\\'),array('/'),$error_str).'</span>'."\n";
			$result .= "\t\t".'</div>'."\n";
			$result .= "\t".'</div>'."\n";
			echo $result;
		}//if(strlen(self::$js_show_error) && class_exists('NApp') && is_object(NApp::GetCurrentInstance()) && NApp::$gui_loaded)
	}//END public function DisplayError
}//END class ErrorHandler