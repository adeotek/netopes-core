<?php
/**
 * NETopes application main class file.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use NETopes\Ajax\BaseRequest;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NETopes\Core\Data\DataProvider;
/**
  * Class App
  *
  * @package  NETopes\Core
  * @access   public
  */
abstract class App implements IApp {
    /**
	 * @var    Object Singleton unique instance
	 * @access protected
	 * @static
	 */
	protected static $_app_instance = NULL;
	/**
	 * @var    bool Flag for output buffering (started or not)
	 * @access protected
	 * @static
	 */
	protected static $app_ob_started = FALSE;
	/**
	 * @var    bool State of session before current request (TRUE for existing session or FALSE for newly initialized)
	 * @access protected
	 */
	protected $_app_state = FALSE;
	/**
	 * @var    string Application absolute path (auto-set on constructor)
	 * @access public
	 */
	protected $app_absolute_path = NULL;
	/**
	 * @var    \NETopes\Core\App\Debugger Object for debugging
	 * @access public
	 */
	public $debugger = NULL;
	/**
	 * @var    \NETopes\Ajax\Request Object for ajax requests processing
	 * @access public
	 */
	public $arequest = NULL;
	/**
	 * @var    \NETopes\Core\App\Url Object for application URL processing
	 * @access public
	 */
	public $url = NULL;
	/**
	 * @var    string Application non-public path (auto-set on constructor)
	 * @access public
	 */
	public $app_path = NULL;
	/**
	 * @var    string Application public path (auto-set on constructor)
	 * @access public
	 */
	public $app_public_path = NULL;
	/**
	 * @var    string Application base link (auto-set on constructor)
	 * @access public
	 */
	public $app_web_link = NULL;
	/**
	 * @var    bool Flag to indicate if the request is ajax or not
	 * @access public
	 */
	public $ajax = FALSE;
	/**
	 * @var    bool Flag to indicate if the request should keep the session alive
	 * @access public
	 */
	public $keep_alive = TRUE;
	/**
	 * @var    string Sub-session key (page hash)
	 * @access public
	 */
	public $phash = NULL;
	/**
	 * @var    bool Flag indicating if GUI was loaded or not
	 * @access public
	 * @static
	 */
	public static $gui_loaded = FALSE;
	/**
	 * @var    bool Flag for setting silent errors
	 * @access public
	 * @static
	 */
	public static $silent_errors = FALSE;
	/**
	 * @var    bool Debug mode
	 * @access public
	 * @static
	 */
	public static $debug = FALSE;
	/**
	 * @var    \NETopes\Core\App\ITheme Current theme object instance
	 * @access public
	 * @static
	 */
	public static $theme = NULL;
	/**
	 * @var    bool If set TRUE, name-space session will be cleared at commit
	 * @access protected
	 */
	protected $clear_namespace_session = FALSE;
    /**
     * @var    string Account API security key (auto-loaded on LoadAppOptions() method)
     * @access protected
     */
	protected $app_access_key = NULL;
    /**
     * @var    array An array of global variables
     * @access public
     */
	public $globals = [];
    /**
     * @var    string Current namespace
     * @access public
     */
	public $current_namespace = '';
    /**
     * @var    string Current section folder
     * @access public
     */
	public $current_section_folder = '';
    /**
     * @var    bool TRUE if current namespace requires login
     * @access public
     */
	public $requires_login = NULL;
    /**
     * @var    string Namespace to be used for login
     * @access public
     */
	public $login_namespace = NULL;
    /**
     * @var    bool With sections
     * @access public
     */
	public $with_sections = TRUE;
	/**
	 * @var    string Start page
	 * @access public
	 */
	public $start_page = '';
	/**
	 * @var    string Logged in start page
	 * @access public
	 */
	public $loggedin_start_page = '';
	/**
	 * @var    string Name of the default database connection
	 * (from config/connections.inc)
	 * @access public
	 */
	public $default_db_connection = '';
	/**
	 * @var    bool Login status
	 * @access public
	 */
	public $login_status = NULL;
	/**
	 * @var    int User status
	 * @access public
	 */
	public $user_status = 0;
	/**
	 * @var    bool Application database stored option load state
	 * @access public
	 */
	public $app_options_loaded = FALSE;
	/**
	 * Magic method for accessing non-static public members with static call
	 *
	 * If no arguments are provided, first tries to return the property with the given name
	 * and if such property doesn't exists or inaccessible, tries to call the public method with the given name
	 *
	 * @param  string $name Name of the member to be accessed
	 * @param  array  $arguments The arguments for accessing methods
	 * @return mixed Returns the property or method result
	 * @throws \NETopes\Core\AppException|\ReflectionException
	 * @access public
	 * @static
	 */
	public static function __callStatic($name,$arguments) {
		$class = get_called_class();
		if(!is_object(self::$_app_instance)) { throw new AppException("Invalid class [{$class}] instance static call for [{$name}]",E_ERROR); }
		// self::$_app_instance->Dlog($name,'__callStatic:name');
		// self::$_app_instance->Dlog($class,'__callStatic:class');
		$reflector = new \ReflectionClass($class);
		if(strpos($name,'_')!==0) {
			$member = $name;
			// self::$_app_instance->Dlog($member,'__callStatic:property');
			if($reflector->hasProperty($member)) {
				$property = $reflector->getProperty($member);
				if(!$property->isStatic() && $property->isPublic()) { return self::$_app_instance->{$member}; }
			}//if($reflector->hasProperty($member))
		} else {
			$member = substr($name,1);
			// self::$_app_instance->Dlog($member,'__callStatic:method');
			if($reflector->hasMethod($member)) {
				$method = $reflector->getMethod($member);
				if(!$method->isStatic() && $method->isPublic()) {
					// self::$_app_instance->Dlog($arguments,'__callStatic:arguments');
					if(preg_match('/^[DWEI]log$/i',$member) && (AppConfig::console_show_file()===TRUE || (isset($arguments[2]) && $arguments[2]===TRUE))) {
						$dbg = debug_backtrace();
						$caller = array_shift($dbg);
						$cfile = isset($caller['file']) ? $caller['file'] : '';
						$label = '['.(isset($arguments[3]) && $arguments[3]===TRUE ? $cfile : basename($cfile)).(isset($caller['line']) ? ':'.$caller['line'] : '').']'.(isset($arguments[1]) ? $arguments[1] : '');
						$larguments = array((isset($arguments[0]) ? $arguments[0] : NULL),$label);
					} else {
						$larguments = $arguments;
					}//if(preg_match('/^[DWEI]log$/i',$member) && (AppConfig::console_show_file()===TRUE || (isset($arguments[2]) && $arguments[2]===TRUE)))
					// self::$_app_instance->Dlog($larguments,'__callStatic:$larguments');
					return call_user_func_array(array(self::$_app_instance,$member),$larguments);
				}//if(!$method->isStatic() && $method->isPublic())
			}//if($reflector->hasMethod($member))
		}//if(strpos($name,'_')!==0)
		throw new \InvalidArgumentException("Undefined or inaccessible property/method [{$member}]!");
	}//END public static function __callStatic
	/**
	 * Classic singleton method for retrieving the NETopes App object
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $session_init Flag indicating if session should be started or not
	 * @param  bool  $do_not_keep_alive Flag indicating if session should be kept alive by the current request
	 * @param  bool  $shell Shell mode on/off
	 * @return Object
	 * @access public
	 * @static
	 */
	public static function GetInstance($ajax = FALSE,$params = [],$session_init = TRUE,$do_not_keep_alive = NULL,$shell = FALSE) {
		if($session_init) {
			AppSession::SetWithSession(TRUE);
			$cdir = Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
			AppSession::SessionStart($cdir,$do_not_keep_alive,$ajax);
		} else {
			AppSession::SetWithSession(FALSE);
		}//if($session_init)
		if(is_null(self::$_app_instance)) {
			$class_name = get_called_class();
			self::$_app_instance = new $class_name($ajax,$params,$do_not_keep_alive,$shell);
		}//if(is_null(self::$_app_instance))
		return self::$_app_instance;
	}//END public static function GetInstance
	/**
	 * Method for returning the static instance property
	 *
	 * @return object Returns the value of $_aapp_instance property
	 * @access public
	 * @static
	 */
	public static function GetCurrentInstance() {
		return self::$_app_instance;
	}//END public static function GetCurrentInstance
	/**
	 * Static setter for phash property
	 *
	 * @param  string $value The new value for phash property
	 * @return void
	 * @access public
	 * @static
	 */
	public static function SetPhash($value) {
		self::$_app_instance->phash = $value;
	}//END public static function SetPhash
	/**
	 * NETopes App constructor function
	 *
	 * @param  bool  $ajax Optional flag indicating whether is an ajax request or not
	 * @param  array $params An optional key-value array containing to be assigned to non-static properties
	 * (key represents name of the property and value the value to be assigned)
	 * @param  bool  $do_not_keep_alive Do not keep alive user session
	 * @param  bool  $shell Shell mode on/off
	 * @throws \Exception|\ReflectionException
	 * @return void
	 * @access protected
	 */
	protected function __construct(bool $ajax = FALSE,array $params = [],$do_not_keep_alive = NULL,bool $shell = FALSE) {
	    $this->app_options_loaded = FALSE;
		$this->app_absolute_path = _NAPP_ROOT_PATH;
		$this->app_path = _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH;
		$this->app_public_path = _NAPP_ROOT_PATH._NAPP_PUBLIC_ROOT_PATH._NAPP_PUBLIC_PATH;
		$this->ajax = $ajax;
		$this->keep_alive = $do_not_keep_alive ? FALSE : TRUE;
		if($shell) {
			$this->_app_state = TRUE;
			$app_domain = trim(get_array_value($_GET,'domain','','is_string'),' /\\');
			if(strlen($app_domain)) {
				$app_web_protocol = trim(get_array_value($_GET,'protocol','http','is_notempty_string'),' /:\\').'://';
				$url_folder = trim(get_array_value($_GET,'uri_path','','is_string'),' /\\');
			} else {
				$app_web_protocol = '';
				$url_folder = '';
			}//if(strlen($app_domain))
			$this->url = new Url($app_domain,$app_web_protocol,$url_folder);
			$this->app_web_link = $this->url->GetWebLink();
		} else {
			$this->_app_state = AppSession::WithSession() ? AppSession::GetState() : TRUE;
			$app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
			$app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			$url_folder = Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
			$this->url = new Url($app_domain,$app_web_protocol,$url_folder);
			$this->app_web_link = $this->url->GetWebLink();
			if(AppConfig::split_session_by_page()) {
				$this->phash = get_array_value($_GET,'phash',get_array_value($_POST,'phash',NULL,'is_notempty_string'),'is_notempty_string');
				if(!$this->phash) {
					$this->phash = is_array($_COOKIE) && array_key_exists('__napp_pHash_',$_COOKIE) && strlen($_COOKIE['__napp_pHash_']) && strlen($_COOKIE['__napp_pHash_'])>15 ? substr($_COOKIE['__napp_pHash_'],0,-15) : NULL;
				}//if(!$this->phash)
				if(!$this->phash ) { $this->phash = AppSession::GetNewUID(); }
			}//if(AppConfig::split_session_by_page())
		}//if($shell)
		if(is_array($params) && count($params)>0) {
			foreach($params as $key=>$value) {
				if(property_exists($this,$key)) {
					$prop = new \ReflectionProperty($this,$key);
					if(!$prop->isStatic()) {
						$this->$key = $value;
					} else {
						$this::$$key = $value;
					}//if(!$prop->isStatic())
				}//if(property_exists($this,$key))
			}//foreach($params as $key=>$value)
		}//if(is_array($params) && count($params)>0)
		if($shell) { return; }
		$this->InitDebugger();
		$this->StartOutputBuffer();
        $this->current_namespace = (array_key_exists('namespace',$params) && $params['namespace']) ? $params['namespace'] : '';
		global $_DOMAINS_CONFIG;
		if(!isset($_DOMAINS_CONFIG['domains']) || !is_array($_DOMAINS_CONFIG['domains'])) { die('Invalid domain registry settings!'); }
		$keydomain = $shell ? '_default' : (array_key_exists($this->url->GetAppDomain(),$_DOMAINS_CONFIG['domains']) ? $this->url->GetAppDomain() : (array_key_exists('_default',$_DOMAINS_CONFIG['domains']) ? '_default' : ''));
		if(!$keydomain || !isset($_DOMAINS_CONFIG['domains'][$keydomain]) || !$_DOMAINS_CONFIG['domains'][$keydomain]) { die('Wrong domain registry settings!'); }
		if(!$this->current_namespace) { $this->current_namespace = array_key_exists('namespace',$_GET) ? $_GET['namespace'] : $_DOMAINS_CONFIG['domains'][$keydomain]; }
		if(!isset($_DOMAINS_CONFIG['namespaces'][$this->current_namespace])) { die('Invalid namespace!'); }
		if(!is_array($this->globals)) { $this->globals = []; }
		$this->globals['domain_config'] = $_DOMAINS_CONFIG['namespaces'][$this->current_namespace];
		$this->default_db_connection = $this->globals['domain_config']['db_connection'];
		$this->url->url_virtual_path = isset($this->globals['domain_config']['link_alias']) ? $this->globals['domain_config']['link_alias'] : '';
		$default_views_dir = get_array_value($this->globals['domain_config'],'default_views_dir','','is_string');
		if(strlen($default_views_dir)) { AppConfig::app_default_views_dir($default_views_dir); }
		$views_extension = get_array_value($this->globals['domain_config'],'views_extension','','is_string');
		if(strlen($views_extension)) { AppConfig::app_views_extension($views_extension); }
		$app_theme = get_array_value($this->globals['domain_config'],'app_theme',NULL,'is_string');
		if(isset($app_theme)) { AppConfig::app_theme($app_theme); }
		$this->requires_login = $this->globals['domain_config']['requires_login'];
		$this->login_namespace = isset($this->globals['domain_config']['login_namespace']) ? $this->globals['domain_config']['login_namespace'] : NULL;
		$this->with_sections = $this->globals['domain_config']['with_sections'];
		$this->start_page = $this->globals['domain_config']['start_page'];
		$this->loggedin_start_page = isset($this->globals['domain_config']['loggedin_start_page']) ? $this->globals['domain_config']['loggedin_start_page'] : $this->start_page;
		if($shell) {
			self::$gui_loaded = FALSE;
			return;
		}//if($shell)
		self::$gui_loaded = $ajax;
		$this->url->data = $ajax ? (is_array($this->GetPageParam('get_params')) ? $this->GetPageParam('get_params') : []) : $this->url->data;
		$this->SetPageParam('get_params',$this->url->data);
		$this->url->special_params = array('language','urlid','namespace');
		if($ajax!==TRUE) {
			$curl = $this->url->GetCurrentUrl();
			if($this->GetPageParam('current_url')!=$curl) { $this->SetPageParam('old_url',$this->GetPageParam('current_url')); }
			$this->SetPageParam('current_url',$curl);
		}//if($ajax!==TRUE)
		if(AppSession::WithSession() && array_key_exists('robot',$_SESSION) && $_SESSION['robot']==1) { AppConfig::debug(FALSE); }
		self::$debug = AppConfig::debug();
	}//END protected function __construct
	/**
     * Check if AJAX request object is valid
     *
     * @return bool
     */
    public function IsValidAjaxRequest(): bool {
	    return (is_object($this->arequest) && is_subclass_of($this->arequest,BaseRequest::class));
	}//END public function IsValidAjaxRequest
    /**
     * Initialize AJAX Request object
     *
     * @param  array  $postParams Default parameters to be send via post on ajax requests
     * @param  string $subSession Sub-session key/path
     * @return bool
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function AjaxRequestInit(array $postParams = [],$subSession = NULL): bool {
	    if(!AppConfig::app_use_ajax_extension()) { return FALSE; }
	    if(!$this->IsValidAjaxRequest()) {
	        $ajaxRequestClass = AppConfig::ajax_class_name();
	        if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) { throw new AppException('Invalid AJAX Request class: ['.$ajaxRequestClass.']!'); }
			$this->arequest = new $ajaxRequestClass($this,$subSession,$postParams);
	    }//if(!$this->IsValidAjaxRequest())
        return TRUE;
	}//END public function AjaxRequestInit
    /**
     * Execute AJAX request class method
     *
     * @param array $postParams
     * @param null  $subSession
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function ExecuteAjaxRequest(array $postParams = [],$subSession = NULL): void {
	    if(!AppConfig::app_use_ajax_extension()) { die('Invalid AJAX request!'); }
	    $ajaxRequestClass = AppConfig::ajax_class_name();
	    if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) { die('Invalid AJAX Request class!'); }
	    /** @var \NETopes\Ajax\BaseRequest $ajaxRequestClass */
        $ajaxRequestClass::PrepareAndExecuteRequest($this,$postParams,$subSession);
	}//END public function ExecuteAjaxRequest
	/**
	 * Gets application absolute path
	 *
	 * @return string Returns the application absolute path
	 * @access public
	 */
	public function GetAppAbsolutePath() {
		return $this->app_absolute_path;
	}//END public function GetAppAbsolutePath
	/**
	 * @return bool
	 */
	public function OutputBufferStarted() {
		return self::$app_ob_started;
	}//END public function OutputBufferStarted
	/**
	 * @return bool
	 */
	public function StartOutputBuffer() {
		if(!$this->ajax && !AppConfig::buffered_output() && !$this->debugger) { return FALSE; }
		ob_start();
		return (self::$app_ob_started = TRUE);
	}//END public function StartOutputBuffer
	/**
	 * @param bool $end
	 * @return bool
	 */
	public function FlushOutputBuffer($end = FALSE) {
		if(!self::$app_ob_started) { return FALSE; }
		if(is_object($this->debugger)) { $this->debugger->SendData(); }
		if($end===TRUE) {
			ob_end_flush();
			self::$app_ob_started = FALSE;
		} else {
			ob_flush();
		}//if($end===TRUE)
		return TRUE;
	}//END public function FlushOutputBuffer
	/**
	 * @param bool $clear
	 * @return bool|string
	 */
	public function GetOutputBufferContent($clear = TRUE) {
		if(!self::$app_ob_started) { return FALSE; }
		if($clear===TRUE) {
			$content = ob_get_clean();
		} else {
			$content = ob_get_contents();
		}//if($clear===TRUE)
		return $content;
	}//END public function GetOutputBufferContent
	/**
	 * @param bool $end
	 * @return bool
	 */
	public function ClearOutputBuffer($end = FALSE) {
		if(!self::$app_ob_started) { return FALSE; }
		if($end===TRUE) {
			ob_end_clean();
			self::$app_ob_started = FALSE;
		} else {
			ob_clean();
		}//if($end===TRUE)
		return TRUE;
	}//END public function ClearOutputBuffer
    /**
     * Commit the temporary session into the session
     *
     * @param  bool   $clear If TRUE is passed the session will be cleared
     * @param  bool   $preserve_output_buffer If true output buffer is preserved
     * @param  bool   $show_errors Display errors TRUE/FALSE
     * @param  string $key Session key to commit (do partial commit)
     * @param  string $phash Page (tab) hash
     * @param  bool   $reload Reload session data after commit
     * @return void
     * @poaram bool $reload Reload session after commit (default TRUE)
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function SessionCommit($clear = FALSE,$preserve_output_buffer = FALSE,$show_errors = TRUE,$key = NULL,$phash = NULL,$reload = TRUE) {
		if(!AppSession::WithSession()) {
			if($show_errors && method_exists('\ErrorHandler','ShowErrors')) { \ErrorHandler::ShowErrors(); }
			if($preserve_output_buffer!==TRUE) { $this->FlushOutputBuffer(); }
			return;
		}//if(!AppSession::WithSession())
		AppSession::SessionCommit($clear,$show_errors,$key,$phash,$reload);
		if($preserve_output_buffer!==TRUE) { $this->FlushOutputBuffer(); }
	}//END public function SessionCommit
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
	 */
	public function GetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
		$lphash = isset($phash) ? $phash : $this->phash;
		return AppSession::GetGlobalParam($key,$lphash,$path,$keys_case);
	}//END public function GetGlobalParam
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
	 */
	public function SetGlobalParam($key,$val,$phash = NULL,$path = NULL,$keys_case = NULL) {
		$lphash = isset($phash) ? $phash : $this->phash;
		return AppSession::SetGlobalParam($key,$val,$lphash,$path,$keys_case);
	}//END public function SetGlobalParam
	/**
	 * Delete a global parameter (a parameter from first level of the array) from the session data array
	 *
	 * @param  string $key The key of the searched parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (App property) page hash will not be used
	 * @param  null   $path
	 * @param  null   $keys_case
	 * @return bool
	 * @access public
	 */
	public function UnsetGlobalParam($key,$phash = NULL,$path = NULL,$keys_case = NULL) {
		$lphash = isset($phash) ? $phash : $this->phash;
		return AppSession::UnsetGlobalParam($key,$lphash,$path,$keys_case);
	}//END public function UnsetGlobalParam
	/**
	 * Redirect to home page/login page if an error occurs in Ajax\Request execution
	 *
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public function RedirectOnError() {
		if($this->ajax && is_subclass_of($this->arequest,BaseRequest::class,FALSE)) {
			echo BaseRequest::$app_act_sep.'window.location.href = "'.$this->GetAppWebLink().'";';
		} else {
			header('Location:'.$this->GetAppWebLink());
			exit();
		}//if($this->ajax)
	}//END public function RedirectOnError
	/**
	 * Redirect to a url
	 *
	 * @param  string $url Target URL for the redirect
	 * (if empty or null, the redirect will be made to the application root url)
	 * @param  bool $dont_commit_session Flag for bypassing session commit on redirect
	 * @param  bool $exit Flag for stopping or not the request execution
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public function Redirect($url = NULL,$dont_commit_session = FALSE,$exit = TRUE) {
		$lurl = strlen($url) ? $url : $this->GetAppWebLink();
		if($dont_commit_session!==TRUE) { $this->NamespaceSessionCommit(FALSE,TRUE); }
		if($this->ajax) {
			// TODO: check if is working every time
			if($this->IsValidAjaxRequest()) {
				$this->arequest->ExecuteJs("window.location.href = '{$lurl}';");
			}//if($this->IsValidAjaxRequest())
		} else {
			header('Location:'.$lurl);
			if($exit) { $this->FlushOutputBuffer(); exit(); }
		}//if($this->ajax && is_object($this->arequest))
	}//END public function Redirect
	/**
	 * Redirect to a url by modifying headers
	 *
	 * @param  string $url Target URL for the redirect
	 * (if empty or null, the redirect will be made to the application root url)
	 * @param  bool $dont_commit_session Flag for bypassing session commit on redirect
	 * @param  bool $exit Flag for stopping or not the request execution
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public function FullRedirect($url = NULL,$dont_commit_session = FALSE,$exit = TRUE) {
		$lurl = strlen($url) ? $url : $this->GetAppWebLink();
		if($dont_commit_session!==TRUE) {$this->NamespaceSessionCommit(FALSE,TRUE); }
		header('Location:'.$lurl);
		if($exit) { $this->FlushOutputBuffer(); exit(); }
	}//END public function FullRedirect
    /**
     * Commit the namespace temporary session into the session
     *
     * @param null $clear
     * @param bool $preserve_output_buffer
     * @param bool $show_errors
     * @param null $namespace
     * @param null $phash
     * @return void
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function NamespaceSessionCommit($clear = NULL,$preserve_output_buffer = FALSE,$show_errors = TRUE,$namespace = NULL,$phash = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$lclear = isset($clear) ? $clear : $this->clear_namespace_session;
		$this->SessionCommit($lclear,$preserve_output_buffer,$show_errors,$lnamespace,$phash);
	}//END public function NamespaceSessionCommit
	/**
	 * Set clear namespace session flag (on commit namespace session will be cleared)
	 *
	 * @return void
	 * @access public
	 */
	public function ClearNamespaceSession() {
		$this->clear_namespace_session = TRUE;
	}//END public function ClearNamespaceSession
	/**
	 * Gets a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param bool    $phash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return mixed  Returns the parameter value or NULL
	 * @access public
	 */
	public function GetParam($key,$phash = FALSE,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$lphash = isset($phash) ? $phash : $this->phash;
		$data = AppSession::GetData();
		if(!is_array($data) || !array_key_exists($lnamespace,$data) || !is_array($data[$lnamespace]) || ($lphash && (!array_key_exists($lphash,$data[$lnamespace]) || !is_array($data[$lnamespace][$lphash])))) { return NULL; }
		$lkey = AppSession::ConvertToSessionCase($key);
		if($lphash) { return array_key_exists($lkey,$data[$lnamespace][$lphash]) ? $data[$lnamespace][$lphash][$lkey] : NULL; }
		return array_key_exists($lkey,$data[$lnamespace]) ? $data[$lnamespace][$lkey] : NULL;
	}//END public function GetParam
	/**
	 * Gets a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return mixed  Returns the parameter value or NULL
	 * @access public
	 */
	public function GetPageParam($key,$phash = NULL,$namespace = NULL) {
		return $this->GetParam($key,$phash,$namespace);
	}//END public function GetPageParam
	/**
	 * Sets a parameter to the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  mixed  $val The value of the parameter
	 * @param bool    $phash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
	 */
	public function SetParam($key,$val,$phash = FALSE,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$lphash = isset($phash) ? $phash : $this->phash;
		$data = AppSession::GetData();
		if(!is_array($data)) { $data = []; }
		$lkey = AppSession::ConvertToSessionCase($key);
		if($lphash) {
			$data[$lnamespace][$lphash][$lkey] = $val;
		} else {
			$data[$lnamespace][$lkey] = $val;
		}//if($lphash)
		AppSession::SetData($data);
	}//END public function SetParam
	/**
	 * Sets a parameter to the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  mixed  $val The value of the parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
	 */
	public function SetPageParam($key,$val,$phash = NULL,$namespace = NULL) {
		$this->SetParam($key,$val,$phash,$namespace);
	}//END public function SetPageParam
	/**
	 * Delete a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param bool    $phash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
	 */
	public function UnsetParam($key,$phash = FALSE,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$lphash = isset($phash) ? $phash : $this->phash;
		$data = AppSession::GetData();
		$lkey = AppSession::ConvertToSessionCase($key);
		if($lphash) {
			unset($data[$lnamespace][$lphash][$lkey]);
		} else {
			unset($data[$lnamespace][$lkey]);
		}//if($lphash)
		AppSession::SetData($data);
	}//END public function UnsetParam
	/**
	 * Delete a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
	 */
	public function UnsetPageParam($key,$phash = NULL,$namespace = NULL) {
		$this->UnsetParam($key,$phash,$namespace);
	}//END public function UnsetPageParam
	/**
	 * description
	 *
	 * @param      $uid
	 * @param null $namespace
	 * @return mixed
	 * @access public
	 */
	public function GetSessionAcceptedRequest($uid,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$data = AppSession::GetData();
		if(!isset($data[$lnamespace]['xURLRequests'][$uid])) { return NULL; }
		return $data[$lnamespace]['xURLRequests'][$uid];
	}//END public function GetSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $uid
	 * @param null $namespace
	 * @return string
	 * @access public
	 */
	public function SetSessionAcceptedRequest($uid,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		if($uid===TRUE) { $uid = AppSession::GetNewUID(NULL,'md5'); }
		$data = AppSession::GetData();
		$data[$lnamespace]['xURLRequests'][$uid] = TRUE;
		AppSession::SetData($data);
		return $uid;
	}//END public function SetSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $uid
	 * @param null $namespace
	 * @return void
	 * @access public
	 */
	public function UnsetSessionAcceptedRequest($uid,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$data = AppSession::GetData();
		unset($data[$lnamespace]['xURLRequests'][$uid]);
		AppSession::SetData($data);
	}//END public function UnsetSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $uid
	 * @param bool $reset
	 * @param null $namespace
	 * @return bool
	 * @access public
	 */
	public function CheckSessionAcceptedRequest($uid,$reset = FALSE,$namespace = NULL) {
		$result = $this->GetSessionAcceptedRequest($uid,$namespace);
		if($reset===TRUE) { $this->UnsetSessionAcceptedRequest($uid,$namespace); }
		return ($result===TRUE);
	}//END public function CheckSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $key
	 * @param null $def_value
	 * @param null $validation
	 * @return mixed
	 * @access public
	 */
	public function GetGlobalVar($key,$def_value = NULL,$validation = NULL) {
	    return get_array_value($this->globals,$key,$def_value,$validation);
	}//END public function GetGlobalVar
	/**
	 * description
	 *
	 * @param $key
	 * @param $value
	 * @return bool
	 * @access public
	 */
	public function SetGlobalVar($key,$value) {
		if(!is_numeric($key) && (!is_string($key) || !strlen($key))) { return FALSE; }
		if(!is_array($this->globals)) { $this->globals = []; }
		$this->globals[$key] = $value;
		return TRUE;
	}//END public function SetGlobalVar
	/**
     * Initialize NETopes application javascript
     *
	 * @param bool $output
	 * @return string|null
	 */
	public function JsInit(bool $output = TRUE): ?string {
	    $jsRootUrl = $this->app->app_web_link.AppConfig::app_js_path();
	    $jsThemeBaseUrl = $this->app_web_link.$this->GetSectionPath();
	    $js = <<<HTML
        <script type="text/javascript">
            const xAppWebLink = '{$this->app_web_link}';
            const xAppThemeLink = '{$jsThemeBaseUrl}';
            const NAPP_PHASH = '{$this->app->phash}';
            const NAPP_JS_PATH = '{$jsRootUrl}';
        </script>
        <script type="text/javascript" src="{$jsRootUrl}/gibberish-aes.min.js?v=1901081"></script>
        <script type="text/javascript" src="{$jsRootUrl}/main.min.js?v=1901081"></script>
HTML;
        if($this->IsValidAjaxRequest()) { $js .= $this->arequest->GetJsScripts($jsRootUrl); }
		if(is_object($this->debugger)) {
			$dbg_scripts = $this->debugger->GetScripts();
			if(is_array($dbg_scripts) && count($dbg_scripts)) {
				foreach($dbg_scripts as $dsk=>$ds) {
				    $js .= <<<HTML
        <script type="text/javascript" src="{$jsRootUrl}/debug/{$ds}?v=1712011"></script>
HTML;
				}//END foreach
			}//if(is_array($dbg_scripts) && count($dbg_scripts))
		}//if(is_object($this->debugger))
		if($output) { echo $js; return NULL; }
		return $js;
	}//END public function JsInit
	/**
	 * Add javascript code to the dynamic js queue (executed at the end of the current request)
	 *
	 * @param  string $value Javascript code
	 * @param bool    $dynamic
	 * @return void
	 * @access public
	 */
	public function ExecJs($value,$dynamic = FALSE) {
		if(!is_string($value) || !strlen($value)) { return; }
		if(!$dynamic && $this->ajax && is_object($this->arequest)) {
			$this->arequest->ExecuteJs($value);
		} else {
			$dynamic_js_scripts = $this->GetGlobalVar('dynamic_js_scripts',[],'is_array');
			$dynamic_js_scripts[] = ['js'=>$value];
			$this->SetGlobalVar('dynamic_js_scripts',$dynamic_js_scripts);
		}//if($this->ajax && is_object($this->arequest))
	}//END public function ExecJs
	/**
	 * Get dynamic javascript to be executed
	 *
	 * @return string Returns scripts to be executed
	 * @access public
	 */
	public function GetDynamicJs() {
		$scripts = self::GetGlobalVar('dynamic_js_scripts',[],'is_array');
		if(!count($scripts)) { return NULL; }
		$html_data = '';
		$data = '';
		foreach($scripts as $s) {
			if(is_array($s)) {
				$d_js = get_array_value($s,'js','','is_string');
				$d_html = get_array_value($s,'html','','is_string');
				if(strlen($d_js)) { $data .= $d_js."\n"; }
				if(strlen($d_html)) { $html_data .= $d_html."\n"; }
			} elseif(is_string($s) && strlen($s)) {
				$data .= $s."\n\n";
			}//if(is_array($s))
		}//END foreach
		return $html_data.'<script type="text/javascript">'."\n".$data."\n".'</script>'."\n";
	}//END public function GetDynamicJs
	/**
	 * description
	 *
	 * @return void
	 * @access public
	 */
	public function ProcessRequestParams() {
		if(!is_array($this->globals)) { $this->globals = []; }
		if(!array_key_exists('req_params',$this->globals) || !is_array($this->globals['req_params'])) { $this->globals['req_params'] = []; }
		$uripage = $this->url->GetParamElement('page');
		$uripag = $this->url->GetParamElement('pag');
		$this->globals['req_params']['id_page'] = is_numeric($uripage) ? $uripage : NULL;
		$this->globals['req_params']['pagination'] = is_numeric($uripag) ? $uripag : NULL;
		$urlid = strtolower(trim($this->url->GetParamElement('urlid'),'/'));
		if(strpos($urlid,'/')===FALSE) {
			$this->globals['req_params']['category'] = NULL;
			$this->globals['req_params']['subcategories'] = NULL;
			$this->globals['req_params']['page'] = $urlid;
		} else {
			$urlid_arr = explode('/',$urlid);
			$e_page = array_pop($urlid_arr);
			$e_cat = array_shift($urlid_arr);
			$e_scat = is_array($urlid_arr) && count($urlid_arr) ? implode('/',$urlid_arr) : NULL;
			$this->globals['req_params']['category'] = $e_cat;
			$this->globals['req_params']['subcategories'] = $e_scat;
			$this->globals['req_params']['page'] = $e_page;
		}//if(strpos($urlid,'/')===FALSE)
		$this->globals['req_params']['module'] = $this->url->GetParam('module');
		$this->globals['req_params']['action'] = $this->url->GetParam('a');
	}//END public function ProcessRequestParams
	/**
	 * description
	 *
	 * @param      $key
	 * @param null $def_value
	 * @param null $validation
	 * @return mixed
	 * @access public
	 */
	public function GetRequestParamValue($key,$def_value = NULL,$validation = NULL) {
		if(!is_array($this->globals) || !array_key_exists('req_params',$this->globals)) { return $def_value; }
		return get_array_value($this->globals['req_params'],$key,$def_value,$validation);
	}//END public function GetRequestParamValue
	/**
	 * Get current language ID
	 *
	 * @return int|null Returns current language ID
	 * @access public
	 */
	public function GetLanguageId() {
		$result = $this->GetPageParam('id_language');
		if(!is_numeric($result) || $result<=0) {
			$result = $this->GetParam('id_language');
		}//if(!is_numeric($result) || $result<=0)
		// $this->Dlog($result,'GetLanguageId');
		return $result;
	}//END public function GetLanguageId
	/**
	 * Get current language code
	 *
	 * @return string Returns current language code
	 * @access public
	 */
	public function GetLanguageCode() {
		$result = $this->GetPageParam('language_code');
		if(!is_string($result) || !strlen($result)) { $result = $this->GetParam('language_code'); }
		// $this->Dlog($result,'GetLanguageCode');
		return $result;
	}//END public function GetLanguageCode
	/**
	 * Gets the current application version
	 *
	 * @param  string $type Specifies the return type:
	 * - NULL or empty string (default) for return as string
	 * - 'array' for return as array (key-value)
	 * - 'major' for return only the major version as int
	 * - 'minor' for return only the minor version as int
	 * - 'build' for return only the build version as int
	 * @return mixed Returns the application version as a string or an array or a specific part of the version
	 * @access public
	 */
	public function GetVersion($type = NULL) {
		switch($type) {
		  	case 'array':
				$ver_arr = explode('.',AppConfig::app_version());
				return array('major'=>$ver_arr[0],'minor'=>$ver_arr[1],'build'=>$ver_arr[2]);
				break;
			case 'major':
				$ver_arr = explode('.',AppConfig::app_version());
				return intval($ver_arr[0]);
				break;
			case 'minor':
				$ver_arr = explode('.',AppConfig::app_version());
				return intval($ver_arr[1]);
				break;
			case 'buid':
				$ver_arr = explode('.',AppConfig::app_version());
				return intval($ver_arr[2]);
				break;
		  	default:
				return AppConfig::app_version();
				break;
		}//END switch
	}//END public function GetVersion
	/**
	 * Gets the current application framework version
	 *
	 * @param  string $type Specifies the return type:
	 * - NULL or empty string (default) for return as string
	 * - 'array' for return as array (key-value)
	 * - 'major' for return only the major version as int
	 * - 'minor' for return only the minor version as int
	 * - 'build' for return only the build version as int
	 * @return mixed Returns the application framework version
	 * @access public
	 */
	public function GetFrameworkVersion($type = NULL) {
		switch($type) {
		    case 'array':
				$ver_arr = explode('.',AppConfig::framework_version());
				return array('major'=>$ver_arr[0],'minor'=>$ver_arr[1],'build'=>$ver_arr[2]);
				break;
			case 'major':
				$ver_arr = explode('.',AppConfig::framework_version());
				return intval($ver_arr[0]);
				break;
			case 'minor':
				$ver_arr = explode('.',AppConfig::framework_version());
				return intval($ver_arr[1]);
				break;
			case 'buid':
				$ver_arr = explode('.',AppConfig::framework_version());
				return intval($ver_arr[2]);
				break;
		    default:
				return AppConfig::framework_version();
				break;
		}//END switch
	}//END public function GetFrameworkVersion
	/**
	 * description
	 *
	 * @return string
	 * @access public
	 */
	public function GetAppDomain() {
		return $this->url->GetAppDomain();
	}//END public function GetAppDomain
	/**
	 * Gets the website name
	 *
	 * @return string Returns the website name
	 * @access public
	 */
	public function GetWebsiteName() {
		return AppConfig::website_name();
	}//END public function GetWebsiteName
	/**
	 * Gets the application name
	 *
	 * @return string Returns the application name
	 * @access public
	 */
	public function GetAppName() {
		return AppConfig::app_name();
	}//END public function GetAppName
	/**
	 * Gets the application copyright
	 *
	 * @return string Returns the application copyright
	 * @access public
	 */
	public function GetAppCopyright() {
		$copyright = AppConfig::app_copyright();
		return ($copyright ? $copyright : '&copy; ').date('Y');
	}//END public function GetAppCopyright
	/**
	 * Gets first page title
	 *
	 * @return string Returns first page title
	 * @access public
	 */
	public function GetFirstPageTitle() {
		return AppConfig::app_first_page_title();
	}//END public function GetFirstPageTitle
	/**
	 * Gets author name
	 *
	 * @return string Returns author name
	 * @access public
	 */
	public function GetAuthorName() {
		return AppConfig::app_author_name();
	}//END public function GetAuthorName
	/**
	 * Gets provider name
	 *
	 * @return string Returns provider name
	 * @access public
	 */
	public function GetProviderName() {
		return AppConfig::app_provider_name();
	}//END public function GetProviderName
	/**
	 * Gets provider url
	 *
	 * @return string Returns provider url
	 * @access public
	 */
	public function GetProviderUrl() {
		return AppConfig::app_provider_url();
	}//END public function GetProviderUrl
	/**
	 * Gets multi-language flag
	 *
	 * @param string $namespace Namespace to test if is multi-language
	 * If NULL or empty, current namespace is used
	 * @return bool Returns multi-lanhuage flag
	 * @access public
	 */
	public function IsMultiLanguage($namespace = NULL) {
		if(!is_array(AppConfig::app_multi_language())) { return AppConfig::app_multi_language(); }
		$namespace = $namespace ? $namespace : $this->current_namespace;
		return get_array_value(AppConfig::app_multi_language(),$namespace,TRUE,'bool');
	}//END public function IsMultiLanguage
	/**
	 * Get database cache state
	 *
	 * @return boolean TRUE is database caching is active for this namespace, FALSE otherwise
	 * @access public
	 */
	public function CacheDbCall() {
		return (AppConfig::app_db_cache() && $this->current_namespace=='web');
	}//END public static function CacheDbCall
	/**
	 * Gets the login timeout in minutes
	 *
	 * @return int Returns login timeout
	 * @access public
	 */
	public function GetLoginTimeout() {
		$cookie_hash = $this->GetCookieHash();
		if(array_key_exists($cookie_hash,$_COOKIE) && strlen($_COOKIE[$cookie_hash]) && $_COOKIE[$cookie_hash]==$this->GetParam('user_hash')) { return intval(AppConfig::cookie_login_lifetime() / 24 / 60); }
		return intval(AppConfig::session_timeout() / 60);
	}//END public function GetLoginTimeout
	/**
	 * description
	 *
	 * @return string
	 * @access public
	 */
	public function GetCurrentNamespace() {
		return $this->current_namespace;
	}//END public function GetCurrentTemplate
	/**
	 * Get application non-public repository path
	 *
	 * @return string
	 * @access public
	 */
	public function GetRepositoryPath() {
		$repository_path = AppConfig::repository_path();
		if(is_string($repository_path) && strlen($repository_path) && file_exists($repository_path)) {
			return rtrim($repository_path,'/\\').'/';
		}//if(is_string($repository_path) && strlen($repository_path) && file_exists($repository_path))
		return $this->app_path.'/repository/';
	}//END public function GetRepositoryPath
	/**
	 * Get application cache path
	 *
	 * @return string
	 * @access public
	 */
	public function GetCachePath() {
		$cache_path = AppConfig::app_cache_path();
		if(is_string($cache_path) && strlen($cache_path) && file_exists($cache_path)) {
			return rtrim($cache_path,'/\\').'/';
		}//if(is_string($cache_path) && strlen($cache_path) && file_exists($cache_path))
		if(!file_exists($this->app_path.'/.cache')) {
			mkdir($this->app_path.'/.cache',755);
		}//if(!file_exists($this->app_path.'/.cache'))
		return $this->app_path.'/.cache/';
	}//END public function GetCachePath
	/**
	 * Gets the key used to encrypt data send/receive through API
	 *
	 * @return string Returns the encryption key used in API communications
	 * @access public
	 */
	public function GetApiKey() {
		return AppConfig::app_api_key();
	}//END public function GetApiKey
    /**
	 * Gets the API security key separator (key used for authentication)
	 *
	 * @return string Returns the API security key separator
	 * @access public
	 */
	public function GetApiSeparator() {
		return AppConfig::app_api_separator();
	}//END public function GetApiSeparator
	/**
	 * Gets the account API security key (auto loaded on LoadAppOptions() method)
	 *
	 * @return string Returns account API security key
	 * @access public
	 */
	public function GetMyAccessKey() {
		return $this->app_access_key;
	}//END public function GetMyAccessKey
	/**
	 * Gets the previous visited URL
	 *
	 * @return string Returns previous URL or home URL
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public function GetPreviousUrl() {
		$url = $this->GetPageParam('old_url');
		if(is_string($url) && strlen($url)) { return $url; }
		return $this->GetAppWebLink();
	}//END public function GetPreviousUrl
	/**
	 * Gets the application base link with or without language path
	 *
	 * @param null    $uri
	 * @param  string $namespace Namespace for generating app_web_link or NULL for current namespace
	 * @param  bool   $base If set to TRUE will return only base link (app_web_link property) else will return base link + language path
	 * @param null    $langcode
	 * @return string The link of the application (with or without language path)
	 * @throws \NETopes\Core\AppException
	 * @access public
	 */
	public function GetAppWebLink($uri = NULL,$namespace = NULL,$base = FALSE,$langcode = NULL) {
		$namespace = $namespace ? $namespace : $this->current_namespace;
		if($namespace!=$this->current_namespace) {
			global $_DOMAINS_CONFIG;
			$domainreg = get_array_value($_DOMAINS_CONFIG,['namespaces',$namespace],NULL,'is_array');
			if(!is_array($domainreg) || !count($domainreg)) { throw new AppException('Invalid domain registry!',E_ERROR); }
			$ns_link_alias = get_array_value($domainreg,'link_alias','','is_string');
		} else {
			$ns_link_alias = get_array_value($this->globals,['domain_config','link_alias'],'','is_string');
		}//if($namespace!=$this->current_namespace)
		$llangcode = $langcode===FALSE ? '' : (is_string($langcode) && strlen($langcode) ? $langcode : $this->GetLanguageCode());
		$lang = ($this->IsMultiLanguage($namespace) && !AppConfig::url_without_language() && strlen($llangcode)) ? strtolower($llangcode) : '';
		if(AppConfig::app_mod_rewrite()) {
			if($base) { return $this->app_web_link.'/'.($ns_link_alias ? $ns_link_alias.'/' : ''); }
			return $this->app_web_link.'/'.($ns_link_alias ? $ns_link_alias.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen($uri) ? '?'.$uri : '');
		}//if(AppConfig::app_mod_rewrite())
		$url = $this->app_web_link.'/';
		if(strlen($ns_link_alias)) { $url .= '?namespace='.$ns_link_alias; }
		if($base) { return $url; }
		if(strlen($lang)) { $url .= (strpos($url,'?')===FALSE ? '?' : '&').'language='.$lang; }
		if(strlen($uri)) { $url .= (strpos($url,'?')===FALSE ? '?' : '&').$uri; }
		return $url;
	}//END public function GetAppWebLink
	/**
	 * Create new application URL
	 *
	 * @param object|null $params Parameters object (instance of [Params])
	 * @param int         $url_format
	 * @return string
	 * @access public
	 */
	public function GetNewUrl($params = NULL,$url_format = Url::URL_FORMAT_FRIENDLY) {
		return $this->url->GetNewUrl($params,$url_format);
	}//public function GetNewUrl
	/**
	 * Gets the session state befor current request (TRUE for existing session or FALSE for newly initialized)
	 *
	 * @return bool Session state (TRUE for existing session or FALSE for newly initialized)
	 * @access public
	 */
	public function CheckGlobalParams() {
		return $this->_app_state;
	}//public function CheckGlobalParams
    /**
     * Initializes KCFinder session parameters
     *
     * @param array $params
     * @return void
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function InitializeKCFinder($params = NULL) {
		if(!AppSession::WithSession() || !AppConfig::use_kc_finder()) { return; }
		$type = get_array_value($params,'type','','is_string');
		switch(strtolower($type)) {
			case 'public':
				AppSession::SetGlobalParam('disabled',FALSE,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadURL',$this->app_web_link.'/repository/public','__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadDir',$this->app_public_path.'/repository/public','__KCFINDER',NULL,FALSE);
				break;
			case 'app':
				AppSession::SetGlobalParam('disabled',($this->login_status && $this->GetParam('user_hash')) ? FALSE : TRUE,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadURL',$this->app_web_link.'/repository/app','__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadDir',$this->app_public_path.'/repository/app','__KCFINDER',NULL,FALSE);
				break;
			case 'cms':
			default:
				$section_folder = get_array_value($params,'section_folder',$this->GetParam('section_folder'),'is_string');
				$zone_code = get_array_value($params,'zone_code',$this->GetParam('zone_code'),'is_string');
				// TODO: fix multi instance
				AppSession::SetGlobalParam('disabled',($this->login_status && $this->GetParam('user_hash')) ? FALSE : TRUE,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadURL',$this->app_web_link.'/repository/'.$section_folder.'/'.$zone_code,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadDir',$this->app_public_path.'/repository/'.$section_folder.'/'.$zone_code,'__KCFINDER',NULL,FALSE);
				break;
		}//END switch
		$this->SessionCommit(FALSE,TRUE,TRUE,NULL,'__KCFINDER',FALSE);
	}//END public function InitializeKCFinder
	/**
	 * Gets the login cookie hash
	 *
	 * @param  string $namespace The namespace for the cookie or NULL for current namespace
	 * @param null    $salt
	 * @return string The name (hash) of the login cookie
	 * @access public
	 */
	public function GetCookieHash($namespace = NULL,$salt = NULL) {
		$lnamespace = $namespace ? $namespace : $this->current_namespace;
		$lsalt = strlen($salt) ? $salt : 'loggedin';
		return AppSession::GetNewUID(AppConfig::app_session_key().$this->url->GetAppDomain().$this->url->GetUrlFolder().$lnamespace.$lsalt,'sha256',TRUE);
	}//END public function GetCookieHash
	/**
	 * description
	 *
	 * @param      $name
	 * @param null $namespace
	 * @param bool $set_if_missing
	 * @param null $validity
	 * @return string|null
	 * @access public
	 */
	public function GetHashFromCookie($name,$namespace = NULL,$set_if_missing = TRUE,$validity = NULL) {
		$c_hash = $this->GetCookieHash($namespace,$name);
		$c_cookie_hash = NULL;
		if(array_key_exists($c_hash,$_COOKIE) && strlen($_COOKIE[$c_hash])) {
			$c_cookie_hash = \GibberishAES::dec($_COOKIE[$c_hash],AppConfig::app_session_key());
		} elseif($set_if_missing===TRUE || $set_if_missing===1 || $set_if_missing==='1') {
			$c_cookie_hash = AppSession::GetNewUID();
			$lvalability = (is_numeric($validity) && $validity>0 ? $validity : 180)*24*3600;
			$_COOKIE[$c_hash] = \GibberishAES::enc($c_cookie_hash,AppConfig::app_session_key());
			setcookie($c_hash,$_COOKIE[$c_hash],time()+$lvalability,'/',$this->url->GetAppDomain());
		}//if(array_key_exists($sc_hash,$_COOKIE) && strlen($_COOKIE[$sc_hash]))
		return $c_cookie_hash;
	}//END public function GetHashFromCookie
	/**
	 * Set the login cookie
	 *
	 * @param  string $uhash The user hash
	 * @param  integer $validity The cookie lifetime or NULL for default
	 * @param  string $cookie_hash The name (hash) of the login cookie
	 * @param  string $namespace The namespace for the cookie or NULL for current namespace
	 * @return bool True on success or false
	 * @access public
	 */
	public function SetLoginCookie($uhash,$validity = NULL,$cookie_hash = NULL,$namespace = NULL) {
		if(!is_string($uhash)) { return FALSE; }
		$lvalidity = is_numeric($validity) && $validity>0 ? $validity : AppConfig::cookie_login_lifetime()*24*3600;
		$lcookie_hash = $cookie_hash ? $cookie_hash : $this->GetCookieHash($namespace);
		if(!$uhash) {
			unset($_COOKIE[$cookie_hash]);
			setcookie($lcookie_hash,'',time()+$lvalidity,'/',$this->url->GetAppDomain());
			return TRUE;
		}//if(!$uhash)
		$_COOKIE[$cookie_hash] = \GibberishAES::enc($uhash,AppConfig::app_session_key());
		setcookie($lcookie_hash,$_COOKIE[$cookie_hash],time()+$lvalidity,'/',$this->url->GetAppDomain());
		return TRUE;
	}//END public function SetLoginCookie
    /**
     * Loads application settings from database or from request parameters
     *
     * @param bool       $notFromDb
     * @param array|null $params
     * @return void
     * @throws \Exception
     * @access public
     */
	public function LoadAppSettings(bool $notFromDb = FALSE,?array $params = NULL): void {
		if($this->app_options_loaded) { return; }
		$cookie_hash = $this->GetCookieHash();
		$auto_login = 1;
		$user_hash = $this->url->GetParam('uhash');
		if(!strlen($user_hash) && array_key_exists($cookie_hash,$_COOKIE) && strlen($_COOKIE[$cookie_hash])) {
			$user_hash = \GibberishAES::dec($_COOKIE[$cookie_hash],AppConfig::app_session_key());
		}//if(!strlen($user_hash) && array_key_exists($cookie_hash,$_COOKIE) && strlen($_COOKIE[$cookie_hash]))
		if(!strlen($user_hash)) {
			$auto_login = 0;
			$user_hash = $this->GetParam('user_hash');
		}//if(!strlen($user_hash))
		$idsection = $this->url->GetParam('section');
		$idzone = $this->url->GetParam('zone');
		$langcode = $this->url->GetParam('language');
		if($this->ajax || !is_string($langcode) || !strlen($langcode)) { $langcode = $this->GetLanguageCode(); }
		if($notFromDb) {
		    $this->user_status = -1;
            $this->login_status = FALSE;
            $this->SetParam('login_status',$this->login_status);
            $this->SetParam('id_section',$idsection);
            $this->SetParam('id_zone',$idzone);
            $this->SetParam('user_hash',$user_hash);
            $this->current_section_folder = '';
            $this->SetParam('account_timezone',AppConfig::server_timezone());
            $this->SetParam('timezone',AppConfig::server_timezone());
            if(strlen(AppConfig::server_timezone())) { date_default_timezone_set(AppConfig::server_timezone()); }
            $this->SetParam('website_name',AppConfig::website_name());
            $this->SetParam('rows_per_page',20);
            $this->SetParam('decimal_separator','.');
            $this->SetParam('group_separator',',');
            $this->SetParam('date_separator','.');
            $this->SetParam('time_separator',':');
            $this->SetPageParam('language_code',strtolower($langcode));
            $this->url->SetParam('language',$langcode);
        } else {
            $appdata = DataProvider::Get('System\System','GetAppSettings',[
                'for_domain'=>$this->url->GetAppDomain(),
                'for_namespace'=>$this->current_namespace,
                'for_lang_code'=>$langcode,
                'for_user_hash'=>$user_hash,
                'login_namespace'=>(strlen($this->login_namespace) ? $this->login_namespace : NULL),
                'section_id'=>((is_numeric($idsection) && $idsection>0) ? $idsection : NULL),
                'zone_id'=>((is_numeric($idzone) && $idzone>0) ? $idzone : NULL),
                'validity'=>$this->GetLoginTimeout(),
                'keep_alive'=>($this->keep_alive ? 1 : 0),
                'auto_login'=>$auto_login,
                'for_user_ip'=>(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'),
            ],['mode'=>'native']);
            if(!is_object($appdata)) { die('Invalid application settings!'); }
            $login_msg = $appdata->safeGetLoginMsg('','is_string');
            if(!is_object($appdata) || !$appdata->safeGetIdAccount(0,'is_integer')  || !$appdata->safeGetIdSection(0,'is_integer') || !$appdata->safeGetIdZone(0,'is_integer') || !$appdata->safeGetIdLanguage(0,'is_integer') || $login_msg=='incorect_namespace') { die('Wrong domain or application settings !!!'); }
            $this->user_status = $appdata->safeGetState(-1,'is_integer');
            $this->login_status = ($login_msg=='1' && ($this->user_status==1 || $this->user_status==2));
            if($this->login_status && isset($_COOKIE[$cookie_hash]) && strlen($appdata->getProperty('user_hash'))) {
                $this->SetLoginCookie($appdata->getProperty('user_hash'),NULL,$cookie_hash);
            }//if($this->login_status && isset($_COOKIE[$cookie_hash]) && strlen($appdata->getProperty('user_hash')))
            $this->SetParam('login_status',$this->login_status);
            $this->SetParam('id_registry',$appdata->getProperty('id_registry'));
            $this->SetParam('id_section',$appdata->getProperty('id_section'));
            $this->SetParam('section_folder',$appdata->getProperty('section_folder'));
            $this->current_section_folder = '';
            $c_section_dir = $appdata->getProperty('section_folder','','is_string');
            if($this->with_sections && strlen($c_section_dir)) { $this->current_section_folder = '/'.$c_section_dir; }
            $this->SetParam('id_zone',$appdata->getProperty('id_zone'));
            $this->SetParam('zone_code',$appdata->getProperty('zone_code'));
            $this->SetParam('id_account',$appdata->getProperty('id_account'));
            $this->SetPageParam('id_account',$appdata->getProperty('id_account'));
            $this->SetParam('account_type',$appdata->getProperty('account_type'));
            $this->SetParam('account_name',$appdata->getProperty('account_name'));
            $this->SetParam('access_key',$appdata->getProperty('access_key'));
            $this->app_access_key = $appdata->getProperty('access_key');
            $this->SetParam('account_timezone',$appdata->getProperty('account_timezone',AppConfig::server_timezone(),'is_notempty_string'));
            $this->SetParam('id_entity',$appdata->getProperty('id_entity'));
            $this->SetParam('id_location',$appdata->getProperty('id_location'));
            $this->SetParam('website_name',$appdata->getProperty('website_name'));
            $this->SetParam('rows_per_page',$appdata->getProperty('rows_per_page'));
            $timezone = $appdata->getProperty('timezone',$this->GetParam('account_timezone'),'is_notempty_string');
            $this->SetParam('timezone',$timezone);
            if(strlen($timezone)) { date_default_timezone_set($timezone); }
            $this->SetParam('translation_cache_is_dirty',$appdata->getProperty('is_dirty'));
            $this->SetParam('decimal_separator',$appdata->getProperty('decimal_separator'));
            $this->SetParam('group_separator',$appdata->getProperty('group_separator'));
            $this->SetParam('date_separator',$appdata->getProperty('date_separator'));
            $this->SetParam('time_separator',$appdata->getProperty('time_separator'));
            $this->SetParam('id_user',$appdata->getProperty('id_user'));
            $this->SetParam('id_users_group',$appdata->getProperty('id_users_group'));
            $this->SetParam('restrict_access',$appdata->getProperty('restrict_access'));
            $this->SetParam('id_country',$appdata->getProperty('id_country'));
            $this->SetParam('id_company',$appdata->getProperty('id_company'));
            $this->SetParam('company_name',$appdata->getProperty('company_name'));
            $this->SetParam('user_hash',$appdata->getProperty('user_hash'));
            $this->SetParam('user_email',$appdata->getProperty('email'));
            $this->SetParam('username',$appdata->getProperty('username'));
            $this->SetParam('user_full_name',$appdata->getProperty('surname').' '.$appdata->getProperty('name'));
            $this->SetParam('user_phone',$appdata->getProperty('phone'));
            $this->SetParam('confirmed_user',$appdata->getProperty('confirmed'));
            $this->SetParam('sadmin',$appdata->getProperty('sadmin'));
            $app_theme = $appdata->getProperty('app_theme',NULL,'is_string');
            if(strlen($app_theme)) { AppConfig::app_theme($app_theme=='_default' ? NULL : $app_theme); }
            $this->SetPageParam('menu_state',$appdata->getProperty('menu_state'));
            $this->SetPageParam('id_language',$appdata->getProperty('id_language'));
            $this->SetPageParam('language_code',strtolower($appdata->getProperty('lang_code')));
            $this->url->SetParam('language',strtolower($appdata->getProperty('lang_code')));
            /*
            $this->db_global_params = array(
                'user_id'=>$this->GetParam('id_user'),
                'user_ip'=>(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '::'),
                'language_id'=>$this->GetPageParam('id_language'),
            );
            DataProvider::SetGlobalVariables($this->db_global_params);
            */
		}//if($notFromDb)
		static::$theme = $this->GetTheme();
		$this->InitializeKCFinder();
		if($this->current_namespace=='web') { $this->app_options_loaded = TRUE; return; }
		//Load user rights
		if($this->login_status && !$notFromDb) {
			$ur_ts = $this->GetParam('user_rights_revoked_ts');
			$dt_ur_ts = strlen($ur_ts) ? new \DateTime($ur_ts) : new \DateTime('1900-01-01 01:00:00');
			if($dt_ur_ts->add(new \DateInterval('PT30M'))<(new \DateTime('now'))) {
				$rightsrevoked = DataProvider::GetArray('System\Users','GetUserRightsRevoked',array('user_id'=>$this->GetParam('id_user')),array('results_keys_case'=>CASE_LOWER));
				$this->SetParam('user_rights_revoked',Module::ConvertRightsRevokedArray($rightsrevoked));
				$this->SetParam('user_rights_revoked_ts',date('Y-m-d H:i:s'));
			}//if($dt_ur_ts->add(new DateInterval('PT30M'))<(new DateTime('now')))
		} else {
			$this->SetParam('user_rights_revoked_ts',NULL);
			$this->SetParam('user_rights_revoked',NULL);
		}//if($this->login_status && !$notFromDb)
		$this->app_options_loaded = TRUE;
	}//END public function LoadAppSettings
	/**
	 * This function checks the authenticity
	 * of the login information in the database
	 * and creates the session effectively logging in the user.
	 *
	 * @param      $username
	 * @param      $password
	 * @param int  $remember
	 * @param null $login_namespace
	 * @param bool $allow_null_company
	 * @return bool Returns TRUE if login is successfull or FALSE otherwise
	 * @throws \NETopes\Core\AppException
	 * @access public
	 */
	public function Login($username,$password,$remember = 0,$login_namespace = NULL,$allow_null_company = FALSE) {
		$this->login_status = FALSE;
		$tries = $this->GetParam('login_tries');
        if(is_numeric($tries) && $tries>=0) {
            $tries += 1;
        } else {
            $tries = 1;
        }//if(is_numeric($tries) && $tries>=0)
        $this->SetParam('login_tries',$tries);
		if($tries>50) {
            $this->Redirect($this->app_web_link.'/bruteforce.php');
            return $this->login_status;
        }//if($tries>50)
        if(!is_string($password) || !strlen($password)) { return FALSE; }
        $lnamespace = (strlen($login_namespace) ? $login_namespace : (strlen($this->login_namespace) ? $this->login_namespace : $this->current_namespace));
		switch($lnamespace) {
			case 'web':
				$userdata = DataProvider::Get('Cms\Users','GetLogin',[
					'section_id'=>($this->GetParam('id_section') ? $this->GetParam('id_section') : NULL),
					'zone_id'=>($this->GetParam('id_zone') ? $this->GetParam('id_zone') : NULL),
					'for_username'=>$username,
					'allow_null_company'=>intval($allow_null_company),
					'web_session'=>$this->GetHashFromCookie('websession'),
				]);
				break;
			default:
				$userdata = DataProvider::Get('System\Users','GetLogin',['for_username'=>$username]);
		}//END switch
		if(!is_object($userdata)) { return \Translate::Get('msg_unknown_error'); }
		$login_msg = $userdata->getProperty('login_msg','','is_string');
		if(!strlen($login_msg)) { return \Translate::Get('msg_unknown_error'); }
		if($login_msg!='1') { return \Translate::Get('msg_'.$login_msg); }
		$this->login_status = password_verify($password,$userdata->getProperty('password_hash'));
		if(!$this->login_status) { return \Translate::Get('msg_invalid_password'); }
		$this->SetParam('login_tries',NULL);
		$this->user_status = $userdata->getProperty('active',0,'is_integer');
		if($this->user_status<>1 && $this->user_status<>2) { return \Translate::Get('msg_inactive_user'); }
		$this->SetParam('id_user',$userdata->getProperty('id',NULL,'is_integer'));
		$this->SetParam('confirmed_user',$userdata->getProperty('confirmed',NULL,'is_integer'));
		$this->SetParam('user_hash',$userdata->getProperty('hash',NULL,'is_string'));
		$this->SetParam('id_users_group',$userdata->getProperty('id_users_group',NULL,'is_integer'));
		$this->SetParam('id_company',$userdata->getProperty('id_company',$this->GetParam('id_company'),'is_integer'));
		$this->SetParam('company_name',$userdata->getProperty('company_name',NULL,'is_string'));
		$this->SetParam('id_country',$userdata->getProperty('id_country',$this->GetParam('id_country'),'is_integer'));
		$this->SetParam('id_element',$userdata->getProperty('id_element',$this->GetParam('id_element'),'is_integer'));
		$this->SetParam('user_email',$userdata->getProperty('email',NULL,'is_string'));
		$this->SetParam('username',$userdata->getProperty('username',NULL,'is_string'));
		$user_full_name = trim($userdata->getProperty('surname','','is_string').' '.$userdata->getProperty('name','','is_string'));
		$this->SetParam('user_full_name',$user_full_name);
		$this->SetParam('phone',$userdata->getProperty('phone',NULL,'is_string'));
		$this->SetParam('sadmin',$userdata->getProperty('sadmin',0,'is_integer'));
		$this->SetPageParam('menu_state',$userdata->getProperty('menu_state',0,'is_integer'));
		$this->SetParam('rows_per_page',$userdata->getProperty('rows_per_page',$this->GetParam('rows_per_page'),'is_integer'));
		$this->SetParam('timezone',$userdata->getProperty('timezone',$this->GetParam('timezone'),'is_notempty_string'));
		$this->SetParam('decimal_separator',$userdata->getProperty('decimal_separator',$this->GetParam('decimal_separator'),'is_notempty_string'));
		$this->SetParam('group_separator',$userdata->getProperty('group_separator',$this->GetParam('group_separator'),'is_notempty_string'));
		$this->SetParam('date_separator',$userdata->getProperty('date_separator',$this->GetParam('date_separator'),'is_notempty_string'));
		$this->SetParam('time_separator',$userdata->getProperty('time_separator',$this->GetParam('time_separator'),'is_notempty_string'));
		if($userdata->getProperty('id_language_def',0,'is_integer')>0 && strlen($userdata->getProperty('lang_code','','is_string'))) {
			$this->SetPageParam('id_language',$userdata->getProperty('id_language_def'));
			$this->SetPageParam('language_code',$userdata->getProperty('lang_code'));
			$this->url->SetParam('language',$userdata->getProperty('lang_code'));
		}//if($userdata->getProperty('id_language_def',0,'is_integer')>0 && strlen($userdata->getProperty('lang_code','','is_string')))
		if($remember && strlen($userdata->getProperty('hash','','is_string'))) {
			$this->SetLoginCookie($userdata->getProperty('hash','','is_string'));
		} else {
			$this->SetLoginCookie('',-4200);
		}//if($remember && strlen($userdata->getProperty('hash','','is_string')))
		//DataProvider::GetArray('System\Users','SetUserLoginLog',array('id_user'=>$this->GetParam('id_user'),'id_account'=>$this->GetParam('id_account')));
		return $this->login_status;
	}//END public function Login
	/**
	 * Method called on user logout action for clearing the session
	 * and the login cookie
	 *
	 * @param  string $namespace If passed, logs out the specified namespace
	 * else logs out the current namespace
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public function Logout($namespace = NULL) {
		$lnamespace = $namespace ? $namespace : $this->current_namespace;
		$this->SetLoginCookie('',-4200,NULL,$lnamespace);
		switch($lnamespace) {
			case 'web':
				$id_user = $this->GetParam('id_user');
				if(is_numeric($id_user) && $id_user>0) { DataProvider::Get('Cms\Users','SetLastRequest',array('user_id'=>$id_user)); }
				break;
			default:
				$id_user = $this->GetParam('id_user');
				if(is_numeric($id_user) && $id_user>0) { DataProvider::Get('System\Users','SetLastRequest',array('user_id'=>$id_user)); }
				break;
		}//END switch
		$this->login_status = FALSE;
		$this->NamespaceSessionCommit(TRUE,NULL,NULL,$lnamespace);
	}//END function Logout
	/**
	 * Get current namespace section relative path (with theme)
	 *
	 * @param  string $theme_dir Optional theme directory
	 * For non-web namespaces overwrites configuration theme
	 * @return string Returns the current namespace section relative path
	 * For non-web namespaces includes theme directory
	 * @access public
	 */
	public function GetSectionPath($theme_dir = NULL) {
		$relative_path = '/templates/'.$this->current_namespace.$this->current_section_folder;
		if($this->current_namespace=='web') {
			$relative_path .= (is_string($theme_dir) && strlen($theme_dir) ? '/themes/'.$theme_dir : '').'/';
		} else {
			$app_theme = AppConfig::app_theme();
			$relative_path .= '/themes/'.(is_string($theme_dir) && strlen($theme_dir) ? $theme_dir : (is_string($app_theme) && strlen($app_theme) && $app_theme!='_default' ? $app_theme : 'default')).'/';
		}//if($this->current_namespace=='web')
		return $relative_path;
	}//END public function GetSectionPath
    /**
     * @param string      $option
     * @param string      $section
     * @param null        $defValue
     * @param null|string $validation
     * @param int|null    $contextId
     * @return string|null
     * @access public
     */
    public function GetIOption(string $option,string $section = '',$defValue = NULL,?string $validation = NULL,?int $contextId = NULL): ?string {
        if(is_null($contextId)) { $contextId = $this->GetPageParam(AppConfig::context_id_field()); }
        if(!AppConfig::IsInstanceConfigLoaded()) { $this->LoadInstanceConfig(); }
        return AppConfig::GetInstanceOption($option,$section,$defValue,$validation,$contextId);
	}//END public function GetIOption
    /**
     * @param array $data
     * @param bool  $raw
     * @return array
     * @access public
     */
    public function SetInstanceConfigData(array $data,bool $raw): array {
        return AppConfig::SetInstanceConfigData($data,$raw,AppConfig::context_id_field());
	}//END protected function SetInstanceConfigData
	/**
	 * Get application (user) date format string
	 *
	 * @param bool $forPhp
	 * @return string|null
	 * @access public
	 */
	public function GetDateFormat(bool $forPhp = FALSE): ?string {
		$format = $this->GetParam('date_format');
		if(!strlen($format)) {
		    if(!strlen($this->GetParam('date_separator'))) { return NULL; }
		    $format = 'dd'.$this->GetParam('date_separator').'MM'.$this->GetParam('date_separator').'yyyy';
		}//if(!strlen($format))
		if(!$forPhp) { return $format; }
		return str_replace(['yyyy','mm','MM','dd','yy'],['Y','m','m','d','Y'],$format);
	}//END public function GetDateFormat
	/**
	 * Get application (user) time format string
	 *
	 * @param bool $forPhp
	 * @return string|null
	 * @access public
	 */
	public function GetTimeFormat(bool $forPhp = FALSE): ?string {
		$format = $this->GetParam('time_format');
		if(!strlen($format)) {
		    if(!strlen($this->GetParam('time_separator'))) { return NULL; }
		    $format = 'HH'.$this->GetParam('time_separator').'mm'.$this->GetParam('time_separator').'ss';
		}//if(!strlen($format))
		if(!$forPhp) { return $format; }
		return str_replace(['HH','hh','mm','ss'],['H','h','i','s'],$format);
	}//END public function GetTimeFormat
	/**
	 * Get application (user) datetime format string
	 *
	 * @param bool $forPhp
	 * @return string|null
	 * @access public
	 */
	public function GetDateTimeFormat(bool $forPhp = FALSE): ?string {
		return $this->GetDateFormat($forPhp).' '.$this->GetTimeFormat($forPhp);
	}//END public function GetTimeFormat
	/**
	 * Initialize debug environment
	 *
	 * @return bool
	 * @throws \Exception
	 * @access public
	 */
    public function InitDebugger() {
		if(AppConfig::debug()!==TRUE || !class_exists('\NETopes\Core\Debugger')) { return FALSE; }
		if(is_object($this->debugger)) { return $this->debugger->IsEnabled(); }
		$tmp_path = isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT']) && strpos(_NAPP_ROOT_PATH,$_SERVER['DOCUMENT_ROOT'])!==FALSE ? _NAPP_ROOT_PATH.'/../tmp' : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.'/tmp';
		$this->debugger = new Debugger(AppConfig::debug(),_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::logs_path(),$tmp_path,AppConfig::debug_console_password());
		$this->debugger->log_file = AppConfig::log_file();
		$this->debugger->errors_log_file = AppConfig::errors_log_file();
		$this->debugger->debug_log_file = AppConfig::debug_log_file();
		return $this->debugger->IsEnabled();
	}//END public function DebuggerStart
	/**
	 * Get debugger state
	 *
	 * @return bool Returns TRUE if debugger is started, FALSE otherwise
	 * @access public
	 * @static
	 */
    public static function GetDebuggerState() {
		if(!is_object(self::$_app_instance)) { return FALSE; }
		return is_object(self::$_app_instance->debugger);
    }//END public static function GetDebuggerState
	/**
	 * Displays a value in the debugger plug-in as a debug message
	 *
	 * @param  mixed   $value Value to be displayed by the debug objects
	 * @param  string  $label Label assigned to the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Dlog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,Debugger::DBG_DEBUG);
	}//END public function Dlog
	/**
	 * Displays a value in the debugger plug-in as a warning message
	 *
	 * @param  mixed   $value Value to be displayed by the debug objects
	 * @param  string  $label Label assigned to the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Wlog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,Debugger::DBG_WARNING);
	}//END public function Wlog
	/**
	 * Displays a value in the debugger plug-in as an error message
	 *
	 * @param  mixed $value Value to be displayed by the debug objects
	 * @param  string $label Label assigned to the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Elog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,Debugger::DBG_ERROR);
	}//END public function Elog
	/**
	 * Displays a value in the debugger plug-in as an info message
	 *
	 * @param  mixed $value Value to be displayed by the debug objects
	 * @param  string $label Label assigned to the value to be displayed
	 * @param  boolean $file Output file name
	 * @param  boolean $path Output file path
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function Ilog($value,$label = '',$file = FALSE,$path = FALSE) {
		if(!is_object($this->debugger)) { return; }
		if(AppConfig::console_show_file()===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::console_show_file()===TRUE || $file===TRUE)
		$this->debugger->Debug($value,$label,Debugger::DBG_INFO);
	}//END public function Ilog
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
		if(is_object($this->debugger)) { return $this->debugger->Write2LogFile($msg,$type,$file,$path); }
		$lpath = (is_string($path) && strlen($path) ? rtrim($path,'/') : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::logs_path()).'/';
		switch(strtolower($type)) {
			case 'error':
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::errors_log_file()));
			case 'debug':
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::debugging_log_file()));
			case 'log':
			default:
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::log_file()));
		}//switch(strtolower($type))
	}//END public function WriteToLog
	/**
	 * description
	 *
	 * @param  string|array $msg Text to be written to log
	 * @param  string $file Custom log file complete name (path + name) (optional)
	 * @param  string $script_name Name of the file that sent the message to log (optional)
	 * @return bool|string Returns TRUE for success or error message on failure
	 * @access public
	 * @static
	 */
	public static function Log2File($msg,$file = '',$script_name = '') {
		return Debugger::Log2File($msg,$file,$script_name);
	}//END public static function AddToLog
	/**
	 * Starts a debug timer
	 *
	 * @param  string $name Name of the timer (required)
	 * @return bool
	 * @access public
	 * @static
	 */
	public static function StartTimeTrack($name) {
		return Debugger::StartTimeTrack($name);
	}//END public static function TimerStart
	/**
	 * Displays a debug timer elapsed time
	 *
	 * @param  string $name Name of the timer (required)
	 * @param  bool $stop Flag for stopping and destroying the timer (default TRUE)
	 * @return double
	 * @access public
	 * @static
	 */
	public static function ShowTimeTrack($name,$stop = TRUE) {
		return Debugger::ShowTimeTrack($name,$stop);
	}//END public static function TimerStart
	/**
	 * Get theme object
	 *
	 * @param string $theme
	 * @return ITheme|null
	 * @access public
	 */
	public abstract function GetTheme(string $theme = ''): ?ITheme;
	/**
	 * Load instance specific configuration options (into protected $instanceConfig property)
	 *
	 * @return void
	 * @access public
	 */
	protected abstract function LoadInstanceConfig(): void;
	/**
	 * Get current user ID
	 *
	 * @return int|null Returns current user ID
	 * @access public
	 */
	public abstract function GetCurrentUserId(): ?int;
}//END class App implements IApp