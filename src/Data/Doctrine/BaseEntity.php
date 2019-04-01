<?php
/**
 * BaseEntity class file
 * Base for all entities implementations
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data\Doctrine;
use Exception;
use NETopes\Core\AppException;
/**
 * BaseEntity class
 * Base for all entities implementations
 * @package  NETopes\Core\App
 */
abstract class BaseEntity {
    /**
     * @var bool
     */
    public static $isCustomDS = FALSE;

    /**
     * VirtualEntity dynamic method call
     * @param string $name
     * @param array  $arguments
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function __call($name,array $arguments) {
        if(strtolower(substr($name,0,7))==='safeget') {
            $prop_name = substr($name,7);
            $strict = FALSE;
        } else {
            throw new AppException('Undefined method ['.$name.']!',E_ERROR,1);
        }//if(strtolower(substr($name,0,3))==='get')
        $default_value = get_array_value($arguments,0,NULL,'isset');
        $validation = get_array_value($arguments,1,NULL,'is_string');
        return $this->GetPropertyValue($prop_name,$strict,$default_value,$validation);
    }//END public function __call

    /**
     * Get property value by name
     * @param string|null $name
     * @param null   $default_value
     * @param string|null $validation
     * @param bool   $strict
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function getProperty(?string $name,$default_value = NULL,?string $validation = NULL,bool $strict = FALSE) {
        return $this->GetPropertyValue($name,$strict,$default_value,$validation);
    }//END public function getProperty

    /**
     * BaseEntity dynamic getter method
     * @param  string|null $name The name of the property
     * @param bool    $strict
     * @param null    $default_value
     * @param string|null $validation
     * @return mixed Returns the value of the property
     * @throws \NETopes\Core\AppException
     */
    protected function GetPropertyValue(?string $name,bool $strict = FALSE,$default_value = NULL,?string $validation = NULL) {
        $key = convert_to_camel_case($name,TRUE);
        if(method_exists($this,'get'.ucfirst($key))) {
            $getter = 'get'.ucfirst($key);
            $value = $this->$getter();
            return validate_param($value,$default_value,$validation);
        }//if(method_exists($this,'get'.ucfirst($key)))
        if($strict && !property_exists($this,$key)) {
            throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
        }//if($strict && !property_exists($this,$key))
        if(property_exists($this,$key)) { return validate_param($this->$key,$default_value,$validation); }
        return $default_value;
    }//END protected function GetPropertyValue

    /**
     * Check if property exists
     * @param  string $name The name of the property
     * @param  bool   $not_null
     * @return bool Returns TRUE if property exists
     */
    public function hasProperty(?string $name,bool $not_null = FALSE): bool {
        $key = convert_to_camel_case($name,TRUE);
        if($not_null) {
            $value = NULL;
            if(method_exists($this,'get'.ucfirst($key))) {
                $getter = 'get'.ucfirst($key);
                $value = $this->$getter();
            } elseif(property_exists($this,$key)) {
                $value = $this->$key;
            }//if(method_exists($this,'get'.ucfirst($key)))
            return isset($value);
        }//if($not_null)
        return (method_exists($this,'get'.ucfirst($key)) || property_exists($this,$key));
    }//END public function hasProperty

    /**
     * Get data array
     * @param bool $originalNames
     * @return array
     */
    public function toArray(bool $originalNames = FALSE): array {
        $properties = get_object_vars($this);
        if(!is_array($properties) || !count($properties)) { return []; }
        if($originalNames) { return $properties; }
        $result = [];
        foreach($properties as $k=>$v) { $result[convert_from_camel_case($k,FALSE)] = $v; }
        return $result;
    }//END public function toArray

    /**
     * Check if is a new instance
     */
    public function isNew(): bool {
        if(method_exists($this,'getId')) {
            try {
                $result = (!is_numeric($this->getId()) || $this->getId()==0);
            } catch(Exception $e) {
                $result = FALSE;
            }//END try
            return $result;
        }//if(method_exists($this,'getId'))
        return FALSE;
    }//END public function isNew

    /**
     * @param array $params
     * @return $this
     */
    public function setBulkAttributes(array $params) {
        foreach($params as $k=>$v) {
            $mv = 'set'.convert_to_camel_case($k);
            $this->$mv($v);
        }//END foreach
        return $this;
    }//END public function setBulkAttributes
}//END abstract class BaseEntity