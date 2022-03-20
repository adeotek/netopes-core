<?php
/**
 * DataSet class file
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for data manipulation (principally for data fetched from databases)
 *
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    4.0.0.0
 */

namespace NETopes\Core\Data;
use NETopes\Core\Validators\Validator;
use function array_key_exists;
use function count;

/**
 * DataSet class
 */
class DataSet extends Collection {
    /**
     * Elements total count
     *
     * @var int|null
     */
    public $total_count;

    /**
     * Initializes a new DataSet.
     *
     * @param array    $elements
     * @param int|null $count
     */
    public function __construct(?array $elements=[],int $count=NULL) {
        parent::__construct($elements);
        $this->total_count=$count;
    }

    /**
     * @param mixed       $key
     * @param mixed       $defaultValue
     * @param string|null $validation
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function safeGet($key,$defaultValue=NULL,$validation=NULL) {
        return Validator::ValidateArrayValue($this->elements,$key,$defaultValue,$validation);
    }

    /**
     * @param int|string  $key
     * @param int|string  $property
     * @param mixed       $defaultValue
     * @param string|null $validation
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function safeGetProperty($key,$property,$defaultValue=NULL,$validation=NULL) {
        if(!array_key_exists($key,$this->elements)) {
            return $defaultValue;
        }
        if($this->elements[$key] instanceof IEntity) {
            return $this->elements[$key]->getProperty($property,$defaultValue,$validation);
        }
        if(is_array($this->elements[$key])) {
            return Validator::ValidateArrayValue($this->elements[$key],$property,$defaultValue,$validation);
        }
        return $defaultValue;
    }

    /**
     * @param bool $safe
     * @return int|null Elements total count
     */
    public function getTotalCount($safe=TRUE) {
        if(!$safe) {
            return $this->total_count;
        }
        return is_numeric($this->total_count) && $this->total_count>0 ? $this->total_count : count($this->elements);
    }

    /**
     * @var int|null Elements total count
     */
    public function setTotalCount($value) {
        $this->total_count=$value;
    }
}//END class DataSet extends Collection