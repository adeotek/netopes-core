<?php
/**
 * Validator class file
 * Class containing methods for variable/parameters value validation/formatting/conversion
 * @package    NETopes\Core\Validators
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.6
 * @filesource
 */
namespace NETopes\Core\Validators;
use NETopes\Core\AppConfig;
use NETopes\Core\AppException;
use NApp;
/**
 * Class Validator
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
    protected static $validatorAdapter = NULL;
    /**
     * @var string Formatter adapter class
     */
    protected static $formatterAdapter = NULL;
    /**
     * Get converter adapter class
     * @param string|null $method
     * @return string|null Converter adapter class
     * @throws \NETopes\Core\AppException
     * @throws \Exception
     */
    public static function GetConverterAdapter(?string $method = NULL): string {
        $customAdapter = AppConfig::GetValue('converter_adapter_class');
        $class = '\\'.ltrim(static::$converterAdapter??$customAdapter??ConverterAdapter::class,'\\');
        if(!strlen($method)) {
            if(!class_exists($class)) { throw new AppException('Invalid converter adapter class ['.$class.']!'); }
            return $class;
        }//if(!strlen($method))
        if(!class_exists($class) || !method_exists($class,$method)) { throw new AppException('Invalid converter adapter class/method ['.$class.'::'.$method.']!'); }
        return $class.'::'.$method;
    }//END public static function GetConverterAdapter
    /**
     * @param string|null $converterAdapter Converter adapter class
     * @throws \NETopes\Core\AppException
     */
    public static function SetConverterAdapter(?string $converterAdapter): void {
        if(isset($converterAdapter) && !class_exists($converterAdapter) && is_subclass_of($converterAdapter,ConverterAdapter::class)) { throw new AppException('Invalid converter adapter class!'); }
        static::$converterAdapter = $converterAdapter;
    }//END public static function SetConverterAdapter
    /**
     * Get validator adapter class
     * @param string|null $method
     * @return string|null Validator adapter class
     * @throws \NETopes\Core\AppException
     */
    public static function GetValidatorAdapter(?string $method = NULL): string {
        $customAdapter = AppConfig::GetValue('validator_adapter_class');
        $class = '\\'.ltrim(static::$validatorAdapter??$customAdapter??ValidatorAdapter::class,'\\');
        if(!strlen($method)) {
            if(!class_exists($class)) { throw new AppException('Invalid validator adapter class ['.$class.']!'); }
            return $class;
        }//if(!strlen($method))
        if(!class_exists($class) || !method_exists($class,$method)) { throw new AppException('Invalid validator adapter class/method ['.$class.'::'.$method.']!'); }
        return $class.'::'.$method;
    }//END public static function GetValidatorAdapter
    /**
     * @param string|null $validatorAdapter Validator adapter class
     * @throws \NETopes\Core\AppException
     */
    public static function SetValidatorAdapter(?string $validatorAdapter): void {
        if(isset($validatorAdapter) && !class_exists($validatorAdapter) && is_subclass_of($validatorAdapter,ValidatorAdapter::class)) { throw new AppException('Invalid validator adapter class!'); }
        static::$validatorAdapter = $validatorAdapter;
    }//END public static function SetValidatorAdapter
    /**
     * Get formatter adapter class
     * @param string|null $method
     * @return string|null Formatter adapter class
     * @throws \NETopes\Core\AppException
     */
    public static function GetFormatterAdapter(?string $method = NULL): string {
        $customAdapter = AppConfig::GetValue('formatter_adapter_class');
        $class = '\\'.ltrim(static::$formatterAdapter??$customAdapter??FormatterAdapter::class,'\\');
        if(!strlen($method)) {
            if(!class_exists($class)) { throw new AppException('Invalid formatter adapter class ['.$class.']!'); }
            return $class;
        }//if(!strlen($method))
        if(!class_exists($class) || !method_exists($class,$method)) { throw new AppException('Invalid formatter adapter class/method ['.$class.'::'.$method.']!'); }
        return $class.'::'.$method;
    }//END public static function GetFormatterAdapter
    /**
     * @param string|null $formatterAdapter Formatter adapter class
     * @throws \NETopes\Core\AppException
     */
    public static function SetFormatterAdapter(?string $formatterAdapter): void {
        if(isset($formatterAdapter) && !class_exists($formatterAdapter) && is_subclass_of($formatterAdapter,FormatterAdapter::class)) { throw new AppException('Invalid formatter adapter class!'); }
        static::$formatterAdapter = $formatterAdapter;
    }//END public static function SetFormatterAdapter
    /**
     * Magic static call methods from one of the 3 adapters
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \NETopes\Core\AppException
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
     * @param mixed       $value
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param string|null $sourceFormat
     * @param bool        $isValid
     * @return mixed
     * @throws \Exception
     * @throws \NETopes\Core\AppException
     */
	public static function ValidateValue($value,$defaultValue = NULL,?string $validation = NULL,?string $sourceFormat = NULL,bool &$isValid = FALSE) {
	    $adapter = static::GetValidatorAdapter(NULL);
	    if(!method_exists($adapter,'Validate')) { throw new AppException('Invalid validator adapter method ['.$adapter.'::Validate]!'); }
        return $adapter::Validate($value,$defaultValue,$validation,$sourceFormat,$isValid);
	}//END public static function ValidateValue
	/**
	 * Check if parameter value is valid
	 * @param mixed       $value
	 * @param string|null $validation
     * @param string|null $sourceFormat
	 * @return bool
     * @throws \NETopes\Core\AppException
	 */
	public static function IsValidValue($value,?string $validation = NULL,?string $sourceFormat = NULL): bool {
	    $isValid = FALSE;
	    static::ValidateValue($value,NULL,$validation,$sourceFormat,$isValid);
	    return $isValid;
	}//END public static function IsValidValue
    /**
     * Validate array value
     * @param mixed       $array
     * @param mixed       $key
     * @param mixed|null  $defaultValue
     * @param string|null $validation
     * @param string|null $sourceFormat
     * @param bool        $isValid
     * @return mixed
     * @throws \Exception
     * @throws \NETopes\Core\AppException
     */
	public static function ValidateArrayValue($array,$key,$defaultValue = NULL,?string $validation = NULL,?string $sourceFormat = NULL,bool &$isValid = FALSE) {
	    if(is_array($key)) {
	        if(!count($key)) {
	            $isValid = FALSE;
	            return $defaultValue;
	        }//if(!count($key))
	        $lKey = array_shift($key);
	    } else {
	        $lKey = $key;
	        $key = [];
	    }//if(is_array($key))
	    if(is_null($lKey) || !(is_string($lKey) || is_integer($lKey))) {
	        $isValid = FALSE;
	        return $defaultValue;
	    }//if(is_null($lKey) || !(is_string($lKey) || is_integer($lKey)))
		if(is_array($array)) {
			if(!array_key_exists($lKey,$array)) {
			    $isValid = FALSE;
	            return $defaultValue;
			}//if(!array_key_exists($lKey,$array))
			if(is_array($key) && count($key)) {
			    $value = static::ValidateArrayValue($array[$lKey],$key,$defaultValue,$validation,$sourceFormat,$isValid);
			} else {
			    $value = $array[$lKey];
			}//if(is_array($key) && count($key))
		} elseif(is_object($array) && method_exists($array,'toArray')) {
			$lparams = $array->toArray();
			if(!is_array($lparams) || !array_key_exists($lKey,$lparams)) {
			    $isValid = FALSE;
	            return $defaultValue;
			}//if(!is_array($lparams) || !array_key_exists($lKey,$lparams))
			if(is_array($key) && count($key)) {
			    $value = static::ValidateArrayValue($lparams[$lKey],$key,$defaultValue,$validation,$sourceFormat,$isValid);
			} else {
			    $value = $lparams[$lKey];
			}//if(is_array($key) && count($key))
		} else {
			$isValid = FALSE;
	        return $defaultValue;
		}//if(is_array($params))
		return static::ValidateValue($value,$defaultValue,$validation,$sourceFormat,$isValid);
	}//END public static function ValidateArrayValue
	/**
	 * Check if array value is valid
     * @param mixed       $array
     * @param mixed       $key
     * @param string|null $validation
     * @param string|null $sourceFormat
	 * @return bool
     * @throws \NETopes\Core\AppException
	 */
	public static function IsValidArrayValue($array,$key,?string $validation = NULL,?string $sourceFormat = NULL): bool {
	    $isValid = FALSE;
	    static::ValidateArrayValue($array,$key,NULL,$validation,$sourceFormat,$isValid);
	    return $isValid;
	}//END public static function IsValidArrayValue
	/**
     * Format value
     * @param mixed       $value
     * @param string      $mode
     * @param array|null  $regionals
     * @param string|null $prefix
     * @param string|null $sufix
     * @param string|null $defaultValue
     * @param string|null $validation
     * @param bool        $htmlEntities
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
	public static function FormatValue($value,string $mode,?array $regionals = NULL,?string $prefix = NULL,?string $sufix = NULL,?string $defaultValue = NULL,?string $validation = NULL,bool $htmlEntities = FALSE): ?string {
	    return call_user_func(static::GetFormatterAdapter('Format'),$value,$mode,$regionals,$prefix,$sufix,$defaultValue,$validation,$htmlEntities);
	}//END public static function FormatValue
	/**
     * Convert value
     * @param mixed       $value
     * @param string      $mode
     * @param string|null $defaultValue
     * @param string|null $validation
     * @return mixed
     * @throws \NETopes\Core\AppException
     */
	public static function ConvertValue($value,string $mode,?string $defaultValue = NULL,?string $validation = NULL) {
	    return call_user_func(static::GetConverterAdapter('Convert'),$value,$mode,$defaultValue,$validation);
	}//END public static function FormatValue
    /**
     * Format numeric value from NETopes format string
     * @param        $value
     * @param string $numberFormat
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
	public static function FormatNumberValue($value,string $numberFormat): ?string {
	    return call_user_func(static::GetFormatterAdapter('NumberValue'),$value,$numberFormat);
	}//END public static function FormatNumberValue
    /**
     * Convert number to words representation
     * @param float       $value
     * @param string|null $currency
     * @param string|null $subCurrency
     * @param string|null $langCode
     * @param bool        $useIntl
     * @return string|null
     * @throws \NETopes\Core\AppException
     */
	public static function ConvertNumberToWords(float $value,?string $currency = NULL,?string $subCurrency = NULL,?string $langCode = NULL,bool $useIntl = TRUE): ?string {
	    return call_user_func(static::GetConverterAdapter('NumberToWords'),$value,$currency,$subCurrency,$langCode,$useIntl);
	}//END public static function ConvertNumberToWord
	/**
	 * Converts a number to standard format
	 * @param  mixed $number The number to be converted
	 * @param  string|null $decimalSeparator The decimal separator
	 * @param  string|null $groupSeparator The group separator
	 * @return string Returns the number in the database format
     * @throws \NETopes\Core\AppException
	 */
	public static function ConvertNumberToStandardFormat($number,?string $decimalSeparator = NULL,?string $groupSeparator = NULL): ?string {
		return call_user_func(static::GetConverterAdapter('NumberToStandardFormat'),$number,$decimalSeparator,$groupSeparator);
	}//END public static function ConvertNumberToDbFormat
	/**
	 * Converts a datetime string value to DateTime instance
	 * @param  string     $date Datetime to be converted
	 * @param  null|string $format Format of the date to be converted
	 * @param  null|string $timezone User's timezone
	 * @return \DateTime|null Returns the datetime object or null
     * @throws \NETopes\Core\AppException
	 */
	public static function ConvertDateTimeToObject(?string $date,?string $format = NULL,?string $timezone = NULL): ?\DateTime {
	    return call_user_func(static::GetConverterAdapter('DateTimeToObject'),$date,$format,$timezone);
	}//END public static function ConvertDateTimeToObject
    /**
     * Converts a datetime value to database format
     * @param  mixed       $date Datetime to be converted
     * @param  string|null $timezone User's timezone
     * @param  int|null    $dayPart If set to 0  time is set to "00:00:00.000",
     * if set to 1 time is set to "23:59:59.999", else time is set to original value
     * @param  bool        $dateOnly If set TRUE eliminates the time
     * @return string Returns the datetime in the database format
     * @throws \Exception
     */
	public static function ConvertDateTimeToDbFormat($date,?string $timezone = NULL,?int $dayPart = NULL,bool $dateOnly = FALSE): ?string {
		return call_user_func(static::GetConverterAdapter('DateTimeToDbFormat'),$date,NULL,$timezone,$dayPart,$dateOnly);
	}//END public static function ConvertDateTimeToDbFormat
	/**
     * Converts a datetime value to application (user) format
     * @param  mixed       $date Datetime to be converted
     * @param  string|null $timezone User's timezone
     * if set to 1 time is set to "23:59:59.999", else time is set to original value
     * @param  bool        $dateOnly If set TRUE eliminates the time
     * @return string Returns the datetime in the database format
     * @throws \Exception
     */
	public static function ConvertDateTimeToAppFormat($date,?string $timezone = NULL,bool $dateOnly = FALSE): ?string {
		$format = $dateOnly ? NApp::GetDateFormat(TRUE) : NApp::GetDateTimeFormat(TRUE);
		return call_user_func(static::GetConverterAdapter('DateTimeToFormat'),$date,$format,$timezone);
	}//END public static function ConvertDateTimeToAppFormat
	/**
     * Convert datetime value to provided format
     * @param  mixed       $date Datetime to be converted
     * @param  string      $format Datetime format string
     * @param  string|null $timezone User's timezone
     * @return string Returns the datetime in the the provided format
     * @throws \Exception
     */
	public static function ConvertDateTimeToFormat($date,string $format,?string $timezone = NULL): ?string {
        return call_user_func(static::GetConverterAdapter('DateTimeToFormat'),$date,$format,$timezone);
    }//END public static function ConvertDateTimeToFormat
	/**
     * Convert datetime value to application or provided format
     * @param  mixed       $date Datetime to be converted
     * @param  string|null $timezone User's timezone
     * @param  bool        $dateOnly If set TRUE eliminates the time from value
     * @param  bool        $timeOnly If set TRUE eliminates the date from value
     * @param  string|null $format Datetime format string
     * @return string Returns the datetime in the application or provided format
     * @throws \Exception
     */
	public static function ConvertDateTime($date,$timezone = NULL,bool $dateOnly = FALSE,bool $timeOnly = FALSE,?string $format = NULL): ?string {
		if(!strlen($format)) {
			if($timeOnly===TRUE) {
			    $format = NApp::GetTimeFormat(TRUE);
			} elseif($dateOnly===TRUE) {
			    $format = NApp::GetDateFormat(TRUE);
			} else {
			    $format = NApp::GetDateTimeFormat(TRUE);
			}//if($timeOnly===TRUE)
		}//if(!strlen($format))
		return call_user_func(static::GetConverterAdapter('DateTimeToFormat'),$date,$format,$timezone);
	}//END public static function ConvertDateTime
}//END class Validator