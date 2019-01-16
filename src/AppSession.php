<?php
/**
 * NETopes application session class file
 *
 * The NETopes session class can be used for interacting with the session data.
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
 * @filesource
 */
namespace NETopes\Core;
use NETopes\Core\App\Debugger;
/**
 * Class AppSession
 *
 * @package  NETopes\Core
 * @access   public
 */
class AppSession {
	/**
	 * @var    bool Use PHP session for current request
	 * @access protected
	 * @static
	 */
	protected static $with_session = NULL;
	/**
	 * @var    bool Flag for state of the session (started or not)
	 * @access protected
	 * @static
	 */
	protected static $session_started = FALSE;
	/**
	 * @var    bool Flag for clearing session on commit
	 * @access protected
	 * @static
	 */
	protected static $marked_for_deletion = FALSE;
	/**
	 * @var    array Session initial data
	 * @access public
	 * @static
	 */
	protected static $initial_data = NULL;
	/**
	 * @var    array Session data
	 * @access protected
	 * @static
	 */
	protected static $data = NULL;
	/**
	 * GetNewUID method generates a new unique ID
	 *
	 * @param      string $salt A string to be added as salt to the generated unique ID (NULL and empty string means no salt will be used)
	 * @param      string $algorithm The name of the algorithm used for unique ID generation (possible values are those in hash_algos() array - see: http://www.php.net/manual/en/function.hash-algos.php)
	 * @param      bool   $notime Flag for salting with current micro-time
	 * @param      bool   $raw Sets return type: hexits for FALSE (default) or raw binary for TRUE
	 * @return     string Returns an unique ID as lowercase hex or raw binary representation if $raw is set to TRUE.
	 * @access     public
	 * @static
	 */
	public static function GetNewUID(?string $salt = NULL,string $algorithm = 'sha1',bool $notime = FALSE,bool $raw = FALSE): string
	{
		if($notime) { return hash($algorithm,$salt,$raw); }
		return hash($algorithm,(strlen($salt) ? $salt : '').uniqid(microtime().rand(),TRUE),$raw);
	}//END public static function GetNewUID
	/**
	 * Get with session flag
	 *
	 * @return bool
	 */
	public static function WithSession(): bool {
		return self::$with_session;
	}//END public static function WithSession
	/**
	 * Set with session flag
	 *
	 * @param bool $value
	 */
	public static function SetWithSession(bool $value): void {
		self::$with_session = $value;
	}//END public static function SetWithSession
    /**
     * Get session data
     *
     * @return array
     */
	public static function GetData(): array {
		return self::$data??[];
	}//END public static function GetData
    /**
     * Set session data
     *
     * @param array $data
     */
	public static function SetData(array $data): void {
		self::$data = $data;
	}//END public static function SetData
	/**
	 * Gets the session state befor current request (TRUE for existing session or FALSE for newly initialized)
	 *
	 * @return bool Session state (TRUE for existing session or FALSE for newly initialized)
	 * @access public
	 */
	public static function GetState() {
		return (self::$with_session && is_array(self::$data));
	}//END public static function GetState
	/**
	 * Set clear session flag (on commit session will be cleared)
	 *
	 * @return void
	 * @access public
	 */
	public static function MarkForDeletion() {
		self::$marked_for_deletion = TRUE;
	}//END public static function MarkForDeletion
    /**
     * Convert a string to the session keys case (set in configuration)
     *
     * @param  string $input The string to be converted to the session case
     * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
     * FALSE - do not change case, NULL - use the configuration value
     * @return string|array The value converted to the session case
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function ConvertToSessionCase($input,$keys_case = NULL) {
		if($keys_case===FALSE) { return $input; }
		if(is_array($input)) {
			$linput = array();
			foreach($input as $k=>$v) { $linput[$k] = self::ConvertToSessionCase($v,$keys_case); }
			return $linput;
		}//if(is_array($input))
		if(!is_string($input)) { return $input; }
		switch(is_numeric($keys_case) ? $keys_case : AppConfig::GetValue('session_keys_case')) {
			case CASE_LOWER:
				return strtolower($input);
			case CASE_UPPER:
				return strtoupper($input);
			default:
				return $input;
		}//END switch
	}//END public static function ConvertToSessionCase
    /**
     * Set session configuration
     *
     * @param      $absolute_path
     * @param      $domain
     * @param      $session_timeout
     * @param null $session_id
     * @param null $log_file
     * @return string
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function ConfigAndStartSession($absolute_path,$domain,$session_timeout,$session_id = NULL,$log_file = NULL) {
		self::$session_started = FALSE;
		if(class_exists('\ErrorHandler')) { \ErrorHandler::$silent_mode = TRUE; }
		$errors = [];
		$dbg_data = '';
		$session_name = AppConfig::GetValue('session_name');
		ini_set('session.use_cookies',1);
		ini_set('session.cookie_lifetime',0);
		ini_set('session.cookie_domain',$domain);
		ini_set('session.gc_maxlifetime',$session_timeout);
		ini_set('session.cache_expire',$session_timeout/60);
		if(AppConfig::GetValue('session_redis')===TRUE) {
			if(class_exists('\Redis',FALSE)) {
				try {
					ini_set('session.save_handler','redis');
					ini_set('session.save_path',AppConfig::GetValue('session_redis_server'));
					ini_set('session.cache_expire',intval($session_timeout/60));
					if(is_string($session_name) && strlen($session_name)) { session_name($session_name); }
					if(is_string($session_id) && strlen($session_id)) {
						session_id($session_id);
						$dbg_data .= 'Set new session id: '.$session_id."\n";
					}//if(is_string($session_id) && strlen($session_id))
					session_start();
				} catch(\Exception $e) {
					$errors[] = ['errstr'=>$e->getMessage(),'errno'=>$e->getCode(),'errfile'=>$e->getFile(),'errline'=>$e->getLine()];
				} finally {
					if(class_exists('\ErrorHandler') && \ErrorHandler::HasErrors()) {
						$eh_errors = \ErrorHandler::GetErrors(TRUE);
						$errors = array_merge($errors,$eh_errors);
					}//if(class_exists('\ErrorHandler') && \ErrorHandler::HasErrors())
					if(count($errors)>0) {
						self::$session_started = FALSE;
						if($log_file) { Debugger::Log2File(print_r($errors,1),$absolute_path.$log_file); }
						$dbg_data .= 'Session start [handler: Redis] errors: '.print_r($errors,1)."\n";
					} else {
						self::$session_started = TRUE;
						$dbg_data .= 'Session start done [handler: Redis]'."\n";
					}//if(count($errors)>0)
				}//try
			}//if(class_exists('\Redis',FALSE))
		}//if(self::$session_redis===TRUE)
		if(!self::$session_started && AppConfig::GetValue('session_memcached')===TRUE) {
			$errors = [];
			if(class_exists('\Memcached',FALSE)) {
				try {
					ini_set('session.save_handler','memcached');
					ini_set('session.save_path',AppConfig::GetValue('session_memcached_server'));
					ini_set('session.cache_expire',intval($session_timeout/60));
					if(is_string($session_name) && strlen($session_name)) { session_name($session_name); }
					if(is_string($session_id) && strlen($session_id)) {
						session_id($session_id);
						$dbg_data .= 'Set new session id: '.$session_id."\n";
					}//if(is_string($session_id) && strlen($session_id))
					session_start();
				} catch(\Exception $e) {
					$errors[] = ['errstr'=>$e->getMessage(),'errno'=>$e->getCode(),'errfile'=>$e->getFile(),'errline'=>$e->getLine()];
				} finally {
					if(class_exists('\ErrorHandler') && \ErrorHandler::HasErrors()) {
						$eh_errors = \ErrorHandler::GetErrors(TRUE);
						$errors = array_merge($errors,$eh_errors);
					}//if(class_exists('\ErrorHandler') && \ErrorHandler::HasErrors())
					if(count($errors)>0) {
						self::$session_started = FALSE;
						if($log_file) { Debugger::Log2File(print_r($errors,1),$absolute_path.$log_file); }
						$dbg_data .= 'Session start [handler: Memcached] errors: '.print_r($errors,1)."\n";
					} else {
						self::$session_started = TRUE;
						$dbg_data .= 'Session start done [handler: Memcached]'."\n";
					}//if(count($errors)>0)
				}//try
			} elseif(class_exists('\Memcache',FALSE)) {
				try {
					ini_set('session.save_handler','memcache');
					ini_set('session.save_path',AppConfig::GetValue('session_memcached_server'));
					ini_set('session.cache_expire',intval($session_timeout/60));
					if(is_string($session_name) && strlen($session_name)) { session_name($session_name); }
					if(is_string($session_id) && strlen($session_id)) {
						session_id($session_id);
						$dbg_data .= 'Set new session id: '.$session_id."\n";
					}//if(is_string($session_id) && strlen($session_id))
					session_start();
				} catch(\Exception $e) {
					$errors[] = ['errstr'=>$e->getMessage(),'errno'=>$e->getCode(),'errfile'=>$e->getFile(),'errline'=>$e->getLine()];
				} finally {
					if(class_exists('\ErrorHandler') && \ErrorHandler::HasErrors()) {
						$eh_errors = \ErrorHandler::GetErrors(TRUE);
						$errors = array_merge($errors,$eh_errors);
					}//if(class_exists('\ErrorHandler') && \ErrorHandler::HasErrors())
					if(count($errors)>0) {
						self::$session_started = FALSE;
						if($log_file) { Debugger::Log2File(print_r($errors,1),$absolute_path.$log_file); }
						$dbg_data .= 'Session start [handler: Memcache] errors: '.print_r($errors,1)."\n";
					} else {
						self::$session_started = TRUE;
						$dbg_data .= 'Session start done [handler: Memcache]'."\n";
					}//if(count($errors)>0)
				}//try
			}//if(class_exists('\Memcached',FALSE))
		}//if(!$initialized && self::$session_memcached===TRUE)
		if(class_exists('\ErrorHandler')) { \ErrorHandler::$silent_mode = FALSE; }
		if(!self::$session_started) {
			ini_set('session.save_handler','files');
			$session_file_path = AppConfig::GetValue('session_file_path');
			if(strlen($session_file_path)) {
				if((substr($session_file_path,0,1)=='/' || substr($session_file_path,1,2)==':\\') && file_exists($session_file_path)) {
					session_save_path($session_file_path);
				} elseif(file_exists($absolute_path.'/'.$session_file_path)) {
					session_save_path($absolute_path.'/'.$session_file_path);
				}//if((substr($session_file_path,0,1)=='/' || substr($session_file_path,1,2)==':\\') && file_exists($session_file_path))
			}//if(strlen($session_file_path))
			if(is_string($session_name) && strlen($session_name)) { session_name($session_name); }
			if(is_string($session_id) && strlen($session_id)) {
				session_id($session_id);
				$dbg_data .= 'Set new session id: '.$session_id."\n";
			}//if(is_string($session_id) && strlen($session_id))
			session_start();
			self::$session_started = TRUE;
			$dbg_data .= 'Session started [handler: Files]'."\n";
		}//if(!$initialized)
		return $dbg_data;
	}//END public static function ConfigAndStartSession
    /**
     * Initiate/re-initiate session and read session data
     *
     * @param string    $path URL path
     * @param bool|null $do_not_keep_alive
     * @param bool      $ajax Is AJAX request
     * @return void
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function SessionStart(string $path = '',?bool $do_not_keep_alive = NULL,$ajax = FALSE) {
		if(!self::$with_session) { return; }
		$dbg_data = '>> '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'console')."\n";
		$dbg_data .= 'Session started: '.(self::$session_started ? 'TRUE' : 'FALSE')."\n";
		$absolute_path = _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH;
		$cremoteaddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		$cdomain = strtolower((array_key_exists('SERVER_NAME',$_SERVER) && $_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
		$cfulldomain = $cdomain.$path;
		$cuseragent = array_key_exists('HTTP_USER_AGENT',$_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN USER AGENT';
		$session_timeout = AppConfig::GetValue('session_timeout');
		$log_file = AppConfig::GetValue('logs_path').'/'.AppConfig::GetValue('errors_log_file');
		if(!self::$session_started) { $dbg_data .= self::ConfigAndStartSession($absolute_path,$cdomain,$session_timeout,NULL,$log_file); }
		$dbg_data .= 'Session ID: '.session_id()."\n";
		$dbg_data .= 'Session age: '.(isset($_SESSION['X_SCAT']) ? (time()-$_SESSION['X_SCAT']) : 'N/A')."\n";
		$dbg_data .= 'Last request: '.(isset($_SESSION['X_SEXT']) ? (time()-$_SESSION['X_SEXT']) : 'N/A')."\n";
		$dbg_data .= 'X_SKEY: '.(isset($_SESSION['X_SKEY']) ? $_SESSION['X_SKEY'] : 'N/A')."\n";
		$session_key = AppConfig::GetValue('session_key');
        if(!isset($_SESSION['X_SEXT']) || !isset($_SESSION['X_SKEY']) || ($_SESSION['X_SEXT']+$session_timeout)<time() || $_SESSION['X_SKEY']!=self::GetNewUID($session_key.session_id(),'sha256',TRUE)) {
            $dbg_data .= 'Do: SESSION RESET'."\n";
        	$_SESSION = [];
		    setcookie(session_name(),'',time()-4200,'/',$cdomain);
			session_destroy();
			ini_set('session.use_cookies',1);
			ini_set('session.cookie_lifetime',0);
			ini_set('cookie_domain',$cdomain);
			ini_set('session.gc_maxlifetime',$session_timeout);
			ini_set('session.cache_expire',$session_timeout/60);
			$new_session_id = self::GetNewUID($cfulldomain.$cuseragent.$cremoteaddress,'sha256');
			$dbg_data .= self::ConfigAndStartSession($absolute_path,$cdomain,$session_timeout,$new_session_id,$log_file);
			$_SESSION['X_SCAT'] = time();
			$_SESSION['SESSION_ID'] = session_id();
			$dbg_data .= 'Session ID (new): '.session_id()."\n";
		}//if(!isset($_SESSION['X_SEXT']) || !isset($_SESSION['X_SKEY']) || ($_SESSION['X_SEXT']+self::$session_timeout)<time() || $_SESSION['X_SKEY']!=self::GetNewUID(self::$session_key.session_id(),'sha256',TRUE))
		set_time_limit(AppConfig::GetValue('request_time_limit'));
		$_SESSION['X_SKEY'] = self::GetNewUID($session_key.session_id(),'sha256',TRUE);
		$dbg_data .= 'Do not keep alive: '.($do_not_keep_alive!==TRUE && $do_not_keep_alive!==1 ? 'FALSE' : 'TRUE')."\n";
		if($do_not_keep_alive!==TRUE && $do_not_keep_alive!==1) { $_SESSION['X_SEXT'] = time(); }
		// vprint($dbg_data);
		// self::Log2File($dbg_data,$absolute_path.AppConfig::GetValue('logs_path').'/'.AppConfig::GetValue('debugging_log_file'));
		self::$data = $_SESSION;
		self::$initial_data = self::$data;
		if(AppConfig::GetValue('async_session') && $ajax) { AppSession::SessionClose(); }
    }//END public static function SessionStart
    /**
     * Commit the temporary session into the session
     *
     * @param  bool   $clear If TRUE is passed the session will be cleared
     * @param  bool   $show_errors Display errors TRUE/FALSE
     * @param  string $key Session key to commit (do partial commit)
     * @param  string $phash Page (tab) hash
     * @param  bool   $reload Reload session data after commit
     * @return void
     * @poaram bool $reload Reload session after commit (default TRUE)
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function SessionCommit($clear = FALSE,$show_errors = TRUE,$key = NULL,$phash = NULL,$reload = TRUE) {
		if(!self::$with_session) {
			if($show_errors && method_exists('\ErrorHandler','ShowErrors')) { \ErrorHandler::ShowErrors(); }
			return;
		}//if(!self::$with_session)
		if(!is_array(self::$data)) { self::$data = []; }
		if(!self::$session_started) { session_start(); }
		if($clear===TRUE || self::$marked_for_deletion===TRUE) {
			if(strlen($key)) {
				if(strlen($phash)) {
					unset(self::$initial_data[$key][$phash]);
					unset(self::$data[$key][$phash]);
					unset($_SESSION[$key][$phash]);
				} else {
					unset(self::$initial_data[$key]);
					unset(self::$data[$key]);
					unset($_SESSION[$key]);
				}//if(strlen($phash))
			} else {
				if(strlen($phash)) {
					unset(self::$initial_data[$phash]);
					unset(self::$data[$phash]);
					unset($_SESSION[$phash]);
				} else {
					self::$initial_data = NULL;
					self::$data = NULL;
					unset($_SESSION);
				}//if(strlen($phash))
			}//if(strlen($key))
		} else {
			if(strlen($key)) {
				if(strlen($phash)) {
					$lvalue = (array_key_exists($key,self::$data) && is_array(self::$data[$key]) && array_key_exists($phash,self::$data[$key])) ? self::$data[$key][$phash] : NULL;
					$li_arr = (array_key_exists($key,self::$initial_data) && is_array(self::$initial_data[$key]) && array_key_exists($phash,self::$initial_data[$key])) ? self::$initial_data[$key][$phash] : NULL;
					if(array_key_exists($key,$_SESSION) && is_array($_SESSION[$key]) && array_key_exists($phash,$_SESSION[$key])) {
						$_SESSION[$key][$phash] = self::MergeSession($_SESSION[$key][$phash],$lvalue,TRUE,$li_arr);
					} else {
						$_SESSION[$key][$phash] = $lvalue;
					}//if(array_key_exists($key,$_SESSION) && is_array($_SESSION[$key]) && array_key_exists($phash,$_SESSION[$key]))
				} else {
					$lvalue = array_key_exists($key,self::$data) ? self::$data[$key] : NULL;
					$li_arr = array_key_exists($key,self::$initial_data) ? self::$initial_data[$key] : NULL;
					if(array_key_exists($key,$_SESSION)) {
						$_SESSION[$key] = self::MergeSession($_SESSION[$key],$lvalue,TRUE,$li_arr);
					} else {
						$_SESSION[$key] = $lvalue;
					}//if(array_key_exists($key,$_SESSION))
				}//if(strlen($phash))
			} else {
				if(strlen($phash)) {
					$lvalue = array_key_exists($phash,self::$data) ? self::$data[$phash] : NULL;
					$li_arr = is_array(self::$initial_data) && array_key_exists($phash,self::$initial_data) ? self::$initial_data[$phash] : NULL;
					if(array_key_exists($phash,$_SESSION)) {
						$_SESSION[$phash] = self::MergeSession($_SESSION[$phash],$lvalue,TRUE,$li_arr);
					} else {
						$_SESSION[$phash] = $lvalue;
					}//if(array_key_exists($phash,$_SESSION))
				} else {
					$_SESSION = self::MergeSession($_SESSION,self::$data,TRUE,self::$initial_data);
				}//if(strlen($phash))
			}//if(strlen($key))
			if($reload) {
				self::$data = $_SESSION;
				self::$initial_data = self::$data;
			}//if($reload)
		}//($clear===TRUE || $this->clear_session===TRUE)
		if(!self::$session_started) { session_write_close(); }
		if($show_errors && method_exists('\ErrorHandler','ShowErrors')) { \ErrorHandler::ShowErrors(); }
	}//END public static function SessionCommit
	/**
	 * Close session for write
	 *
	 * @param bool $write
	 * @return void
	 * @access public
	 * @static
	 */
	public static function SessionClose($write = TRUE) {
		if(!self::$session_started) { return; }
		if($write) {
			session_write_close();
		} else {
			session_abort();
		}//if($write)
		self::$session_started = FALSE;
	}//END public static function SessionClose
	/**
	 * Gets a session parameter at a certain path (path = a succession of keys of the session data array)
	 *
	 * @param  string $key The key of the searched parameter
	 * @param  string $path An array containing the succession of keys for the searched parameter
	 * @param  array $data The session data array to be searched
	 * @return mixed Value of the parameter if it exists or NULL
	 * @access protected
	 * @static
	 */
	protected static function GetCustomParam($key,$path,$data) {
		if(!is_array(self::$data)) { return NULL; }
		if(is_array($path) && count($path)) {
			$lpath = array_shift($path);
			if(!strlen($lpath) || !array_key_exists($lpath,$data)) { return NULL; }
			return self::GetCustomParam($key,$path,$data[$lpath]);
		}//if(is_array($path) && count($path))
		if(is_string($path) && strlen($path)) {
			if(!array_key_exists($path,$data) || !is_array($data[$path])) { return NULL; }
			return array_key_exists($key,$data[$path]) ? $data[$path][$key] : NULL;
		}//if(is_string($path) && strlen($path))
		return array_key_exists($key,$data) ? $data[$key] : NULL;
	}//END protected static function GetCustomParam
    /**
     * Get a global parameter (a parameter from first level of the array) from the session data array
     *
     * @param  string $key The key of the searched parameter
     * @param  string $phash The page hash (default NULL)
     * If FALSE is passed, the main (App property) page hash will not be used
     * @param  string $path An array containing the succession of keys for the searched parameter
     * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
     * FALSE - do not change case, NULL - use the configuration value
     * @return mixed Returns the parameter value or NULL
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function GetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
		if(!is_array(self::$data)) { return NULL; }
		$lkey = self::ConvertToSessionCase($key,$keys_case);
		$lpath = self::ConvertToSessionCase($path,$keys_case);
		if($phash) {
			if(!array_key_exists($phash,self::$data)) { return NULL; }
			if(isset($lpath)) { return self::GetCustomParam($lkey,$lpath,self::$data[$phash]); }
			return (array_key_exists($lkey,self::$data[$phash]) ? self::$data[$phash][$lkey] : NULL);
		}//if($lphash)
		if($lpath) { return self::GetCustomParam($key,$lpath,self::$data); }
		return (array_key_exists($lkey,self::$data) ? self::$data[$lkey] : NULL);
	}//END public static function GetGlobalParam
    /**
     * Set a global parameter (a parameter from first level of the array) from the session data array
     *
     * @param  string $key The key of the searched parameter
     * @param  mixed  $val The value to be set
     * @param  string $phash The page hash (default NULL)
     * If FALSE is passed, the main (App property) page hash will not be used
     * @param  string $path An array containing the succession of keys for the searched parameter
     * @param  mixed  @keys_case Custom session keys case: CASE_LOWER/CASE_UPPER,
     * FALSE - do not change case, NULL - use the configuration value
     * @return bool Returns TRUE on success or FALSE otherwise
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function SetGlobalParam($key,$val,$phash = NULL,$path = NULL,$keys_case = NULL) {
		if(!is_array(self::$data)) { self::$data = []; }
		$lkey = self::ConvertToSessionCase($key,$keys_case);
		$lpath = self::ConvertToSessionCase($path,$keys_case);
		if(isset($lpath)) {
			if(is_array($lpath) && count($lpath)) {
				$part_arr = array($lkey=>$val);
				foreach(array_reverse($lpath) as $k) { $part_arr = array($k=>$part_arr); }
				if($phash) {
					self::$data[$phash] = self::MergeSession(self::$data[$phash],$part_arr,TRUE);
				} else {
					self::$data = self::MergeSession(self::$data,$part_arr,TRUE);
				}//if($lphash)
				return TRUE;
			}//if(is_array($path) && count($path))1
			if(is_string($lpath) && strlen($lpath)) {
				if($phash) {
					self::$data[$phash][$lpath][$lkey] = $val;
				} else {
					self::$data[$lpath][$lkey] = $val;
				}//if($lphash)
				return TRUE;
			}//if(is_string($path) && strlen($path))
			return FALSE;
		}//if(isset($path))
		if($phash) {
			self::$data[$phash][$lkey] = $val;
		} else {
			self::$data[$lkey] = $val;
		}//if($lphash)
		return TRUE;
	}//END public static function SetGlobalParam
    /**
     * Delete a global parameter (a parameter from first level of the array) from the session data array
     *
     * @param  string $key The key of the searched parameter
     * @param  string $phash The page hash (default NULL)
     * If FALSE is passed, the main (App property) page hash will not be used
     * @param null    $path
     * @param null    $keys_case
     * @return bool
     * @access public
     * @static
     * @throws \NETopes\Core\AppException
     */
	public static function UnsetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
		if(!is_array(self::$data)) { return TRUE; }
		$lkey = self::ConvertToSessionCase($key,$keys_case);
		$lpath = self::ConvertToSessionCase($path,$keys_case);
		if(isset($lpath)) {
			if(is_array($lpath) && count($lpath)) {
				$part_arr = array($lkey=>NULL);
				foreach(array_reverse($lpath) as $k) { $part_arr = array($k=>$part_arr); }
				if($phash) {
					self::$data[$phash] = self::MergeSession(self::$data[$phash],$part_arr,TRUE);
				} else {
					self::$data = self::MergeSession(self::$data,$part_arr,TRUE);
				}//if($lphash)
				return TRUE;
			}//if(is_array($path) && count($path))1
			if(is_string($lpath) && strlen($lpath)) {
				if($phash) {
					unset(self::$data[$phash][$lpath][$lkey]);
				} else {
					unset(self::$data[$lpath][$lkey]);
				}//if($lphash)
				return TRUE;
			}//if(is_string($path) && strlen($path))
			return FALSE;
		}//if(isset($path))
		if($phash) {
			unset(self::$data[$phash][$lkey]);
		} else {
			unset(self::$data[$lkey]);
		}//if($lphash)
		return TRUE;
	}//END public static function UnsetGlobalParam
	/**
     * Array merge with overwrite option (the 2 input arrays remains untouched).
     * The second array will overwrite the first.
     *
     * @param   array $current First array to merge
     * @param   array $new Second array to merge
     * @param   bool  $overwrite Overwrite sitch: TRUE with overwrite (default), FALSE without overwrite
     * @param   array $initial
     * @return  array|bool Returns the merged array or FALSE if one of the arr arguments is not an array
     */
    public static function MergeSession($current,$new,$overwrite = TRUE,$initial = NULL) {
        if(!is_array($current) || !is_array($new)) { return NULL; }
        if(!is_array($current)) { return $new; }
        if(!is_array($new)) { return $current; }
        $result = $current;
        foreach($new as $k=>$v) {
            $i_arr = is_array($initial) && array_key_exists($k,$initial) ? $initial[$k] : NULL;
            if($i_arr && $v===$i_arr) { continue; }
            if(array_key_exists($k,$result)) {
                if(is_array($result[$k]) && is_array($v)) {
                    $result[$k] = self::MergeSession($result[$k],$v,$overwrite,$i_arr);
                } else {
                    if($overwrite===TRUE) { $result[$k] = $v; }
                }//if(is_array($result[$k]) && is_array($v))
            } else {
                $result[$k] = $v;
            }//if(array_key_exists($k,$result))
        }//END foreach
        if(is_array($initial) && count($initial)) {
            foreach(array_diff_key($initial,$new) as $k=>$v) { unset($result[$k]); }
        }//if(is_array($initial) && count($initial))
        return $result;
    }//END public static function MergeSession
    /**
     * Gets a parameter from the temporary session
     *
     * @param  string     $key The name of the parameter
     * @param string|null $phash The page hash (default FALSE, global context)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetParam(string $key,?string $phash = NULL,?string $namespace = NULL) {
	    if(!is_array(self::$data)) { return NULL; }
	    $data = self::$data;
	    if(is_string($namespace)) {
	        if(!array_key_exists($namespace,$data) || !is_array($data[$namespace])) { return NULL; }
            $data = $data[$namespace];
	    }//if(is_string($namespace))
	    if(is_string($phash)) {
	        if(!array_key_exists($phash,$data) || !is_array($data[$phash])) { return NULL; }
            $data = $data[$phash];
	    }//if(is_string($phash))
		$lkey = self::ConvertToSessionCase($key);
		if(!array_key_exists($lkey,$data)) { return NULL; }
		return $data[$lkey];
	}//END public static function GetParam
    /**
     * Sets a parameter to the temporary session
     *
     * @param  string     $key The name of the parameter
     * @param  mixed      $val The value of the parameter
     * @param string|null $phash The page hash (default FALSE, global context)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return void
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function SetParam(string $key,$val,?string $phash = NULL,?string $namespace = NULL) {
		if(!is_array(self::$data)) { self::$data = []; }
		$lkey = self::ConvertToSessionCase($key);
		if(isset($namespace)) {
		    if(isset($phash)) {
                self::$data[$namespace][$phash][$lkey] = $val;
		    } else {
		        self::$data[$namespace][$lkey] = $val;
		    }//if(isset($phash))
		} elseif(isset($phash)) {
		    self::$data[$phash][$lkey] = $val;
		} else {
		    self::$data[$lkey] = $val;
		}//if(isset($namespace))
	}//END public static function SetParam
    /**
     * Delete a parameter from the temporary session
     *
     * @param  string        $key The name of the parameter
     * @param string|null    $phash The page hash (default FALSE, global context)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null    $namespace
     * @return void
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function UnsetParam($key,?string $phash = NULL,?string $namespace = NULL) {
		$lkey = AppSession::ConvertToSessionCase($key);
		if(isset($namespace)) {
		    if(isset($phash)) {
                unset(self::$data[$namespace][$phash][$lkey]);
		    } else {
		        unset(self::$data[$namespace][$lkey]);
		    }//if(isset($phash))
		} elseif(isset($phash)) {
		    unset(self::$data[$phash][$lkey]);
		} else {
		    unset(self::$data[$lkey]);
		}//if(isset($namespace))
	}//END public static function UnsetParam
    /**
     * description
     *
     * @param string      $uid
     * @param string|null $namespace
     * @return mixed
     * @access public
     */
	public static function GetSessionAcceptedRequest(string $uid,?string $namespace = NULL) {
	    if(strlen($namespace)) { return isset(static::$data[$namespace]['xURLRequests'][$uid]) ? static::$data[$namespace]['xURLRequests'][$uid] : NULL; }
		return isset(static::$data['xURLRequests'][$uid]) ? static::$data['xURLRequests'][$uid] : NULL;
	}//END public static function GetSessionAcceptedRequest
    /**
     * description
     *
     * @param string|null $uid
     * @param string|null $namespace
     * @return string
     * @access public
     */
	public static function SetSessionAcceptedRequest(?string $uid,?string $namespace = NULL): string {
		if(is_null($uid)) { $uid = static::GetNewUID(NULL,'md5'); }
		if(strlen($namespace)) {
		    static::$data[$namespace]['xURLRequests'][$uid] = TRUE;
		} else {
		    static::$data['xURLRequests'][$uid] = TRUE;
		}//if(strlen($namespace))
		return $uid;
	}//END public static function SetSessionAcceptedRequest
    /**
     * description
     *
     * @param string      $uid
     * @param string|null $namespace
     * @return void
     * @access public
     */
	public static function UnsetSessionAcceptedRequest(string $uid,?string $namespace = NULL): void {
		if(strlen($namespace)) {
		    unset(static::$data[$namespace]['xURLRequests'][$uid]);
		} else {
		    unset(static::$data['xURLRequests'][$uid]);
		}//if(strlen($namespace))
	}//END public static function UnsetSessionAcceptedRequest
    /**
     * description
     *
     * @param string      $uid
     * @param bool        $reset
     * @param string|null $namespace
     * @return bool
     * @access public
     */
	public static function CheckSessionAcceptedRequest(string $uid,bool $reset = FALSE,?string $namespace = NULL): bool {
		$result = static::GetSessionAcceptedRequest($uid,$namespace);
		if($reset===TRUE) { static::UnsetSessionAcceptedRequest($uid,$namespace); }
		return ($result===TRUE);
	}//END public static function CheckSessionAcceptedRequest
}//END class AppSession