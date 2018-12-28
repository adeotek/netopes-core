<?php
/**
 * Validator class file
 *
 * Class containing methods for variable/parameters value validation/formatting/conversion
 *
 * @package    NETopes\Core\Validators
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.3.1.1
 * @filesource
 */
namespace NETopes\Core\Validators;
use PAF\AppException;
use NApp;

/**
 * Class Validator
 *
 * @package NETopes\Core\Validators
 */
class Validator {
    /**
     * @var string|null Converter adapter class
     */
    protected static $converterAdapter = NULL;
    /**
     * @var string Validator adapter class
     */
    protected static $validatorAdapter = ValidatorAdapter::class;
    /**
     * @var string Formatter adapter class
     */
    protected static $formatterAdapter = FormatterAdapter::class;
    /**
     * Get converter adapter class
     *
     * @param string|null $method
     * @return string|null Converter adapter class
     * @throws \PAF\AppException
     */
    public static function GetConverterAdapter(?string $method = NULL): string {
        if(!strlen($method)) { return static::$converterAdapter??ConverterAdapter::class; }
        if(!class_exists(static::$converterAdapter??ConverterAdapter::class) || !method_exists(static::$converterAdapter??ConverterAdapter::class,$method)) { throw new AppException('Invalid converter adapter class/method ['.static::$converterAdapter??ConverterAdapter::class.'::'.$method.']!'); }
        return static::$converterAdapter??ConverterAdapter::class.'::'.$method;
    }//END public static function GetConverterAdapter
    /**
     * @param string|null $converterAdapter Converter adapter class
     * @throws \PAF\AppException
     */
    public static function SetConverterAdapter(?string $converterAdapter): void {
        if(isset($converterAdapter) && !class_exists($converterAdapter)) { throw new AppException('Invalid converter adapter class!'); }
        static::$converterAdapter = $converterAdapter;
    }//END public static function SetConverterAdapter
    /**
     * Get validator adapter class
     *
     * @param string|null $method
     * @return string|null Validator adapter class
     * @throws \PAF\AppException
     */
    public static function GetValidatorAdapter(?string $method = NULL): string {
        if(!strlen($method)) { return static::$validatorAdapter??ValidatorAdapter::class; }
        if(!class_exists(static::$validatorAdapter??ValidatorAdapter::class) || !method_exists(static::$validatorAdapter??ValidatorAdapter::class,$method)) { throw new AppException('Invalid validator adapter class/method ['.static::$validatorAdapter??ValidatorAdapter::class.'::'.$method.']!'); }
        return static::$validatorAdapter??ValidatorAdapter::class.'::'.$method;
    }//END public static function GetValidatorAdapter
    /**
     * @param string|null $validatorAdapter Validator adapter class
     * @throws \PAF\AppException
     */
    public static function SetValidatorAdapter(?string $validatorAdapter): void {
        if(isset($validatorAdapter) && !class_exists($validatorAdapter)) { throw new AppException('Invalid validator adapter class!'); }
        static::$validatorAdapter = $validatorAdapter;
    }//END public static function SetValidatorAdapter
    /**
     * Get formatter adapter class
     *
     * @param string|null $method
     * @return string|null Formatter adapter class
     * @throws \PAF\AppException
     */
    public static function GetFormatterAdapter(?string $method = NULL): string {
        if(!strlen($method)) { return static::$formatterAdapter??FormatterAdapter::class; }
        if(!class_exists(static::$formatterAdapter??FormatterAdapter::class) || !method_exists(static::$formatterAdapter??FormatterAdapter::class,$method)) { throw new AppException('Invalid formatter adapter class/method ['.static::$formatterAdapter??FormatterAdapter::class.'::'.$method.']!'); }
        return static::$formatterAdapter??FormatterAdapter::class.'::'.$method;
    }//END public static function GetFormatterAdapter
    /**
     * @param string|null $formatterAdapter Formatter adapter class
     * @throws \PAF\AppException
     */
    public static function SetFormatterAdapter(?string $formatterAdapter): void {
        if(isset($formatterAdapter) && !class_exists($formatterAdapter)) { throw new AppException('Invalid formatter adapter class!'); }
        static::$formatterAdapter = $formatterAdapter;
    }//END public static function SetFormatterAdapter
    /**
     * Magic static call methods from one of the 3 adapters
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \PAF\AppException
     */
    public static function __callStatic($name,$arguments) {
        if(substr($name,0,6)==='Format') {
            $adapter = static::GetFormatterAdapter(substr($name,6));
        } elseif(substr($name,0,7)==='Convert') {
            $adapter = static::GetFormatterAdapter(substr($name,7));
        } else {
            $adapter = static::GetFormatterAdapter((substr($name,0,8)==='Validate' ? substr($name,8) : $name));
        }//if(substr($name,0,6)==='Format')
        return call_user_func_array($adapter,$arguments);
    }//END public static function __callStatic
    /**
     * Validate parameter value
     *
     * @param mixed       $value
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param string|null $format
     * @param bool        $checkOnly
     * @return mixed
     * @throws \Exception
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static function ValidateParam($value,$defaultValue = NULL,?string $validation = NULL,?string $format = NULL,bool $checkOnly = FALSE) {
        return call_user_func(static::GetValidatorAdapter('Validate'),$value,$defaultValue,$validation,$format,$checkOnly);
	}//END public static function ValidateParam
    /**
     * Validate array value
     *
     * @param mixed       $array
     * @param mixed       $key
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param string|null $sourceFormat
     * @param bool        $checkOnly
     * @return mixed
     * @throws \Exception
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static function ValidateArrayParam($array,$key,$defaultValue = NULL,?string $validation = NULL,?string $sourceFormat = NULL,bool $checkOnly = FALSE) {
	    if(is_array($key)) {
	        if(!count($key)) { return $checkOnly ? FALSE : $defaultValue; }
	        $lKey = array_shift($key);
	    } else {
	        $lKey = $key;
	        $key = [];
	    }//if(is_array($key))
	    if(is_null($lKey) || !(is_string($lKey) || is_integer($lKey))) { return $checkOnly ? FALSE : $defaultValue; }
		if(is_array($array)) {
			if(!array_key_exists($lKey,$array)) { return $checkOnly ? FALSE : $defaultValue; }
			if(is_array($key) && count($key)) {
			    $value = static::ValidateArrayParam($array[$lKey],$key,$defaultValue,$validation,$sourceFormat);
			} else {
			    $value = $array[$lKey];
			}//if(is_array($key) && count($key))
		} elseif(is_object($array) && method_exists($array,'toArray')) {
			$lparams = $array->toArray();
			if(!is_array($lparams) || !array_key_exists($lKey,$lparams)) { return $defaultValue; }
			if(is_array($key) && count($key)) {
			    $value = static::ValidateArrayParam($lparams[$lKey],$key,$defaultValue,$validation,$sourceFormat);
			} else {
			    $value = $lparams[$lKey];
			}//if(is_array($key) && count($key))
		} else {
			return $defaultValue;
		}//if(is_array($params))
		return static::ValidateParam($value,$defaultValue,$validation,$sourceFormat,$checkOnly);
	}//END public static function ValidateArrayParam
	/**
	 * Check if parameter value is valid
	 *
	 * @param mixed       $value
	 * @param string|null $validation
     * @param string|null $sourceFormat
	 * @return bool
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function IsValidParam($value,?string $validation = NULL,?string $sourceFormat = NULL): bool {
	    return (bool) call_user_func(static::GetValidatorAdapter('Validate'),$value,$validation,$sourceFormat,TRUE);
	}//END public static function IsValidParam
	/**
	 * Check if array value is valid
	 *
     * @param mixed       $array
     * @param mixed       $key
     * @param string|null $validation
     * @param string|null $sourceFormat
	 * @return bool
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function IsValidArrayValue($array,$key,?string $validation = NULL,?string $sourceFormat = NULL): bool {
	    return (bool) static::ValidateArrayParam($array,$key,NULL,$validation,$sourceFormat,TRUE);
	}//END public static function IsValidArrayParam
	/**
     * Format value
     *
     * @param mixed       $value
     * @param string      $mode
     * @param array|null  $regionals
     * @param string|null $prefix
     * @param string|null $sufix
     * @param string|null $defaultValue
     * @param string|null $validation
     * @param bool        $htmlEntities
     * @return string|null
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static function FormatValue($value,string $mode,?array $regionals = NULL,?string $prefix = NULL,?string $sufix = NULL,?string $defaultValue = NULL,?string $validation = NULL,bool $htmlEntities = FALSE): ?string {
	    return call_user_func(static::GetFormatterAdapter('Format'),$value,$mode,$regionals,$prefix,$sufix,$defaultValue,$validation,$htmlEntities);
	}//END public static function FormatValue
	/**
     * Convert value
     *
     * @param mixed       $value
     * @param string      $mode
     * @param string|null $defaultValue
     * @param string|null $validation
     * @return mixed
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static function ConvertValue($value,string $mode,?string $defaultValue = NULL,?string $validation = NULL) {
	    return call_user_func(static::GetConverterAdapter('Convert'),$value,$mode,$defaultValue,$validation);
	}//END public static function FormatValue
    /**
     * Format numeric value from NETopes format string
     *
     * @param        $value
     * @param string $numberFormat
     * @return string|null
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static function FormatNumberValue($value,string $numberFormat): ?string {
	    return call_user_func(static::GetFormatterAdapter('NumberValue'),$value,$numberFormat);
	}//END public static function FormatNumberValue
    /**
     * Convert number to words representation
     *
     * @param float       $value
     * @param string|null $currency
     * @param string|null $subCurrency
     * @param string|null $langCode
     * @param bool        $useIntl
     * @return string|null
     * @throws \PAF\AppException
     * @access public
     * @static
     */
	public static function ConvertNumberToWords(float $value,?string $currency = NULL,?string $subCurrency = NULL,?string $langCode = NULL,bool $useIntl = TRUE): ?string {
	    return call_user_func(static::GetConverterAdapter('NumberToWords'),$value,$currency,$subCurrency,$langCode,$useIntl);
	}//END public static function ConvertNumberToWord
	/**
	 * Converts a number to standard format
	 *
	 * @param  mixed $number The number to be converted
	 * @param  string|null $decimalSeparator The decimal separator
	 * @param  string|null $groupSeparator The group separator
	 * @return string Returns the number in the database format
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function ConvertNumberToStandardFormat($number,?string $decimalSeparator = NULL,?string $groupSeparator = NULL): ?string {
		return call_user_func(static::GetConverterAdapter('NumberToStandardFormat'),$number,$decimalSeparator,$groupSeparator);
	}//END public static function ConvertNumberToDbFormat
	/**
	 * Converts a datetime string value to DateTime instance
	 *
	 * @param  string     $date Datetime to be converted
	 * @param  null|string $format Format of the date to be converted
	 * @param  null|string $timezone User's timezone
	 * @return \DateTime|null Returns the datetime object or null
     * @throws \PAF\AppException
	 * @access public
	 * @static
	 */
	public static function ConvertDateTimeToObject(?string $date,?string $format = NULL,?string $timezone = NULL): ?\DateTime {
	    return call_user_func(static::GetConverterAdapter('DateTimeToObject'),$date,$format,$timezone);
	}//END public static function ConvertDateTimeToObject
    /**
     * Converts a datetime value to database format
     *
     * @param  mixed       $date Datetime to be converted
     * @param  string|null $timezone User's timezone
     * @param  int|null    $dayPart If set to 0  time is set to "00:00:00.000",
     * if set to 1 time is set to "23:59:59.999", else time is set to original value
     * @param  bool        $dateOnly If set TRUE eliminates the time
     * @return string Returns the datetime in the database format
     * @access public
     * @static
     * @throws \Exception
     */
	public static function ConvertDateTimeToDbFormat($date,?string $timezone = NULL,?int $dayPart = NULL,bool $dateOnly = FALSE): ?string {
		$format = $dateOnly ? static::GetDateFormat(TRUE) : static::GetDateTimeFormat(TRUE);
		return call_user_func(static::GetConverterAdapter('DateTimeToDbFormat'),$date,$format,$timezone,$dayPart,$dateOnly);
	}//END public static function ConvertDateTimeToDbFormat
	/**
     * Convert datetime value to provided format
     *
     * @param  mixed       $date Datetime to be converted
     * @param  string      $format Datetime format string
     * @param  string|null $timezone User's timezone
     * @return string Returns the datetime in the the provided format
     * @throws \Exception
     * @access public
     * @static
     */
	public static function ConvertDateTimeToFormat($date,string $format,?string $timezone = NULL): ?string {
        return call_user_func(static::GetConverterAdapter('DateTimeToFormat'),$date,$format,$timezone);
    }//END public static function ConvertDateTimeToFormat
	/**
     * Convert datetime value to application or provided format
     *
     * @param  mixed       $date Datetime to be converted
     * @param  string|null $timezone User's timezone
     * @param  bool        $dateOnly If set TRUE eliminates the time from value
     * @param  bool        $timeOnly If set TRUE eliminates the date from value
     * @param  string|null $format Datetime format string
     * @return string Returns the datetime in the application or provided format
     * @access public
     * @static
     * @throws \Exception
     */
	public static function ConvertDateTime($date,$timezone = NULL,bool $dateOnly = FALSE,bool $timeOnly = FALSE,?string $format = NULL): ?string {
		if(!strlen($format)) {
			if($timeOnly===TRUE) {
			    $format = NApp::_GetTimeFormat(TRUE);
			} elseif($dateOnly===TRUE) {
			    $format = NApp::_GetDateFormat(TRUE);
			} else {
			    $format = NApp::_GetDateTimeFormat(TRUE);
			}//if($timeOnly===TRUE)
		}//if(!strlen($format))
		return call_user_func(static::GetConverterAdapter('DateTimeToFormat'),$date,$format,$timezone);
	}//END public static function ConvertDateTime
}//END class Validator