<?php
/**
 * NETopes application configuration structure file
 *
 * Here are all the configuration elements definition for PAF
 *
 * @package    NETopes\Core\App\Helpers
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2012 - 2018 AdeoTEK
 * @license    LICENSE.md
 * @version    2.2.0.1
 * @filesource
 */
if(!defined('_VALID_AAPP_REQ') || _VALID_AAPP_REQ!==TRUE) { die('Invalid request!'); }

	$_NAPP_CONFIG_STRUCTURE = [
	//START NETopes specific configuration
		// Files non-public repository path (absolute)
		'repository_path'=>['type'=>'readonly','default'=>NULL,'validation'=>'is_string'],
		// Use CDN for loading resources
		'use_cdn'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// PHP password_hash function algorithm
		'password_hash_algo'=>['type'=>'readonly','default'=>CRYPT_BLOWFISH,'validation'=>'is_integer'],
		// Website name
		'website_name'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		// Application name
		'app_name'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		// NETopes Core version
		'app_version'=>['type'=>'readonly','default'=>'1.0.0','validation'=>'is_string'],
		// NETopes Core version
		'framework_version'=>['type'=>'readonly','default'=>'2.2.0.1','validation'=>'is_string'],
		// Application copyright text (NULL/empty = auto-generated)
		'app_copyright'=>['type'=>'readonly','default'=>NULL,'validation'=>'is_string'],
		// First page title
		'app_first_page_title'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		// Application author name
		'app_author_name'=>['type'=>'readonly','default'=>'','validation'=>'is_string'],
		//  Provider name
		'app_provider_name'=>['type'=>'readonly','default'=>'Style Mag Universal SRL','validation'=>'is_string'],
		// Provider url
		'app_provider_url'=>['type'=>'readonly','default'=>'http://www.adeotek.com','validation'=>'is_string'],
		// Enable multi-account support
		'app_multi_account'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// Enable documents (invoicing) support
		'app_with_documents'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
		// Enable documents location filter on/off
		'app_filter_by_location'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// Multi-language flag
		'app_multi_language'=>['type'=>'readonly','default'=>TRUE,'validation'=>'isset'],
		// Flag for using or not the language code in the application urls
		'url_without_language'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
		// Auto-populate resources translation table
		'auto_insert_missing_translations'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
  		// View files extension
		'app_views_extension'=>['type'=>'readonly','default'=>'.php','validation'=>'is_notempty_string'],
  		// View files default directory inside theme
		'app_default_views_dir'=>['type'=>'readonly','default'=>NULL,'validation'=>'is_string'],
	 	// Admin application theme (NULL or empty for default theme)
		'app_theme'=>['type'=>'public','default'=>NULL,'validation'=>'is_string'],
 		// Application theme type
  		//   Values:
  		//   - native/NULL -> custom HTML+CSS
  		//   - bootstrap2 -> Tweeter Bootstrap 2
  		//   - bootstrap3 -> Tweeter Bootstrap 3
  		//   - bootstrap4 -> Tweeter Bootstrap 4
		'app_theme_type'=>['type'=>'public','default'=>'bootstrap3','validation'=>'is_string'],
 		// Admin application theme default controls size (Values: xlg/lg/sm/xs/xxs)
		'app_theme_def_controls_size'=>['type'=>'public','default'=>'xxs','validation'=>'is_string'],
 		// Admin application theme default actions (buttons) size (Values: xlg/lg/sm/xs/xxs)
		'app_theme_def_actions_size'=>['type'=>'public','default'=>'xs','validation'=>'is_string'],
 		// Modules themed views path
		//   If NULL/empty modules sub-directory with theme name will be used,
		//   else the relative to application path given will be used
		'x_app_theme_modules_views_path'=>['type'=>'public','default'=>NULL,'validation'=>'is_string'],
		// Main sidebar (menu) default state (opened/closed)
		'left_sidebar_state'=>['type'=>'public','default'=>TRUE,'validation'=>'bool'],
  		// Right sidebar (notifications) default state (opened/closed)
		'right_sidebar_state'=>['type'=>'public','default'=>FALSE,'validation'=>'bool'],
  		// Push system notifications (NodeJS events)
		'with_pushed_sys_notification'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Push system notifications server URL (NodeJS server)
		'sys_notifications_url'=>['type'=>'readonly','default'=>'http://localhost:3339','validation'=>'is_string'],
  		// API security key separator
		'app_api_separator'=>['type'=>'readonly','default'=>'[!]','validation'=>'is_notempty_string'],
  		// Name of the API log file
		'api_log_file'=>['type'=>'readonly','default'=>'api.log','validation'=>'is_notempty_string'],
  		// Name of the cron jobs log file
		'cron_jobs_log_file'=>['type'=>'readonly','default'=>'cron_jobs.log','validation'=>'is_notempty_string'],
  		// Name of the system tasks log file
		'sys_tasks_log_file'=>['type'=>'readonly','default'=>'sys_tasks.log','validation'=>'is_notempty_string'],
  		// Name of the API cron jobs log file
		'api_cron_jobs_log_file'=>['type'=>'readonly','default'=>'api_cron_jobs.log','validation'=>'is_notempty_string'],
	//END START NETopes specific configuration
	//START Basic configuration
		// Request max duration in seconds
		'request_time_limit'=>['type'=>'readonly','default'=>1800,'validation'=>'is_not0integer'],
  		// Use output buffering via ob_start/ob_flush
		'bufferd_output'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
  		// Use internal cache system
		'app_cache'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Use database internal cache system
		'app_db_cache'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Use Redis storage for internal cache system
		'app_cache_redis'=>['type'=>'readonly','default'=>FALSE,'validation'=>'bool'],
  		// Cache files path (absolute)
		'app_cache_path'=>['type'=>'readonly','default'=>NULL,'validation'=>'is_string'],
  		// PAF cached calls separator
		'app_cache_separator'=>['type'=>'readonly','default'=>'![PAFC[','validation'=>'is_notempty_string'],
  		// PAF cached arguments separator
		'app_cache_arg_separator'=>['type'=>'readonly','default'=>']!PAFC!A![','validation'=>'is_notempty_string'],
  		// Cookie login on/off
		'cookie_login'=>['type'=>'readonly','default'=>TRUE,'validation'=>'bool'],
  		// Validity of login cookie from last action (in days)
		'cookie_login_lifetime'=>['type'=>'readonly','default'=>15,'validation'=>'is_not0integer'],
	//END START Basic configuration
	//START PAF configuration overwrites
		// Session name (NULL for default)
		'session_name'=>['type'=>'readonly','default'=>'NETOPESPID','validation'=>'is_notempty_string'],
	//END PAF configuration overwrites
	];
?>