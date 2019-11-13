<?php
/**
 * ControlBuilder abstract class file
 *
 * @package    NETopes\Core\Controls
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.9.3
 * @filesource
 */
namespace NETopes\Core\Controls;

use NETopes\Core\AppException;

/**
 * Class ControlBuilder
 *
 * @package NETopes\Core\Controls
 */
abstract class ControlBuilder {

    /**
     * @var    array params array
     */
    protected $params=[];

    /**
     * BasicForm class constructor method
     *
     * @param array $params Parameters array
     * @return void
     */
    public function __construct($params=NULL) {
        if(is_array($params)) {
            $this->params=$params;
        }
    }//END public function __construct

    /**
     * @return array
     */
    public function GetParams(): array {
        return $this->params;
    }//END public function GetParams

    /**
     * @param array $params
     */
    public function SetParams(array $params): void {
        $this->params=$params;
    }//END public function SetParams

    /**
     * @param string $name
     * @param mixed  $value
     * @throws \NETopes\Core\AppException
     */
    public function AddParam(string $name,$value): void {
        if(!is_array($this->params)) {
            $this->params=[];
        }
        if(array_key_exists($name,$this->params)) {
            throw new AppException('Unable to add parameter ['.$name.'], already exists!');
        }
        $this->params[$name]=$value;
    }//END public function AddParam

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function SetParam(string $name,$value): void {
        if(!is_array($this->params)) {
            $this->params=[];
        }
        $this->params[$name]=$value;
    }//END public function SetParam

    /**
     * @param string $name
     */
    public function UnsetParam(string $name): void {
        if(!is_array($this->params)) {
            $this->params=[];
        }
        unset($this->params[$name]);
    }//END public function UnsetParam

    /**
     * @return array
     */
    abstract public function GetConfig(): array;
}//END abstract class ControlBuilder