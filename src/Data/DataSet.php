<?php
/**
 * DataSet class file
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for data manipulation (principally for data fetched from databases)
 *
 * @package    NETopes\Core\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\Data;
use ArrayIterator;
use Closure;
use NETopes\Core\Validators\Validator;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_search;
use function array_slice;
use function array_values;
use function asort;
use function count;
use function current;
use function end;
use function in_array;
use function key;
use function ksort;
use function next;
use function reset;
use function spl_object_hash;

/**
 * DataSet class
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for data manipulation (principally for data fetched from databases)
 *
 * @package  NETopes\Core\Data
 */
class DataSet implements Collection {
    /**
     * An array containing the entries of this collection.
     *
     * @var array
     */
    protected $elements;
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
        $this->elements=$elements ?? [];
        $this->total_count=$count;
    }

    /**
     * Creates a new instance from the specified elements.
     * This method is provided for derived classes to specify how a new
     * instance should be created when constructor semantics have changed.
     *
     * @param array $elements Elements.
     * @return static
     */
    protected function createFrom(array $elements) {
        return new static($elements);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(bool $recursive=FALSE): array {
        if(!$recursive) {
            return $this->elements;
        }
        $result=[];
        foreach($this->elements as $k=>$v) {
            if(is_object($v) && method_exists($v,'toArray')) {
                $result[$k]=$v->toArray($recursive);
            } else {
                $result[$k]=$v;
            }//if(is_object($v) && method_exists($v,'toArray'))
        }//END foreach
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function first() {
        return reset($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function last() {
        return end($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function key() {
        return key($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function next() {
        return next($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function current() {
        return current($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key) {
        if(!isset($this->elements[$key]) && !array_key_exists($key,$this->elements)) {
            return NULL;
        }
        $removed=$this->elements[$key];
        unset($this->elements[$key]);
        return $removed;
    }

    /**
     * {@inheritDoc}
     */
    public function removeElement($element) {
        $key=array_search($element,$this->elements,TRUE);
        if($key===FALSE) {
            return FALSE;
        }
        unset($this->elements[$key]);
        return TRUE;
    }

    /**
     * Required by interface ArrayAccess.
     * {@inheritDoc}
     */
    public function offsetExists($offset) {
        return $this->containsKey($offset);
    }

    /**
     * Required by interface ArrayAccess.
     * {@inheritDoc}
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    /**
     * Required by interface ArrayAccess.
     * {@inheritDoc}
     */
    public function offsetSet($offset,$value) {
        if(!isset($offset)) {
            $this->add($value);
            return;
        }
        $this->set($offset,$value);
    }

    /**
     * Required by interface ArrayAccess.
     * {@inheritDoc}
     */
    public function offsetUnset($offset) {
        $this->remove($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function containsKey($key) {
        return isset($this->elements[$key]) || array_key_exists($key,$this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($element) {
        return in_array($element,$this->elements,TRUE);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(Closure $p) {
        foreach($this->elements as $key=>$element) {
            if($p($key,$element)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * {@inheritDoc}
     */
    public function indexOf($element) {
        return array_search($element,$this->elements,TRUE);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key) {
        return $this->elements[$key] ?? NULL;
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
     * {@inheritDoc}
     */
    public function getKeys() {
        return array_keys($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function getValues() {
        return array_values($this->elements);
    }

    /**
     * {@inheritDoc}
     */
    public function count() {
        return count($this->elements);
    }

    /**
     * @var int|null Elements total count
     */
    public function setTotalCount($value) {
        $this->total_count=$value;
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
     * {@inheritDoc}
     */
    public function set($key,$value) {
        $this->elements[$key]=$value;
    }

    /**
     * {@inheritDoc}
     */
    public function add($element,bool $first=FALSE): bool {
        if($first) {
            array_unshift($this->elements,$element);
        } else {
            $this->elements[]=$element;
        }//if($first)
        return TRUE;
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty() {
        return empty($this->elements);
    }

    /**
     * Required by interface IteratorAggregate.
     * {@inheritDoc}
     */
    public function getIterator() {
        return new ArrayIterator($this->elements);
    }

    /**
     * {@inheritDoc}
     * @return static
     */
    public function map(Closure $func) {
        return $this->createFrom(array_map($func,$this->elements));
    }

    /**
     * {@inheritDoc}
     * @return static
     */
    public function filter(Closure $p) {
        return $this->createFrom(array_filter($this->elements,$p));
    }

    /**
     * {@inheritDoc}
     */
    public function forAll(Closure $p) {
        foreach($this->elements as $key=>$element) {
            if(!$p($key,$element)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * {@inheritDoc}
     */
    public function partition(Closure $p) {
        $matches=$noMatches=[];
        foreach($this->elements as $key=>$element) {
            if($p($key,$element)) {
                $matches[$key]=$element;
            } else {
                $noMatches[$key]=$element;
            }
        }
        return [$this->createFrom($matches),$this->createFrom($noMatches)];
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString() {
        return __CLASS__.'@'.spl_object_hash($this);
    }

    /**
     * {@inheritDoc}
     */
    public function clear() {
        $this->elements=[];
    }

    /**
     * {@inheritDoc}
     */
    public function slice($offset,$length=NULL) {
        return array_slice($this->elements,$offset,$length,TRUE);
    }

    /**
     * {@inheritDoc}
     */
    public function asort(int $mode=SORT_REGULAR) {
        asort($this->elements,$mode);
    }

    /**
     * {@inheritDoc}
     */
    public function ksort(int $mode=SORT_NUMERIC) {
        ksort($this->elements,$mode);
    }

    /**
     * {@inheritDoc}
     */
    public function reverse(bool $preserveKeys=FALSE) {
        array_reverse($this->elements,$preserveKeys);
    }

    /**
     * @return false|mixed|string
     */
    public function jsonSerialize() {
        return json_encode($this->elements);
    }

    /**
     * Merge an array or a VirtualEntity instance to current instance
     *
     * @param array|object $data The data to be merged into this instance
     * @param bool         $recursive
     * @return bool Returns TRUE on success, FALSE otherwise
     */
    public function merge($data,bool $recursive=FALSE) {
        if(is_object($data) && count($data)) {
            if(!is_array($this->elements)) {
                $this->elements=[];
            }
            if($recursive) {
                $this->elements=array_merge_recursive($this->elements,$data->toArray());
            } else {
                $this->elements=array_merge($this->elements,$data->toArray());
            }//if($recursive)
        } elseif(is_array($data) && count($data)) {
            if(!is_array($this->elements)) {
                $this->elements=[];
            }
            if($recursive) {
                $this->elements=array_merge_recursive($this->elements,$data);
            } else {
                $this->elements=array_merge($this->elements,$data);
            }//if($recursive)
        } else {
            return FALSE;
        }//if(is_object($data) && count($data))
        return TRUE;
    }//END public function merge
}//END class DataSet implements Collection