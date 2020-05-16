<?php
/**
 * NETopes application main class file.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use ErrorHandler;
use Exception;
use NETopes\Ajax\BaseRequest;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NETopes\Core\AppSession;
use NETopes\Core\Logging\FileLoggerAdapter;
use NETopes\Core\Logging\LogEvent;
use NETopes\Core\Logging\Logger;

/**
 * Class App
 *
 * @package  NETopes\Core
 */
abstract class App implements IApp {
    /**
     * @var    bool Flag for output buffering (started or not)
     */
    protected static $_appObStarted=FALSE;
    /**
     * @var    bool State of session before current request (TRUE for existing session or FALSE for newly initialized)
     */
    protected static $_appState=FALSE;
    /**
     * @var    string Account API security key (auto-loaded on LoadAppOptions() method)
     */
    protected static $_appAccessKey=NULL;
    /**
     * @var    \NETopes\Ajax\Request Object for ajax requests processing
     */
    protected static $_ajaxRequest=NULL;
    /**
     * @var    \NETopes\Core\App\Url Object for application URL processing
     */
    protected static $_url=NULL;
    /**
     * @var    bool Flag to indicate if the request is ajax or not
     */
    protected static $_isAjax=FALSE;
    /**
     * @var    string Page (browser tab) hash
     */
    protected static $_pHash=NULL;
    /**
     * @var    string Application absolute path (auto-set on start)
     */
    public static $appAbsolutePath=NULL;
    /**
     * @var    string Application non-public path (auto-set on start)
     */
    public static $appPath=NULL;
    /**
     * @var    string Application public path (auto-set on start)
     */
    public static $appPublicPath=NULL;
    /**
     * @var    string Application base URL (auto-set on start)
     */
    public static $appBaseUrl=NULL;
    /**
     * @var    string|null Name of the default database connection (from Configs/Connections.inc)
     */
    public static $defaultDbConnection=NULL;
    /**
     * @var    \NETopes\Core\App\ITheme Current theme object instance
     */
    public static $theme=NULL;
    /**
     * @var    bool Flag indicating if GUI was loaded or not
     */
    public static $guiLoaded=FALSE;
    /**
     * @var    bool Flag for setting silent errors
     */
    public static $silentErrors=FALSE;
    /**
     * @var    bool Debug mode
     */
    public static $debug=FALSE;
    /**
     * @var    \NETopes\Core\Logging\Logger Application logger instance
     */
    public static $logger=NULL;
    /**
     * @var    bool Flag to indicate if the request should keep the session alive
     */
    public static $keepAlive=TRUE;
    /**
     * @var    bool If set TRUE, name-space session will be cleared at commit
     */
    public static $clearNamespaceSession=FALSE;
    /**
     * @var    string Current namespace
     */
    public static $currentNamespace=NULL;
    /**
     * @var    string Current section folder
     */
    public static $currentSectionFolder=NULL;
    /**
     * @var    bool TRUE if current namespace requires login
     */
    public static $requiresLogin=NULL;
    /**
     * @var    string Namespace to be used for login
     */
    public static $loginNamespace=NULL;
    /**
     * @var    bool With sections
     */
    public static $withSections=TRUE;
    /**
     * @var    string Start page
     */
    public static $startPage='';
    /**
     * @var    string Logged in start page
     */
    public static $loggedInStartPage='';
    /**
     * @var    bool Application database stored option load state
     */
    public static $appOptionsLoaded=FALSE;

    /**
     * Load domain specific configuration
     *
     * @param bool        $isCli
     * @param string|null $virtualPath
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected static function LoadDomainConfig(bool $isCli=FALSE,?string $virtualPath=NULL): void {
        if(!defined('_NAPP_DOMAINS_CONFIG') || !is_array(_NAPP_DOMAINS_CONFIG['domains'])) {
            die('Invalid domain registry settings!');
        }
        $keyDomain=$isCli ? '_default' : (array_key_exists(static::$_url->GetAppDomain(),_NAPP_DOMAINS_CONFIG['domains']) ? static::$_url->GetAppDomain() : (array_key_exists('_default',_NAPP_DOMAINS_CONFIG['domains']) ? '_default' : ''));
        if(!$keyDomain || !isset(_NAPP_DOMAINS_CONFIG['domains'][$keyDomain]) || !_NAPP_DOMAINS_CONFIG['domains'][$keyDomain]) {
            die('Wrong domain registry settings!');
        }
        if(!static::$currentNamespace) {
            static::$currentNamespace=array_key_exists('namespace',$_GET) && strlen($_GET['namespace']) ? $_GET['namespace'] : _NAPP_DOMAINS_CONFIG['domains'][$keyDomain];
        }
        if(!isset(_NAPP_DOMAINS_CONFIG['namespaces'][static::$currentNamespace])) {
            die('Invalid namespace!');
        }
        $domainConfig=_NAPP_DOMAINS_CONFIG['namespaces'][static::$currentNamespace];
        static::$defaultDbConnection=$domainConfig['db_connection'];
        static::$_url->urlVirtualPath=trim((isset($domainConfig['link_alias']) ? $domainConfig['link_alias'] : '').'/'.(strlen($virtualPath) ? $virtualPath : ''),'/');
        $defaultViewsDir=get_array_value($domainConfig,'default_views_dir','','is_string');
        if(strlen($defaultViewsDir)) {
            AppConfig::SetValue('app_default_views_dir',$defaultViewsDir);
        }
        $viewsExtension=get_array_value($domainConfig,'views_extension','','is_string');
        if(strlen($viewsExtension)) {
            AppConfig::SetValue('app_views_extension',$viewsExtension);
        }
        $appTheme=get_array_value($domainConfig,'app_theme',NULL,'is_string');
        if(isset($appTheme)) {
            AppConfig::SetValue('app_theme',$appTheme);
        }
        static::$requiresLogin=$domainConfig['requires_login'];
        static::$loginNamespace=isset($domainConfig['login_namespace']) ? $domainConfig['login_namespace'] : NULL;
        static::$withSections=$domainConfig['with_sections'];
        static::$startPage=$domainConfig['start_page'];
        static::$loggedInStartPage=isset($domainConfig['loggedin_start_page']) ? $domainConfig['loggedin_start_page'] : static::$startPage;
        AppHelpers::SetGlobalVar('domain_config',$domainConfig);
    }//END protected static function LoadDomainConfig

    /**
     * Application initializer method
     *
     * @param bool      $isAjax          Optional flag indicating whether is an ajax request or not
     * @param array     $params          An optional key-value array containing to be assigned to non-static properties
     *                                   (key represents name of the property and value the value to be assigned)
     * @param bool      $sessionInit     Flag indicating if session should be started or not
     * @param bool|null $doNotKeepAlive  Flag indicating if session should be kept alive by the current request
     * @param bool      $isCli           Run in CLI mode
     * @return void
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     */
    public static function Start(bool $isAjax=FALSE,array $params=[],bool $sessionInit=TRUE,$doNotKeepAlive=NULL,bool $isCli=FALSE): void {
        if(is_array($params) && count($params)) {
            foreach($params as $key=>$value) {
                if(property_exists(static::class,$key)) {
                    static::$$key=$value;
                }
            }
        }//if(is_array($params) && count($params))
        static::$appOptionsLoaded=FALSE;
        static::$appAbsolutePath=_NAPP_ROOT_PATH;
        static::$appPath=_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH;
        static::$appPublicPath=_NAPP_ROOT_PATH._NAPP_PUBLIC_ROOT_PATH._NAPP_PUBLIC_PATH;
        static::$keepAlive=$doNotKeepAlive;
        static::$currentNamespace=array_key_exists('namespace',$params) && strlen($params['namespace']) ? $params['namespace'] : NULL;
        $customUserSessionAdapter=AppConfig::GetValue('user_session_adapter_class');
        if(strlen($customUserSessionAdapter)) {
            UserSession::SetAdapterClass($customUserSessionAdapter);
        }
        if($isCli) {
            AppSession::SetWithSession(FALSE);
            $appDomain=trim(get_array_value($_GET,'domain','','is_string'),' /\\');
            if(strlen($appDomain)) {
                $appWebProtocol=trim(get_array_value($_GET,'protocol','http','is_notempty_string'),' /:\\').'://';
                $urlFolder=trim(get_array_value($_GET,'uri_path','','is_string'),' /\\');
            } else {
                $appWebProtocol='';
                $urlFolder='';
            }//if(strlen($appDomain))
            static::$_isAjax=FALSE;
            static::$_url=new Url($appDomain,$appWebProtocol,$urlFolder);
            static::$appBaseUrl=static::$_url->GetWebLink();
            static::$_appState=TRUE;
            static::LoadDomainConfig(TRUE);
            static::$guiLoaded=FALSE;
            static::$debug=AppConfig::GetValue('debug');
            ErrorHandler::SetExecuteOnShutdown([static::class,'OnShutdown']);
            return;
        }//if($isCli)
        if($sessionInit) {
            AppSession::SetWithSession(TRUE);
            $cDir=Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
            AppSession::SessionStart($cDir,$doNotKeepAlive,$isAjax);
        } else {
            AppSession::SetWithSession(FALSE);
        }//if($session_init)
        static::$_isAjax=$isAjax;
        $appWebProtocol=(isset($_SERVER['HTTPS']) ? 'https' : 'http').'://';
        $appDomain=strtolower((array_key_exists('HTTP_HOST',$_SERVER) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
        $urlFolder=Url::ExtractUrlPath((is_array($params) && array_key_exists('startup_path',$params) ? $params['startup_path'] : NULL));
        $urlRemoveParams=isset($_GET['language']) && strlen($_GET['language']) ? [$_GET['language']] : NULL;
        static::$_url=new Url($appDomain,$appWebProtocol,$urlFolder,$urlRemoveParams);
        static::$appBaseUrl=static::$_url->GetWebLink();
        if(AppConfig::GetValue('split_session_by_page')) {
            static::$_pHash=get_array_value($_GET,'phash',get_array_value($_POST,'phash',NULL,'is_notempty_string'),'is_notempty_string');
            if(!static::$_pHash) {
                static::$_pHash=is_array($_COOKIE) && array_key_exists('__napp_pHash_',$_COOKIE) && strlen($_COOKIE['__napp_pHash_']) && strlen($_COOKIE['__napp_pHash_'])>15 ? substr($_COOKIE['__napp_pHash_'],0,-15) : NULL;
            }//if(!static::$_pHash)
            if(!static::$_pHash) {
                static::$_pHash=AppSession::GetNewUID();
            }
        }//if(AppConfig::GetValue('split_session_by_page'))
        static::ConfigureLogger();
        static::StartOutputBuffer();
        static::$_appState=AppSession::WithSession() ? AppSession::GetState() : TRUE;
        static::LoadDomainConfig(FALSE,array_key_exists('vpath',$params) && strlen($params['vpath']) ? $params['vpath'] : NULL);
        static::$guiLoaded=static::$_isAjax;
        static::$_url->data=static::$_isAjax ? (is_array(static::GetPageParam('get_params')) ? static::GetPageParam('get_params') : []) : static::$_url->data;
        static::SetPageParam('get_params',static::$_url->data);
        static::$_url->specialParams=['language','urlid','namespace','vpath'];
        if(!static::$_isAjax!==TRUE) {
            $curl=static::$_url->GetCurrentUrl();
            if(static::GetPageParam('current_url')!=$curl) {
                static::SetPageParam('old_url',static::GetPageParam('current_url'));
            }
            static::SetPageParam('current_url',$curl);
        }//if(static::$_isAjax!==TRUE)
        if(AppSession::WithSession() && array_key_exists('robot',$_SESSION) && $_SESSION['robot']==1) {
            AppConfig::SetValue('debug',FALSE);
        }
        static::$debug=AppConfig::GetValue('debug');
        ErrorHandler::SetExecuteOnShutdown([static::class,'OnShutdown']);
    }//END public static function Start

    /**
     * On application shutdown
     */
    public static function OnShutdown() {
        if(static::$logger instanceof Logger) {
            static::$logger->FlushLogs(FALSE);
        }
    }//END public static function OnShutdown

    /**
     * Gets application state
     *
     * @return bool Application (session if started) state
     */
    public static function GetAppState(): bool {
        return static::$_appState;
    }//END public static function GetAppState

    /**
     * Set base URL
     *
     * @param string      $appDomain
     * @param string|null $appWebProtocol
     * @param string|null $urlFolder
     * @return void
     */
    public static function SetUrl(string $appDomain,?string $appWebProtocol=NULL,?string $urlFolder=NULL): void {
        static::$_url=new Url($appDomain,$appWebProtocol ?? 'http://',$urlFolder);
        static::$appBaseUrl=static::$_url->GetWebLink();
    }//END public static function SetUrl

    /**
     * Gets the account API security key (auto loaded on LoadAppSettings() method)
     *
     * @return string|null Returns account API security key
     */
    public static function GetMyAccessKey(): ?string {
        return static::$_appAccessKey;
    }//END public static function GetMyAccessKey

    /**
     * Page hash getter
     *
     * @return string|null
     */
    public static function GetPhash(): ?string {
        return static::$_pHash;
    }//END public static function GetPhash

    /**
     * Page hash setter
     *
     * @param string $value The new value for phash property
     * @return void
     */
    public static function SetPhash(?string $value): void {
        static::$_pHash=$value;
    }//END public static function SetPhash

    /**
     * Gets a parameter from the temporary session
     *
     * @param string      $key   The name of the parameter
     * @param bool        $pHash The page hash (default FALSE, global context)
     *                           If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @throws \NETopes\Core\AppException
     */
    public static function GetParam(string $key,$pHash=FALSE,?string $namespace=NULL) {
        $lnamespace=strlen($namespace) ? $namespace : static::$currentNamespace;
        $lphash=isset($pHash) ? ($pHash===FALSE ? NULL : $pHash) : static::$_pHash;
        return AppSession::GetParam($key,$lphash,$lnamespace);
    }//END public static function GetParam

    /**
     * Gets a parameter from the temporary session
     *
     * @param string      $key   The name of the parameter
     * @param string      $pHash The page hash (default NULL)
     *                           If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return mixed  Returns the parameter value or NULL
     * @throws \NETopes\Core\AppException
     */
    public static function GetPageParam(string $key,$pHash=NULL,?string $namespace=NULL) {
        return static::GetParam($key,$pHash,$namespace);
    }//END public static function GetPageParam

    /**
     * Sets a parameter to the temporary session
     *
     * @param string      $key   The name of the parameter
     * @param mixed       $val   The value of the parameter
     * @param bool        $pHash The page hash (default FALSE, global context)
     *                           If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function SetParam(string $key,$val,$pHash=FALSE,?string $namespace=NULL) {
        $lnamespace=strlen($namespace) ? $namespace : static::$currentNamespace;
        $lphash=isset($pHash) ? ($pHash===FALSE ? NULL : $pHash) : static::$_pHash;
        AppSession::SetParam($key,$val,$lphash,$lnamespace);
    }//END public static function SetParam

    /**
     * Sets a parameter to the temporary session
     *
     * @param string      $key   The name of the parameter
     * @param mixed       $val   The value of the parameter
     * @param string      $pHash The page hash (default NULL)
     *                           If FALSE is passed, the main (NApp property) page hash will not be used
     * @param string|null $namespace
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function SetPageParam(string $key,$val,$pHash=NULL,?string $namespace=NULL) {
        static::SetParam($key,$val,$pHash,$namespace);
    }//END public static function SetPageParam

    /**
     * Delete a parameter from the temporary session
     *
     * @param string $key    The name of the parameter
     * @param bool   $pHash  The page hash (default FALSE, global context)
     *                       If FALSE is passed, the main (NApp property) page hash will not be used
     * @param null   $namespace
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function UnsetParam($key,$pHash=FALSE,$namespace=NULL) {
        $lnamespace=strlen($namespace) ? $namespace : static::$currentNamespace;
        $lphash=isset($pHash) ? ($pHash===FALSE ? NULL : $pHash) : static::$_pHash;
        AppSession::UnsetParam($key,$lphash,$lnamespace);
    }//END public static function UnsetParam

    /**
     * Delete a parameter from the temporary session
     *
     * @param string $key    The name of the parameter
     * @param string $pHash  The page hash (default NULL)
     *                       If FALSE is passed, the main (NApp property) page hash will not be used
     * @param null   $namespace
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function UnsetPageParam($key,$pHash=NULL,$namespace=NULL) {
        static::UnsetParam($key,$pHash,$namespace);
    }//END public static function UnsetPageParam

    /**
     * Commit the temporary session into the session
     *
     * @param bool        $clear                If TRUE is passed the session will be cleared
     * @param bool        $preserveOutputBuffer If true output buffer is preserved
     * @param bool        $showErrors           Display errors TRUE/FALSE
     * @param string|null $key                  Session key to commit (do partial commit)
     * @param string|null $phash                Page (tab) hash
     * @param bool        $reload               Reload session data after commit
     * @return void
     * @poaram bool $reload Reload session after commit (default TRUE)
     * @throws \NETopes\Core\AppException
     */
    public static function SessionCommit(bool $clear=FALSE,bool $preserveOutputBuffer=FALSE,bool $showErrors=TRUE,?string $key=NULL,?string $phash=NULL,bool $reload=TRUE) {
        if(!AppSession::WithSession()) {
            if($showErrors && method_exists('\ErrorHandler','ShowErrors')) {
                ErrorHandler::ShowErrors();
            }
            if(!$preserveOutputBuffer) {
                static::FlushOutputBuffer();
            }
            return;
        }//if(!AppSession::WithSession())
        AppSession::SessionCommit($clear,$showErrors,$key,$phash,$reload);
        if(!$preserveOutputBuffer) {
            static::FlushOutputBuffer();
        }
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
     * @throws \NETopes\Core\AppException
     */
    public static function NamespaceSessionCommit(?bool $clear=NULL,bool $preserveOutputBuffer=FALSE,bool $showErrors=TRUE,?string $namespace=NULL,?string $phash=NULL) {
        $namespace=strlen($namespace) ? $namespace : static::$currentNamespace;
        $clear=isset($clear) ? $clear : static::$clearNamespaceSession;
        static::SessionCommit($clear,$preserveOutputBuffer,$showErrors,$namespace,$phash);
    }//END public static function NamespaceSessionCommit

    /**
     * @return bool
     */
    public static function IsOutputBufferStarted(): bool {
        return static::$_appObStarted;
    }//END public static function IsOutputBufferStarted

    /**
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public static function StartOutputBuffer(): bool {
        if(!static::$_isAjax && !AppConfig::GetValue('buffered_output') && !static::LoggerRequiresOutputBuffering()) {
            return FALSE;
        }
        ob_start();
        return (static::$_appObStarted=TRUE);
    }//END public static function StartOutputBuffer

    /**
     * @param bool $end
     * @return bool
     */
    public static function FlushOutputBuffer(bool $end=FALSE): bool {
        if(!static::$_appObStarted) {
            return FALSE;
        }
        if(static::$logger instanceof Logger) {
            static::$logger->FlushLogs(TRUE);
        }
        if($end===TRUE) {
            ob_end_flush();
            static::$_appObStarted=FALSE;
        } else {
            ob_flush();
        }//if($end===TRUE)
        return TRUE;
    }//END public static function FlushOutputBuffer

    /**
     * @param bool $clear
     * @return string|null
     */
    public static function GetOutputBufferContent(bool $clear=TRUE): ?string {
        if(!static::$_appObStarted) {
            return NULL;
        }
        if($clear===TRUE) {
            return ob_get_clean();
        }
        return ob_get_contents();
    }//END public static function GetOutputBufferContent

    /**
     * @param bool $end
     * @return bool
     */
    public static function ClearOutputBuffer(bool $end=FALSE): bool {
        if(!static::$_appObStarted) {
            return FALSE;
        }
        if($end===TRUE) {
            ob_end_clean();
            static::$_appObStarted=FALSE;
        } else {
            ob_clean();
        }//if($end===TRUE)
        return TRUE;
    }//END public static function ClearOutputBuffer

    /**
     * Gets the AJAX flag
     *
     * @return bool Returns is AJAX flag
     */
    public static function IsAjax(): bool {
        return static::$_isAjax;
    }//END public static function IsAjax

    /**
     * Gets the AJAX object
     *
     * @return \NETopes\Ajax\BaseRequest Returns AJAX object
     */
    public static function Ajax() {
        return static::$_ajaxRequest;
    }//END public static function Ajax

    /**
     * Check if AJAX request object is valid
     *
     * @return bool
     */
    public static function IsValidAjaxRequest(): bool {
        return (is_object(static::$_ajaxRequest) && is_subclass_of(static::$_ajaxRequest,BaseRequest::class));
    }//END public static function IsValidAjaxRequest

    /**
     * Set the AJAX object
     *
     * @param $value
     */
    public static function SetAjaxRequest($value): void {
        static::$_ajaxRequest=$value;
    }//END public static function SetAjaxRequest

    /**
     * Initialize AJAX Request object
     *
     * @param array  $postParams Default parameters to be send via post on ajax requests
     * @param string $subSession Sub-session key/path
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public static function AjaxRequestInit(array $postParams=[],$subSession=NULL): bool {
        if(!AppConfig::GetValue('app_use_ajax_extension')) {
            return FALSE;
        }
        if(!static::IsValidAjaxRequest()) {
            $ajaxRequestClass=AppConfig::GetValue('ajax_class_name');
            if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) {
                throw new AppException('Invalid AJAX Request class: ['.$ajaxRequestClass.']!');
            }
            static::$_ajaxRequest=new $ajaxRequestClass($subSession,$postParams);
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
     */
    public static function ExecuteAjaxRequest(array $postParams=[],$subSession=NULL): void {
        if(!AppConfig::GetValue('app_use_ajax_extension')) {
            die('Invalid AJAX request!');
        }
        $ajaxRequestClass=AppConfig::GetValue('ajax_class_name');
        if(!strlen($ajaxRequestClass) || !class_exists($ajaxRequestClass) || !is_subclass_of($ajaxRequestClass,BaseRequest::class)) {
            die('Invalid AJAX Request class!');
        }
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
    public static function JsInit(bool $output=TRUE): ?string {
        $js=static::GetJsConstants();
        $js.=static::GetJsScripts();
        if($output) {
            echo $js;
            return NULL;
        }
        return $js;
    }//END public static function JsInit

    /**
     * Get NETopes application javascript constants
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public static function GetJsConstants(): string {
        $jsRootUrl=static::$appBaseUrl.AppConfig::GetValue('app_js_path');
        $jsThemeBaseUrl=static::$appBaseUrl.AppHelpers::GetSectionPath(AppHelpers::GetRequestParamValue('theme_folder'));
        $appBaseUrl=static::$appBaseUrl;
        $pHash=static::$_pHash;
        $js=<<<HTML
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
        $jsRootUrl=static::$appBaseUrl.AppConfig::GetValue('app_js_path');
        $js=<<<HTML
        <script type="text/javascript" src="{$jsRootUrl}/gibberish-aes.min.js?v=2005151"></script>
        <script type="text/javascript" src="{$jsRootUrl}/main.min.js?v=2005151"></script>
HTML;
        if(static::IsValidAjaxRequest()) {
            $js.=static::$_ajaxRequest->GetJsScripts($jsRootUrl);
        }
        $loggerScripts=static::$logger->GetScripts();
        if(is_array($loggerScripts) && count($loggerScripts)) {
            foreach($loggerScripts as $dsk=>$ds) {
                $js.=<<<HTML
        <script type="text/javascript" src="{$jsRootUrl}/debug/{$ds}?v=2005151"></script>
HTML;
            }//END foreach
        }//if(is_array($loggerScripts) && count($loggerScripts))
        return $js;
    }//END public static function GetJsScripts

    /**
     * Add javascript code to the dynamic js queue (executed at the end of the current request)
     *
     * @param string     $value Javascript code
     * @param bool       $fromFile
     * @param array|null $jsParams
     * @param bool       $dynamic
     * @return void
     */
    public static function AddJsScript(string $value,bool $fromFile=FALSE,?array $jsParams=NULL,bool $dynamic=FALSE) {
        if(!strlen($value)) {
            return;
        }
        AppHelpers::AddJsScript($value,$fromFile,$jsParams,$dynamic);
    }//END public static function AddJsScript

    /**
     * Get dynamic javascript to be executed
     *
     * @param string|null $html
     * @param bool        $raw
     * @return string Returns scripts to be executed
     */
    public static function GetDynamicJs(?string &$html=NULL,bool $raw=FALSE): ?string {
        if($raw) {
            $result=AppHelpers::GetDynamicJs(FALSE,$raw);
            $html=get_array_value($result,'html','','is_string');
            return get_array_value($result,'js','','is_string');
        }
        $result=AppHelpers::GetDynamicJs();
        return (is_array($result) ? implode(' ',$result) : $result);
    }//END public static function GetDynamicJs

    /**
     * Gets the URL object
     *
     * @return \NETopes\Core\App\Url Returns URL object
     */
    public static function Url() {
        return static::$_url;
    }//END public static function Url

    /**
     * Gets the previous visited URL
     *
     * @return string Returns previous URL or home URL
     * @throws \NETopes\Core\AppException
     */
    public static function GetPreviousUrl() {
        $url=static::GetPageParam('old_url');
        return (is_string($url) && strlen($url)) ? $url : NULL;
    }//END public static function GetPreviousUrl

    /**
     * Gets the application base link with or without language path
     *
     * @param string|null      $uri
     * @param string           $namespace Namespace for generating app_web_link or NULL for current namespace
     * @param bool             $base      If set to TRUE will return only base link (app_web_link property) else will return base link + language path
     * @param string|bool|null $langCode
     * @param string|null      $virtualPath
     * @return string The link of the application (with or without language path)
     * @throws \NETopes\Core\AppException
     */
    public static function GetAppBaseUrl(?string $uri=NULL,?string $namespace=NULL,bool $base=FALSE,?string $langCode=NULL,?string $virtualPath=NULL): string {
        $namespace=$namespace ? $namespace : static::$currentNamespace;
        if($namespace!=static::$currentNamespace) {
            $domainsConfig=defined('_NAPP_DOMAINS_CONFIG') ? _NAPP_DOMAINS_CONFIG : NULL;
            $domainReg=get_array_value($domainsConfig,['namespaces',$namespace],[],'is_array');
            if(!count($domainReg)) {
                throw new AppException('Invalid domain registry!');
            }
            $nsLinkAlias=get_array_value($domainReg,'link_alias','','is_string');
        } else {
            $nsLinkAlias=AppHelpers::GetGlobalVar(['domain_config','link_alias'],'','is_string');
        }//if($namespace!=static::$currentNamespace)
        $langCode=$langCode===FALSE ? '' : (is_string($langCode) && strlen($langCode) ? $langCode : static::GetLanguageCode());
        $lang=(static::IsMultiLanguage($namespace) && !AppConfig::GetValue('url_without_language') && strlen($langCode)) ? strtolower($langCode) : '';
        if(AppConfig::GetValue('app_mod_rewrite')) {
            if($base) {
                return static::$appBaseUrl.'/'.($nsLinkAlias ? $nsLinkAlias.'/' : '').(strlen($virtualPath) ? $virtualPath.'/' : '');
            }
            return static::$appBaseUrl.'/'.($nsLinkAlias ? $nsLinkAlias.'/' : '').(strlen($virtualPath) ? $virtualPath.'/' : '').(strlen($lang) ? $lang.'/' : '').(strlen($uri) ? '?'.$uri : '');
        }//if(AppConfig::GetValue('app_mod_rewrite'))
        $url=static::$appBaseUrl.'/';
        if(strlen($nsLinkAlias)) {
            $url.='?namespace='.$nsLinkAlias;
        }
        if(strlen($virtualPath)) {
            $url.='?vpath='.$virtualPath;
        }
        if($base) {
            return $url;
        }
        if(strlen($lang)) {
            $url.=(strpos($url,'?')===FALSE ? '?' : '&').'language='.$lang;
        }
        if(strlen($uri)) {
            $url.=(strpos($url,'?')===FALSE ? '?' : '&').$uri;
        }
        return $url;
    }//END public static function GetAppBaseUrl

    /**
     * Redirect to a url
     *
     * @param string $url                 Target URL for the redirect
     *                                    (if empty or null, the redirect will be made to the application root url)
     * @param bool   $doNotCommitSession  Flag for bypassing session commit on redirect
     * @param bool   $exit                Flag for stopping or not the request execution
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function Redirect(?string $url=NULL,bool $doNotCommitSession=FALSE,bool $exit=TRUE) {
        $url=strlen($url) ? $url : static::GetAppBaseUrl();
        if(!$doNotCommitSession) {
            static::NamespaceSessionCommit(FALSE,TRUE);
        }
        if(static::$_isAjax) {
            // TODO: check if is working every time
            if(static::IsValidAjaxRequest()) {
                static::$_ajaxRequest->ExecuteJs("window.location.href = '{$url}';");
            }//if(static::$IsValidAjaxRequest())
        } else {
            header('Location:'.$url);
            if($exit) {
                static::FlushOutputBuffer();
                exit();
            }
        }//if(static::$_isAjax)
    }//END public static function Redirect

    /**
     * Redirect to a url by modifying headers
     *
     * @param string $url                 Target URL for the redirect
     *                                    (if empty or null, the redirect will be made to the application root url)
     * @param bool   $doNotCommitSession  Flag for bypassing session commit on redirect
     * @param bool   $exit                Flag for stopping or not the request execution
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public static function FullRedirect(?string $url=NULL,bool $doNotCommitSession=FALSE,bool $exit=TRUE) {
        $url=strlen($url) ? $url : static::GetAppBaseUrl();
        if(!$doNotCommitSession) {
            static::NamespaceSessionCommit(FALSE,TRUE);
        }
        header('Location:'.$url);
        if($exit) {
            static::FlushOutputBuffer();
            exit();
        }
    }//END public static function FullRedirect

    /**
     * Get current namespace section relative path (with theme)
     *
     * @param string $themeDir  Optional theme directory
     *                          For non-web namespaces overwrites configuration theme
     * @return string Returns the current namespace section relative path
     *                          For non-web namespaces includes theme directory
     * @throws \NETopes\Core\AppException
     */
    public static function GetSectionPath($themeDir=NULL) {
        return AppHelpers::GetSectionPath($themeDir);
    }//END public static function GetSectionPath

    /**
     * Get application non-public repository path
     *
     * @return string
     * @throws \NETopes\Core\AppException
     */
    public static function GetRepositoryPath() {
        return AppHelpers::GetRepositoryPath();
    }//END public static function GetRepositoryPath

    /**
     * Get user ID key
     *
     * @return string Returns the key of the user ID
     */
    public static function GetUserIdKey(): string {
        return UserSession::GetUserIdKey();
    }//END public static function GetUserIdKey

    /**
     * Get current user ID
     *
     * @return int|null Returns current user ID
     * @throws \NETopes\Core\AppException
     */
    public static function GetCurrentUserId(): ?int {
        return UserSession::GetCurrentUserId();
    }//END public static function GetCurrentUserId

    /**
     * Loads application settings from database or from request parameters
     *
     * @param bool       $notFromDb
     * @param array|null $params
     * @return void
     * @throws \Exception
     */
    public static function LoadAppSettings(bool $notFromDb=FALSE,?array $params=NULL): void {
        if(static::$appOptionsLoaded) {
            return;
        }
        UserSession::LoadAppSettings($notFromDb,$params,static::$_appAccessKey);
        static::$theme=static::GetTheme();
        AppHelpers::InitializeKCFinder();
        static::$appOptionsLoaded=TRUE;
    }//END public static function LoadAppSettings

    /**
     * Get user login status
     *
     * @return boolean UserSession login status
     */
    public static function GetLoginStatus(): bool {
        return UserSession::$loginStatus;
    }//END public static function GetLoginStatus

    /**
     * Get database cache state
     *
     * @return boolean TRUE is database caching is active for this namespace, FALSE otherwise
     * @throws \NETopes\Core\AppException
     */
    public static function CacheDbCall() {
        return (AppConfig::GetValue('app_db_cache') && static::$currentNamespace==='web');
    }//END public static function CacheDbCall

    /**
     * Get current language ID
     *
     * @return int|null Returns current language ID
     * @throws \NETopes\Core\AppException
     */
    public static function GetLanguageId() {
        $result=static::GetPageParam('id_language');
        if(!is_numeric($result) || $result<=0) {
            $result=static::GetParam('id_language');
        }//if(!is_numeric($result) || $result<=0)
        // static::$Dlog($result,'GetLanguageId');
        return $result;
    }//END public static function GetLanguageId

    /**
     * Get current language code
     *
     * @return string Returns current language code
     * @throws \NETopes\Core\AppException
     */
    public static function GetLanguageCode() {
        $result=static::GetPageParam('language_code');
        if(!is_string($result) || !strlen($result)) {
            $result=static::GetParam('language_code');
        }
        // static::$Dlog($result,'GetLanguageCode');
        return $result;
    }//END public static function GetLanguageCode

    /**
     * Gets multi-language flag
     *
     * @param string|null $namespace Namespace to test if is multi-language
     *                               If NULL or empty, current namespace is used
     * @return bool Returns multi-language flag
     * @throws \NETopes\Core\AppException
     */
    public static function IsMultiLanguage(?string $namespace=NULL) {
        if(!is_array(AppConfig::GetValue('app_multi_language'))) {
            return AppConfig::GetValue('app_multi_language');
        }
        $namespace=$namespace ? $namespace : static::$currentNamespace;
        $appMultiLanguage=AppConfig::GetValue('app_multi_language');
        return get_array_value($appMultiLanguage,$namespace,TRUE,'bool');
    }//END public static function IsMultiLanguage

    /**
     * Get application (user) date format string
     *
     * @param bool $forPhp
     * @param bool $forMoment
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public static function GetDateFormat(bool $forPhp=FALSE,bool $forMoment=FALSE): ?string {
        $format=static::GetParam('date_format');
        if(!strlen($format)) {
            if(!strlen(static::GetParam('date_separator'))) {
                return NULL;
            }
            $format='dd'.static::GetParam('date_separator').'MM'.static::GetParam('date_separator').'yyyy';
        }//if(!strlen($format))
        if(!$forPhp) {
            return ($forMoment ? strtoupper($format) : $format);
        }
        return str_replace(['yyyy','mm','MM','dd','yy'],['Y','m','m','d','Y'],$format);
    }//END public static function GetDateFormat

    /**
     * Get application (user) time format string
     *
     * @param bool $forPhp
     * @param bool $forMoment
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public static function GetTimeFormat(bool $forPhp=FALSE,bool $forMoment=FALSE): ?string {
        $format=static::GetParam('time_format');
        if(!strlen($format)) {
            if(!strlen(static::GetParam('time_separator'))) {
                return NULL;
            }
            $format='HH'.static::GetParam('time_separator').'mm'.static::GetParam('time_separator').'ss';
        }//if(!strlen($format))
        if(!$forPhp) {
            return $format;
        }
        return str_replace(['HH','hh','mm','ss'],['H','h','i','s'],$format);
    }//END public static function GetTimeFormat

    /**
     * Get application (user) datetime format string
     *
     * @param bool $forPhp
     * @param bool $forMoment
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public static function GetDateTimeFormat(bool $forPhp=FALSE,bool $forMoment=FALSE): ?string {
        return static::GetDateFormat($forPhp,$forMoment).' '.static::GetTimeFormat($forPhp,$forMoment);
    }//END public static function GetTimeFormat

    /**
     * @param string      $option
     * @param string      $section
     * @param null        $defValue
     * @param null|string $validation
     * @param int|null    $contextId
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
    public static function GetIOption(string $option,string $section='',$defValue=NULL,?string $validation=NULL,?int $contextId=NULL): ?string {
        if(is_null($contextId)) {
            $contextId=static::GetPageParam(AppConfig::GetValue('context_id_field'));
        }
        if(!AppConfig::IsInstanceConfigLoaded()) {
            static::LoadInstanceConfig();
        }
        return AppConfig::GetInstanceOption($option,$section,$defValue,$validation,$contextId);
    }//END public static function GetIOption

    /**
     * @param array $data
     * @param bool  $raw
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public static function SetInstanceConfigData(array $data,bool $raw): array {
        return AppConfig::SetInstanceConfigData($data,$raw,AppConfig::GetValue('context_id_field'));
    }//END public static function SetInstanceConfigData

    /**
     * Initialize application logger
     *
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public static function ConfigureLogger() {
        if(!class_exists('\NETopes\Core\Logging\Logger')) {
            return FALSE;
        }
        if(static::$logger instanceof Logger) {
            return static::$logger->IsEnabled();
        }
        $adapters=AppConfig::GetValue('logging_adapters');
        static::$logger=new Logger(is_array($adapters) ? $adapters : [],_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::GetValue('logs_path'),AppConfig::GetValue('log_file'),_NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.'/tmp');
        return static::$logger->IsEnabled();
    }//END public static function InitDebugger

    /**
     * Get logger state
     *
     * @return bool Returns TRUE if logger is started, FALSE otherwise
     */
    public static function GetLoggerState() {
        return static::$logger instanceof Logger ? static::$logger->IsEnabled() : FALSE;
    }//END public static function GetLoggerState

    /**
     * Get logger output buffering requirements
     *
     * @return bool Returns TRUE if logger requires output buffering, FALSE otherwise
     */
    public static function LoggerRequiresOutputBuffering() {
        return static::$logger instanceof Logger ? static::$logger->RequiresOutputBuffering() : FALSE;
    }//END public static function LoggerRequiresOutputBuffering

    /**
     * Send data to logger active adapters with INFO level
     *
     * @param mixed       $data        Log data
     * @param string|null $label       Main label assigned to the log entry
     * @param array       $extraLabels Extra labels assigned to the log entry
     * @return void
     */
    public static function Ilog($data,?string $label=NULL,array $extraLabels=[]) {
        if(!static::GetLoggerState()) {
            return;
        }
        try {
            static::$logger->AddLogEntry($data,LogEvent::LEVEL_INFO,$label,$extraLabels,debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        } catch(Exception $e) {
            unset($e);
        }//END try
    }//END public static function Ilog

    /**
     * Send data to logger active adapters with DEBUG level
     *
     * @param mixed       $data        Log data
     * @param string|null $label       Main label assigned to the log entry
     * @param array       $extraLabels Extra labels assigned to the log entry
     * @return void
     */
    public static function Dlog($data,?string $label=NULL,array $extraLabels=[]) {
        if(!static::$debug || !static::GetLoggerState()) {
            return;
        }
        try {
            static::$logger->AddLogEntry($data,LogEvent::LEVEL_DEBUG,$label,$extraLabels,debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        } catch(Exception $e) {
            unset($e);
        }//END try
    }//END public static function Dlog

    /**
     * Send data to logger active adapters with WARNING level
     *
     * @param mixed       $data        Log data
     * @param string|null $label       Main label assigned to the log entry
     * @param array       $extraLabels Extra labels assigned to the log entry
     * @return void
     */
    public static function Wlog($data,?string $label=NULL,array $extraLabels=[]) {
        if(!static::GetLoggerState()) {
            return;
        }
        try {
            static::$logger->AddLogEntry($data,LogEvent::LEVEL_WARNING,$label,$extraLabels,debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        } catch(Exception $e) {
            unset($e);
        }//END try
    }//END public static function Wlog

    /**
     * Send data to logger active adapters with ERROR level
     *
     * @param mixed       $data        Log data
     * @param string|null $label       Main label assigned to the log entry
     * @param array       $extraLabels Extra labels assigned to the log entry
     * @return void
     */
    public static function Elog($data,?string $label=NULL,array $extraLabels=[]) {
        if(!static::GetLoggerState()) {
            return;
        }
        try {
            static::$logger->AddLogEntry($data,LogEvent::LEVEL_ERROR,$label,$extraLabels,debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        } catch(Exception $e) {
            unset($e);
        }//END try
    }//END public static function Elog

    /**
     * Writes a message into a log files
     *
     * @param mixed       $message    Data to be written to log
     * @param string|null $file       Custom log file  (optional)
     * @param string|null $scriptName Name of the file that sent the message to log (optional)
     * @return bool|AppException
     */
    public static function Log2File($message,?string $file=NULL,?string $scriptName=NULL) {
        return self::LogToFile($message,$scriptName,$file,NULL);
    }//END public static function Log2File

    /**
     * Writes a message into a log files
     *
     * @param mixed       $message    Data to be written to log
     * @param string|null $scriptName Name of the file that sent the message to log (optional)
     * @param string|null $file       Custom log file  (optional)
     * @param string|null $path       Custom logs path (optional)
     * @return bool|AppException
     */
    public static function LogToFile($message,?string $scriptName=NULL,?string $file=NULL,?string $path=NULL) {
        try {
            FileLoggerAdapter::LogToFile($message,strlen($file) ? $file : AppConfig::GetValue('log_file'),strlen($path) ? $path : _NAPP_ROOT_PATH._NAPP_APPLICATION_PATH.AppConfig::GetValue('logs_path'),$scriptName);
            return TRUE;
        } catch(AppException $e) {
            return $e;
        }//END try
    }//END public static function LogToFile

    /**
     * Load instance specific configuration options (into protected $instanceConfig property)
     *
     * @return void
     */
    protected static abstract function LoadInstanceConfig(): void;
}//END class App implements IApp