<?php
/**
 * NETopes application configuration structure file
 * Here are all the configuration elements definition for NETopes
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
if(!defined('_VALID_NAPP_REQ') || _VALID_NAPP_REQ!==TRUE) { die('Invalid request!'); }
$_NAPP_CONFIG_STRUCTURE = [
//START NETopes configuration
    // Files non-public repository path (absolute)
    'repository_path'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
    // Use CDN for loading resources
    'use_cdn'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Use KCFinder plugin
    'use_kc_finder'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // PHP password_hash function algorithm
    'password_hash_algo'=>['access'=>'readonly','default'=>CRYPT_BLOWFISH,'validation'=>'is_integer'],
    // Website name
    'website_name'=>['access'=>'readonly','default'=>'','validation'=>'is_string'],
    // Application name
    'app_name'=>['access'=>'readonly','default'=>'','validation'=>'is_string'],
    // Application version
    'app_version'=>['access'=>'readonly','default'=>'1.0.0','validation'=>'is_string'],
    // NETopes Core version
    'framework_version'=>['access'=>'readonly','default'=>'2.2.0','validation'=>'is_string'],
    // Application copyright text (NULL/empty = auto-generated)
    'app_copyright'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
    // First page title
    'app_first_page_title'=>['access'=>'readonly','default'=>'','validation'=>'is_string'],
    // Application author name
    'app_author_name'=>['access'=>'readonly','default'=>'','validation'=>'is_string'],
    //  Provider name
    'app_provider_name'=>['access'=>'readonly','default'=>'AdeoTEK Software SRL','validation'=>'is_string'],
    // Provider URL
    'app_provider_url'=>['access'=>'readonly','default'=>'http://www.adeotek.com','validation'=>'is_string'],
    // Application context ID field (ID account/location/etc)
    'context_id_field'=>['access'=>'readonly','default'=>'id_account','validation'=>'is_notempty_string'],
    // Enable multi-account support
    'app_multi_account'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Enable documents (invoicing) support
    'app_with_documents'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
    // Enable documents location filter on/off
    'app_filter_by_location'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Multi-language support
    'app_multi_language'=>['access'=>'readonly','default'=>TRUE,'validation'=>'isset'],
    // Flag for using or not the language code in the application urls
    'url_without_language'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Auto-populate resources translation table
    'auto_insert_missing_translations'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
    // View files extension
    'app_views_extension'=>['access'=>'readonly','default'=>'.php','validation'=>'is_notempty_string'],
    // View files default directory inside theme
    'app_default_views_dir'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
    // Application theme (NULL or empty for default theme)
    'app_theme'=>['access'=>'public','default'=>NULL,'validation'=>'is_string'],
    // Modules themed views path
    //   If NULL/empty modules sub-directory with theme name will be used,
    //   else the relative to application path given will be used
    'app_theme_modules_views_path'=>['access'=>'public','default'=>NULL,'validation'=>'is_string'],
    // Main sidebar (menu) default state (opened/closed)
    'left_sidebar_state'=>['access'=>'public','default'=>TRUE,'validation'=>'bool'],
    // Right sidebar (notifications) default state (opened/closed)
    'right_sidebar_state'=>['access'=>'public','default'=>FALSE,'validation'=>'bool'],
    // Push system notifications (NodeJS events)
    'with_pushed_sys_notification'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Push system notifications server URL (NodeJS server)
    'sys_notifications_url'=>['access'=>'readonly','default'=>'http://localhost:3339','validation'=>'is_string'],
    // API security key separator
    'app_api_separator'=>['access'=>'readonly','default'=>'[!]','validation'=>'is_notempty_string'],
    // Enable API requests logging
    'api_log_requests'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Name of the API log file
    'api_log_file'=>['access'=>'readonly','default'=>'api.log','validation'=>'is_notempty_string'],
    // Name of the API error log file
    'api_error_log_file'=>['access'=>'readonly','default'=>'api_error.log','validation'=>'is_notempty_string'],
    // Name of the cron jobs log file
    'cron_jobs_log_file'=>['access'=>'readonly','default'=>'cron_jobs.log','validation'=>'is_notempty_string'],
    // Name of the system tasks log file
    'sys_tasks_log_file'=>['access'=>'readonly','default'=>'sys_tasks.log','validation'=>'is_notempty_string'],
    // Name of the API cron jobs log file
    'api_cron_jobs_log_file'=>['access'=>'readonly','default'=>'api_cron_jobs.log','validation'=>'is_notempty_string'],
//END START NETopes configuration
//START NETopes base configuration
    // Root namespace
    'app_root_namespace'=>['access'=>'readonly','default'=>'NETopes','validation'=>'is_notempty_string'],
    // Modules (controllers) namespace prefix
    'app_modules_namespace_prefix'=>['access'=>'readonly','default'=>'Modules\\','validation'=>'is_string'],
    // Data sources namespace prefix
    'app_data_sources_namespace_prefix'=>['access'=>'readonly','default'=>'DataSources\\','validation'=>'is_string'],
    // Use internal modules and data sources autoloader
    'use_internal_autoloader'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Relative path to NETopes javascript files (linux style)
    'app_js_path'=>['access'=>'readonly','default'=>'/lib/netopes','validation'=>'is_string'],
    // Use NETopes AJAX extension
    'app_use_ajax_extension'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Custom validator adapter class
    'validator_adapter_class'=>['access'=>'readonly','default'=>NULL,'validation'=>'?is_string'],
    // Custom converter adapter class
    'converter_adapter_class'=>['access'=>'readonly','default'=>NULL,'validation'=>'?is_string'],
    // Custom formatter adapter class
    'formatter_adapter_class'=>['access'=>'readonly','default'=>NULL,'validation'=>'?is_string'],
    // Custom user session (LoadAppSettings/Login/Logout methods) adapter class
    'user_session_adapter_class'=>['access'=>'readonly','default'=>NULL,'validation'=>'?is_string'],
    // Error handler class NULL/empty for default NETopes implementation
    //   (must implement NETopes\Core\App\IErrorHandler interface)
    'error_handler_class'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
    // Request max duration in seconds
    'request_time_limit'=>['access'=>'readonly','default'=>1800,'validation'=>'is_not0_integer'],
    // Use output buffering via ob_start/ob_flush
    'buffered_output'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
    // Doctrine entities relative path (relative to application directory)
    'doctrine_entities_path'=>['access'=>'readonly','default'=>'DataEntities','validation'=>'is_string'],
    // Doctrine entities namespace
    'doctrine_entities_namespace'=>['access'=>'readonly','default'=>'NETopes\DataEntities','validation'=>'is_string'],
    // Doctrine proxies relative path (relative to application directory)
    'doctrine_proxies_path'=>['access'=>'readonly','default'=>'DataProxies','validation'=>'is_string'],
    // Doctrine proxies namespace
    'doctrine_proxies_namespace'=>['access'=>'readonly','default'=>'NETopes\DataProxies','validation'=>'is_string'],
    // Doctrine develop mode
    'doctrine_develop_mode'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Doctrine cache driver (empty for default ArrayCache)
    'doctrine_cache_driver'=>['access'=>'readonly','default'=>'','validation'=>'is_string'],
    // Use internal cache system
    'app_cache'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Use database internal cache system
    'app_db_cache'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Use Redis storage for internal cache system
    'app_cache_redis'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Cache files path (absolute)
    'app_cache_path'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
    // NETopes cached calls separator
    'app_cache_separator'=>['access'=>'readonly','default'=>'![NAPPC[','validation'=>'is_notempty_string'],
    // NETopes cached arguments separator
    'app_cache_arg_separator'=>['access'=>'readonly','default'=>']!NAPPC!A![','validation'=>'is_notempty_string'],
    // Cookie login on/off
    'cookie_login'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
    // Validity of login cookie from last action (in days)
    'cookie_login_lifetime'=>['access'=>'readonly','default'=>15,'validation'=>'is_not0_integer'],
    // Use internal cache system
    'app_encryption_key'=>['access'=>'readonly','default'=>'nAppEk','validation'=>'is_notempty_string'],
//END NETopes base configuration
//START Session configuration
    // Server timezone
    'server_timezone'=>['access'=>'readonly','default'=>'Europe/Bucharest','validation'=>'is_notempty_string'],
    // PHP Session name (NULL for default)
    'session_name'=>['access'=>'readonly','default'=>'NETOPESPID','validation'=>'is_notempty_string'],
    // Use session splitting by window.name or not
    'split_session_by_page'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
    // Use asynchronous session read/write
    'async_session'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
    // Session timeout in seconds
    'session_timeout'=>['access'=>'readonly','default'=>3600,'validation'=>'is_not0_integer'],
    // Use redis for session storage
    'session_redis'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Redis server connection string (host_name:port?params)
    'session_redis_server'=>['access'=>'readonly','default'=>'tcp://127.0.0.1:6379?timeout=1&weight=1&database=0','validation'=>'is_notempty_string'],
    // Use memcache for session storage
    'session_memcached'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Memcache server connection string (host_name:port)
    'session_memcached_server'=>['access'=>'readonly','default'=>'localhost:11211','validation'=>'is_notempty_string'],
    // PHP Session file path. If left blank default php setting will be used (absolute or relative path).
    'session_file_path'=>['access'=>'readonly','default'=>'tmp','validation'=>'is_notempty_string'],
    // Verification key for session data
    'session_key'=>['access'=>'readonly','default'=>'1234567890','validation'=>'is_string'],
    // Session array keys case: CASE_LOWER/CASE_UPPER or NULL for no case modification
    'session_keys_case'=>['access'=>'readonly','default'=>CASE_LOWER,'validation'=>'is_integer'],
//END Session configuration
//START Logs & errors reporting
    // Debug mode on/off
    'debug'=>['access'=>'public','default'=>TRUE,'validation'=>'bool'],
    // Database debug mode on/off
    'db_debug'=>['access'=>'public','default'=>FALSE,'validation'=>'bool'],
    // Database debug to file on/off
    'db_debug2file'=>['access'=>'public','default'=>FALSE,'validation'=>'bool'],
    // Show exception trace data
    'show_exceptions_trace'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
    // Show debug invocation source file name and path in browser console on/off
    'console_show_file'=>['access'=>'public','default'=>TRUE,'validation'=>'bool'],
    // Javascript php console password
    'debug_console_password'=>['access'=>'readonly','default'=>'112233','validation'=>'is_string'],
    // Relative path to the logs folder
    'logs_path'=>['access'=>'readonly','default'=>'/.logs','validation'=>'is_notempty_string'],
    // Name of the main log file
    'log_file'=>['access'=>'readonly','default'=>'app.log','validation'=>'is_notempty_string'],
    // Name of the errors log file
    'errors_log_file'=>['access'=>'readonly','default'=>'errors.log','validation'=>'is_notempty_string'],
    // Name of the debugging log file
    'debug_log_file'=>['access'=>'readonly','default'=>'debugging.log','validation'=>'is_notempty_string'],
//END START Logs & errors reporting
];