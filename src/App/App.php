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
use NETopes\Core\AppHelpers;
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
	 * @var    \NETopes\Core\App\App Singleton unique instance
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
     * @var    string Account API security key (auto-loaded on LoadAppOptions() method)
     * @access protected
     */
	protected $app_access_key = NULL;
	/**
	 * @var    string Application absolute path (auto-set on constructor)
	 * @access public
	 */
	public static $app_absolute_path = NULL;
	/**
	 * @var    string Application non-public path (auto-set on constructor)
	 * @access public
	 */
	public static $app_path = NULL;
	/**
	 * @var    string Application public path (auto-set on constructor)
	 * @access public
	 */
	public static $app_public_path = NULL;
	/**
	 * @var    string Application base link (auto-set on constructor)
	 * @access public
	 */
	public static $app_web_link = NULL;
	/**
	 * @var    \NETopes\Core\App\ITheme Current theme object instance
	 * @access public
	 * @static
	 */
	public static $theme = NULL;
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
	 * @var    bool If set TRUE, name-space session will be cleared at commit
	 * @access public
	 */
	public $clear_namespace_session = FALSE;
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
		self::$app_absolute_path = _NAPP_ROOT_PATH;
		self::$app_path = _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH;
		self::$app_public_path = _NAPP_ROOT_PATH._NAPP_PUBLIC_ROOT_PATH._NAPP_PUBLIC_PATH;
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
			self::$app_web_link = $this->url->GetWebLink();
		} else {
			$this->_app_state = AppSession::WithSession() ? AppSession::GetState() : TRUE;
			$app_web_protocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
			$app_domain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
			$url_folder = Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
			$this->url = new Url($app_domain,$app_web_protocol,$url_folder);
			self::$app_web_link = $this->url->GetWebLink();
			if(AppConfig::GetValue('split_session_by_page')) {
				$this->phash = get_array_value($_GET,'phash',get_array_value($_POST,'phash',NULL,'is_notempty_string'),'is_notempty_string');
				if(!$this->phash) {
					$this->phash = is_array($_COOKIE) && array_key_exists('__napp_pHash_',$_COOKIE) && strlen($_COOKIE['__napp_pHash_']) && strlen($_COOKIE['__napp_pHash_'])>15 ? substr($_COOKIE['__napp_pHash_'],0,-15) : NULL;
				}//if(!$this->phash)
				if(!$this->phash ) { $this->phash = AppSession::GetNewUID(); }
			}//if(AppConfig::GetValue('split_session_by_page'))
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
		if(!$shell) {
		    $this->_InitDebugger();
		    $this->_StartOutputBuffer();
		}//if(!$shell)
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
		if(strlen($default_views_dir)) { AppConfig::SetValue('app_default_views_dir',$default_views_dir); }
		$views_extension = get_array_value($this->globals['domain_config'],'views_extension','','is_string');
		if(strlen($views_extension)) { AppConfig::SetValue('app_views_extension',$views_extension); }
		$app_theme = get_array_value($this->globals['domain_config'],'app_theme',NULL,'is_string');
		if(isset($app_theme)) { AppConfig::SetValue('app_theme',$app_theme); }
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
		$this->url->data = $ajax ? (is_array(self::GetPageParam('get_params')) ? self::GetPageParam('get_params') : []) : $this->url->data;
		self::SetPageParam('get_params',$this->url->data);
		$this->url->special_params = array('language','urlid','namespace');
		if($ajax!==TRUE) {
			$curl = $this->url->GetCurrentUrl();
			if(self::GetPageParam('current_url')!=$curl) { self::SetPageParam('old_url',self::GetPageParam('current_url')); }
			self::SetPageParam('current_url',$curl);
		}//if($ajax!==TRUE)
		if(AppSession::WithSession() && array_key_exists('robot',$_SESSION) && $_SESSION['robot']==1) { AppConfig::SetValue('debug',FALSE); }
		self::$debug = AppConfig::GetValue('debug');
	}//END protected function __construct
	/**
     * Check if AJAX request object is valid
     *
     * @return bool
     */
    public function _IsValidAjaxRequest(): bool {
	    return (is_object($this->arequest) && is_subclass_of($this->arequest,BaseRequest::class));
	}//END public function _IsValidAjaxRequest
	/**
     * @return bool
     * @throws \NETopes\Core\AppException
     */
	public function _StartOutputBuffer() {
		if(!$this->ajax && !AppConfig::GetValue('buffered_output') && !$this->debugger) { return FALSE; }
		ob_start();
		return (self::$app_ob_started = TRUE);
	}//END public function _StartOutputBuffer
	/**
	 * @param bool $end
	 * @return bool
	 */
	public function _FlushOutputBuffer($end = FALSE) {
		if(!self::$app_ob_started) { return FALSE; }
		if(is_object($this->debugger)) { $this->debugger->SendData(); }
		if($end===TRUE) {
			ob_end_flush();
			self::$app_ob_started = FALSE;
		} else {
			ob_flush();
		}//if($end===TRUE)
		return TRUE;
	}//END public function _FlushOutputBuffer
	/**
	 * @param bool $clear
	 * @return bool|string
	 */
	public function _GetOutputBufferContent($clear = TRUE) {
		if(!self::$app_ob_started) { return FALSE; }
		if($clear===TRUE) {
			$content = ob_get_clean();
		} else {
			$content = ob_get_contents();
		}//if($clear===TRUE)
		return $content;
	}//END public function _GetOutputBufferContent
	/**
	 * @param bool $end
	 * @return bool
	 */
	public function _ClearOutputBuffer($end = FALSE) {
		if(!self::$app_ob_started) { return FALSE; }
		if($end===TRUE) {
			ob_end_clean();
			self::$app_ob_started = FALSE;
		} else {
			ob_clean();
		}//if($end===TRUE)
		return TRUE;
	}//END public function _ClearOutputBuffer
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
	public function _SessionCommit($clear = FALSE,$preserve_output_buffer = FALSE,$show_errors = TRUE,$key = NULL,$phash = NULL,$reload = TRUE) {
		if(!AppSession::WithSession()) {
			if($show_errors && method_exists('\ErrorHandler','ShowErrors')) { \ErrorHandler::ShowErrors(); }
			if($preserve_output_buffer!==TRUE) { $this->_FlushOutputBuffer(); }
			return;
		}//if(!AppSession::WithSession())
		AppSession::SessionCommit($clear,$show_errors,$key,$phash,$reload);
		if($preserve_output_buffer!==TRUE) { $this->_FlushOutputBuffer(); }
	}//END public function _SessionCommit
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
	public function _NamespaceSessionCommit($clear = NULL,$preserve_output_buffer = FALSE,$show_errors = TRUE,$namespace = NULL,$phash = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : $this->current_namespace;
		$lclear = isset($clear) ? $clear : $this->clear_namespace_session;
		$this->_SessionCommit($lclear,$preserve_output_buffer,$show_errors,$lnamespace,$phash);
	}//END public function _NamespaceSessionCommit
	/**
     * Initialize NETopes application javascript
     *
     * @param bool $output
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
	public function _JsInit(bool $output = TRUE): ?string {
	    $js = $this->_GetJsConstants();
	    $js .= $this->_GetJsScripts();
		if($output) { echo $js; return NULL; }
		return $js;
	}//END public function _JsInit
    /**
     * Get NETopes application javascript constants
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
	public function _GetJsConstants(): string {
	    $jsRootUrl = self::$app_web_link.AppConfig::GetValue('app_js_path');
	    $jsThemeBaseUrl = self::$app_web_link.$this->GetSectionPath();
	    $appWebLink = self::$app_web_link;
	    $js = <<<HTML
        <script type="text/javascript">
            const xAppWebLink = '{$appWebLink}';
            const xAppThemeLink = '{$jsThemeBaseUrl}';
            const NAPP_PHASH = '{$this->phash}';
            const NAPP_JS_PATH = '{$jsRootUrl}';
        </script>
HTML;
		return $js;
	}//END public function _GetJsConstants
    /**
     * Get NETopes application javascript
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
	public function _GetJsScripts(): string {
	    $jsRootUrl = self::$app_web_link.AppConfig::GetValue('app_js_path');
	    $js = <<<HTML
        <script type="text/javascript" src="{$jsRootUrl}/gibberish-aes.min.js?v=1901081"></script>
        <script type="text/javascript" src="{$jsRootUrl}/main.min.js?v=1901081"></script>
HTML;
        if($this->_IsValidAjaxRequest()) { $js .= $this->arequest->GetJsScripts($jsRootUrl); }
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
		return $js;
	}//END public function _GetJsScripts
	/**
     * Get current namespace section relative path (with theme)
     *
     * @param  string $theme_dir Optional theme directory
     * For non-web namespaces overwrites configuration theme
     * @return string Returns the current namespace section relative path
     * For non-web namespaces includes theme directory
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function _GetSectionPath($theme_dir = NULL) {
		$relative_path = '/templates/'.$this->current_namespace.$this->current_section_folder;
		if($this->current_namespace=='web') {
			$relative_path .= (is_string($theme_dir) && strlen($theme_dir) ? '/themes/'.$theme_dir : '').'/';
		} else {
			$app_theme = AppConfig::GetValue('app_theme');
			$relative_path .= '/themes/'.(is_string($theme_dir) && strlen($theme_dir) ? $theme_dir : (is_string($app_theme) && strlen($app_theme) && $app_theme!='_default' ? $app_theme : 'default')).'/';
		}//if($this->current_namespace=='web')
		return $relative_path;
	}//END public function _GetSectionPath
	/**
	 * Initialize debug environment
	 *
	 * @return bool
	 * @throws \Exception
	 * @access public
	 */
    public function _InitDebugger() {
		if(AppConfig::GetValue('debug')!==TRUE || !class_exists('\NETopes\Core\App\Debugger')) { return FALSE; }
		if(is_object($this->debugger)) { return $this->debugger->IsEnabled(); }
		$tmp_path = isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT']) && strpos(_NAPP_ROOT_PATH,$_SERVER['DOCUMENT_ROOT'])!==FALSE ? _NAPP_ROOT_PATH.'/../tmp' : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.'/tmp';
		$this->debugger = new Debugger(AppConfig::GetValue('debug'),_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::GetValue('logs_path'),$tmp_path,AppConfig::GetValue('debug_console_password'));
		$this->debugger->log_file = AppConfig::GetValue('log_file');
		$this->debugger->errors_log_file = AppConfig::GetValue('errors_log_file');
		$this->debugger->debug_log_file = AppConfig::GetValue('debug_log_file');
		return $this->debugger->IsEnabled();
	}//END public function _InitDebugger
    /**
     * Gets the login timeout in minutes
     *
     * @return int Returns login timeout
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function _GetLoginTimeout() {
		$cookie_hash = $this->_GetCookieHash();
		if(array_key_exists($cookie_hash,$_COOKIE) && strlen($_COOKIE[$cookie_hash]) && $_COOKIE[$cookie_hash]==self::GetParam('user_hash')) { return intval(AppConfig::GetValue('cookie_login_lifetime') / 24 / 60); }
		return intval(AppConfig::GetValue('session_timeout') / 60);
	}//END public function _GetLoginTimeout
    /**
     * Initializes KCFinder session parameters
     *
     * @param array $params
     * @return void
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function _InitializeKCFinder($params = NULL) {
		if(!AppSession::WithSession() || !AppConfig::GetValue('use_kc_finder')) { return; }
		$type = get_array_value($params,'type','','is_string');
		switch(strtolower($type)) {
			case 'public':
				AppSession::SetGlobalParam('disabled',FALSE,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadURL',self::$app_web_link.'/repository/public','__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadDir',self::$app_public_path.'/repository/public','__KCFINDER',NULL,FALSE);
				break;
			case 'app':
				AppSession::SetGlobalParam('disabled',($this->login_status && self::GetParam('user_hash')) ? FALSE : TRUE,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadURL',self::$app_web_link.'/repository/app','__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadDir',self::$app_public_path.'/repository/app','__KCFINDER',NULL,FALSE);
				break;
			case 'cms':
			default:
				$section_folder = get_array_value($params,'section_folder',self::GetParam('section_folder'),'is_string');
				$zone_code = get_array_value($params,'zone_code',self::GetParam('zone_code'),'is_string');
				// TODO: fix multi instance
				AppSession::SetGlobalParam('disabled',($this->login_status && self::GetParam('user_hash')) ? FALSE : TRUE,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadURL',self::$app_web_link.'/repository/'.$section_folder.'/'.$zone_code,'__KCFINDER',NULL,FALSE);
				AppSession::SetGlobalParam('uploadDir',self::$app_public_path.'/repository/'.$section_folder.'/'.$zone_code,'__KCFINDER',NULL,FALSE);
				break;
		}//END switch
		$this->_SessionCommit(FALSE,TRUE,TRUE,NULL,'__KCFINDER',FALSE);
	}//END public function _InitializeKCFinder
    /**
     * Gets the login cookie hash
     *
     * @param  string $namespace The namespace for the cookie or NULL for current namespace
     * @param null    $salt
     * @return string The name (hash) of the login cookie
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function _GetCookieHash($namespace = NULL,$salt = NULL) {
		$lnamespace = $namespace ? $namespace : $this->current_namespace;
		$lsalt = strlen($salt) ? $salt : 'loggedin';
		return AppSession::GetNewUID(AppConfig::GetValue('app_encryption_key').$this->url->GetAppDomain().$this->url->GetUrlFolder().$lnamespace.$lsalt,'sha256',TRUE);
	}//END public function _GetCookieHash
    /**
     * description
     *
     * @param      $name
     * @param null $namespace
     * @param bool $set_if_missing
     * @param null $validity
     * @return string|null
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function _GetHashFromCookie($name,$namespace = NULL,$set_if_missing = TRUE,$validity = NULL) {
		$c_hash = $this->GetCookieHash($namespace,$name);
		$c_cookie_hash = NULL;
		if(array_key_exists($c_hash,$_COOKIE) && strlen($_COOKIE[$c_hash])) {
			$c_cookie_hash = \GibberishAES::dec($_COOKIE[$c_hash],AppConfig::GetValue('app_encryption_key'));
		} elseif($set_if_missing===TRUE || $set_if_missing===1 || $set_if_missing==='1') {
			$c_cookie_hash = AppSession::GetNewUID();
			$lvalability = (is_numeric($validity) && $validity>0 ? $validity : 180)*24*3600;
			$_COOKIE[$c_hash] = \GibberishAES::enc($c_cookie_hash,AppConfig::GetValue('app_encryption_key'));
			setcookie($c_hash,$_COOKIE[$c_hash],time()+$lvalability,'/',$this->url->GetAppDomain());
		}//if(array_key_exists($sc_hash,$_COOKIE) && strlen($_COOKIE[$sc_hash]))
		return $c_cookie_hash;
	}//END public function _GetHashFromCookie
    /**
     * Set the login cookie
     *
     * @param  string  $uhash The user hash
     * @param  integer $validity The cookie lifetime or NULL for default
     * @param  string  $cookie_hash The name (hash) of the login cookie
     * @param  string  $namespace The namespace for the cookie or NULL for current namespace
     * @return bool True on success or false
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public function _SetLoginCookie($uhash,$validity = NULL,$cookie_hash = NULL,$namespace = NULL) {
		if(!is_string($uhash)) { return FALSE; }
		$lvalidity = is_numeric($validity) && $validity>0 ? $validity : AppConfig::GetValue('cookie_login_lifetime')*24*3600;
		$lcookie_hash = $cookie_hash ? $cookie_hash : $this->_GetCookieHash($namespace);
		if(!$uhash) {
			unset($_COOKIE[$cookie_hash]);
			setcookie($lcookie_hash,'',time()+$lvalidity,'/',$this->url->GetAppDomain());
			return TRUE;
		}//if(!$uhash)
		$_COOKIE[$cookie_hash] = \GibberishAES::enc($uhash,AppConfig::GetValue('app_encryption_key'));
		setcookie($lcookie_hash,$_COOKIE[$cookie_hash],time()+$lvalidity,'/',$this->url->GetAppDomain());
		return TRUE;
	}//END public function _SetLoginCookie

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
		$cookie_hash = $this->_GetCookieHash();
		$auto_login = 1;
		$user_hash = $this->url->GetParam('uhash');
		if(!strlen($user_hash) && array_key_exists($cookie_hash,$_COOKIE) && strlen($_COOKIE[$cookie_hash])) {
			$user_hash = \GibberishAES::dec($_COOKIE[$cookie_hash],AppConfig::GetValue('app_encryption_key'));
		}//if(!strlen($user_hash) && array_key_exists($cookie_hash,$_COOKIE) && strlen($_COOKIE[$cookie_hash]))
		if(!strlen($user_hash)) {
			$auto_login = 0;
			$user_hash = self::GetParam('user_hash');
		}//if(!strlen($user_hash))
		$idsection = $this->url->GetParam('section');
		$idzone = $this->url->GetParam('zone');
		$langCode = $this->url->GetParam('language');
		if($this->ajax || !is_string($langCode) || !strlen($langCode)) { $langCode = self::GetLanguageCode(); }
		if($notFromDb) {
		    $this->user_status = -1;
            $this->login_status = FALSE;
            self::SetParam('login_status',$this->login_status);
            self::SetParam('id_section',$idsection);
            self::SetParam('id_zone',$idzone);
            self::SetParam('user_hash',$user_hash);
            $this->current_section_folder = '';
            self::SetParam('account_timezone',AppConfig::GetValue('server_timezone'));
            self::SetParam('timezone',AppConfig::GetValue('server_timezone'));
            if(strlen(AppConfig::GetValue('server_timezone'))) { date_default_timezone_set(AppConfig::GetValue('server_timezone')); }
            self::SetParam('website_name',AppConfig::GetValue('website_name'));
            self::SetParam('rows_per_page',20);
            self::SetParam('decimal_separator','.');
            self::SetParam('group_separator',',');
            self::SetParam('date_separator','.');
            self::SetParam('time_separator',':');
            self::SetPageParam('language_code',strtolower($langCode));
            $this->url->SetParam('language',$langCode);
        } else {
            $appdata = DataProvider::Get('System\System','GetAppSettings',[
                'for_domain'=>$this->url->GetAppDomain(),
                'for_namespace'=>$this->current_namespace,
                'for_lang_code'=>$langCode,
                'for_user_hash'=>$user_hash,
                'login_namespace'=>(strlen($this->login_namespace) ? $this->login_namespace : NULL),
                'section_id'=>((is_numeric($idsection) && $idsection>0) ? $idsection : NULL),
                'zone_id'=>((is_numeric($idzone) && $idzone>0) ? $idzone : NULL),
                'validity'=>$this->_GetLoginTimeout(),
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
                $this->_SetLoginCookie($appdata->getProperty('user_hash'),NULL,$cookie_hash);
            }//if($this->login_status && isset($_COOKIE[$cookie_hash]) && strlen($appdata->getProperty('user_hash')))
            self::SetParam('login_status',$this->login_status);
            self::SetParam('id_registry',$appdata->getProperty('id_registry'));
            self::SetParam('id_section',$appdata->getProperty('id_section'));
            self::SetParam('section_folder',$appdata->getProperty('section_folder'));
            $this->current_section_folder = '';
            $c_section_dir = $appdata->getProperty('section_folder','','is_string');
            if($this->with_sections && strlen($c_section_dir)) { $this->current_section_folder = '/'.$c_section_dir; }
            self::SetParam('id_zone',$appdata->getProperty('id_zone'));
            self::SetParam('zone_code',$appdata->getProperty('zone_code'));
            self::SetParam('id_account',$appdata->getProperty('id_account'));
            self::SetPageParam('id_account',$appdata->getProperty('id_account'));
            self::SetParam('account_type',$appdata->getProperty('account_type'));
            self::SetParam('account_name',$appdata->getProperty('account_name'));
            self::SetParam('access_key',$appdata->getProperty('access_key'));
            $this->app_access_key = $appdata->getProperty('access_key');
            self::SetParam('account_timezone',$appdata->getProperty('account_timezone',AppConfig::GetValue('server_timezone'),'is_notempty_string'));
            self::SetParam('id_entity',$appdata->getProperty('id_entity'));
            self::SetParam('id_location',$appdata->getProperty('id_location'));
            self::SetParam('website_name',$appdata->getProperty('website_name'));
            self::SetParam('rows_per_page',$appdata->getProperty('rows_per_page'));
            $timezone = $appdata->getProperty('timezone',self::GetParam('account_timezone'),'is_notempty_string');
            self::SetParam('timezone',$timezone);
            if(strlen($timezone)) { date_default_timezone_set($timezone); }
            self::SetParam('translation_cache_is_dirty',$appdata->getProperty('is_dirty'));
            self::SetParam('decimal_separator',$appdata->getProperty('decimal_separator'));
            self::SetParam('group_separator',$appdata->getProperty('group_separator'));
            self::SetParam('date_separator',$appdata->getProperty('date_separator'));
            self::SetParam('time_separator',$appdata->getProperty('time_separator'));
            self::SetParam('id_user',$appdata->getProperty('id_user'));
            self::SetParam('id_users_group',$appdata->getProperty('id_users_group'));
            self::SetParam('restrict_access',$appdata->getProperty('restrict_access'));
            self::SetParam('id_country',$appdata->getProperty('id_country'));
            self::SetParam('id_company',$appdata->getProperty('id_company'));
            self::SetParam('company_name',$appdata->getProperty('company_name'));
            self::SetParam('user_hash',$appdata->getProperty('user_hash'));
            self::SetParam('user_email',$appdata->getProperty('email'));
            self::SetParam('username',$appdata->getProperty('username'));
            self::SetParam('user_full_name',$appdata->getProperty('surname').' '.$appdata->getProperty('name'));
            self::SetParam('user_phone',$appdata->getProperty('phone'));
            self::SetParam('confirmed_user',$appdata->getProperty('confirmed'));
            self::SetParam('sadmin',$appdata->getProperty('sadmin'));
            $app_theme = $appdata->getProperty('app_theme',NULL,'is_string');
            if(strlen($app_theme)) { AppConfig::SetValue('app_theme',$app_theme=='_default' ? NULL : $app_theme); }
            self::SetPageParam('menu_state',$appdata->getProperty('menu_state'));
            self::SetPageParam('id_language',$appdata->getProperty('id_language'));
            self::SetPageParam('language_code',strtolower($appdata->getProperty('lang_code')));
            $this->url->SetParam('language',strtolower($appdata->getProperty('lang_code')));
            /*
            $this->db_global_params = array(
                'user_id'=>self::GetParam('id_user'),
                'user_ip'=>(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '::'),
                'language_id'=>self::GetPageParam('id_language'),
            );
            DataProvider::SetGlobalVariables($this->db_global_params);
            */
		}//if($notFromDb)
		static::$theme = $this->GetTheme();
		$this->_InitializeKCFinder();
		if($this->current_namespace=='web') { $this->app_options_loaded = TRUE; return; }
		//Load user rights
		if($this->login_status && !$notFromDb) {
			$ur_ts = self::GetParam('user_rights_revoked_ts');
			$dt_ur_ts = strlen($ur_ts) ? new \DateTime($ur_ts) : new \DateTime('1900-01-01 01:00:00');
			if($dt_ur_ts->add(new \DateInterval('PT30M'))<(new \DateTime('now'))) {
				$rightsrevoked = DataProvider::GetArray('System\Users','GetUserRightsRevoked',array('user_id'=>self::GetParam('id_user')),array('results_keys_case'=>CASE_LOWER));
				self::SetParam('user_rights_revoked',Module::ConvertRightsRevokedArray($rightsrevoked));
				self::SetParam('user_rights_revoked_ts',date('Y-m-d H:i:s'));
			}//if($dt_ur_ts->add(new DateInterval('PT30M'))<(new DateTime('now')))
		} else {
			self::SetParam('user_rights_revoked_ts',NULL);
			self::SetParam('user_rights_revoked',NULL);
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
		$tries = self::GetParam('login_tries');
        if(is_numeric($tries) && $tries>=0) {
            $tries += 1;
        } else {
            $tries = 1;
        }//if(is_numeric($tries) && $tries>=0)
        self::SetParam('login_tries',$tries);
		if($tries>50) {
            $this->Redirect(self::$app_web_link.'/bruteforce.php');
            return $this->login_status;
        }//if($tries>50)
        if(!is_string($password) || !strlen($password)) { return FALSE; }
        $lnamespace = (strlen($login_namespace) ? $login_namespace : (strlen($this->login_namespace) ? $this->login_namespace : $this->current_namespace));
		switch($lnamespace) {
			case 'web':
				$userdata = DataProvider::Get('Cms\Users','GetLogin',[
					'section_id'=>(self::GetParam('id_section') ? self::GetParam('id_section') : NULL),
					'zone_id'=>(self::GetParam('id_zone') ? self::GetParam('id_zone') : NULL),
					'for_username'=>$username,
					'allow_null_company'=>intval($allow_null_company),
					'web_session'=>$this->_GetHashFromCookie('websession'),
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
		self::SetParam('login_tries',NULL);
		$this->user_status = $userdata->getProperty('active',0,'is_integer');
		if($this->user_status<>1 && $this->user_status<>2) { return \Translate::Get('msg_inactive_user'); }
		self::SetParam('id_user',$userdata->getProperty('id',NULL,'is_integer'));
		self::SetParam('confirmed_user',$userdata->getProperty('confirmed',NULL,'is_integer'));
		self::SetParam('user_hash',$userdata->getProperty('hash',NULL,'is_string'));
		self::SetParam('id_users_group',$userdata->getProperty('id_users_group',NULL,'is_integer'));
		self::SetParam('id_company',$userdata->getProperty('id_company',self::GetParam('id_company'),'is_integer'));
		self::SetParam('company_name',$userdata->getProperty('company_name',NULL,'is_string'));
		self::SetParam('id_country',$userdata->getProperty('id_country',self::GetParam('id_country'),'is_integer'));
		self::SetParam('id_element',$userdata->getProperty('id_element',self::GetParam('id_element'),'is_integer'));
		self::SetParam('user_email',$userdata->getProperty('email',NULL,'is_string'));
		self::SetParam('username',$userdata->getProperty('username',NULL,'is_string'));
		$user_full_name = trim($userdata->getProperty('surname','','is_string').' '.$userdata->getProperty('name','','is_string'));
		self::SetParam('user_full_name',$user_full_name);
		self::SetParam('phone',$userdata->getProperty('phone',NULL,'is_string'));
		self::SetParam('sadmin',$userdata->getProperty('sadmin',0,'is_integer'));
		self::SetPageParam('menu_state',$userdata->getProperty('menu_state',0,'is_integer'));
		self::SetParam('rows_per_page',$userdata->getProperty('rows_per_page',self::GetParam('rows_per_page'),'is_integer'));
		self::SetParam('timezone',$userdata->getProperty('timezone',self::GetParam('timezone'),'is_notempty_string'));
		self::SetParam('decimal_separator',$userdata->getProperty('decimal_separator',self::GetParam('decimal_separator'),'is_notempty_string'));
		self::SetParam('group_separator',$userdata->getProperty('group_separator',self::GetParam('group_separator'),'is_notempty_string'));
		self::SetParam('date_separator',$userdata->getProperty('date_separator',self::GetParam('date_separator'),'is_notempty_string'));
		self::SetParam('time_separator',$userdata->getProperty('time_separator',self::GetParam('time_separator'),'is_notempty_string'));
		if($userdata->getProperty('id_language_def',0,'is_integer')>0 && strlen($userdata->getProperty('lang_code','','is_string'))) {
			self::SetPageParam('id_language',$userdata->getProperty('id_language_def'));
			self::SetPageParam('language_code',$userdata->getProperty('lang_code'));
			$this->url->SetParam('language',$userdata->getProperty('lang_code'));
		}//if($userdata->getProperty('id_language_def',0,'is_integer')>0 && strlen($userdata->getProperty('lang_code','','is_string')))
		if($remember && strlen($userdata->getProperty('hash','','is_string'))) {
			$this->_SetLoginCookie($userdata->getProperty('hash','','is_string'));
		} else {
			$this->_SetLoginCookie('',-4200);
		}//if($remember && strlen($userdata->getProperty('hash','','is_string')))
		//DataProvider::GetArray('System\Users','SetUserLoginLog',array('id_user'=>self::GetParam('id_user'),'id_account'=>self::GetParam('id_account')));
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
		$this->_SetLoginCookie('',-4200,NULL,$lnamespace);
		switch($lnamespace) {
			case 'web':
				$id_user = self::GetParam('id_user');
				if(is_numeric($id_user) && $id_user>0) { DataProvider::Get('Cms\Users','SetLastRequest',array('user_id'=>$id_user)); }
				break;
			default:
				$id_user = self::GetParam('id_user');
				if(is_numeric($id_user) && $id_user>0) { DataProvider::Get('System\Users','SetLastRequest',array('user_id'=>$id_user)); }
				break;
		}//END switch
		$this->login_status = FALSE;
		$this->_NamespaceSessionCommit(TRUE,NULL,NULL,$lnamespace);
	}//END function Logout
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
     * @throws \NETopes\Core\AppException
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
	 * @return object Returns the value of $_app_instance property
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
     * Get AJAX request object
     *
     * @return object
     */
    public static function Ajax() {
	    return self::$_app_instance->arequest;
	}//END public static function Ajax
	/**
     * Check if AJAX request object is valid
     *
     * @return bool
     */
    public static function IsValidAjaxRequest(): bool {
	    return self::$_app_instance->_IsValidAjaxRequest();
	}//END public static function _IsValidAjaxRequest
    /**
     * Initialize AJAX Request object
     *
     * @param  array  $postParams Default parameters to be send via post on ajax requests
     * @param  string $subSession Sub-session key/path
     * @return bool
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function AjaxRequestInit(array $postParams = [],$subSession = NULL): bool {
	    if(!AppConfig::GetValue('app_use_ajax_extension')) { return FALSE; }
	    if(!self::IsValidAjaxRequest()) {
	        $ajaxRequestClass = AppConfig::GetValue('ajax_class_name');
	        if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) { throw new AppException('Invalid AJAX Request class: ['.$ajaxRequestClass.']!'); }
			self::$_app_instance->arequest = new $ajaxRequestClass(self::$_app_instance,$subSession,$postParams);
	    }//if(!self::IsValidAjaxRequest())
        return TRUE;
	}//END public static function AjaxRequestInit
    /**
     * Execute AJAX request class method
     *
     * @param array $postParams
     * @param null  $subSession
     * @return void
     * @throws \NETopes\Core\AppException
     * @static
     */
    public static function ExecuteAjaxRequest(array $postParams = [],$subSession = NULL): void {
	    if(!AppConfig::GetValue('app_use_ajax_extension')) { die('Invalid AJAX request!'); }
	    $ajaxRequestClass = AppConfig::GetValue('ajax_class_name');
	    if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) { die('Invalid AJAX Request class!'); }
	    /** @var \NETopes\Ajax\BaseRequest $ajaxRequestClass */
        $ajaxRequestClass::PrepareAndExecuteRequest(self::$_app_instance,$postParams,$subSession);
	}//END public static function ExecuteAjaxRequest
	/**
	 * @return bool
	 */
	public static function OutputBufferStarted() {
		return self::$app_ob_started;
	}//END public function OutputBufferStarted
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
	public static function Redirect($url = NULL,$dont_commit_session = FALSE,$exit = TRUE) {
		$lurl = strlen($url) ? $url : self::GetAppWebLink();
		if($dont_commit_session!==TRUE) { self::$_app_instance->_NamespaceSessionCommit(FALSE,TRUE); }
		if(self::$_app_instance->ajax) {
			// TODO: check if is working every time
			if(self::IsValidAjaxRequest()) {
				self::$_app_instance->arequest->ExecuteJs("window.location.href = '{$lurl}';");
			}//if($this->IsValidAjaxRequest())
		} else {
			header('Location:'.$lurl);
			if($exit) { self::$_app_instance->_FlushOutputBuffer(); exit(); }
		}//if(self::$_app_instance->ajax)
	}//END public static function Redirect
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
	public static function FullRedirect($url = NULL,$dont_commit_session = FALSE,$exit = TRUE) {
		$lurl = strlen($url) ? $url : self::GetAppWebLink();
		if($dont_commit_session!==TRUE) { self::$_app_instance->_NamespaceSessionCommit(FALSE,TRUE); }
		header('Location:'.$lurl);
		if($exit) { self::$_app_instance->_FlushOutputBuffer(); exit(); }
	}//END public static function FullRedirect
    /**
     * Gets a parameter from the temporary session
     *
     * @param  string     $key The name of the parameter
     * @param bool        $phash The page hash (default FALSE, global context)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @access public
     * @throws \NETopes\Core\AppException
     */
    public static function GetParam(string $key,$phash = FALSE,?string $namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : self::$_app_instance->current_namespace;
		$lphash = isset($phash) ? ($phash===FALSE ? NULL : $phash) : self::$_app_instance->phash;
		return AppSession::GetParam($key,$lphash,$lnamespace);
	}//END public static function GetParam
    /**
     * Gets a parameter from the temporary session
     *
     * @param  string     $key The name of the parameter
     * @param  string     $phash The page hash (default NULL)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetPageParam(string $key,$phash = NULL,?string $namespace = NULL) {
		return self::GetParam($key,$phash,$namespace);
	}//END public static function GetPageParam
	/**
	 * Sets a parameter to the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  mixed  $val The value of the parameter
	 * @param bool    $phash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param string|null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function SetParam(string $key,$val,$phash = FALSE,?string $namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : self::$_app_instance->current_namespace;
		$lphash = isset($phash) ? ($phash===FALSE ? NULL : $phash) : self::$_app_instance->phash;
		AppSession::SetParam($key,$val,$lphash,$lnamespace);
	}//END public static function SetParam
	/**
	 * Sets a parameter to the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  mixed  $val The value of the parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param string|null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function SetPageParam(string $key,$val,$phash = NULL,?string $namespace = NULL) {
		self::SetParam($key,$val,$phash,$namespace);
	}//END public static function SetPageParam
	/**
	 * Delete a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param bool    $phash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function UnsetParam($key,$phash = FALSE,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : self::$_app_instance->current_namespace;
		$lphash = isset($phash) ? ($phash===FALSE ? NULL : $phash) : self::$_app_instance->phash;
		AppSession::UnsetParam($key,$lphash,$lnamespace);
	}//END public static function UnsetParam
	/**
	 * Delete a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  string $phash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function UnsetPageParam($key,$phash = NULL,$namespace = NULL) {
		self::UnsetParam($key,$phash,$namespace);
	}//END public static function UnsetPageParam
	/**
	 * description
	 *
	 * @param      $uid
	 * @param null $namespace
	 * @return mixed
	 * @access public
	 */
	public static function GetSessionAcceptedRequest($uid,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : self::$_app_instance->current_namespace;
		$data = AppSession::GetData();
		if(!isset($data[$lnamespace]['xURLRequests'][$uid])) { return NULL; }
		return $data[$lnamespace]['xURLRequests'][$uid];
	}//END public static function GetSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $uid
	 * @param null $namespace
	 * @return string
	 * @access public
	 */
	public static function SetSessionAcceptedRequest($uid,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : self::$_app_instance->current_namespace;
		if($uid===TRUE) { $uid = AppSession::GetNewUID(NULL,'md5'); }
		$data = AppSession::GetData();
		$data[$lnamespace]['xURLRequests'][$uid] = TRUE;
		AppSession::SetData($data);
		return $uid;
	}//END public static function SetSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $uid
	 * @param null $namespace
	 * @return void
	 * @access public
	 */
	public static function UnsetSessionAcceptedRequest($uid,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : self::$_app_instance->current_namespace;
		$data = AppSession::GetData();
		unset($data[$lnamespace]['xURLRequests'][$uid]);
		AppSession::SetData($data);
	}//END public static function UnsetSessionAcceptedRequest
	/**
	 * description
	 *
	 * @param      $uid
	 * @param bool $reset
	 * @param null $namespace
	 * @return bool
	 * @access public
	 */
	public static function CheckSessionAcceptedRequest($uid,$reset = FALSE,$namespace = NULL) {
		$result = self::GetSessionAcceptedRequest($uid,$namespace);
		if($reset===TRUE) { self::UnsetSessionAcceptedRequest($uid,$namespace); }
		return ($result===TRUE);
	}//END public static function CheckSessionAcceptedRequest
	/**
	 * Add javascript code to the dynamic js queue (executed at the end of the current request)
	 *
	 * @param  string $value Javascript code
	 * @param bool    $dynamic
	 * @return void
	 * @access public
	 */
	public static function ExecJs(string $value,bool $dynamic = FALSE) {
		if(!strlen($value)) { return; }
		AppHelpers::AddJsScript($value,$dynamic);
	}//END public static function _ExecJs
    /**
     * Get current language ID
     *
     * @return int|null Returns current language ID
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetLanguageId() {
		$result = self::GetPageParam('id_language');
		if(!is_numeric($result) || $result<=0) {
			$result = self::GetParam('id_language');
		}//if(!is_numeric($result) || $result<=0)
		// $this->Dlog($result,'GetLanguageId');
		return $result;
	}//END public static function GetLanguageId
    /**
     * Get current language code
     *
     * @return string Returns current language code
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetLanguageCode() {
		$result = self::GetPageParam('language_code');
		if(!is_string($result) || !strlen($result)) { $result = self::GetParam('language_code'); }
		// $this->Dlog($result,'GetLanguageCode');
		return $result;
	}//END public static function GetLanguageCode
    /**
     * Gets multi-language flag
     *
     * @param string|null $namespace Namespace to test if is multi-language
     * If NULL or empty, current namespace is used
     * @return bool Returns multi-lanhuage flag
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function IsMultiLanguage(?string $namespace = NULL) {
		if(!is_array(AppConfig::GetValue('app_multi_language'))) { return AppConfig::GetValue('app_multi_language'); }
		$namespace = $namespace ? $namespace : self::$_app_instance->current_namespace;
		return get_array_value(AppConfig::GetValue('app_multi_language'),$namespace,TRUE,'bool');
	}//END public static function IsMultiLanguage
    /**
     * Get database cache state
     *
     * @return boolean TRUE is database caching is active for this namespace, FALSE otherwise
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function CacheDbCall() {
		return (AppConfig::GetValue('app_db_cache') && self::$_app_instance->current_namespace=='web');
	}//END public static static function CacheDbCall
	/**
	 * description
	 *
	 * @return string
	 * @access public
	 */
	public static function GetCurrentNamespace() {
		return self::$_app_instance->current_namespace;
	}//END public static function GetCurrentTemplate
	/**
	 * Gets the account API security key (auto loaded on LoadAppOptions() method)
	 *
	 * @return string Returns account API security key
	 * @access public
	 */
	public static function GetMyAccessKey() {
		return self::$_app_instance->app_access_key;
	}//END public static function GetMyAccessKey
	/**
	 * Gets the URL object
	 *
	 * @return \NETopes\Core\App\Url Returns URL object
	 * @access public
	 */
	public static function Url() {
		return self::$_app_instance->url;
	}//END public static function Url
	/**
	 * Gets the previous visited URL
	 *
	 * @return string Returns previous URL or home URL
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public static function GetPreviousUrl() {
		$url = self::GetPageParam('old_url');
		if(is_string($url) && strlen($url)) { return $url; }
		return self::GetAppWebLink();
	}//END public static function GetPreviousUrl
	/**
	 * Gets the application base link with or without language path
	 *
	 * @param string|null    $uri
	 * @param  string $namespace Namespace for generating app_web_link or NULL for current namespace
	 * @param  bool   $base If set to TRUE will return only base link (app_web_link property) else will return base link + language path
	 * @param string|null    $langCode
	 * @return string The link of the application (with or without language path)
	 * @throws \NETopes\Core\AppException
	 * @access public
	 */
	public static function GetAppWebLink(?string $uri = NULL,?string $namespace = NULL,bool $base = FALSE,?string $langCode = NULL): string {
		$namespace = $namespace ? $namespace : self::$_app_instance->current_namespace;
		if($namespace!=self::$_app_instance->current_namespace) {
			global $_DOMAINS_CONFIG;
			$domainreg = get_array_value($_DOMAINS_CONFIG,['namespaces',$namespace],NULL,'is_array');
			if(!is_array($domainreg) || !count($domainreg)) { throw new AppException('Invalid domain registry!',E_ERROR); }
			$ns_link_alias = get_array_value($domainreg,'link_alias','','is_string');
		} else {
			$ns_link_alias = AppHelpers::GetGlobalVar(['domain_config','link_alias'],'','is_string');
		}//if($namespace!=$this->current_namespace)
		$llangcode = $langCode===FALSE ? '' : (is_string($langCode) && strlen($langCode) ? $langCode : self::GetLanguageCode());
		$lang = (self::IsMultiLanguage($namespace) && !AppConfig::GetValue('url_without_language') && strlen($llangcode)) ? strtolower($llangcode) : '';
		if(AppConfig::GetValue('app_mod_rewrite')) {
			if($base) { return self::$app_web_link.'/'.($ns_link_alias ? $ns_link_alias.'/' : ''); }
			return self::$app_web_link.'/'.($ns_link_alias ? $ns_link_alias.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen($uri) ? '?'.$uri : '');
		}//if(AppConfig::GetValue('app_mod_rewrite'))
		$url = self::$app_web_link.'/';
		if(strlen($ns_link_alias)) { $url .= '?namespace='.$ns_link_alias; }
		if($base) { return $url; }
		if(strlen($lang)) { $url .= (strpos($url,'?')===FALSE ? '?' : '&').'language='.$lang; }
		if(strlen($uri)) { $url .= (strpos($url,'?')===FALSE ? '?' : '&').$uri; }
		return $url;
	}//END public static function GetAppWebLink
	/**
	 * Gets the session state befor current request (TRUE for existing session or FALSE for newly initialized)
	 *
	 * @return bool Session state (TRUE for existing session or FALSE for newly initialized)
	 * @access public
	 */
	public function CheckGlobalParams() {
		return $this->_app_state;
	}//END public function CheckGlobalParams
    /**
     * @param string      $option
     * @param string      $section
     * @param null        $defValue
     * @param null|string $validation
     * @param int|null    $contextId
     * @return string|null
     * @access public
     * @throws \NETopes\Core\AppException
     */
    public static function GetIOption(string $option,string $section = '',$defValue = NULL,?string $validation = NULL,?int $contextId = NULL): ?string {
        if(is_null($contextId)) { $contextId = self::GetPageParam(AppConfig::GetValue('context_id_field')); }
        if(!AppConfig::IsInstanceConfigLoaded()) { self::LoadInstanceConfig(); }
        return AppConfig::GetInstanceOption($option,$section,$defValue,$validation,$contextId);
	}//END public static function GetIOption
    /**
     * @param array $data
     * @param bool  $raw
     * @return array
     * @access public
     * @throws \NETopes\Core\AppException
     */
    public static function SetInstanceConfigData(array $data,bool $raw): array {
        return AppConfig::SetInstanceConfigData($data,$raw,AppConfig::GetValue('context_id_field'));
	}//END public static function SetInstanceConfigData
    /**
     * Get application (user) date format string
     *
     * @param bool $forPhp
     * @return string|null
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetDateFormat(bool $forPhp = FALSE): ?string {
		$format = self::GetParam('date_format');
		if(!strlen($format)) {
		    if(!strlen(self::GetParam('date_separator'))) { return NULL; }
		    $format = 'dd'.self::GetParam('date_separator').'MM'.self::GetParam('date_separator').'yyyy';
		}//if(!strlen($format))
		if(!$forPhp) { return $format; }
		return str_replace(['yyyy','mm','MM','dd','yy'],['Y','m','m','d','Y'],$format);
	}//END public static function GetDateFormat
    /**
     * Get application (user) time format string
     *
     * @param bool $forPhp
     * @return string|null
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetTimeFormat(bool $forPhp = FALSE): ?string {
		$format = self::GetParam('time_format');
		if(!strlen($format)) {
		    if(!strlen(self::GetParam('time_separator'))) { return NULL; }
		    $format = 'HH'.self::GetParam('time_separator').'mm'.self::GetParam('time_separator').'ss';
		}//if(!strlen($format))
		if(!$forPhp) { return $format; }
		return str_replace(['HH','hh','mm','ss'],['H','h','i','s'],$format);
	}//END public static function GetTimeFormat
    /**
     * Get application (user) datetime format string
     *
     * @param bool $forPhp
     * @return string|null
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetDateTimeFormat(bool $forPhp = FALSE): ?string {
		return self::GetDateFormat($forPhp).' '.self::GetTimeFormat($forPhp);
	}//END public static function GetTimeFormat
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
	public static function Dlog($value,?string $label = NULL,bool $file = FALSE,bool $path = FALSE) {
		if(!is_object(self::GetCurrentInstance()->debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		self::GetCurrentInstance()->debugger->Debug($value,$label,Debugger::DBG_DEBUG);
	}//END public static function Dlog
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
	public static function Wlog($value,?string $label = NULL,bool $file = FALSE,bool $path = FALSE) {
		if(!is_object(self::GetCurrentInstance()->debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		self::GetCurrentInstance()->debugger->Debug($value,$label,Debugger::DBG_WARNING);
	}//END public static function Wlog
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
	public static function Elog($value,?string $label = NULL,bool $file = FALSE,bool $path = FALSE) {
		if(!is_object(self::GetCurrentInstance()->debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		self::GetCurrentInstance()->debugger->Debug($value,$label,Debugger::DBG_ERROR);
	}//END public static function Elog
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
	public static function Ilog($value,?string $label = NULL,bool $file = FALSE,bool $path = FALSE) {
		if(!is_object(self::GetCurrentInstance()->debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		self::GetCurrentInstance()->debugger->Debug($value,$label,Debugger::DBG_INFO);
	}//END public static function Ilog
    /**
     * Writes a message in one of the application log files
     *
     * @param  string      $msg Text to be written to log
     * @param  string      $type Log type (log, error or debug) (optional)
     * @param  string|null $file Custom log file complete name (path + name) (optional)
     * @param string|null  $path
     * @return bool|string
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function Write2LogFile(string $msg,string $type = 'log',?string $file = NULL,?string $path = NULL) {
		if(is_object(self::GetCurrentInstance()->debugger)) { return self::GetCurrentInstance()->debugger->Write2LogFile($msg,$type,$file,$path); }
		$lpath = (strlen($path) ? rtrim($path,'/') : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::GetValue('logs_path')).'/';
		switch(strtolower($type)) {
			case 'error':
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::GetValue('errors_log_file')));
			case 'debug':
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::GetValue('debugging_log_file')));
			case 'log':
			default:
				return Debugger::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::GetValue('log_file')));
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
     * @static
	 */
	protected static abstract function LoadInstanceConfig(): void;
	/**
	 * Get current user ID
	 *
	 * @return int|null Returns current user ID
	 * @access public
     * @static
	 */
	public static abstract function GetCurrentUserId(): ?int;
}//END class App implements IApp