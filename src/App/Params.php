<?php
/**
 * Params class file
 *
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for passing variable number of parameters
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.0.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use ArrayIterator;
use Closure;
use NETopes\Core\Data\Collection;
use NETopes\Core\Validators\Validator;
use NETopes\Core\AppException;
/**
 * Params class
 *
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for passing variable number of parameters
 *
 * @package  NETopes\Core\App
 * @access   public
 */
class Params implements Collection {
	/**
     * An array containing the entries of this collection.
     *
     * @var array
     */
    protected $elements;
	/**
	 * Converts a string (custom or json) to array
	 *
	 * @param $input
	 * @return array
	 * @throws \NETopes\Core\AppException
	 */
	public static function ConvertStringToArray($input) {
		if(!is_string($input) || !strlen($input)) { return []; }
		try {
			if(in_array(substr(trim($input),0,1),['{','['])) {
				return json_decode($input,TRUE);
			} else {
				$result = [];
				foreach(explode('~',$input) as $sv) {
					$tmp_arr = null;
					if(strpos($sv,'|')!==FALSE) {
						$tmp_arr = explode('|',$sv);
						$result[$tmp_arr[0]] = str_replace(array('^[!]^','^[^]^'),array('|','~'),$tmp_arr[1]);
					}else{
						$result[] = str_replace(array('^[!]^','^[^]^'),array('|','~'),$sv);
					}//if(strpos($sv,'|')!==FALSE)
				}//END foreach
				return $result;
			}//if(in_array(substr(trim($input),0,1),['{','[']))
		} catch(\Exception $e) {
			throw new AppException($e->getMessage(),E_ERROR,1);
		}//END try
	}//END public static function ConvertStringToArray
	/**
	 * Initializes a new DataSet.
	 *
	 * @param mixed $params
	 * @throws \NETopes\Core\AppException
	 */
    public function __construct($params = NULL) {
        if(is_null($params)) {
            $this->elements = [];
        } elseif(is_array($params)) {
            $this->elements = $params;
        } elseif(is_string($params)) {
			$this->elements = self::ConvertStringToArray($params);
        }//if(is_null($params))
    }//END public function __construct
	/**
	 * Creates a new instance from the specified elements.
	 *
	 * This method is provided for derived classes to specify how a new
	 * instance should be created when constructor semantics have changed.
	 *
	 * @param array $elements Elements.
	 *
	 * @return static
	 * @throws \NETopes\Core\AppException
	 */
    protected function createFrom(array $elements)
    {
        return new static($elements);
    }
    /**
     * {@inheritDoc}
     */
    public function toArray(?int $keysCase = NULL): ?array
    {
		if(!is_array($this->elements)) { return NULL; }
		if(is_null($keysCase)) { return $this->elements; }
        return array_change_key_case_recursive($this->elements,$keysCase);
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
	 * Check if property exists
	 *
	 * @param  string $name The name of the property
	 * @param  bool   $not_null
	 * @return bool Returns TRUE if property exists
	 * @access public
	 */
	public function hasProperty($name,$not_null = FALSE): bool {
		if($not_null) { return array_key_exists($name,$this->elements) && isset($this->elements[$name]); }
		return array_key_exists($name,$this->elements);
	}//END public function hasProperty
    /**
	 * @param string|int  $key
	 * @param string|null $validation
	 * @param string|null $failMessage
	 * @return mixed
     * @throws \NETopes\Core\AppException
	 */
    public function getOrFail($key,?string $validation = NULL,?string $failMessage = NULL)
    {
        $result = Validator::ValidateArrayValue($this->elements,$key,NULL,$validation);
        if(is_null($result)) {
            $dbgTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,1);
            throw new AppException($failMessage??'Invalid value for: '.print_r($key),E_ERROR,1,get_array_value($dbgTrace,[0,'file'],__FILE__,'is_string'),get_array_value($dbgTrace,[0,'line'],__LINE__,'is_string'));
        }//if(is_null($result))
        return $result;
    }
    /**
     * @param string|int  $key
     * @param mixed       $defaultValue
     * @param string|null $validation
     * @param string|null $sourceFormat
     * @param bool        $isValid
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function safeGet($key,$defaultValue = NULL,?string $validation = NULL,?string $sourceFormat = NULL,bool &$isValid = TRUE) {
        return Validator::ValidateArrayValue($this->elements,$key,$defaultValue,$validation,$sourceFormat,$isValid);
    }
    /**
	 * @param string|int  $key
	 * @param mixed       $defaultValue
     * @param null        $format
	 * @param string|null $validation
	 * @param string|null $sub_key
	 * @return mixed
	 */
    public function safeGetValue($key,$defaultValue = NULL,$format = NULL,$validation = NULL,$sub_key = NULL)
    {
        \NApp::Wlog('Deprecated method [Params::safeGetValue] usage: '.print_r(call_back_trace(1,NULL),1));
        if(!strlen($validation)) {
            if(strlen($format)) {
                $validation = in_array(substr($format,0,2),['is','bo']) ? $format : 'is_'.$format;
            } else {
                $validation = 'isset';
            }
        }
        if(isset($sub_key)) { return get_array_value($this->elements,[$key,$sub_key],$defaultValue,$validation); }
        return get_array_value($this->elements,$key,$defaultValue,$validation);
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
     * @throws \NETopes\Core\AppException
     */
    public function map(Closure $func)
    {
        return $this->createFrom(array_map($func, $this->elements));
    }
    /**
     * {@inheritDoc}
     *
     * @return static
     * @throws \NETopes\Core\AppException
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
    /**
	 * Merge an array or a Param instance to current instance
	 *
	 * @param  array|object $data The data to be merged into this instance
	 * @param  bool $recursive
	 * @return bool Returns TRUE on success, FALSE otherwise
	 * @access public
	 */
	public function merge($data,bool $recursive = FALSE)
	{
		if(is_object($data) && count($data)) {
			if(!is_array($this->elements)) { $this->elements = []; }
			if($recursive) {
				$this->elements = array_merge_recursive($this->data,$data->toArray());
			} else {
				$this->elements = array_merge($this->elements,$data->toArray());
			}//if($recursive)
		} elseif(is_array($data) && count($data)) {
			if(!is_array($this->elements)) { $this->elements = []; }
			if($recursive) {
				$this->elements = array_merge_recursive($this->elements,$data);
			} else {
				$this->elements = array_merge($this->elements,$data);
			}//if($recursive)
		} else {
			return FALSE;
		}//if(is_object($data) && count($data))
		return TRUE;
	}//END public function merge
}//class Params  implements Collection