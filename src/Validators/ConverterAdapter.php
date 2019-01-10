<?php
/**
 * Converter adapter class file
 *
 * Class containing methods for converting values
 *
 * @package    NETopes\Core\App
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.5.0.6
 * @filesource
 */
namespace NETopes\Core\Validators;
use NETopes\Core\AppConfig;
use NApp;
use Translate;
/**
 * Class ConverterAdapter
 *
 * @package NETopes\Core\Validators
 */
class ConverterAdapter {
    /**
     * Convert value
     *
     * @param mixed       $value
     * @param string      $mode
     * @param string|null $defaultValue
     * @param string|null $validation
     * @return mixed
     * @throws \NETopes\Core\AppException
     * @access public
     * @static
     */
	public static final function Convert($value,string $mode,?string $defaultValue = NULL,?string $validation = NULL) {
        if(isset($validation)) { $value = Validator::ValidateValue($value,$defaultValue,$validation); }
        if(is_null($value)) { return NULL; }
        $method = convert_to_camel_case($mode);
        if(strtolower(substr($method,0,2))!=='to') { $method = 'To'.$mode; }
        if(!method_exists(static::class,$method)) {
            NApp::_Elog('Invalid converter adapter method ['.static::class.'::'.$method.']!');
            return $value;
        }//if(!method_exists(static::class,$method))
        return static::$method($value);
	}//END public static final function Format
    /**
     * Converts a datetime string value to DateTime instance
     *
     * @param  mixed       $date Datetime to be converted
     * @param  null|string $sourceFormat Format of the date to be converted
     * @param  null|string $timezone User's timezone
     * @param bool         $convertToServerTimezone Default value TRUE
     * @return \DateTime|null Returns the datetime object or null
     * @throws \Exception
     * @access public
     * @static
     */
	public static function DateTimeToObject($date,?string $sourceFormat = NULL,?string $timezone = NULL,bool $convertToServerTimezone = TRUE): ?\DateTime {
	    if($date instanceof \DateTime) { return clone $date; }
	    $timezone = strlen($timezone) ? $timezone : NApp::_GetParam('timezone');
		$timezone = strlen($timezone) ? $timezone : AppConfig::server_timezone();
	    if(is_numeric($date)) {
            if(!($dt = new \DateTime('now',new \DateTimeZone($timezone)))) { return NULL; }
			$dt->setTimestamp($date);
	    } elseif(is_string($date) && strlen($date)) {
	        if(!strlen($sourceFormat)) {
	            if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date)) {
	                 $sourceFormat = 'Y-m-d H:i:s';
	                 $date .= ' 00:00:00';
	            } elseif(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}(\s|T)[0-9]{2}:[0-9]{2}$/',$date)) {
	                 $sourceFormat = 'Y-m-d H:i:s';
	                 $date .= ':00';
	            } elseif(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}(\s|T)[0-9]{2}:[0-9]{2}:[0-9]{2}$/',$date)) {
	                 $sourceFormat = 'Y-m-d H:i:s';
	            } else {
	                $sourceFormat = NApp::_GetDateTimeFormat(TRUE);
	                if(strpos($date,' ')===FALSE && strpos($date,'T')===FALSE) {
	                    $date .= ' 00:00'.(substr($sourceFormat,-2)==':s' ? ':00' : '');
	                } elseif(substr($sourceFormat,-2)==':s' && preg_match('/(\s|T)[0-9]{2}:[0-9]{2}$/',$date)) {
	                    $date .= ':00';
	                }//if(strpos($date,' ')===FALSE && strpos($date,'T')===FALSE)
	            }//if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date))
	        } else {
	            if(strpos($sourceFormat,' ') && strpos($date,' ')===FALSE && strpos($date,'T')===FALSE) {
                    $date .= ' 00:00'.(substr($sourceFormat,-2)==':s' ? ':00' : '');
                } elseif(substr($sourceFormat,-2)==':s' && preg_match('/(\s|T)[0-9]{2}:[0-9]{2}$/',$date)) {
                    $date .= ':00';
                }//if(strpos($sourceFormat,' ') && strpos($date,' ')===FALSE && strpos($date,'T')===FALSE)
	        }//if(!strlen($sourceFormat))
            if(!($dt = \DateTime::createFromFormat($sourceFormat,$date,new \DateTimeZone($timezone)))) { return NULL; }
	    } elseif(!is_object($date) || !($date instanceof \DateTime)) {
	        return NULL;
	    }//if(is_numeric($date))
	    if($convertToServerTimezone && $timezone!==AppConfig::server_timezone()) { $dt->setTimezone(new \DateTimeZone(AppConfig::server_timezone())); }
		return $dt;
	}//END public static function DateTimeToObject
    /**
     * Converts a datetime value to database format
     *
     * @param  mixed       $date Datetime to be converted
     * @param  string      $sourceFormat Datetime format string
     * @param  string|null $timezone User's timezone
     * @param  int|null    $dayPart If set to 0  time is set to "00:00:00.000",
     * if set to 1 time is set to "23:59:59.999", else time is set to original value
     * @param  bool        $dateOnly If set TRUE eliminates the time
     * @return string Returns the datetime in the database format
     * @throws \Exception
     * @access public
     * @static
     */
	public static function DateTimeToDbFormat($date,?string $sourceFormat = NULL,?string $timezone = NULL,?int $dayPart = NULL,bool $dateOnly = FALSE) {
	    $timezone = strlen($timezone) ? $timezone : NApp::_GetParam('timezone');
		$timezone = strlen($timezone) ? $timezone : AppConfig::server_timezone();
	    $dt = static::DateTimeToObject($date,$sourceFormat,$timezone,FALSE);
	    if(is_null($dt)) { return NULL; }
		if($dayPart===0) {
            $dt->setTime(0,0,0,0);
        } elseif($dayPart===1) {
            $dt->setTime(23,59,59,999);
        }//if($dayPart===0)
		if($timezone!==AppConfig::server_timezone()) { $dt->setTimezone(new \DateTimeZone(AppConfig::server_timezone())); }
		return $dt->format(($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s'));
	}//END public static function DateTimeToDbFormat
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
	public static function DateTimeToFormat($date,string $format,?string $timezone = NULL): ?string {
	    $dt = static::DateTimeToObject($date,NULL,$timezone);
	    if(is_null($dt)) { return NULL; }
        return $dt->format($format);
	}//END public static function DateTimeToFormat
	/**
     * @param mixed $value
     * @return \DateTime|null
     * @throws \Exception
     */
    public static function ToDatetimeObj($value): ?\DateTime {
        return static::DateTimeToObject($value);
	}//END public static function ToDatetimeObj
    /**
     * @param mixed $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToDatetime($value): ?string {
        $dt = static::DateTimeToObject($value);
        if(is_null($dt)) { return NULL; }
        return $dt->format('Y-m-d H:i:s');
	}//END public static function ToDatetime
    /**
     * @param mixed       $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToAppDatetime($value): ?string {
        $dt = static::DateTimeToObject($value);
        if(is_null($dt)) { return NULL; }
        return $dt->format(NApp::_GetDateTimeFormat(TRUE));
	}//END public static function ToAppDatetime
    /**
     * @param mixed       $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToAppDate($value): ?string {
        $dt = static::DateTimeToObject($value);
        if(is_null($dt)) { return NULL; }
        return $dt->format(NApp::_GetDateFormat(TRUE));
	}//END public static function ToAppDate
    /**
     * @param mixed $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToDbDatetime($value): ?string {
        $dt = static::DateTimeToObject($value,NApp::_GetDateTimeFormat(TRUE));
        if(is_null($dt)) { return NULL; }
        return $dt->format('Y-m-d H:i:s');
	}//END public static function ToDbDatetime
	/**
     * @param mixed $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToDbDate($value): ?string {
        $dt = static::DateTimeToObject($value,NApp::_GetDateFormat(TRUE));
        if(is_null($dt)) { return NULL; }
        return $dt->format('Y-m-d');
	}//END public static function ToDbDate
    /**
     * @param mixed $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToSodDatetime($value): ?string {
        return static::DateTimeToDbFormat($value,NApp::_GetDateTimeFormat(TRUE),NULL,1);
	}//END public static function ToSodDatetime
	/**
     * @param mixed $value
     * @return string|null
     * @throws \Exception
     */
    public static function ToEodDatetime($value): ?string {
        return static::DateTimeToDbFormat($value,NApp::_GetDateTimeFormat(TRUE),NULL,2);
	}//END public static function ToEodDatetime
	/**
	 * Converts a number to standard format
	 *
	 * @param  mixed $number The number to be converted
	 * @param  string|null $decimalSeparator The decimal separator
	 * @param  string|null $groupSeparator The group separator
	 * @return string Returns the number in the database format
	 * @access public
	 * @static
	 */
	public static function NumberToStandardFormat($number,?string $decimalSeparator = NULL,?string $groupSeparator = NULL): ?string {
		if(!is_scalar($number) || !strlen($number)) { return NULL; }
		$decimalSeparator = strlen($decimalSeparator) ? $decimalSeparator : NApp::_GetParam('decimal_separator');
		$groupSeparator = isset($groupSeparator) ? $groupSeparator : NApp::_GetParam('group_separator');
		return str_replace($decimalSeparator,'.',str_replace($groupSeparator,'',$number));
	}//END public static function NumberToStandardFormat
    /**
     * Convert number to words representation
     *
     * @param float       $value
     * @param string|null $currency
     * @param string|null $subCurrency
     * @param string|null $langCode
     * @param bool        $useIntl
     * @return string|null
     * @access public
     * @static
     */
	public static function NumberToWords(float $value,?string $currency = NULL,?string $subCurrency = NULL,?string $langCode = NULL,bool $useIntl = TRUE): ?string {
		$langCode = strlen($langCode) ? $langCode : NApp::_GetLanguageCode();
		if(!is_numeric($value) || !strlen($langCode)) { return NULL; }
		$decimals = intval((round($value,2) * 100) % 100);
		$value = intval($value);
		if($value==0 && $decimals==0) { return Translate::Get('label_zero',$langCode).(strlen($currency) ? ' '.$currency : ''); }
		$result = '';
		if($useIntl && class_exists('\NumberFormatter')) {
			$nw = new \NumberFormatter($langCode,\NumberFormatter::SPELLOUT);
			if(abs($value)>0) {
				if($value<0) { $result .= Translate::Get('label_minus',$langCode).' '; }
				$result .= $nw->format(abs($value)).(strlen($currency) ? ' '.$currency : '');
			}//if(abs($value)>0)
			if($decimals>0) { $result .= (strlen($result) ? ' '.strtolower(Translate::Get('label_and',$langCode)).' ' : '').$nw->format($decimals).(strlen($subCurrency) ? ' '.$subCurrency : ''); }
			return $result;
		}//if($useIntl && class_exists('\NumberFormatter'))
		if(abs($value)>0) {
			if($value<0) { $result .= Translate::Get('label_minus',$langCode).' '; }
			$result .= convert_number_to_words(abs($value),$langCode).(strlen($currency) ? ' '.$currency : '');
		}//if(abs($value)>0)
		if($decimals>0) { $result .= (strlen($result) ? ' '.strtolower(Translate::Get('label_and',$langCode)).' ' : '').convert_number_to_words($decimals,$langCode).(strlen($subCurrency) ? ' '.$subCurrency : ''); }
		return $result;
	}//END public static function NumberToWords
    /**
     * @param mixed $value
     * @return float|null
     */
    public static function ToNumeric($value): ?float {
        if(is_numeric($value)) { return $value; }
        if(is_string($value) && strlen($value)) {
            $tmpVal = static::NumberToStandardFormat($value);
            $tmpVal = filter_var($tmpVal,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
            return is_numeric($tmpVal) ? $tmpVal : NULL;
        }//if(is_string($value) && strlen($value))
        return NULL;
	}//END public static function ToNumeric
    /**
     * @param mixed $value
     * @return float|null
     */
    public static function ToFloat($value): ?float {
        return static::ToNumeric($value);
	}//END public static function ToFloat
    /**
     * @param mixed $value
     * @return float|null
     */
    public static function ToDecimal($value): ?float {
        return static::ToNumeric($value);
	}//END public static function ToDecimal
    /**
     * @param mixed $value
     * @return int|null
     */
    public static function ToInteger($value): ?int {
        return floor(static::ToNumeric($value));
	}//END public static function ToInteger
	/**
     * @param mixed $value
     * @return string|null
     */
    public static function ToMultiLineString($value): ?string {
        if(!is_string($value)) { return NULL; }
        return custom_nl2br($value);
	}//END public static function ToMultiLineString
	/**
     * @param mixed $value
     * @return string|null
     */
    public static function ToTrimmedString($value): ?string {
        if(!is_string($value)) { return NULL; }
        return trim($value);
	}//END public static function ToTrimmedString
}//END class ConverterAdapter