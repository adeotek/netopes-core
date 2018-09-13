<?php
/**
 * NETopes application configuration structure file
 *
 * Here are all the configuration elements definition for PAF
 *
 * @package    NETopes\Core\App\Helpers
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.5.7
 * @filesource
 */
if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }

	$_NAPP_CONFIG_STRUCTURE = [
	//START NETopes specific configuration
		// Files non-public repository path (absolute)
		'repository_path'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
		// Use CDN for loading resources
		'use_cdn'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
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
	//END START NETopes specific configuration
	//START Basic configuration
		// Root namespace
		'app_root_namespace'=>['access'=>'readonly','default'=>'NETopes','validation'=>'is_notempty_string'],
		// Use custom modules and data sources autoloader
		'use_custom_autoloader'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// Error handler class NULL/empty for default NETopes implementation
		//   (must implement NETopes\Core\App\IErrorHandler interface)
		'error_handler_class'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
		// Request max duration in seconds
		'request_time_limit'=>['access'=>'readonly','default'=>1800,'validation'=>'is_not0_integer'],
  		// Use output buffering via ob_start/ob_flush
		'bufferd_output'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
  		// Use internal cache system
		'app_cache'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Use database internal cache system
		'app_db_cache'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Use Redis storage for internal cache system
		'app_cache_redis'=>['access'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Cache files path (absolute)
		'app_cache_path'=>['access'=>'readonly','default'=>NULL,'validation'=>'is_string'],
  		// PAF cached calls separator
		'app_cache_separator'=>['access'=>'readonly','default'=>'![PAFC[','validation'=>'is_notempty_string'],
  		// PAF cached arguments separator
		'app_cache_arg_separator'=>['access'=>'readonly','default'=>']!PAFC!A![','validation'=>'is_notempty_string'],
  		// Cookie login on/off
		'cookie_login'=>['access'=>'readonly','default'=>TRUE,'validation'=>'bool'],
  		// Validity of login cookie from last action (in days)
		'cookie_login_lifetime'=>['access'=>'readonly','default'=>15,'validation'=>'is_not0_integer'],
	//END START Basic configuration
	//START PAF configuration overwrites
		// Session name (NULL for default)
		'session_name'=>['access'=>'readonly','default'=>'NETOPESPID','validation'=>'is_notempty_string'],
		// PAF implementing class name
		'ajax_class_name'=>['access'=>'readonly','default'=>'NETopes\Core\App\AjaxRequest','validation'=>'is_string'],
	//END PAF configuration overwrites
	];
?>