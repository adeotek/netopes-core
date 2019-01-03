<?php
/**
 * Formatter adapter class file
 *
 * Class containing methods for formatting values
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
 * Class FormatterAdapter
 *
 * @package NETopes\Core\Validators
 */
class FormatterAdapter {
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
     * @throws \ReflectionException
     * @access public
     * @static
     */
	public static final function Format($value,string $mode,?array $regionals = NULL,?string $prefix = NULL,?string $sufix = NULL,?string $defaultValue = NULL,?string $validation = NULL,bool $htmlEntities = FALSE): ?string {
	    if(substr($mode,-4)==='-0dv') { $validation = (substr($validation,0,1)==='?' ? '?' : '').'is_not0_numeric'; }
        if(isset($validation)) { $value = Validator::ValidateValue($value,$defaultValue,$validation); }
        if(is_null($value)) { return NULL; }
        $method = convert_to_camel_case($mode);
        if(!method_exists(static::class,$method)) {
            NApp::_Elog('Invalid formatter adapter method ['.static::class.'::'.$method.']!');
            return is_string($value) ? $prefix.$value.$sufix : NULL;
        }//if(!method_exists(static::class,$method))
        $reflection = new \ReflectionMethod(static::class,$method);
        $arguments = [];
        foreach($reflection->getParameters() as $param) {
            switch(strtolower($param->getName())) {
                case 'value':
                    $arguments[] = $value;
                    break;
                case 'regionals':
                    $arguments[] = $regionals;
                    break;
                case 'htmlentities':
                    $arguments[] = $htmlEntities;
                    break;
                case 'size':
                    $arguments[] = get_array_param($regionals,'size',100,'is_not0_integer');
                    break;
                case 'number_format':
                    $arguments[] = get_array_param($regionals,'number_format','6|.||','is_string');
                    break;
                case 'groupseparator':
                    $arguments[] = get_array_param($regionals,'group_separator','','is_string');
                    break;
                default:
                    $arguments[] = NULL;
                    break;
            }//END switch
        }//END foreach
        $result = call_user_func_array(static::class.'::'.$method,$arguments);
		return is_string($result) ? $prefix.$result.$sufix : NULL;
	}//END public static final function Format
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     * @throws \Exception
     */
    public static function Datetime($value,?array $regionals = NULL): ?string {
	    if(!is_object($value) && (!is_string($value) || !strlen($value))) { return $value; }
		$timezone = get_array_value($regionals,'timezone',NApp::_GetParam('timezone'),'?is_notempty_string');
		$format = get_array_value($regionals,'format',NULL,'?is_notempty_string');
		$dateOnly = get_array_value($regionals,'date_only',FALSE,'bool');
		$timeOnly = get_array_value($regionals,'time_only',FALSE,'bool');
        return Validator::ConvertDateTime($value,$timezone,$dateOnly,$timeOnly,$format);
	}//END public static function Datetime
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     * @throws \Exception
     */
    public static function DatetimeNosec($value,?array $regionals = NULL): ?string {
        $format = NApp::_GetTimeFormat(TRUE);
        if(strpos($format,NApp::_GetParam('time_separator').'s')!==FALSE) { $format = str_replace(NApp::_GetParam('time_separator').'s','',$format); }
        $format = NApp::_GetDateFormat(TRUE).' '.$format;
	    if(!is_array($regionals)) { $regionals = ['format'=>$format]; }
        else { $regionals['format'] = $format; }
        return static::Datetime($value,$regionals);
	}//END public static function DatetimeNosec
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     * @throws \Exception
     */
    public static function Date($value,?array $regionals = NULL): ?string {
	    if(!is_array($regionals)) { $regionals = ['date_only'=>TRUE]; }
        else { $regionals['date_only'] = TRUE; }
        return static::Datetime($value,$regionals);
	}//END public static function Date
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     * @throws \Exception
     */
    public static function Time($value,?array $regionals = NULL): ?string {
	    if(!is_array($regionals)) {
	        $regionals = ['date_only'=>FALSE,'time_only'=>TRUE];
	    } else {
            $regionals['date_only'] = FALSE;
            $regionals['time_only'] = TRUE;
        }//if(!is_array($regionals))
        return static::Datetime($value,$regionals);
	}//END public static function Time
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     * @throws \Exception
     */
    public static function TimeNosec($value,?array $regionals = NULL): ?string {
        $format = NApp::_GetTimeFormat(TRUE);
        if(strpos($format,NApp::_GetParam('time_separator').'s')!==FALSE) { $format = str_replace(NApp::_GetParam('time_separator').'s','',$format); }
	    if(!is_array($regionals)) { $regionals = ['format'=>$format]; }
        else { $regionals['format'] = $format; }
        return static::Datetime($value,$regionals);
	}//END public static function TimeNosec
    /**
     * @param $value
     * @return string|null
     */
    public static function HiTs($value): ?string {
        return timestamp_to_str_time(intval($value),FALSE,TRUE);
	}//END public static function HiTs
    /**
     * @param $value
     * @return string|null
     */
    public static function HisTs($value): ?string {
        return timestamp_to_str_time(intval($value),TRUE, TRUE);
	}//END public static function HisTs
    /**
     * @param $value
     * @return string|null
     */
    public static function DHisTs($value): ?string {
        return timestamp_to_str_duration(round($value,0),TRUE,TRUE);
	}//END public static function DHisTs
    /**
     * @param $value
     * @return string|null
     */
    public static function DIsTs($value): ?string {
        return timestamp_to_str_duration(round($value,0),TRUE);
	}//END public static function DIsTs
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function FsizeMB($value,?array $regionals = NULL): ?string {
	    if(!is_numeric($value)) { return $value; }
	    $decimalsNo = get_array_value($regionals,'decimals_no',3,'is_string');
	    $decimalSeparator = get_array_value($regionals,'decimal_separator',NApp::_GetParam('decimal_separator'),'is_string');
		$groupSeparator = get_array_value($regionals,'group_separator',NApp::_GetParam('group_separator'),'is_string');
		$value = $value / 1024 / 1024;
		return number_format($value,$decimalsNo,$decimalSeparator,$groupSeparator).' MB';
	}//END public static function FsizeMB
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function FsizeMB1($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>1]; }
        else { $regionals['decimals_no'] = 1; }
        return static::FsizeMB($value,$regionals);
    }//END public static function FsizeMB1
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function FsizeMB3($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>3]; }
        else { $regionals['decimals_no'] = 3; }
        return static::FsizeMB($value,$regionals);
    }//END public static function FsizeMB3
    /**
     * @param          $value
     * @param int|null $size
     * @return string|null
     */
    public static function LimitText($value,?int $size = NULL): ?string {
        return limit_text($value,$size);
    }//END public static function LimitText
    /**
     * @param $value
     * @return string|null
     */
    public static function LimitText255($value): ?string {
        return static::LimitText($value,255);
    }//END public static function LimitText
    /**
     * Format numeric value from NETopes format string
     *
     * @param        $value
     * @param string $numberFormat
     * @return string|null
     * @access public
     * @static
     */
	public static function NumberValue($value,string $numberFormat): ?string {
		if(!is_numeric($value)) { return NULL; }
		if(!strlen($numberFormat)) { return (string)$value; }
		$f_arr = explode('|',$numberFormat);
		if(count($f_arr)!=4) { return $value; }
		return number_format($value,$f_arr[0],$f_arr[1],$f_arr[2]).$f_arr[3];
	}//END public static function NumberValue
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Numeric($value,?array $regionals = NULL): ?string {
	    if(!is_numeric($value)) { return $value; }
	    $decimalsNo = get_array_value($regionals,'decimals_no',6,'is_string');
	    $decimalSeparator = get_array_value($regionals,'decimal_separator',NApp::_GetParam('decimal_separator'),'is_string');
		$groupSeparator = get_array_value($regionals,'group_separator',NApp::_GetParam('group_separator'),'is_string');
		return number_format($value,$decimalsNo,$decimalSeparator,$groupSeparator);
	}//END public static function Numeric
    /**
     * @param        $value
     * @param string $groupSeparator
     * @return string|null
     */
    public static function Integer($value,string $groupSeparator = ''): ?string {
	    $regionals = ['decimals_no'=>0,'decimal_separator'=>'','group_separator'=>$groupSeparator];
        return static::Numeric($value,$regionals);
    }//END public static function Integer
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal($value,?array $regionals = NULL): ?string {
        return static::Numeric($value,$regionals);
    }//END public static function Decimal
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal0($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>0]; }
        else { $regionals['decimals_no'] = 0; }
        return static::Numeric($value,$regionals);
    }//END public static function Decimal0
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal1($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>1]; }
        else { $regionals['decimals_no'] = 1; }
        return static::Numeric($value,$regionals);
    }//END public static function Decimal1
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal2($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>2]; }
        else { $regionals['decimals_no'] = 2; }
        return static::Numeric($value,$regionals);
    }//END public static function Decimal2
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal3($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>3]; }
        else { $regionals['decimals_no'] = 3; }
        return static::Numeric($value,$regionals);
    }//END public static function Decimal3
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal4($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>4]; }
        else { $regionals['decimals_no'] = 4; }
        return static::Numeric($value,$regionals);
    }//END public static function Decimal4
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @return string|null
     */
    public static function Decimal6($value,?array $regionals = NULL): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>6]; }
        else { $regionals['decimals_no'] = 6; }
        return static::Numeric($value,$regionals);
    }//END public static function Decimal6
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent0($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>0]; }
        else { $regionals['decimals_no'] = 0; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent0
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent1($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>1]; }
        else { $regionals['decimals_no'] = 1; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent1
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent2($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>2]; }
        else { $regionals['decimals_no'] = 2; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent2
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent2s($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>2]; }
        else { $regionals['decimals_no'] = 2; }
        $percentSufix = $htmlEntities ? '&#37;' : '%';
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent2s
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent4($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>4]; }
        else { $regionals['decimals_no'] = 4; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent4
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent0x100($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>0]; }
        else { $regionals['decimals_no'] = 0; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        if(is_numeric($value)) { $value = $value * 100; }
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent0x100
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent1x100($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>1]; }
        else { $regionals['decimals_no'] = 1; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        if(is_numeric($value)) { $value = $value * 100; }
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent1x100
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent2x100($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>2]; }
        else { $regionals['decimals_no'] = 2; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        if(is_numeric($value)) { $value = $value * 100; }
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent2x100
    /**
     * @param mixed      $value
     * @param array|null $regionals
     * @param bool       $htmlEntities
     * @return string|null
     */
    public static function Percent4x100($value,?array $regionals = NULL,bool $htmlEntities = FALSE): ?string {
        if(!is_array($regionals)) { $regionals = ['decimals_no'=>4]; }
        else { $regionals['decimals_no'] = 4; }
        $percentSufix = $htmlEntities ? '&nbsp;&#37;' : ' %';
        if(is_numeric($value)) { $value = $value * 100; }
        return static::Numeric($value,$regionals).$percentSufix;
    }//END public static function Percent4x100
}//END class FormatterAdapter