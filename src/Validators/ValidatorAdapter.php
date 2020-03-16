<?php
/**
 * Validator adapter class file
 * Class containing methods for validating values
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.6
 * @filesource
 */
namespace NETopes\Core\Validators;
use DateTime;
use Exception;
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
     * @throws \NETopes\Core\AppException
     */
    public static final function Validate($value,$defaultValue=NULL,?string $validation=NULL,?string $sourceFormat=NULL,bool &$isValid=FALSE) {
        $value=strlen($sourceFormat) ? Validator::ConvertValue($value,$sourceFormat) : $value;
        if(!strlen($validation)) {
            if(isset($validation)) {
                $isValid=isset($value);
                return $value ?? $defaultValue;
            }//if(isset($validation))
            $isValid=TRUE;
            return $value;
        }//if(!strlen($validation))
        if(substr($validation,0,1)==='?' && is_null($value)) {
            $isValid=TRUE;
            return NULL;
        }//if(substr($validation,0,1)==='?' && is_null($value))
        $method=convert_to_camel_case(trim($validation,'? '));
        if(!method_exists(static::class,$method)) {
            NApp::Elog('Invalid validator adapter method ['.static::class.'::'.$method.']!');
            $isValid=isset($value);
            return $value ?? $defaultValue;
        }//if(!method_exists(static::class,$method))
        $isValid=static::$method($value);
        return ($isValid ? $value : $defaultValue);
    }//END public static final function Validate

    /**
     * @param mixed $value
     * @return bool
     */
    public static function Bool(&$value): bool {
        if(is_null($value)) {
            return FALSE;
        }
        $value=(strtolower($value)=='true' ? TRUE : (strtolower($value)=='false' ? FALSE : (bool)$value));
        return TRUE;
    }//END public static function Bool

    /**
     * @param mixed $value
     * @return bool
     */
    public static function Isset(&$value): bool {
        return isset($value);
    }//END public static function Isset

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsBool(&$value): bool {
        return is_bool($value);
    }//END public static function IsBool

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsBoolean(&$value): bool {
        return is_bool($value);
    }//END public static function IsBoolean

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsObject(&$value): bool {
        return is_object($value);
    }//END public static function IsObject

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsScalar(&$value): bool {
        return is_scalar($value);
    }//END public static function IsScalar

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsString(&$value): bool {
        if(!is_scalar($value)) {
            return FALSE;
        }
        $value=strval($value);
        return TRUE;
    }//END public static function IsString

    /**
     * @param mixed $value
     * @return bool
     */
    public static function TrimIsString(&$value): bool {
        if(!is_scalar($value)) {
            return FALSE;
        }
        $value=trim(strval($value));
        return TRUE;
    }//END public static function TrimIsString

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNotemptyString(&$value): bool {
        if(!is_scalar($value)) {
            return FALSE;
        }
        $value=strval($value);
        return (bool)strlen($value);
    }//END public static function IsNotemptyString

    /**
     * @param mixed $value
     * @return bool
     */
    public static function TrimIsNotemptyString(&$value): bool {
        if(!is_scalar($value)) {
            return FALSE;
        }
        $value=trim(strval($value));
        return (bool)strlen($value);
    }//END public static function IsNotemptyString

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNumeric(&$value): bool {
        if(!is_numeric($value)) {
            return FALSE;
        }
        $value=$value + 0;
        return TRUE;
    }//END public static function IsNumeric

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNot0Numeric(&$value): bool {
        if(!is_numeric($value)) {
            return FALSE;
        }
        $value=$value + 0;
        return $value!==0;
    }//END public static function IsNot0Numeric

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsInteger(&$value): bool {
        if(!is_numeric($value) || !is_integer($value * 1)) {
            return FALSE;
        }
        $value=intval($value);
        return TRUE;
    }//END public static function IsInteger

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNot0Integer(&$value): bool {
        if(!is_numeric($value) || !is_integer($value * 1)) {
            return FALSE;
        }
        $value=intval($value);
        return $value!==0;
    }//END public static function IsNot0Integer

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsFloat(&$value): bool {
        if(!is_numeric($value) || !is_float($value * 1)) {
            return FALSE;
        }
        $value=floatval($value);
        return TRUE;
    }//END public static function IsFloat

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNot0Float(&$value): bool {
        if(!is_numeric($value) || !is_float($value * 1)) {
            return FALSE;
        }
        $value=floatval($value);
        return $value!==0;
    }//END public static function IsNot0Float

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsArray(&$value): bool {
        return is_array($value);
    }//END public static function IsArray

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNotemptyArray(&$value): bool {
        return is_array($value) && count($value);
    }//END public static function IsNotemptyArray

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsCollection(&$value): bool {
        return is_iterable($value);
    }//END public static function IsCollection

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNotemptyCollection(&$value): bool {
        if(!is_iterable($value)) {
            return FALSE;
        }
        return (bool)count($value);
    }//END public static function IsNotemptyCollection

    /**
     * @param mixed $value
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public static function IsDatetime(&$value): bool {
        $value=Validator::ConvertDateTimeToObject($value);
        return ($value instanceof DateTime);
    }//END public static function IsDatetime

    /**
     * @param mixed $value
     * @return bool
     * @throws \NETopes\Core\AppException
     */
    public static function IsTime(&$value): bool {
        if(is_numeric($value)) {
            $value=Validator::ConvertTimestampToDatetime($value);
        } else {
            $value=Validator::ConvertDateTimeToObject($value,'H:i:s','+00:00',FALSE);
        }//if(is_numeric($value))
        return ($value instanceof DateTime);
    }//END public static function IsTime

    /**
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public static function DbDatetime(&$value): bool {
        $value=Validator::ConvertDateTimeToDbFormat($value);
        return (is_string($value) && strlen($value));
    }//END public static function DbDatetime

    /**
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public static function DbDate(&$value): bool {
        $value=Validator::ConvertDateTimeToDbFormat($value,NULL,NULL,TRUE);
        return (is_string($value) && strlen($value));
    }//END public static function DbDate

    /**
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public static function AppDatetime(&$value): bool {
        $value=Validator::ConvertDateTimeToAppFormat($value);
        return (is_string($value) && strlen($value));
    }//END public static function AppDatetime

    /**
     * @param mixed $value
     * @return bool
     * @throws \Exception
     */
    public static function AppDate(&$value): bool {
        $value=Validator::ConvertDateTimeToAppFormat($value,NULL,TRUE);
        return (is_string($value) && strlen($value));
    }//END public static function AppDate

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsEmail(&$value): bool {
        if(!is_string($value)) {
            return FALSE;
        }
        $rawValue=trim($value);
        $value=filter_var($rawValue,FILTER_VALIDATE_EMAIL);
        return ($rawValue===$value);
    }//END public static function IsEmail

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsEmailOrEmpty(&$value): bool {
        if(!is_string($value)) {
            return FALSE;
        }
        $rawValue=$value=trim($value);
        if(!strlen($value)) {
            return TRUE;
        }
        $value=filter_var($rawValue,FILTER_VALIDATE_EMAIL);
        return ($rawValue===$value);
    }//END public static function IsEmailOrEmpty

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsPhone(&$value): bool {
        if(!is_string($value)) {
            return FALSE;
        }
        $value=trim($value);
        if(!preg_match('/^(?!(?:\d*-){5,})(?!(?:\d* ){5,})\+?[\d\- \.]+$/',$value)) {
            return FALSE;
        }
        return (strlen(preg_replace('/[^0-9]/','',$value))>2);
    }//END public static function IsPhone

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsPhoneOrEmpty(&$value): bool {
        if(!is_string($value)) {
            return FALSE;
        }
        $value=trim($value);
        if(!strlen($value)) {
            return TRUE;
        }
        if(!preg_match('/^(?!(?:\d*-){5,})(?!(?:\d* ){5,})\+?[\d\- \.]+$/',$value)) {
            return FALSE;
        }
        return (strlen(preg_replace('/[^0-9]/','',$value))>2);
    }//END public static function IsPhoneOrEmpty

    /**
     * @param mixed       $value
     * @param string|null $quoteReplacer
     * @return bool
     */
    public static function ToJson(&$value,?string $quoteReplacer=NULL): bool {
        if(!is_string($value) || !strlen($value)) {
            return FALSE;
        }
        if(strlen($quoteReplacer)) {
            $value=str_replace($quoteReplacer,'"',$value);
        }
        try {
            $value=json_decode($value,TRUE);
            if(json_last_error()!=JSON_ERROR_NONE) {
                throw new Exception('JSON error: '.json_last_error());
            }
        } catch(Exception $e) {
            $value=NULL;
            NApp::Elog($e);
        }//END try
        return is_array($value);
    }//END public static function ToJson

    /**
     * @param mixed $value
     * @return bool
     */
    public static function CustomToJson(&$value): bool {
        return static::ToJson($value,'``');
    }//END public static function ToJson

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsStringFromCkeditor(&$value): bool {
        $result=static::IsString($value);
        if($result && strlen($value)) {
            $value=str_replace('``','"',$value);
        }
        return $result;
    }//END public static function IsStringFromCkeditor

    /**
     * @param mixed $value
     * @return bool
     */
    public static function TrimIsStringFromCkeditor(&$value): bool {
        $result=static::TrimIsString($value);
        if($result && strlen($value)) {
            $value=str_replace('``','"',$value);
        }
        return $result;
    }//END public static function TrimIsStringFromCkeditor

    /**
     * @param mixed $value
     * @return bool
     */
    public static function IsNotemptyStringFromCkeditor(&$value): bool {
        $result=static::IsNotemptyString($value);
        if($result && strlen($value)) {
            $value=str_replace('``','"',$value);
        }
        return $result;
    }//END public static function IsNotemptyStringFromCkeditor

    /**
     * @param mixed $value
     * @return bool
     */
    public static function TrimIsNotemptyStringFromCkeditor(&$value): bool {
        $result=static::TrimIsNotemptyString($value);
        if($result && strlen($value)) {
            $value=str_replace('``','"',$value);
        }
        return $result;
    }//END public static function IsNotemptyStringFromCkeditor
}//END class ValidatorBaseAdapter