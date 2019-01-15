<?php
/**
 * Base Validator trait file
 *
 * Trait containing base validation methods
 *
 * @package    NETopes\Core\Validators
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
 * @filesource
 */
namespace NETopes\Core\Validators;

/**
 * Trait TBaseValidator
 *
 * @package NETopes\Core\Validators
 */
trait TBaseValidator {
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
}//END trait TBaseValidator