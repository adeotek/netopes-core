<?php
/**
 * DataSet class file
 *
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for data manipulation (principally for data fetched from databases)
 *
 * @package    NETopes\Core\Classes\Data
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.2.0.0
 * @filesource
 */
namespace NETopes\Core\Classes\Data;
use ArrayIterator;
use Closure;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reverse;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function current;
use function end;
use function in_array;
use function key;
use function next;
use function reset;
use function spl_object_hash;
use function uasort;
/**
 * DataSet class
 *
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for data manipulation (principally for data fetched from databases)
 *
 * @package  NETopes\Core\Classes\Data
 * @access   public
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
	 * @param array $elements
	 * @param int|null  $count
	 */
    public function __construct(array $elements = [],int $count = NULL)
    {
        $this->elements = $elements;
        $this->total_count = $count;
    }
    /**
     * Creates a new instance from the specified elements.
     *
     * This method is provided for derived classes to specify how a new
     * instance should be created when constructor semantics have changed.
     *
     * @param array $elements Elements.
     *
     * @return static
     */
    protected function createFrom(array $elements)
    {
        return new static($elements);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->elements;
    }
    /**
     * {@inheritDoc}
     */
    public function first()
    {
        return reset($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function last()
    {
        return end($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return key($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function next()
    {
        return next($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return current($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        if (! isset($this->elements[$key]) && ! array_key_exists($key, $this->elements)) {
            return null;
        }
        $removed = $this->elements[$key];
        unset($this->elements[$key]);
        return $removed;
    }
    /**
     * {@inheritDoc}
     */
    public function removeElement($element)
    {
        $key = array_search($element, $this->elements, true);
        if ($key === false) {
            return false;
        }
        unset($this->elements[$key]);
        return true;
    }
    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }
    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        if (! isset($offset)) {
            $this->add($value);
            return;
        }
        $this->set($offset, $value);
    }
    /**
     * Required by interface ArrayAccess.
     *
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
    /**
     * {@inheritDoc}
     */
    public function containsKey($key)
    {
        return isset($this->elements[$key]) || array_key_exists($key, $this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function contains($element)
    {
        return in_array($element, $this->elements, true);
    }
    /**
     * {@inheritDoc}
     */
    public function exists(Closure $p)
    {
        foreach ($this->elements as $key => $element) {
            if ($p($key, $element)) {
                return true;
            }
        }
        return false;
    }
    /**
     * {@inheritDoc}
     */
    public function indexOf($element)
    {
        return array_search($element, $this->elements, true);
    }
    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        return $this->elements[$key] ?? null;
    }
    /**
     * {@inheritDoc}
     */
    public function getKeys()
    {
        return array_keys($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function getValues()
    {
        return array_values($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->elements);
    }
    /**
     * @var int|null Elements total count
     */
    public function setTotalCount($value)
    {
        $this->total_count = $value;
    }
	/**
	 * @param bool $safe
	 * @return int|null Elements total count
	 */
    public function getTotalCount($safe = TRUE)
    {
        if(!$safe) { return $this->total_count; }
        return is_numeric($this->total_count) && $this->total_count>0 ? $this->total_count : count($this->elements);
    }
    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        $this->elements[$key] = $value;
    }
    /**
     * {@inheritDoc}
     */
    public function add($element)
    {
        $this->elements[] = $element;
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return empty($this->elements);
    }
    /**
     * Required by interface IteratorAggregate.
     *
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }
    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function map(Closure $func)
    {
        return $this->createFrom(array_map($func, $this->elements));
    }
    /**
     * {@inheritDoc}
     *
     * @return static
     */
    public function filter(Closure $p)
    {
        return $this->createFrom(array_filter($this->elements, $p));
    }
    /**
     * {@inheritDoc}
     */
    public function forAll(Closure $p)
    {
        foreach ($this->elements as $key => $element) {
            if (! $p($key, $element)) {
                return false;
            }
        }
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function partition(Closure $p)
    {
        $matches = $noMatches = [];
        foreach ($this->elements as $key => $element) {
            if ($p($key, $element)) {
                $matches[$key] = $element;
            } else {
                $noMatches[$key] = $element;
            }
        }
        return [$this->createFrom($matches), $this->createFrom($noMatches)];
    }
    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__.'@'.spl_object_hash($this);
    }
    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->elements = [];
    }
    /**
     * {@inheritDoc}
     */
    public function slice($offset, $length = null)
    {
        return array_slice($this->elements, $offset, $length, true);
    }

    public function jsonSerialize()
    {
        return json_encode($this->elements);
    }
}//END class DataSet implements Collection
?>