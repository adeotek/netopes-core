<?php
/**
 * short description
 *
 * long description
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    2.1.0.1
 * @filesource
 */
namespace NETopes\Core\App;
use PAF\AppConfig;
use NApp;
/**
 * Validator description
 *
 * long_description
 *
 * @package  NETopes\Base
 * @access   public
 */
class Validator {
	/**
	 * Converts a number to database format
	 *
	 * @param  mixed $number The number to be converted
	 * @param  string $decimal_separator The decimal separator
	 * @param  string $group_separator The group separator
	 * @return string Returns the number in the database format
	 * @access public
	 * @static
	 */
	public static function ConvertNumberToDbFormat($number,$decimal_separator = NULL,$group_separator = NULL) {
		if(!is_scalar($number) || strlen($number)==0) { return NULL; }
		$decimal_separator = $decimal_separator ? $decimal_separator : NApp::_GetParam('decimal_separator');
		$group_separator = $group_separator ? $group_separator : NApp::_GetParam('group_separator');
		return str_replace($decimal_separator,'.',str_replace($group_separator,'',$number));
	}//END public static function ConvertNumberToDbFormat
	/**
	 * Converts a datetime string value to DateTime instance
	 *
	 * @param  string     $date Datetime to be converted
	 * @param  null|string $format Format of the date to be converted
	 * @param  null|string $timezone User's timezone
	 * @return \DateTime|null Returns the datetime object or null
	 * @access public
	 * @static
	 */
	public static function ConvertDateTimeToObject(?string $date,?string $format = NULL,?string $timezone = NULL): ?\DateTime {
		if(!is_scalar($date) || !strlen($date)) { return NULL; }
		$timezone = strlen($timezone) ? $timezone : NApp::_GetParam('timezone');
		$timezone = strlen($timezone) ? $timezone : AppConfig::server_timezone();
		if(!strlen($format)) { $format = 'Y-m-d H:i:s'; }
		$dt = \DateTime::createFromFormat($format,$date,new \DateTimeZone($timezone));
		if(!$dt) { return NULL; }
		$dt->setTimezone(new \DateTimeZone(AppConfig::server_timezone()));
		return $dt;
	}//END public static function ConvertDateTimeToObject
	/**
	 * Converts a datetime value to database format
	 *
	 * @param  string $date Datetime to be converted
	 * @param  string $timezone User's timezone
	 * @param  int    $daypart If set to 0  time is set to "00:00:00.000",
	 * if set to 1 time is set to "23:59:59.999", else time is set to original value
	 * @param  bool   $dateonly If set TRUE eliminates the time
	 * @return string Returns the datetime in the database format
	 * @access public
	 * @static
	 */
	public static function ConvertDateTimeToDbFormat($date,$timezone = NULL,$daypart = NULL,$dateonly = FALSE) {
		if(!is_scalar($date) || !strlen($date)) { return NULL; }
		$timezone = strlen($timezone) ? $timezone : NApp::_GetParam('timezone');
		$timezone = strlen($timezone) ? $timezone : AppConfig::server_timezone();
		if($daypart===0) {
			if(strpos($date,' ')!==FALSE) { $date = substr($date,0,strpos($date,' ')); }
			$date .= ' 00:00:00.000';
		} elseif($daypart===1) {
			if(strpos($date,' ')!==FALSE) { $date = substr($date,0,strpos($date,' ')); }
			$date .= ' 23:59:59.999';
		}//if($daypart===0)
		$dt = new \DateTime($date,new \DateTimeZone($timezone));
		$dt->setTimezone(new \DateTimeZone(AppConfig::server_timezone()));
		//if($daypart===0) { $dt->add(new DateInterval('P1D')); }
		//elseif($daypart===1) { $dt->add(new DateInterval('P1D')); }
		return $dt->format(($dateonly ? 'Y-m-d' : 'Y-m-d H:i:s'));
	}//END public static function ConvertDateTimeToDbFormat
	/**
	 * Converts a datetime value to DotNet format
	 *
	 * @param  string $date Datetime to be converted
	 * @param  string $timezone User's timezone
	 * @param  int    $daypart If set to 0  time is set to "00:00:00.000",
	 * if set to 1 time is set to "23:59:59.999", else time is set to original value
	 * @param  bool   $dateonly If set TRUE eliminates the time
	 * @return string Returns the datetime in the database format
	 * @access public
	 * @static
	 */
	public static function ConvertDateTimeToDotNetFormat($date,$timezone = NULL,$daypart = NULL,$dateonly = FALSE) {
		if(!is_scalar($date) || !strlen($date)) { return NULL; }
		$timezone = strlen($timezone) ? $timezone : NApp::_GetParam('timezone');
		$timezone = strlen($timezone) ? $timezone : AppConfig::server_timezone();
		if($daypart===0) {
			if(strpos($date,' ')!==FALSE) { $date = substr($date,0,strpos($date,' ')); }
			$date .= ' 00:00:00.000';
		} elseif($daypart===1) {
			if(strpos($date,' ')!==FALSE) { $date = substr($date,0,strpos($date,' ')); }
			$date .= ' 23:59:59.999';
		}//if($daypart===0)
		$dt = new \DateTime($date,new \DateTimeZone($timezone));
		$dt->setTimezone(new \DateTimeZone(AppConfig::server_timezone()));
		//if($daypart===0) { $dt->add(new DateInterval('P1D')); }
		//elseif($daypart===1) { $dt->add(new DateInterval('P1D')); }
		return str_replace(' ','T',$dt->format(($dateonly ? 'Y-m-d' : 'Y-m-d H:i:s')));
	}//END public static function ConvertDateTimeToDotNetFormat
	/**
	 * description
	 *
	 * @param  string $date Datetime to be converted
	 * @param  string $timezone User's timezone
	 * @param  bool   $dateonly If set TRUE eliminates the time from value
	 * @param  string $date_separator The date separator
	 * @param  string $time_separator The time separator
	 * @param bool    $timeonly
	 * @param null    $format
	 * @return string Returns the datetime in the application format
	 * @access public
	 * @static
	 */
	public static function ConvertDateTimeFromDbFormat($date,$timezone = NULL,$dateonly = FALSE,$date_separator = NULL,$time_separator = NULL,$timeonly = FALSE,$format = NULL) {
		$timezone = $timezone ? $timezone : NApp::_GetParam('timezone');
		$date_separator = is_string($date_separator) ? $date_separator : NApp::_GetParam('date_separator');
		$time_separator = is_string($time_separator) ? $time_separator : NApp::_GetParam('time_separator');
		if(is_object($date)) {
			$dt = $date;
		} elseif(is_numeric($date) && $date>0) {
			$dt = new \DateTime('now',new \DateTimeZone(AppConfig::server_timezone()));
			$dt->setTimestamp($date);
		} elseif(!is_string($date) || !strlen($date)) {
			return NULL;
		} else {
			$dt = new \DateTime($date,new \DateTimeZone(AppConfig::server_timezone()));
		}//if(is_object($date))
		if(strlen($timezone)>0) { $dt->setTimezone(new \DateTimeZone($timezone)); }
		if(!strlen($format)) {
			$format = 'd'.$date_separator.'m'.$date_separator.'Y';
			if($dateonly!==TRUE) { $format .= ' H'.$time_separator.'i'.$time_separator.'s'; }
			if($timeonly===TRUE) { $format = 'H'.$time_separator.'i'.$time_separator.'s'; }
		}//if(!strlen($format))
		return $dt->format($format);
	}//END public static function ConvertDateTimeFromDbFormat
	/**
	 * description
	 *
	 * @param $value
	 * @param $format
	 * @return mixed
	 * @access public
	 * @static
	 */
	public static function ConvertParamFormat($value,$format) {
		if(!isset($value) || !is_string($format) || !strlen($format)) { return $value; }
		$result = NULL;
		switch($format) {
			case 'numeric':
				if(is_numeric($value)) {
					$result = $value;
				} elseif(is_string($value) && strlen($value)) {
					$tmp_val = self::ConvertNumberToDbFormat($value);
					$tmp_val = filter_var($tmp_val,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
					$result = is_numeric($tmp_val) ? $tmp_val : NULL;
				}//if(is_numeric($value))
				break;
			case 'datetime':
				if(is_object($value)) {
					$result = $value->format('Y-m-d H:i:s.u');
				} else {
					$result = $value;
				}//if(is_object($value))
				break;
			case 'u_date':
				$result = $value ? self::ConvertDateTimeFromDbFormat($value,NApp::_GetParam('timezone'),TRUE) : NULL;
				break;
			case 'u_datetime':
				$result = $value ? self::ConvertDateTimeFromDbFormat($value,NApp::_GetParam('timezone'),FALSE) : NULL;
				break;
			case 'db_datetime':
				$result = $value ? self::ConvertDateTimeToDbFormat($value,NApp::_GetParam('timezone')) : NULL;
				break;
			case 'db_date':
				$result = $value ? self::ConvertDateTimeToDbFormat($value,NApp::_GetParam('timezone'),NULL,TRUE) : NULL;
				break;
			case 'db_sod_datetime':
				$result = $value ? self::ConvertDateTimeToDbFormat($value,NApp::_GetParam('timezone'),0) : NULL;
				break;
			case 'db_eod_datetime':
				$result = $value ? self::ConvertDateTimeToDbFormat($value,NApp::_GetParam('timezone'),1) : NULL;
				break;
			case 'dn_date':
				$result = $value ? self::ConvertDateTimeToDotNetFormat($value,NApp::_GetParam('timezone'),TRUE) : NULL;
				break;
			case 'dn_datetime':
				$result = $value ? self::ConvertDateTimeToDotNetFormat($value,NApp::_GetParam('timezone'),FALSE) : NULL;
				break;
			case 'bool':
				$result = boolval($value);
				break;
			case 'validated':
				$result = $value ? $value : NULL;
				break;
			case 'array':
				$result = is_array($value) ? $value : NULL;
				break;
			case 'multi_line_string':
				$result = custom_nl2br($value);
				break;
			default:
				$result = $value;
				break;
		}//switch($format)
		return $result;
	}//public static function ConvertParamFormat
	/**
	 * description
	 *
	 * @param      $value
	 * @param null $def_value
	 * @param null $validation
	 * @param null $format
	 * @param bool $checkonly
	 * @return mixed
	 * @access public
	 * @static
	 */
	public static function ValidateParam($value,$def_value = NULL,$validation = NULL,$format = NULL,$checkonly = FALSE) {
		$lvalue = is_string($format) && strlen($format) ? self::ConvertParamFormat($value,$format) : $value;
		if(!is_string($validation) || !strlen($validation)) { return validate_param($lvalue,$def_value,NULL,$checkonly); }
		return validate_param($lvalue,$def_value,$validation,$checkonly);
	}//END public static function ValidateParam
	/**
	 * description
	 *
	 * @param      $array
	 * @param      $key
	 * @param null $def_value
	 * @param null $validation
	 * @param null $format
	 * @param bool $checkonly
	 * @return mixed
	 * @access public
	 * @static
	 */
	public static function ValidateArrayParam($array,$key,$def_value = NULL,$validation = NULL,$format = NULL,$checkonly = FALSE) {
		if(!check_array_key($key,$array,'isset')){ return ($checkonly ? FALSE :$def_value); }
		return self::ValidateParam($array[$key],$def_value,$validation,$format,$checkonly);
	}//END public static function ValidateArrayParam
	/**
	 * description
	 *
	 * @param      $value
	 * @param null $format
	 * @param null $validation
	 * @return bool
	 * @access public
	 * @static
	 */
	public static function IsValidParam($value,$format = NULL,$validation = NULL) {
		return (bool)self::ValidateParam($value,NULL,$validation,$format,TRUE);
	}//END public static function IsValidParam
	/**
	 * description
	 *
	 * @param      $array
	 * @param      $key
	 * @param null $format
	 * @param null $validation
	 * @return bool
	 * @access public
	 * @static
	 */
	public static function IsValidArrayParam($array,$key,$format = NULL,$validation = NULL) {
		return (bool)self::ValidateArrayParam($array,$key,NULL,$validation,$format,TRUE);
	}//END public static function IsValidArrayParam
	/**
	 * description
	 *
	 * @param mixed  $value
	 * @param string $mode
	 * @param bool   $html_entities
	 * @param string $prefix
	 * @param string $sufix
	 * @param string $def_value
	 * @param string $format
	 * @param string $validation
	 * @param array  $regionals
	 * @return string
	 * @access public
	 * @static
	 */
	public static function FormatValue($value,$mode,$html_entities = FALSE,$prefix = '',$sufix = '',$def_value = '',$format = '',$validation = '',$regionals = []) {
		$lvalue = ($format && $validation) ? self::ValidateParam($value,NULL,$validation,$format) : $value;
		if(!isset($lvalue)) { return $def_value; }
		$decimal_sep = get_array_value($regionals,'decimal_separator',NApp::_GetParam('decimal_separator'),'is_notempty_string');
		$group_sep = get_array_value($regionals,'group_separator',NApp::_GetParam('group_separator'),'is_notempty_string');
		$date_sep = get_array_value($regionals,'date_separator',NApp::_GetParam('date_separator'),'is_notempty_string');
		$time_sep = get_array_value($regionals,'time_separator',NApp::_GetParam('time_separator'),'is_notempty_string');
		$timezone = get_array_value($regionals,'timezone',NApp::_GetParam('timezone'),'is_notempty_string');
		$percent_sufix = $html_entities ? '&nbsp;&#37;' : ' %';
		switch($mode) {
			case 'date':
				if(!is_object($lvalue) && !strlen($lvalue)) { return $def_value; }
				$result = self::ConvertDateTimeFromDbFormat($lvalue,$timezone,TRUE,$date_sep);
				break;
			case 'datetime':
				if(!is_object($lvalue) && !strlen($lvalue)) { return $def_value; }
				$result = self::ConvertDateTimeFromDbFormat($lvalue,$timezone,FALSE,$date_sep,$time_sep);
				break;
			case 'datetime_nosec':
				if(!is_object($lvalue) && !strlen($lvalue)) { return $def_value; }
				$result = self::ConvertDateTimeFromDbFormat($lvalue,$timezone,FALSE,$date_sep,$time_sep,FALSE,'d'.$date_sep.'m'.$date_sep.'Y H'.$time_sep.'i');
				break;
			case 'time':
				if(!is_object($lvalue) && !strlen($lvalue)) { return $def_value; }
				$result = self::ConvertDateTimeFromDbFormat($lvalue,$timezone,FALSE,$date_sep,$time_sep,TRUE);
				break;
			case 'time_nosec':
				if(!is_object($lvalue) && !strlen($lvalue)) { return $def_value; }
				$result = self::ConvertDateTimeFromDbFormat($lvalue,$timezone,FALSE,$date_sep,$time_sep,TRUE,'H'.$time_sep.'i');
				break;
			case 'integer':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,0,'','');
				break;
			case 'decimal0':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,0,$decimal_sep,$group_sep);
				break;
			case 'decimal1':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,1,$decimal_sep,$group_sep);
				break;
			case 'decimal2':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,2,$decimal_sep,$group_sep);
				break;
			case 'decimal2-0dv':
				if(!is_numeric($lvalue) || $lvalue==0) { return $def_value; }
				$result = number_format($lvalue,2,$decimal_sep,$group_sep);
				break;
			case 'decimal3':
				$result = number_format($lvalue,3,$decimal_sep,$group_sep);
				break;
			case 'decimal4':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,4,$decimal_sep,$group_sep);
				break;
			case 'decimal6':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,6,$decimal_sep,$group_sep);
				break;
			case 'percent0':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,0,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent1':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,1,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent2':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,2,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent4':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue,4,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent2s':
				if(!is_numeric($lvalue)) { return $def_value; }
				$percent_sufix = $html_entities ? '&#37;' : '%';
				$result = number_format($lvalue,2,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent2-0dv':
				if(!is_numeric($lvalue) || $lvalue==0) { return $def_value; }
				$result = number_format($lvalue,2,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent0x100':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue*100,0,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent1x100':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue*100,1,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent2x100':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue*100,2,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'percent4x100':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue*100,4,$decimal_sep,$group_sep).$percent_sufix;
				break;
			case 'hi_ts':
				if(!is_numeric($value)) { return $def_value; }
				$result = timestamp_to_str_time(intval($lvalue),FALSE,TRUE);
				break;
			case 'his_ts':
				if(!is_numeric($value)) { return $def_value; }
				$result = timestamp_to_str_time($lvalue,TRUE);
				break;
			case 'd_his_ts':
				if(!is_numeric($value)) { return $def_value; }
				$result = timestamp_to_str_duration(round($lvalue,0),TRUE,TRUE);
				break;
			case 'd_is_ts':
				if(!is_numeric($value)) { return $def_value; }
				$result = timestamp_to_str_duration(round($lvalue,0),TRUE);
				break;
			case 'fsizeMB1':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue/1024/1024,1,$decimal_sep,$group_sep).' MB';
				break;
			case 'fsizeMB3':
				if(!is_numeric($lvalue)) { return $def_value; }
				$result = number_format($lvalue/1024/1024,3,$decimal_sep,$group_sep).' MB';
				break;
			case 'limit_text_255':
				if(!strlen($lvalue)) { return $def_value; }
				$result = limit_text($lvalue,255);
				break;
			case 'limit_text':
				if(!strlen($lvalue)) { return $def_value; }
				$result = limit_text($lvalue,$format);
				break;
			default:
				$result = $lvalue;
				break;
		}//END switch
		return $prefix.$result.$sufix;
	}//END public static function FormatValue
	/**
	 * description
	 *
	 * @param mixed  $value
	 * @param string $mode
	 * @param array  $regionals
	 * @param bool   $html_entities
	 * @param string $prefix
	 * @param string $sufix
	 * @param string $def_value
	 * @param string $format
	 * @param string $validation
	 * @return string formatted value
	 * @access public
	 * @static
	 */
	public static function CustomFormatValue($value,$mode,$regionals = [],$html_entities = FALSE,$prefix = '',$sufix = '',$def_value = '',$format = '',$validation = '') {
		return self::FormatValue($value,$mode,$html_entities,$prefix,$sufix,$def_value,$format,$validation,$regionals);
	}//END public static function CustomFormatValue
	/**
	 * description
	 *
	 * @param $value
	 * @param $numberformat
	 * @return void
	 * @access public
	 * @static
	 */
	public static function FormatNumberValue($value,$numberformat) {
		if(!is_numeric($value)) { return NULL; }
		if(!strlen($numberformat)) { return $value; }
		$f_arr = explode('|',$numberformat);
		if(count($f_arr)!=4) { return $value; }
		return number_format($value,$f_arr[0],$f_arr[1],$f_arr[2]).$f_arr[3];
	}//END public static function FormatNumberValue
	/**
	 * description
	 *
	 * @param      $value
	 * @param null $currency
	 * @param null $subcurrency
	 * @param null $langcode
	 * @param bool $use_intl
	 * @return void
	 * @access public
	 * @static
	 */
	public static function ConvertNumberToWords($value,$currency = NULL,$subcurrency = NULL,$langcode = NULL,$use_intl = TRUE) {
		$langcode = strlen($langcode) ? $langcode : NApp::_GetLanguageCode();
		if(!is_numeric($value) || !strlen($langcode)) { return NULL; }
		$decimals = intval((round($value,2) * 100) % 100);
		$value = intval($value);
		if($value==0 && $decimals==0) { return \Translate::Get('label_zero',$langcode).(strlen($currency) ? ' '.$currency : ''); }
		$result = '';
		if($use_intl && class_exists('NumberFormatter')) {
			$nw = new NumberFormatter($langcode,NumberFormatter::SPELLOUT);
			if(abs($value)>0) {
				if($value<0) { $result .= \Translate::Get('label_minus').' '; }
				$result .= $nw->format(abs($value)).(strlen($currency) ? ' '.$currency : '');
			}//if(abs($value)>0)
			if($decimals>0) { $result .= (strlen($result) ? ' '.strtolower(\Translate::Get('label_and',$langcode)).' ' : '').$nw->format($decimals).(strlen($subcurrency) ? ' '.$subcurrency : ''); }
			return $result;
		}//if($use_intl && class_exists('NumberFormatter'))
		if(abs($value)>0) {
			if($value<0) { $result .= \Translate::Get('label_minus').' '; }
			$result .= convert_number_to_words(abs($value),$langcode).(strlen($currency) ? ' '.$currency : '');
		}//if(abs($value)>0)
		if($decimals>0) { $result .= (strlen($result) ? ' '.strtolower(\Translate::Get('label_and',$langcode)).' ' : '').convert_number_to_words($decimals,$langcode).(strlen($subcurrency) ? ' '.$subcurrency : ''); }
		return $result;
	}//END public static function ConvertNumberToWord
	/**
	 * description
	 *
	 * @param bool $forPhp
	 * @return string|null
	 * @access public
	 * @static
	 */
	public static function GetDateFormat(bool $forPhp = FALSE): ?string {
		$format = NApp::_GetParam('date_format');
		if(!strlen($format)) { return NULL; }
		if(!$forPhp) { return $format; }
		return str_replace(['yyyy','mm','MM','dd','yy'],['Y','m','m','d','Y'],$format);
	}//END public static function GetDateFormat
	/**
	 * description
	 *
	 * @param bool $forPhp
	 * @return string|null
	 * @access public
	 * @static
	 */
	public static function GetTimeFormat(bool $forPhp = FALSE): ?string {
		$format = NApp::_GetParam('time_format');
		if(!strlen($format)) { return NULL; }
		if(!$forPhp) { return $format; }
		return str_replace(['HH','hh','mm','ss'],['H','h','i','s'],$format);
	}//END public static function GetTimeFormat
	/**
	 * description
	 *
	 * @param bool $forPhp
	 * @return string|null
	 * @access public
	 * @static
	 */
	public static function GetDateTimeFormat($forPhp = FALSE): ?string {
		return self::GetDateFormat($forPhp).' '.self::GetTimeFormat($forPhp);
	}//END public static function GetTimeFormat
}//END class Validator