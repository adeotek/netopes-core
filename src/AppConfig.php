<?php
/**
 * NETopes application global configuration class file.
 *
 * Here are all the configuration parameters for the NETopes application.
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.0
 * @filesource
 */
namespace NETopes\Core;
if(!defined('_VALID_NAPP_REQ') || _VALID_NAPP_REQ!==TRUE) { die('Invalid request!'); }
/**
 * Class AppConfig
 *
 * AppConfig contains all the configuration parameters for the NETopes application
 *
 * @package    NETopes\Core\App
 * @access     public
 * @abstract
 * @method static server_timezone($value = NULL)
 * @method static db_debug($value = NULL)
 * @method static db_debug2file($value = NULL)
 * @method static error_handler_class($value = NULL)
 * @method static errors_log_file($value = NULL)
 * @method static logs_path($value = NULL)
 * @method static use_custom_autoloader($value = NULL)
 * @method static app_views_extension($value = NULL)
 * @method static app_theme($value = NULL)
 * @method static app_default_views_dir($value = NULL)
 * @method static app_theme_modules_views_path($value = NULL)
 * @method static app_root_namespace($value = NULL)
 * @method static debug($value = NULL)
 * @method static app_arequest_js_callback($value = NULL)
 * @method static app_version($value = NULL)
 * @method static framework_version($value = NULL)
 * @method static website_name($value = NULL)
 * @method static app_name($value = NULL)
 * @method static app_copyright($value = NULL)
 * @method static app_first_page_title($value = NULL)
 * @method static app_author_name($value = NULL)
 * @method static app_provider_name($value = NULL)
 * @method static app_provider_url($value = NULL)
 * @method static app_multi_language($value = NULL)
 * @method static app_db_cache($value = NULL)
 * @method static cookie_login_lifetime($value = NULL)
 * @method static session_timeout($value = NULL)
 * @method static repository_path($value = NULL)
 * @method static url_without_language($value = NULL)
 * @method static app_mod_rewrite($value = NULL)
 * @method static app_cache_path($value = NULL)
 * @method static app_api_key($value = NULL)
 * @method static app_api_separator($value = NULL)
 * @method static app_encryption_key($value = NULL)
 * @method static ajax_class_name($value = NULL)
 * @method static ajax_class_file($value = NULL)
 * @method static use_kc_finder($value = NULL)
 * @method static context_id_field($value = NULL)
 * @method static auto_insert_missing_translations($value = NULL)
 * @method static app_ajax_target($value = NULL)
 * @method static doctrine_entities_namespace($value = NULL)
 * @method static app_cache_redis($value = NULL)
 * @method static doctrine_proxies_path($value = NULL)
 * @method static doctrine_entities_path($value = NULL)
 * @method static doctrine_cache_driver($value = NULL)
 * @method static doctrine_proxies_namespace($value = NULL)
 * @method static doctrine_develop_mode($value = NULL)
 * @method static validator_adapter_class($value = NULL)
 * @method static formatter_adapter_class($value = NULL)
 * @method static converter_adapter_class($value = NULL)
 * @method static session_memcached($value = NULL)
 * @method static session_memcached_server($value = NULL)
 * @method static session_redis_server($value = NULL)
 * @method static session_keys_case($value = NULL)
 * @method static session_name($value = NULL)
 * @method static session_redis($value = NULL)
 * @method static session_file_path($value = NULL)
 * @method static session_key($value = NULL)
 * @method static request_time_limit($value = NULL)
 * @method static async_session($value = NULL)
 * @method static console_show_file($value = NULL)
 * @method static split_session_by_page($value = NULL)
 * @method static buffered_output($value = NULL)
 * @method static debug_console_password($value = NULL)
 * @method static log_file($value = NULL)
 * @method static debug_log_file($value = NULL)
 * @method static debugging_log_file($value = NULL)
 * @method static app_secure_http($value = NULL)
 * @method static app_js_path($value = NULL)
 * @method static app_params_encrypt($value = NULL)
 * @method static app_use_window_name($value = NULL)
 * @method static app_use_ajax_extension($value = NULL)
 */
class AppConfig {
	/**
	 * @var    array Configuration structure
	 * @access protected
	 * @static
	 */
	private static $structure = NULL;
	/**
	 * @var    array Configuration data
	 * @access protected
	 * @static
	 */
	private static $data = NULL;
	/**
	 * @var    array|null An array of instance configuration options
	 * @access private
	 */
	private static $instanceConfig = NULL;
	/**
	 * Initialize application configuration class (structure and data)
	 *
	 * @param array $data
	 * @param array $customStructure
	 * @throws \Exception
	 */
	public static function LoadConfig(array $data,array $customStructure) {
		require_once(__DIR__.'/napp_cfg_structure.php');
		if(!isset($_NAPP_CONFIG_STRUCTURE) || !is_array($_NAPP_CONFIG_STRUCTURE)) { die('Invalid NETopes configuration structure!'); }
		self::$structure = array_merge($_NAPP_CONFIG_STRUCTURE,$customStructure);
		self::$data = $data;
	}//END public static function LoadConfig
	/**
	 * Add application configuration structure array (merge with current structure)
	 *
	 * @param array $structure
	 * @throws \Exception
	 */
	public static function AddConfigStructure(array $structure) {
		self::$structure = array_merge(self::$structure,$structure);
	}//END public static function LoadConfig
	/**
	 * Magic method for accessing configuration elements
	 *
	 * @param  string $name Name of the configuration element
	 * @param  array  $arguments Value to be set to the configuration element
	 * @return mixed Returns the configuration element value
	 * @throws \Exception
	 * @access public
	 * @static
	 */
	public static function __callStatic(string $name,$arguments) {
		if(!is_array(self::$structure)) { throw new \Exception('Invalid configuration structure!'); }
		$element = get_array_value(self::$structure,$name,NULL,'is_array');
		if(!is_array($element)) { throw new \InvalidArgumentException("Undefined or invalid property [{$name}]!"); }
		$access = get_array_value($element,'access','readonly','is_notempty_string');
		$validation = get_array_value($element,'validation','','is_string');
		$result = NULL;
		if(is_array($arguments) && count($arguments)) {
			// is setter
			if($access!='public') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
			if(strlen($validation) && !get_array_value($arguments[0],NULL,$validation,TRUE)) { throw new \InvalidArgumentException("Invalid value for property [{$name}]!"); }
			self::$data[$name] = $arguments[0];
		} else {
			if($access=='private') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
			// is getter
			$default = get_array_value($element,'default',NULL,'isset');
			$result = get_array_value(self::$data,$name,$default,$validation);
		}//if(is_array($arguments) && count($arguments))
		return $result;
	}//END public static function __callStatic
	/**
     * @return bool
     * @access public
     * @static
     */
    public static function IsInstanceConfigLoaded(): bool {
        return isset(static::$instanceConfig);
    }//END public static function IsInstanceConfigLoaded
    /**
     * @param array  $config
     * @param string $contextIdField
     * @param bool   $raw
     * @return array
     * @access public
     * @static
     */
    public static function SetInstanceConfigData(array $config,bool $raw = TRUE,?string $contextIdField = NULL): array {
        if($raw) {
            static::$instanceConfig = [];
            foreach($config as $item) {
                $section = strtolower(get_array_value($item,'section','','is_string'));
                $option = strtolower(get_array_value($item,'option','','is_string'));
                if(!strlen($option)) { continue; }
                $contextId = get_array_value($item,$contextIdField??'',NULL,'is_integer');
                if(!isset(static::$instanceConfig[$section])) {
                    static::$instanceConfig[$section] = [];
                } elseif(!isset(static::$instanceConfig[$section][$option])) {
                    static::$instanceConfig[$section][$option] = [];
                }//if(!isset($result[$section]))
                static::$instanceConfig[$section][$option][(string)$contextId] = get_array_value($item,'ivalue',get_array_value($item,'svalue',get_array_value($item,'value',NULL,'isset'),'is_string'),'is_integer');
            }//END foreach
        } else {
            static::$instanceConfig = $config;
        }//if($raw)
        return static::$instanceConfig;
	}//END public static function SetInstanceConfigData
    /**
     * @param string      $option
     * @param string      $section
     * @param null        $defValue
     * @param null|string $validation
     * @param int|null    $contextId
     * @return string|null
     * @access public
     * @static
     */
    public static function GetInstanceOption(string $option,string $section = '',$defValue = NULL,?string $validation = NULL,?int $contextId = NULL): ?string {
        $options = get_array_value(static::$instanceConfig,[strtolower($section),strtolower($option)],[],'is_array');
        $defValue = get_array_value($options,'',$defValue,$validation);
        if(is_null($contextId)) { return $defValue; }
        return get_array_value($options,$contextId,$defValue,$validation);
	}//END public static function GetInstanceOption
}//END class AppConfig