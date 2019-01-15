<?php
/**
 * Validator adapter class file
 *
 * Class containing methods for validating values
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.6.0.0
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
    use TBaseValidator;
    /**
     * Validate value
     *
     * @param mixed       $value
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param string|null $sourceFormat
     * @param bool        $isValid
     * @return mixed
     * @throws \NETopes\Core\AppException
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
     * @throws \NETopes\Core\AppException
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
     * @throws \Exception
     */
    public static function AppDatetime(&$value): bool {
        $value = Validator::ConvertDateTimeToAppFormat($value);
        return (is_string($value) && strlen($value));
    }//END public static function AppDatetime
    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public static function AppDate(&$value): bool {
        $value = Validator::ConvertDateTimeToAppFormat($value,NULL,TRUE);
        return (is_string($value) && strlen($value));
    }//END public static function AppDate
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