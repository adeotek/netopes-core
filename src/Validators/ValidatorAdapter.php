<?php
/**
 * Validator adapter class file
 *
 * Class containing methods for validating values
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.3.1.1
 * @filesource
 */
namespace NETopes\Core\Validators;
use NApp;

/**
 * Class ValidatorAdapter
 *
 * @package NETopes\Core\App
 */
class ValidatorAdapter {
    /**
     * Validate value
     *
     * @param mixed       $value
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param string|null $sourceFormat
     * @param bool        $isValid
     * @return mixed
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static final function Validate($value,$defaultValue = NULL,?string $validation = NULL,?string $sourceFormat = NULL,bool &$isValid = FALSE) {
		$value = strlen($sourceFormat) ? Validator::ConvertValue($value,$sourceFormat) : $value;
		if(!strlen($validation)) {
		    if(isset($validation)) {
		        $isValid = isset($value);
		        return $value??$defaultValue;
		    }//if(isset($validation))
		    $isValid = TRUE;
		    return $value;
		}//if(!strlen($validation))
		if(substr($validation,0,1)==='?' && is_null($value)) {
		    $isValid = TRUE;
		    return NULL;
		}//if(substr($validation,0,1)==='?' && is_null($value))
        $method = convert_to_camel_case(trim($validation,'? '));
        if(!method_exists(static::class,$method)) {
            NApp::_Elog('Invalid validator adapter method ['.static::class.'::'.$method.']!');
            $isValid = isset($value);
		    return $value??$defaultValue;
        }//if(!method_exists(static::class,$method))
        $isValid = static::$method($value);
        return ($isValid ? $value : $defaultValue);
	}//END public static final function Validate
    /**
     * @param $value
     * @return bool
     */
    public static function Bool(&$value): bool {
        if(is_null($value)) { return FALSE; }
        $value = (strtolower($value)=='true' ? TRUE : (strtolower($value)=='false' ? FALSE : (bool) $value));
        return TRUE;
    }//END public static function Bool
    /**
     * @param $value
     * @return bool
     */
    public static function Isset(&$value): bool {
        return isset($value);
    }//END public static function Isset
    /**
     * @param $value
     * @return bool
     */
    public static function IsBool(&$value): bool {
        return is_bool($value);
    }//END public static function IsBool
    /**
     * @param $value
     * @return bool
     */
    public static function IsBoolean(&$value): bool {
        return is_bool($value);
    }//END public static function IsBoolean
    /**
     * @param $value
     * @return bool
     */
    public static function IsObject(&$value): bool {
        return is_object($value);
    }//END public static function IsObject
    /**
     * @param $value
     * @return bool
     */
    public static function IsScalar(&$value): bool {
        return is_scalar($value);
    }//END public static function IsScalar
    /**
     * @param $value
     * @return bool
     */
    public static function IsString(&$value): bool {
        if(!is_scalar($value)) { return FALSE; }
        $value = strval($value);
        return TRUE;
    }//END public static function IsString
    /**
     * @param $value
     * @return bool
     */
    public static function IsNotemptyString(&$value): bool {
        if(!is_scalar($value)) { return FALSE; }
        $value = strval($value);
        return (bool) strlen($value);
    }//END public static function IsNotemptyString
    /**
     * @param $value
     * @return bool
     */
    public static function TrimIsNotemptyString(&$value): bool {
        if(!is_scalar($value)) { return FALSE; }
        $value = trim(strval($value));
        return (bool) strlen($value);
    }//END public static function IsNotemptyString
    /**
     * @param $value
     * @return bool
     */
    public static function IsNumeric(&$value): bool {
        if(!is_numeric($value)) { return FALSE; }
        $value = $value + 0;
        return TRUE;
    }//END public static function IsNumeric
    /**
     * @param $value
     * @return bool
     */
    public static function IsNot0Numeric(&$value): bool {
        if(!is_numeric($value)) { return FALSE; }
        $value = $value + 0;
        return $value!==0;
    }//END public static function IsNot0Numeric
    /**
     * @param $value
     * @return bool
     */
    public static function IsInteger(&$value): bool {
        if(!is_numeric($value) || !is_integer($value*1)) { return FALSE; }
        $value = intval($value);
        return TRUE;
    }//END public static function IsInteger
    /**
     * @param $value
     * @return bool
     */
    public static function IsNot0Integer(&$value): bool {
        if(!is_numeric($value) || !is_integer($value*1)) { return FALSE; }
        $value = intval($value);
        return $value!==0;
    }//END public static function IsNot0Integer
    /**
     * @param $value
     * @return bool
     */
    public static function IsFloat(&$value): bool {
        if(!is_numeric($value) || !is_float($value*1)) { return FALSE; }
        $value = floatval($value);
        return TRUE;
    }//END public static function IsFloat
    /**
     * @param $value
     * @return bool
     */
    public static function IsNot0Float(&$value): bool {
        if(!is_numeric($value) || !is_float($value*1)) { return FALSE; }
        $value = floatval($value);
        return $value!==0;
    }//END public static function IsNot0Float
    /**
     * @param $value
     * @return bool
     */
    public static function IsArray(&$value): bool {
        return is_array($value);
    }//END public static function IsArray
    /**
     * @param $value
     * @return bool
     */
    public static function IsNotemptyArray(&$value): bool {
        return is_array($value) && count($value);
    }//END public static function IsNotemptyArray
    /**
     * @param $value
     * @return bool
     */
    public static function IsCollection(&$value): bool {
        return is_iterable($value);
    }//END public static function IsCollection
    /**
     * @param $value
     * @return bool
     */
    public static function IsNotemptyCollection(&$value): bool {
        if(!is_iterable($value)) { return FALSE; }
        return (bool) count($value);
    }//END public static function IsNotemptyCollection
    /**
     * @param $value
     * @return bool
     * @throws \PAF\AppException
     */
    public static function IsDatetime(&$value): bool {
        $value = Validator::ConvertDateTimeToObject($value);
        return ($value instanceof \DateTime);
    }//END public static function IsDatetime
    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public static function DbDatetime(&$value): bool {
        $value = Validator::ConvertDateTimeToDbFormat($value);
        return (is_string($value) && strlen($value));
    }//END public static function DbDatetime
    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public static function DbDate(&$value): bool {
        $value = Validator::ConvertDateTimeToDbFormat($value,NULL,NULL,TRUE);
        return (is_string($value) && strlen($value));
    }//END public static function DbDate
    /**
     * @param $value
     * @return bool
     */
    public static function IsEmail(&$value): bool {
        if(!is_string($value)) { return FALSE; }
        $rawValue = trim($value);
        $value = filter_var($rawValue,FILTER_VALIDATE_EMAIL);
        return ($rawValue===$value);
    }//END public static function IsEmail
    /**
     * @param $value
     * @return bool
     */
    public static function IsEmailOrEmpty(&$value): bool {
        if(!is_string($value)) { return FALSE; }
        $rawValue = $value = trim($value);
        if(!strlen($value)) { return TRUE; }
        $value = filter_var($rawValue,FILTER_VALIDATE_EMAIL);
        return ($rawValue===$value);
    }//END public static function IsEmailOrEmpty
    /**
     * @param $value
     * @return bool
     */
    public static function IsPhone(&$value): bool {
        if(!is_string($value)) { return FALSE; }
        $value = trim($value);
        if(!preg_match('/^(?!(?:\d*-){5,})(?!(?:\d* ){5,})\+?[\d- .]+$/',$value)) { return FALSE; }
        return (strlen(preg_replace('/[^0-9]/','',$value))>2);
    }//END public static function IsPhone
    /**
     * @param $value
     * @return bool
     */
    public static function IsPhoneOrEmpty(&$value): bool {
        if(!is_string($value)) { return FALSE; }
        $value = trim($value);
        if(!strlen($value)) { return TRUE; }
        if(!preg_match('/^(?!(?:\d*-){5,})(?!(?:\d* ){5,})\+?[\d- .]+$/',$value)) { return FALSE; }
        return (strlen(preg_replace('/[^0-9]/','',$value))>2);
    }//END public static function IsPhoneOrEmpty
}//END class ValidatorBaseAdapter