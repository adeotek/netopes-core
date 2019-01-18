<?php
/**
 * NETopes application debugger class file.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;

/**
 * Class Debugger
 *
 * @package  NETopes\Core\App
 * @access   public
 */
class Debugger {
    /**
     * DBG_DEBUG constant definition
     */
    const DBG_DEBUG = 'log';
    /**
     * DBG_WARNING constant definition
     */
    const DBG_WARNING = 'warning';
    /**
     * DBG_ERROR constant definition
     */
    const DBG_ERROR = 'error';
    /**
     * DBG_INFO constant definition
     */
    const DBG_INFO = 'info';
	/**
	 * @var    array List of debugging plug-ins. To activate/inactivate an plug-in, change the value for "active" key corresponding to that plug-in.
	 * @access protected
	 */
	protected $debug_extensions = [
		'Firefox'=>['QuantumPHP'=>['active'=>FALSE,'js'=>FALSE]],
		'Chrome'=>[
			'PhpConsole'=>['active'=>TRUE,'js'=>FALSE],
			'QuantumPHP'=>['active'=>FALSE,'js'=>FALSE],
		],
		'Other'=>['QuantumPHP'=>['active'=>FALSE,'js'=>TRUE]],
	];
	/**
	 * @var        boolean Debug mode on/off
	 * @access     protected
	 */
	protected $enabled = FALSE;
	/**
	 * @var    array Array containing debugging plug-ins objects.
	 * @access protected
	 */
	protected $debug_objects = FALSE;
	/**
	 * @var    array Array containing debugging plug-ins JavaScripts.
	 * @access protected
	 */
	protected $debug_scripts = [];
	/**
	 * @var    array Array containing started debug timers.
	 * @access protected
	 * @static
	 */
	protected static $debug_timers = [];
	/**
	 * @var        string Browser console password (extension)
	 * @access     protected
	 */
	protected $js_console_password = '';
	/**
	 * @var        boolean Show exception trace on Elog
	 * @access     public
	 */
	public $showExceptionsTrace = FALSE;
	/**
	 * @var        string Relative path to the logs folder
	 * @access     public
	 */
	public $logs_path = '.logs';
	/**
	 * @var        string Name of the main log file
	 * @access     public
	 */
	public $log_file = 'app.log';
	/**
	 * @var        string Name of the errors log file
	 * @access     public
	 */
	public $errors_log_file = 'errors.log';
	/**
	 * @var        string Name of the debugging log file
	 * @access     public
	 */
	public $debug_log_file = 'debug.log';
	/**
	 * Debugger class constructor
	 *
	 * @param  boolean $debug Debug mode TRUE/FALSE
	 * @param  string  $logs_path Logs directory relative path
	 * @param  string  $tmp_path Temp directory absolute path
	 * (must be outside document root)
	 * @param null     $console_password
	 * @throws \Exception
	 * @access public
	 */
    public function __construct($debug,$logs_path = NULL,$tmp_path = NULL,$console_password = NULL) {
		if($debug!==TRUE) { return; }
		if(strlen($console_password)) { $this->js_console_password = $console_password; }
		if(strlen($logs_path)) { $this->logs_path = $logs_path; }
		if(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Chrome/',$_SERVER['HTTP_USER_AGENT'])===1) {
			$this->LoggerInit('Chrome',$tmp_path);
		} elseif(array_key_exists('HTTP_USER_AGENT',$_SERVER) && preg_match('/Firefox/',$_SERVER['HTTP_USER_AGENT'])===1) {
			$this->LoggerInit('Firefox',$tmp_path);
		} else {
			$this->LoggerInit('Other',$tmp_path);
		}//if(...
		$this->enabled = (is_array($this->debug_objects) && count($this->debug_objects));
	}//END protected function __construct
	/**
	 * Enable PHP browser logger
	 *
	 * @param  string $browser_type Browser type extracted from HTTP_USER_AGENT
	 * @param  string $tmp_path Temp directory absolute path
	 * @return void
	 * @access protected
	 * @throws \Exception
	 */
	protected function LoggerInit($browser_type,$tmp_path = NULL) {
		if(!is_string($browser_type) || !strlen($browser_type) || !is_array($this->debug_extensions) || !count($this->debug_extensions) || !array_key_exists($browser_type,$this->debug_extensions) || !is_array($this->debug_extensions[$browser_type]) || !count($this->debug_extensions[$browser_type])) { return; }
		foreach($this->debug_extensions[$browser_type] as $dk=>$dv) {
			if($dv['active']!==TRUE) { continue; }
			switch($dk) {
				case 'PhpConsole':
					if(!class_exists('\PhpConsole\Connector')) { continue; }
					\PhpConsole\Connector::setPostponeStorage(new \PhpConsole\Storage\File((strlen($tmp_path) ? rtrim($tmp_path,'/') : '').'/phpcons.data'));
					$this->debug_objects[$dk] = \PhpConsole\Connector::getInstance();
					if(\PhpConsole\Connector::getInstance()->isActiveClient()) {
						$this->debug_objects[$dk]->setServerEncoding('UTF-8');
						if(isset($this->js_console_password) && strlen($this->js_console_password)) { $this->debug_objects[$dk]->setPassword($this->js_console_password); }
					} else {
						$this->debug_objects[$dk] = NULL;
					}//if(\PhpConsole\Connector::getInstance()->isActiveClient())
					break;
				case 'QuantumPHP':
					if(!class_exists('\QuantumPHP')) { continue; }
					switch($browser_type) {
						case 'Chrome':
							\QuantumPHP::$MODE = 3;
							break;
						case 'Firefox':
							\QuantumPHP::$MODE = 2;
							break;
						default:
							\QuantumPHP::$MODE = 1;
							if(!is_array($this->debug_scripts)) { $this->debug_scripts = []; }
							$this->debug_scripts[$dk] = 'QuantumPHP.min.js';
							break;
					}//END swith
					$this->debug_objects[$dk] = $dk;
					break;
			}//END switch
		}//END foreach
	}//END protected function LoggerInit
	/**
	 * Send data to browser
	 *
	 * @return void
	 * @access public
	 */
	public function SendData() {
		if($this->enabled && is_array($this->debug_objects)) {
			foreach($this->debug_objects as $dk=>$dv) {
				switch($dk) {
					case 'QuantumPHP':
							\QuantumPHP::send();
						break;
					default:
						break;
				}//END switch
			}//END foreach
		}//if($this->enabled && is_array($this->debug_objects))
	}//END public function SendData
	/**
	 * Get debugging plug-ins JavaScripts to be loaded
	 *
	 * @return array Returns an array of debugging plug-ins JavaScripts to be loaded
	 * @access public
	 */
	public function GetScripts() {
		return ($this->enabled ? $this->debug_scripts : []);
	}//END public function GetScripts
	/**
	 * Get enabled state
	 *
	 * @return bool Returns TRUE if enabled or FALSE otherwise
	 * @access public
	 */
	public function IsEnabled() {
		return $this->enabled;
	}//END public function IsEnabled
	/**
	 * Displays a value in the debugger plugin
	 *
	 * @param  mixed $value Value to be displayed by the debug objects
	 * @param  string $label Label assigned tot the value to be displayed
	 * @param  string $type Debug type defined bay NETopes\Core\Debugger::DBG_... constants
	 * (NETopes\Core\Debugger::DBG_DEBUG, NETopes\Core\Debugger::DBG_WARNING, NETopes\Core\Debugger::DBG_ERROR or NETopes\Core\Debugger::DBG_INFO)
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Debug($value,$label = '',$type = self::DBG_DEBUG,$file = FALSE,$path = FALSE) {
		if(!$this->enabled || !is_array($this->debug_objects)) { return; }
		if($file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
		}//if($file===TRUE)
		foreach($this->debug_objects as $dn=>$do) {
				switch($dn) {
					case 'PhpConsole':
					if(is_object($do)) {
						switch($type) {
							case self::DBG_WARNING:
							case self::DBG_ERROR:
							case self::DBG_INFO:
						  	case self::DBG_DEBUG:
						  	default:
								$do->getDebugDispatcher()->dispatchDebug($value,$label);
								break;
						}//END switch($type)
					}//if(is_object($do))
						break;
				case 'QuantumPHP':
					if(!class_exists('\\QuantumPHP')) { break; }
						switch($type) {
							case self::DBG_WARNING:
								\QuantumPHP::add($value,'warning');
								break;
							case self::DBG_ERROR:
							if(is_object($value) && strpos(get_class($value),'Exception')!==FALSE) {
									\QuantumPHP::add($label,'error',$value);
							} else {
									\QuantumPHP::add($value,'error');
							}//if(is_object($value) && strpos(get_class($value),'Exception')!==FALSE)
								break;
							case self::DBG_INFO:
								\QuantumPHP::log($label.': '.print_r($value,1));
								break;
						  	case self::DBG_DEBUG:
						  	default:
						    if(is_null($value)) {
							        \QuantumPHP::log($label.': [NULL]');
						    } elseif(is_string($value)) {
							        \QuantumPHP::log($label.': '.$value);
						    } else {
							        \QuantumPHP::add($value,$label,FALSE,FALSE,FALSE,FALSE,TRUE);
						    }//if(is_null($value))
								break;
						}//END switch($type)
					break;
				  	default:
						break;
				}//END switch
		}//END foreach
	}//END public function Debug
	/**
	 * Displays a value in the debugger plugin as a debug message
	 *
	 * @param  mixed $value Value to be displayed by the debug objects
	 * @param  string $label Label assigned tot the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Dlog($value,$label = '',$file = FALSE,$path = FALSE) {
		if($file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
		}//if($file===TRUE)
		$this->Debug($value,$label,self::DBG_DEBUG);
	}//END public function Dlog
	/**
	 * Displays a value in the debugger plugin as a warning message
	 *
	 * @param  mixed $value Value to be displayed by the debug objects
	 * @param  string $label Label assigned tot the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Wlog($value,$label = '',$file = FALSE,$path = FALSE) {
		if($file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
		}//if($file===TRUE)
		$this->Debug($value,$label,self::DBG_WARNING);
	}//END public function Wlog
    /**
     * Displays a value in the debugger plugin as an error message
     *
     * @param  mixed  $value Value to be displayed by the debug objects
     * @param  string $label Label assigned tot the value to be displayed
     * @param bool    $showExceptionsTrace
     * @param  bool   $file Output file name
     * @param  bool   $path Output file path
     * @return void
     * @throws \Exception
     * @access public
     */
	public function Elog($value,?string $label = NULL,bool $showExceptionsTrace = FALSE,bool $file = FALSE,bool $path = FALSE) {
		if($file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
		}//if($file===TRUE)
		if($value instanceof \Exception) {
            if($showExceptionsTrace || $this->showExceptionsTrace) {
                $this->Debug($value->getMessage(),$label??get_class($value),self::DBG_ERROR);
            } else {
                $this->Debug('MESSAGE>> '.$value->getMessage(),$label??get_class($value),self::DBG_ERROR);
                $this->Debug('TRACE>> '.$value->getTrace(),$label??get_class($value),self::DBG_ERROR);
            }//if($showExceptionsTrace || $this->showExceptionsTrace)
		} else {
		    $this->Debug($value,$label,self::DBG_ERROR);
		}//if($value instanceof \Exception)
	}//END public function Elog
	/**
	 * Displays a value in the debugger plugin as an info message
	 *
	 * @param  mixed $value Value to be displayed by the debug objects
	 * @param  string $label Label assigned tot the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Ilog($value,$label = '',$file = FALSE,$path = FALSE) {
		if($file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = '['.($path===TRUE ? $caller['file'] : basename($caller['file'])).':'.$caller['line'].']'.$label;
		}//if($file===TRUE)
		$this->Debug($value,$label,self::DBG_INFO);
	}//END public function Ilog
	/**
	 * Add entry to log file
	 *
	 * @param  string|array $msg Text to be written to log
	 * @param  string|null $file Custom log file complete name (path + name)
	 * @param  string|null $scriptName Name of the file that sent the message to log (optional)
	 * @return bool|string Returns TRUE for success or error message on failure
	 * @access public
	 * @static
	 */
	public static function Log2File($msg,?string $file = NULL,?string $scriptName = NULL) {
		$lf = strlen($file) ? $file : 'unknown.log';
		try {
			$fileHandler = fopen($lf,'a');
			if(!$fileHandler) { throw new AppException("Unable to open log file [{$file}]!",E_WARNING,1); }
			if(is_array($msg) && count($msg)) {
				$scriptName = (array_key_exists('file',$msg) && strlen($msg['file'])) ? $msg['file'] : (strlen($scriptName) ? $scriptName : __FILE__);
				$scriptName .= (array_key_exists('line',$msg) && strlen($msg['line'])) ? ' (ln: '.$msg['line'].')' : '';
				$type = (array_key_exists('type',$msg) && strlen($msg['type'])) ? ' #'.strtoupper($msg['type']).((array_key_exists('no',$msg) && strlen($msg['no'])) ? ':'.strtoupper($msg['no']) : '').'#' : '';
				$message = (array_key_exists('message',$msg) && strlen($msg['message'])) ? $msg['message'] : '';
			} else {
				$scriptName = strlen($scriptName) ? $scriptName : __FILE__;
				$type = ' #LOG#';
				$message = $msg;
			}//if(is_array($msg) && count($msg))
			fwrite($fileHandler,'#'.date('Y-m-d H:i:s')."# <{$scriptName}>{$type} {$message}\n");
			fclose($fileHandler);
			return TRUE;
		} catch(AppException $e) {
			return $e->getMessage();
		}//END try
	}//END public static function AddToLog
	/**
	 * Writes a message in one of the application log files
	 *
	 * @param  string $msg Text to be written to log
	 * @param  string $type Log type (log, error or debug) (optional)
	 * @param  string $file Custom log file complete name (path + name) (optional)
	 * @param string  $path
	 * @return bool|string
	 * @access public
	 */
	public function Write2LogFile($msg,$type = 'log',$file = '',$path = '') {
		$lpath = (is_string($path) && strlen($path) ? rtrim($path,'/') : $this->logs_path).'/';
		switch(strtolower($type)) {
			case 'error':
				return self::Log2File($msg,$lpath.(strlen($file) ? $file : $this->errors_log_file));
			case 'debug':
				return self::Log2File($msg,$lpath.(strlen($file) ? $file : $this->debug_log_file));
			case 'log':
			default:
				return self::Log2File($msg,$lpath.(strlen($file) ? $file : $this->log_file));
		}//switch(strtolower($type))
	}//END public function WriteToLog
	/**
	 * Starts a debug timer
	 *
	 * @param  string $name Name of the timer (required)
	 * @return bool
	 * @access public
	 * @static
	 */
	public static function StartTimeTrack($name) {
		if(!$name) { return FALSE; }
		self::$debug_timers[$name] = microtime(TRUE);
		return TRUE;
	}//END public static function TimerStart
	/**
	 * Displays a debug timer elapsed time
	 *
	 * @param  string $name Name of the timer (required)
	 * @param  bool $stop Flag for stopping and destroying the timer (default TRUE)
	 * @return double|null
	 * @access public
	 * @static
	 */
	public static function ShowTimeTrack($name,$stop = TRUE) {
		if(!$name || !array_key_exists($name,self::$debug_timers)) { return NULL; }
		$time = self::$debug_timers[$name];
		if($stop) { unset(self::$debug_timers[$name]); }
		return (microtime(TRUE)-$time);
	}//END public static function TimerStart
}//END class Debugger