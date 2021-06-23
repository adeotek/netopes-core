<?php
/**
 * Params class file
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for passing variable number of parameters
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.1.0.0
 * @filesource
 */
namespace NETopes\Core\App;
use Exception;
use NApp;
use NETopes\Core\AppException;
use NETopes\Core\Data\Collection;
use NETopes\Core\DataHelpers;
use NETopes\Core\Validators\Validator;

/**
 * Params class
 * Wrapper for standard array (implements Traversable, Countable, JsonSerializable, IteratorAggregate, ArrayAccess)
 * to be used for passing variable number of parameters
 *
 * @package  NETopes\Core\App
 */
class Params extends Collection {

    /**
     * Converts a string (custom or json) to array
     *
     * @param $input
     * @return array
     * @throws \NETopes\Core\AppException
     */
    public static function ConvertStringToArray($input) {
        if(!is_string($input) || !strlen($input)) {
            return [];
        }
        try {
            if(in_array(substr(trim($input),0,1),['{','['])) {
                return json_decode($input,TRUE);
            } else {
                $result=[];
                foreach(explode('~',$input) as $sv) {
                    $tmp_arr=NULL;
                    if(strpos($sv,'|')!==FALSE) {
                        $tmp_arr=explode('|',$sv);
                        $result[$tmp_arr[0]]=str_replace(['^[!]^','^[^]^'],['|','~'],$tmp_arr[1]);
                    } else {
                        $result[]=str_replace(['^[!]^','^[^]^'],['|','~'],$sv);
                    }//if(strpos($sv,'|')!==FALSE)
                }//END foreach
                return $result;
            }//if(in_array(substr(trim($input),0,1),['{','[']))
        } catch(Exception $e) {
            throw new AppException($e->getMessage(),E_ERROR,1);
        }//END try
    }//END public static function ConvertStringToArray

    /**
     * Initializes a new DataSet.
     *
     * @param mixed $params
     * @throws \NETopes\Core\AppException
     */
    public function __construct($params=NULL) {
        if(is_null($params)) {
            parent::__construct([]);
            $this->elements=[];
        } elseif(is_array($params)) {
            parent::__construct($params);
        } elseif(is_string($params)) {
            parent::__construct(self::ConvertStringToArray($params));
        }//if(is_null($params))
    }//END public function __construct

    /**
     * @param int|null $keysCase
     * @return array|null
     */
    public function asArray(?int $keysCase=NULL): ?array {
        if(!is_array($this->elements)) {
            return NULL;
        }
        if(is_null($keysCase)) {
            return $this->elements;
        }
        return DataHelpers::changeArrayValuesCase($this->elements,TRUE,$keysCase);
    }

    /**
     * Check if property exists
     *
     * @param string $name The name of the property
     * @param bool   $not_null
     * @return bool Returns TRUE if property exists
     */
    public function hasProperty($name,$not_null=FALSE): bool {
        if($not_null) {
            return array_key_exists($name,$this->elements) && isset($this->elements[$name]);
        }
        return array_key_exists($name,$this->elements);
    }//END public function hasProperty

    /**
     * @param string|int  $key
     * @param string|null $validation
     * @param string|null $failMessage
     * @param int         $severity
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function getOrFail($key,?string $validation=NULL,?string $failMessage=NULL,int $severity=1) {
        if(is_null($validation)) {
            $result=isset($this->elements[$key]) ? $this->elements[$key] : NULL;
        } else {
            $result=Validator::ValidateArrayValue($this->elements,$key,NULL,$validation);
        }//if(is_null($validation))
        if(is_null($result)) {
            $dbgTrace=debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,1);
            throw new AppException($failMessage ?? 'Invalid value for: '.print_r($key),-1,$severity,get_array_value($dbgTrace,[0,'file'],__FILE__,'is_string'),get_array_value($dbgTrace,[0,'line'],__LINE__,'is_string'));
        }//if(is_null($result))
        return $result;
    }

    /**
     * @param string|int|array $key
     * @param mixed            $defaultValue
     * @param string|null      $validation
     * @param string|null      $sourceFormat
     * @param bool             $isValid
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
    public function safeGet($key,$defaultValue=NULL,?string $validation=NULL,?string $sourceFormat=NULL,bool &$isValid=TRUE) {
        if(is_null($validation)) {
            if(isset($this->elements[$key])) {
                $isValid=TRUE;
                return $this->elements[$key];
            }//if(isset($this->elements[$key]))
            $isValid=FALSE;
            return $defaultValue;
        }//if(is_null($validation))
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
    public function safeGetValue($key,$defaultValue=NULL,$format=NULL,$validation=NULL,$sub_key=NULL) {
        NApp::Wlog('Deprecated method ['.self::class.'::safeGetValue] usage: '.print_r(call_back_trace(1,NULL),1));
        if(!strlen($validation)) {
            if(strlen($format)) {
                $validation=in_array(substr($format,0,2),['is','bo']) ? $format : 'is_'.$format;
            } else {
                $validation='isset';
            }
        }
        if(isset($sub_key)) {
            return get_array_value($this->elements,[$key,$sub_key],$defaultValue,$validation);
        }
        return get_array_value($this->elements,$key,$defaultValue,$validation);
    }
}//class Params  implements ICollection