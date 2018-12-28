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
     * @param bool        $checkOnly
     * @return mixed
     * @throws \Exception
     * @access public
     * @static
     */
	public static final function Validate($value,$defaultValue = NULL,?string $validation = NULL,?string $sourceFormat = NULL,bool $checkOnly = FALSE) {
		$value = strlen($sourceFormat) ? Validator::ConvertValue($value,$sourceFormat) : $value;
		if(!strlen($validation)) {
		    if($checkOnly) { return isset($validation) ? isset($value) : TRUE; }
		    return isset($validation) ? $value??$defaultValue : $value;
		}//if(!strlen($validation))
		if(substr($validation,0,1)==='?' && is_null($value)) { return $checkOnly ? TRUE : NULL; }
        $method = convert_to_camel_case(trim($validation,'? '));
        if(!method_exists(static::class,$method)) {
            NApp::_Elog('Invalid validator adapter method ['.static::class.'::'.$method.']!');
            if($checkOnly) { return isset($value); }
		    return $value??$defaultValue;
        }//if(!method_exists(static::class,$method))
        if($checkOnly) { return call_user_func(static::class.'::'.$method,$value); }
        $isValid = call_user_func(static::class.'::'.$method,$value);
        return ($isValid ? $value : $defaultValue);
	}//END public static final function Validate

    public static function Bool(&$value): bool {
        if(is_null($value)) { return FALSE; }
        $value = (strtolower($value)=='true' ? TRUE : (strtolower($value)=='false' ? FALSE : (bool) $value));
        return TRUE;
    }//END public static function Bool

    public static function Isset(&$value): bool {
        return isset($value);
    }//END public static function Isset

    public static function IsBool(&$value): bool {
        return is_bool($value);
    }//END public static function IsBool

    public static function IsBoolean(&$value): bool {
        return is_bool($value);
    }//END public static function IsBoolean

    public static function IsObject(&$value): bool {
        return is_object($value);
    }//END public static function IsObject

    public static function IsScalar(&$value): bool {
        return is_scalar($value);
    }//END public static function IsScalar

    public static function IsString(&$value): bool {
        if(!is_scalar($value)) { return FALSE; }
        $value = strval($value);
        return TRUE;
    }//END public static function IsString

    public static function IsNotemptyString(&$value): bool {
        if(!is_scalar($value)) { return FALSE; }
        $value = strval($value);
        return (bool) strlen($value);
    }//END public static function IsNotemptyString

    public static function TrimIsNotemptyString(&$value): bool {
        if(!is_scalar($value)) { return FALSE; }
        $value = trim(strval($value));
        return (bool) strlen($value);
    }//END public static function IsNotemptyString

    public static function IsNumeric(&$value): bool {
        if(!is_numeric($value)) { return FALSE; }
        $value = $value + 0;
        return TRUE;
    }//END public static function IsNumeric

    public static function IsNot0Numeric(&$value): bool {
        if(!is_numeric($value)) { return FALSE; }
        $value = $value + 0;
        return $value!==0;
    }//END public static function IsNot0Numeric

    public static function IsInteger(&$value): bool {
        if(!is_numeric($value) || !is_integer($value*1)) { return FALSE; }
        $value = intval($value);
        return TRUE;
    }//END public static function IsInteger

    public static function IsNot0Integer(&$value): bool {
        if(!is_numeric($value) || !is_integer($value*1)) { return FALSE; }
        $value = intval($value);
        return $value!==0;
    }//END public static function IsNot0Integer

    public static function IsFloat(&$value): bool {
        if(!is_numeric($value) || !is_float($value*1)) { return FALSE; }
        $value = floatval($value);
        return TRUE;
    }//END public static function IsFloat

    public static function IsNot0Float(&$value): bool {
        if(!is_numeric($value) || !is_float($value*1)) { return FALSE; }
        $value = floatval($value);
        return $value!==0;
    }//END public static function IsNot0Float

    public static function IsArray(&$value): bool {
        return is_array($value);
    }//END public static function IsArray

    public static function IsNotemptyArray(&$value): bool {
        return is_array($value) && count($value);
    }//END public static function IsNotemptyArray

    public static function IsCollection(&$value): bool {
        return is_iterable($value);
    }//END public static function IsCollection

    public static function IsNotemptyCollection(&$value): bool {
        if(!is_iterable($value)) { return FALSE; }
        return (bool) count($value);
    }//END public static function IsNotemptyCollection

    public static function IsDatetime(&$value): bool {
        $value = Validator::ConvertDateTimeToObject($value);
        return ($value instanceof \DateTime);
    }//END public static function IsDatetime

    public static function DbDatetime(&$value): bool {
        $value = Validator::ConvertDateTimeToDbFormat($value);
        return (is_string($value) && strlen($value));
    }//END public static function DbDatetime

    public static function DbDate(&$value): bool {
        $value = Validator::ConvertDateTimeToDbFormat($value,NULL,NULL,TRUE);
        return (is_string($value) && strlen($value));
    }//END public static function DbDate
}//END class ValidatorBaseAdapter