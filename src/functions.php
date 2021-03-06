<?php
/**
 * NETopes helper functions file
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2019 AdeoTEK Software SRL
 * @license    LICENSE.md
 * @version    3.4.1.0
 * @filesource
 */

/**
 * Check if path is absolute
 *
 * @param string Path to be checked
 * @return bool TRUE if path is absolute, FALSE otherwise
 */
function is_absolute_path(?string $path): bool {
    return strlen($path) && preg_match('/^\/|[a-zA-Z]:\\\\/',$path);
}//END function is_absolute_path
/**
 * Get short class name (without namespace)
 *
 * @param $class
 * @return  string Short class name
 */
function get_class_basename($class) {
    $fname=explode('\\',(is_object($class) ? get_class($class) : $class));
    return array_pop($fname);
}//END function get_class_basename
/**
 * Changes the case of the first letter of the string or for the first letter of each word in string.
 *
 * @param string|null $str String to be processed.
 * @param bool        $all If all param is set TRUE, all words in the string will be processed with ucfirst()
 *                         standard php function, otherwise just the first letter in string will be changed to upper.
 * @param bool        $lowercase
 * @param string|null $delimiter
 * @param bool        $remove_delimiter
 * @return  string|null The processed string.
 */
function custom_ucfirst(?string $str,bool $all=TRUE,bool $lowercase=TRUE,?string $delimiter=NULL,bool $remove_delimiter=FALSE): ?string {
    if(!strlen($str)) {
        return $str;
    }
    if($all) {
        $delimiter=strlen($delimiter) ? $delimiter : ' ';
        $str_arr=explode($delimiter,trim(($lowercase ? strtolower($str) : $str)));
        $result='';
        foreach($str_arr as $stri) {
            $result.=(strlen($result) && !$remove_delimiter ? $delimiter : '').ucfirst($stri);
        }
    } else {
        $result=ucfirst(trim(($lowercase ? strtolower($str) : $str)));
    }//if($all)
    return $result;
}//END function custom_ucfirst
/**
 * Converts a string of form [abcd_efgh_ijk] into a camelcase form [AbcdEfghIjk]
 *
 * @param string $string      String to be converted
 * @param bool   $lower_first Flag to indicate if the first char should be lower case
 * @param bool   $namespaced
 * @return string Returns the string in camelcase format or NULL on error
 */
function convert_to_camel_case(?string $string,bool $lower_first=FALSE,bool $namespaced=FALSE): ?string {
    if(!strlen($string)) {
        return $string;
    }
    if($namespaced) {
        $str_arr=explode('-',$string);
        $result=implode('\\',array_map(function($str) {
            return custom_ucfirst($str,TRUE,FALSE,'_',TRUE);
        },$str_arr));
    } else {
        $result=custom_ucfirst($string,TRUE,FALSE,'_',TRUE);
        if($lower_first) {
            $result=lcfirst($result);
        }
    }//if($namespaced)
    return $result;
}//END function convert_to_camel_case
/**
 * Converts a camelcase string to one of form [abcd_efgh_ijk]
 *
 * @param string $string String to be converted
 * @param bool   $upper  Flag to indicate if the result should be upper case
 * @return string Returns the string converted from camel case format or NULL on error
 */
function convert_from_camel_case($string,$upper=FALSE) {
    $result=str_replace('\\','-',$string);
    $result=preg_replace('/(?<=\\w)(?=[A-Z])/','_$1',$result);
    return ($upper ? strtoupper($result) : strtolower($result));
}//END function convert_from_camel_case
/**
 * Change string case
 *
 * @param mixed    $input
 * @param int|null $case
 * @return  mixed
 */
function change_case($input,?int $case=CASE_LOWER) {
    if(!is_string($input) || ($case!==CASE_LOWER && $case!==CASE_UPPER)) {
        return $input;
    }
    return ($case===CASE_UPPER ? strtoupper($input) : strtolower($input));
}//END function change_case
/**
 * description
 *
 * @param      $input
 * @param bool $recursive
 * @param int  $case
 * @return array
 */
function change_array_keys_case($input,bool $recursive=FALSE,int $case=CASE_LOWER) {
    if(!is_array($input) || ($case!==CASE_LOWER && $case!==CASE_UPPER)) {
        return $input;
    }
    if($recursive) {
        return array_map(function($item) use ($case) {
            if(is_array($item)) {
                $item=change_array_keys_case($item,$case);
            }
            return $item;
        },array_change_key_case($input,$case));
    }//if($recursive)
    return array_change_key_case($input,$case);
}//END function change_array_keys_case
/**
 * Validate variable value
 *
 * @param mixed  $value        Variable to be validated
 * @param mixed  $defaultValue Default value to be returned if param is not validated
 * @param string $validation   Validation type
 * @param bool   $checkOnly    Flag for setting validation as check only
 * @return  mixed Returns param value or default value if not validated
 *                             or TRUE/FALSE if $checkOnly is TRUE
 */
function validate_param($value,$defaultValue=NULL,?string $validation=NULL,bool $checkOnly=FALSE) {
    if(!strlen($validation)) {
        if($checkOnly) {
            return isset($value);
        }
        return (isset($value) ? $value : $defaultValue);
    }//if(!strlen($validation))
    if(substr($validation,0,1)=='?') {
        if(is_null($value)) {
            return NULL;
        }
        $validation=substr($validation,1);
    }//if(substr($validation,0,1)=='?')
    if($checkOnly) {
        switch(strtolower($validation)) {
            case 'true':
                return ($value ? TRUE : FALSE);
            case 'is_object':
                return is_object($value);
            case 'is_scalar':
                return is_scalar($value);
            case 'is_numeric':
                return is_numeric($value);
            case 'is_integer':
                return (is_numeric($value) && is_integer($value * 1));
            case 'is_float':
                return (is_numeric($value) && is_float($value * 1));
            case 'is_not0_numeric':
                return (is_numeric($value) && $value<>0);
            case 'is_not0_integer':
                return (is_numeric($value) && is_integer($value * 1) && $value<>0);
            case 'is_not0_float':
                return (is_numeric($value) && is_float($value * 1) && $value<>0);
            case 'is_array':
                return is_array($value);
            case 'is_notempty_array':
                return (is_array($value) && count($value));
            case 'is_string':
                return is_scalar($value);
            case 'is_notempty_string':
                return (is_scalar($value) && strlen(strval($value)));
            case 'trim_is_notempty_string':
                return (is_scalar($value) && strlen(trim(strval($value))));
            case 'is_bool':
            case 'is_boolean':
                return is_bool($value);
            case 'isset':
            case 'bool':
            default:
                return isset($value);
        }//END switch
    }//if($checkOnly)
    switch(strtolower($validation)) {
        case 'true':
            return ($value ? $value : $defaultValue);
        case 'is_object':
            return (is_object($value) ? $value : $defaultValue);
        case 'is_scalar':
            return is_scalar($value) ? $value : $defaultValue;
        case 'is_numeric':
            return (is_numeric($value) ? ($value + 0) : $defaultValue);
        case 'is_integer':
            return (is_numeric($value) && is_integer($value * 1) ? intval($value) : $defaultValue);
        case 'is_float':
            return (is_numeric($value) ? floatval($value) : $defaultValue);
        case 'is_not0_numeric':
            return (is_numeric($value) && $value<>0 ? ($value + 0) : $defaultValue);
        case 'is_not0_integer':
            return (is_numeric($value) && is_integer($value * 1) && intval($value)<>0 ? intval($value) : $defaultValue);
        case 'is_not0_float':
            return (is_numeric($value) && $value<>0 ? floatval($value) : $defaultValue);
        case 'is_array':
            return is_array($value) ? $value : $defaultValue;
        case 'is_notempty_array':
            return (is_array($value) && count($value) ? $value : $defaultValue);
        case 'is_string':
            return (is_scalar($value) ? strval($value) : $defaultValue);
        case 'is_notempty_string':
            return (is_scalar($value) && strlen(strval($value)) ? strval($value) : $defaultValue);
        case 'trim_is_notempty_string':
            return (is_scalar($value) && strlen(trim(strval($value))) ? strval($value) : $defaultValue);
        case 'is_bool':
        case 'is_boolean':
            return is_bool($value) ? $value : $defaultValue;
        case 'bool':
            return (isset($value) ? (strtolower($value)=='true' ? TRUE : (strtolower($value)=='false' ? FALSE : (bool)$value)) : $defaultValue);
        case 'isset':
        default:
            return (isset($value) ? $value : $defaultValue);
    }//END switch
}//END function validate_param
/**
 * Checks if a key exists in an array and validates its value
 * (if validation is set)
 *
 * @param mixed  $key        Key to be checked
 * @param array  $array      Array to be searched (passed by reference)
 * @param string $validation Validation type
 *                           (as implemented in validate_param function)
 * @return  bool Returns TRUE if $key exists in the $array or FALSE otherwise.
 *                           If $validation is not NULL, result is TRUE only if $array[$key] is validated
 */
function check_array_key($key,&$array,?string $validation=NULL) {
    if(!is_array($array) || is_null($key) || (!is_integer($key) && !is_string($key)) || !array_key_exists($key,$array)) {
        return FALSE;
    }
    if(!is_string($validation)) {
        return TRUE;
    }
    return validate_param($array[$key],NULL,$validation,TRUE);
}//END function check_array_key
/**
 * Extracts a value from a an multi-dimensional array
 *
 * @param mixed        $var          Params array
 *                                   (parsed as reference)
 * @param string|array $key          Key of the param to be returned
 * @param mixed        $defaultValue Default value to be returned if param is not validated
 * @param string       $validation   Validation type
 *                                   (as implemented in validate_param function)
 * @return  mixed Returns param value or default value if not validated
 */
function get_array_value(&$var,$key,$defaultValue=NULL,?string $validation=NULL) {
    if(is_array($key)) {
        if(!count($key)) {
            return $defaultValue;
        }
        $lKey=array_shift($key);
    } else {
        $lKey=$key;
        $key=[];
    }//if(is_array($key))
    if(is_null($lKey) || !(is_string($lKey) || is_integer($lKey))) {
        return $defaultValue;
    }
    if(is_array($var)) {
        if(!array_key_exists($lKey,$var)) {
            return $defaultValue;
        }
        if(is_array($key) && count($key)) {
            $value=get_array_value($var[$lKey],$key,$defaultValue,$validation);
        } else {
            $value=$var[$lKey];
        }//if(is_array($key) && count($key))
    } elseif(is_object($var) && method_exists($var,'toArray')) {
        $lparams=$var->toArray();
        if(!is_array($lparams) || !array_key_exists($lKey,$lparams)) {
            return $defaultValue;
        }
        if(is_array($key) && count($key)) {
            $value=get_array_value($lparams[$lKey],$key,$defaultValue,$validation);
        } else {
            $value=$lparams[$lKey];
        }//if(is_array($key) && count($key))
    } else {
        return $defaultValue;
    }//if(is_array($params))
    return validate_param($value,$defaultValue,$validation);
}//END function get_array_value
/**
 * This returns the element from certain level of the backtrace stack.
 *
 * @param integer $step  The backtrace step index to be returned, starting from 0 (default 1)
 * @param string  $param Type of the return.
 *                       Values can be: "function" and "class" for returning full array of the specified step
 *                       or "array" and empty string for returning an array containing only the name of the function/method
 *                       and the  class name (if there is one) of the specified step.
 * @return  array|string The full array or an array containing function/method and class names from the specified stop.
 */
function call_back_trace(int $step=1,?string $param='function') {
    $result=[];
    $trdata=debug_backtrace();
    if($step<0 || !array_key_exists($step,$trdata)) {
        return $result;
    }
    $lstep=$step + 1;
    switch(strtolower($param)) {
        case 'function':
        case 'class':
            $result=array_key_exists($param,$trdata[$lstep]) ? $trdata[$lstep][$param] : '';
            break;
        case 'array':
            $result=[
                'function'=>(array_key_exists('function',$trdata[$lstep]) ? $trdata[$lstep]['function'] : ''),
                'class'=>(array_key_exists('class',$trdata[$lstep]) ? $trdata[$lstep]['class'] : ''),
            ];
            break;
        case 'full':
            $result=$trdata[$lstep];
            break;
        default:
            $result=(array_key_exists('class',$trdata[$lstep]) ? $trdata[$lstep]['class'].'::' : '').(array_key_exists('function',$trdata[$lstep]) ? $trdata[$lstep]['function'] : '').(array_key_exists('file',$trdata[$lstep]) ? ' in file ['.$trdata[$lstep]['file'].']' : '').(array_key_exists('line',$trdata[$lstep]) ? ' on line ['.$trdata[$lstep]['line'].']' : '');
            break;
    }//END switch
    return $result;
}//END function call_back_trace
/**
 * @param      $var
 * @param bool $html_entities
 * @param bool $return
 * @param bool $utf8encode
 * @return string|null
 */
function vprint($var,$html_entities=FALSE,$return=FALSE,$utf8encode=FALSE) {
    if(is_string($var)) {
        $result=$var;
    } else {
        $result=print_r($var,TRUE);
    }
    if($html_entities) {
        $result=htmlentities($result,NULL,($utf8encode ? 'utf-8' : NULL));
    } else {
        if($utf8encode) {
            $result=utf8_encode($result);
        }
        $result='<pre>'.$result.'</pre>';
    }//if($html_entities)
    if($return===TRUE) {
        return $result;
    }
    echo $result;
    return NULL;
}//END function vprint
/**
 * @param mixed       $var
 * @param string|null $label
 */
function cli_print($var,?string $label=NULL) {
    echo (strlen($label) ? $label.': ' : '').print_r($var,TRUE).PHP_EOL;
}//END function cli_print
/**
 * @param string $key
 * @param array  $array
 * @return array
 */
function array_group_by(string $key,array $array): array {
    $grouped=[];
    foreach($array as $item) {
        $dKey=get_array_value($item,$key,NULL);
        if(isset($grouped[$dKey])) {
            $grouped[$dKey][]=$item;
        } else {
            $grouped[$dKey]=[$item];
        }
    }
    return $grouped;
}//END function array_group_by
/**
 * Return the first element in an array passing a given truth test.
 *
 * @param array         $array
 * @param mixed         $default
 * @param callable|null $callback
 * @return mixed
 */
function array_first(array $array,$default=NULL,?callable $callback=NULL) {
    if(is_null($callback)) {
        $firstValue=count($array) ? reset($array) : $default;
    } else {
        $firstValue=$default;
        foreach($array as $key=>$value) {
            if(call_user_func($callback,$value,$key)) {
                $firstValue=$value;
                break;
            }
        }
    }
    return $firstValue;
}//END function array_first
/**
 * @param array       $input
 * @param array       $output
 * @param string|null $keyPrefix
 * @param mixed|null  $value
 * @param string|null $parent
 * @return array
 */
function array_to_hierarchy(array $input,array $output=[],?string $keyPrefix=NULL,$value=NULL,?string $parent=NULL): array {
    if(!count($input)) {
        if(isset($value)) {
            $output[]=$value;
        }
        return $output;
    }
    $item=array_shift($input);
    $key=$keyPrefix.$item;
    if(!isset($output[(string)$key])) {
        $output[(string)$key]=[];
    }
    $output[(string)$key]=array_to_hierarchy($input,$output[(string)$key],$keyPrefix,$value,$parent);
    return $output;
}//END function array_to_hierarchy
/**
 * @param string        $key
 * @param array         $array
 * @param bool          $itemAsValue
 * @param string|null   $keyPrefix
 * @param string|null   $defaultGroup
 * @param callable|null $filter
 * @return array
 */
function array_group_by_hierarchical(string $key,array $array,bool $itemAsValue=FALSE,?string $keyPrefix=NULL,?string $defaultGroup=NULL,?callable $filter=NULL): array {
    $grouped=[];
    foreach($array as $item) {
        if(isset($filter) && !$filter($item)) {
            continue;
        }
        $dKey=get_array_value($item,$key,$defaultGroup);
        if(!strlen($dKey)) {
            if($itemAsValue) {
                $grouped[]=$item;
            }
            continue;
        }
        $grouped=array_to_hierarchy(explode('-',trim($dKey,$keyPrefix)),$grouped,$keyPrefix,($itemAsValue ? $item : NULL));
    }//END foreach
    return $grouped;
}//END function array_group_by