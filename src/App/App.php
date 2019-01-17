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

/**
  * Class App
  *
  * @package  NETopes\Core
  * @access   public
  */
abstract class App implements IApp {
    /**
	 * @var    bool Flag for output buffering (started or not)
	 * @access protected
	 */
	protected static $_appObStarted = FALSE;
	/**
	 * @var    bool State of session before current request (TRUE for existing session or FALSE for newly initialized)
	 * @access protected
	 */
	protected static $_appState = FALSE;
	/**
     * @var    string Account API security key (auto-loaded on LoadAppOptions() method)
     * @access protected
     */
	protected static $_appAccessKey = NULL;
	/**
	 * @var    \NETopes\Ajax\Request Object for ajax requests processing
	 * @access public
	 */
	protected static $_ajaxRequest = NULL;
	/**
	 * @var    \NETopes\Core\App\Url Object for application URL processing
	 * @access public
	 */
	protected static $_url = NULL;
	/**
	 * @var    bool Flag to indicate if the request is ajax or not
	 * @access public
	 */
	protected static $_isAjax = FALSE;
	/**
	 * @var    string Page (browser tab) hash
	 * @access public
	 */
	protected static $_pHash = NULL;
	/**
	 * @var    string Application absolute path (auto-set on start)
	 * @access public
	 */
	public static $appAbsolutePath = NULL;
	/**
	 * @var    string Application non-public path (auto-set on start)
	 * @access public
	 */
	public static $appPath = NULL;
	/**
	 * @var    string Application public path (auto-set on start)
	 * @access public
	 */
	public static $appPublicPath = NULL;
	/**
	 * @var    string Application base URL (auto-set on start)
	 * @access public
	 */
	public static $appBaseUrl = NULL;
	/**
	 * @var    string|null Name of the default database connection (from Configs/Connections.inc)
	 * @access public
	 */
	public static $defaultDbConnection = NULL;
	/**
	 * @var    \NETopes\Core\App\ITheme Current theme object instance
	 * @access public
	 */
	public static $theme = NULL;
	/**
	 * @var    bool Flag indicating if GUI was loaded or not
	 * @access public
	 */
	public static $guiLoaded = FALSE;
	/**
	 * @var    bool Flag for setting silent errors
	 * @access public
	 */
	public static $silentErrors = FALSE;
	/**
	 * @var    bool Debug mode
	 * @access public
	 */
	public static $debug = FALSE;
	/**
	 * @var    \NETopes\Core\App\Debugger Object for debugging
	 * @access public
	 */
	public static $debugger = NULL;
	/**
	 * @var    bool Flag to indicate if the request should keep the session alive
	 * @access public
	 */
	public static $keepAlive = TRUE;
	/**
	 * @var    bool If set TRUE, name-space session will be cleared at commit
	 * @access public
	 */
	public static $clearNamespaceSession = FALSE;
    /**
     * @var    string Current namespace
     * @access public
     */
	public static $currentNamespace = NULL;
    /**
     * @var    string Current section folder
     * @access public
     */
	public static $currentSectionFolder = NULL;
    /**
     * @var    bool TRUE if current namespace requires login
     * @access public
     */
	public static $requiresLogin = NULL;
    /**
     * @var    string Namespace to be used for login
     * @access public
     */
	public static $loginNamespace = NULL;
    /**
     * @var    bool With sections
     * @access public
     */
	public static $withSections = TRUE;
	/**
	 * @var    string Start page
	 * @access public
	 */
	public static $startPage = '';
	/**
	 * @var    string Logged in start page
	 * @access public
	 */
	public static $loggedInStartPage = '';
	/**
	 * @var    bool Application database stored option load state
	 * @access public
	 */
	public static $appOptionsLoaded = FALSE;
	/**
     * Load domain specific configuration
     *
     * @param bool $isCli
     * @return void
     * @throws \NETopes\Core\AppException
     * @access protected
     */
	protected static function LoadDomainConfig(bool $isCli = FALSE): void {
        global $_DOMAINS_CONFIG;
		if(!isset($_DOMAINS_CONFIG['domains']) || !is_array($_DOMAINS_CONFIG['domains'])) { die('Invalid domain registry settings!'); }
		$keyDomain = $isCli ? '_default' : (array_key_exists(static::$_url->GetAppDomain(),$_DOMAINS_CONFIG['domains']) ? static::$_url->GetAppDomain() : (array_key_exists('_default',$_DOMAINS_CONFIG['domains']) ? '_default' : ''));
		if(!$keyDomain || !isset($_DOMAINS_CONFIG['domains'][$keyDomain]) || !$_DOMAINS_CONFIG['domains'][$keyDomain]) { die('Wrong domain registry settings!'); }
		if(!static::$currentNamespace) { static::$currentNamespace = array_key_exists('namespace',$_GET) && strlen($_GET['namespace']) ? $_GET['namespace'] : $_DOMAINS_CONFIG['domains'][$keyDomain]; }
		if(!isset($_DOMAINS_CONFIG['namespaces'][static::$currentNamespace])) { die('Invalid namespace!'); }
		$domainConfig = $_DOMAINS_CONFIG['namespaces'][static::$currentNamespace];
		static::$defaultDbConnection = $domainConfig['db_connection'];
		static::$_url->url_virtual_path = isset($domainConfig['link_alias']) ? $domainConfig['link_alias'] : '';
		$defaultViewsDir = get_array_value($domainConfig,'default_views_dir','','is_string');
		if(strlen($defaultViewsDir)) { AppConfig::SetValue('app_default_views_dir',$defaultViewsDir); }
		$viewsExtension = get_array_value($domainConfig,'views_extension','','is_string');
		if(strlen($viewsExtension)) { AppConfig::SetValue('app_views_extension',$viewsExtension); }
		$appTheme = get_array_value($domainConfig,'app_theme',NULL,'is_string');
		if(isset($appTheme)) { AppConfig::SetValue('app_theme',$appTheme); }
		static::$requiresLogin = $domainConfig['requires_login'];
		static::$loginNamespace = isset($domainConfig['login_namespace']) ? $domainConfig['login_namespace'] : NULL;
		static::$withSections = $domainConfig['with_sections'];
		static::$startPage = $domainConfig['start_page'];
		static::$loggedInStartPage = isset($domainConfig['loggedin_start_page']) ? $domainConfig['loggedin_start_page'] : static::$startPage;
		AppHelpers::SetGlobalVar('domain_config',$domainConfig);
	}//END protected static function LoadDomainConfig
    /**
     * Application initializer method
     *
     * @param  bool      $isAjax Optional flag indicating whether is an ajax request or not
     * @param  array     $params An optional key-value array containing to be assigned to non-static properties
     * (key represents name of the property and value the value to be assigned)
     * @param  bool      $sessionInit Flag indicating if session should be started or not
     * @param  bool|null $doNotKeepAlive Flag indicating if session should be kept alive by the current request
     * @param  bool      $isCli Run in CLI mode
     * @return void
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     * @access public
     */
	public static function Start(bool $isAjax = FALSE,array $params = [],bool $sessionInit = TRUE,$doNotKeepAlive = NULL,bool $isCli = FALSE): void {
	    if(is_array($params) && count($params)) {
			foreach($params as $key=>$value) { if(property_exists(static::class,$key)) { static::$$key = $value; } }
		}//if(is_array($params) && count($params))
	    static::$appOptionsLoaded = FALSE;
		static::$appAbsolutePath = _NAPP_ROOT_PATH;
		static::$appPath = _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH;
		static::$appPublicPath = _NAPP_ROOT_PATH._NAPP_PUBLIC_ROOT_PATH._NAPP_PUBLIC_PATH;
		static::$keepAlive = $doNotKeepAlive;
		static::$currentNamespace = array_key_exists('namespace',$params) && strlen($params['namespace']) ? $params['namespace'] : NULL;
	    if($isCli) {
	        AppSession::SetWithSession(FALSE);
			$appDomain = trim(get_array_value($_GET,'domain','','is_string'),' /\\');
			if(strlen($appDomain)) {
				$appWebProtocol = trim(get_array_value($_GET,'protocol','http','is_notempty_string'),' /:\\').'://';
				$urlFolder = trim(get_array_value($_GET,'uri_path','','is_string'),' /\\');
			} else {
				$appWebProtocol = '';
				$urlFolder = '';
			}//if(strlen($appDomain))
			static::$_isAjax = FALSE;
			static::$_url = new Url($appDomain,$appWebProtocol,$urlFolder);
			static::$appBaseUrl = static::$_url->GetWebLink();
			static::$_appState = TRUE;
			static::LoadDomainConfig(TRUE);
			static::$guiLoaded = FALSE;
			static::$debug = AppConfig::GetValue('debug');
			return;
		}//if($isCli)
		if($sessionInit) {
            AppSession::SetWithSession(TRUE);
            $cDir = Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
            AppSession::SessionStart($cDir,$doNotKeepAlive,$isAjax);
        } else {
            AppSession::SetWithSession(FALSE);
        }//if($session_init)
        static::$_isAjax = $isAjax;
        $appWebProtocol = (isset($_SERVER["HTTPS"]) ? 'https' : 'http').'://';
        $appDomain = strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
        $urlFolder = Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
        static::$_url = new Url($appDomain,$appWebProtocol,$urlFolder);
        static::$appBaseUrl = static::$_url->GetWebLink();
        if(AppConfig::GetValue('split_session_by_page')) {
            static::$_pHash = get_array_value($_GET,'phash',get_array_value($_POST,'phash',NULL,'is_notempty_string'),'is_notempty_string');
            if(!static::$_pHash) {
                static::$_pHash = is_array($_COOKIE) && array_key_exists('__napp_pHash_',$_COOKIE) && strlen($_COOKIE['__napp_pHash_']) && strlen($_COOKIE['__napp_pHash_'])>15 ? substr($_COOKIE['__napp_pHash_'],0,-15) : NULL;
            }//if(!static::$_pHash)
            if(!static::$_pHash ) { static::$_pHash = AppSession::GetNewUID(); }
        }//if(AppConfig::GetValue('split_session_by_page'))
        static::InitDebugger();
        static::StartOutputBuffer();
        static::$_appState = AppSession::WithSession() ? AppSession::GetState() : TRUE;
        static::LoadDomainConfig();
		static::$guiLoaded = static::$_isAjax;
		static::$_url->data = static::$_isAjax ? (is_array(static::GetPageParam('get_params')) ? static::GetPageParam('get_params') : []) : static::$_url->data;
		static::SetPageParam('get_params',static::$_url->data);
		static::$_url->special_params = array('language','urlid','namespace');
		if(!static::$_isAjax!==TRUE) {
			$curl = static::$_url->GetCurrentUrl();
			if(static::GetPageParam('current_url')!=$curl) { static::SetPageParam('old_url',static::GetPageParam('current_url')); }
			static::SetPageParam('current_url',$curl);
		}//if(static::$_isAjax!==TRUE)
		if(AppSession::WithSession() && array_key_exists('robot',$_SESSION) && $_SESSION['robot']==1) { AppConfig::SetValue('debug',FALSE); }
		static::$debug = AppConfig::GetValue('debug');
	}//END public static function Start
	/**
	 * Gets application state
     *
	 * @return bool Application (session if started) state
	 * @access public
	 */
	public static function GetAppState(): bool {
		return static::$_appState;
	}//END public static function GetAppState
	/**
	 * Gets the account API security key (auto loaded on LoadAppOptions() method)
	 *
	 * @return string Returns account API security key
	 * @access public
	 */
	public static function GetMyAccessKey() {
		return static::$_appAccessKey;
	}//END public static function GetMyAccessKey
	/**
	 * Page hash getter
	 *
	 * @return string|null
	 * @access public
	 */
	public static function GetPhash(): ?string {
		return static::$_pHash;
	}//END public static function GetPhash
	/**
	 * Page hash setter
	 *
	 * @param  string $value The new value for phash property
	 * @return void
	 * @access public
	 */
	public static function SetPhash(?string $value): void {
		static::$_pHash = $value;
	}//END public static function SetPhash
    /**
     * Gets a parameter from the temporary session
     *
     * @param  string     $key The name of the parameter
     * @param bool        $pHash The page hash (default FALSE, global context)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @access public
     * @throws \NETopes\Core\AppException
     */
    public static function GetParam(string $key,$pHash = FALSE,?string $namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : static::$currentNamespace;
		$lphash = isset($pHash) ? ($pHash===FALSE ? NULL : $pHash) : static::$_pHash;
		return AppSession::GetParam($key,$lphash,$lnamespace);
	}//END public static function GetParam
    /**
     * Gets a parameter from the temporary session
     *
     * @param  string     $key The name of the parameter
     * @param  string     $pHash The page hash (default NULL)
     * If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetPageParam(string $key,$pHash = NULL,?string $namespace = NULL) {
		return static::GetParam($key,$pHash,$namespace);
	}//END public static function GetPageParam
	/**
	 * Sets a parameter to the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  mixed  $val The value of the parameter
	 * @param bool    $pHash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param string|null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function SetParam(string $key,$val,$pHash = FALSE,?string $namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : static::$currentNamespace;
		$lphash = isset($pHash) ? ($pHash===FALSE ? NULL : $pHash) : static::$_pHash;
		AppSession::SetParam($key,$val,$lphash,$lnamespace);
	}//END public static function SetParam
	/**
	 * Sets a parameter to the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  mixed  $val The value of the parameter
	 * @param  string $pHash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param string|null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function SetPageParam(string $key,$val,$pHash = NULL,?string $namespace = NULL) {
		static::SetParam($key,$val,$pHash,$namespace);
	}//END public static function SetPageParam
	/**
	 * Delete a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param bool    $pHash The page hash (default FALSE, global context)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function UnsetParam($key,$pHash = FALSE,$namespace = NULL) {
		$lnamespace = strlen($namespace) ? $namespace : static::$currentNamespace;
		$lphash = isset($pHash) ? ($pHash===FALSE ? NULL : $pHash) : static::$_pHash;
		AppSession::UnsetParam($key,$lphash,$lnamespace);
	}//END public static function UnsetParam
	/**
	 * Delete a parameter from the temporary session
	 *
	 * @param  string $key The name of the parameter
	 * @param  string $pHash The page hash (default NULL)
	 * If FALSE is passed, the main (NApp property) page hash will not be used
	 * @param null    $namespace
	 * @return void
	 * @access public
     * @throws \NETopes\Core\AppException
	 */
	public static function UnsetPageParam($key,$pHash = NULL,$namespace = NULL) {
		static::UnsetParam($key,$pHash,$namespace);
	}//END public static function UnsetPageParam
	/**
     * Commit the temporary session into the session
     *
     * @param  bool   $clear If TRUE is passed the session will be cleared
     * @param  bool   $preserveOutputBuffer If true output buffer is preserved
     * @param  bool   $showErrors Display errors TRUE/FALSE
     * @param  string|null $key Session key to commit (do partial commit)
     * @param  string|null $phash Page (tab) hash
     * @param  bool   $reload Reload session data after commit
     * @return void
     * @poaram bool $reload Reload session after commit (default TRUE)
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function SessionCommit(bool $clear = FALSE,bool $preserveOutputBuffer = FALSE,bool $showErrors = TRUE,?string $key = NULL,?string $phash = NULL,bool $reload = TRUE) {
		if(!AppSession::WithSession()) {
			if($showErrors && method_exists('\ErrorHandler','ShowErrors')) { \ErrorHandler::ShowErrors(); }
			if(!$preserveOutputBuffer) { static::FlushOutputBuffer(); }
			return;
		}//if(!AppSession::WithSession())
		AppSession::SessionCommit($clear,$showErrors,$key,$phash,$reload);
		if(!$preserveOutputBuffer) { static::FlushOutputBuffer(); }
	}//END public static function SessionCommit
    /**
     * Commit the namespace temporary session into the session
     *
     * @param bool|null   $clear
     * @param bool        $preserveOutputBuffer
     * @param bool        $showErrors
     * @param string|null $namespace
     * @param string|null $phash
     * @return void
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function NamespaceSessionCommit(?bool $clear = NULL,bool $preserveOutputBuffer = FALSE,bool $showErrors = TRUE,?string $namespace = NULL,?string $phash = NULL) {
		$namespace = strlen($namespace) ? $namespace : static::$currentNamespace;
		$clear = isset($clear) ? $clear : static::$clearNamespaceSession;
		static::SessionCommit($clear,$preserveOutputBuffer,$showErrors,$namespace,$phash);
	}//END public static function NamespaceSessionCommit
	/**
	 * @return bool
	 */
	public static function IsOutputBufferStarted(): bool {
		return static::$_appObStarted;
	}//END public function IsOutputBufferStarted
	/**
     * @return bool
     * @throws \NETopes\Core\AppException
	 */
	public static function StartOutputBuffer(): bool {
		if(!static::$_isAjax && !AppConfig::GetValue('buffered_output') && !static::$debugger) { return FALSE; }
		ob_start();
		return (static::$_appObStarted = TRUE);
	}//END public static function StartOutputBuffer
    /**
	 * @param bool $end
	 * @return bool
	 */
	public static function FlushOutputBuffer(bool $end = FALSE): bool {
		if(!static::$_appObStarted) { return FALSE; }
		if(is_object(static::$debugger)) { static::$debugger->SendData(); }
		if($end===TRUE) {
			ob_end_flush();
			static::$_appObStarted = FALSE;
		} else {
			ob_flush();
		}//if($end===TRUE)
		return TRUE;
	}//END public static function FlushOutputBuffer
	/**
	 * @param bool $clear
     * @return string|null
     */
	public function GetOutputBufferContent(bool $clear = TRUE): ?string {
		if(!static::$_appObStarted) { return NULL; }
		if($clear===TRUE) { return ob_get_clean(); }
		return ob_get_contents();
	}//END public function GetOutputBufferContent
    /**
	 * @param bool $end
	 * @return bool
     */
	public static function ClearOutputBuffer(bool $end = FALSE): bool {
		if(!static::$_appObStarted) { return FALSE; }
		if($end===TRUE) {
			ob_end_clean();
			static::$_appObStarted = FALSE;
		} else {
			ob_clean();
		}//if($end===TRUE)
		return TRUE;
	}//END public static function ClearOutputBuffer
	/**
	 * Gets the AJAX flag
	 *
	 * @return bool Returns is AJAX flag
	 * @access public
	 */
	public static function IsAjax(): bool {
		return static::$_isAjax;
	}//END public static function IsAjax
	/**
	 * Gets the AJAX object
	 *
	 * @return \NETopes\Ajax\BaseRequest Returns AJAX object
	 * @access public
	 */
	public static function Ajax() {
		return static::$_ajaxRequest;
	}//END public static function Ajax
	/**
     * Check if AJAX request object is valid
     *
     * @return bool
     * @access public
     */
    public static function IsValidAjaxRequest(): bool {
	    return (is_object(static::$_ajaxRequest) && is_subclass_of(static::$_ajaxRequest,BaseRequest::class));
	}//END public static function IsValidAjaxRequest
    /**
     * Set the AJAX object
     *
     * @param $value
     * @access public
     */
	public static function SetAjaxRequest($value): void {
		static::$_ajaxRequest = $value;
	}//END public static function SetAjaxRequest
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
	    if(!static::IsValidAjaxRequest()) {
	        $ajaxRequestClass = AppConfig::GetValue('ajax_class_name');
	        if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) { throw new AppException('Invalid AJAX Request class: ['.$ajaxRequestClass.']!'); }
			static::$_ajaxRequest = new $ajaxRequestClass($subSession,$postParams);
	    }//if(!static::IsValidAjaxRequest())
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
        $ajaxRequestClass::PrepareAndExecuteRequest($postParams,$subSession);
	}//END public static function ExecuteAjaxRequest
	/**
     * Initialize NETopes application javascript
     *
     * @param bool $output
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
	public static function JsInit(bool $output = TRUE): ?string {
	    $js = static::GetJsConstants();
	    $js .= static::GetJsScripts();
		if($output) { echo $js; return NULL; }
		return $js;
	}//END public static function JsInit
    /**
     * Get NETopes application javascript constants
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
	public static function GetJsConstants(): string {
	    $jsRootUrl = static::$appBaseUrl.AppConfig::GetValue('app_js_path');
	    $jsThemeBaseUrl = static::$appBaseUrl.AppHelpers::GetSectionPath();
	    $appBaseUrl = static::$appBaseUrl;
	    $pHash = static::$_pHash;
	    $js = <<<HTML
        <script type="text/javascript">
            const nAppBaseUrl = '{$appBaseUrl}';
            const nAppThemeUrl = '{$jsThemeBaseUrl}';
            const NAPP_PHASH = '{$pHash}';
            const NAPP_JS_PATH = '{$jsRootUrl}';
        </script>
HTML;
		return $js;
	}//END public static function GetJsConstants
    /**
     * Get NETopes application javascript
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
	public static function GetJsScripts(): string {
	    $jsRootUrl = static::$appBaseUrl.AppConfig::GetValue('app_js_path');
	    $js = <<<HTML
        <script type="text/javascript" src="{$jsRootUrl}/gibberish-aes.min.js?v=1901081"></script>
        <script type="text/javascript" src="{$jsRootUrl}/main.min.js?v=1901081"></script>
HTML;
        if(static::IsValidAjaxRequest()) { $js .= static::$_ajaxRequest->GetJsScripts($jsRootUrl); }
		if(is_object(static::$debugger)) {
			$dbg_scripts = static::$debugger->GetScripts();
			if(is_array($dbg_scripts) && count($dbg_scripts)) {
				foreach($dbg_scripts as $dsk=>$ds) {
				    $js .= <<<HTML
        <script type="text/javascript" src="{$jsRootUrl}/debug/{$ds}?v=1712011"></script>
HTML;
				}//END foreach
			}//if(is_array($dbg_scripts) && count($dbg_scripts))
		}//if(is_object(static::$debugger))
		return $js;
	}//END public static function GetJsScripts
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
	 * Get dynamic javascript to be executed
	 *
	 * @return string Returns scripts to be executed
	 * @access public
	 */
	public static function GetDynamicJs(): ?string {
        return AppHelpers::GetDynamicJs();
    }//END public static function GetDynamicJs
	/**
	 * Gets the URL object
	 *
	 * @return \NETopes\Core\App\Url Returns URL object
	 * @access public
	 */
	public static function Url() {
		return static::$_url;
	}//END public static function Url
    /**
     * Gets the previous visited URL
     *
     * @return string Returns previous URL or home URL
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetPreviousUrl() {
		$url = static::GetPageParam('old_url');
		return (is_string($url) && strlen($url)) ? $url : NULL;
	}//END public static function GetPreviousUrl
	/**
	 * Gets the application base link with or without language path
	 *
	 * @param string|null    $uri
	 * @param  string $namespace Namespace for generating app_web_link or NULL for current namespace
	 * @param  bool   $base If set to TRUE will return only base link (app_web_link property) else will return base link + language path
	 * @param string|bool|null    $langCode
	 * @return string The link of the application (with or without language path)
	 * @throws \NETopes\Core\AppException
	 * @access public
	 */
	public static function GetAppBaseUrl(?string $uri = NULL,?string $namespace = NULL,bool $base = FALSE,$langCode = NULL): string {
		$namespace = $namespace ? $namespace : static::$currentNamespace;
		if($namespace!=static::$currentNamespace) {
			global $_DOMAINS_CONFIG;
			$domainReg = get_array_value($_DOMAINS_CONFIG,['namespaces',$namespace],[],'is_array');
			if(!count($domainReg)) { throw new AppException('Invalid domain registry!'); }
			$nsLinkAlias = get_array_value($domainReg,'link_alias','','is_string');
		} else {
			$nsLinkAlias = AppHelpers::GetGlobalVar(['domain_config','link_alias'],'','is_string');
		}//if($namespace!=static::$currentNamespace)
		$langCode = $langCode===FALSE ? '' : (is_string($langCode) && strlen($langCode) ? $langCode : static::GetLanguageCode());
		$lang = (static::IsMultiLanguage($namespace) && !AppConfig::GetValue('url_without_language') && strlen($langCode)) ? strtolower($langCode) : '';
		if(AppConfig::GetValue('app_mod_rewrite')) {
			if($base) { return static::$appBaseUrl.'/'.($nsLinkAlias ? $nsLinkAlias.'/' : ''); }
			return static::$appBaseUrl.'/'.($nsLinkAlias ? $nsLinkAlias.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen($uri) ? '?'.$uri : '');
		}//if(AppConfig::GetValue('app_mod_rewrite'))
		$url = static::$appBaseUrl.'/';
		if(strlen($nsLinkAlias)) { $url .= '?namespace='.$nsLinkAlias; }
		if($base) { return $url; }
		if(strlen($lang)) { $url .= (strpos($url,'?')===FALSE ? '?' : '&').'language='.$lang; }
		if(strlen($uri)) { $url .= (strpos($url,'?')===FALSE ? '?' : '&').$uri; }
		return $url;
	}//END public static function GetAppBaseUrl
	/**
	 * Redirect to a url
	 *
	 * @param  string $url Target URL for the redirect
	 * (if empty or null, the redirect will be made to the application root url)
	 * @param  bool $doNotCommitSession Flag for bypassing session commit on redirect
	 * @param  bool $exit Flag for stopping or not the request execution
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public static function Redirect(?string $url = NULL,bool $doNotCommitSession = FALSE,bool $exit = TRUE) {
		$url = strlen($url) ? $url : static::GetAppBaseUrl();
		if(!$doNotCommitSession) { static::NamespaceSessionCommit(FALSE,TRUE); }
		if(static::$_isAjax) {
			// TODO: check if is working every time
			if(static::IsValidAjaxRequest()) {
				static::$_ajaxRequest->ExecuteJs("window.location.href = '{$url}';");
			}//if(static::$IsValidAjaxRequest())
		} else {
			header('Location:'.$url);
			if($exit) { static::FlushOutputBuffer(); exit(); }
		}//if(static::$_isAjax)
	}//END public static function Redirect
	/**
	 * Redirect to a url by modifying headers
	 *
	 * @param  string $url Target URL for the redirect
	 * (if empty or null, the redirect will be made to the application root url)
	 * @param  bool $doNotCommitSession Flag for bypassing session commit on redirect
	 * @param  bool $exit Flag for stopping or not the request execution
	 * @return void
	 * @access public
	 * @throws \NETopes\Core\AppException
	 */
	public static function FullRedirect(?string $url = NULL,bool $doNotCommitSession = FALSE,bool $exit = TRUE) {
		$url = strlen($url) ? $url : static::GetAppBaseUrl();
		if(!$doNotCommitSession) { static::NamespaceSessionCommit(FALSE,TRUE); }
		header('Location:'.$url);
		if($exit) { static::FlushOutputBuffer(); exit(); }
	}//END public static function FullRedirect
	/**
     * Loads application settings from database or from request parameters
     *
     * @param bool       $notFromDb
     * @param array|null $params
     * @return void
     * @throws \Exception
     * @access public
     */
	public static function LoadAppSettings(bool $notFromDb = FALSE,?array $params = NULL): void {
        if(static::$appOptionsLoaded) { return; }
        UserSession::LoadAppSettings($notFromDb,$params,static::$_appAccessKey);
        static::$theme = static::GetTheme();
		AppHelpers::InitializeKCFinder();
        static::$appOptionsLoaded = TRUE;
    }//END public static function LoadAppSettings
    /**
     * Get user login status
     *
     * @return boolean UserSession login status
     * @access public
     */
	public static function GetLoginStatus(): bool {
		return UserSession::$loginStatus;
	}//END public static function GetLoginStatus
    /**
     * Get database cache state
     *
     * @return boolean TRUE is database caching is active for this namespace, FALSE otherwise
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function CacheDbCall() {
		return (AppConfig::GetValue('app_db_cache') && static::$currentNamespace==='web');
	}//END public static function CacheDbCall
    /**
     * Get current language ID
     *
     * @return int|null Returns current language ID
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetLanguageId() {
		$result = static::GetPageParam('id_language');
		if(!is_numeric($result) || $result<=0) {
			$result = static::GetParam('id_language');
		}//if(!is_numeric($result) || $result<=0)
		// static::$Dlog($result,'GetLanguageId');
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
		$result = static::GetPageParam('language_code');
		if(!is_string($result) || !strlen($result)) { $result = static::GetParam('language_code'); }
		// static::$Dlog($result,'GetLanguageCode');
		return $result;
	}//END public static function GetLanguageCode
    /**
     * Gets multi-language flag
     *
     * @param string|null $namespace Namespace to test if is multi-language
     * If NULL or empty, current namespace is used
     * @return bool Returns multi-language flag
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function IsMultiLanguage(?string $namespace = NULL) {
		if(!is_array(AppConfig::GetValue('app_multi_language'))) { return AppConfig::GetValue('app_multi_language'); }
		$namespace = $namespace ? $namespace : static::$currentNamespace;
		return get_array_value(AppConfig::GetValue('app_multi_language'),$namespace,TRUE,'bool');
	}//END public static function IsMultiLanguage
    /**
     * Get application (user) date format string
     *
     * @param bool $forPhp
     * @return string|null
     * @access public
     * @throws \NETopes\Core\AppException
     */
	public static function GetDateFormat(bool $forPhp = FALSE): ?string {
		$format = static::GetParam('date_format');
		if(!strlen($format)) {
		    if(!strlen(static::GetParam('date_separator'))) { return NULL; }
		    $format = 'dd'.static::GetParam('date_separator').'MM'.static::GetParam('date_separator').'yyyy';
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
		$format = static::GetParam('time_format');
		if(!strlen($format)) {
		    if(!strlen(static::GetParam('time_separator'))) { return NULL; }
		    $format = 'HH'.static::GetParam('time_separator').'mm'.static::GetParam('time_separator').'ss';
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
		return static::GetDateFormat($forPhp).' '.static::GetTimeFormat($forPhp);
	}//END public static function GetTimeFormat
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
        if(is_null($contextId)) { $contextId = static::GetPageParam(AppConfig::GetValue('context_id_field')); }
        if(!AppConfig::IsInstanceConfigLoaded()) { static::LoadInstanceConfig(); }
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
	 * Initialize debug environment
	 *
	 * @return bool
	 * @throws \Exception
	 * @access public
	 */
    public static function InitDebugger() {
		if(AppConfig::GetValue('debug')!==TRUE || !class_exists('\NETopes\Core\App\Debugger')) { return FALSE; }
		if(is_object(static::$debugger)) { return static::$debugger->IsEnabled(); }
		$tmpPath = isset($_SERVER['DOCUMENT_ROOT']) && strlen($_SERVER['DOCUMENT_ROOT']) && strpos(_NAPP_ROOT_PATH,$_SERVER['DOCUMENT_ROOT'])!==FALSE ? _NAPP_ROOT_PATH.'/../tmp' : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.'/tmp';
		static::$debugger = new Debugger(AppConfig::GetValue('debug'),_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::GetValue('logs_path'),$tmpPath,AppConfig::GetValue('debug_console_password'));
		static::$debugger->log_file = AppConfig::GetValue('log_file');
		static::$debugger->errors_log_file = AppConfig::GetValue('errors_log_file');
		static::$debugger->debug_log_file = AppConfig::GetValue('debug_log_file');
		return static::$debugger->IsEnabled();
	}//END public static function InitDebugger
	/**
	 * Get debugger state
	 *
	 * @return bool Returns TRUE if debugger is started, FALSE otherwise
	 * @access public
	 */
    public static function GetDebuggerState() {
		return is_object(static::$debugger) && static::$debugger->IsEnabled();
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
		if(!is_object(static::$debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		static::$debugger->Debug($value,$label,Debugger::DBG_DEBUG);
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
		if(!is_object(static::$debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		static::$debugger->Debug($value,$label,Debugger::DBG_WARNING);
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
		if(!is_object(static::$debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		static::$debugger->Debug($value,$label,Debugger::DBG_ERROR);
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
		if(!is_object(static::$debugger)) { return; }
		if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE) {
			$dbg = debug_backtrace();
			$caller = array_shift($dbg);
			$label = (isset($caller['file']) ? ('['.($path===TRUE ? $caller['file'] : basename($caller['file'])).(isset($caller['line']) ? ':'.$caller['line'] : '').']') : '').$label;
		}//if(AppConfig::GetValue('console_show_file')===TRUE || $file===TRUE)
		static::$debugger->Debug($value,$label,Debugger::DBG_INFO);
	}//END public static function Ilog
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
        return Debugger::Log2File($msg,$file,$scriptName);
    }//END public static function Log2File
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
		if(is_object(static::$debugger)) { return static::$debugger->Write2LogFile($msg,$type,$file,$path); }
		$lpath = (strlen($path) ? rtrim($path,'/') : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::GetValue('logs_path')).'/';
		switch(strtolower($type)) {
			case 'error':
				return static::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::GetValue('errors_log_file')));
			case 'debug':
				return static::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::GetValue('debugging_log_file')));
			case 'log':
			default:
				return static::Log2File($msg,$lpath.(strlen($file) ? $file : AppConfig::GetValue('log_file')));
		}//switch(strtolower($type))
	}//END public function WriteToLog
	/**
	 * Load instance specific configuration options (into protected $instanceConfig property)
	 *
	 * @return void
	 * @access public
	 */
	protected static abstract function LoadInstanceConfig(): void;
}//END class App implements IApp