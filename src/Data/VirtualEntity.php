<?php
/**
 * VirtualEntity class file
 * Generic implementation for entities with no predefined structure (properties)
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use NETopes\Core\Validators\Validator;
use NETopes\Core\AppException;
/**
 * VirtualEntity class
 * Generic implementation for entities with no predefined structure (properties)
 * @package  NETopes\Core\App
 */
class VirtualEntity {
    /**
     * int Fields/properties naming mode: original
     */
    const ORIGINAL_NAME = 0;
    /**
     * int Fields/properties naming mode: camel case
     */
    const CAMELCASE_NAME = 1;
	/**
     * An array containing the entity data.
     * @var array
     */
	protected $data;
	/**
     * Naming mode (original/camelcase).
     * @var int
     */
	protected $namingMode = VirtualEntity::CAMELCASE_NAME;
	/**
     * Strict mode onn/off.
     * @var bool
     */
	protected $strictMode = TRUE;
	/**
	 * Initializes a new VirtualEntity.
	 * @param array $data
	 * @param int   $namingMode
	 * @param bool  $strictMode
	 */
    public function __construct(array $data = [],$namingMode = self::CAMELCASE_NAME,$strictMode = TRUE) {
        $this->data = $data;
        $this->namingMode = $namingMode;
        $this->strictMode = $strictMode;
    }//END public function __construct
	/**
	 * VirtualEntity dynamic property getter
	 * @param  string $name The name of the property
	 * @return mixed Returns the value of the property
	 * @throws \NETopes\Core\AppException
	 */
	public function __get($name) {
		$key = $this->namingMode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if(is_array($this->data) && array_key_exists($key,$this->data)) { return $this->data[$key]; }
		elseif(!$this->strictMode) { return NULL; }
		throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
	}//END public function __get
	/**
	 * VirtualEntity dynamic method call
	 * @param string $name
	 * @param array  $arguments
	 * @return mixed
	 * @throws \NETopes\Core\AppException
	 */
	public function __call($name,array $arguments) {
		if(strtolower(substr($name,0,3))==='get') {
			$prop_name = substr($name,3);
			$strict = is_array($arguments) ? get_array_value($arguments,2,FALSE,'bool') : $this->strictMode;
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
		$defaultValue = get_array_value($arguments,0,NULL,'isset');
		$validation = get_array_value($arguments,1,NULL,'is_string');
		return $this->GetPropertyValue($prop_name,$strict,$defaultValue,$validation);
	}//END public function __call
	/**
     * {@inheritDoc}
     */
    public function set($key,$value) {
        $this->data[$key] = $value;
    }
	/**
	 * Get property value by name
	 * @param string $name
	 * @param null   $defaultValue
	 * @param null   $validation
	 * @param bool   $strict
	 * @return mixed
	 * @throws \NETopes\Core\AppException
	 */
	public function getProperty($name,$defaultValue = NULL,$validation = NULL,$strict = FALSE) {
		return $this->GetPropertyValue($name,$strict,$defaultValue,$validation);
	}//END public function getProperty
	/**
	 * VirtualEntity dynamic getter method
	 * @param  string $name The name of the property
	 * @param bool    $strict
	 * @param null    $defaultValue
	 * @param null    $validation
	 * @return mixed Returns the value of the property
	 * @throws \NETopes\Core\AppException
	 */
	protected function GetPropertyValue($name,$strict = FALSE,$defaultValue = NULL,$validation = NULL) {
		$key = $this->namingMode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if($strict && (!is_array($this->data) || !array_key_exists($key,$this->data))) {
			throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
		}//if(is_array($this->data) && array_key_exists($key,$this->data))
		if(is_null($validation)) {
            if(isset($this->data[$key])) { return $this->data[$key]; }
            return $defaultValue;
        }//if(is_null($validation))
		return Validator::ValidateArrayValue($this->data,$key,$defaultValue,$validation);
	}//END protected function GetPropertyValue
	/**
	 * VirtualEntity dynamic setter method
	 * @param  string $name The name of the property
	 * @param mixed    $value
	 * @param bool    $strict
	 * @return void
	 * @throws \NETopes\Core\AppException
	 */
	protected function SetPropertyValue($name,$value,$strict = FALSE) {
		$key = $this->namingMode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if($strict && (!is_array($this->data) || !array_key_exists($key,$this->data))) {
			throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
		}//if(is_array($this->data) && array_key_exists($key,$this->data))
		if(!is_array($this->data)) { $this->data = []; }
		$this->data[$key] = $value;
	}//END protected function SetPropertyValue
	/**
	 * Check if property exists
	 * @param  string $name The name of the property
	 * @param  bool   $not_null
	 * @return bool Returns TRUE if property exists
	 */
	public function hasProperty($name,$not_null = FALSE): bool {
		$key = $this->namingMode===self::CAMELCASE_NAME ? convert_from_camel_case($name,FALSE) : $name;
		if($not_null) { return array_key_exists($key,$this->data) && isset($this->data[$key]); }
		return array_key_exists($key,$this->data);
	}//END public function hasProperty
	/**
     * Get data array
     * @param bool $recursive
     * @return array
     */
    public function toArray(bool $recursive = FALSE): array {
        if(!$recursive) { return $this->data; }
        $result = [];
        foreach($this->data as $k=>$v) {
            if(is_object($v) && method_exists($v,'toArray')) {
                $result[$k] = $v->toArray($recursive);
            } else {
                $result[$k] = $v;
            }//if(is_object($v) && method_exists($v,'toArray'))
        }//END foreach
        return $result;
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
	 * @param  array|object $data The data to be merged into this instance
	 * @param  bool $recursive
	 * @return bool Returns TRUE on success, FALSE otherwise
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