<?php
/**
 * NETopes application global configuration class file.
 * Here are all the configuration parameters for the NETopes application.
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core;
if(!defined('_VALID_NAPP_REQ') || _VALID_NAPP_REQ!==TRUE) { die('Invalid request!'); }
/**
 * Class AppConfig
 * AppConfig contains all the configuration parameters for the NETopes application
 * @package    NETopes\Core\App
 */
class AppConfig {
	/**
	 * @var    array Configuration structure
	 */
	private static $structure = NULL;
	/**
	 * @var    array Configuration data
	 */
	private static $data = NULL;
	/**
	 * @var    array|null An array of instance configuration options
	 * @access private
	 */
	private static $instanceConfig = NULL;
	/**
	 * Initialize application configuration class (structure and data)
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
	 * @param array $structure
	 * @throws \Exception
	 */
	public static function AddConfigStructure(array $structure) {
		self::$structure = array_merge(self::$structure,$structure);
	}//END public static function LoadConfig
    /**
     * Get an application configuration value
     * @param string $name
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public static function GetValue(string $name) {
	    if(!is_array(self::$structure)) { throw new AppException('Invalid configuration structure!'); }
		$element = get_array_value(self::$structure,$name,NULL,'is_array');
		if(!is_array($element)) { throw new \InvalidArgumentException("Undefined or invalid property [{$name}]!"); }
		$access = get_array_value($element,'access','readonly','is_notempty_string');
		if($access=='private') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
        $default = get_array_value($element,'default',NULL,'isset');
        $validation = get_array_value($element,'validation','','is_string');
		return get_array_value(self::$data,$name,$default,$validation);
	}//END public static function GetValue
    /**
     * Set an application configuration value
     * @param string $name
     * @param mixed  $value
     * @throws \NETopes\Core\AppException
     */
    public static function SetValue(string $name,$value): void {
        if(!is_array(self::$structure)) { throw new AppException('Invalid configuration structure!'); }
		$element = get_array_value(self::$structure,$name,NULL,'is_array');
		if(!is_array($element)) { throw new \InvalidArgumentException("Undefined or invalid property [{$name}]!"); }
		$access = get_array_value($element,'access','readonly','is_notempty_string');
		if($access!='public') { throw new \InvalidArgumentException("Inaccessible property [{$name}]!"); }
		$validation = get_array_value($element,'validation','','is_string');
		if(strlen($validation) && !validate_param($value,NULL,$validation,TRUE)) { throw new \InvalidArgumentException("Invalid value for property [{$name}]!"); }
		self::$data[$name] = $value;
	}//END public static function SetValue
	/**
     * @return bool
     */
    public static function IsInstanceConfigLoaded(): bool {
        return isset(static::$instanceConfig);
    }//END public static function IsInstanceConfigLoaded
    /**
     * @param array  $config
     * @param string $contextIdField
     * @param bool   $raw
     * @return array
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
     */
    public static function GetInstanceOption(string $option,string $section = '',$defValue = NULL,?string $validation = NULL,?int $contextId = NULL): ?string {
        $options = get_array_value(static::$instanceConfig,[strtolower($section),strtolower($option)],[],'is_array');
        $defValue = get_array_value($options,'',$defValue,$validation);
        if(is_null($contextId)) { return $defValue; }
        return get_array_value($options,$contextId,$defValue,$validation);
	}//END public static function GetInstanceOption
}//END class AppConfig