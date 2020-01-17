<?php
/**
 * BaseEntity class file
 * Base for all entities implementations
 *
 * @package    NETopes\Core\Data\Doctrine
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data\Doctrine;
use Exception;
use NETopes\Core\AppException;
use NETopes\Core\Data\IEntity;

/**
 * BaseEntity class
 * Base for all entities implementations
 *
 * @package  NETopes\Core\App
 */
abstract class BaseEntity implements IEntity {
    /**
     * @var bool
     */
    public static $isCustomDS=FALSE;

    /**
     * VirtualEntity dynamic method call
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function __call($name,array $arguments) {
        if(strtolower(substr($name,0,7))==='safeget') {
            $prop_name=substr($name,7);
            $strict=FALSE;
        } else {
            throw new AppException('Undefined method ['.$name.']!',E_ERROR,1);
        }//if(strtolower(substr($name,0,3))==='get')
        $default_value=get_array_value($arguments,0,NULL,'isset');
        $validation=get_array_value($arguments,1,NULL,'is_string');
        return $this->GetPropertyValue($prop_name,$strict,$default_value,$validation);
    }//END public function __call

    /**
     * @param string|null $name
     * @param bool|null   $special
     * @return string|null
     */
    protected function convertPropertyName(?string $name,?bool &$special=NULL): ?string {
        if(is_null($name)) {
            return NULL;
        }
        $mainKey=ltrim($name,'_');
        $keyPrefixCount=strlen($name) - strlen($mainKey);
        $key=convert_to_camel_case(rtrim($mainKey,'_'),TRUE);
        if($keyPrefixCount>0) {
            $key=str_repeat('_',$keyPrefixCount).$key;
            $special=TRUE;
        } else {
            $special=FALSE;
        }//if(count($keyComponents))
        return $key;
    }//END protected function convertPropertyName

    /**
     * Get property value by name
     *
     * @param string|null $name
     * @param null        $default_value
     * @param string|null $validation
     * @param bool        $strict
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function getProperty(?string $name,$default_value=NULL,?string $validation=NULL,bool $strict=FALSE) {
        return $this->GetPropertyValue($name,$strict,$default_value,$validation);
    }//END public function getProperty

    /**
     * BaseEntity dynamic getter method
     *
     * @param string|null $name The name of the property
     * @param bool        $strict
     * @param null        $default_value
     * @param string|null $validation
     * @return mixed Returns the value of the property
     * @throws \NETopes\Core\AppException
     */
    protected function GetPropertyValue(?string $name,bool $strict=FALSE,$default_value=NULL,?string $validation=NULL) {
        $special=NULL;
        $key=$this->convertPropertyName($name,$special);
        if(!$special && method_exists($this,'get'.ucfirst($key))) {
            $getter='get'.ucfirst($key);
            $value=$this->$getter();
            return validate_param($value,$default_value,$validation);
        }//if(method_exists($this,'get'.ucfirst($key)))
        if($strict && !property_exists($this,$key)) {
            throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
        }//if($strict && !property_exists($this,$key))
        if(property_exists($this,$key)) {
            return validate_param($this->$key,$default_value,$validation);
        }
        return $default_value;
    }//END protected function GetPropertyValue

    /**
     * Check if property exists
     *
     * @param string $name The name of the property
     * @param bool   $notNull
     * @return bool Returns TRUE if property exists
     */
    public function hasProperty(?string $name,bool $notNull=FALSE): bool {
        $special=NULL;
        $key=$this->convertPropertyName($name,$special);
        if($notNull) {
            $value=NULL;
            if(!$special && method_exists($this,'get'.ucfirst($key))) {
                $getter='get'.ucfirst($key);
                $value=$this->$getter();
            } elseif(property_exists($this,$key)) {
                $value=$this->$key;
            }//if(method_exists($this,'get'.ucfirst($key)))
            return isset($value);
        }//if($notNull)
        return (method_exists($this,'get'.ucfirst($key)) || property_exists($this,$key));
    }//END public function hasProperty

    /**
     * @param string $key
     * @param mixed  $value
     * @throws \NETopes\Core\AppException
     */
    public function set(string $key,$value): void {
        $this->SetPropertyValue($key,$value,FALSE);
    }//END public function set

    /**
     * Set property value by name
     *
     * @param string $name The name of the property
     * @param mixed  $value
     * @param bool   $strict
     * @return void
     * @throws \NETopes\Core\AppException
     */
    public function setProperty(string $name,$value,bool $strict=FALSE): void {
        $this->SetPropertyValue($name,$value,$strict);
    }//END public function setProperty

    /**
     * VirtualEntity dynamic setter method
     *
     * @param string $name The name of the property
     * @param mixed  $value
     * @param bool   $strict
     * @return void
     * @throws \NETopes\Core\AppException
     */
    protected function SetPropertyValue(string $name,$value,bool $strict=FALSE): void {
        $special=NULL;
        $key=$this->convertPropertyName($name,$special);
        $setter='set'.ucfirst($key);
        if(!$special && method_exists($this,$setter)) {
            $this->$setter($value);
            return;
        }
        if(property_exists($this,$key) || !$strict) {
            $this->$key=$value;
            return;
        }//if(method_exists($this,$setter))
        throw new AppException('Undefined property ['.$name.']!',E_ERROR,1);
    }//END protected function SetPropertyValue

    /**
     * Get data array
     *
     * @param bool $originalNames
     * @return array
     */
    public function toArray(bool $originalNames=FALSE): array {
        $properties=get_object_vars($this);
        if(!is_array($properties) || !count($properties)) {
            return [];
        }
        if($originalNames) {
            return $properties;
        }
        $result=[];
        foreach($properties as $k=>$v) {
            $result[convert_from_camel_case($k,FALSE)]=$v;
        }
        return $result;
    }//END public function toArray

    /**
     * Check if is a new instance
     */
    public function isNew(): bool {
        if(method_exists($this,'getId')) {
            try {
                $result=(!is_numeric($this->getId()) || $this->getId()==0);
            } catch(Exception $e) {
                $result=FALSE;
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
            $mv='set'.convert_to_camel_case($k);
            $this->$mv($v);
        }//END foreach
        return $this;
    }//END public function setBulkAttributes
}//END abstract class BaseEntity