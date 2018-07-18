<?php
/**
 * VirtualEntity class file
 *
 * Generic implementation for entities with no predefined structure (properties)
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use PAF\AppException;
/**
 * VirtualEntity class
 *
 * Generic implementation for entities with no predefined structure (properties)
 *
 * @package  NETopes\Core\App
 * @access   public
 */
class VirtualEntity {
	const ORIGINAL_NAME = 0;
	const CAMELCASE_NAME = 1;
	/**
     * An array containing the entity data.
     *
     * @var array
     */
	protected $data;
	/**
     * Naming mode (original/camelcase).
     *
     * @var int
     */
	protected $naming_mode = VirtualEntity::CAMELCASE_NAME;
	/**
     * Strict mode onn/off.
     *
     * @var bool
     */
	protected $strict_mode = TRUE;
	/**
	 * Initializes a new VirtualEntity.
	 *
	 * @param array $data
	 * @param int   $naming_mode
	 * @param bool  $strict_mode
	 */
    public function __construct(array $data = [],$naming_mode = self::CAMELCASE_NAME,$strict_mode = TRUE) {
        $this->data = $data;
        $this->naming_mode = $naming_mode;
        $this->strict_mode = $strict_mode;
    }//END public function __construct
	/**
	 * VirtualEntity dynamic property getter
	 *
	 * @param  string $name The name of the property
	 * @return mixed Returns the value of the property
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function __get($name) {
		$key = $this->naming_mode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if(is_array($this->data) && array_key_exists($key,$this->data)) { return $this->data[$key]; }
		elseif(!$this->strict_mode) { return NULL; }
		throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
	}//END public function __get
	/**
	 * VirtualEntity dynamic method call
	 *
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 * @access public
	 * @throws \PAF\AppException
	 */
	public function __call($name,array $arguments) {
		if(strtolower(substr($name,0,3))==='get') {
			$prop_name = substr($name,3);
			$strict = is_array($arguments) ? get_array_param($arguments,2,FALSE,'bool') : $this->strict_mode;
		} elseif(strtolower(substr($name,0,7))==='safeget') {
			$prop_name = substr($name,7);
			$strict = FALSE;
		} elseif(strtolower(substr($name,0,3))==='set') {
			$prop_name = substr($name,3);
			if(!is_array($arguments) || count($arguments)!=1) { throw new AppException('No value provided!'); }
			$this->SetPropertyValue($prop_name,$arguments[0]);
			return TRUE;
		} else {
			throw new AppException('Undefined method ['.$name.']!',E_ERROR,1);
		}//if(strtolower(substr($name,0,3))==='get')
		if(!strlen($prop_name)) { throw new AppException('Invalid method ['.$name.']!',E_ERROR,1); }
		$default_value = get_array_param($arguments,0,NULL,'isset');
		$validation = get_array_param($arguments,1,NULL,'is_string');
		return $this->GetPropertyValue($prop_name,$strict,$default_value,$validation);
	}//END public function __call
	/**
	 * Get property value by name
	 *
	 * @param string $name
	 * @param null   $default_value
	 * @param null   $validation
	 * @param bool   $strict
	 * @return mixed
	 * @throws \PAF\AppException
	 * @access public
	 */
	public function getProperty($name,$default_value = NULL,$validation = NULL,$strict = FALSE) {
		return $this->GetPropertyValue($name,$strict,$default_value,$validation);
	}//END public function getProperty
	/**
	 * VirtualEntity dynamic getter method
	 *
	 * @param  string $name The name of the property
	 * @param bool    $strict
	 * @param null    $default_value
	 * @param null    $validation
	 * @return mixed Returns the value of the property
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function GetPropertyValue($name,$strict = FALSE,$default_value = NULL,$validation = NULL) {
		$key = $this->naming_mode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if($strict && (!is_array($this->data) || !array_key_exists($key,$this->data))) {
			throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
		}//if(is_array($this->data) && array_key_exists($key,$this->data))
		return get_array_param($this->data,$key,$default_value,$validation);
	}//END protected function GetPropertyValue
	/**
	 * VirtualEntity dynamic setter method
	 *
	 * @param  string $name The name of the property
	 * @param mixed    $value
	 * @param bool    $strict
	 * @return void
	 * @throws \PAF\AppException
	 * @access protected
	 */
	protected function SetPropertyValue($name,$value,$strict = FALSE) {
		$key = $this->naming_mode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if($strict && (!is_array($this->data) || !array_key_exists($key,$this->data))) {
			throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
		}//if(is_array($this->data) && array_key_exists($key,$this->data))
		if(!is_array($this->data)) { $this->data = []; }
		$this->data[$key] = $value;
	}//END protected function SetPropertyValue
	/**
	 * Check if property exists
	 *
	 * @param  string $name The name of the property
	 * @param  bool   $not_null
	 * @return bool Returns TRUE if property exists
	 * @access public
	 */
	public function hasProperty($name,$not_null = FALSE): bool {
		$key = $this->naming_mode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if($not_null) { return array_key_exists($key,$this->data) && isset($this->data[$key]); }
		return array_key_exists($key,$this->data);
	}//END public function hasProperty
	/**
     * Get data array
     */
    public function toArray(): array {
        return $this->data;
    }//END public function toArray
    /**
     * Check if is a new instance
     */
    public function isNew(): bool {
        if(array_key_exists('id',$this->data)) { return (!is_numeric($this->data['id']) || $this->data['id']==0); }
        return FALSE;
    }//END public function isNew
    /**
	 * Merge an array or a VirtualEntity instance to current instance
	 *
	 * @param  array|object $data The data to be merged into this instance
	 * @param  bool $recursive
	 * @return bool Returns TRUE on success, FALSE otherwise
	 * @access public
	 */
	public function merge($data,bool $recursive = FALSE) {
		if(is_object($data) && count($data)) {
			if(!is_array($this->data)) { $this->data = []; }
			if($recursive) {
				$this->data = array_merge_recursive($this->data,$data->toArray());
			} else {
				$this->data = array_merge($this->data,$data->toArray());
			}//if($recursive)
		} elseif(is_array($data) && count($data)) {
			if(!is_array($this->data)) { $this->data = []; }
			if($recursive) {
				$this->data = array_merge_recursive($this->data,$data);
			} else {
				$this->data = array_merge($this->data,$data);
			}//if($recursive)
		} else {
			return FALSE;
		}//if(is_object($data) && count($data))
		return TRUE;
	}//END public function merge
}//END class VirtualEntity
?>